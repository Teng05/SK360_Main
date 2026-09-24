{{-- File guide: Blade view template for resources/views/auth/reset-password.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 | Reset Password')

{{-- Styled by the shared design system (public/css/sk360-ui.css, .sk-auth). --}}
@section('page_css')
@endsection

@section('content')
<div class="sk-auth">
    <section class="sk-auth__brand">
        <div class="sk-auth__top">
            <div class="sk-auth__mark">
                <img src="{{ asset('images/logo.png') }}" alt="SK 360 logo">
                <div>
                    <p class="sk-auth__mark-name">SK 360&deg;</p>
                    <p class="sk-auth__mark-tag">Management System</p>
                </div>
            </div>

            <a class="sk-auth__back" href="{{ route('login') }}">
                @include('partials.ui.icon', ['icon' => 'chevron-left', 'iconSize' => 16])
                Back to Login
            </a>
        </div>

        <div>
            <h1 class="sk-auth__headline">Create New Password</h1>
            <p class="sk-auth__lead">Enter a new password for your SK360 account.</p>
        </div>

        <div></div>
    </section>

    <section class="sk-auth__panel">
        <form method="POST" action="{{ route('password.update') }}" class="sk-auth__form">
            @csrf
            <span class="sk-eyebrow"><span class="sk-dot"></span>Account Security</span>
            <h2 class="sk-auth__title">Create New Password</h2>
            <p class="sk-auth__subtitle">Enter a new password for your SK360 account.</p>

            @if ($errors->any())
                <div class="sk-alert sk-alert--error" style="margin-top: 20px;">
                    @include('partials.ui.icon', ['icon' => 'circle-alert', 'iconSize' => 18])
                    <div>
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <input type="hidden" name="token" value="{{ $token }}">

            <label class="sk-field">
                <span class="sk-field__label">Email Address</span>
                <span class="sk-field__control">
                    @include('partials.ui.icon', ['icon' => 'mail', 'iconSize' => 18])
                    <input type="email" name="email" value="{{ old('email', $email) }}" required>
                </span>
            </label>

            <label class="sk-field">
                <span class="sk-field__label">New Password</span>
                <span class="sk-field__control">
                    @include('partials.ui.icon', ['icon' => 'lock', 'iconSize' => 18])
                    <input type="password" name="password" required>
                </span>
            </label>

            <label class="sk-field">
                <span class="sk-field__label">Confirm Password</span>
                <span class="sk-field__control">
                    @include('partials.ui.icon', ['icon' => 'lock', 'iconSize' => 18])
                    <input type="password" name="password_confirmation" required>
                </span>
            </label>

            <button type="submit" class="sk-btn sk-btn--primary sk-btn--lg" style="width: 100%; margin-top: 28px;">Reset Password</button>

            <div class="sk-auth__footer" style="justify-content: center;">
                <a href="{{ route('login') }}" class="sk-link">Back to Login</a>
            </div>
        </form>
    </section>
</div>
@endsection
