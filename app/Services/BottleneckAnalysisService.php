<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class BottleneckAnalysisService
{
    public function __construct(
        private readonly SlotService $slotService
    ) {}

    /**
     * Analyze bottlenecks for the given date range
     */
    public function analyzeBottlenecks(string $start, string $end, int $thresholdMinutes = 30): array
    {
        try {
            $diffExpr = $this->slotService->getTimestampDiffMinutesExpression('s.arrival_time', 's.actual_start');
            $waitExpr = "GREATEST({$diffExpr}, 0)";

            $bottle = DB::table('slots as s')
                ->join('md_warehouse as w', 's.warehouse_id', '=', 'w.id')
                ->leftJoin('md_gates as g', function ($join) {
                    $join->on('g.id', '=', DB::raw('COALESCE(s.actual_gate_id, s.planned_gate_id)'))
                        ->on('s.warehouse_id', '=', 'g.warehouse_id');
                })
                ->whereNotNull('s.arrival_time')
                ->whereNotNull('s.actual_start')
                ->whereIn('s.status', ['in_progress', 'completed'])
                ->whereBetween('s.arrival_time', [$start . ' 00:00:00', $end . ' 23:59:59'])
                ->groupBy(['w.wh_code', 'g.gate_number', 's.direction'])
                ->havingRaw("AVG({$waitExpr}) > ?", [0])
                ->orderByDesc('avg_wait_minutes')
                ->selectRaw('
                    w.wh_code as warehouse_code,
                    g.gate_number,
                    s.direction,
                    COUNT(*) AS slot_count,
                    AVG(' . $waitExpr . ') AS avg_wait_minutes,
                    SUM(' . $waitExpr . ') AS total_wait_minutes,
                    SUM(CASE WHEN ' . $waitExpr . ' > ? THEN 1 ELSE 0 END) AS long_wait_count
                ', [$thresholdMinutes])
                ->limit(20)
                ->get();

            return $this->formatBottleneckData($bottle, $thresholdMinutes);
        } catch (\Throwable $e) {
            return [
                'rows' => [],
                'labels' => [],
                'values' => [],
                'directions' => [],
                'threshold_minutes' => $thresholdMinutes,
            ];
        }
    }

    /**
     * Format bottleneck data for display
     */
    private function formatBottleneckData($bottle, int $thresholdMinutes): array
    {
        $bottleneckRows = [];
        $bottleneckLabels = [];
        $bottleneckValues = [];
        $bottleneckDirections = [];

        foreach ($bottle as $r) {
            $whCode = (string) ($r->warehouse_code ?? '');
            $gateNo = (string) ($r->gate_number ?? '');
            $dir = (string) ($r->direction ?? '');
            $dirShort = $dir === 'inbound' ? 'In' : ($dir === 'outbound' ? 'Out' : ucfirst($dir));

            $labelParts = [];
            $labelParts[] = $gateNo !== '' ? ('Gate ' . $gateNo) : 'Gate ?';
            if ($whCode !== '') {
                $labelParts[] = '(' . $whCode . ')';
            }
            if ($dirShort !== '') {
                $labelParts[] = $dirShort;
            }
            $label = trim(implode(' ', $labelParts));
            if ($label === '') {
                $label = 'Other';
            }

            $bottleneckRows[] = [
                'warehouse_code' => $whCode,
                'gate_number' => $gateNo,
                'direction' => $dir,
                'label' => $label,
                'slot_count' => (int) ($r->slot_count ?? 0),
                'avg_wait_minutes' => (float) ($r->avg_wait_minutes ?? 0),
                'total_wait_minutes' => (int) ($r->total_wait_minutes ?? 0),
                'long_wait_count' => (int) ($r->long_wait_count ?? 0),
            ];

            $bottleneckLabels[] = $label;
            $bottleneckValues[] = (float) ($r->avg_wait_minutes ?? 0);
            $bottleneckDirections[] = $dir;
        }

        return [
            'rows' => $bottleneckRows,
            'labels' => $bottleneckLabels,
            'values' => $bottleneckValues,
            'directions' => $bottleneckDirections,
            'threshold_minutes' => $thresholdMinutes,
        ];
    }

    /**
     * Get gate utilization statistics
     */
    public function getGateUtilization(string $date): array
    {
        $gates = DB::table('md_gates as g')
            ->leftJoin('md_warehouse as w', 'g.warehouse_id', '=', 'w.id')
            ->where('g.is_active', true)
            ->select([
                'g.id',
                'g.gate_number',
                'g.is_active',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code'
            ])
            ->orderBy('w.wh_name')
            ->orderBy('g.gate_number')
            ->get();

        $gateStats = [];

        foreach ($gates as $gate) {
            $gateId = (int) ($gate->id ?? 0);
            $stats = $this->calculateGateStats($gateId, $date);

            $gateStats[] = [
                'gate_id' => $gateId,
                'warehouse_code' => (string) ($gate->warehouse_code ?? ''),
                'warehouse_name' => (string) ($gate->warehouse_name ?? ''),
                'gate_number' => (string) ($gate->gate_number ?? ''),
                'is_active' => (int) ($gate->is_active ?? 0) === 1,
                'total_slots' => $stats['total_slots'],
                'completed_slots' => $stats['completed_slots'],
                'utilization_rate' => $stats['utilization_rate'],
                'avg_wait_time' => $stats['avg_wait_time'],
                'peak_hour' => $stats['peak_hour'],
            ];
        }

        return $gateStats;
    }

    /**
     * Calculate statistics for a specific gate
     */
    private function calculateGateStats(int $gateId, string $date): array
    {
        $stats = [
            'total_slots' => 0,
            'completed_slots' => 0,
            'utilization_rate' => 0,
            'avg_wait_time' => 0,
            'peak_hour' => null,
        ];

        try {
            // Get total and completed slots
            $slotStats = DB::table('slots as s')
                ->join('md_gates as g', function ($join) {
                    $join->on('g.id', '=', DB::raw('COALESCE(s.actual_gate_id, s.planned_gate_id)'))
                        ->on('s.warehouse_id', '=', 'g.warehouse_id');
                })
                ->where('g.id', $gateId)
                ->whereDate('s.planned_start', $date)
                ->selectRaw('
                    COUNT(*) as total_slots,
                    SUM(CASE WHEN s.status = ? THEN 1 ELSE 0 END) as completed_slots
                ', ['completed'])
                ->first();

            if ($slotStats) {
                $stats['total_slots'] = (int) ($slotStats->total_slots ?? 0);
                $stats['completed_slots'] = (int) ($slotStats->completed_slots ?? 0);

                // Calculate utilization rate (8-hour working day = 480 minutes)
                $totalCapacity = 480; // minutes per gate per day
                $usedMinutes = $stats['completed_slots'] * 60; // assume 60 minutes per slot
                $stats['utilization_rate'] = $totalCapacity > 0 ? round(($usedMinutes / $totalCapacity) * 100, 2) : 0;
            }

            // Get average wait time
            $diffExpr = $this->slotService->getTimestampDiffMinutesExpression('s.arrival_time', 's.actual_start');
            $avgWait = DB::table('slots as s')
                ->join('md_gates as g', function ($join) {
                    $join->on('g.id', '=', DB::raw('COALESCE(s.actual_gate_id, s.planned_gate_id)'))
                        ->on('s.warehouse_id', '=', 'g.warehouse_id');
                })
                ->where('g.id', $gateId)
                ->whereDate('s.planned_start', $date)
                ->whereNotNull('s.arrival_time')
                ->whereNotNull('s.actual_start')
                ->selectRaw('AVG(' . $diffExpr . ') as avg_wait')
                ->value('avg_wait');

            $stats['avg_wait_time'] = $avgWait ? round((float) $avgWait, 2) : 0;

            // Get peak hour
            $hourExpr = "EXTRACT(HOUR FROM COALESCE(s.actual_start, s.planned_start))";
            if (DB::getDriverName() !== 'pgsql') {
                $hourExpr = 'HOUR(COALESCE(s.actual_start, s.planned_start))';
            }
            $peakHour = DB::table('slots as s')
                ->join('md_gates as g', function ($join) {
                    $join->on('g.id', '=', DB::raw('COALESCE(s.actual_gate_id, s.planned_gate_id)'))
                        ->on('s.warehouse_id', '=', 'g.warehouse_id');
                })
                ->where('g.id', $gateId)
                ->whereDate('s.planned_start', $date)
                ->selectRaw($hourExpr . ' as hour, COUNT(*) as slot_count')
                ->groupBy('hour')
                ->orderByDesc('slot_count')
                ->limit(1)
                ->first();

            if ($peakHour) {
                $stats['peak_hour'] = (int) ($peakHour->hour ?? 0);
            }

        } catch (\Throwable $e) {
            // Return default stats on error
        }

        return $stats;
    }

    /**
     * Get waiting time distribution
     */
    public function getWaitingTimeDistribution(string $date): array
    {
        $distribution = [
            '0-15' => 0,
            '15-30' => 0,
            '30-60' => 0,
            '60-120' => 0,
            '120+' => 0,
        ];

        try {
            $diffExpr = $this->slotService->getTimestampDiffMinutesExpression('s.arrival_time', 's.actual_start');
            $waitTimes = DB::table('slots as s')
                ->whereDate('s.arrival_time', $date)
                ->whereNotNull('s.arrival_time')
                ->whereNotNull('s.actual_start')
                ->selectRaw($diffExpr . ' as wait_minutes')
                ->pluck('wait_minutes')
                ->filter()
                ->map(fn ($time) => (int) $time);

            foreach ($waitTimes as $minutes) {
                if ($minutes <= 15) {
                    $distribution['0-15']++;
                } elseif ($minutes <= 30) {
                    $distribution['15-30']++;
                } elseif ($minutes <= 60) {
                    $distribution['30-60']++;
                } elseif ($minutes <= 120) {
                    $distribution['60-120']++;
                } else {
                    $distribution['120+']++;
                }
            }
        } catch (\Throwable $e) {
            // Return empty distribution on error
        }

        return $distribution;
    }

    /**
     * Identify critical bottlenecks (above threshold)
     */
    public function getCriticalBottlenecks(string $date, int $thresholdMinutes = 45): array
    {
        try {
            $diffExpr = $this->slotService->getTimestampDiffMinutesExpression('s.arrival_time', 's.actual_start');
            $critical = DB::table('slots as s')
                ->join('md_warehouse as w', 's.warehouse_id', '=', 'w.id')
                ->leftJoin('md_gates as g', function ($join) {
                    $join->on('g.id', '=', DB::raw('COALESCE(s.actual_gate_id, s.planned_gate_id)'))
                        ->on('s.warehouse_id', '=', 'g.warehouse_id');
                })
                ->whereDate('s.arrival_time', $date)
                ->whereNotNull('s.arrival_time')
                ->whereNotNull('s.actual_start')
                ->whereRaw($diffExpr . ' > ?', [$thresholdMinutes])
                ->select([
                    'w.wh_code as warehouse_code',
                    'w.wh_name as warehouse_name',
                    'g.gate_number',
                    's.po_number',
                    's.arrival_time',
                    's.actual_start',
                    's.actual_finish',
                ])
                ->selectRaw($diffExpr . ' as wait_minutes')
                ->orderByDesc('wait_minutes')
                ->limit(10)
                ->get();

            return $critical->map(function ($item) {
                return [
                    'warehouse_code' => $item->warehouse_code,
                    'warehouse_name' => $item->warehouse_name,
                    'gate_number' => $item->gate_number,
                    'po_number' => $item->po_number,
                    'arrival_time' => $item->arrival_time,
                    'actual_start' => $item->actual_start,
                    'wait_minutes' => (int) $item->wait_minutes,
                ];
            })->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Get bottleneck trends over time
     */
    public function getBottleneckTrends(string $start, string $end, int $daysInterval = 7): array
    {
        $trends = [];

        try {
            $startDt = new \DateTime($start);
            $endDt = new \DateTime($end);

            while ($startDt <= $endDt) {
                $periodEnd = (clone $startDt)->modify('+' . ($daysInterval - 1) . ' days');
                if ($periodEnd > $endDt) {
                    $periodEnd = $endDt;
                }

                $periodStart = $startDt->format('Y-m-d');
                $periodEndStr = $periodEnd->format('Y-m-d');

                $avgWait = DB::table('slots as s')
                    ->whereBetween('s.arrival_time', [$periodStart . ' 00:00:00', $periodEndStr . ' 23:59:59'])
                    ->whereNotNull('s.arrival_time')
                    ->whereNotNull('s.actual_start')
                    ->selectRaw('AVG(' . $this->slotService->getTimestampDiffMinutesExpression('s.arrival_time', 's.actual_start') . ') as avg_wait')
                    ->value('avg_wait');

                $trends[] = [
                    'period' => $periodStart . ' - ' . $periodEndStr,
                    'avg_wait_minutes' => $avgWait ? round((float) $avgWait, 2) : 0,
                ];

                $startDt->modify('+' . $daysInterval . ' days');
            }
        } catch (\Throwable $e) {
            // Return empty trends on error
        }

        return $trends;
    }

    /**
     * Get bottleneck summary statistics
     */
    public function getBottleneckSummary(string $date): array
    {
        try {
            $diffExpr = $this->slotService->getTimestampDiffMinutesExpression('s.arrival_time', 's.actual_start');
            $summary = DB::table('slots as s')
                ->whereDate('s.arrival_time', $date)
                ->whereNotNull('s.arrival_time')
                ->whereNotNull('s.actual_start')
                ->selectRaw('
                    COUNT(*) as total_slots,
                    AVG(' . $diffExpr . ') as avg_wait,
                    MIN(' . $diffExpr . ') as min_wait,
                    MAX(' . $diffExpr . ') as max_wait,
                    SUM(CASE WHEN ' . $diffExpr . ' > 30 THEN 1 ELSE 0 END) as long_wait_count
                ')
                ->first();

            return [
                'total_slots' => (int) ($summary->total_slots ?? 0),
                'avg_wait_minutes' => $summary->avg_wait ? round((float) $summary->avg_wait, 2) : 0,
                'min_wait_minutes' => $summary->min_wait ? (int) $summary->min_wait : 0,
                'max_wait_minutes' => $summary->max_wait ? (int) $summary->max_wait : 0,
                'long_wait_count' => (int) ($summary->long_wait_count ?? 0),
                'long_wait_percentage' => $summary->total_slots > 0 ?
                    round(((int) ($summary->long_wait_count ?? 0) / (int) ($summary->total_slots)) * 100, 2) : 0,
            ];
        } catch (\Throwable $e) {
            return [
                'total_slots' => 0,
                'avg_wait_minutes' => 0,
                'min_wait_minutes' => 0,
                'max_wait_minutes' => 0,
                'long_wait_count' => 0,
                'long_wait_percentage' => 0,
            ];
        }
    }
}
