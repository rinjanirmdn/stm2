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
        // Get current role IDs
        $adminRoleId = DB::table('roles')->where('name', 'Admin')->value('id');
        $operatorRoleId = DB::table('roles')->where('name', 'Operator')->value('id');
        $sectionHeadRoleId = DB::table('roles')->where('name', 'Section Head')->value('id');

        echo "Admin Role ID: $adminRoleId\n";
        echo "Operator Role ID: $operatorRoleId\n";
        echo "Section Head Role ID: $sectionHeadRoleId\n";

        // Update users based on their old role string values
        DB::table('users')
            ->where('role', 'admin')
            ->update(['role_id' => $adminRoleId]);

        DB::table('users')
            ->where('role', 'operator')
            ->update(['role_id' => $operatorRoleId]);

        DB::table('users')
            ->where('role', 'Section Head')
            ->update(['role_id' => $sectionHeadRoleId]);

        // Also check for case variations
        DB::table('users')
            ->where('role', 'Admin')
            ->update(['role_id' => $adminRoleId]);

        DB::table('users')
            ->where('role', 'Operator')
            ->update(['role_id' => $operatorRoleId]);

        // Verify the updates
        echo "\nUpdated users:\n";
        $users = DB::table('users')->select('username', 'role', 'role_id')->get();
        foreach ($users as $user) {
            echo "Username: {$user->username}, Old Role: {$user->role}, Role ID: {$user->role_id}\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set role_id back to null
        DB::table('users')->update(['role_id' => null]);
    }
};
