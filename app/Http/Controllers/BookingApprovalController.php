<?php

namespace App\Http\Controllers;

use App\Models\Gate;
use App\Models\BookingRequest;
use App\Models\Slot;
use App\Models\SlotPoItem;
use App\Models\TruckTypeDuration;
use App\Models\Warehouse;
use App\Services\BookingApprovalService;
use App\Services\SlotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class BookingApprovalController extends Controller
{
    public function __construct(
        private readonly BookingApprovalService $bookingService,
        private readonly SlotService $slotService,
    ) {}

    /**
     * List all pending bookings for admin
     */
    public function index(Request $request)
    {
        $query = BookingRequest::with(['requester', 'approver', 'convertedSlot', 'convertedSlot.warehouse', 'convertedSlot.plannedGate']);

        // Default to pending approval
        $status = $request->get('status', BookingRequest::STATUS_PENDING);
        if ($status === 'pending_approval') {
            $status = BookingRequest::STATUS_PENDING;
        }

        if ($status === 'all') {
            // no-op
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

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhere('po_number', 'like', "%{$search}%")
                    ->orWhere('supplier_name', 'like', "%{$search}%")
                    ->orWhereHas('requester', function ($q2) use ($search) {
                        $q2->where('full_name', 'like', "%{$search}%");
                    });
            });
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get counts for tabs
        $counts = [
            'pending' => BookingRequest::where('status', BookingRequest::STATUS_PENDING)->count(),
            'approved' => BookingRequest::where('status', BookingRequest::STATUS_APPROVED)->count(),
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
        $booking = BookingRequest::with([
            'items',
            'requester',
            'approver',
            'convertedSlot',
            'convertedSlot.warehouse',
            'convertedSlot.plannedGate',
            'convertedSlot.actualGate',
        ])->findOrFail($id);

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

        return view('admin.bookings.show', compact('booking', 'warehouses', 'gates', 'truckTypes'));
    }

    /**
     * Approve booking
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
            'planned_gate_id' => 'required|integer|exists:gates,id',
        ]);

        $bookingRequest = BookingRequest::where('id', $id)
            ->where('status', BookingRequest::STATUS_PENDING)
            ->with(['items', 'requester'])
            ->firstOrFail();

        try {
            // Get warehouse from selected gate
            $plannedGateId = $request->planned_gate_id;
            $gate = Gate::where('id', $plannedGateId)
                ->where('is_active', true)
                ->with('warehouse')
                ->first();
            
            if (!$gate) {
                return back()->with('error', 'Selected gate is not active or not found.');
            }
            
            $warehouseId = $gate->warehouse_id;
            $plannedStart = (string) ($bookingRequest->planned_start?->format('Y-m-d H:i:s') ?? '');
            $durationMinutes = (int) ($bookingRequest->planned_duration ?? 0);

            // Check availability for the selected gate
            if ($warehouseId > 0 && $plannedStart !== '' && $durationMinutes > 0) {
                $check = $this->bookingService->checkAvailability(
                    $warehouseId,
                    $plannedGateId,
                    $plannedStart,
                    $durationMinutes,
                    null
                );
                if (empty($check['available'])) {
                    $reason = (string) ($check['reason'] ?? 'Gate tidak tersedia');
                    return back()->with('error', $reason);
                }
            }

            $slot = DB::transaction(function () use ($bookingRequest, $warehouseId, $plannedGateId, $request) {
                $vendorType = $bookingRequest->direction === 'outbound' ? 'customer' : 'supplier';
                $slot = Slot::create([
                    'ticket_number' => null,
                    'direction' => $bookingRequest->direction,
                    'warehouse_id' => $warehouseId,
                    'po_number' => $bookingRequest->po_number,
                    'vendor_code' => $bookingRequest->supplier_code,
                    'vendor_name' => $bookingRequest->supplier_name,
                    'vendor_type' => $vendorType,
                    'planned_gate_id' => $plannedGateId,
                    'planned_start' => $bookingRequest->planned_start,
                    'planned_duration' => (int) $bookingRequest->planned_duration,
                    'truck_type' => $bookingRequest->truck_type,
                    'vehicle_number_snap' => $bookingRequest->vehicle_number,
                    'driver_name' => $bookingRequest->driver_name,
                    'driver_number' => $bookingRequest->driver_number,
                    'late_reason' => $bookingRequest->notes,
                    'coa_path' => $bookingRequest->coa_path,
                    'surat_jalan_path' => $bookingRequest->surat_jalan_path,
                    'status' => Slot::STATUS_PENDING_APPROVAL,
                    'slot_type' => 'planned',
                    'created_by' => $bookingRequest->requested_by,
                    'requested_by' => $bookingRequest->requested_by,
                    'requested_at' => $bookingRequest->created_at,
                ]);

                foreach ($bookingRequest->items as $it) {
                    SlotPoItem::create([
                        'slot_id' => $slot->id,
                        'po_number' => $bookingRequest->po_number,
                        'item_no' => $it->item_no,
                        'material_code' => $it->material_code,
                        'material_name' => $it->material_name,
                        'uom' => $it->unit_po,
                        'qty_booked' => (float) ($it->qty_requested ?? 0),
                    ]);
                }

                $this->bookingService->approveBooking($slot, Auth::user(), $request->notes);

                $bookingRequest->update([
                    'status' => BookingRequest::STATUS_APPROVED,
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                    'approval_notes' => $request->notes,
                    'converted_slot_id' => $slot->id,
                ]);

                return $slot;
            });

            if (! empty($bookingRequest->planned_start)) {
                $approvedDate = $bookingRequest->planned_start instanceof \DateTimeInterface
                    ? $bookingRequest->planned_start->format('Y-m-d')
                    : date('Y-m-d', strtotime((string) $bookingRequest->planned_start));
                \Illuminate\Support\Facades\Cache::forget("vendor_availability_{$approvedDate}");
            }

            return redirect()
                ->route('bookings.show', $bookingRequest->id)
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

        $bookingRequest = BookingRequest::where('id', $id)
            ->where('status', BookingRequest::STATUS_PENDING)
            ->firstOrFail();

        try {
            $bookingRequest->update([
                'status' => BookingRequest::STATUS_REJECTED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'approval_notes' => $request->reason,
            ]);

            return redirect()->route('bookings.index')->with('success', 'Booking rejected.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to reject booking: ' . $e->getMessage());
        }
    }

    /**
     * Show reschedule form
     */
    public function rescheduleForm($id)
    {
        $booking = BookingRequest::where('id', $id)
            ->where('status', BookingRequest::STATUS_PENDING)
            ->with(['requester'])
            ->findOrFail($id);

        // Get all gates for gate-only selection
        $gatesQ = Gate::query();
        if (Schema::hasColumn('gates', 'is_active')) {
            $gatesQ->where('is_active', true);
        }
        $gates = $gatesQ->with('warehouse')->get();
        $truckTypes = TruckTypeDuration::orderBy('truck_type')->get();

        return view('admin.bookings.reschedule', compact('booking', 'gates', 'truckTypes'));
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
            'planned_gate_id' => 'required|integer|exists:gates,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $bookingRequest = BookingRequest::where('id', $id)
            ->where('status', BookingRequest::STATUS_PENDING)
            ->with(['items', 'requester'])
            ->firstOrFail();

        // Get warehouse from selected gate
        $plannedGateId = $request->planned_gate_id;
        $gate = Gate::where('id', $plannedGateId)
            ->where('is_active', true)
            ->with('warehouse')
            ->first();
        
        if (!$gate) {
            return back()->withInput()->with('error', 'Selected gate is not active or not found.');
        }
        
        $warehouseId = $gate->warehouse_id;
        $plannedStart = $request->planned_date . ' ' . $request->planned_time . ':00';

        // Update schedule on request (final schedule before approval)
        $plannedEnd = $this->slotService->computePlannedFinish($plannedStart, (int) $request->planned_duration);
        if ($plannedEnd === null) {
            return back()->withInput()->with('error', 'Invalid planned schedule.');
        }

        $dateAddExpr = $this->slotService->getDateAddExpression('br.planned_start', 'br.planned_duration');
        $pendingOverlap = (int) DB::table('booking_requests as br')
            ->where('br.status', BookingRequest::STATUS_PENDING)
            ->where('br.id', '<>', (int) $bookingRequest->id)
            ->whereRaw("? < {$dateAddExpr}", [$plannedStart])
            ->whereRaw('? > br.planned_start', [$plannedEnd])
            ->count();

        if ($pendingOverlap > 0) {
            return back()->withInput()->with('error', 'Waktu ini sedang diblokir karena menunggu konfirmasi tim WH');
        }

        $bookingRequest->update([
            'planned_start' => $plannedStart,
            'planned_duration' => (int) $request->planned_duration,
            'planned_gate_id' => $plannedGateId,
            'warehouse_id' => $warehouseId,
            'approval_notes' => $request->notes,
        ]);

        \Illuminate\Support\Facades\Cache::forget("vendor_availability_{$plannedStartAt->format('Y-m-d')}");

        return $this->approve($request, $id);
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
            ->with(['requester'])
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
                    'vendor_name' => $s->vendor_name ?? '-',
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
        $count = BookingRequest::where('status', BookingRequest::STATUS_PENDING)->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }
}
