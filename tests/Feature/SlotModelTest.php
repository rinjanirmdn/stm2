<?php

namespace Tests\Feature;

use App\Models\PO;
use App\Models\Slot;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SlotModelTest extends TestCase
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

    public function test_slot_can_be_created_with_relationships(): void
    {
        $user = User::where('username', 'admin')->first();
        $warehouse = Warehouse::where('code', 'WH1')->first();
        $vendor = Vendor::where('type', 'supplier')->first();

        // Create PO first
        $po = PO::factory()->create([
            'po_number' => 'TESTPO001',
            'warehouse_id' => $warehouse->id,
            'vendor_id' => $vendor->id,
        ]);

        // Create slot with relationships
        $slot = Slot::factory()->create([
            'po_number' => 'TESTPO001',
            'po_id' => $po->id,
            'warehouse_id' => $warehouse->id,
            'vendor_id' => $vendor->id,
            'status' => 'scheduled',
        ]);

        $this->assertDatabaseHas('slots', [
            'id' => $slot->id,
            'po_number' => 'TESTPO001',
            'po_id' => $po->id,
        ]);

        // Test relationships
        $this->assertEquals($po->id, $slot->po_id);
        $this->assertEquals($warehouse->id, $slot->warehouse_id);
        $this->assertEquals($vendor->id, $slot->vendor_id);
    }

    public function test_slot_index_query_requires_po_id(): void
    {
        // Create slot without po_id
        $slot = Slot::factory()->create([
            'po_id' => null,
            'po_number' => 'NOTRUCK001',
        ]);

        // This slot won't appear in index query because of INNER JOIN
        $results = \DB::table('slots as s')
            ->join('po as t', 's.po_id', '=', 't.id')
            ->where('s.id', $slot->id)
            ->get();

        $this->assertCount(0, $results);

        // Create slot with po_id
        $po = PO::factory()->create();
        $slotWithTruck = Slot::factory()->create([
            'po_id' => $po->id,
            'po_number' => 'WITHTRUCK001',
        ]);

        $results = \DB::table('slots as s')
            ->join('po as t', 's.po_id', '=', 't.id')
            ->where('s.id', $slotWithTruck->id)
            ->get();

        $this->assertCount(1, $results);
        // Query returns po_number from PO table, not slots table
        $this->assertEquals($po->po_number, $results->first()->po_number);
    }
}
