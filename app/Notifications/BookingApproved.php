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
        $subjectPrefix = $this->isRescheduled ? 'Booking Rescheduled & Approved' : 'Booking Approved';
        $messageLine = $this->isRescheduled
            ? 'Your booking has been rescheduled and approved.'
            : 'Great news! Your booking request has been approved.';
        $targetId = $this->bookingRequestId ?: $this->slot->id;

        return (new MailMessage())
            ->subject($subjectPrefix.' - '.$this->slot->ticket_number)
            ->view('emails.booking-approved', [
                'subjectPrefix' => $subjectPrefix,
                'messageLine' => $messageLine,
                'plannedDate' => $plannedDate,
                'gateName' => $gateName,
                'targetId' => $targetId,
                'slot' => $this->slot,
                'notifiable' => $notifiable,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $targetId = $this->bookingRequestId ?: $this->slot->id;
        $title = $this->isRescheduled ? 'Booking Approved (Rescheduled)' : 'Booking Approved';
        $message = $this->isRescheduled
            ? 'Your booking '.$this->slot->ticket_number.' has been rescheduled and approved.'
            : 'Your booking '.$this->slot->ticket_number.' has been approved.';

        return [
            'slot_id' => $this->slot->id,
            'title' => $title,
            'message' => $message,
            'action_url' => url('/vendor/bookings/'.$targetId),
            'icon' => 'fas fa-check-circle',
            'color' => 'green',
        ];
    }
}
