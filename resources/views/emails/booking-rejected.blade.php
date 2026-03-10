<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;padding:0;background:#f3f4f6;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:100%;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #e5e7eb;">
                <tr>
                    <td style="padding:20px 24px;background:linear-gradient(135deg,#0f172a,#ef4444);color:#ffffff;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="left" style="display:flex;align-items:center;gap:12px;">
                                    <img src="{{ url('/img/logo-full.png') }}" alt="Logo" style="height:36px;width:auto;border-radius:4px;display:block;">
                                </td>
                            </tr>
                            <tr>
                                <td style="padding-top:12px;">
                                    <div style="font-size:18px;font-weight:600;">Booking Rejected - {{ $ticketNumber }}</div>
                                    <div style="font-size:13px;opacity:0.9;">{{ $notifiable->name ?? 'Vendor' }}</div>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding-top:8px;">
                                    <div style="font-size:14px;">Hello {{ $notifiable->name ?? 'Vendor' }},</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:20px 24px;color:#111827;font-family:Arial,Helvetica,sans-serif;">
                        <p style="margin:0 0 12px 0;color:#111827;">Unfortunately, your booking request has been rejected.</p>

                        <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                            <tr>
                                <td style="padding:12px 14px;background:#f9fafb;font-size:14px;">
                                    <div style="padding:4px 0;"><strong>Ticket:</strong> {{ $ticketNumber }}</div>
                                    <div style="padding:4px 0;"><strong>Requested Time:</strong> {{ $plannedDate }}</div>
                                    <div style="padding:4px 0;"><strong>Reason:</strong> {{ $reason }}</div>
                                </td>
                            </tr>
                        </table>

                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="center" style="padding:16px 0 8px 0;">
                                    <a href="{{ url('/vendor/bookings/create') }}"
                                       style="display:inline-block;background-color:#ef4444;color:#ffffff;padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:600;">
                                        Submit New Booking
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:8px 0 0 0;color:#374151;">You can submit a new booking request with a different time slot.</p>

                        <p style="margin:16px 0 0 0;color:#374151;">Regards,<br>Warehouse – SCM – PT Oneject Indonesia</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
