<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;
    
    protected $table = 'activity_logs';
    protected $primaryKey = 'id_activity_logs';
    public $timestamps = false;

    protected $fillable = [
        'activity_type',
        'description',
        'feature',
        'old_value',
        'new_value',
        'slot_id',
        'booking_request_id',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'slot_id' => 'integer',
        'created_by' => 'integer',
        'booking_request_id' => 'integer',
    ];

    // Relasi
    public function slot()
    {
        return $this->belongsTo(Slot::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
