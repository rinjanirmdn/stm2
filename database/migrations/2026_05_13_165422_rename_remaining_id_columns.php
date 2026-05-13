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
        Schema::table('activity_logs', function (Blueprint $table) {
            if (Schema::hasColumn('activity_logs', 'id')) {
                $table->renameColumn('id', 'id_activity_logs');
            }
            if (! Schema::hasColumn('activity_logs', 'booking_request_id')) {
                $table->integer('booking_request_id')->nullable();
            }
        });

        Schema::table('booking_histories', function (Blueprint $table) {
            if (Schema::hasColumn('booking_histories', 'id')) {
                $table->renameColumn('id', 'id_booking_histories');
            }
        });

        if (Schema::hasTable('md_warehouse')) {
            Schema::table('md_warehouse', function (Blueprint $table) {
                if (Schema::hasColumn('md_warehouse', 'id_warehouse')) {
                    $table->renameColumn('id_warehouse', 'id_wh');
                }
            });
        }

        if (Schema::hasTable('md_permissions')) {
            Schema::table('md_permissions', function (Blueprint $table) {
                if (Schema::hasColumn('md_permissions', 'id_permissions')) {
                    $table->renameColumn('id_permissions', 'id_permission');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            if (Schema::hasColumn('activity_logs', 'id_activity_logs')) {
                $table->renameColumn('id_activity_logs', 'id');
            }
            if (Schema::hasColumn('activity_logs', 'booking_request_id')) {
                $table->dropColumn('booking_request_id');
            }
        });

        Schema::table('booking_histories', function (Blueprint $table) {
            if (Schema::hasColumn('booking_histories', 'id_booking_histories')) {
                $table->renameColumn('id_booking_histories', 'id');
            }
        });

        if (Schema::hasTable('md_warehouse')) {
            Schema::table('md_warehouse', function (Blueprint $table) {
                if (Schema::hasColumn('md_warehouse', 'id_wh')) {
                    $table->renameColumn('id_wh', 'id_warehouse');
                }
            });
        }

        if (Schema::hasTable('md_permissions')) {
            Schema::table('md_permissions', function (Blueprint $table) {
                if (Schema::hasColumn('md_permissions', 'id_permission')) {
                    $table->renameColumn('id_permission', 'id_permissions');
                }
            });
        }
    }
};
