<?php

namespace App\Notifications;

use App\Models\Slot;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRequested extends Notification
{
    public function __construct(
        public Slot $slot
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
        $vendorName = $this->slot->vendor_name ?? 'Unknown Vendor';
        $plannedDate = $this->slot->planned_start?->format('d M Y H:i') ?? '-';

        return (new MailMessage)
            ->subject('New Booking Request - ' . $this->slot->ticket_number)
            ->greeting('Hello ' . $notifiable->full_name . ',')
            ->line('A new booking request has been submitted and requires your approval.')
            ->line('**Ticket:** ' . $this->slot->ticket_number)
            ->line('**Vendor:** ' . $vendorName)
            ->line('**Scheduled Time:** ' . $plannedDate)
            ->line('**Direction:** ' . ucfirst($this->slot->direction))
            ->action('Review Booking', url('/bookings/' . $this->slot->id))
            ->line('Please review and approve or reject this booking request.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Booking Request',
            'message' => 'Request from ' . ($this->slot->vendor_name ?? 'Vendor') . ' for ' . $this->slot->ticket_number,
            'action_url' => route('bookings.show', $this->slot->id, false),
            'icon' => 'fas fa-plus-circle',
            'color' => 'blue',
        ];
    }
}
