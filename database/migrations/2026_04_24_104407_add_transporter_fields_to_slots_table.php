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
        Schema::table('slots', function (Blueprint $table) {
            $table->string('transporter_type', 50)->nullable(); // 'internal' or 'vendor'
            $table->unsignedBigInteger('vendor_transporter_id')->nullable();

            $table->foreign('vendor_transporter_id')->references('id')->on('md_vendor_transporters')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->dropForeign(['vendor_transporter_id']);
            $table->dropColumn(['vendor_transporter_id', 'transporter_type']);
        });
    }
};
