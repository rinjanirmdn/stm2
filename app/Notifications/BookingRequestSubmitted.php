<?php

namespace App\Notifications;

use App\Models\BookingRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRequestSubmitted extends Notification
{
    public function __construct(
        public BookingRequest $bookingRequest
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
        $vendorName = $this->bookingRequest->supplier_name
            ?? $this->bookingRequest->requester?->name
            ?? 'Vendor';
        $poNumber = $this->bookingRequest->po_number ?? '-';
        $plannedDate = $this->bookingRequest->planned_start?->format('d M Y H:i') ?? '-';
        $direction = ucfirst((string) ($this->bookingRequest->direction ?? '-'));

        return (new MailMessage)
            ->subject('New Booking Request - PO ' . $poNumber)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new booking request has been submitted and requires your review.')
            ->line('**Vendor:** ' . $vendorName)
            ->line('**PO/DO Number:** ' . $poNumber)
            ->line('**Scheduled Time:** ' . $plannedDate)
            ->line('**Direction:** ' . $direction)
            ->action('Review Booking', url('/bookings/' . $this->bookingRequest->id))
            ->line('Please review and approve or reject this booking request.');
    }

    public function toArray(object $notifiable): array
    {
        $vendorName = $this->bookingRequest->supplier_name
            ?? $this->bookingRequest->requester?->name
            ?? 'Vendor';

        return [
            'title' => 'New Booking Request',
            'message' => 'Request from ' . $vendorName . ' for PO ' . ($this->bookingRequest->po_number ?? '-'),
            'action_url' => route('bookings.show', $this->bookingRequest->id, false),
            'icon' => 'fas fa-plus-circle',
            'color' => 'blue',
        ];
    }
}
