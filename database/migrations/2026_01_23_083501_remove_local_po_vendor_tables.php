<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('slots')) {
            Schema::table('slots', function (Blueprint $table) {
                if (!Schema::hasColumn('slots', 'po_number')) {
                    $table->string('po_number', 50)->nullable()->after('direction');
                }
                if (!Schema::hasColumn('slots', 'vendor_code')) {
                    $table->string('vendor_code', 50)->nullable()->after('po_number');
                }
                if (!Schema::hasColumn('slots', 'vendor_name')) {
                    $table->string('vendor_name', 255)->nullable()->after('vendor_code');
                }
                if (!Schema::hasColumn('slots', 'vendor_type')) {
                    $table->string('vendor_type', 20)->nullable()->after('vendor_name');
                }
            });

            $driver = DB::getDriverName();
            if ($driver === 'pgsql') {
                DB::statement(
                    "UPDATE slots\n" .
                    "SET po_number = COALESCE(slots.po_number, src.po_number),\n" .
                    "    vendor_name = COALESCE(slots.vendor_name, src.vendor_name, src.po_vendor_name),\n" .
                    "    vendor_code = COALESCE(slots.vendor_code, src.vendor_code, src.po_vendor_code),\n" .
                    "    vendor_type = COALESCE(slots.vendor_type, src.vendor_type, src.po_vendor_type)\n" .
                    "FROM (\n" .
                    "    SELECT s.id, t.po_number,\n" .
                    "           v.bp_name AS vendor_name, v.bp_code AS vendor_code, v.bp_type::text AS vendor_type,\n" .
                    "           pv.bp_name AS po_vendor_name, pv.bp_code AS po_vendor_code, pv.bp_type::text AS po_vendor_type\n" .
                    "    FROM slots s\n" .
                    "    LEFT JOIN po t ON t.id = s.po_id\n" .
                    "    LEFT JOIN business_partner v ON v.id = s.bp_id\n" .
                    "    LEFT JOIN business_partner pv ON pv.id = t.bp_id\n" .
                    ") src\n" .
                    "WHERE slots.id = src.id"
                );
            } else {
                DB::statement(
                    "UPDATE slots s\n" .
                    "LEFT JOIN po t ON t.id = s.po_id\n" .
                    "LEFT JOIN business_partner v ON v.id = s.bp_id\n" .
                    "LEFT JOIN business_partner pv ON pv.id = t.bp_id\n" .
                    "SET s.po_number = COALESCE(s.po_number, t.po_number),\n" .
                    "    s.vendor_name = COALESCE(s.vendor_name, v.bp_name, pv.bp_name),\n" .
                    "    s.vendor_code = COALESCE(s.vendor_code, v.bp_code, pv.bp_code),\n" .
                    "    s.vendor_type = COALESCE(s.vendor_type, v.bp_type, pv.bp_type)"
                );
            }

            if ($driver === 'pgsql') {
                DB::statement("DO $$ BEGIN\n" .
                    "IF EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'slots_po_id_foreign') THEN\n" .
                    "    ALTER TABLE slots DROP CONSTRAINT slots_po_id_foreign;\n" .
                    "END IF;\n" .
                    "IF EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'slots_bp_id_foreign') THEN\n" .
                    "    ALTER TABLE slots DROP CONSTRAINT slots_bp_id_foreign;\n" .
                    "END IF;\n" .
                    "IF EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'slots_vendor_id_foreign') THEN\n" .
                    "    ALTER TABLE slots DROP CONSTRAINT slots_vendor_id_foreign;\n" .
                    "END IF;\n" .
                    "END $$;");
            } else {
                Schema::table('slots', function (Blueprint $table) {
                    if (Schema::hasColumn('slots', 'po_id')) {
                        $table->dropForeign(['po_id']);
                    }
                    if (Schema::hasColumn('slots', 'bp_id')) {
                        $table->dropForeign(['bp_id']);
                    }
                    if (Schema::hasColumn('slots', 'vendor_id')) {
                        $table->dropForeign(['vendor_id']);
                    }
                });
            }

            Schema::table('slots', function (Blueprint $table) {
                if (Schema::hasColumn('slots', 'po_id')) {
                    $table->dropColumn('po_id');
                }
                if (Schema::hasColumn('slots', 'bp_id')) {
                    $table->dropColumn('bp_id');
                }
                if (Schema::hasColumn('slots', 'vendor_id')) {
                    $table->dropColumn('vendor_id');
                }
            });
        }

        if (Schema::hasTable('users')) {
            if ($driver === 'pgsql') {
                DB::statement("DO $$ BEGIN\n" .
                    "IF EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'users_vendor_id_foreign') THEN\n" .
                    "    ALTER TABLE users DROP CONSTRAINT users_vendor_id_foreign;\n" .
                    "END IF;\n" .
                    "END $$;");
            }
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'vendor_id')) {
                    if (DB::getDriverName() !== 'pgsql') {
                        $table->dropForeign(['vendor_id']);
                    }
                    $table->dropColumn('vendor_id');
                }
            });
        }

        if (Schema::hasTable('po')) {
            Schema::drop('po');
        }

        if (Schema::hasTable('business_partner')) {
            Schema::drop('business_partner');
        }

        if (Schema::hasTable('vendors')) {
            Schema::drop('vendors');
        }
    }

    public function down(): void
    {
        // Irreversible cleanup: local PO/vendor tables removed.
    }
};
