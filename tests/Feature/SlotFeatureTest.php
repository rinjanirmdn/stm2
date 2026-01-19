<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Slot;
use App\Models\Warehouse;
use App\Models\Vendor;
use App\Models\Gate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SlotFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            \Database\Seeders\AdminUserSeeder::class,
            \Database\Seeders\WarehouseSeeder::class,
            \Database\Seeders\GateSeeder::class,
            \Database\Seeders\VendorSeeder::class,
            \Database\Seeders\TruckTypeDurationSeeder::class,
        ]);
    }

    public function test_index_page_loads_with_slots(): void
    {
        $user = User::where('username', 'admin')->first();

        // Create a PO first with known po_number
        $po = \App\Models\PO::factory()->create(['po_number' => 'TEST001']);

        Slot::factory()->create([
            'po_number' => 'SLOT001', // This is different from PO's po_number
            'po_id' => $po->id, // The index displays PO's po_number
            'warehouse_id' => Warehouse::where('code', 'WH1')->first()->id,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($user)
                        ->get(route('slots.index'));

        $response->assertStatus(200);
        // The index page displays PO's po_number as truck_number, not slot's po_number
        $response->assertSee('TEST001');
    }

    public function test_can_create_planned_slot(): void
    {
        $user = User::where('username', 'admin')->first();
        $warehouse = Warehouse::where('code', 'WH1')->first();
        $vendor = Vendor::where('type', 'supplier')->first();

        $response = $this->actingAs($user)
                        ->post(route('slots.store'), [
                            'po_number' => 'PO123456',
                            'truck_number' => 'TRUCK001',
                            'truck_type' => 'Container 20ft',
                            'direction' => 'inbound',
                            'warehouse_id' => $warehouse->id,
                            'vendor_id' => $vendor->id,
                            'planned_start' => now()->addHours(2)->format('Y-m-d H:i'),
                            // Skip planned_gate_id to avoid overlap check
                        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('slots', [
            'po_number' => 'PO123456',
            'truck_number' => 'TRUCK001',
        ]);
    }

    public function test_can_start_slot_processing(): void
    {
        $user = User::where('username', 'admin')->first();
        $warehouse = Warehouse::where('code', 'WH1')->first();
        $gate = Gate::where('warehouse_id', $warehouse->id)->first();

        // Create a slot with status 'scheduled'
        $slot = Slot::factory()->create([
            'status' => 'scheduled',
            'planned_start' => now()->subMinutes(30),
        ]);

        // First, record arrival
        $arrivalResponse = $this->actingAs($user)
                ->post(route('slots.arrival.store', $slot->id), [
                    'ticket_number' => 'A2510001',
                    'sj_number' => 'SJ001',
                    'truck_type' => 'Container 20ft',
                    'actual_arrival' => now()->format('Y-m-d H:i'),
                    'actual_gate_id' => $gate->id,
                ]);

        // Then start processing
        $response = $this->actingAs($user)
                        ->post(route('slots.start.store', $slot->id), [
                            'actual_gate_id' => $gate->id,
                        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('slots', [
            'id' => $slot->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_can_complete_slot(): void
    {
        $user = User::where('username', 'admin')->first();
        $warehouse = Warehouse::where('code', 'WH1')->first();
        $gate = Gate::where('warehouse_id', $warehouse->id)->first();

        // Create a slot with status 'in_progress'
        $slot = Slot::factory()->create([
            'status' => 'in_progress',
            'planned_start' => now()->subMinutes(60),
            'actual_start' => now()->subMinutes(30),
        ]);

        $response = $this->actingAs($user)
                        ->post(route('slots.complete.store', $slot->id), [
                            'mat_doc' => 'MAT001',
                            'sj_number' => 'SJ001',
                            'truck_type' => 'Container 20ft',
                            'vehicle_number' => 'B1234XYZ',
                            'driver_number' => 'DRV001',
                            'actual_finish' => now()->format('Y-m-d H:i'),
                        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('slots', [
            'id' => $slot->id,
            'status' => 'completed',
        ]);
    }

    public function test_ajax_search_suggestions(): void
    {
        $user = User::where('username', 'admin')->first();
        $warehouse = Warehouse::where('code', 'WH1')->first();
        $vendor = Vendor::where('type', 'supplier')->first();

        // Create PO records first
        DB::table('po')->insert([
            ['po_number' => 'PO123456', 'truck_number' => 'TRUCK001', 'truck_type' => 'Container 20ft', 'direction' => 'inbound', 'vendor_id' => $vendor->id, 'warehouse_id' => $warehouse->id, 'is_active' => true],
            ['po_number' => 'PO789012', 'truck_number' => 'TRUCK002', 'truck_type' => 'Container 40ft', 'direction' => 'inbound', 'vendor_id' => $vendor->id, 'warehouse_id' => $warehouse->id, 'is_active' => true],
        ]);

        // Create slots linked to PO
        $po1 = DB::table('po')->where('po_number', 'PO123456')->first();
        $po2 = DB::table('po')->where('po_number', 'PO789012')->first();

        Slot::factory()->create([
            'po_id' => $po1->id,
            'po_number' => 'PO123456',
            'truck_number' => 'TRUCK001',
            'status' => 'scheduled', // not completed
            'warehouse_id' => $warehouse->id,
            'vendor_id' => $vendor->id,
        ]);

        Slot::factory()->create([
            'po_id' => $po2->id,
            'po_number' => 'PO789012',
            'truck_number' => 'TRUCK002',
            'status' => 'arrived', // not completed
            'warehouse_id' => $warehouse->id,
            'vendor_id' => $vendor->id,
        ]);

        $response = $this->actingAs($user)
                        ->get('/slots/search-suggestions?q=PO');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['text', 'highlighted']
        ]);

        // Should return results
        $this->assertNotEmpty($response->json());
    }
}
