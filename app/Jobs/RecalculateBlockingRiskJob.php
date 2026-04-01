<?php

namespace App\Jobs;

use App\Services\SlotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecalculateBlockingRiskJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300; // 5 minutes max

    public function __construct()
    {
        //
    }

    public function handle(SlotService $slotService): void
    {
        $startTime = microtime(true);
        $updated = 0;
        $errors = 0;

        // Only recalculate for active slots (not completed or cancelled)
        $slots = DB::table('slots')
            ->whereIn('status', ['scheduled', 'arrived', 'waiting', 'in_progress'])
            ->whereRaw("COALESCE(slot_type, 'planned') <> 'unplanned'")
            ->select([
                'id',
                'warehouse_id',
                'planned_gate_id',
                'planned_start',
                'planned_duration',
                'blocking_risk',
            ])
            ->orderBy('id')
            ->get();

        // Collect all updates first, then batch update
        $pendingUpdates = [];

        foreach ($slots as $slot) {
            try {
                $newRisk = $slotService->calculateBlockingRisk(
                    (int) $slot->warehouse_id,
                    $slot->planned_gate_id ? (int) $slot->planned_gate_id : null,
                    (string) ($slot->planned_start ?? ''),
                    (int) ($slot->planned_duration ?? 0),
                    (int) $slot->id
                );

                // Only update if value changed
                if ((int) ($slot->blocking_risk ?? 0) !== $newRisk) {
                    $pendingUpdates[$slot->id] = $newRisk;
                    $updated++;
                }
            } catch (\Throwable $e) {
                $errors++;
                Log::warning('Failed to recalculate blocking risk for slot '.$slot->id.': '.$e->getMessage());
            }
        }

        // Batch update all changed slots in a single query
        if (! empty($pendingUpdates)) {
            try {
                $ids = array_keys($pendingUpdates);
                $cases = [];
                $bindings = [];

                foreach ($pendingUpdates as $id => $risk) {
                    $cases[] = 'WHEN id = ? THEN ?';
                    $bindings[] = $id;
                    $bindings[] = $risk;
                }

                $bindings[] = now();
                $bindings = array_merge($bindings, $ids);

                $casesSql = implode(' ', $cases);
                $placeholders = implode(',', array_fill(0, count($ids), '?'));

                DB::statement(
                    "UPDATE slots SET blocking_risk = CASE {$casesSql} END, blocking_risk_cached_at = ? WHERE id IN ({$placeholders})",
                    $bindings
                );
            } catch (\Throwable $e) {
                Log::warning('Batch update for blocking risk failed: '.$e->getMessage());
            }
        }

        $duration = round(microtime(true) - $startTime, 2);
        Log::info("RecalculateBlockingRiskJob completed: {$slots->count()} slots processed, {$updated} updated, {$errors} errors in {$duration}s");
    }
}
