<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PO>
 */
class POFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'po_number' => $this->faker->unique()->numerify('PO########'),
            'mat_doc' => $this->faker->unique()->numerify('MAT##########'),
            'truck_number' => $this->faker->bothify('TRUCK-####'),
            'truck_type' => $this->faker->randomElement(['Container 20ft', 'Container 40ft', 'Tronton', 'CDE', 'CDD']),
            'vendor_id' => DB::table('vendors')->inRandomOrder()->first()->id,
            'direction' => $this->faker->randomElement(['inbound', 'outbound']),
            'warehouse_id' => DB::table('md_warehouse')->inRandomOrder()->first()->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
