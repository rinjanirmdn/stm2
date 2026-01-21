<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Convert legacy status 'rejected' into 'cancelled'
        // Keep the reason in cancelled_reason if possible.
        DB::table('slots')
            ->where('status', 'rejected')
            ->update([
                'status' => 'cancelled',
                'cancelled_reason' => DB::raw("COALESCE(cancelled_reason, approval_notes, 'Rejected by admin')"),
                'cancelled_at' => DB::raw('COALESCE(cancelled_at, approved_at, NOW())'),
                'updated_at' => DB::raw('NOW()'),
            ]);
    }

    public function down(): void
    {
        // No down migration: we intentionally unify 'rejected' into 'cancelled'
    }
};
