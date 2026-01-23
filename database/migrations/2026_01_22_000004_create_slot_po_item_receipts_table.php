<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slot_po_item_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('slot_id');
            $table->string('po_number', 20);
            $table->string('item_no', 10);
            $table->decimal('qty_received', 12, 3)->default(0);
            $table->decimal('sap_qty_gr_total_after', 12, 3)->nullable()->comment('SAP QtyGRTotal snapshot after this slot completion');
            $table->timestamps();

            $table->foreign('slot_id')->references('id')->on('slots')->onDelete('cascade');
            $table->index(['slot_id']);
            $table->index(['po_number', 'item_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slot_po_item_receipts');
    }
};
