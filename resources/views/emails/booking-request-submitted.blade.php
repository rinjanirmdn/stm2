@component('mail::message')
# New Booking Request - {{ $poNumber }}

Hello {{ $notifiable->name ?? 'Admin' }},

A new booking request has been submitted and requires your review.

- **Vendor:** {{ $vendorName }}
- **PO/DO Number:** {{ $poNumber }}
- **Scheduled Time:** {{ $plannedDate }}
- **Direction:** {{ $direction }}

@component('mail::button', ['url' => url('/bookings/' . $bookingRequest->id)])
Review Booking
@endcomponent

Please review and approve or reject this booking request.

Regards,<br>
Warehouse – SCM – PT Oneject Indonesia
@endcomponent
