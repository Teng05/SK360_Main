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

    .reset-container {
        max-width: 450px;
        width: 100%;
    }

    .auth-card {
        background: #fff;
        border-radius: 30px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        width: 100%;
        box-sizing: border-box;
        padding: 40px;
    }

    .sk-logo,
    .success-icon-circle {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.08);
        font-weight: 900;
    }

    .sk-logo {
        background: #fff;
        color: var(--sk-red);
        font-size: 34px;
    }

    .success-icon-circle {
        background-color: #ffca28;
        color: #fff;
        font-size: 38px;
    }

    .main-title {
        color: var(--sk-red);
        font-size: 1.75rem;
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
        transition: 0.2s;
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
        font-weight: 800;
        flex: 0 0 28px;
    }

    .form-control-custom {
        box-sizing: border-box;
        width: 100%;
        background-color: #f1f3f5;
        border: 0;
        border-radius: 10px;
        padding: 13px 15px;
        outline: none;
        font: inherit;
    }

    .form-control-custom:focus {
        box-shadow: 0 0 0 3px rgba(211, 47, 47, 0.16);
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
        font: inherit;
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
        align-items: center;
        gap: 8px;
        margin-bottom: 15px;
        max-width: 100%;
        overflow-wrap: anywhere;
    }

    .expiry-alert {
        background-color: #fff5f5;
        border: 1px solid #ffebed;
        color: #d32f2f;
        padding: 10px;
        border-radius: 8px;
        font-size: 0.8rem;
        margin: 15px 0 0;
    }

    .help-text {
        color: #6b7280;
        font-size: 0.8rem;
        line-height: 1.5;
        margin: 0;
    }

    .back-link {
        text-decoration: none;
        color: #6b7280;
        font-size: 0.85rem;
        display: inline-flex;
        justify-content: center;
        gap: 6px;
        margin-top: 16px;
        background: transparent;
        border: 0;
        cursor: pointer;
        font: inherit;
    }

    .back-link.danger {
        color: var(--sk-red);
        font-weight: 800;
    }

    .hidden {
        display: none;
    }
</style>
@endsection

@section('content')
@php
    $resetMethod = session('reset_method');
    $resetTarget = session('reset_target', session('reset_phone'));
    $showEmailVerify = session('show_email_verify') || session('reset_email');
    $showPhoneVerify = session('show_phone_verify') || session('reset_phone');
    $showCodeVerify = $showEmailVerify || $showPhoneVerify;
    $showSuccess = session()->has('reset_success') || $showCodeVerify;
@endphp

<div class="reset-page">
    <div class="reset-container">
        <div id="request-view" class="{{ $showSuccess ? 'hidden' : '' }}">
            <div class="sk-logo">SK</div>
            <h2 class="main-title">Reset Your Password</h2>
            <p class="sub-text">Choose your preferred reset method</p>

            <div class="auth-card">
                @if ($errors->any())
                    <div class="notice error">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="method-toggle">
                    <button type="button" class="btn-toggle {{ old('method', 'email') === 'email' ? 'active' : '' }}" id="btn-email" onclick="switchMethod('email')">
                        Email
                    </button>
                    <button type="button" class="btn-toggle {{ old('method') === 'phone' ? 'active' : '' }}" id="btn-phone" onclick="switchMethod('phone')">
                        Phone
                    </button>
                </div>

                <form method="POST" action="{{ route('password.email') }}" id="email-form" class="{{ old('method', 'email') === 'email' ? '' : 'hidden' }}">
                    @csrf
                    <input type="hidden" name="method" value="email">
                    <label class="form-label-custom">
                        <span class="label-icon">@</span> Email Address
                    </label>
                    <input type="email" name="email" class="form-control-custom" placeholder="sk360@gmail.com" value="{{ old('email') }}" required>
                    <button type="submit" class="btn-sk-primary">Send Reset Code</button>
                </form>

                <form method="POST" action="{{ route('password.email') }}" id="phone-form" class="{{ old('method') === 'phone' ? '' : 'hidden' }}">
                    @csrf
                    <input type="hidden" name="method" value="phone">
                    <label class="form-label-custom">
                        <span class="label-icon">P</span> Phone Number
                    </label>
                    <input type="text" name="phone" class="form-control-custom" placeholder="+639123456789" value="{{ old('phone') }}" required>
                    <button type="submit" class="btn-sk-primary">Send Reset Code</button>
                </form>

                <a href="{{ route('login') }}" class="back-link">Back to Login</a>
            </div>
        </div>

        <div id="success-view" class="{{ $showSuccess ? '' : 'hidden' }}">
            <div class="success-icon-circle">{{ $resetMethod === 'phone' ? '#' : '@' }}</div>
            <h2 class="main-title">Reset Code Sent!</h2>
            <p class="sub-text">
                {{ $resetMethod === 'phone' ? 'Check your phone for password reset instructions' : 'Check your email for password reset instructions' }}
            </p>

            <div class="auth-card">
                @if ($errors->any())
                    <div class="notice error">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                @if ($resetTarget)
                    <div class="info-badge">
                        <span>{{ $resetMethod === 'phone' ? 'Phone' : 'Email' }}</span>
                        <span>{{ $resetTarget }}</span>
                    </div>
                @endif

                @if ($showCodeVerify)
                    <form method="POST" action="{{ $showEmailVerify ? route('password.verify-email') : route('password.verify-phone') }}">
                        @csrf
                        <label class="form-label-custom">
                            <span class="label-icon">#</span> {{ $showEmailVerify ? 'Email Code' : 'SMS Code' }}
                        </label>
                        <input type="text" name="code" class="form-control-custom" placeholder="6-digit code" maxlength="6" inputmode="numeric" required>

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
                    <p class="help-text">
                        We sent instructions to your registered account. Please check your inbox and follow the instructions to reset your password.
                    </p>
                    <div class="expiry-alert">Code expires in 15 minutes</div>
                    <a href="{{ route('login') }}" class="btn-sk-primary" style="box-sizing: border-box; display: inline-block; text-decoration: none;">Back to Login</a>
                @endif

                <button type="button" class="back-link danger" onclick="resetFlow()">Try Different Method</button>
            </div>
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

    function resetFlow() {
        document.getElementById('request-view').classList.remove('hidden');
        document.getElementById('success-view').classList.add('hidden');
    }
</script>
@endpush
