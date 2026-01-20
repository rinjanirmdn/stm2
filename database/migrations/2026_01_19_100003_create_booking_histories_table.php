<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Create booking_histories table for audit trail of booking approval flow.
     */
    public function up(): void
    {
        Schema::create('booking_histories', function (Blueprint $table) {
            $table->id();
            
            // Reference to the slot
            $table->unsignedBigInteger('slot_id');
            $table->foreign('slot_id')->references('id')->on('slots')->onDelete('cascade');
            
            // Action type: requested, approved, rejected, rescheduled, vendor_confirmed, vendor_rejected, vendor_proposed
            $table->string('action', 50);
            
            // Who performed the action
            $table->unsignedBigInteger('performed_by');
            $table->foreign('performed_by')->references('id')->on('users')->onDelete('cascade');
            
            // Notes/reason for the action
            $table->text('notes')->nullable();
            
            // Status tracking
            $table->string('old_status', 50)->nullable();
            $table->string('new_status', 50);
            
            // Schedule tracking (for rescheduling)
            $table->timestamp('old_planned_start')->nullable();
            $table->timestamp('new_planned_start')->nullable();
            $table->integer('old_planned_duration')->nullable();
            $table->integer('new_planned_duration')->nullable();
            
            // Gate tracking (for gate changes)
            $table->unsignedBigInteger('old_gate_id')->nullable();
            $table->unsignedBigInteger('new_gate_id')->nullable();
            $table->foreign('old_gate_id')->references('id')->on('gates')->onDelete('set null');
            $table->foreign('new_gate_id')->references('id')->on('gates')->onDelete('set null');
            
            $table->timestamps();
            
            // Indexes for common queries
            $table->index(['slot_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index('performed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_histories');
    }
};
