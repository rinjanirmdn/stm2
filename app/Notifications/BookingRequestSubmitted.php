<?php

namespace App\Notifications;

use App\Models\BookingRequest;
use Illuminate\Notifications\Notification;

class BookingRequestSubmitted extends Notification
{
    public function __construct(
        public BookingRequest $bookingRequest
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
