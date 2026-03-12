<?php

namespace App\Notifications;

use App\Models\Slot;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRequested extends Notification
{
    public function __construct(
        public Slot $slot
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
        $poNumber = $this->slot->po_number ?? '-';
        $vendorName = $this->slot->vendor_name ?? $this->slot->requester?->name ?? 'Vendor';

        return (new MailMessage())
            ->subject('New Booking Request - PO '.$poNumber)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A new booking request has been submitted and requires your review.')
            ->line('**Vendor:** '.$vendorName)
            ->line('**PO/DO Number:** '.$poNumber)
            ->line('**Scheduled Time:** '.$plannedDate)
            ->line('**Direction:** '.ucfirst((string) ($this->slot->direction ?? '')))
            ->action('Review Booking', url('/unplanned/approval/'.$this->slot->id))
            ->line('Please review and approve or reject this booking request.')
            ->salutation('Regards,\nWarehouse – SCM – PT Oneject Indonesia');
    }

    public function toArray(object $notifiable): array
    {
        $vendorName = $this->slot->vendor_name ?? $this->slot->requester?->name ?? 'Vendor';
        $poNumber = $this->slot->po_number ?? '-';

        return [
            'slot_id' => $this->slot->id,
            'title' => 'New Booking Request',
            'message' => 'Request from '.$vendorName.' for PO '.$poNumber,
            'action_url' => url('/unplanned/approval/'.$this->slot->id),
            'icon' => 'fas fa-plus-circle',
            'color' => 'blue',
        ];
    }
}
