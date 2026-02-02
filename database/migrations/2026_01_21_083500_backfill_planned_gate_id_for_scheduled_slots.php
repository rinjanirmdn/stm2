<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('slots') || !Schema::hasTable('gates')) {
            return;
        }

        if (!Schema::hasColumn('slots', 'planned_gate_id')) {
            return;
        }

        $activeStatuses = ['scheduled', 'arrived', 'waiting', 'in_progress'];

        $slots = DB::table('slots')
            ->where('status', 'scheduled')
            ->whereNull('planned_gate_id')
            ->whereNotNull('warehouse_id')
            ->whereNotNull('planned_start')
            ->whereNotNull('planned_duration')
            ->select(['id', 'warehouse_id', 'planned_start', 'planned_duration'])
            ->orderBy('id')
            ->get();

        $driver = DB::getDriverName();

        foreach ($slots as $slot) {
            $slotId = (int) ($slot->id ?? 0);
            $warehouseId = (int) ($slot->warehouse_id ?? 0);
            $plannedStart = (string) ($slot->planned_start ?? '');
            $durationMinutes = (int) ($slot->planned_duration ?? 0);

            if ($slotId <= 0 || $warehouseId <= 0 || $plannedStart === '' || $durationMinutes <= 0) {
                continue;
            }

            try {
                $startDt = new \DateTime($plannedStart);
            } catch (\Throwable $e) {
                continue;
            }

            $finishDt = clone $startDt;
            $finishDt->modify('+' . $durationMinutes . ' minutes');
            $plannedFinish = $finishDt->format('Y-m-d H:i:s');

            $gatesQ = DB::table('md_gates')->where('warehouse_id', $warehouseId);
            if (Schema::hasColumn('md_gates', 'is_active')) {
                $gatesQ->where('is_active', true);
            }
            $gates = $gatesQ->orderBy('gate_number')->select(['id'])->get();

            $bestGateId = null;
            $bestOverlap = null;

            foreach ($gates as $g) {
                $gid = (int) ($g->id ?? 0);
                if ($gid <= 0) {
                    continue;
                }

                $q = DB::table('slots')
                    ->where('id', '<>', $slotId)
                    ->where('planned_gate_id', $gid)
                    ->whereIn('status', $activeStatuses);

                if ($driver === 'pgsql') {
                    $q->whereRaw('planned_start < ? AND (planned_start + (planned_duration * interval \'1 minute\')) > ?', [$plannedFinish, $plannedStart]);
                } else {
                    $q->whereRaw('planned_start < ? AND DATE_ADD(planned_start, INTERVAL planned_duration MINUTE) > ?', [$plannedFinish, $plannedStart]);
                }

                $overlapCount = (int) $q->count();

                if ($bestGateId === null || $overlapCount < (int) $bestOverlap) {
                    $bestGateId = $gid;
                    $bestOverlap = $overlapCount;
                    if ($overlapCount === 0) {
                        break;
                    }
                }
            }

            if ($bestGateId !== null) {
                DB::table('slots')->where('id', $slotId)->update([
                    'planned_gate_id' => $bestGateId,
                ]);
            }
        }
    }

    public function down(): void
    {
        // no-op
    }
};
