{{-- File guide: Blade view template for resources/views/auth/forgot-password.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 | Forgot Password')

@section('page_css')
<style>
    :root {
        --sk-red: #d32f2f;
        --sk-light-bg: #f2f6fb;
        --sk-yellow-bg: #fefad4;
        --sk-yellow-border: #e9d9ab;
        --sk-yellow-text: #8f763f;
    }

    body {
        margin: 0;
        background-color: var(--sk-light-bg);
        font-family: Inter, "Segoe UI", Tahoma, sans-serif;
        min-height: 100vh;
    }

    .reset-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        text-align: center;
    }

    .auth-card {
        background: #fff;
        border-radius: 30px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        max-width: 450px;
        width: 100%;
        padding: 40px;
        margin: 0 auto;
    }

    .sk-logo {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        border-radius: 999px;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--sk-red);
        font-size: 34px;
        font-weight: 900;
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.08);
    }

    .main-title {
        color: var(--sk-red);
        font-weight: 800;
        margin: 0 0 8px;
    }

    .sub-text {
        color: #6b7280;
        font-size: 0.9rem;
        margin: 0 0 25px;
    }

    .method-toggle {
        background-color: #fce4e4;
        border-radius: 12px;
        padding: 6px;
        display: flex;
        margin-bottom: 25px;
    }

    .btn-toggle {
        flex: 1;
        border-radius: 8px;
        padding: 10px;
        border: 0;
        font-size: 0.9rem;
        font-weight: 700;
        cursor: pointer;
        background: transparent;
        color: var(--sk-red);
    }

    .btn-toggle.active {
        background-color: var(--sk-red);
        color: #fff;
    }

    .form-label-custom {
        font-size: 0.85rem;
        font-weight: 700;
        color: #333;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
        text-align: left;
    }

    .label-icon {
        background-color: #fce4e4;
        color: var(--sk-red);
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
    }

    .form-control-custom {
        box-sizing: border-box;
        width: 100%;
        background-color: #f1f3f5;
        border: 0;
        border-radius: 10px;
        padding: 13px 15px;
        outline: none;
    }

    .btn-sk-primary {
        background-color: var(--sk-red);
        border: 0;
        border-radius: 10px;
        padding: 13px;
        font-weight: 700;
        width: 100%;
        margin-top: 20px;
        color: #fff;
        cursor: pointer;
    }

    .btn-sk-primary:hover {
        background-color: #b71c1c;
    }

    .notice {
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
        font-size: 0.85rem;
        font-weight: 700;
    }

    .notice.success {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }

    .notice.error {
        background: #fff5f5;
        color: #d32f2f;
        border: 1px solid #ffebed;
        text-align: left;
    }

    .info-badge {
        background-color: var(--sk-yellow-bg);
        border: 1px solid var(--sk-yellow-border);
        color: var(--sk-yellow-text);
        padding: 8px 15px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 700;
        display: inline-flex;
        margin-bottom: 15px;
    }

    .back-link {
        text-decoration: none;
        color: #6b7280;
        font-size: 0.85rem;
        display: inline-flex;
        margin-top: 16px;
    }

    .hidden {
        display: none;
    }
</style>
@endsection

@section('content')
<div class="reset-page">
    <div>
        <div class="sk-logo">SK</div>
        <h2 class="main-title">Reset Your Password</h2>
        <p class="sub-text">Choose email or SMS to recover your account</p>

        <div class="auth-card">
            @if (session('reset_success'))
                <div class="notice success">{{ session('reset_success') }}</div>
            @endif

            @if ($errors->any())
                <div class="notice error">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @if (session('show_phone_verify') || session('reset_phone'))
                <form method="POST" action="{{ route('password.phone.verify') }}">
                    @csrf
                    <div class="info-badge">{{ session('reset_target', session('reset_phone')) }}</div>
                    <label class="form-label-custom">
                        <span class="label-icon">#</span> SMS Code
                    </label>
                    <input type="text" name="code" class="form-control-custom" placeholder="6-digit code" maxlength="6" required>

                    <label class="form-label-custom" style="margin-top: 16px;">
                        <span class="label-icon">*</span> New Password
                    </label>
                    <input type="password" name="password" class="form-control-custom" placeholder="New password" required>

                    <label class="form-label-custom" style="margin-top: 16px;">
                        <span class="label-icon">*</span> Confirm Password
                    </label>
                    <input type="password" name="password_confirmation" class="form-control-custom" placeholder="Confirm password" required>

                    <button type="submit" class="btn-sk-primary">Reset Password</button>
                </form>
            @else
                <div class="method-toggle">
                    <button type="button" class="btn-toggle active" id="btn-email" onclick="switchMethod('email')">Email</button>
                    <button type="button" class="btn-toggle" id="btn-phone" onclick="switchMethod('phone')">Phone</button>
                </div>

                <form method="POST" action="{{ route('password.send') }}" id="email-form">
                    @csrf
                    <input type="hidden" name="method" value="email">
                    <label class="form-label-custom">
                        <span class="label-icon">@</span> Email Address
                    </label>
                    <input type="email" name="email" class="form-control-custom" placeholder="sk360@gmail.com" value="{{ old('email') }}" required>
                    <button type="submit" class="btn-sk-primary">Send Reset Link</button>
                </form>

                <form method="POST" action="{{ route('password.send') }}" id="phone-form" class="hidden">
                    @csrf
                    <input type="hidden" name="method" value="phone">
                    <label class="form-label-custom">
                        <span class="label-icon">P</span> Phone Number
                    </label>
                    <input type="text" name="phone" class="form-control-custom" placeholder="+639123456789" value="{{ old('phone') }}" required>
                    <button type="submit" class="btn-sk-primary">Send Reset Code</button>
                </form>
            @endif

            <a href="{{ route('login') }}" class="back-link">Back to Login</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function switchMethod(method) {
        const emailForm = document.getElementById('email-form');
        const phoneForm = document.getElementById('phone-form');
        const btnEmail = document.getElementById('btn-email');
        const btnPhone = document.getElementById('btn-phone');

        emailForm.classList.toggle('hidden', method !== 'email');
        phoneForm.classList.toggle('hidden', method !== 'phone');
        btnEmail.classList.toggle('active', method === 'email');
        btnPhone.classList.toggle('active', method === 'phone');
    }
</script>
@endpush
