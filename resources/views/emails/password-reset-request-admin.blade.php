<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>[{{ $appName }}] Password Reset Request</title>
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
                                <td align="left" style="display:flex;align-items:center;gap:12px;">
                                    <img src="{{ asset('img/e-DCS full putih.png') }}" alt="{{ $appName }}" style="height:36px;width:auto;border-radius:4px;display:block;">
                                </td>
                            </tr>
                            <tr>
                                <td style="padding-top:12px;">
                                    <div style="font-size:18px;font-weight:600;">Password Reset Request</div>
                                    <div style="font-size:13px;opacity:0.9;">{{ $user->full_name ?? $user->username ?? 'Vendor' }} &mdash; {{ $appName }}</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:20px 24px 8px;color:#0f172a;font-size:14px;">
                        <p style="margin:0 0 12px;">Dear Administrator,</p>
                        <p style="margin:0 0 12px;">
                            A vendor user has requested a password reset from the <strong>Vendor Profile</strong> page.
                            Please review the user details below and proceed to update the password if appropriate.
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 24px 16px;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:13px;color:#111827;">
                            <tr>
                                <td colspan="2" style="padding-bottom:6px;font-weight:600;color:#4b5563;">User Details</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0;width:140px;color:#6b7280;">Name</td>
                                <td style="padding:4px 0;">{{ $user->full_name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0;color:#6b7280;">Username</td>
                                <td style="padding:4px 0;">{{ $user->username ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0;color:#6b7280;">Email</td>
                                <td style="padding:4px 0;">{{ $user->email ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0;color:#6b7280;">NIK</td>
                                <td style="padding:4px 0;">{{ $user->nik ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0;color:#6b7280;">Role</td>
                                <td style="padding:4px 0;">{{ $user->getRoleNames()->first() ?? '-' }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 24px 20px;">
                        <div style="padding:10px 12px;border-radius:8px;background-color:#eff6ff;border:1px solid #bfdbfe;font-size:12px;color:#1e3a8a;">
                            <strong>Request Notes:</strong>
                            <div style="margin-top:4px;color:#1f2937;">{{ $reason }}</div>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding:0 24px 24px;">
                        <a href="{{ $adminEditUrl }}" target="_blank" rel="noopener"
                           style="display:inline-block;padding:10px 18px;border-radius:999px;background-color:#0f172a;color:#ffffff;text-decoration:none;font-size:13px;font-weight:600;">
                            Open User in Admin Panel
                        </a>
                        <div style="margin-top:8px;font-size:11px;color:#6b7280;">
                            This link will take you directly to the user management page in {{ $appName }}.
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:12px 24px 18px;border-top:1px solid #e5e7eb;color:#9ca3af;font-size:11px;text-align:center;">
                        <div>{{ $companyName }} &bull; {{ $appName }}</div>
                        <div style="margin-top:2px;">This is an automated notification. Please do not reply directly to this email.</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
