<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>[{{ config('app.name', 'e-DCS') }}] Booking Rejected - {{ $ticketNumber }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:24px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(15,23,42,0.12);">
                <tr>
                    <td style="padding:20px 24px;background:linear-gradient(135deg,#7f1d1d,#dc2626);color:#ffffff;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="left">
                                    <div style="font-size:20px;font-weight:700;color:#ffffff;">{{ config('app.name', 'e-DCS') }}</div>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding-top:12px;">
                                    <div style="font-size:18px;font-weight:600;">Booking Rejected</div>
                                    <div style="font-size:13px;opacity:0.9;">
                                        <img src="{{ url('/img/e-Docking Control System.png') }}" alt="e-Docking Control System" style="height:20px;width:auto;display:block;margin-top:4px;">
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding-top:8px;">
                                    <div style="font-size:14px;">Dear {{ $notifiable->name ?? 'Vendor' }},</div>
                                    <div style="font-size:12px;opacity:0.8;margin-top:4px;">Vendor - PT Oneject Indonesia</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:24px;">
                        <p style="margin:0 0 16px 0;color:#374151;">Unfortunately, your booking request has been rejected.</p>

                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                            <tr>
                                <td style="padding:12px;background-color:#fef2f2;border-radius:8px;">
                                    <table width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="padding:4px 0;font-size:14px;"><strong>Ticket:</strong> {{ $ticketNumber }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:4px 0;font-size:14px;"><strong>Requested Time:</strong> {{ $plannedDate }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:4px 0;font-size:14px;color:#dc2626;"><strong>Reason:</strong> {{ $reason }}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="center" style="padding:16px 0;">
                                    <a href="{{ url('/vendor/bookings/create') }}"
                                       style="display:inline-block;background-color:#dc2626;color:#ffffff;padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:500;">
                                        Submit New Booking
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:16px 0 0 0;color:#374151;">You can submit a new booking request with a different time slot or gate.</p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:20px 24px;border-top:1px solid #e5e7eb;">
                        <p style="margin:0;color:#6b7280;font-size:12px;">
                            Regards,<br>
                            Warehouse – SCM – PT Oneject Indonesia
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
