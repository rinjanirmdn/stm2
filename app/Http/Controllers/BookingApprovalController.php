<?php

namespace App\Http\Controllers;

use App\Models\Gate;
use App\Models\Slot;
use App\Models\TruckTypeDuration;
use App\Models\Warehouse;
use App\Services\BookingApprovalService;
use App\Services\PoSearchService;
use App\Services\SlotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class BookingApprovalController extends Controller
{
    public function __construct(
        private readonly BookingApprovalService $bookingService,
        private readonly SlotService $slotService,
        private readonly PoSearchService $poSearchService,
    ) {}

    /**
     * List all pending bookings for admin
     */
    public function index(Request $request)
    {
        $query = Slot::with(['warehouse', 'plannedGate', 'vendor', 'requester']);

        // Default to pending approval
        $status = $request->get('status', 'pending_approval');

        if ($status === 'all') {
            // Show all booking requests (not regular slots)
            $query->whereNotNull('requested_by');
        } elseif ($status === 'pending') {
            $query->whereIn('status', [
                Slot::STATUS_PENDING_APPROVAL,
                Slot::STATUS_PENDING_VENDOR_CONFIRMATION,
            ]);
        } else {
            $query->where('status', $status);
        }

        // Filter by date
        if ($request->filled('date_from')) {
            $query->whereDate('planned_start', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('planned_start', '<=', $request->date_to);
        }

        // Filter by warehouse
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('requester', function ($q2) use ($search) {
                        $q2->where('full_name', 'like', "%{$search}%");
                    });
            });
        }

        $bookings = $query->orderBy('requested_at', 'desc')->paginate(20);

        // Get counts for tabs
        $counts = [
            'pending_approval' => Slot::where('status', Slot::STATUS_PENDING_APPROVAL)->count(),
            'pending_vendor' => Slot::where('status', Slot::STATUS_PENDING_VENDOR_CONFIRMATION)->count(),
            'scheduled' => Slot::whereNotNull('requested_by')
                ->where('status', Slot::STATUS_SCHEDULED)
                ->whereDate('planned_start', '>=', now())
                ->count(),
        ];

        $warehousesQ = Warehouse::query();
        if (Schema::hasColumn('warehouses', 'is_active')) {
            $warehousesQ->where('is_active', true);
        }
        $warehouses = $warehousesQ->get();

        return view('admin.bookings.index', compact('bookings', 'counts', 'warehouses', 'status'));
    }

    /**
     * Show booking detail
     */
    public function show($id)
    {
        $booking = Slot::with([
            'warehouse',
            'plannedGate',
            'actualGate',
            'originalPlannedGate',
            'vendor',
            'requester',
            'approver',
            'bookingHistories.performer',
            'po',
        ])->findOrFail($id);

        // PO Items: Use slot_po_items as the authoritative source for booked qty in this booking.
        $poNumber = $booking->po?->po_number ? trim((string) $booking->po->po_number) : '';
        $bookedItems = collect();
        if (Schema::hasTable('slot_po_items')) {
            $bookedItems = $booking->poItems()->get();
            if ($poNumber === '' && $bookedItems->isNotEmpty()) {
                $poNumber = trim((string) ($bookedItems->first()->po_number ?? ''));
            }
        }

        $bookedByItem = [];
        foreach ($bookedItems as $bi) {
            $itemNo = trim((string) ($bi->item_no ?? ''));
            if ($itemNo === '') continue;
            $bookedByItem[$itemNo] = (float) ($bi->qty_booked ?? 0);
        }

        $poItems = [];
        if ($poNumber !== '') {
            $detail = $this->poSearchService->getPoDetail($poNumber);
            $items = is_array($detail['items'] ?? null) ? $detail['items'] : [];

            // Enrich SAP items with booked qty for this slot
            foreach ($items as $it) {
                if (!is_array($it)) continue;
                $itemNo = trim((string) ($it['item_no'] ?? ''));
                if ($itemNo === '') continue;
                $it['qty_booked_slot'] = (float) ($bookedByItem[$itemNo] ?? 0);
                $poItems[] = $it;
            }
        }

        // Fallback: if SAP detail not available, use booked items only
        if (empty($poItems) && $bookedItems->isNotEmpty()) {
            foreach ($bookedItems as $bi) {
                $poItems[] = [
                    'item_no' => (string) ($bi->item_no ?? ''),
                    'material' => (string) ($bi->material_code ?? ''),
                    'description' => (string) ($bi->material_name ?? ''),
                    'qty' => null,
                    'uom' => (string) ($bi->uom ?? ''),
                    'qty_booked_slot' => (float) ($bi->qty_booked ?? 0),
                ];
            }
        }

        $warehousesQ = Warehouse::query();
        if (Schema::hasColumn('warehouses', 'is_active')) {
            $warehousesQ->where('is_active', true);
        }
        $warehouses = $warehousesQ->get();

        $gatesQ = Gate::query();
        if (Schema::hasColumn('gates', 'is_active')) {
            $gatesQ->where('is_active', true);
        }
        $gates = $gatesQ
            ->with('warehouse')
            ->get()
            ->groupBy('warehouse_id');
        $truckTypes = TruckTypeDuration::orderBy('truck_type')->get();

        return view('admin.bookings.show', compact('booking', 'warehouses', 'gates', 'truckTypes', 'poNumber', 'poItems'));
    }

    /**
     * Approve booking
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'planned_gate_id' => 'nullable|integer|exists:gates,id',
        ]);

        $slot = Slot::whereIn('status', [
                Slot::STATUS_PENDING_APPROVAL,
                Slot::STATUS_PENDING_VENDOR_CONFIRMATION,
            ])->findOrFail($id);

        try {
            $requestedWarehouseId = $request->filled('warehouse_id') ? (int) $request->warehouse_id : null;
            $warehouseId = $requestedWarehouseId ?: (int) ($slot->warehouse_id ?? 0);
            $plannedStart = (string) ($slot->planned_start ?? '');
            $durationMinutes = (int) ($slot->planned_duration ?? 0);

            $requestedGateId = $request->filled('planned_gate_id') ? (int) $request->planned_gate_id : null;
            $effectiveGateId = $requestedGateId ?: (!empty($slot->planned_gate_id) ? (int) $slot->planned_gate_id : null);

            if ($requestedGateId) {
                $gateOk = Gate::where('id', $requestedGateId)->where('warehouse_id', $warehouseId)->exists();
                if (! $gateOk) {
                    return back()->with('error', 'Gate tidak sesuai dengan warehouse yang dipilih.');
                }
            }

            if ($warehouseId > 0 && $plannedStart !== '' && $durationMinutes > 0) {
                if ($requestedGateId) {
                    $check = $this->bookingService->checkAvailability(
                        $warehouseId,
                        $requestedGateId,
                        $plannedStart,
                        $durationMinutes,
                        (int) $slot->id
                    );
                    if (empty($check['available'])) {
                        $reason = (string) ($check['reason'] ?? 'Gate tidak tersedia');
                        return back()->with('error', $reason);
                    }
                    $effectiveGateId = $requestedGateId;
                }

                if (!$effectiveGateId) {
                    $gatesQ = Gate::where('warehouse_id', $warehouseId);
                    if (Schema::hasColumn('gates', 'is_active')) {
                        $gatesQ->where('is_active', true);
                    }
                    $candidateGates = $gatesQ->orderBy('gate_number')->get();

                    $bestGateId = null;
                    $bestRisk = null;
                    foreach ($candidateGates as $g) {
                        $gid = (int) ($g->id ?? 0);
                        if ($gid <= 0) continue;
                        $check = $this->bookingService->checkAvailability(
                            $warehouseId,
                            $gid,
                            $plannedStart,
                            $durationMinutes,
                            (int) $slot->id
                        );
                        if (empty($check['available'])) {
                            continue;
                        }
                        $risk = (int) ($check['blocking_risk'] ?? 0);
                        if ($bestGateId === null || $risk < (int) $bestRisk) {
                            $bestGateId = $gid;
                            $bestRisk = $risk;
                        }
                    }

                    if ($bestGateId === null) {
                        return back()->with('error', 'Gate penuh / tidak tersedia untuk jadwal ini. Silakan reschedule atau pilih waktu lain.');
                    }
                    $effectiveGateId = $bestGateId;
                }
            }

            if ($requestedWarehouseId && $requestedWarehouseId !== (int) ($slot->warehouse_id ?? 0)) {
                $slot->warehouse_id = $requestedWarehouseId;
            }
            if ($effectiveGateId) {
                $slot->planned_gate_id = $effectiveGateId;
            } else {
                $slot->planned_gate_id = null;
            }

            if ($slot->isDirty(['warehouse_id', 'planned_gate_id'])) {
                $slot->save();
            }

            $this->bookingService->approveBooking($slot, Auth::user(), $request->notes);

            return redirect()
                ->route('bookings.show', $slot->id)
                ->with('success', 'Booking approved successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to approve booking: ' . $e->getMessage());
        }
    }

    /**
     * Reject booking
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $slot = Slot::whereIn('status', [
                Slot::STATUS_PENDING_APPROVAL,
                Slot::STATUS_PENDING_VENDOR_CONFIRMATION,
                Slot::STATUS_SCHEDULED,
            ])->findOrFail($id);

        try {
            $this->bookingService->rejectBooking($slot, Auth::user(), $request->reason);

            return redirect()
                ->route('bookings.index')
                ->with('success', 'Booking rejected.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to reject booking: ' . $e->getMessage());
        }
    }

    /**
     * Show reschedule form
     */
    public function rescheduleForm($id)
    {
        $booking = Slot::whereIn('status', [
                Slot::STATUS_PENDING_APPROVAL,
                Slot::STATUS_PENDING_VENDOR_CONFIRMATION,
                Slot::STATUS_SCHEDULED,
            ])
            ->with(['warehouse', 'plannedGate', 'vendor', 'requester'])
            ->findOrFail($id);

        $warehousesQ = Warehouse::query();
        if (Schema::hasColumn('warehouses', 'is_active')) {
            $warehousesQ->where('is_active', true);
        }
        $warehouses = $warehousesQ->get();

        $gatesQ = Gate::query();
        if (Schema::hasColumn('gates', 'is_active')) {
            $gatesQ->where('is_active', true);
        }
        $gates = $gatesQ
            ->with('warehouse')
            ->get()
            ->groupBy('warehouse_id');
        $truckTypes = TruckTypeDuration::orderBy('truck_type')->get();

        return view('admin.bookings.reschedule', compact('booking', 'warehouses', 'gates', 'truckTypes'));
    }

    /**
     * Process reschedule
     */
    public function reschedule(Request $request, $id)
    {
        $request->validate([
            'planned_date' => 'required|date|after_or_equal:today',
            'planned_time' => 'required|date_format:H:i',
            'planned_duration' => 'required|integer|min:30|max:480',
            'planned_gate_id' => 'nullable|exists:gates,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $slot = Slot::whereIn('status', [
                Slot::STATUS_PENDING_APPROVAL,
                Slot::STATUS_PENDING_VENDOR_CONFIRMATION,
                Slot::STATUS_SCHEDULED,
            ])->findOrFail($id);

        $plannedStart = $request->planned_date . ' ' . $request->planned_time . ':00';

        // Check availability
        $availability = $this->bookingService->checkAvailability(
            $slot->warehouse_id,
            $request->planned_gate_id ?? $slot->planned_gate_id,
            $plannedStart,
            $request->planned_duration,
            $slot->id
        );

        if (!$availability['available']) {
            return back()
                ->withInput()
                ->with('error', $availability['reason']);
        }

        try {
            $this->bookingService->rescheduleBooking($slot, Auth::user(), [
                'planned_start' => $plannedStart,
                'planned_duration' => $request->planned_duration,
                'planned_gate_id' => $request->planned_gate_id ?? $slot->planned_gate_id,
            ], $request->notes);

            return redirect()
                ->route('bookings.show', $slot->id)
                ->with('success', 'Booking rescheduled. Waiting for vendor confirmation.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to reschedule booking: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Get calendar data for admin view
     */
    public function calendarData(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
        ]);

        $date = $request->date;
        $warehouseId = $request->warehouse_id;

        // Get gates for this warehouse
        $gates = Gate::where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->with(['warehouse'])
            ->orderBy('gate_number')
            ->get();

        // Get slots for the date (including pending ones)
        $slots = Slot::where('warehouse_id', $warehouseId)
            ->whereDate('planned_start', $date)
            ->whereNotIn('status', [Slot::STATUS_CANCELLED])
            ->with(['vendor', 'requester'])
            ->get()
            ->groupBy('planned_gate_id');

        $calendarData = [];
        foreach ($gates as $gate) {
            $gateSlots = $slots->get($gate->id, collect());
            $calendarData[] = [
                'gate' => [
                    'id' => $gate->id,
                    'name' => $gate->name ?? ($gate->warehouse->wh_code . '-' . $gate->gate_number),
                ],
                'slots' => $gateSlots->map(fn($s) => [
                    'id' => $s->id,
                    'ticket_number' => $s->ticket_number,
                    'vendor_name' => $s->vendor?->name ?? '-',
                    'requester_name' => $s->requester?->full_name ?? '-',
                    'start_time' => $s->planned_start->format('H:i'),
                    'end_time' => $s->planned_start->copy()->addMinutes($s->planned_duration)->format('H:i'),
                    'duration' => $s->planned_duration,
                    'status' => $s->status,
                    'status_label' => $s->status_label,
                    'status_color' => $s->status_badge_color,
                    'direction' => $s->direction,
                    'is_pending' => $s->isPendingApproval(),
                    'needs_action' => $s->needsAction(),
                ])->values(),
            ];
        }

        return response()->json([
            'success' => true,
            'date' => $date,
            'gates' => $calendarData,
        ]);
    }

    /**
     * Dashboard widget: Pending approvals count
     */
    public function pendingCount()
    {
        $count = Slot::where('status', Slot::STATUS_PENDING_APPROVAL)->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }
}
