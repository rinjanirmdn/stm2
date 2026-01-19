<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class GateStatusService
{
    public function __construct(
        private readonly SlotService $slotService
    ) {}

    /**
     * Get gate cards with current and next slot information
     */
    public function getGateCards(string $today): array
    {
        $gates = $this->getActiveGates();
        $gateCards = [];

        foreach ($gates as $gate) {
            $gateId = (int) ($gate->id ?? 0);
            $gateCard = $this->buildGateCard($gate, $gateId, $today);
            $gateCards[] = $gateCard;
        }

        return $gateCards;
    }

    /**
     * Get current slot for a gate
     */
    public function getCurrentSlot(int $gateId): ?object
    {
        $laneGroup = $this->slotService->getGateLaneGroup($gateId);
        $laneGateIds = $laneGroup ? $this->slotService->getGateIdsByLaneGroup($laneGroup) : [$gateId];

        if (empty($laneGateIds)) {
            return null;
        }

        $today = date('Y-m-d');

        return DB::table('slots as s')
            ->where('s.status', 'in_progress')
            ->whereIn('s.actual_gate_id', $laneGateIds)
            ->whereDate('s.actual_start', $today)
            ->orderByDesc('s.actual_start')
            ->select(['s.id', 's.po_id', 's.actual_start'])
            ->first();
    }

    /**
     * Get next scheduled slot for a gate
     */
    public function getNextSlot(int $gateId): ?object
    {
        $laneGroup = $this->slotService->getGateLaneGroup($gateId);
        $laneGateIds = $laneGroup ? $this->slotService->getGateIdsByLaneGroup($laneGroup) : [$gateId];

        if (empty($laneGateIds)) {
            return null;
        }

        $today = date('Y-m-d');

        return DB::table('slots as s')
            ->whereIn('s.planned_gate_id', $laneGateIds)
            ->whereIn('s.status', ['scheduled', 'arrived', 'waiting'])
            ->whereDate('s.planned_start', $today)
            ->orderBy('s.planned_start', 'asc')
            ->select(['s.id', 's.po_id', 's.planned_start', 's.status'])
            ->first();
    }

    /**
     * Get gate status label
     */
    public function getGateStatusLabel(?object $currentSlot, ?object $nextSlot): string
    {
        if ($currentSlot) {
            return 'In progress';
        } elseif ($nextSlot) {
            $status = (string) ($nextSlot->status ?? '');
            return in_array($status, ['arrived', 'waiting'], true) ? 'Queue' : 'Upcoming';
        }

        return 'Idle';
    }

    /**
     * Get gate status CSS class
     */
    public function getGateStatusClass(?object $currentSlot, ?object $nextSlot): string
    {
        if ($currentSlot) {
            return 'st-status-processing';
        } elseif ($nextSlot) {
            $status = (string) ($nextSlot->status ?? '');
            return in_array($status, ['arrived', 'waiting'], true) ? 'st-status-early' : 'st-status-idle';
        }

        return 'st-status-idle';
    }

    /**
     * Format gate display text for current slot
     */
    public function formatCurrentSlotText(?object $currentSlot): string
    {
        if (!$currentSlot) {
            return '-';
        }

        $truckNumber = (string) (DB::table('po')->where('id', (int) $currentSlot->po_id)->value('po_number') ?? '');
        $startTime = date('H:i', strtotime((string) $currentSlot->actual_start));

        return $truckNumber !== '' ? ('PO ' . $truckNumber . ' @ ' . $startTime) : '-';
    }

    /**
     * Format gate display text for next slot
     */
    public function formatNextSlotText(?object $nextSlot): string
    {
        if (!$nextSlot) {
            return '-';
        }

        $truckNumber = (string) (DB::table('po')->where('id', (int) $nextSlot->po_id)->value('po_number') ?? '');
        $startTime = date('H:i', strtotime((string) $nextSlot->planned_start));

        return $truckNumber !== '' ? ('PO ' . $truckNumber . ' @ ' . $startTime) : '-';
    }

    /**
     * Get active gates from database
     */
    private function getActiveGates(): \Illuminate\Support\Collection
    {
        return DB::table('gates as g')
            ->join('warehouses as w', 'g.warehouse_id', '=', 'w.id')
            ->orderBy('w.wh_name')
            ->orderBy('g.gate_number')
            ->select(['g.id', 'g.gate_number', 'g.is_backup', 'g.is_active', 'w.wh_name as warehouse_name', 'w.wh_code as warehouse_code'])
            ->get();
    }

    /**
     * Build gate card data
     */
    private function buildGateCard(object $gate, int $gateId, string $today): array
    {
        $currentSlot = $this->getCurrentSlot($gateId);
        $nextSlot = $this->getNextSlot($gateId);

        $warehouseCode = (string) ($gate->warehouse_code ?? '');
        $gateNumber = (string) ($gate->gate_number ?? '');

        $gateDisplay = $this->slotService->getGateDisplayName($warehouseCode, $gateNumber);
        $gateTitle = $gateDisplay === '-' ? (string) ($gate->warehouse_name ?? '-') : ((string) ($gate->warehouse_name ?? '-') . ' - ' . $gateDisplay);

        return [
            'gate_id' => $gateId,
            'warehouse_code' => $warehouseCode,
            'warehouse_name' => (string) ($gate->warehouse_name ?? ''),
            'gate_number' => $gateNumber,
            'title' => $gateTitle,
            'is_backup' => (int) ($gate->is_backup ?? 0) === 1,
            'is_active' => (int) ($gate->is_active ?? 0) === 1,
            'status_label' => $this->getGateStatusLabel($currentSlot, $nextSlot),
            'status_class' => $this->getGateStatusClass($currentSlot, $nextSlot),
            'current_text' => $this->formatCurrentSlotText($currentSlot),
            'next_text' => $this->formatNextSlotText($nextSlot),
        ];
    }

    /**
     * Get gate availability for a specific time
     */
    public function getGateAvailability(string $dateTime): array
    {
        $targetDateTime = new \DateTime($dateTime);
        $targetDate = $targetDateTime->format('Y-m-d');
        $targetHour = (int) $targetDateTime->format('H');

        $gates = $this->getActiveGates();
        $availability = [];

        foreach ($gates as $gate) {
            $gateId = (int) ($gate->id ?? 0);
            $isAvailable = $this->isGateAvailableAtTime($gateId, $targetDate, $targetHour);

            $availability[] = [
                'gate_id' => $gateId,
                'warehouse_code' => (string) ($gate->warehouse_code ?? ''),
                'gate_number' => (string) ($gate->gate_number ?? ''),
                'is_available' => $isAvailable,
                'status' => $isAvailable ? 'Available' : 'Occupied',
            ];
        }

        return $availability;
    }

    /**
     * Check if gate is available at specific time
     */
    private function isGateAvailableAtTime(int $gateId, string $date, int $hour): bool
    {
        $laneGroup = $this->slotService->getGateLaneGroup($gateId);
        $laneGateIds = $laneGroup ? $this->slotService->getGateIdsByLaneGroup($laneGroup) : [$gateId];

        if (empty($laneGateIds)) {
            return true;
        }

        $hourExpr = "EXTRACT(HOUR FROM s.planned_start)";
        $driver = DB::getDriverName();
        if ($driver !== 'pgsql') {
            $hourExpr = 'HOUR(s.planned_start)';
        }
        $endExpr = $this->slotService->getDateAddExpression('s.planned_start', 'COALESCE(s.planned_duration, 60)');
        $endHourExpr = "EXTRACT(HOUR FROM {$endExpr})";
        if ($driver !== 'pgsql') {
            $endHourExpr = "HOUR({$endExpr})";
        }

        // Check for overlapping slots at the target time
        $conflictCount = DB::table('slots as s')
            ->whereIn('s.planned_gate_id', $laneGateIds)
            ->whereDate('s.planned_start', $date)
            ->where('s.status', '!=', 'cancelled')
            ->whereRaw("{$hourExpr} <= ?", [$hour])
            ->whereRaw("{$endHourExpr} > ?", [$hour])
            ->count();

        return $conflictCount === 0;
    }

    /**
     * Get gate performance metrics
     */
    public function getGatePerformance(string $date): array
    {
        $gates = $this->getActiveGates();
        $performance = [];

        foreach ($gates as $gate) {
            $gateId = (int) ($gate->id ?? 0);
            $metrics = $this->calculateGatePerformance($gateId, $date);

            $performance[] = [
                'gate_id' => $gateId,
                'warehouse_code' => (string) ($gate->warehouse_code ?? ''),
                'gate_number' => (string) ($gate->gate_number ?? ''),
                'total_slots' => $metrics['total_slots'],
                'completed_slots' => $metrics['completed_slots'],
                'completion_rate' => $metrics['completion_rate'],
                'avg_duration' => $metrics['avg_duration'],
                'on_time_rate' => $metrics['on_time_rate'],
            ];
        }

        return $performance;
    }

    /**
     * Calculate performance metrics for a gate
     */
    private function calculateGatePerformance(int $gateId, string $date): array
    {
        $metrics = [
            'total_slots' => 0,
            'completed_slots' => 0,
            'completion_rate' => 0,
            'avg_duration' => 0,
            'on_time_rate' => 0,
        ];

        try {
            $laneGroup = $this->slotService->getGateLaneGroup($gateId);
            $laneGateIds = $laneGroup ? $this->slotService->getGateIdsByLaneGroup($laneGroup) : [$gateId];

            if (!empty($laneGateIds)) {
                $stats = DB::table('slots as s')
                    ->join('gates as g', function ($join) {
                        $join->on(DB::raw('COALESCE(s.actual_gate_id, s.planned_gate_id)'), '=', 'g.id')
                            ->on('s.warehouse_id', '=', 'g.warehouse_id');
                    })
                    ->whereIn('g.id', $laneGateIds)
                    ->whereDate(DB::raw('COALESCE(s.actual_start, s.planned_start)'), $date)
                    ->select([
                        DB::raw('COUNT(*) as total_slots'),
                        DB::raw("SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) as completed_slots"),
                        DB::raw('AVG(' . $this->slotService->getTimestampDiffMinutesExpression('s.actual_start', 's.actual_finish') . ') as avg_duration'),
                        DB::raw("SUM(CASE WHEN s.status = 'completed' AND (s.is_late = false OR s.is_late IS NULL) THEN 1 ELSE 0 END) as on_time_count"),
                    ])
                    ->first();

                if ($stats) {
                    $metrics['total_slots'] = (int) ($stats->total_slots ?? 0);
                    $metrics['completed_slots'] = (int) ($stats->completed_slots ?? 0);
                    $metrics['completion_rate'] = $metrics['total_slots'] > 0 ?
                        round(($metrics['completed_slots'] / $metrics['total_slots']) * 100, 2) : 0;
                    $metrics['avg_duration'] = $stats->avg_duration ? round((float) $stats->avg_duration, 2) : 0;
                    $metrics['on_time_rate'] = $metrics['completed_slots'] > 0 ?
                        round(((int) ($stats->on_time_count ?? 0) / $metrics['completed_slots']) * 100, 2) : 0;
                }
            }
        } catch (\Throwable $e) {
            // Return default metrics on error
        }

        return $metrics;
    }

    /**
     * Get gate summary for dashboard
     */
    public function getGateSummary(string $today): array
    {
        $gates = $this->getActiveGates();
        $summary = [
            'total_gates' => $gates->count(),
            'active_gates' => $gates->where('is_active', true)->count(),
            'busy_gates' => 0,
            'available_gates' => 0,
            'upcoming_gates' => 0,
        ];

        foreach ($gates as $gate) {
            $gateId = (int) ($gate->id ?? 0);
            $currentSlot = $this->getCurrentSlot($gateId);
            $nextSlot = $this->getNextSlot($gateId);

            if ($currentSlot) {
                $summary['busy_gates']++;
            } elseif ($nextSlot) {
                $status = (string) ($nextSlot->status ?? '');
                if (in_array($status, ['arrived', 'waiting'], true)) {
                    $summary['busy_gates']++;
                } else {
                    $summary['upcoming_gates']++;
                }
            } else {
                $summary['available_gates']++;
            }
        }

        return $summary;
    }
}
