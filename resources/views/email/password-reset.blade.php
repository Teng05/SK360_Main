{{-- File guide: Blade email template for resources/views/email/password-reset.blade.php. --}}
<div style="font-family: Arial, sans-serif; color: #222;">
    <h2 style="color: #d32f2f;">Reset your SK360 password</h2>
    <p>Hello {{ $first_name ?? 'there' }},</p>
    <p>Use this verification code to reset your password. This code expires in 15 minutes.</p>
    <p style="display: inline-block; background: #fce4e4; color: #d32f2f; padding: 14px 22px; border-radius: 8px; font-size: 28px; font-weight: bold; letter-spacing: 4px;">
        {{ $reset_code }}
    </p>
    <p>If you did not request a password reset, you can ignore this email.</p>
</div>