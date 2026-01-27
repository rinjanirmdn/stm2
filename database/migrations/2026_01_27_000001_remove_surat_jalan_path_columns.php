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
        Schema::table('booking_requests', function (Blueprint $table) {
            $table->dropColumn('surat_jalan_path');
        });

        Schema::table('slots', function (Blueprint $table) {
            $table->dropColumn('surat_jalan_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            $table->string('surat_jalan_path', 255)->nullable();
        });

        Schema::table('slots', function (Blueprint $table) {
            $table->string('surat_jalan_path')->nullable();
        });
    }
};
