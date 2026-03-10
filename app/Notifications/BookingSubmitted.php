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
        $plannedDate = $this->slot->planned_start?->format('d-m-Y H:i') ?? '-';
        $gateName = $this->slot->plannedGate?->name ?? 'TBD';
        $poNumber = $this->slot->po_number ?? '-';

        return (new MailMessage)
            ->subject('Booking Request Submitted - PO ' . $poNumber)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your booking request has been submitted successfully and is waiting for approval.')
            ->line('**PO/DO Number:** ' . $poNumber)
            ->line('**Scheduled Time:** ' . $plannedDate)
            ->line('**Gate:** ' . $gateName)
            ->line('**Direction:** ' . ucfirst((string) ($this->slot->direction ?? '')))
            ->action('View Booking Details', url('/vendor/bookings/' . $this->slot->id))
            ->line('Thank you for using our service.');
    }

    public function toArray(object $notifiable): array
    {
        $poNumber = $this->slot->po_number ?? '-';

        return [
            'slot_id' => $this->slot->id,
            'title' => 'Booking Request Submitted',
            'message' => 'Your booking request for PO ' . $poNumber . ' has been submitted and is waiting for approval.',
            'action_url' => route('vendor.bookings.show', $this->slot->id, false),
            'icon' => 'fas fa-paper-plane',
            'color' => 'blue',
        ];
    }
}
