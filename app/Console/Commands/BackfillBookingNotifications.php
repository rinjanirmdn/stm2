<?php

namespace App\Console\Commands;

use App\Models\Slot;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BackfillBookingNotifications extends Command
{
    protected $signature = 'notifications:backfill-bookings {--vendor : Also create vendor submission notifications} {--limit=0 : Max number of slots to process (0 = unlimited)} {--dry-run : Do not write notifications} {--force : Skip confirmation prompt}';

    protected $description = 'Backfill database notifications for existing booking requests (one-time command)';

    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('This is a one-time backfill command. Are you sure you want to run it?')) {
            $this->info('Cancelled.');
            return 0;
        }

        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');
        $withVendor = (bool) $this->option('vendor');

        $this->info('Backfilling booking notifications...');
        $this->info('Options: vendor=' . ($withVendor ? 'yes' : 'no') . ', limit=' . $limit . ', dry-run=' . ($dryRun ? 'yes' : 'no'));

        $slotsQ = Slot::query()
            ->where('status', Slot::STATUS_PENDING_APPROVAL)
            ->orderBy('created_at', 'desc');

        if ($limit > 0) {
            $slotsQ->limit($limit);
        }

        $slots = $slotsQ->get();
        $this->info('Pending slots to check: ' . $slots->count());

        $admins = User::whereHas('roles', function ($q) {
            $q->whereIn(DB::raw('LOWER(roles_name)'), ['admin', 'section head', 'super admin', 'super administrator']);
        })->get();

        if ($admins->isEmpty()) {
            $this->warn('No admin users found.');
        } else {
            $this->info('Admin recipients: ' . $admins->count());
        }

        $createdAdmin = 0;
        $createdVendor = 0;
        $skipped = 0;

        foreach ($slots as $slot) {
            $adminUrl = route('bookings.show', $slot->id, false);
            $vendorUrl = route('vendor.bookings.show', $slot->id, false);

            // Admin notifications (inline — no separate notification class needed)
            foreach ($admins as $admin) {
                if ($this->notificationExists($admin, $adminUrl)) {
                    $skipped++;
                    continue;
                }

                $createdAdmin++;
                if (!$dryRun) {
                    try {
                        DB::table('notifications')->insert([
                            'id' => Str::uuid()->toString(),
                            'type' => 'App\\Notifications\\BookingRequestSubmitted',
                            'notifiable_type' => get_class($admin),
                            'notifiable_id' => $admin->id,
                            'data' => json_encode([
                                'title' => 'New Booking Request',
                                'message' => 'Request from ' . ($slot->vendor_name ?? 'Vendor') . ' for ' . ($slot->ticket_number ?? '-'),
                                'action_url' => $adminUrl,
                                'icon' => 'fas fa-plus-circle',
                                'color' => 'blue',
                            ]),
                            'created_at' => $slot->created_at ?? now(),
                            'updated_at' => $slot->created_at ?? now(),
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('Backfill admin notify failed: ' . $e->getMessage(), [
                            'admin_id' => $admin->id,
                            'slot_id' => $slot->id,
                        ]);
                    }
                }
            }

            if ($withVendor) {
                $vendor = $slot->requester;
                if ($vendor) {
                    if ($this->notificationExists($vendor, $vendorUrl)) {
                        $skipped++;
                    } else {
                        $createdVendor++;
                        if (!$dryRun) {
                            try {
                                DB::table('notifications')->insert([
                                    'id' => Str::uuid()->toString(),
                                    'type' => 'App\\Notifications\\BookingApproved',
                                    'notifiable_type' => get_class($vendor),
                                    'notifiable_id' => $vendor->id,
                                    'data' => json_encode([
                                        'title' => 'Booking Submitted',
                                        'message' => 'Your booking request ' . ($slot->ticket_number ?? '-') . ' has been submitted.',
                                        'action_url' => $vendorUrl,
                                        'icon' => 'fas fa-paper-plane',
                                        'color' => 'blue',
                                    ]),
                                    'created_at' => $slot->created_at ?? now(),
                                    'updated_at' => $slot->created_at ?? now(),
                                ]);
                            } catch (\Throwable $e) {
                                Log::warning('Backfill vendor notify failed: ' . $e->getMessage(), [
                                    'vendor_user_id' => $vendor->id,
                                    'slot_id' => $slot->id,
                                ]);
                            }
                        }
                    }
                }
            }
        }

        $this->info('Created admin notifications: ' . $createdAdmin);
        $this->info('Created vendor notifications: ' . $createdVendor);
        $this->info('Skipped (already existed): ' . $skipped);

        if ($dryRun) {
            $this->warn('Dry-run: no notifications were written.');
        }

        $this->info('Done.');
        return 0;
    }

    private function notificationExists(User $user, string $actionUrl): bool
    {
        return DatabaseNotification::query()
            ->where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->where('data', 'like', '%"action_url":"' . addcslashes($actionUrl, '"\\') . '"%')
            ->exists();
    }
}
