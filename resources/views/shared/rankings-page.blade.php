{{-- File guide: Blade view template for resources/views/shared/rankings-page.blade.php. --}}
<div class="flex h-screen bg-gray-100">
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
        <div class="flex items-center gap-2 mb-3">
            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" class="w-7 h-7" alt="logo">
            <h2 class="text-base font-bold">SK 360&deg;</h2>
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
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800 uppercase tracking-tight">Gamified Rankings</h1>
                <p class="text-gray-500">Encouraging timely submissions and active participation{{ $latestPeriod ? ' for ' . $latestPeriod : '' }}</p>
            </div>

            @if ($topRankings->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                    @foreach ($topRankings as $top)
                        <div class="bg-white p-6 rounded-2xl border-2 {{ $top['color'] }} shadow-sm text-center">
                            <div class="text-3xl mb-2">{{ $top['icon'] }}</div>
                            <h3 class="text-xs font-black text-gray-800 uppercase mb-1">{{ $top['name'] }}</h3>
                            <p class="text-2xl font-black text-red-600 leading-none mb-3">{{ $top['points'] }} <span class="text-[10px] text-gray-400 uppercase">pts</span></p>
                            <div class="flex justify-center gap-1 flex-wrap">
                                @foreach ($top['badges'] as $badge)
                                    <span class="bg-gray-100 text-[8px] font-black uppercase px-2 py-1 rounded text-gray-500 border border-gray-200">{{ $badge }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-10 shadow-sm">
                <div class="mb-6">
                    <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Complete Leaderboard</h3>
                </div>

                <div class="space-y-6">
                    @forelse ($leaderboard as $row)
                        <div class="bg-gray-50/50 rounded-2xl p-5 border border-gray-100 flex flex-col md:flex-row items-center gap-6 group hover:border-red-200 transition">
                            <div class="flex items-center gap-4 min-w-[200px]">
                                <div class="text-2xl">{{ $row->rank == 1 ? '🥇' : ($row->rank == 2 ? '🥈' : ($row->rank == 3 ? '🥉' : '🏅')) }}</div>
                                <div class="text-left leading-tight">
                                    <h4 class="text-xs font-black text-gray-800 uppercase">{{ $row->name }}</h4>
                                    <p class="text-[10px] text-gray-400">{{ $row->points }} points</p>
                                </div>
                            </div>

                            <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-6 w-full">
                                @foreach ([
                                    ['label' => 'On-time Rate', 'val' => $row->on_time],
                                    ['label' => 'Completion', 'val' => $row->completion],
                                    ['label' => 'Engagement', 'val' => $row->engagement],
                                ] as $metric)
                                    <div>
                                        <div class="flex justify-between text-[8px] font-black uppercase text-gray-400 mb-1">
                                            <span>{{ $metric['label'] }}</span>
                                            <span>{{ $metric['val'] }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-red-500 h-full" style="width: {{ $metric['val'] }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="text-xs px-2 py-0.5 rounded bg-green-100 text-green-600 font-black text-[9px]">#{{ $row->rank }}</span>
                                <span>{{ $row->trend === 'up' ? '📈' : '📉' }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-400 italic">No rankings found.</p>
                    @endforelse
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-10">
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <h3 class="text-sm font-black text-gray-800 mb-1">Points System</h3>
                    <p class="text-xs text-gray-400 mb-6">How points are earned and deducted</p>
                    <div class="space-y-4">
                        @foreach ($pointSystem ?? [] as $rule)
                            @php
                                $isPositive = ($rule['type'] ?? 'positive') === 'positive';
                                $badgeClass = $isPositive ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600';
                                $prefix = $rule['points'] > 0 ? '+' : '';
                            @endphp
                            <div class="flex justify-between items-center text-[11px]">
                                <span class="text-gray-700 font-medium">{{ $rule['label'] }}</span>
                                <span class="{{ $badgeClass }} px-2 py-1 rounded-lg font-black">{{ $prefix }}{{ $rule['points'] }} points</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-6">Achievement Badges</h3>
                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <div class="bg-gray-50 w-10 h-10 rounded-full flex items-center justify-center shadow-inner">🏆</div>
                            <div>
                                <h4 class="text-xs font-black text-gray-800 uppercase leading-none">Top Performer</h4>
                                <p class="text-[10px] text-gray-400 mt-1">Highest overall score for the current ranking period</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="bg-gray-50 w-10 h-10 rounded-full flex items-center justify-center shadow-inner">⭐</div>
                            <div>
                                <h4 class="text-xs font-black text-gray-800 uppercase leading-none">Consistent Performer</h4>
                                <p class="text-[10px] text-gray-400 mt-1">Strong submission and participation metrics across the board</p>
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
    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');

    notifBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        notifDropdown.classList.toggle('hidden');
        userDropdown.classList.add('hidden');
    });

    userMenuBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        userDropdown.classList.toggle('hidden');
        notifDropdown.classList.add('hidden');
    });

    document.addEventListener('click', function (e) {
        if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
            notifDropdown.classList.add('hidden');
        }

        if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.classList.add('hidden');
        }
    });
</script>
@endpush
