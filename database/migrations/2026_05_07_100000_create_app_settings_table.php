<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Seed default template version
        DB::table('app_settings')->insert([
            ['key' => 'import_template_version', 'value' => 'v1.0', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'import_template_hash', 'value' => '', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
