<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_item_gr_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 20);
            $table->string('item_no', 10);
            $table->decimal('sap_qty_gr_total_last', 12, 3)->default(0);
            $table->timestamp('updated_at')->nullable();

            $table->unique(['po_number', 'item_no']);
            $table->index(['po_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_item_gr_checkpoints');
    }
};
