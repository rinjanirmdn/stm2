<?php

namespace App\Console\Commands;

use App\Models\Slot;
use App\Models\User;
use App\Notifications\BookingRequested;
use App\Notifications\BookingSubmitted;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillBookingNotifications extends Command
{
    protected $signature = 'notifications:backfill-bookings {--vendor : Also create vendor submission notifications} {--limit=0 : Max number of slots to process (0 = unlimited)} {--dry-run : Do not write notifications}';

    protected $description = 'Backfill database notifications for existing booking requests (admin, optionally vendor)';

    public function handle(): int
    {
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
            $this->warn('No admin users found (roles_name in admin/section_head/super_admin/super_administrator).');
        } else {
            $this->info('Admin recipients: ' . $admins->count());
        }

        $createdAdmin = 0;
        $createdVendor = 0;
        $skipped = 0;

        foreach ($slots as $slot) {
            $adminUrl = route('bookings.show', $slot->id);
            $vendorUrl = route('vendor.bookings.show', $slot->id);

            // Admin notifications
            foreach ($admins as $admin) {
                if ($this->notificationExists($admin, BookingRequested::class, $adminUrl)) {
                    $skipped++;
                    continue;
                }

                $createdAdmin++;
                if (!$dryRun) {
                    try {
                        $admin->notify(new BookingRequested($slot));
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
                    if ($this->notificationExists($vendor, BookingSubmitted::class, $vendorUrl)) {
                        $skipped++;
                    } else {
                        $createdVendor++;
                        if (!$dryRun) {
                            try {
                                $vendor->notify(new BookingSubmitted($slot));
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

    private function notificationExists(User $user, string $type, string $actionUrl): bool
    {
        $actionUrl = (string) $actionUrl;

        // notifications table is polymorphic (notifiable_type, notifiable_id)
        // data is stored as JSON text. We use a LIKE check on action_url for idempotency.
        return DatabaseNotification::query()
            ->where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->where('type', $type)
            ->where('data', 'like', '%"action_url":"' . addcslashes($actionUrl, '"\\') . '"%')
            ->exists();
    }
}
