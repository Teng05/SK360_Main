{{-- Shared sidebar for signed-in SK360 web pages. --}}
@php
    $skRole = auth()->user()->role ?? null;
    $skRoleLabel = $roleLabel ?? match ($skRole) {
        'sk_president' => 'SK Federation President',
        'sk_chairman' => 'SK Chairman',
        'sk_secretary' => 'SK Secretary',
        default => 'SK Official',
    };
    $skNavGroups = [
        'Main' => ['home', 'dashboard', 'community', 'announcements', 'calendar'],
        'Management' => ['reports', 'budget', 'budget monitoring', 'budget & finance', 'submission slots', 'consolidation', 'meetings', 'video meetings', 'module management', 'rankings'],
        'Organization' => ['leadership', 'user management', 'barangays'],
        'Resources' => ['learning modules', 'archive'],
        'Communication' => ['chat'],
    ];
    $skNavIcons = [
        'home' => 'house','dashboard' => 'layout-dashboard','community' => 'users','announcements' => 'megaphone','calendar' => 'calendar-days',
        'reports' => 'file-text','budget' => 'wallet','budget monitoring' => 'wallet','budget & finance' => 'wallet','submission slots' => 'file-plus','consolidation' => 'files','meetings' => 'video','video meetings' => 'video',
        'module management' => 'settings','rankings' => 'chart-column','leadership' => 'id-card','user management' => 'user-cog','barangays' => 'building-2',
        'learning modules' => 'file-text','archive' => 'archive','chat' => 'message-square',
    ];
    $skSections = array_fill_keys(array_keys($skNavGroups), []);
    $skItemsForNavigation = $menuItems ?? [];
    if (in_array($skRole, ['sk_president', 'sk_chairman'], true)) {
        $skFinancePrefix = $skRole === 'sk_president' ? 'sk_pres' : 'sk_chairman';
        $skFinanceItem = ['label' => $skRole === 'sk_president' ? 'Budget & Finance' : 'Budget', 'link' => route($skFinancePrefix.'.finances'), 'financial' => true];
        $skFinanceInserted = false;
        $skItemsForNavigation = array_values(array_filter(array_map(function ($item) use ($skFinancePrefix, $skFinanceItem, &$skFinanceInserted) {
            $isFinancial = in_array(strtolower(trim($item['label'])), ['budget', 'budget monitoring', 'budget & finance'], true)
                || in_array($item['link'], [route($skFinancePrefix.'.finances'), url($skFinancePrefix.'/budget')], true);
            if (!$isFinancial) return $item;
            if ($skFinanceInserted) return null;
            $skFinanceInserted = true;
            return $skFinanceItem;
        }, $skItemsForNavigation)));
        if (!$skFinanceInserted) $skItemsForNavigation[] = $skFinanceItem;
    }
    foreach ($skItemsForNavigation as $skItem) {
        $skKey = strtolower(trim($skItem['label']));
        $skGroup = 'More';
        foreach ($skNavGroups as $skName => $skLabels) {
            if (in_array($skKey, $skLabels, true)) { $skGroup = $skName; break; }
        }
        $skSections[$skGroup][] = $skItem + ['skIcon' => $skNavIcons[$skKey] ?? 'layout-grid'];
    }
    $skSections = array_filter($skSections);
@endphp
<aside class="sk-sidebar" id="skSidebar" aria-label="Main navigation">
    <div class="sk-sidebar__brand">
        <img src="{{ asset('images/logo.png') }}?v={{ @filemtime(public_path('images/logo.png')) }}" class="sk-sidebar__logo" alt="SK 360 logo">
        <div class="min-w-0">
            <p class="sk-sidebar__name">SK 360&deg;</p>
            <p class="sk-sidebar__tag">Management System</p>
        </div>
        <button type="button" class="sk-icon-btn sk-sidebar__close" data-sk-sidebar-close aria-label="Close menu">
            @include('partials.ui.icon', ['icon' => 'x', 'iconSize' => 20])
        </button>
    </div>
    <div class="sk-role-badge">
        <span class="sk-dot"></span>
        <span>{{ $skRoleLabel }}</span>
    </div>
    <nav class="sk-nav">
        @foreach ($skSections as $skGroup => $skItems)
            <p class="sk-nav__heading">{{ $skGroup }}</p>
            @foreach ($skItems as $skItem)
                @php
                    $skActive = !empty($skItem['financial'])
                        ? request()->routeIs($skFinancePrefix.'.finances*', $skFinancePrefix.'.budget*')
                        : $skItem['link'] === ($currentUrl ?? null);
                @endphp
                <a href="{{ $skItem['link'] }}" class="sk-nav__link {{ $skActive ? 'is-active' : '' }}" @if ($skActive) aria-current="page" @endif>
                    @include('partials.ui.icon', ['icon' => $skItem['skIcon'], 'iconSize' => 19])
                    <span class="truncate">{{ $skItem['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>
    <div class="sk-sidebar__footer">
        <div class="sk-sidebar__footnote"><span>Sangguniang Kabataan</span><strong>Lipa City</strong></div>
    </div>
</aside>
<div class="sk-sidebar-backdrop" data-sk-sidebar-close></div>
