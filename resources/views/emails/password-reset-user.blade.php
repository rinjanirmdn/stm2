<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>[{{ $appName }}] Your Password Has Been Reset</title>
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
                                    <div style="font-size:18px;font-weight:600;">Password Reset Successful</div>
                                    <div style="font-size:13px;opacity:0.9;">{{ $appName }}</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:24px 24px 8px 24px;">
                        <p style="margin:0 0 12px 0;font-size:14px;color:#0f172a;">Hi {{ $userName ?: 'User' }},</p>
                        <p style="margin:0 0 12px 0;font-size:14px;color:#0f172a;">
                            Your account password has been reset by the administrator.
                        </p>
                        <p style="margin:0 0 12px 0;font-size:14px;color:#0f172a;">
                            Here are your new login details:
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 24px 16px 24px;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background-color:#f9fafb;border-radius:10px;border:1px solid #e5e7eb;">
                            <tr>
                                <td style="padding:14px 16px;font-size:13px;color:#0f172a;border-bottom:1px solid #e5e7eb;">
                                    <strong>Email / Username</strong><br>
                                    <span style="color:#334155;">{{ $userEmail }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:14px 16px;font-size:13px;color:#0f172a;">
                                    <strong>New Password</strong><br>
                                    <span style="color:#b91c1c;">{{ $plainPassword }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:8px 24px 24px 24px;">
                        <p style="margin:0 0 10px 0;font-size:12px;color:#6b7280;">
                            For security reasons, please sign in as soon as possible and change this password to a new one that only you know.
                        </p>
                        <p style="margin:0;font-size:12px;color:#9ca3af;">
                            If you did not request this password change, please contact your administrator immediately.
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 24px 20px 24px;border-top:1px solid #e5e7eb;background-color:#f9fafb;text-align:center;">
                        <p style="margin:0;font-size:11px;color:#9ca3af;">This is an automated message from {{ $appName }}. Please do not reply to this email.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
