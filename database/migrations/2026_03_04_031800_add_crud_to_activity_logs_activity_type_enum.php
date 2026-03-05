<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Postgres enum: add value 'crud' if it doesn't already exist
        try {
            DB::unprepared(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_type t
        JOIN pg_enum e ON t.oid = e.enumtypid
        WHERE t.typname = 'activity_logs_activity_type'
          AND e.enumlabel = 'crud'
    ) THEN
        ALTER TYPE activity_logs_activity_type ADD VALUE 'crud';
    END IF;
END
$$;
SQL);
        } catch (\Throwable $e) {
            // Intentionally swallow: migration should not hard-fail if enum/type differs between envs.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Removing enum values in Postgres is non-trivial and unsafe; no-op.
    }
};
