<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('booking_requests')) {
            return;
        }

        Schema::create('booking_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 50)->nullable()->unique();

            $table->unsignedBigInteger('requested_by');

            $table->string('po_number', 50);
            $table->string('supplier_code', 50)->nullable();
            $table->string('supplier_name', 255)->nullable();
            $table->date('doc_date')->nullable();

            $table->enum('direction', ['inbound', 'outbound']);
            $table->dateTime('planned_start');
            $table->integer('planned_duration')->default(60);

            $table->string('truck_type', 50)->nullable();
            $table->string('vehicle_number', 50)->nullable();
            $table->string('driver_name', 50)->nullable();
            $table->string('driver_number', 50)->nullable();

            $table->text('notes')->nullable();

            $table->string('coa_path', 255)->nullable();
            $table->string('surat_jalan_path', 255)->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');

            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->text('approval_notes')->nullable();

            $table->unsignedBigInteger('converted_slot_id')->nullable();

            $table->timestamps();

            $table->foreign('requested_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');
            $table->foreign('converted_slot_id')->references('id')->on('slots');

            $table->index(['status', 'planned_start']);
            $table->index(['po_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_requests');
    }
};
