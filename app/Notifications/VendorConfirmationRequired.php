<?php

namespace App\Notifications;

use App\Models\Slot;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorConfirmationRequired extends Notification
{
    public function __construct(
        public Slot $slot,
        public string $action = 'confirmed'
    ) {}

    public function via(object $notifiable): array
    {
        if (!empty($notifiable->email)) {
            return ['mail', 'database'];
        }

        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $vendorName = $this->slot->vendor?->name ?? 'Vendor';
        $plannedDate = $this->slot->planned_start?->format('d M Y H:i') ?? '-';

        if ($this->action === 'confirmed') {
            return (new MailMessage)
                ->subject('Vendor Confirmed Booking - ' . $this->slot->ticket_number)
                ->greeting('Hello ' . $notifiable->full_name . ',')
                ->line('The vendor has confirmed the rescheduled booking.')
                ->line('**Ticket:** ' . $this->slot->ticket_number)
                ->line('**Vendor:** ' . $vendorName)
                ->line('**Scheduled Time:** ' . $plannedDate)
                ->action('View Booking', url('/bookings/' . $this->slot->id))
                ->line('The booking is now confirmed and scheduled.');
        }

        return (new MailMessage)
            ->subject('Vendor Rejected Reschedule - ' . $this->slot->ticket_number)
            ->greeting('Hello ' . $notifiable->full_name . ',')
            ->line('The vendor has rejected the rescheduled booking.')
            ->line('**Ticket:** ' . $this->slot->ticket_number)
            ->line('**Vendor:** ' . $vendorName)
            ->action('View Booking', url('/bookings/' . $this->slot->id))
            ->line('The booking has been cancelled.');
    }

    public function toArray(object $notifiable): array
    {
        $color = $this->action === 'confirmed' ? 'green' : 'red';
        $icon = $this->action === 'confirmed' ? 'fas fa-check-double' : 'fas fa-ban';

        return [
            'title' => 'Vendor ' . ucfirst($this->action) . ' Schedule',
            'message' => 'Vendor has ' . $this->action . ' the reschedule for ' . $this->slot->ticket_number,
            'action_url' => route('bookings.show', $this->slot->id, false),
            'icon' => $icon,
            'color' => $color,
        ];
    }
}
