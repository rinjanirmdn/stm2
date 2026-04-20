<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GateSeeder extends Seeder
{
    public function run(): void
    {
        // Get warehouse IDs
        $wh1 = DB::table('md_warehouse')->where('code', 'WH1')->first();
        $wh2 = DB::table('md_warehouse')->where('code', 'WH2')->first();

        $gates = [
            // WH1 Gates
            ['warehouse_id' => $wh1->id, 'gate_number' => '1', 'name' => 'Gate 1', 'is_active' => true],

            // WH2 Gates
            ['warehouse_id' => $wh2->id, 'gate_number' => '2', 'name' => 'Gate 2', 'is_active' => true],
            ['warehouse_id' => $wh2->id, 'gate_number' => '3', 'name' => 'Gate 3', 'is_active' => true],
        ];

        foreach ($gates as $gate) {
            DB::table('md_gates')->insert([
                'warehouse_id' => $gate['warehouse_id'],
                'gate_number' => $gate['gate_number'],
                'name' => $gate['name'],
                'is_active' => $gate['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('✓ Gates created successfully');
    }
}
