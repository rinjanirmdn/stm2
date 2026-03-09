@component('mail::message')
# Booking Rejected - {{ $ticketNumber }}

Hello {{ $notifiable->name ?? 'Vendor' }},

Unfortunately, your booking request has been rejected.

- **Ticket:** {{ $ticketNumber }}
- **Requested Time:** {{ $plannedDate }}
- **Reason:** {{ $reason }}

@component('mail::button', ['url' => url('/vendor/bookings/create')])
Submit New Booking
@endcomponent

You can submit a new booking request with a different time slot.

Regards,<br>
Warehouse – SCM – PT Oneject Indonesia
@endcomponent
