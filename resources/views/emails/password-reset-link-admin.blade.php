<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>[{{ $appName }}] Password Reset Link</title>
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
                                    <div style="font-size:18px;font-weight:600;">Password Reset</div>
                                    <div style="font-size:13px;opacity:0.9;">{{ $appName }}</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:24px 24px 8px 24px;">
                        <p style="margin:0 0 12px 0;font-size:14px;color:#0f172a;">Hi {{ $userName ?: 'Administrator' }},</p>
                        <p style="margin:0 0 12px 0;font-size:14px;color:#0f172a;">
                            We received a request to reset your administrator account password. Click the button below to set a new password:
                        </p>
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding:8px 24px 20px;">
                        <a href="{{ $resetUrl }}" target="_blank" rel="noopener"
                           style="display:inline-block;padding:12px 28px;border-radius:999px;background-color:#0f172a;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;">
                            Reset Password
                        </a>
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 24px 16px 24px;">
                        <div style="padding:12px 14px;border-radius:8px;background-color:#fef3c7;border:1px solid #fcd34d;font-size:12px;color:#92400e;">
                            <strong>⏱ This link will expire in {{ $expiryMinutes }} minutes.</strong>
                            <div style="margin-top:4px;">If you didn't request this, you can safely ignore this email — your password will remain unchanged.</div>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 24px 20px 24px;">
                        <p style="margin:0 0 6px 0;font-size:12px;color:#6b7280;">
                            If the button above doesn't work, copy and paste this link into your browser:
                        </p>
                        <p style="margin:0;font-size:11px;color:#3b82f6;word-break:break-all;">
                            {{ $resetUrl }}
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 24px 20px 24px;border-top:1px solid #e5e7eb;background-color:#f9fafb;text-align:center;">
                        <p style="margin:0;font-size:11px;color:#9ca3af;">{{ $companyName }} &bull; {{ $appName }}</p>
                        <p style="margin:4px 0 0;font-size:11px;color:#9ca3af;">This is an automated message. Please do not reply to this email.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
