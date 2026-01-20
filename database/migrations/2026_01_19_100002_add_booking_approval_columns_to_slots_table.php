<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add columns for vendor booking and approval workflow.
     */
    public function up(): void
    {
        // First, modify the status enum to include new statuses
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            // MySQL: Modify enum to include new statuses
            DB::statement("ALTER TABLE slots MODIFY COLUMN status ENUM('scheduled', 'arrived', 'waiting', 'in_progress', 'completed', 'cancelled', 'pending_approval', 'rejected', 'pending_vendor_confirmation') DEFAULT 'scheduled'");
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: Add new values to enum type
            DB::statement("ALTER TYPE slots_status ADD VALUE IF NOT EXISTS 'pending_approval'");
            DB::statement("ALTER TYPE slots_status ADD VALUE IF NOT EXISTS 'rejected'");
            DB::statement("ALTER TYPE slots_status ADD VALUE IF NOT EXISTS 'pending_vendor_confirmation'");
        }
        // SQLite doesn't enforce enum types, so no change needed

        Schema::table('slots', function (Blueprint $table) {
            // Vendor who requested the booking
            if (!Schema::hasColumn('slots', 'requested_by')) {
                $table->unsignedBigInteger('requested_by')->nullable()->after('created_by');
                $table->foreign('requested_by')->references('id')->on('users')->onDelete('set null');
            }
            
            // Admin who approved/rejected/rescheduled
            if (!Schema::hasColumn('slots', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('requested_by');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            }
            
            // Approval action: approved, rejected, rescheduled
            if (!Schema::hasColumn('slots', 'approval_action')) {
                $table->string('approval_action', 50)->nullable()->after('approved_by');
            }
            
            // Admin notes for approval/rejection
            if (!Schema::hasColumn('slots', 'approval_notes')) {
                $table->text('approval_notes')->nullable()->after('approval_action');
            }
            
            // Timestamp when vendor requested
            if (!Schema::hasColumn('slots', 'requested_at')) {
                $table->timestamp('requested_at')->nullable()->after('approval_notes');
            }
            
            // Timestamp when admin approved
            if (!Schema::hasColumn('slots', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('requested_at');
            }
            
            // Timestamp when vendor confirmed reschedule
            if (!Schema::hasColumn('slots', 'vendor_confirmed_at')) {
                $table->timestamp('vendor_confirmed_at')->nullable()->after('approved_at');
            }
            
            // Original requested schedule (before admin reschedule)
            if (!Schema::hasColumn('slots', 'original_planned_start')) {
                $table->timestamp('original_planned_start')->nullable()->after('vendor_confirmed_at');
            }
            
            if (!Schema::hasColumn('slots', 'original_planned_gate_id')) {
                $table->unsignedBigInteger('original_planned_gate_id')->nullable()->after('original_planned_start');
                $table->foreign('original_planned_gate_id')->references('id')->on('gates')->onDelete('set null');
            }
            
            // Add index for pending approvals query
            $table->index(['status', 'requested_at'], 'slots_pending_approval_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            // Drop foreign keys first
            if (Schema::hasColumn('slots', 'requested_by')) {
                $table->dropForeign(['requested_by']);
            }
            if (Schema::hasColumn('slots', 'approved_by')) {
                $table->dropForeign(['approved_by']);
            }
            if (Schema::hasColumn('slots', 'original_planned_gate_id')) {
                $table->dropForeign(['original_planned_gate_id']);
            }
            
            // Drop index
            $table->dropIndex('slots_pending_approval_idx');
            
            // Drop columns
            $columns = [
                'requested_by',
                'approved_by',
                'approval_action',
                'approval_notes',
                'requested_at',
                'approved_at',
                'vendor_confirmed_at',
                'original_planned_start',
                'original_planned_gate_id',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('slots', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
        
        // Revert status enum (MySQL only)
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE slots MODIFY COLUMN status ENUM('scheduled', 'arrived', 'waiting', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled'");
        }
    }
};
