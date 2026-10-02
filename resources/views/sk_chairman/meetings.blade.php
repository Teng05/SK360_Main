{{-- File guide: Blade view template for resources/views/sk_chairman/meetings.blade.php. --}}
@extends('layouts.app')
@section('title','SK 360° | Meetings')
@section('page_css')
<script src="https://cdn.tailwindcss.com"></script>
@endsection
@section('content')
<div class="flex h-screen bg-gray-100 overflow-hidden">
    @include('partials.app.sidebar')
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        @include('partials.app.topbar')
        <main class="flex-1 overflow-y-auto p-8">
            <section class="bg-white rounded-[28px] shadow-sm border border-gray-100 p-6 xl:p-8">
                <div>
                    <span class="sk-eyebrow"><span class="sk-dot"></span>Meetings</span>
                    <h1 class="text-[32px] font-bold tracking-tight text-gray-900">Meetings</h1>
                    <p class="mt-2 text-sm text-gray-500">View president-created meetings and join an ongoing call when needed.</p>
                </div>

                @if(session('status'))
                    <div class="mt-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
                @endif
                @if(session('warning'))
                    <div class="mt-6 rounded-2xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-700">{{ session('warning') }}</div>
                @endif

                <div class="mt-8 space-y-6">
                    <div class="rounded-[24px] border border-gray-100 bg-[#fbfbfd] p-5">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900">Upcoming Meetings</h2>
                                <p class="text-xs text-gray-500">Scheduled meetings that have not started yet</p>
                            </div>
                            <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-[#d90f1f]">{{ $upcomingMeetings->count() }} upcoming</span>
                        </div>
                        <div class="mt-4 space-y-3">
                            @forelse($upcomingMeetings as $meeting)
                                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900">{{ $meeting->title }}</p>
                                            <p class="mt-1 text-xs text-gray-500">{{ $meeting->preview_datetime }}</p>
                                            <p class="mt-2 text-xs text-gray-400">{{ $meeting->agenda ?: 'No agenda provided yet.' }}</p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-[11px] font-semibold text-blue-600">{{ $meeting->status_label }}</span>
                                    </div>
                                    <div class="mt-4">
                                        <span class="inline-flex items-center rounded-xl bg-gray-100 px-4 py-2 text-xs font-semibold text-gray-500">Available when meeting starts</span>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-10 text-center">
                                    <p class="text-sm font-semibold text-gray-700">No upcoming meetings.</p>
                                    <p class="mt-2 text-xs text-gray-500">Wait for the SK President to schedule a meeting.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-[24px] border border-red-100 bg-red-50/40 p-5">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900">Ongoing Meetings</h2>
                                <p class="text-xs text-gray-500">Join the official call if your barangay cannot attend face-to-face</p>
                            </div>
                            <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-600">{{ $activeMeetings->count() }} ongoing</span>
                        </div>
                        <div class="mt-4 space-y-3">
                            @forelse($activeMeetings as $meeting)
                                <div class="rounded-2xl border border-red-100 bg-white p-4 shadow-sm">
                                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900">{{ $meeting->title }}</p>
                                            <p class="mt-1 text-xs text-gray-500">{{ $meeting->preview_datetime }}</p>
                                            <p class="mt-2 text-xs text-gray-400">{{ $meeting->agenda ?: 'No agenda provided yet.' }}</p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-red-50 px-3 py-1 text-[11px] font-semibold text-red-600">{{ $meeting->status_label }}</span>
                                    </div>
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <a href="{{ route('sk_chairman.meetings.call',$meeting->meeting_id) }}" class="inline-flex items-center gap-2 rounded-xl bg-[#d90f1f] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#b90e1b]">
                                            @include('partials.ui.icon',['icon'=>'video','iconSize'=>15])
                                            Join Video Call
                                        </a>
                                        <span class="inline-flex items-center rounded-xl bg-green-50 px-4 py-2 text-xs font-semibold text-green-700">Successful call join counts as present</span>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-red-100 bg-white px-6 py-10 text-center">
                                    <p class="text-sm font-semibold text-gray-700">No ongoing meetings.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-[24px] border border-gray-100 bg-[#fbfbfd] p-5">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900">Past Meetings</h2>
                                <p class="text-xs text-gray-500">Completed and cancelled meeting records</p>
                            </div>
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">{{ $pastMeetings->count() }} past</span>
                        </div>
                        <div class="mt-4 space-y-3">
                            @forelse($pastMeetings as $meeting)
                                <div class="rounded-2xl border border-gray-100 bg-white p-4">
                                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $meeting->title }}</p>
                                            <p class="mt-1 text-xs text-gray-500">{{ $meeting->preview_datetime }}</p>
                                        </div>
                                        <span class="rounded-full {{ $meeting->status==='cancelled' ? 'bg-red-50 text-red-600' : 'bg-gray-100 text-gray-600' }} px-3 py-1 text-[11px] font-semibold">{{ $meeting->status_label }}</span>
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
            </section>
        </main>
    </div>
</div>
@endsection
@push('scripts')
<script>
const notifBtn=document.getElementById('notifBtn');
const notifDropdown=document.getElementById('notifDropdown');
const userMenuBtn=document.getElementById('userMenuBtn');
const userDropdown=document.getElementById('userDropdown');
if(notifBtn&&notifDropdown){
    notifBtn.addEventListener('click',e=>{e.stopPropagation();notifDropdown.classList.toggle('hidden');userDropdown?.classList.add('hidden');});
}
if(userMenuBtn&&userDropdown){
    userMenuBtn.addEventListener('click',e=>{e.stopPropagation();userDropdown.classList.toggle('hidden');notifDropdown?.classList.add('hidden');});
}
document.addEventListener('click',e=>{
    if(notifBtn&&notifDropdown&&!notifBtn.contains(e.target)&&!notifDropdown.contains(e.target))notifDropdown.classList.add('hidden');
    if(userMenuBtn&&userDropdown&&!userMenuBtn.contains(e.target)&&!userDropdown.contains(e.target))userDropdown.classList.add('hidden');
});
</script>
@endpush
