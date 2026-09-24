{{--
    Shared public portal navbar. Links are the portal's existing pages; the
    current page is highlighted from the route name.
--}}
@php
    $skPortalLinks = [
        ['route' => 'public.home', 'label' => 'Home'],
        ['route' => 'public.announcements', 'label' => 'Announcements'],
        ['route' => 'public.calendar', 'label' => 'Calendar'],
        ['route' => 'public.leadership', 'label' => 'Leadership'],
        ['route' => 'public.budgets', 'label' => 'Annual Budget'],
    ];
@endphp

<header class="sk-lnav sk-pnav" data-sk-portal-nav>
    <div class="max-w-6xl mx-auto px-6 sk-lnav__inner">
        <a href="{{ route('public.home') }}" class="sk-lnav__brand">
            <img src="{{ asset('images/logo.png') }}" alt="SK360 Logo">
            <span class="leading-tight">
                <span class="block">SK 360&deg;</span>
                <span class="block text-[10.5px] font-extrabold uppercase tracking-[0.12em] text-gray-500">Public Information Portal</span>
            </span>
        </a>

        <nav class="sk-lnav__links" aria-label="Public portal">
            @foreach ($skPortalLinks as $skLink)
                @if (Route::has($skLink['route']))
                    <a href="{{ route($skLink['route']) }}" class="{{ request()->routeIs($skLink['route']) ? 'is-active' : '' }}" @if (request()->routeIs($skLink['route'])) aria-current="page" @endif>
                        {{ $skLink['label'] }}
                    </a>
                @endif
            @endforeach
        </nav>

        <a href="{{ url('/') }}" class="sk-btn sk-btn--secondary sk-btn--sm sk-lnav__cta">
            @include('partials.ui.icon', ['icon' => 'chevron-left', 'iconSize' => 15])
            Main Website
        </a>

        <button type="button" class="sk-icon-btn sk-icon-btn--filled sk-lnav__toggle" aria-label="Open menu" data-sk-portal-menu>
            @include('partials.ui.icon', ['icon' => 'menu', 'iconSize' => 21])
        </button>
    </div>
</header>

<script>
    (function () {
        const nav = document.querySelector('[data-sk-portal-nav]');
        if (!nav) return;
        const onScroll = () => nav.classList.toggle('is-scrolled', window.scrollY > 8);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        nav.querySelector('[data-sk-portal-menu]')?.addEventListener('click', () => nav.classList.toggle('is-open'));
    })();
</script>
