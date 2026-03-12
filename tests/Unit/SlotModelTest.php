<?php

namespace Tests\Unit;

use App\Models\Slot;
use Tests\TestCase;

class SlotModelTest extends TestCase
{
    /**
     * Test all status constants exist.
     */
    public function test_status_constants_are_defined(): void
    {
        $this->assertEquals('scheduled', Slot::STATUS_SCHEDULED);
        $this->assertEquals('arrived', Slot::STATUS_ARRIVED);
        $this->assertEquals('waiting', Slot::STATUS_WAITING);
        $this->assertEquals('in_progress', Slot::STATUS_IN_PROGRESS);
        $this->assertEquals('completed', Slot::STATUS_COMPLETED);
        $this->assertEquals('cancelled', Slot::STATUS_CANCELLED);
        $this->assertEquals('pending_approval', Slot::STATUS_PENDING_APPROVAL);
    }

    /**
     * Test allStatuses returns all 7 statuses.
     */
    public function test_all_statuses_returns_complete_list(): void
    {
        $statuses = Slot::allStatuses();

        $this->assertCount(7, $statuses);
        $this->assertContains('scheduled', $statuses);
        $this->assertContains('arrived', $statuses);
        $this->assertContains('waiting', $statuses);
        $this->assertContains('in_progress', $statuses);
        $this->assertContains('completed', $statuses);
        $this->assertContains('cancelled', $statuses);
        $this->assertContains('pending_approval', $statuses);
    }

    /**
     * Test activeStatuses excludes completed and cancelled.
     */
    public function test_active_statuses_exclude_finished(): void
    {
        $active = Slot::activeStatuses();

        $this->assertNotContains('completed', $active);
        $this->assertNotContains('cancelled', $active);
        $this->assertContains('scheduled', $active);
        $this->assertContains('in_progress', $active);
    }

    /**
     * Test adminActionStatuses includes only pending_approval.
     */
    public function test_admin_action_statuses(): void
    {
        $statuses = Slot::adminActionStatuses();

        $this->assertCount(1, $statuses);
        $this->assertContains('pending_approval', $statuses);
    }

    /**
     * Test isPendingApproval returns correct boolean.
     */
    public function test_is_pending_approval(): void
    {
        $slot = new Slot();
        $slot->status = 'pending_approval';
        $this->assertTrue($slot->isPendingApproval());

        $slot->status = 'scheduled';
        $this->assertFalse($slot->isPendingApproval());
    }

    /**
     * Test needsAction returns true for actionable statuses.
     */
    public function test_needs_action(): void
    {
        $slot = new Slot();

        $slot->status = 'pending_approval';
        $this->assertTrue($slot->needsAction());

        $slot->status = 'completed';
        $this->assertFalse($slot->needsAction());

        $slot->status = 'scheduled';
        $this->assertFalse($slot->needsAction());
    }

    /**
     * Test status label accessor.
     */
    public function test_status_label_accessor(): void
    {
        $slot = new Slot();

        $slot->status = 'scheduled';
        $this->assertEquals('Scheduled', $slot->status_label);

        $slot->status = 'in_progress';
        $this->assertEquals('In Progress', $slot->status_label);

        $slot->status = 'pending_approval';
        $this->assertEquals('Pending Approval', $slot->status_label);

        $slot->status = 'completed';
        $this->assertEquals('Completed', $slot->status_label);
    }

    /**
     * Test status badge color accessor.
     */
    public function test_status_badge_color_accessor(): void
    {
        $slot = new Slot();

        $slot->status = 'scheduled';
        $this->assertEquals('secondary', $slot->status_badge_color);

        $slot->status = 'in_progress';
        $this->assertEquals('primary', $slot->status_badge_color);

        $slot->status = 'completed';
        $this->assertEquals('success', $slot->status_badge_color);

        $slot->status = 'cancelled';
        $this->assertEquals('dark', $slot->status_badge_color);
    }

    /**
     * Test fillable attributes are comprehensive.
     */
    public function test_fillable_attributes(): void
    {
        $slot = new Slot();
        $fillable = $slot->getFillable();

        $this->assertContains('ticket_number', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('vendor_code', $fillable);
        $this->assertContains('planned_start', $fillable);
        $this->assertContains('requested_by', $fillable);
        $this->assertContains('approved_by', $fillable);
    }

    /**
     * Test datetime fields are properly cast.
     */
    public function test_datetime_casts(): void
    {
        $slot = new Slot();
        $casts = $slot->getCasts();

        $this->assertEquals('datetime', $casts['planned_start']);
        $this->assertEquals('datetime', $casts['arrival_time']);
        $this->assertEquals('datetime', $casts['actual_start']);
        $this->assertEquals('datetime', $casts['actual_finish']);
        $this->assertEquals('boolean', $casts['is_late']);
        $this->assertEquals('boolean', $casts['moved_gate']);
    }

    /**
     * Test approval action constants.
     */
    public function test_approval_action_constants(): void
    {
        $this->assertEquals('approved', Slot::APPROVAL_APPROVED);
        $this->assertEquals('rejected', Slot::APPROVAL_REJECTED);
        $this->assertEquals('rescheduled', Slot::APPROVAL_RESCHEDULED);
    }
}
