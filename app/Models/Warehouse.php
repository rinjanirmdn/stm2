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
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
