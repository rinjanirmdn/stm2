<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gate extends Model
{
    use HasFactory;

    protected $table = 'md_gates';

    protected $fillable = [
        'warehouse_id',
        'gate_number',
        'is_active',
        'is_backup',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_backup' => 'boolean',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Scope: only active gates (#42)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
