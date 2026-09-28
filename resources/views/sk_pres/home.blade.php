{{-- File guide: Blade view template for resources/views/sk_pres/home.blade.php. --}}
@extends('layouts.app')
@section('title', 'SK 360 Dashboard')
@section('page_css')
<script src="https://cdn.tailwindcss.com"></script>
@endsection
@section('content')
@php
$cardMeta=[
    'Reports Submitted'=>'Accomplishment and budget reports this term',
    'Community Engagement'=>'Barangays with a submission this term',
    'Pending Reviews'=>'Submitted reports awaiting review',
    'Upcoming Events'=>'Public events that have not ended',
];
@endphp
<div class="flex h-screen bg-gray-100">
    @include('partials.app.sidebar')
    <div class="flex-1 flex flex-col min-w-0">
        @include('partials.app.topbar')
        <div class="flex-1 p-8 overflow-y-auto">
            <div class="sk-page-head">
                <div class="sk-page-head__text">
                    <span class="sk-eyebrow"><span class="sk-dot"></span>{{ now()->format('l, F j, Y') }}</span>
                    <h1 class="sk-page-title">Good morning, <span>{{ $fullName }}</span>!</h1>
                </div>
                <div class="sk-page-head__actions">
                    <a href="{{ route('sk_pres.calendar') }}" class="sk-btn sk-btn--secondary">@include('partials.ui.icon', ['icon'=>'calendar-days','iconSize'=>17]) Events</a>
                    <a href="{{ route('sk_pres.meetings') }}" class="sk-btn sk-btn--secondary">@include('partials.ui.icon', ['icon'=>'video','iconSize'=>17]) Meeting</a>
                    <a href="{{ route('sk_pres.announcements') }}" class="sk-btn sk-btn--primary">@include('partials.ui.icon', ['icon'=>'megaphone','iconSize'=>17]) Post</a>
                </div>
            </div>
            @include('partials.app.summary-cards')
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">@include('shared.wall-feed')</div>
                <div class="space-y-6">@include('partials.app.calendar-preview', ['calendarUrl'=>route('sk_pres.calendar')])</div>
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
notifBtn?.addEventListener('click',e=>{e.stopPropagation();notifDropdown.classList.toggle('hidden');userDropdown.classList.add('hidden');});
userMenuBtn?.addEventListener('click',e=>{e.stopPropagation();userDropdown.classList.toggle('hidden');notifDropdown.classList.add('hidden');});
document.addEventListener('click',e=>{if(notifBtn&&!notifBtn.contains(e.target)&&!notifDropdown.contains(e.target))notifDropdown.classList.add('hidden');if(userMenuBtn&&!userMenuBtn.contains(e.target)&&!userDropdown.contains(e.target))userDropdown.classList.add('hidden');});
</script>
@endpush
