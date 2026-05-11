<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add soft delete (deleted_at) column to all master data tables.
     */
    public function up(): void
    {
        // Truck Types
        if (Schema::hasTable('md_truck') && ! Schema::hasColumn('md_truck', 'deleted_at')) {
            Schema::table('md_truck', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Vendor Transporters
        if (Schema::hasTable('md_vendor_transporters') && ! Schema::hasColumn('md_vendor_transporters', 'deleted_at')) {
            Schema::table('md_vendor_transporters', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Users
        if (Schema::hasTable('md_users') && ! Schema::hasColumn('md_users', 'deleted_at')) {
            Schema::table('md_users', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('md_truck') && Schema::hasColumn('md_truck', 'deleted_at')) {
            Schema::table('md_truck', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('md_vendor_transporters') && Schema::hasColumn('md_vendor_transporters', 'deleted_at')) {
            Schema::table('md_vendor_transporters', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('md_users') && Schema::hasColumn('md_users', 'deleted_at')) {
            Schema::table('md_users', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
