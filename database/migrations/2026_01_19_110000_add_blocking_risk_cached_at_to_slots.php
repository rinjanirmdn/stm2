<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add blocking_risk_cached_at column if it doesn't exist
        if (Schema::hasTable('slots') && !Schema::hasColumn('slots', 'blocking_risk_cached_at')) {
            Schema::table('slots', function (Blueprint $table) {
                $table->timestamp('blocking_risk_cached_at')->nullable()->after('blocking_risk');
            });
        }
        
        // Add index for blocking_risk for faster filtering
        if (Schema::hasTable('slots') && Schema::hasColumn('slots', 'blocking_risk')) {
            try {
                Schema::table('slots', function (Blueprint $table) {
                    $table->index('blocking_risk', 'slots_blocking_risk_index');
                });
            } catch (\Throwable $e) {
                // Index might already exist
            }
        }
        
        // Add index for ticket_number for faster lookups
        if (Schema::hasTable('slots') && Schema::hasColumn('slots', 'ticket_number')) {
            try {
                Schema::table('slots', function (Blueprint $table) {
                    $table->index('ticket_number', 'slots_ticket_number_index');
                });
            } catch (\Throwable $e) {
                // Index might already exist
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('slots')) {
            Schema::table('slots', function (Blueprint $table) {
                if (Schema::hasColumn('slots', 'blocking_risk_cached_at')) {
                    $table->dropColumn('blocking_risk_cached_at');
                }
                
                try {
                    $table->dropIndex('slots_blocking_risk_index');
                } catch (\Throwable $e) {}
                
                try {
                    $table->dropIndex('slots_ticket_number_index');
                } catch (\Throwable $e) {}
            });
        }
    }
};
