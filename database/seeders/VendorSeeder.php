<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = [
            ['code' => 'V001', 'name' => 'PT. Ekspedisi Cepat', 'type' => 'supplier', 'is_active' => true],
            ['code' => 'V002', 'name' => 'PT. Logistik Indonesia', 'type' => 'supplier', 'is_active' => true],
            ['code' => 'V003', 'name' => 'PT. Transportasi Maju', 'type' => 'supplier', 'is_active' => true],
            ['code' => 'V004', 'name' => 'PT. Kargo Express', 'type' => 'supplier', 'is_active' => true],
            ['code' => 'V005', 'name' => 'PT. Distribusi Nasional', 'type' => 'supplier', 'is_active' => true],
            ['code' => 'C001', 'name' => 'PT. Customer Utama', 'type' => 'customer', 'is_active' => true],
            ['code' => 'C002', 'name' => 'PT. Retail Besar', 'type' => 'customer', 'is_active' => true],
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
