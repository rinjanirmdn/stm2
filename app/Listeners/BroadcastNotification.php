<?php

namespace App\Listeners;

use App\Events\NewNotification;
use Illuminate\Notifications\Events\NotificationSent;

/**
 * When a Laravel notification is stored in the database, broadcast it
 * via WebSocket so the user sees it instantly without polling.
 */
class BroadcastNotification
{
    public function handle(NotificationSent $event): void
    {
        // Only broadcast for database channel notifications
        if ($event->channel !== 'database') {
            return;
        }

        $notifiable = $event->notifiable;
        if (!$notifiable || !isset($notifiable->id)) {
            return;
        }

        // Extract data from the notification's toArray response
        $data = method_exists($event->notification, 'toArray')
            ? $event->notification->toArray($notifiable)
            : [];

        try {
            broadcast(new NewNotification(
                userId: (int) $notifiable->id,
                title: $data['title'] ?? 'Notification',
                message: $data['message'] ?? '',
                url: $data['action_url'] ?? null,
                notificationId: $event->response ?? null,
            ));
        } catch (\Throwable $e) {
            // Don't let broadcast failures break the notification flow
        }
    }
}
