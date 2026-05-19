<?php

namespace App\Http\Controllers;

use App\Helpers\HolidayHelper;
use App\Models\BookingRequest;
use App\Models\Gate;
use App\Models\Slot;
use App\Models\TruckTypeDuration;
use App\Models\User;
use App\Notifications\BookingRequestSubmitted;
use App\Notifications\SlotLifecycleNotification;
use App\Services\BookingApprovalService;
use App\Services\PoSearchService;
use App\Services\SlotService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Milon\Barcode\DNS1D;

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
            if ($aNorm === '') {
                $aNorm = '0';
            }
            if ($bNorm === '') {
                $bNorm = '0';
            }

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
            $query->where('br.id_booking_requests', '<>', $excludeRequestId);
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
        if (! $row) {
            return null;
        }

        return (int) $row->target_duration_minutes;
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
                    $q->whereIn(DB::raw('LOWER(roles_name)'), [
                        'section head',
                        'super account',
                    ]);
                })
                ->get();

            if ($admins->isEmpty()) {
                Log::warning('No admin recipients found for booking request notification', [
                    'booking_request_id' => $bookingRequest->id_booking_requests,
                ]);
            }

            foreach ($admins as $admin) {
                /** @var User $admin */
                $admin->notify(new BookingRequestSubmitted($bookingRequest));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send booking request notification: '.$e->getMessage());
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
        $brBase = BookingRequest::where('requested_by', $user->id_users)
            ->whereDate('planned_start', '>=', $rangeStart)
            ->whereDate('planned_start', '<=', $rangeEnd);

        // Slot stats (operational statuses live on slots table)
        // Ensure we only count slots that are actually tied to a BookingRequest visible to the vendor
        $slotBase = Slot::whereIn('id_slots', function ($q) use ($user) {
            $q->select('converted_slot_id')
                ->from('booking_requests')
                ->where('requested_by', $user->id_users)
                ->whereNotNull('converted_slot_id');
        })
            ->where(function ($q) {
                $q->whereNull('slot_type')->orWhere('slot_type', '!=', 'unplanned');
            })
            ->whereDate('planned_start', '>=', $rangeStart)
            ->whereDate('planned_start', '<=', $rangeEnd);

        $pendingCount = (clone $brBase)->where('status', BookingRequest::STATUS_PENDING)->count();
        // Count approved BRs that have no slot as scheduled (to handle legacy data/seeders)
        $approvedBrCount = (clone $brBase)->where('status', BookingRequest::STATUS_APPROVED)
            ->whereNull('converted_slot_id')->count();
        $scheduledCount = (clone $slotBase)->where('status', Slot::STATUS_SCHEDULED)->count() + $approvedBrCount;
        $waitingCount = (clone $slotBase)->where('status', Slot::STATUS_WAITING)->count();
        $inProgCount = (clone $slotBase)->where('status', Slot::STATUS_IN_PROGRESS)->count();
        $completedCount = (clone $slotBase)->where('status', Slot::STATUS_COMPLETED)->count();
        $rejectedCount = (clone $brBase)->where('status', BookingRequest::STATUS_REJECTED)->count();
        $cancelledCount = (clone $brBase)->where('status', BookingRequest::STATUS_CANCELLED)->count()
                        + (clone $slotBase)->where('status', Slot::STATUS_CANCELLED)->count();

        $stats = [
            'pending' => $pendingCount,
            'scheduled' => $scheduledCount,
            'waiting' => $waitingCount,
            'in_progress' => $inProgCount,
            'completed' => $completedCount,
            'rejected' => $rejectedCount,
            'cancelled' => $cancelledCount,
            // Dashboard "Total" card should only show core operational statuses
            'total' => $scheduledCount + $waitingCount + $inProgCount + $completedCount,
        ];

        // Get recent bookings (show both pending BR and converted slots)
        $recentBookingsQuery = BookingRequest::where('requested_by', $user->id_users)
            ->with(['convertedSlot', 'convertedSlot.warehouse', 'convertedSlot.plannedGate'])
            ->when($rangeStart && $rangeEnd, function ($q) use ($rangeStart, $rangeEnd) {
                $q->whereDate('planned_start', '>=', $rangeStart)
                    ->whereDate('planned_start', '<=', $rangeEnd);
            })
            ->orderBy('created_at', 'desc')
            ->limit(20);

        $recentBookings = $recentBookingsQuery->get();

        // Arrival and vendor filters are now handled client-side (no page reload)
        $arrivalFilter = '';

        $recentBookings = $recentBookings->take(20);

        // Performance metrics
        $performance = $this->computeVendorPerformance($user->id_users, $rangeStart, $rangeEnd);

        $isInternalVendor = $user->isInternalVendor();

        // For internal vendors: collect unique vendor names for filter dropdown
        $vendorNames = [];
        $vendorFilter = '';
        if ($isInternalVendor) {
            $vendorNames = BookingRequest::where('requested_by', $user->id_users)
                ->whereNotNull('supplier_name')
                ->where('supplier_name', '!=', '')
                ->distinct()
                ->pluck('supplier_name')
                ->sort()
                ->values()
                ->all();
        }

        return view('vendor.dashboard', compact('stats', 'recentBookings', 'performance', 'rangeStart', 'rangeEnd', 'arrivalFilter', 'isInternalVendor', 'vendorNames', 'vendorFilter'));
    }

    /**
     * AJAX: Get detailed booking list for dashboard chart click
     */
    public function ajaxChartDetails(Request $request)
    {
        $user = Auth::user();
        if (! $user->vendor_code && ! $user->isInternalVendor()) {
            return response()->json(['success' => false, 'message' => 'Account Configuration Error: Not linked to a Vendor.']);
        }
        $vendorCode = $this->getVendorCodeForUser();

        $status = $request->query('status', '');
        $dateFrom = $request->query('date_from', '');
        $dateTo = $request->query('date_to', '');

        if ($dateFrom === '' && $dateTo === '') {
            $dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
            $dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
        } elseif ($dateFrom !== '' && $dateTo === '') {
            $dateTo = $dateFrom;
        } elseif ($dateFrom === '' && $dateTo !== '') {
            $dateFrom = $dateTo;
        }

        // Query based on BookingRequest to match Dashboard logic
        $query = DB::table('booking_requests as br')
            ->leftJoin('slots as s', 'br.converted_slot_id', '=', 's.id_slots')
            ->leftJoin('md_warehouse as w', 'br.warehouse_id', '=', 'w.id_wh')
            ->where('br.requested_by', $user->id_users);

        // Apply date filter (Dashboard strictly uses planned_start for both BR and Slot counts)
        $query->whereDate(DB::raw('COALESCE(s.planned_start, br.planned_start)'), '>=', $dateFrom)
            ->whereDate(DB::raw('COALESCE(s.planned_start, br.planned_start)'), '<=', $dateTo);

        switch ($status) {
            case 'pending':
                $query->where('br.status', 'pending');
                break;
            case 'scheduled':
                $query->where(function ($q) {
                    $q->where('s.status', 'scheduled')
                        ->orWhere(function ($q2) {
                            $q2->where('br.status', 'approved')
                                ->whereNull('br.converted_slot_id');
                        });
                });
                break;
            case 'waiting':
                $query->where('s.status', 'waiting');
                break;
            case 'in_progress':
                $query->where('s.status', 'in_progress');
                break;
            case 'completed':
                $query->where('s.status', 'completed');
                break;
            case 'rejected':
                $query->where('br.status', 'rejected');
                break;
            case 'cancelled':
                $query->where(function ($q) {
                    $q->where('br.status', 'cancelled')
                        ->orWhere('s.status', 'cancelled');
                });
                break;
            case 'arrived':
                $query->where('s.status', 'arrived');
                break;
            default:
                $query->where('br.status', $status);
        }

        $query->select([
            'br.id_booking_requests',
            'br.po_number',
            DB::raw('COALESCE(s.status::varchar, br.status) as display_status'),
            'br.planned_start',
            's.actual_start',
            's.arrival_time',
            'w.wh_name as warehouse',
        ])
            ->orderBy('br.id_booking_requests', 'desc')
            ->limit(100);

        $results = $query->get()->map(function ($row) {
            $dateToShow = $row->actual_start ?: ($row->planned_start ?: $row->arrival_time);

            return [
                'id' => $row->id_booking_requests,
                'po' => $row->po_number ?: '-',
                'status' => $row->display_status,
                'date' => $dateToShow ? Carbon::parse($dateToShow)->format('d M Y H:i') : '-',
                'warehouse' => $row->warehouse,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
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
            ->where(function ($q) {
                $q->whereNull('slot_type')->orWhere('slot_type', '!=', 'unplanned');
            })
            ->whereNotNull('arrival_time')
            ->whereNotNull('planned_start')
            ->whereBetween('planned_start', [$rangeStart.' 00:00:00', $rangeEnd.' 23:59:59'])
            ->get();

        // Untuk rata-rata waiting & process, tetap gunakan slot yang sudah completed
        $completedSlots = Slot::where('requested_by', $userId)
            ->where(function ($q) {
                $q->whereNull('slot_type')->orWhere('slot_type', '!=', 'unplanned');
            })
            ->where('status', Slot::STATUS_COMPLETED)
            ->whereNotNull('actual_finish')
            ->whereBetween('actual_finish', [$rangeStart.' 00:00:00', $rangeEnd.' 23:59:59'])
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
            'on_time' => $onTime,
            'late' => $late,
            'avg_late' => $lateArrivalCount > 0 ? round($totalLateArrival / $lateArrivalCount) : null,
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

        $baseQuery = BookingRequest::where('requested_by', $user->id_users);

        // Filter by date range (align with Dashboard logic: prioritize Slot's date if converted)
        if ($request->filled('date_from')) {
            $dateFrom = $request->date_from;
            $dateTo = $request->date_to; // date_to can be empty, but usually filled together

            $baseQuery->where(function ($q) use ($dateFrom, $dateTo) {
                // Condition 1: Has a slot, and the slot's date is in range
                $q->whereHas('convertedSlot', function ($qSlot) use ($dateFrom, $dateTo) {
                    $qSlot->whereDate('planned_start', '>=', $dateFrom);
                    if ($dateTo) {
                        $qSlot->whereDate('planned_start', '<=', $dateTo);
                    }
                })
                // Condition 2: No slot yet, and the booking request's date is in range
                    ->orWhere(function ($qBr) use ($dateFrom, $dateTo) {
                        $qBr->whereNull('converted_slot_id')
                            ->whereDate('planned_start', '>=', $dateFrom);
                        if ($dateTo) {
                            $qBr->whereDate('planned_start', '<=', $dateTo);
                        }
                    });
            });
        }

        // Search (uses LOWER + table-qualified columns, consistent with Activity Logs pattern)
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $search = str_replace(['%', '_'], ['\\%', '\\_'], $search);

            $tokens = preg_split('/\s+/', $search) ?: [];
            $tokens = array_values(array_filter(array_map(fn ($t) => trim((string) $t), $tokens), fn ($t) => $t !== ''));

            $normalized = str_replace('-', ' ', $search);
            $moreTokens = preg_split('/\s+/', $normalized) ?: [];
            $moreTokens = array_values(array_filter(array_map(fn ($t) => trim((string) $t), $moreTokens), fn ($t) => $t !== ''));

            $allTokens = array_values(array_unique(array_merge($tokens, $moreTokens)));

            foreach ($allTokens as $tok) {
                $like = '%'.strtolower($tok).'%';
                $baseQuery->where(function ($q) use ($like) {
                    $q->whereRaw('LOWER(booking_requests.request_number) like ?', [$like])
                        ->orWhereRaw('LOWER(booking_requests.po_number) like ?', [$like])
                        ->orWhereRaw('LOWER(booking_requests.supplier_name) like ?', [$like])
                        ->orWhereRaw('LOWER(COALESCE(booking_requests.vehicle_number, \'\')) like ?', [$like]);
                });
            }
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
        // Dashboard drill-down may send operational slot statuses (waiting, in_progress, completed, scheduled)
        // which don't exist on BookingRequest. Map these to slot-level filters.
        if ($request->filled('status')) {
            $statusParam = $request->status;
            $slotStatuses = ['waiting', 'in_progress', 'completed', 'scheduled'];

            if (in_array($statusParam, $slotStatuses)) {
                // Filter by the converted slot's operational status
                $query->whereHas('convertedSlot', function ($qSlot) use ($statusParam) {
                    $qSlot->where('status', $statusParam);
                });
            } else {
                // Standard BR status filter (pending, approved, cancelled, rejected)
                $query->where('status', $statusParam);
            }
        }

        // Handle dynamic page size
        $pageSize = $request->query('page_size', '15');
        if ($pageSize === 'all') {
            $totalCount = $query->count();
            $bookings = $query->orderBy('created_at', 'desc')->paginate($totalCount ?: 1);
        } else {
            $limit = is_numeric($pageSize) ? (int) $pageSize : 15;
            $bookings = $query->orderBy('created_at', 'desc')->paginate($limit);
        }

        $isInternalVendor = $user->isInternalVendor();

        return view('vendor.bookings.index', compact('bookings', 'counts', 'isInternalVendor'));
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

        // If autocomplete finds nothing but the user typed an exact number, try getPoDetail to hit SO check
        if (empty($results) && strlen(preg_replace('/\D+/', '', $q)) >= 5) {
            $exactMatch = $this->poSearchService->getPoDetail($q);
            if ($exactMatch) {
                $results = [$exactMatch];
            }
        }

        $user = Auth::user();
        if (! $user?->isInternalVendor()) {
            $results = $this->filterPoResultsByVendor($results, $vendorCode);
        }

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
            return response()->json(['success' => false, 'message' => 'PO/SO number is required']);
        }

        $vendorCode = $this->getVendorCodeForUser();
        $user = Auth::user();
        if ($vendorCode === '' && ! $user?->isInternalVendor()) {
            return response()->json(['success' => false, 'message' => 'Vendor is not linked']);
        }

        $po = $this->poSearchService->getPoDetail($poNumber);
        if (! $po) {
            return response()->json(['success' => false, 'message' => 'PO/SO not found']);
        }

        $poVendorCode = trim((string) ($po['vendor_code'] ?? ''));
        $user = Auth::user();
        if (! $user?->isInternalVendor() && ($poVendorCode === '' || ! $this->vendorCodesMatch($poVendorCode, $vendorCode))) {
            return response()->json(['success' => false, 'message' => 'PO/SO is not assigned to your vendor']);
        }

        return response()->json(['success' => true, 'data' => $po]);
    }

    /**
     * Store new booking request
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (! $user->vendor_code && ! $user->isInternalVendor()) {
            return back()->withInput()->with('error', 'Account Configuration Error: Your user account is not linked to a Vendor profile.');
        }

        // Normalize DD-MM-YYYY → Y-m-d (daterangepicker submits display format)
        $rawDate = $request->input('planned_date', '');
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $rawDate)) {
            $parts = explode('-', $rawDate);
            $request->merge(['planned_date' => $parts[2].'-'.$parts[1].'-'.$parts[0]]);
        }

        $request->validate([
            'po_number' => 'required|array|min:1',
            'po_number.*' => 'required|string',
            'planned_gate_id' => 'nullable|integer|exists:md_gates,id_gates',
            'planned_date' => 'required|date_format:Y-m-d|after_or_equal:'.Carbon::today()->format('Y-m-d'),
            'planned_time' => 'required|date_format:H:i',
            'truck_type' => 'required|string|max:50',
            'vehicle_number' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z]{1,2}\s\d{1,4}\s[A-Za-z]{1,3}$/'],
            'driver_name' => 'nullable|string|max:50',
            'driver_number' => ['nullable', 'string', 'regex:/^08[0-9]{8,11}$/'],
            'notes' => 'nullable|string|max:500',
        ], [
            'vehicle_number.regex' => 'The vehicle number format is invalid (e.g., B 1234 ABC).',
            'driver_number.regex' => 'The driver phone must start with 08 and be between 10-13 digits.',
        ]);

        // Auto-assign gate based on availability
        $plannedDuration = $this->resolvePlannedDuration($request->truck_type);
        if ($plannedDuration === null) {
            return back()->withInput()->with('error', 'Please select a valid truck type.');
        }
        $plannedGateId = $this->assignAvailableGate($request->planned_date, $request->planned_time, $plannedDuration);

        if (! $plannedGateId) {
            return back()->withInput()->with('error', 'No available gates at the selected time for the selected truck type. Please choose a different time or truck type.');
        }

        $gate = Gate::where('id_gates', $plannedGateId)
            ->where('is_active', true)
            ->with('warehouse')
            ->first();

        if (! $gate) {
            return back()->withInput()->with('error', 'Unable to assign an active gate.');
        }

        $warehouseId = $gate->warehouse_id;
        $plannedStart = $request->planned_date.' '.$request->planned_time.':00';
        try {
            $plannedStartAt = Carbon::createFromFormat('Y-m-d H:i:s', $plannedStart);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Invalid planned schedule.');
        }

        $forcedTimes = Cache::get('admin_gates_forced_times_'.$request->planned_date, []);
        $forcedTimes = is_array($forcedTimes) ? $forcedTimes : [];
        $timeStr = date('H:i', strtotime($request->planned_time));
        $isForcedByAdmin = in_array($timeStr, $forcedTimes, true);

        // On Sunday/Holiday/Today/Tomorrow: block unless the specific time is forced open by WH team
        $today = Carbon::today();
        $minAllowedDate = Carbon::today()->addDays(2);
        $isTodayOrTomorrow = $plannedStartAt->greaterThanOrEqualTo($today) && $plannedStartAt->lessThan($minAllowedDate);
        $isSundayOrHoliday = $plannedStartAt->isSunday() || HolidayHelper::isHoliday($plannedStartAt) || $isTodayOrTomorrow;

        if ($isSundayOrHoliday && ! $isForcedByAdmin) {
            $holidayName = HolidayHelper::getHolidayName($plannedStartAt);
            if ($plannedStartAt->isSunday()) {
                return back()->withInput()->with('error', 'Booking on Sunday is not available. The warehouse team must open specific hours first.');
            }
            $msg = $holidayName
                ? "Booking on {$holidayName} is not available. The warehouse team must open specific hours first."
                : 'Booking on this holiday is not available. The warehouse team must open specific hours first.';

            return back()->withInput()->with('error', $msg);
        }

        if ($plannedStartAt->format('H:i') > '19:00') {
            return back()->withInput()->with('error', 'Maximum booking time is 19:00.');
        }

        // Validate booking doesn't overlap with admin-disabled time slots
        $disabledTimes = Cache::get('admin_gates_disabled_times_'.$request->planned_date, []);
        $disabledTimes = is_array($disabledTimes) ? $disabledTimes : [];
        if (! empty($disabledTimes)) {
            $disabledMap = array_fill_keys($disabledTimes, true);
            $bookingStart = strtotime($request->planned_time);
            $bookingEnd = strtotime('+'.$plannedDuration.' minutes', $bookingStart);
            for ($t = $bookingStart; $t < $bookingEnd; $t = strtotime('+30 minutes', $t)) {
                $sliceTime = date('H:i', $t);
                if (! empty($disabledMap[$sliceTime]) && ! $isForcedByAdmin) {
                    return back()->withInput()->with('error', 'The selected time is not available. Your booking duration ('.$plannedDuration.' min) overlaps with a blocked time slot ('.$sliceTime.'). Please choose a different time.');
                }
            }
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
        $poNumbersArray = array_filter(array_map('trim', $request->po_number ?? []));
        if (empty($poNumbersArray)) {
            return back()->withInput()->with('error', 'Please provide a valid PO/SO number.');
        }
        $poNumber = implode(', ', $poNumbersArray);

        // Validate against SAP remaining quantities
        $vendorCode = $this->getVendorCodeForUser();
        $bypassSap = (bool) $request->input('bypass_sap', false);
        $poDetail = null;
        $direction = null;
                if (! $bypassSap) {
            $firstPoDetail = null;
            $firstDirection = null;
            $firstVendorCode = null;
            $firstVendorName = null;

            // Check for duplicates
            if (count($poNumbersArray) !== count(array_unique($poNumbersArray))) {
                return back()->withInput()->with('error', "Duplicate PO/SO numbers are not allowed.");
            }

            foreach ($poNumbersArray as $index => $singlePo) {
                $detail = $this->poSearchService->getPoDetail($singlePo);
                if (! $detail) {
                    return back()->withInput()->with('error', "PO/SO '{$singlePo}' not found in SAP.");
                }

                $poVendorCode = trim((string) ($detail['vendor_code'] ?? ''));
                $poVendorName = trim((string) ($detail['vendor_name'] ?? ''));
                
                if (! $user->isInternalVendor() && ($poVendorCode === '' || ! $this->vendorCodesMatch($poVendorCode, $vendorCode))) {
                    return back()->withInput()->with('error', "PO/SO '{$singlePo}' is not assigned to your vendor.");
                }

                $dir = $this->resolveDirection($detail);

                if ($index === 0) {
                    $firstPoDetail = $detail;
                    $firstDirection = $dir;
                    $firstVendorCode = $poVendorCode;
                    $firstVendorName = $poVendorName;
                } else {
                    if ($dir !== $firstDirection) {
                        return back()->withInput()->with('error', "Multiple PO/SO must be of the same type (Inbound/Outbound). '{$singlePo}' is {$dir}, but the first is {$firstDirection}.");
                    }
                    if ($poVendorCode !== $firstVendorCode && $poVendorName !== $firstVendorName) {
                        return back()->withInput()->with('error', "Multiple PO/SO must be from the same vendor. '{$singlePo}' belongs to a different vendor.");
                    }
                }
            }
            $poDetail = $firstPoDetail;
            $direction = $firstDirection;
        } else {
            $direction = 'inbound'; // Default direction for bypass mode
        }

        try {
            $notes = (string) $request->notes;
            $driverName = trim((string) $request->driver_name);

            $bookingRequest = BookingRequest::create([
                'request_number' => null,
                'requested_by' => $user->id_users,
                'po_number' => $poNumber,
                'supplier_code' => ! empty($poDetail['supplier_code']) ? $poDetail['supplier_code'] : ($poDetail['vendor_code'] ?? null),
                'supplier_name' => ! empty($poDetail['supplier_name']) ? $poDetail['supplier_name'] : ($poDetail['vendor_name'] ?? null),
                'doc_date' => ! empty($poDetail['doc_date']) ? $poDetail['doc_date'] : null,
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
                ->route('vendor.bookings.show', $bookingRequest->id_booking_requests)
                ->with('success', 'Booking request submitted successfully. Please wait for admin approval.');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to submit booking: '.$e->getMessage());
        }
    }

    /**
     * Show booking detail
     */
    public function show($id)
    {
        $user = Auth::user();

        // Find booking
        $booking = BookingRequest::where('id_booking_requests', $id)
            ->where('requested_by', $user->id_users)
            ->with(['approver', 'convertedSlot', 'convertedSlot.warehouse', 'convertedSlot.plannedGate', 'convertedSlot.actualGate'])
            ->firstOrFail();

        $isInternalVendor = Auth::user()?->isInternalVendor() ?? false;

        return view('vendor.bookings.show', compact('booking', 'isInternalVendor'));
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

        $booking = BookingRequest::where('id_booking_requests', $id)
            ->where('requested_by', $user->id_users)
            ->where('status', BookingRequest::STATUS_PENDING)
            ->firstOrFail();

        try {
            $reason = trim((string) $request->reason);
            $actorName = trim((string) ($user->display_name ?? $user->name ?? $user->full_name ?? $user->username ?? 'Vendor'));
            $notes = 'Cancelled by '.$actorName;
            if ($reason !== '') {
                $notes .= ': '.$reason;
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

            // Notify Section Head & Super Account about vendor cancellation
            try {
                $recipients = User::where('is_active', true)
                    ->whereHas('roles', function ($q) {
                        $q->whereIn(DB::raw('LOWER(roles_name)'), ['section head', 'super account']);
                    })
                    ->get();

                if ($recipients->isNotEmpty()) {
                    $slotId = (int) $booking->converted_slot_id;
                    $ticketNumber = '';
                    $slotType = 'planned';

                    if ($slotId > 0) {
                        $slot = DB::table('slots')->where('id_slots', $slotId)->first();
                        $ticketNumber = $slot->ticket_number ?? '';
                        $slotType = $slot->slot_type ?? 'planned';
                    }

                    $notification = new SlotLifecycleNotification(
                        slotId: $slotId > 0 ? $slotId : $booking->id_booking_requests, // fallback to booking ID if slot not yet created
                        slotType: $slotType,
                        event: 'cancel',
                        poNumber: $booking->po_number ?? '',
                        vendorName: $booking->vendor_name ?? '',
                        ticketNumber: $ticketNumber,
                        performedBy: $actorName,
                        gateName: null,
                        reason: $reason
                    );

                    foreach ($recipients as $recipient) {
                        $recipient->notify(clone $notification);
                    }
                }
            } catch (\Throwable $e) {
                // Ignore notification error
            }

            return redirect()
                ->route('vendor.bookings.index')
                ->with('success', 'Booking cancelled successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to cancel booking: '.$e->getMessage());
        }
    }

    /**
     * Show slot availability calendar
     */
    public function availability(Request $request)
    {
        // Get all active gates for display
        $gates = Gate::where('is_active', true)->with('warehouse')->get();

        $selectedDate = $request->date ?? now()->addDays(2)->format('Y-m-d');

        // Get holidays for calendar using HolidayHelper
        $holidays = HolidayHelper::getHolidayMap($selectedDate);

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
                'planned_duration' => 'nullable|integer|min:0|max:720',
            ]);

            $date = $request->date;
            $hasExplicitDuration = $request->has('planned_duration') && $request->input('planned_duration') !== null;
            $plannedDuration = (int) ($request->input('planned_duration') ?? 60);
            if ($plannedDuration <= 0) {
                $plannedDuration = 60;
            }

            $isToday = $date === now()->format('Y-m-d');
            $minAllowed = now()->addHours(4);
            $disabledTimes = Cache::get('admin_gates_disabled_times_'.$date, []);
            $forcedTimes = Cache::get('admin_gates_forced_times_'.$date, []);
            if (! is_array($disabledTimes)) {
                $disabledTimes = [];
            }
            if (! is_array($forcedTimes)) {
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
            // Auto-block ALL times on Sunday or National Holiday.
            // Only times explicitly forced ON by the WH team can override.
            $isSundayOrHoliday = false;
            try {
                $dateObj = Carbon::parse($date);
                $today = Carbon::today();
                $minAllowedDate = Carbon::today()->addDays(2);
                $isTodayOrTomorrow = $dateObj->greaterThanOrEqualTo($today) && $dateObj->lessThan($minAllowedDate);
                $isSundayOrHoliday = $dateObj->isSunday() || HolidayHelper::isHoliday($dateObj) || $isTodayOrTomorrow;
            } catch (\Throwable $e) {
                // ignore parse error
            }

            if ($isSundayOrHoliday) {
                // Block every time slot that is NOT in forced_times
                $allTimeSlots = [];
                $s = strtotime('07:00');
                $e = strtotime('19:00');
                while ($s <= $e) {
                    $allTimeSlots[] = date('H:i', $s);
                    $s = strtotime('+30 minutes', $s);
                }
                foreach ($allTimeSlots as $ts) {
                    if (! in_array($ts, $forcedTimes, true)) {
                        $disabledTimes[] = $ts;
                    }
                }
                $disabledTimes = array_values(array_unique($disabledTimes));
            }

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
                $slotEnd = strtotime('+'.(int) $requestRow->planned_duration.' minutes', $slotStart);
                for ($currentTime = $slotStart; $currentTime < $slotEnd; $currentTime = strtotime('+30 minutes', $currentTime)) {
                    $timeKey = date('H:i', $currentTime);
                    $globalBlocked[$timeKey] = true;
                }
            }

            // Get all active gates - cached query
            $totalGates = Cache::remember('active_gates_count', 3600, function () {
                return Gate::where('is_active', true)->count();
            });

            // Retrieve all active gates to check true backend availability
            $gates = Gate::where('is_active', true)->with('warehouse')->get();

            // Check availability for each time slot (for the whole duration window)
            $availableSlots = [];
            foreach ($timeSlots as $time) {
                // Min 4 hours rule: times earlier than now+4h (only for today) should never show as available.
                if ($isToday) {
                    try {
                        $slotStartAt = Carbon::parse($date.' '.$time);
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
                $windowEnd = strtotime('+'.$plannedDuration.' minutes', $windowStart);
                for ($t = $windowStart; $t < $windowEnd; $t = strtotime('+30 minutes', $t)) {
                    $durationSlices[] = date('H:i', $t);
                }

                // Admin disable check:
                // - With explicit duration (Create Booking): check full duration window.
                //   e.g. admin disables 09:00, booking at 08:00 with 240min → blocked.
                // - Without explicit duration (Availability page): check start time only,
                //   matching the admin Gates view behavior.
                $blockedByAdmin = false;
                if ($hasExplicitDuration) {
                    foreach ($durationSlices as $sliceTime) {
                        if (! empty($disabledMap[$sliceTime])) {
                            $blockedByAdmin = true;
                            break;
                        }
                    }
                } else {
                    $blockedByAdmin = ! empty($disabledMap[$time]);
                }
                // Force is only evaluated on the selected start time.
                $forcedByAdmin = ! empty($forcedMap[$time]);

                $windowGloballyBlocked = false;
                foreach ($durationSlices as $sliceTime) {
                    if (! empty($globalBlocked[$sliceTime])) {
                        $windowGloballyBlocked = true;
                        break;
                    }
                }

                if ($windowGloballyBlocked) {
                    $isAvailable = ! $blockedByAdmin && $forcedByAdmin;
                    $availableSlots[] = [
                        'time' => $time,
                        'is_available' => $isAvailable,
                        'available_gates' => $isAvailable ? max(1, $totalGates) : 0,
                        'disabled_by_admin' => $blockedByAdmin,
                        'forced_by_admin' => $forcedByAdmin,
                    ];

                    continue;
                }

                $availableGates = 0;
                $isAvailable = false;

                if ($blockedByAdmin) {
                    $isAvailable = false;
                    $availableGates = 0;
                } elseif ($forcedByAdmin) {
                    $isAvailable = true;
                    $availableGates = max(1, $totalGates);
                } else {
                    $candidateStart = $date.' '.$time.':00';
                    foreach ($gates as $gate) {
                        $result = $this->bookingService->checkAvailability(
                            $gate->warehouse_id,
                            $gate->id_gates,
                            $candidateStart,
                            $plannedDuration,
                            null
                        );
                        if (! empty($result['available'])) {
                            $availableGates++;
                            break; // Early exit: we only need to know >= 1 is available
                        }
                    }
                    $isAvailable = ($availableGates > 0);
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
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed: '.$e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in getAvailableSlots: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Failed to load availability: '.$e->getMessage(),
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
            'planned_duration' => 'required|integer|min:0',
            'exclude_slot_id' => 'nullable|exists:slots,id_slots',
        ]);

        // Check all gates for availability
        $gates = Gate::where('is_active', true)->get();
        $availableGates = [];

        foreach ($gates as $gate) {
            $result = $this->bookingService->checkAvailability(
                $gate->warehouse_id,
                $gate->id_gates,
                $request->planned_start,
                $request->planned_duration,
                $request->exclude_slot_id
            );

            if ($result['available']) {
                $availableGates[] = [
                    'id_gates' => $gate->id_gates,
                    'gate_id' => $gate->id_gates,
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
                'No gates available at this time',
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
            $gateSlots = $slots->get($gate->id_gates, collect());
            $calendarData[] = [
                'gate' => [
                    'id_gates' => $gate->id_gates,
                    'id' => $gate->id_gates,
                    'name' => $gate->name ?? ($gate->warehouse->wh_code.'-'.$gate->gate_number),
                ],
                'slots' => $gateSlots->map(fn ($s) => [
                    'id_slots' => $s->id_slots,
                    'id' => $s->id_slots,
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
            ->join('md_warehouse as w', 's.warehouse_id', '=', 'w.id_wh')
            ->leftJoin('md_gates as pg', 's.planned_gate_id', '=', 'pg.id_gates')
            ->leftJoin('md_gates as ag', 's.actual_gate_id', '=', 'ag.id_gates')
            ->leftJoin('md_warehouse as wpg', 'pg.warehouse_id', '=', 'wpg.id_wh')
            ->leftJoin('md_warehouse as wag', 'ag.warehouse_id', '=', 'wag.id_wh')
            ->leftJoin('md_truck as td', 's.truck_type', '=', 'td.truck_type')
            ->where('s.id_slots', $slotId)
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

        if ((int) ($slot->requested_by ?? 0) !== (int) $user->id_users) {
            abort(403);
        }

        $cacheKey = 'ticket_pdf_'.$slotId.'_'.md5(json_encode([
            $slot->ticket_number ?? '',
            $slot->truck_number ?? '',
            $slot->vendor_name ?? '',
            $slot->vehicle_number_snap ?? '',
            $slot->direction ?? '',
            $slot->planned_start ?? '',
            $slot->planned_gate_number ?? '',
            $slot->actual_gate_number ?? '',
        ]));

        $pdfContent = Cache::remember($cacheKey, 3600, function () use ($slot, $slotId) {
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
                    $barcodePng = (string) Cache::remember('ticket_barcode_png_'.sha1($ticketNumber), 86400, function () use ($ticketNumber) {
                        $barcodeC = new DNS1D();
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
                        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath));
                    }
                } catch (\Throwable $e) {
                }
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

            $pdf = Pdf::loadView('slots.ticket', [
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

            return $pdf->output();
        });

        $filename = 'ticket-'.($slot->ticket_number ?? 'unknown').'.pdf';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Assign an available gate based on the planned date and time
     */
    private function assignAvailableGate($date, $time, $plannedDuration = 60)
    {
        $plannedDuration = (int) ($plannedDuration ?? 0);
        if ($plannedDuration < 0) {
            $plannedDuration = 0;
        }
        $plannedStart = $date.' '.$time.':00';

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
                $gate->id_gates,
                $plannedStart,
                $plannedDuration, // Use actual planned duration
                null
            );

            if ($result['available']) {
                return $gate->id_gates;
            }
        }
    }

    /**
     * AJAX: Get Sunday/Holiday dates that have admin-forced times.
     * Used by calendar and datepicker to know which otherwise-blocked dates are available.
     */
    public function ajaxForcedHolidayDates(Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $start = Carbon::parse($request->start)->startOfDay();
        $end = Carbon::parse($request->end)->endOfDay();

        // Limit range to 90 days max
        if ($start->diffInDays($end) > 90) {
            $end = $start->copy()->addDays(90);
        }

        $holidays = HolidayHelper::getHolidayMap($start->format('Y-m-d'));
        // If range spans two years, merge both
        if ($start->year !== $end->year) {
            $holidays = array_merge($holidays, HolidayHelper::getHolidayMap($end->format('Y-m-d')));
        }

        $forcedDates = [];
        $current = $start->copy();

        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            $today = Carbon::today();
            $minAllowedDate = Carbon::today()->addDays(2);
            $isTodayOrTomorrow = $current->greaterThanOrEqualTo($today) && $current->lessThan($minAllowedDate);
            $isSundayOrHoliday = $current->isSunday() || isset($holidays[$dateStr]) || $isTodayOrTomorrow;

            if ($isSundayOrHoliday) {
                // Date is clickable only if admin has explicitly forced some times open
                $forced = Cache::get('admin_gates_forced_times_'.$dateStr, []);
                if (is_array($forced) && count($forced) > 0) {
                    $forcedDates[] = $dateStr;
                }
            }

            $current->addDay();
        }

        return response()->json([
            'success' => true,
            'forced_dates' => $forcedDates,
        ]);
    }
}
