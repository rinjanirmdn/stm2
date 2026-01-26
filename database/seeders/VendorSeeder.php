<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = [
            ['code' => 'V001', 'name' => 'PT. Fast Expedition', 'type' => 'supplier', 'is_active' => true],
            ['code' => 'V002', 'name' => 'PT. Indonesia Logistics', 'type' => 'supplier', 'is_active' => true],
            ['code' => 'V003', 'name' => 'PT. Advanced Transport', 'type' => 'supplier', 'is_active' => true],
            ['code' => 'V004', 'name' => 'PT. Express Cargo', 'type' => 'supplier', 'is_active' => true],
            ['code' => 'V005', 'name' => 'PT. National Distribution', 'type' => 'supplier', 'is_active' => true],
            ['code' => 'C001', 'name' => 'PT. Main Customer', 'type' => 'customer', 'is_active' => true],
            ['code' => 'C002', 'name' => 'PT. Big Retail', 'type' => 'customer', 'is_active' => true],
        ];

        foreach ($vendors as $vendor) {
            DB::table('vendors')->insert([
                'code' => $vendor['code'],
                'name' => $vendor['name'],
                'type' => $vendor['type'],
                'is_active' => $vendor['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('✓ Vendors created successfully');
    }
}
