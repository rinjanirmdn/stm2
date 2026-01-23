<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlotPoItemReceipt extends Model
{
    protected $fillable = [
        'slot_id',
        'po_number',
        'item_no',
        'qty_received',
        'sap_qty_gr_total_after',
    ];

    protected $casts = [
        'qty_received' => 'decimal:3',
        'sap_qty_gr_total_after' => 'decimal:3',
    ];

    /**
     * Relationship to slot
     */
    public function slot()
    {
        return $this->belongsTo(Slot::class);
    }
}
