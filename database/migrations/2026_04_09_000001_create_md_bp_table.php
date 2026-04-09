<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('md_bp', function (Blueprint $table) {
            $table->id();
            $table->string('bp_code', 20)->unique()->comment('Kode BP (Vendor/Customer)');
            $table->string('bp_name', 200)->comment('Nama Vendor/Customer');
            $table->enum('bp_type', ['vendor', 'customer'])->default('vendor')->comment('Tipe BP');
            $table->string('npwp', 30)->nullable()->comment('NPWP');
            $table->string('address', 500)->nullable()->comment('Alamat');
            $table->string('city', 100)->nullable()->comment('Kota');
            $table->string('phone', 50)->nullable()->comment('Telepon');
            $table->string('email', 150)->nullable()->comment('Email');
            $table->string('pic_name', 100)->nullable()->comment('Nama PIC');
            $table->string('pic_phone', 50)->nullable()->comment('No HP PIC');
            $table->boolean('is_active')->default(true)->comment('Status aktif');
            $table->foreignId('created_by')->nullable()->constrained('md_users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('md_users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('md_bp');
    }
};
