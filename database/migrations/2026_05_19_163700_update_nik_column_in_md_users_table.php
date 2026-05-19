<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Clean up existing NIKs in the database
        $users = DB::table('md_users')->get();
        foreach ($users as $user) {
            $cleanNik = preg_replace('/\D/', '', $user->nik); // Keep only digits
            if (empty($cleanNik)) {
                $cleanNik = str_pad((string)$user->id_users, 8, '0', STR_PAD_LEFT);
            } else {
                $cleanNik = substr($cleanNik, -8); // Keep last 8 digits if longer
            }
            
            // Avoid duplicate NIKs during cleanup
            $exists = DB::table('md_users')
                ->where('nik', $cleanNik)
                ->where('id_users', '<>', $user->id_users)
                ->exists();
                
            if ($exists) {
                $cleanNik = str_pad((string)mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
            }

            DB::table('md_users')
                ->where('id_users', $user->id_users)
                ->update([
                    'nik' => $cleanNik,
                    'username' => $cleanNik
                ]);
        }

        // 2. Safely alter the NIK column to VARCHAR(8)
        Schema::table('md_users', function (Blueprint $table) {
            $table->string('nik', 8)->change();
            $table->string('username', 8)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('md_users', function (Blueprint $table) {
            $table->string('nik', 50)->change();
            $table->string('username', 50)->change();
        });
    }
};
