{{-- File guide: Blade view template for resources/views/sk_secretary/meetings.blade.php. --}}
@extends('layouts.app')

@section('title', 'Meetings & Video Conference')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .meeting-scrollbar::-webkit-scrollbar { width: 8px; }
        .meeting-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 9999px; }
    </style>
@endsection

@section('content')
<div class="flex h-screen bg-gray-100 overflow-hidden">
    {{-- Shared secretary sidebar/topbar layout --}}
    @include('sk_secretary.partials.sidebar')
    <div class="flex-1 flex flex-col overflow-hidden min-w-0">
        @include('sk_secretary.partials.topbar')
        {{-- Meeting schedule, past meetings, and video conference tabs --}}
        <main class="flex-1 overflow-y-auto p-8">
            <section class="bg-white rounded-[28px] shadow-sm border border-gray-100 p-6 xl:p-8">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h1 class="text-[32px] font-bold tracking-tight text-gray-900">Meetings & Video Conference</h1>
                        <p class="mt-2 text-sm text-gray-500">View president-created meetings and join scheduled conferences</p>
                    </div>
                </div>

                <div class="mt-8 mx-auto flex w-full max-w-md items-center justify-between rounded-full bg-[#f6f7fb] p-1 text-xs font-semibold text-gray-500">
                    <button id="scheduleTabBtn" type="button" class="tab-btn flex-1 rounded-full px-4 py-2 bg-white text-gray-900 shadow-sm">Meeting Schedule</button>
                    <button id="conferenceTabBtn" type="button" class="tab-btn flex-1 rounded-full px-4 py-2">Video Conference</button>
                </div>

                <div id="scheduleTab" class="mt-8 space-y-6">
                    <div class="rounded-[24px] border border-gray-100 bg-[#fbfbfd] p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900">Upcoming Meetings</h2>
                                <p class="text-xs text-gray-500">Scheduled meetings and sessions</p>
                            </div>
                            <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-[#d90f1f]">{{ $upcomingMeetings->count() }} upcoming</span>
                        </div>
                        <div class="mt-4 space-y-3">
                            @forelse ($upcomingMeetings as $meeting)
                                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900">{{ $meeting->title }}</p>
                                            <p class="mt-1 text-xs text-gray-500">{{ $meeting->preview_datetime }}</p>
                                            <p class="mt-2 text-xs text-gray-400">{{ $meeting->agenda ?: 'No agenda provided yet.' }}</p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-green-50 px-3 py-1 text-[11px] font-semibold text-green-600">{{ $meeting->status_label }}</span>
                                    </div>
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <a href="{{ route('sk_secretary.meetings.call', $meeting->meeting_id) }}" class="inline-flex items-center rounded-xl bg-[#d90f1f] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#b90e1b]">Join Meeting</a>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-10 text-center">
                                    <p class="text-sm font-semibold text-gray-700">No scheduled meetings yet.</p>
                                    <p class="mt-2 text-xs text-gray-500">Wait for the SK President to create a meeting, then join it here.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-[24px] border border-gray-100 bg-[#fbfbfd] p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900">Past Meetings</h2>
                                <p class="text-xs text-gray-500">Meeting history and records</p>
                            </div>
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">{{ $pastMeetings->count() }} completed</span>
                        </div>
                        <div class="mt-4 space-y-3">
                            @forelse ($pastMeetings as $meeting)
                                <div class="rounded-2xl border border-gray-100 bg-white p-4">
                                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $meeting->title }}</p>
                                            <p class="mt-1 text-xs text-gray-500">{{ $meeting->preview_datetime }}</p>
                                        </div>
                                        <span class="rounded-full bg-gray-100 px-3 py-1 text-[11px] font-semibold text-gray-600">{{ $meeting->status_label }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-10 text-center">
                                    <p class="text-sm text-gray-500">No past meetings yet.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div id="conferenceTab" class="mt-8 hidden">
                    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_240px]">
                        <div class="rounded-[24px] bg-[#1b2230] p-4 text-white shadow-inner">
                            <div class="flex items-center justify-between">
                                <span class="rounded-full bg-red-500/20 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-red-200">Live</span>
                                <span class="text-xs text-white/60">Agora Preview</span>
                            </div>
                            <div class="mt-4 flex h-[300px] flex-col items-center justify-center rounded-[20px] bg-[#202938]">
                                @php $activeMeeting = $activeMeetings->first(); $initials = collect(explode(' ', trim($fullName)))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode(''); @endphp
                                <div class="flex h-24 w-24 items-center justify-center rounded-full bg-[#eb5757] text-2xl font-bold">{{ $initials ?: 'SK' }}</div>
                                <p class="mt-4 text-lg font-semibold">{{ $fullName }}</p>
                                <span class="mt-2 rounded-full bg-green-500/20 px-3 py-1 text-[11px] font-semibold text-green-300">{{ $activeMeeting ? 'Ready to join' : 'No active room' }}</span>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">
                                @forelse ($upcomingMeetings->take(4) as $meeting)
                                    <a href="{{ route('sk_secretary.meetings.call', $meeting->meeting_id) }}" class="rounded-2xl bg-[#273042] p-3 transition hover:bg-[#2d384d]">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#ef4444] text-sm font-bold">{{ strtoupper(substr($meeting->title, 0, 2)) }}</div>
                                        <p class="mt-3 truncate text-xs font-semibold">{{ $meeting->title }}</p>
                                        <p class="mt-1 text-[10px] text-white/50">Tap to join</p>
                                    </a>
                                @empty
                                    <div class="col-span-full rounded-2xl border border-dashed border-white/15 px-4 py-8 text-center text-sm text-white/60">No live-ready meetings available.</div>
                                @endforelse
                            </div>
                            <div class="mt-4 flex items-center justify-center gap-3 rounded-2xl bg-white/5 px-4 py-3">
                                <span class="rounded-xl bg-white/10 px-3 py-2 text-xs">Mic</span>
                                <span class="rounded-xl bg-white/10 px-3 py-2 text-xs">Cam</span>
                                <a href="{{ $activeMeeting ? route('sk_secretary.meetings.call', $activeMeeting->meeting_id) : '#' }}" class="rounded-xl bg-[#ef4444] px-4 py-2 text-xs font-semibold text-white {{ $activeMeeting ? '' : 'pointer-events-none opacity-50' }}">Join Active Meeting</a>
                                <span class="rounded-xl bg-white/10 px-3 py-2 text-xs">More</span>
                            </div>
                        </div>

                        <div class="rounded-[24px] border border-gray-100 bg-white p-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-900">Participants</h3>
                                <span class="text-xs text-gray-400">{{ $upcomingMeetings->count() }} meetings</span>
                            </div>
                            <div class="meeting-scrollbar mt-4 space-y-3 max-h-[460px] overflow-y-auto pr-1">
                                @forelse ($upcomingMeetings as $meeting)
                                    <div class="flex items-start gap-3 rounded-2xl border border-gray-100 p-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#fce8ea] text-xs font-bold text-[#d90f1f]">{{ strtoupper(substr($meeting->title, 0, 2)) }}</div>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-gray-800">{{ $meeting->title }}</p>
                                            <p class="mt-1 text-[11px] text-gray-400">{{ $meeting->preview_datetime }}</p>
                                            <div class="mt-2 flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-green-500"></span><span class="text-[11px] text-green-600">{{ $meeting->status_label }}</span></div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-gray-200 px-4 py-8 text-center text-sm text-gray-500">No upcoming meeting rooms yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
const notifBtn = document.getElementById('notifBtn');
const notifDropdown = document.getElementById('notifDropdown');
const userMenuBtn = document.getElementById('userMenuBtn');
const userDropdown = document.getElementById('userDropdown');
const scheduleTabBtn = document.getElementById('scheduleTabBtn');
const conferenceTabBtn = document.getElementById('conferenceTabBtn');
const scheduleTab = document.getElementById('scheduleTab');
const conferenceTab = document.getElementById('conferenceTab');
if (notifBtn && notifDropdown) { notifBtn.addEventListener('click', (e) => { e.stopPropagation(); notifDropdown.classList.toggle('hidden'); userDropdown.classList.add('hidden'); }); }
if (userMenuBtn && userDropdown) {
    userMenuBtn.addEventListener('click', (e) => { e.stopPropagation(); userDropdown.classList.toggle('hidden'); if (notifDropdown) notifDropdown.classList.add('hidden'); });
    document.addEventListener('click', (e) => {
        if (notifBtn && notifDropdown && !notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) notifDropdown.classList.add('hidden');
        if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) userDropdown.classList.add('hidden');
    });
}
const showScheduleTab = () => { scheduleTab.classList.remove('hidden'); conferenceTab.classList.add('hidden'); scheduleTabBtn.classList.add('bg-white', 'text-gray-900', 'shadow-sm'); conferenceTabBtn.classList.remove('bg-white', 'text-gray-900', 'shadow-sm'); };
const showConferenceTab = () => { conferenceTab.classList.remove('hidden'); scheduleTab.classList.add('hidden'); conferenceTabBtn.classList.add('bg-white', 'text-gray-900', 'shadow-sm'); scheduleTabBtn.classList.remove('bg-white', 'text-gray-900', 'shadow-sm'); };
scheduleTabBtn.addEventListener('click', showScheduleTab);
conferenceTabBtn.addEventListener('click', showConferenceTab);
</script>
@endpush
