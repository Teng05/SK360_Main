{{-- File guide: Blade view template for resources/views/auth/login.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 | Login')

@section('page_css')
    <style>
        {!! file_get_contents(resource_path('css/login.css')) !!}
    </style>
@endsection

@section('content')
    <div class="login-page">
        <div class="container">
            <div class="left-panel">
                <div class="back">
                    <a href="{{ url('/') }}">Back to home</a>
                </div>

                <h2 class="logo">SK 360&deg;</h2>
                <h1>Welcome Back!</h1>

                <p>
                    Access your dashboard to manage reports, coordinate with your team,
                    and drive youth governance forward.
                </p>

                <div class="features">
                    <div class="feature">
                        <span class="feature-icon">Secure</span>
                        <div>
                            <b>Secure Access</b>
                            <p>Role-based authentication for data protection</p>
                        </div>
                    </div>

                    <div class="feature">
                        <span class="feature-icon">Dash</span>
                        <div>
                            <b>Centralized Dashboard</b>
                            <p>All your tools in one place</p>
                        </div>
                    </div>

                    <div class="feature">
                        <span class="feature-icon">Team</span>
                        <div>
                            <b>Real-Time Collaboration</b>
                            <p>Connect with SK officials instantly</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right-panel">
                <h2>Sign In</h2>
                <p class="subtitle">Enter your official account credentials</p>

                <form method="POST" action="{{ route('login.submit') }}">
                    @csrf

                    <label for="email">Email Address</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        placeholder="x.sk@gmail.com"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                    >

                    <label for="password">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >

                    <div class="options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                            Remember me
                        </label>

                        <a href="{{ route('password.request') }}">Forgot Password?</a>
                    </div>

                    <button class="login-btn" type="submit">Sign In</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@if (session('verified'))
<script>
Swal.fire({
    icon:'success',
    title:'Success',
    text:@json(session('verified'))
});
</script>
@endif

@if ($errors->any())
<script>
Swal.fire({
    icon:'error',
    title:'Login Failed',
    html:{!! json_encode(implode('<br>',$errors->all())) !!}
});
</script>
@endif
@endpush
