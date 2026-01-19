<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slot extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'mat_doc',
        'sj_start_number',
        'sj_complete_number',
        'truck_type',
        'vehicle_number_snap',
        'driver_number',
        'direction',
        'po_id',
        'warehouse_id',
        'bp_id',
        'planned_gate_id',
        'actual_gate_id',
        'status',
        'slot_type',
        'planned_start',
        'arrival_time',
        'actual_start',
        'actual_finish',
        'planned_duration',
        'is_late',
        'late_reason',
        'cancelled_reason',
        'cancelled_at',
        'moved_gate',
        'blocking_risk',
        'created_by',
    ];

    protected $casts = [
        'planned_start' => 'datetime',
        'arrival_time' => 'datetime',
        'actual_start' => 'datetime',
        'actual_finish' => 'datetime',
        'cancelled_at' => 'datetime',
        'is_late' => 'boolean',
        'moved_gate' => 'boolean',
        'blocking_risk' => 'integer',
    ];

    // Relasi yang bisa ditambahkan
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'bp_id');
    }

    public function plannedGate()
    {
        return $this->belongsTo(Gate::class, 'planned_gate_id');
    }

    public function actualGate()
    {
        return $this->belongsTo(Gate::class, 'actual_gate_id');
    }

    public function po()
    {
        return $this->belongsTo(PO::class, 'po_id');
    }
}
