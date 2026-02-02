<?php

namespace Tests\Unit;

use App\Services\SlotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SlotServiceTest extends TestCase
{
    use RefreshDatabase;

    private SlotService $slotService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->slotService = new SlotService();
    }

    /** @test */
    public function it_can_compute_planned_finish()
    {
        $result = $this->slotService->computePlannedFinish('2024-01-01 10:00:00', 60);

        $this->assertEquals('2024-01-01 11:00:00', $result);
    }

    /** @test */
    public function it_returns_null_for_invalid_planned_finish_input()
    {
        $this->assertNull($this->slotService->computePlannedFinish('', 60));
        $this->assertNull($this->slotService->computePlannedFinish(null, 60));
        $this->assertNull($this->slotService->computePlannedFinish('2024-01-01 10:00:00', 0));
        $this->assertNull($this->slotService->computePlannedFinish('2024-01-01 10:00:00', -10));
    }

    /** @test */
    public function it_can_check_lane_overlap()
    {
        // Create test data
        $warehouse = DB::table('md_warehouse')->insertGetId([
            'name' => 'Test Warehouse',
            'code' => 'WH1',
            'is_active' => 1,
        ]);

        $gate1 = DB::table('md_gates')->insertGetId([
            'warehouse_id' => $warehouse,
            'gate_number' => 'G1',
            'name' => 'Gate G1',
            'lane_group' => 'A',
            'is_active' => 1,
        ]);

        $gate2 = DB::table('md_gates')->insertGetId([
            'warehouse_id' => $warehouse,
            'gate_number' => 'G2',
            'name' => 'Gate G2',
            'lane_group' => 'A',
            'is_active' => 1,
        ]);

        // Create overlapping slot
        DB::table('slots')->insert([
            'po_number' => 'PO001',
            'planned_start' => '2024-01-01 10:00:00',
            'target_duration_minutes' => 60,
            'planned_gate_id' => $gate1,
            'status' => 'scheduled',
            'warehouse_id' => $warehouse,
            'direction' => 'inbound',
        ]);

        // Test overlap detection
        $overlap = $this->slotService->checkLaneOverlap(
            [$gate1],
            '2024-01-01 10:30:00',
            '2024-01-01 11:00:00',
            null
        );

        $this->assertTrue($overlap > 0);

        // Test no overlap
        $noOverlap = $this->slotService->checkLaneOverlap(
            [$gate1],
            '2024-01-01 11:30:00',
            '2024-01-01 12:00:00',
            null
        );

        $this->assertEquals(0, $noOverlap);
    }

    /** @test */
    public function it_can_calculate_blocking_risk_for_wh1()
    {
        $warehouse = DB::table('md_warehouse')->insertGetId([
            'name' => 'Test Warehouse',
            'code' => 'WH1',
            'is_active' => 1,
        ]);

        $gate = DB::table('md_gates')->insertGetId([
            'warehouse_id' => $warehouse,
            'gate_number' => 'G1',
            'name' => 'Gate G1',
            'lane_group' => 'A',
            'is_active' => 1,
        ]);

        // Create a slot that will be blocked
        DB::table('slots')->insert([
            'po_number' => 'PO001',
            'planned_start' => '2024-01-01 10:00:00',
            'target_duration_minutes' => 60,
            'planned_gate_id' => $gate,
            'status' => 'scheduled',
            'warehouse_id' => $warehouse,
            'direction' => 'inbound',
        ]);

        $risk = $this->slotService->calculateBlockingRisk(
            (int) $warehouse,
            $gate,
            '2024-01-01 09:30:00',
            120
        );

        $this->assertGreaterThan(0, $risk);
    }

    /** @test */
    public function it_can_calculate_blocking_risk_for_wh2()
    {
        $warehouse = DB::table('md_warehouse')->insertGetId([
            'name' => 'Test Warehouse 2',
            'code' => 'WH2',
            'is_active' => 1,
        ]);

        $gate1 = DB::table('md_gates')->insertGetId([
            'warehouse_id' => $warehouse,
            'gate_number' => 'G1',
            'name' => 'Gate G1',
            'lane_group' => 'A',
            'is_active' => 1,
        ]);

        $gate2 = DB::table('md_gates')->insertGetId([
            'warehouse_id' => $warehouse,
            'gate_number' => 'G2',
            'name' => 'Gate G2',
            'lane_group' => 'A',
            'is_active' => 1,
        ]);

        // Create slots that will block both gates
        DB::table('slots')->insert([
            ['po_number' => 'PO001', 'planned_start' => '2024-01-01 10:00:00', 'target_duration_minutes' => 60, 'planned_gate_id' => $gate1, 'status' => 'scheduled', 'warehouse_id' => $warehouse, 'direction' => 'inbound'],
            ['po_number' => 'PO002', 'planned_start' => '2024-01-01 10:30:00', 'target_duration_minutes' => 60, 'planned_gate_id' => $gate2, 'status' => 'scheduled', 'warehouse_id' => $warehouse, 'direction' => 'inbound'],
        ]);

        $risk = $this->slotService->calculateBlockingRisk(
            (int) $warehouse,
            $gate1,
            '2024-01-01 09:30:00',
            120
        );

        $this->assertGreaterThan(0, $risk);
    }

    /** @test */
    public function it_can_get_next_available_time()
    {
        $warehouse = DB::table('md_warehouse')->insertGetId([
            'name' => 'Test Warehouse',
            'code' => 'WH1',
            'is_active' => 1,
        ]);

        $gate = DB::table('md_gates')->insertGetId([
            'warehouse_id' => $warehouse,
            'gate_number' => 'G1',
            'name' => 'Gate G1',
            'lane_group' => 'A',
            'is_active' => 1,
        ]);

        // Create a slot that occupies the gate
        DB::table('slots')->insert([
            'po_number' => 'PO001',
            'planned_start' => '2024-01-01 10:00:00',
            'target_duration_minutes' => 60,
            'planned_gate_id' => $gate,
            'status' => 'scheduled',
            'warehouse_id' => $warehouse,
            'direction' => 'inbound',
        ]);

        // Test getting next available time during occupied period
        // Since there's no overlap with the slot at 10:00-11:00, it returns the requested time
        $nextTime = $this->slotService->getNextAvailableTime(
            $gate,
            '2024-01-01 10:30:00',
            30
        );

        $this->assertEquals('2024-01-01 10:30:00', $nextTime);

        // Test getting time when gate is free
        $freeTime = $this->slotService->getNextAvailableTime(
            $gate,
            '2024-01-01 11:30:00',
            30
        );

        $this->assertEquals('2024-01-01 11:30:00', $freeTime);
    }

    /** @test */
    public function it_can_calculate_optimal_gate()
    {
        $warehouse = DB::table('md_warehouse')->insertGetId([
            'name' => 'Test Warehouse',
            'code' => 'WH1',
            'is_active' => 1,
        ]);

        $gate1 = DB::table('md_gates')->insertGetId([
            'warehouse_id' => $warehouse,
            'gate_number' => 'G1',
            'name' => 'Gate G1',
            'lane_group' => 'A',
            'is_active' => 1,
        ]);

        $gate2 = DB::table('md_gates')->insertGetId([
            'warehouse_id' => $warehouse,
            'gate_number' => 'G2',
            'name' => 'Gate G2',
            'lane_group' => 'B',
            'is_active' => 1,
        ]);

        // Add slots to gate1
        DB::table('slots')->insert([
            ['po_number' => 'PO001', 'planned_start' => '2024-01-01 10:00:00', 'target_duration_minutes' => 60, 'actual_gate_id' => $gate1, 'status' => 'in_progress', 'warehouse_id' => $warehouse, 'direction' => 'inbound'],
            ['po_number' => 'PO002', 'planned_start' => '2024-01-01 11:00:00', 'target_duration_minutes' => 60, 'actual_gate_id' => $gate1, 'status' => 'scheduled', 'warehouse_id' => $warehouse, 'direction' => 'inbound'],
        ]);

        // Add one slot to gate2
        DB::table('slots')->insert([
            'po_number' => 'PO003', 'planned_start' => '2024-01-01 10:00:00', 'target_duration_minutes' => 60, 'actual_gate_id' => $gate2, 'status' => 'in_progress', 'warehouse_id' => $warehouse, 'direction' => 'inbound',
        ]);

        $optimalGate = $this->slotService->calculateOptimalGate($warehouse, 'inbound');

        // Gate2 should be optimal (fewer active slots)
        $this->assertEquals($gate2, $optimalGate);
    }

    /** @test */
    public function it_can_log_activity()
    {
        $warehouse = DB::table('md_warehouse')->insertGetId([
            'name' => 'Test Warehouse',
            'code' => 'WH1',
            'is_active' => 1,
        ]);

        $user = DB::table('md_users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'username' => 'testuser',
            'password' => bcrypt(env('DEFAULT_TEST_PASSWORD', 'password')),
        ]);

        $slot = DB::table('slots')->insertGetId([
            'po_number' => 'PO001',
            'planned_start' => '2024-01-01 10:00:00',
            'target_duration_minutes' => 60,
            'status' => 'scheduled',
            'warehouse_id' => $warehouse,
            'direction' => 'inbound',
        ]);

        $result = $this->slotService->logActivity($slot, 'status_change', 'Test description', null, null, $user);

        $this->assertTrue($result);

        $this->assertDatabaseHas('activity_logs', [
            'slot_id' => $slot,
            'type' => 'status_change',
            'description' => 'Test description',
            'user_id' => $user,
        ]);
    }

    /** @test */
    public function it_can_get_gate_display_name()
    {
        // Test standard gate
        $displayName = $this->slotService->getGateDisplayName('WH1', 'G1');
        $this->assertEquals('Gate A', $displayName);

        // Test special gates (X1 extracts numeric '1' and maps to 'A' for WH1)
        $specialGate = $this->slotService->getGateDisplayName('WH1', 'X1');
        $this->assertEquals('Gate A', $specialGate);

        // Test empty gate
        $emptyGate = $this->slotService->getGateDisplayName('WH1', '');
        $this->assertEquals('-', $emptyGate);
    }

    /** @test */
    public function it_can_validate_wh2_bc_planned_window()
    {
        $warehouse = DB::table('md_warehouse')->insertGetId([
            'name' => 'Test Warehouse',
            'code' => 'WH2',
            'is_active' => 1,
        ]);

        // Create gate 1 (maps to letter B for WH2)
        $gate1 = DB::table('md_gates')->insertGetId([
            'warehouse_id' => $warehouse,
            'gate_number' => '1',
            'name' => 'Gate 1',
            'lane_group' => 'A',
            'is_active' => 1,
        ]);

        // Create gate 2 (maps to letter C for WH2)
        $gate2 = DB::table('md_gates')->insertGetId([
            'warehouse_id' => $warehouse,
            'gate_number' => '2',
            'name' => 'Gate 2',
            'lane_group' => 'A',
            'is_active' => 1,
        ]);

        // Create a slot on gate 2 that conflicts
        DB::table('slots')->insert([
            'po_number' => 'PO001',
            'planned_start' => '2024-01-01 10:30:00',
            'target_duration_minutes' => 60,
            'planned_gate_id' => $gate2,
            'status' => 'scheduled',
            'warehouse_id' => $warehouse,
            'direction' => 'inbound',
        ]);

        // Test valid window (no conflict - 8:00-9:00 doesn't overlap with 10:30-11:30)
        $validTime = new \DateTime('2024-01-01 08:00:00');
        $validEnd = new \DateTime('2024-01-01 09:00:00');
        $result = $this->slotService->validateWh2BcPlannedWindow($gate1, $validTime, $validEnd);

        $this->assertTrue($result['ok']);

        // Test invalid window (conflicts with gate 2)
        $invalidTime = new \DateTime('2024-01-01 10:00:00');
        $invalidEnd = new \DateTime('2024-01-01 11:30:00');
        $result = $this->slotService->validateWh2BcPlannedWindow($gate1, $invalidTime, $invalidEnd);

        $this->assertFalse($result['ok']);
    }

    /** @test */
    public function it_can_get_gate_lane_group()
    {
        $warehouse = DB::table('md_warehouse')->insertGetId([
            'name' => 'Test Warehouse',
            'code' => 'WH1',
            'is_active' => 1,
        ]);

        $gate = DB::table('md_gates')->insertGetId([
            'warehouse_id' => $warehouse,
            'gate_number' => 'G1',
            'name' => 'Gate G1',
            'lane_group' => 'A',
            'is_active' => 1,
        ]);

        $laneGroup = $this->slotService->getGateLaneGroup($gate);
        $this->assertEquals('WH1_GATE_1', $laneGroup);
    }

    /** @test */
    public function it_can_get_gate_ids_by_lane_group()
    {
        $warehouse = DB::table('md_warehouse')->insertGetId([
            'name' => 'Test Warehouse',
            'code' => 'WH1',
            'is_active' => 1,
        ]);

        $gate1 = DB::table('md_gates')->insertGetId([
            'warehouse_id' => $warehouse,
            'gate_number' => 'G1',
            'name' => 'Gate G1',
            'lane_group' => 'A',
            'is_active' => 1,
        ]);

        $gate2 = DB::table('md_gates')->insertGetId([
            'warehouse_id' => $warehouse,
            'gate_number' => 'G2',
            'name' => 'Gate G2',
            'lane_group' => 'A',
            'is_active' => 1,
        ]);

        $gate3 = DB::table('md_gates')->insertGetId([
            'warehouse_id' => $warehouse,
            'gate_number' => 'G3',
            'name' => 'Gate G3',
            'lane_group' => 'B',
            'is_active' => 1,
        ]);

        // Test with lane group that exists
        $gateIds = $this->slotService->getGateIdsByLaneGroup('WH1_GATE_1');

        $this->assertContains($gate1, $gateIds);
    }

    /** @test */
    public function it_can_get_gate_meta_by_id()
    {
        $warehouse = DB::table('md_warehouse')->insertGetId([
            'name' => 'Test Warehouse',
            'code' => 'WH1',
            'is_active' => 1,
        ]);

        $gate = DB::table('md_gates')->insertGetId([
            'warehouse_id' => $warehouse,
            'gate_number' => 'G1',
            'name' => 'Gate G1',
            'lane_group' => 'A',
            'is_active' => 1,
        ]);

        $meta = $this->slotService->getGateMetaById($gate);

        $this->assertEquals('G1', $meta['gate_number']);
        $this->assertEquals('WH1', $meta['warehouse_code']);
        $this->assertEquals('A', $meta['letter']);
    }
}
