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
            if (Schema::hasColumn('slots', 'sj_start_number')) {
                $table->dropColumn('sj_start_number');
            }
            if (Schema::hasColumn('slots', 'sj_complete_number')) {
                $table->dropColumn('sj_complete_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            if (! Schema::hasColumn('slots', 'sj_start_number')) {
                $table->string('sj_start_number', 50)->nullable()->after('mat_doc');
            }
            if (! Schema::hasColumn('slots', 'sj_complete_number')) {
                $table->string('sj_complete_number', 50)->nullable()->after('sj_start_number');
            }
        });
    }
};
