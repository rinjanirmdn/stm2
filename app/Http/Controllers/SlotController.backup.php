<?php

namespace App\Http\Controllers;

use App\Services\SlotService;
use App\Services\PoSearchService;
use App\Services\SlotConflictService;
use App\Services\SlotFilterService;
use App\Services\TimeCalculationService;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
        private readonly TimeCalculationService $timeService
    ) {
    }

    public function ajaxPoSearch(Request $request)
    {
        $results = $this->poSearchService->searchPo($request->get('q', ''));

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    public function ajaxPoDetail(string $poNumber)
    {
        $poNumber = trim($poNumber);
        if ($poNumber === '') {
            return response()->json(['success' => false, 'message' => 'PO/DO number is required']);
        }

        $po = $this->poSearchService->getPoDetail($poNumber);

        if (!$po) {
            return response()->json(['success' => false, 'message' => 'PO/DO not found']);
        }

        return response()->json(['success' => true, 'data' => $po]);
    }

    public function searchSuggestions(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        $like = '%' . $q . '%';

        $rows = DB::table('slots as s')
            ->join('po as t', 's.po_id', '=', 't.id')
            ->join('md_warehouse as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('vendors as v', 's.vendor_id', '=', 'v.id')
            ->where(function ($sub) use ($like) {
                $sub->where('s.po_number', 'like', $like)
                    ->orWhere('s.mat_doc', 'like', $like)
                    ->orWhere('v.name', 'like', $like);
            })
            ->where('s.status', '<>', 'completed')
            ->select([
                's.po_number as truck_number',
                's.mat_doc',
                'v.name as vendor_name',
                'w.name as warehouse_name',
            ])
            ->orderByRaw("CASE
                WHEN s.po_number LIKE ? THEN 1
                WHEN COALESCE(s.mat_doc, '') LIKE ? THEN 2
                WHEN v.name LIKE ? THEN 3
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
            ->join('po as t', 's.po_id', '=', 't.id')
            ->join('md_warehouse as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('vendors as v', 's.vendor_id', '=', 'v.id')
            ->leftJoin('md_gates as pg', 's.planned_gate_id', '=', 'pg.id')
            ->leftJoin('md_gates as ag', 's.actual_gate_id', '=', 'ag.id')
            ->leftJoin('md_warehouse as wpg', 'pg.warehouse_id', '=', 'wpg.id')
            ->leftJoin('md_warehouse as wag', 'ag.warehouse_id', '=', 'wag.id')
            ->where('s.id', $slotId)
            ->select([
                's.*',
                's.po_number as truck_number',
                'w.name as warehouse_name',
                'w.code as warehouse_code',
                'v.name as vendor_name',
                'pg.gate_number as planned_gate_number',
                'ag.gate_number as actual_gate_number',
                'wpg.code as planned_gate_warehouse_code',
                'wag.code as actual_gate_warehouse_code',
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
        $sort = trim($request->query('sort', ''));
        $dir = $this->filterService->validateSortDirection($request->query('dir', 'desc'));
        $pageSize = $this->filterService->validatePageSize($request->query('page_size', 'all'));

        // Build filtered query
        $query = $this->filterService->filterSlots($request);

        // Apply sorting
        $query = $this->filterService->applySorting($query, $sort, $dir);

        // Apply page size limit
        $query = $this->filterService->applyPageSize($query, $pageSize);

        $slots = $query->get();

        // Recalculate blocking risk for each slot to ensure real-time accuracy (except cancelled slots)
        foreach ($slots as $slot) {
            if ((string) ($slot->status ?? '') !== 'cancelled') {
                $currentRiskLevel = $this->slotService->calculateBlockingRisk(
                    (int) $slot->warehouse_id,
                    $slot->planned_gate_id ? (int) $slot->planned_gate_id : null,
                    (string) ($slot->planned_start ?? ''),
                    (int) ($slot->planned_duration ?? 0),
                    (int) ($slot->id ?? 0) ?: null
                );
                $slot->blocking = $currentRiskLevel; // Override the database value
            }
        }

        // Get filter options
        $filterOptions = $this->filterService->getFilterOptions();

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
            'date_from' => trim($request->query('date_from', '')),
            'date_to' => trim($request->query('date_to', '')),
            'warehouseFilter' => $warehouseValues,
            'gateFilter' => $gateValues,
            'statusFilter' => $statusValues,
            'directionFilter' => $dirValues,
            'lateFilter' => $lateValues,
            'blockingFilter' => $blockingValues,
            'pageSize' => $pageSize,
            'warehouses' => $filterOptions['warehouses'],
            'gates' => $filterOptions['gates'],
        ]);
    }

    public function create()
    {
        $warehouses = DB::table('md_warehouse')->orderBy('name')->get();
        $vendors = DB::table('vendors')->orderBy('name')->get();
        $gates = DB::table('md_gates as g')
            ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id')
            ->where('g.is_active', 1)
            ->orderBy('w.name')
            ->orderBy('g.gate_number')
            ->select(['g.*', 'w.name as warehouse_name', 'w.code as warehouse_code'])
            ->get();

        $truckTypes = $this->getTruckTypeOptions();
        $truckTypeDurations = DB::table('md_truck')
            ->orderBy('truck_type')
            ->pluck('target_duration_minutes', 'truck_type')
            ->all();

        return view('slots.create', [
            'warehouses' => $warehouses,
            'vendors' => $vendors,
            'gates' => $gates,
            'truckTypes' => $truckTypes,
            'truckTypeDurations' => $truckTypeDurations,
        ]);
    }

    public function store(Request $request)
    {
        $truckNumber = trim((string) ($request->input('po_number', $request->input('truck_number', ''))));
        $truckNumberInput = trim((string) $request->input('truck_number', ''));
        $direction = (string) $request->input('direction', '');
        $warehouseId = (int) $request->input('warehouse_id', 0);
        $truckType = trim((string) $request->input('truck_type', ''));

        if ($truckNumber !== '' && strlen($truckNumber) > 12) {
            return back()->withInput()->withErrors(['po_number' => 'PO/DO number max 12 karakter']);
        }

        $vendorType = $direction === 'outbound' ? 'customer' : 'supplier';
        $vendorId = $request->input('vendor_id') !== null && (string) $request->input('vendor_id') !== '' ? (int) $request->input('vendor_id') : null;
        $plannedGateId = $request->input('planned_gate_id') !== null && (string) $request->input('planned_gate_id') !== '' ? (int) $request->input('planned_gate_id') : null;
        $plannedStart = (string) $request->input('planned_start', '');

        if ($truckNumber === '' || $warehouseId === 0 || $plannedStart === '' || $direction === '' || $truckType === '') {
            return back()->withInput()->with('error', 'PO/DO number, direction, warehouse, and planned start are required');
        }

        // Get planned duration from master data, or use manual input if not found
        $plannedDurationMinutes = (int) DB::table('md_truck')
            ->where('truck_type', $truckType)
            ->value('target_duration_minutes');

        if ($plannedDurationMinutes <= 0) {
            // If no duration found in master data, use manual input
            $plannedDurationMinutes = (int) $request->input('planned_duration', 0);
            if ($plannedDurationMinutes <= 0) {
                return back()->withInput()->with('error', 'Truck Type does not have a standard duration. Please enter manual duration (in minutes).');
            }
        }

        if ($vendorId !== null) {
            $vendor = DB::table('vendors')->where('id', $vendorId)->select(['type'])->first();
            if (! $vendor || (string) ($vendor->type ?? '') !== $vendorType) {
                return back()->withInput()->with('error', 'Selected vendor does not match vendor type');
            }
        }

        if ($plannedGateId !== null) {
            $gate = DB::table('md_gates')->where('id', $plannedGateId)->where('is_active', 1)->select(['warehouse_id'])->first();
            if (! $gate || (int) ($gate->warehouse_id ?? 0) !== $warehouseId) {
                return back()->withInput()->with('error', 'Selected gate does not belong to chosen warehouse or is inactive');
            }
        }

        $plannedStartDt = null;
        try {
            $plannedStartDt = new DateTime($plannedStart);
        } catch (\Throwable $e) {
            $plannedStartDt = null;
        }
        if (! $plannedStartDt) {
            return back()->withInput()->with('error', 'Invalid planned start time');
        }

        $plannedEndDt = clone $plannedStartDt;
        $plannedEndDt->modify('+' . (int) $plannedDurationMinutes . ' minutes');

        if ($plannedGateId === null && $warehouseId > 0 && $plannedStart !== '') {
            $candidateGates = DB::table('md_gates')
                ->where('warehouse_id', $warehouseId)
                ->where('is_active', 1)
                ->orderBy('gate_number')
                ->select(['id'])
                ->get();

            if ($candidateGates->isNotEmpty()) {
                $bestGateId = null;
                $bestRisk = null;

                $bestValidGateId = null;
                $bestValidRisk = null;

                $startStr = $plannedStartDt->format('Y-m-d H:i:s');
                $endStr = $plannedEndDt->format('Y-m-d H:i:s');

                foreach ($candidateGates as $cg) {
                    $gid = (int) $cg->id;

                    $risk = $this->slotService->calculateBlockingRisk($warehouseId, $gid, $plannedStart, $plannedDurationMinutes);

                    $laneGroup = $this->slotService->getGateLaneGroup($gid);
                    $laneGateIds = $laneGroup ? $this->slotService->getGateIdsByLaneGroup($laneGroup) : [$gid];
                    if (empty($laneGateIds)) {
                        $laneGateIds = [$gid];
                    }

                    $overlapCount = $this->slotService->checkLaneOverlap($laneGateIds, $startStr, $endStr);

                    $isValid = $overlapCount === 0;
                    if ($isValid) {
                        $bcCheck = $this->slotService->validateWh2BcPlannedWindow($gid, $plannedStartDt, $plannedEndDt);
                        $isValid = ! empty($bcCheck['ok']);
                    }

                    if ($isValid && ($bestValidRisk === null || $risk < $bestValidRisk)) {
                        $bestValidRisk = $risk;
                        $bestValidGateId = $gid;
                    }

                    if ($bestRisk === null || $risk < $bestRisk) {
                        $bestRisk = $risk;
                        $bestGateId = $gid;
                    }
                }

                if ($bestValidGateId !== null) {
                    $plannedGateId = $bestValidGateId;
                } else {
                    // No valid gate available without conflicts
                    return back()->withInput()->with('error', 'Cannot create slot because all gates have conflicting schedules at the selected time. Please choose another time.');
                }
            }
        }

        if ($plannedGateId !== null) {
            $laneGroup = $this->slotService->getGateLaneGroup($plannedGateId);
            $laneGateIds = $laneGroup ? $this->slotService->getGateIdsByLaneGroup($laneGroup) : [$plannedGateId];
            if (empty($laneGateIds)) {
                $laneGateIds = [$plannedGateId];
            }

            $startStr = $plannedStartDt->format('Y-m-d H:i:s');
            $endStr = $plannedEndDt->format('Y-m-d H:i:s');

            $overlapCount = $this->slotService->checkLaneOverlap($laneGateIds, $startStr, $endStr);

            if ($overlapCount > 0) {
                return back()->withInput()->with('error', 'Planned time overlaps with another slot on the same lane');
            }

            $bcCheck = $this->slotService->validateWh2BcPlannedWindow($plannedGateId, $plannedStartDt, $plannedEndDt);
            if (empty($bcCheck['ok'])) {
                return back()->withInput()->with('error', (string) ($bcCheck['message'] ?? 'Invalid planned window'));
            }
        }

        $blockingRisk = $this->slotService->calculateBlockingRisk($warehouseId, $plannedGateId, $plannedStart, $plannedDurationMinutes);
        if ($blockingRisk >= 2) {
            return back()->withInput()->with('error', 'Cannot create slot due to high blocking risk. Please choose another gate or time.');
        }

        $slotId = (int) DB::transaction(function () use ($truckNumber, $truckNumberInput, $direction, $warehouseId, $vendorId, $plannedGateId, $plannedStart, $plannedDurationMinutes, $blockingRisk, $truckType, $plannedStartDt) {
            $truck = DB::table('po')->where('po_number', $truckNumber)->select(['id'])->first();
            if ($truck) {
                $truckId = (int) $truck->id;
            } else {
                $truckId = (int) DB::table('po')->insertGetId([
                    'po_number' => $truckNumber,
                    'truck_number' => $truckNumber, // Using truck_number as fallback
                    'truck_type' => $truckType,
                    'vendor_id' => $vendorId,
                    'direction' => $direction,
                    'warehouse_id' => $warehouseId,
                    'is_active' => true,
                ]);
            }

            $slotId = (int) DB::table('slots')->insertGetId([
                'po_id' => $truckId,
                'direction' => $direction,
                'warehouse_id' => $warehouseId,
                'vendor_id' => $vendorId,
                'planned_gate_id' => $plannedGateId,
                'planned_start' => $plannedStart,
                'status' => 'scheduled',
                'is_late' => 0,
                'moved_gate' => 0,
                'blocking_risk' => $blockingRisk,
                'planned_duration' => $plannedDurationMinutes,
                'truck_type' => $truckType,
                'created_by' => Auth::id(),
                'ticket_number' => null,
                'slot_type' => 'planned',
            ]);

        $yy = $plannedStartDt ? $plannedStartDt->format('y') : date('y');
        $monthNum = (int) ($plannedStartDt ? $plannedStartDt->format('n') : date('n'));
        $monthNum = max(1, min(12, $monthNum));
        $monthLetter = chr(ord('A') + $monthNum - 1);

        $prefix = 'Z';
        if (!empty($plannedGateId)) {
            $gateInfo = DB::table('md_gates as g')
                ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id')
                ->where('g.id', (int) $plannedGateId)
                ->select(['w.code as warehouse_code', 'g.gate_number'])
                ->first();
            $whCode = strtoupper(trim((string) ($gateInfo->warehouse_code ?? '')));
            $gn = trim((string) ($gateInfo->gate_number ?? ''));

            if ($whCode === 'WH1' && $gn === 'A') {
                $prefix = 'A';
            } elseif ($whCode === 'WH2' && $gn === 'B') {
                $prefix = 'B';
            } elseif ($whCode === 'WH2' && $gn === 'C') {
                $prefix = 'C';
            } elseif ($whCode === 'WH1') {
                $prefix = 'A';
            } elseif ($whCode === 'WH2' && $prefix === 'Z') {
                // Only use fallback if no specific gate was matched
                $prefix = 'B';
            }
        }

        $lockKey = 'ticket_seq_' . $prefix . $yy . $monthLetter;
        $locked = false;

        // Skip locking for SQLite (testing environment)
        $isSqlite = DB::getDriverName() === 'sqlite';

        try {
            if (!$isSqlite) {
                $lockRow = DB::selectOne('SELECT GET_LOCK(?, 5) as l', [$lockKey]);
                $locked = (int) (($lockRow->l ?? 0)) === 1;
            }

            $ticketPrefix = $prefix . $yy . $monthLetter;
            $pattern = $ticketPrefix . '%';
            $lastTicket = DB::table('slots')
                ->where('ticket_number', 'like', $pattern)
                ->orderByDesc('ticket_number')
                ->value('ticket_number');

            $seq = 1;
            if (is_string($lastTicket) && preg_match('/(\d{4})$/', $lastTicket, $m)) {
                $seq = ((int) $m[1]) + 1;
            }

            $ticketNumber = $ticketPrefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            DB::table('slots')->where('id', $slotId)->update([
                'ticket_number' => $ticketNumber,
            ]);
        } finally {
            if ($locked && !$isSqlite) {
                try {
                    DB::select('SELECT RELEASE_LOCK(?)', [$lockKey]);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }

            $this->slotService->logActivity($slotId, 'status_change', 'Slot created');

            return $slotId;
        });

        return redirect()->route('slots.show', ['slotId' => $slotId])->with('success', 'Slot created successfully');
    }

    public function unplannedIndex(Request $request)
    {
        $pageTitle = 'Unplanned';

        // Get request parameters
        $sort = $request->get('sort', '');
        $dir = $request->get('dir', 'desc');
        $pageSize = $request->get('page_size', '50');

        // If no sort specified, use default for query but don't pass to view
        $actualSort = $sort ?: 'arrival_time';

        // If sort is explicitly 'reset', use default but don't pass to view
        $isResetSort = ($sort === 'reset');
        if ($isResetSort) {
            $actualSort = 'arrival_time';
            $sort = '';
        }

        // Build query
        $query = DB::table('slots as s')
            ->leftJoin('po as t', 's.po_id', '=', 't.id')
            ->join('md_warehouse as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('vendors as v', 's.vendor_id', '=', 'v.id')
            ->leftJoin('md_gates as g', 's.actual_gate_id', '=', 'g.id')
            ->whereRaw("COALESCE(s.slot_type, 'planned') = 'unplanned'")
            ->select([
                's.*',
                's.po_number as truck_number',
                'w.name as warehouse_name',
                'w.code as warehouse_code',
                'v.name as vendor_name',
                'g.gate_number as actual_gate_number',
            ]);

        // Apply filters
        if ($request->filled('q')) {
            $search = '%' . $request->get('q') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('s.po_number', 'like', $search)
                  ->orWhere('s.mat_doc', 'like', $search)
                  ->orWhere('v.name', 'like', $search)
                  ->orWhere('s.sj_complete_number', 'like', $search);
            });
        }

        if ($request->filled('po_number')) {
            $query->where('s.po_number', 'like', '%' . $request->get('po_number') . '%');
        }

        if ($request->filled('mat_doc')) {
            $query->where('s.mat_doc', 'like', '%' . $request->get('mat_doc') . '%');
        }

        if ($request->filled('vendor')) {
            $query->where('v.name', 'like', '%' . $request->get('vendor') . '%');
        }

        if ($request->filled('warehouse')) {
            $query->where('w.name', $request->get('warehouse'));
        }

        if ($request->filled('direction')) {
            $query->where('s.direction', $request->get('direction'));
        }

        if ($request->filled('sj_number')) {
            $query->where('s.sj_complete_number', 'like', '%' . $request->get('sj_number') . '%');
        }

        // Apply sorting
        if ($actualSort === 'arrival_time') {
            $query->orderBy('s.arrival_time', $dir);
        } elseif ($actualSort === 'po') {
            $query->orderBy('s.po_number', $dir);
        } elseif ($actualSort === 'mat_doc') {
            $query->orderBy('s.mat_doc', $dir);
        } elseif ($actualSort === 'vendor') {
            $query->orderBy('v.name', $dir);
        } elseif ($actualSort === 'warehouse') {
            $query->orderBy('w.name', $dir);
        } else {
            $query->orderBy('s.arrival_time', 'desc');
        }

        // Apply pagination
        if ($pageSize === 'all') {
            $unplannedSlots = $query->get();
        } else {
            $limit = is_numeric($pageSize) ? (int) $pageSize : 50;
            $unplannedSlots = $query->limit($limit)->get();
        }

        // Get warehouses and gates for filter dropdowns
        $warehouses = DB::table('md_warehouse')
            ->orderBy('name')
            ->pluck('name', 'name')
            ->toArray();

        $gates = DB::table('md_gates as g')
            ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id')
            ->where('g.is_active', 1)
            ->orderBy('w.name')
            ->orderBy('g.gate_number')
            ->select(['g.*', 'w.name as warehouse_name'])
            ->get();

        // Prepare data for view
        $viewData = compact('unplannedSlots', 'warehouses', 'gates', 'pageTitle');

        // If sort was reset, pass empty sort to view to clear indicators
        if ($isResetSort) {
            $viewData['sort'] = '';
            $viewData['dir'] = 'desc';
        } else {
            $viewData['sort'] = $sort;
            $viewData['dir'] = $dir;
        }

        return view('unplanned.index', $viewData);
    }

    public function unplannedCreate()
    {
        $warehouses = DB::table('md_warehouse')->orderBy('name')->get();
        $vendors = DB::table('vendors')->orderBy('name')->get();
        $gates = DB::table('md_gates as g')
            ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id')
            ->where('g.is_active', 1)
            ->orderBy('w.name')
            ->orderBy('g.gate_number')
            ->select(['g.*', 'w.name as warehouse_name'])
            ->get();

        $truckTypes = $this->getTruckTypeOptions();

        return view('unplanned.create', compact('warehouses', 'vendors', 'gates', 'truckTypes'));
    }

    public function unplannedStore(Request $request)
    {
        $truckNumber = trim((string) ($request->input('po_number', $request->input('truck_number', ''))));
        $direction = (string) $request->input('direction', 'inbound');
        $warehouseId = (int) $request->input('warehouse_id');
        $vendorId = (int) $request->input('vendor_id');
        $gateId = (int) $request->input('gate_id');
        $matDoc = trim((string) $request->input('mat_doc', ''));
        $sjNumber = trim((string) $request->input('sj_number', ''));
        $notes = trim((string) $request->input('notes', ''));

        $arrivalTime = now();
        $status = 'arrived';

        $slotId = DB::transaction(function () use ($truckNumber, $direction, $warehouseId, $vendorId, $gateId, $matDoc, $sjNumber, $notes, $arrivalTime, $status) {
            $slotId = DB::table('slots')->insertGetId([
                'po_id' => null,
                'truck_number' => $truckNumber,
                'direction' => $direction,
                'warehouse_id' => $warehouseId,
                'vendor_id' => $vendorId,
                'planned_gate_id' => $gateId,
                'actual_gate_id' => $gateId,
                'mat_doc' => $matDoc,
                'sj_complete_number' => $sjNumber,
                'planned_start' => null,
                'planned_duration' => 0,
                'created_by' => Auth::id(),
                'ticket_number' => null,
                'slot_type' => 'unplanned',
                'arrival_time' => $arrivalTime,
                'actual_start' => null,
                'actual_finish' => null,
                'status' => $status,
                'late_reason' => $notes !== '' ? $notes : null,
            ]);

            $this->slotService->logActivity($slotId, 'status_change', 'Unplanned arrival recorded as ' . $status);
        });

        return redirect()->route('unplanned.index')->with('success', 'Unplanned transaction recorded successfully');
    }

    public function show(int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Slot not found');
        }

        $slotType = (string) ($slot->slot_type ?? 'planned');
        $isUnplanned = $slotType === 'unplanned';

        $plannedFinish = $this->slotService->computePlannedFinish((string) ($slot->planned_start ?? ''), (int) ($slot->planned_duration ?? 0));
        $leadMinutes = $this->minutesDiff($slot->arrival_time ?? null, $slot->actual_start ?? null);
        $processMinutes = $this->minutesDiff($slot->actual_start ?? null, $slot->actual_finish ?? null);

        $activityLogs = DB::table('activity_logs as al')
            ->join('md_users as u', 'al.user_id', '=', 'u.id')
            ->where('al.slot_id', $slotId)
            ->orderBy('al.created_at', 'desc')
            ->select(['al.*', 'u.username'])
            ->get();

        $viewName = $isUnplanned ? 'unplanned.show' : 'slots.show';

        return view($viewName, [
            'slot' => $slot,
            'isUnplanned' => $isUnplanned,
            'plannedFinish' => $plannedFinish,
            'leadMinutes' => $leadMinutes,
        ]);
    }

    public function unplannedStore(Request $request)
    {
        $warehouseId = (int) $request->input('warehouse_id', 0);
        $vendorId = $request->input('vendor_id') !== null && (string) $request->input('vendor_id') !== '' ? (int) $request->input('vendor_id') : null;
        $actualGateId = $request->input('actual_gate_id') !== null && (string) $request->input('actual_gate_id') !== '' ? (int) $request->input('actual_gate_id') : null;
        $arrivalTime = (string) $request->input('arrival_time', $request->input('actual_arrival', ''));
        $markWaiting = (string) $request->input('set_waiting', '0') === '1';

        // Validate actual_gate_id belongs to warehouse
        if ($actualGateId) {
            $gate = DB::table('md_gates')->where('id', $actualGateId)->where('is_active', 1)->select(['warehouse_id'])->first();
            if (!$gate || (int)($gate->warehouse_id ?? 0) !== $warehouseId) {
                return back()->withInput()->with('error', 'Selected gate does not belong to chosen warehouse or is inactive');
            }
        }

        if ($truckNumber !== '' && strlen($truckNumber) > 12) {
            return back()->withInput()->withErrors(['po_number' => 'PO/DO number max 12 karakter']);
        }

        $matDoc = trim((string) $request->input('mat_doc', ''));
        $sjNumber = trim((string) $request->input('sj_number', ''));
        $truckType = trim((string) $request->input('truck_type', ''));
        $vehicleNumber = trim((string) $request->input('vehicle_number_snap', ''));
        $driverNumber = trim((string) $request->input('driver_number', ''));
        $notes = trim((string) $request->input('notes', ''));

        if ($truckNumber === '' || $warehouseId === 0 || $arrivalTime === '') {
            return back()->withInput()->with('error', 'PO/DO number, warehouse, and arrival time are required');
        }

        DB::transaction(function () use ($truckNumber, $direction, $warehouseId, $vendorId, $actualGateId, $arrivalTime, $markWaiting, $matDoc, $sjNumber, $truckType, $vehicleNumber, $driverNumber, $notes) {
            $truck = DB::table('po')->where('po_number', $truckNumber)->select(['id'])->first();
            if ($truck) {
                $truckId = (int) $truck->id;
            } else {
                $truckId = (int) DB::table('po')->insertGetId([
                    'po_number' => $truckNumber,
                ]);
            }

            $status = $markWaiting ? 'waiting' : 'arrived';

            $slotId = (int) DB::table('slots')->insertGetId([
                'po_id' => $truckId,
                'direction' => $direction,
                'warehouse_id' => $warehouseId,
                'vendor_id' => $vendorId,
                'planned_gate_id' => null,
                'planned_start' => $arrivalTime,
                'status' => $status,
                'is_late' => 0,
                'moved_gate' => 0,
                'blocking_risk' => 0,
                'planned_duration' => 0,
                'created_by' => Auth::id(),
                'ticket_number' => null,
                'slot_type' => 'unplanned',
                'arrival_time' => $arrivalTime,
                'actual_start' => null,
                'actual_finish' => null,
                'actual_gate_id' => $actualGateId,
                'mat_doc' => $matDoc !== '' ? $matDoc : null,
                'sj_complete_number' => $sjNumber !== '' ? $sjNumber : null,
                'truck_type' => $truckType !== '' ? $truckType : null,
                'vehicle_number_snap' => $vehicleNumber !== '' ? $vehicleNumber : null,
                'driver_number' => $driverNumber !== '' ? $driverNumber : null,
                'late_reason' => $notes !== '' ? $notes : null,
            ]);

            $this->slotService->logActivity($slotId, 'status_change', 'Unplanned arrival recorded as ' . $status);
        });

        return redirect()->route('unplanned.index')->with('success', 'Unplanned transaction recorded successfully');
    }

    public function show(int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Slot not found');
        }

        // Recalculate blocking risk to get the latest value (only for non-cancelled slots)
        if ((string) ($slot->status ?? '') !== 'cancelled') {
            $currentRiskLevel = $this->slotService->calculateBlockingRisk(
                (int) $slot->warehouse_id,
                $slot->planned_gate_id ? (int) $slot->planned_gate_id : null,
                (string) ($slot->planned_start ?? ''),
                (int) ($slot->planned_duration ?? 0),
                $slotId
            );

            // Override the old blocking_risk with the calculated one
            $slot->blocking_risk = $currentRiskLevel;

            // Also update database to keep it in sync
            DB::table('slots')->where('id', $slotId)->update(['blocking_risk' => $currentRiskLevel]);
        }

        $slotType = (string) ($slot->slot_type ?? 'planned');
        $isUnplanned = $slotType === 'unplanned';

        $plannedFinish = $this->slotService->computePlannedFinish((string) ($slot->planned_start ?? ''), (int) ($slot->planned_duration ?? 0));
        $leadMinutes = $this->minutesDiff($slot->arrival_time ?? null, $slot->actual_start ?? null);
        $processMinutes = $this->minutesDiff($slot->actual_start ?? null, $slot->actual_finish ?? null);

        $logs = DB::table('activity_logs as al')
            ->leftJoin('md_users as u', 'al.created_by', '=', 'u.id')
            ->where('al.slot_id', $slotId)
            ->orderBy('al.created_at', 'asc')
            ->select(['al.*', 'u.username'])
            ->get();

        $viewName = $isUnplanned ? 'unplanned.show' : 'slots.show';

        return view($viewName, [
            'slot' => $slot,
            'isUnplanned' => $isUnplanned,
            'plannedFinish' => $plannedFinish,
            'leadMinutes' => $leadMinutes,
            'processMinutes' => $processMinutes,
            'logs' => $logs,
        ]);
    }

    public function edit(int $slotId)
    {
        $warehouses = DB::table('md_warehouse')->orderBy('name')->get();
        $vendors = DB::table('vendors')->orderBy('name')->get();
        $gates = DB::table('md_gates as g')
            ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id')
            ->where('g.is_active', 1)
            ->orderBy('w.name')
            ->orderBy('g.gate_number')
            ->select(['g.*', 'w.name as warehouse_name', 'w.code as warehouse_code'])
            ->get();
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Slot not found');
        }

        if (((string) ($slot->slot_type ?? 'planned')) !== 'planned' || (string) ($slot->status ?? '') !== 'scheduled') {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Only scheduled planned slots can be edited');
        }

        return view('slots.edit', [
            'slot' => $slot,
            'warehouses' => $warehouses,
            'vendors' => $vendors,
            'gates' => $gates,
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

        $truckNumber = trim((string) ($request->input('po_number', $request->input('truck_number', ''))));
        $direction = (string) $request->input('direction', '');
        $warehouseId = (int) $request->input('warehouse_id', 0);
        $vendorId = $request->input('vendor_id') !== null && (string) $request->input('vendor_id') !== '' ? (int) $request->input('vendor_id') : null;
        $plannedGateId = $request->input('planned_gate_id') !== null && (string) $request->input('planned_gate_id') !== '' ? (int) $request->input('planned_gate_id') : null;
        $plannedStart = (string) $request->input('planned_start', '');
        $plannedDurationMinutes = (int) $request->input('planned_duration', 60);

        if ($truckNumber !== '' && strlen($truckNumber) > 12) {
            return back()->withInput()->withErrors(['po_number' => 'PO/DO number max 12 karakter']);
        }

        if ($truckNumber === '' || $warehouseId === 0 || $plannedStart === '' || $direction === '') {
            return back()->withInput()->with('error', 'PO/DO number, direction, warehouse, and planned start are required');
        }

        if ($plannedGateId !== null) {
            $gate = DB::table('md_gates')->where('id', $plannedGateId)->where('is_active', 1)->select(['warehouse_id'])->first();
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
                ->whereIn('status', ['scheduled', 'arrived', 'waiting', 'in_progress'])
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

        DB::transaction(function () use ($slotId, $truckNumber, $direction, $warehouseId, $vendorId, $plannedGateId, $plannedStart, $plannedDurationMinutes) {
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
                'vendor_id' => $vendorId,
                'planned_gate_id' => $plannedGateId,
                'planned_start' => $plannedStart,
                'planned_duration' => $plannedDurationMinutes,
            ]);

            $this->slotService->logActivity($slotId, 'status_change', 'Slot updated');
        });

        return redirect()->route('slots.show', ['slotId' => $slotId])->with('success', 'Slot updated successfully');
    }

    public function arrival(int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Slot not found');
        }

        $allowed = ['scheduled', 'arrived', 'waiting'];
        if (! in_array((string) ($slot->status ?? ''), $allowed, true)) {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Arrival can only be recorded for pending slots');
        }

        $truckTypes = $this->getTruckTypeOptions();

        return view('slots.arrival', [
            'slot' => $slot,
            'truckTypes' => $truckTypes,
        ]);
    }

    public function arrivalStore(Request $request, int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Slot not found');
        }

        $allowed = ['scheduled', 'arrived', 'waiting'];
        if (! in_array((string) ($slot->status ?? ''), $allowed, true)) {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Arrival can only be recorded for pending slots');
        }

        $ticketNumber = trim((string) $request->input('ticket_number', ''));
        $sjNumber = trim((string) $request->input('sj_number', ''));
        $truckType = trim((string) $request->input('truck_type', ''));
        $markWaiting = (string) $request->input('set_waiting', '0') === '1';

        if ($ticketNumber === '' || $sjNumber === '' || $truckType === '') {
            return back()->withInput()->with('error', 'Ticket number, Surat Jalan number, and Truck Type are required');
        }

        DB::transaction(function () use ($slot, $slotId, $ticketNumber, $sjNumber, $truckType, $markWaiting) {
            $now = date('Y-m-d H:i:s');
            $firstArrival = empty($slot->arrival_time);

            $arrivalTime = $firstArrival ? $now : (string) ($slot->arrival_time ?? $now);
            $nextStatus = (string) ($slot->status ?? 'scheduled');

            if ($firstArrival) {
                $nextStatus = $markWaiting ? 'waiting' : 'arrived';
            } elseif ($markWaiting && (string) ($slot->status ?? '') === 'arrived') {
                $nextStatus = 'waiting';
            }

            DB::table('slots')->where('id', $slotId)->update([
                'arrival_time' => $arrivalTime,
                'ticket_number' => $ticketNumber,
                'sj_start_number' => $sjNumber,
                'truck_type' => $truckType !== '' ? $truckType : ($slot->truck_type ?? null),
                'status' => $nextStatus,
            ]);

            $gateName = '';
            if (!empty($slot->planned_gate_id) && !empty($slot->planned_gate_number)) {
                $gateName = $this->buildGateLabel((string) ($slot->planned_gate_warehouse_code ?? $slot->warehouse_code ?? ''), (string) ($slot->planned_gate_number ?? ''));
            } else {
                $gateName = (string) ($slot->warehouse_name ?? '');
            }

            if ($firstArrival) {
                $this->slotService->logActivity($slotId, 'status_change', 'Status changed to ' . $nextStatus . ' after arrival at ' . $gateName);
                $this->slotService->logActivity($slotId, 'arrival_recorded', 'Arrival recorded with ticket ' . $ticketNumber . ' and SJ ' . $sjNumber);
            } else {
                $this->slotService->logActivity($slotId, 'arrival_updated', 'Arrival details updated');
                if ((string) ($slot->status ?? '') !== $nextStatus) {
                    $this->slotService->logActivity($slotId, 'status_change', 'Status changed to ' . $nextStatus . ' after arrival update');
                }
            }
        });

        return redirect()->route('slots.show', ['slotId' => $slotId])->with('success', 'Arrival recorded');
    }

    public function ticket(int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Slot not found');
        }

        return view('slots.ticket', [
            'slot' => $slot,
        ]);
    }

    public function start(int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Slot not found');
        }

        if (! in_array((string) ($slot->status ?? ''), ['arrived', 'waiting'], true)) {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Only arrived/waiting slots can be started');
        }

        if (empty($slot->arrival_time)) {
            return redirect()->route('slots.arrival', ['slotId' => $slotId])->with('error', 'Please record Arrival before starting this slot');
        }

        $gates = DB::table('md_gates as g')
            ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id')
            ->where('g.is_active', 1)
            ->orderBy('w.name')
            ->orderBy('g.gate_number')
            ->select(['g.*', 'w.name as warehouse_name', 'w.code as warehouse_code'])
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

        // Get conflict details using the service
        $conflictSlotIds = array_keys($allConflict);
        $conflictDetails = [];

        if (!empty($conflictSlotIds)) {
            // Use the conflict service to get details
            $details = $this->conflictService->buildConflictMessage($conflictSlotIds);
            // Convert string messages back to expected format for compatibility
            foreach ($details as $index => $message) {
                $conflictDetails[$conflictSlotIds[$index] ?? 0] = (object) ['message' => $message];
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

        $viewName = ((string) ($slot->slot_type ?? 'planned')) === 'unplanned' ? 'unplanned.start' : 'slots.start';

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

        if (! in_array((string) ($slot->status ?? ''), ['arrived', 'waiting'], true)) {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Only arrived/waiting slots can be started');
        }

        if (empty($slot->arrival_time)) {
            return redirect()->route('slots.arrival', ['slotId' => $slotId])->with('error', 'Please record Arrival before starting this slot');
        }

        $actualGateId = $request->input('actual_gate_id') !== null && (string) $request->input('actual_gate_id') !== '' ? (int) $request->input('actual_gate_id') : null;
        if (! $actualGateId) {
            return back()->withInput()->with('error', 'Actual gate is required');
        }

        $gateRow = DB::table('md_gates')->where('id', $actualGateId)->where('is_active', 1)->select(['id', 'warehouse_id'])->first();
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
            $arrivalTime = (string) $slot->arrival_time;
            $isLate = $this->isLateByPlannedStart((string) ($slot->planned_start ?? ''), $now) ? 1 : 0;

            DB::table('slots')->where('id', $slotId)->update([
                'status' => 'in_progress',
                'arrival_time' => $arrivalTime,
                'actual_start' => $now,
                'is_late' => $isLate,
                'actual_gate_id' => $actualGateId,
            ]);

            $gateMeta = $this->slotService->getGateMetaById($actualGateId);
            $gateName = $this->buildGateLabel((string) ($gateMeta['warehouse_code'] ?? ''), (string) ($gateMeta['gate_number'] ?? ''));

            if ($isLate) {
                $this->slotService->logActivity($slotId, 'late_arrival', 'Truck arrived late at ' . $gateName);
            } else {
                $this->slotService->logActivity($slotId, 'early_arrival', 'Truck arrived on time/early at ' . $gateName);
            }
            $this->slotService->logActivity($slotId, 'status_change', 'Slot started at ' . $gateName);
        });

        return redirect()->route('slots.show', ['slotId' => $slotId])->with('success', 'Slot started');
    }

    public function unplannedStart(int $slotId)
    {
        return $this->start($slotId);
    }

    public function unplannedStartStore(Request $request, int $slotId)
    {
        $result = $this->startStore($request, $slotId);

        // Redirect to unplanned show instead of slots show
        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return redirect()->route('unplanned.show', ['slotId' => $slotId])->with('success', 'Unplanned slot started');
        }

        return $result;
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

        $warehouses = DB::table('md_warehouse')->orderBy('name')->get();
        $vendors = DB::table('vendors')->orderBy('name')->get();
        $gates = DB::table('md_gates as g')
            ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id')
            ->where('g.is_active', 1)
            ->orderBy('w.name')
            ->orderBy('g.gate_number')
            ->select(['g.*', 'w.name as warehouse_name', 'w.code as warehouse_code'])
            ->get();

        return view('unplanned.edit', [
            'slot' => $slot,
            'warehouses' => $warehouses,
            'vendors' => $vendors,
            'gates' => $gates,
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

        $truckNumber = trim((string) ($request->input('po_number', $request->input('truck_number', ''))));
        $direction = (string) $request->input('direction', 'inbound');
        $warehouseId = (int) $request->input('warehouse_id', 0);
        $vendorId = $request->input('vendor_id') !== null && (string) $request->input('vendor_id') !== '' ? (int) $request->input('vendor_id') : null;
        $actualGateId = $request->input('actual_gate_id') !== null && (string) $request->input('actual_gate_id') !== '' ? (int) $request->input('actual_gate_id') : null;
        $arrivalTime = (string) $request->input('arrival_time', '');

        if ($truckNumber !== '' && strlen($truckNumber) > 12) {
            return back()->withInput()->withErrors(['po_number' => 'PO/DO number max 12 karakter']);
        }

        $matDoc = trim((string) $request->input('mat_doc', ''));
        $sjNumber = trim((string) $request->input('sj_number', ''));
        $truckType = trim((string) $request->input('truck_type', ''));
        $vehicleNumber = trim((string) $request->input('vehicle_number_snap', ''));
        $driverNumber = trim((string) $request->input('driver_number', ''));
        $notes = trim((string) $request->input('notes', ''));

        if ($truckNumber === '' || $warehouseId === 0 || $arrivalTime === '') {
            return back()->withInput()->with('error', 'PO/DO number, warehouse, and arrival time are required');
        }

        DB::transaction(function () use ($slotId, $truckNumber, $direction, $warehouseId, $vendorId, $actualGateId, $arrivalTime, $matDoc, $sjNumber, $truckType, $vehicleNumber, $driverNumber, $notes) {
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
                'vendor_id' => $vendorId,
                'actual_gate_id' => $actualGateId,
                'arrival_time' => $arrivalTime,
                'planned_start' => $arrivalTime,
                'mat_doc' => $matDoc !== '' ? $matDoc : null,
                'sj_complete_number' => $sjNumber !== '' ? $sjNumber : null,
                'truck_type' => $truckType !== '' ? $truckType : null,
                'vehicle_number_snap' => $vehicleNumber !== '' ? $vehicleNumber : null,
                'driver_number' => $driverNumber !== '' ? $driverNumber : null,
                'late_reason' => $notes !== '' ? $notes : null,
            ]);

            $this->slotService->logActivity($slotId, 'status_change', 'Unplanned transaction updated');
        });

        return redirect()->route('unplanned.create')->with('success', 'Unplanned transaction updated successfully');
    }

    public function complete(int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Slot not found');
        }

        if ((string) ($slot->status ?? '') !== 'in_progress') {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Only in-progress slots can be completed');
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

        $matDoc = trim((string) $request->input('mat_doc', ''));
        $sjNumber = trim((string) $request->input('sj_number', ''));
        $truckType = trim((string) $request->input('truck_type', ''));
        $vehicleNumber = trim((string) $request->input('vehicle_number', ''));
        $driverNumber = trim((string) $request->input('driver_number', ''));
        $notes = trim((string) $request->input('notes', ''));

        if ($matDoc === '' || $sjNumber === '' || $truckType === '' || $vehicleNumber === '' || $driverNumber === '') {
            return back()->withInput()->with('error', 'All required fields must be filled');
        }

        DB::transaction(function () use ($slotId, $matDoc, $sjNumber, $truckType, $vehicleNumber, $driverNumber, $notes) {
            $now = date('Y-m-d H:i:s');

            DB::table('slots')->where('id', $slotId)->update([
                'status' => 'completed',
                'actual_finish' => $now,
                'mat_doc' => $matDoc,
                'sj_complete_number' => $sjNumber,
                'truck_type' => $truckType,
                'vehicle_number_snap' => $vehicleNumber,
                'driver_number' => $driverNumber,
                'late_reason' => $notes !== '' ? $notes : null,
            ]);

            $this->slotService->logActivity($slotId, 'status_change', 'Slot completed with MAT DOC ' . $matDoc . ', SJ ' . $sjNumber . ', truck ' . $truckType . ', vehicle ' . $vehicleNumber . ', driver ' . $driverNumber);
        });

        return redirect()->route('slots.show', ['slotId' => $slotId])->with('success', 'Slot completed');
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
            $this->slotService->logActivity($slotId, 'status_change', 'Slot cancelled', null, ['reason' => $reason, 'cancelled_at' => $now]);
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
                ->whereIn('status', ['scheduled', 'arrived', 'waiting', 'in_progress'])
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
                    ->whereIn('status', ['scheduled', 'arrived', 'waiting', 'in_progress'])
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
            ->where('g.is_active', 1)
            ->orderBy('g.gate_number')
            ->select(['g.id', 'g.gate_number', 'w.code as warehouse_code', 'w.name as warehouse_name'])
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
                ->whereIn('status', ['scheduled', 'arrived', 'waiting', 'in_progress'])
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
            ->whereIn('s.status', ['scheduled', 'arrived', 'waiting', 'in_progress'])
            ->orderBy('s.planned_start', 'asc')
            ->select(['s.id', 's.planned_start', 's.planned_duration', 's.status', 'g.gate_number', 'w.name as warehouse_name', 'w.code as warehouse_code']);

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
}
