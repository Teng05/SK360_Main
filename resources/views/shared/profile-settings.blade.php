{{-- File guide: Blade view template for resources/views/shared/profile-settings.blade.php. --}}
@extends('layouts.app')

@section('title', 'Profile Settings | SK 360°')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* Page-scoped pieces the shared design system does not cover. Every
           selector is prefixed `pf-` so nothing here leaks into other pages. */

        /* Identity card */
        .pf-avatar-wrap { position: relative; flex: none; }

        .pf-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 88px;
            height: 88px;
            border-radius: 24px;
            overflow: hidden;
            background: var(--sk-red-soft);
            color: var(--sk-red-hover);
            font-size: 30px;
            font-weight: 800;
            letter-spacing: .02em;
            box-shadow: 0 0 0 3px #fff, 0 0 0 4.5px var(--sk-red-line);
        }

        .pf-avatar img { width: 100%; height: 100%; object-fit: cover; }

        .pf-avatar-btn {
            position: absolute;
            right: -6px;
            bottom: -6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border: 2px solid #fff;
            border-radius: 99px;
            background: var(--sk-red);
            color: #fff;
            cursor: pointer;
            box-shadow: var(--sk-shadow-sm);
        }

        .pf-avatar-btn:hover { background: var(--sk-red-hover); }

        /* Tabs */
        .pf-tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--sk-border);
            overflow-x: auto;
            scrollbar-width: none;
        }

        .pf-tabs::-webkit-scrollbar { display: none; }

        .pf-tab {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 14px;
            border: 0;
            border-radius: 10px 10px 0 0;
            background: transparent;
            color: var(--sk-muted);
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            white-space: nowrap;
            cursor: pointer;
        }

        .pf-tab:hover { color: var(--sk-ink); background: var(--sk-subtle); }
        .pf-tab[aria-selected="true"] { color: var(--sk-red); }

        .pf-tab[aria-selected="true"]::after {
            content: '';
            position: absolute;
            left: 10px;
            right: 10px;
            bottom: -1px;
            height: 2px;
            border-radius: 2px 2px 0 0;
            background: var(--sk-red);
        }

        .pf-panel { animation: sk-fade var(--sk-med) var(--sk-ease); }
        .pf-panel[hidden] { display: none; }
        .pf-panel:focus { outline: none; }

        /* Fields */
        .pf-field { min-width: 0; }

        .pf-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 7px;
            font-size: 11.5px;
            font-weight: 800;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: var(--sk-faint);
        }

        .pf-lock {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 99px;
            background: var(--sk-gray-soft);
            color: var(--sk-muted);
            font-size: 10px;
            letter-spacing: .06em;
        }

        .sk-app .pf-input {
            width: 100%;
            height: 46px;
            padding: 0 14px;
            border: 1px solid transparent;
            border-radius: var(--sk-radius-control);
            background: var(--sk-subtle);
            color: var(--sk-ink);
            font-family: inherit;
            font-size: 14.5px;
            font-weight: 600;
            transition: background-color var(--sk-fast) var(--sk-ease), border-color var(--sk-fast) var(--sk-ease), box-shadow var(--sk-fast) var(--sk-ease);
        }

        .sk-app .pf-input:not([readonly]):not(:disabled) {
            background: var(--sk-surface);
            border-color: var(--sk-border-strong);
        }

        .sk-app .pf-input:focus {
            outline: none;
            background: var(--sk-surface);
            border-color: rgba(201, 35, 54, .5);
            box-shadow: 0 0 0 4px rgba(201, 35, 54, .1);
        }

        .sk-app .pf-input[readonly] { cursor: default; }

        .sk-app .pf-input:disabled {
            color: var(--sk-faint);
            -webkit-text-fill-color: var(--sk-faint);
            opacity: 1;
            cursor: not-allowed;
        }

        .sk-app .pf-input.has-error:not([readonly]) {
            border-color: var(--sk-red-line);
            background: var(--sk-red-soft);
        }

        .pf-error {
            margin-top: 6px;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--sk-red-hover);
        }

        .pf-hint {
            font-size: 12.5px;
            line-height: 1.5;
            color: var(--sk-faint);
        }

        .pf-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 22px;
            padding-top: 18px;
            border-top: 1px solid var(--sk-border);
        }

        .pf-actions[hidden] { display: none; }

        /* Read-only detail rows */
        .pf-rows {
            display: grid;
            gap: 1px;
            border: 1px solid var(--sk-border);
            border-radius: 14px;
            background: var(--sk-border);
            overflow: hidden;
        }

        .pf-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 4px 16px;
            padding: 13px 16px;
            background: var(--sk-surface);
        }

        .pf-row dt { font-size: 13px; font-weight: 700; color: var(--sk-muted); }
        .pf-row dd { font-size: 14px; font-weight: 700; color: var(--sk-ink); text-align: right; }

        /* Security row */
        .pf-security {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px;
            border: 1px solid var(--sk-border);
            border-radius: 16px;
            background: var(--sk-subtle);
        }

        /* Password modal */
        .pf-modal {
            position: fixed;
            inset: 0;
            z-index: 90;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .pf-modal:not(.is-open) { display: none; }

        .pf-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(17, 24, 39, .42);
            backdrop-filter: blur(4px);
            animation: sk-fade var(--sk-med) var(--sk-ease);
        }

        .pf-modal__panel {
            position: relative;
            width: 100%;
            max-width: 460px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
            animation: sk-rise var(--sk-med) var(--sk-ease);
        }

        .pf-pass { position: relative; }
        .sk-app .pf-pass .pf-input { padding-right: 46px; }

        .pf-eye {
            position: absolute;
            top: 50%;
            right: 6px;
            transform: translateY(-50%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border: 0;
            border-radius: 9px;
            background: transparent;
            color: var(--sk-faint);
            cursor: pointer;
        }

        .pf-eye:hover { background: var(--sk-field); color: var(--sk-text); }

        .pf-checks { display: grid; gap: 9px; }

        .pf-check {
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 13px;
            font-weight: 600;
            color: var(--sk-faint);
            transition: color var(--sk-fast) var(--sk-ease);
        }

        .pf-check::before {
            content: '';
            flex: none;
            width: 15px;
            height: 15px;
            border: 2px solid currentColor;
            border-radius: 99px;
            transition: background-color var(--sk-fast) var(--sk-ease), box-shadow var(--sk-fast) var(--sk-ease);
        }

        .pf-check.is-ok { color: var(--sk-green); }
        .pf-check.is-ok::before { background: var(--sk-green); box-shadow: inset 0 0 0 2px #fff; }

        .sk-btn:disabled { opacity: .5; box-shadow: none; }

        .pf-dismiss {
            flex: none;
            margin: -4px -6px -4px 0;
            padding: 4px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: inherit;
            opacity: .6;
            cursor: pointer;
        }

        .pf-dismiss:hover { opacity: 1; background: rgba(0, 0, 0, .06); }
    </style>
@endsection

@php
    $skInitials = collect(preg_split('/\s+/', trim($userName)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('') ?: 'SK';

    $profilePic = $user->profile_pic ?? null;
    $photoUrl = ($hasProfilePicColumn && ! empty($profilePic))
        ? asset(\Illuminate\Support\Str::startsWith($profilePic, 'uploads/')
            ? $profilePic
            : 'uploads/profile_pics/'.$profilePic)
        : null;

    $profileErrors = $errors->getBag('profile');
    $passwordErrors = $errors->getBag('password');

    // The name fields start locked; a failed save reopens them so the user can fix it.
    $startEditing = $profileErrors->hasAny(['first_name', 'last_name']);
    $startTab = $passwordErrors->any() ? 'security' : (session('tab') ?: 'personal');

    $formatDate = fn ($value) => $value
        ? \Illuminate\Support\Carbon::parse($value)->format('M j, Y')
        : null;

    $termStart = $formatDate($user->term_start ?? null);
    $termEnd = $formatDate($user->term_end ?? null);
    $termLabel = ($termStart || $termEnd)
        ? ($termStart ?? 'Start not set') . ' – ' . ($termEnd ?? 'Present')
        : null;

    $isVerified = (bool) ($user->is_verified ?? false);
    $isActive = ($user->status ?? 'active') === 'active';
@endphp

@section('content')
<div class="flex h-screen bg-gray-100 overflow-hidden">
    @include('partials.app.sidebar')

    <div class="flex-1 flex flex-col overflow-hidden min-w-0">
        @include('partials.app.topbar', [
            'accountButtonId' => 'profileDropdownBtn',
            'accountMenuId' => 'profileMenu',
            'bindBell' => true,
        ])

        <main class="flex-1 overflow-y-auto p-5 sm:p-8">
            <div class="max-w-4xl mx-auto">

                {{-- PAGE HEAD --}}
                <div class="sk-page-head">
                    <div class="sk-page-head__text">
                        <span class="sk-eyebrow"><span class="sk-dot"></span>Profile</span>
                        <h1 class="sk-page-title">Profile Settings</h1>
                        <p class="sk-page-subtitle">{{ $pageDescription }}</p>
                    </div>
                </div>

                {{-- ALERTS --}}
                @if (session('status'))
                    <div class="sk-alert sk-alert--success mb-5" role="status" data-dismissible>
                        @include('partials.ui.icon', ['icon' => 'circle-check', 'iconSize' => 18])
                        <span class="flex-1">{{ session('status') }}</span>
                        <button type="button" class="pf-dismiss" data-dismiss aria-label="Dismiss message">
                            @include('partials.ui.icon', ['icon' => 'x', 'iconSize' => 16])
                        </button>
                    </div>
                @endif

                @if ($profileErrors->has('profile_pic'))
                    <div class="sk-alert sk-alert--error mb-5" role="alert">
                        @include('partials.ui.icon', ['icon' => 'circle-alert', 'iconSize' => 18])
                        <span class="flex-1">{{ $profileErrors->first('profile_pic') }}</span>
                    </div>
                @endif

                {{-- IDENTITY CARD --}}
                <div class="sk-card p-5 sm:p-7 mb-7">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                        <div class="pf-avatar-wrap">
                            <span class="pf-avatar">
                                @if ($photoUrl)
                                    <img src="{{ $photoUrl }}" alt="Your profile photo">
                                @else
                                    {{ $skInitials }}
                                @endif
                            </span>

                            @if ($hasProfilePicColumn)
                                <button type="button" class="pf-avatar-btn" data-photo-pick aria-label="Change profile photo" title="Change profile photo">
                                    @include('partials.ui.icon', ['icon' => 'image', 'iconSize' => 15])
                                </button>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <h2 class="sk-section-title truncate">{{ $userName }}</h2>
                            <p class="sk-section-subtitle">{{ $roleLabel }} &middot; Barangay {{ $barangayName }}</p>

                            <div class="flex flex-wrap gap-2 mt-3">
                                @if ($isVerified)
                                    <span class="sk-badge sk-badge--green sk-badge--dot">Verified</span>
                                @else
                                    <span class="sk-badge sk-badge--yellow sk-badge--dot">Pending verification</span>
                                @endif

                                <span class="sk-badge {{ $isActive ? 'sk-badge--blue' : 'sk-badge--gray' }}">
                                    {{ $isActive ? 'Active account' : 'Inactive account' }}
                                </span>

                                @if ($termLabel)
                                    <span class="sk-badge sk-badge--gray">Term {{ $termLabel }}</span>
                                @endif
                            </div>
                        </div>

                        @if ($hasProfilePicColumn)
                            <div class="flex sm:flex-col gap-2">
                                <button type="button" class="sk-btn sk-btn--secondary sk-btn--sm" data-photo-pick>
                                    @include('partials.ui.icon', ['icon' => 'upload', 'iconSize' => 16])
                                    {{ $photoUrl ? 'Change photo' : 'Add photo' }}
                                </button>

                                @if ($photoUrl)
                                    <button type="button" class="sk-btn sk-btn--ghost sk-btn--sm" data-photo-remove>
                                        @include('partials.ui.icon', ['icon' => 'trash-2', 'iconSize' => 16])
                                        Remove
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if ($hasProfilePicColumn)
                        <p class="pf-hint mt-4">JPG, PNG or WEBP &middot; up to 2 MB. Your photo appears beside your name across SK 360&deg;.</p>

                        {{-- The photo posts on its own so a picture never waits on the name form. --}}
                        <form id="photoForm" action="{{ $updateRoute }}" method="POST" enctype="multipart/form-data" hidden>
                            @csrf
                            <input type="file" name="profile_pic" id="photoInput" accept="image/jpeg,image/png,image/webp">
                            <input type="hidden" name="remove_photo" id="removePhotoFlag" value="0">
                        </form>
                    @endif
                </div>

                {{-- TABS --}}
                <div class="pf-tabs" role="tablist" aria-label="Profile sections">
                    <button type="button" class="pf-tab" role="tab" id="tab-personal" data-tab="personal"
                            aria-controls="panel-personal" aria-selected="true">
                        @include('partials.ui.icon', ['icon' => 'user', 'iconSize' => 17])
                        Personal
                    </button>
                    <button type="button" class="pf-tab" role="tab" id="tab-security" data-tab="security"
                            aria-controls="panel-security" aria-selected="false" tabindex="-1">
                        @include('partials.ui.icon', ['icon' => 'shield-check', 'iconSize' => 17])
                        Security
                    </button>
                </div>

                {{-- PERSONAL --}}
                <section class="pf-panel space-y-6" id="panel-personal" role="tabpanel" aria-labelledby="tab-personal" tabindex="0">
                    <form id="personalForm" action="{{ $updateRoute }}" method="POST" class="sk-card p-5 sm:p-7">
                        @csrf

                        <div class="sk-card__header mb-6">
                            <div class="min-w-0">
                                <h3 class="sk-section-title">Personal information</h3>
                                <p class="sk-section-subtitle">Your name appears on reports, announcements and the public leadership page.</p>
                            </div>

                            <button type="button" id="editToggle" class="sk-btn sk-btn--soft sk-btn--sm" aria-expanded="false" aria-controls="personalForm">
                                @include('partials.ui.icon', ['icon' => 'pencil', 'iconSize' => 15])
                                <span data-edit-label>Edit</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div class="pf-field">
                                <label class="pf-label" for="first_name">First name</label>
                                <input id="first_name" name="first_name" type="text" maxlength="50" autocomplete="given-name"
                                       class="pf-input {{ $profileErrors->has('first_name') ? 'has-error' : '' }}"
                                       value="{{ old('first_name', $user->first_name) }}"
                                       data-initial="{{ $user->first_name }}"
                                       data-editable readonly>
                                @if ($profileErrors->has('first_name'))
                                    <p class="pf-error">{{ $profileErrors->first('first_name') }}</p>
                                @endif
                            </div>

                            <div class="pf-field">
                                <label class="pf-label" for="last_name">Last name</label>
                                <input id="last_name" name="last_name" type="text" maxlength="50" autocomplete="family-name"
                                       class="pf-input {{ $profileErrors->has('last_name') ? 'has-error' : '' }}"
                                       value="{{ old('last_name', $user->last_name) }}"
                                       data-initial="{{ $user->last_name }}"
                                       data-editable readonly>
                                @if ($profileErrors->has('last_name'))
                                    <p class="pf-error">{{ $profileErrors->first('last_name') }}</p>
                                @endif
                            </div>

                            <div class="pf-field">
                                <label class="pf-label" for="profile_email">
                                    Email address
                                    <span class="pf-lock">
                                        @include('partials.ui.icon', ['icon' => 'lock', 'iconSize' => 11])
                                        Locked
                                    </span>
                                </label>
                                <input id="profile_email" type="email" class="pf-input" value="{{ $user->email }}" disabled>
                            </div>

                            <div class="pf-field">
                                <label class="pf-label" for="profile_phone">
                                    Phone number
                                    <span class="pf-lock">
                                        @include('partials.ui.icon', ['icon' => 'lock', 'iconSize' => 11])
                                        Locked
                                    </span>
                                </label>
                                <input id="profile_phone" type="tel" class="pf-input"
                                       value="{{ $user->phone_number ?: 'Not set' }}" disabled>
                            </div>
                        </div>

                        <div class="sk-alert sk-alert--info mt-6">
                            @include('partials.ui.icon', ['icon' => 'info', 'iconSize' => 18])
                            <span>Your email address and phone number sign you in, so only the SK Federation office can change them. Message them if either one is wrong.</span>
                        </div>

                        <div class="pf-actions" data-edit-actions hidden>
                            <button type="button" id="editCancel" class="sk-btn sk-btn--ghost">Cancel</button>
                            <button type="submit" class="sk-btn sk-btn--primary">
                                @include('partials.ui.icon', ['icon' => 'circle-check', 'iconSize' => 17])
                                Save changes
                            </button>
                        </div>
                    </form>

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
                                <dd>{{ $termLabel ?? 'Not set' }}</dd>
                            </div>
                            <div class="pf-row">
                                <dt>Account status</dt>
                                <dd>{{ $isActive ? 'Active' : 'Inactive' }} &middot; {{ $isVerified ? 'Verified' : 'Not yet verified' }}</dd>
                            </div>
                        </dl>
                    </div>
                </section>

                {{-- SECURITY --}}
                <section class="pf-panel space-y-6" id="panel-security" role="tabpanel" aria-labelledby="tab-security" tabindex="0" hidden>
                    <div class="sk-card p-5 sm:p-7">
                        <h3 class="sk-section-title mb-1">Password</h3>
                        <p class="sk-section-subtitle mb-5">Change it if you have shared it with anyone, or if you think someone else knows it.</p>

                        <div class="pf-security">
                            <div class="flex items-center gap-4 min-w-0">
                                <span class="sk-icon-tile sk-icon-tile--lg">
                                    @include('partials.ui.icon', ['icon' => 'key-round', 'iconSize' => 22])
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-gray-800">Account password</p>
                                    <p class="pf-hint">At least 8 characters. You will need your current password to change it.</p>
                                </div>
                            </div>

                            <button type="button" class="sk-btn sk-btn--primary" data-open-password>
                                @include('partials.ui.icon', ['icon' => 'lock', 'iconSize' => 16])
                                Change password
                            </button>
                        </div>
                    </div>

                    <div class="sk-card p-5 sm:p-7">
                        <h3 class="sk-section-title mb-1">Keeping your account safe</h3>
                        <p class="sk-section-subtitle mb-5">This account can file reports and publish announcements for Barangay {{ $barangayName }}.</p>

                        <ul class="space-y-3">
                            <li class="flex gap-3 text-sm text-gray-600">
                                @include('partials.ui.icon', ['icon' => 'circle-check', 'iconSize' => 18, 'iconClass' => 'text-green-600 shrink-0 mt-0.5'])
                                <span>Use a password you do not use on any other website.</span>
                            </li>
                            <li class="flex gap-3 text-sm text-gray-600">
                                @include('partials.ui.icon', ['icon' => 'circle-check', 'iconSize' => 18, 'iconClass' => 'text-green-600 shrink-0 mt-0.5'])
                                <span>Never share it, even with other council members.</span>
                            </li>
                            <li class="flex gap-3 text-sm text-gray-600">
                                @include('partials.ui.icon', ['icon' => 'circle-check', 'iconSize' => 18, 'iconClass' => 'text-green-600 shrink-0 mt-0.5'])
                                <span>Log out when you finish on a shared or barangay hall computer.</span>
                            </li>
                        </ul>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>

{{-- PASSWORD MODAL --}}
<div class="pf-modal" id="passwordModal" role="dialog" aria-modal="true" aria-labelledby="passwordModalTitle">
    <div class="pf-modal__backdrop" data-close-password></div>

    <div class="sk-modal pf-modal__panel">
        <button type="button" class="sk-icon-btn sk-modal__close" data-close-password aria-label="Close">
            @include('partials.ui.icon', ['icon' => 'x', 'iconSize' => 19])
        </button>

        <div class="p-6 sm:p-7">
            <h2 class="sk-modal__title" id="passwordModalTitle">Change password</h2>
            <p class="sk-modal__subtitle">You will stay signed in on this device.</p>

            <form id="passwordForm" action="{{ $passwordRoute }}" method="POST" class="mt-6">
                @csrf

                <div class="space-y-4">
                    <div class="pf-field">
                        <label class="pf-label" for="current_password">Current password</label>
                        <div class="pf-pass">
                            <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                                   class="pf-input {{ $passwordErrors->has('current_password') ? 'has-error' : '' }}">
                            <button type="button" class="pf-eye" data-toggle-pass="current_password" aria-pressed="false" aria-label="Show password">
                                <span data-eye-on>@include('partials.ui.icon', ['icon' => 'eye', 'iconSize' => 17])</span>
                                <span data-eye-off hidden>@include('partials.ui.icon', ['icon' => 'eye-off', 'iconSize' => 17])</span>
                            </button>
                        </div>
                        @if ($passwordErrors->has('current_password'))
                            <p class="pf-error">{{ $passwordErrors->first('current_password') }}</p>
                        @endif
                    </div>

                    <div class="pf-field">
                        <label class="pf-label" for="password">New password</label>
                        <div class="pf-pass">
                            <input id="password" name="password" type="password" required autocomplete="new-password" minlength="8"
                                   class="pf-input {{ $passwordErrors->has('password') ? 'has-error' : '' }}">
                            <button type="button" class="pf-eye" data-toggle-pass="password" aria-pressed="false" aria-label="Show password">
                                <span data-eye-on>@include('partials.ui.icon', ['icon' => 'eye', 'iconSize' => 17])</span>
                                <span data-eye-off hidden>@include('partials.ui.icon', ['icon' => 'eye-off', 'iconSize' => 17])</span>
                            </button>
                        </div>
                        @if ($passwordErrors->has('password'))
                            <p class="pf-error">{{ $passwordErrors->first('password') }}</p>
                        @endif
                    </div>

                    <div class="pf-field">
                        <label class="pf-label" for="password_confirmation">Confirm new password</label>
                        <div class="pf-pass">
                            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                                   class="pf-input">
                            <button type="button" class="pf-eye" data-toggle-pass="password_confirmation" aria-pressed="false" aria-label="Show password">
                                <span data-eye-on>@include('partials.ui.icon', ['icon' => 'eye', 'iconSize' => 17])</span>
                                <span data-eye-off hidden>@include('partials.ui.icon', ['icon' => 'eye-off', 'iconSize' => 17])</span>
                            </button>
                        </div>
                    </div>
                </div>

                <ul class="pf-checks mt-5" aria-live="polite">
                    <li class="pf-check" data-check="length">At least 8 characters</li>
                    <li class="pf-check" data-check="match">Both new password fields match</li>
                    <li class="pf-check" data-check="different">Different from your current password</li>
                </ul>

                <div class="flex gap-3 mt-7">
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
(function () {
    'use strict';

    /* ---------- Account dropdown (IDs match the topbar include) ---------- */
    const accountBtn = document.getElementById('profileDropdownBtn');
    const accountMenu = document.getElementById('profileMenu');

    if (accountBtn && accountMenu) {
        // No stopPropagation here: the layout's own handler needs this click so
        // that opening the account menu closes the notification one.
        accountBtn.addEventListener('click', function () {
            accountMenu.classList.toggle('hidden');
        });

        document.addEventListener('click', function (event) {
            if (!accountMenu.contains(event.target) && !accountBtn.contains(event.target)) {
                accountMenu.classList.add('hidden');
            }
        });
    }

    /* ---------- Dismissible alerts ---------- */
    document.querySelectorAll('[data-dismiss]').forEach(function (button) {
        button.addEventListener('click', function () {
            button.closest('[data-dismissible]').remove();
        });
    });

    /* ---------- Tabs ---------- */
    const tabs = Array.prototype.slice.call(document.querySelectorAll('.pf-tab'));
    const panels = {
        personal: document.getElementById('panel-personal'),
        security: document.getElementById('panel-security')
    };

    function selectTab(name, moveFocus) {
        if (!panels[name]) {
            return;
        }

        tabs.forEach(function (tab) {
            const isCurrent = tab.dataset.tab === name;

            tab.setAttribute('aria-selected', isCurrent ? 'true' : 'false');
            tab.tabIndex = isCurrent ? 0 : -1;
            panels[tab.dataset.tab].hidden = !isCurrent;

            if (isCurrent && moveFocus) {
                tab.focus();
            }
        });

        if (history.replaceState) {
            history.replaceState(null, '', '#' + name);
        }
    }

    tabs.forEach(function (tab, index) {
        tab.addEventListener('click', function () {
            selectTab(tab.dataset.tab, false);
        });

        tab.addEventListener('keydown', function (event) {
            const step = event.key === 'ArrowRight' ? 1 : (event.key === 'ArrowLeft' ? -1 : 0);

            if (!step) {
                return;
            }

            event.preventDefault();
            selectTab(tabs[(index + step + tabs.length) % tabs.length].dataset.tab, true);
        });
    });

    const hashTab = (window.location.hash || '').replace('#', '');
    selectTab(panels[hashTab] ? hashTab : @json($startTab), false);

    /* ---------- Personal information: read mode / edit mode ---------- */
    const personalForm = document.getElementById('personalForm');
    const editToggle = document.getElementById('editToggle');
    const editCancel = document.getElementById('editCancel');
    const editables = Array.prototype.slice.call(personalForm.querySelectorAll('[data-editable]'));
    const editActions = personalForm.querySelector('[data-edit-actions]');
    const editLabel = editToggle.querySelector('[data-edit-label]');

    function setEditing(editing, focusFirst) {
        editables.forEach(function (field) {
            field.readOnly = !editing;
        });

        editActions.hidden = !editing;
        editLabel.textContent = editing ? 'Cancel' : 'Edit';
        editToggle.setAttribute('aria-expanded', editing ? 'true' : 'false');

        if (editing && focusFirst && editables.length) {
            const first = editables[0];

            first.focus();
            first.setSelectionRange(first.value.length, first.value.length);
        }
    }

    function resetFields() {
        editables.forEach(function (field) {
            field.value = field.dataset.initial || '';
            field.classList.remove('has-error');
        });
    }

    editToggle.addEventListener('click', function () {
        const startEditing = editActions.hidden;

        if (!startEditing) {
            resetFields();
        }

        setEditing(startEditing, true);
    });

    editCancel.addEventListener('click', function () {
        resetFields();
        setEditing(false, false);
        editToggle.focus();
    });

    // Nothing changed means there is nothing to save.
    personalForm.addEventListener('submit', function (event) {
        const changed = editables.some(function (field) {
            return field.value.trim() !== (field.dataset.initial || '');
        });

        if (!changed) {
            event.preventDefault();
            setEditing(false, false);
        }
    });

    setEditing(@json($startEditing), false);

    /* ---------- Profile photo ---------- */
    const photoForm = document.getElementById('photoForm');

    if (photoForm) {
        const photoInput = document.getElementById('photoInput');
        const removeFlag = document.getElementById('removePhotoFlag');

        document.querySelectorAll('[data-photo-pick]').forEach(function (button) {
            button.addEventListener('click', function () {
                photoInput.click();
            });
        });

        photoInput.addEventListener('change', function () {
            const file = photoInput.files && photoInput.files[0];

            if (!file) {
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                window.alert('That photo is larger than 2 MB. Please choose a smaller one.');
                photoInput.value = '';
                return;
            }

            photoForm.submit();
        });

        document.querySelectorAll('[data-photo-remove]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!window.confirm('Remove your profile photo? Your initials will be shown instead.')) {
                    return;
                }

                removeFlag.value = '1';
                photoForm.submit();
            });
        });
    }

    /* ---------- Password modal ---------- */
    const modal = document.getElementById('passwordModal');
    const passwordForm = document.getElementById('passwordForm');
    const submitButton = document.getElementById('passwordSubmit');
    const currentField = document.getElementById('current_password');
    const newField = document.getElementById('password');
    const confirmField = document.getElementById('password_confirmation');
    let lastFocused = null;

    function validatePassword() {
        const current = currentField.value;
        const next = newField.value;
        const repeated = confirmField.value;

        const state = {
            length: next.length >= 8,
            match: next.length > 0 && next === repeated,
            different: next.length > 0 && next !== current
        };

        Object.keys(state).forEach(function (key) {
            passwordForm.querySelector('[data-check="' + key + '"]').classList.toggle('is-ok', state[key]);
        });

        submitButton.disabled = !(current.length > 0 && state.length && state.match && state.different);
    }

    function openModal() {
        lastFocused = document.activeElement;
        modal.classList.add('is-open');
        currentField.focus();
    }

    function closeModal() {
        modal.classList.remove('is-open');
        passwordForm.reset();
        validatePassword();

        if (lastFocused) {
            lastFocused.focus();
        }
    }

    document.querySelectorAll('[data-open-password]').forEach(function (button) {
        button.addEventListener('click', openModal);
    });

    document.querySelectorAll('[data-close-password]').forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        if (modal.classList.contains('is-open')) {
            closeModal();
        } else if (accountMenu) {
            accountMenu.classList.add('hidden');
        }
    });

    [currentField, newField, confirmField].forEach(function (field) {
        field.addEventListener('input', validatePassword);
    });

    passwordForm.querySelectorAll('[data-toggle-pass]').forEach(function (button) {
        button.addEventListener('click', function () {
            const field = document.getElementById(button.dataset.togglePass);
            const showing = field.type === 'text';

            field.type = showing ? 'password' : 'text';
            button.setAttribute('aria-pressed', showing ? 'false' : 'true');
            button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            button.querySelector('[data-eye-on]').hidden = !showing;
            button.querySelector('[data-eye-off]').hidden = showing;
        });
    });

    validatePassword();

    @if ($passwordErrors->any())
        openModal();
    @endif
})();
</script>
@endpush
