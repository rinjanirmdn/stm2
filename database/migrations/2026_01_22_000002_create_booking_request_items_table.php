<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('booking_request_items')) {
            return;
        }

        Schema::create('booking_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_request_id');

            $table->string('po_number', 50);
            $table->string('item_no', 20);

            $table->string('material_code', 50)->nullable();
            $table->string('material_name', 255)->nullable();

            $table->decimal('qty_po', 18, 3)->nullable();
            $table->string('unit_po', 20)->nullable();
            $table->decimal('qty_gr_total', 18, 3)->nullable();

            $table->decimal('qty_requested', 18, 3)->default(0);

            $table->timestamps();

            $table->foreign('booking_request_id')->references('id')->on('booking_requests')->onDelete('cascade');
            $table->index(['po_number', 'item_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_request_items');
    }
};
