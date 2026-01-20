<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop constraint lama yang salah (asumsi nama constraint generated oleh Laravel)
            $table->dropForeign(['vendor_id']);
            
            // Buat constraint baru ke business_partner
            $table->foreign('vendor_id')
                  ->references('id')
                  ->on('business_partner')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            // Kembalikan ke vendors (meskipun salah, untuk rollback purpose)
            $table->foreign('vendor_id')
                  ->references('id')
                  ->on('vendors')
                  ->onDelete('set null');
        });
    }
};
