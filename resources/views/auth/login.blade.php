{{-- File guide: Blade view template for resources/views/auth/login.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 | Login')

@section('page_css')
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/sk360-login.css') }}?v={{ @filemtime(public_path('css/sk360-login.css')) }}">
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

                <a class="sk-auth__back" href="{{ url('/') }}">
                    @include('auth.partials.login-icon', ['icon' => 'chevron-left', 'iconSize' => 16])
                    Back to home
                </a>
            </div>

            <div>
                <h1 class="sk-auth__headline">Welcome Back!</h1>

                <p class="sk-auth__lead">
                    Access your dashboard to manage reports, coordinate with your team,
                    and drive youth governance forward.
                </p>
            </div>

            <div class="sk-auth__features">
                <div class="sk-auth__feature">
                    <span class="sk-auth__feature-icon">
                        @include('auth.partials.login-icon', ['icon' => 'shield-check', 'iconSize' => 21])
                    </span>
                    <div>
                        <b>Secure Access</b>
                        <p>Role-based authentication for data protection</p>
                    </div>
                </div>

                <div class="sk-auth__feature">
                    <span class="sk-auth__feature-icon">
                        @include('auth.partials.login-icon', ['icon' => 'layout-dashboard', 'iconSize' => 21])
                    </span>
                    <div>
                        <b>Centralized Dashboard</b>
                        <p>All your tools in one place</p>
                    </div>
                </div>

                <div class="sk-auth__feature">
                    <span class="sk-auth__feature-icon">
                        @include('auth.partials.login-icon', ['icon' => 'users', 'iconSize' => 21])
                    </span>
                    <div>
                        <b>Real-Time Collaboration</b>
                        <p>Connect with SK officials instantly</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="sk-auth__panel">
            <div class="sk-auth__form">
                <span class="sk-eyebrow"><span class="sk-dot"></span>Official Account</span>
                <h2 class="sk-auth__title">Sign In</h2>
                <p class="sk-auth__subtitle">Enter your official account credentials</p>

                <form method="POST" action="{{ route('login.submit') }}">
                    @csrf

                    <label class="sk-field" for="email">
                        <span class="sk-field__label">Email Address</span>
                        <span class="sk-field__control">
                            @include('auth.partials.login-icon', ['icon' => 'mail', 'iconSize' => 18])
                            <input
                                id="email"
                                type="email"
                                name="email"
                                placeholder="x.sk@gmail.com"
                                value="{{ old('email') }}"
                                autocomplete="email"
                                required
                            >
                        </span>
                    </label>

                    <label class="sk-field" for="password">
                        <span class="sk-field__label">Password</span>
                        <span class="sk-field__control">
                            @include('auth.partials.login-icon', ['icon' => 'lock', 'iconSize' => 18])
                            <input
                                id="password"
                                type="password"
                                name="password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required
                            >
                        </span>
                    </label>

                    <div class="sk-auth__options">
                        <label class="sk-check">
                            <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                            Remember me
                        </label>

                        <a class="sk-link" href="{{ route('password.request') }}">Forgot Password?</a>
                    </div>

                    <button class="sk-btn sk-btn--primary sk-btn--lg" type="submit" style="width: 100%; margin-top: 28px;">
                        Sign In
                        @include('auth.partials.login-icon', ['icon' => 'arrow-right', 'iconSize' => 18])
                    </button>
                </form>

                <div class="sk-auth__footer">
                    @include('auth.partials.login-icon', ['icon' => 'shield-check', 'iconSize' => 17])
                    <span>SK 360&deg; &middot; Sangguniang Kabataan of Lipa City</span>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@if(session('verified'))
<script>
Swal.fire({
    icon:'success',
    title:'Success',
    text:@json(session('verified'))
});
</script>
@endif

@if($errors->has('login'))
<script>
Swal.fire({
    icon:'error',
    title:'Login Failed',
    text:@json($errors->first('login'))
});
</script>
@elseif($errors->any())
<script>
Swal.fire({
    icon:'error',
    title:'Login Failed',
    html:{!! json_encode(implode('<br>',$errors->all())) !!}
});
</script>
@endif
@endpush