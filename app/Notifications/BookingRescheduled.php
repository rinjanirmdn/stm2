<?php

namespace App\Notifications;

use App\Models\Slot;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRescheduled extends Notification
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
        $originalDate = $this->slot->original_planned_start?->format('d M Y H:i') ?? '-';
        $newDate = $this->slot->planned_start?->format('d M Y H:i') ?? '-';
        $gateName = $this->slot->plannedGate?->name ?? 'TBD';
        $notes = $this->slot->approval_notes ?? '';

        return (new MailMessage)
            ->subject('Booking Rescheduled - Action Required - ' . $this->slot->ticket_number)
            ->greeting('Hello ' . $notifiable->full_name . ',')
            ->line('Your booking has been rescheduled by the admin and requires your confirmation.')
            ->line('**Ticket:** ' . $this->slot->ticket_number)
            ->line('**Original Time:** ' . $originalDate)
            ->line('**New Time:** ' . $newDate)
            ->line('**Gate:** ' . $gateName)
            ->when($notes, fn($mail) => $mail->line('**Admin Notes:** ' . $notes))
            ->action('Confirm or Reject', url('/vendor/bookings/' . $this->slot->id . '/confirm'))
            ->line('Please confirm or reject this new schedule as soon as possible.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Booking Rescheduled',
            'message' => 'Action Required: Your booking ' . $this->slot->ticket_number . ' has been rescheduled.',
            'action_url' => route('vendor.bookings.confirm', $this->slot->id, false),
            'icon' => 'fas fa-clock',
            'color' => 'yellow',
        ];
    }
}
