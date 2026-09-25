{{-- File guide: Blade view template for resources/views/sk_pres/calendar.blade.php. --}}
@extends('layouts.app')

@section('title', 'Event Calendar')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

    <style>
        .fc .fc-toolbar-title {
            font-size: 1rem;
            font-weight: 600;
            color: #374151;
        }

        .fc .fc-button {
            background: #f3f4f6 !important;
            border: none !important;
            color: #6b7280 !important;
            box-shadow: none !important;
            padding: 0.35rem 0.65rem !important;
        }

        .fc .fc-button:hover {
            background: #e5e7eb !important;
        }

        .fc .fc-daygrid-day {
            background: #fff;
        }

        .fc .fc-daygrid-day-frame {
            min-height: 90px;
        }

        .fc-theme-standard td,
        .fc-theme-standard th,
        .fc-theme-standard .fc-scrollgrid {
            border-color: #e5e7eb;
        }

        .fc .fc-col-header-cell-cushion,
        .fc .fc-daygrid-day-number {
            color: #6b7280;
            font-size: 12px;
            text-decoration: none !important;
        }

        .fc-event {
            border: none !important;
            padding: 2px 6px !important;
            border-radius: 6px !important;
            font-size: 11px !important;
        }
    </style>
@endsection

@section('content')
<div class="flex h-screen bg-[#f1f5f9] overflow-hidden">
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
        <div class="flex items-center gap-3 mb-4">
    <img src="{{ asset('images/logo.png') }}" class="w-8 h-8 rounded-full object-cover"  alt="logo">
    <div class="leading-tight">
        <h2 class="text-lg font-extrabold tracking-wide">SK 360°</h2>
        <p class="text-[10px] opacity-80">Management System</p>
    </div>
</div>

        <div class="bg-red-500 rounded-lg p-2 flex items-center gap-2 mb-3 shadow text-xs">
            <div class="bg-yellow-400 text-red-600 p-1 rounded-full text-sm">👤</div>
            <div>
                <p class="font-semibold text-xs">SK President</p>
                <p class="text-xs opacity-80">Active Role</p>
            </div>
        </div>

        <nav class="space-y-1 text-xs">
            @foreach ($menuItems as $item)
                <a href="{{ $item['link'] }}" class="flex items-center gap-2 p-2 rounded-lg {{ $item['link'] === $currentUrl ? 'bg-red-500' : 'hover:bg-red-500 transition' }}">
                    <span class="{{ $item['link'] === $currentUrl ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded text-sm">{{ $item['icon'] }}</span>
                    <span class="{{ $item['link'] === $currentUrl ? 'text-yellow-300 font-semibold' : '' }} text-xs">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="flex-1 flex flex-col">
        <div class="bg-red-600 text-white px-6 py-3 flex justify-between items-center shadow">
            <input type="text" placeholder="Search" class="px-4 py-2 rounded-full text-black w-1/3 focus:outline-none">

            <div class="flex items-center gap-3 relative">
                <div class="relative">
                    <button id="notifBtn" type="button" class="text-xl hover:bg-red-500 p-2 rounded-lg transition">🔔</button>
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
                        <a href="{{ route('sk_pres.profile') }}" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-100 transition">
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

        <main class="flex-1 overflow-y-auto p-8 bg-[#f8fafc]">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h2 class="text-[38px] font-bold text-gray-900 leading-tight">Event Calendar</h2>
                    <p class="text-gray-500 mt-2 text-base">Schedule and coordinate SK events, meetings, and deadlines</p>
                </div>

                <button id="openEventModalBtn" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm">
                    ＋ Add Event
                </button>
            </div>

            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 mb-5 flex flex-wrap gap-6 text-sm text-gray-700">
                @foreach ($legendItems as [$color, $label])
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded {{ $color }} inline-block"></span>
                        <span>{{ $label }}</span>
                    </div>
                @endforeach
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-4 mb-5">
                <div id="calendar"></div>
            </div>

            <div class="space-y-3">
                @forelse ($upcomingEvents as $event)
                    <div class="flex items-center justify-between border border-gray-200 rounded-xl px-4 py-3">
                        <div class="flex items-start gap-3">
                            <span class="w-2.5 h-2.5 mt-2 rounded-full {{ $typeColors[$event->event_type] ?? 'bg-gray-500' }}"></span>
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $event->title }}</p>
                                <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($event->start_datetime)->format('M d, Y h:i A') }}</p>
                                @if(!empty($event->location))
                                    <p class="text-xs text-gray-400">{{ $event->location }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-gray-500">{{ $event->type_label ?? $event->event_type }}</span>

                            @if(isset($event->event_id))
                                <button type="button"
                                    class="edit-event-btn rounded-lg bg-red-50 px-3 py-1.5 text-xs font-bold text-red-600 hover:bg-red-100"
                                    data-update-url="{{ route('sk_pres.calendar.update',$event->event_id) }}"
                                    data-title="{{ $event->title }}"
                                    data-event-type="{{ $event->event_type }}"
                                    data-description="{{ $event->description }}"
                                    data-location="{{ $event->location }}"
                                    data-start="{{ \Carbon\Carbon::parse($event->start_datetime)->format('Y-m-d\\TH:i') }}"
                                    data-end="{{ \Carbon\Carbon::parse($event->end_datetime)->format('Y-m-d\\TH:i') }}"
                                    data-visibility="{{ $event->visibility }}">
                                    Edit
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-400 py-6">No upcoming events yet.</div>
                @endforelse
            </div>

            <div id="eventModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50 px-4">
                <div class="bg-white w-full max-w-2xl rounded-[24px] shadow-2xl p-8 relative">
                    <button id="closeEventModalBtn" class="absolute top-4 right-5 text-gray-500 hover:text-red-600 text-2xl font-bold">
                        &times;
                    </button>

                    <h2 id="eventModalTitle" class="text-3xl font-bold text-gray-900 mb-2">Add Event</h2>
                    <p id="eventModalDescription" class="text-gray-600 mb-6 text-base">Create a calendar event for the SK calendar</p>

                    <form id="eventForm" action="{{ route('sk_pres.calendar.store') }}" method="POST" class="space-y-5">
                        @csrf
                        <input id="eventFormMethod" type="hidden" name="_method" value="PUT" disabled>

                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Event Title</label>
                            <input id="eventTitle" type="text" name="event_title" value="{{ old('event_title') }}" class="w-full h-12 px-4 rounded-xl border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-red-400" required>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Event Type</label>
                            <select id="eventType" name="event_type" class="w-full h-12 px-4 rounded-xl border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-red-400">
                                <option value="meeting" @selected(old('event_type') === 'meeting')>Meeting</option>
                                <option value="deadline" @selected(old('event_type') === 'deadline')>Deadline</option>
                                <option value="program" @selected(old('event_type') === 'program')>Program</option>
                                <option value="other" @selected(old('event_type') === 'other')>Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Description</label>
                            <textarea id="eventDescription" name="description" rows="3" class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-red-400">{{ old('description') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Location</label>
                            <input id="eventLocation" type="text" name="location" value="{{ old('location') }}" class="w-full h-12 px-4 rounded-xl border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-red-400" placeholder="Venue or activity location">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-900 mb-2">Start Date & Time</label>
                                <input id="eventStart" type="datetime-local" name="start_datetime" value="{{ old('start_datetime') }}" class="w-full h-12 px-4 rounded-xl border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-red-400" required>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-900 mb-2">End Date & Time</label>
                                <input id="eventEnd" type="datetime-local" name="end_datetime" value="{{ old('end_datetime') }}" class="w-full h-12 px-4 rounded-xl border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-red-400">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Visibility</label>
                            <select id="eventVisibility" name="visibility" class="w-full h-12 px-4 rounded-xl border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-red-400">
                                <option value="public" @selected(old('visibility') === 'public')>All Users</option>
                                <option value="officials_only" @selected(old('visibility') === 'officials_only')>SK Chairman and SK Secretary</option>
                                <option value="chairman_only" @selected(old('visibility') === 'chairman_only')>SK Chairman Only</option>
                                <option value="secretary_only" @selected(old('visibility') === 'secretary_only')>SK Secretary Only</option>
                            </select>
                        </div>

                        <button id="eventSubmitButton" type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white text-lg font-bold py-3 rounded-2xl transition">
                            Save Event
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
@vite(['resources/js/app.js'])
<script>
    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');
    const openEventModalBtn = document.getElementById('openEventModalBtn');
    const closeEventModalBtn = document.getElementById('closeEventModalBtn');
    const eventModal = document.getElementById('eventModal');
    const eventForm = document.getElementById('eventForm');
    const eventFormMethod = document.getElementById('eventFormMethod');
    const eventModalTitle = document.getElementById('eventModalTitle');
    const eventModalDescription = document.getElementById('eventModalDescription');
    const eventSubmitButton = document.getElementById('eventSubmitButton');
    const eventTitle = document.getElementById('eventTitle');
    const eventType = document.getElementById('eventType');
    const eventDescription = document.getElementById('eventDescription');
    const eventLocation = document.getElementById('eventLocation');
    const eventStart = document.getElementById('eventStart');
    const eventEnd = document.getElementById('eventEnd');
    const eventVisibility = document.getElementById('eventVisibility');
    const createEventUrl = @js(route('sk_pres.calendar.store'));

    const toggleMenu = (btn, menu, other) => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            menu.classList.toggle('hidden');
            other.classList.add('hidden');
        });
    };

    toggleMenu(notifBtn, notifDropdown, userDropdown);
    toggleMenu(userMenuBtn, userDropdown, notifDropdown);

    document.addEventListener('click', e => {
        if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) notifDropdown.classList.add('hidden');
        if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) userDropdown.classList.add('hidden');
    });

    const showEventModal = () => {
        eventModal.classList.remove('hidden');
        eventModal.classList.add('flex');
    };

    const hideEventModal = () => {
        eventModal.classList.add('hidden');
        eventModal.classList.remove('flex');
    };

    const toDateTimeLocal = (value) => {
        if (!value) return '';

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return String(value).slice(0, 16);
        }

        const pad = (number) => String(number).padStart(2, '0');

        return [
            date.getFullYear(),
            pad(date.getMonth() + 1),
            pad(date.getDate())
        ].join('-') + 'T' + [
            pad(date.getHours()),
            pad(date.getMinutes())
        ].join(':');
    };

    const openCreateEventModal = () => {
        eventForm.reset();
        eventForm.action = createEventUrl;
        eventFormMethod.disabled = true;
        eventModalTitle.textContent = 'Add Event';
        eventModalDescription.textContent = 'Create a calendar event for the SK calendar';
        eventSubmitButton.textContent = 'Save Event';
        eventType.value = 'meeting';
        eventVisibility.value = 'public';
        showEventModal();
    };

    const openEditEventModal = (eventData) => {
        eventForm.reset();
        eventForm.action = eventData.updateUrl;
        eventFormMethod.disabled = false;
        eventModalTitle.textContent = 'Edit Event';
        eventModalDescription.textContent = 'Update the title, date/time, location, audience, or event setting.';
        eventSubmitButton.textContent = 'Save Changes';
        eventTitle.value = eventData.title || '';
        eventType.value = eventData.eventType || 'meeting';
        eventDescription.value = eventData.description || '';
        eventLocation.value = eventData.location || '';
        eventStart.value = toDateTimeLocal(eventData.start);
        eventEnd.value = toDateTimeLocal(eventData.end);
        eventVisibility.value = eventData.visibility || 'public';
        showEventModal();
    };

    openEventModalBtn.addEventListener('click', openCreateEventModal);
    closeEventModalBtn.addEventListener('click', hideEventModal);

    eventModal.addEventListener('click', e => {
        if (e.target === eventModal) hideEventModal();
    });

    document.addEventListener('DOMContentLoaded', () => {
        const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
            initialView: 'dayGridMonth',
            height: 'auto',
            headerToolbar: {
                left: 'title',
                center: '',
                right: 'prev,next'
            },
            events: @json($calendarEvents),
            eventClick: (info) => {
                if (!info.event.extendedProps.editable) {
                    return;
                }

                openEditEventModal({
                    updateUrl: @js(url('/sk_pres/calendar')) + '/' + info.event.id,
                    title: info.event.title,
                    eventType: info.event.extendedProps.event_type,
                    description: info.event.extendedProps.description,
                    location: info.event.extendedProps.location,
                    start: info.event.start,
                    end: info.event.end,
                    visibility: info.event.extendedProps.visibility,
                });
            },
        });

        calendar.render();
    });

    document.querySelectorAll('.edit-event-btn').forEach(button => {
        button.addEventListener('click', () => {
            openEditEventModal({
                updateUrl: button.dataset.updateUrl,
                title: button.dataset.title,
                eventType: button.dataset.eventType,
                description: button.dataset.description,
                location: button.dataset.location,
                start: button.dataset.start,
                end: button.dataset.end,
                visibility: button.dataset.visibility,
            });
        });
    });

    @if ($errors->any())
        showEventModal();
    @endif
</script>
@endpush
