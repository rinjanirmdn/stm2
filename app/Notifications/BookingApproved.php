<?php

namespace App\Notifications;

use App\Models\Slot;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingApproved extends Notification
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
        $plannedDate = $this->slot->planned_start?->format('d M Y H:i') ?? '-';
        $gateName = $this->slot->plannedGate?->name ?? 'TBD';

        return (new MailMessage)
            ->subject('Booking Approved - ' . $this->slot->ticket_number)
            ->greeting('Hello ' . $notifiable->full_name . ',')
            ->line('Great news! Your booking request has been approved.')
            ->line('**Ticket:** ' . $this->slot->ticket_number)
            ->line('**Scheduled Time:** ' . $plannedDate)
            ->line('**Gate:** ' . $gateName)
            ->line('**Direction:** ' . ucfirst($this->slot->direction))
            ->action('View Booking Details', url('/vendor/bookings/' . $this->slot->id))
            ->line('Please make sure to arrive on time. Thank you for using our service.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Booking Approved',
            'message' => 'Your booking ' . $this->slot->ticket_number . ' has been approved.',
            'action_url' => route('vendor.bookings.show', $this->slot->id, false),
            'icon' => 'fas fa-check-circle',
            'color' => 'green',
        ];
    }
}
