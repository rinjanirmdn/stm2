<?php

namespace App\Notifications;

use App\Models\Slot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SlotStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Slot $slot,
        public string $oldStatus,
        public string $newStatus
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Slot {$this->slot->ticket_number} Status Changed")
            ->line("Slot status changed from {$this->oldStatus} to {$this->newStatus}")
            ->action('View Slot', route('slots.show', $this->slot))
            ->line('Thank you for using our application!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'slot_id' => $this->slot->id,
            'ticket_number' => $this->slot->ticket_number,
            'po_number' => $this->slot->po_number,
            'truck_number' => $this->slot->truck_number,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'message' => "Slot {$this->slot->ticket_number} is now {$this->newStatus}",
            'created_at' => now()->toDateTimeString()
        ];
    }
}
