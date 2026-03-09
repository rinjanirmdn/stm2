<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast whenever slot/booking/gate data changes.
 * All authenticated users listening on the "data-updates" channel
 * will receive this event and can refresh their view.
 */
class SlotDataChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $type;    // e.g. 'slot', 'booking', 'gate'
    public string $action;  // e.g. 'created', 'updated', 'deleted'
    public ?int $recordId;

    public function __construct(string $type = 'slot', string $action = 'updated', ?int $recordId = null)
    {
        $this->type = $type;
        $this->action = $action;
        $this->recordId = $recordId;
    }

    /**
     * Broadcast on a public channel so all authenticated users receive it.
     * Authorization is handled by the "auth" middleware on the Reverb endpoint.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('data-updates'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'data.changed';
    }

    /**
     * Data sent to the client.
     */
    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'action' => $this->action,
            'id' => $this->recordId,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
