<!DOCTYPE html>
<html>
<body style="font-family: Arial, Helvetica, sans-serif; background:#f5f5f3; padding:32px 0; margin:0;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table width="480" cellpadding="0" cellspacing="0" style="background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 8px 30px rgba(15,23,42,.08);">
                    <tr>
                        <td style="background:#111111; padding:24px 32px;">
                            <span style="color:#D4AF37; font-weight:900; font-size:16px;">Performix</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="font-size:15px; color:#1e293b; margin:0 0 16px;">Hi {{ $name }},</p>
                            <p style="font-size:14px; color:#475569; line-height:1.6; margin:0 0 24px;">
                                You've been invited to join <strong>{{ $companyName }}</strong> on Performix as
                                {{ $roleLabel }}. Click the button below to set your password and get started —
                                this link expires in 24 hours.
                            </p>
                            <p style="text-align:center; margin:0 0 24px;">
                                <a href="{{ $acceptUrl }}" style="display:inline-block; background:#111111; color:#D4AF37; text-decoration:none; font-weight:700; font-size:14px; padding:12px 28px; border-radius:12px;">
                                    Accept Invite
                                </a>
                            </p>
                            <p style="font-size:12px; color:#94a3b8; line-height:1.6; margin:0;">
                                If you weren't expecting this invite, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
