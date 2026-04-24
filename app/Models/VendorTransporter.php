<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorTransporter extends Model
{
    protected $table = 'md_vendor_transporters';

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
