<?php

namespace App\Notifications;

use App\Models\Slot;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingSubmitted extends Notification
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
        $ticketNumber = $this->slot->ticket_number ?? '-';
        $plannedDate = $this->slot->planned_start?->format('d M Y H:i') ?? '-';

        return (new MailMessage)
            ->subject('Booking Submitted - ' . $ticketNumber)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your booking request has been submitted successfully and is waiting for admin approval.')
            ->line('**Ticket:** ' . $ticketNumber)
            ->line('**Scheduled Time:** ' . $plannedDate)
            ->line('**Direction:** ' . ucfirst((string) ($this->slot->direction ?? '-')))
            ->action('View Booking', url('/vendor/bookings/' . $this->slot->id))
            ->line('You will be notified once the warehouse team reviews your request.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Booking Submitted',
            'message' => 'Your booking request ' . $this->slot->ticket_number . ' has been submitted and is waiting for admin approval.',
            'action_url' => route('vendor.bookings.show', $this->slot->id, false),
            'icon' => 'fas fa-paper-plane',
            'color' => 'blue',
        ];
    }
}
