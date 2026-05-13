<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $table = 'md_users';

    protected $primaryKey = 'id_users';

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
            'password_changed_at' => 'datetime',
        ];
    }

    /**
     * Check if the user's password has expired (older than 90 days).
     */
    public function isPasswordExpired(): bool
    {
        if (! $this->password_changed_at) {
            return true;
        }

        return $this->password_changed_at->addDays(90)->isPast();
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
     * Get the vendor's company name with fallback logic.
     */
    public function getVendorCompanyNameAttribute(): string
    {
        if ($this->is_internal_vendor) {
            return strtoupper(trim((string) ($this->vendor_code ?? '')));
        }

        if (! empty($this->vendor_code)) {
            $companyName = trim((string) ($this->company_name ?? ''));

            if ($companyName === '') {
                $companyName = Cache::get('vendor_company_'.$this->vendor_code) ?? '';
            }

            if ($companyName === '') {
                $companyName = (string) (DB::table('slots')
                    ->where('vendor_code', $this->vendor_code)
                    ->whereNotNull('vendor_name')
                    ->where('vendor_name', '!=', '')
                    ->value('vendor_name') ?? '');

                if ($companyName !== '') {
                    Cache::put('vendor_company_'.$this->vendor_code, $companyName, now()->addDays(30));
                }
            }

            if ($companyName !== '') {
                return $companyName;
            }

            return $this->vendor_code;
        }

        return 'Vendor';
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

        // External vendor → use company_name field first, then cache, then slots fallback
        if (! empty($this->vendor_code) && ! $this->is_internal_vendor) {
            $companyName = $this->vendor_company_name;

            if ($companyName !== '' && $companyName !== $this->vendor_code) {
                return $name.' ('.$companyName.')';
            }
        }

        return $name;
    }

    /**
     * Get the entity's notifications.
     */
    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable')->latest();
    }

    /**
     * Get the entity's unread notifications.
     */
    public function unreadNotifications()
    {
        return $this->notifications()->whereNull('read_at');
    }
}
