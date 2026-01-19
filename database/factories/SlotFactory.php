<?php

namespace Database\Factories;

use App\Models\PO;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Slot>
 */
class SlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get random IDs from related tables
        $warehouseId = DB::table('warehouses')->inRandomOrder()->first()->id;
        $vendorId = DB::table('vendors')->inRandomOrder()->first()->id;
        $gateId = DB::table('gates')->inRandomOrder()->first()->id;

        return [
            'ticket_number' => 'TKT-' . date('Ymd') . '-' . $this->faker->unique()->numberBetween(1000, 9999),
            'po_number' => $this->faker->unique()->numerify('PO########'),
            'mat_doc' => $this->faker->unique()->numerify('MAT##########'),
            'sj_number' => $this->faker->unique()->numerify('SJ##########'),
            'truck_number' => $this->faker->bothify('TRUCK-####'),
            'truck_type' => $this->faker->randomElement(['Container 20ft', 'Container 40ft', 'Tronton', 'CDE', 'CDD']),
            'direction' => $this->faker->randomElement(['inbound', 'outbound']),
            'po_id' => PO::factory(),
            'warehouse_id' => $warehouseId,
            'vendor_id' => $vendorId,
            'planned_gate_id' => $gateId,
            'actual_gate_id' => null,
            'status' => $this->faker->randomElement(['scheduled', 'arrived', 'waiting', 'in_progress', 'completed']),
            'slot_type' => 'planned',
            'planned_start' => $this->faker->dateTimeBetween('+1 hour', '+1 week'),
            'planned_finish' => $this->faker->dateTimeBetween('+2 hours', '+1 week'),
            'target_duration_minutes' => $this->faker->numberBetween(30, 240),
            'created_by' => 1, // Admin user
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
