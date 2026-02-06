<?php

namespace App\Http\Controllers;

use App\Services\SlotService;
use App\Services\PoSearchService;
use App\Services\SlotConflictService;
use App\Services\SlotFilterService;
use App\Services\TimeCalculationService;
use App\Services\SlotReceiptReconciliationService;
use App\Exports\SlotsExport;
use App\Models\Slot;
use App\Models\SlotPoItem;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\SlotStoreRequest;
use App\Http\Requests\SlotArrivalStoreRequest;
use App\Http\Requests\SlotStartStoreRequest;
use App\Http\Requests\SlotCompleteStoreRequest;
use App\Http\Requests\SlotCancelStoreRequest;

class SlotController extends Controller
{
    public function __construct(
        private readonly SlotService $slotService,
        private readonly PoSearchService $poSearchService,
        private readonly SlotConflictService $conflictService,
        private readonly SlotFilterService $filterService,
        private readonly TimeCalculationService $timeService,
        private readonly SlotReceiptReconciliationService $receiptReconciliationService
    ) {
    }

    public function ajaxPoSearch(Request $request)
    {
        $q = (string) $request->query('q', '');

        // Prefer SAP search for autocomplete responsiveness
        $results = $this->poSearchService->searchPoSapOnly($q, 20);
        if (empty($results)) {
            // Fallback to hybrid search if SAP search fails
            $results = $this->poSearchService->searchPo($q);
        }

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }



    public function ajaxPoDetail(string $poNumber)
    {
        try {
            $poNumber = trim($poNumber);
            if ($poNumber === '') {
                return response()->json(['success' => false, 'message' => 'PO/DO number is required']);
            }

            $po = $this->poSearchService->getPoDetail($poNumber);

            if (!$po) {
                return response()->json(['success' => false, 'message' => 'PO/DO not found']);
            }

            $po = $this->withRemainingQty($po);

            return response()->json(['success' => true, 'data' => $po]);
        } catch (\Throwable $e) {
            Log::warning('ajaxPoDetail failed', [
                'poNumber' => $poNumber,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to load PO/DO detail'], 200);
        }
    }

    public function searchSuggestions(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        $like = '%' . $q . '%';

        $rows = DB::table('slots as s')
            ->join('md_warehouse as w', 's.warehouse_id', '=', 'w.id')
            ->where(function ($sub) use ($like) {
                $sub->where('s.po_number', 'like', $like)
                    ->orWhere('s.mat_doc', 'like', $like)
                    ->orWhere('s.vendor_name', 'like', $like);
            })
            ->where('s.status', '<>', 'completed')
            ->select([
                's.po_number as truck_number',
                's.mat_doc',
                's.vendor_name',
                'w.wh_name as warehouse_name',
            ])
            ->orderByRaw("CASE
                WHEN s.po_number LIKE ? THEN 1
                WHEN COALESCE(s.mat_doc, '') LIKE ? THEN 2
                WHEN s.vendor_name LIKE ? THEN 3
                ELSE 4
            END", [$q . '%', $q . '%', $q . '%'])
            ->orderBy('s.po_number')
            ->limit(10)
            ->get();

        $highlight = function (?string $text) use ($q): string {
            $text = (string) ($text ?? '');
            if ($text === '') {
                return '';
            }

            $pos = stripos($text, $q);
            if ($pos === false) {
                return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            }

            $before = substr($text, 0, $pos);
            $match = substr($text, $pos, strlen($q));
            $after = substr($text, $pos + strlen($q));

            return htmlspecialchars($before, ENT_QUOTES, 'UTF-8')
                . '<strong>' . htmlspecialchars($match, ENT_QUOTES, 'UTF-8') . '</strong>'
                . htmlspecialchars($after, ENT_QUOTES, 'UTF-8');
        };

        $results = [];
        $seen = [];

        foreach ($rows as $row) {
            $truck = trim((string) ($row->truck_number ?? ''));
            $matDoc = trim((string) ($row->mat_doc ?? ''));
            $vendor = trim((string) ($row->vendor_name ?? ''));

            // 1. Truck - Vendor
            if ($truck !== '' && $vendor !== '') {
                $text = $truck . ' - ' . $vendor;
                if (! in_array($text, $seen, true)) {
                    $seen[] = $text;
                    $results[] = [
                        'text' => $text,
                        'highlighted' => $highlight($text),
                    ];
                }
            }

            // 2. Truck only
            if ($truck !== '' && ! in_array($truck, $seen, true)) {
                $seen[] = $truck;
                $results[] = [
                    'text' => $truck,
                    'highlighted' => $highlight($truck),
                ];
            }

            // 3. MAT DOC
            if ($matDoc !== '' && ! in_array($matDoc, $seen, true)) {
                $seen[] = $matDoc;
                $results[] = [
                    'text' => $matDoc,
                    'highlighted' => $highlight($matDoc),
                ];
            }

            // 4. Vendor only
            if ($vendor !== '' && ! in_array($vendor, $seen, true)) {
                $seen[] = $vendor;
                $results[] = [
                    'text' => $vendor,
                    'highlighted' => $highlight($vendor),
                ];
            }

            if (count($results) >= 10) {
                break;
            }
        }

        return response()->json(array_slice($results, 0, 10));
    }

    private function getTruckTypeOptions(): array
    {
        return $this->timeService->getTruckTypeOptions();
    }

    private function loadSlotDetailRow(int $slotId): ?object
    {
        return DB::table('slots as s')
            ->join('md_warehouse as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('md_users as ru', 's.requested_by', '=', 'ru.id')
            ->leftJoin('md_gates as pg', 's.planned_gate_id', '=', 'pg.id')
            ->leftJoin('md_gates as ag', 's.actual_gate_id', '=', 'ag.id')
            ->leftJoin('md_warehouse as wpg', 'pg.warehouse_id', '=', 'wpg.id')
            ->leftJoin('md_warehouse as wag', 'ag.warehouse_id', '=', 'wag.id')
            ->leftJoin('md_truck as td', 's.truck_type', '=', 'td.truck_type')
            ->where('s.id', $slotId)
            ->select([
                's.*',
                's.po_number as po_number',
                's.po_number as truck_number',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
                's.vendor_name',
                'pg.gate_number as planned_gate_number',
                'ag.gate_number as actual_gate_number',
                'wpg.wh_code as planned_gate_warehouse_code',
                'wag.wh_code as actual_gate_warehouse_code',
                'td.target_duration_minutes',
            ])
            ->first();
    }

    private function buildGateLabel(?string $warehouseCode, ?string $gateNumber): string
    {
        $wh = strtoupper(trim((string) $warehouseCode));
        $gateLabel = $this->slotService->getGateDisplayName($wh, (string) $gateNumber);
        if ($wh !== '' && $gateLabel !== '-') {
            return $wh . ' - ' . $gateLabel;
        }
        return $gateLabel;
    }


    private function minutesDiff(?string $start, ?string $end): ?int
    {
        return $this->timeService->minutesDiff($start, $end);
    }

    private function isLateByPlannedStart(?string $plannedStart, string $actualTime): bool
    {
        return $this->timeService->isLateByPlannedStart($plannedStart, $actualTime);
    }

    private function getPlannedDurationForStart(object $slot): int
    {
        return $this->timeService->getPlannedDurationForStart($slot);
    }

    private function findInProgressConflicts(int $actualGateId, int $excludeSlotId = 0): array
    {
        return $this->conflictService->findInProgressConflicts($actualGateId, $excludeSlotId);
    }

    private function buildConflictLines(array $slotIds): array
    {
        return $this->conflictService->buildConflictMessage($slotIds);
    }

    public function index(Request $request)
    {
        // Validate and sanitize inputs
        $rawSort = $request->query('sort', []);
        $rawDir = $request->query('dir', []);

        $sorts = is_array($rawSort) ? $rawSort : [trim((string) $rawSort)];
        $dirs = is_array($rawDir) ? $rawDir : [trim((string) $rawDir)];

        $sorts = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $sorts), fn ($v) => $v !== ''));
        $dirs = array_values(array_map(fn ($v) => $this->filterService->validateSortDirection(trim((string) $v)), $dirs));

        // Backward-compatible single sort/dir values (used by some views/JS)
        $sort = $sorts[0] ?? '';
        $dir = $dirs[0] ?? 'desc';
        $pageSize = $this->filterService->validatePageSize($request->query('page_size', '10'));

        // Build filtered query
        $query = $this->filterService->filterSlots($request);

        // Apply sorting
        $query = $this->filterService->applySortingMulti($query, $sorts, $dirs);

        // Apply page size limit
        $query = $this->filterService->applyPageSize($query, $pageSize);

        $slots = $query->get();

        // Note: Blocking risk is now recalculated in background by RecalculateBlockingRiskJob
        // which runs every 5 minutes via Laravel scheduler.
        // This removes the N+1 query problem that was causing 20+ second page loads.
        // The blocking_risk value from database is used directly (set in buildBaseQuery select).

        // For display purposes, map blocking_risk to 'blocking' property
        foreach ($slots as $slot) {
            $slot->blocking = (int) ($slot->blocking_risk ?? 0);
        }

        // Get filter options
        $filterOptions = $this->filterService->getFilterOptions();

        $warehouses = $filterOptions['warehouses'] ?? [];
        foreach ($warehouses as $wh) {
            if (is_object($wh) && !isset($wh->name) && isset($wh->wh_name)) {
                $wh->name = $wh->wh_name;
            }
        }

        // Extract filter values for view
        $warehouseValues = array_values(array_filter((array) $request->query('warehouse_id', []), fn ($v) => (string) $v !== ''));
        $gateValues = array_values(array_filter((array) $request->query('gate', []), fn ($v) => (string) $v !== ''));
        $statusValues = array_values(array_filter((array) $request->query('status', []), fn ($v) => (string) $v !== ''));
        $dirValues = array_values(array_filter((array) $request->query('direction', []), fn ($v) => (string) $v !== ''));
        $lateValues = array_values(array_filter((array) $request->query('late', []), fn ($v) => (string) $v !== ''));
        $blockingValues = array_values(array_filter((array) $request->query('blocking', []), fn ($v) => (string) $v !== ''));
        $targetStatusValues = array_values(array_filter((array) $request->query('target_status', []), fn ($v) => (string) $v !== ''));

        return view('slots.index', [
            'slots' => $slots,
            'pageTitle' => 'Slots',
            'search' => trim($request->query('q', '')),
            'truck' => trim($request->query('truck', '')),
            'vendor' => trim($request->query('vendor', '')),
            'mat_doc' => trim($request->query('mat_doc', '')),
            'arrival_from' => trim($request->query('arrival_from', '')),
            'arrival_to' => trim($request->query('arrival_to', '')),
            'lead_time_min' => trim($request->query('lead_time_min', '')),
            'lead_time_max' => trim($request->query('lead_time_max', '')),
            'targetStatusFilter' => $targetStatusValues,
            'sort' => $sort,
            'dir' => $dir,
            'sorts' => $sorts,
            'dirs' => $dirs,
            'date_from' => trim($request->query('date_from', '')),
            'date_to' => trim($request->query('date_to', '')),
            'warehouseFilter' => $warehouseValues,
            'gateFilter' => $gateValues,
            'statusFilter' => $statusValues,
            'directionFilter' => $dirValues,
            'lateFilter' => $lateValues,
            'blockingFilter' => $blockingValues,
            'pageSize' => $pageSize,
            'warehouses' => $warehouses,
            'gates' => $filterOptions['gates'],
        ]);

    }

    public function create()
    {
        $warehouses = DB::table('md_warehouse')
            ->select(['id', 'wh_name as name', 'wh_code as code'])
            ->orderBy('wh_name')
            ->get();
        $gates = DB::table('md_gates as g')
            ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id')
            ->where('g.is_active', true)
            ->orderBy('w.wh_name')
            ->orderBy('g.gate_number')
            ->select(['g.*', 'w.wh_name as warehouse_name', 'w.wh_code as warehouse_code'])
            ->get();

        $truckTypes = $this->getTruckTypeOptions();
        $truckTypeDurations = DB::table('md_truck')
            ->orderBy('truck_type')
            ->pluck('target_duration_minutes', 'truck_type')
            ->all();

        $vendors = [];

        return view('slots.create', [
            'warehouses' => $warehouses,
            'gates' => $gates,
            'vendors' => $vendors,
            'truckTypes' => $truckTypes,
            'truckTypeDurations' => $truckTypeDurations,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'po_number' => 'required|string|max:12',
            'po_items' => 'required|array',
            'po_items.*.qty' => 'nullable|numeric|min:0',
            'direction' => 'required|in:inbound,outbound',
            'truck_type' => 'required|string|max:100',
            'planned_gate_id' => 'required|integer|exists:md_gates,id',
            'planned_start' => 'required|string',
            'planned_duration' => 'required|integer|min:1|max:1440',
            'vehicle_number_snap' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:50',
            'driver_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
            'coa_pdf' => 'required|file|mimes:pdf|max:5120',
        ]);

        $truckNumber = trim((string) ($request->input('po_number', $request->input('truck_number', ''))));
        $direction = (string) $request->input('direction', '');
        $plannedGateId = $request->input('planned_gate_id') !== null && (string) $request->input('planned_gate_id') !== '' ? (int) $request->input('planned_gate_id') : null;
        $plannedStart = (string) $request->input('planned_start', '');
        $plannedDurationMinutes = (int) $request->input('planned_duration', 60);
        $truckType = trim((string) $request->input('truck_type', ''));

        $vehicleNumber = trim((string) $request->input('vehicle_number_snap', ''));
        $driverName = trim((string) $request->input('driver_name', ''));
        $driverNumber = trim((string) $request->input('driver_number', ''));
        $notes = trim((string) $request->input('notes', ''));
        $vehicleNumber = trim((string) $request->input('vehicle_number_snap', ''));
        $driverName = trim((string) $request->input('driver_name', ''));
        $driverNumber = trim((string) $request->input('driver_number', ''));
        $notes = trim((string) $request->input('notes', ''));

        if (! $plannedGateId) {
            return back()->withInput()->with('error', 'Gate is required');
        }

        $gateRow = DB::table('md_gates')
            ->where('id', $plannedGateId)
            ->where('is_active', true)
            ->select(['id', 'warehouse_id'])
            ->first();
        if (! $gateRow) {
            return back()->withInput()->with('error', 'Selected gate is not active');
        }
        $warehouseId = (int) ($gateRow->warehouse_id ?? 0);

        $selectedItems = (array) $request->input('po_items', []);
        $hasQty = false;
        foreach ($selectedItems as $it) {
            $qty = isset($it['qty']) ? (float) $it['qty'] : 0.0;
            if ($qty > 0) {
                $hasQty = true;
                break;
            }
        }
        if (!$hasQty) {
            return back()->withInput()->with('error', 'Please input at least one PO item quantity for this slot.');
        }

        $poNumber = $truckNumber;
        $poDetail = $this->poSearchService->getPoDetail($poNumber);
        if (!$poDetail) {
            return back()->withInput()->with('error', 'PO/DO not found in SAP.');
        }
        $poDetail = $this->withRemainingQty($poDetail);

        $remainingMap = [];
        foreach (($poDetail['items'] ?? []) as $it) {
            if (!is_array($it)) continue;
            $itemNo = trim((string) ($it['item_no'] ?? ''));
            if ($itemNo === '') continue;
            $remainingMap[$itemNo] = (float) ($it['remaining_qty'] ?? 0);
        }

        foreach ($selectedItems as $itemNo => $it) {
            $itemNo = trim((string) $itemNo);
            if ($itemNo === '') continue;
            $qty = isset($it['qty']) ? (float) $it['qty'] : 0.0;
            if ($qty <= 0) continue;
            $remain = (float) ($remainingMap[$itemNo] ?? 0);
            if ($remain <= 0) {
                return back()->withInput()->with('error', "Item {$itemNo} has no remaining quantity.");
            }
            if ($qty - $remain > 0.000001) {
                return back()->withInput()->with('error', "Item {$itemNo} quantity exceeds remaining ({$remain}).");
            }
        }

        if ($truckNumber !== '' && strlen($truckNumber) > 12) {
            return back()->withInput()->withErrors(['po_number' => 'PO/DO number max 12 karakter']);
        }

        if ($truckNumber === '' || $plannedStart === '' || $direction === '') {
            return back()->withInput()->with('error', 'PO/DO number, direction, gate, and planned start are required');
        }

        try {
            $plannedStartDt = new DateTime($plannedStart);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Invalid planned start time');
        }

        $plannedEndDt = clone $plannedStartDt;
        $plannedEndDt->modify('+' . (int) $plannedDurationMinutes . ' minutes');

        if ($plannedGateId !== null) {
            $laneGroup = $this->slotService->getGateLaneGroup($plannedGateId);
            $laneGateIds = $laneGroup ? $this->slotService->getGateIdsByLaneGroup($laneGroup) : [$plannedGateId];
            if (empty($laneGateIds)) {
                $laneGateIds = [$plannedGateId];
            }

            $startStr = $plannedStartDt->format('Y-m-d H:i:s');
            $endStr = $plannedEndDt->format('Y-m-d H:i:s');

            $overlapCount = (int) DB::table('slots')
                ->whereIn('planned_gate_id', $laneGateIds)
                ->whereIn('status', ['scheduled', 'waiting', 'in_progress'])
                ->whereRaw('? < ' . $this->slotService->getDateAddExpression('planned_start', 'planned_duration'), [$startStr])
                ->whereRaw('? > planned_start', [$endStr])
                ->count();

            if ($overlapCount > 0) {
                return back()->withInput()->with('error', 'Planned time overlaps with another slot on the same lane');
            }

            $bcCheck = $this->slotService->validateWh2BcPlannedWindow($plannedGateId, $plannedStartDt, $plannedEndDt, 0);
            if (empty($bcCheck['ok'])) {
                return back()->withInput()->with('error', (string) ($bcCheck['message'] ?? 'Invalid planned window'));
            }
        }

        if (! $plannedGateId) {
            return back()->withInput()->with('error', 'Gate penuh / tidak tersedia untuk jadwal ini. Silakan pilih gate atau waktu lain.');
        }

        $slotId = 0;
        DB::transaction(function () use (&$slotId, $truckNumber, $direction, $warehouseId, $plannedGateId, $plannedStart, $plannedDurationMinutes, $truckType, $vehicleNumber, $driverName, $driverNumber, $notes, $selectedItems, $poDetail) {
            $now = date('Y-m-d H:i:s');
            $ticket = $this->slotService->generateTicketNumber($warehouseId, $plannedGateId);
            $slotId = (int) DB::table('slots')->insertGetId([
                'po_number' => $truckNumber,
                'direction' => $direction,
                'warehouse_id' => $warehouseId,
                'vendor_code' => $poDetail['vendor_code'] ?? null,
                'vendor_name' => $poDetail['vendor_name'] ?? null,
                'vendor_type' => $poDetail['vendor_type'] ?? null,
                'planned_gate_id' => $plannedGateId,
                'planned_start' => $plannedStart,
                'planned_duration' => $plannedDurationMinutes,
                'truck_type' => $truckType !== '' ? $truckType : null,
                'vehicle_number_snap' => $vehicleNumber !== '' ? $vehicleNumber : null,
                'driver_name' => $driverName !== '' ? $driverName : null,
                'driver_number' => $driverNumber !== '' ? $driverNumber : null,
                'late_reason' => $notes !== '' ? $notes : null,
                'ticket_number' => $ticket,
                'status' => 'scheduled',
                'slot_type' => 'planned',
                'created_by' => Auth::id(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($slotId > 0 && Schema::hasTable('slot_po_items')) {
                $itemsDetailMap = [];
                foreach (($poDetail['items'] ?? []) as $it) {
                    if (!is_array($it)) continue;
                    $itemNo = trim((string) ($it['item_no'] ?? ''));
                    if ($itemNo === '') continue;
                    $itemsDetailMap[$itemNo] = $it;
                }

                foreach ($selectedItems as $itemNo => $it) {
                    $itemNo = trim((string) $itemNo);
                    if ($itemNo === '') continue;
                    $qty = isset($it['qty']) ? (float) $it['qty'] : 0.0;
                    if ($qty <= 0) continue;

                    $detailIt = $itemsDetailMap[$itemNo] ?? [];
                    SlotPoItem::create([
                        'slot_id' => $slotId,
                        'po_number' => $truckNumber,
                        'item_no' => $itemNo,
                        'material_code' => $detailIt['material'] ?? null,
                        'material_name' => $detailIt['description'] ?? null,
                        'uom' => $detailIt['uom'] ?? null,
                        'qty_booked' => $qty,
                    ]);
                }
            }

            if ($slotId > 0) {
                $this->slotService->logActivity($slotId, 'status_change', 'Slot Created');
            }
        });

        if ($slotId <= 0) {
            return back()->withInput()->with('error', 'Failed to create slot');
        }

        $updates = [];
        if ($request->hasFile('coa_pdf')) {
            $coaFile = $request->file('coa_pdf');
            $coaName = 'coa_' . $slotId . '_' . time() . '.pdf';
            $coaPath = $coaFile->storeAs('booking-documents/' . $slotId, $coaName, 'public');
            $updates['coa_path'] = $coaPath;
        }
        if (!empty($updates)) {
            DB::table('slots')->where('id', $slotId)->update($updates);
        }

        // Calculate blocking risk immediately after creation (real-time accuracy)
        $blockingRisk = $this->slotService->calculateBlockingRisk(
            $warehouseId,
            $plannedGateId,
            $plannedStart,
            $plannedDurationMinutes,
            $slotId
        );
        DB::table('slots')->where('id', $slotId)->update([
            'blocking_risk' => $blockingRisk,
            'blocking_risk_cached_at' => now(),
        ]);

        return redirect()->route('slots.index')->with('success', 'Slot created successfully');
    }

    public function edit(int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Slot not found');
        }

        if (((string) ($slot->slot_type ?? 'planned')) !== 'planned' || (string) ($slot->status ?? '') !== 'scheduled') {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Only scheduled planned slots can be edited');
        }

        $warehouses = DB::table('md_warehouse')
            ->select(['id', 'wh_name as name', 'wh_code as code'])
            ->orderBy('wh_name')
            ->get();

        $vendors = [];

        $gates = DB::table('md_gates as g')
            ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id')
            ->where('g.is_active', true)
            ->orderBy('w.wh_name')
            ->orderBy('g.gate_number')
            ->select(['g.*', 'w.wh_name as warehouse_name', 'w.wh_code as warehouse_code', 'w.id as warehouse_id'])
            ->get();

        $truckTypes = $this->getTruckTypeOptions();
        $truckTypeDurations = DB::table('md_truck')
            ->orderBy('truck_type')
            ->pluck('target_duration_minutes', 'truck_type')
            ->all();

        return view('slots.edit', [
            'slot' => $slot,
            'warehouses' => $warehouses,
            'vendors' => $vendors,
            'gates' => $gates,
            'truckTypes' => $truckTypes,
            'truckTypeDurations' => $truckTypeDurations,
        ]);
    }

    public function update(Request $request, int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Slot not found');
        }

        if (((string) ($slot->slot_type ?? 'planned')) !== 'planned' || (string) ($slot->status ?? '') !== 'scheduled') {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Only scheduled planned slots can be edited');
        }

        $request->validate([
            'po_number' => 'required|string|max:12',
            'direction' => 'required|in:inbound,outbound',
            'truck_type' => 'required|string|max:100',
            'vendor_id' => 'nullable|integer|exists:business_partner,id',
            'planned_gate_id' => 'required|integer|exists:md_gates,id',
            'planned_start' => 'required|string',
            'planned_duration' => 'required|integer|min:1|max:1440',
            'vehicle_number_snap' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:50',
            'driver_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
            'coa_pdf' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        if (! $request->hasFile('coa_pdf') && trim((string) ($slot->coa_path ?? '')) === '') {
            return back()->withInput()->withErrors(['coa_pdf' => 'COA (PDF) is required.']);
        }

        $truckNumber = trim((string) ($request->input('po_number', $request->input('truck_number', ''))));
        $direction = (string) $request->input('direction', '');
        $vendorId = $request->input('vendor_id') !== null && (string) $request->input('vendor_id') !== '' ? (int) $request->input('vendor_id') : null;
        $plannedGateId = $request->input('planned_gate_id') !== null && (string) $request->input('planned_gate_id') !== '' ? (int) $request->input('planned_gate_id') : null;
        $plannedStart = (string) $request->input('planned_start', '');
        $plannedDurationMinutes = (int) $request->input('planned_duration', 60);
        $truckType = trim((string) $request->input('truck_type', ''));

        $vehicleNumber = trim((string) $request->input('vehicle_number_snap', ''));
        $driverName = trim((string) $request->input('driver_name', ''));
        $driverNumber = trim((string) $request->input('driver_number', ''));
        $notes = trim((string) $request->input('notes', ''));

        if (! $plannedGateId) {
            return back()->withInput()->with('error', 'Gate is required');
        }

        $gateRow = DB::table('md_gates')
            ->where('id', $plannedGateId)
            ->where('is_active', true)
            ->select(['id', 'warehouse_id'])
            ->first();
        if (! $gateRow) {
            return back()->withInput()->with('error', 'Selected gate is not active');
        }
        $warehouseId = (int) ($gateRow->warehouse_id ?? 0);

        if ($truckNumber !== '' && strlen($truckNumber) > 12) {
            return back()->withInput()->withErrors(['po_number' => 'PO/DO number max 12 karakter']);
        }

        if ($plannedGateId !== null) {
            $gate = DB::table('md_gates')->where('id', $plannedGateId)->where('is_active', true)->select(['warehouse_id'])->first();
            if (! $gate || (int) ($gate->warehouse_id ?? 0) !== $warehouseId) {
                return back()->withInput()->with('error', 'Selected gate does not belong to chosen warehouse or is inactive');
            }
        }

        try {
            $plannedStartDt = new DateTime($plannedStart);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Invalid planned start time');
        }

        $plannedEndDt = clone $plannedStartDt;
        $plannedEndDt->modify('+' . (int) $plannedDurationMinutes . ' minutes');

        if ($plannedGateId !== null) {
            $laneGroup = $this->slotService->getGateLaneGroup($plannedGateId);
            $laneGateIds = $laneGroup ? $this->slotService->getGateIdsByLaneGroup($laneGroup) : [$plannedGateId];
            if (empty($laneGateIds)) {
                $laneGateIds = [$plannedGateId];
            }

            $startStr = $plannedStartDt->format('Y-m-d H:i:s');
            $endStr = $plannedEndDt->format('Y-m-d H:i:s');

            $overlapCount = (int) DB::table('slots')
                ->where('id', '<>', $slotId)
                ->whereIn('planned_gate_id', $laneGateIds)
                ->whereIn('status', ['scheduled', 'waiting', 'in_progress'])
                ->whereRaw('? < ' . $this->slotService->getDateAddExpression('planned_start', 'planned_duration'), [$startStr])
                ->whereRaw('? > planned_start', [$endStr])
                ->count();

            if ($overlapCount > 0) {
                return back()->withInput()->with('error', 'Planned time overlaps with another slot on the same lane');
            }

            $bcCheck = $this->slotService->validateWh2BcPlannedWindow($plannedGateId, $plannedStartDt, $plannedEndDt, $slotId);
            if (empty($bcCheck['ok'])) {
                return back()->withInput()->with('error', (string) ($bcCheck['message'] ?? 'Invalid planned window'));
            }
        }

        DB::transaction(function () use ($slotId, $truckNumber, $direction, $warehouseId, $vendorId, $plannedGateId, $plannedStart, $plannedDurationMinutes, $truckType, $vehicleNumber, $driverName, $driverNumber, $notes) {
            $truck = DB::table('po')->where('po_number', $truckNumber)->select(['id'])->first();
            if ($truck) {
                $truckId = (int) $truck->id;
            } else {
                $truckId = (int) DB::table('po')->insertGetId([
                    'po_number' => $truckNumber,
                ]);
            }

            DB::table('slots')->where('id', $slotId)->update([
                'po_id' => $truckId,
                'direction' => $direction,
                'warehouse_id' => $warehouseId,
                'bp_id' => $vendorId,
                'planned_gate_id' => $plannedGateId,
                'planned_start' => $plannedStart,
                'planned_duration' => $plannedDurationMinutes,
                'truck_type' => $truckType,
                'vehicle_number_snap' => $vehicleNumber !== '' ? $vehicleNumber : null,
                'driver_name' => $driverName !== '' ? $driverName : null,
                'driver_number' => $driverNumber !== '' ? $driverNumber : null,
                'late_reason' => $notes !== '' ? $notes : null,
                'updated_at' => now(),
            ]);

            $this->slotService->logActivity($slotId, 'status_change', 'Slot Updated');
        });

        if ($request->hasFile('coa_pdf')) {
            $coaFile = $request->file('coa_pdf');
            $coaName = 'coa_' . $slotId . '_' . time() . '.pdf';
            $coaPath = $coaFile->storeAs('booking-documents/' . $slotId, $coaName, 'public');
            DB::table('slots')->where('id', $slotId)->update([
                'coa_path' => $coaPath,
                'updated_at' => now(),
            ]);
        }

        // Calculate blocking risk immediately after update
        $blockingRisk = $this->slotService->calculateBlockingRisk(
            $warehouseId,
            $plannedGateId,
            $plannedStart,
            $plannedDurationMinutes,
            $slotId
        );
        DB::table('slots')->where('id', $slotId)->update([
            'blocking_risk' => $blockingRisk,
            'blocking_risk_cached_at' => now(),
        ]);

        return redirect()->route('slots.index')->with('success', 'Slot updated successfully');
    }

    private function toFloatQty($v): float
    {
        if ($v === null) return 0.0;
        if (is_numeric($v)) return (float) $v;
        $s = trim((string) $v);
        if ($s === '') return 0.0;
        $s = str_replace([' ', ','], ['', '.'], $s);
        if (!is_numeric($s)) return 0.0;
        return (float) $s;
    }

    private function getBookedQtyByItemNo(string $poNumber): array
    {
        $poNumber = trim($poNumber);
        if ($poNumber === '') {
            return [];
        }

        if (!Schema::hasTable('slot_po_items')) {
            return [];
        }

        try {
            $rows = DB::table('slot_po_items as spi')
                ->join('slots as s', 's.id', '=', 'spi.slot_id')
                ->where('spi.po_number', $poNumber)
                ->whereNotIn('s.status', [Slot::STATUS_CANCELLED, Slot::STATUS_REJECTED])
                ->groupBy('spi.item_no')
                ->select([
                    'spi.item_no',
                    DB::raw('SUM(spi.qty_booked) as qty_booked'),
                ])
                ->get();
        } catch (\Throwable $e) {
            return [];
        }

        $out = [];
        foreach ($rows as $r) {
            $key = trim((string) ($r->item_no ?? ''));
            if ($key === '') {
                continue;
            }
            $out[$key] = (float) ($r->qty_booked ?? 0);
        }

        return $out;
    }

    private function withRemainingQty(array $po): array
    {
        $poNumber = trim((string) ($po['po_number'] ?? ''));
        $bookedByItem = $poNumber !== '' ? $this->getBookedQtyByItemNo($poNumber) : [];

        $items = is_array($po['items'] ?? null) ? $po['items'] : [];
        $hasRemaining = false;
        foreach ($items as $idx => $it) {
            if (!is_array($it)) {
                continue;
            }
            $itemNo = trim((string) ($it['item_no'] ?? ''));
            $qtyPo = $this->toFloatQty($it['qty'] ?? null);
            $qtyGr = $this->toFloatQty($it['qty_gr_total'] ?? null);
            $booked = $itemNo !== '' ? (float) ($bookedByItem[$itemNo] ?? 0) : 0.0;
            $remaining = $qtyPo - $qtyGr - $booked;
            if ($remaining < 0) {
                $remaining = 0.0;
            }
            if ($remaining > 0) {
                $hasRemaining = true;
            }
            $it['qty_booked'] = $booked;
            $it['remaining_qty'] = $remaining;
            $items[$idx] = $it;
        }

        $po['items'] = $items;
        $po['has_remaining'] = $hasRemaining;
        return $po;
    }

    public function show(int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Slot not found');
        }

        $slotItems = SlotPoItem::where('slot_id', $slotId)
            ->orderBy('item_no')
            ->get();

        $poNumber = trim((string) ($slot->po_number ?? ''));
        if ($poNumber !== '') {
            try {
                $poDetail = $this->poSearchService->getPoDetail($poNumber);
                if (is_array($poDetail)) {
                    $vn = trim((string) ($poDetail['vendor_name'] ?? ''));
                    if ($vn !== '') {
                        $slot->vendor_name = $vn;
                    }
                }
            } catch (\Throwable $e) {
                // ignore SAP errors on detail view
            }
        }

        $slotType = (string) ($slot->slot_type ?? 'planned');
        $isUnplanned = $slotType === 'unplanned';

        // Calculate blocking risk in real-time for detail page (always accurate)
        if ((string) ($slot->status ?? '') !== 'cancelled' && !$isUnplanned) {
            $currentRiskLevel = $this->slotService->calculateBlockingRisk(
                (int) $slot->warehouse_id,
                $slot->planned_gate_id ? (int) $slot->planned_gate_id : null,
                (string) ($slot->planned_start ?? ''),
                (int) ($slot->planned_duration ?? 0),
                (int) $slot->id
            );
            $slot->blocking = $currentRiskLevel;

            // Also update the database so list page shows accurate value
            DB::table('slots')->where('id', $slotId)->update([
                'blocking_risk' => $currentRiskLevel,
                'blocking_risk_cached_at' => now(),
            ]);
        }

        if (! $isUnplanned && (string) ($slot->status ?? '') !== 'cancelled' && empty($slot->ticket_number)) {
            $st = (string) ($slot->status ?? '');
            if (in_array($st, ['scheduled', 'arrived', 'waiting', 'in_progress', 'completed'], true)) {
                $warehouseId = (int) ($slot->warehouse_id ?? 0);
                $gateId = !empty($slot->planned_gate_id) ? (int) $slot->planned_gate_id : null;
                $ticket = $this->slotService->generateTicketNumber($warehouseId, $gateId);
                DB::table('slots')->where('id', $slotId)->update([
                    'ticket_number' => $ticket,
                ]);
                $slot->ticket_number = $ticket;
            }
        }

        $plannedFinish = $this->slotService->computePlannedFinish(
            (string) ($slot->planned_start ?? ''),
            isset($slot->planned_duration) ? (int) $slot->planned_duration : 0
        );

        $leadMinutes = $this->minutesDiff($slot->arrival_time ?? null, $slot->actual_start ?? null);
        $processMinutes = $this->minutesDiff($slot->actual_start ?? null, $slot->actual_finish ?? null);

        // Calculate total lead time (waiting + process) for target status
        $totalLeadTimeMinutes = null;
        if ($leadMinutes !== null && $processMinutes !== null) {
            $totalLeadTimeMinutes = $leadMinutes + $processMinutes;
        } elseif ($processMinutes !== null) {
            $totalLeadTimeMinutes = $processMinutes;
        } elseif (!empty($slot->arrival_time) && !empty($slot->actual_finish)) {
            $totalLeadTimeMinutes = $this->minutesDiff($slot->arrival_time, $slot->actual_finish);
        }

        // Calculate target status using planned_duration
        $plannedDurationMinutes = isset($slot->planned_duration) ? (int) $slot->planned_duration : null;
        $targetStatus = null;
        if ($plannedDurationMinutes !== null && $plannedDurationMinutes > 0 && $totalLeadTimeMinutes !== null) {
            $threshold = $plannedDurationMinutes + 15;
            $targetStatus = $totalLeadTimeMinutes <= $threshold ? 'achieve' : 'not_achieve';
        }

        $logs = DB::table('activity_logs as al')
            ->leftJoin('md_users as u', 'al.created_by', '=', 'u.id')
            ->where('al.slot_id', $slotId)
            ->orderBy('al.created_at', 'desc')
            ->select([
                'al.id',
                'al.slot_id',
                'al.activity_type',
                'al.description',
                'al.created_at',
                'u.nik as username',
            ])
            ->get();

        $viewName = $isUnplanned ? 'unplanned.show' : 'slots.show';

        return view($viewName, [
            'slot' => $slot,
            'isUnplanned' => $isUnplanned,
            'plannedFinish' => $plannedFinish,
            'leadMinutes' => $leadMinutes,
            'processMinutes' => $processMinutes,
            'totalLeadTimeMinutes' => $totalLeadTimeMinutes,
            'targetStatus' => $targetStatus,
            'logs' => $logs,
            'slotItems' => $slotItems,
        ]);
    }

    public function unplannedIndex(Request $request)
    {
        $pageTitle = 'Unplanned';

        // Get request parameters
        $rawSort = $request->get('sort', '');
        $rawDir = $request->get('dir', 'desc');

        $sorts = is_array($rawSort) ? $rawSort : [trim((string) $rawSort)];
        $dirs = is_array($rawDir) ? $rawDir : [trim((string) $rawDir)];

        $sorts = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $sorts), fn ($v) => $v !== ''));
        $dirs = array_values(array_map(function ($v) {
            $v = strtolower(trim((string) $v));
            return in_array($v, ['asc', 'desc'], true) ? $v : 'desc';
        }, $dirs));

        $sort = $sorts[0] ?? '';
        $dir = $dirs[0] ?? 'desc';
        $pageSize = $request->get('page_size', '10');

        // If sort is explicitly 'reset', use default but don't pass to view
        $isResetSort = (!is_array($rawSort) && (string) $rawSort === 'reset');
        if ($isResetSort) {
            $sort = '';
            $sorts = [];
            $dirs = [];
        } elseif ($sort === '') {
            // Only set default sort for database query, not for view
            $querySort = 'created_at';
        } else {
            $querySort = $sort;
        }

        // Build query
        $query = DB::table('slots as s')
            ->join('md_warehouse as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('md_gates as g', 's.actual_gate_id', '=', 'g.id')
            ->leftJoin('md_truck as td', 's.truck_type', '=', 'td.truck_type')
            ->whereRaw("COALESCE(s.slot_type, 'planned') = 'unplanned'")
            ->select([
                's.*',
                's.po_number as truck_number',
                's.po_number as truck_number',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
                's.vendor_name',
                'g.gate_number as actual_gate_number',
                'td.target_duration_minutes',
            ]);

        // Apply filters
        if ($request->filled('q')) {
            $search = '%' . $request->get('q') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('s.po_number', 'like', $search)
                  ->orWhere('s.mat_doc', 'like', $search)
                  ->orWhere('s.vendor_name', 'like', $search);
            });
        }

        if ($request->filled('po_number')) {
            $query->where('s.po_number', 'like', '%' . $request->get('po_number') . '%');
        }

        if ($request->filled('mat_doc')) {
            $query->where('s.mat_doc', 'like', '%' . $request->get('mat_doc') . '%');
        }

        if ($request->filled('vendor')) {
            $query->where('s.vendor_name', 'like', '%' . $request->get('vendor') . '%');
        }

        if ($request->filled('warehouse')) {
            $query->where('w.wh_name', $request->get('warehouse'));
        }

        if ($request->filled('gate')) {
            $query->where('g.gate_number', $request->get('gate'));
        }

        if ($request->filled('direction')) {
            $query->where('s.direction', $request->get('direction'));
        }

        if ($request->filled('status')) {
            $status = (string) $request->get('status');
            if (in_array($status, ['waiting', 'completed'], true)) {
                $query->where('s.status', $status);
            }
        }

        if ($request->filled('arrival_from')) {
            $arrivalFrom = $request->get('arrival_from');
            $query->whereDate('s.arrival_time', '>=', $arrivalFrom);
        }

        if ($request->filled('arrival_to')) {
            $arrivalTo = $request->get('arrival_to');
            $query->whereDate('s.arrival_time', '<=', $arrivalTo);
        }

        // Apply sorting
        $allowedSorts = [
            'po_number', 'mat_doc', 'vendor_name', 'warehouse_name',
            'direction', 'arrival_time', 'created_at'
        ];

        $applied = 0;
        if (count($sorts) > 0) {
            foreach ($sorts as $i => $s) {
                if (! in_array($s, $allowedSorts, true)) {
                    continue;
                }
                $d = $dirs[$i] ?? 'desc';
                if ($s === 'po_number') {
                    $query->orderBy('s.po_number', $d);
                } elseif ($s === 'vendor_name') {
                    $query->orderBy('s.vendor_name', $d);
                } elseif ($s === 'warehouse_name') {
                    $query->orderBy('w.wh_name', $d);
                } else {
                    $query->orderBy('s.' . $s, $d);
                }
                $applied++;
            }
        }

        if ($applied === 0) {
            $actualSort = $querySort ?? 'created_at';
            if (in_array($actualSort, $allowedSorts, true)) {
                if ($actualSort === 'po_number') {
                    $query->orderBy('s.po_number', $dir);
                    $query->orderBy('s.po_number', $dir);
                } elseif ($actualSort === 'vendor_name') {
                    $query->orderBy('s.vendor_name', $dir);
                    $query->orderBy('s.vendor_name', $dir);
                } elseif ($actualSort === 'warehouse_name') {
                    $query->orderBy('w.wh_name', $dir);
                } else {
                    $query->orderBy('s.' . $actualSort, $dir);
                }
            } else {
                $query->orderByRaw('COALESCE(s.arrival_time, s.planned_start) DESC');
            }
        }

        $query->orderByDesc('s.created_at')->orderByDesc('s.id');

        // Apply pagination
        if ($pageSize === 'all') {
            $unplannedSlots = $query->get();
        } else {
            $limit = is_numeric($pageSize) ? (int) $pageSize : 50;
            $unplannedSlots = $query->limit($limit)->get();
        }

        // Get warehouses and gates for filter dropdowns
        $warehouses = DB::table('md_warehouse')
            ->select(['id', 'wh_name as name', 'wh_code as code'])
            ->orderBy('wh_name')
            ->get();
        $gates = DB::table('md_gates')
            ->where('is_active', true)
            ->orderBy('gate_number')
            ->pluck('gate_number')
            ->all();

        // Prepare data for view
        $viewData = compact('unplannedSlots', 'warehouses', 'gates', 'pageTitle');

        // If sort was reset, pass empty sort to view to clear indicators
        if ($isResetSort) {
            $viewData['sort'] = '';
            $viewData['dir'] = 'desc';
            $viewData['sorts'] = [];
            $viewData['dirs'] = [];
        } else {
            // Only pass sort to view if it was explicitly set by user
            $viewData['sort'] = $sort;
            $viewData['dir'] = $dir;
            $viewData['sorts'] = $sorts;
            $viewData['dirs'] = $dirs;
        }

        return view('unplanned.index', $viewData);
    }

    public function unplannedCreate()
    {
        $warehouses = DB::table('md_warehouse')
            ->select(['id', 'wh_name as name', 'wh_code as code'])
            ->orderBy('wh_name')
            ->get();
        $gates = DB::table('md_gates as g')
            ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id')
            ->where('g.is_active', true)
            ->orderBy('w.wh_name')
            ->orderBy('g.gate_number')
            ->select(['g.*', 'w.wh_name as warehouse_name', 'w.wh_code as warehouse_code'])
            ->get();

        $truckTypes = $this->getTruckTypeOptions();

        return view('unplanned.create', compact('warehouses', 'gates', 'truckTypes'));
    }

    public function unplannedStore(Request $request)
    {
        $request->validate([
            'po_items' => 'nullable|array',
            'po_items.*.qty' => 'nullable|numeric|min:0',
            'coa_pdf' => 'required|file|mimes:pdf|max:5120',
            'driver_name' => 'nullable|string|max:50',
            'actual_gate_id' => 'required|integer|exists:md_gates,id',
        ]);

        $poNumber = trim((string) $request->input('po_number', ''));
        $direction = (string) $request->input('direction', '');
        $actualGateId = $request->input('actual_gate_id') !== null && (string) $request->input('actual_gate_id') !== '' ? (int) $request->input('actual_gate_id') : null;
        $arrivalInput = trim((string) $request->input('actual_arrival', ''));

        if (! $actualGateId) {
            return back()->withInput()->with('error', 'Gate is required');
        }

        $gateRow = DB::table('md_gates')
            ->where('id', $actualGateId)
            ->where('is_active', true)
            ->select(['id', 'warehouse_id'])
            ->first();
        if (! $gateRow) {
            return back()->withInput()->with('error', 'Selected gate is not active');
        }
        $warehouseId = (int) ($gateRow->warehouse_id ?? 0);

        if ($poNumber === '' || $arrivalInput === '' || $direction === '') {
            return back()->withInput()->with('error', 'PO/DO number, direction, gate, and arrival time are required');
        }

        if (strlen($poNumber) > 12) {
            return back()->withInput()->withErrors(['po_number' => 'PO/DO number max 12 karakter']);
        }

        if (! in_array($direction, ['inbound', 'outbound'], true)) {
            return back()->withInput()->withErrors(['direction' => 'Direction harus inbound atau outbound']);
        }

        try {
            $arrivalDt = new DateTime($arrivalInput);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['actual_arrival' => 'Arrival time harus berupa tanggal yang valid']);
        }

        $arrivalTime = $arrivalDt->format('Y-m-d H:i:s');

        $matDoc = trim((string) $request->input('mat_doc', ''));
        $truckType = trim((string) $request->input('truck_type', ''));
        $vehicleNumber = trim((string) $request->input('vehicle_number_snap', ''));
        $driverName = trim((string) $request->input('driver_name', ''));
        $driverNumber = trim((string) $request->input('driver_number', ''));
        $notes = trim((string) $request->input('notes', ''));

        $selectedItems = (array) $request->input('po_items', []);
        $hasQty = false;
        foreach ($selectedItems as $it) {
            $qty = isset($it['qty']) ? (float) $it['qty'] : 0.0;
            if ($qty > 0) {
                $hasQty = true;
                break;
            }
        }

        $poDetail = null;
        if ($hasQty) {
            $poDetail = $this->poSearchService->getPoDetail($poNumber);
            if (! $poDetail) {
                return back()->withInput()->with('error', 'PO/DO not found in SAP.');
            }
            $poDetail = $this->withRemainingQty($poDetail);

            $remainingMap = [];
            foreach (($poDetail['items'] ?? []) as $it) {
                if (!is_array($it)) continue;
                $itemNo = trim((string) ($it['item_no'] ?? ''));
                if ($itemNo === '') continue;
                $remainingMap[$itemNo] = (float) ($it['remaining_qty'] ?? 0);
            }

            foreach ($selectedItems as $itemNo => $it) {
                $itemNo = trim((string) $itemNo);
                if ($itemNo === '') continue;
                $qty = isset($it['qty']) ? (float) $it['qty'] : 0.0;
                if ($qty <= 0) continue;
                $remain = (float) ($remainingMap[$itemNo] ?? 0);
                if ($remain <= 0) {
                    return back()->withInput()->with('error', "Item {$itemNo} has no remaining quantity.");
                }
                if ($qty - $remain > 0.000001) {
                    return back()->withInput()->with('error', "Item {$itemNo} quantity exceeds remaining ({$remain}).");
                }
            }
        }

        $setWaiting = $request->filled('set_waiting') && (string) $request->input('set_waiting') === '1';
        $status = $setWaiting ? 'waiting' : 'arrived';
        $actualStart = null;
        $actualFinish = null;
        if ($status === 'arrived') {
            $status = 'waiting';
        }

        $slotId = DB::transaction(function () use ($poNumber, $direction, $warehouseId, $actualGateId, $arrivalTime, $matDoc, $truckType, $vehicleNumber, $driverName, $driverNumber, $notes, $status, $actualStart, $actualFinish, $hasQty, $selectedItems, $poDetail) {
            $slotId = (int) DB::table('slots')->insertGetId([
                'po_number' => $poNumber,
                'direction' => $direction,
                'warehouse_id' => $warehouseId,
                'vendor_code' => $poDetail['vendor_code'] ?? null,
                'vendor_name' => $poDetail['vendor_name'] ?? null,
                'vendor_type' => $poDetail['vendor_type'] ?? null,
                'actual_gate_id' => $actualGateId,
                'arrival_time' => $arrivalTime,
                'mat_doc' => $matDoc !== '' ? $matDoc : null,
                'truck_type' => $truckType !== '' ? $truckType : null,
                'vehicle_number_snap' => $vehicleNumber !== '' ? $vehicleNumber : null,
                'driver_name' => $driverName !== '' ? $driverName : null,
                'driver_number' => $driverNumber !== '' ? $driverNumber : null,
                'late_reason' => $notes !== '' ? $notes : null,
                'status' => $status,
                'slot_type' => 'unplanned',
                'actual_start' => $actualStart,
                'actual_finish' => $actualFinish,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($slotId > 0 && $hasQty && Schema::hasTable('slot_po_items')) {
                $itemsDetailMap = [];
                foreach (($poDetail['items'] ?? []) as $it) {
                    if (!is_array($it)) continue;
                    $itemNo = trim((string) ($it['item_no'] ?? ''));
                    if ($itemNo === '') continue;
                    $itemsDetailMap[$itemNo] = $it;
                }

                foreach ($selectedItems as $itemNo => $it) {
                    $itemNo = trim((string) $itemNo);
                    if ($itemNo === '') continue;
                    $qty = isset($it['qty']) ? (float) $it['qty'] : 0.0;
                    if ($qty <= 0) continue;

                    $detailIt = $itemsDetailMap[$itemNo] ?? [];
                    SlotPoItem::create([
                        'slot_id' => $slotId,
                        'po_number' => $poNumber,
                        'item_no' => $itemNo,
                        'material_code' => $detailIt['material'] ?? null,
                        'material_name' => $detailIt['description'] ?? null,
                        'uom' => $detailIt['uom'] ?? null,
                        'qty_booked' => $qty,
                    ]);
                }
            }

            if ($slotId > 0) {
                $this->slotService->logActivity($slotId, 'status_change', 'Unplanned Transaction Recorded as ' . $status);
            }

            return $slotId;
        });

        return redirect()->route('unplanned.show', ['slotId' => $slotId])->with('success', 'Unplanned transaction recorded successfully');
    }

    public function unplannedEdit(int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Slot not found');
        }

        if (((string) ($slot->slot_type ?? 'planned')) !== 'unplanned') {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Only unplanned transactions can be edited here');
        }

        $warehouses = DB::table('md_warehouse')
            ->select(['id', 'wh_name as name', 'wh_code as code'])
            ->orderBy('wh_name')
            ->get();

        $vendorsQ = DB::table('business_partner')
            ->select([
                'id',
                'bp_name as name',
                'bp_code as code',
                'bp_type as type',
            ])
            ->orderBy('bp_name');
        if (Schema::hasColumn('business_partner', 'is_active')) {
            $vendorsQ->where('is_active', true);
        }
        $vendors = $vendorsQ->get();

        $gates = DB::table('md_gates as g')
            ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id')
            ->where('g.is_active', true)
            ->orderBy('w.wh_name')
            ->orderBy('g.gate_number')
            ->select(['g.*', 'w.wh_name as warehouse_name', 'w.wh_code as warehouse_code'])
            ->get();

        $truckTypes = $this->getTruckTypeOptions();

        return view('unplanned.edit', [
            'slot' => $slot,
            'warehouses' => $warehouses,
            'vendors' => $vendors,
            'gates' => $gates,
            'truckTypes' => $truckTypes,
        ]);
    }

    public function unplannedUpdate(Request $request, int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Slot not found');
        }

        if (((string) ($slot->slot_type ?? 'planned')) !== 'unplanned') {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Only unplanned transactions can be edited here');
        }

        $request->validate([
            'po_number' => 'required|string|max:12',
            'direction' => 'required|in:inbound,outbound',
            'vendor_id' => 'nullable|integer|exists:business_partner,id',
            'actual_gate_id' => 'required|integer|exists:md_gates,id',
            'arrival_time' => 'required|string',
        ]);

        $truckNumber = trim((string) ($request->input('po_number', $request->input('truck_number', ''))));
        $direction = (string) $request->input('direction', '');
        $vendorId = $request->input('vendor_id') !== null && (string) $request->input('vendor_id') !== '' ? (int) $request->input('vendor_id') : null;
        $actualGateId = $request->input('actual_gate_id') !== null && (string) $request->input('actual_gate_id') !== '' ? (int) $request->input('actual_gate_id') : null;
        $arrivalTime = (string) $request->input('arrival_time', '');

        if (! $actualGateId) {
            return back()->withInput()->with('error', 'Gate is required');
        }

        $gateRow = DB::table('md_gates')
            ->where('id', $actualGateId)
            ->where('is_active', true)
            ->select(['id', 'warehouse_id'])
            ->first();
        if (! $gateRow) {
            return back()->withInput()->with('error', 'Selected gate is not active');
        }
        $warehouseId = (int) ($gateRow->warehouse_id ?? 0);

        if ($truckNumber !== '' && strlen($truckNumber) > 12) {
            return back()->withInput()->withErrors(['po_number' => 'PO/DO number max 12 karakter']);
        }

        $matDoc = trim((string) $request->input('mat_doc', ''));
        $truckType = trim((string) $request->input('truck_type', ''));
        $vehicleNumber = trim((string) $request->input('vehicle_number_snap', ''));
        $driverName = trim((string) $request->input('driver_name', ''));
        $driverNumber = trim((string) $request->input('driver_number', ''));
        $notes = trim((string) $request->input('notes', ''));

        $setWaiting = $request->filled('set_waiting') && (string) $request->input('set_waiting') === '1';
        $status = $setWaiting ? 'waiting' : 'arrived';
        if ($status === 'arrived' && (string)($slot->status ?? '') === 'waiting') {
             // allow downgrade? for now follow request
        }
        if ($status === 'arrived') {
            $status = 'waiting'; // Unplanned is usually waiting
        }

        DB::transaction(function () use ($slotId, $truckNumber, $direction, $warehouseId, $vendorId, $actualGateId, $arrivalTime, $matDoc, $truckType, $vehicleNumber, $driverName, $driverNumber, $notes, $status) {
            $truck = DB::table('po')->where('po_number', $truckNumber)->select(['id'])->first();
            if ($truck) {
                $truckId = (int) $truck->id;
            } else {
                $truckId = (int) DB::table('po')->insertGetId([
                    'po_number' => $truckNumber,
                ]);
            }

            DB::table('slots')->where('id', $slotId)->update([
                'po_id' => $truckId,
                'direction' => $direction,
                'warehouse_id' => $warehouseId,
                'bp_id' => $vendorId,
                'actual_gate_id' => $actualGateId,
                'arrival_time' => $arrivalTime,
                'mat_doc' => $matDoc !== '' ? $matDoc : null,
                'truck_type' => $truckType !== '' ? $truckType : null,
                'vehicle_number_snap' => $vehicleNumber !== '' ? $vehicleNumber : null,
                'driver_name' => $driverName !== '' ? $driverName : null,
                'driver_number' => $driverNumber !== '' ? $driverNumber : null,
                'late_reason' => $notes !== '' ? $notes : null,
                'status' => $status,
                'updated_at' => now(),
            ]);

            $this->slotService->logActivity($slotId, 'status_change', 'Unplanned Transaction Updated');
        });

        return redirect()->route('unplanned.show', ['slotId' => $slotId])->with('success', 'Unplanned transaction updated successfully');
    }

    public function arrival(int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Slot not found');
        }

        if (((string) ($slot->slot_type ?? 'planned')) === 'unplanned') {
            return redirect()->route('unplanned.show', ['slotId' => $slotId])->with('error', 'Unplanned slot tidak memiliki proses arrival');
        }

        if ((string) ($slot->status ?? '') !== Slot::STATUS_SCHEDULED) {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Only scheduled slots can be arrived');
        }

        return view('slots.arrival', [
            'slot' => $slot,
            'slotItems' => SlotPoItem::where('slot_id', $slotId)
                ->orderBy('item_no')
                ->get(),
        ]);
    }

    public function arrivalStore(Request $request, int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Slot not found');
        }

        if (((string) ($slot->slot_type ?? 'planned')) === 'unplanned') {
            return redirect()->route('unplanned.show', ['slotId' => $slotId])->with('error', 'Unplanned slot tidak memiliki proses arrival');
        }

        if ((string) ($slot->status ?? '') !== Slot::STATUS_SCHEDULED) {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Only scheduled slots can be arrived');
        }

        $ticketNumber = trim((string) $request->input('ticket_number', ''));
        if ($ticketNumber === '') {
            return back()->withInput()->with('error', 'Ticket number is required');
        }

        $expectedTicket = trim((string) ($slot->ticket_number ?? ''));
        if ($expectedTicket !== '' && $ticketNumber !== $expectedTicket) {
            return back()->withInput()->with('error', 'Ticket number does not match this slot.');
        }

        DB::transaction(function () use ($slotId, $ticketNumber) {
            $now = date('Y-m-d H:i:s');
            DB::table('slots')->where('id', $slotId)->update([
                'arrival_time' => $now,
                'ticket_number' => $ticketNumber,
                'status' => Slot::STATUS_WAITING,
            ]);

            $this->slotService->logActivity($slotId, 'status_change', 'Status Changed to Waiting After Arrival');
            $this->slotService->logActivity($slotId, 'arrival_recorded', 'Arrival Recorded with Ticket ' . $ticketNumber);
        });

        return redirect()->route('slots.show', ['slotId' => $slotId])->with('success', 'Arrival recorded');
    }

    public function ticket(int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Slot not found');
        }

        $gateNumber = (string) ($slot->actual_gate_number ?? '');
        $gateWarehouse = (string) ($slot->actual_gate_warehouse_code ?? '');
        if ($gateNumber === '') {
            $gateNumber = (string) ($slot->planned_gate_number ?? '');
            $gateWarehouse = (string) ($slot->planned_gate_warehouse_code ?? '');
        }
        if ($gateWarehouse === '') {
            $gateWarehouse = (string) ($slot->warehouse_code ?? '');
        }
        $gateLetter = $this->slotService->getGateLetterByWarehouseAndNumber($gateWarehouse, $gateNumber);

        // Generate barcode
        $barcodeC = new \Milon\Barcode\DNS1D();
        $barcodeC->setStorPath(storage_path('app/public/'));
        $barcodePng = '';
        if (!empty($slot->ticket_number)) {
            $barcodePng = $barcodeC->getBarcodePNG($slot->ticket_number, 'C128', 2.5, 60);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('slots.ticket', [
            'slot' => $slot,
            'gateLetter' => $gateLetter,
            'barcodePng' => $barcodePng,
            'barcodeHtml' => null,
            'barcodeSvg' => null,
        ]);

        // Set paper size to custom small ticket size (approx 80mm x 100mm or similar)
        // 227pt is approx 80mm, 350pt is approx 123mm
        $pdf->setPaper([0, 0, 240, 350]);

        return $pdf->stream('ticket-' . ($slot->ticket_number ?? $slot->id) . '.pdf');
    }

    public function start(int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Slot not found');
        }

        $slotType = (string) ($slot->slot_type ?? 'planned');
        if ($slotType === 'unplanned') {
            if ((string) ($slot->status ?? '') !== Slot::STATUS_WAITING) {
                return redirect()->route('unplanned.show', ['slotId' => $slotId])->with('error', 'Only waiting unplanned slots can be started');
            }
        } else {
            if (! in_array((string) ($slot->status ?? ''), ['arrived', 'waiting'], true)) {
                return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Only arrived/waiting slots can be started');
            }

            if (empty($slot->arrival_time)) {
                return redirect()->route('slots.arrival', ['slotId' => $slotId])->with('error', 'Please record Arrival before starting this slot');
            }
        }

        $gates = DB::table('md_gates as g')
            ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id')
            ->where('g.is_active', true)
            ->orderBy('w.wh_name')
            ->orderBy('g.gate_number')
            ->select([
                'g.*',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
            ])
            ->get();

        $plannedDurationMinutes = $this->getPlannedDurationForStart($slot);

        $gateStatuses = [];
        $allConflict = [];
        foreach ($gates as $g) {
            $gid = (int) ($g->id ?? 0);
            if ($gid <= 0) {
                continue;
            }
            $conflicts = $this->findInProgressConflicts($gid, $slotId);
            foreach ($conflicts as $cid) {
                $allConflict[$cid] = true;
            }
            $gateStatuses[$gid] = [
                'is_conflict' => ! empty($conflicts),
                'overlapping_slots' => $conflicts,
                'lane_utilization_pct' => ! empty($conflicts) ? 100 : 0,
            ];
        }

        $conflictSlotIds = array_keys($allConflict);
        $conflictDetails = [];
        if (!empty($conflictSlotIds)) {
            $rows = DB::table('slots')
                ->whereIn('id', $conflictSlotIds)
                ->select(['id', 'ticket_number'])
                ->get();
            foreach ($rows as $r) {
                $rid = (int) ($r->id ?? 0);
                if ($rid <= 0) continue;
                $conflictDetails[$rid] = $r;
            }
        }

        $recommendedGateId = null;
        if (!empty($slot->planned_gate_id)) {
            $pgid = (int) $slot->planned_gate_id;
            if (empty(($gateStatuses[$pgid] ?? [])['is_conflict'])) {
                $recommendedGateId = $pgid;
            }
        }
        if ($recommendedGateId === null) {
            foreach ($gates as $g) {
                $gid = (int) ($g->id ?? 0);
                if ($gid <= 0) continue;
                if ((int) ($g->warehouse_id ?? 0) !== (int) ($slot->warehouse_id ?? 0)) continue;
                if (empty(($gateStatuses[$gid] ?? [])['is_conflict'])) {
                    $recommendedGateId = $gid;
                    break;
                }
            }
        }
        if ($recommendedGateId === null) {
            foreach ($gates as $g) {
                $gid = (int) ($g->id ?? 0);
                if ($gid <= 0) continue;
                if (empty(($gateStatuses[$gid] ?? [])['is_conflict'])) {
                    $recommendedGateId = $gid;
                    break;
                }
            }
        }

        $selectedGateId = $recommendedGateId;

        $viewName = $slotType === 'unplanned' ? 'unplanned.start' : 'slots.start';

        return view($viewName, [
            'slot' => $slot,
            'gates' => $gates,
            'plannedDurationMinutes' => $plannedDurationMinutes,
            'gateStatuses' => $gateStatuses,
            'conflictDetails' => $conflictDetails,
            'recommendedGateId' => $recommendedGateId,
            'selectedGateId' => $selectedGateId,
        ]);
    }

    public function startStore(Request $request, int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Slot not found');
        }

        $slotType = (string) ($slot->slot_type ?? 'planned');
        if ($slotType === 'unplanned') {
            if ((string) ($slot->status ?? '') !== Slot::STATUS_WAITING) {
                return redirect()->route('unplanned.show', ['slotId' => $slotId])->with('error', 'Only waiting unplanned slots can be started');
            }
        } else {
            if (! in_array((string) ($slot->status ?? ''), ['arrived', 'waiting'], true)) {
                return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Only arrived/waiting slots can be started');
            }
            if (empty($slot->arrival_time)) {
                return redirect()->route('slots.arrival', ['slotId' => $slotId])->with('error', 'Please record Arrival before starting this slot');
            }
        }

        $actualGateId = $request->input('actual_gate_id') !== null && (string) $request->input('actual_gate_id') !== '' ? (int) $request->input('actual_gate_id') : null;
        if (! $actualGateId) {
            return back()->withInput()->with('error', 'Actual gate is required');
        }

        $gateRow = DB::table('md_gates')->where('id', $actualGateId)->where('is_active', true)->select(['id', 'warehouse_id'])->first();
        if (! $gateRow) {
            return back()->withInput()->with('error', 'Selected gate is not active');
        }
        if ((int) ($gateRow->warehouse_id ?? 0) !== (int) ($slot->warehouse_id ?? 0)) {
            return back()->withInput()->with('error', 'Selected gate does not belong to the slot\'s warehouse');
        }

        $conflicts = $this->findInProgressConflicts($actualGateId, $slotId);
        if (! empty($conflicts)) {
            $lines = $this->buildConflictLines($conflicts);
            return back()
                ->withInput()
                ->with('conflict_lines', $lines);
        }

        DB::transaction(function () use ($slot, $slotId, $actualGateId) {
            $now = date('Y-m-d H:i:s');
            $arrivalTime = (string) ($slot->arrival_time ?? $now);
            $isLate = 0;
            if (((string) ($slot->slot_type ?? 'planned')) !== 'unplanned') {
                $isLate = $this->isLateByPlannedStart((string) ($slot->planned_start ?? ''), $now) ? 1 : 0;
            }

            DB::table('slots')->where('id', $slotId)->update([
                'status' => Slot::STATUS_IN_PROGRESS,
                'arrival_time' => $arrivalTime,
                'actual_start' => $now,
                'is_late' => $isLate,
                'actual_gate_id' => $actualGateId,
            ]);

            $gateMeta = $this->slotService->getGateMetaById($actualGateId);
            $gateName = $this->buildGateLabel((string) ($gateMeta['warehouse_code'] ?? ''), (string) ($gateMeta['gate_number'] ?? ''));

            if (((string) ($slot->slot_type ?? 'planned')) !== 'unplanned') {
                if ($isLate) {
                    $this->slotService->logActivity($slotId, 'late_arrival', 'Truck Arrived Late at ' . $gateName);
                } else {
                    $this->slotService->logActivity($slotId, 'early_arrival', 'Truck Arrived on Time/Early at ' . $gateName);
                }
            }
            $this->slotService->logActivity($slotId, 'status_change', 'Slot Started at ' . $gateName);
        });

        if ($slotType === 'unplanned') {
            return redirect()->route('unplanned.show', ['slotId' => $slotId])->with('success', 'Unplanned slot started');
        }

        return redirect()->route('slots.show', ['slotId' => $slotId])->with('success', 'Slot started');
    }

    public function unplannedStart(int $slotId)
    {
        return $this->start($slotId);
    }

    public function unplannedStartStore(Request $request, int $slotId)
    {
        return $this->startStore($request, $slotId);
    }

    public function complete(int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Slot not found');
        }

        $truckTypes = $this->getTruckTypeOptions();
        $viewName = ((string) ($slot->slot_type ?? 'planned')) === 'unplanned' ? 'unplanned.complete' : 'slots.complete';

        return view($viewName, [
            'slot' => $slot,
            'truckTypes' => $truckTypes,
        ]);
    }

    public function completeStore(Request $request, int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Slot not found');
        }

        if ((string) ($slot->status ?? '') !== 'in_progress') {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Only in-progress slots can be completed');
        }

        if (empty($slot->actual_start)) {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Cannot complete: actual start time is missing. Please start the slot first.');
        }

        $matDoc = trim((string) $request->input('mat_doc', ''));
        // Truck type comes from slot data, not from form
        $truckType = (string) ($slot->truck_type ?? '');
        $vehicleNumber = trim((string) $request->input('vehicle_number', ''));
        $driverName = trim((string) $request->input('driver_name', ''));
        $driverNumber = trim((string) $request->input('driver_number', ''));
        $notes = trim((string) $request->input('notes', ''));

        if ($matDoc === '' || $truckType === '' || $vehicleNumber === '' || $driverNumber === '') {
            return back()->withInput()->with('error', 'All required fields must be filled');
        }

        DB::transaction(function () use ($slotId, $matDoc, $truckType, $vehicleNumber, $driverName, $driverNumber, $notes) {
            $now = date('Y-m-d H:i:s');

            // Get slot info before updating
            $slotInfo = DB::table('slots')->where('id', $slotId)->first();

            if (! $slotInfo || empty($slotInfo->actual_start)) {
                throw new \RuntimeException('Cannot complete: actual start time is missing.');
            }

            DB::table('slots')->where('id', $slotId)->update([
                'status' => 'completed',
                'actual_finish' => $now,
                'mat_doc' => $matDoc,
                'truck_type' => $truckType,
                'vehicle_number_snap' => $vehicleNumber,
                'driver_name' => $driverName !== '' ? $driverName : null,
                'driver_number' => $driverNumber,
                'late_reason' => $notes !== '' ? $notes : null,
            ]);

            // Reconcile receipts with SAP QtyGRTotal (including LIFO reversal handling)
            $this->receiptReconciliationService->reconcileSlotCompletion($slotId);

            // Auto-cancel obsolete scheduled slots when a slot is completed
            if ($slotInfo && $slotInfo->actual_gate_id) {
                $this->autoCancelObsoleteSlots($slotInfo->actual_gate_id, $slotInfo->actual_start, $slotInfo->actual_finish, $slotId);
            }

            $this->slotService->logActivity($slotId, 'status_change', 'Slot Completed with MAT DOC ' . $matDoc . ', Truck ' . $truckType . ', Vehicle ' . $vehicleNumber . ', Driver ' . $driverNumber);
        });

        return redirect()->route('slots.index')->with('success', 'Slot completed');
    }

    /**
     * Auto-cancel obsolete scheduled slots when a slot is started or completed
     * This handles the case where a truck arrives earlier than scheduled and starts operation,
     * making future bookings for the same gate/time obsolete
     */
    private function autoCancelObsoleteSlots(int $gateId, string $actualStart, ?string $actualFinish, int $excludeSlotId): void
    {
        // Get lane group for the gate
        $laneGroup = $this->slotService->getGateLaneGroup($gateId);
        $laneGateIds = $laneGroup ? $this->slotService->getGateIdsByLaneGroup($laneGroup) : [$gateId];

        // For in-progress slots, estimate finish time based on planned duration
        $estimatedFinish = $actualFinish;
        if ($estimatedFinish === null) {
            // Get the slot to estimate finish time
            $currentSlot = DB::table('slots')->where('id', $excludeSlotId)->first();
            if ($currentSlot && $currentSlot->planned_duration) {
                $finishTime = new \DateTime($actualStart);
                $finishTime->modify('+' . (int) $currentSlot->planned_duration . ' minutes');
                $estimatedFinish = $finishTime->format('Y-m-d H:i:s');
            } else {
                // Default to 1 hour if no duration specified
                $finishTime = new \DateTime($actualStart);
                $finishTime->modify('+1 hour');
                $estimatedFinish = $finishTime->format('Y-m-d H:i:s');
            }
        }

        // Find scheduled slots that overlap with the current slot's time
        $obsoleteSlots = DB::table('slots')
            ->whereIn('actual_gate_id', $laneGateIds)
            ->where('status', 'scheduled')
            ->where('id', '<>', $excludeSlotId)
            ->where(function($query) use ($actualStart, $estimatedFinish) {
                $query->where(function($sub) use ($actualStart, $estimatedFinish) {
                    // Scheduled slot starts during current slot's time
                    $sub->where('planned_start', '>=', $actualStart)
                        ->where('planned_start', '<=', $estimatedFinish);
                })->orWhere(function($sub) use ($actualStart, $estimatedFinish) {
                    // Scheduled slot ends during current slot's time
                    $sub->where('planned_start', '<=', $actualStart)
                        ->whereRaw('(' . $this->slotService->getDateAddExpression('planned_start', 'planned_duration') . ') >= ?', [$actualStart]);
                });
            })
            ->get();

        if ($obsoleteSlots->isEmpty()) {
            return;
        }

        // Cancel obsolete scheduled slots
        foreach ($obsoleteSlots as $obsoleteSlot) {
            DB::table('slots')
                ->where('id', $obsoleteSlot->id)
                ->update([
                    'status' => 'cancelled',
                    'blocking_risk' => 0,
                    'cancelled_reason' => 'Auto-cancelled: Truck started operation earlier at same gate',
                    'cancelled_at' => now()
                ]);

            $this->slotService->logActivity(
                $obsoleteSlot->id,
                'status_change',
                'Auto-cancelled due to earlier operation start at same gate'
            );
        }
    }

    public function unplannedComplete(int $slotId)
    {
        return $this->complete($slotId);
    }

    public function unplannedCompleteStore(Request $request, int $slotId)
    {
        $result = $this->completeStore($request, $slotId);

        // Redirect to unplanned show instead of slots show
        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return redirect()->route('unplanned.show', ['slotId' => $slotId])->with('success', 'Unplanned slot completed');
        }

        return $result;
    }

    public function cancel(int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Slot not found');
        }

        if ((string) ($slot->status ?? '') === 'cancelled') {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Slot already cancelled');
        }

        return view('slots.cancel', ['slot' => $slot]);
    }

    public function cancelStore(Request $request, int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Slot not found');
        }

        if ((string) ($slot->status ?? '') === 'cancelled') {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Slot already cancelled');
        }

        $reason = trim((string) $request->input('cancelled_reason', ''));
        if ($reason === '') {
            return back()->withInput()->with('error', 'Cancellation reason is required');
        }

        DB::transaction(function () use ($slotId, $reason) {
            $now = date('Y-m-d H:i:s');
            DB::table('slots')->where('id', $slotId)->update([
                'status' => 'cancelled',
                'cancelled_reason' => $reason,
                'cancelled_at' => $now,
            ]);
            $this->slotService->logActivity($slotId, 'status_change', 'Slot Cancelled', null, ['reason' => $reason, 'cancelled_at' => $now]);
        });

        return redirect()->route('slots.index')->with('success', 'Slot cancelled');
    }

    public function ajaxCheckRisk(Request $request)
    {
        $warehouseId = (int) $request->input('warehouse_id', 0);
        $plannedGateId = $request->input('planned_gate_id') !== null && (string) $request->input('planned_gate_id') !== '' ? (int) $request->input('planned_gate_id') : null;
        $plannedStart = (string) $request->input('planned_start', '');
        $plannedDuration = (int) $request->input('planned_duration', 0);
        $durationUnit = (string) $request->input('duration_unit', 'minutes');

        if ($warehouseId === 0 || $plannedStart === '' || $plannedDuration <= 0) {
            return response()->json(['success' => false, 'message' => 'Incomplete data']);
        }

        $plannedDurationMinutes = $plannedDuration;
        if ($durationUnit === 'hours') {
            $plannedDurationMinutes = $plannedDuration * 60;
        }

        try {
            $startDt = new DateTime($plannedStart);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Invalid planned start']);
        }

        $endDt = clone $startDt;
        $endDt->modify('+' . (int) $plannedDurationMinutes . ' minutes');

        $startStr = $startDt->format('Y-m-d H:i:s');
        $endStr = $endDt->format('Y-m-d H:i:s');

        // Use calculateBlockingRisk directly - it includes all logic including BC edge cases
        $riskLevel = $this->slotService->calculateBlockingRisk($warehouseId, $plannedGateId, $plannedStart, $plannedDurationMinutes);

        $label = 'Low';
        $badge = 'success';
        $message = 'Low risk for this time and gate combination.';

        if ($riskLevel === 1) {
            $label = 'Medium';
            $badge = 'warning';
            $message = 'Watch for potential blocking. Consider checking schedules at other gates or shifting time.';
        } elseif ($riskLevel === 2) {
            $label = 'High';
            $badge = 'danger';
            $message = 'High blocking potential. Recommended to change gate or slot time.';
        }

        return response()->json([
            'success' => true,
            'risk_level' => $riskLevel,
            'label' => $label,
            'badge' => $badge,
            'message' => $message,
        ]);
    }

    public function ajaxCheckSlotTime(Request $request)
    {
        $warehouseId = (int) $request->input('warehouse_id', 0);
        $gateId = $request->input('planned_gate_id') !== null && (string) $request->input('planned_gate_id') !== '' ? (int) $request->input('planned_gate_id') : null;
        $plannedStart = (string) $request->input('planned_start', '');
        $plannedDuration = (int) $request->input('planned_duration', 0);
        $durationUnit = (string) $request->input('duration_unit', 'minutes');

        if ($warehouseId === 0 || $plannedStart === '' || $plannedDuration <= 0) {
            return response()->json(['success' => false, 'message' => 'Incomplete data']);
        }

        $plannedDurationMinutes = $plannedDuration;
        if ($durationUnit === 'hours') {
            $plannedDurationMinutes = $plannedDuration * 60;
        }

        try {
            $startDt = new DateTime($plannedStart);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Invalid planned start']);
        }

        $endDt = clone $startDt;
        $endDt->modify('+' . (int) $plannedDurationMinutes . ' minutes');

        $response = [
            'success' => true,
            'overlap' => false,
            'message' => '',
            'suggested_start' => null,
        ];

        if ($gateId !== null) {
            $laneGroup = $this->slotService->getGateLaneGroup($gateId);
            $laneGateIds = $laneGroup ? $this->slotService->getGateIdsByLaneGroup($laneGroup) : [$gateId];
            if (empty($laneGateIds)) {
                $laneGateIds = [$gateId];
            }

            $startStr = $startDt->format('Y-m-d H:i:s');
            $endStr = $endDt->format('Y-m-d H:i:s');

            $conflicts = DB::table('slots')
                ->whereIn('planned_gate_id', $laneGateIds)
                ->whereIn('status', ['scheduled', 'waiting', 'in_progress'])
                ->whereRaw('? < ' . $this->slotService->getDateAddExpression('planned_start', 'planned_duration'), [$startStr])
                ->whereRaw('? > planned_start', [$endStr])
                ->orderBy('planned_start', 'asc')
                ->select(['planned_start', 'planned_duration'])
                ->get();

            if ($conflicts->isNotEmpty()) {
                $response['overlap'] = true;
                $response['message'] = 'Planned time overlaps with another slot on this lane.';

                $day = $startDt->format('Y-m-d');
                $latest = DB::table('slots')
                    ->whereIn('planned_gate_id', $laneGateIds)
                    ->whereIn('status', ['scheduled', 'waiting', 'in_progress'])
                    ->whereRaw('DATE(planned_start) = ?', [$day])
                    ->orderByRaw($this->slotService->getDateAddExpression('planned_start', 'planned_duration') . ' DESC')
                    ->limit(1)
                    ->select(['planned_start', 'planned_duration'])
                    ->first();

                if ($latest) {
                    try {
                        $safeStart = new DateTime((string) $latest->planned_start);
                        $safeStart->modify('+' . (int) ($latest->planned_duration ?? 0) . ' minutes');
                        $response['suggested_start'] = $safeStart->format('Y-m-d H:i');
                    } catch (\Throwable $e) {
                        $response['suggested_start'] = null;
                    }
                }
            }

            if (! $response['overlap']) {
                $bcCheck = $this->slotService->validateWh2BcPlannedWindow($gateId, $startDt, $endDt);
                if (empty($bcCheck['ok'])) {
                    $response['overlap'] = true;
                    $response['message'] = (string) ($bcCheck['message'] ?? 'Planned time is not allowed for this gate');
                    $response['suggested_start'] = null;
                }
            }
        }

        return response()->json($response);
    }

    public function ajaxRecommendGate(Request $request)
    {
        $warehouseId = (int) $request->input('warehouse_id', 0);
        $plannedStart = (string) $request->input('planned_start', '');
        $plannedDuration = (int) $request->input('planned_duration', 0);
        $durationUnit = (string) $request->input('duration_unit', 'minutes');

        if ($warehouseId === 0 || $plannedStart === '' || $plannedDuration <= 0) {
            return response()->json(['success' => false, 'message' => 'Incomplete data']);
        }

        $plannedDurationMinutes = $plannedDuration;
        if ($durationUnit === 'hours') {
            $plannedDurationMinutes = $plannedDuration * 60;
        }

        try {
            $startDt = new DateTime($plannedStart);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Invalid planned start']);
        }

        $endDt = clone $startDt;
        $endDt->modify('+' . (int) $plannedDurationMinutes . ' minutes');

        $startStr = $startDt->format('Y-m-d H:i:s');
        $endStr = $endDt->format('Y-m-d H:i:s');

        $gates = DB::table('md_gates as g')
            ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id')
            ->where('g.warehouse_id', $warehouseId)
            ->where('g.is_active', true)
            ->orderBy('g.gate_number')
            ->select(['g.id', 'g.gate_number', 'w.wh_code as warehouse_code', 'w.wh_name as warehouse_name'])
            ->get();

        if ($gates->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No active gates for this warehouse']);
        }

        $bestGate = null;
        $bestRisk = null;

        foreach ($gates as $gate) {
            $gid = (int) $gate->id;

            $laneGroup = $this->slotService->getGateLaneGroup($gid);
            $laneGateIds = $laneGroup ? $this->slotService->getGateIdsByLaneGroup($laneGroup) : [$gid];
            if (empty($laneGateIds)) {
                $laneGateIds = [$gid];
            }

            $overlapCount = (int) DB::table('slots')
                ->whereIn('planned_gate_id', $laneGateIds)
                ->whereIn('status', ['scheduled', 'waiting', 'in_progress'])
                ->whereRaw('? < ' . $this->slotService->getDateAddExpression('planned_start', 'planned_duration'), [$startStr])
                ->whereRaw('? > planned_start', [$endStr])
                ->count();

            if ($overlapCount > 0) {
                continue;
            }

            $bcCheck = $this->slotService->validateWh2BcPlannedWindow($gid, $startDt, $endDt);
            if (empty($bcCheck['ok'])) {
                continue;
            }

            $risk = $this->slotService->calculateBlockingRisk($warehouseId, $gid, $plannedStart, $plannedDurationMinutes);

            $pick = false;
            if ($bestRisk === null || $risk < $bestRisk) {
                $pick = true;
            } elseif ($bestRisk !== null && $risk === $bestRisk) {
                $whCode = strtoupper(trim((string) ($gate->warehouse_code ?? '')));
                if ($whCode === 'WH2') {
                    $letterNow = $this->slotService->getGateLetterByWarehouseAndNumber($whCode, (string) ($gate->gate_number ?? ''));
                    $bestLetter = $bestGate ? $this->slotService->getGateLetterByWarehouseAndNumber($whCode, (string) ($bestGate->gate_number ?? '')) : null;
                    if ($letterNow === 'C' && $bestLetter !== 'C') {
                        $pick = true;
                    }
                }
            }

            if ($pick) {
                $bestRisk = $risk;
                $bestGate = (object) ([
                    'id' => (int) $gate->id,
                    'gate_number' => (string) ($gate->gate_number ?? ''),
                    'warehouse_code' => (string) ($gate->warehouse_code ?? ''),
                    'warehouse_name' => (string) ($gate->warehouse_name ?? ''),
                    'risk' => (int) $risk,
                ]);
            }
        }

        if ($bestGate === null) {
            return response()->json(['success' => false, 'message' => 'No available gate for this time']);
        }

        $label = 'Low';
        if ($bestGate->risk === 1) {
            $label = 'Medium';
        } elseif ($bestGate->risk === 2) {
            $label = 'High';
        }

        $gateDisplay = $this->slotService->getGateDisplayName($bestGate->warehouse_code, $bestGate->gate_number);
        $gateLabel = trim(($bestGate->warehouse_code !== '' ? ($bestGate->warehouse_code . ' - ' . $gateDisplay) : $gateDisplay));

        $note = null;
        $whCodeOut = strtoupper(trim((string) $bestGate->warehouse_code));
        if ($whCodeOut === 'WH2') {
            $letterOut = $this->slotService->getGateLetterByWarehouseAndNumber($whCodeOut, (string) $bestGate->gate_number);
            if ($letterOut === 'C') {
                $note = 'WH2: Prioritize Gate C if available because Gate B is in front/line and can block access to Gate C when operating.';
            }
        }

        return response()->json([
            'success' => true,
            'gate_id' => (int) $bestGate->id,
            'gate_label' => $gateLabel,
            'risk_level' => (int) $bestGate->risk,
            'risk_label' => $label,
            'note' => $note,
        ]);
    }

    public function ajaxSchedulePreview(Request $request)
    {
        $warehouseId = (int) $request->input('warehouse_id', 0);
        $plannedGateId = $request->input('planned_gate_id') !== null && (string) $request->input('planned_gate_id') !== '' ? (int) $request->input('planned_gate_id') : null;
        $date = (string) $request->input('date', '');

        if ($warehouseId === 0) {
            return response()->json(['success' => false, 'message' => 'Warehouse is required']);
        }

        if ($date === '') {
            $date = date('Y-m-d');
        }

        $q = DB::table('slots as s')
            ->join('md_warehouse as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('md_gates as g', 's.planned_gate_id', '=', 'g.id')
            ->where('s.warehouse_id', $warehouseId)
            ->whereRaw('DATE(s.planned_start) = ?', [$date])
            ->whereIn('s.status', ['scheduled', 'waiting', 'in_progress'])
            ->orderBy('s.planned_start', 'asc')
            ->select(['s.id', 's.planned_start', 's.planned_duration', 's.status', 'g.gate_number', 'w.wh_name as warehouse_name', 'w.wh_code as warehouse_code']);

        if ($plannedGateId !== null) {
            $q->where('s.planned_gate_id', $plannedGateId);
        }

        $rows = $q->get();

        $data = [];
        foreach ($rows as $row) {
            $start = (string) ($row->planned_start ?? '');
            $finish = null;
            if (! empty($row->planned_start) && ! empty($row->planned_duration)) {
                try {
                    $dt = new DateTime((string) $row->planned_start);
                    $dt->modify('+' . (int) $row->planned_duration . ' minutes');
                    $finish = $dt->format('Y-m-d H:i:s');
                } catch (\Throwable $e) {
                    $finish = null;
                }
            }

            $gateLabel = $this->slotService->getGateDisplayName((string) ($row->warehouse_code ?? ''), (string) ($row->gate_number ?? ''));

            $data[] = [
                'id' => (int) ($row->id ?? 0),
                'planned_start' => $start,
                'planned_finish' => $finish,
                'status' => $row->status,
                'gate' => $gateLabel,
                'warehouse' => $row->warehouse_name,
            ];
        }

        return response()->json(['success' => true, 'items' => $data]);
    }

    public function export()
    {
        $slots = Slot::with(['warehouse', 'plannedGate', 'actualGate'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'slots_export_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new SlotsExport($slots), $filename);
    }
}
