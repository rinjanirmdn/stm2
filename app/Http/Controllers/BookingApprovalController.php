<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Gate;
use App\Models\BookingRequest;
use App\Models\Slot;
use App\Models\TruckTypeDuration;
use App\Models\Warehouse;
use App\Services\BookingApprovalService;
use App\Services\SlotService;
use App\Notifications\BookingApproved;
use App\Notifications\BookingRejected;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
        $query = BookingRequest::query()
            ->with(['requester', 'approver', 'convertedSlot', 'convertedSlot.warehouse', 'convertedSlot.plannedGate'])
            ->leftJoin('md_users as u_requester', 'booking_requests.requested_by', '=', 'u_requester.id')
            ->leftJoin('slots as s_converted', 'booking_requests.converted_slot_id', '=', 's_converted.id')
            ->leftJoin('md_gates as g_planned', 's_converted.planned_gate_id', '=', 'g_planned.id')
            ->select('booking_requests.*');

        // Default to all
        $status = $request->get('status', 'all');
        if ($status === 'pending_approval') {
            $status = BookingRequest::STATUS_PENDING;
        }

        if ($status === 'all') {
            // no-op
        } else {
            $query->where('booking_requests.status', $status);
        }

        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        // Filter by date
        if ($request->filled('date_from')) {
            $query->whereDate('booking_requests.planned_start', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('booking_requests.planned_start', '<=', $request->date_to);
        }

        // Column filters
        $requestNumber = trim((string) $request->query('request_number', ''));
        if ($requestNumber !== '') {
            $requestNumber = str_replace(['%', '_'], ['\%', '\_'], $requestNumber);
            $query->where('booking_requests.request_number', 'like', '%' . $requestNumber . '%');
        }

        $poNumber = trim((string) $request->query('po_number', ''));
        if ($poNumber !== '') {
            $poNumber = str_replace(['%', '_'], ['\%', '\_'], $poNumber);
            $query->where('booking_requests.po_number', 'like', '%' . $poNumber . '%');
        }

        $supplierName = trim((string) $request->query('supplier_name', ''));
        if ($supplierName !== '') {
            $supplierName = str_replace(['%', '_'], ['\%', '\_'], $supplierName);
            $query->where('booking_requests.supplier_name', 'like', '%' . $supplierName . '%');
        }

        $requestedBy = trim((string) $request->query('requested_by', ''));
        if ($requestedBy !== '') {
            $requestedBy = str_replace(['%', '_'], ['\%', '\_'], $requestedBy);
            $query->where(function ($q) use ($requestedBy) {
                $q->where('u_requester.full_name', 'like', '%' . $requestedBy . '%')
                    ->orWhere('u_requester.name', 'like', '%' . $requestedBy . '%')
                    ->orWhere('u_requester.email', 'like', '%' . $requestedBy . '%');
            });
        }

        $plannedStart = trim((string) $request->query('planned_start', ''));
        if ($plannedStart !== '') {
            $query->whereDate('booking_requests.planned_start', '=', $plannedStart);
        }

        $convertedTicket = trim((string) $request->query('converted_ticket', ''));
        if ($convertedTicket !== '') {
            $convertedTicket = str_replace(['%', '_'], ['\%', '\_'], $convertedTicket);
            $query->where('s_converted.ticket_number', 'like', '%' . $convertedTicket . '%');
        }

        $gate = trim((string) $request->query('gate', ''));
        if ($gate !== '') {
            $gate = str_replace(['%', '_'], ['\%', '\_'], $gate);
            $query->where(function ($q) use ($gate) {
                $q->where('g_planned.name', 'like', '%' . $gate . '%')
                    ->orWhere('g_planned.gate_number', 'like', '%' . $gate . '%');
            });
        }

        $plannedGateId = (int) $request->query('planned_gate_id', 0);
        if ($plannedGateId > 0) {
            $query->where('s_converted.planned_gate_id', '=', $plannedGateId);
        }

        $direction = trim((string) $request->query('direction', ''));
        if ($direction !== '') {
            $query->where('booking_requests.direction', '=', $direction);
        }

        $statusFilter = trim((string) $request->query('status_filter', ''));
        if ($statusFilter !== '') {
            $query->where('booking_requests.status', '=', $statusFilter);
        }

        $createdAt = trim((string) $request->query('created_at', ''));
        if ($createdAt !== '') {
            $query->whereDate('booking_requests.created_at', '=', $createdAt);
        }

        // Search
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhere('po_number', 'like', "%{$search}%")
                    ->orWhere('supplier_name', 'like', "%{$search}%")
                    ->orWhereHas('requester', function ($q2) use ($search) {
                        $q2->where('full_name', 'like', "%{$search}%");
                    });
            });
        }

        // Multi-sort
        $sorts = $request->query('sort', []);
        $dirs = $request->query('dir', []);
        $sorts = is_array($sorts) ? $sorts : [$sorts];
        $dirs = is_array($dirs) ? $dirs : [$dirs];

        $sortMap = [
            'request_number' => 'booking_requests.request_number',
            'po_number' => 'booking_requests.po_number',
            'supplier_name' => 'booking_requests.supplier_name',
            'requested_by' => 'u_requester.full_name',
            'planned_start' => 'booking_requests.planned_start',
            'converted_ticket' => 's_converted.ticket_number',
            'gate' => 'g_planned.name',
            'direction' => 'booking_requests.direction',
            'status' => 'booking_requests.status',
            'created_at' => 'booking_requests.created_at',
        ];

        $applied = 0;
        foreach (array_values($sorts) as $i => $s) {
            $key = trim((string) $s);
            if ($key === '' || !array_key_exists($key, $sortMap)) {
                continue;
            }
            $dir = strtolower(trim((string) ($dirs[$i] ?? 'desc')));
            if (!in_array($dir, ['asc', 'desc'], true)) {
                $dir = 'desc';
            }
            $col = $sortMap[$key];
            if ($col instanceof \Illuminate\Database\Query\Expression) {
                $query->orderByRaw($col->getValue(DB::connection()->getQueryGrammar()) . ' ' . strtoupper($dir));
            } else {
                $query->orderBy($col, $dir);
            }
            $applied++;
        }
        if ($applied === 0) {
            $query->orderBy('booking_requests.created_at', 'desc');
        }

        $bookings = $query->paginate(20);

        // Get counts for tabs
        $statusCounts = BookingRequest::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $pendingCount = (int) ($statusCounts[BookingRequest::STATUS_PENDING] ?? 0);
        $approvedCount = (int) ($statusCounts[BookingRequest::STATUS_APPROVED] ?? 0);
        $cancelledCount = (int) ($statusCounts[BookingRequest::STATUS_CANCELLED] ?? 0);
        $rejectedCount = (int) ($statusCounts[BookingRequest::STATUS_REJECTED] ?? 0);
        $counts = [
            'pending' => $pendingCount,
            'approved' => $approvedCount,
            'cancelled' => $cancelledCount,
            'rejected' => $rejectedCount,
            'all' => $pendingCount + $approvedCount + $cancelledCount + $rejectedCount,
        ];

        $warehouses = Warehouse::all();

        $gateOptions = Cache::remember('bookings:index:gate_options', now()->addMinutes(10), function () {
            return Gate::query()
                ->from('md_gates', 'g')
                ->join('md_warehouse as w', 'g.warehouse_id', '=', 'w.id')
                ->where('g.is_active', true)
                ->orderBy('w.wh_code')
                ->orderBy('g.gate_number')
                ->get(['g.id', 'g.gate_number', 'w.wh_code'])
                ->map(function ($g) {
                    $whCode = (string) ($g->wh_code ?? '');
                    $gateNo = (string) ($g->gate_number ?? '');
                    $display = $this->slotService->getGateDisplayName($whCode, $gateNo);
                    return [
                        'id' => (int) ($g->id ?? 0),
                        'label' => trim(($whCode !== '' ? ($whCode . ' - ') : '') . $display),
                    ];
                })
                ->values();
        });

        return view('admin.bookings.index', compact('bookings', 'counts', 'warehouses', 'status', 'sorts', 'dirs', 'gateOptions'));
    }

    public function ajaxCheckGateAvailability(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|integer|exists:booking_requests,id',
            'planned_gate_id' => 'required|integer|exists:md_gates,id',
        ]);

        $bookingRequest = BookingRequest::query()
            ->where('id', (int) $request->booking_id)
            ->firstOrFail();

        $plannedGateId = (int) $request->planned_gate_id;
        $gate = Gate::where('id', $plannedGateId)
            ->where('is_active', true)
            ->with('warehouse')
            ->first();

        if (! $gate) {
            return response()->json([
                'available' => false,
                'label' => 'Not Available',
                'reason' => 'Selected gate is not active or not found.',
            ]);
        }

        $warehouseId = (int) ($gate->warehouse_id ?? 0);

        // Allow override from query (used by reschedule form)
        $overrideDate = trim((string) $request->query('planned_date', ''));
        $overrideTime = trim((string) $request->query('planned_time', ''));
        $overrideDuration = (int) $request->query('planned_duration', 0);

        if ($overrideDate !== '' && $overrideTime !== '' && $overrideDuration > 0) {
            $plannedStart = $overrideDate . ' ' . $overrideTime . ':00';
            $durationMinutes = $overrideDuration;
        } else {
            $plannedStart = (string) ($bookingRequest->planned_start?->format('Y-m-d H:i:s') ?? '');
            $durationMinutes = (int) ($bookingRequest->planned_duration ?? 0);
        }
        if ($warehouseId <= 0 || $plannedStart === '' || $durationMinutes <= 0) {
            return response()->json([
                'available' => false,
                'label' => 'Not Available',
                'reason' => 'Missing schedule information.',
            ]);
        }

        $check = $this->bookingService->checkAvailability(
            $warehouseId,
            $plannedGateId,
            $plannedStart,
            $durationMinutes,
            null
        );

        $available = ! empty($check['available']);

        $reason = (string) ($check['reason'] ?? '');
        $reason = $this->translateAvailabilityReason($reason);
        return response()->json([
            'available' => $available,
            'label' => $available ? 'Available' : 'Not Available',
            'reason' => $reason,
        ]);
    }

    private function translateAvailabilityReason(string $reason): string
    {
        $reason = trim($reason);
        if ($reason === '') {
            return '';
        }

        $map = [
            'Waktu ini sedang diblokir karena menunggu konfirmasi tim WH' => 'This time is blocked pending WH confirmation.',
            'Waktu ini sudah terisi oleh booking lain' => 'This time is already occupied by another booking.',
            'Booking harus dalam jam operasional (07:00 - 23:00)' => 'Booking must be within operating hours (07:00 - 23:00).',
            'Booking harus selesai sebelum jam 23:00' => 'Booking must finish before 23:00.',
            'Gate penuh / tidak tersedia untuk jadwal ini. Silakan pilih gate atau waktu lain.' => 'Gate is not available for this schedule. Please choose another gate or time.',
            'Gate tidak tersedia' => 'Gate is not available.',
        ];

        if (array_key_exists($reason, $map)) {
            return $map[$reason];
        }

        return $reason;
    }

    /**
     * Show booking detail
     */
    public function show($id)
    {
        $booking = BookingRequest::with([
            'requester',
            'approver',
            'convertedSlot',
            'convertedSlot.warehouse',
            'convertedSlot.plannedGate',
            'convertedSlot.actualGate',
        ])->findOrFail($id);

        $warehouses = Warehouse::all();

        $gates = Gate::where('is_active', true)
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
            'planned_gate_id' => 'required|integer|exists:md_gates,id',
        ]);

        $bookingRequest = BookingRequest::where('id', $id)
            ->where('status', BookingRequest::STATUS_PENDING)
            ->with(['requester'])
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
                    $reason = (string) ($check['reason'] ?? 'Gate is not available');
                    $reason = (string) ($check['reason'] ?? 'Gate is not available');
                    return back()->with('error', $reason);
                }
            }

            Log::info('Approve booking request started', ['booking_id' => $id, 'user_id' => Auth::id()]);

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
                    'approval_notes' => $bookingRequest->notes,
                    'status' => Slot::STATUS_PENDING_APPROVAL,
                    'slot_type' => 'planned',
                    'created_by' => $bookingRequest->requested_by,
                    'requested_by' => $bookingRequest->requested_by,
                    'requested_at' => $bookingRequest->created_at,
                ]);

                $approvalAction = (string) $request->input('approval_action', Slot::APPROVAL_APPROVED);
                $this->bookingService->approveBooking(
                    $slot,
                    Auth::user(),
                    $request->notes,
                    (int) $bookingRequest->id,
                    $approvalAction
                );

                Log::info('Booking service approve completed', ['slot_id' => $slot->id]);

                $bookingRequest->update([
                    'status' => BookingRequest::STATUS_APPROVED,
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                    'approval_notes' => $request->notes,
                    'planned_gate_id' => $plannedGateId,
                    'warehouse_id' => $warehouseId,
                    'converted_slot_id' => $slot->id,
                ]);

                Log::info('Booking request updated to approved', ['booking_id' => $bookingRequest->id]);

                return $slot;
            });

            if (! empty($bookingRequest->planned_start)) {
                $approvedDate = $bookingRequest->planned_start instanceof \DateTimeInterface
                    ? $bookingRequest->planned_start->format('Y-m-d')
                    : date('Y-m-d', strtotime((string) $bookingRequest->planned_start));
                \Illuminate\Support\Facades\Cache::forget("vendor_availability_{$approvedDate}");
            }

            // Notification is dispatched by BookingApprovalService::approveBooking()
            // Do NOT dispatch again here to avoid double notification (#31/#32)

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
            ->with(['requester'])
            ->firstOrFail();

        try {
            $bookingRequest->update([
                'status' => BookingRequest::STATUS_REJECTED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'approval_notes' => $request->reason,
            ]);

            // Clear availability cache to restore slot availability
            $plannedStart = Carbon::parse($bookingRequest->planned_start);
            Cache::forget("vendor_availability_{$plannedStart->format('Y-m-d')}");

            // Notify vendor (requester) about rejected booking via email + database
            try {
                if ($bookingRequest->requester) {
                    $bookingRequest->requester->notify(new BookingRejected(null, $bookingRequest, $request->reason));
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to send booking rejected notification: ' . $e->getMessage());
            }

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
        $gates = Gate::where('is_active', true)->with('warehouse')->get();
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
            'planned_gate_id' => 'required|integer|exists:md_gates,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $bookingRequest = BookingRequest::where('id', $id)
            ->where('status', BookingRequest::STATUS_PENDING)
            ->with(['requester'])
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
            return back()->withInput()->with('error', 'This time is blocked while awaiting warehouse team confirmation');
        }

        $bookingRequest->update([
            'planned_start' => $plannedStart,
            'planned_duration' => (int) $request->planned_duration,
            'planned_gate_id' => $plannedGateId,
            'warehouse_id' => $warehouseId,
            'approval_notes' => $request->notes,
        ]);

        $plannedStartAt = $plannedStart instanceof \DateTimeInterface
            ? $plannedStart
            : Carbon::parse((string) $plannedStart);
        \Illuminate\Support\Facades\Cache::forget("vendor_availability_{$plannedStartAt->format('Y-m-d')}");

        $request->merge(['approval_action' => Slot::APPROVAL_RESCHEDULED]);
        return $this->approve($request, $id);
    }

    /**
     * AJAX: Get calendar data for admin view
     */
    public function calendarData(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:md_warehouse,id',
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
                    /** @var Slot $s */
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
            'holidays' => $this->getHolidaysForDate($date),
        ]);
    }

    /**
     * Get holidays for a specific date
     */
    private function getHolidaysForDate(string $date): array
    {
        return \App\Helpers\HolidayHelper::getHolidayMap($date);
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

    /**
     * AJAX: Reminder data for pending bookings within the next 5 hours
     */
    public function reminderData(Request $request)
    {
        $now = now();
        $limit = $now->copy()->addHours(5);

        $pending = BookingRequest::query()
            ->where('status', BookingRequest::STATUS_PENDING)
            ->whereNotNull('planned_start')
            ->whereBetween('planned_start', [$now, $limit])
            ->orderBy('planned_start')
            ->limit(20)
            ->get(['id', 'request_number', 'po_number', 'supplier_name', 'planned_start']);

        $items = $pending->map(function (BookingRequest $booking) use ($now) {
            $plannedStart = $booking->planned_start;
            $minutesToStart = $plannedStart ? $now->diffInMinutes($plannedStart, false) : null;

            return [
                'id' => $booking->id,
                'request_number' => $booking->request_number,
                'po_number' => $booking->po_number,
                'supplier_name' => $booking->supplier_name,
                'planned_start' => $plannedStart ? $plannedStart->format('Y-m-d H:i') : null,
                'minutes_to_start' => $minutesToStart,
                'show_url' => route('bookings.show', $booking->id, false),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'count' => $items->count(),
            'items' => $items,
        ]);
    }
}
