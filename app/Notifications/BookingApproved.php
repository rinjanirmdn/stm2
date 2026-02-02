<?php

namespace App\Notifications;

use App\Models\Slot;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingApproved extends Notification
{
    public function __construct(
        public Slot $slot,
        public ?int $bookingRequestId = null,
        public bool $isRescheduled = false
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
        $subjectPrefix = $this->isRescheduled ? 'Booking Rescheduled & Approved' : 'Booking Approved';
        $messageLine = $this->isRescheduled
            ? 'Your booking has been rescheduled and approved.'
            : 'Great news! Your booking request has been approved.';
        $targetId = $this->bookingRequestId ?: $this->slot->id;

        return (new MailMessage)
            ->subject($subjectPrefix . ' - ' . $this->slot->ticket_number)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($messageLine)
            ->line('**Ticket:** ' . $this->slot->ticket_number)
            ->line('**Scheduled Time:** ' . $plannedDate)
            ->line('**Gate:** ' . $gateName)
            ->line('**Direction:** ' . ucfirst($this->slot->direction))
            ->action('View Booking Details', url('/vendor/bookings/' . $targetId))
            ->line('Please make sure to arrive on time. Thank you for using our service.');
    }

    public function toArray(object $notifiable): array
    {
        $targetId = $this->bookingRequestId ?: $this->slot->id;
        $title = $this->isRescheduled ? 'Booking Approved (Rescheduled)' : 'Booking Approved';
        $message = $this->isRescheduled
            ? 'Your booking ' . $this->slot->ticket_number . ' has been rescheduled and approved.'
            : 'Your booking ' . $this->slot->ticket_number . ' has been approved.';
        return [
            'slot_id' => $this->slot->id,
            'title' => $title,
            'message' => $message,
            'action_url' => route('vendor.bookings.show', $targetId, false),
            'icon' => 'fas fa-check-circle',
            'color' => 'green',
        ];
    }
}
