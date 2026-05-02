{{-- File guide: Blade view template for resources/views/auth/verify.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 | Verify Account')

@section('content')
    <div class="min-h-screen bg-[#F2F6FB] flex items-center justify-center">
        <div class="w-[740px] text-center p-8">
            <h1 class="text-3xl font-semibold text-[#D32F2F] mb-3">Verify your Account</h1>
            <p class="text-sm text-[#5D6269] mb-3">Enter the 6-digit code sent to your email</p>

            @if (!empty($email))
                <p class="text-sm text-[#8F763F] mb-6 font-medium">{{ $email }}</p>
            @endif

            <div class="bg-white max-w-[430px] mx-auto p-12 rounded-[24px] shadow">
                <form method="POST" action="{{ route('verify.submit') }}">
                    @csrf

                    <div class="grid grid-cols-6 gap-3 mb-8">
                        <input type="text" name="code[]" maxlength="1" class="code-input h-[60px] text-center text-2xl bg-gray-100 rounded">
                        <input type="text" name="code[]" maxlength="1" class="code-input h-[60px] text-center text-2xl bg-gray-100 rounded">
                        <input type="text" name="code[]" maxlength="1" class="code-input h-[60px] text-center text-2xl bg-gray-100 rounded">
                        <input type="text" name="code[]" maxlength="1" class="code-input h-[60px] text-center text-2xl bg-gray-100 rounded">
                        <input type="text" name="code[]" maxlength="1" class="code-input h-[60px] text-center text-2xl bg-gray-100 rounded">
                        <input type="text" name="code[]" maxlength="1" class="code-input h-[60px] text-center text-2xl bg-gray-100 rounded">
                    </div>

                    <button type="submit" class="w-full bg-red-600 text-white py-3 rounded mb-4">
                        Verify Account
                    </button>
                </form>

                <form method="POST" action="{{ route('verify.resend') }}">
                    @csrf
                    <button type="submit" class="text-red-600 font-semibold">
                        Resend Code
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="[cdn.jsdelivr.net](https://cdn.jsdelivr.net/npm/sweetalert2@11)"></script>

    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: @json(session('success'))
            });
        </script>
    @endif

    @if ($errors->any())
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Verification Failed',
                html: {!! json_encode(implode('<br>', $errors->all())) !!}
            });
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const inputs = document.querySelectorAll('.code-input');

            inputs.forEach((input, index) => {
                input.addEventListener('input', function () {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    if (this.value && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                });

                input.addEventListener('keydown', function (e) {
                    if (e.key === 'Backspace' && !this.value && index > 0) {
                        inputs[index - 1].focus();
                    }
                });
            });

            if (inputs.length > 0) {
                inputs[0].focus();
            }
        });
    </script>
@endpush
