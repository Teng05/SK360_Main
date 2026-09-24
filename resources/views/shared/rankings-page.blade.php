{{-- File guide: Blade view template for resources/views/shared/rankings-page.blade.php. --}}
@php
    $rankInitials = fn ($name) => collect(preg_split('/\s+/', trim((string) $name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('') ?: 'SK';

    // Gold, silver and bronze only mark placement; red stays the main colour.
    $placeStyles = [
        1 => ['label' => '1st Place', 'ring' => '#d4a020', 'soft' => '#fff7dc', 'ink' => '#8a5a00', 'icon' => 'crown'],
        2 => ['label' => '2nd Place', 'ring' => '#9aa3b2', 'soft' => '#f1f3f6', 'ink' => '#4b5363', 'icon' => 'medal'],
        3 => ['label' => '3rd Place', 'ring' => '#c27a3a', 'soft' => '#fbefe4', 'ink' => '#8a4b16', 'icon' => 'award'],
    ];

    // Podium order: 2nd, 1st, 3rd.
    $podium = collect([2, 1, 3])
        ->map(fn ($rank) => $topRankings->first(fn ($top) => (int) ($top['rank'] ?? 0) === $rank))
        ->filter();
    if ($podium->isEmpty()) {
        $podium = $topRankings;
    }
@endphp

<div class="flex h-screen bg-gray-100">
    @include('partials.app.sidebar')

    <div class="flex-1 flex flex-col min-w-0">
        @include('partials.app.topbar')

        <div class="flex-1 p-8 overflow-y-auto h-full">
            <div class="sk-page-head">
                <div class="sk-page-head__text">
                    <span class="sk-eyebrow"><span class="sk-dot"></span>Barangay Rankings</span>
                    <h1 class="sk-page-title">Point System Rankings</h1>
                    <p class="sk-page-subtitle">
                        Rankings based on recorded submissions, documentation, and participation{{ $latestPeriod ? ' for ' . $latestPeriod : '' }}
                    </p>
                </div>

                <form method="GET" action="{{ url()->current() }}" class="w-full md:w-64">
                    <label for="rankingPeriod" class="sk-overline block mb-2">
                        Month / Year
                    </label>

                    <div class="relative">
                        <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-red-500">
                            @include('partials.ui.icon', ['icon' => 'calendar-days', 'iconSize' => 17])
                        </span>

                        <select id="rankingPeriod" name="period" onchange="this.form.submit()"
                            class="w-full appearance-none bg-white border border-gray-200 rounded-xl pl-10 pr-10 py-3 text-sm font-bold text-gray-800 shadow-sm cursor-pointer">
                            @foreach ($rankingPeriods ?? [] as $period)
                                <option value="{{ $period['value'] }}" @selected(($selectedPeriod ?? '') === $period['value'])>
                                    {{ $period['label'] }}
                                </option>
                            @endforeach
                        </select>

                        <span class="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400">
                            @include('partials.ui.icon', ['icon' => 'chevron-down', 'iconSize' => 17])
                        </span>
                    </div>
                </form>
            </div>

            @isset($rankingsLiveRoute)
                <div id="react-rankings-live" data-url="{{ $rankingsLiveRoute }}"></div>
            @endisset

            {{-- TOP 3 --}}
            @if ($topRankings->isNotEmpty())
                <section class="sk-card relative mb-8 overflow-hidden px-6 pb-8 pt-7">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-48 bg-gradient-to-b from-red-50/70 to-transparent"></div>

                    <div class="relative text-center mb-8">
                        <span class="sk-eyebrow !mb-3">
                            @include('partials.ui.icon', ['icon' => 'trophy', 'iconSize' => 13])
                            Honor Roll
                        </span>
                        <h2 class="sk-section-title !text-[22px]">Top Performing Councils</h2>
                        @if ($latestPeriod)
                            <p class="sk-section-subtitle">{{ $latestPeriod }}</p>
                        @endif
                    </div>

                    <div class="relative grid grid-cols-1 md:grid-cols-3 items-end gap-5 max-w-4xl mx-auto">
                        @foreach ($podium as $top)
                            @php
                                $place = (int) ($top['rank'] ?? 0);
                                $style = $placeStyles[$place] ?? $placeStyles[3];
                                $isFirst = $place === 1;
                            @endphp

                            <div class="{{ $isFirst ? 'md:order-2 md:-translate-y-2' : ($place === 2 ? 'md:order-1' : 'md:order-3') }} order-{{ $place }}">
                                <div class="sk-rank-card rounded-[20px] border text-center transition hover:-translate-y-1 {{ $isFirst ? 'border-yellow-200 bg-white shadow-sm px-6 pt-8 pb-7' : 'border-gray-200 bg-white/90 shadow-sm px-5 pt-6 pb-6' }}" style="--sk-rank-accent: {{ $style['ring'] }}; --sk-rank-soft: {{ $style['soft'] }}; --sk-rank-ink: {{ $style['ink'] }};">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-extrabold uppercase tracking-wider" style="background: {{ $style['soft'] }}; color: {{ $style['ink'] }};">
                                        @include('partials.ui.icon', ['icon' => $style['icon'], 'iconSize' => 13])
                                        {{ $style['label'] }}
                                    </span>

                                    <div class="relative mx-auto mt-5 {{ $isFirst ? 'h-24 w-24' : 'h-20 w-20' }}">
                                        <div class="flex h-full w-full items-center justify-center rounded-full bg-white text-xl font-extrabold text-red-600" style="box-shadow: 0 0 0 4px #fff, 0 0 0 {{ $isFirst ? '8px' : '7px' }} {{ $style['ring'] }};">
                                            <span class="{{ $isFirst ? 'text-2xl' : 'text-xl' }}">{{ $rankInitials($top['name']) }}</span>
                                        </div>
                                        <span class="absolute -bottom-2 -right-1 flex h-8 w-8 items-center justify-center rounded-full border-2 border-white text-sm font-extrabold text-white" style="background: {{ $isFirst ? 'var(--sk-red)' : $style['ring'] }};">
                                            {{ $place }}
                                        </span>
                                    </div>

                                    <h3 class="mt-6 font-extrabold text-gray-900 {{ $isFirst ? 'text-lg' : 'text-base' }}">{{ $top['name'] }}</h3>

                                    <div class="sk-rank-points mt-4 rounded-2xl px-4 py-3">
                                        <p class="sk-overline">Total Points</p>
                                        <p class="mt-1 font-extrabold leading-none tracking-tight {{ $isFirst ? 'text-4xl text-red-600' : 'text-3xl text-gray-900' }}">
                                            {{ $top['points'] }}
                                            <span class="text-xs font-bold uppercase text-gray-400">pts</span>
                                        </p>
                                    </div>

                                    @if (!empty($top['badges']))
                                        <div class="mt-4 flex justify-center gap-1.5 flex-wrap">
                                            @foreach ($top['badges'] as $badge)
                                                <span class="sk-badge sk-badge--gray !text-[11px]">
                                                    {{ $badge }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- COMPLETE LEADERBOARD --}}
            <section class="sk-card p-6 mb-8">
                <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
                    <div>
                        <h3 class="sk-section-title">Complete Leaderboard</h3>
                        <p class="sk-section-subtitle">
                            Ranking metrics are based on recorded activity for {{ $latestPeriod ?? 'the selected ranking period' }}.
                        </p>
                    </div>

                    <div class="w-full md:w-80">
                        <label for="leaderboardSearch" class="sr-only">
                            Search Barangay
                        </label>

                        <div class="sk-search">
                            @include('partials.ui.icon', ['icon' => 'search', 'iconSize' => 18])

                            <input type="text" id="leaderboardSearch" placeholder="Search barangay..." autocomplete="off">

                            <button id="clearLeaderboardSearch" type="button"
                                class="hidden absolute right-2 top-1/2 -translate-y-1/2 sk-icon-btn !w-8 !h-8"
                                title="Clear search">
                                @include('partials.ui.icon', ['icon' => 'x', 'iconSize' => 16])
                            </button>
                        </div>
                    </div>
                </div>

                <div class="hidden md:grid grid-cols-[minmax(220px,1.1fr)_2fr_110px] gap-6 px-5 pb-3 border-b border-gray-100 sk-overline">
                    <span>Barangay</span>
                    <span>On-time &middot; Documentation &middot; Participation</span>
                    <span class="text-right">Rank</span>
                </div>

                <div id="leaderboardList" class="divide-y divide-gray-100">
                    @forelse ($leaderboard as $row)
                        @php $rowStyle = $placeStyles[(int) $row->rank] ?? null; @endphp

                        <div class="leaderboard-row grid grid-cols-1 md:grid-cols-[minmax(220px,1.1fr)_2fr_110px] items-center gap-4 md:gap-6 px-5 py-4 transition hover:bg-[#fbfbfc] {{ (int) $row->rank === 1 ? 'bg-red-50/40' : '' }}"
                            data-barangay="{{ $row->name }}">

                            <div class="flex items-center gap-3.5 min-w-0">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-extrabold"
                                    style="{{ $rowStyle ? 'background:' . $rowStyle['soft'] . ';color:' . $rowStyle['ink'] . ';' : 'background:var(--sk-gray-soft);color:var(--sk-text);' }}">
                                    {{ $row->rank }}
                                </span>

                                <span class="sk-avatar !w-10 !h-10 !text-xs">{{ $rankInitials($row->name) }}</span>

                                <div class="min-w-0 leading-tight">
                                    <h4 class="truncate text-sm font-bold text-gray-900">{{ $row->name }}</h4>
                                    <p class="mt-0.5 text-xs font-semibold text-gray-500">{{ $row->points }} points</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 w-full">
                                @foreach ([
                                    ['label' => 'On-time Rate', 'val' => $row->on_time],
                                    ['label' => 'Documentation', 'val' => $row->completion],
                                    ['label' => 'Participation Score', 'val' => $row->engagement],
                                ] as $metric)
                                    <div>
                                        <div class="flex justify-between text-[11px] font-bold text-gray-500 mb-1.5">
                                            <span>{{ $metric['label'] }}</span>
                                            <span class="text-gray-800">{{ $metric['val'] }}%</span>
                                        </div>

                                        <div class="sk-progress">
                                            <span style="width: {{ max(0, min(100, $metric['val'])) }}%"></span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex items-center gap-2 md:justify-end">
                                <span class="sk-badge sk-badge--red">
                                    #{{ $row->rank }}
                                </span>

                                @if ($row->trend === 'up')
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-green-50 text-green-600" title="Rank increased">
                                        @include('partials.ui.icon', ['icon' => 'trending-up', 'iconSize' => 15])
                                    </span>
                                @elseif ($row->trend === 'down')
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-red-50 text-red-600 rotate-180 -scale-x-100" title="Rank decreased">
                                        @include('partials.ui.icon', ['icon' => 'trending-up', 'iconSize' => 15])
                                    </span>
                                @elseif ($row->trend === 'same')
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-100 text-gray-400 font-extrabold" title="No change in rank">&mdash;</span>
                                @else
                                    <span class="sk-badge sk-badge--blue !text-[11px]" title="No previous ranking record">
                                        New
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="sk-empty">
                            <span class="sk-icon-tile sk-icon-tile--yellow">
                                @include('partials.ui.icon', ['icon' => 'trophy', 'iconSize' => 24])
                            </span>
                            <p class="sk-empty__text">No rankings found.</p>
                        </div>
                    @endforelse
                </div>

                <div id="leaderboardEmpty" class="hidden sk-empty">
                    <span class="sk-icon-tile sk-icon-tile--gray">
                        @include('partials.ui.icon', ['icon' => 'search', 'iconSize' => 24])
                    </span>
                    <p class="sk-empty__title">No barangay found</p>
                    <p class="sk-empty__text">Try another barangay name.</p>
                </div>

                @if ($leaderboard->isNotEmpty())
                    <div id="leaderboardPaginationWrapper" class="mt-4 pt-5 border-t border-gray-100">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <p id="leaderboardInfo" class="text-xs font-semibold text-gray-500"></p>

                            <div class="flex items-center gap-2 flex-wrap">
                                <button id="leaderboardPrevious" type="button"
                                    class="sk-btn sk-btn--secondary sk-btn--sm disabled:opacity-40 disabled:cursor-not-allowed">
                                    @include('partials.ui.icon', ['icon' => 'chevron-left', 'iconSize' => 15])
                                    Previous
                                </button>

                                <div id="leaderboardPages" class="flex items-center gap-1 flex-wrap"></div>

                                <button id="leaderboardNext" type="button"
                                    class="sk-btn sk-btn--secondary sk-btn--sm disabled:opacity-40 disabled:cursor-not-allowed">
                                    Next
                                    @include('partials.ui.icon', ['icon' => 'chevron-right', 'iconSize' => 15])
                                </button>
                            </div>
                        </div>

                        <p class="text-xs text-gray-500 mt-4 flex items-center gap-1.5">
                            @include('partials.ui.icon', ['icon' => 'info', 'iconSize' => 14, 'iconClass' => 'text-gray-400'])
                            Participation Score is relative to the highest recorded participation points for {{ $latestPeriod ?? 'the selected ranking period' }}.
                        </p>
                    </div>
                @endif
            </section>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-10">
                <section class="sk-card p-6">
                    <div class="flex items-start gap-3 mb-5">
                        <span class="sk-icon-tile sk-icon-tile--sm">
                            @include('partials.ui.icon', ['icon' => 'star', 'iconSize' => 18])
                        </span>
                        <div>
                            <h3 class="sk-section-title !text-[17px]">Points System</h3>
                            <p class="sk-section-subtitle">How points are currently earned and deducted</p>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-100">
                        @foreach ($pointSystem ?? [] as $rule)
                            @php
                                $isPositive = ($rule['type'] ?? 'positive') === 'positive';
                                $prefix = $rule['points'] > 0 ? '+' : '';
                            @endphp

                            <div class="flex justify-between items-center gap-4 py-3 text-sm">
                                <span class="text-gray-700 font-semibold">{{ $rule['label'] }}</span>
                                <span class="sk-badge {{ $isPositive ? 'sk-badge--green' : 'sk-badge--red' }}">
                                    {{ $prefix }}{{ $rule['points'] }} points
                                </span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="sk-card p-6">
                    <div class="flex items-start gap-3 mb-5">
                        <span class="sk-icon-tile sk-icon-tile--sm sk-icon-tile--blue">
                            @include('partials.ui.icon', ['icon' => 'info', 'iconSize' => 18])
                        </span>
                        <div>
                            <h3 class="sk-section-title !text-[17px]">Ranking Indicators</h3>
                            <p class="sk-section-subtitle">What each part of the leaderboard means</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-start gap-4">
                            <span class="sk-icon-tile sk-icon-tile--sm sk-icon-tile--yellow">
                                @include('partials.ui.icon', ['icon' => 'trophy', 'iconSize' => 17])
                            </span>

                            <div>
                                <h4 class="text-sm font-bold text-gray-900">Selected Period Rank</h4>
                                <p class="text-[13px] text-gray-500 mt-0.5 leading-relaxed">
                                    Position is based on total recorded points for the selected ranking period.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <span class="sk-icon-tile sk-icon-tile--sm sk-icon-tile--green">
                                @include('partials.ui.icon', ['icon' => 'trending-up', 'iconSize' => 17])
                            </span>

                            <div>
                                <h4 class="text-sm font-bold text-gray-900">Rank Movement</h4>
                                <p class="text-[13px] text-gray-500 mt-0.5 leading-relaxed">
                                    Compares the selected period's rank with the immediately previous month.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <span class="sk-icon-tile sk-icon-tile--sm sk-icon-tile--blue">
                                @include('partials.ui.icon', ['icon' => 'chart-column', 'iconSize' => 17])
                            </span>

                            <div>
                                <h4 class="text-sm font-bold text-gray-900">Recorded Metrics</h4>
                                <p class="text-[13px] text-gray-500 mt-0.5 leading-relaxed">
                                    On-time, documentation, and participation values come from recorded ranking activity for the selected period.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');

    if (notifBtn && notifDropdown && userDropdown) {
        notifBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            notifDropdown.classList.toggle('hidden');
            userDropdown.classList.add('hidden');
        });
    }

    if (userMenuBtn && userDropdown && notifDropdown) {
        userMenuBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            userDropdown.classList.toggle('hidden');
            notifDropdown.classList.add('hidden');
        });
    }

    document.addEventListener('click', function (e) {
        if (notifBtn && notifDropdown && !notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
            notifDropdown.classList.add('hidden');
        }

        if (userMenuBtn && userDropdown && !userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.classList.add('hidden');
        }
    });

    const searchInput = document.getElementById('leaderboardSearch');
    const clearSearch = document.getElementById('clearLeaderboardSearch');
    const rows = Array.from(document.querySelectorAll('.leaderboard-row'));
    const emptyState = document.getElementById('leaderboardEmpty');
    const paginationWrapper = document.getElementById('leaderboardPaginationWrapper');
    const info = document.getElementById('leaderboardInfo');
    const previousButton = document.getElementById('leaderboardPrevious');
    const nextButton = document.getElementById('leaderboardNext');
    const pagesContainer = document.getElementById('leaderboardPages');

    const perPage = 10;
    let currentPage = 1;
    let filteredRows = rows;

    function filterRows() {
        const term = searchInput ? searchInput.value.trim().toLowerCase() : '';

        filteredRows = rows.filter(function (row) {
            const barangay = (row.dataset.barangay || '').toLowerCase();
            return barangay.includes(term);
        });

        if (clearSearch) {
            clearSearch.classList.toggle('hidden', term.length === 0);
        }
    }

    function buildPageButton(page) {
        const button = document.createElement('button');
        const isActive = page === currentPage;

        button.type = 'button';
        button.textContent = page;
        button.className = isActive
            ? 'min-w-[34px] h-[34px] px-3 rounded-[10px] bg-red-600 text-white text-xs font-bold shadow-sm'
            : 'min-w-[34px] h-[34px] px-3 rounded-[10px] border border-gray-200 bg-white text-gray-600 text-xs font-bold hover:border-red-200 hover:bg-red-50 hover:text-red-600 transition';

        button.addEventListener('click', function () {
            currentPage = page;
            renderLeaderboard();
        });

        return button;
    }

    function renderPageNumbers(totalPages) {
        if (!pagesContainer) {
            return;
        }

        pagesContainer.innerHTML = '';

        if (totalPages <= 1) {
            return;
        }

        const maxVisible = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        let endPage = Math.min(totalPages, startPage + maxVisible - 1);

        if (endPage - startPage + 1 < maxVisible) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }

        for (let page = startPage; page <= endPage; page++) {
            pagesContainer.appendChild(buildPageButton(page));
        }
    }

    function renderLeaderboard() {
        filterRows();

        const totalResults = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(totalResults / perPage));

        if (currentPage > totalPages) {
            currentPage = totalPages;
        }

        rows.forEach(function (row) {
            row.classList.add('hidden');
        });

        if (totalResults === 0) {
            if (emptyState) {
                emptyState.classList.remove('hidden');
            }

            if (paginationWrapper) {
                paginationWrapper.classList.add('hidden');
            }

            return;
        }

        if (emptyState) {
            emptyState.classList.add('hidden');
        }

        if (paginationWrapper) {
            paginationWrapper.classList.remove('hidden');
        }

        const startIndex = (currentPage - 1) * perPage;
        const endIndex = Math.min(startIndex + perPage, totalResults);

        filteredRows.slice(startIndex, endIndex).forEach(function (row) {
            row.classList.remove('hidden');
        });

        if (info) {
            info.textContent = 'Showing ' + (startIndex + 1) + '-' + endIndex + ' of ' + totalResults + ' barangays';
        }

        if (previousButton) {
            previousButton.disabled = currentPage <= 1;
        }

        if (nextButton) {
            nextButton.disabled = currentPage >= totalPages;
        }

        renderPageNumbers(totalPages);
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            currentPage = 1;
            renderLeaderboard();
        });
    }

    if (clearSearch) {
        clearSearch.addEventListener('click', function () {
            if (!searchInput) {
                return;
            }

            searchInput.value = '';
            currentPage = 1;
            searchInput.focus();
            renderLeaderboard();
        });
    }

    if (previousButton) {
        previousButton.addEventListener('click', function () {
            if (currentPage <= 1) {
                return;
            }

            currentPage--;
            renderLeaderboard();
        });
    }

    if (nextButton) {
        nextButton.addEventListener('click', function () {
            const totalPages = Math.max(1, Math.ceil(filteredRows.length / perPage));

            if (currentPage >= totalPages) {
                return;
            }

            currentPage++;
            renderLeaderboard();
        });
    }

    renderLeaderboard();
});
</script>
@endpush
