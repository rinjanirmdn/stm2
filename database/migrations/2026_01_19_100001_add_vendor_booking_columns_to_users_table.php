<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add vendor_id and email columns to users table for vendor role support.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add email column for vendor notifications
            if (!Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable()->after('full_name');
            }
            
            // Add vendor_id to link user account to vendor company
            if (!Schema::hasColumn('users', 'vendor_id')) {
                $table->unsignedBigInteger('vendor_id')->nullable()->after('role');
                $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'vendor_id')) {
                $table->dropForeign(['vendor_id']);
                $table->dropColumn('vendor_id');
            }
            
            if (Schema::hasColumn('users', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};
