{{-- File guide: Blade view template for resources/views/sk_secretary/calendar.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK Secretary Calendar')

@section('page_css')
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
@endsection

@section('content')
<div class="flex h-screen bg-gray-100 overflow-hidden">
    {{-- Shared secretary sidebar/topbar layout --}}
    @include('sk_secretary.partials.sidebar')

    <div class="flex-1 flex flex-col overflow-hidden">
        @include('sk_secretary.partials.topbar')

        {{-- Calendar content --}}
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
@endsection

@push('scripts')
@include('sk_secretary.partials.dropdown-scripts')
<script>
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
@endpush
