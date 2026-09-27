{{-- File guide: Blade view template for resources/views/sk_pres/meetings.blade.php. --}}
@extends('layouts.app')
@section('title','Meetings')
@section('page_css')
<script src="https://cdn.tailwindcss.com"></script>
<style>
.meeting-scrollbar::-webkit-scrollbar{width:8px}
.meeting-scrollbar::-webkit-scrollbar-thumb{background:#d1d5db;border-radius:9999px}
</style>
@endsection
@section('content')
@php
$focusMeeting=(int)request()->query('focus_meeting',0);
$activeMeeting=$activeMeetings->first();
$nameParts=preg_split('/\s+/',trim((string)$fullName)) ?: [];
$meetingInitials='';
foreach(array_slice(array_values(array_filter($nameParts)),0,2) as $part){
    $meetingInitials.=strtoupper(substr($part,0,1));
}
$meetingInitials=$meetingInitials!=='' ? $meetingInitials : 'SK';
@endphp
<div class="flex h-screen bg-gray-100">
        @include('partials.app.sidebar')


    <div class="flex-1 flex flex-col min-w-0">
                @include('partials.app.topbar')


        <main class="flex-1 overflow-y-auto p-8">
            <section class="bg-white rounded-[28px] shadow-sm border border-gray-100 p-6 xl:p-8">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <span class="sk-eyebrow"><span class="sk-dot"></span>Meetings</span>
                        <h1 class="text-[32px] font-bold tracking-tight text-gray-900">Meetings</h1>
                        <p class="mt-2 text-sm text-gray-500">Organize SK meetings, participation, and attendance records</p>
                    </div>

                    <button id="openModalBtn" type="button" class="sk-btn sk-btn--primary">
                        @include('partials.ui.icon', ['icon'=>'plus','iconSize'=>17,'iconStroke'=>2.4])
                        Schedule Meeting
                    </button>
                </div>

                @if(session('status'))
                    <div class="mt-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                @if(session('warning'))
                    <div class="mt-6 rounded-2xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-700">
                        {{ session('warning') }}
                    </div>
                @endif

                <div id="scheduleTab" class="mt-8 space-y-6">
                    <div class="rounded-[24px] border border-gray-100 bg-[#fbfbfd] p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900">Upcoming Meetings</h2>
                                <p class="text-xs text-gray-500">Scheduled meetings that have not started yet</p>
                            </div>

                            <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-[#d90f1f]">
                                {{ $upcomingMeetings->count() }} upcoming
                            </span>
                        </div>

                        <div class="mt-4 space-y-3">
                            @forelse($upcomingMeetings as $meeting)
                                @php($isFocused=(int)$meeting->meeting_id===$focusMeeting)

                                <div id="meeting-card-{{ $meeting->meeting_id }}"
                                    data-focus-meeting-card="{{ $isFocused ? '1' : '0' }}"
                                    class="rounded-2xl border bg-white p-4 shadow-sm transition-all duration-500 {{ $isFocused ? 'border-yellow-400 bg-yellow-50 ring-4 ring-yellow-200' : 'border-gray-100' }}">

                                    @if($isFocused)
                                        <div class="mb-3 rounded-xl border border-yellow-200 bg-yellow-100 px-3 py-2 text-xs font-semibold text-yellow-800">
                                            @include('partials.ui.icon', ['icon'=>'bell','iconSize'=>14]) This is the meeting from the notification you opened.
                                        </div>
                                    @endif

                                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900">
                                                {{ $meeting->title }}
                                            </p>

                                            <p class="mt-1 text-xs text-gray-500">
                                                {{ $meeting->preview_datetime }}
                                            </p>

                                            @if($meeting->location_or_link)
                                                <p class="mt-1 text-xs text-gray-500">
                                                    {{ $meeting->location_or_link }}
                                                </p>
                                            @endif

                                            <p class="mt-2 text-xs text-gray-400">
                                                {{ $meeting->agenda ?: 'No agenda provided yet.' }}
                                            </p>
                                        </div>

                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-[11px] font-semibold text-blue-600">
                                            {{ $meeting->status_label }}
                                        </span>
                                    </div>

                                    <div class="mt-4">
                                        <span class="inline-flex items-center rounded-xl bg-gray-100 px-4 py-2 text-xs font-semibold text-gray-500">
                                            Available when meeting starts
                                        </span>

                                        <button type="button"
                                            class="editMeetingBtn ml-2 inline-flex items-center rounded-xl bg-red-50 px-4 py-2 text-xs font-semibold text-[#d90f1f] transition hover:bg-red-100"
                                            data-id="{{ $meeting->meeting_id }}"
                                            data-action="{{ route('sk_pres.meetings.update',$meeting->meeting_id) }}"
                                            data-title="{{ $meeting->title }}"
                                            data-agenda="{{ $meeting->agenda ?? '' }}"
                                            data-location="{{ $meeting->location_or_link ?? '' }}"
                                            data-date="{{ \Carbon\Carbon::parse($meeting->meeting_date)->format('Y-m-d') }}"
                                            data-time="{{ \Illuminate\Support\Str::of($meeting->meeting_time)->substr(0,5) }}"
                                            data-ongoing="0">
                                            Edit
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-10 text-center">
                                    <p class="text-sm font-semibold text-gray-700">No upcoming meetings.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-[24px] border border-red-100 bg-red-50/40 p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900">Ongoing Meetings</h2>
                                <p class="text-xs text-gray-500">Meetings currently open for face-to-face or video-call participation</p>
                            </div>

                            <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-600">
                                {{ $activeMeetings->count() }} ongoing
                            </span>
                        </div>

                        <div class="mt-4 space-y-3">
                            @forelse($activeMeetings as $meeting)
                                @php($isFocused=(int)$meeting->meeting_id===$focusMeeting)

                                <div id="meeting-card-{{ $meeting->meeting_id }}"
                                    data-focus-meeting-card="{{ $isFocused ? '1' : '0' }}"
                                    class="rounded-2xl border bg-white p-4 shadow-sm transition-all duration-500 {{ $isFocused ? 'border-yellow-400 bg-yellow-50 ring-4 ring-yellow-200' : 'border-red-100' }}">

                                    @if($isFocused)
                                        <div class="mb-3 rounded-xl border border-yellow-200 bg-yellow-100 px-3 py-2 text-xs font-semibold text-yellow-800">
                                            @include('partials.ui.icon', ['icon'=>'bell','iconSize'=>14]) This meeting is ready to join.
                                        </div>
                                    @endif

                                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900">
                                                {{ $meeting->title }}
                                            </p>

                                            <p class="mt-1 text-xs text-gray-500">
                                                {{ $meeting->preview_datetime }}
                                            </p>

                                            @if($meeting->location_or_link)
                                                <p class="mt-1 text-xs text-gray-500">
                                                    {{ $meeting->location_or_link }}
                                                </p>
                                            @endif

                                            <p class="mt-2 text-xs text-gray-400">
                                                {{ $meeting->agenda ?: 'No agenda provided yet.' }}
                                            </p>
                                        </div>

                                        <span class="inline-flex items-center rounded-full bg-red-50 px-3 py-1 text-[11px] font-semibold text-red-600">
                                            {{ $meeting->status_label }}
                                        </span>
                                    </div>

                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <a href="{{ route('sk_pres.meetings.call',$meeting->meeting_id) }}"
                                            class="inline-flex items-center rounded-xl bg-[#d90f1f] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#b90e1b]">
                                            Join Video Call
                                        </a>

                                        <button type="button"
                                            class="editMeetingBtn inline-flex items-center rounded-xl bg-red-50 px-4 py-2 text-xs font-semibold text-[#d90f1f] transition hover:bg-red-100"
                                            data-id="{{ $meeting->meeting_id }}"
                                            data-action="{{ route('sk_pres.meetings.update',$meeting->meeting_id) }}"
                                            data-title="{{ $meeting->title }}"
                                            data-agenda="{{ $meeting->agenda ?? '' }}"
                                            data-location="{{ $meeting->location_or_link ?? '' }}"
                                            data-date="{{ \Carbon\Carbon::parse($meeting->meeting_date)->format('Y-m-d') }}"
                                            data-time="{{ \Illuminate\Support\Str::of($meeting->meeting_time)->substr(0,5) }}"
                                            data-ongoing="1">
                                            Edit Info
                                        </button>

                                        <form method="POST"
                                            action="{{ route('sk_pres.meetings.finish',$meeting->meeting_id) }}"
                                            onsubmit="return confirm('End this meeting for everyone? Everyone in the video call will be disconnected and attendance can then be finalized.');">
                                            @csrf

                                            <button type="submit"
                                                class="inline-flex items-center rounded-xl bg-gray-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-gray-700">
                                                End Meeting for Everyone
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-red-100 bg-white px-6 py-10 text-center">
                                    <p class="text-sm font-semibold text-gray-700">No ongoing meetings.</p>
                                    <p class="mt-2 text-xs text-gray-500">A scheduled meeting becomes ongoing once its start time arrives.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-[24px] border border-gray-100 bg-[#fbfbfd] p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900">Past Meetings</h2>
                                <p class="text-xs text-gray-500">Completed and cancelled meeting records</p>
                            </div>

                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                                {{ $pastMeetings->count() }} past
                            </span>
                        </div>

                        <div class="mt-4 space-y-3">
                            @forelse($pastMeetings as $meeting)
                                @php($isFocused=(int)$meeting->meeting_id===$focusMeeting)

                                <div id="meeting-card-{{ $meeting->meeting_id }}"
                                    data-focus-meeting-card="{{ $isFocused ? '1' : '0' }}"
                                    class="rounded-2xl border bg-white p-4 transition-all duration-500 {{ $isFocused ? 'border-yellow-400 bg-yellow-50 ring-4 ring-yellow-200' : 'border-gray-100' }}">

                                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $meeting->title }}</p>
                                            <p class="mt-1 text-xs text-gray-500">{{ $meeting->preview_datetime }}</p>

                                            @if($meeting->location_or_link)
                                                <p class="mt-1 text-xs text-gray-500">{{ $meeting->location_or_link }}</p>
                                            @endif

                                            @if($meeting->status==='completed' && $meeting->attendance_finalized)
                                                <div class="mt-3 flex flex-wrap gap-2">
                                                    <span class="rounded-full bg-green-50 px-3 py-1 text-[11px] font-semibold text-green-600">
                                                        {{ $meeting->attendance_present_count }} present
                                                    </span>

                                                    <span class="rounded-full bg-red-50 px-3 py-1 text-[11px] font-semibold text-red-600">
                                                        {{ $meeting->attendance_absent_count }} absent
                                                    </span>
                                                </div>
                                            @endif
                                        </div>

                                        <span class="rounded-full {{ $meeting->status==='cancelled' ? 'bg-red-50 text-red-600' : 'bg-gray-100 text-gray-600' }} px-3 py-1 text-[11px] font-semibold">
                                            {{ $meeting->status_label }}
                                        </span>
                                    </div>

                                    @if($meeting->status==='completed')
                                        <div class="mt-4 flex flex-wrap items-center gap-2">
                                            @if($meeting->attendance_finalized)
                                                <span class="inline-flex items-center rounded-xl bg-green-50 px-4 py-2 text-xs font-semibold text-green-700">
                                                    Attendance Recorded
                                                </span>
                                            @else
                                                <button type="button"
                                                    class="recordAttendanceBtn inline-flex items-center rounded-xl bg-[#d90f1f] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#b90e1b]"
                                                    data-title="{{ $meeting->title }}"
                                                    data-action="{{ route('sk_pres.meetings.attendance',$meeting->meeting_id) }}"
                                                    data-video-attendance='@json($meeting->attendance_video_barangay_ids ?? [])'>
                                                    Record Attendance
                                                </button>

                                                <span class="text-[11px] text-gray-400">
                                                    Video-call attendance is loaded automatically. Mark only additional face-to-face attendees.
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-10 text-center">
                                    <p class="text-sm text-gray-500">No past meetings yet.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>

<div id="scheduleModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/45 px-4">
    <div class="w-full max-w-xl rounded-[28px] bg-white p-8 shadow-2xl">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 id="meetingModalTitle" class="text-2xl font-bold text-gray-900">Schedule New Meeting</h2>
                <p id="meetingModalDescription" class="mt-2 text-sm text-gray-500">Create a meeting and invite participants</p>
            </div>

            <button id="closeModalBtn" type="button" class="rounded-xl p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                X
            </button>
        </div>

        <form id="meetingForm" action="{{ route('sk_pres.meetings.store') }}" method="POST" class="mt-8 space-y-5" novalidate>
            @csrf

            <input id="meetingFormMethod" type="hidden" name="_method" value="PUT" disabled>

            <div id="meetingFormWarning" class="hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p id="meetingFormWarningTitle" class="font-bold">Please check the meeting details.</p>
                <ul id="meetingFormWarningList" class="mt-2 list-disc space-y-1 pl-5 text-xs"></ul>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-800">Meeting Title</label>
                <input id="meetingTitle"
                    type="text"
                    name="title"
                    class="h-12 w-full rounded-xl border border-red-100 bg-[#fff7f7] px-4 text-sm text-gray-700 outline-none transition focus:border-[#d90f1f] focus:bg-white">
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-800">Agenda</label>
                <textarea id="meetingAgenda"
                    name="agenda"
                    rows="4"
                    class="w-full rounded-xl border border-red-100 bg-[#fff7f7] px-4 py-3 text-sm text-gray-700 outline-none transition focus:border-[#d90f1f] focus:bg-white"></textarea>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-800">Location or Meeting Link</label>
                <input id="meetingLocation"
                    type="text"
                    name="location_or_link"
                    placeholder="Room, venue, or meeting link"
                    class="h-12 w-full rounded-xl border border-red-100 bg-[#fff7f7] px-4 text-sm text-gray-700 outline-none transition focus:border-[#d90f1f] focus:bg-white">
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-800">Date</label>
                    <input id="meetingDate"
                        type="date"
                        name="meeting_date"
                        class="h-12 w-full rounded-xl border border-red-100 bg-[#fff7f7] px-4 text-sm text-gray-700 outline-none transition focus:border-[#d90f1f] focus:bg-white">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-800">Time</label>
                    <input id="meetingTime"
                        type="time"
                        name="meeting_time"
                        class="h-12 w-full rounded-xl border border-red-100 bg-[#fff7f7] px-4 text-sm text-gray-700 outline-none transition focus:border-[#d90f1f] focus:bg-white">
                </div>
            </div>

            <button id="meetingSubmitButton"
                type="submit"
                class="w-full rounded-xl bg-[#d90f1f] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b90e1b]">
                Add Event
            </button>
        </form>
    </div>
</div>

<div id="attendanceModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/45 px-4">
    <div class="w-full max-w-3xl rounded-[28px] bg-white p-6 shadow-2xl">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Record Meeting Attendance</h2>
                <p id="attendanceMeetingTitle" class="mt-1 text-sm font-semibold text-[#d90f1f]"></p>
                <p class="mt-2 text-xs text-gray-500">
                    Video-call attendance is loaded automatically from the actual SK360 call record. Mark only additional barangays that attended face-to-face.
                </p>
            </div>

            <button id="closeAttendanceModalBtn"
                type="button"
                class="rounded-xl p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                X
            </button>
        </div>

        <form id="attendanceForm" method="POST" class="mt-6">
            @csrf

            <label class="mb-4 flex cursor-pointer items-center gap-3 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 transition hover:bg-red-100">
                <input id="selectAllAttendance"
                    type="checkbox"
                    class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">

                <div>
                    <p class="text-sm font-semibold text-red-700">Select All Present</p>
                    <p class="text-[11px] text-red-500">Mark all remaining non-video barangays as face-to-face attendees.</p>
                </div>
            </label>

            <div class="meeting-scrollbar grid max-h-[420px] grid-cols-1 gap-2 overflow-y-auto pr-2 sm:grid-cols-2 lg:grid-cols-3">
                @forelse($attendanceBarangays as $barangay)
                    <label data-attendance-row="{{ $barangay->barangay_id }}"
                        class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-100 bg-gray-50 px-3 py-3 transition hover:border-red-200 hover:bg-red-50">

                        <input type="checkbox"
                            name="present_barangays[]"
                            value="{{ $barangay->barangay_id }}"
                            class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">

                        <div class="flex min-w-0 flex-1 items-center justify-between gap-2">
                            <span class="text-xs font-semibold text-gray-700">
                                {{ $barangay->barangay_name }}
                            </span>

                            <span data-video-badge class="hidden shrink-0 rounded-full bg-green-100 px-2 py-1 text-[9px] font-bold uppercase text-green-700">
                                Video Call
                            </span>
                        </div>
                    </label>
                @empty
                    <div class="col-span-full rounded-xl border border-dashed border-gray-200 px-4 py-8 text-center text-sm text-gray-500">
                        No current barangays available for attendance.
                    </div>
                @endforelse
            </div>

            <div class="mt-6 rounded-2xl bg-yellow-50 px-4 py-3 text-xs text-yellow-700">
                Video-call attendees are pre-checked and locked from the official call record. Present barangays receive +5 and barangays with neither attendance source receive -5.
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button id="cancelAttendanceBtn"
                    type="button"
                    class="rounded-xl bg-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-200">
                    Cancel
                </button>

                <button type="submit"
                    class="rounded-xl bg-[#d90f1f] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#b90e1b]">
                    Finalize Attendance
                </button>
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
const openModalBtn=document.getElementById('openModalBtn');
const closeModalBtn=document.getElementById('closeModalBtn');
const scheduleModal=document.getElementById('scheduleModal');
const meetingForm=document.getElementById('meetingForm');
const meetingFormMethod=document.getElementById('meetingFormMethod');
const meetingFormWarning=document.getElementById('meetingFormWarning');
const meetingFormWarningTitle=document.getElementById('meetingFormWarningTitle');
const meetingFormWarningList=document.getElementById('meetingFormWarningList');
const meetingModalTitle=document.getElementById('meetingModalTitle');
const meetingModalDescription=document.getElementById('meetingModalDescription');
const meetingTitle=document.getElementById('meetingTitle');
const meetingAgenda=document.getElementById('meetingAgenda');
const meetingLocation=document.getElementById('meetingLocation');
const meetingDate=document.getElementById('meetingDate');
const meetingTime=document.getElementById('meetingTime');
const meetingSubmitButton=document.getElementById('meetingSubmitButton');
const scheduleTab=document.getElementById('scheduleTab');
const attendanceModal=document.getElementById('attendanceModal');
const attendanceForm=document.getElementById('attendanceForm');
const attendanceMeetingTitle=document.getElementById('attendanceMeetingTitle');
const closeAttendanceModalBtn=document.getElementById('closeAttendanceModalBtn');
const cancelAttendanceBtn=document.getElementById('cancelAttendanceBtn');
const selectAllAttendance=document.getElementById('selectAllAttendance');
const createMeetingUrl=@js(route('sk_pres.meetings.store'));
const feedUrl=@js(route('notifications.feed'));
const readBaseUrl=@js(url('/notifications'));
const csrfToken=@js(csrf_token());

const escapeHtml=(value)=>String(value ?? '')
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'",'&#039;');

if(notifBtn && notifDropdown){
    notifBtn.addEventListener('click',(e)=>{
        e.stopPropagation();
        notifDropdown.classList.toggle('hidden');
        userDropdown?.classList.add('hidden');
    });
}

if(userMenuBtn && userDropdown){
    userMenuBtn.addEventListener('click',(e)=>{
        e.stopPropagation();
        userDropdown.classList.toggle('hidden');
        notifDropdown?.classList.add('hidden');
    });
}

document.addEventListener('click',(e)=>{
    if(
        notifBtn &&
        notifDropdown &&
        !notifBtn.contains(e.target) &&
        !notifDropdown.contains(e.target)
    ){
        notifDropdown.classList.add('hidden');
    }

    if(
        userMenuBtn &&
        userDropdown &&
        !userMenuBtn.contains(e.target) &&
        !userDropdown.contains(e.target)
    ){
        userDropdown.classList.add('hidden');
    }
});

if(notifBtn && notifDropdown){
    let badge=notifBtn.querySelector('[data-notification-badge]');

    if(!badge){
        badge=document.createElement('span');
        badge.setAttribute('data-notification-badge','true');
        badge.className='hidden absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-yellow-400 text-red-700 text-[10px] font-bold flex items-center justify-center';
        notifBtn.appendChild(badge);
    }

    const renderNotifications=(payload)=>{
        const unreadCount=payload.unread_count || 0;
        const notifications=payload.notifications || [];

        badge.textContent=unreadCount>99 ? '99+' : String(unreadCount);
        badge.classList.toggle('hidden',unreadCount===0);

        const body=notifications.length===0
            ? '<div class="px-4 py-3 text-sm text-gray-700">No notifications yet</div>'
            : notifications.map((notification)=>`
                <a href="${escapeHtml(notification.url || '#')}"
                    data-notification-link
                    data-id="${Number(notification.id) || 0}"
                    class="block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 ${notification.is_read ? 'bg-white' : 'bg-red-50'}">
                    <div class="text-sm font-semibold text-gray-800">${escapeHtml(notification.title)}</div>
                    <div class="mt-1 text-xs text-gray-600">${escapeHtml(notification.message)}</div>
                    <div class="mt-1 text-[11px] text-gray-400">${escapeHtml(notification.created_at || '')}</div>
                </a>
            `).join('');

        notifDropdown.innerHTML=`
            <div class="px-4 py-3 font-semibold border-b text-gray-800">
                Notifications
            </div>
            <div class="max-h-72 overflow-y-auto">
                ${body}
            </div>
        `;
    };

    const fetchFeed=async()=>{
        try{
            const response=await fetch(feedUrl,{
                headers:{
                    'X-Requested-With':'XMLHttpRequest',
                    'Accept':'application/json'
                },
                credentials:'same-origin'
            });

            if(response.ok){
                renderNotifications(await response.json());
            }
        }catch(error){}
    };

    notifDropdown.addEventListener('click',async(event)=>{
        const link=event.target.closest('[data-notification-link]');

        if(!link){
            return;
        }

        event.preventDefault();

        const destination=link.getAttribute('href') || '#';

        try{
            await fetch(`${readBaseUrl}/${link.dataset.id}/read`,{
                method:'POST',
                headers:{
                    'X-CSRF-TOKEN':csrfToken,
                    'X-Requested-With':'XMLHttpRequest',
                    'Accept':'application/json'
                },
                credentials:'same-origin'
            });
        }catch(error){}

        window.location.href=destination;
    });

    fetchFeed();
    setInterval(fetchFeed,5000);
}


const clearMeetingWarning=()=>{
    meetingFormWarning?.classList.add('hidden');

    if(meetingFormWarningList){
        meetingFormWarningList.innerHTML='';
    }
};

const showMeetingWarning=(messages,mode)=>{
    if(!meetingFormWarning || !meetingFormWarningList){
        return;
    }

    meetingFormWarningTitle.textContent=
        mode==='edit'
            ? 'Conference change rejected'
            : 'Unable to schedule conference';

    meetingFormWarningList.innerHTML='';

    messages.forEach((message)=>{
        const item=document.createElement('li');
        item.textContent=message;
        meetingFormWarningList.appendChild(item);
    });

    meetingFormWarning.classList.remove('hidden');

    meetingFormWarning.scrollIntoView({
        behavior:'smooth',
        block:'nearest'
    });
};

const meetingValidationMessages=()=>{
    const messages=[];
    const title=meetingTitle.value.trim();
    const location=meetingLocation.value.trim();

    if(!title){
        messages.push('Meeting title is required.');
    }else if(title.length>255){
        messages.push('Meeting title may not be greater than 255 characters.');
    }

    if(location.length>255){
        messages.push('Conference location or link may not be greater than 255 characters.');
    }

    if(!meetingDate.value){
        messages.push('Meeting date is required.');
    }

    if(!meetingTime.value){
        messages.push('Meeting time is required.');
    }

    if(
        meetingDate.value &&
        meetingTime.value &&
        meetingForm.dataset.ongoing!=='1'
    ){
        const scheduledAt=new Date(`${meetingDate.value}T${meetingTime.value}:00`);

        if(Number.isNaN(scheduledAt.getTime())){
            messages.push('Meeting date or time is invalid.');
        }else if(scheduledAt.getTime()<=Date.now()){
            messages.push('The conference date and time must be in the future.');
        }
    }

    return messages;
};

const openScheduleModal=(keepWarning=false)=>{
    if(!keepWarning){
        clearMeetingWarning();
    }

    scheduleModal.classList.remove('hidden');
    scheduleModal.classList.add('flex');
};

const closeScheduleModal=()=>{
    scheduleModal.classList.add('hidden');
    scheduleModal.classList.remove('flex');
};

const openCreateMeetingModal=(keepValues=false,keepWarning=false)=>{
    if(!keepValues){
        meetingForm.reset();
    }

    meetingForm.action=createMeetingUrl;
    meetingFormMethod.disabled=true;
    meetingForm.dataset.mode='create';
    meetingForm.dataset.ongoing='0';

    meetingModalTitle.textContent='Schedule New Meeting';
    meetingModalDescription.textContent='Create a meeting and invite participants';
    meetingSubmitButton.textContent='Add Event';

    openScheduleModal(keepWarning);
};

const openEditMeetingModal=(button,keepWarning=false)=>{
    meetingForm.reset();

    meetingForm.action=button.dataset.action;
    meetingFormMethod.disabled=false;
    meetingForm.dataset.mode='edit';
    meetingForm.dataset.ongoing=button.dataset.ongoing || '0';

    meetingModalTitle.textContent='Edit Meeting Information';
    meetingModalDescription.textContent='Update valid conference information for this meeting.';
    meetingSubmitButton.textContent='Save Changes';

    meetingTitle.value=button.dataset.title || '';
    meetingAgenda.value=button.dataset.agenda || '';
    meetingLocation.value=button.dataset.location || '';
    meetingDate.value=button.dataset.date || '';
    meetingTime.value=button.dataset.time || '';

    openScheduleModal(keepWarning);
};

openModalBtn?.addEventListener('click',()=>openCreateMeetingModal());
closeModalBtn?.addEventListener('click',closeScheduleModal);

scheduleModal?.addEventListener('click',(e)=>{
    if(e.target===scheduleModal){
        closeScheduleModal();
    }
});

document.querySelectorAll('.editMeetingBtn').forEach((button)=>{
    button.addEventListener('click',()=>openEditMeetingModal(button));
});

meetingForm?.addEventListener('submit',(event)=>{
    const messages=meetingValidationMessages();

    if(messages.length){
        event.preventDefault();

        showMeetingWarning(
            messages,
            meetingForm.dataset.mode || 'create'
        );
    }
});

meetingForm?.querySelectorAll('input,textarea').forEach((field)=>{
    field.addEventListener('input',clearMeetingWarning);
    field.addEventListener('change',clearMeetingWarning);
});

const attendanceCheckboxes=()=>Array.from(
    attendanceForm.querySelectorAll(
        'input[name="present_barangays[]"]'
    )
);

const manualAttendanceCheckboxes=()=>attendanceCheckboxes()
    .filter((checkbox)=>!checkbox.disabled);

const syncSelectAllAttendance=()=>{
    if(!selectAllAttendance){
        return;
    }

    const checkboxes=manualAttendanceCheckboxes();

    const checkedCount=checkboxes.filter(
        (checkbox)=>checkbox.checked
    ).length;

    selectAllAttendance.checked=
        checkboxes.length>0 &&
        checkedCount===checkboxes.length;

    selectAllAttendance.indeterminate=
        checkedCount>0 &&
        checkedCount<checkboxes.length;
};

selectAllAttendance?.addEventListener('change',()=>{
    manualAttendanceCheckboxes().forEach((checkbox)=>{
        checkbox.checked=selectAllAttendance.checked;
    });

    selectAllAttendance.indeterminate=false;
});

attendanceCheckboxes().forEach((checkbox)=>{
    checkbox.addEventListener('change',syncSelectAllAttendance);
});

const openAttendanceModal=(button)=>{
    attendanceForm.action=button.dataset.action;
    attendanceMeetingTitle.textContent=button.dataset.title;

    let videoAttendance=[];

    try{
        videoAttendance=JSON.parse(
            button.dataset.videoAttendance || '[]'
        ).map(String);
    }catch(error){
        videoAttendance=[];
    }

    attendanceCheckboxes().forEach((checkbox)=>{
        const isVideoAttendance=videoAttendance.includes(
            String(checkbox.value)
        );

        checkbox.checked=isVideoAttendance;
        checkbox.disabled=isVideoAttendance;

        const row=checkbox.closest('[data-attendance-row]');
        const badge=row?.querySelector('[data-video-badge]');

        row?.classList.toggle('border-green-200',isVideoAttendance);
        row?.classList.toggle('bg-green-50',isVideoAttendance);
        row?.classList.toggle('border-gray-100',!isVideoAttendance);
        row?.classList.toggle('bg-gray-50',!isVideoAttendance);

        badge?.classList.toggle(
            'hidden',
            !isVideoAttendance
        );
    });

    syncSelectAllAttendance();

    attendanceModal.classList.remove('hidden');
    attendanceModal.classList.add('flex');
};

const closeAttendanceModal=()=>{
    attendanceModal.classList.add('hidden');
    attendanceModal.classList.remove('flex');
};

document.querySelectorAll('.recordAttendanceBtn').forEach((button)=>{
    button.addEventListener('click',()=>openAttendanceModal(button));
});

closeAttendanceModalBtn?.addEventListener('click',closeAttendanceModal);
cancelAttendanceBtn?.addEventListener('click',closeAttendanceModal);

attendanceModal?.addEventListener('click',(e)=>{
    if(e.target===attendanceModal){
        closeAttendanceModal();
    }
});

@if($errors->getBag('meetingCreate')->any())
openCreateMeetingModal(true,true);
meetingTitle.value=@js(old('title',''));
meetingAgenda.value=@js(old('agenda',''));
meetingLocation.value=@js(old('location_or_link',''));
meetingDate.value=@js(old('meeting_date',''));
meetingTime.value=@js(old('meeting_time',''));

showMeetingWarning(
    @js($errors->getBag('meetingCreate')->all()),
    'create'
);
@endif

@if($errors->getBag('meetingEdit')->any())
@php($failedEditMeetingId=(string)session('edit_meeting_id',''))

const failedEditMeetingId=@js($failedEditMeetingId);
const failedEditButton=document.querySelector(
    `.editMeetingBtn[data-id="${failedEditMeetingId}"]`
);

if(failedEditButton){
    openEditMeetingModal(
        failedEditButton,
        true
    );

    meetingTitle.value=@js(old('title',''));
    meetingAgenda.value=@js(old('agenda',''));
    meetingLocation.value=@js(old('location_or_link',''));
    meetingDate.value=@js(old('meeting_date',''));
    meetingTime.value=@js(old('meeting_time',''));

    showMeetingWarning(
        @js($errors->getBag('meetingEdit')->all()),
        'edit'
    );
}
@endif

const focusedMeeting=document.querySelector(
    '[data-focus-meeting-card="1"]'
);

if(focusedMeeting){
    setTimeout(()=>{
        focusedMeeting.scrollIntoView({
            behavior:'smooth',
            block:'center'
        });
    },250);
}
</script>
@endpush