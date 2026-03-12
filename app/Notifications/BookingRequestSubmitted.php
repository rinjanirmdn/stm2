<?php

namespace App\Notifications;

use App\Models\BookingRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class BookingRequestSubmitted extends Notification
{
    public function __construct(
        public BookingRequest $bookingRequest
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
        $vendorName = $this->bookingRequest->supplier_name
            ?? $this->bookingRequest->requester?->name
            ?? 'Vendor';
        $poNumber = $this->bookingRequest->po_number ?? '-';
        $plannedDate = $this->bookingRequest->planned_start?->format('d-m-Y H:i') ?? '-';
        $direction = ucfirst((string) ($this->bookingRequest->direction ?? '-'));

        $appName = 'e-Docking Control System';
        $companyName = 'PT Oneject Indonesia';
        $logoUrl = url('/img/logo-full.png');

        try {
            $viewData = [
                'appName' => $appName,
                'companyName' => $companyName,
                'logoUrl' => $logoUrl,
                'notifiable' => $notifiable,
                'poNumber' => $poNumber,
                'vendorName' => $vendorName,
                'plannedDate' => $plannedDate,
                'direction' => $direction,
                'bookingRequest' => $this->bookingRequest,
            ];

            return (new MailMessage())
                ->subject('['.$appName.'] New Booking Request - PO '.$poNumber)
                ->view('emails.booking-request-submitted-new', $viewData);
        } catch (\Throwable $e) {
            Log::error('Failed to send booking request notification: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return (new MailMessage())
                ->subject('New Booking Request - PO '.$poNumber)
                ->line('Failed to send email notification. Please contact administrator.');
        }
    }

    public function toArray(object $notifiable): array
    {
        $vendorName = $this->bookingRequest->supplier_name
            ?? $this->bookingRequest->requester?->name
            ?? 'Vendor';

        return [
            'title' => 'New Booking Request',
            'message' => 'Request from '.$vendorName.' for PO '.($this->bookingRequest->po_number ?? '-'),
            'action_url' => route('bookings.show', $this->bookingRequest->id, false),
            'icon' => 'fas fa-plus-circle',
            'color' => 'blue',
        ];
    }
}
