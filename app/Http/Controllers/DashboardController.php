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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('model_has_roles.model_id', Auth::id())
                ->where(DB::raw('LOWER(roles.roles_name)'), 'vendor') // Fixed column name & Case insensitive
                ->exists();

            if ($isVendor) {
                return redirect()->route('vendor.dashboard');
            }

            // Fallback (jaga-jaga Spatie load role dengan nama lain)
            if (Auth::user() && Auth::user()->hasRole(['Vendor', 'vendor'])) {
                return redirect()->route('vendor.dashboard');
            }
        }

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
        $avgTimesByTruckType = $this->statsService->getAverageTimesByTruckType($rangeStart, $rangeEnd);

        // Get bottleneck analysis
        $bottleneckData = $this->bottleneckService->analyzeBottlenecks($rangeStart, $rangeEnd);

        // Get gate status
        $gateCards = $this->gateService->getGateCards($today);

        // Get schedule and timeline data
        $schedule = $this->timelineService->getSchedule($scheduleDate, $scheduleFrom, $scheduleTo);
        $timelineBlocks = $this->timelineService->getTimelineBlocks($scheduleDate);

        // Add pending bookings from booking_requests to schedule data for chart
        $pendingBookings = \App\Models\BookingRequest::query()
            ->where('status', \App\Models\BookingRequest::STATUS_PENDING)
            ->when($scheduleFrom && $scheduleTo, function($q) use ($scheduleFrom, $scheduleTo) {
                return $q->whereBetween('planned_start', [$scheduleFrom, $scheduleTo]);
            }, function($q) use ($scheduleDate) {
                return $q->whereDate('planned_start', $scheduleDate);
            })
            ->get(['id', 'status', 'direction', 'planned_start', 'supplier_name', 'po_number', 'request_number'])
            ->map(function($booking) {
                return [
                    'id' => null, // Pending bookings don't have slot ID yet
                    'status' => $booking->status,
                    'direction' => $booking->direction,
                    'planned_start' => $booking->planned_start,
                    'supplier_name' => $booking->supplier_name,
                    'po_number' => $booking->po_number,
                    'request_number' => $booking->request_number,
                    'ticket_number' => $booking->request_number,
                    'vendor_name' => $booking->supplier_name,
                    'warehouse_name' => null, // Not available in booking_requests
                    'truck_type' => null, // Not available in booking_requests
                    'is_pending_booking' => true, // Flag to identify pending bookings
                ];
            })
            ->toArray();

        // Merge pending bookings with schedule data for chart
        $allScheduleData = array_merge($schedule, $pendingBookings);

        // Debug: Log schedule data
        Log::info('Schedule data sent to view', [
            'schedule_count' => count($schedule),
            'pending_count' => count($pendingBookings),
            'total_count' => count($allScheduleData),
            'schedule_data' => $schedule,
            'pending_data' => $pendingBookings,
            'merged_data' => $allScheduleData
        ]);

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

        return view('dashboard', [
            'pendingApprovals' => $pendingApprovals,
            'today' => $today,
            'range_start' => $rangeStart,
            'range_end' => $rangeEnd,

            // Range statistics
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
            'avgTimesByTruckType' => (function() use ($avgTimesByTruckType) {
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
                    $result[] = (object) array_merge(['truck_type' => $name], $data);
                }
                return $result;
            })(),

            // Gate status
            'gateCards' => $gateCards,

            // Schedule and timeline
            'schedule_date' => $scheduleDate,
            'schedule_from' => $scheduleFrom,
            'schedule_to' => $scheduleTo,
            'schedule' => $allScheduleData, // Includes pending from booking_requests
            'slots_only' => $schedule, // Pure slots data for chart
            'timelineBlocksByGate' => $timelineBlocks,

            // Activity data
            'activity_date' => $activityDate,
            'activity_warehouse' => $activityWarehouseId,
            'activity_user' => $activityUserId,
            'activityWarehouses' => $activityData['warehouses'],
            'activityUsers' => $activityData['users'],
            'recentActivities' => $activityData['activities'],
            'holidays' => $this->getHolidaysForYear($today),
        ]);
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
