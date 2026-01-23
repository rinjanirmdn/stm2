<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
    public function __construct(
        private readonly SlotService $slotService
    ) {}

    /**
     * Get range statistics for dashboard
     * Optimized: Single query instead of 10 separate queries
     */
    public function getRangeStats(string $start, string $end): array
    {
        $rangeDate = DB::raw('DATE(planned_start)');

        // Single query with conditional aggregation instead of 10 separate queries
        $stats = DB::table('slots')
            ->whereBetween($rangeDate, [$start, $end])
            ->selectRaw("
                COUNT(*) AS total_all,
                SUM(CASE WHEN status != 'cancelled' THEN 1 ELSE 0 END) AS total,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) AS scheduled,
                SUM(CASE WHEN status IN ('arrived', 'waiting') THEN 1 ELSE 0 END) AS waiting,
                SUM(CASE WHEN status IN ('pending_approval', 'pending_vendor_confirmation') THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status = 'completed' AND is_late = true THEN 1 ELSE 0 END) AS late,
                SUM(CASE WHEN direction = 'inbound' AND status != 'cancelled' THEN 1 ELSE 0 END) AS inbound,
                SUM(CASE WHEN direction = 'outbound' AND status != 'cancelled' THEN 1 ELSE 0 END) AS outbound
            ")
            ->first();

        return [
            'total_all' => (int) ($stats->total_all ?? 0),
            'total' => (int) ($stats->total ?? 0),
            'cancelled' => (int) ($stats->cancelled ?? 0),
            'active' => (int) ($stats->active ?? 0),
            'scheduled' => (int) ($stats->scheduled ?? 0),
            'waiting' => (int) ($stats->waiting ?? 0),
            'pending' => (int) ($stats->pending ?? 0),
            'completed' => (int) ($stats->completed ?? 0),
            'late' => (int) ($stats->late ?? 0),
            'inbound' => (int) ($stats->inbound ?? 0),
            'outbound' => (int) ($stats->outbound ?? 0),
        ];
    }

    /**
     * Get on-time statistics by direction
     * Optimized: Single query with aggregation
     */
    public function getOnTimeStats(string $start, string $end): array
    {
        $rangeDate = DB::raw('DATE(planned_start)');

        // Single query for all stats
        $stats = DB::table('slots')
            ->where('status', 'completed')
            ->whereBetween($rangeDate, [$start, $end])
            ->selectRaw("
                SUM(CASE WHEN is_late = false OR is_late IS NULL THEN 1 ELSE 0 END) AS on_time_all,
                SUM(CASE WHEN is_late = true THEN 1 ELSE 0 END) AS late_all,
                SUM(CASE WHEN direction = 'inbound' AND (is_late = false OR is_late IS NULL) THEN 1 ELSE 0 END) AS on_time_in,
                SUM(CASE WHEN direction = 'inbound' AND is_late = true THEN 1 ELSE 0 END) AS late_in,
                SUM(CASE WHEN direction = 'outbound' AND (is_late = false OR is_late IS NULL) THEN 1 ELSE 0 END) AS on_time_out,
                SUM(CASE WHEN direction = 'outbound' AND is_late = true THEN 1 ELSE 0 END) AS late_out
            ")
            ->first();

        return [
            'all' => [
                'on_time' => (int) ($stats->on_time_all ?? 0),
                'late' => (int) ($stats->late_all ?? 0)
            ],
            'inbound' => [
                'on_time' => (int) ($stats->on_time_in ?? 0),
                'late' => (int) ($stats->late_in ?? 0)
            ],
            'outbound' => [
                'on_time' => (int) ($stats->on_time_out ?? 0),
                'late' => (int) ($stats->late_out ?? 0)
            ],
        ];
    }

    /**
     * Get target achievement statistics
     */
    public function getTargetAchievementStats(string $start, string $end): array
    {
        $exprActual = $this->slotService->getTimestampDiffMinutesExpression('s.actual_start', 's.actual_finish');
        $rangeDate = DB::raw('DATE(s.planned_start)');

        $achieveRange = (int) DB::table('slots as s')
            ->leftJoin('truck_type_durations as td', 's.truck_type', '=', 'td.truck_type')
            ->where('s.status', 'completed')
            ->whereNotNull('td.target_duration_minutes')
            ->whereNotNull('s.actual_finish')
            ->whereNotNull('s.actual_start')
            ->whereBetween($rangeDate, [$start, $end])
            ->whereRaw("{$exprActual} <= td.target_duration_minutes + 15")
            ->count();

        $notAchieveRange = (int) DB::table('slots as s')
            ->leftJoin('truck_type_durations as td', 's.truck_type', '=', 'td.truck_type')
            ->where('s.status', 'completed')
            ->whereNotNull('td.target_duration_minutes')
            ->whereNotNull('s.actual_finish')
            ->whereNotNull('s.actual_start')
            ->whereBetween($rangeDate, [$start, $end])
            ->whereRaw("{$exprActual} > td.target_duration_minutes + 15")
            ->count();

        $targetDir = [
            'all' => ['achieve' => $achieveRange, 'not_achieve' => $notAchieveRange],
            'inbound' => ['achieve' => 0, 'not_achieve' => 0],
            'outbound' => ['achieve' => 0, 'not_achieve' => 0],
        ];

        $targetDirRows = DB::table('slots as s')
            ->leftJoin('truck_type_durations as td', 's.truck_type', '=', 'td.truck_type')
            ->where('s.status', 'completed')
            ->whereNotNull('td.target_duration_minutes')
            ->whereNotNull('s.actual_finish')
            ->whereNotNull('s.actual_start')
            ->whereBetween($rangeDate, [$start, $end])
            ->groupBy('s.direction')
            ->select([
                's.direction',
                DB::raw("SUM(CASE WHEN {$exprActual} <= td.target_duration_minutes + 15 THEN 1 ELSE 0 END) AS achieve_count"),
                DB::raw("SUM(CASE WHEN {$exprActual} > td.target_duration_minutes + 15 THEN 1 ELSE 0 END) AS not_achieve_count"),
            ])
            ->get();

        foreach ($targetDirRows as $r) {
            $dir = (string) ($r->direction ?? '');
            $key = $dir === 'inbound' ? 'inbound' : ($dir === 'outbound' ? 'outbound' : null);
            if ($key) {
                $targetDir[$key]['achieve'] = (int) ($r->achieve_count ?? 0);
                $targetDir[$key]['not_achieve'] = (int) ($r->not_achieve_count ?? 0);
            }
        }

        return $targetDir;
    }

    /**
     * Get completion statistics by warehouse and direction
     */
    public function getCompletionStats(string $start, string $end): array
    {
        $completionRows = DB::table('slots as s')
            ->join('warehouses as w', 's.warehouse_id', '=', 'w.id')
            ->whereBetween(DB::raw('DATE(s.planned_start)'), [$start, $end])
            ->where('s.status', '!=', 'cancelled')
            ->groupBy(['s.direction', 'w.wh_code'])
            ->select([
                's.direction',
                'w.wh_code as warehouse_code',
                DB::raw('COUNT(*) AS total_slots'),
                DB::raw("SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) AS completed_slots"),
            ])
            ->get();

        $completionWarehouses = [];
        $completionData = [];

        foreach ($completionRows as $r) {
            $code = (string) ($r->warehouse_code ?? '');
            if ($code !== '' && !in_array($code, $completionWarehouses, true)) {
                $completionWarehouses[] = $code;
            }
            $completionData[] = [
                'direction' => (string) ($r->direction ?? ''),
                'warehouse_code' => $code,
                'total' => (int) ($r->total_slots ?? 0),
                'completed' => (int) ($r->completed_slots ?? 0),
            ];
        }

        return [
            'warehouses' => $completionWarehouses,
            'data' => $completionData,
        ];
    }

    /**
     * Get target achievement by segment (warehouse + direction)
     */
    public function getTargetSegmentStats(string $start, string $end): array
    {
        $exprActual = $this->slotService->getTimestampDiffMinutesExpression('s.actual_start', 's.actual_finish');
        $rangeDate = DB::raw('DATE(s.planned_start)');

        $segRows = DB::table('slots as s')
            ->leftJoin('truck_type_durations as td', 's.truck_type', '=', 'td.truck_type')
            ->join('warehouses as w', 's.warehouse_id', '=', 'w.id')
            ->where('s.status', 'completed')
            ->whereNotNull('td.target_duration_minutes')
            ->whereNotNull('s.actual_finish')
            ->whereNotNull('s.actual_start')
            ->whereBetween($rangeDate, [$start, $end])
            ->groupBy(['s.direction', 'w.wh_code'])
            ->orderBy('w.wh_code')
            ->orderBy('s.direction')
            ->select([
                's.direction',
                'w.wh_code as warehouse_code',
                DB::raw("SUM(CASE WHEN {$exprActual} <= td.target_duration_minutes + 15 THEN 1 ELSE 0 END) AS achieve_count"),
                DB::raw("SUM(CASE WHEN {$exprActual} > td.target_duration_minutes + 15 THEN 1 ELSE 0 END) AS not_achieve_count"),
            ])
            ->get();

        $targetSegmentLabels = [];
        $targetSegmentAchieve = [];
        $targetSegmentNotAchieve = [];
        $targetSegmentDirections = [];

        foreach ($segRows as $r) {
            $direction = (string) ($r->direction ?? '');
            $dirLabel = $direction === 'inbound' ? 'In' : ($direction === 'outbound' ? 'Out' : ucfirst($direction));
            $whCode = (string) ($r->warehouse_code ?? '');
            $label = trim(($whCode ? $whCode : '') . ' ' . $dirLabel);
            if ($label === '') {
                $label = $direction !== '' ? ucfirst($direction) : 'Other';
            }

            $targetSegmentLabels[] = $label;
            $targetSegmentAchieve[] = (int) ($r->achieve_count ?? 0);
            $targetSegmentNotAchieve[] = (int) ($r->not_achieve_count ?? 0);
            $targetSegmentDirections[] = $direction;
        }

        return [
            'labels' => $targetSegmentLabels,
            'achieve' => $targetSegmentAchieve,
            'not_achieve' => $targetSegmentNotAchieve,
            'directions' => $targetSegmentDirections,
        ];
    }

    /**
     * Get trend data for completed slots per day
     */
    public function getTrendData(string $start, string $end): array
    {
        $trendDays = [];
        $trendCounts = [];
        $trendInbound = [];
        $trendOutbound = [];
        $rangeDate = DB::raw('DATE(planned_start)');

        $completedPerDay = DB::table('slots')
            ->where('status', 'completed')
            ->whereBetween($rangeDate, [$start, $end])
            ->groupBy($rangeDate)
            ->orderBy($rangeDate, 'asc')
            ->select([
                DB::raw('DATE(planned_start) as d'),
                DB::raw('COUNT(*) as c'),
            ])
            ->get();

        $map = [];
        foreach ($completedPerDay as $r) {
            $map[(string) $r->d] = (int) ($r->c ?? 0);
        }

        $completedPerDayDir = DB::table('slots')
            ->where('status', 'completed')
            ->whereBetween($rangeDate, [$start, $end])
            ->groupBy($rangeDate)
            ->groupBy('direction')
            ->orderBy($rangeDate, 'asc')
            ->select([
                DB::raw('DATE(planned_start) as d'),
                'direction',
                DB::raw('COUNT(*) as c'),
            ])
            ->get();

        $mapInbound = [];
        $mapOutbound = [];
        foreach ($completedPerDayDir as $r) {
            $d = (string) ($r->d ?? '');
            $dir = (string) ($r->direction ?? '');
            $c = (int) ($r->c ?? 0);
            if ($d === '') {
                continue;
            }
            if ($dir === 'inbound') {
                $mapInbound[$d] = $c;
            } elseif ($dir === 'outbound') {
                $mapOutbound[$d] = $c;
            }
        }

        try {
            $startDt = new \DateTime($start);
            $endDt = new \DateTime($end);
            $endDtInc = (clone $endDt)->modify('+1 day');
            $period = new \DatePeriod($startDt, new \DateInterval('P1D'), $endDtInc);

            foreach ($period as $dt) {
                $d = $dt->format('Y-m-d');
                $trendDays[] = $d;
                $trendCounts[] = (int) ($map[$d] ?? 0);
                $trendInbound[] = (int) ($mapInbound[$d] ?? 0);
                $trendOutbound[] = (int) ($mapOutbound[$d] ?? 0);
            }
        } catch (\Throwable $e) {
            $today = date('Y-m-d');
            $trendDays = [$today];
            $trendCounts = [(int) ($map[$today] ?? 0)];
            $trendInbound = [(int) ($mapInbound[$today] ?? 0)];
            $trendOutbound = [(int) ($mapOutbound[$today] ?? 0)];
        }

        return [
            'days' => $trendDays,
            'counts' => $trendCounts,
            'inbound' => $trendInbound,
            'outbound' => $trendOutbound,
            'completed_total' => !empty($trendCounts) ? array_sum($trendCounts) : 0,
            'avg_7_days' => $this->calculateAvg7Days($trendCounts),
        ];
    }

    /**
     * Calculate 7-day average
     */
    private function calculateAvg7Days(array $trendCounts): float
    {
        if (empty($trendCounts)) {
            return 0.0;
        }

        $last7 = array_slice($trendCounts, -7);
        return count($last7) ? round(array_sum($last7) / count($last7), 1) : 0.0;
    }

    /**
     * Get average lead and processing times
     */
    public function getAverageTimes(string $start, string $end): array
    {
        $avgLeadMinutes = null;
        $avgProcessMinutes = null;

        $rangeDate = DB::raw('DATE(s.planned_start)');

        try {
            $avgLeadMinutes = DB::table('slots as s')
                ->where('s.status', 'completed')
                ->whereNotNull('s.arrival_time')
                ->whereNotNull('s.actual_finish')
                ->whereBetween($rangeDate, [$start, $end])
                ->avg(DB::raw($this->slotService->getTimestampDiffMinutesExpression('s.arrival_time', 's.actual_finish')));

            $avgProcessMinutes = DB::table('slots as s')
                ->where('s.status', 'completed')
                ->whereNotNull('s.actual_start')
                ->whereNotNull('s.actual_finish')
                ->whereBetween($rangeDate, [$start, $end])
                ->avg(DB::raw($this->slotService->getTimestampDiffMinutesExpression('s.actual_start', 's.actual_finish')));
        } catch (\Throwable $e) {
            // Return null values on error
        }

        return [
            'avg_lead_minutes' => $avgLeadMinutes,
            'avg_process_minutes' => $avgProcessMinutes,
        ];
    }

    public function getOnTimeWarehouseStats(string $start, string $end): array
    {
        $rangeDate = DB::raw('DATE(s.planned_start)');

        $rows = DB::table('slots as s')
            ->join('warehouses as w', 's.warehouse_id', '=', 'w.id')
            ->where('s.status', 'completed')
            ->whereBetween($rangeDate, [$start, $end])
            ->groupBy(['s.direction', 'w.wh_code'])
            ->select([
                's.direction',
                'w.wh_code as warehouse_code',
                DB::raw("SUM(CASE WHEN s.is_late = true THEN 0 ELSE 1 END) AS on_time"),
                DB::raw("SUM(CASE WHEN s.is_late = true THEN 1 ELSE 0 END) AS late"),
            ])
            ->get();

        $warehouses = [];
        $data = [];

        foreach ($rows as $r) {
            $code = (string) ($r->warehouse_code ?? '');
            if ($code !== '' && !in_array($code, $warehouses, true)) {
                $warehouses[] = $code;
            }
            $data[] = [
                'direction' => (string) ($r->direction ?? ''),
                'warehouse_code' => $code,
                'on_time' => (int) ($r->on_time ?? 0),
                'late' => (int) ($r->late ?? 0),
            ];
        }

        return [
            'warehouses' => $warehouses,
            'data' => $data,
        ];
    }

    public function getTargetAchievementWarehouseStats(string $start, string $end): array
    {
        $exprActual = $this->slotService->getTimestampDiffMinutesExpression('s.actual_start', 's.actual_finish');
        $rangeDate = DB::raw('DATE(s.planned_start)');

        $rows = DB::table('slots as s')
            ->leftJoin('truck_type_durations as td', 's.truck_type', '=', 'td.truck_type')
            ->join('warehouses as w', 's.warehouse_id', '=', 'w.id')
            ->where('s.status', 'completed')
            ->whereNotNull('td.target_duration_minutes')
            ->whereNotNull('s.actual_finish')
            ->whereNotNull('s.actual_start')
            ->whereBetween($rangeDate, [$start, $end])
            ->groupBy(['s.direction', 'w.wh_code'])
            ->select([
                's.direction',
                'w.wh_code as warehouse_code',
                DB::raw("SUM(CASE WHEN {$exprActual} <= td.target_duration_minutes + 15 THEN 1 ELSE 0 END) AS achieve_count"),
                DB::raw("SUM(CASE WHEN {$exprActual} > td.target_duration_minutes + 15 THEN 1 ELSE 0 END) AS not_achieve_count"),
            ])
            ->get();

        $warehouses = [];
        $data = [];

        foreach ($rows as $r) {
            $code = (string) ($r->warehouse_code ?? '');
            if ($code !== '' && !in_array($code, $warehouses, true)) {
                $warehouses[] = $code;
            }
            $data[] = [
                'direction' => (string) ($r->direction ?? ''),
                'warehouse_code' => $code,
                'achieve' => (int) ($r->achieve_count ?? 0),
                'not_achieve' => (int) ($r->not_achieve_count ?? 0),
            ];
        }

        return [
            'warehouses' => $warehouses,
            'data' => $data,
        ];
    }

    /**
     * Calculate completion rate
     */
    public function calculateCompletionRate(int $totalSlots, int $completedSlots): int
    {
        return $totalSlots > 0 ? (int) round(($completedSlots / $totalSlots) * 100) : 0;
    }

    /**
     * Get activity statistics
     */
    public function getActivityStats(string $date, int $warehouseId = 0, int $userId = 0): array
    {
        $activityQ = DB::table('activity_logs as al')
            ->leftJoin('slots as s', 'al.slot_id', '=', 's.id')
            ->leftJoin('users as u', 'al.created_by', '=', 'u.id')
            ->leftJoin('warehouses as w', 's.warehouse_id', '=', 'w.id')
            ->select([
                'al.id',
                'al.activity_type',
                'al.description',
                'al.created_at',
                's.po_number',
                'u.nik',
                'w.wh_name as warehouse_name',
            ]);

        if ($date !== '') {
            $activityQ->whereDate('al.created_at', $date);
        }
        if ($warehouseId > 0) {
            $activityQ->where('s.warehouse_id', $warehouseId);
        }
        if ($userId > 0) {
            $activityQ->where('al.created_by', $userId);
        }

        return [
            'activities' => $activityQ
                ->orderByDesc('al.created_at')
                ->limit(50)
                ->get(),
            'warehouses' => DB::table('warehouses')->select(['id', 'wh_name as name'])->orderBy('wh_name')->get(),
            'users' => DB::table('users')->select(['id', 'nik'])->orderBy('nik')->get(),
        ];
    }

    /**
     * Get average lead and processing times grouped by truck type
     */
    public function getAverageTimesByTruckType(string $start, string $end): array
    {
        $rangeDate = DB::raw('DATE(s.planned_start)');

        $leadTimeExpr = $this->slotService->getTimestampDiffMinutesExpression('s.arrival_time', 's.actual_finish');
        $processTimeExpr = $this->slotService->getTimestampDiffMinutesExpression('s.actual_start', 's.actual_finish');

        // Normalize truck type names (handle common typos and case sensitivity)
        $normalizedType = "CASE
            WHEN s.truck_type LIKE '%C%ntainer 40ft (L%ose)%' OR s.truck_type LIKE '%C%ntainer 40ft (L%ouse)%' OR s.truck_type LIKE '%K%ntainer 40ft (L%ose)%' THEN 'Container 40ft (Loose)'
            WHEN LOWER(s.truck_type) LIKE '%c%ntainer 40ft (p%aletize)%' OR LOWER(s.truck_type) LIKE '%k%ntainer 40ft (p%aletize)%' THEN 'Container 40ft (Paletize)'
            WHEN s.truck_type LIKE '%C%ntainer 20ft (L%ose)%' OR s.truck_type LIKE '%C%ntainer 20ft (L%ouse)%' OR s.truck_type LIKE '%K%ntainer 20ft (L%ose)%' THEN 'Container 20ft (Loose)'
            WHEN LOWER(s.truck_type) LIKE '%c%ntainer 20ft (p%aletize)%' OR LOWER(s.truck_type) LIKE '%k%ntainer 20ft (p%aletize)%' THEN 'Container 20ft (Paletize)'
            WHEN s.truck_type LIKE '%Wingbox (L%ose)%' OR s.truck_type LIKE '%Wingbox (L%ouse)%' THEN 'Wingbox (Loose)'
            WHEN LOWER(s.truck_type) LIKE '%wingbox (p%aletize)%' THEN 'Wingbox (Paletize)'
            WHEN LOWER(s.truck_type) LIKE '%fuso%' THEN 'Fuso'
            WHEN LOWER(s.truck_type) LIKE '%cdd%' OR LOWER(s.truck_type) LIKE '%cde%' THEN 'CDD/CDE'
            ELSE NULL
        END";

        $orderExpr = "CASE truck_type
            WHEN 'Container 40ft (Loose)' THEN 1
            WHEN 'Container 40ft (Paletize)' THEN 2
            WHEN 'Container 20ft (Loose)' THEN 3
            WHEN 'Container 20ft (Paletize)' THEN 4
            WHEN 'Wingbox (Loose)' THEN 5
            WHEN 'Wingbox (Paletize)' THEN 6
            WHEN 'Fuso' THEN 7
            WHEN 'CDD/CDE' THEN 8
            ELSE 9
        END";

        try {
            // Use subquery to make grouping and ordering cleaner across different DB drivers
            return DB::table(function ($query) use ($normalizedType, $leadTimeExpr, $processTimeExpr, $rangeDate, $start, $end) {
                $query->from('slots as s')
                    ->where('s.status', 'completed')
                    ->whereBetween($rangeDate, [$start, $end])
                    ->select([
                        DB::raw("({$normalizedType}) as truck_type"),
                        DB::raw("({$leadTimeExpr}) as lead_min"),
                        DB::raw("({$processTimeExpr}) as proc_min")
                    ]);
            }, 'sub')
            ->whereNotNull('truck_type')
            ->groupBy('truck_type')
            ->select([
                'truck_type',
                DB::raw('AVG(lead_min) as avg_lead_minutes'),
                DB::raw('AVG(proc_min) as avg_process_minutes'),
                DB::raw('COUNT(*) as total_count')
            ])
            ->orderByRaw($orderExpr)
            ->get()
            ->toArray();
        } catch (\Throwable $e) {
             // \Log::error('Dashboard Truck Stats Error: ' . $e->getMessage());
            return [];
        }
    }
}
