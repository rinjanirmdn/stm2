<?php

namespace App\Notifications;

use App\Models\BookingRequest;
use App\Models\Slot;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRejected extends Notification
{
    private ?Slot $slot;

    private ?BookingRequest $bookingRequest;

    private string $reason;

    /**
     * Accept either a Slot (from BookingApprovalService) or a BookingRequest (from controller reject).
     * This eliminates the need for fake Slot objects (#32).
     */
    public function __construct(?Slot $slot = null, ?BookingRequest $bookingRequest = null, ?string $reason = null)
    {
        $this->slot = $slot;
        $this->bookingRequest = $bookingRequest;
        $this->reason = $reason ?? '';
    }

    public function via(object $notifiable): array
    {
        if (! empty($notifiable->email)) {
            return ['mail', 'database'];
        }

        return ['database'];
    }

    private function getTicketNumber(): string
    {
        if ($this->slot) {
            return $this->slot->ticket_number ?? '-';
        }

        return $this->bookingRequest->request_number ?? $this->bookingRequest->po_number ?? '-';
    }

    private function getPlannedDate(): string
    {
        $start = $this->slot?->planned_start ?? $this->bookingRequest?->planned_start;

        return $start?->format('d-m-Y H:i') ?? '-';
    }

    private function getReason(): string
    {
        if ($this->reason !== '') {
            return $this->reason;
        }

        return $this->slot?->approval_notes ?? 'No reason provided';
    }

    private function getNotificationId(): int
    {
        if ($this->slot) {
            return (int) $this->slot->id;
        }

        return (int) $this->bookingRequest->id;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ticketNumber = $this->getTicketNumber();
        $plannedDate = $this->getPlannedDate();
        $reason = $this->getReason();

        return (new MailMessage())
            ->subject('Booking Rejected - '.$ticketNumber)
            ->view('emails.booking-rejected', [
                'ticketNumber' => $ticketNumber,
                'plannedDate' => $plannedDate,
                'reason' => $reason,
                'notifiable' => $notifiable,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $id = $this->getNotificationId();
        $ticketNumber = $this->getTicketNumber();

        return [
            'title' => 'Booking Rejected',
            'message' => 'Your booking '.$ticketNumber.' has been rejected.',
            'action_url' => route('vendor.bookings.show', $id, false),
            'icon' => 'fas fa-times-circle',
            'color' => 'red',
        ];
    }
}
