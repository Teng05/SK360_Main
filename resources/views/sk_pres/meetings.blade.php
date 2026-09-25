{{-- File guide: Blade view template for resources/views/sk_pres/meetings.blade.php. --}}
@extends('layouts.app')

@section('title', 'Meetings & Video Conference')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .meeting-scrollbar::-webkit-scrollbar {
            width: 8px;
        }

        .meeting-scrollbar::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 9999px;
        }
    </style>
@endsection

@section('content')
<div class="flex h-screen bg-gray-100">
    <aside class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
        <div class="flex items-center gap-3 mb-4">
    <img src="{{ asset('images/logo.png') }}" class="w-8 h-8 rounded-full object-cover"  alt="logo">
    <div class="leading-tight">
        <h2 class="text-lg font-extrabold tracking-wide">SK 360°</h2>
        <p class="text-[10px] opacity-80">Management System</p>
    </div>
</div>

        <div class="bg-red-500 rounded-lg p-2 flex items-center gap-2 mb-3 shadow text-xs">
            <div class="bg-yellow-400 text-red-600 p-1 rounded-full text-sm">&#128100;</div>
            <div>
                <p class="font-semibold text-xs">SK President</p>
                <p class="text-xs opacity-80">Active Role</p>
            </div>
        </div>

        <nav class="space-y-1 text-xs">
            @foreach ($menuItems as $item)
                <a href="{{ $item['link'] }}"
                   class="flex items-center gap-2 p-2 rounded-lg {{ $item['link'] === $currentUrl ? 'bg-red-500' : 'hover:bg-red-500 transition' }}">
                    <span class="{{ $item['link'] === $currentUrl ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded text-sm">{!! $item['icon'] !!}</span>
                    <span class="{{ $item['link'] === $currentUrl ? 'text-yellow-300 font-semibold' : '' }} text-xs">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        <header class="bg-red-600 text-white px-6 py-3 flex justify-between items-center shadow">
            <input
                type="text"
                placeholder="Search..."
                class="px-4 py-2 rounded-full text-black w-1/3 focus:outline-none"
            >

            <div class="flex items-center gap-3 relative">
                <div class="relative">
                    <button id="notifBtn" type="button" class="text-xl hover:bg-red-500 p-2 rounded-lg transition">
                        &#128276;
                    </button>

                    <div id="notifDropdown" class="hidden absolute right-0 mt-3 w-72 bg-white rounded-2xl shadow-xl border z-50 overflow-hidden">
                        <div class="px-4 py-3 font-semibold border-b text-gray-800">
                            Notifications
                        </div>

                        <div class="max-h-64 overflow-y-auto">
                            <div class="px-4 py-3 hover:bg-gray-100 text-sm text-gray-700">
                                No notifications yet
                            </div>
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
                            <span>&#128100;</span>
                            <span class="text-gray-700">Profile Settings</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left flex items-center gap-3 px-5 py-3 text-red-500 hover:bg-gray-100 transition">
                                <span>&#8617;</span>
                                <span>Log Out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <section class="bg-white rounded-[28px] shadow-sm border border-gray-100 p-6 xl:p-8">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h1 class="text-[32px] font-bold tracking-tight text-gray-900">Meetings & Video Conference</h1>
                        <p class="mt-2 text-sm text-gray-500">Organize SK meetings and conduct virtual conferences</p>
                    </div>

                    <button id="openModalBtn" type="button" class="inline-flex items-center justify-center rounded-xl bg-[#d90f1f] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#b90e1b]">
                        + Schedule Meeting
                    </button>
                </div>

                @if (session('status'))
                    <div class="mt-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('warning'))
                    <div class="mt-6 rounded-2xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-700">
                        {{ session('warning') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="mt-8 mx-auto flex w-full max-w-md items-center justify-between rounded-full bg-[#f6f7fb] p-1 text-xs font-semibold text-gray-500">
                    <button id="scheduleTabBtn" type="button" class="tab-btn flex-1 rounded-full px-4 py-2 bg-white text-gray-900 shadow-sm">
                        Meeting Schedule
                    </button>
                    <button id="conferenceTabBtn" type="button" class="tab-btn flex-1 rounded-full px-4 py-2">
                        Video Conference
                    </button>
                </div>

                <div id="scheduleTab" class="mt-8 space-y-6">
                    <div class="rounded-[24px] border border-gray-100 bg-[#fbfbfd] p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900">Upcoming Meetings</h2>
                                <p class="text-xs text-gray-500">Scheduled meetings that have not started yet</p>
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
                                            @if($meeting->location_or_link)
                                                <p class="mt-1 text-xs text-gray-500">{{ $meeting->location_or_link }}</p>
                                            @endif
                                            <p class="mt-2 text-xs text-gray-400">{{ $meeting->agenda ?: 'No agenda provided yet.' }}</p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-[11px] font-semibold text-blue-600">{{ $meeting->status_label }}</span>
                                    </div>
                                    <div class="mt-4">
                                        <span class="inline-flex items-center rounded-xl bg-gray-100 px-4 py-2 text-xs font-semibold text-gray-500">Available when meeting starts</span>
                                        <button type="button"
                                            class="editMeetingBtn ml-2 inline-flex items-center rounded-xl bg-red-50 px-4 py-2 text-xs font-semibold text-[#d90f1f] transition hover:bg-red-100"
                                            data-action="{{ route('sk_pres.meetings.update',$meeting->meeting_id) }}"
                                            data-title="{{ $meeting->title }}"
                                            data-agenda="{{ $meeting->agenda }}"
                                            data-location="{{ $meeting->location_or_link }}"
                                            data-date="{{ $meeting->meeting_date }}"
                                            data-time="{{ \Illuminate\Support\Str::of($meeting->meeting_time)->substr(0,5) }}">
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
                            <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-600">{{ $activeMeetings->count() }} ongoing</span>
                        </div>

                        <div class="mt-4 space-y-3">
                            @forelse ($activeMeetings as $meeting)
                                <div class="rounded-2xl border border-red-100 bg-white p-4 shadow-sm">
                                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900">{{ $meeting->title }}</p>
                                            <p class="mt-1 text-xs text-gray-500">{{ $meeting->preview_datetime }}</p>
                                            @if($meeting->location_or_link)
                                                <p class="mt-1 text-xs text-gray-500">{{ $meeting->location_or_link }}</p>
                                            @endif
                                            <p class="mt-2 text-xs text-gray-400">{{ $meeting->agenda ?: 'No agenda provided yet.' }}</p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-red-50 px-3 py-1 text-[11px] font-semibold text-red-600">{{ $meeting->status_label }}</span>
                                    </div>

                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <a href="{{ route('sk_pres.meetings.call',$meeting->meeting_id) }}" class="inline-flex items-center rounded-xl bg-[#d90f1f] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#b90e1b]">Join Video Call</a>
                                        <button type="button"
                                            class="editMeetingBtn inline-flex items-center rounded-xl bg-red-50 px-4 py-2 text-xs font-semibold text-[#d90f1f] transition hover:bg-red-100"
                                            data-action="{{ route('sk_pres.meetings.update',$meeting->meeting_id) }}"
                                            data-title="{{ $meeting->title }}"
                                            data-agenda="{{ $meeting->agenda }}"
                                            data-location="{{ $meeting->location_or_link }}"
                                            data-date="{{ $meeting->meeting_date }}"
                                            data-time="{{ \Illuminate\Support\Str::of($meeting->meeting_time)->substr(0,5) }}">
                                            Edit Info
                                        </button>
                                        <form method="POST" action="{{ route('sk_pres.meetings.finish',$meeting->meeting_id) }}" onsubmit="return confirm('Finish this meeting? Video-call attendance will stop accepting new participants and attendance can then be finalized.');">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center rounded-xl bg-gray-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-gray-700">Finish Meeting</button>
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
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">{{ $pastMeetings->count() }} past</span>
                        </div>

                        <div class="mt-4 space-y-3">
                            @forelse ($pastMeetings as $meeting)
                                <div class="rounded-2xl border border-gray-100 bg-white p-4">
                                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $meeting->title }}</p>
                                            <p class="mt-1 text-xs text-gray-500">{{ $meeting->preview_datetime }}</p>
                                            @if($meeting->location_or_link)
                                                <p class="mt-1 text-xs text-gray-500">{{ $meeting->location_or_link }}</p>
                                            @endif

                                            @if($meeting->status==='completed' && $meeting->attendance_finalized)
                                                <div class="mt-3 flex flex-wrap gap-2">
                                                    <span class="rounded-full bg-green-50 px-3 py-1 text-[11px] font-semibold text-green-600">{{ $meeting->attendance_present_count }} present</span>
                                                    <span class="rounded-full bg-red-50 px-3 py-1 text-[11px] font-semibold text-red-600">{{ $meeting->attendance_absent_count }} absent</span>
                                                </div>
                                            @endif
                                        </div>

                                        <span class="rounded-full {{ $meeting->status==='cancelled' ? 'bg-red-50 text-red-600' : 'bg-gray-100 text-gray-600' }} px-3 py-1 text-[11px] font-semibold">{{ $meeting->status_label }}</span>
                                    </div>

                                    @if($meeting->status==='completed')
                                        <div class="mt-4 flex flex-wrap items-center gap-2">
                                            @if($meeting->attendance_finalized)
                                                <span class="inline-flex items-center rounded-xl bg-green-50 px-4 py-2 text-xs font-semibold text-green-700">Attendance Recorded</span>
                                            @else
                                                <button type="button" class="recordAttendanceBtn inline-flex items-center rounded-xl bg-[#d90f1f] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#b90e1b]" data-title="{{ $meeting->title }}" data-action="{{ route('sk_pres.meetings.attendance',$meeting->meeting_id) }}">Record Attendance</button>
                                                <span class="text-[11px] text-gray-400">Face-to-face attendance is selected manually. Video-call attendance is included automatically.</span>
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

                <div id="conferenceTab" class="mt-8 hidden">
                    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_240px]">
                        <div class="rounded-[24px] bg-[#1b2230] p-4 text-white shadow-inner">
                            <div class="flex items-center justify-between">
                                <span class="rounded-full bg-red-500/20 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-red-200">Live</span>
                                <span class="text-xs text-white/60">Agora Preview</span>
                            </div>

                            <div class="mt-4 flex h-[300px] flex-col items-center justify-center rounded-[20px] bg-[#202938]">
                                @php
                                    $activeMeeting=$activeMeetings->first();
                                    $initials=collect(explode(' ',trim($fullName)))->filter()->map(fn($part)=>strtoupper(substr($part,0,1)))->take(2)->implode('');
                                @endphp
                                <div class="flex h-24 w-24 items-center justify-center rounded-full bg-[#eb5757] text-2xl font-bold">{{ $initials ?: 'SK' }}</div>
                                <p class="mt-4 text-lg font-semibold">{{ $fullName }}</p>
                                <span class="mt-2 rounded-full bg-green-500/20 px-3 py-1 text-[11px] font-semibold text-green-300">{{ $activeMeeting ? 'Ongoing - ready to join' : 'No active room' }}</span>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">
                                @forelse ($activeMeetings->take(4) as $meeting)
                                    <a href="{{ route('sk_pres.meetings.call',$meeting->meeting_id) }}" class="rounded-2xl bg-[#273042] p-3 transition hover:bg-[#2d384d]">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#ef4444] text-sm font-bold">{{ strtoupper(substr($meeting->title,0,2)) }}</div>
                                        <p class="mt-3 truncate text-xs font-semibold">{{ $meeting->title }}</p>
                                        <p class="mt-1 text-[10px] text-white/50">Join ongoing call</p>
                                    </a>
                                @empty
                                    <div class="col-span-full rounded-2xl border border-dashed border-white/15 px-4 py-8 text-center text-sm text-white/60">No ongoing meeting rooms available.</div>
                                @endforelse
                            </div>

                            <div class="mt-4 flex items-center justify-center gap-3 rounded-2xl bg-white/5 px-4 py-3">
                                <span class="rounded-xl bg-white/10 px-3 py-2 text-xs">Mic</span>
                                <span class="rounded-xl bg-white/10 px-3 py-2 text-xs">Cam</span>
                                <a href="{{ $activeMeeting ? route('sk_pres.meetings.call',$activeMeeting->meeting_id) : '#' }}" class="rounded-xl bg-[#ef4444] px-4 py-2 text-xs font-semibold text-white {{ $activeMeeting ? '' : 'pointer-events-none opacity-50' }}">Join Active Meeting</a>
                                <span class="rounded-xl bg-white/10 px-3 py-2 text-xs">More</span>
                            </div>
                        </div>

                        <div class="rounded-[24px] border border-gray-100 bg-white p-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-900">Active Meetings</h3>
                                <span class="text-xs text-gray-400">{{ $activeMeetings->count() }} active</span>
                            </div>

                            <div class="meeting-scrollbar mt-4 space-y-3 max-h-[460px] overflow-y-auto pr-1">
                                @forelse ($activeMeetings as $meeting)
                                    <div class="flex items-start gap-3 rounded-2xl border border-gray-100 p-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#fce8ea] text-xs font-bold text-[#d90f1f]">{{ strtoupper(substr($meeting->title,0,2)) }}</div>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-gray-800">{{ $meeting->title }}</p>
                                            <p class="mt-1 text-[11px] text-gray-400">{{ $meeting->preview_datetime }}</p>
                                            <div class="mt-2 flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-green-500"></span><span class="text-[11px] text-green-600">{{ $meeting->status_label }}</span></div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-gray-200 px-4 py-8 text-center text-sm text-gray-500">No ongoing meeting rooms.</div>
                                @endforelse
                            </div>
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

        <form id="meetingForm" action="{{ route('sk_pres.meetings.store') }}" method="POST" class="mt-8 space-y-5">
            @csrf
            <input id="meetingFormMethod" type="hidden" name="_method" value="PUT" disabled>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-800">Meeting Title</label>
                <input id="meetingTitle" type="text" name="title" value="{{ old('title') }}" class="h-12 w-full rounded-xl border border-red-100 bg-[#fff7f7] px-4 text-sm text-gray-700 outline-none transition focus:border-[#d90f1f] focus:bg-white" required>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-800">Agenda</label>
                <textarea id="meetingAgenda" name="agenda" rows="4" class="w-full rounded-xl border border-red-100 bg-[#fff7f7] px-4 py-3 text-sm text-gray-700 outline-none transition focus:border-[#d90f1f] focus:bg-white">{{ old('agenda') }}</textarea>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-gray-800">Conference Location or Link</label>
                <input id="meetingLocation" type="text" name="location_or_link" value="{{ old('location_or_link') }}" class="h-12 w-full rounded-xl border border-red-100 bg-[#fff7f7] px-4 text-sm text-gray-700 outline-none transition focus:border-[#d90f1f] focus:bg-white" placeholder="Room, venue, or conference link">
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-800">Date</label>
                    <input id="meetingDate" type="date" name="meeting_date" value="{{ old('meeting_date') }}" class="h-12 w-full rounded-xl border border-red-100 bg-[#fff7f7] px-4 text-sm text-gray-700 outline-none transition focus:border-[#d90f1f] focus:bg-white" required>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-800">Time</label>
                    <input id="meetingTime" type="time" name="meeting_time" value="{{ old('meeting_time') }}" class="h-12 w-full rounded-xl border border-red-100 bg-[#fff7f7] px-4 text-sm text-gray-700 outline-none transition focus:border-[#d90f1f] focus:bg-white" required>
                </div>
            </div>

            <button id="meetingSubmitButton" type="submit" class="w-full rounded-xl bg-[#d90f1f] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#b90e1b]">
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
                <p class="mt-2 text-xs text-gray-500">Check barangays that attended face-to-face. Barangays that successfully joined the official SK360 video call are automatically counted as present even if left unchecked here.</p>
            </div>
            <button id="closeAttendanceModalBtn" type="button" class="rounded-xl p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">X</button>
        </div>

        <form id="attendanceForm" method="POST" class="mt-6">
            @csrf

            <label class="mb-4 flex cursor-pointer items-center gap-3 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 transition hover:bg-red-100">
                <input id="selectAllAttendance" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                <div>
                    <p class="text-sm font-semibold text-red-700">Select All Present</p>
                    <p class="text-[11px] text-red-500">Check all barangays as face-to-face attendees.</p>
                </div>
            </label>

            <div class="meeting-scrollbar grid max-h-[420px] grid-cols-1 gap-2 overflow-y-auto pr-2 sm:grid-cols-2 lg:grid-cols-3">
                @forelse($attendanceBarangays as $barangay)
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-100 bg-gray-50 px-3 py-3 transition hover:border-red-200 hover:bg-red-50">
                        <input type="checkbox" name="present_barangays[]" value="{{ $barangay->barangay_id }}" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                        <span class="text-xs font-semibold text-gray-700">{{ $barangay->barangay_name }}</span>
                    </label>
                @empty
                    <div class="col-span-full rounded-xl border border-dashed border-gray-200 px-4 py-8 text-center text-sm text-gray-500">No current barangays available for attendance.</div>
                @endforelse
            </div>

            <div class="mt-6 rounded-2xl bg-yellow-50 px-4 py-3 text-xs text-yellow-700">
                Attendance is finalized once submitted. Present barangays receive +5 and barangays with neither face-to-face nor video-call attendance receive -5.
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button id="cancelAttendanceBtn" type="button" class="rounded-xl bg-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-200">Cancel</button>
                <button type="submit" class="rounded-xl bg-[#d90f1f] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#b90e1b]">Finalize Attendance</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');
    const openModalBtn = document.getElementById('openModalBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const scheduleModal = document.getElementById('scheduleModal');
    const meetingForm = document.getElementById('meetingForm');
    const meetingFormMethod = document.getElementById('meetingFormMethod');
    const meetingModalTitle = document.getElementById('meetingModalTitle');
    const meetingModalDescription = document.getElementById('meetingModalDescription');
    const meetingTitle = document.getElementById('meetingTitle');
    const meetingAgenda = document.getElementById('meetingAgenda');
    const meetingLocation = document.getElementById('meetingLocation');
    const meetingDate = document.getElementById('meetingDate');
    const meetingTime = document.getElementById('meetingTime');
    const meetingSubmitButton = document.getElementById('meetingSubmitButton');
    const scheduleTabBtn = document.getElementById('scheduleTabBtn');
    const conferenceTabBtn = document.getElementById('conferenceTabBtn');
    const scheduleTab = document.getElementById('scheduleTab');
    const conferenceTab = document.getElementById('conferenceTab');
    const attendanceModal = document.getElementById('attendanceModal');
    const attendanceForm = document.getElementById('attendanceForm');
    const attendanceMeetingTitle = document.getElementById('attendanceMeetingTitle');
    const closeAttendanceModalBtn = document.getElementById('closeAttendanceModalBtn');
    const cancelAttendanceBtn = document.getElementById('cancelAttendanceBtn');
    const selectAllAttendance = document.getElementById('selectAllAttendance');
    const createMeetingUrl = @js(route('sk_pres.meetings.store'));

    const attendanceCheckboxes=()=>Array.from(
        attendanceForm.querySelectorAll('input[name="present_barangays[]"]')
    );

    const syncSelectAllAttendance=()=>{
        if(!selectAllAttendance) return;

        const checkboxes=attendanceCheckboxes();
        const checkedCount=checkboxes.filter((checkbox)=>checkbox.checked).length;

        selectAllAttendance.checked=checkboxes.length>0 && checkedCount===checkboxes.length;
        selectAllAttendance.indeterminate=checkedCount>0 && checkedCount<checkboxes.length;
    };

    if(selectAllAttendance){
        selectAllAttendance.addEventListener('change',()=>{
            attendanceCheckboxes().forEach((checkbox)=>{
                checkbox.checked=selectAllAttendance.checked;
            });
            selectAllAttendance.indeterminate=false;
        });
    }

    attendanceCheckboxes().forEach((checkbox)=>{
        checkbox.addEventListener('change',syncSelectAllAttendance);
    });

    if (notifBtn && notifDropdown) {
        notifBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            notifDropdown.classList.toggle('hidden');
            userDropdown.classList.add('hidden');
        });
    }

    if (userMenuBtn && userDropdown) {
        userMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('hidden');
            if (notifDropdown) {
                notifDropdown.classList.add('hidden');
            }
        });

        document.addEventListener('click', (e) => {
            if (notifBtn && notifDropdown && !notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
                notifDropdown.classList.add('hidden');
            }

            if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                userDropdown.classList.add('hidden');
            }
        });
    }

    const showScheduleTab = () => {
        scheduleTab.classList.remove('hidden');
        conferenceTab.classList.add('hidden');
        scheduleTabBtn.classList.add('bg-white', 'text-gray-900', 'shadow-sm');
        conferenceTabBtn.classList.remove('bg-white', 'text-gray-900', 'shadow-sm');
    };

    const showConferenceTab = () => {
        conferenceTab.classList.remove('hidden');
        scheduleTab.classList.add('hidden');
        conferenceTabBtn.classList.add('bg-white', 'text-gray-900', 'shadow-sm');
        scheduleTabBtn.classList.remove('bg-white', 'text-gray-900', 'shadow-sm');
    };

    scheduleTabBtn.addEventListener('click', showScheduleTab);
    conferenceTabBtn.addEventListener('click', showConferenceTab);

    const openScheduleModal = () => {
        scheduleModal.classList.remove('hidden');
        scheduleModal.classList.add('flex');
    };

    const closeScheduleModal = () => {
        scheduleModal.classList.add('hidden');
        scheduleModal.classList.remove('flex');
    };

    const openCreateMeetingModal = () => {
        meetingForm.reset();
        meetingForm.action = createMeetingUrl;
        meetingFormMethod.disabled = true;
        meetingModalTitle.textContent = 'Schedule New Meeting';
        meetingModalDescription.textContent = 'Create a meeting and invite participants';
        meetingSubmitButton.textContent = 'Add Event';
        openScheduleModal();
    };

    const openEditMeetingModal = (button) => {
        meetingForm.reset();
        meetingForm.action = button.dataset.action;
        meetingFormMethod.disabled = false;
        meetingModalTitle.textContent = 'Edit Meeting Information';
        meetingModalDescription.textContent = 'Update valid conference information for this meeting.';
        meetingSubmitButton.textContent = 'Save Changes';
        meetingTitle.value = button.dataset.title || '';
        meetingAgenda.value = button.dataset.agenda || '';
        meetingLocation.value = button.dataset.location || '';
        meetingDate.value = button.dataset.date || '';
        meetingTime.value = button.dataset.time || '';
        openScheduleModal();
    };

    openModalBtn.addEventListener('click', openCreateMeetingModal);
    closeModalBtn.addEventListener('click', closeScheduleModal);

    scheduleModal.addEventListener('click', (e) => {
        if (e.target === scheduleModal) {
            closeScheduleModal();
        }
    });

    document.querySelectorAll('.editMeetingBtn').forEach((button)=>{
        button.addEventListener('click',()=>openEditMeetingModal(button));
    });

    const openAttendanceModal=(button)=>{
        attendanceForm.action=button.dataset.action;
        attendanceMeetingTitle.textContent=button.dataset.title;
        attendanceCheckboxes().forEach((checkbox)=>checkbox.checked=false);

        if(selectAllAttendance){
            selectAllAttendance.checked=false;
            selectAllAttendance.indeterminate=false;
        }

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

    if(closeAttendanceModalBtn) closeAttendanceModalBtn.addEventListener('click',closeAttendanceModal);
    if(cancelAttendanceBtn) cancelAttendanceBtn.addEventListener('click',closeAttendanceModal);
    if(attendanceModal){
        attendanceModal.addEventListener('click',(e)=>{
            if(e.target===attendanceModal) closeAttendanceModal();
        });
    }

    @if ($errors->any())
        openScheduleModal();
    @endif
</script>
@endpush
