<?php

namespace App\Http\Controllers;

use App\Models\Gate;
use App\Models\BookingRequest;
use App\Models\Slot;
use App\Models\TruckTypeDuration;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\BookingApprovalService;
use App\Services\PoSearchService;
use App\Services\SlotService;
use App\Notifications\BookingRequestSubmitted;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class VendorBookingController extends Controller
{
    public function __construct(
        private readonly BookingApprovalService $bookingService,
        private readonly SlotService $slotService,
        private readonly PoSearchService $poSearchService
    ) {}

    private function vendorCodesMatch(string $a, string $b): bool
    {
        $a = trim($a);
        $b = trim($b);

        if ($a === '' || $b === '') {
            return false;
        }

        if (strcasecmp($a, $b) === 0) {
            return true;
        }

        $aDigits = preg_replace('/\D+/', '', $a);
        $bDigits = preg_replace('/\D+/', '', $b);

        if ($aDigits !== '' && $bDigits !== '') {
            $aNorm = ltrim($aDigits, '0');
            $bNorm = ltrim($bDigits, '0');
            if ($aNorm === '') $aNorm = '0';
            if ($bNorm === '') $bNorm = '0';
            return $aNorm === $bNorm;
        }

        return false;
    }

    private function countPendingRequestOverlapGlobal(string $start, string $end, ?int $excludeRequestId = null): int
    {
        $dateAddExpr = $this->slotService->getDateAddExpression('br.planned_start', 'br.planned_duration');

        $query = DB::table('booking_requests as br')
            ->where('br.status', BookingRequest::STATUS_PENDING)
            ->whereRaw("? < {$dateAddExpr}", [$start])
            ->whereRaw('? > br.planned_start', [$end]);

        if ($excludeRequestId) {
            $query->where('br.id', '<>', $excludeRequestId);
        }

        return (int) $query->count();
    }

    private function resolvePlannedDuration(?string $truckType): ?int
    {
        $truckType = trim((string) ($truckType ?? ''));
        if ($truckType === '') {
            return null;
        }

        $row = TruckTypeDuration::where('truck_type', $truckType)->first();
        $duration = (int) ($row?->target_duration_minutes ?? 0);

        return $duration > 0 ? $duration : null;
    }

    private function resolveDirection(array $poDetail): string
    {
        $direction = strtolower(trim((string) ($poDetail['direction'] ?? '')));
        if (in_array($direction, ['inbound', 'outbound'], true)) {
            return $direction;
        }

        $customerCode = trim((string) ($poDetail['customer_code'] ?? ''));
        $supplierCode = trim((string) ($poDetail['supplier_code'] ?? ''));

        if ($customerCode !== '') {
            return 'outbound';
        }

        if ($supplierCode !== '') {
            return 'inbound';
        }

        return 'inbound';
    }

    private function notifyAdminsBookingRequest(BookingRequest $bookingRequest): void
    {
        try {
            $admins = User::where('is_active', true)
                ->whereHas('roles', function ($q) {
                    $q->whereRaw('LOWER(roles_name) = ?', ['section head']);
                })
                ->get();

            if ($admins->isEmpty()) {
                Log::warning('No admin recipients found for booking request notification', [
                    'booking_request_id' => $bookingRequest->id,
                ]);
            }

            foreach ($admins as $admin) {
                /** @var User $admin */
                $admin->notify(new BookingRequestSubmitted($bookingRequest));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send booking request notification: ' . $e->getMessage());
        }
    }

    private function getVendorCodeForUser(): string
    {
        $user = Auth::user();
        $vendorCode = $user?->vendor_code;
        return trim((string) ($vendorCode ?? ''));
    }

    private function filterPoResultsByVendor(array $results, string $vendorCode): array
    {
        $vendorCode = trim((string) $vendorCode);
        if ($vendorCode === '') {
            return [];
        }

        $out = [];
        foreach ($results as $row) {
            $poVendor = isset($row['vendor_code']) ? trim((string) $row['vendor_code']) : '';
            if ($poVendor === '') {
                continue;
            }

            if ($this->vendorCodesMatch($poVendor, $vendorCode)) {
                $out[] = $row;
            }
        }

        return array_values($out);
    }

    /**
     * Vendor Dashboard
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();

        $today = date('Y-m-d');
        $rangeStart = trim((string) $request->query('range_start', ''));
        $rangeEnd = trim((string) $request->query('range_end', ''));

        if ($rangeStart === '' && $rangeEnd === '') {
            $firstOfMonth = date('Y-m-01', strtotime($today));
            $lastOfMonth = date('Y-m-t', strtotime($today));
            $rangeStart = $firstOfMonth;
            $rangeEnd = $lastOfMonth;
        } elseif ($rangeStart === '' && $rangeEnd !== '') {
            $rangeStart = $rangeEnd;
        } elseif ($rangeEnd === '' && $rangeStart !== '') {
            $rangeEnd = $rangeStart;
        }

        foreach (['rangeStart', 'rangeEnd'] as $var) {
            $val = $$var;
            $dt = \DateTime::createFromFormat('Y-m-d', $val);
            if (! $dt || $dt->format('Y-m-d') !== $val) {
                $$var = $today;
            }
        }

        if (strtotime($rangeStart) > strtotime($rangeEnd)) {
            $tmp = $rangeStart;
            $rangeStart = $rangeEnd;
            $rangeEnd = $tmp;
        }

        // BookingRequest stats (only valid BR statuses: pending, approved, rejected, cancelled)
        $brBase = BookingRequest::where('requested_by', $user->id)
            ->whereDate('planned_start', '>=', $rangeStart)
            ->whereDate('planned_start', '<=', $rangeEnd);

        // Slot stats (operational statuses live on slots table)
        $slotBase = Slot::where('requested_by', $user->id)
            ->whereDate('planned_start', '>=', $rangeStart)
            ->whereDate('planned_start', '<=', $rangeEnd);

        $pendingCount   = (clone $brBase)->where('status', BookingRequest::STATUS_PENDING)->count();
        $scheduledCount = (clone $slotBase)->where('status', Slot::STATUS_SCHEDULED)->count();
        $waitingCount   = (clone $slotBase)->where('status', Slot::STATUS_WAITING)->count();
        $inProgCount    = (clone $slotBase)->where('status', Slot::STATUS_IN_PROGRESS)->count();
        $completedCount = (clone $slotBase)->where('status', Slot::STATUS_COMPLETED)->count();
        $rejectedCount  = (clone $brBase)->where('status', BookingRequest::STATUS_REJECTED)->count();
        $cancelledCount = (clone $brBase)->where('status', BookingRequest::STATUS_CANCELLED)->count()
                        + (clone $slotBase)->where('status', Slot::STATUS_CANCELLED)->count();

        $stats = [
            'pending'     => $pendingCount,
            'scheduled'   => $scheduledCount,
            'waiting'     => $waitingCount,
            'in_progress' => $inProgCount,
            'completed'   => $completedCount,
            'rejected'    => $rejectedCount,
            'cancelled'   => $cancelledCount,
            // Dashboard "Total" card should only show core operational statuses
            'total'       => $scheduledCount + $waitingCount + $inProgCount + $completedCount,
        ];

        // Get recent bookings (show both pending BR and converted slots)
        $recentBookingsQuery = BookingRequest::where('requested_by', $user->id)
            ->with(['convertedSlot', 'convertedSlot.warehouse', 'convertedSlot.plannedGate'])
            ->when($rangeStart && $rangeEnd, function ($q) use ($rangeStart, $rangeEnd) {
                $q->whereDate('planned_start', '>=', $rangeStart)
                    ->whereDate('planned_start', '<=', $rangeEnd);
            })
            ->orderBy('created_at', 'desc')
            ->limit(20);

        $recentBookings = $recentBookingsQuery->get();

        $arrivalFilter = (string) $request->query('arrival_filter', '');
        if (in_array($arrivalFilter, ['ontime', 'late'], true)) {
            $recentBookings = $recentBookings->filter(function ($booking) use ($arrivalFilter) {
                $slot = $booking->convertedSlot;
                if (! $slot || ! $slot->planned_start || ! $slot->arrival_time) {
                    return false;
                }

                $diffMin = $slot->planned_start->diffInMinutes($slot->arrival_time, false);

                if ($arrivalFilter === 'late') {
                    return $diffMin > 15;
                }

                return $diffMin <= 15;
            })->values();
        }

        $recentBookings = $recentBookings->take(5);

        // Performance metrics
        $performance = $this->computeVendorPerformance($user->id, $rangeStart, $rangeEnd);

        return view('vendor.dashboard', compact('stats', 'recentBookings', 'performance', 'rangeStart', 'rangeEnd', 'arrivalFilter'));
    }

    /**
     * Compute vendor performance metrics from completed slots
     */
    private function computeVendorPerformance(int $userId, string $rangeStart, string $rangeEnd): array
    {
        // Hitung on-time vs late untuk semua slot yang sudah punya arrival & planned_start,
        // terlepas dari statusnya (termasuk in_progress, waiting, dll.).
        // Range tanggal mengikuti planned_start agar konsisten dengan komponen dashboard lain.
        $arrivalSlots = Slot::where('requested_by', $userId)
            ->whereNotNull('arrival_time')
            ->whereNotNull('planned_start')
            ->whereBetween('planned_start', [$rangeStart . ' 00:00:00', $rangeEnd . ' 23:59:59'])
            ->get();

        // Untuk rata-rata waiting & process, tetap gunakan slot yang sudah completed
        $completedSlots = Slot::where('requested_by', $userId)
            ->where('status', Slot::STATUS_COMPLETED)
            ->whereNotNull('actual_finish')
            ->whereBetween('actual_finish', [$rangeStart . ' 00:00:00', $rangeEnd . ' 23:59:59'])
            ->get();

        $onTime = 0;
        $late = 0;
        $totalLateArrival = 0;
        $lateArrivalCount = 0;
        $totalWaiting = 0;
        $totalProcess = 0;
        $waitingCount = 0;
        $processCount = 0;

        foreach ($arrivalSlots as $slot) {
            // On-time vs late: compare arrival_time to planned_start
            if ($slot->arrival_time && $slot->planned_start) {
                // Positive value means truck arrived AFTER planned_start
                $diffMin = $slot->planned_start->diffInMinutes($slot->arrival_time, false);
                if ($diffMin > 15) {
                    $late++;
                    $totalLateArrival += $diffMin;
                    $lateArrivalCount++;
                } else {
                    $onTime++;
                }
            }
        }

        foreach ($completedSlots as $slot) {
            // Avg waiting: arrival_time to actual_start
            if ($slot->arrival_time && $slot->actual_start) {
                $totalWaiting += $slot->arrival_time->diffInMinutes($slot->actual_start);
                $waitingCount++;
            }

            // Avg process: actual_start to actual_finish
            if ($slot->actual_start && $slot->actual_finish) {
                $totalProcess += $slot->actual_start->diffInMinutes($slot->actual_finish);
                $processCount++;
            }
        }

        return [
            'on_time'     => $onTime,
            'late'        => $late,
            'avg_late'    => $lateArrivalCount > 0 ? round($totalLateArrival / $lateArrivalCount) : null,
            'avg_waiting' => $waitingCount > 0 ? round($totalWaiting / $waitingCount) : null,
            'avg_process' => $processCount > 0 ? round($totalProcess / $processCount) : null,
            'waiting_count' => $waitingCount,
            'process_count' => $processCount,
        ];
    }

    /**
     * List vendor's bookings
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $baseQuery = BookingRequest::where('requested_by', $user->id);

        // Filter by date range
        if ($request->filled('date_from')) {
            $baseQuery->whereDate('planned_start', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $baseQuery->whereDate('planned_start', '<=', $request->date_to);
        }

        // Search by ticket number
        if ($request->filled('search')) {
            $search = str_replace(['%', '_'], ['\\%', '\\_'], $request->search);
            $baseQuery->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhere('po_number', 'like', "%{$search}%")
                    ->orWhere('vehicle_number', 'like', "%{$search}%");
            });
        }

        // Counts for status tabs (independent from current status filter & pagination)
        $pendingCount = (clone $baseQuery)->where('status', BookingRequest::STATUS_PENDING)->count();
        $approvedCount = (clone $baseQuery)->where('status', BookingRequest::STATUS_APPROVED)->count();
        $cancelledCount = (clone $baseQuery)->where('status', BookingRequest::STATUS_CANCELLED)->count();
        $rejectedCount = (clone $baseQuery)->where('status', BookingRequest::STATUS_REJECTED)->count();
        $counts = [
            'pending' => $pendingCount,
            'approved' => $approvedCount,
            'cancelled' => $cancelledCount,
            'rejected' => $rejectedCount,
            'all' => $pendingCount + $approvedCount + $cancelledCount + $rejectedCount,
        ];

        $query = (clone $baseQuery)
            ->with(['convertedSlot', 'convertedSlot.warehouse', 'convertedSlot.plannedGate', 'approver']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('vendor.bookings.index', compact('bookings', 'counts'));
    }

    /**
     * Show booking creation form
     */
    public function create()
    {
        $truckTypes = TruckTypeDuration::orderBy('truck_type')->get();

        // Get all gates for gate-only selection
        $gates = Gate::where('is_active', true)->with('warehouse')->get();

        return view('vendor.bookings.create', compact('truckTypes', 'gates'));
    }

    public function ajaxPoSearch(Request $request)
    {
        $q = (string) $request->query('q', '');
        $vendorCode = $this->getVendorCodeForUser();

        // Prefer SAP search for autocomplete responsiveness
        $results = $this->poSearchService->searchPoSapOnly($q, 20);
        if (empty($results)) {
            // Fallback to hybrid search if SAP search fails
            $results = $this->poSearchService->searchPo($q);
        }
        $results = $this->filterPoResultsByVendor($results, $vendorCode);

        // Remaining quantities are evaluated on ajaxPoDetail (when user selects a PO)
        $filtered = array_values(array_slice($results, 0, 20));

        return response()->json([
            'success' => true,
            'data' => $filtered,
        ]);
    }

    public function ajaxPoDetail(string $poNumber)
    {
        $poNumber = trim($poNumber);
        if ($poNumber === '') {
            return response()->json(['success' => false, 'message' => 'PO/DO number is required']);
        }

        $vendorCode = $this->getVendorCodeForUser();
        if ($vendorCode === '') {
            return response()->json(['success' => false, 'message' => 'Vendor is not linked']);
        }

        $po = $this->poSearchService->getPoDetail($poNumber);
        if (! $po) {
            return response()->json(['success' => false, 'message' => 'PO/DO not found']);
        }

        $poVendorCode = trim((string) ($po['vendor_code'] ?? ''));
        if ($poVendorCode === '' || ! $this->vendorCodesMatch($poVendorCode, $vendorCode)) {
            return response()->json(['success' => false, 'message' => 'PO/DO is not assigned to your vendor']);
        }

        return response()->json(['success' => true, 'data' => $po]);
    }

    /**
     * Store new booking request
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->vendor_code) {
             return back()->withInput()->with('error', 'Account Configuration Error: Your user account is not linked to a Vendor profile.');
        }

        // Normalize DD-MM-YYYY → Y-m-d (daterangepicker submits display format)
        $rawDate = $request->input('planned_date', '');
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $rawDate)) {
            $parts = explode('-', $rawDate);
            $request->merge(['planned_date' => $parts[2] . '-' . $parts[1] . '-' . $parts[0]]);
        }

        $request->validate([
            'po_number' => 'required|string', // Enforce here
            'planned_gate_id' => 'nullable|integer|exists:md_gates,id',
            'planned_date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'planned_time' => 'required|date_format:H:i',
            'truck_type' => 'required|string|max:50',
            'vehicle_number' => 'required|string|max:50',
            'driver_name' => 'nullable|string|max:50',
            'driver_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        // Auto-assign gate based on availability
        $plannedDuration = $this->resolvePlannedDuration($request->truck_type);
        $plannedGateId = $this->assignAvailableGate($request->planned_date, $request->planned_time, $plannedDuration);

        if (!$plannedGateId) {
            return back()->withInput()->with('error', 'No available gates at the selected time for the selected truck type. Please choose a different time or truck type.');
        }

        $gate = Gate::where('id', $plannedGateId)
            ->where('is_active', true)
            ->with('warehouse')
            ->first();

        if (!$gate) {
            return back()->withInput()->with('error', 'Unable to assign an active gate.');
        }

        $warehouseId = $gate->warehouse_id;
        $plannedStart = $request->planned_date . ' ' . $request->planned_time . ':00';
        try {
            $plannedStartAt = Carbon::createFromFormat('Y-m-d H:i:s', $plannedStart);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Invalid planned schedule.');
        }

        if ($plannedStartAt->isSunday()) {
            return back()->withInput()->with('error', 'Booking date cannot be on Sunday.');
        }

        // Check if date is holiday using HolidayHelper
        try {
            if (\App\Helpers\HolidayHelper::isHoliday($plannedStartAt)) {
                $holidayName = \App\Helpers\HolidayHelper::getHolidayName($plannedStartAt);
                $msg = $holidayName ? "Booking date is a holiday: {$holidayName}." : 'Booking date cannot be on a holiday.';
                return back()->withInput()->with('error', $msg);
            }
        } catch (\Exception $e) {
            // If holiday check fails, continue with booking
        }

        $minAllowed = now()->addHours(4);
        if ($plannedStartAt->lessThan($minAllowed)) {
            return back()->withInput()->with('error', 'Booking must be at least 4 hours from now.');
        }

        if ($plannedStartAt->format('H:i') > '19:00') {
            return back()->withInput()->with('error', 'Maximum booking time is 19:00.');
        }
        $plannedDuration = $this->resolvePlannedDuration($request->truck_type);
        if ($plannedDuration === null) {
            return back()->withInput()->with('error', 'Please select a truck type to determine duration.');
        }

        $plannedEnd = $this->slotService->computePlannedFinish($plannedStart, $plannedDuration);
        if ($plannedEnd === null) {
            return back()->withInput()->with('error', 'Invalid planned schedule.');
        }

        // Blocking GLOBAL: pending request blocks all vendors for the same time range
        $pendingOverlap = $this->countPendingRequestOverlapGlobal($plannedStart, $plannedEnd, null);
        if ($pendingOverlap > 0) {
            return back()->withInput()->with('error', 'This time is blocked while awaiting warehouse team confirmation');
        }

        // Resolve PO ID from PO Number
        // Since we validated 'required', we strictly process it.
        $poNumber = trim($request->po_number);

        // Validate against SAP remaining quantities
        $vendorCode = $this->getVendorCodeForUser();
        $poDetail = $this->poSearchService->getPoDetail($poNumber);
        if (!$poDetail) {
            return back()->withInput()->with('error', 'PO/DO not found in SAP.');
        }
        $poVendorCode = trim((string) ($poDetail['vendor_code'] ?? ''));
        if ($poVendorCode === '' || !$this->vendorCodesMatch($poVendorCode, $vendorCode)) {
            return back()->withInput()->with('error', 'PO/DO is not assigned to your vendor.');
        }
        $direction = $this->resolveDirection($poDetail);

        try {
            $notes = (string) $request->notes;
            $driverName = trim((string) $request->driver_name);

            $bookingRequest = BookingRequest::create([
                'request_number' => null,
                'requested_by' => $user->id,
                'po_number' => $poNumber,
                'supplier_code' => $poDetail['supplier_code'] ?? null,
                'supplier_name' => $poDetail['supplier_name'] ?? ($poDetail['vendor_name'] ?? null),
                'doc_date' => !empty($poDetail['doc_date']) ? $poDetail['doc_date'] : null,
                'direction' => $direction,
                'planned_start' => $plannedStart,
                'planned_duration' => $plannedDuration,
                'planned_gate_id' => $plannedGateId,
                'warehouse_id' => $warehouseId,
                'truck_type' => $request->truck_type,
                'vehicle_number' => $request->vehicle_number,
                'driver_name' => $driverName !== '' ? $driverName : null,
                'driver_number' => $request->driver_number,
                'notes' => $notes !== '' ? $notes : null,
                'status' => BookingRequest::STATUS_PENDING,
            ]);

            Cache::forget("vendor_availability_{$plannedStartAt->format('Y-m-d')}");
            $this->notifyAdminsBookingRequest($bookingRequest);

            return redirect()
                ->route('vendor.bookings.show', $bookingRequest->id)
                ->with('success', 'Booking request submitted successfully. Please wait for admin approval.');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to submit booking: ' . $e->getMessage());
        }
    }

    /**
     * Show booking detail
     */
    public function show($id)
    {
        $user = Auth::user();

        // Find booking
        $booking = BookingRequest::where('id', $id)
            ->where('requested_by', $user->id)
            ->with(['approver', 'convertedSlot', 'convertedSlot.warehouse', 'convertedSlot.plannedGate', 'convertedSlot.actualGate'])
            ->firstOrFail();

        return view('vendor.bookings.show', compact('booking'));
    }

    /**
     * Cancel pending booking
     */
    public function cancel(Request $request, $id)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $booking = BookingRequest::where('id', $id)
            ->where('requested_by', $user->id)
            ->where('status', BookingRequest::STATUS_PENDING)
            ->firstOrFail();

        try {
            $reason = trim((string) $request->reason);
            $actorName = trim((string) ($user->name ?? $user->full_name ?? $user->username ?? 'Vendor'));
            $notes = 'Cancelled by ' . $actorName;
            if ($reason !== '') {
                $notes .= ': ' . $reason;
            }

            $booking->update([
                'status' => BookingRequest::STATUS_CANCELLED,
                'approval_notes' => $notes,
            ]);

            if (! empty($booking->planned_start)) {
                $cancelDate = $booking->planned_start instanceof \DateTimeInterface
                    ? $booking->planned_start->format('Y-m-d')
                    : date('Y-m-d', strtotime((string) $booking->planned_start));
                // Clear all cached availability variants (duration-specific)
                $durations = [30, 60, 90, 120, 150, 180, 240, 300, 360, 420, 480, 540, 600, 660, 720];
                foreach ($durations as $d) {
                    Cache::forget("vendor_availability_{$cancelDate}_{$d}");
                }
            }

            return redirect()
                ->route('vendor.bookings.index')
                ->with('success', 'Booking cancelled successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to cancel booking: ' . $e->getMessage());
        }
    }

    /**
     * Show slot availability calendar
     */
    public function availability(Request $request)
    {
        // Get all active gates for display
        $gates = Gate::where('is_active', true)->with('warehouse')->get();

        $selectedDate = $request->date ?? now()->format('Y-m-d');

        // Get holidays for calendar using HolidayHelper
        $holidays = \App\Helpers\HolidayHelper::getHolidayMap($selectedDate);

        return view('vendor.bookings.availability', compact('gates', 'selectedDate', 'holidays'));
    }

    /**
     * AJAX: Get available slots for a date
     */
    public function getAvailableSlots(Request $request)
    {
        try {
            $request->validate([
                'date' => 'required|date',
                'planned_duration' => 'nullable|integer|min:30|max:720',
            ]);

            $date = $request->date;
            $plannedDuration = (int) ($request->input('planned_duration') ?? 60);
            if ($plannedDuration <= 0) {
                $plannedDuration = 60;
            }

            $isToday = $date === now()->format('Y-m-d');
            $minAllowed = now()->addHours(4)->seconds(0);
            $disabledTimes = Cache::get('admin_gates_disabled_times_' . $date, []);
            $forcedTimes = Cache::get('admin_gates_forced_times_' . $date, []);
            if (!is_array($disabledTimes)) {
                $disabledTimes = [];
            }
            if (!is_array($forcedTimes)) {
                $forcedTimes = [];
            }
            $disabledTimes = array_values(array_unique(array_filter(array_map(function ($time) {
                $val = trim((string) $time);
                return preg_match('/^\d{2}:\d{2}$/', $val) ? $val : null;
            }, $disabledTimes))));
            $forcedTimes = array_values(array_unique(array_filter(array_map(function ($time) {
                $val = trim((string) $time);
                return preg_match('/^\d{2}:\d{2}$/', $val) ? $val : null;
            }, $forcedTimes))));
            $disabledMap = array_fill_keys($disabledTimes, true);
            $forcedMap = array_fill_keys($forcedTimes, true);

            // Use cache for 5 minutes to improve performance
            $cacheKey = "vendor_availability_{$date}_{$plannedDuration}";
            // Do not cache today's availability because it depends on current time (min 4 hours rule).
            if (! $isToday) {
                $cached = cache()->get($cacheKey);
                if ($cached) {
                    return response()->json($cached);
                }
            }

            // Get all time slots from 07:00 to 19:00 with 30-minute intervals
            $timeSlots = [];
            $startTime = strtotime('07:00');
            $endTime = strtotime('19:00');

            while ($startTime <= $endTime) {
                $timeSlots[] = date('H:i', $startTime);
                $startTime = strtotime('+30 minutes', $startTime);
            }

            // Global blocking: pending booking requests block all gates
            $pendingRequests = BookingRequest::whereDate('planned_start', $date)
                ->where('status', BookingRequest::STATUS_PENDING)
                ->select('planned_start', 'planned_duration')
                ->get();

            $globalBlocked = [];
            foreach ($pendingRequests as $requestRow) {
                $start = Carbon::parse($requestRow->planned_start);
                $slotStart = strtotime($start->format('H:i'));
                $slotEnd = strtotime('+' . (int) $requestRow->planned_duration . ' minutes', $slotStart);
                for ($currentTime = $slotStart; $currentTime < $slotEnd; $currentTime = strtotime('+30 minutes', $currentTime)) {
                    $timeKey = date('H:i', $currentTime);
                    $globalBlocked[$timeKey] = true;
                }
            }

            // Get all existing slots for the date - optimized query
            Log::info('Getting slots for date: ' . $date);
            $existingSlots = Slot::whereDate('planned_start', $date)
                ->whereIn('status', [
                    Slot::STATUS_PENDING_APPROVAL,
                    Slot::STATUS_SCHEDULED,
                    Slot::STATUS_ARRIVED,
                    Slot::STATUS_WAITING,
                    Slot::STATUS_IN_PROGRESS,
                ])
                ->select('planned_start', 'planned_duration', 'planned_gate_id')
                ->get();

            Log::info('Found ' . $existingSlots->count() . ' slots');

            // Build time conflicts map
            $timeConflicts = [];
            foreach ($existingSlots as $slot) {
                $slotStart = strtotime($slot->planned_start->format('H:i'));
                // Calculate end time based on duration
                $slotEnd = strtotime('+ ' . $slot->planned_duration . ' minutes', $slotStart);

                $currentTime = $slotStart;
                while ($currentTime < $slotEnd) {
                    $timeKey = date('H:i', $currentTime);
                    if (!isset($timeConflicts[$timeKey])) {
                        $timeConflicts[$timeKey] = [];
                    }
                    $timeConflicts[$timeKey][] = $slot->planned_gate_id;
                    $currentTime = strtotime('+30 minutes', $currentTime);
                }
            }

            // Get all active gates - cached query
            $totalGates = Cache::remember('active_gates_count', 3600, function () {
                return Gate::where('is_active', true)->count();
            });

            // Check availability for each time slot (for the whole duration window)
            $availableSlots = [];
            foreach ($timeSlots as $time) {
                // Min 4 hours rule: times earlier than now+4h (only for today) should never show as available.
                if ($isToday) {
                    try {
                        $slotStartAt = Carbon::parse($date . ' ' . $time);
                        if ($slotStartAt->lessThan($minAllowed)) {
                            $availableSlots[] = [
                                'time' => $time,
                                'is_available' => false,
                                'available_gates' => 0,
                                'disabled_by_admin' => false,
                                'forced_by_admin' => false,
                                'too_soon' => true,
                            ];
                            continue;
                        }
                    } catch (\Throwable $e) {
                        // If parsing fails, continue with normal logic.
                    }
                }

                $durationSlices = [];
                $windowStart = strtotime($time);
                $windowEnd = strtotime('+' . $plannedDuration . ' minutes', $windowStart);
                for ($t = $windowStart; $t < $windowEnd; $t = strtotime('+30 minutes', $t)) {
                    $durationSlices[] = date('H:i', $t);
                }

                // Admin disable/force rules must apply to the whole duration window.
                // If ANY slice is disabled by admin, the start time should be unavailable (unless forced on the start time).
                $blockedByAdmin = false;
                foreach ($durationSlices as $sliceTime) {
                    if (!empty($disabledMap[$sliceTime])) {
                        $blockedByAdmin = true;
                        break;
                    }
                }
                // Force is only evaluated on the selected start time.
                $forcedByAdmin = !empty($forcedMap[$time]);

                $windowGloballyBlocked = false;
                foreach ($durationSlices as $sliceTime) {
                    if (!empty($globalBlocked[$sliceTime])) {
                        $windowGloballyBlocked = true;
                        break;
                    }
                }

                if ($windowGloballyBlocked) {
                    $isAvailable = !$blockedByAdmin && $forcedByAdmin;
                    $availableSlots[] = [
                        'time' => $time,
                        'is_available' => $isAvailable,
                        'available_gates' => $isAvailable ? max(1, $totalGates) : 0,
                        'disabled_by_admin' => $blockedByAdmin,
                        'forced_by_admin' => $forcedByAdmin,
                    ];
                    continue;
                }

                $conflictedGates = [];
                foreach ($durationSlices as $sliceTime) {
                    if (!empty($timeConflicts[$sliceTime])) {
                        $conflictedGates = array_merge($conflictedGates, $timeConflicts[$sliceTime]);
                    }
                }
                $availableGates = $totalGates - count(array_unique($conflictedGates));
                $isAvailable = $availableGates > 0;
                if ($blockedByAdmin) {
                    $isAvailable = false;
                    $availableGates = 0;
                }
                if ($forcedByAdmin) {
                    $isAvailable = true;
                    $availableGates = max(1, $availableGates);
                }

                $availableSlots[] = [
                    'time' => $time,
                    'is_available' => $isAvailable,
                    'available_gates' => $availableGates,
                    'disabled_by_admin' => $blockedByAdmin,
                    'forced_by_admin' => $forcedByAdmin,
                ];
            }

            $response = [
                'success' => true,
                'slots' => $availableSlots,
            ];

            // Cache for 5 minutes
            if (! $isToday) {
                cache()->put($cacheKey, $response, 300);
            }

            return response()->json($response);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in getAvailableSlots: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load availability: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * AJAX: Check availability for a specific time
     */
    public function checkAvailability(Request $request)
    {
        $request->validate([
            'planned_start' => 'required|date',
            'planned_duration' => 'required|integer|min:30',
            'exclude_slot_id' => 'nullable|exists:slots,id',
        ]);

        // Check all gates for availability
        $gates = Gate::where('is_active', true)->get();
        $availableGates = [];

        foreach ($gates as $gate) {
            $result = $this->bookingService->checkAvailability(
                $gate->warehouse_id,
                $gate->id,
                $request->planned_start,
                $request->planned_duration,
                $request->exclude_slot_id
            );

            if ($result['available']) {
                $availableGates[] = [
                    'gate_id' => $gate->id,
                    'gate_number' => $gate->gate_number,
                    'warehouse_id' => $gate->warehouse_id,
                ];
            }
        }

        return response()->json([
            'available' => count($availableGates) > 0,
            'available_gates' => $availableGates,
            'message' => count($availableGates) > 0 ?
                sprintf('%d gate(s) available', count($availableGates)) :
                'No gates available at this time'
        ]);
    }

    /**
     * AJAX: Get truck type duration suggestion
     */
    public function getTruckTypeDuration(Request $request)
    {
        $truckType = $request->truck_type;

        $duration = TruckTypeDuration::where('truck_type', $truckType)->first();

        return response()->json([
            'success' => true,
            'duration' => $duration?->target_duration_minutes ?? 60,
        ]);
    }

    /**
     * AJAX: Get slots for calendar view
     */
    public function calendarSlots(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $date = $request->date;

        // Get all active gates
        $gates = Gate::where('is_active', true)
            ->with(['warehouse'])
            ->orderBy('warehouse_id')
            ->orderBy('gate_number')
            ->get();

        // Get slots for the date across all warehouses
        $slots = Slot::whereDate('planned_start', $date)
            ->where('status', Slot::STATUS_PENDING_APPROVAL)
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
                    'start_time' => $s->planned_start->format('H:i'),
                    'end_time' => $s->planned_start->copy()->addMinutes($s->planned_duration)->format('H:i'),
                    'duration' => $s->planned_duration,
                    'status' => $s->status,
                    'status_label' => $s->status_label,
                    'status_color' => $s->status_badge_color,
                    'direction' => $s->direction,
                ])->values(),
            ];
        }

        return response()->json([
            'success' => true,
            'date' => $date,
            'gates' => $calendarData,
        ]);
    }

    public function ticket(int $id)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        $slotId = (int) $id;

        $slot = DB::table('slots as s')
            ->join('md_warehouse as w', 's.warehouse_id', '=', 'w.id')
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

        if (! $slot) {
            return redirect()->route('vendor.bookings.index')->with('error', 'Booking not found');
        }

        if ((int) ($slot->requested_by ?? 0) !== (int) $user->id) {
            abort(403);
        }

        $gateLetter = '-';
        try {
            $whCode = trim((string) ($slot->planned_gate_warehouse_code ?? ''));
            $gateNo = trim((string) ($slot->planned_gate_number ?? ''));
            if ($whCode !== '' && $gateNo !== '') {
                $gateLetter = $this->slotService->getGateDisplayName($whCode, $gateNo);
            }
        } catch (\Throwable $e) {
            $gateLetter = '-';
        }

        $barcodePng = '';
        if (! empty($slot->ticket_number)) {
            try {
                $ticketNumber = (string) $slot->ticket_number;
                $barcodePng = (string) Cache::remember('ticket_barcode_png_' . sha1($ticketNumber), 86400, function () use ($ticketNumber) {
                    $barcodeC = new \Milon\Barcode\DNS1D();
                    $barcodeC->setStorPath(storage_path('app/public/'));
                    return (string) $barcodeC->getBarcodePNG($ticketNumber, 'C128', 2.5, 60);
                });
            } catch (\Throwable $e) {
                $barcodePng = '';
                Log::warning('Barcode generation failed', [
                    'slot_id' => $slotId,
                    'ticket_number' => $slot->ticket_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $logoDataUri = Cache::rememberForever('ticket_logo_data_uri', function () {
            try {
                $logoPath = public_path('img/logo-full.png');
                if (is_string($logoPath) && $logoPath !== '' && file_exists($logoPath)) {
                    return 'data:image/png;base64,' . base64_encode((string) file_get_contents($logoPath));
                }
            } catch (\Throwable $e) {
            }
            return null;
        });

        $ticketCss = Cache::rememberForever('ticket_css_inline', function () {
            try {
                $cssPath = public_path('ticket.css');
                if (is_string($cssPath) && $cssPath !== '' && file_exists($cssPath)) {
                    return (string) file_get_contents($cssPath);
                }
            } catch (\Throwable $e) {
            }
            return '';
        });

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('slots.ticket', [
            'slot' => $slot,
            'gateLetter' => $gateLetter,
            'barcodePng' => $barcodePng,
            'barcodeSvg' => null,
            'barcodeHtml' => null,
            'logoDataUri' => $logoDataUri,
            'ticketCss' => $ticketCss,
        ])
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('chroot', public_path())
            ->setPaper([0, 0, 252, 396], 'portrait');

        return $pdf->stream('ticket-' . ($slot->ticket_number ?? 'unknown') . '.pdf');
    }

    /**
     * Assign an available gate based on the planned date and time
     */
    private function assignAvailableGate($date, $time, $plannedDuration = 60)
    {
        $plannedStart = $date . ' ' . $time . ':00';

        // Get all active gates
        $gates = Gate::where('is_active', true)
            ->with('warehouse')
            ->orderBy('warehouse_id')
            ->orderBy('gate_number')
            ->get();

        foreach ($gates as $gate) {
            // Check if gate is available at the planned time using bookingService
            $result = $this->bookingService->checkAvailability(
                $gate->warehouse_id,
                $gate->id,
                $plannedStart,
                $plannedDuration, // Use actual planned duration
                null
            );

            if ($result['available']) {
                return $gate->id;
            }
        }

        return null;
    }
}
