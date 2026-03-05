<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $table = 'md_warehouse';

    protected $fillable = [
        'wh_code',
        'wh_name',
    ];

    public function gates(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Gate::class);
    }
}
