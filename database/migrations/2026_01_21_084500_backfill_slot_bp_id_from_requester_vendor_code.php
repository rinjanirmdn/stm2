<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('slots') || !Schema::hasTable('users') || !Schema::hasTable('business_partner')) {
            return;
        }

        if (!Schema::hasColumn('slots', 'bp_id') || !Schema::hasColumn('slots', 'requested_by')) {
            return;
        }

        if (!Schema::hasColumn('users', 'vendor_code')) {
            return;
        }

        $driver = DB::getDriverName();

        // Fill slots.bp_id from requester user vendor_code -> business_partner.bp_code
        // Case-insensitive match.
        if ($driver === 'pgsql') {
            DB::statement(
                "UPDATE slots s\n" .
                "SET bp_id = bp.id\n" .
                "FROM users u\n" .
                "JOIN business_partner bp ON LOWER(bp.bp_code) = LOWER(u.vendor_code)\n" .
                "WHERE s.bp_id IS NULL\n" .
                "  AND s.requested_by IS NOT NULL\n" .
                "  AND u.id = s.requested_by\n" .
                "  AND u.vendor_code IS NOT NULL\n" .
                "  AND TRIM(u.vendor_code) <> ''"
            );
        } else {
            DB::statement(
                "UPDATE slots s\n" .
                "JOIN users u ON u.id = s.requested_by\n" .
                "JOIN business_partner bp ON LOWER(bp.bp_code) = LOWER(u.vendor_code)\n" .
                "SET s.bp_id = bp.id\n" .
                "WHERE s.bp_id IS NULL\n" .
                "  AND s.requested_by IS NOT NULL\n" .
                "  AND u.vendor_code IS NOT NULL\n" .
                "  AND TRIM(u.vendor_code) <> ''"
            );
        }
    }

    public function down(): void
    {
        // no-op
    }
};
