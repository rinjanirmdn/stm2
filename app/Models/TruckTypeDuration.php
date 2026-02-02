<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TruckTypeDuration extends Model
{
    use HasFactory;

    protected $table = 'md_truck'; // Explicitly define table name

    protected $fillable = [
        'truck_type',
        'target_duration_minutes',
    ];

    protected $casts = [
        'target_duration_minutes' => 'integer',
    ];

    // Relasi (jika ada slots yang reference ini)
    public function slots()
    {
        return $this->hasMany(Slot::class, 'truck_type', 'truck_type');
    }
}
