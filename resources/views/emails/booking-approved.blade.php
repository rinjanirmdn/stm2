@component('mail::message')
# {{ $subjectPrefix }} - {{ $slot->ticket_number }}

Hello {{ $notifiable->name ?? 'Vendor' }},

{{ $messageLine }}

- **Ticket:** {{ $slot->ticket_number }}
- **Scheduled Time:** {{ $plannedDate }}
- **Gate:** {{ $gateName }}
- **Direction:** {{ ucfirst($slot->direction) }}

@component('mail::button', ['url' => url('/vendor/bookings/' . $targetId)])
View Booking Details
@endcomponent

Please make sure to arrive on time. Thank you for using our service.

Regards,<br>
Warehouse – SCM – PT Oneject Indonesia
@endcomponent
