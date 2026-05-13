<?php
 
namespace database\migrations;
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
 
return new class extends Migration
{
    public function up(): void
    {
        // 1. Ensure feature column exists
        if (Schema::hasTable('activity_logs') && !Schema::hasColumn('activity_logs', 'feature')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->string('feature', 100)->nullable();
            });
        }
 
        // 2. Ensure activity_type is string (standardize from previous migrations)
        if (Schema::hasTable('activity_logs')) {
            try {
                DB::statement('ALTER TABLE activity_logs ALTER COLUMN activity_type TYPE VARCHAR(50) USING activity_type::text');
            } catch (\Throwable $e) {
                // Already string or table doesn't exist yet
            }
        }
 
        // 3. Update activity_type values to standard lowercase CRUD
        if (Schema::hasTable('activity_logs')) {
            try {
                DB::table('activity_logs')->where('activity_type', 'create')->update(['activity_type' => 'insert']);
                DB::table('activity_logs')->where('activity_type', 'edit')->update(['activity_type' => 'update']);
                
                $updateTypes = [
                    'status_change', 'late_arrival', 'early_arrival', 
                    'gate_activation', 'gate_deactivation', 'gate_change', 
                    'backdate', 'waiting_time', 'arrival_recorded', 'arrival_updated'
                ];
                DB::table('activity_logs')->whereIn('activity_type', $updateTypes)->update(['activity_type' => 'update']);
            } catch (\Throwable $e) {
                // Ignore
            }
        }
 
        // 4. Populate feature column based on description patterns
        if (Schema::hasTable('activity_logs')) {
            $featureMap = [
                'Planned Slot' => [
                    'scheduled slot', 'booking started', 'slot completed', 'slot cancelled', 
                    'slot edited', 'slot updated', 'status changed to waiting', 
                    'arrival recorded', 'arrival backdated', 'start backdated', 
                    'complete backdated', 'truck arrived late', 'truck arrived on time', 
                    'gate changed to', 'auto-cancelled'
                ],
                'Unplanned Slot' => ['unplanned'],
                'Gate Management' => ['gate activated', 'gate deactivated'],
                'Auth' => ['logged in', 'logged out', 'login', 'logout', 'password'],
                'User Management' => ['user ', 'account '],
                'Booking' => ['booking request', 'booking approved', 'booking rejected'],
                'Truck Type' => ['truck type', 'truck duration']
            ];
 
            foreach ($featureMap as $feature => $patterns) {
                try {
                    $query = DB::table('activity_logs')->whereNull('feature');
                    $query->where(function ($q) use ($patterns) {
                        foreach ($patterns as $pattern) {
                            $q->orWhere('description', 'ilike', "%$pattern%");
                        }
                    });
                    $query->update(['feature' => $feature]);
                } catch (\Throwable $e) {}
            }
 
            try {
                DB::table('activity_logs')->whereNull('feature')->update(['feature' => 'System']);
            } catch (\Throwable $e) {}
        }
    }
 
    public function down(): void
    {
        if (Schema::hasTable('activity_logs')) {
            DB::table('activity_logs')->where('activity_type', 'insert')->update(['activity_type' => 'create']);
            DB::table('activity_logs')->where('activity_type', 'update')->update(['activity_type' => 'edit']);
        }
    }
};
