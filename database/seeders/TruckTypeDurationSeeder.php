<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TruckTypeDurationSeeder extends Seeder
{
    public function run(): void
    {
        $truckTypes = [
            ['truck_type' => 'Container 20ft', 'target_duration_minutes' => 120],
            ['truck_type' => 'Container 40ft', 'target_duration_minutes' => 180],
            ['truck_type' => 'Tronton', 'target_duration_minutes' => 90],
            ['truck_type' => 'CDE', 'target_duration_minutes' => 60],
            ['truck_type' => 'CDD', 'target_duration_minutes' => 45],
            ['truck_type' => 'Cargo', 'target_duration_minutes' => 60],
            ['truck_type' => 'Small Cargo', 'target_duration_minutes' => 30],
            ['truck_type' => 'Medium Cargo', 'target_duration_minutes' => 90],
            ['truck_type' => 'Large Cargo', 'target_duration_minutes' => 120],
        ];

        foreach ($truckTypes as $type) {
            DB::table('md_truck')->insert([
                'truck_type' => $type['truck_type'],
                'target_duration_minutes' => $type['target_duration_minutes'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('✓ Truck type durations created successfully');
    }
}
