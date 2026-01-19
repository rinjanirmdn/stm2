<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'description',
        'mat_doc',
        'po_number',
        'slot_id',
        'user_id',
    ];

    protected $casts = [
        'slot_id' => 'integer',
        'user_id' => 'integer',
    ];

    // Relasi
    public function slot()
    {
        return $this->belongsTo(Slot::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
