<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $table = 'md_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'nik',
        'username',
        'email',
        'vendor_code',
        'is_internal_vendor',
        'password',
        'is_active',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_internal_vendor' => 'boolean',
        ];
    }

    // Accessor and Mutator for 'name' to map to 'full_name'
    public function getNameAttribute()
    {
        return $this->attributes['full_name'] ?? null;
    }

    public function setNameAttribute($value)
    {
        $this->attributes['full_name'] = $value;
    }

    /**
     * Check if user is a vendor
     */
    public function isVendor(): bool
    {
        return ! empty($this->vendor_code) || $this->is_internal_vendor;
    }

    /**
     * Check if user is an internal vendor (bypasses PO filtering)
     */
    public function isInternalVendor(): bool
    {
        return $this->is_internal_vendor === true;
    }

    /**
     * Get the vendor associated with this user
     */
    public function requestedBookings(): HasMany
    {
        return $this->hasMany(Slot::class, 'requested_by');
    }

    /**
     * Display name with department/company context.
     * Internal vendor: "Full Name (DEPARTMENT)"
     * External vendor: "Full Name (Company Name)"
     * Others: "Full Name"
     */
    public function getDisplayNameAttribute(): string
    {
        $name = trim((string) ($this->full_name ?? ''));
        if ($name === '') {
            $name = 'User';
        }

        // Internal vendor → append division from vendor_code
        if ($this->is_internal_vendor && ! empty($this->vendor_code)) {
            return $name.' ('.strtoupper(trim($this->vendor_code)).')';
        }

        // External vendor → get company name from cache, fallback to slots table
        if (! empty($this->vendor_code) && ! $this->is_internal_vendor) {
            $cacheKey = 'vendor_company_'.$this->vendor_code;
            $companyName = Cache::get($cacheKey);

            // Fallback: lookup from slots table if cache is empty
            if (! $companyName) {
                $companyName = DB::table('slots')
                    ->where('vendor_code', $this->vendor_code)
                    ->whereNotNull('vendor_name')
                    ->where('vendor_name', '!=', '')
                    ->value('vendor_name');

                if ($companyName) {
                    Cache::put($cacheKey, $companyName, now()->addDays(30));
                }
            }

            if ($companyName) {
                return $name.' ('.$companyName.')';
            }
        }

        return $name;
    }
}
