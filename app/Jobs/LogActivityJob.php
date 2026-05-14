<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LogActivityJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 30;

    /**
     * @param  array<string, mixed>  $logData  Pre-built log data to insert
     * @param  int|null  $slotId  Optional slot ID to resolve sj_no/po_number
     */
    public function __construct(
        private readonly array $logData,
        private readonly ?int $slotId = null,
    ) {}

    public function handle(): void
    {
        try {
            $insert = $this->logData;

            // Resolve sj_no/po_number from slot if needed
            if ($this->slotId !== null) {
                $columns = Schema::getColumnListing('activity_logs');
                $has = static fn (string $col): bool => in_array($col, $columns, true);

                if (($has('sj_no') || $has('po_number')) && ($insert['sj_no'] ?? null) === null && ($insert['po_number'] ?? null) === null) {
                    try {
                        $slotRow = DB::table('slots')->where('id_slots', $this->slotId)->select(['sj_no', 'po_number'])->first();
                        if ($slotRow) {
                            if ($has('sj_no') && ($insert['sj_no'] ?? null) === null) {
                                $insert['sj_no'] = $slotRow->sj_no ?? null;
                            }
                            if ($has('po_number') && ($insert['po_number'] ?? null) === null) {
                                $insert['po_number'] = $slotRow->po_number ?? null;
                            }
                        }
                    } catch (\Throwable $e) {
                        // no-op
                    }
                }
            }

            if (! empty($insert) && isset($insert['description'])) {
                DB::table('activity_logs')->insert($insert);
            }
        } catch (\Throwable $e) {
            Log::warning('LogActivityJob failed: '.$e->getMessage());
        }
    }
}
