@php
    $heading = match ($purpose) {
        'password-reset' => 'Reset your password',
        'sign-in' => 'Confirm your sign-in',
        default => 'Verify your email',
    };
    $description = match ($purpose) {
        'password-reset' => 'Enter this code in PyroSense to continue resetting your password.',
        'sign-in' => 'Enter this code in PyroSense to finish signing in with Google.',
        default => 'Welcome to PyroSense. Enter this code to verify your email and continue creating your account.',
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>{{ $heading }} | PyroSense</title>
</head>
<body style="margin:0;padding:0;width:100%;background-color:#f5f6f8;color:#202938;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;">
    <div style="display:none;font-size:1px;line-height:1px;color:#f5f6f8;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">Your PyroSense code is ready. It expires in 10 minutes.</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f5f6f8;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <!--[if mso]><table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;background-color:#ffffff;border:1px solid #e5e7eb;border-radius:20px;border-collapse:separate;overflow:hidden;">
                    <tr>
                        <td style="height:5px;font-size:1px;line-height:5px;background-color:#dc2626;border-radius:20px 20px 0 0;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="padding:28px 28px 24px;border-bottom:1px solid #f0f1f4;">
                            <p style="margin:0;font-size:25px;line-height:32px;font-weight:700;letter-spacing:-0.8px;color:#202938;">Pyro<span style="color:#dc2626;">Sense</span></p>
                            <p style="margin:4px 0 0;font-size:12px;line-height:18px;color:#667085;">Account verification</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 28px;">
                            <h1 style="margin:0 0 14px;font-size:26px;line-height:34px;font-weight:700;letter-spacing:-0.5px;color:#202938;">{{ $heading }}</h1>
                            <p style="margin:0 0 26px;font-size:15px;line-height:24px;color:#586174;">{{ $description }}</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #fecaca;border-radius:12px;background-color:#fff7f5;">
                                <tr>
                                    <td align="center" style="padding:22px 12px;">
                                        <p style="margin:0 0 12px;font-size:11px;line-height:16px;letter-spacing:1.5px;font-weight:700;text-transform:uppercase;color:#9f3030;">Your verification code</p>
                                        <p style="margin:0;font-family:'Courier New',Courier,monospace;font-size:32px;line-height:42px;letter-spacing:5px;font-weight:700;color:#991b1b;white-space:nowrap;">{{ $otpCode }}</p>
                                        <p style="margin:12px 0 0;font-size:13px;line-height:20px;color:#7a4545;">Expires in <strong>10 minutes</strong></p>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:22px 0 0;font-size:14px;line-height:22px;color:#586174;">Keep this code private. Never share it with anyone.</p>
                            <p style="margin:12px 0 0;font-size:13px;line-height:21px;color:#667085;">Didn't request this code? You can safely ignore this email.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 28px;border-top:1px solid #f0f1f4;background-color:#fafbfc;border-radius:0 0 20px 20px;">
                            <p style="margin:0;font-size:12px;line-height:19px;color:#667085;">This is an automated message from PyroSense.</p>
                        </td>
                    </tr>
                </table>
                <!--[if mso]></td></tr></table><![endif]-->
                <p style="margin:20px 0 0;font-size:12px;line-height:18px;color:#667085;">PyroSense &middot; Account security</p>
            </td>
        </tr>
    </table>
</body>
</html>
