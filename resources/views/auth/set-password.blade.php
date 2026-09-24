{{-- File guide: Initial password setup page for newly created SK official accounts. --}}
@extends('layouts.app')

@section('title', 'SK 360 | Set Password')

{{-- Layout comes from the shared design system (.sk-auth); these rules only
     style the live password checks driven by the script below. --}}
@section('page_css')
<style>
    .sk-auth .sk-field input[readonly] { color: var(--sk-muted); cursor: not-allowed; }
    .sk-auth .sk-field input.input-error { border-color: #dc2626; box-shadow: 0 0 0 4px rgba(220, 38, 38, .08); }
    .sk-auth .sk-field input.input-valid { border-color: #16a34a; box-shadow: 0 0 0 4px rgba(22, 128, 60, .08); }

    .requirements {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px 12px;
        margin-top: 12px;
        padding: 12px 14px;
        border-radius: 12px;
        background: var(--sk-subtle);
        border: 1px solid var(--sk-border);
    }

    .requirement {
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: 12.5px;
        font-weight: 600;
        color: var(--sk-red-hover);
    }

    .requirement.valid { color: var(--sk-green); }

    .requirement-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 17px;
        height: 17px;
        border-radius: 99px;
        background: currentColor;
        color: inherit;
        font-size: 10px;
        font-weight: 800;
        line-height: 1;
        flex-shrink: 0;
    }

    .requirement-icon { background: var(--sk-red-soft); }
    .requirement.valid .requirement-icon { background: var(--sk-green-soft); }

    .field-message { display: none; margin-top: 8px; font-size: 13px; font-weight: 600; }
    .field-message.error { display: block; color: #b91c1c; }
    .field-message.success { display: block; color: var(--sk-green); }

    #setPasswordBtn:disabled { background: #d7dbe1; color: #6b7383; box-shadow: none; cursor: not-allowed; }

    .expired-email {
        margin-top: 20px;
        padding: 12px 14px;
        border-radius: 12px;
        background: var(--sk-subtle);
        border: 1px solid var(--sk-border);
        font-size: 14px;
        font-weight: 700;
        color: var(--sk-text);
        word-break: break-all;
    }

    @media (max-width: 480px) {
        .requirements { grid-template-columns: 1fr; }
    }
</style>
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
        </div>

        <div>
            <h1 class="sk-auth__headline">Welcome to SK 360&deg;</h1>
            <p class="sk-auth__lead">Set your password to activate your account and access the centralized platform for youth governance.</p>
        </div>

        <div></div>
    </section>

    <section class="sk-auth__panel">
        @if ($linkExpired ?? false)

            <div class="sk-auth__form">
                <span class="sk-icon-tile sk-icon-tile--lg" style="margin-bottom: 20px;">
                    @include('partials.ui.icon', ['icon' => 'triangle-alert', 'iconSize' => 26])
                </span>

                <h2 class="sk-auth__title">Setup Link Invalid or Expired</h2>

                <p class="sk-auth__subtitle">
                    This password setup link is no longer valid.
                </p>

                <div class="sk-alert sk-alert--info" style="margin-top: 20px;">
                    @include('partials.ui.icon', ['icon' => 'info', 'iconSize' => 18])
                    <span>
                        @if (($accountRole ?? '') === 'sk_secretary')
                            Please contact your SK Chairman to request a new password setup link for your account.
                        @else
                            Please contact your SK President to request a new password setup link for your account.
                        @endif
                    </span>
                </div>

                <div class="expired-email">
                    {{ $email }}
                </div>

                <a href="{{ route('login') }}" class="sk-btn sk-btn--primary sk-btn--lg" style="width: 100%; margin-top: 24px;">
                    Back to Login
                </a>
            </div>

        @else

            <form class="sk-auth__form" id="setPasswordForm" method="POST" action="{{ route('password.setup.store') }}">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <span class="sk-eyebrow"><span class="sk-dot"></span>Account Activation</span>
                <h2 class="sk-auth__title">Set Your Password</h2>
                <p class="sk-auth__subtitle">Create a secure password for your SK360 account.</p>

                <label class="sk-field">
                    <span class="sk-field__label">Email Address</span>
                    <span class="sk-field__control">
                        @include('partials.ui.icon', ['icon' => 'mail', 'iconSize' => 18])
                        <input type="email" value="{{ $email }}" readonly>
                    </span>
                </label>

                <div class="sk-field">
                    <label class="sk-field__label" for="password">Password</label>

                    <span class="sk-field__control">
                        @include('partials.ui.icon', ['icon' => 'lock', 'iconSize' => 18])
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your new password"
                            autocomplete="new-password"
                            required
                        >
                    </span>

                    <div class="requirements">
                        <div class="requirement" id="lengthRule">
                            <span class="requirement-icon">✕</span>
                            At least 8 characters
                        </div>

                        <div class="requirement" id="uppercaseRule">
                            <span class="requirement-icon">✕</span>
                            At least 1 uppercase letter
                        </div>

                        <div class="requirement" id="lowercaseRule">
                            <span class="requirement-icon">✕</span>
                            At least 1 lowercase letter
                        </div>

                        <div class="requirement" id="numberRule">
                            <span class="requirement-icon">✕</span>
                            At least 1 number
                        </div>
                    </div>
                </div>

                <div class="sk-field">
                    <label class="sk-field__label" for="password_confirmation">Confirm Password</label>

                    <span class="sk-field__control">
                        @include('partials.ui.icon', ['icon' => 'lock', 'iconSize' => 18])
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            placeholder="Re-enter your new password"
                            autocomplete="new-password"
                            required
                        >
                    </span>

                    <div id="confirmMessage" class="field-message"></div>
                </div>

                <button
                    type="submit"
                    id="setPasswordBtn"
                    class="sk-btn sk-btn--primary sk-btn--lg"
                    style="width: 100%; margin-top: 26px;"
                    disabled
                >
                    Set Password
                </button>

                <div class="sk-auth__footer" style="justify-content: center;">
                    Already set your password?
                    <a class="sk-link" href="{{ route('login') }}">Sign In</a>
                </div>
            </form>

        @endif
    </section>
</div>
@endsection

@push('scripts')

@if ($errors->getBag('default')->any())
<script>
Swal.fire({
    icon:'error',
    title:'Unable to Set Password',
    html:{!! json_encode(implode('<br>', $errors->getBag('default')->all())) !!},
    confirmButtonColor:'#dc2626'
});
</script>
@endif

<script>
document.addEventListener('DOMContentLoaded',function(){
    const password=document.getElementById('password');
    const confirmPassword=document.getElementById('password_confirmation');
    const button=document.getElementById('setPasswordBtn');

    if(!password || !confirmPassword || !button) return;

    const confirmMessage=document.getElementById('confirmMessage');

    const rules={
        length:{
            element:document.getElementById('lengthRule'),
            test:value=>value.length>=8
        },
        uppercase:{
            element:document.getElementById('uppercaseRule'),
            test:value=>/[A-Z]/.test(value)
        },
        lowercase:{
            element:document.getElementById('lowercaseRule'),
            test:value=>/[a-z]/.test(value)
        },
        number:{
            element:document.getElementById('numberRule'),
            test:value=>/[0-9]/.test(value)
        }
    };

    function updateRule(rule,valid){
        const icon=rule.element.querySelector('.requirement-icon');
        rule.element.classList.toggle('valid',valid);
        icon.textContent=valid?'✓':'✕';
    }

    function validate(){
        const value=password.value;
        const confirmValue=confirmPassword.value;

        let passwordValid=true;

        Object.values(rules).forEach(rule=>{
            const valid=rule.test(value);

            updateRule(rule,valid);

            if(!valid){
                passwordValid=false;
            }
        });

        if(value.length>0){
            password.classList.toggle('input-valid',passwordValid);
            password.classList.toggle('input-error',!passwordValid);
        }else{
            password.classList.remove('input-valid','input-error');
        }

        let confirmValid=false;

        if(confirmValue.length===0){
            confirmPassword.classList.remove('input-valid','input-error');
            confirmMessage.className='field-message';
            confirmMessage.textContent='';
        }else if(confirmValue!==value){
            confirmPassword.classList.remove('input-valid');
            confirmPassword.classList.add('input-error');
            confirmMessage.className='field-message error';
            confirmMessage.textContent='Passwords do not match.';
        }else{
            confirmValid=true;
            confirmPassword.classList.remove('input-error');
            confirmPassword.classList.add('input-valid');
            confirmMessage.className='field-message success';
            confirmMessage.textContent='Passwords match.';
        }

        button.disabled=!(passwordValid&&confirmValid);
    }

    password.addEventListener('input',validate);
    confirmPassword.addEventListener('input',validate);

    validate();
});
</script>
@endpush
