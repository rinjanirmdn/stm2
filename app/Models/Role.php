<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $table = 'roles';

    protected $fillable = [
        'roles_name',
        'roles_guard_name',
    ];

    public function getNameAttribute(): string
    {
        return (string) ($this->getAttributeValue('roles_name') ?? '');
    }

    public function setNameAttribute($value): void
    {
        $this->setAttribute('roles_name', $value);
    }

    public function getGuardNameAttribute(): string
    {
        return (string) ($this->getAttributeValue('roles_guard_name') ?? '');
    }

    public function setGuardNameAttribute($value): void
    {
        $this->setAttribute('roles_guard_name', $value);
    }

    public function scopeWhereName(Builder $query, string $name): Builder
    {
        return $query->where('roles_name', $name);
    }

    public static function findByName(string $name, $guardName = null): self
    {
        $guardName = $guardName ?? config('auth.defaults.guard');

        $role = static::query()
            ->where('roles_name', $name)
            ->where('roles_guard_name', $guardName)
            ->first();

        if (! $role) {
            throw static::getRoleDoesNotExistException($name, $guardName);
        }

        return $role;
    }

    public static function findOrCreate(string $name, $guardName = null): self
    {
        $guardName = $guardName ?? config('auth.defaults.guard');

        return static::query()->firstOrCreate([
            'roles_name' => $name,
            'roles_guard_name' => $guardName,
        ]);
    }
}
