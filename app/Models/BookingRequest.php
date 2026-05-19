<?php

namespace App\Models;

use App\Events\SlotDataChanged;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingRequest extends Model
{
    use HasFactory;

    protected $table = 'booking_requests';

    protected $primaryKey = 'id_booking_requests';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'request_number',
        'requested_by',
        'po_number',
        'supplier_code',
        'supplier_name',
        'doc_date',
        'direction',
        'planned_start',
        'planned_duration',
        'planned_gate_id',
        'warehouse_id',
        'truck_type',
        'vehicle_number',
        'driver_name',
        'driver_number',
        'notes',
        'status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'converted_slot_id',
    ];

    protected $casts = [
        'doc_date' => 'date',
        'planned_start' => 'datetime',
        'approved_at' => 'datetime',
        'planned_duration' => 'integer',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function convertedSlot()
    {
        return $this->belongsTo(Slot::class, 'converted_slot_id');
    }

    protected static function booted(): void
    {
        static::created(function ($model) {
            if (empty($model->request_number)) {
                $model->request_number = 'REQ-' . $model->id_booking_requests;
                $model->saveQuietly();
            }
            try {
                broadcast(new SlotDataChanged('booking', 'created', $model->id_booking_requests));
            } catch (\Throwable $e) {
            }
        });

        static::updated(function ($model) {
            try {
                broadcast(new SlotDataChanged('booking', 'updated', $model->id_booking_requests));
            } catch (\Throwable $e) {
            }
        });

        static::deleted(function ($model) {
            try {
                broadcast(new SlotDataChanged('booking', 'deleted', $model->id_booking_requests));
            } catch (\Throwable $e) {
            }
        });
    }
}
