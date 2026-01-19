<?php

namespace Tests\Feature;

use App\Models\PO;
use App\Models\Slot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlotCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelled_slot_retains_original_blocking_risk_and_does_not_recalculate()
    {
        // Create a slot with high blocking risk
        $po = PO::factory()->create([
            'po_number' => 'TEST001',
            'direction' => 'inbound',
            'warehouse_id' => 1,
        ]);

        $slot = Slot::factory()->create([
            'po_id' => $po->id,
            'status' => 'scheduled',
            'warehouse_id' => 1,
            'planned_gate_id' => 1,
            'planned_start' => '2024-01-01 10:00:00',
            'planned_duration' => 60,
            'blocking_risk' => 2, // High risk
        ]);

        // Verify initial state
        $this->assertEquals('scheduled', $slot->status);
        $this->assertEquals(2, $slot->blocking_risk);

        // Cancel the slot
        $response = $this->post("/slots/{$slot->id}/cancel", [
            'cancelled_reason' => 'Test cancellation',
        ]);

        $response->assertRedirect();

        // Reload the slot
        $slot->refresh();

        // Verify status changed to cancelled
        $this->assertEquals('cancelled', $slot->status);
        $this->assertEquals('Test cancellation', $slot->cancelled_reason);
        $this->assertNotNull($slot->cancelled_at);

        // IMPORTANT: Verify blocking risk remains unchanged
        $this->assertEquals(2, $slot->blocking_risk);
    }

    public function test_show_page_does_not_recalculate_blocking_risk_for_cancelled_slots()
    {
        // Create a cancelled slot with specific blocking risk
        $po = PO::factory()->create([
            'po_number' => 'TEST002',
            'direction' => 'inbound',
            'warehouse_id' => 1,
        ]);

        $slot = Slot::factory()->create([
            'po_id' => $po->id,
            'status' => 'cancelled',
            'warehouse_id' => 1,
            'planned_gate_id' => 1,
            'planned_start' => '2024-01-01 10:00:00',
            'planned_duration' => 60,
            'blocking_risk' => 1, // Medium risk
            'cancelled_reason' => 'Already cancelled',
            'cancelled_at' => '2024-01-01 09:00:00',
        ]);

        // Visit the show page
        $response = $this->get("/slots/{$slot->id}");

        $response->assertStatus(200);

        // Reload the slot to verify blocking risk wasn't recalculated
        $slot->refresh();

        // Verify blocking risk remains unchanged
        $this->assertEquals(1, $slot->blocking_risk);
        $this->assertEquals('cancelled', $slot->status);
    }

    public function test_index_page_does_not_recalculate_blocking_risk_for_cancelled_slots()
    {
        // Create a cancelled slot with specific blocking risk
        $po = PO::factory()->create([
            'po_number' => 'TEST003',
            'direction' => 'inbound',
            'warehouse_id' => 1,
        ]);

        $slot = Slot::factory()->create([
            'po_id' => $po->id,
            'status' => 'cancelled',
            'warehouse_id' => 1,
            'planned_gate_id' => 1,
            'planned_start' => '2024-01-01 10:00:00',
            'planned_duration' => 60,
            'blocking_risk' => 0, // Low risk
            'cancelled_reason' => 'Already cancelled',
            'cancelled_at' => '2024-01-01 09:00:00',
        ]);

        // Visit the index page
        $response = $this->get('/slots');

        $response->assertStatus(200);

        // Reload the slot to verify blocking risk wasn't recalculated
        $slot->refresh();

        // Verify blocking risk remains unchanged
        $this->assertEquals(0, $slot->blocking_risk);
        $this->assertEquals('cancelled', $slot->status);
    }
}
