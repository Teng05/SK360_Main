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
    @include('partials.app.sidebar')

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        @include('partials.app.topbar')

        <div class="p-8 overflow-y-auto h-full bg-gray-50">
            <div class="mb-8">
                <span class="sk-eyebrow"><span class="sk-dot"></span>Calendar</span>
                <h1 class="text-3xl font-bold text-gray-800 uppercase">Event Calendar</h1>
                <p class="text-gray-500">View scheduled activities, programs, meetings, deadlines, and submission slots.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <div class="lg:col-span-3 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <div id="calendar"></div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                        <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Legend</h3>
                        <div class="sk-calendar-legend space-y-3">
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
                            @forelse($upcomingEvents as $event)
                                @php
                                    $isSlot=($event->source_type ?? '')==='slot';
                                    $startValue=\Carbon\Carbon::parse($event->start_datetime)->format($isSlot ? 'Y-m-d' : 'Y-m-d\TH:i');
                                    $endValue=\Carbon\Carbon::parse($event->end_datetime)->format($isSlot ? 'Y-m-d' : 'Y-m-d\TH:i');
                                @endphp

                                <button type="button"
                                    class="upcoming-event-btn w-full text-left border-l-4 border-red-500 pl-3 transition hover:bg-red-50 focus-visible:outline-red-600"
                                    data-source-type="{{ $event->source_type ?? 'event' }}"
                                    data-title="{{ $event->title }}"
                                    data-type-label="{{ $event->type_label }}"
                                    data-description="{{ $event->description ?? '' }}"
                                    data-location="{{ $event->location ?? '' }}"
                                    data-start="{{ $startValue }}"
                                    data-end="{{ $endValue }}"
                                    data-visibility-label="{{ $event->visibility_label ?? ($event->role ?? 'Both') }}"
                                >
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="text-[11px] font-black text-gray-800 uppercase leading-none">{{ $event->title }}</p>
                                            <p class="text-[9px] text-gray-400 mt-2">{{ \Carbon\Carbon::parse($event->start_datetime)->format($isSlot ? 'M d, Y' : 'M d, Y • h:i A') }}</p>
                                            <p class="text-[9px] text-gray-400 mt-1 truncate">{{ $event->location ?: 'No location provided' }}</p>
                                        </div>

                                        <span data-sk-event="{{ $event->event_type ?? 'submission_slot' }}" class="shrink-0 rounded-full px-2 py-1 text-[8px] font-bold uppercase {{ $event->type_badge ?? 'bg-gray-100 text-gray-600' }}">{{ $event->type_label }}</span>
                                    </div>
                                </button>
                            @empty
                                <div class="rounded-2xl border border-dashed border-gray-200 p-6 text-center">
                                    <p class="text-xs text-gray-400">No upcoming events yet.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="eventDetailsModal" class="fixed inset-0 bg-slate-950/45 backdrop-blur-sm hidden items-center justify-center z-[60] px-4">
    <div class="bg-white w-full max-w-xl rounded-[28px] shadow-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-red-600 to-red-500 px-6 py-5 text-white">
            <div class="flex items-start justify-between gap-5">
                <div>
                    <p id="detailType" class="text-[9px] font-black uppercase tracking-[0.18em] text-red-100">Calendar Event</p>
                    <h2 id="detailTitle" class="text-2xl font-black mt-1">Event Details</h2>
                </div>

                <button id="closeEventDetailsBtn" type="button" class="w-9 h-9 rounded-full bg-white/15 hover:bg-white/25 flex items-center justify-center text-xl">&times;</button>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="rounded-2xl bg-gray-50 border border-gray-100 p-4">
                    <p class="text-[9px] font-black uppercase tracking-widest text-gray-400">Start</p>
                    <p id="detailStart" class="text-sm font-bold text-gray-800 mt-1"></p>
                </div>

                <div class="rounded-2xl bg-gray-50 border border-gray-100 p-4">
                    <p class="text-[9px] font-black uppercase tracking-widest text-gray-400">End</p>
                    <p id="detailEnd" class="text-sm font-bold text-gray-800 mt-1"></p>
                </div>

                <div class="rounded-2xl bg-gray-50 border border-gray-100 p-4">
                    <p class="text-[9px] font-black uppercase tracking-widest text-gray-400">Location</p>
                    <p id="detailLocation" class="text-sm font-bold text-gray-800 mt-1"></p>
                </div>

                <div class="rounded-2xl bg-gray-50 border border-gray-100 p-4">
                    <p class="text-[9px] font-black uppercase tracking-widest text-gray-400">Audience</p>
                    <p id="detailVisibility" class="text-sm font-bold text-gray-800 mt-1"></p>
                </div>
            </div>

            <div class="mt-4 rounded-2xl border border-gray-100 p-4">
                <p class="text-[9px] font-black uppercase tracking-widest text-gray-400">Description</p>
                <p id="detailDescription" class="text-sm leading-relaxed text-gray-600 mt-2 whitespace-pre-line"></p>
            </div>

            <div id="detailSlotNotice" class="hidden mt-4 rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-xs text-indigo-700">
                This item is a submission slot from Module Management.
            </div>

            <div class="flex justify-end mt-6">
                <button id="detailsCloseButton" type="button" class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-black text-white hover:bg-red-700">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const notifBtn=document.getElementById('notifBtn');
const notifDropdown=document.getElementById('notifDropdown');
const userMenuBtn=document.getElementById('userMenuBtn');
const userDropdown=document.getElementById('userDropdown');

notifBtn?.addEventListener('click',e=>{
    e.stopPropagation();
    notifDropdown.classList.toggle('hidden');
    userDropdown.classList.add('hidden');
});

userMenuBtn?.addEventListener('click',e=>{
    e.stopPropagation();
    userDropdown.classList.toggle('hidden');
    notifDropdown.classList.add('hidden');
});

document.addEventListener('click',e=>{
    if(notifBtn && notifDropdown && !notifBtn.contains(e.target) && !notifDropdown.contains(e.target)){
        notifDropdown.classList.add('hidden');
    }

    if(userMenuBtn && userDropdown && !userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)){
        userDropdown.classList.add('hidden');
    }
});

const eventDetailsModal=document.getElementById('eventDetailsModal');
const closeEventDetailsBtn=document.getElementById('closeEventDetailsBtn');
const detailsCloseButton=document.getElementById('detailsCloseButton');
const detailType=document.getElementById('detailType');
const detailTitle=document.getElementById('detailTitle');
const detailStart=document.getElementById('detailStart');
const detailEnd=document.getElementById('detailEnd');
const detailLocation=document.getElementById('detailLocation');
const detailVisibility=document.getElementById('detailVisibility');
const detailDescription=document.getElementById('detailDescription');
const detailSlotNotice=document.getElementById('detailSlotNotice');

const formatDate=(value,isSlot=false)=>{
    if(!value)return 'Not provided';

    if(isSlot){
        const parts=String(value).slice(0,10).split('-');
        const date=new Date(Number(parts[0]),Number(parts[1])-1,Number(parts[2]));

        return date.toLocaleDateString('en-US',{
            month:'short',
            day:'numeric',
            year:'numeric'
        });
    }

    const date=new Date(value);

    return date.toLocaleString('en-US',{
        month:'short',
        day:'numeric',
        year:'numeric',
        hour:'numeric',
        minute:'2-digit'
    });
};

const openDetails=data=>{
    const isSlot=data.sourceType==='slot';

    detailType.textContent=data.typeLabel || 'Calendar Event';
    detailTitle.textContent=data.title || 'Untitled Event';
    detailStart.textContent=formatDate(data.start,isSlot);
    detailEnd.textContent=formatDate(data.end,isSlot);
    detailLocation.textContent=data.location || 'No location provided';
    detailVisibility.textContent=data.visibilityLabel || 'Not specified';
    detailDescription.textContent=data.description || 'No description provided.';
    detailSlotNotice.classList.toggle('hidden',!isSlot);

    eventDetailsModal.classList.remove('hidden');
    eventDetailsModal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
};

const closeDetails=()=>{
    eventDetailsModal.classList.add('hidden');
    eventDetailsModal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
};

closeEventDetailsBtn.addEventListener('click',closeDetails);
detailsCloseButton.addEventListener('click',closeDetails);

eventDetailsModal.addEventListener('click',e=>{
    if(e.target===eventDetailsModal)closeDetails();
});

document.addEventListener('keydown',e=>{
    if(e.key==='Escape' && !eventDetailsModal.classList.contains('hidden')){
        closeDetails();
    }
});

document.addEventListener('DOMContentLoaded',()=>{
    const calendar=new FullCalendar.Calendar(
        document.getElementById('calendar'),
        {
            initialView:'dayGridMonth',
            height:'auto',
            fixedWeekCount:false,
            dayMaxEventRows:3,
            headerToolbar:{
                left:'prev,next today',
                center:'title',
                right:''
            },
            events:@json($calendarEvents),
            eventClick:info=>{
                const props=info.event.extendedProps || {};

                openDetails({
                    sourceType:props.source_type || 'event',
                    typeLabel:props.type_label || 'Calendar Event',
                    title:info.event.title,
                    description:props.description || '',
                    location:props.location || '',
                    start:props.start_value || info.event.start,
                    end:props.end_value || info.event.end,
                    visibilityLabel:props.visibility_label || 'Not specified'
                });
            }
        }
    );

    calendar.render();
});

document.querySelectorAll('.upcoming-event-btn').forEach(button=>{
    button.addEventListener('click',()=>{
        openDetails({
            sourceType:button.dataset.sourceType,
            typeLabel:button.dataset.typeLabel,
            title:button.dataset.title,
            description:button.dataset.description,
            location:button.dataset.location,
            start:button.dataset.start,
            end:button.dataset.end,
            visibilityLabel:button.dataset.visibilityLabel
        });
    });
});
</script>
@endpush