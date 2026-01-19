<?php

namespace App\Http\Controllers;

use App\Services\SlotService;
use App\Services\DashboardStatsService;
use App\Services\BottleneckAnalysisService;
use App\Services\GateStatusService;
use App\Services\ScheduleTimelineService;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Support\Facades\DB;
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
        $today = date('Y-m-d');

        // Validate and prepare date ranges
        [$rangeStart, $rangeEnd] = $this->validateAndPrepareDateRange($request, $today);

        // Get request parameters
        $scheduleDate = (string) $request->query('schedule_date', $today);
        $scheduleFrom = trim($request->query('schedule_from', ''));
        $scheduleTo = trim($request->query('schedule_to', ''));
        $activityDate = (string) $request->query('activity_date', $today);
        $activityWarehouseId = (int) $request->query('activity_warehouse', 0);
        $activityUserId = (int) $request->query('activity_user', 0);

        // Get statistics data
        $rangeStats = $this->statsService->getRangeStats($rangeStart, $rangeEnd);
        $onTimeStats = $this->statsService->getOnTimeStats($rangeStart, $rangeEnd);
        $onTimeWarehouseStats = $this->statsService->getOnTimeWarehouseStats($rangeStart, $rangeEnd);
        $targetStats = $this->statsService->getTargetAchievementStats($rangeStart, $rangeEnd);
        $targetWarehouseStats = $this->statsService->getTargetAchievementWarehouseStats($rangeStart, $rangeEnd);
        $completionStats = $this->statsService->getCompletionStats($rangeStart, $rangeEnd);
        $targetSegmentStats = $this->statsService->getTargetSegmentStats($rangeStart, $rangeEnd);
        $trendData = $this->statsService->getTrendData($rangeStart, $rangeEnd);
        $averageTimes = $this->statsService->getAverageTimes($rangeStart, $rangeEnd);

        // Get bottleneck analysis
        $bottleneckData = $this->bottleneckService->analyzeBottlenecks($rangeStart, $rangeEnd);

        // Get gate status
        $gateCards = $this->gateService->getGateCards($today);

        // Get schedule and timeline data
        $schedule = $this->timelineService->getSchedule($scheduleDate, $scheduleFrom, $scheduleTo);
        $timelineBlocks = $this->timelineService->getTimelineBlocks($scheduleDate);

        // Get activity data
        $activityData = $this->statsService->getActivityStats($activityDate, $activityWarehouseId, $activityUserId);

        // Calculate completion rate
        $completionRate = $this->statsService->calculateCompletionRate(
            $rangeStats['total'],
            $trendData['completed_total']
        );

        return view('dashboard', [
            'today' => $today,
            'range_start' => $rangeStart,
            'range_end' => $rangeEnd,

            // Range statistics
            'totalRange' => $rangeStats['total'],
            'totalAllRange' => $rangeStats['total_all'],
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

            // Trend data
            'trendDays' => $trendData['days'],
            'trendCounts' => $trendData['counts'],
            'trendInbound' => $trendData['inbound'] ?? [],
            'trendOutbound' => $trendData['outbound'] ?? [],
            'completedInRange' => $trendData['completed_total'],
            'avg7' => $trendData['avg_7_days'],

            // Direction-based statistics
            'onTimeDir' => $onTimeStats,
            'targetDir' => $targetStats,

            // Warehouse-based statistics (for WH dropdown)
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

            // Target segment data
            'targetSegmentLabels' => $targetSegmentStats['labels'],
            'targetSegmentAchieve' => $targetSegmentStats['achieve'],
            'targetSegmentNotAchieve' => $targetSegmentStats['not_achieve'],
            'targetSegmentDirections' => $targetSegmentStats['directions'],

            // Bottleneck analysis
            'bottleneckRows' => $bottleneckData['rows'],
            'bottleneckLabels' => $bottleneckData['labels'],
            'bottleneckValues' => $bottleneckData['values'],
            'bottleneckDirections' => $bottleneckData['directions'],
            'bottleneckThresholdMinutes' => $bottleneckData['threshold_minutes'],

            // Average times
            'avgLeadMinutes' => $averageTimes['avg_lead_minutes'],
            'avgProcessMinutes' => $averageTimes['avg_process_minutes'],

            // Gate status
            'gateCards' => $gateCards,

            // Schedule and timeline
            'schedule_date' => $scheduleDate,
            'schedule_from' => $scheduleFrom,
            'schedule_to' => $scheduleTo,
            'schedule' => $schedule,
            'timelineBlocksByGate' => $timelineBlocks,

            // Activity data
            'activity_date' => $activityDate,
            'activity_warehouse' => $activityWarehouseId,
            'activity_user' => $activityUserId,
            'activityWarehouses' => $activityData['warehouses'],
            'activityUsers' => $activityData['users'],
            'recentActivities' => $activityData['activities'],
        ]);
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
            } catch (\Throwable $e) {
                $rangeStart = $today;
            }
            $rangeEnd = $today;
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
}
