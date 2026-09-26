{{-- File guide: Blade view template for resources/views/sk_chairman/calendar.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK Chairman Calendar')

@section('page_css')
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<style>
.fc .fc-toolbar{gap:12px;margin-bottom:18px}
.fc .fc-toolbar-title{font-size:1.05rem;font-weight:800;color:#111827}
.fc .fc-button{background:#fff!important;border:1px solid #e5e7eb!important;color:#4b5563!important;box-shadow:none!important;border-radius:10px!important;padding:.45rem .75rem!important;font-size:.72rem!important;font-weight:800!important}
.fc .fc-button:hover{background:#fef2f2!important;border-color:#fecaca!important;color:#dc2626!important}
.fc .fc-col-header-cell{background:#f8fafc}
.fc .fc-col-header-cell-cushion{padding:10px 4px!important;color:#6b7280;font-size:10px;font-weight:900;text-transform:uppercase;text-decoration:none!important}
.fc .fc-daygrid-day-frame{min-height:112px}
.fc .fc-daygrid-day-number{color:#6b7280;font-size:11px;font-weight:700;text-decoration:none!important;padding:8px!important}
.fc .fc-day-today{background:#fff7f7!important}
.fc-event{border:none!important;padding:3px 6px!important;border-radius:7px!important;font-size:10px!important;font-weight:700!important;cursor:pointer!important}
</style>
@endsection

@section('content')
<div class="flex h-screen bg-gray-100 overflow-hidden">
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
        <div class="flex items-center gap-3 mb-4">
            <img src="{{ asset('images/logo.png') }}" class="w-8 h-8 rounded-full object-cover" alt="logo">
            <div class="leading-tight">
                <h2 class="text-lg font-extrabold tracking-wide">SK 360°</h2>
                <p class="text-[10px] opacity-80">Management System</p>
            </div>
        </div>

        <div class="bg-red-500 rounded-lg p-2 flex items-center gap-2 mb-3 shadow text-xs">
            <div class="bg-yellow-400 text-red-600 p-1 rounded-full text-sm">&#128100;</div>
            <div>
                <p class="font-semibold text-xs">SK Chairman</p>
                <p class="text-xs opacity-80">Active Role</p>
            </div>
        </div>

        <nav class="space-y-1 text-xs">
            @foreach($menuItems as $item)
                @php $isActive=$item['link']===$currentUrl; @endphp

                <a href="{{ $item['link'] }}" class="flex items-center gap-2 p-2 rounded-lg {{ $isActive ? 'bg-red-500' : 'hover:bg-red-500 transition' }}">
                    <span class="{{ $isActive ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded text-sm">{!! $item['icon'] !!}</span>
                    <span class="{{ $isActive ? 'text-yellow-300 font-semibold' : '' }} text-xs">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="flex-1 flex flex-col overflow-hidden">
        <div class="bg-red-600 text-white px-6 py-3 flex justify-between items-center shadow">
            <input type="text" placeholder="Search..." class="px-4 py-2 rounded-full text-black w-1/3 focus:outline-none">

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

                        <a href="{{ route('sk_chairman.profile') }}" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-100 transition">
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

        <main class="p-8 overflow-y-auto h-full bg-[#f8fafc]">
            <div class="mb-7">
                <div class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-red-600 mb-3">📅 Official Schedule</div>
                <h1 class="text-3xl font-black text-gray-900">Event Calendar</h1>
                <p class="text-sm text-gray-500 mt-2">View scheduled activities, programs, meetings, deadlines, and submission slots.</p>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_320px] gap-6">
                <section class="bg-white rounded-[24px] border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <div>
                                <h2 class="text-sm font-black text-gray-900">Calendar Schedule</h2>
                                <p class="text-xs text-gray-400 mt-1">Click any item to view its complete available details.</p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @foreach($legendItems as [$color,$label])
                                    <div class="flex items-center gap-2 rounded-full border border-gray-100 bg-gray-50 px-3 py-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full {{ $color }}"></span>
                                        <span class="text-[9px] font-bold text-gray-600">{{ $label }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="p-5 md:p-6">
                        <div id="calendar"></div>
                    </div>
                </section>

                <aside class="space-y-5">
                    <div class="rounded-[24px] bg-gradient-to-br from-red-600 to-red-500 p-5 text-white shadow-sm">
                        <p class="text-[9px] font-black uppercase tracking-[0.2em] text-red-100">Calendar Guide</p>
                        <h3 class="text-lg font-black mt-2">Stay updated</h3>
                        <p class="text-xs leading-relaxed text-red-100 mt-2">Click any calendar item to view its details. Events are shown according to your allowed audience.</p>
                    </div>

                    <div class="bg-white p-5 rounded-[24px] border border-gray-100 shadow-sm">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <div>
                                <h3 class="text-xs font-black text-gray-900">Upcoming</h3>
                                <p class="text-[10px] text-gray-400 mt-1">Nearest calendar items</p>
                            </div>

                            <span class="rounded-full bg-red-50 px-2.5 py-1 text-[9px] font-black text-red-600">{{ $upcomingEvents->count() }}</span>
                        </div>

                        <div class="space-y-3">
                            @forelse($upcomingEvents as $event)
                                @php
                                    $isSlot=($event->source_type ?? '')==='slot';
                                    $startValue=\Carbon\Carbon::parse($event->start_datetime)->format($isSlot ? 'Y-m-d' : 'Y-m-d\TH:i');
                                    $endValue=\Carbon\Carbon::parse($event->end_datetime)->format($isSlot ? 'Y-m-d' : 'Y-m-d\TH:i');
                                @endphp

                                <button type="button"
                                    class="upcoming-event-btn w-full text-left rounded-2xl border border-gray-100 bg-gray-50/70 p-4 hover:border-red-200 hover:bg-red-50/40 transition"
                                    data-source-type="{{ $event->source_type ?? 'event' }}"
                                    data-title="{{ $event->title }}"
                                    data-type-label="{{ $event->type_label }}"
                                    data-description="{{ $event->description ?? '' }}"
                                    data-location="{{ $event->location ?? '' }}"
                                    data-start="{{ $startValue }}"
                                    data-end="{{ $endValue }}"
                                    data-visibility-label="{{ $event->visibility_label ?? ($event->role ?? 'Both') }}"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-[11px] font-black text-gray-800">{{ $event->title }}</p>
                                            <p class="text-[9px] text-gray-400 mt-2">{{ \Carbon\Carbon::parse($event->start_datetime)->format($isSlot ? 'M d, Y' : 'M d, Y • h:i A') }}</p>
                                            <p class="text-[9px] text-gray-400 mt-1 truncate">{{ $event->location ?: 'No location provided' }}</p>
                                        </div>

                                        <span class="shrink-0 rounded-full px-2 py-1 text-[8px] font-bold uppercase {{ $event->type_badge ?? 'bg-gray-100 text-gray-600' }}">{{ $event->type_label }}</span>
                                    </div>
                                </button>
                            @empty
                                <div class="rounded-2xl border border-dashed border-gray-200 p-6 text-center">
                                    <p class="text-xs text-gray-400">No upcoming events yet.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </aside>
            </div>
        </main>
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