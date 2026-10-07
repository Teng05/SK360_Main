{{-- File guide: Shared profile settings page for SK President, Chairman, and Secretary. --}}
@extends('layouts.app')

@section('title','SK 360° | Profile Settings')

@section('page_css')
<script src="https://cdn.tailwindcss.com"></script>
<style>
.pf-avatar-wrap{position:relative;flex:none}
.pf-avatar{display:flex;align-items:center;justify-content:center;width:88px;height:88px;border-radius:24px;overflow:hidden;background:#fff1f2;color:#bd1e2d;font-size:30px;font-weight:800;letter-spacing:.02em;box-shadow:0 0 0 3px #fff,0 0 0 5px #fecdd3}
.pf-avatar img{width:100%;height:100%;object-fit:cover}
.pf-avatar-btn{position:absolute;right:-6px;bottom:-6px;display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border:2px solid #fff;border-radius:99px;background:#c92336;color:#fff;cursor:pointer;box-shadow:0 4px 12px rgba(15,23,42,.16)}
.pf-avatar-btn:hover{background:#a91c2c}
.pf-tabs{display:flex;gap:4px;margin-bottom:24px;border-bottom:1px solid #dfe5ee;overflow-x:auto;scrollbar-width:none}
.pf-tabs::-webkit-scrollbar{display:none}
.pf-tab{position:relative;display:inline-flex;align-items:center;gap:8px;padding:13px 16px;border:0;border-radius:10px 10px 0 0;background:transparent;color:#64748b;font-size:14px;font-weight:700;white-space:nowrap;cursor:pointer;transition:.2s}
.pf-tab:hover{color:#0f172a;background:#f8fafc}
.pf-tab[aria-selected="true"]{background:#c92336;color:#fff}
.pf-panel[hidden]{display:none}
.pf-field{min-width:0}
.pf-contact-choice{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px}
.pf-contact-choice[hidden]{display:none}
.pf-contact-choice legend{margin-bottom:10px;font-size:13px;font-weight:700;color:#475569}
.pf-contact-choice label{display:flex;align-items:center;gap:8px;padding:10px 14px;border:1px solid #cbd5e1;border-radius:12px;cursor:pointer;font-size:14px;font-weight:600}
.pf-contact-choice label:has(input:checked){border-color:#c92336;background:#fff1f2;color:#a91c2c}
.pf-contact-choice input{accent-color:#c92336}
#contactSend:disabled{opacity:.55;cursor:not-allowed;box-shadow:none}
.pf-personal-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:24px}
.pf-personal-header>div{flex:1;min-width:0}
.pf-personal-header #editToggle{flex-shrink:0;min-width:94px;min-height:44px}
.pf-personal-header #editToggle:focus-visible{outline:3px solid #f3a5ae;outline-offset:3px}
@media(max-width:480px){.pf-personal-header{gap:10px}.pf-personal-header #editToggle{min-width:80px;padding:0 12px}}
.pf-label{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:7px;font-size:11px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#64748b}
.pf-lock{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:99px;background:#e9eef5;color:#64748b;font-size:9px;font-weight:800;letter-spacing:.06em}
.pf-input{width:100%;height:50px;padding:0 14px;border:1px solid transparent;border-radius:13px;background:#f4f6fa;color:#0f172a;font-size:14px;font-weight:600;transition:.2s}
.pf-input:not([readonly]):not(:disabled){background:#fff;border-color:#cbd5e1}
.pf-input:focus{outline:none;background:#fff;border-color:#f3a5ae;box-shadow:0 0 0 4px rgba(201,35,54,.12)}
.pf-input[readonly]{cursor:default}
.pf-input:disabled{color:#64748b;opacity:1;cursor:not-allowed}
.pf-input.has-error:not([readonly]){border-color:#fecaca;background:#fff1f2}
.pf-error{margin-top:6px;font-size:12px;font-weight:600;color:#dc2626}
.pf-hint{font-size:12px;line-height:1.5;color:#64748b}
.pf-actions{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:10px;margin-top:22px;padding-top:18px;border-top:1px solid #e5e7eb}
.pf-actions[hidden]{display:none}
.pf-rows{display:grid;gap:1px;border:1px solid #dfe5ee;border-radius:14px;background:#dfe5ee;overflow:hidden}
.pf-row{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:4px 16px;padding:13px 16px;background:#fff}
.pf-row dt{font-size:13px;font-weight:700;color:#64748b}
.pf-row dd{font-size:14px;font-weight:700;color:#0f172a;text-align:right}
.pf-security{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;padding:18px;border:1px solid #e5e7eb;border-radius:16px;background:#f8fafc}
.pf-modal{position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:24px}
.pf-modal:not(.is-open){display:none}
.pf-modal__backdrop{position:absolute;inset:0;background:rgba(30,41,59,.42);backdrop-filter:blur(5px)}
.pf-modal__panel{position:relative;width:100%;max-width:505px;max-height:calc(100vh - 48px);overflow-y:auto;background:#fff;border:1px solid #e5e7eb;border-radius:24px;box-shadow:0 25px 60px rgba(15,23,42,.24)}
.pf-modal__panel .sk-modal__close{position:absolute;top:20px;right:20px;z-index:2}
.pf-modal__panel .sk-modal__title{font-size:25px;line-height:1.25;font-weight:800;color:#18233a}
.pf-modal__panel .sk-modal__subtitle{margin-top:6px;font-size:14px;color:#64748b}
.pf-pass{position:relative}
.pf-pass .pf-input{height:52px;padding-right:48px;background:#fff;border:1px solid #cbd5e1;border-radius:13px}
.pf-pass input[type="password"]::-ms-reveal{display:none}
.pf-pass .pf-input:focus{border-color:#f3a5ae;box-shadow:0 0 0 4px rgba(201,35,54,.12)}
.pf-eye{position:absolute;top:50%;right:8px;transform:translateY(-50%);display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border:0;border-radius:9px;background:transparent;color:#64748b;cursor:pointer}
.pf-eye:hover{background:#f8fafc;color:#334155}
.pf-eye span{display:flex;align-items:center;justify-content:center}
.pf-eye span[hidden]{display:none!important}
.pf-checks{display:grid;gap:10px;margin-top:20px}
.pf-check{display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600;color:#64748b}
.pf-check:before{content:'';flex:none;width:16px;height:16px;border:2px solid currentColor;border-radius:999px}
.pf-check.is-ok{color:#16a34a}
.pf-check.is-ok:before{background:#16a34a;box-shadow:inset 0 0 0 3px #fff}
.pf-modal__panel .sk-btn{min-height:45px;border-radius:12px;font-size:14px;font-weight:800}
.pf-modal__panel .sk-btn--primary:disabled{background:#e58b98!important;color:#fff!important;opacity:1!important;cursor:not-allowed;box-shadow:none}
.pf-dismiss{flex:none;margin:-4px -6px -4px 0;padding:4px;border:0;border-radius:8px;background:transparent;color:inherit;opacity:.6;cursor:pointer}
.pf-dismiss:hover{opacity:1;background:rgba(0,0,0,.06)}
</style>
@endsection

@php
$skInitials=collect(preg_split('/\s+/',trim($userName)))
    ->filter()
    ->take(2)
    ->map(fn($part)=>mb_strtoupper(mb_substr($part,0,1)))
    ->implode('')?:'SK';

$profilePic=$user->profile_pic??null;
$photoUrl=($hasProfilePicColumn&&!empty($profilePic))
    ? asset(\Illuminate\Support\Str::startsWith($profilePic,'uploads/')
        ? $profilePic
        : 'uploads/profile_pics/'.$profilePic)
    : null;

$profileErrors=$errors->getBag('profile');
$passwordErrors=$errors->getBag('password');
$contactErrors=$errors->getBag('contact');
$startEditing=$contactErrors->any();
$startTab=$passwordErrors->any()?'security':(session('tab')?:'personal');

$formatDate=fn($value)=>$value
    ? \Illuminate\Support\Carbon::parse($value)->format('M j, Y')
    : null;

$termStart=$formatDate($user->term_start??null);
$termEnd=$formatDate($user->term_end??null);
$termLabel=($termStart||$termEnd)
    ? ($termStart??'Start not set').' – '.($termEnd??'Present')
    : null;

$isVerified=(bool)($user->is_verified??false);
$isActive=($user->status??'active')==='active';
@endphp

@section('content')
<div class="flex h-screen bg-gray-100 overflow-hidden">
    @include('partials.app.sidebar')

    <div class="flex-1 flex flex-col overflow-hidden min-w-0">
        @include('partials.app.topbar',[
            'accountButtonId'=>'profileDropdownBtn',
            'accountMenuId'=>'profileMenu',
            'bindBell'=>false,
        ])

        <main class="flex-1 overflow-y-auto p-5 sm:p-8">
            <div class="max-w-4xl mx-auto">

                <div class="sk-page-head">
                    <div class="sk-page-head__text">
                        <span class="sk-eyebrow"><span class="sk-dot"></span>Profile</span>
                        <h1 class="sk-page-title">Profile Settings</h1>
                        <p class="sk-page-subtitle">{{ $pageDescription }}</p>
                    </div>
                </div>

                @if(session('status'))
                    <div class="sk-alert sk-alert--success mb-5" role="status" data-dismissible>
                        @include('partials.ui.icon',['icon'=>'circle-check','iconSize'=>18])
                        <span class="flex-1">{{ session('status') }}</span>
                        <button type="button" class="pf-dismiss" data-dismiss aria-label="Dismiss">
                            @include('partials.ui.icon',['icon'=>'x','iconSize'=>16])
                        </button>
                    </div>
                @endif

                @if($profileErrors->has('profile_pic'))
                    <div class="sk-alert sk-alert--error mb-5" role="alert">
                        @include('partials.ui.icon',['icon'=>'circle-alert','iconSize'=>18])
                        <span class="flex-1">{{ $profileErrors->first('profile_pic') }}</span>
                    </div>
                @endif

                <div class="sk-card p-5 sm:p-7 mb-7">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                        <div class="pf-avatar-wrap">
                            <span class="pf-avatar">
                                @if($photoUrl)
                                    <img src="{{ $photoUrl }}" alt="Your profile photo">
                                @else
                                    {{ $skInitials }}
                                @endif
                            </span>

                            @if($hasProfilePicColumn)
                                <button type="button" class="pf-avatar-btn" data-photo-pick aria-label="Change profile photo" title="Change profile photo">
                                    @include('partials.ui.icon',['icon'=>'image','iconSize'=>15])
                                </button>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <h2 class="sk-section-title truncate">{{ $userName }}</h2>
                            <p class="sk-section-subtitle">{{ $roleLabel }} &middot; Barangay {{ $barangayName }}</p>

                            <div class="flex flex-wrap gap-2 mt-3">
                                @if($isVerified)
                                    <span class="sk-badge sk-badge--green sk-badge--dot">Verified</span>
                                @else
                                    <span class="sk-badge sk-badge--yellow sk-badge--dot">Pending verification</span>
                                @endif

                                <span class="sk-badge {{ $isActive?'sk-badge--blue':'sk-badge--gray' }}">
                                    {{ $isActive?'Active account':'Inactive account' }}
                                </span>

                                @if($termLabel)
                                    <span class="sk-badge sk-badge--gray">Term {{ $termLabel }}</span>
                                @endif
                            </div>
                        </div>

                        @if($hasProfilePicColumn)
                            <div class="flex sm:flex-col gap-2">
                                <button type="button" class="sk-btn sk-btn--secondary sk-btn--sm" data-photo-pick>
                                    @include('partials.ui.icon',['icon'=>'upload','iconSize'=>16])
                                    {{ $photoUrl?'Change photo':'Add photo' }}
                                </button>

                                @if($photoUrl)
                                    <button type="button" class="sk-btn sk-btn--ghost sk-btn--sm" data-photo-remove>
                                        @include('partials.ui.icon',['icon'=>'trash-2','iconSize'=>16])
                                        Remove
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if($hasProfilePicColumn)
                        <p class="pf-hint mt-4">JPG, PNG or WEBP &middot; up to 2 MB. Your photo appears beside your name across SK 360&deg;.</p>

                        <form id="photoForm" action="{{ $updateRoute }}" method="POST" enctype="multipart/form-data" hidden>
                            @csrf
                            <input type="file" name="profile_pic" id="photoInput" accept="image/jpeg,image/png,image/webp">
                            <input type="hidden" name="remove_photo" id="removePhotoFlag" value="0">
                        </form>
                    @endif
                </div>

                <div class="pf-tabs" role="tablist" aria-label="Profile sections">
                    <button type="button" class="pf-tab" role="tab" id="tab-personal" data-tab="personal" aria-controls="panel-personal" aria-selected="true">
                        @include('partials.ui.icon',['icon'=>'user','iconSize'=>17])
                        Personal
                    </button>

                    <button type="button" class="pf-tab" role="tab" id="tab-security" data-tab="security" aria-controls="panel-security" aria-selected="false" tabindex="-1">
                        @include('partials.ui.icon',['icon'=>'shield-check','iconSize'=>17])
                        Security
                    </button>
                </div>

                <section class="pf-panel space-y-6" id="panel-personal" role="tabpanel" aria-labelledby="tab-personal" tabindex="0">
                    <form id="personalForm" action="{{ $contactSendRoute }}" method="POST" class="sk-card p-5 sm:p-7">
                        @csrf

                        <div class="pf-personal-header">
                            <div class="min-w-0">
                                <h3 class="sk-section-title">Personal information</h3>
                                <p class="sk-section-subtitle">Your name appears on reports, announcements and the public leadership page.</p>
                            </div>

                            <button type="button" id="editToggle" class="sk-btn sk-btn--primary" aria-expanded="false" aria-controls="personalEditActions">
                                @include('partials.ui.icon',['icon'=>'pencil','iconSize'=>15])
                                <span data-edit-label>Edit</span>
                            </button>
                        </div>

                        <fieldset id="contactChoice" class="pf-contact-choice" hidden>
                            <legend>Which contact detail would you like to change?</legend>
                            <label><input type="radio" name="contact_channel" value="email" {{ old('contact_channel','email')==='email'?'checked':'' }}> Email address</label>
                            <label><input type="radio" name="contact_channel" value="phone_number" {{ old('contact_channel')==='phone_number'?'checked':'' }}> Phone number</label>
                        </fieldset>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div class="pf-field">
                                <label class="pf-label" for="first_name">First name<span class="pf-lock">@include('partials.ui.icon',['icon'=>'lock','iconSize'=>11]) Locked</span></label>

                                <input id="first_name" type="text" maxlength="50" autocomplete="given-name"
                                    class="pf-input {{ $profileErrors->has('first_name')?'has-error':'' }}"
                                    value="{{ $user->first_name }}"
                                    data-initial="{{ $user->first_name }}"
                                    readonly>

                                @if($profileErrors->has('first_name'))
                                    <p class="pf-error">{{ $profileErrors->first('first_name') }}</p>
                                @endif
                            </div>

                            <div class="pf-field">
                                <label class="pf-label" for="last_name">Last name<span class="pf-lock">@include('partials.ui.icon',['icon'=>'lock','iconSize'=>11]) Locked</span></label>

                                <input id="last_name" type="text" maxlength="50" autocomplete="family-name"
                                    class="pf-input {{ $profileErrors->has('last_name')?'has-error':'' }}"
                                    value="{{ $user->last_name }}"
                                    data-initial="{{ $user->last_name }}"
                                    readonly>

                                @if($profileErrors->has('last_name'))
                                    <p class="pf-error">{{ $profileErrors->first('last_name') }}</p>
                                @endif
                            </div>

                            @foreach(['email'=>'Email address','phone_number'=>'Phone number'] as $field=>$label)
                            <div class="pf-field">
                                <label class="pf-label" for="{{ $field==='email'?'profile_email':'profile_phone' }}">{{ $label }}</label>
                                <input id="{{ $field==='email'?'profile_email':'profile_phone' }}" name="{{ $field }}" type="{{ $field==='email'?'email':'tel' }}" class="pf-input" value="{{ old($field,$user->{$field}) }}" data-initial="{{ $user->{$field} }}" data-editable readonly>
                            </div>
                            @endforeach
                        </div>
                        <div class="sk-alert sk-alert--info mt-6">
                            @include('partials.ui.icon',['icon'=>'info','iconSize'=>18])
                            <span>Your name is locked. To change an email address or phone number, verify a code sent to the new contact detail. Change one contact detail at a time.</span>
                        </div>
                        @if($contactErrors->any())
                            <div class="sk-alert sk-alert--error mt-4" role="alert">{{ $contactErrors->first() }}</div>
                        @endif
                        <div id="personalEditActions" class="pf-actions" data-edit-actions hidden>
                            <div class="pf-field w-full">
                                <label class="pf-label" for="contact_password">Current password</label>
                                <input id="contact_password" name="current_password" type="password" autocomplete="current-password" class="pf-input" required>
                                <p id="contactSendHint" class="pf-hint mt-2" aria-live="polite">Enter a new email address and your current password to receive a code.</p>
                            </div>
                            <button type="button" id="editCancel" class="sk-btn sk-btn--ghost">Cancel</button>
                            <button type="submit" id="contactSend" class="sk-btn sk-btn--primary" aria-describedby="contactSendHint" disabled>Send verification code</button>
                        </div>
                    </form>
                    @if($pendingContact)
                        <form method="POST" action="{{ $contactVerifyRoute }}" class="sk-card p-5 sm:p-7">
                            @csrf
                            <h3 class="sk-section-title">Verify contact change</h3>
                            <p class="pf-hint mt-2">Enter the 6-digit code sent to {{ $pendingContact['value'] }}. It expires after 10 minutes. Your current contact detail remains unchanged until verification succeeds.</p>
                            <label class="pf-label mt-4" for="contact_code">Verification code</label>
                            <input id="contact_code" name="contact_code" class="pf-input" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required>
                            <button type="submit" class="sk-btn sk-btn--primary mt-4">Verify and save</button>
                        </form>
                    @endif

                    <div class="sk-card p-5 sm:p-7">
                        <h3 class="sk-section-title mb-1">Council details</h3>
                        <p class="sk-section-subtitle mb-5">Set by the SK Federation office. Shown here so you can check it is correct.</p>

                        <dl class="pf-rows">
                            <div class="pf-row">
                                <dt>Position</dt>
                                <dd>{{ $roleLabel }}</dd>
                            </div>

                            <div class="pf-row">
                                <dt>Barangay</dt>
                                <dd>{{ $barangayName }}</dd>
                            </div>

                            <div class="pf-row">
                                <dt>Term</dt>
                                <dd>{{ $termLabel??'Not set' }}</dd>
                            </div>

                            <div class="pf-row">
                                <dt>Account status</dt>
                                <dd>{{ $isActive?'Active':'Inactive' }} &middot; {{ $isVerified?'Verified':'Not yet verified' }}</dd>
                            </div>
                        </dl>
                    </div>
                </section>

                <section class="pf-panel space-y-6" id="panel-security" role="tabpanel" aria-labelledby="tab-security" tabindex="0" hidden>
                    <div class="sk-card p-5 sm:p-7">
                        <h3 class="sk-section-title mb-1">Password</h3>
                        <p class="sk-section-subtitle mb-5">Change it if you have shared it with anyone, or if you think someone else knows it.</p>

                        <div class="pf-security">
                            <div class="flex items-center gap-4 min-w-0">
                                <span class="sk-icon-tile sk-icon-tile--lg">
                                    @include('partials.ui.icon',['icon'=>'key-round','iconSize'=>22])
                                </span>

                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-gray-800">Account password</p>
                                    <p class="pf-hint">At least 8 characters. You will need your current password to change it.</p>
                                </div>
                            </div>

                            <button type="button" class="sk-btn sk-btn--primary" data-open-password>
                                @include('partials.ui.icon',['icon'=>'lock','iconSize'=>16])
                                Change password
                            </button>
                        </div>
                    </div>

                    <div class="sk-card p-5 sm:p-7">
                        <h3 class="sk-section-title mb-1">Keeping your account safe</h3>
                        <p class="sk-section-subtitle mb-5">Keep your SK360 account secure when using shared devices.</p>

                        <ul class="space-y-3">
                            <li class="flex gap-3 text-sm text-gray-600">
                                @include('partials.ui.icon',['icon'=>'circle-check','iconSize'=>18,'iconClass'=>'text-green-600 shrink-0 mt-0.5'])
                                <span>Use a password you do not use on any other website.</span>
                            </li>

                            <li class="flex gap-3 text-sm text-gray-600">
                                @include('partials.ui.icon',['icon'=>'circle-check','iconSize'=>18,'iconClass'=>'text-green-600 shrink-0 mt-0.5'])
                                <span>Never share your password with another user.</span>
                            </li>

                            <li class="flex gap-3 text-sm text-gray-600">
                                @include('partials.ui.icon',['icon'=>'circle-check','iconSize'=>18,'iconClass'=>'text-green-600 shrink-0 mt-0.5'])
                                <span>Log out when you finish using a shared or barangay hall computer.</span>
                            </li>
                        </ul>
                    </div>
                </section>

            </div>
        </main>
    </div>
</div>

<div class="pf-modal" id="passwordModal" role="dialog" aria-modal="true" aria-labelledby="passwordModalTitle">
    <div class="pf-modal__backdrop" data-close-password></div>

    <div class="pf-modal__panel">
        <button type="button" class="sk-icon-btn sk-modal__close" data-close-password aria-label="Close">
            @include('partials.ui.icon',['icon'=>'x','iconSize'=>19])
        </button>

        <div class="p-7 sm:p-8">
            <h2 class="sk-modal__title" id="passwordModalTitle">Change password</h2>
            <p class="sk-modal__subtitle">You will stay signed in on this device.</p>

            <form id="passwordForm" action="{{ $passwordRoute }}" method="POST" class="mt-7">
                @csrf

                <div class="space-y-5">
                    <div class="pf-field">
                        <label class="pf-label" for="current_password">Current password</label>

                        <div class="pf-pass">
                            <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                                class="pf-input {{ $passwordErrors->has('current_password')?'has-error':'' }}">

                            <button type="button" class="pf-eye" data-toggle-pass="current_password" aria-pressed="false" aria-label="Show password" title="Show password">
                                <span data-eye-visible>
                                    @include('partials.ui.icon',['icon'=>'eye','iconSize'=>18])
                                </span>
                                <span data-eye-hidden hidden>
                                    @include('partials.ui.icon',['icon'=>'eye-off','iconSize'=>18])
                                </span>
                            </button>
                        </div>

                        @if($passwordErrors->has('current_password'))
                            <p class="pf-error">{{ $passwordErrors->first('current_password') }}</p>
                        @endif
                    </div>

                    <div class="pf-field">
                        <label class="pf-label" for="password">New password</label>

                        <div class="pf-pass">
                            <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"
                                class="pf-input {{ $passwordErrors->has('password')?'has-error':'' }}">

                            <button type="button" class="pf-eye" data-toggle-pass="password" aria-pressed="false" aria-label="Show password" title="Show password">
                                <span data-eye-visible>
                                    @include('partials.ui.icon',['icon'=>'eye','iconSize'=>18])
                                </span>
                                <span data-eye-hidden hidden>
                                    @include('partials.ui.icon',['icon'=>'eye-off','iconSize'=>18])
                                </span>
                            </button>
                        </div>

                        @if($passwordErrors->has('password'))
                            <p class="pf-error">{{ $passwordErrors->first('password') }}</p>
                        @endif
                    </div>

                    <div class="pf-field">
                        <label class="pf-label" for="password_confirmation">Confirm new password</label>

                        <div class="pf-pass">
                            <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="pf-input">

                            <button type="button" class="pf-eye" data-toggle-pass="password_confirmation" aria-pressed="false" aria-label="Show password" title="Show password">
                                <span data-eye-visible>
                                    @include('partials.ui.icon',['icon'=>'eye','iconSize'=>18])
                                </span>
                                <span data-eye-hidden hidden>
                                    @include('partials.ui.icon',['icon'=>'eye-off','iconSize'=>18])
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <ul class="pf-checks" aria-live="polite">
                    <li class="pf-check" data-check="length">At least 8 characters</li>
                    <li class="pf-check" data-check="match">Both new password fields match</li>
                    <li class="pf-check" data-check="different">Different from your current password</li>
                </ul>

                <div class="flex gap-3 mt-8">
                    <button type="button" class="sk-btn sk-btn--ghost flex-1" data-close-password>Cancel</button>
                    <button type="submit" class="sk-btn sk-btn--primary flex-1" id="passwordSubmit" disabled>Update password</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    'use strict';

    const notifBtn=document.getElementById('notifBtn');
    const notifDropdown=document.getElementById('notifDropdown');
    const accountBtn=document.getElementById('profileDropdownBtn');
    const accountMenu=document.getElementById('profileMenu');

    if(notifBtn&&notifDropdown){
        notifBtn.addEventListener('click',function(e){
            e.stopPropagation();
            notifDropdown.classList.toggle('hidden');
            if(accountMenu)accountMenu.classList.add('hidden');
        });
    }

    if(accountBtn&&accountMenu){
        accountBtn.addEventListener('click',function(e){
            e.stopPropagation();
            accountMenu.classList.toggle('hidden');
            if(notifDropdown)notifDropdown.classList.add('hidden');
        });
    }

    document.addEventListener('click',function(e){
        if(notifBtn&&notifDropdown&&!notifBtn.contains(e.target)&&!notifDropdown.contains(e.target)){
            notifDropdown.classList.add('hidden');
        }

        if(accountBtn&&accountMenu&&!accountBtn.contains(e.target)&&!accountMenu.contains(e.target)){
            accountMenu.classList.add('hidden');
        }
    });

    document.querySelectorAll('[data-dismiss]').forEach(function(button){
        button.addEventListener('click',function(){
            button.closest('[data-dismissible]')?.remove();
        });
    });

    const tabs=Array.from(document.querySelectorAll('.pf-tab'));
    const panels={
        personal:document.getElementById('panel-personal'),
        security:document.getElementById('panel-security')
    };

    function selectTab(name,moveFocus){
        if(!panels[name])return;

        tabs.forEach(function(tab){
            const active=tab.dataset.tab===name;
            tab.setAttribute('aria-selected',active?'true':'false');
            tab.tabIndex=active?0:-1;
            panels[tab.dataset.tab].hidden=!active;

            if(active&&moveFocus)tab.focus();
        });

        if(history.replaceState){
            history.replaceState(null,'','#'+name);
        }
    }

    tabs.forEach(function(tab,index){
        tab.addEventListener('click',function(){
            selectTab(tab.dataset.tab,false);
        });

        tab.addEventListener('keydown',function(event){
            const step=event.key==='ArrowRight'?1:(event.key==='ArrowLeft'?-1:0);

            if(!step)return;

            event.preventDefault();
            selectTab(tabs[(index+step+tabs.length)%tabs.length].dataset.tab,true);
        });
    });

    const hashTab=(window.location.hash||'').replace('#','');
    selectTab(panels[hashTab]?hashTab:@json($startTab),false);

    const personalForm=document.getElementById('personalForm');
    const editToggle=document.getElementById('editToggle');
    const editCancel=document.getElementById('editCancel');
    const editables=Array.from(personalForm.querySelectorAll('[data-editable]'));
    const editActions=personalForm.querySelector('[data-edit-actions]');
    const editLabel=editToggle.querySelector('[data-edit-label]');
    const contactChoice=document.getElementById('contactChoice');
    const contactSend=document.getElementById('contactSend');
    const contactPassword=document.getElementById('contact_password');
    let sendingContact=false;

    function syncContactChoice(){
        const channel=personalForm.querySelector('[name="contact_channel"]:checked').value;
        const editing=!editActions.hidden;
        const selected=editables.find(field=>field.name===channel);
        editables.forEach(field=>{
            field.readOnly=!editing || field!==selected;
            field.disabled=editing && field!==selected;
            field.required=editing && field===selected;
        });
        const normalize=value=>channel==='email'?value.trim().toLowerCase():value.replace(/\D/g,'').replace(/^(63|0)/,'');
        const changed=normalize(selected.value)!==normalize(selected.dataset.initial||'');
        contactSend.disabled=sendingContact || !editing || !changed || !selected.value.trim() || !selected.checkValidity() || !contactPassword.value;
        document.getElementById('contactSendHint').textContent=channel==='email'
            ? 'We will send a code to your new email address. Your current email stays active until verified.'
            : 'We will text a code to your new phone number. Your current number stays active until verified.';
    }
    contactChoice.addEventListener('change',()=>{
        syncContactChoice();
        editables.find(field=>!field.readOnly).focus();
    });
    editables.forEach(field=>field.addEventListener('input',syncContactChoice));
    contactPassword.addEventListener('input',syncContactChoice);

    function setEditing(editing,focusFirst){
        editables.forEach(function(field){
            field.readOnly=!editing;
        });

        editActions.hidden=!editing;
        contactChoice.hidden=!editing;
        syncContactChoice();
        editLabel.textContent=editing?'Cancel':'Edit';
        editToggle.classList.toggle('sk-btn--primary',!editing);
        editToggle.classList.toggle('sk-btn--secondary',editing);
        editToggle.setAttribute('aria-expanded',editing?'true':'false');

        if(editing&&focusFirst&&editables.length){
            const first=editables.find(field=>!field.readOnly);
            first.focus();
            if(first.type==='text'||first.type==='tel') first.setSelectionRange(first.value.length,first.value.length);
        }
    }

    function resetFields(){
        document.getElementById('contact_password').value='';
        editables.forEach(function(field){
            field.value=field.dataset.initial||'';
            field.classList.remove('has-error');
        });
    }

    editToggle.addEventListener('click',function(){
        const startEditing=editActions.hidden;

        if(!startEditing)resetFields();

        setEditing(startEditing,true);
    });

    editCancel.addEventListener('click',function(){
        resetFields();
        setEditing(false,false);
        editToggle.focus();
    });

    personalForm.addEventListener('submit',function(event){
        syncContactChoice();
        if(contactSend.disabled){
            event.preventDefault();
            return;
        }
        sendingContact=true;
        contactSend.disabled=true;
        contactSend.textContent='Sending code...';
        personalForm.setAttribute('aria-busy','true');
    });

    setEditing(@json($startEditing),false);

    const photoForm=document.getElementById('photoForm');

    if(photoForm){
        const photoInput=document.getElementById('photoInput');
        const removeFlag=document.getElementById('removePhotoFlag');

        document.querySelectorAll('[data-photo-pick]').forEach(function(button){
            button.addEventListener('click',function(){
                removeFlag.value='0';
                photoInput.click();
            });
        });

        photoInput.addEventListener('change',function(){
            const file=photoInput.files&&photoInput.files[0];

            if(!file)return;

            const allowed=['image/jpeg','image/png','image/webp'];

            if(!allowed.includes(file.type)){
                window.alert('Please choose a JPG, PNG or WEBP image.');
                photoInput.value='';
                return;
            }

            if(file.size>2*1024*1024){
                window.alert('That photo is larger than 2 MB. Please choose a smaller one.');
                photoInput.value='';
                return;
            }

            photoForm.submit();
        });

        document.querySelectorAll('[data-photo-remove]').forEach(function(button){
            button.addEventListener('click',function(){
                if(!window.confirm('Remove your profile photo? Your initials will be shown instead.'))return;

                removeFlag.value='1';
                photoInput.value='';
                photoForm.submit();
            });
        });
    }

    const modal=document.getElementById('passwordModal');
    const passwordForm=document.getElementById('passwordForm');
    const submitButton=document.getElementById('passwordSubmit');
    const currentField=document.getElementById('current_password');
    const newField=document.getElementById('password');
    const confirmField=document.getElementById('password_confirmation');
    let lastFocused=null;

    function validatePassword(){
        const current=currentField.value;
        const next=newField.value;
        const repeated=confirmField.value;

        const state={
            length:next.length>=8,
            match:next.length>0&&next===repeated,
            different:next.length>0&&next!==current
        };

        Object.keys(state).forEach(function(key){
            const check=passwordForm.querySelector('[data-check="'+key+'"]');
            if(check)check.classList.toggle('is-ok',state[key]);
        });

        submitButton.disabled=!(current.length>0&&state.length&&state.match&&state.different);
    }

    function resetEyeIcons(){
        passwordForm.querySelectorAll('[data-toggle-pass]').forEach(function(button){
            const field=document.getElementById(button.dataset.togglePass);
            if(!field)return;

            field.type='password';

            const normalEye=button.querySelector('[data-eye-visible]');
            const slashEye=button.querySelector('[data-eye-hidden]');

            if(normalEye)normalEye.hidden=false;
            if(slashEye)slashEye.hidden=true;

            button.setAttribute('aria-pressed','false');
            button.setAttribute('aria-label','Show password');
            button.setAttribute('title','Show password');
        });
    }

    function openModal(){
        lastFocused=document.activeElement;
        modal.classList.add('is-open');
        setTimeout(()=>currentField.focus(),50);
    }

    function closeModal(){
        modal.classList.remove('is-open');
        passwordForm.reset();
        resetEyeIcons();
        validatePassword();

        if(lastFocused)lastFocused.focus();
    }

    document.querySelectorAll('[data-open-password]').forEach(function(button){
        button.addEventListener('click',openModal);
    });

    document.querySelectorAll('[data-close-password]').forEach(function(button){
        button.addEventListener('click',closeModal);
    });

    document.addEventListener('keydown',function(event){
        if(event.key!=='Escape')return;

        if(modal.classList.contains('is-open')){
            closeModal();
        }else{
            if(accountMenu)accountMenu.classList.add('hidden');
            if(notifDropdown)notifDropdown.classList.add('hidden');
        }
    });

    [currentField,newField,confirmField].forEach(function(field){
        field.addEventListener('input',validatePassword);
    });

    passwordForm.querySelectorAll('[data-toggle-pass]').forEach(function(button){
        button.addEventListener('click',function(){
            const field=document.getElementById(button.dataset.togglePass);
            if(!field)return;

            const willShow=field.type==='password';
            field.type=willShow?'text':'password';

            const normalEye=button.querySelector('[data-eye-visible]');
            const slashEye=button.querySelector('[data-eye-hidden]');

            if(normalEye)normalEye.hidden=willShow;
            if(slashEye)slashEye.hidden=!willShow;

            button.setAttribute('aria-pressed',willShow?'true':'false');
            button.setAttribute('aria-label',willShow?'Hide password':'Show password');
            button.setAttribute('title',willShow?'Hide password':'Show password');
        });
    });

    resetEyeIcons();
    validatePassword();

    @if($passwordErrors->any())
        openModal();
    @endif
})();
</script>
@endpush
