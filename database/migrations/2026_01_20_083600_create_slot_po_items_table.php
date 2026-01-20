<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('slot_po_items')) {
            return;
        }

        Schema::create('slot_po_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('slot_id');
            $table->string('po_number', 50);
            $table->string('item_no', 20);
            $table->string('material_code', 50)->nullable();
            $table->string('material_name', 255)->nullable();
            $table->string('uom', 20)->nullable();
            $table->decimal('qty_booked', 18, 3)->default(0);
            $table->timestamps();

            $table->foreign('slot_id')->references('id')->on('slots')->onDelete('cascade');
            $table->index(['po_number', 'item_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slot_po_items');
    }
};
