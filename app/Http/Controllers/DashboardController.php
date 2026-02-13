<?php

namespace App\Http\Controllers;

use App\Services\DashboardStatsService;
use App\Services\BottleneckAnalysisService;
use App\Services\GateStatusService;
use App\Services\ScheduleTimelineService;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardStatsService $statsService,
        private readonly BottleneckAnalysisService $bottleneckService,
        private readonly GateStatusService $gateService,
        private readonly ScheduleTimelineService $timelineService
    ) {
    }

    public function __invoke(Request $request)
    {
        // FORCE CHECK: Direct DB query to bypass Permission Cache
        if (Auth::check()) {
            $isVendor = DB::table('model_has_roles')
                ->join('md_roles', 'model_has_roles.role_id', '=', 'md_roles.id')
                ->where('model_has_roles.model_id', Auth::id())
                ->where(DB::raw('LOWER(md_roles.roles_name)'), 'vendor')
                ->exists();

            if ($isVendor) {
                return redirect()->route('vendor.dashboard');
            }

            $u = Auth::user();
            if ($u && is_callable([$u, 'hasRole'])) {
                try {
                    if ((bool) call_user_func([$u, 'hasRole'], ['Vendor', 'vendor'])) {
                        return redirect()->route('vendor.dashboard');
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }

        $dashboardData = $this->buildDashboardData($request);

        return view('dashboard.react', ['dashboardData' => $dashboardData]);
    }

    /**
     * AJAX endpoint: return dashboard data as JSON (no page reload)
     */
    public function data(Request $request)
    {
        return response()->json($this->buildDashboardData($request));
    }

    /**
     * Build all dashboard data from request parameters.
     * Shared by both the page view (__invoke) and the AJAX endpoint (data).
     */
    private function buildDashboardData(Request $request): array
    {
        $today = date('Y-m-d');

        // Validate and prepare date ranges
        [$rangeStart, $rangeEnd] = $this->validateAndPrepareDateRange($request, $today);

        // Get request parameters (timeline filters)
        $timelineDate = (string) $request->query('timeline_date', $today);
        $timelineFrom = trim($request->query('timeline_from', ''));
        $timelineTo = trim($request->query('timeline_to', ''));
        if ($timelineFrom === '' && $timelineTo === '') {
            $now = Carbon::now();
            $currentTime = $now->format('H:i');
            if ($currentTime >= '07:00' && $currentTime < '15:00') {
                $timelineFrom = '07:00';
                $timelineTo = '15:00';
            } elseif ($currentTime >= '15:00' && $currentTime < '23:00') {
                $timelineFrom = '15:00';
                $timelineTo = '23:00';
            } else {
                $timelineFrom = '23:00';
                $timelineTo = '07:00';
            }
        }
        // Get request parameters (schedule slide filters)
        $scheduleDate = (string) $request->query('schedule_date', $today);
        $scheduleFrom = trim($request->query('schedule_from', ''));
        $scheduleTo = trim($request->query('schedule_to', ''));
        if ($scheduleFrom === '' && $scheduleTo === '') {
            $now = Carbon::now();
            $currentTime = $now->format('H:i');
            if ($currentTime >= '07:00' && $currentTime < '15:00') {
                $scheduleFrom = '07:00';
                $scheduleTo = '15:00';
            } elseif ($currentTime >= '15:00' && $currentTime < '23:00') {
                $scheduleFrom = '15:00';
                $scheduleTo = '23:00';
            } else {
                $scheduleFrom = '23:00';
                $scheduleTo = '07:00';
            }
        }
        $activityDate = (string) $request->query('activity_date', $today);
        $activityWarehouseId = (int) $request->query('activity_warehouse', 0);
        $activityUserId = (int) $request->query('activity_user', 0);

        // Get statistics data
        $rangeStats = $this->statsService->getRangeStats($rangeStart, $rangeEnd);
        $directionByGate = $this->statsService->getDirectionByGate($rangeStart, $rangeEnd);
        $onTimeGateStats = $this->statsService->getOnTimeGateStats($rangeStart, $rangeEnd);
        $targetGateStats = $this->statsService->getTargetAchievementGateStats($rangeStart, $rangeEnd);
        $completionGateStats = $this->statsService->getCompletionGateStats($rangeStart, $rangeEnd);
        $onTimeStats = $this->statsService->getOnTimeStats($rangeStart, $rangeEnd);
        $onTimeWarehouseStats = $this->statsService->getOnTimeWarehouseStats($rangeStart, $rangeEnd);
        $targetStats = $this->statsService->getTargetAchievementStats($rangeStart, $rangeEnd);
        $targetWarehouseStats = $this->statsService->getTargetAchievementWarehouseStats($rangeStart, $rangeEnd);
        $completionStats = $this->statsService->getCompletionStats($rangeStart, $rangeEnd);
        $targetSegmentStats = $this->statsService->getTargetSegmentStats($rangeStart, $rangeEnd);
        $trendData = $this->statsService->getTrendData($rangeStart, $rangeEnd);
        $averageTimes = $this->statsService->getAverageTimes($rangeStart, $rangeEnd);
        $avgTimesByTruckType = $this->statsService->getAverageTimesByTruckType($rangeStart, $rangeEnd);

        // Get bottleneck analysis
        $bottleneckData = $this->bottleneckService->analyzeBottlenecks($rangeStart, $rangeEnd);

        // Get gate status
        $gateCards = $this->gateService->getGateCards($today);

        // Get schedule and timeline data
        $schedule = $this->timelineService->getSchedule($scheduleDate, $scheduleFrom, $scheduleTo);
        $timelineBlocks = $this->timelineService->getTimelineBlocks($timelineDate);

        // Add pending bookings from booking_requests to schedule data for chart
        $scheduleHasRange = $scheduleFrom !== '' && $scheduleTo !== '';
        $scheduleDateObj = Carbon::parse($scheduleDate ?: $today);
        $scheduleFromDt = $scheduleHasRange ? $scheduleDateObj->copy()->setTimeFromTimeString($scheduleFrom) : null;
        $scheduleToDt = $scheduleHasRange ? $scheduleDateObj->copy()->setTimeFromTimeString($scheduleTo) : null;
        if ($scheduleHasRange && $scheduleFromDt && $scheduleToDt && $scheduleFromDt->gt($scheduleToDt)) {
            $scheduleToDt->addDay();
        }

        $pendingBookings = \App\Models\BookingRequest::query()
            ->where('status', \App\Models\BookingRequest::STATUS_PENDING)
            ->when($scheduleHasRange && $scheduleFromDt && $scheduleToDt, function($q) use ($scheduleFromDt, $scheduleToDt) {
                return $q->whereBetween('planned_start', [$scheduleFromDt, $scheduleToDt]);
            }, function($q) use ($scheduleDate) {
                return $q->whereDate('planned_start', $scheduleDate);
            })
            ->get(['id', 'status', 'direction', 'planned_start', 'supplier_name', 'po_number', 'request_number'])
            ->map(function($booking) {
                return [
                    'id' => null,
                    'status' => $booking->status,
                    'direction' => $booking->direction,
                    'planned_start' => $booking->planned_start,
                    'supplier_name' => $booking->supplier_name,
                    'po_number' => $booking->po_number,
                    'request_number' => $booking->request_number,
                    'ticket_number' => $booking->request_number,
                    'vendor_name' => $booking->supplier_name,
                    'warehouse_name' => null,
                    'truck_type' => null,
                    'is_pending_booking' => true,
                ];
            })
            ->toArray();

        // Merge pending bookings with schedule data for chart
        $scheduleSlots = [];
        if (is_array($schedule)) {
            foreach ($schedule as $key => $val) {
                if (is_int($key) && is_array($val)) {
                    $scheduleSlots[] = $val;
                }
            }
        }
        $allScheduleData = array_values(array_merge($scheduleSlots, $pendingBookings));

        $processStatusCounts = $this->buildProcessStatusCounts($scheduleDate, $scheduleFrom, $scheduleTo);

        // Get activity data
        $activityData = $this->statsService->getActivityStats($activityDate, $activityWarehouseId, $activityUserId);

        // Calculate completion rate
        $completionRate = $this->statsService->calculateCompletionRate(
            $rangeStats['total'],
            $trendData['completed_total']
        );

        // Get Pending Approvals
        $pendingApprovals = \App\Models\Slot::where('status', 'pending_approval')
            ->with(['requester', 'warehouse'])
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();

        $totalAllRangeUi = (int) ($rangeStats['pending'] ?? 0)
            + (int) ($rangeStats['scheduled'] ?? 0)
            + (int) ($rangeStats['waiting'] ?? 0)
            + (int) ($rangeStats['active'] ?? 0)
            + (int) ($rangeStats['completed'] ?? 0)
            + (int) ($rangeStats['cancelled'] ?? 0);

        $avgTimesByTruckTypeFormatted = (function() use ($avgTimesByTruckType) {
            $master = [
                'Container 40ft (Loose)' => ['avg_lead_minutes' => null, 'avg_process_minutes' => null, 'total_count' => 0],
                'Container 40ft (Paletize)' => ['avg_lead_minutes' => null, 'avg_process_minutes' => null, 'total_count' => 0],
                'Container 20ft (Loose)' => ['avg_lead_minutes' => null, 'avg_process_minutes' => null, 'total_count' => 0],
                'Container 20ft (Paletize)' => ['avg_lead_minutes' => null, 'avg_process_minutes' => null, 'total_count' => 0],
                'Wingbox (Loose)' => ['avg_lead_minutes' => null, 'avg_process_minutes' => null, 'total_count' => 0],
                'Wingbox (Paletize)' => ['avg_lead_minutes' => null, 'avg_process_minutes' => null, 'total_count' => 0],
                'Fuso' => ['avg_lead_minutes' => null, 'avg_process_minutes' => null, 'total_count' => 0],
                'CDD/CDE' => ['avg_lead_minutes' => null, 'avg_process_minutes' => null, 'total_count' => 0],
            ];
            foreach ($avgTimesByTruckType as $item) {
                if (isset($master[$item->truck_type])) {
                    $master[$item->truck_type] = [
                        'avg_lead_minutes' => $item->avg_lead_minutes,
                        'avg_process_minutes' => $item->avg_process_minutes,
                        'total_count' => (int)$item->total_count,
                    ];
                }
            }
            $result = [];
            foreach ($master as $name => $data) {
                $result[] = array_merge(['truck_type' => $name], $data);
            }
            return $result;
        })();

        return [
            'pendingApprovals' => $pendingApprovals,
            'today' => $today,
            'range_start' => $rangeStart,
            'range_end' => $rangeEnd,
            'directionByGate' => $directionByGate,
            'onTimeGateData' => $onTimeGateStats['data'] ?? [],
            'targetGateData' => $targetGateStats['data'] ?? [],
            'completionGateData' => $completionGateStats['data'] ?? [],
            'totalRange' => $rangeStats['total'],
            'totalAllRange' => $totalAllRangeUi,
            'cancelledRange' => $rangeStats['cancelled'],
            'activeRange' => $rangeStats['active'],
            'pendingRange' => $rangeStats['pending'],
            'scheduledRange' => $rangeStats['scheduled'],
            'waitingRange' => $rangeStats['waiting'],
            'completedStatusRange' => $rangeStats['completed'],
            'lateRange' => $rangeStats['late'],
            'inboundRange' => $rangeStats['inbound'],
            'outboundRange' => $rangeStats['outbound'],
            'completedRange' => $onTimeStats['all']['on_time'] + $onTimeStats['all']['late'],
            'onTimeRange' => $onTimeStats['all']['on_time'],
            'achieveRange' => $targetStats['all']['achieve'],
            'notAchieveRange' => $targetStats['all']['not_achieve'],
            'trendDays' => $trendData['days'],
            'trendCounts' => $trendData['counts'],
            'trendInbound' => $trendData['inbound'] ?? [],
            'trendOutbound' => $trendData['outbound'] ?? [],
            'completedInRange' => $trendData['completed_total'],
            'avg7' => $trendData['avg_7_days'],
            'onTimeDir' => $onTimeStats,
            'targetDir' => $targetStats,
            'onTimeWarehouseData' => $onTimeWarehouseStats['data'] ?? [],
            'targetWarehouseData' => $targetWarehouseStats['data'] ?? [],
            'kpiWarehouses' => array_values(array_unique(array_merge(
                $onTimeWarehouseStats['warehouses'] ?? [],
                $targetWarehouseStats['warehouses'] ?? [],
                $completionStats['warehouses'] ?? []
            ))),
            'completionRate' => $completionRate,
            'completionTotalSlots' => $rangeStats['total'],
            'completionCompletedSlots' => $trendData['completed_total'],
            'completionData' => $completionStats['data'],
            'completionWarehouses' => $completionStats['warehouses'],
            'targetSegmentLabels' => $targetSegmentStats['labels'],
            'targetSegmentAchieve' => $targetSegmentStats['achieve'],
            'targetSegmentNotAchieve' => $targetSegmentStats['not_achieve'],
            'targetSegmentDirections' => $targetSegmentStats['directions'],
            'bottleneckRows' => $bottleneckData['rows'],
            'bottleneckLabels' => $bottleneckData['labels'],
            'bottleneckValues' => $bottleneckData['values'],
            'bottleneckDirections' => $bottleneckData['directions'],
            'bottleneckWarehouseCodes' => $bottleneckData['warehouseCodes'] ?? [],
            'bottleneckGateNumbers' => $bottleneckData['gateNumbers'] ?? [],
            'bottleneckThresholdMinutes' => $bottleneckData['threshold_minutes'],
            'avgLeadMinutes' => $averageTimes['avg_lead_minutes'],
            'avgProcessMinutes' => $averageTimes['avg_process_minutes'],
            'avgTimesByTruckType' => $avgTimesByTruckTypeFormatted,
            'gateCards' => $gateCards,
            'schedule_date' => $scheduleDate,
            'schedule_from' => $scheduleFrom,
            'schedule_to' => $scheduleTo,
            'timeline_date' => $timelineDate,
            'timeline_from' => $timelineFrom,
            'timeline_to' => $timelineTo,
            'schedule' => $allScheduleData,
            'slots_only' => $scheduleSlots,
            'timelineBlocksByGate' => $timelineBlocks,
            'processStatusCounts' => $processStatusCounts,
            'activity_date' => $activityDate,
            'activity_warehouse' => $activityWarehouseId,
            'activity_user' => $activityUserId,
            'activityWarehouses' => $activityData['warehouses'],
            'activityUsers' => $activityData['users'],
            'recentActivities' => $activityData['activities'],
            'holidays' => $this->getHolidaysForYear($today),
        ];
    }

    /**
     * Get holidays for current year
     */
    private function getHolidaysForYear(string $date): array
    {
        try {
            $year = date('Y', strtotime($date));
            $holidayData = \App\Helpers\HolidayHelper::getHolidaysByYear($year);
            return collect($holidayData)->pluck('name', 'date')->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function buildProcessStatusCounts(string $scheduleDate, string $scheduleFrom, string $scheduleTo): array
    {
        $counts = [
            'pending' => 0,
            'scheduled' => 0,
            'waiting' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];

        $dateFilter = $scheduleDate !== '' ? $scheduleDate : date('Y-m-d');

        $slotStats = DB::table('slots')
            ->where(function($q) use ($dateFilter) {
                $q->whereDate('actual_start', $dateFilter)
                    ->orWhereDate('planned_start', $dateFilter);
            })
            ->where(function($q) {
                $q->whereNull('slot_type')
                    ->orWhere('slot_type', '!=', 'unplanned');
            })
            ->whereNotIn('status', ['pending_approval', 'cancelled'])
            ->selectRaw("
                SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) as waiting,
                SUM(CASE WHEN status = 'arrived' THEN 1 ELSE 0 END) as arrived,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            ")
            ->first();

        $counts['scheduled'] = (int) ($slotStats->scheduled ?? 0);
        $counts['waiting'] = (int) (($slotStats->waiting ?? 0) + ($slotStats->arrived ?? 0));
        $counts['in_progress'] = (int) ($slotStats->in_progress ?? 0);
        $counts['completed'] = (int) ($slotStats->completed ?? 0);
        $counts['cancelled'] = (int) ($slotStats->cancelled ?? 0);

        $scheduleHasRange = $scheduleFrom !== '' && $scheduleTo !== '';
        $scheduleDateObj = Carbon::parse($dateFilter ?: date('Y-m-d'));
        $scheduleFromDt = $scheduleHasRange ? $scheduleDateObj->copy()->setTimeFromTimeString($scheduleFrom) : null;
        $scheduleToDt = $scheduleHasRange ? $scheduleDateObj->copy()->setTimeFromTimeString($scheduleTo) : null;
        if ($scheduleHasRange && $scheduleFromDt && $scheduleToDt && $scheduleFromDt->gt($scheduleToDt)) {
            $scheduleToDt->addDay();
        }

        $pendingCount = \App\Models\BookingRequest::query()
            ->where('status', \App\Models\BookingRequest::STATUS_PENDING)
            ->when($scheduleHasRange && $scheduleFromDt && $scheduleToDt, function($q) use ($scheduleFromDt, $scheduleToDt) {
                return $q->whereBetween('planned_start', [$scheduleFromDt, $scheduleToDt]);
            }, function($q) use ($dateFilter) {
                return $q->whereDate('planned_start', $dateFilter);
            })
            ->count();

        $counts['pending'] += $pendingCount;

        return $counts;
    }

    /**
     * Validate and prepare date range for dashboard
     */
    private function validateAndPrepareDateRange(Request $request, string $today): array
    {
        $rangeStart = trim($request->query('range_start', ''));
        $rangeEnd = trim($request->query('range_end', ''));

        if ($rangeStart === '' && $rangeEnd === '') {
            try {
                $dt = new DateTime($today);
                $rangeStart = $dt->format('Y-m-01');
                $rangeEnd = $dt->format('Y-m-t'); // Last day of current month (This Month)
            } catch (\Throwable $e) {
                $rangeStart = $today;
                $rangeEnd = $today;
            }
        } elseif ($rangeStart === '' && $rangeEnd !== '') {
            $rangeStart = $rangeEnd;
        } elseif ($rangeEnd === '' && $rangeStart !== '') {
            $rangeEnd = $rangeStart;
        }

        foreach (['rangeStart', 'rangeEnd'] as $var) {
            $val = $$var;
            $dt = DateTime::createFromFormat('Y-m-d', $val);
            if (!$dt || $dt->format('Y-m-d') !== $val) {
                $$var = $today;
            }
        }

        if (strtotime($rangeStart) > strtotime($rangeEnd)) {
            $tmp = $rangeStart;
            $rangeStart = $rangeEnd;
            $rangeEnd = $tmp;
        }

        return [$rangeStart, $rangeEnd];
    }

    /**
     * API: Get waiting reasons for a specific bottleneck gate/direction
     */
    public function waitingReasons(Request $request)
    {
        $warehouseCode = trim((string) $request->query('warehouse_code', ''));
        $gateNumber = trim((string) $request->query('gate_number', ''));
        $direction = trim((string) $request->query('direction', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        if ($warehouseCode === '' || $gateNumber === '' || $direction === '') {
            return response()->json(['success' => false, 'error' => 'Missing parameters'], 422);
        }

        $today = date('Y-m-d');
        if ($dateFrom === '') $dateFrom = date('Y-m-d', strtotime('-30 days'));
        if ($dateTo === '') $dateTo = $today;

        $reasons = $this->bottleneckService->getWaitingReasons($dateFrom, $dateTo, $warehouseCode, $gateNumber, $direction);

        return response()->json([
            'success' => true,
            'data' => $reasons,
            'gate' => "Gate {$gateNumber} ({$warehouseCode}) - " . ucfirst($direction),
        ]);
    }
}
