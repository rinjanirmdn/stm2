<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('slots') || !Schema::hasTable('po')) {
            return;
        }

        if (!Schema::hasColumn('slots', 'bp_id') || !Schema::hasColumn('slots', 'po_id')) {
            return;
        }

        if (!Schema::hasColumn('po', 'bp_id')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement(
                "UPDATE slots s\n" .
                "SET bp_id = p.bp_id\n" .
                "FROM po p\n" .
                "WHERE s.bp_id IS NULL\n" .
                "  AND s.po_id = p.id\n" .
                "  AND p.bp_id IS NOT NULL"
            );
        } else {
            DB::statement(
                "UPDATE slots s\n" .
                "JOIN po p ON p.id = s.po_id\n" .
                "SET s.bp_id = p.bp_id\n" .
                "WHERE s.bp_id IS NULL\n" .
                "  AND p.bp_id IS NOT NULL"
            );
        }
    }

    public function down(): void
    {
        // no-op
    }
};
