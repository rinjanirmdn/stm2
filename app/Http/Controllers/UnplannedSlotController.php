<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\SlotHelperTrait;
use App\Services\PoSearchService;
use App\Services\SlotConflictService;
use App\Services\SlotFilterService;
use App\Services\SlotService;
use App\Services\TimeCalculationService;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UnplannedSlotController extends Controller
{
    use SlotHelperTrait;

    public function __construct(
        private readonly SlotService $slotService,
        private readonly PoSearchService $poSearchService,
        private readonly SlotConflictService $conflictService,
        private readonly SlotFilterService $filterService,
        private readonly TimeCalculationService $timeService
    ) {}

    public function index(Request $request)
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
        $isResetSort = (! is_array($rawSort) && (string) $rawSort === 'reset');
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
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
                's.vendor_name',
                'g.gate_number as actual_gate_number',
                'td.target_duration_minutes',
            ]);

        // Apply filters
        if ($request->filled('q')) {
            $search = '%'.$request->get('q').'%';
            $query->where(function ($q) use ($search) {
                $q->where('s.po_number', 'like', $search)
                    ->orWhere('s.mat_doc', 'like', $search)
                    ->orWhere('s.vendor_name', 'like', $search);
            });
        }

        if ($request->filled('po_number')) {
            $query->where('s.po_number', 'like', '%'.$request->get('po_number').'%');
        }

        if ($request->filled('mat_doc')) {
            $query->where('s.mat_doc', 'like', '%'.$request->get('mat_doc').'%');
        }

        if ($request->filled('vendor')) {
            $query->where('s.vendor_name', 'like', '%'.$request->get('vendor').'%');
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
            'direction', 'arrival_time', 'created_at',
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
                    $query->orderBy('s.'.$s, $d);
                }
                $applied++;
            }
        }

        if ($applied === 0) {
            $actualSort = $querySort ?? 'created_at';
            if (in_array($actualSort, $allowedSorts, true)) {
                if ($actualSort === 'po_number') {
                    $query->orderBy('s.po_number', $dir);
                } elseif ($actualSort === 'vendor_name') {
                    $query->orderBy('s.vendor_name', $dir);
                } elseif ($actualSort === 'warehouse_name') {
                    $query->orderBy('w.wh_name', $dir);
                } else {
                    $query->orderBy('s.'.$actualSort, $dir);
                }
            } else {
                $query->orderByRaw('COALESCE(s.arrival_time, s.planned_start) DESC');
            }
        }

        $query->orderByDesc('s.created_at')->orderByDesc('s.id');

        // Apply pagination
        if ($pageSize === 'all') {
            $unplannedSlotsQuery = $query;
        } else {
            $limit = is_numeric($pageSize) ? (int) $pageSize : 50;
            $unplannedSlotsQuery = $query->limit($limit);
        }

        $unplannedCacheKey = 'unplanned:index:data:'.sha1(json_encode([
            'uid' => Auth::id(),
            'query' => $request->query(),
            'version' => (string) Cache::get('st_realtime_version', '0'),
        ]));
        $unplannedSlots = Cache::remember($unplannedCacheKey, now()->addSeconds(10), function () use ($unplannedSlotsQuery) {
            return $unplannedSlotsQuery->get();
        });

        // Get warehouses and gates for filter dropdowns
        $warehouses = Cache::remember('unplanned:index:warehouses', now()->addMinutes(10), function () {
            return DB::table('md_warehouse')
                ->select(['id', 'wh_name as name', 'wh_code as code'])
                ->orderBy('wh_name')
                ->get();
        });
        $gates = Cache::remember('unplanned:index:gates', now()->addMinutes(10), function () {
            return DB::table('md_gates')
                ->where('is_active', true)
                ->orderBy('gate_number')
                ->pluck('gate_number')
                ->all();
        });

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

    public function create()
    {
        $warehouses = Cache::remember('unplanned:create:warehouses', now()->addMinutes(10), function () {
            return DB::table('md_warehouse')
                ->select(['id', 'wh_name as name', 'wh_code as code'])
                ->orderBy('wh_name')
                ->get();
        });
        $gates = Cache::remember('unplanned:create:gates', now()->addMinutes(10), function () {
            return DB::table('md_gates as g')
                ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id')
                ->where('g.is_active', true)
                ->orderBy('w.wh_name')
                ->orderBy('g.gate_number')
                ->select(['g.*', 'w.wh_name as warehouse_name', 'w.wh_code as warehouse_code'])
                ->get();
        });

        $truckTypes = $this->getTruckTypeOptions();

        return view('unplanned.create', compact('warehouses', 'gates', 'truckTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
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
            return back()->withInput()->withErrors(['po_number' => 'PO/DO number max 12 characters']);
        }

        if (! in_array($direction, ['inbound', 'outbound'], true)) {
            return back()->withInput()->withErrors(['direction' => 'Direction must be inbound or outbound']);
        }

        try {
            $arrivalDt = new DateTime($arrivalInput);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['actual_arrival' => 'Arrival time must be a valid date']);
        }

        $arrivalTime = $arrivalDt->format('Y-m-d H:i:s');

        $matDoc = trim((string) $request->input('mat_doc', ''));
        $truckType = trim((string) $request->input('truck_type', ''));
        $vehicleNumber = trim((string) $request->input('vehicle_number_snap', ''));
        $driverName = trim((string) $request->input('driver_name', ''));
        $driverNumber = trim((string) $request->input('driver_number', ''));
        $notes = trim((string) $request->input('notes', ''));

        $poDetail = $this->poSearchService->getPoDetail($poNumber);
        if (! $poDetail) {
            return back()->withInput()->with('error', 'PO/DO not found in SAP.');
        }

        $setWaiting = $request->filled('set_waiting') && (string) $request->input('set_waiting') === '1';
        $status = $setWaiting ? 'waiting' : 'completed';
        $actualStart = $status === 'completed' ? $arrivalTime : null;
        $actualFinish = $status === 'completed' ? $arrivalTime : null;

        $slotId = DB::transaction(function () use ($poNumber, $direction, $warehouseId, $actualGateId, $arrivalTime, $matDoc, $truckType, $vehicleNumber, $driverName, $driverNumber, $notes, $status, $actualStart, $actualFinish, $poDetail) {
            $slotId = (int) DB::table('slots')->insertGetId([
                'po_number' => $poNumber,
                'direction' => $direction,
                'warehouse_id' => $warehouseId,
                'vendor_code' => $poDetail['vendor_code'] ?? null,
                'vendor_name' => $poDetail['vendor_name'] ?? null,
                'vendor_type' => $poDetail['vendor_type'] ?? null,
                'actual_gate_id' => $actualGateId,
                'planned_start' => $arrivalTime,
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

            if ($slotId > 0) {
                $this->slotService->logActivity($slotId, 'status_change', 'Unplanned Transaction Recorded as '.$status);
            }

            return $slotId;
        });

        return redirect()->route('unplanned.show', ['slotId' => $slotId])->with('success', 'Unplanned transaction recorded successfully');
    }

    public function edit(int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Data not found');
        }

        if (((string) ($slot->slot_type ?? 'planned')) !== 'unplanned') {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Only unplanned transactions can be edited here');
        }

        $warehouses = DB::table('md_warehouse')
            ->select(['id', 'wh_name as name', 'wh_code as code'])
            ->orderBy('wh_name')
            ->get();

        $vendors = collect(); // Business partner table removed, vendors now stored in slots

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

    public function update(Request $request, int $slotId)
    {
        $slot = $this->loadSlotDetailRow($slotId);
        if (! $slot) {
            return redirect()->route('slots.index')->with('error', 'Data not found');
        }

        if (((string) ($slot->slot_type ?? 'planned')) !== 'unplanned') {
            return redirect()->route('slots.show', ['slotId' => $slotId])->with('error', 'Only unplanned transactions can be edited here');
        }

        $request->validate([
            'po_number' => 'required|string|max:12',
            'direction' => 'required|in:inbound,outbound',
            'vendor_id' => 'nullable|string|max:255',
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
            return back()->withInput()->withErrors(['po_number' => 'PO/DO number max 12 characters']);
        }

        $matDoc = trim((string) $request->input('mat_doc', ''));
        $truckType = trim((string) $request->input('truck_type', ''));
        $vehicleNumber = trim((string) $request->input('vehicle_number_snap', ''));
        $driverName = trim((string) $request->input('driver_name', ''));
        $driverNumber = trim((string) $request->input('driver_number', ''));
        $notes = trim((string) $request->input('notes', ''));

        // Check if any data was actually changed
        $dataChanged = (
            $slot->po_number !== $truckNumber ||
            $slot->direction !== $direction ||
            $slot->warehouse_id !== $warehouseId ||
            $slot->actual_gate_id !== $actualGateId ||
            $slot->arrival_time !== $arrivalTime ||
            $slot->mat_doc !== $matDoc ||
            $slot->truck_type !== $truckType ||
            $slot->vehicle_number_snap !== $vehicleNumber ||
            $slot->driver_name !== $driverName ||
            $slot->driver_number !== $driverNumber ||
            $slot->late_reason !== $notes
        );

        $setWaiting = $request->filled('set_waiting') && (string) $request->input('set_waiting') === '1';
        $status = $setWaiting ? 'waiting' : 'completed';
        $actualStart = $status === 'completed' ? $arrivalTime : null;
        $actualFinish = $status === 'completed' ? $arrivalTime : null;

        DB::transaction(function () use ($slotId, $truckNumber, $direction, $warehouseId, $actualGateId, $arrivalTime, $matDoc, $truckType, $vehicleNumber, $driverName, $driverNumber, $notes, $status, $actualStart, $actualFinish) {
            DB::table('slots')->where('id', $slotId)->update([
                'po_number' => $truckNumber,
                'direction' => $direction,
                'warehouse_id' => $warehouseId,
                'actual_gate_id' => $actualGateId,
                'arrival_time' => $arrivalTime,
                'mat_doc' => $matDoc !== '' ? $matDoc : null,
                'truck_type' => $truckType !== '' ? $truckType : null,
                'vehicle_number_snap' => $vehicleNumber !== '' ? $vehicleNumber : null,
                'driver_name' => $driverName !== '' ? $driverName : null,
                'driver_number' => $driverNumber !== '' ? $driverNumber : null,
                'late_reason' => $notes !== '' ? $notes : null,
                'status' => $status,
                'actual_start' => $actualStart,
                'actual_finish' => $actualFinish,
                'updated_at' => now(),
            ]);

            $this->slotService->logActivity($slotId, 'status_change', 'Unplanned Transaction Updated');
        });

        // Smart redirect logic
        if ($dataChanged) {
            // Data was changed, go to unplanned index
            return redirect()->route('unplanned.index')->with('success', 'Unplanned transaction updated successfully');
        } else {
            // Data not changed, go back to previous page
            $referer = $request->header('referer');
            if ($referer && str_contains($referer, route('unplanned.index'))) {
                // Came from unplanned menu, go back to unplanned index
                return redirect()->route('unplanned.index');
            } else {
                // Came from other pages (reports, etc.), go back to previous page
                return redirect()->back();
            }
        }
    }
}
