{{-- File guide: Blade view template for resources/views/shared/rankings-page.blade.php. --}}
<div class="flex h-screen bg-gray-100">
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
        <div class="flex items-center gap-3 mb-4">
            <img src="{{ asset('images/logo.png') }}" class="w-8 h-8 rounded-full object-cover" alt="logo">
            <div class="leading-tight">
                <h2 class="text-lg font-extrabold tracking-wide">SK 360°</h2>
                <p class="text-[10px] opacity-80">Management System</p>
            </div>
        </div>

        <div class="bg-red-500 rounded-lg p-2 flex items-center gap-2 mb-3 shadow text-xs">
            <div class="bg-yellow-400 text-red-600 p-1 rounded-full text-sm">👤</div>
            <div>
                <p class="font-semibold text-xs">{{ $fullName }}</p>
                <p class="text-xs opacity-80">{{ $roleLabel }}</p>
            </div>
        </div>

        <nav class="space-y-1 text-xs">
            @foreach ($menuItems as $item)
                @php $isActive = $item['link'] === $currentUrl; @endphp
                <a href="{{ $item['link'] }}" class="flex items-center gap-2 p-2 rounded-lg {{ $isActive ? 'bg-red-500' : 'hover:bg-red-500 transition' }}">
                    <span class="{{ $isActive ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded text-sm">{!! $item['icon'] !!}</span>
                    <span class="{{ $isActive ? 'text-yellow-300 font-semibold' : '' }} text-xs">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="flex-1 flex flex-col">
        <div class="bg-red-600 text-white px-6 py-3 flex justify-between items-center shadow">
            <input type="text" placeholder="Search..." class="px-4 py-2 rounded-full text-black w-1/3 focus:outline-none">

            <div class="flex items-center gap-3 relative">
                <div class="relative">
                    <button id="notifBtn" type="button" class="text-xl hover:bg-red-500 p-2 rounded-lg transition">
                        🔔
                    </button>

                    <div id="notifDropdown" class="hidden absolute right-0 mt-3 w-72 bg-white rounded-2xl shadow-xl border z-50 overflow-hidden">
                        <div class="px-4 py-3 font-semibold border-b text-gray-800">Notifications</div>
                        <div class="max-h-64 overflow-y-auto">
                            <div class="px-4 py-3 hover:bg-gray-100 text-sm text-gray-700">No notifications yet</div>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <button id="userMenuBtn" type="button" class="flex items-center gap-2 hover:bg-red-500 px-3 py-2 rounded-lg transition">
                        <span class="font-semibold">{{ $fullName }}</span>
                    </button>

                    <div id="userDropdown" class="hidden absolute right-0 mt-3 w-64 bg-white rounded-2xl shadow-xl border overflow-hidden z-50">
                        <div class="px-5 py-4 font-semibold text-gray-800 border-b">My Account</div>

                        <a href="{{ $profileRoute ?? '#' }}" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-100 transition">
                            <span>👤</span>
                            <span class="text-gray-700">Profile Settings</span>
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left flex items-center gap-3 px-5 py-3 text-red-500 hover:bg-gray-100 transition">
                                <span>↩️</span>
                                <span>Log Out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-8 overflow-y-auto h-full bg-gray-50">
            <div class="mb-8 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 uppercase tracking-tight">Point System Rankings</h1>
                    <p class="text-gray-500">
                        Rankings based on recorded submissions, documentation, and participation{{ $latestPeriod ? ' for ' . $latestPeriod : '' }}
                    </p>
                </div>

                <form method="GET" action="{{ url()->current() }}" class="w-full md:w-64">
                    <label for="rankingPeriod" class="block text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">
                        Month / Year
                    </label>

                    <div class="relative">
                        <select id="rankingPeriod" name="period" onchange="this.form.submit()"
                            class="w-full appearance-none bg-white border border-gray-200 rounded-xl px-4 py-3 pr-10 text-xs font-bold text-gray-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-red-100 focus:border-red-300 cursor-pointer">
                            @foreach ($rankingPeriods ?? [] as $period)
                                <option value="{{ $period['value'] }}" @selected(($selectedPeriod ?? '') === $period['value'])>
                                    {{ $period['label'] }}
                                </option>
                            @endforeach
                        </select>

                        <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs">▼</span>
                    </div>
                </form>
            </div>

            @isset($rankingsLiveRoute)
                <div id="react-rankings-live" data-url="{{ $rankingsLiveRoute }}"></div>
            @endisset

            {{-- TOP 3 --}}
            @if ($topRankings->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                    @foreach ($topRankings as $top)
                        <div class="bg-white p-6 rounded-2xl border-2 {{ $top['color'] }} shadow-sm text-center">
                            <div class="text-3xl mb-2">{{ $top['icon'] }}</div>

                            <h3 class="text-xs font-black text-gray-800 uppercase mb-1">{{ $top['name'] }}</h3>

                            <p class="text-2xl font-black text-red-600 leading-none mb-3">
                                {{ $top['points'] }}
                                <span class="text-[10px] text-gray-400 uppercase">pts</span>
                            </p>

                            <div class="flex justify-center gap-1 flex-wrap">
                                @foreach ($top['badges'] as $badge)
                                    <span class="bg-gray-100 text-[8px] font-black uppercase px-2 py-1 rounded text-gray-500 border border-gray-200">
                                        {{ $badge }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- COMPLETE LEADERBOARD --}}
            <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-10 shadow-sm">
                <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Complete Leaderboard</h3>
                        <p class="text-xs text-gray-400 mt-1">
                            Ranking metrics are based on recorded activity for {{ $latestPeriod ?? 'the selected ranking period' }}.
                        </p>
                    </div>

                    <div class="w-full md:w-80">
                        <label for="leaderboardSearch" class="block text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">
                            Search Barangay
                        </label>

                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs">🔍</span>

                            <input type="text" id="leaderboardSearch" placeholder="Search barangay..." autocomplete="off"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 pl-9 pr-9 py-2.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-100 focus:border-red-300">

                            <button id="clearLeaderboardSearch" type="button"
                                class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 text-sm font-bold"
                                title="Clear search">
                                ×
                            </button>
                        </div>
                    </div>
                </div>

                <div id="leaderboardList" class="space-y-6">
                    @forelse ($leaderboard as $row)
                        <div class="leaderboard-row bg-gray-50/50 rounded-2xl p-5 border border-gray-100 flex flex-col md:flex-row items-center gap-6 group hover:border-red-200 transition"
                            data-barangay="{{ $row->name }}">

                            <div class="flex items-center gap-4 min-w-[200px]">
                                <div class="text-2xl">
                                    {{ $row->rank == 1 ? '🥇' : ($row->rank == 2 ? '🥈' : ($row->rank == 3 ? '🥉' : '🏅')) }}
                                </div>

                                <div class="text-left leading-tight">
                                    <h4 class="text-xs font-black text-gray-800 uppercase">{{ $row->name }}</h4>
                                    <p class="text-[10px] text-gray-400">{{ $row->points }} points</p>
                                </div>
                            </div>

                            <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-6 w-full">
                                @foreach ([
                                    ['label' => 'On-time Rate', 'val' => $row->on_time],
                                    ['label' => 'Documentation', 'val' => $row->completion],
                                    ['label' => 'Participation Score', 'val' => $row->engagement],
                                ] as $metric)
                                    <div>
                                        <div class="flex justify-between text-[8px] font-black uppercase text-gray-400 mb-1">
                                            <span>{{ $metric['label'] }}</span>
                                            <span>{{ $metric['val'] }}%</span>
                                        </div>

                                        <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-red-500 h-full" style="width: {{ max(0, min(100, $metric['val'])) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex items-center gap-2 min-w-[90px] justify-end">
                                <span class="text-xs px-2 py-0.5 rounded bg-green-100 text-green-600 font-black text-[9px]">
                                    #{{ $row->rank }}
                                </span>

                                @if ($row->trend === 'up')
                                    <span class="text-green-500 text-xs font-black" title="Rank increased">↑</span>
                                @elseif ($row->trend === 'down')
                                    <span class="text-red-500 text-xs font-black" title="Rank decreased">↓</span>
                                @elseif ($row->trend === 'same')
                                    <span class="text-gray-400 text-xs font-black" title="No change in rank">—</span>
                                @else
                                    <span class="bg-blue-100 text-blue-600 px-2 py-0.5 rounded text-[8px] font-black uppercase" title="No previous ranking record">
                                        New
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-400 italic">No rankings found.</p>
                    @endforelse
                </div>

                <div id="leaderboardEmpty" class="hidden py-10 text-center">
                    <div class="text-3xl mb-2">🔍</div>
                    <p class="text-sm font-bold text-gray-600">No barangay found</p>
                    <p class="text-xs text-gray-400 mt-1">Try another barangay name.</p>
                </div>

                @if ($leaderboard->isNotEmpty())
                    <div id="leaderboardPaginationWrapper" class="mt-6 pt-5 border-t border-gray-100">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <p id="leaderboardInfo" class="text-[10px] text-gray-400"></p>

                            <div class="flex items-center gap-2 flex-wrap">
                                <button id="leaderboardPrevious" type="button"
                                    class="px-3 py-2 rounded-lg border border-gray-200 bg-white text-[10px] font-black uppercase text-gray-600 hover:border-red-300 hover:text-red-500 disabled:opacity-40 disabled:cursor-not-allowed transition">
                                    Previous
                                </button>

                                <div id="leaderboardPages" class="flex items-center gap-1 flex-wrap"></div>

                                <button id="leaderboardNext" type="button"
                                    class="px-3 py-2 rounded-lg border border-gray-200 bg-white text-[10px] font-black uppercase text-gray-600 hover:border-red-300 hover:text-red-500 disabled:opacity-40 disabled:cursor-not-allowed transition">
                                    Next
                                </button>
                            </div>
                        </div>

                        <p class="text-[10px] text-gray-400 mt-4">
                            Participation Score is relative to the highest recorded participation points for {{ $latestPeriod ?? 'the selected ranking period' }}.
                        </p>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-10">
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <h3 class="text-sm font-black text-gray-800 mb-1">Points System</h3>
                    <p class="text-xs text-gray-400 mb-6">How points are currently earned and deducted</p>

                    <div class="space-y-4">
                        @foreach ($pointSystem ?? [] as $rule)
                            @php
                                $isPositive = ($rule['type'] ?? 'positive') === 'positive';
                                $badgeClass = $isPositive ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600';
                                $prefix = $rule['points'] > 0 ? '+' : '';
                            @endphp

                            <div class="flex justify-between items-center text-[11px]">
                                <span class="text-gray-700 font-medium">{{ $rule['label'] }}</span>
                                <span class="{{ $badgeClass }} px-2 py-1 rounded-lg font-black">
                                    {{ $prefix }}{{ $rule['points'] }} points
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-6">
                        Ranking Indicators
                    </h3>

                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <div class="bg-gray-50 w-10 h-10 rounded-full flex items-center justify-center shadow-inner">
                                🏆
                            </div>

                            <div>
                                <h4 class="text-xs font-black text-gray-800 uppercase leading-none">Selected Period Rank</h4>
                                <p class="text-[10px] text-gray-400 mt-1">
                                    Position is based on total recorded points for the selected ranking period.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="bg-gray-50 w-10 h-10 rounded-full flex items-center justify-center shadow-inner">
                                ↕
                            </div>

                            <div>
                                <h4 class="text-xs font-black text-gray-800 uppercase leading-none">Rank Movement</h4>
                                <p class="text-[10px] text-gray-400 mt-1">
                                    Compares the selected period's rank with the immediately previous month.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="bg-gray-50 w-10 h-10 rounded-full flex items-center justify-center shadow-inner">
                                📊
                            </div>

                            <div>
                                <h4 class="text-xs font-black text-gray-800 uppercase leading-none">Recorded Metrics</h4>
                                <p class="text-[10px] text-gray-400 mt-1">
                                    On-time, documentation, and participation values come from recorded ranking activity for the selected period.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
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
            ? 'min-w-[32px] px-3 py-2 rounded-lg bg-red-600 text-white text-[10px] font-black'
            : 'min-w-[32px] px-3 py-2 rounded-lg border border-gray-200 bg-white text-gray-600 text-[10px] font-black hover:border-red-300 hover:text-red-500 transition';

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