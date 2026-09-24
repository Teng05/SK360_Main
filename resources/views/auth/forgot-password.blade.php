{{-- File guide: Blade view template for resources/views/auth/forgot-password.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 | Forgot Password')

@section('page_css')
<style>
    /* Styled with the shared design system tokens (public/css/sk360-ui.css). */
    body {
        margin: 0;
        min-height: 100vh;
        background:
            radial-gradient(60% 50% at 100% 0%, rgba(220, 38, 38, .08), transparent 70%),
            radial-gradient(50% 40% at 0% 100%, rgba(46, 98, 209, .05), transparent 70%),
            var(--sk-bg);
    }

    .reset-page, .reset-page * { box-sizing: border-box; }

    .reset-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 32px 20px;
        text-align: center;
    }

    .reset-container { max-width: 460px; width: 100%; }

    .auth-card {
        background: var(--sk-surface);
        border: 1px solid var(--sk-border);
        border-radius: 24px;
        box-shadow: var(--sk-shadow-lg);
        width: 100%;
        padding: 32px;
        text-align: left;
    }

    .sk-logo,
    .success-icon-circle {
        width: 72px;
        height: 72px;
        margin: 0 auto 20px;
        border-radius: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: var(--sk-shadow);
    }

    .sk-logo { background: #fff; border: 1px solid var(--sk-border); padding: 8px; }
    .sk-logo img { width: 100%; height: 100%; object-fit: contain; }

    .success-icon-circle { background: var(--sk-yellow-soft); color: var(--sk-yellow); border: 1px solid #f5e3a8; }

    .main-title {
        color: var(--sk-ink);
        font-size: 28px;
        font-weight: 800;
        letter-spacing: -.025em;
        margin: 0 0 8px;
    }

    .sub-text { color: var(--sk-muted); font-size: 15px; margin: 0 0 24px; }

    .method-toggle {
        background: var(--sk-field);
        border-radius: 14px;
        padding: 5px;
        display: flex;
        gap: 4px;
        margin-bottom: 24px;
    }

    .btn-toggle {
        flex: 1;
        border-radius: 10px;
        padding: 10px;
        border: 0;
        font: inherit;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        background: transparent;
        color: var(--sk-muted);
        transition: background-color var(--sk-fast) var(--sk-ease), color var(--sk-fast) var(--sk-ease), box-shadow var(--sk-fast) var(--sk-ease);
    }

    .btn-toggle:hover { color: var(--sk-ink); }

    .btn-toggle.active {
        background: var(--sk-surface);
        color: var(--sk-red-hover);
        box-shadow: var(--sk-shadow-sm);
    }

    .form-label-custom {
        font-size: 14px;
        font-weight: 700;
        color: var(--sk-text);
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;
    }

    .label-icon {
        background: var(--sk-red-soft);
        color: var(--sk-red);
        width: 30px;
        height: 30px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 30px;
    }

    .form-control-custom {
        width: 100%;
        height: 50px;
        background: var(--sk-field);
        border: 1px solid var(--sk-border);
        border-radius: var(--sk-radius-control);
        padding: 0 16px;
        outline: none;
        font: inherit;
        font-size: 15px;
        color: var(--sk-ink);
        transition: border-color var(--sk-fast) var(--sk-ease), box-shadow var(--sk-fast) var(--sk-ease), background-color var(--sk-fast) var(--sk-ease);
    }

    .form-control-custom:focus {
        background: var(--sk-surface);
        border-color: rgba(220, 38, 38, .5);
        box-shadow: 0 0 0 4px rgba(220, 38, 38, .1);
    }

    .btn-sk-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: var(--sk-red);
        border: 0;
        border-radius: 14px;
        min-height: 50px;
        padding: 0 20px;
        width: 100%;
        margin-top: 22px;
        color: #fff;
        cursor: pointer;
        font: inherit;
        font-size: 15px;
        font-weight: 700;
        box-shadow: var(--sk-shadow-red);
        transition: background-color var(--sk-fast) var(--sk-ease), transform var(--sk-fast) var(--sk-ease);
    }

    .btn-sk-primary:hover { background: var(--sk-red-hover); }
    .btn-sk-primary:active { transform: translateY(1px); }

    .notice {
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 18px;
        font-size: 14px;
        font-weight: 600;
    }

    .notice.error {
        background: var(--sk-red-soft);
        color: var(--sk-red-deep);
        border: 1px solid var(--sk-red-line);
    }

    .info-badge {
        background: var(--sk-yellow-soft);
        border: 1px solid #f5e3a8;
        color: #7d5100;
        padding: 9px 14px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 18px;
        max-width: 100%;
        overflow-wrap: anywhere;
    }

    .expiry-alert {
        background: var(--sk-red-soft);
        border: 1px solid var(--sk-red-line);
        color: var(--sk-red-deep);
        padding: 10px 14px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        margin: 16px 0 0;
    }

    .help-text { color: var(--sk-muted); font-size: 14px; line-height: 1.6; margin: 0; }

    .back-link {
        text-decoration: none;
        color: var(--sk-muted);
        font-size: 14px;
        font-weight: 700;
        display: flex;
        width: 100%;
        justify-content: center;
        align-items: center;
        gap: 6px;
        margin-top: 18px;
        background: transparent;
        border: 0;
        cursor: pointer;
        font-family: inherit;
    }

    .back-link:hover { color: var(--sk-ink); }
    .back-link.danger { color: var(--sk-red-hover); }

    .hidden { display: none; }
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
            <div class="sk-logo"><img src="{{ asset('images/logo.png') }}" alt="SK 360 logo"></div>
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
                        <span class="label-icon">@include('partials.ui.icon', ['icon' => 'mail', 'iconSize' => 16])</span> Email Address
                    </label>
                    <input type="email" name="email" class="form-control-custom" placeholder="sk360@gmail.com" value="{{ old('email') }}" required>
                    <button type="submit" class="btn-sk-primary">Send Reset Code</button>
                </form>

                <form method="POST" action="{{ route('password.email') }}" id="phone-form" class="{{ old('method') === 'phone' ? '' : 'hidden' }}">
                    @csrf
                    <input type="hidden" name="method" value="phone">
                    <label class="form-label-custom">
                        <span class="label-icon">@include('partials.ui.icon', ['icon' => 'phone', 'iconSize' => 16])</span> Phone Number
                    </label>
                    <input type="text" name="phone" class="form-control-custom" placeholder="+639123456789" value="{{ old('phone') }}" required>
                    <button type="submit" class="btn-sk-primary">Send Reset Code</button>
                </form>

                <a href="{{ route('login') }}" class="back-link">Back to Login</a>
            </div>
        </div>

        <div id="success-view" class="{{ $showSuccess ? '' : 'hidden' }}">
            <div class="success-icon-circle">@include('partials.ui.icon', ['icon' => $resetMethod === 'phone' ? 'phone' : 'mail', 'iconSize' => 30])</div>
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
                            <span class="label-icon">@include('partials.ui.icon', ['icon' => 'key-round', 'iconSize' => 16])</span> {{ $showEmailVerify ? 'Email Code' : 'SMS Code' }}
                        </label>
                        <input type="text" name="code" class="form-control-custom" placeholder="6-digit code" maxlength="6" inputmode="numeric" required>

                        <label class="form-label-custom" style="margin-top: 16px;">
                            <span class="label-icon">@include('partials.ui.icon', ['icon' => 'lock', 'iconSize' => 16])</span> New Password
                        </label>
                        <input type="password" name="password" class="form-control-custom" placeholder="New password" required>

                        <label class="form-label-custom" style="margin-top: 16px;">
                            <span class="label-icon">@include('partials.ui.icon', ['icon' => 'lock', 'iconSize' => 16])</span> Confirm Password
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