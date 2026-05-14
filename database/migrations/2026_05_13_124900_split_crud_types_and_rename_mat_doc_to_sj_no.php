<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 1. Ensure activity_logs.activity_type column exists (rename from 'type' if needed)
     * 2. Convert activity_type to VARCHAR (Postgres safety)
     * 3. Update existing 'crud' rows to more specific types
     * 4. Rename 'mat_doc' to 'sj_no'
     */
    public function up(): void
    {
        // --- 1. Handle activity_type column naming ---
        if (Schema::hasTable('activity_logs')) {
            // Rename 'type' to 'activity_type' if it exists and activity_type doesn't
            if (Schema::hasColumn('activity_logs', 'type') && !Schema::hasColumn('activity_logs', 'activity_type')) {
                try {
                    DB::statement('ALTER TABLE activity_logs RENAME COLUMN "type" TO activity_type');
                } catch (\Throwable $e) {
                    // Ignore if rename fails (e.g. column already exists somehow)
                }
            }
 
            // --- 2. Convert activity_type to VARCHAR if it's not already ---
            if (Schema::hasColumn('activity_logs', 'activity_type')) {
                try {
                    DB::statement('ALTER TABLE activity_logs ALTER COLUMN activity_type TYPE VARCHAR(50) USING activity_type::text');
                } catch (\Throwable $e) {
                    // Ignore
                }
            }
 
            // --- 3. Migrate existing 'crud' rows to specific types ---
            try {
                DB::table('activity_logs')
                    ->where('activity_type', 'crud')
                    ->where('description', 'ilike', '%deleted%')
                    ->update(['activity_type' => 'delete']);
 
                DB::table('activity_logs')
                    ->where('activity_type', 'crud')
                    ->where(function ($q) {
                        $q->where('description', 'ilike', '%created%')
                          ->orWhere('description', 'ilike', '%submitted%')
                          ->orWhere('description', 'ilike', '%logged in%')
                          ->orWhere('description', 'ilike', '%logged out%');
                    })
                    ->update(['activity_type' => 'create']);
 
                DB::table('activity_logs')
                    ->where('activity_type', 'crud')
                    ->update(['activity_type' => 'edit']);
            } catch (\Throwable $e) {
                // Ignore
            }
        }
 
        // --- 4. Rename mat_doc -> sj_no in slots table ---
        if (Schema::hasColumn('slots', 'mat_doc') && ! Schema::hasColumn('slots', 'sj_no')) {
            try {
                DB::statement('ALTER TABLE slots RENAME COLUMN mat_doc TO sj_no');
            } catch (\Throwable $e) {
                // Ignore
            }
        }
 
        // --- 5. Rename mat_doc -> sj_no in activity_logs table ---
        if (Schema::hasColumn('activity_logs', 'mat_doc') && ! Schema::hasColumn('activity_logs', 'sj_no')) {
            try {
                DB::statement('ALTER TABLE activity_logs RENAME COLUMN mat_doc TO sj_no');
            } catch (\Throwable $e) {
                // Ignore
            }
        }
    }
 
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('slots', 'sj_no') && ! Schema::hasColumn('slots', 'mat_doc')) {
            DB::statement('ALTER TABLE slots RENAME COLUMN sj_no TO mat_doc');
        }
        if (Schema::hasColumn('activity_logs', 'sj_no') && ! Schema::hasColumn('activity_logs', 'mat_doc')) {
            DB::statement('ALTER TABLE activity_logs RENAME COLUMN sj_no TO mat_doc');
        }
    }
};
