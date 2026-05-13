<?php
 
namespace database\migrations;
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 1. Ensure activity_logs.activity_type is a string (VARCHAR) to avoid ENUM constraints in Postgres
     * 2. Update existing 'crud' rows to more specific types based on description
     * 3. Rename 'mat_doc' to 'sj_no' in slots and activity_logs tables
     */
    public function up(): void
    {
        // --- 1. Convert activity_type to VARCHAR if it's not already ---
        // This avoids issues with ALTER TYPE ADD VALUE which can't run in transactions easily
        try {
            DB::statement('ALTER TABLE activity_logs ALTER COLUMN activity_type TYPE VARCHAR(50) USING activity_type::text');
        } catch (\Throwable $e) {
            // If table doesn't exist yet or column is already varchar, ignore
        }
 
        // --- 2. Migrate existing 'crud' rows to specific types ---
        if (Schema::hasTable('activity_logs')) {
            try {
                // Delete operations
                DB::table('activity_logs')
                    ->where('activity_type', 'crud')
                    ->where('description', 'ilike', '%deleted%')
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
 
                // Any remaining 'crud' rows -> default to 'edit'
                DB::table('activity_logs')
                    ->where('activity_type', 'crud')
                    ->update(['activity_type' => 'edit']);
            } catch (\Throwable $e) {
                // Ignore data migration errors
            }
        }
 
        // --- 3. Rename mat_doc -> sj_no in slots table ---
        if (Schema::hasColumn('slots', 'mat_doc') && ! Schema::hasColumn('slots', 'sj_no')) {
            try {
                DB::statement('ALTER TABLE slots RENAME COLUMN mat_doc TO sj_no');
            } catch (\Throwable $e) {
                // Ignore
            }
        }
 
        // --- 4. Rename mat_doc -> sj_no in activity_logs table ---
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
        // Revert names
        if (Schema::hasColumn('slots', 'sj_no') && ! Schema::hasColumn('slots', 'mat_doc')) {
            DB::statement('ALTER TABLE slots RENAME COLUMN sj_no TO mat_doc');
        }
        if (Schema::hasColumn('activity_logs', 'sj_no') && ! Schema::hasColumn('activity_logs', 'mat_doc')) {
            DB::statement('ALTER TABLE activity_logs RENAME COLUMN sj_no TO mat_doc');
        }
    }
};
