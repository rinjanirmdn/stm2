<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('booking_request_items');
        Schema::dropIfExists('slot_po_items');
        Schema::dropIfExists('slot_po_item_receipts');
        Schema::dropIfExists('po_item_gr_checkpoints');
    }

    public function down(): void
    {
        if (!Schema::hasTable('booking_request_items')) {
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

        if (!Schema::hasTable('slot_po_items')) {
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

        if (!Schema::hasTable('slot_po_item_receipts')) {
            Schema::create('slot_po_item_receipts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('slot_id');
                $table->string('po_number', 50);
                $table->string('item_no', 20);
                $table->decimal('qty_received', 18, 3)->default(0);
                $table->decimal('sap_qty_gr_total_after', 18, 3)->default(0);
                $table->timestamps();
                $table->index(['po_number', 'item_no']);
                $table->foreign('slot_id')->references('id')->on('slots')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('po_item_gr_checkpoints')) {
            Schema::create('po_item_gr_checkpoints', function (Blueprint $table) {
                $table->id();
                $table->string('po_number', 50);
                $table->string('item_no', 20);
                $table->decimal('sap_qty_gr_total_last', 18, 3)->default(0);
                $table->timestamps();
                $table->index(['po_number', 'item_no']);
            });
        }
    }
};
