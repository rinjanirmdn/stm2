<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $table = 'business_partner';
    public $timestamps = false;

    protected $fillable = [
        'bp_code',
        'bp_name',
        'bp_type',
    ];

    protected $casts = [
    ];

    public function getNameAttribute()
    {
        return $this->bp_name;
    }

    public function getCodeAttribute()
    {
        return $this->bp_code;
    }
}
