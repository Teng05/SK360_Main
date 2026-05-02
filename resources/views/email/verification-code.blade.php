{{-- File guide: Blade view template for resources/views/email/verification-code.blade.php. --}}
<div style="font-family: Arial, sans-serif;">
    <h2>Verify your account</h2>
    <p>Hello {{ $first_name }},</p>
    <p>Your verification code is:</p>
    <h1 style="letter-spacing: 4px; color: #D32F2F;">{{ $verification_code }}</h1>
    <p>This code will expire in 1 hour.</p>
</div>
