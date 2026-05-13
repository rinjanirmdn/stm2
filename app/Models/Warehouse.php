<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $table = 'md_warehouse';
    protected $primaryKey = 'id_wh';

    protected $fillable = [
        'wh_code',
        'wh_name',
    ];

    public function gates(): HasMany
    {
        return $this->hasMany(Gate::class);
    }
}
