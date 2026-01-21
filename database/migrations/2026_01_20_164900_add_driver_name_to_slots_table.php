<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            if (!Schema::hasColumn('slots', 'driver_name')) {
                $table->string('driver_name', 50)->nullable()->after('vehicle_number_snap');
            }
        });
    }

    public function down(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            if (Schema::hasColumn('slots', 'driver_name')) {
                $table->dropColumn('driver_name');
            }
        });
    }
};
