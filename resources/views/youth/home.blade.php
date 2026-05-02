{{-- File guide: Blade view template for resources/views/youth/home.blade.php. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Youth Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <div class="w-64 bg-red-600 text-white flex flex-col p-3">
            <div class="flex items-center gap-2 mb-3">
                <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" class="w-7 h-7" alt="logo">
                <h2 class="text-base font-bold text-white">SK 360&deg;</h2>
            </div>

            <div class="bg-red-500 rounded-lg p-2 flex items-center gap-2 mb-3 shadow text-xs">
                <div class="bg-yellow-400 text-red-600 p-1 rounded-full font-bold">&#128100;</div>
                <div>
                    <p class="font-semibold">{{ $userName }}</p>
                    <p class="opacity-80 text-[10px]">Youth Member</p>
                </div>
            </div>

            <nav class="space-y-1 text-xs">
                @foreach ($menuItems as $item)
                    @php $isActive = $currentUrl === $item['link']; @endphp
                    <a href="{{ $item['link'] }}" class="flex items-center gap-2 p-2 rounded-lg {{ $isActive ? 'bg-red-500' : 'hover:bg-red-500' }}">
                        <span class="{{ $isActive ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded">{{ $item['icon'] }}</span>
                        <span class="{{ $isActive ? 'text-yellow-300 font-semibold' : '' }}">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="flex-1 flex flex-col">
            <div class="bg-red-600 text-white px-6 py-3 flex justify-between items-center shadow relative">
                <div class="w-1/4"></div>
                <div class="w-1/3">
                    <input type="text" placeholder="Search..." class="w-full px-4 py-2 rounded-full text-black focus:outline-none text-sm">
                </div>

                <div class="w-1/4 flex justify-end items-center gap-5 text-sm">
                    <div class="relative">
                        <button id="notifBtn" type="button" class="hover:opacity-80 relative">&#128276;</button>
                        <div id="notifDropdown" class="hidden absolute right-0 mt-3 w-72 bg-white rounded-2xl shadow-xl border z-[9999] overflow-hidden">
                            <div class="px-4 py-3 font-semibold border-b text-gray-800">Notifications</div>
                            <div class="max-h-64 overflow-y-auto">
                                <div class="px-4 py-3 text-sm text-gray-700">No notifications yet</div>
                            </div>
                        </div>
                    </div>

                    <div class="relative">
                        <button id="profileDropdownBtn" class="flex items-center gap-2 font-semibold focus:outline-none hover:opacity-80 transition">
                            <span>{{ $userName }}</span>
                            <span class="text-[10px]">&#9660;</span>
                        </button>

                        <div id="profileMenu" class="absolute right-0 mt-3 w-48 bg-white rounded-xl shadow-2xl py-2 z-[9999] hidden border border-gray-100">
                            <div class="px-4 py-3 border-b border-gray-50">
                                <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest">Account Settings</p>
                            </div>
                            <a href="{{ route('youth.profile') }}" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 text-xs flex items-center gap-2 transition">
                                <span>&#128100;</span> View Profile
                            </a>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-3 text-red-600 hover:bg-red-50 text-xs font-bold flex items-center gap-2 transition">
                                    <span>&#128682;</span> Log Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6 overflow-y-auto">
                <h1 class="text-2xl font-bold mb-4 uppercase tracking-tight text-gray-800">Lipa Youth Dashboard</h1>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-blue-500 text-white p-5 rounded-xl shadow border-b-4 border-blue-700 text-center">
                        <h2 class="text-2xl font-bold">{{ $participationRate }}%</h2>
                        <p class="text-[10px] uppercase font-semibold opacity-90">Participation Rate</p>
                    </div>
                    <div class="bg-yellow-500 text-white p-5 rounded-xl shadow border-b-4 border-yellow-700 text-center">
                        <h2 class="text-2xl font-bold">{{ $eventsJoined }}</h2>
                        <p class="text-[10px] uppercase font-semibold opacity-90">Events Joined</p>
                    </div>
                    <div class="bg-green-500 text-white p-5 rounded-xl shadow border-b-4 border-green-700 text-center">
                        <h2 class="text-2xl font-bold">{{ $barangayRank ? '#'.$barangayRank : 'N/A' }}</h2>
                        <p class="text-[10px] uppercase font-semibold opacity-90">Your Barangay's Rank</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-4">
                        @include('shared.wall-feed')

                        <div class="flex justify-between items-center">
                            <h2 class="font-bold text-gray-700 uppercase text-xs tracking-wider">Activity Feed</h2>
                            <div class="flex gap-1 bg-gray-200 p-1 rounded-lg text-[9px] font-bold">
                                <button onclick="filterFeed('all')" id="tab-all" class="feed-tab bg-white px-3 py-1.5 rounded shadow text-red-600">ALL</button>
                                <button onclick="filterFeed('announcement')" id="tab-announcement" class="feed-tab px-3 py-1.5 rounded text-gray-500 hover:bg-gray-300">ANNOUNCEMENTS</button>
                                <button onclick="filterFeed('event')" id="tab-event" class="feed-tab px-3 py-1.5 rounded text-gray-500 hover:bg-gray-300">EVENTS</button>
                            </div>
                        </div>

                        <div id="activity-container" class="space-y-4">
                            <div class="feed-item" data-category="announcement">
                                @if ($latestAnnouncement)
                                    <div class="bg-white rounded-xl shadow overflow-hidden border">
                                        <div class="p-4">
                                            <span class="bg-blue-100 text-blue-600 text-[8px] font-bold px-2 py-0.5 rounded uppercase">Latest Announcement</span>
                                            <h3 class="font-bold text-lg mt-1">{{ $latestAnnouncement->title }}</h3>
                                            <p class="text-gray-500 text-xs mt-1 leading-relaxed">
                                                {{ \Illuminate\Support\Str::limit(strip_tags($latestAnnouncement->content), 150) }}
                                            </p>
                                        </div>
                                    </div>
                                @else
                                    <div class="bg-white rounded-xl shadow border p-4 text-xs text-gray-500">No announcements available.</div>
                                @endif
                            </div>

                            @forelse ($upcomingEvents as $event)
                                <div class="feed-item" data-category="event">
                                    <div class="bg-white p-4 rounded-xl shadow border flex justify-between items-center">
                                        <div class="flex items-center gap-3">
                                            <div class="bg-red-100 text-red-600 p-2 rounded text-center font-bold text-xs min-w-[42px]">
                                                {{ \Carbon\Carbon::parse($event->start_datetime)->format('d') }}<br>
                                                <span class="text-[8px] uppercase">{{ \Carbon\Carbon::parse($event->start_datetime)->format('M') }}</span>
                                            </div>
                                            <div>
                                                <p class="text-[11px] font-bold text-gray-800">{{ $event->title }}</p>
                                                <p class="text-[9px] text-gray-400">&#128205; {{ $event->location ?: 'No Location' }}</p>
                                            </div>
                                        </div>
                                        <a href="#" class="text-red-600 font-bold text-[9px] border border-red-600 px-3 py-1 rounded hover:bg-red-600 hover:text-white transition uppercase">View</a>
                                    </div>
                                </div>
                            @empty
                                <div class="bg-white rounded-xl shadow border p-4 text-xs text-gray-500">No upcoming events available.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white p-4 rounded-xl shadow border">
                            <h2 class="font-bold text-gray-700 uppercase text-[10px] mb-3 tracking-widest border-b pb-2">Quick Actions</h2>
                            <div class="grid grid-cols-3 gap-2">
                                <a href="#" class="flex flex-col items-center justify-center p-3 bg-blue-600 rounded-lg hover:bg-blue-700 transition aspect-square">
                                    <span class="text-white text-xl">&#128197;</span>
                                    <span class="text-[8px] font-bold mt-1 text-white text-center">JOIN EVENT</span>
                                </a>
                                <a href="#" class="flex flex-col items-center justify-center p-3 bg-green-600 rounded-lg hover:bg-green-700 transition aspect-square">
                                    <span class="text-white text-xl">&#127942;</span>
                                    <span class="text-[8px] font-bold mt-1 text-white text-center">RANKINGS</span>
                                </a>
                                <a href="#" class="flex flex-col items-center justify-center p-3 bg-indigo-600 rounded-lg hover:bg-indigo-700 transition aspect-square">
                                    <span class="text-white text-xl">&#128100;</span>
                                    <span class="text-[8px] font-bold mt-1 text-white text-center">PROFILE</span>
                                </a>
                            </div>
                        </div>

                        <div class="bg-white p-4 rounded-xl shadow border">
                            <h2 class="font-bold text-gray-700 uppercase text-[10px] mb-3 tracking-widest border-b pb-2">Calendar Preview</h2>
                            <div class="space-y-4">
                                @forelse ($upcomingEvents as $event)
                                    <div class="border-l-4 border-red-500 pl-3">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-[11px] font-black text-gray-800 uppercase leading-none">{{ $event->title }}</p>
                                            <span class="rounded-full px-2 py-1 text-[8px] font-bold uppercase {{ $event->type_badge }}">
                                                {{ $event->type_label }}
                                            </span>
                                        </div>
                                        <p class="text-[9px] text-gray-500 mt-2">
                                            {{ \Carbon\Carbon::parse($event->start_datetime)->format('M d, Y • h:i A') }}
                                        </p>
                                        <p class="text-[9px] text-gray-400 mt-1">
                                            {{ $event->location ?: 'No location provided' }}
                                        </p>
                                    </div>
                                @empty
                                    <p class="text-[10px] text-gray-400 italic">No scheduled events.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const notifBtn = document.getElementById('notifBtn');
        const notifDropdown = document.getElementById('notifDropdown');
        const dropdownBtn = document.getElementById('profileDropdownBtn');
        const profileMenu = document.getElementById('profileMenu');

        notifBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            notifDropdown.classList.toggle('hidden');
            profileMenu.classList.add('hidden');
        });

        dropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            profileMenu.classList.toggle('hidden');
            notifDropdown.classList.add('hidden');
        });

        window.addEventListener('click', (e) => {
            if (!notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
                notifDropdown.classList.add('hidden');
            }

            if (!profileMenu.contains(e.target) && !dropdownBtn.contains(e.target)) {
                profileMenu.classList.add('hidden');
            }
        });

        function filterFeed(category) {
            const items = document.querySelectorAll('.feed-item');
            const tabs = document.querySelectorAll('.feed-tab');

            tabs.forEach((tab) => {
                tab.classList.remove('bg-white', 'shadow', 'text-red-600');
                tab.classList.add('text-gray-500', 'hover:bg-gray-300');
            });

            const activeTab = document.getElementById('tab-' + category);
            activeTab.classList.add('bg-white', 'shadow', 'text-red-600');
            activeTab.classList.remove('text-gray-500', 'hover:bg-gray-300');

            items.forEach((item) => {
                item.style.display = category === 'all' || item.getAttribute('data-category') === category ? 'block' : 'none';
            });
        }

        (function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
            const feedUrl = @json(route('notifications.feed'));
            const readBaseUrl = @json(url('/notifications'));

            let badge = notifBtn.querySelector('[data-notification-badge]');
            if (!badge) {
                badge = document.createElement('span');
                badge.setAttribute('data-notification-badge', 'true');
                badge.className = 'hidden absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-yellow-400 text-red-700 text-[10px] font-bold flex items-center justify-center';
                notifBtn.appendChild(badge);
            }

            function render(payload) {
                const unreadCount = payload.unread_count || 0;
                const notifications = payload.notifications || [];

                badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
                badge.classList.toggle('hidden', unreadCount === 0);

                const body = notifications.length === 0
                    ? '<div class="px-4 py-3 text-sm text-gray-700">No notifications yet</div>'
                    : notifications.map((notification) => `
                        <a href="${notification.url || '#'}" data-notification-link data-id="${notification.id}" class="block px-4 py-3 border-b border-gray-100 ${notification.is_read ? 'bg-white' : 'bg-red-50'}">
                            <div class="text-sm font-semibold text-gray-800">${notification.title}</div>
                            <div class="mt-1 text-xs text-gray-600">${notification.message}</div>
                            <div class="mt-1 text-[11px] text-gray-400">${notification.created_at || ''}</div>
                        </a>
                    `).join('');

                notifDropdown.innerHTML = `
                    <div class="px-4 py-3 font-semibold border-b text-gray-800">Notifications</div>
                    <div class="max-h-64 overflow-y-auto">${body}</div>
                `;
            }

            async function fetchFeed() {
                const response = await fetch(feedUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    return;
                }

                render(await response.json());
            }

            notifDropdown.addEventListener('click', async (event) => {
                const link = event.target.closest('[data-notification-link]');
                if (!link) return;

                await fetch(`${readBaseUrl}/${link.getAttribute('data-id')}/read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
            });

            fetchFeed();
            setInterval(fetchFeed, 5000);
        })();
    </script>
</body>
</html>
