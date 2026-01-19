<?php

namespace Tests\Feature;

use App\Models\PO;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SlotTicketPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            \Database\Seeders\RolePermissionSeeder::class,
            \Database\Seeders\AdminUserSeeder::class,
            \Database\Seeders\WarehouseSeeder::class,
            \Database\Seeders\GateSeeder::class,
            \Database\Seeders\VendorSeeder::class,
            \Database\Seeders\TruckTypeDurationSeeder::class,
        ]);

        // Ensure seeded users have Spatie model_has_roles rows (some tests don't go through login flow)
        if (DB::table('users')->whereNotNull('role_id')->exists()) {
            $users = DB::table('users')->select(['id', 'role_id'])->whereNotNull('role_id')->get();
            foreach ($users as $u) {
                $exists = DB::table('model_has_roles')
                    ->where('role_id', (int) $u->role_id)
                    ->where('model_type', 'App\\Models\\User')
                    ->where('model_id', (int) $u->id)
                    ->exists();

                if (! $exists) {
                    DB::table('model_has_roles')->insert([
                        'role_id' => (int) $u->role_id,
                        'model_type' => 'App\\Models\\User',
                        'model_id' => (int) $u->id,
                    ]);
                }
            }
        }

        try {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
        }
    }

    public function test_operator_cannot_access_print_ticket_endpoint(): void
    {
        $operator = User::where('username', 'operator')->first();
        $this->assertNotNull($operator);

        $po = PO::factory()->create();
        $slot = Slot::factory()->create([
            'po_id' => $po->id,
            'ticket_number' => 'A2510001',
            'status' => 'waiting',
        ]);

        $response = $this->actingAs($operator)
            ->get(route('slots.ticket', ['slotId' => $slot->id]));

        $response->assertStatus(403);
    }

    public function test_admin_can_access_print_ticket_endpoint(): void
    {
        $admin = User::where('username', 'admin')->first();
        $this->assertNotNull($admin);

        $po = PO::factory()->create();
        $slot = Slot::factory()->create([
            'po_id' => $po->id,
            'ticket_number' => 'A2510002',
            'status' => 'waiting',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('slots.ticket', ['slotId' => $slot->id]));

        $response->assertStatus(200);
        $response->assertHeader('content-type');
    }
}
