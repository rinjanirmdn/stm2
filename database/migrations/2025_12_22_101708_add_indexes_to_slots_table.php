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
        if (! Schema::hasTable('slots')) {
            return;
        }

        $has = fn (string $col) => Schema::hasColumn('slots', $col);

        $createIndex = function (string $name, string $sql) {
            try {
                DB::statement($sql);
            } catch (\Throwable $e) {
                $msg = strtolower((string) $e->getMessage());
                if (str_contains($msg, 'already exists') || str_contains($msg, 'duplicate') || str_contains($msg, '42p07')) {
                    return;
                }
                throw $e;
            }
        };

        // Indexes for frequently queried columns (guarded)
        if ($has('actual_gate_id') && $has('status')) {
            $createIndex('slots_actual_gate_status_index', 'CREATE INDEX IF NOT EXISTS slots_actual_gate_status_index ON slots (actual_gate_id, status)');
        }
        if ($has('vendor_id')) {
            $createIndex('slots_vendor_id_index', 'CREATE INDEX IF NOT EXISTS slots_vendor_id_index ON slots (vendor_id)');
        }
        if ($has('po_id')) {
            $createIndex('slots_po_id_index', 'CREATE INDEX IF NOT EXISTS slots_po_id_index ON slots (po_id)');
        }
        if ($has('slot_type')) {
            $createIndex('slots_slot_type_index', 'CREATE INDEX IF NOT EXISTS slots_slot_type_index ON slots (slot_type)');
        }
        if ($has('mat_doc')) {
            $createIndex('slots_mat_doc_index', 'CREATE INDEX IF NOT EXISTS slots_mat_doc_index ON slots (mat_doc)');
        }
        if ($has('sj_number')) {
            $createIndex('slots_sj_number_index', 'CREATE INDEX IF NOT EXISTS slots_sj_number_index ON slots (sj_number)');
        }
        if ($has('created_by')) {
            $createIndex('slots_created_by_index', 'CREATE INDEX IF NOT EXISTS slots_created_by_index ON slots (created_by)');
        }

        // Composite indexes (guarded)
        if ($has('status') && $has('warehouse_id') && $has('planned_start')) {
            $createIndex('slots_status_warehouse_planned_start_index', 'CREATE INDEX IF NOT EXISTS slots_status_warehouse_planned_start_index ON slots (status, warehouse_id, planned_start)');
        }
        if ($has('planned_gate_id') && $has('planned_start') && $has('status')) {
            $createIndex('slots_gate_start_status_index', 'CREATE INDEX IF NOT EXISTS slots_gate_start_status_index ON slots (planned_gate_id, planned_start, status)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            try { $table->dropIndex('slots_actual_gate_status_index'); } catch (\Throwable $e) {}
            try { $table->dropIndex('slots_vendor_id_index'); } catch (\Throwable $e) {}
            try { $table->dropIndex('slots_po_id_index'); } catch (\Throwable $e) {}
            try { $table->dropIndex('slots_slot_type_index'); } catch (\Throwable $e) {}
            try { $table->dropIndex('slots_mat_doc_index'); } catch (\Throwable $e) {}
            try { $table->dropIndex('slots_sj_number_index'); } catch (\Throwable $e) {}
            try { $table->dropIndex('slots_created_by_index'); } catch (\Throwable $e) {}
            try { $table->dropIndex('slots_status_warehouse_planned_start_index'); } catch (\Throwable $e) {}
            try { $table->dropIndex('slots_gate_start_status_index'); } catch (\Throwable $e) {}
        });
    }
};
