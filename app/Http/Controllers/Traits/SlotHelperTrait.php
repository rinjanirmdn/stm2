<?php

namespace App\Http\Controllers\Traits;

trait SlotHelperTrait
{
    private function buildGateLabel(?string $warehouseCode, ?string $gateNumber): string
    {
        $wh = strtoupper(trim((string) $warehouseCode));
        $gateLabel = $this->slotService->getGateDisplayName($wh, (string) $gateNumber);
        if ($wh !== '' && $gateLabel !== '-') {
            return $wh . ' - ' . $gateLabel;
        }
        return $gateLabel;
    }

    private function minutesDiff(?string $start, ?string $end): ?int
    {
        return $this->timeService->minutesDiff($start, $end);
    }

    private function isLateByPlannedStart(?string $plannedStart, string $actualTime): bool
    {
        return $this->timeService->isLateByPlannedStart($plannedStart, $actualTime);
    }

    private function getPlannedDurationForStart(object $slot): int
    {
        return $this->timeService->getPlannedDurationForStart($slot);
    }

    private function findInProgressConflicts(int $actualGateId, int $excludeSlotId = 0): array
    {
        return $this->conflictService->findInProgressConflicts($actualGateId, $excludeSlotId);
    }

    private function buildConflictLines(array $slotIds): array
    {
        return $this->conflictService->buildConflictMessage($slotIds);
    }

    private function getTruckTypeOptions(): array
    {
        return $this->timeService->getTruckTypeOptions();
    }

    private function loadSlotDetailRow(int $slotId): ?object
    {
        return \Illuminate\Support\Facades\DB::table('slots as s')
            ->join('md_warehouse as w', 's.warehouse_id', '=', 'w.id')
            ->leftJoin('md_users as ru', 's.requested_by', '=', 'ru.id')
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
    }
}
