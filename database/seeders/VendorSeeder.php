<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $table = null;
        if (Schema::hasTable('vendors')) {
            $table = 'vendors';
        } elseif (Schema::hasTable('business_partner')) {
            $table = 'business_partner';
        }

        if (!$table) {
            $this->command->warn('VendorSeeder skipped: vendors table not found.');
            return;
        }

        $vendors = [
            ['code' => 'V001', 'name' => 'PT Master Label', 'type' => 'supplier', 'is_active' => true],
            ['code' => 'V002', 'name' => 'PT. Serunigraf Jaya Sentosa', 'type' => 'supplier', 'is_active' => true],
            ['code' => 'V003', 'name' => 'Thai Polyethylene Co. LTD', 'type' => 'supplier', 'is_active' => true],
            ['code' => 'V004', 'name' => 'Wenzhou Beston Import and Export Co', 'type' => 'supplier', 'is_active' => true],
            ['code' => 'C001', 'name' => 'PT. Ganesha Abaditama', 'type' => 'customer', 'is_active' => true],
            ['code' => 'C002', 'name' => 'PT. Sentra Asia gemilang', 'type' => 'customer', 'is_active' => true],
            ['code' => 'C003', 'name' => 'PT. Buana Intiprima Usaha', 'type' => 'customer', 'is_active' => true],
            ['code' => 'C004', 'name' => 'PT. Itama Ranoraya Tbk', 'type' => 'customer', 'is_active' => true],
            ['code' => 'C005', 'name' => 'PT. TOPLA FONDAMEN SUKSES', 'type' => 'customer', 'is_active' => true],
            ['code' => 'C006', 'name' => 'PT. Itama Ranoraya Tbk', 'type' => 'customer', 'is_active' => true],
        ];

        $codeCol = Schema::hasColumn($table, 'code') ? 'code' : 'bp_code';
        $nameCol = Schema::hasColumn($table, 'name') ? 'name' : 'bp_name';
        $typeCol = Schema::hasColumn($table, 'type') ? 'type' : 'bp_type';
        $activeCol = Schema::hasColumn($table, 'is_active') ? 'is_active' : null;

        foreach ($vendors as $vendor) {
            $payload = [
                $codeCol => $vendor['code'],
                $nameCol => $vendor['name'],
                $typeCol => $vendor['type'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($activeCol) {
                $payload[$activeCol] = $vendor['is_active'];
            }

            DB::table($table)->insert($payload);
        }

        $this->command->info('✓ Vendors created successfully');
    }
}
