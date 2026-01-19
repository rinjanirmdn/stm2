<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SlotService;
use Illuminate\Support\Facades\DB;

class FixBlockingRisk extends Command
{
    protected $signature = 'fix:blocking-risk';
    protected $description = 'Fix all blocking risk values';

    public function handle()
    {
        $this->info('Fixing blocking risk values...');

        $slots = DB::table('slots')->get();
        $service = app(SlotService::class);

        foreach ($slots as $slot) {
            $risk = $service->calculateBlockingRisk(
                $slot->warehouse_id,
                $slot->planned_gate_id,
                $slot->planned_start,
                $slot->planned_duration,
                (int) $slot->id
            );

            DB::table('slots')->where('id', $slot->id)->update(['blocking_risk' => $risk]);

            $riskLevel = $risk >= 2 ? 'High' : ($risk === 1 ? 'Medium' : 'Low');
            $this->info("Slot {$slot->ticket_number}: {$riskLevel} ({$risk})");
        }

        $this->info('Done!');
        return 0;
    }
}
