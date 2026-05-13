<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
 
class VendorTransporter extends Model
{
    use HasFactory, SoftDeletes;
 
    protected $table = 'md_vendor_transporters';
    protected $primaryKey = 'id_vendor_transporters';
 
    protected $fillable = [
        'name',
        'is_active',
    ];
 
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
