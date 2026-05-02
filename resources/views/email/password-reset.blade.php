{{-- File guide: Blade email template for resources/views/email/password-reset.blade.php. --}}
<div style="font-family: Arial, sans-serif; color: #222;">
    <h2 style="color: #d32f2f;">Reset your SK360 password</h2>
    <p>Hello {{ $first_name ?? 'there' }},</p>
    <p>Click the button below to create a new password. This link expires in 15 minutes.</p>
    <p>
        <a href="{{ $reset_url }}" style="display: inline-block; background: #d32f2f; color: #fff; padding: 12px 18px; border-radius: 8px; text-decoration: none; font-weight: bold;">
            Reset Password
        </a>
    </p>
    <p>If the button does not work, open this link:</p>
    <p style="word-break: break-all;">{{ $reset_url }}</p>
</div>
