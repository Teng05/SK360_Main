{{-- File guide: Blade view template for resources/views/auth/verify.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 | Verify Account')

@section('page_css')
    {{-- This logic loads verify.css, exactly like your login.blade.php handles css --}}
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/verify.css'])
    @elseif (file_exists(resource_path('css/verify.css')))
        <style>
            {!! file_get_contents(resource_path('css/verify.css')) !!}
        </style>
    @endif
@endsection

@section('content')
    {{-- 1. Main Wrapper Div (defined in verify.css) --}}
    <div class="verify-page">


        {{-- 2. Light Blue Background Area --}}
        <div class="verify-content-area">
            <div class="verify-header-section">
                {{-- Logo (Put your logo.png in the public folder and update path) --}}
                <img src="{{ asset('images/logo.png') }}" alt="SK Logo" class="sk-logo-verify">

                <h1 class="title-verify">Verify your Account</h1>
                
                <p class="subtitle-verify">
                    Enter the code sent to your registered email or phone
                </p>

                {{-- 4. Email Badge Pill --}}
                @if (!empty($email))
                    <div class="email-badge-pill">
                        {{-- SVG Envelope Icon --}}
                        <svg class="icon-yellow" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span>{{ $email }}</span>
                    </div>
                @endif
            </div>

            {{-- 5. INNER WHITE CARD --}}
            <div class="verify-card-inner">
                    
                    {{-- Inner Header with Shield icon --}}
                    <div class="inner-header-verify">
                        <div class="shield-container">
                            {{-- SVG Shield Check Icon --}}
                             <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <h2 class="digit-text">Enter 6-Digit Code</h2>
                    </div>

                    {{-- Form: Code inputs and main verify button --}}
                    <form method="POST" action="{{ route('verify.submit') }}" class="submit-form-verify">
                        @csrf
                        <div class="input-grid-verify">
                            @for ($i = 0; $i < 6; $i++)
                                <input type="text" 
                                       name="code[]" 
                                       maxlength="1" 
                                       class="code-input"
                                       required>
                            @endfor
                        </div>

                        <button type="submit" class="verify-btn-submit">
                            Verify Account
                        </button>
                    </form>

                    {{-- Footer area: Resend option --}}
                    <p class="did-not-text">Didn't receive the code?</p>

                    <form method="POST" action="{{ route('verify.resend') }}" class="resend-form-verify">
                        @csrf
                        <button type="submit" class="resend-btn-verify">
                            
                            Resend Code
                        </button>
                    </form>
            </div> {{-- End Inner White Card --}}
        </div> {{-- End Outer Content Area --}}
    </div> {{-- End Main Page Wrapper --}}
@endsection

{{-- Keep your existing scripts section unchanged. Just ensure confirmButtonColor matches SK Red (#D32F2F) --}}
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: @json(session('success')),
                confirmButtonColor: '#D32F2F'
            });
        </script>
    @endif

    @if ($errors->any())
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Verification Failed',
                html: {!! json_encode(implode('<br>', $errors->all())) !!},
                confirmButtonColor: '#D32F2F'
            });
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const inputs = document.querySelectorAll('.code-input');

            inputs.forEach((input, index) => {
                // Focus next input when typed
                input.addEventListener('input', function () {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    if (this.value && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                });

                // Focus previous input on backspace if empty
                input.addEventListener('keydown', function (e) {
                    if (e.key === 'Backspace' && !this.value && index > 0) {
                        inputs[index - 1].focus();
                    }
                });
            });

            // Focus the first box initially
            if (inputs.length > 0) {
                inputs[0].focus();
            }
        });
    </script>
@endpush