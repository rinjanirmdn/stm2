<?php

namespace App\Console\Commands;

use App\Helpers\HolidayHelper;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GenerateFebruaryDashboardDummy extends Command
{
    protected $signature = 'demo:february:generate {--year=2026 : Year to generate February data for} {--from= : Start date (Y-m-d). If set, overrides February start} {--to= : End date (Y-m-d). Inclusive. If set, overrides February end} {--purge=1 : Delete existing slots in selected range first (1/0)} {--min=3 : Min PO per day} {--max=6 : Max PO per day} {--seed=202602 : Random seed for reproducible results}';

    protected $description = 'Generate dashboard dummy slots for February (completed) with realistic timing, gates, waiting and late samples.';

    public function handle(): int
    {
        $year = (int) $this->option('year');
        $fromOpt = trim((string) $this->option('from'));
        $toOpt = trim((string) $this->option('to'));
        $purge = (string) $this->option('purge') === '1';
        $minPerDay = max(1, (int) $this->option('min'));
        $maxPerDay = max($minPerDay, (int) $this->option('max'));
        $seed = (int) $this->option('seed');

        try {
            if ($fromOpt !== '' && $toOpt !== '') {
                $start = Carbon::createFromFormat('Y-m-d', $fromOpt)->startOfDay();
                $endExclusive = Carbon::createFromFormat('Y-m-d', $toOpt)->addDay()->startOfDay();
            } else {
                $start = Carbon::create($year, 2, 1, 0, 0, 0);
                $endExclusive = $start->copy()->addMonth(); // March 1st
            }
        } catch (\Throwable $e) {
            $this->error('Invalid --from/--to format. Use Y-m-d (e.g. 2026-03-02).');

            return self::FAILURE;
        }

        if ($start->gte($endExclusive)) {
            $this->error('Invalid date range: start must be <= end.');

            return self::FAILURE;
        }

        if (! Schema::hasTable('slots')) {
            $this->error('Table slots not found.');

            return self::FAILURE;
        }

        $warehouseQuery = DB::table('md_warehouse');
        if (Schema::hasColumn('md_warehouse', 'is_active')) {
            $warehouseQuery->where('is_active', true);
        }
        $warehouse = $warehouseQuery->orderBy('id')->first();

        if (! $warehouse) {
            $this->error('No active warehouse found in md_warehouse.');

            return self::FAILURE;
        }

        $gateQuery = DB::table('md_gates')->where('warehouse_id', $warehouse->id);
        if (Schema::hasColumn('md_gates', 'is_active')) {
            $gateQuery->where('is_active', true);
        }
        if (Schema::hasColumn('md_gates', 'gate_number')) {
            $gateQuery->whereIn('gate_number', ['A', 'B', 'C']);
        } elseif (Schema::hasColumn('md_gates', 'name')) {
            $gateQuery->whereIn('name', ['Gate A', 'Gate B', 'Gate C']);
        }
        $gates = $gateQuery->orderBy('id')->get();

        if ($gates->isEmpty()) {
            $this->error('No active Gate A/B/C found for selected warehouse.');

            return self::FAILURE;
        }

        $truckTypes = DB::table('md_truck')->get();
        if ($truckTypes->isEmpty()) {
            $this->error('No truck types found in md_truck.');

            return self::FAILURE;
        }

        $vendors = collect([
            (object) ['code' => 'V001', 'name' => 'PT Master Label', 'type' => 'supplier'],
            (object) ['code' => 'V002', 'name' => 'PT. Serunigraf Jaya Sentosa', 'type' => 'supplier'],
            (object) ['code' => 'V003', 'name' => 'Thai Polyethylene Co. LTD', 'type' => 'supplier'],
            (object) ['code' => 'V004', 'name' => 'Wenzhou Beston Import and Export Co', 'type' => 'supplier'],
            (object) ['code' => 'C001', 'name' => 'PT. Ganesha Abaditama', 'type' => 'customer'],
            (object) ['code' => 'C002', 'name' => 'PT. Sentra Asia Gemilang', 'type' => 'customer'],
        ]);

        $slotColumns = array_flip(Schema::getColumnListing('slots'));

        if ($purge) {
            $deleted = DB::table('slots')
                ->where('planned_start', '>=', $start->format('Y-m-d 00:00:00'))
                ->where('planned_start', '<', $endExclusive->format('Y-m-d 00:00:00'))
                ->delete();

            $this->info('Deleted existing February slots: '.$deleted);
        }

        mt_srand($seed);

        $rows = [];
        $ticketCounter = 1;
        $daysGenerated = 0;

        for ($date = $start->copy(); $date->lt($endExclusive); $date->addDay()) {
            if ($date->dayOfWeek === Carbon::SUNDAY) {
                continue;
            }

            if (HolidayHelper::isHoliday($date)) {
                continue;
            }

            $daysGenerated++;

            $dailyCount = mt_rand($minPerDay, $maxPerDay);

            // shift distribution: shift 1 most traffic (max 4), shift 2 remaining (max 2)
            $shift1Count = min(4, $dailyCount);
            $shift2Count = max(0, $dailyCount - $shift1Count);

            // For "peak" days (6 PO) ensure gap between finishing and next arrival is > 2 hours
            $isPeak = $dailyCount >= 6;

            $daySlots = [];

            $makeArrivalTimes = function (int $count, Carbon $base, int $minHour, int $maxHour) {
                $times = [];
                if ($count <= 0) {
                    return $times;
                }

                // spread within window, keep non-overlapping and visually spaced
                $windowMinutes = max(60, ($maxHour - $minHour) * 60);
                $step = (int) floor($windowMinutes / max(1, $count));

                for ($i = 0; $i < $count; $i++) {
                    $minuteOffset = ($i * $step) + mt_rand(0, min(25, max(5, $step - 10)));
                    $t = $base->copy()->setTime($minHour, 0)->addMinutes($minuteOffset);
                    $times[] = $t;
                }

                usort($times, fn ($a, $b) => $a->getTimestamp() <=> $b->getTimestamp());

                return $times;
            };

            // Shift 1: arrivals mostly around 08:00-12:00
            // Shift 2: arrivals around 13:00-17:00 (explicitly includes 13:00)
            $shift1Arrivals = $makeArrivalTimes($shift1Count, $date, 8, 13);
            $shift2Arrivals = $makeArrivalTimes($shift2Count, $date, 13, 18);

            // Ensure we have at least one 13:00 arrival if shift2 exists
            if ($shift2Count > 0) {
                $shift2Arrivals[0] = $date->copy()->setTime(13, mt_rand(0, 20));
                usort($shift2Arrivals, fn ($a, $b) => $a->getTimestamp() <=> $b->getTimestamp());
            }

            $arrivalTimes = array_merge($shift1Arrivals, $shift2Arrivals);
            usort($arrivalTimes, fn ($a, $b) => $a->getTimestamp() <=> $b->getTimestamp());

            // Build slots
            foreach ($arrivalTimes as $idx => $arrivalTime) {
                $truckType = $truckTypes[mt_rand(0, $truckTypes->count() - 1)];
                $targetDuration = (int) ($truckType->target_duration_minutes ?? 60);

                // waiting because of security scanning (counted before entering gate)
                $waitingMinutes = mt_rand(12, 35);
                // processing duration based on truck type
                $processMinutes = max(30, min(210, $targetDuration + mt_rand(-10, 35)));

                $actualStart = $arrivalTime->copy()->addMinutes($waitingMinutes);
                $actualFinish = $actualStart->copy()->addMinutes($processMinutes);

                // Planned start and planned duration: dashboard KPI compares actual_finish vs planned_start + planned_duration.
                // Since waiting (security scanning) is counted before entering gate, planned_duration should include it.
                $plannedStart = $arrivalTime->copy();
                $plannedDuration = $waitingMinutes + $processMinutes + mt_rand(-8, 18);
                $plannedDuration = max(35, min(260, $plannedDuration));
                $plannedEnd = $plannedStart->copy()->addMinutes($plannedDuration);

                $vendor = $vendors[mt_rand(0, $vendors->count() - 1)];
                $vendorType = (string) ($vendor->type ?? 'supplier');
                $direction = $vendorType === 'customer' ? 'outbound' : 'inbound';

                // Some late samples
                $forceLate = mt_rand(1, 100) <= 14;
                if ($forceLate) {
                    $actualFinish = $plannedEnd->copy()->addMinutes(mt_rand(8, 45));
                }

                $isLate = $actualFinish->greaterThan($plannedEnd);

                $gate = $gates[$idx % $gates->count()];

                $blockingRisk = 0;
                if ($isLate && $waitingMinutes >= 25) {
                    $blockingRisk = 2;
                } elseif ($isLate || $waitingMinutes >= 30 || $processMinutes >= 150) {
                    $blockingRisk = 1;
                }

                // Enforce >2 hours separation on peak days (approx) by pushing later arrivals if needed
                if ($isPeak && $idx > 0) {
                    $prevFinish = $daySlots[$idx - 1]['actual_finish'] ?? null;
                    if ($prevFinish) {
                        $prevFinishTs = Carbon::parse($prevFinish);
                        $minArrival = $prevFinishTs->copy()->addHours(2)->addMinutes(mt_rand(5, 25));
                        if ($arrivalTime->lt($minArrival)) {
                            $arrivalTime = $minArrival;
                            $plannedStart = $arrivalTime->copy()->subMinutes(mt_rand(0, 10));
                            $actualStart = $arrivalTime->copy()->addMinutes($waitingMinutes);
                            $actualFinish = $actualStart->copy()->addMinutes($processMinutes);

                            $plannedEnd = $plannedStart->copy()->addMinutes($plannedDuration);
                            if ($forceLate) {
                                $actualFinish = $plannedEnd->copy()->addMinutes(mt_rand(5, 35));
                            }
                            $isLate = $actualFinish->greaterThan($plannedEnd);
                        }
                    }
                }

                $ticketNumber = sprintf('T%s%04d', $date->format('ymd'), $ticketCounter++);
                $poNumber = $date->format('ymd').str_pad((string) mt_rand(1, 999), 3, '0', STR_PAD_LEFT);

                $slot = [
                    'ticket_number' => $ticketNumber,
                    'mat_doc' => null,
                    'sj_start_number' => null,
                    'sj_complete_number' => null,
                    'truck_type' => (string) ($truckType->truck_type ?? 'Cargo'),
                    'vehicle_number_snap' => sprintf('B %04d %s', mt_rand(1000, 9999), chr(mt_rand(65, 90))),
                    'driver_name' => 'Driver '.mt_rand(10, 99),
                    'driver_number' => 'DRV'.mt_rand(1000, 9999),
                    'direction' => $direction,
                    'po_id' => null,
                    'po_number' => $poNumber,
                    'warehouse_id' => $warehouse->id,
                    'vendor_id' => null,
                    'vendor_code' => (string) ($vendor->code ?? null),
                    'vendor_name' => (string) ($vendor->name ?? 'Vendor'),
                    'vendor_type' => $vendorType,
                    'planned_gate_id' => $gate->id,
                    'actual_gate_id' => $gate->id,
                    'planned_start' => $plannedStart->format('Y-m-d H:i:s'),
                    'arrival_time' => $arrivalTime->format('Y-m-d H:i:s'),
                    'actual_start' => $actualStart->format('Y-m-d H:i:s'),
                    'actual_finish' => $actualFinish->format('Y-m-d H:i:s'),
                    'planned_duration' => $plannedDuration,
                    'status' => 'completed',
                    'is_late' => $isLate,
                    'late_reason' => $isLate ? 'Late because of queue and security scanning' : null,
                    'waiting_reason' => 'Security scanning',
                    'cancelled_reason' => null,
                    'cancelled_at' => null,
                    'moved_gate' => false,
                    'blocking_risk' => $blockingRisk,
                    'created_by' => null,
                    'slot_type' => 'planned',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $daySlots[] = array_intersect_key($slot, $slotColumns);
            }

            foreach ($daySlots as $slotRow) {
                $rows[] = $slotRow;
            }
        }

        if (empty($rows)) {
            $this->warn('No rows generated. Check holidays/master data.');

            return self::SUCCESS;
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('slots')->insert($chunk);
        }

        $this->info('✓ Dummy slots generated: '.count($rows).' rows for '.$daysGenerated.' working days (excluding Sundays & holidays).');

        return self::SUCCESS;
    }
}
