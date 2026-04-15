<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Database-only notification for slot lifecycle events (arrival, start, complete).
 * Sent to Section Head and Super Account — NO email.
 */
class SlotLifecycleNotification extends Notification
{
    public function __construct(
        public int $slotId,
        public string $slotType,
        public string $event,
        public string $poNumber,
        public string $vendorName,
        public string $ticketNumber,
        public string $performedBy,
        public ?string $gateName = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $eventLabels = [
            'arrival' => 'Arrival Recorded',
            'start' => 'Process Started',
            'complete' => 'Process Completed',
        ];

        $iconMap = [
            'arrival' => 'fas fa-truck-loading',
            'start' => 'fas fa-play-circle',
            'complete' => 'fas fa-check-circle',
        ];

        $colorMap = [
            'arrival' => 'blue',
            'start' => 'orange',
            'complete' => 'green',
        ];

        $label = $eventLabels[$this->event] ?? ucfirst($this->event);
        $icon = $iconMap[$this->event] ?? 'fas fa-info-circle';
        $color = $colorMap[$this->event] ?? 'blue';

        $typeLabel = $this->slotType === 'unplanned' ? 'Unplanned' : 'Planned';

        $message = $label . ' by ' . $this->performedBy;
        if ($this->ticketNumber !== '') {
            $message .= ' — Ticket ' . $this->ticketNumber;
        }
        if ($this->poNumber !== '') {
            $message .= ' (PO ' . $this->poNumber . ')';
        }
        if ($this->gateName) {
            $message .= ' at ' . $this->gateName;
        }

        $actionUrl = $this->slotType === 'unplanned'
            ? route('unplanned.show', $this->slotId, false)
            : route('slots.show', ['slotId' => $this->slotId], false);

        return [
            'title' => $typeLabel . ': ' . $label,
            'message' => $message,
            'action_url' => $actionUrl,
            'icon' => $icon,
            'color' => $color,
        ];
    }
}
