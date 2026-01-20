<?php

namespace App\Notifications;

use App\Models\Slot;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRejected extends Notification
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
        $reason = $this->slot->approval_notes ?? 'No reason provided';

        return (new MailMessage)
            ->subject('Booking Rejected - ' . $this->slot->ticket_number)
            ->greeting('Hello ' . $notifiable->full_name . ',')
            ->line('Unfortunately, your booking request has been rejected.')
            ->line('**Ticket:** ' . $this->slot->ticket_number)
            ->line('**Requested Time:** ' . $plannedDate)
            ->line('**Reason:** ' . $reason)
            ->action('Submit New Booking', url('/vendor/bookings/create'))
            ->line('You can submit a new booking request with a different time slot.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Booking Rejected',
            'message' => 'Your booking ' . $this->slot->ticket_number . ' has been rejected.',
            'action_url' => route('vendor.bookings.show', $this->slot->id, false),
            'icon' => 'fas fa-times-circle',
            'color' => 'red',
        ];
    }
}
