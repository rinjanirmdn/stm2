<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $table = 'md_permissions';

    protected $fillable = [
        'perm_name',
        'perm_guard_name',
    ];

    public function getNameAttribute(): string
    {
        return (string) ($this->getAttributeValue('perm_name') ?? '');
    }

    public function setNameAttribute($value): void
    {
        $this->setAttribute('perm_name', $value);
    }

    public function getGuardNameAttribute(): string
    {
        return (string) ($this->getAttributeValue('perm_guard_name') ?? '');
    }

    public function setGuardNameAttribute($value): void
    {
        $this->setAttribute('perm_guard_name', $value);
    }

    public function scopeWhereName(Builder $query, string $name): Builder
    {
        return $query->where('perm_name', $name);
    }

    // findByName is intentionally NOT overridden here.
    // The parent Spatie method uses the PermissionRegistrar cache (in-memory),
    // which avoids a DB query on every @can / hasPermissionTo check.
    // The getNameAttribute/getGuardNameAttribute accessors handle the column mapping.

    public static function findOrCreate(string $name, $guardName = null): self
    {
        $guardName = $guardName ?? config('auth.defaults.guard');

        return static::query()->firstOrCreate([
            'perm_name' => $name,
            'perm_guard_name' => $guardName,
        ]);
    }
}
