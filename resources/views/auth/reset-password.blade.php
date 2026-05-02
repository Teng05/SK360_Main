{{-- File guide: Blade view template for resources/views/auth/reset-password.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 | Reset Password')

@section('page_css')
<style>
    body {
        margin: 0;
        background: #f2f6fb;
        font-family: Inter, "Segoe UI", Tahoma, sans-serif;
    }

    .reset-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
    }

    .auth-card {
        background: #fff;
        border-radius: 30px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        max-width: 450px;
        width: 100%;
        padding: 40px;
    }

    h1 {
        color: #d32f2f;
        margin: 0 0 8px;
        text-align: center;
    }

    p {
        color: #6b7280;
        text-align: center;
        margin: 0 0 24px;
    }

    label {
        display: block;
        margin: 14px 0 8px;
        font-size: 0.85rem;
        font-weight: 700;
    }

    input {
        box-sizing: border-box;
        width: 100%;
        background-color: #f1f3f5;
        border: 0;
        border-radius: 10px;
        padding: 13px 15px;
        outline: none;
    }

    button {
        background-color: #d32f2f;
        border: 0;
        border-radius: 10px;
        padding: 13px;
        font-weight: 700;
        width: 100%;
        margin-top: 20px;
        color: #fff;
        cursor: pointer;
    }

    .error {
        background: #fff5f5;
        color: #d32f2f;
        border: 1px solid #ffebed;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
        font-size: 0.85rem;
    }

    .back-link {
        display: block;
        margin-top: 16px;
        text-align: center;
        color: #6b7280;
        text-decoration: none;
        font-size: 0.85rem;
    }
</style>
@endsection

@section('content')
<div class="reset-page">
    <form method="POST" action="{{ route('password.update') }}" class="auth-card">
        @csrf
        <h1>Create New Password</h1>
        <p>Enter a new password for your SK360 account.</p>

        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <input type="hidden" name="token" value="{{ $token }}">

        <label>Email Address</label>
        <input type="email" name="email" value="{{ old('email', $email) }}" required>

        <label>New Password</label>
        <input type="password" name="password" required>

        <label>Confirm Password</label>
        <input type="password" name="password_confirmation" required>

        <button type="submit">Reset Password</button>
        <a href="{{ route('login') }}" class="back-link">Back to Login</a>
    </form>
</div>
@endsection
