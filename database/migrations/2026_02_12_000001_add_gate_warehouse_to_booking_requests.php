<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('booking_requests', 'planned_gate_id')) {
                $table->unsignedBigInteger('planned_gate_id')->nullable()->after('planned_duration');
            }
            if (!Schema::hasColumn('booking_requests', 'warehouse_id')) {
                $table->unsignedBigInteger('warehouse_id')->nullable()->after('planned_gate_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            $table->dropColumn(['planned_gate_id', 'warehouse_id']);
        });
    }
};
