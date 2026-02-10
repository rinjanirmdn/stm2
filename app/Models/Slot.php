<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slot extends Model
{
    use HasFactory;

    /**
     * Status constants
     */
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_ARRIVED = 'arrived';
    public const STATUS_WAITING = 'waiting';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Approval action constants
     */
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';
    public const APPROVAL_RESCHEDULED = 'rescheduled';

    protected $fillable = [
        'ticket_number',
        'mat_doc',
        'truck_type',
        'vehicle_number_snap',
        'driver_name',
        'driver_number',
        'direction',
        'po_number',
        'warehouse_id',
        'vendor_code',
        'vendor_name',
        'vendor_type',
        'planned_gate_id',
        'actual_gate_id',
        'status',
        'slot_type',
        'planned_start',
        'arrival_time',
        'actual_start',
        'actual_finish',
        'planned_duration',
        'is_late',
        'late_reason',
        'cancelled_reason',
        'cancelled_at',
        'moved_gate',
        'blocking_risk',
        'created_by',
        // New booking approval fields
        'requested_by',
        'approved_by',
        'approval_action',
        'approval_notes',
        'requested_at',
        'approved_at',
        'original_planned_start',
        'original_planned_gate_id',
    ];

    protected $casts = [
        'planned_start' => 'datetime',
        'arrival_time' => 'datetime',
        'actual_start' => 'datetime',
        'actual_finish' => 'datetime',
        'cancelled_at' => 'datetime',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'original_planned_start' => 'datetime',
        'is_late' => 'boolean',
        'moved_gate' => 'boolean',
        'blocking_risk' => 'integer',
    ];

    /**
     * Get all possible statuses
     */
    public static function allStatuses(): array
    {
        return [
            self::STATUS_SCHEDULED,
            self::STATUS_ARRIVED,
            self::STATUS_WAITING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_PENDING_APPROVAL,
            self::STATUS_REJECTED,
        ];
    }

    /**
     * Get statuses that require vendor action
     */
    public static function vendorActionStatuses(): array
    {
        return [];
    }

    /**
     * Get statuses that require admin action
     */
    public static function adminActionStatuses(): array
    {
        return [
            self::STATUS_PENDING_APPROVAL,
        ];
    }

    /**
     * Get active statuses (not completed or cancelled)
     */
    public static function activeStatuses(): array
    {
        return [
            self::STATUS_SCHEDULED,
            self::STATUS_ARRIVED,
            self::STATUS_WAITING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_PENDING_APPROVAL,
        ];
    }

    /**
     * Check if slot is pending approval
     */
    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    /**
     * Check if slot needs action
     */
    public function needsAction(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING_APPROVAL,
        ]);
    }

    /**
     * Get status label for display
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->status === 'rejected') {
            return 'Cancelled';
        }
        return match ($this->status) {
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_ARRIVED => 'Waiting',
            self::STATUS_WAITING => 'Waiting',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_PENDING_APPROVAL => 'Pending Approval',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    /**
     * Get status badge color for display
     */
    public function getStatusBadgeColorAttribute(): string
    {
        if ($this->status === 'rejected') {
            return 'dark';
        }
        return match ($this->status) {
            self::STATUS_SCHEDULED => 'secondary',
            self::STATUS_ARRIVED => 'warning',
            self::STATUS_WAITING => 'warning',
            self::STATUS_IN_PROGRESS => 'primary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'dark',
            self::STATUS_PENDING_APPROVAL => 'pending_approval',
            self::STATUS_REJECTED => 'danger',
            default => 'secondary',
        };
    }

    // Relationships
    
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function plannedGate()
    {
        return $this->belongsTo(Gate::class, 'planned_gate_id');
    }

    public function actualGate()
    {
        return $this->belongsTo(Gate::class, 'actual_gate_id');
    }

    public function originalPlannedGate()
    {
        return $this->belongsTo(Gate::class, 'original_planned_gate_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function bookingHistories()
    {
        return $this->hasMany(BookingHistory::class)->orderBy('created_at', 'desc');
    }

    public function poItems()
    {
        return $this->hasMany(SlotPoItem::class, 'slot_id');
    }

    /**
     * Scope for pending approval slots
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_PENDING_APPROVAL);
    }

    /**
     * Scope for vendor's own bookings
     */
    public function scopeRequestedBy($query, $userId)
    {
        return $query->where('requested_by', $userId);
    }
}
