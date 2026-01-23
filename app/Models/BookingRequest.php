<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingRequest extends Model
{
    use HasFactory;

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
        'truck_type',
        'vehicle_number',
        'driver_name',
        'driver_number',
        'notes',
        'coa_path',
        'surat_jalan_path',
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

    public function items()
    {
        return $this->hasMany(BookingRequestItem::class, 'booking_request_id');
    }
}
