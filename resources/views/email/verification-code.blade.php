<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>{{ $heading }} | SK360</title></head>
<body style="margin:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#18233a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:24px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:20px;overflow:hidden;">
<tr><td style="padding:32px 28px;background-color:#a91c2c;background-image:linear-gradient(120deg,#8e1827,#c92336);border-radius:20px 20px 0 0;">
<p style="margin:0;color:#ffffff;font-size:25px;font-weight:800;letter-spacing:2px;">SK360</p>
<p style="margin:9px 0 0;color:#fde68a;font-size:12px;line-height:1.5;letter-spacing:2px;">SANGGUNIANG KABATAAN &middot; LIPA CITY</p>
</td></tr>
<tr><td style="padding:32px 28px;">
<h1 style="margin:0 0 18px;font-size:26px;line-height:1.3;color:#18233a;">{{ $heading }}</h1>
<p style="margin:0 0 12px;font-size:15px;line-height:1.7;color:#64748b;">Hello {{ $first_name ?? 'there' }},</p>
<p style="margin:0 0 26px;font-size:15px;line-height:1.7;color:#64748b;">{{ $purpose }} This code expires in <strong>{{ $expiresMinutes }} minutes</strong>.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:30px 12px;background-color:#fff1f2;border:1px solid #ffe4e6;border-radius:14px;">
<p style="margin:0;font-family:'Courier New',monospace;font-size:36px;font-weight:700;line-height:1.4;letter-spacing:6px;color:#b91c32;">{{ $code }}</p>
<p style="margin:14px 0 0;font-size:13px;line-height:1.5;color:#64748b;">One-time verification code</p>
</td></tr></table>
<p style="margin:24px 0 0;font-size:14px;line-height:1.7;color:#a91c2c;">Never share this code. SK360 staff will never ask you for it.</p>
<p style="margin:14px 0 0;font-size:13px;line-height:1.7;color:#64748b;">{{ $notice ?? 'If you did not request this code, you can safely ignore this email.' }}</p>
</td></tr>
<tr><td style="padding:20px 28px;border-top:1px solid #e2e8f0;"><p style="margin:0;font-size:12px;line-height:1.7;color:#94a3b8;">SK360 &middot; Lipa City<br>This is an automated email. Please do not reply.</p></td></tr>
</table></td></tr></table>
</body></html>
