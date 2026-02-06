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
        if (Schema::hasColumn('md_gates', 'gate_number')) {
            $gateQuery->whereIn('gate_number', ['A', 'B', 'C']);
        } elseif (Schema::hasColumn('md_gates', 'name')) {
            $gateQuery->whereIn('name', ['Gate A', 'Gate B', 'Gate C']);
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
                (object) ['id' => null, 'code' => 'V001', 'name' => 'PT Master Label', 'type' => 'supplier'],
                (object) ['id' => null, 'code' => 'V002', 'name' => 'PT. Serunigraf Jaya Sentosa', 'type' => 'supplier'],
                (object) ['id' => null, 'code' => 'V003', 'name' => 'Thai Polyethylene Co. LTD', 'type' => 'supplier'],
                (object) ['id' => null, 'code' => 'V004', 'name' => 'Wenzhou Beston Import and Export Co', 'type' => 'supplier'],
                (object) ['id' => null, 'code' => 'C001', 'name' => 'PT. Ganesha Abaditama', 'type' => 'customer'],
                (object) ['id' => null, 'code' => 'C002', 'name' => 'PT. Sentra Asia gemilang', 'type' => 'customer'],
                (object) ['id' => null, 'code' => 'C003', 'name' => 'PT. Buana Intiprima Usaha', 'type' => 'customer'],
                (object) ['id' => null, 'code' => 'C004', 'name' => 'PT. Itama Ranoraya Tbk', 'type' => 'customer'],
                (object) ['id' => null, 'code' => 'C005', 'name' => 'PT. TOPLA FONDAMEN SUKSES', 'type' => 'customer'],
                (object) ['id' => null, 'code' => 'C006', 'name' => 'PT. Itama Ranoraya Tbk', 'type' => 'customer'],
            ]);
        }
        $truckTypes = DB::table('md_truck')->get();

        if ($warehouses->isEmpty() || $gates->isEmpty() || $vendors->isEmpty() || $truckTypes->isEmpty()) {
            $this->command->warn('SlotSeeder skipped: required master data missing.');
            return;
        }

        $slotColumns = array_flip(Schema::getColumnListing('slots'));

        $startDate = now()->subDays(29)->startOfDay();
        $endDate = now()->endOfDay();

        $holidayDates = [];

        if (Schema::hasTable('booking_requests') && Schema::hasColumn('booking_requests', 'converted_slot_id')) {
            DB::table('booking_requests')
                ->whereNotNull('converted_slot_id')
                ->update(['converted_slot_id' => null]);
        }

        DB::table('slots')->delete();

        mt_srand(20260204);
        $slotRows = [];
        $ticketCounter = 1;

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if ($date->dayOfWeek === Carbon::SUNDAY) {
                continue;
            }

            $isToday = $date->isSameDay(Carbon::today());

            $dateStr = $date->format('Y-m-d');
            if (in_array($dateStr, $holidayDates, true)) {
                continue;
            }

            $dayIndex = (int) $date->format('z');
            $dayRoll = $dayIndex % 14;
            $dayOfMonth = (int) $date->format('j');
            $progress = 0;
            try {
                $totalDays = max(1, $startDate->diffInDays($endDate));
                $progress = min(1, max(0, $startDate->diffInDays($date) / $totalDays));
            } catch (\Throwable $e) {
                $progress = 0;
            }
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
                    $baseCount = $progress < 0.35 ? 1 : ($progress < 0.75 ? 2 : 4);
                    $slotCount = match ($volumeTier) {
                        'peak' => $baseCount + 2,
                        'high' => $baseCount + 1,
                        'medium' => $baseCount,
                        'valley' => 1,
                        'low' => 1,
                        default => $baseCount,
                    };
                    if ($isToday) {
                        $slotCount = max($slotCount, 6);
                    }
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
                        $statusRoll = max(1, $statusRoll - (int) round($progress * 55));
                        if ($volumeTier === 'peak' || $volumeTier === 'high') {
                            $completedCap = (int) round(35 + ($progress * 70));
                            $inProgressCap = min(89, $completedCap + 6);
                            $waitingCap = min(92, $inProgressCap + 2);
                            $arrivedCap = min(94, $waitingCap + 2);
                            $status = $statusRoll <= $completedCap ? 'completed'
                                : ($statusRoll <= $inProgressCap ? 'in_progress'
                                    : ($statusRoll <= $waitingCap ? 'waiting'
                                        : ($statusRoll <= $arrivedCap ? 'arrived' : 'scheduled')));
                        } elseif ($volumeTier === 'valley' || $volumeTier === 'low') {
                            $completedCap = (int) round(25 + ($progress * 65));
                            $inProgressCap = min(85, $completedCap + 6);
                            $waitingCap = min(89, $inProgressCap + 2);
                            $arrivedCap = min(92, $waitingCap + 2);
                            $status = $statusRoll <= $completedCap ? 'completed'
                                : ($statusRoll <= $inProgressCap ? 'in_progress'
                                    : ($statusRoll <= $waitingCap ? 'waiting'
                                        : ($statusRoll <= $arrivedCap ? 'arrived' : 'scheduled')));
                        } else {
                            $completedCap = (int) round(30 + ($progress * 75));
                            $inProgressCap = min(87, $completedCap + 6);
                            $waitingCap = min(90, $inProgressCap + 2);
                            $arrivedCap = min(93, $waitingCap + 2);
                            $status = $statusRoll <= $completedCap ? 'completed'
                                : ($statusRoll <= $inProgressCap ? 'in_progress'
                                    : ($statusRoll <= $waitingCap ? 'waiting'
                                        : ($statusRoll <= $arrivedCap ? 'arrived' : 'scheduled')));
                        }

                        if ($isToday) {
                            $todayCycle = ['scheduled', 'waiting', 'in_progress', 'cancelled', 'completed', 'scheduled', 'waiting'];
                            $status = $todayCycle[($i + (int) ($gate->id ?? 0)) % count($todayCycle)];
                        }

                        if ($status === 'arrived') {
                            $status = 'waiting';
                        }

                        $arrivalTime = null;
                        $actualStart = null;
                        $actualFinish = null;

                        if (in_array($status, ['arrived', 'waiting', 'in_progress', 'completed'], true)) {
                            $arrivalTime = $plannedStart->copy()->addMinutes(mt_rand(-6, 4));
                        }

                        if (in_array($status, ['in_progress', 'completed'], true)) {
                            $actualStart = ($arrivalTime ?? $plannedStart)->copy()->addMinutes(mt_rand(1, 6));
                        }

                        if ($status === 'completed') {
                            $isLateSeed = mt_rand(1, 100) <= 8;
                            $finishSlack = $isLateSeed ? mt_rand(5, 18) : mt_rand(-35, -8);
                            $actualFinish = ($actualStart ?? $plannedStart)->copy()->addMinutes($duration + $finishSlack);
                        }

                        $isLate = false;
                        if ($status === 'completed' && $actualFinish) {
                            $isLate = $actualFinish->greaterThan($plannedEnd);
                        }

                        $ticketNumber = sprintf('T%s%04d', $date->format('ymd'), $ticketCounter++);
                        $poNumber = $date->format('ymd') . str_pad((string) mt_rand(1, 999), 3, '0', STR_PAD_LEFT);

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
                            'po_number' => $poNumber,
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
                            'blocking_risk' => mt_rand(1, 100) <= 85 ? 0 : (mt_rand(1, 100) <= 75 ? 1 : 2),
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
