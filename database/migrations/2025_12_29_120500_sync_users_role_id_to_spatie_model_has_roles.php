<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Sync users.role_id (custom column) into Spatie pivot table model_has_roles
        // so Blade @can(...) and permission middleware work.

        $users = DB::table('md_users')
            ->select('id', 'role_id')
            ->whereNotNull('role_id')
            ->get();

        foreach ($users as $user) {
            $exists = DB::table('model_has_roles')
                ->where('role_id', $user->role_id)
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $user->id)
                ->exists();

            if (! $exists) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $user->role_id,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $user->id,
                ]);
            }
        }

        // Clear Spatie permission cache
        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        DB::table('model_has_roles')
            ->where('model_type', 'App\\Models\\User')
            ->delete();

        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
