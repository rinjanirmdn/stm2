<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Dynamically detect column name
        $roleNameCol = Schema::hasColumn('md_roles', 'roles_name') ? 'roles_name' : 'name';

        // Get current role IDs
        $adminRoleId = DB::table('md_roles')->where($roleNameCol, 'Admin')->value('id');
        $operatorRoleId = DB::table('md_roles')->where($roleNameCol, 'Operator')->value('id');
        $sectionHeadRoleId = DB::table('md_roles')->where($roleNameCol, 'Section Head')->value('id');

        echo "Admin Role ID: $adminRoleId\n";
        echo "Operator Role ID: $operatorRoleId\n";
        echo "Section Head Role ID: $sectionHeadRoleId\n";

        $usersTable = Schema::hasTable('md_users') ? 'md_users' : 'users';

        // Update users based on their old role string values
        DB::table($usersTable)
            ->where('role', 'admin')
            ->update(['role_id' => $adminRoleId]);

        DB::table($usersTable)
            ->where('role', 'operator')
            ->update(['role_id' => $operatorRoleId]);

        DB::table($usersTable)
            ->where('role', 'Section Head')
            ->update(['role_id' => $sectionHeadRoleId]);

        // Also check for case variations
        DB::table($usersTable)
            ->where('role', 'Admin')
            ->update(['role_id' => $adminRoleId]);

        DB::table($usersTable)
            ->where('role', 'Operator')
            ->update(['role_id' => $operatorRoleId]);

        // Verify the updates
        echo "\nUpdated users:\n";
        $users = DB::table($usersTable)->select('username', 'role', 'role_id')->get();
        foreach ($users as $user) {
            echo "Username: {$user->username}, Old Role: {$user->role}, Role ID: {$user->role_id}\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $usersTable = \Illuminate\Support\Facades\Schema::hasTable('md_users') ? 'md_users' : 'users';
        // Set role_id back to null
        DB::table($usersTable)->update(['role_id' => null]);
    }
};
