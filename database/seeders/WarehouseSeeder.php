<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            ['code' => 'WH1', 'name' => 'Warehouse 1', 'is_active' => true],
            ['code' => 'WH2', 'name' => 'Warehouse 2', 'is_active' => true],
            ['code' => 'WH3', 'name' => 'Warehouse 3', 'is_active' => true],
        ];

        foreach ($warehouses as $warehouse) {
            DB::table('warehouses')->insert([
                'code' => $warehouse['code'],
                'name' => $warehouse['name'],
                'is_active' => $warehouse['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('✓ Warehouses created successfully');
    }
}
