<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Seed sample vendor data
        $now = now();
        DB::table('md_bp')->insert([
            [
                'bp_code' => 'V001',
                'bp_name' => 'PT Sumber Makmur Abadi',
                'bp_type' => 'vendor',
                'npwp' => '01.234.567.8-001.000',
                'address' => 'Jl. Industri No. 12, Kawasan MM2100',
                'city' => 'Bekasi',
                'phone' => '021-88881234',
                'email' => 'pt.sma@example.com',
                'pic_name' => 'Budi Santoso',
                'pic_phone' => '08111234567',
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bp_code' => 'V002',
                'bp_name' => 'CV Jaya Logistik Nusantara',
                'bp_type' => 'vendor',
                'npwp' => '02.345.678.9-002.000',
                'address' => 'Jl. Diponegoro No. 45',
                'city' => 'Surabaya',
                'phone' => '031-55559876',
                'email' => 'cv.jln@example.com',
                'pic_name' => 'Siti Rahayu',
                'pic_phone' => '08221234567',
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bp_code' => 'V003',
                'bp_name' => 'PT Global Sukses Sejahtera',
                'bp_type' => 'vendor',
                'npwp' => '03.456.789.0-003.000',
                'address' => 'Jl. Gatot Subroto Kav. 56',
                'city' => 'Jakarta Selatan',
                'phone' => '021-52226789',
                'email' => 'pt.gss@example.com',
                'pic_name' => 'Ahmad Fauzi',
                'pic_phone' => '08131234567',
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bp_code' => 'V004',
                'bp_name' => 'PT Berkah Transindo',
                'bp_type' => 'vendor',
                'npwp' => '04.567.890.1-004.000',
                'address' => 'Jl. Raya Bogor KM 35',
                'city' => 'Bogor',
                'phone' => '0251-8889012',
                'email' => 'pt.bt@example.com',
                'pic_name' => 'Dewi Kusuma',
                'pic_phone' => '08561234567',
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bp_code' => 'V005',
                'bp_name' => 'UD Setia Kawan Jaya',
                'bp_type' => 'vendor',
                'npwp' => '05.678.901.2-005.000',
                'address' => 'Jl. Veteran No. 88',
                'city' => 'Bandung',
                'phone' => '022-6661234',
                'email' => 'ud.skj@example.com',
                'pic_name' => 'Eko Prasetyo',
                'pic_phone' => '08171234567',
                'is_active' => false,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bp_code' => 'C001',
                'bp_name' => 'PT Maju Bersama Indonesia',
                'bp_type' => 'customer',
                'npwp' => '06.789.012.3-006.000',
                'address' => 'Jl. Sudirman No. 100',
                'city' => 'Jakarta Pusat',
                'phone' => '021-57891234',
                'email' => 'pt.mbi@example.com',
                'pic_name' => 'Hendro Wibowo',
                'pic_phone' => '08819876543',
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'bp_code' => 'C002',
                'bp_name' => 'PT Karya Mandiri Unggul',
                'bp_type' => 'customer',
                'npwp' => '07.890.123.4-007.000',
                'address' => 'Jl. Ahmad Yani No. 234',
                'city' => 'Semarang',
                'phone' => '024-76549876',
                'email' => 'pt.kmu@example.com',
                'pic_name' => 'Rina Wulandari',
                'pic_phone' => '08119876543',
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('md_bp')->whereIn('bp_code', ['V001', 'V002', 'V003', 'V004', 'V005', 'C001', 'C002'])->delete();
    }
};
