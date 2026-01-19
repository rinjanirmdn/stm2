<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Services\SlotConflictService;
use App\Services\SlotService;

class SlotObsoleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup test data
        $this->setupTestWarehousesAndGates();
    }

    private function setupTestWarehousesAndGates(): void
    {
        // Create warehouses
        DB::table('warehouses')->insert([
            ['id' => 1, 'name' => 'Warehouse 1', 'code' => 'WH1', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Warehouse 2', 'code' => 'WH2', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Create gates
        DB::table('gates')->insert([
            ['id' => 1, 'warehouse_id' => 2, 'gate_number' => 'B', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'warehouse_id' => 2, 'gate_number' => 'C', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Create vendor
        DB::table('vendors')->insert([
            ['id' => 1, 'name' => 'Test Vendor', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Create PO
        DB::table('po')->insert([
            ['id' => 1, 'po_number' => 'TEST001', 'truck_number' => 'TRUCK001', 'vendor_id' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /** @test */
    public function it_should_auto_cancel_obsolete_scheduled_slots_when_slot_starts()
    {
        // Create a scheduled slot for future date (tanggal 9)
        $scheduledSlotId = DB::table('slots')->insertGetId([
            'ticket_number' => 'B26010001',
            'po_id' => 1,
            'warehouse_id' => 2,
            'vendor_id' => 1,
            'planned_gate_id' => 1,
            'actual_gate_id' => 1,
            'status' => 'scheduled',
            'slot_type' => 'planned',
            'planned_start' => '2026-01-09 10:00:00',
            'planned_duration' => 60,
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create and start a slot that arrived earlier (tanggal 7)
        $earlySlotId = DB::table('slots')->insertGetId([
            'ticket_number' => 'B26010002',
            'po_id' => 1,
            'warehouse_id' => 2,
            'vendor_id' => 1,
            'planned_gate_id' => 1,
            'actual_gate_id' => 1,
            'status' => 'in_progress',
            'slot_type' => 'planned',
            'planned_start' => '2026-01-07 10:00:00',
            'arrival_time' => '2026-01-07 10:00:00',
            'actual_start' => '2026-01-07 10:00:00',
            'planned_duration' => 60,
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Test the conflict service - should not find conflicts with obsolete scheduled slot
        $conflictService = new SlotConflictService(app(SlotService::class));
        $hasConflicts = $conflictService->hasPotentialConflicts(
            1, // Gate B
            '2026-01-09 10:00:00',
            '2026-01-09 11:00:00',
            0
        );

        // The scheduled slot should be auto-cancelled, so no conflicts
        $this->assertFalse($hasConflicts);

        // Verify the scheduled slot was cancelled
        $scheduledSlot = DB::table('slots')->where('id', $scheduledSlotId)->first();
        $this->assertEquals('cancelled', $scheduledSlot->status);
        $this->assertStringContainsString('Auto-cancelled', $scheduledSlot->cancelled_reason);
    }

    /** @test */
    public function it_should_auto_cancel_obsolete_scheduled_slots_when_completed_slot_exists()
    {
        // Create a scheduled slot for future date (tanggal 9)
        $scheduledSlotId = DB::table('slots')->insertGetId([
            'ticket_number' => 'B26010001',
            'po_id' => 1,
            'warehouse_id' => 2,
            'vendor_id' => 1,
            'planned_gate_id' => 1,
            'actual_gate_id' => 1,
            'status' => 'scheduled',
            'slot_type' => 'planned',
            'planned_start' => '2026-01-09 10:00:00',
            'planned_duration' => 60,
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create and complete a slot that arrived earlier (tanggal 7)
        $earlySlotId = DB::table('slots')->insertGetId([
            'ticket_number' => 'B26010002',
            'po_id' => 1,
            'warehouse_id' => 2,
            'vendor_id' => 1,
            'planned_gate_id' => 1,
            'actual_gate_id' => 1,
            'status' => 'in_progress',
            'slot_type' => 'planned',
            'planned_start' => '2026-01-07 10:00:00',
            'arrival_time' => '2026-01-07 10:00:00',
            'actual_start' => '2026-01-07 10:00:00',
            'planned_duration' => 60,
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Complete the early slot
        DB::table('slots')->where('id', $earlySlotId)->update([
            'status' => 'completed',
            'actual_finish' => '2026-01-07 11:00:00',
        ]);

        // Test the conflict service - should not find conflicts with obsolete scheduled slot
        $conflictService = new SlotConflictService(app(SlotService::class));
        $hasConflicts = $conflictService->hasPotentialConflicts(
            1, // Gate B
            '2026-01-09 10:00:00',
            '2026-01-09 11:00:00',
            0
        );

        // The scheduled slot should be auto-cancelled, so no conflicts
        $this->assertFalse($hasConflicts);

        // Verify the scheduled slot was cancelled
        $scheduledSlot = DB::table('slots')->where('id', $scheduledSlotId)->first();
        $this->assertEquals('cancelled', $scheduledSlot->status);
        $this->assertStringContainsString('Auto-cancelled', $scheduledSlot->cancelled_reason);
    }

    /** @test */
    public function it_should_allow_new_booking_after_obsolete_slot_cancelled()
    {
        // Create and complete an early slot
        $earlySlotId = DB::table('slots')->insertGetId([
            'ticket_number' => 'B26010003',
            'po_id' => 1,
            'warehouse_id' => 2,
            'vendor_id' => 1,
            'planned_gate_id' => 1,
            'actual_gate_id' => 1,
            'status' => 'completed',
            'slot_type' => 'planned',
            'planned_start' => '2026-01-07 10:00:00',
            'arrival_time' => '2026-01-07 10:00:00',
            'actual_start' => '2026-01-07 10:00:00',
            'actual_finish' => '2026-01-07 11:00:00',
            'planned_duration' => 60,
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create an obsolete scheduled slot
        $obsoleteSlotId = DB::table('slots')->insertGetId([
            'ticket_number' => 'B26010004',
            'po_id' => 1,
            'warehouse_id' => 2,
            'vendor_id' => 1,
            'planned_gate_id' => 1,
            'actual_gate_id' => 1,
            'status' => 'scheduled',
            'slot_type' => 'planned',
            'planned_start' => '2026-01-09 10:00:00',
            'planned_duration' => 60,
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Test new booking at same time as obsolete slot
        $conflictService = new SlotConflictService(app(SlotService::class));
        $hasConflicts = $conflictService->hasPotentialConflicts(
            1, // Gate B
            '2026-01-09 10:00:00',
            '2026-01-09 11:00:00',
            0
        );

        // Should not have conflicts because obsolete slot will be auto-cancelled
        $this->assertFalse($hasConflicts);

        // Verify we can create new booking
        $newSlotId = DB::table('slots')->insertGetId([
            'ticket_number' => 'B26010005',
            'po_id' => 1,
            'warehouse_id' => 2,
            'vendor_id' => 1,
            'planned_gate_id' => 1,
            'actual_gate_id' => 1,
            'status' => 'scheduled',
            'slot_type' => 'planned',
            'planned_start' => '2026-01-09 10:00:00',
            'planned_duration' => 60,
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNotNull($newSlotId);
    }

    /** @test */
    public function it_should_not_cancel_non_overlapping_scheduled_slots()
    {
        // Create and complete an early slot
        $earlySlotId = DB::table('slots')->insertGetId([
            'ticket_number' => 'B26010006',
            'po_id' => 1,
            'warehouse_id' => 2,
            'vendor_id' => 1,
            'planned_gate_id' => 1,
            'actual_gate_id' => 1,
            'status' => 'completed',
            'slot_type' => 'planned',
            'planned_start' => '2026-01-07 10:00:00',
            'arrival_time' => '2026-01-07 10:00:00',
            'actual_start' => '2026-01-07 10:00:00',
            'actual_finish' => '2026-01-07 11:00:00',
            'planned_duration' => 60,
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a scheduled slot that doesn't overlap (different time)
        $nonOverlappingSlotId = DB::table('slots')->insertGetId([
            'ticket_number' => 'B26010007',
            'po_id' => 1,
            'warehouse_id' => 2,
            'vendor_id' => 1,
            'planned_gate_id' => 1,
            'actual_gate_id' => 1,
            'status' => 'scheduled',
            'slot_type' => 'planned',
            'planned_start' => '2026-01-09 14:00:00', // Different time
            'planned_duration' => 60,
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Test conflict check for different time
        $conflictService = new SlotConflictService(app(SlotService::class));
        $hasConflicts = $conflictService->hasPotentialConflicts(
            1, // Gate B
            '2026-01-09 14:00:00',
            '2026-01-09 15:00:00',
            0
        );

        // Should not have conflicts
        $this->assertFalse($hasConflicts);

        // Verify the non-overlapping slot was NOT cancelled
        $nonOverlappingSlot = DB::table('slots')->where('id', $nonOverlappingSlotId)->first();
        $this->assertEquals('scheduled', $nonOverlappingSlot->status);
        $this->assertNull($nonOverlappingSlot->cancelled_reason);
    }
}
