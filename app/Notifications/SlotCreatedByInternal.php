<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SlotCreatedByInternal extends Notification
{
    public function __construct(
        public int $slotId,
        public string $slotType,
        public string $poNumber,
        public string $vendorName,
        public string $direction,
        public string $plannedDate,
        public string $createdByName,
        public ?string $truckType = null,
        public ?string $vehicleNumber = null,
        public ?string $ticketNumber = null,
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
        $typeLabel = $this->slotType === 'unplanned' ? 'Unplanned' : 'Planned';
        $appName = 'e-Docking Control System';

        try {
            $viewData = [
                'appName' => $appName,
                'notifiable' => $notifiable,
                'typeLabel' => $typeLabel,
                'poNumber' => $this->poNumber,
                'vendorName' => $this->vendorName,
                'direction' => ucfirst($this->direction),
                'plannedDate' => $this->plannedDate,
                'createdByName' => $this->createdByName,
                'truckType' => $this->truckType,
                'vehicleNumber' => $this->vehicleNumber,
                'ticketNumber' => $this->ticketNumber,
                'slotId' => $this->slotId,
                'slotType' => $this->slotType,
            ];

            return (new MailMessage())
                ->subject('['.$appName.'] New '.$typeLabel.' Transaction - PO '.$this->poNumber)
                ->view('emails.slot-created-internal', $viewData);
        } catch (\Throwable $e) {
            Log::error('Failed to render slot created notification email: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return (new MailMessage())
                ->subject('New '.$typeLabel.' Transaction - PO '.$this->poNumber)
                ->line('A new '.$typeLabel.' transaction has been created by '.$this->createdByName.' for PO '.$this->poNumber.'.');
        }
    }

    public function toArray(object $notifiable): array
    {
        $typeLabel = $this->slotType === 'unplanned' ? 'Unplanned' : 'Planned';

        // Route to appropriate detail page based on slot type
        $actionUrl = $this->slotType === 'unplanned'
            ? route('unplanned.show', $this->slotId, false)
            : route('slots.show', ['slotId' => $this->slotId], false);

        return [
            'title' => 'New '.$typeLabel.' Transaction',
            'message' => $typeLabel.' transaction created by '.$this->createdByName.' for PO '.$this->poNumber,
            'action_url' => $actionUrl,
            'icon' => $this->slotType === 'unplanned' ? 'fas fa-truck' : 'fas fa-calendar-plus',
            'color' => $this->slotType === 'unplanned' ? 'orange' : 'green',
        ];
    }
}
