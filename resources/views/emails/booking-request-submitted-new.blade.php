<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>[{{ $appName }}] New Booking Request - {{ $poNumber }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:24px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(15,23,42,0.12);">
                <tr>
                    <td style="padding:20px 24px;background:linear-gradient(135deg,#0f172a,#0284c7);color:#ffffff;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="left">
                                    <div style="font-size:20px;font-weight:700;color:#ffffff;">{{ $appName }}</div>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding-top:12px;">
                                    <div style="font-size:18px;font-weight:600;">New Booking Request</div>
                                    <div style="font-size:13px;opacity:0.9;">
                                        <img src="{{ url('/img/e-Docking Control System.png') }}" alt="e-Docking Control System" style="height:20px;width:auto;display:block;margin-top:4px;">
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding-top:8px;">
                                    <div style="font-size:14px;">Dear {{ $notifiable->name ?? 'Section Head' }},</div>
                                    <div style="font-size:12px;opacity:0.8;margin-top:4px;">{{ $notifiable->roles->first()->name ?? 'Approver' }} Warehouse - PT Oneject Indonesia</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:24px;">
                        <p style="margin:0 0 16px 0;color:#374151;">A new booking request has been submitted and requires your review.</p>

                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                            <tr>
                                <td style="padding:12px;background-color:#f8fafc;border-radius:8px;">
                                    <table width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="padding:4px 0;font-size:14px;"><strong>Vendor:</strong> {{ $vendorName }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:4px 0;font-size:14px;"><strong>PO/DO Number:</strong> {{ $poNumber }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:4px 0;font-size:14px;"><strong>Scheduled Time:</strong> {{ $plannedDate }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:4px 0;font-size:14px;"><strong>Direction:</strong> {{ $direction }}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="center" style="padding:16px 0;">
                                    <a href="{{ route('bookings.show', $bookingRequest->id) }}"
                                       style="display:inline-block;background-color:#0284c7;color:#ffffff;padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:500;">
                                        Review Booking
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="padding:0 0 16px 0;">
                                    <p style="margin:0;color:#6b7280;font-size:12px;font-style:italic;">
                                        If you're having trouble clicking the "Review Booking" button, copy and paste the URL below into your web browser:
                                    </p>
                                    <p style="margin:8px 0 0 0;color:#374151;font-size:12px;font-family:monospace;background-color:#f1f5f9;padding:8px;border-radius:4px;word-break:break-all;">
                                        {{ route('bookings.show', $bookingRequest->id) }}
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:16px 0 0 0;color:#374151;">Please review and approve or reject this booking request.</p>
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
