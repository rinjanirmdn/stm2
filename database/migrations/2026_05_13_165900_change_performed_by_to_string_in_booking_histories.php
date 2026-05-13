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
        Schema::table('booking_histories', function (Blueprint $table) {
            $table->dropForeign(['performed_by']);
            $table->string('performed_by', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_histories', function (Blueprint $table) {
            $table->integer('performed_by')->nullable()->change();
            $table->foreign('performed_by')->references('id_users')->on('md_users');
        });
    }
};
