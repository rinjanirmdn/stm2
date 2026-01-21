<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('slots') || !Schema::hasTable('users')) {
            return;
        }

        if (!Schema::hasColumn('slots', 'bp_id') || !Schema::hasColumn('slots', 'requested_by')) {
            return;
        }

        if (!Schema::hasColumn('users', 'vendor_id')) {
            return;
        }

        // Fill slots.bp_id from requester user vendor_id for vendor booking requests
        // This fixes legacy rows where bp_id was left null.
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement(
                "UPDATE slots s\n" .
                "SET bp_id = u.vendor_id\n" .
                "FROM users u\n" .
                "WHERE s.bp_id IS NULL\n" .
                "  AND s.requested_by IS NOT NULL\n" .
                "  AND u.id = s.requested_by\n" .
                "  AND u.vendor_id IS NOT NULL"
            );
        } else {
            DB::statement(
                "UPDATE slots s\n" .
                "JOIN users u ON u.id = s.requested_by\n" .
                "SET s.bp_id = u.vendor_id\n" .
                "WHERE s.bp_id IS NULL\n" .
                "  AND s.requested_by IS NOT NULL\n" .
                "  AND u.vendor_id IS NOT NULL"
            );
        }
    }

    public function down(): void
    {
        // no-op
    }
};
