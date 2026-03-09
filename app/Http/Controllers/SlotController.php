<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\SlotHelperTrait;
use App\Services\SlotService;
use App\Services\PoSearchService;
use App\Services\SlotConflictService;
use App\Services\SlotFilterService;
use App\Services\TimeCalculationService;
use App\Models\Slot;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SlotController extends Controller
{
    use SlotHelperTrait;

    public function __construct(
        private readonly SlotService $slotService,
        private readonly PoSearchService $poSearchService,
        private readonly SlotConflictService $conflictService,
        private readonly SlotFilterService $filterService,
        private readonly TimeCalculationService $timeService
    ) {
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

        $slotsCacheKey = 'slots:index:data:' . sha1(json_encode([
            'uid' => Auth::id(),
            'query' => $request->query(),
            'version' => (string) Cache::get('st_realtime_version', '0'),
        ]));
        $slots = Cache::remember($slotsCacheKey, now()->addSeconds(10), function () use ($query) {
            return $query->get();
        });

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
            'pageTitle' => 'Planned',
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
            'direction' => 'required|in:inbound,outbound',
            'truck_type' => 'required|string|max:100',
            'planned_gate_id' => 'required|integer|exists:md_gates,id',
            'planned_start' => 'required|string',
            'planned_duration' => 'required|integer|min:1|max:1440',
            'vehicle_number_snap' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:50',
            'driver_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
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

        $poNumber = $truckNumber;
        $poDetail = $this->poSearchService->getPoDetail($poNumber);
        if (!$poDetail) {
            return back()->withInput()->with('error', 'PO/DO not found in SAP.');
        }

        if ($truckNumber !== '' && strlen($truckNumber) > 12) {
            return back()->withInput()->withErrors(['po_number' => 'PO/DO number max 12 characters']);
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
                return back()->withInput()->with('error', 'Planned time overlaps with another booking on the same lane');
            }

            $bcCheck = $this->slotService->validateWh2BcPlannedWindow($plannedGateId, $plannedStartDt, $plannedEndDt, 0);
            if (empty($bcCheck['ok'])) {
                return back()->withInput()->with('error', (string) ($bcCheck['message'] ?? 'Invalid planned window'));
            }
        }

        if (! $plannedGateId) {
            return back()->withInput()->with('error', 'Gate is full or unavailable for this schedule. Please choose another gate or time.');
        }

        $slotId = 0;
        DB::transaction(function () use (&$slotId, $truckNumber, $direction, $warehouseId, $plannedGateId, $plannedStart, $plannedDurationMinutes, $truckType, $vehicleNumber, $driverName, $driverNumber, $notes, $poDetail) {
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

            if ($slotId > 0) {
                $this->slotService->logActivity($slotId, 'status_change', 'Data Created');
            }
        });

        if ($slotId <= 0) {
            return back()->withInput()->with('error', 'Failed to create dat');
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

        return redirect()->route('slots.index')->with('success', 'Data created successfully');
    }

    public function edit(int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Data not found');
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
            return redirect()->route('slots.index')->with('error', 'Data not found');
        }

        if (((string) ($slot->slot_type ?? 'planned')) !== 'planned' || (string) ($slot->status ?? '') !== 'scheduled') {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Only scheduled planned slots can be edited');
        }

        $request->validate([
            'po_number' => 'required|string|max:12',
            'direction' => 'required|in:inbound,outbound',
            'truck_type' => 'required|string|max:100',
            'vendor_id' => 'nullable|string|max:255',
            'planned_gate_id' => 'required|integer|exists:md_gates,id',
            'planned_start' => 'required|string',
            'planned_duration' => 'required|integer|min:1|max:1440',
            'vehicle_number_snap' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:50',
            'driver_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

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
            return back()->withInput()->withErrors(['po_number' => 'PO/DO number max 12 characters']);
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
                return back()->withInput()->with('error', 'Planned time overlaps with another booking on the same lane');
            }

            $bcCheck = $this->slotService->validateWh2BcPlannedWindow($plannedGateId, $plannedStartDt, $plannedEndDt, $slotId);
            if (empty($bcCheck['ok'])) {
                return back()->withInput()->with('error', (string) ($bcCheck['message'] ?? 'Invalid planned window'));
            }
        }

        DB::transaction(function () use ($slotId, $truckNumber, $direction, $warehouseId, $vendorId, $plannedGateId, $plannedStart, $plannedDurationMinutes, $truckType, $vehicleNumber, $driverName, $driverNumber, $notes) {
            DB::table('slots')->where('id', $slotId)->update([
                'po_number' => $truckNumber,
                'direction' => $direction,
                'warehouse_id' => $warehouseId,
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

            $this->slotService->logActivity($slotId, 'status_change', 'Data Updated');
        });

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

        return redirect()->route('slots.index')->with('success', 'Data updated successfully');
    }

    public function show(int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Data not found');
        }

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
                    'updated_at' => now(),
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
            'slotItems' => collect(),
        ]);
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

            // Get slot info before updating for cache clearing
            $slot = DB::table('slots')->where('id', $slotId)->first();

            DB::table('slots')->where('id', $slotId)->update([
                'status' => 'cancelled',
                'cancelled_reason' => $reason,
                'cancelled_at' => $now,
            ]);

            // Clear availability cache to restore slot availability
            if ($slot && !empty($slot->planned_start)) {
                $cancelDate = $slot->planned_start instanceof \DateTimeInterface
                    ? $slot->planned_start->format('Y-m-d')
                    : date('Y-m-d', strtotime((string) $slot->planned_start));

                // Clear all cached availability variants (duration-specific)
                $durations = [30, 60, 90, 120, 150, 180, 240, 300, 360, 420, 480, 540, 600, 660, 720];
                foreach ($durations as $d) {
                    Cache::forget("vendor_availability_{$cancelDate}_{$d}");
                }
            }

            $this->slotService->logActivity($slotId, 'status_change', 'Slot Cancelled', null, ['reason' => $reason, 'cancelled_at' => $now]);
        });

        return redirect()->route('slots.index')->with('success', 'Slot cancelled');
    }
}
