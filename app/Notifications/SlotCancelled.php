<?php

namespace App\Notifications;

use App\Models\Slot;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SlotCancelled extends Notification
{
    public function __construct(
        public Slot $slot,
        public string $reason,
        public ?string $cancelledAt = null,
        public ?int $bookingRequestId = null
    ) {}

    public function via(object $notifiable): array
    {
        if (! empty($notifiable->email)) {
            return ['mail', 'database'];
        }

        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $plannedDate = $this->slot->planned_start?->format('d-m-Y H:i') ?? '-';
        $gateName = (string) ($this->slot->actualGate?->name ?? $this->slot->plannedGate?->name ?? '');
        if ($gateName === '') {
            $gateWh = (string) ($this->slot->actualGate?->warehouse?->wh_code ?? $this->slot->plannedGate?->warehouse?->wh_code ?? $this->slot->warehouse?->wh_code ?? '');
            $gateNo = (string) ($this->slot->actualGate?->gate_number ?? $this->slot->plannedGate?->gate_number ?? '');
            if ($gateWh !== '' && $gateNo !== '') {
                $gateName = $gateWh.'-'.$gateNo;
            }
        }
        if ($gateName === '') {
            $gateName = 'TBD';
        }

        $ticketNumber = $this->slot->ticket_number ?? $this->slot->po_number ?? '-';
        $poNumber = $this->slot->po_number ?? '-';

        $actionUrl = $this->bookingRequestId
            ? route('vendor.bookings.show', $this->bookingRequestId, false)
            : route('vendor.bookings.index', [], false);

        return (new MailMessage())
            ->subject('Booking Cancelled - '.$ticketNumber)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your booking has been cancelled.')
            ->line('**PO/SO Number:** '.$poNumber)
            ->line('**Scheduled Time:** '.$plannedDate)
            ->line('**Gate:** '.$gateName)
            ->line('**Reason:** '.($this->reason !== '' ? $this->reason : '-'))
            ->action('View Booking Details', $actionUrl)
            ->line('Thank you.');
    }

    public function toArray(object $notifiable): array
    {
        $ticketNumber = $this->slot->ticket_number ?? $this->slot->po_number ?? '-';

        $actionUrl = $this->bookingRequestId
            ? route('vendor.bookings.show', $this->bookingRequestId, false)
            : route('vendor.bookings.index', [], false);

        return [
            'slot_id' => $this->slot->id,
            'title' => 'Booking Cancelled',
            'message' => 'Your booking '.$ticketNumber.' has been cancelled.',
            'action_url' => $actionUrl,
            'icon' => 'fas fa-ban',
            'color' => 'red',
        ];
    }
}
