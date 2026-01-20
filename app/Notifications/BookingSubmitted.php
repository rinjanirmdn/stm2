<?php

namespace App\Notifications;

use App\Models\Slot;
use Illuminate\Notifications\Notification;

class BookingSubmitted extends Notification
{
    public function __construct(
        public Slot $slot
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
