<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = \Illuminate\Support\Facades\DB::getDriverName();
        $hasConstraint = true;

        if ($driver === 'pgsql') {
            $hasConstraint = (bool) \Illuminate\Support\Facades\DB::selectOne("
                SELECT 1 FROM pg_constraint JOIN pg_class ON conrelid = pg_class.oid 
                WHERE pg_class.relname = 'users' AND conname = 'users_vendor_id_foreign'
            ");
        } elseif ($driver === 'mysql') {
            $dbName = \Illuminate\Support\Facades\DB::getDatabaseName();
            $hasConstraint = (bool) \Illuminate\Support\Facades\DB::selectOne("
                SELECT 1 FROM information_schema.table_constraints 
                WHERE table_schema = ? AND table_name = 'users' AND constraint_name = 'users_vendor_id_foreign'
            ", [$dbName]);
        }

        Schema::table('users', function (Blueprint $table) use ($hasConstraint) {
            // Drop constraint lama yang salah (asumsi nama constraint generated oleh Laravel)
            if ($hasConstraint) {
                try {
                    $table->dropForeign('users_vendor_id_foreign');
                } catch (\Throwable $e) {
                }
            }

            // Buat constraint baru ke business_partner (only if table exists)
            if (Schema::hasTable('business_partner')) {
                $table->foreign('vendor_id')
                    ->references('id')
                    ->on('business_partner')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        $driver = \Illuminate\Support\Facades\DB::getDriverName();
        $hasConstraint = true;

        if ($driver === 'pgsql') {
            $hasConstraint = (bool) \Illuminate\Support\Facades\DB::selectOne("
                SELECT 1 FROM pg_constraint JOIN pg_class ON conrelid = pg_class.oid 
                WHERE pg_class.relname = 'users' AND conname = 'users_vendor_id_foreign'
            ");
        } elseif ($driver === 'mysql') {
            $dbName = \Illuminate\Support\Facades\DB::getDatabaseName();
            $hasConstraint = (bool) \Illuminate\Support\Facades\DB::selectOne("
                SELECT 1 FROM information_schema.table_constraints 
                WHERE table_schema = ? AND table_name = 'users' AND constraint_name = 'users_vendor_id_foreign'
            ", [$dbName]);
        }

        Schema::table('users', function (Blueprint $table) use ($hasConstraint) {
            if ($hasConstraint) {
                try {
                    $table->dropForeign('users_vendor_id_foreign');
                } catch (\Throwable $e) {
                }
            }

            // Kembalikan ke vendors (meskipun salah, untuk rollback purpose)
            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->onDelete('set null');
        });
    }
};
