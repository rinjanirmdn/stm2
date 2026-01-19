<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            if (!Schema::hasColumn('slots', 'target_duration_minutes')) {
                $table->integer('target_duration_minutes')->nullable();
            }
            if (!Schema::hasColumn('slots', 'actual_duration_minutes')) {
                $table->integer('actual_duration_minutes')->nullable();
            }
            if (!Schema::hasColumn('slots', 'planned_duration')) {
                $table->integer('planned_duration')->nullable();
            }
            if (!Schema::hasColumn('slots', 'lead_time_minutes')) {
                $table->integer('lead_time_minutes')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            //
        });
    }
};
