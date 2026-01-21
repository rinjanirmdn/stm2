<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GateStreamingService
{
    public function __construct(
        private readonly SlotService $slotService
    ) {}

    /**
     * Get current gate statuses for streaming
     */
    public function getGateStatuses(): \Illuminate\Support\Collection
    {
        $endExpr = $this->slotService->getDateAddExpression('s.planned_start', 'COALESCE(s.planned_duration, 0)');

        return DB::table('gates as g')
            ->leftJoin('warehouses as w', 'g.warehouse_id', '=', 'w.id')
            ->leftJoin('slots as s', function ($join) {
                $join->on('g.id', '=', 's.planned_gate_id')
                     ->whereIn('s.status', ['scheduled', 'arrived', 'waiting', 'in_progress'])
                     ->where(function($q) {
                         $q->whereNull('s.slot_type')->orWhere('s.slot_type', 'planned');
                     })
                     ->where('s.planned_start', '<=', now())
                     ->whereRaw($this->slotService->getDateAddExpression('s.planned_start', 'COALESCE(s.planned_duration, 0)') . ' >= ?', [now()]);
            })
            ->leftJoin('po as t', 's.po_id', '=', 't.id')
            ->select([
                'g.id',
                'g.gate_number',
                'g.is_active',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
                't.po_number',
                's.status as slot_status',
                's.planned_start',
                's.planned_duration',
                DB::raw($endExpr . ' as planned_finish'),
                's.actual_start',
                's.actual_finish',
                DB::raw("CASE
                    WHEN s.status = 'in_progress' THEN 'busy'
                    WHEN s.status = 'arrived' OR s.status = 'waiting' THEN 'occupied'
                    WHEN COUNT(s.id) > 0 THEN 'reserved'
                    ELSE 'available'
                END as gate_status")
            ])
            ->groupBy('g.id', 'g.gate_number', 'g.is_active', 'w.wh_name', 'w.wh_code',
                     't.po_number', 's.status', 's.planned_start', 's.planned_duration',
                     's.actual_start', 's.actual_finish')
            ->get();
    }

    /**
     * Transform gate data for streaming response
     */
    public function transformGateData($gates): array
    {
        $data = [];

        foreach ($gates as $gate) {
            $gateData = [
                'id' => $gate->id,
                'gate_number' => $gate->gate_number,
                'warehouse' => $gate->wh_code,
                'status' => $gate->gate_status,
                'current_slot' => $gate->po_number ? [
                    'po_number' => $gate->po_number,
                    'status' => $gate->slot_status,
                    'planned_start' => $gate->planned_start,
                    'planned_finish' => $gate->planned_finish,
                    'actual_start' => $gate->actual_start,
                    'actual_finish' => $gate->actual_finish,
                ] : null,
                'timestamp' => now()->timestamp
            ];

            $data[] = $gateData;
        }

        return $data;
    }

    /**
     * Stream gate status data using Server-Sent Events
     */
    public function streamGateStatuses(callable $callback): void
    {
        $startTime = time();
        $maxDuration = 300; // 5 minutes max connection time
        $lastUpdate = 0;

        while (true) {
            // Check max duration
            if (time() - $startTime > $maxDuration) {
                echo "event: close\ndata: {\"type\": \"close\", \"reason\": \"timeout\"}\n\n";
                ob_flush();
                flush();
                break;
            }

            // Get current gate statuses
            $gates = $this->getGateStatuses();
            $data = $this->transformGateData($gates);

            // Send data if there are updates or every 30 seconds as heartbeat
            if (count($data) > 0 || (time() - $lastUpdate) > 30) {
                $callback([
                    'type' => 'gate_status',
                    'data' => $data,
                    'timestamp' => now()->timestamp
                ]);
                $lastUpdate = time();
            }

            // Sleep for 2 seconds before next check
            sleep(2);
        }
    }

    /**
     * Get gate status for API endpoint (non-streaming)
     */
    public function getCurrentGateStatuses(): array
    {
        $gates = $this->getGateStatuses();
        return $this->transformGateData($gates);
    }

    /**
     * Get gate status by gate ID
     */
    public function getGateStatusById(int $gateId): ?array
    {
        $plannedFinishExpr = $this->slotService->getDateAddExpression('s.planned_start', 'COALESCE(s.planned_duration, 60)');

        $gate = DB::table('gates as g')
            ->leftJoin('warehouses as w', 'g.warehouse_id', '=', 'w.id')
            ->leftJoin('slots as s', function ($join) {
                $join->on('g.id', '=', 's.planned_gate_id')
                     ->whereIn('s.status', ['scheduled', 'arrived', 'waiting', 'in_progress'])
                     ->where(function($q) {
                         $q->whereNull('s.slot_type')->orWhere('s.slot_type', 'planned');
                     })
                     ->where('s.planned_start', '<=', now())
                     ->whereRaw($this->slotService->getDateAddExpression('s.planned_start', 'COALESCE(s.planned_duration, 0)') . ' >= ?', [now()]);
            })
            ->leftJoin('po as t', 's.po_id', '=', 't.id')
            ->where('g.id', $gateId)
            ->select([
                'g.id',
                'g.gate_number',
                'g.is_active',
                'w.wh_name as warehouse_name',
                'w.wh_code as warehouse_code',
                't.po_number',
                's.status as slot_status',
                's.planned_start',
                's.planned_duration',
                DB::raw($plannedFinishExpr . ' as planned_finish'),
                's.actual_start',
                's.actual_finish',
                DB::raw("CASE
                    WHEN s.status = 'in_progress' THEN 'active'
                    WHEN s.status IN ('scheduled', 'arrived', 'waiting') THEN 'upcoming'
                    ELSE 'available'
                END as gate_status")
            ])
            ->groupBy('g.id', 'g.gate_number', 'g.is_active', 'w.wh_name', 'w.wh_code',
                     't.po_number', 's.status', 's.planned_start', 's.planned_duration',
                     's.actual_start', 's.actual_finish')
            ->first();

        if (!$gate) {
            return null;
        }

        return $this->transformGateData(collect([$gate]))[0] ?? null;
    }

    /**
     * Get gate statistics for dashboard
     */
    public function getGateStatistics(): array
    {
        $gates = $this->getGateStatuses();

        $stats = [
            'total_gates' => $gates->count(),
            'active_gates' => $gates->where('is_active', true)->count(),
            'available_gates' => 0,
            'busy_gates' => 0,
            'occupied_gates' => 0,
            'reserved_gates' => 0,
        ];

        foreach ($gates as $gate) {
            switch ($gate->gate_status) {
                case 'available':
                    $stats['available_gates']++;
                    break;
                case 'busy':
                    $stats['busy_gates']++;
                    break;
                case 'occupied':
                    $stats['occupied_gates']++;
                    break;
                case 'reserved':
                    $stats['reserved_gates']++;
                    break;
            }
        }

        return $stats;
    }

    /**
     * Check if gate has conflicts
     */
    public function hasGateConflicts(int $gateId, string $startTime, string $endTime, int $excludeSlotId = 0): bool
    {
        $plannedFinishExpr = $this->slotService->getDateAddExpression('s.planned_start', 'COALESCE(s.planned_duration, 60)');

        $conflicts = DB::table('slots as s')
            ->where('s.planned_gate_id', $gateId)
            ->where('s.id', '<>', $excludeSlotId)
            ->where('s.status', '!=', 'cancelled')
            ->where(function($query) use ($startTime, $endTime) {
                $query->where(function($sub) use ($startTime, $endTime) {
                    // Slot starts during another slot's time
                    $sub->where('s.planned_start', '<=', $startTime)
                        ->whereRaw($this->slotService->getDateAddExpression('s.planned_start', 'COALESCE(s.planned_duration, 60)') . ' >= ?', [$startTime]);
                })->orWhere(function($sub) use ($startTime, $endTime) {
                    // Slot ends during another slot's time
                    $sub->where('s.planned_start', '<', $endTime)
                        ->where('s.planned_start', '>=', $startTime);
                });
            })
            ->count();

        return $conflicts > 0;
    }

    /**
     * Get gate utilization rate
     */
    public function getGateUtilizationRate(int $gateId, string $date): float
    {
        $totalMinutes = 8 * 60; // 8 hours in minutes
        $usedMinutes = DB::table('slots')
            ->where('planned_gate_id', $gateId)
            ->whereDate('planned_start', $date)
            ->where('status', '!=', 'cancelled')
            ->sum('planned_duration');

        if ($usedMinutes === null) {
            return 0.0;
        }

        return min(100.0, round(($usedMinutes / $totalMinutes) * 100, 2));
    }

    /**
     * Get gate performance metrics
     */
    public function getGatePerformanceMetrics(int $gateId, string $date): array
    {
        $metrics = [
            'total_slots' => 0,
            'completed_slots' => 0,
            'avg_duration' => 0,
            'on_time_percentage' => 0,
        ];

        $slots = DB::table('slots')
            ->where('planned_gate_id', $gateId)
            ->whereDate('planned_start', $date)
            ->get();

        $metrics['total_slots'] = $slots->count();
        $metrics['completed_slots'] = $slots->where('status', 'completed')->count();

        if ($metrics['completed_slots'] > 0) {
            $completedSlots = $slots->where('status', 'completed');
            $totalDuration = $completedSlots->sum('planned_duration');
            $metrics['avg_duration'] = $totalDuration > 0 ? round($totalDuration / $metrics['completed_slots'], 2) : 0;

            $onTimeCount = $completedSlots->where('is_late', false)->count();
            $metrics['on_time_percentage'] = round(($onTimeCount / $metrics['completed_slots']) * 100, 2);
        }

        return $metrics;
    }
}
