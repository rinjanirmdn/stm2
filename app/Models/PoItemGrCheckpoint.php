<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoItemGrCheckpoint extends Model
{
    protected $fillable = [
        'po_number',
        'item_no',
        'sap_qty_gr_total_last',
        'updated_at',
    ];

    protected $casts = [
        'sap_qty_gr_total_last' => 'decimal:3',
        'updated_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Get or create checkpoint for PO+item
     */
    public static function getOrCreate(string $poNumber, string $itemNo): self
    {
        return static::query()
            ->where('po_number', $poNumber)
            ->where('item_no', $itemNo)
            ->first()
            ?? static::create([
                'po_number' => $poNumber,
                'item_no' => $itemNo,
                'sap_qty_gr_total_last' => 0,
                'updated_at' => now(),
            ]);
    }

    /**
     * Update checkpoint to new SAP QtyGRTotal
     */
    public function updateCheckpoint(float $newSapQtyGrTotal): void
    {
        $this->update([
            'sap_qty_gr_total_last' => $newSapQtyGrTotal,
            'updated_at' => now(),
        ]);
    }
}
