{{-- File guide: Initial password setup page for newly created SK official accounts. --}}
@extends('layouts.app')

@section('title', 'SK 360 | Set Password')

@section('page_css')
@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/register.css'])
@elseif (file_exists(resource_path('css/register.css')))
    <style>
        {!! file_get_contents(resource_path('css/register.css')) !!}
    </style>
@endif

<style>
    body{background:#eef2f6;}
    .password-container{width:950px;max-width:95%;min-height:520px;display:flex;background:#fff;border-radius:24px;overflow:hidden;box-shadow:0 12px 35px rgba(0,0,0,.12);}
    .password-left{width:45%;background:#e5091a;color:#fff;padding:55px 50px;display:flex;flex-direction:column;justify-content:center;}
    .password-left h1{font-size:42px;margin:0 0 8px;font-weight:800;}
    .password-left h2{font-size:28px;margin:0 0 22px;}
    .password-left p{font-size:16px;line-height:1.6;margin:0;max-width:330px;}
    .password-right{width:55%;padding:55px 50px;display:flex;align-items:center;}
    .password-form{width:100%;}
    .password-form h2{font-size:30px;margin:0 0 8px;color:#111;}
    .subtitle{font-size:14px;color:#777;margin-bottom:32px;}
    .form-group{margin-bottom:20px;}
    .form-group label{display:block;font-size:13px;font-weight:700;color:#333;margin-bottom:7px;}
    .form-group input{width:100%;height:46px;border:1px solid #d5d5d5;border-radius:9px;padding:0 13px;font-size:14px;outline:none;transition:.2s;background:#fff;}
    .form-group input:focus{border-color:#e5091a;box-shadow:0 0 0 3px rgba(229,9,26,.08);}
    .form-group input[readonly]{background:#f4f5f7;color:#666;cursor:not-allowed;}
    .input-error{border-color:#dc2626!important;}
    .input-valid{border-color:#16a34a!important;}
    .requirements{margin-top:9px;padding-left:2px;}
    .requirement{font-size:12px;margin:4px 0;color:#dc2626;display:flex;align-items:center;gap:6px;}
    .requirement.valid{color:#16a34a;}
    .requirement-icon{font-weight:bold;width:14px;}
    .field-message{font-size:12px;margin-top:7px;display:none;}
    .field-message.error{display:block;color:#dc2626;}
    .field-message.success{display:block;color:#16a34a;}
    .password-btn{width:100%;height:47px;border:0;border-radius:9px;background:#e5091a;color:white;font-size:14px;font-weight:700;cursor:pointer;margin-top:8px;transition:.2s;}
    .password-btn:hover:not(:disabled){background:#c90817;}
    .password-btn:disabled{background:#cbd0d6;color:#777;cursor:not-allowed;}
    .login-link{text-align:center;margin-top:18px;font-size:12px;color:#777;}
    .login-link a{color:#e5091a;font-weight:700;text-decoration:none;}
    .expired-box{text-align:center;}
    .expired-icon{width:60px;height:60px;margin:0 auto 20px;border-radius:50%;background:#fff0f1;color:#e5091a;display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:800;}
    .expired-text{font-size:14px;color:#666;line-height:1.6;margin:20px 0 10px;}
    .expired-email{background:#f4f5f7;border:1px solid #ddd;border-radius:9px;padding:12px;margin-bottom:22px;font-size:13px;font-weight:700;color:#333;word-break:break-all;}
    .back-login-btn{width:100%;height:47px;border:0;border-radius:9px;background:#e5091a;color:#fff;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:.2s;}
    .back-login-btn:hover{background:#c90817;}
    @media(max-width:768px){
        .password-container{flex-direction:column;}
        .password-left,.password-right{width:100%;}
        .password-left{padding:35px 30px;}
        .password-right{padding:35px 30px;}
        .password-left h1{font-size:34px;}
    }
</style>
@endsection

@section('content')
<div class="password-container">
    <div class="password-left">
        <h1>SK 360°</h1>
        <h2>Welcome to SK 360°</h2>
        <p>Set your password to activate your account and access the centralized platform for youth governance.</p>
    </div>

    <div class="password-right">
        @if ($linkExpired ?? false)

            <div class="password-form expired-box">
                <div class="expired-icon">!</div>

                <h2>Setup Link Invalid or Expired</h2>

                <p class="subtitle">
                    This password setup link is no longer valid.
                </p>

                <p class="expired-text">
                    Please contact your SK President to request a new password setup link for your account.
                </p>

                <div class="expired-email">
                    {{ $email }}
                </div>

                <a href="{{ route('login') }}" class="back-login-btn">
                    Back to Login
                </a>
            </div>

        @else

            <form class="password-form" id="setPasswordForm" method="POST" action="{{ route('password.setup.store') }}">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <h2>Set Your Password</h2>
                <p class="subtitle">Create a secure password for your SK360 account.</p>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" value="{{ $email }}" readonly>
                </div>

                <div class="form-group">
                    <label>Password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your new password"
                        autocomplete="new-password"
                        required
                    >

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

                <div class="form-group">
                    <label>Confirm Password</label>

                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        placeholder="Re-enter your new password"
                        autocomplete="new-password"
                        required
                    >

                    <div id="confirmMessage" class="field-message"></div>
                </div>

                <button
                    type="submit"
                    id="setPasswordBtn"
                    class="password-btn"
                    disabled
                >
                    Set Password
                </button>

                <div class="login-link">
                    Already set your password?
                    <a href="{{ route('login') }}">Sign In</a>
                </div>
            </form>

        @endif
    </div>
</div>
@endsection

@push('scripts')

@if ($errors->getBag('default')->any())
<script>
Swal.fire({
    icon:'error',
    title:'Unable to Set Password',
    html:{!! json_encode(implode('<br>', $errors->getBag('default')->all())) !!},
    confirmButtonColor:'#e5091a'
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