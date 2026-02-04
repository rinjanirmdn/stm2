<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SlotSeeder extends Seeder
{
    public function run(): void
    {
        $warehouseQuery = DB::table('md_warehouse');
        if (Schema::hasColumn('md_warehouse', 'is_active')) {
            $warehouseQuery->where('is_active', true);
        }
        $warehouses = $warehouseQuery->get();

        $gateQuery = DB::table('md_gates');
        if (Schema::hasColumn('md_gates', 'is_active')) {
            $gateQuery->where('is_active', true);
        }
        $gates = $gateQuery->get()->groupBy('warehouse_id');

        $vendorTable = null;
        if (Schema::hasTable('vendors')) {
            $vendorTable = 'vendors';
        } elseif (Schema::hasTable('business_partner')) {
            $vendorTable = 'business_partner';
        }

        if ($vendorTable) {
            $vendorQuery = DB::table($vendorTable);
            if (Schema::hasColumn($vendorTable, 'is_active')) {
                $vendorQuery->where('is_active', true);
            }
            $vendors = $vendorQuery->get();
        } else {
            $vendors = collect([
                (object) ['id' => null, 'code' => 'V001', 'name' => 'PT. Fast Expedition', 'type' => 'supplier'],
                (object) ['id' => null, 'code' => 'V002', 'name' => 'PT. Indonesia Logistics', 'type' => 'supplier'],
                (object) ['id' => null, 'code' => 'V003', 'name' => 'PT. Advanced Transport', 'type' => 'supplier'],
                (object) ['id' => null, 'code' => 'C001', 'name' => 'PT. Main Customer', 'type' => 'customer'],
            ]);
        }
        $truckTypes = DB::table('md_truck')->get();

        if ($warehouses->isEmpty() || $gates->isEmpty() || $vendors->isEmpty() || $truckTypes->isEmpty()) {
            $this->command->warn('SlotSeeder skipped: required master data missing.');
            return;
        }

        $slotColumns = array_flip(Schema::getColumnListing('slots'));

        $year = (int) now()->format('Y');
        $startDate = Carbon::create($year, 1, 1)->startOfDay();
        $endDate = Carbon::create($year, 2, 1)->endOfMonth()->endOfDay();

        $holidayDates = [
            sprintf('%04d-01-01', $year),
            sprintf('%04d-02-17', $year),
        ];

        if (Schema::hasTable('booking_requests') && Schema::hasColumn('booking_requests', 'converted_slot_id')) {
            $slotIds = DB::table('slots')
                ->whereDate('planned_start', '>=', $startDate->toDateString())
                ->whereDate('planned_start', '<=', $endDate->toDateString())
                ->pluck('id')
                ->all();

            if (!empty($slotIds)) {
                DB::table('booking_requests')
                    ->whereIn('converted_slot_id', $slotIds)
                    ->update(['converted_slot_id' => null]);
            }
        }

        DB::table('slots')
            ->whereDate('planned_start', '>=', $startDate->toDateString())
            ->whereDate('planned_start', '<=', $endDate->toDateString())
            ->delete();

        mt_srand(20260204);
        $slotRows = [];
        $ticketCounter = 1;

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if ($date->dayOfWeek === Carbon::SUNDAY) {
                continue;
            }

            $dateStr = $date->format('Y-m-d');
            if (in_array($dateStr, $holidayDates, true)) {
                continue;
            }

            $dayIndex = (int) $date->format('z');
            $dayRoll = $dayIndex % 14;
            $dayOfMonth = (int) $date->format('j');
            $peakDays = [3, 7, 10, 14, 18, 21, 25, 28];
            $valleyDays = [2, 6, 13, 17, 24, 27];

            if ($date->dayOfWeek === Carbon::SATURDAY) {
                $volumeTier = 'low';
            } elseif (in_array($dayOfMonth, $peakDays, true)) {
                $volumeTier = 'peak';
            } elseif (in_array($dayOfMonth, $valleyDays, true)) {
                $volumeTier = 'valley';
            } elseif ($dayRoll < 4) {
                $volumeTier = 'high';
            } elseif ($dayRoll < 9) {
                $volumeTier = 'medium';
            } else {
                $volumeTier = 'normal';
            }

            foreach ($warehouses as $warehouse) {
                $warehouseGates = $gates->get($warehouse->id, collect());
                if ($warehouseGates->isEmpty()) {
                    continue;
                }

                foreach ($warehouseGates as $gate) {
                    $slotCount = match ($volumeTier) {
                        'peak' => mt_rand(7, 12),
                        'high' => mt_rand(6, 10),
                        'medium' => mt_rand(4, 8),
                        'valley' => mt_rand(2, 4),
                        'low' => mt_rand(1, 3),
                        default => mt_rand(3, 6),
                    };
                    $currentTime = $date->copy()->setTime(7, 0);

                    for ($i = 0; $i < $slotCount; $i++) {
                        $truckType = $truckTypes[mt_rand(0, $truckTypes->count() - 1)];
                        $duration = (int) ($truckType->target_duration_minutes ?? 60);
                        $duration = max(30, min(180, $duration + mt_rand(-15, 30)));

                        if ($currentTime->hour >= 20) {
                            break;
                        }

                        $plannedStart = $currentTime->copy();
                        $plannedEnd = $plannedStart->copy()->addMinutes($duration);

                        if ($plannedEnd->hour > 20 || ($plannedEnd->hour === 20 && $plannedEnd->minute > 0)) {
                            break;
                        }

                        $vendor = $vendors[mt_rand(0, $vendors->count() - 1)];
                        $vendorType = (string) ($vendor->type ?? $vendor->bp_type ?? 'supplier');
                        $direction = $vendorType === 'customer' ? 'outbound' : 'inbound';

                        $statusRoll = mt_rand(1, 100);
                        if ($volumeTier === 'peak' || $volumeTier === 'high') {
                            $status = $statusRoll <= 55 ? 'completed'
                                : ($statusRoll <= 70 ? 'in_progress'
                                    : ($statusRoll <= 85 ? 'waiting'
                                        : ($statusRoll <= 95 ? 'arrived' : 'scheduled')));
                        } elseif ($volumeTier === 'valley' || $volumeTier === 'low') {
                            $status = $statusRoll <= 20 ? 'completed'
                                : ($statusRoll <= 45 ? 'in_progress'
                                    : ($statusRoll <= 65 ? 'waiting'
                                        : ($statusRoll <= 85 ? 'arrived' : 'scheduled')));
                        } else {
                            $status = $statusRoll <= 35 ? 'completed'
                                : ($statusRoll <= 55 ? 'in_progress'
                                    : ($statusRoll <= 75 ? 'waiting'
                                        : ($statusRoll <= 90 ? 'arrived' : 'scheduled')));
                        }

                        $arrivalTime = null;
                        $actualStart = null;
                        $actualFinish = null;

                        if (in_array($status, ['arrived', 'waiting', 'in_progress', 'completed'], true)) {
                            $arrivalTime = $plannedStart->copy()->addMinutes(mt_rand(-10, 15));
                        }

                        if (in_array($status, ['in_progress', 'completed'], true)) {
                            $actualStart = ($arrivalTime ?? $plannedStart)->copy()->addMinutes(mt_rand(5, 25));
                        }

                        if ($status === 'completed') {
                            $actualFinish = ($actualStart ?? $plannedStart)->copy()->addMinutes($duration + mt_rand(-10, 35));
                        }

                        $isLate = false;
                        if ($status === 'completed' && $actualFinish) {
                            $isLate = $actualFinish->greaterThan($plannedEnd);
                        }

                        $ticketNumber = sprintf('T%s%04d', $date->format('ymd'), $ticketCounter++);

                        $slotData = [
                            'ticket_number' => $ticketNumber,
                            'mat_doc' => null,
                            'sj_start_number' => null,
                            'sj_complete_number' => null,
                            'truck_type' => (string) ($truckType->truck_type ?? 'Cargo'),
                            'vehicle_number_snap' => sprintf('B %04d %s', mt_rand(1000, 9999), chr(mt_rand(65, 90))),
                            'driver_name' => 'Driver ' . mt_rand(10, 99),
                            'driver_number' => 'DRV' . mt_rand(1000, 9999),
                            'direction' => $direction,
                            'po_id' => null,
                            'po_number' => sprintf('PO%s%03d', $date->format('ymd'), mt_rand(1, 999)),
                            'warehouse_id' => $warehouse->id,
                            'vendor_id' => $vendor->id ?? null,
                            'vendor_code' => (string) ($vendor->code ?? $vendor->bp_code ?? null),
                            'vendor_name' => (string) ($vendor->name ?? $vendor->bp_name ?? 'Vendor'),
                            'vendor_type' => $vendorType,
                            'planned_gate_id' => $gate->id,
                            'actual_gate_id' => in_array($status, ['arrived', 'waiting', 'in_progress', 'completed'], true) ? $gate->id : null,
                            'planned_start' => $plannedStart->format('Y-m-d H:i:s'),
                            'arrival_time' => $arrivalTime?->format('Y-m-d H:i:s'),
                            'actual_start' => $actualStart?->format('Y-m-d H:i:s'),
                            'actual_finish' => $actualFinish?->format('Y-m-d H:i:s'),
                            'planned_duration' => $duration,
                            'status' => $status,
                            'is_late' => $isLate,
                            'late_reason' => $isLate ? 'Traffic delay' : null,
                            'cancelled_reason' => null,
                            'cancelled_at' => null,
                            'moved_gate' => false,
                            'blocking_risk' => mt_rand(0, 2),
                            'created_by' => null,
                            'slot_type' => 'planned',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        $slotRows[] = array_intersect_key($slotData, $slotColumns);

                        $currentTime->addMinutes($duration + mt_rand(10, 40));
                    }
                }
            }
        }

        foreach (array_chunk($slotRows, 500) as $chunk) {
            DB::table('slots')->insert($chunk);
        }

        $this->command->info('✓ Slots dummy data created (' . count($slotRows) . ' rows).');
    }
}
