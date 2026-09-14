{{-- File guide: Blade view template for resources/views/sk_chairman/home.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK Chairman Dashboard')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
<div class="flex h-screen bg-[#f1f5f9] overflow-hidden">
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
        <div class="flex items-center gap-3 mb-4">
    <img src="{{ asset('images/sk logo.png') }}" class="w-8 h-8 rounded-full object-cover"  alt="logo">
    <div class="leading-tight">
        <h2 class="text-lg font-extrabold tracking-wide">SK 360°</h2>
        <p class="text-[10px] opacity-80">Management System</p>
    </div>
</div>

        @include('shared.sidebar-user-card')

        <nav class="space-y-1 text-xs">
            @foreach ($menuItems as $item)
                @php $isActive = $item['link'] === $currentUrl; @endphp
                <a href="{{ $item['link'] }}" class="flex items-center gap-2 p-2 rounded-lg {{ $isActive ? 'bg-red-500' : 'hover:bg-red-500 transition' }}">
                    <span class="{{ $isActive ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded text-sm">{!! $item['icon'] !!}</span>
                    <span class="{{ $isActive ? 'text-yellow-300 font-semibold' : '' }} text-xs">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="flex-1 flex flex-col">
        @include('shared.topbar', ['legacyAccountMenu' => false])

        <div class="p-6 overflow-y-auto bg-[#f8fafc]">
            <h1 class="text-2xl font-bold mb-4">Good morning, {{ $firstName }}!</h1>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                @foreach ($summaryCards as $card)
                    <div class="{{ $card['classes'] }} p-5 rounded-xl shadow">
                        <h2 class="text-2xl font-bold">{{ $card['value'] }}</h2>
                        <p class="text-sm">{{ $card['label'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    @include('shared.wall-feed')
                </div>

                <div class="space-y-6">
                    <div class="bg-white p-4 rounded-xl shadow border">
                        <h2 class="font-bold text-gray-700 uppercase text-[10px] mb-3 tracking-widest border-b pb-2">Quick Actions</h2>
                        <div class="grid grid-cols-3 gap-2">
                            <a href="{{ route('sk_chairman.reports') }}" class="flex aspect-square flex-col items-center justify-center rounded-lg bg-red-600 p-3 text-white transition hover:bg-red-700">
                                <span class="text-xl">&#128196;</span>
                                <span class="mt-1 text-center text-[8px] font-bold">REPORT</span>
                            </a>
                            <a href="{{ route('sk_chairman.budget') }}" class="flex aspect-square flex-col items-center justify-center rounded-lg bg-blue-600 p-3 text-white transition hover:bg-blue-700">
                                <span class="text-xl">&#128229;</span>
                                <span class="mt-1 text-center text-[8px] font-bold">BUDGET</span>
                            </a>
                            <a href="{{ route('sk_chairman.meetings') }}" class="flex aspect-square flex-col items-center justify-center rounded-lg bg-yellow-500 p-3 text-white transition hover:bg-yellow-600">
                                <span class="text-xl">&#128222;</span>
                                <span class="mt-1 text-center text-[8px] font-bold">MEETING</span>
                            </a>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-xl shadow border">
                        <h2 class="font-bold text-gray-700 uppercase text-[10px] mb-3 tracking-widest border-b pb-2">Calendar Preview</h2>
                        <div class="space-y-4">
                            @forelse ($upcomingEvents as $event)
                                <div class="border-l-4 border-red-500 pl-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-[11px] font-black text-gray-800 uppercase leading-none">{{ $event->title }}</p>
                                        <span class="rounded-full px-2 py-1 text-[8px] font-bold uppercase {{ $event->type_badge }}">
                                            {{ $event->type_label }}
                                        </span>
                                    </div>
                                    <p class="text-[9px] text-gray-500 mt-2">{{ \Carbon\Carbon::parse($event->start_datetime)->format('M d, Y h:i A') }}</p>
                                    <p class="text-[9px] text-gray-400 mt-1">{{ $event->location ?: 'No location provided' }}</p>
                                </div>
                            @empty
                                <p class="text-[10px] text-gray-400 italic">No scheduled events.</p>
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
<script>
    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');

    notifBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        notifDropdown.classList.toggle('hidden');
        userDropdown.classList.add('hidden');
    });

    userMenuBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        userDropdown.classList.toggle('hidden');
        notifDropdown.classList.add('hidden');
    });

    document.addEventListener('click', function (e) {
        if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
            notifDropdown.classList.add('hidden');
        }

        if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.classList.add('hidden');
        }
    });
</script>
@endpush

