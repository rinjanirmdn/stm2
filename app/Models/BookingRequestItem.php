<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingRequestItem extends Model
{
    use HasFactory;

    protected $table = 'booking_request_items';

    protected $fillable = [
        'booking_request_id',
        'po_number',
        'item_no',
        'material_code',
        'material_name',
        'qty_po',
        'unit_po',
        'qty_gr_total',
        'qty_requested',
    ];

    protected $casts = [
        'qty_po' => 'decimal:3',
        'qty_gr_total' => 'decimal:3',
        'qty_requested' => 'decimal:3',
    ];

    public function bookingRequest()
    {
        return $this->belongsTo(BookingRequest::class, 'booking_request_id');
    }
}
