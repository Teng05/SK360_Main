<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Calendar | SK 360&deg;</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <style>
        .fc .fc-toolbar-title { font-size: 1.1rem; font-weight: 700; color: #1f2937; text-transform: uppercase; }
        .fc .fc-button { background: #ef4444 !important; border: none !important; color: #fff !important; font-size: 0.8rem !important; text-transform: uppercase; font-weight: bold; }
        .fc .fc-button:hover { background: #dc2626 !important; }
        .fc-event { border: none !important; padding: 3px 5px !important; border-radius: 4px !important; font-size: 10px !important; cursor: pointer; }
        .fc .fc-daygrid-day-number { color: #6b7280; font-size: 12px; text-decoration: none !important; }
    </style>
</head>

<body class="bg-gray-100 overflow-hidden">
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
                    <button class="hover:opacity-80">&#128276;</button>

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

            <div class="p-8 overflow-y-auto h-full bg-gray-50">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-800 uppercase">Event Calendar</h1>
                    <p class="text-gray-500">Official schedule of activities and programs</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <div class="lg:col-span-3 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <div id="calendar"></div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Legend</h3>
                            <div class="space-y-3">
                                @foreach ($legendItems as [$color, $label])
                                    <div class="flex items-center gap-3">
                                        <span class="w-3 h-3 rounded-full {{ $color }}"></span>
                                        <span class="text-xs font-bold text-gray-600">{{ $label }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Upcoming Agendas</h3>
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
                                    <p class="text-xs text-gray-400 italic">No scheduled events.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const dropdownBtn = document.getElementById('profileDropdownBtn');
        const profileMenu = document.getElementById('profileMenu');

        dropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            profileMenu.classList.toggle('hidden');
        });

        window.addEventListener('click', (e) => {
            if (!profileMenu.contains(e.target) && !dropdownBtn.contains(e.target)) {
                profileMenu.classList.add('hidden');
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                height: 'auto',
                headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
                events: @json($calendarEvents),
                eventClick: (info) => {
                    alert('Event: ' + info.event.title);
                }
            });

            calendar.render();
        });
    </script>
</body>
</html>
