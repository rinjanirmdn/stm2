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

    public static function findByName(string $name, $guardName = null): self
    {
        $guardName = $guardName ?? config('auth.defaults.guard');

        $permission = static::query()
            ->where('perm_name', $name)
            ->where('perm_guard_name', $guardName)
            ->first();

        if (! $permission) {
            throw static::getPermissionDoesNotExistException($name, $guardName);
        }

        return $permission;
    }

    public static function findOrCreate(string $name, $guardName = null): self
    {
        $guardName = $guardName ?? config('auth.defaults.guard');

        return static::query()->firstOrCreate([
            'perm_name' => $name,
            'perm_guard_name' => $guardName,
        ]);
    }
}
