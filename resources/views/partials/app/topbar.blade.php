{{--
    Shared top navbar for every signed-in page.

    Pages keep their own dropdown scripts, so the element IDs those scripts use
    are preserved: #notifBtn / #notifDropdown for notifications (filled in by the
    feed script in layouts/app) and an account button/menu whose IDs are passed
    in, because some pages use #userMenuBtn / #userDropdown and others
    #profileDropdownBtn / #profileMenu.

    Options:
      accountButtonId, accountMenuId  IDs the page's script expects.
      bindBell                        true when the page has no bell script of its
                                      own, so the shared toggle opens it instead.
      search                          null for the standard search field, false to
                                      hide it, or ['id' => ..., 'class' => ...,
                                      'placeholder' => ...] to keep the hooks of a
                                      page's working search input.
--}}
@php
    $skUser = auth()->user();
    $skRole = $skUser->role ?? null;
    $skRoleLabel = match ($skRole) {
        'sk_president' => 'SK Federation President',
        'sk_chairman' => 'SK Chairman',
        'sk_secretary' => 'SK Secretary',
        default => 'SK Official',
    };
    $skProfileRoute = match ($skRole) {
        'sk_chairman' => 'sk_chairman.profile',
        'sk_secretary' => 'sk_secretary.profile',
        default => 'sk_pres.profile',
    };
    $skName = trim($fullName ?? trim(($skUser->first_name ?? '') . ' ' . ($skUser->last_name ?? ''))) ?: 'SK Official';
    $skInitials = collect(preg_split('/\s+/', $skName))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $skPhoto = !empty($skUser->profile_pic ?? null) ? asset('uploads/profile_pics/' . $skUser->profile_pic) : null;
    $skAccountButtonId = $accountButtonId ?? 'userMenuBtn';
    $skAccountMenuId = $accountMenuId ?? 'userDropdown';
    $skBindBell = $bindBell ?? false;
    $skSearch = $search ?? [];
@endphp

<header class="sk-topbar">
    <button type="button" class="sk-icon-btn sk-icon-btn--filled sk-topbar__menu" data-sk-sidebar-open aria-label="Open menu" aria-controls="skSidebar">
        @include('partials.ui.icon', ['icon' => 'menu', 'iconSize' => 21])
    </button>

    @if ($skSearch !== false)
        <label class="sk-search sk-topbar__search">
            <span class="sr-only">Search</span>
            @include('partials.ui.icon', ['icon' => 'search', 'iconSize' => 18])
            <input type="text"
                @isset($skSearch['id']) id="{{ $skSearch['id'] }}" @endisset
                @isset($skSearch['class']) class="{{ $skSearch['class'] }}" @endisset
                placeholder="{{ $skSearch['placeholder'] ?? 'Search...' }}"
                autocomplete="off">
        </label>
    @endif

    <div class="sk-topbar__actions">
        <div class="relative">
            <button id="notifBtn" type="button" class="sk-icon-btn" aria-label="Notifications" @if ($skBindBell) data-sk-dropdown="notifDropdown" @endif>
                @include('partials.ui.icon', ['icon' => 'bell', 'iconSize' => 21])
            </button>

            <div id="notifDropdown" class="hidden sk-dropdown sk-dropdown--notifications">
                <div class="sk-dropdown__header">Notifications</div>
                <div class="sk-dropdown__empty">No notifications yet</div>
            </div>
        </div>

        <span class="sk-topbar__divider" aria-hidden="true"></span>

        <div class="relative">
            <button id="{{ $skAccountButtonId }}" type="button" class="sk-user-btn" aria-haspopup="menu">
                <span class="sk-avatar">
                    @if ($skPhoto)
                        <img src="{{ $skPhoto }}" alt="">
                    @else
                        {{ $skInitials }}
                    @endif
                </span>
                <span class="sk-user-btn__text">
                    <span class="sk-user-btn__name">{{ $skName }}</span>
                    <span class="sk-user-btn__role">{{ $skRoleLabel }}</span>
                </span>
                @include('partials.ui.icon', ['icon' => 'chevron-down', 'iconSize' => 17, 'iconClass' => 'text-gray-400'])
            </button>

            <div id="{{ $skAccountMenuId }}" class="hidden sk-dropdown">
                <div class="sk-dropdown__header">
                    My Account
                    <span class="sk-dropdown__caption">{{ $skName }} &middot; {{ $skRoleLabel }}</span>
                </div>

                @if (Route::has($skProfileRoute))
                    <a href="{{ route($skProfileRoute) }}" class="sk-dropdown__item">
                        @include('partials.ui.icon', ['icon' => 'user', 'iconSize' => 18])
                        <span>Profile Settings</span>
                    </a>
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sk-dropdown__item sk-dropdown__item--danger">
                        @include('partials.ui.icon', ['icon' => 'log-out', 'iconSize' => 18])
                        <span>Log Out</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
