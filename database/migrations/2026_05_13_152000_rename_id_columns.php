<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $renames = [
            'activity_logs' => 'id_activity_logs',
            'booking_histories' => 'id_booking_histories',
            'booking_requests' => 'id_booking_requests',
            'md_gates' => 'id_gates',
            'md_permissions' => 'id_permissions',
            'md_roles' => 'id_roles',
            'md_truck' => 'id_truck',
            'md_users' => 'id_users',
            'md_vendor_transporters' => 'id_vendor_transporters',
            'md_warehouse' => 'id_warehouse',
            'notifications' => 'id_notifications',
            'slot_photos' => 'id_slot_photos',
            'slots' => 'id_slots',
        ];

        foreach ($renames as $table => $newId) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'id')) {
                Schema::table($table, function (Blueprint $tableObj) use ($newId) {
                    $tableObj->renameColumn('id', $newId);
                });
            }
        }
    }

    public function down(): void
    {
        $renames = [
            'activity_logs' => 'id_activity_logs',
            'booking_histories' => 'id_booking_histories',
            'booking_requests' => 'id_booking_requests',
            'md_gates' => 'id_gates',
            'md_permissions' => 'id_permissions',
            'md_roles' => 'id_roles',
            'md_truck' => 'id_truck',
            'md_users' => 'id_users',
            'md_vendor_transporters' => 'id_vendor_transporters',
            'md_warehouse' => 'id_warehouse',
            'notifications' => 'id_notifications',
            'slot_photos' => 'id_slot_photos',
            'slots' => 'id_slots',
        ];

        foreach ($renames as $table => $newId) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $newId)) {
                Schema::table($table, function (Blueprint $tableObj) use ($newId) {
                    $tableObj->renameColumn($newId, 'id');
                });
            }
        }
    }
};
