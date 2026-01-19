<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('truck_type_durations')) {
            return;
        }

        Schema::create('truck_type_durations', function (Blueprint $table) {
            $table->id();
            $table->string('truck_type', 100)->unique();
            $table->unsignedInteger('target_duration_minutes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('truck_type_durations');
    }
};
