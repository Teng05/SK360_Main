{{-- File guide: Blade view template for resources/views/sk_pres/calendar.blade.php. --}}
@extends('layouts.app')

@section('title', 'Event Calendar')

@section('page_css')
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<style>
.fc .fc-toolbar{gap:12px;margin-bottom:18px}
.fc .fc-toolbar-title{font-size:1.05rem;font-weight:800;color:#111827}
.fc .fc-button{background:#fff!important;border:1px solid #e5e7eb!important;color:#4b5563!important;box-shadow:none!important;border-radius:10px!important;padding:.45rem .75rem!important;font-size:.72rem!important;font-weight:800!important;text-transform:capitalize!important}
.fc .fc-button:hover{background:#fef2f2!important;border-color:#fecaca!important;color:#dc2626!important}
.fc .fc-col-header-cell{background:#f8fafc}
.fc .fc-col-header-cell-cushion{padding:10px 4px!important;color:#6b7280;font-size:10px;font-weight:900;text-transform:uppercase;text-decoration:none!important}
.fc .fc-daygrid-day{background:#fff}
.fc .fc-daygrid-day-frame{min-height:112px}
.fc .fc-daygrid-day-number{color:#6b7280;font-size:11px;font-weight:700;text-decoration:none!important;padding:8px!important}
.fc .fc-day-today{background:#fff7f7!important}
.fc .fc-day-today .fc-daygrid-day-number{background:#dc2626;color:#fff;border-radius:999px;width:26px;height:26px;display:flex;align-items:center;justify-content:center;margin:5px}
.fc-theme-standard td,.fc-theme-standard th,.fc-theme-standard .fc-scrollgrid{border-color:#eef2f7}
.fc-event{border:none!important;padding:3px 6px!important;border-radius:7px!important;font-size:10px!important;font-weight:700!important;cursor:pointer!important;box-shadow:0 1px 2px rgb(15 23 42/.08)}
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
            <div class="bg-yellow-400 text-red-600 p-1 rounded-full text-sm">👤</div>
            <div>
                <p class="font-semibold text-xs">SK President</p>
                <p class="text-xs opacity-80">Active Role</p>
            </div>
        </div>

        <nav class="space-y-1 text-xs">
            @foreach($menuItems as $item)
                <a href="{{ $item['link'] }}" class="flex items-center gap-2 p-2 rounded-lg {{ $item['link']===$currentUrl ? 'bg-red-500' : 'hover:bg-red-500 transition' }}">
                    <span class="{{ $item['link']===$currentUrl ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded text-sm">{{ $item['icon'] }}</span>
                    <span class="{{ $item['link']===$currentUrl ? 'text-yellow-300 font-semibold' : '' }} text-xs">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="flex-1 flex flex-col overflow-hidden">
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
            <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-5 mb-7">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-red-600 mb-3">📅 Schedule Management</div>
                    <h1 class="text-3xl md:text-4xl font-black text-gray-900 tracking-tight">Event Calendar</h1>
                    <p class="text-gray-500 mt-2 text-sm">Create, review, and update SK events, meetings, programs, and deadlines.</p>
                </div>

                <button id="openEventModalBtn" type="button" class="inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white px-5 py-3 rounded-xl text-sm font-black shadow-sm transition">
                    <span class="text-lg leading-none">＋</span>
                    Add Event
                </button>
            </div>

            @if(session('status'))
                <div class="mb-5 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-700">{{ session('status') }}</div>
            @endif

            @if(session('warning'))
                <div class="mb-5 rounded-2xl border border-yellow-200 bg-yellow-50 px-5 py-4 text-sm font-semibold text-yellow-700">{{ session('warning') }}</div>
            @endif

            @if($errors->any())
                <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
            @endif

            <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_320px] gap-6">
                <section class="bg-white rounded-[24px] border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <div>
                                <h2 class="text-sm font-black text-gray-900">Calendar Schedule</h2>
                                <p class="text-xs text-gray-400 mt-1">Click any calendar item to view its full details.</p>
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
                        <p class="text-[9px] font-black uppercase tracking-[0.2em] text-red-100">Quick Guide</p>
                        <h3 class="text-lg font-black mt-2">Manage the schedule</h3>
                        <p class="text-xs leading-relaxed text-red-100 mt-2">Click an event to view its details first. Regular calendar events can be edited. Submission slots are managed from Module Management.</p>
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
                                    data-event-id="{{ $event->event_id ?? ('slot-'.$event->slot_id) }}"
                                    data-source-type="{{ $event->source_type ?? 'event' }}"
                                    data-editable="{{ isset($event->event_id) ? '1' : '0' }}"
                                    data-title="{{ $event->title }}"
                                    data-event-type="{{ $event->event_type }}"
                                    data-type-label="{{ $event->type_label }}"
                                    data-description="{{ $event->description ?? '' }}"
                                    data-location="{{ $event->location ?? '' }}"
                                    data-start="{{ $startValue }}"
                                    data-end="{{ $endValue }}"
                                    data-visibility="{{ $event->visibility ?? ($event->role ?? '') }}"
                                    data-visibility-label="{{ $event->visibility_label ?? ($event->role ?? 'Both') }}"
                                    @if(isset($event->event_id))
                                        data-update-url="{{ route('sk_pres.calendar.update',$event->event_id) }}"
                                    @endif
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-[11px] font-black text-gray-800 leading-snug">{{ $event->title }}</p>
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
                    <h2 id="detailTitle" class="text-2xl font-black mt-1 leading-tight">Event Details</h2>
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
                This item is a submission slot. Manage its settings from Module Management.
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button id="detailsCloseButton" type="button" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-50">Close</button>
                <button id="detailsEditButton" type="button" class="hidden rounded-xl bg-red-600 px-4 py-2.5 text-sm font-black text-white hover:bg-red-700">Edit Event</button>
            </div>
        </div>
    </div>
</div>

<div id="eventModal" class="fixed inset-0 bg-slate-950/45 backdrop-blur-sm hidden items-center justify-center z-[70] px-4 py-6">
    <div class="bg-white w-full max-w-2xl max-h-[92vh] overflow-y-auto rounded-[28px] shadow-2xl relative">
        <div class="sticky top-0 bg-white border-b border-gray-100 px-7 py-5 rounded-t-[28px] z-10">
            <button id="closeEventModalBtn" type="button" class="absolute top-4 right-5 w-9 h-9 rounded-full bg-gray-100 hover:bg-red-50 hover:text-red-600 text-gray-500 text-xl font-bold">&times;</button>

            <p class="text-[9px] font-black uppercase tracking-[0.18em] text-red-500">Calendar Management</p>
            <h2 id="eventModalTitle" class="text-2xl font-black text-gray-900 mt-1">Add Event</h2>
            <p id="eventModalDescription" class="text-gray-500 mt-1 text-sm">Create a calendar event for the SK calendar.</p>
        </div>

        <form id="eventForm" action="{{ route('sk_pres.calendar.store') }}" method="POST" class="p-7 space-y-5">
            @csrf
            <input id="eventFormMethod" type="hidden" name="_method" value="PUT" disabled>
            <input id="editingEventId" type="hidden" name="editing_event_id" value="{{ old('editing_event_id') }}">

            <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">Event Title</label>
                <input id="eventTitle" type="text" name="event_title" value="{{ old('event_title') }}" maxlength="255" class="w-full h-12 px-4 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-100 focus:border-red-400" required>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">Event Type</label>
                    <select id="eventType" name="event_type" class="w-full h-12 px-4 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-100 focus:border-red-400" required>
                        <option value="meeting">Meeting</option>
                        <option value="deadline">Deadline</option>
                        <option value="program">Program</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">Audience</label>
                    <select id="eventVisibility" name="visibility" class="w-full h-12 px-4 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-100 focus:border-red-400" required>
                        <option value="public">All Users / Public</option>
                        <option value="officials_only">SK Chairman and SK Secretary</option>
                        <option value="chairman_only">SK Chairman Only</option>
                        <option value="secretary_only">SK Secretary Only</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">Description</label>
                <textarea id="eventDescription" name="description" rows="3" maxlength="2000" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-100 focus:border-red-400" placeholder="Add agenda, activity details, or reminders...">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">Location</label>
                <input id="eventLocation" type="text" name="location" value="{{ old('location') }}" maxlength="255" class="w-full h-12 px-4 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-100 focus:border-red-400" placeholder="Venue, room, or meeting location">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">Start Date & Time</label>
                    <input id="eventStart" type="datetime-local" name="start_datetime" value="{{ old('start_datetime') }}" class="w-full h-12 px-4 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-100 focus:border-red-400" required>
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">End Date & Time</label>
                    <input id="eventEnd" type="datetime-local" name="end_datetime" value="{{ old('end_datetime') }}" class="w-full h-12 px-4 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-100 focus:border-red-400">
                    <p class="text-[9px] text-gray-400 mt-1">If empty, the event automatically ends one hour after the start.</p>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button id="cancelEventModalBtn" type="button" class="rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-bold text-gray-600 hover:bg-gray-50">Cancel</button>
                <button id="eventSubmitButton" type="submit" class="rounded-xl bg-red-600 hover:bg-red-700 text-white px-6 py-3 text-sm font-black transition">Save Event</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const notifBtn=document.getElementById('notifBtn');
const notifDropdown=document.getElementById('notifDropdown');
const userMenuBtn=document.getElementById('userMenuBtn');
const userDropdown=document.getElementById('userDropdown');
const openEventModalBtn=document.getElementById('openEventModalBtn');
const closeEventModalBtn=document.getElementById('closeEventModalBtn');
const cancelEventModalBtn=document.getElementById('cancelEventModalBtn');
const eventModal=document.getElementById('eventModal');
const eventForm=document.getElementById('eventForm');
const eventFormMethod=document.getElementById('eventFormMethod');
const editingEventId=document.getElementById('editingEventId');
const eventModalTitle=document.getElementById('eventModalTitle');
const eventModalDescription=document.getElementById('eventModalDescription');
const eventSubmitButton=document.getElementById('eventSubmitButton');
const eventTitle=document.getElementById('eventTitle');
const eventType=document.getElementById('eventType');
const eventDescription=document.getElementById('eventDescription');
const eventLocation=document.getElementById('eventLocation');
const eventStart=document.getElementById('eventStart');
const eventEnd=document.getElementById('eventEnd');
const eventVisibility=document.getElementById('eventVisibility');
const eventDetailsModal=document.getElementById('eventDetailsModal');
const closeEventDetailsBtn=document.getElementById('closeEventDetailsBtn');
const detailsCloseButton=document.getElementById('detailsCloseButton');
const detailsEditButton=document.getElementById('detailsEditButton');
const detailType=document.getElementById('detailType');
const detailTitle=document.getElementById('detailTitle');
const detailStart=document.getElementById('detailStart');
const detailEnd=document.getElementById('detailEnd');
const detailLocation=document.getElementById('detailLocation');
const detailVisibility=document.getElementById('detailVisibility');
const detailDescription=document.getElementById('detailDescription');
const detailSlotNotice=document.getElementById('detailSlotNotice');
const createEventUrl=@js(route('sk_pres.calendar.store'));
const calendarBaseUrl=@js(url('/sk_pres/calendar'));
let selectedEventData=null;

const toggleMenu=(btn,menu,other)=>{
    if(!btn || !menu || !other)return;

    btn.addEventListener('click',e=>{
        e.stopPropagation();
        menu.classList.toggle('hidden');
        other.classList.add('hidden');
    });
};

toggleMenu(notifBtn,notifDropdown,userDropdown);
toggleMenu(userMenuBtn,userDropdown,notifDropdown);

document.addEventListener('click',e=>{
    if(notifBtn && notifDropdown && !notifBtn.contains(e.target) && !notifDropdown.contains(e.target)){
        notifDropdown.classList.add('hidden');
    }

    if(userMenuBtn && userDropdown && !userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)){
        userDropdown.classList.add('hidden');
    }
});

const showModal=modal=>{
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
};

const hideModal=modal=>{
    modal.classList.add('hidden');
    modal.classList.remove('flex');

    if(eventModal.classList.contains('hidden') && eventDetailsModal.classList.contains('hidden')){
        document.body.classList.remove('overflow-hidden');
    }
};

const toDateTimeLocal=value=>{
    if(!value)return '';

    if(typeof value==='string' && value.length>=16 && value.includes('T')){
        return value.slice(0,16);
    }

    const date=new Date(value);

    if(Number.isNaN(date.getTime())){
        return String(value).slice(0,16);
    }

    const pad=number=>String(number).padStart(2,'0');

    return [
        date.getFullYear(),
        pad(date.getMonth()+1),
        pad(date.getDate())
    ].join('-')+'T'+[
        pad(date.getHours()),
        pad(date.getMinutes())
    ].join(':');
};

const formatDetailDate=(value,isSlot=false)=>{
    if(!value)return 'Not provided';

    if(isSlot){
        const parts=String(value).slice(0,10).split('-');

        if(parts.length===3){
            const date=new Date(Number(parts[0]),Number(parts[1])-1,Number(parts[2]));

            return date.toLocaleDateString('en-US',{
                month:'short',
                day:'numeric',
                year:'numeric'
            });
        }
    }

    const date=new Date(value);

    if(Number.isNaN(date.getTime()))return value;

    return date.toLocaleString('en-US',{
        month:'short',
        day:'numeric',
        year:'numeric',
        hour:'numeric',
        minute:'2-digit'
    });
};

const openCreateEventModal=()=>{
    eventForm.reset();
    eventForm.action=createEventUrl;
    eventFormMethod.disabled=true;
    editingEventId.value='';
    eventModalTitle.textContent='Add Event';
    eventModalDescription.textContent='Create a calendar event for the SK calendar.';
    eventSubmitButton.textContent='Save Event';
    eventType.value='meeting';
    eventVisibility.value='public';

    hideModal(eventDetailsModal);
    showModal(eventModal);
};

const openEditEventModal=eventData=>{
    eventForm.reset();
    eventForm.action=eventData.updateUrl;
    eventFormMethod.disabled=false;
    editingEventId.value=eventData.id || '';
    eventModalTitle.textContent='Edit Event';
    eventModalDescription.textContent='Update the title, date/time, location, audience, or event setting.';
    eventSubmitButton.textContent='Save Changes';
    eventTitle.value=eventData.title || '';
    eventType.value=eventData.eventType || 'meeting';
    eventDescription.value=eventData.description || '';
    eventLocation.value=eventData.location || '';
    eventStart.value=toDateTimeLocal(eventData.start);
    eventEnd.value=toDateTimeLocal(eventData.end);
    eventVisibility.value=eventData.visibility || 'public';

    hideModal(eventDetailsModal);
    showModal(eventModal);
};

const openEventDetails=eventData=>{
    selectedEventData=eventData;
    const isSlot=eventData.sourceType==='slot';

    detailType.textContent=eventData.typeLabel || (isSlot ? 'Submission Slot' : 'Calendar Event');
    detailTitle.textContent=eventData.title || 'Untitled Event';
    detailStart.textContent=formatDetailDate(eventData.start,isSlot);
    detailEnd.textContent=formatDetailDate(eventData.end,isSlot);
    detailLocation.textContent=eventData.location || 'No location provided';
    detailVisibility.textContent=eventData.visibilityLabel || 'Not specified';
    detailDescription.textContent=eventData.description || 'No description provided.';
    detailSlotNotice.classList.toggle('hidden',!isSlot);
    detailsEditButton.classList.toggle('hidden',!eventData.editable);

    showModal(eventDetailsModal);
};

const calendarEventData=event=>{
    const props=event.extendedProps || {};

    return {
        id:event.id,
        sourceType:props.source_type || 'event',
        editable:Boolean(props.editable),
        updateUrl:calendarBaseUrl+'/'+event.id,
        title:event.title,
        eventType:props.event_type || 'other',
        typeLabel:props.type_label || 'Calendar Event',
        description:props.description || '',
        location:props.location || '',
        start:props.start_value || event.start,
        end:props.end_value || event.end,
        visibility:props.visibility || '',
        visibilityLabel:props.visibility_label || 'Not specified'
    };
};

openEventModalBtn.addEventListener('click',openCreateEventModal);
closeEventModalBtn.addEventListener('click',()=>hideModal(eventModal));
cancelEventModalBtn.addEventListener('click',()=>hideModal(eventModal));
closeEventDetailsBtn.addEventListener('click',()=>hideModal(eventDetailsModal));
detailsCloseButton.addEventListener('click',()=>hideModal(eventDetailsModal));

detailsEditButton.addEventListener('click',()=>{
    if(selectedEventData?.editable){
        openEditEventModal(selectedEventData);
    }
});

eventModal.addEventListener('click',e=>{
    if(e.target===eventModal)hideModal(eventModal);
});

eventDetailsModal.addEventListener('click',e=>{
    if(e.target===eventDetailsModal)hideModal(eventDetailsModal);
});

document.addEventListener('keydown',e=>{
    if(e.key!=='Escape')return;

    if(!eventModal.classList.contains('hidden')){
        hideModal(eventModal);
    }else if(!eventDetailsModal.classList.contains('hidden')){
        hideModal(eventDetailsModal);
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
            buttonText:{
                today:'Today'
            },
            events:@json($calendarEvents),
            eventClick:info=>{
                openEventDetails(
                    calendarEventData(info.event)
                );
            }
        }
    );

    calendar.render();
});

document.querySelectorAll('.upcoming-event-btn').forEach(button=>{
    button.addEventListener('click',()=>{
        openEventDetails({
            id:button.dataset.eventId,
            sourceType:button.dataset.sourceType,
            editable:button.dataset.editable==='1',
            updateUrl:button.dataset.updateUrl || '',
            title:button.dataset.title || '',
            eventType:button.dataset.eventType || 'other',
            typeLabel:button.dataset.typeLabel || 'Calendar Event',
            description:button.dataset.description || '',
            location:button.dataset.location || '',
            start:button.dataset.start || '',
            end:button.dataset.end || '',
            visibility:button.dataset.visibility || '',
            visibilityLabel:button.dataset.visibilityLabel || 'Not specified'
        });
    });
});

@if($errors->any())
    @if(old('editing_event_id'))
        openEditEventModal({
            id:@js(old('editing_event_id')),
            updateUrl:calendarBaseUrl+'/'+@js(old('editing_event_id')),
            title:@js(old('event_title')),
            eventType:@js(old('event_type','meeting')),
            description:@js(old('description')),
            location:@js(old('location')),
            start:@js(old('start_datetime')),
            end:@js(old('end_datetime')),
            visibility:@js(old('visibility','public'))
        });
    @else
        eventForm.action=createEventUrl;
        eventFormMethod.disabled=true;
        editingEventId.value='';
        eventModalTitle.textContent='Add Event';
        eventModalDescription.textContent='Create a calendar event for the SK calendar.';
        eventSubmitButton.textContent='Save Event';
        showModal(eventModal);
    @endif
@endif
</script>
@endpush