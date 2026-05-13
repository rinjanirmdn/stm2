<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 1. Add 'create', 'edit', 'delete' enum values to activity_logs_activity_type
     * 2. Update existing 'crud' rows to more specific types based on description
     * 3. Rename 'mat_doc' to 'sj_no' in slots table
     * 4. Rename 'mat_doc' to 'sj_no' in activity_logs table (if column exists)
     */
    public function up(): void
    {
        // --- 1. Add new enum values for activity_type (each must run OUTSIDE a transaction in Postgres) ---
        $newValues = ['create', 'edit', 'delete'];
        foreach ($newValues as $val) {
            try {
                // Check if value already exists first
                $exists = DB::selectOne("
                    SELECT EXISTS (
                        SELECT 1
                        FROM pg_type t
                        JOIN pg_enum e ON t.oid = e.enumtypid
                        WHERE t.typname = 'activity_logs_activity_type'
                          AND e.enumlabel = ?
                    ) AS e
                ", [$val]);

                if ($exists && !$exists->e) {
                    // Commit current transaction, add enum value, start new transaction
                    DB::commit();
                    DB::statement("ALTER TYPE activity_logs_activity_type ADD VALUE '{$val}'");
                    DB::beginTransaction();
                }
            } catch (\Throwable $e) {
                // Swallow: enum type may not exist or value may already exist
                try { DB::beginTransaction(); } catch (\Throwable $e2) {}
            }
        }

        // --- 2. Migrate existing 'crud' rows to specific types ---
        try {
            // Delete operations
            DB::table('activity_logs')
                ->where('activity_type', 'crud')
                ->where(function ($q) {
                    $q->where('description', 'ilike', '%deleted%');
                })
                ->update(['activity_type' => 'delete']);

            // Create operations
            DB::table('activity_logs')
                ->where('activity_type', 'crud')
                ->where(function ($q) {
                    $q->where('description', 'ilike', '%created%')
                      ->orWhere('description', 'ilike', '%submitted%')
                      ->orWhere('description', 'ilike', '%logged in%')
                      ->orWhere('description', 'ilike', '%logged out%');
                })
                ->update(['activity_type' => 'create']);

            // Any remaining 'crud' rows → default to 'edit'
            DB::table('activity_logs')
                ->where('activity_type', 'crud')
                ->update(['activity_type' => 'edit']);
        } catch (\Throwable $e) {
            // Swallow: data migration should not break deployment
        }

        // --- 3. Rename mat_doc → sj_no in slots table ---
        if (Schema::hasColumn('slots', 'mat_doc') && ! Schema::hasColumn('slots', 'sj_no')) {
            try {
                DB::statement('ALTER TABLE slots RENAME COLUMN mat_doc TO sj_no');
            } catch (\Throwable $e) {
                // Swallow
            }
        }

        // --- 4. Rename mat_doc → sj_no in activity_logs table ---
        if (Schema::hasColumn('activity_logs', 'mat_doc') && ! Schema::hasColumn('activity_logs', 'sj_no')) {
            try {
                DB::statement('ALTER TABLE activity_logs RENAME COLUMN mat_doc TO sj_no');
            } catch (\Throwable $e) {
                // Swallow
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename sj_no back to mat_doc in slots
        if (Schema::hasColumn('slots', 'sj_no') && ! Schema::hasColumn('slots', 'mat_doc')) {
            try {
                DB::statement('ALTER TABLE slots RENAME COLUMN sj_no TO mat_doc');
            } catch (\Throwable $e) {
                // Swallow
            }
        }

        // Rename sj_no back to mat_doc in activity_logs
        if (Schema::hasColumn('activity_logs', 'sj_no') && ! Schema::hasColumn('activity_logs', 'mat_doc')) {
            try {
                DB::statement('ALTER TABLE activity_logs RENAME COLUMN sj_no TO mat_doc');
            } catch (\Throwable $e) {
                // Swallow
            }
        }

        // Revert specific types back to 'crud'
        try {
            DB::table('activity_logs')
                ->whereIn('activity_type', ['create', 'edit', 'delete'])
                ->update(['activity_type' => 'crud']);
        } catch (\Throwable $e) {
            // Swallow
        }
    }
};
