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
            $row = DB::selectOne("select exists (select 1 from information_schema.tables where table_name = 'slots' and table_schema = any (current_schemas(true))) as e");
            if ($row && (isset($row->e) ? (bool) $row->e : false)) {
                return;
            }
        } catch (\Throwable $e) {
            if (Schema::hasTable('slots')) {
                return;
            }
        }

        try {
            Schema::create('slots', function (Blueprint $table) {
                $table->id();
                $table->string('ticket_number', 50)->nullable()->unique();
                $table->string('mat_doc', 50)->nullable();
                $table->string('sj_start_number', 50)->nullable();
                $table->string('sj_complete_number', 50)->nullable();
                $table->string('truck_type', 50)->nullable();
                $table->string('vehicle_number_snap', 50)->nullable();
                $table->string('driver_number', 50)->nullable();
                $table->enum('direction', ['inbound', 'outbound']);
                $table->unsignedBigInteger('po_id')->nullable();
                $table->unsignedBigInteger('warehouse_id');
                $table->unsignedBigInteger('vendor_id')->nullable();
                $table->unsignedBigInteger('planned_gate_id')->nullable();
                $table->unsignedBigInteger('actual_gate_id')->nullable();
                $table->datetime('planned_start');
                $table->datetime('arrival_time')->nullable();
                $table->datetime('actual_start')->nullable();
                $table->datetime('actual_finish')->nullable();
                $table->integer('planned_duration')->default(60);
                $table->enum('status', ['scheduled', 'arrived', 'waiting', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
                $table->boolean('is_late')->default(false);
                $table->text('late_reason')->nullable();
                $table->text('cancelled_reason')->nullable();
                $table->datetime('cancelled_at')->nullable();
                $table->boolean('moved_gate')->default(false);
                $table->tinyInteger('blocking_risk')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->enum('slot_type', ['planned', 'unplanned'])->default('planned');
                $table->timestamps();

                $table->foreign('po_id')->references('id')->on('po');
                $table->foreign('warehouse_id')->references('id')->on('warehouses');
                $table->foreign('vendor_id')->references('id')->on('vendors');
                $table->foreign('planned_gate_id')->references('id')->on('gates');
                $table->foreign('actual_gate_id')->references('id')->on('gates');
                $table->foreign('created_by')->references('id')->on('users');
                $table->index(['warehouse_id', 'planned_gate_id', 'planned_start']);
                $table->index(['status', 'planned_start']);
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
        Schema::dropIfExists('slots');
    }
};
