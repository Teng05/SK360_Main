<div style="font-family: Arial, sans-serif; color: #222;">
    <h2 style="color: #d32f2f;">Verify your SK360 password change</h2>
    <p>Hello {{ $first_name ?? 'there' }},</p>
    <p>Use this verification code to confirm your password change. This code expires in 15 minutes.</p>
    <p style="display: inline-block; background: #fce4e4; color: #d32f2f; padding: 14px 22px; border-radius: 8px; font-size: 28px; font-weight: bold; letter-spacing: 4px;">
        {{ $verification_code }}
    </p>
    <p>If you did not request this change, secure your account immediately.</p>
</div>
