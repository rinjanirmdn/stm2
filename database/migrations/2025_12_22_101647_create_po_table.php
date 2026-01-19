<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            $row = DB::selectOne("select exists (select 1 from information_schema.tables where table_name = 'po' and table_schema = any (current_schemas(true))) as e");
            if ($row && (isset($row->e) ? (bool) $row->e : false)) {
                return;
            }
        } catch (\Throwable $e) {
            // fallback
            if (Schema::hasTable('po')) {
                return;
            }
        }

        try {
            Schema::create('po', function (Blueprint $table) {
                $table->id();
                $table->string('po_number', 50);
                $table->string('mat_doc', 50)->nullable();
                $table->string('truck_number', 20);
                $table->string('truck_type', 100);
                $table->string('direction', 20);
                $table->unsignedBigInteger('bp_id')->nullable();
                $table->unsignedBigInteger('warehouse_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique('po_number');
                $table->foreign('bp_id')->references('id')->on('business_partner');
                $table->foreign('warehouse_id')->references('id')->on('warehouses');
            });
        } catch (\Throwable $e) {
            $msg = strtolower((string) $e->getMessage());
            if (str_contains($msg, 'already exists') || str_contains($msg, 'duplicate table') || str_contains($msg, '42p07')) {
                return;
            }
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('po');
    }
};
