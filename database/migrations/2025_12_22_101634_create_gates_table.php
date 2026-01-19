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
            $row = DB::selectOne("select to_regclass('gates') as t");
            if ($row && ! empty($row->t)) {
                return;
            }
        } catch (\Throwable $e) {
            if (Schema::hasTable('gates')) {
                return;
            }
        }

        Schema::create('gates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->integer('gate_number');
            $table->string('name');
            $table->string('lane_group')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->unique(['warehouse_id', 'gate_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gates');
    }
};
