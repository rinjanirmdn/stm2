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
            if (!Schema::hasColumn('slots', 'coa_path')) {
                $table->string('coa_path')->nullable();
            }
            if (!Schema::hasColumn('slots', 'surat_jalan_path')) {
                $table->string('surat_jalan_path')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            if (Schema::hasColumn('slots', 'coa_path')) {
                $table->dropColumn('coa_path');
            }
            if (Schema::hasColumn('slots', 'surat_jalan_path')) {
                $table->dropColumn('surat_jalan_path');
            }
        });
    }
};
