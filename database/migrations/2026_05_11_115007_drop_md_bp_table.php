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
        Schema::dropIfExists('md_bp');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('md_bp', function (Blueprint $table) {
            $table->id();
            $table->string('bp_code')->nullable()->unique();
            $table->string('bp_name');
            $table->string('bp_type')->default('vendor');
            $table->string('contact_person')->nullable();
            $table->string('phone_number')->nullable();
            $table->text('address')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }
};
