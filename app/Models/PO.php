<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\Slot;

class PO extends Model
{
    use HasFactory;

    protected $table = 'po'; // Explicitly define table name

    protected $fillable = [
        'po_number',
        'mat_doc',
        'truck_number',
        'truck_type',
        'direction',
        'bp_id',
        'warehouse_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relasi
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'bp_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function slots()
    {
        return $this->hasMany(Slot::class, 'po_id');
    }
}
