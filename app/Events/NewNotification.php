<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast a new notification to a specific user via private channel.
 * Only the targeted user will receive this event.
 */
class NewNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;

    public string $title;

    public string $message;

    public ?string $url;

    public ?string $notificationId;

    public function __construct(int $userId, string $title, string $message, ?string $url = null, ?string $notificationId = null)
    {
        $this->userId = $userId;
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;
        $this->notificationId = $notificationId;
    }

    /**
     * Private channel — only the target user can listen.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.new';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notificationId,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
