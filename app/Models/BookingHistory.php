<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'slot_id',
        'action',
        'performed_by',
        'notes',
        'old_status',
        'new_status',
        'old_planned_start',
        'new_planned_start',
        'old_planned_duration',
        'new_planned_duration',
        'old_gate_id',
        'new_gate_id',
    ];

    protected $casts = [
        'old_planned_start' => 'datetime',
        'new_planned_start' => 'datetime',
    ];

    /**
     * Action constants
     */
    public const ACTION_REQUESTED = 'requested';
    public const ACTION_APPROVED = 'approved';
    public const ACTION_REJECTED = 'rejected';
    public const ACTION_RESCHEDULED = 'rescheduled';
    public const ACTION_VENDOR_CONFIRMED = 'vendor_confirmed';
    public const ACTION_VENDOR_REJECTED = 'vendor_rejected';
    public const ACTION_VENDOR_PROPOSED = 'vendor_proposed';
    public const ACTION_CANCELLED = 'cancelled';

    /**
     * Get all possible actions
     */
    public static function allActions(): array
    {
        return [
            self::ACTION_REQUESTED,
            self::ACTION_APPROVED,
            self::ACTION_REJECTED,
            self::ACTION_RESCHEDULED,
            self::ACTION_VENDOR_CONFIRMED,
            self::ACTION_VENDOR_REJECTED,
            self::ACTION_VENDOR_PROPOSED,
            self::ACTION_CANCELLED,
        ];
    }

    /**
     * Get action label for display
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_REQUESTED => 'Booking Requested',
            self::ACTION_APPROVED => 'Approved',
            self::ACTION_REJECTED => 'Rejected',
            self::ACTION_RESCHEDULED => 'Rescheduled by Admin',
            self::ACTION_VENDOR_CONFIRMED => 'Confirmed by Vendor',
            self::ACTION_VENDOR_REJECTED => 'Rejected by Vendor',
            self::ACTION_VENDOR_PROPOSED => 'New Schedule Proposed',
            self::ACTION_CANCELLED => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    /**
     * Get action badge color for display
     */
    public function getActionBadgeColorAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_REQUESTED => 'warning',
            self::ACTION_APPROVED, self::ACTION_VENDOR_CONFIRMED => 'success',
            self::ACTION_REJECTED, self::ACTION_VENDOR_REJECTED, self::ACTION_CANCELLED => 'danger',
            self::ACTION_RESCHEDULED, self::ACTION_VENDOR_PROPOSED => 'info',
            default => 'secondary',
        };
    }

    /**
     * Relationship: Slot
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }

    /**
     * Relationship: User who performed the action
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Relationship: Old gate
     */
    public function oldGate(): BelongsTo
    {
        return $this->belongsTo(Gate::class, 'old_gate_id');
    }

    /**
     * Relationship: New gate
     */
    public function newGate(): BelongsTo
    {
        return $this->belongsTo(Gate::class, 'new_gate_id');
    }

    /**
     * Create a history entry for a slot action
     */
    public static function logAction(
        int $slotId,
        string $action,
        int $performedBy,
        string $newStatus,
        ?string $oldStatus = null,
        ?string $notes = null,
        ?array $scheduleData = []
    ): self {
        return self::create([
            'slot_id' => $slotId,
            'action' => $action,
            'performed_by' => $performedBy,
            'notes' => $notes,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'old_planned_start' => $scheduleData['old_planned_start'] ?? null,
            'new_planned_start' => $scheduleData['new_planned_start'] ?? null,
            'old_planned_duration' => $scheduleData['old_planned_duration'] ?? null,
            'new_planned_duration' => $scheduleData['new_planned_duration'] ?? null,
            'old_gate_id' => $scheduleData['old_gate_id'] ?? null,
            'new_gate_id' => $scheduleData['new_gate_id'] ?? null,
        ]);
    }
}
