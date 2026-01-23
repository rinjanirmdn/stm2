<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('slots')) {
            return;
        }

        DB::table('slots')
            ->where('status', 'pending_vendor_confirmation')
            ->update([
                'status' => 'scheduled',
                'updated_at' => DB::raw('NOW()'),
            ]);

        if (Schema::hasColumn('slots', 'vendor_confirmed_at')) {
            Schema::table('slots', function (Blueprint $table) {
                $table->dropColumn('vendor_confirmed_at');
            });
        }

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE slots MODIFY COLUMN status ENUM('scheduled', 'arrived', 'waiting', 'in_progress', 'completed', 'cancelled', 'pending_approval', 'rejected') DEFAULT 'scheduled'");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('slots')) {
            return;
        }

        if (!Schema::hasColumn('slots', 'vendor_confirmed_at')) {
            Schema::table('slots', function (Blueprint $table) {
                $table->timestamp('vendor_confirmed_at')->nullable()->after('approved_at');
            });
        }

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE slots MODIFY COLUMN status ENUM('scheduled', 'arrived', 'waiting', 'in_progress', 'completed', 'cancelled', 'pending_approval', 'rejected', 'pending_vendor_confirmation') DEFAULT 'scheduled'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TYPE slots_status ADD VALUE IF NOT EXISTS 'pending_vendor_confirmation'");
        }
    }
};
