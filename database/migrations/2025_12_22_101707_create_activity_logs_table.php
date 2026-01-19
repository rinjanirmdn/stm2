<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            $row = DB::selectOne("select exists (select 1 from information_schema.tables where table_name = 'activity_logs' and table_schema = any (current_schemas(true))) as e");
            if ($row && (isset($row->e) ? (bool) $row->e : false)) {
                return;
            }
        } catch (\Throwable $e) {
            if (Schema::hasTable('activity_logs')) {
                return;
            }
        }

        try {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->string('type', 50); // slot_created, slot_arrived, etc
                $table->text('description');
                $table->string('mat_doc', 50)->nullable();
                $table->string('po_number', 12)->nullable();
                $table->unsignedBigInteger('slot_id')->nullable();
                $table->unsignedBigInteger('user_id');
                $table->timestamps();

                $table->foreign('slot_id')->references('id')->on('slots')->onDelete('set null');
                $table->foreign('user_id')->references('id')->on('users');
                $table->index(['type', 'created_at']);
            });
        } catch (\Throwable $e) {
            $msg = strtolower((string) $e->getMessage());
            if (str_contains($msg, 'already exists') || str_contains($msg, 'duplicate table') || str_contains($msg, '42p07')) {
                return;
            }
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
