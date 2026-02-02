<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserRoleService
{
    /**
     * Assign role to user
     */
    public function assignRole(int $userId, string $role): bool
    {
        $roleId = $this->getRoleIdByName($role);
        if (!$roleId) {
            Log::error("Role not found: {$role}");
            return false;
        }

        try {
            DB::table('model_has_roles')->insert([
                'role_id' => $roleId,
                'model_type' => 'App\\Models\\User',
                'model_id' => $userId,
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to assign role {$role} to user {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove role from user
     */
    public function removeRole(int $userId): bool
    {
        try {
            $deleted = DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $userId)
                ->delete();

            return $deleted > 0;
        } catch (\Throwable $e) {
            Log::error("Failed to remove role from user {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user roles
     */
    public function getUserRoles(int $userId): \Illuminate\Support\Collection
    {
        return DB::table('model_has_roles as mhr')
            ->join('md_roles as r', 'mhr.role_id', '=', 'r.id')
            ->where('mhr.model_type', 'App\\Models\\User')
            ->where('mhr.model_id', $userId)
            ->select(['r.id', 'r.roles_name', 'r.roles_guard_name'])
            ->get();
    }

    /**
     * Get role ID by name
     */
    private function getRoleIdByName(string $role): ?int
    {
        $roleRecord = DB::table('md_roles')
            ->where('roles_name', $role)
            ->first();

        return $roleRecord ? (int) $roleRecord->id : null;
    }

    /**
     * Check if user has specific role
     */
    public function userHasRole(int $userId, string $role): bool
    {
        $roleId = $this->getRoleIdByName($role);
        if (!$roleId) {
            return false;
        }

        return DB::table('model_has_roles')
            ->where('model_type', 'App\\Models\\User')
            ->where('model_id', $userId)
            ->where('role_id', $roleId)
            ->exists();
    }

    /**
     * Get all available roles
     */
    public function getAllRoles(): \Illuminate\Support\Collection
    {
        return DB::table('md_roles')
            ->select(['id', 'roles_name', 'roles_guard_name'])
            ->orderBy('roles_name')
            ->get();
    }

    /**
     * Get users by role
     */
    public function getUsersByRole(string $role): \Illuminate\Support\Collection
    {
        $roleId = $this->getRoleIdByName($role);
        if (!$roleId) {
            return collect([]);
        }

        return DB::table('model_has_roles as mhr')
            ->join('md_users as u', 'mhr.model_id', '=', 'u.id')
            ->where('mhr.model_type', 'App\\Models\\User')
            ->where('mhr.role_id', $roleId)
            ->select(['u.id', 'u.nik', 'u.email', 'u.is_active'])
            ->orderBy('u.nik')
            ->get();
    }

    /**
     * Sync user roles (remove all existing and assign new ones)
     */
    public function syncUserRoles(int $userId, array $roles): bool
    {
        try {
            // Remove existing roles
            $this->removeRole($userId);

            // Assign new roles
            foreach ($roles as $role) {
                if (!$this->assignRole($userId, $role)) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to sync roles for user {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get role statistics
     */
    public function getRoleStatistics(): array
    {
        $roles = $this->getAllRoles();
        $statistics = [];

        foreach ($roles as $role) {
            $userCount = DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->where('role_id', $role->id)
                ->count();

            $statistics[] = [
                'role_id' => $role->id,
                'role_name' => $role->roles_name,
                'user_count' => $userCount,
            ];
        }

        return $statistics;
    }

    /**
     * Validate role name
     */
    public function validateRoleName(string $role): bool
    {
        return in_array($role, ['admin', 'user', 'manager', 'operator'], true);
    }

    /**
     * Check if user can be deactivated (not last admin)
     */
    public function canDeactivateUser(int $userId): bool
    {
        // Check if user is admin
        if (!$this->userHasRole($userId, 'admin')) {
            return true;
        }

        // Count other active admins
        $otherAdmins = DB::table('model_has_roles as mhr')
            ->join('md_users as u', 'mhr.model_id', '=', 'u.id')
            ->join('md_roles as r', 'mhr.role_id', '=', 'r.id')
            ->where('mhr.model_type', 'App\\Models\\User')
            ->where('r.roles_name', 'admin')
            ->where('u.is_active', 1)
            ->where('u.id', '<>', $userId)
            ->count();

        return $otherAdmins > 0;
    }

    /**
     * Get user permissions based on roles
     */
    public function getUserPermissions(int $userId): array
    {
        $roles = $this->getUserRoles($userId);
        $permissions = [];

        foreach ($roles as $role) {
            $rolePermissions = DB::table('md_role_has_permissions as rhp')
                ->join('md_permissions as p', 'rhp.permission_id', '=', 'p.id')
                ->where('rhp.role_id', $role->id)
                ->pluck('p.perm_name')
                ->toArray();

            $permissions = array_merge($permissions, $rolePermissions);
        }

        return array_unique($permissions);
    }

    /**
     * Check if user has specific permission
     */
    public function userHasPermission(int $userId, string $permission): bool
    {
        $permissions = $this->getUserPermissions($userId);
        return in_array($permission, $permissions);
    }

    /**
     * Get role assignment history
     */
    public function getRoleAssignmentHistory(int $userId, int $limit = 10): \Illuminate\Support\Collection
    {
        return DB::table('activity_logs as al')
            ->leftJoin('md_users as u', 'al.created_by', '=', 'u.id')
            ->where('al.subject_type', 'App\\Models\\User')
            ->where('al.subject_id', $userId)
            ->whereIn('al.activity_type', ['role_assigned', 'role_removed'])
            ->orderByDesc('al.created_at')
            ->limit($limit)
            ->select([
                'al.activity_type',
                'al.description',
                'al.created_at',
                'u.nik as created_by_nik'
            ])
            ->get();
    }
}
