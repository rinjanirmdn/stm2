<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlotPoItem extends Model
{
    use HasFactory;

    protected $table = 'slot_po_items';

    protected $fillable = [
        'slot_id',
        'po_number',
        'item_no',
        'material_code',
        'material_name',
        'uom',
        'qty_booked',
    ];

    protected $casts = [
        'qty_booked' => 'decimal:3',
    ];

    public function slot()
    {
        return $this->belongsTo(Slot::class);
    }
}
