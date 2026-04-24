<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slot_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('slot_id')->index();
            $table->string('phase', 20); // 'start' or 'complete'
            $table->string('filename', 255);
            $table->string('mime_type', 50)->default('image/jpeg');
            $table->binary('photo_data'); // bytea in PostgreSQL
            $table->timestamp('created_at')->useCurrent();

            $table->index(['slot_id', 'phase']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slot_photos');
    }
};
