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
            if (Schema::hasColumn('slots', 'po_number')) {
                $table->dropColumn('po_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            if (! Schema::hasColumn('slots', 'po_number')) {
                $table->string('po_number', 12)->nullable()->after('mat_doc');
            }
        });
    }
};
