{{-- File guide: Blade view template for resources/views/auth/register.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 | Register')

@section('page_css')
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/register.css'])
    @elseif (file_exists(resource_path('css/register.css')))
        <style>
            {!! file_get_contents(resource_path('css/register.css')) !!}
        </style>
    @endif

    <style>
        input, select { display:block; margin-bottom:5px; padding:8px; width:100%; box-sizing:border-box; }
        input.valid, select.valid { border: 2px solid green; }
        input.invalid, select.invalid { border: 2px solid red; }
        button:disabled { background-color: gray; cursor: not-allowed; }
        .hidden { display:none; }
    </style>
@endsection

@section('content')
    <div class="container">
        <div class="left">
            <div class="back"><a href="{{ url('/') }}">Back to home</a></div>
            <h2>SK 360°</h2>
            <h3>Join SK 360°</h3>
            <p>Create your account to access the centralized platform for youth governance in Lipa City.</p>

            <div class="steps">
                <div class="step"><div class="circle">1</div><b>Personal Information</b></div>
                <div class="step"><div class="circle">2</div><b>Security Setup</b></div>
            </div>

            <hr>

            <div class="signin-link">
                <p>Already have an account?</p>
                <a href="{{ route('login') }}">🔑 Sign In Here</a>
            </div>
        </div>

        <div class="right">
            <form id="registerForm" method="POST" action="{{ route('register.submit') }}">
                @csrf

                <div id="info">
                    <h2>Create Account</h2>
                    <p class="subtitle">Fill in your information to get started</p>

                    <label>First Name</label>
                    <input type="text" name="first_name" placeholder="Juan" value="{{ old('first_name') }}">

                    <label>Last Name</label>
                    <input type="text" name="last_name" placeholder="Dela Cruz" value="{{ old('last_name') }}">

                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="sk360@gmail.com" value="{{ old('email') }}">

                    <label>Phone Number</label>
                    <input type="text" name="phone_number" placeholder="09123456789" maxlength="11" value="{{ old('phone_number') }}">

                    <label>Barangay</label>
                    <select name="barangay_id">
                        <option value="">Select your barangay</option>
                        @foreach($barangays as $b)
                            <option value="{{ $b->barangay_id }}" {{ old('barangay_id') == $b->barangay_id ? 'selected' : '' }}>
                                {{ $b->barangay_name }}
                            </option>
                        @endforeach
                    </select>

                    <button type="button" onclick="nextStep()">Continue</button>
                </div>

                <div id="pass" class="hidden">
                    <h2>Set Your Password</h2>
                    <p class="subtitle">Choose a strong password for your account</p>

                    <label>Password</label>
                    <input type="password" name="password" placeholder="Create a strong password">

                    <label>Confirm Password</label>
                    <input type="password" name="password_confirmation" placeholder="Re-enter your password">

                    <div class="btn-group">
                        <button type="button" class="back-btn" onclick="prevStep()">Back</button>
                        <button type="submit">Create Account</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="[cdn.jsdelivr.net](https://cdn.jsdelivr.net/npm/sweetalert2@11)"></script>

    @if ($errors->any())
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Registration Failed',
                html: {!! json_encode(implode('<br>', $errors->all())) !!}
            });
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const firstName = document.querySelector('input[name="first_name"]');
            const lastName = document.querySelector('input[name="last_name"]');
            const email = document.querySelector('input[name="email"]');
            const phoneNumber = document.querySelector('input[name="phone_number"]');
            const barangay = document.querySelector('select[name="barangay_id"]');
            const continueBtn = document.querySelector('button[onclick="nextStep()"]');

            function validateFirstName() {
                const value = firstName.value.trim();
                const isValid = value.length > 0;
                firstName.classList.toggle('valid', isValid);
                firstName.classList.toggle('invalid', !isValid && value.length > 0);
                return isValid;
            }

            function validateLastName() {
                const value = lastName.value.trim();
                const isValid = value.length > 0;
                lastName.classList.toggle('valid', isValid);
                lastName.classList.toggle('invalid', !isValid && value.length > 0);
                return isValid;
            }

            function validateEmail() {
                const value = email.value.trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                const isValid = value.length > 0 && emailRegex.test(value);
                email.classList.toggle('valid', isValid);
                email.classList.toggle('invalid', value.length > 0 && !isValid);
                return isValid;
            }

            function validatePhoneNumber() {
                const value = phoneNumber.value.trim();
                const isValid = /^\d{10,11}$/.test(value);
                phoneNumber.classList.toggle('valid', isValid);
                phoneNumber.classList.toggle('invalid', value.length > 0 && !isValid);
                return isValid;
            }

            function validateBarangay() {
                const value = barangay.value;
                const isValid = value !== '';
                barangay.classList.toggle('valid', isValid);
                barangay.classList.toggle('invalid', !isValid && barangay.selectedIndex > 0);
                return isValid;
            }

            function checkFormValidity() {
                const isValid = validateFirstName() &&
                                validateLastName() &&
                                validateEmail() &&
                                validatePhoneNumber() &&
                                validateBarangay();

                continueBtn.disabled = !isValid;
                return isValid;
            }

            firstName.addEventListener('input', checkFormValidity);
            lastName.addEventListener('input', checkFormValidity);
            email.addEventListener('input', checkFormValidity);
            phoneNumber.addEventListener('input', checkFormValidity);
            barangay.addEventListener('change', checkFormValidity);

            checkFormValidity();
        });

        function nextStep() {
            const firstName = document.querySelector('input[name="first_name"]').value.trim();
            const lastName = document.querySelector('input[name="last_name"]').value.trim();
            const email = document.querySelector('input[name="email"]').value.trim();
            const phoneNumber = document.querySelector('input[name="phone_number"]').value.trim();
            const barangay = document.querySelector('select[name="barangay_id"]').value;

            if (!firstName || !lastName || !email || !phoneNumber || !barangay) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Incomplete Form',
                    text: 'Please fill in all required fields.'
                });
                return;
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Email',
                    text: 'Please enter a valid email address.'
                });
                return;
            }

            if (!/^\d{10,11}$/.test(phoneNumber)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Phone Number',
                    text: 'Phone number must be 10-11 digits.'
                });
                return;
            }

            document.getElementById('info').classList.add('hidden');
            document.getElementById('pass').classList.remove('hidden');
        }

        function prevStep() {
            document.getElementById('pass').classList.add('hidden');
            document.getElementById('info').classList.remove('hidden');
        }
    </script>
@endpush
