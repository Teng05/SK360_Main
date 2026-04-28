@extends('layouts.app')

@section('title', 'SK 360 Dashboard')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
<div class="flex h-screen bg-gray-100">
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
        <div class="flex items-center gap-2 mb-3">
            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" class="w-7 h-7" alt="logo">
            <h2 class="text-base font-bold">SK 360°</h2>
        </div>

        <div class="bg-red-500 rounded-lg p-2 flex items-center gap-2 mb-3 shadow text-xs">
            <div class="bg-yellow-400 text-red-600 p-1 rounded-full text-sm">👤</div>
            <div>
                <p class="font-semibold text-xs">SK President</p>
                <p class="text-xs opacity-80">Active Role</p>
            </div>
        </div>

        <nav class="space-y-1 text-xs">
            @foreach ($menuItems as $item)
                @php
                    $isActive = $item['link'] === $currentUrl;
                @endphp
                <a href="{{ $item['link'] }}" class="flex items-center gap-2 p-2 rounded-lg {{ $isActive ? 'bg-red-500' : 'hover:bg-red-500 transition' }}">
                    <span class="{{ $isActive ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded text-sm">{{ $item['icon'] }}</span>
                    <span class="{{ $isActive ? 'text-yellow-300 font-semibold' : '' }} text-xs">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="flex-1 flex flex-col">
        <div class="bg-red-600 text-white px-6 py-3 flex justify-between items-center shadow">
            <input
                type="text"
                placeholder="Search..."
                class="px-4 py-2 rounded-full text-black w-1/3 focus:outline-none"
            >

            <div class="flex items-center gap-3 relative">
                <div class="relative">
                    <button id="notifBtn" type="button" class="text-xl hover:bg-red-500 p-2 rounded-lg transition">
                        🔔
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
                        <div class="px-5 py-4 font-semibold text-gray-800 border-b">
                            My Account
                        </div>

                        <a href="#" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-100 transition">
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

        <div class="flex-1 bg-gray-100 p-8 overflow-y-auto">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">
                Welcome back, SK President
            </h1>
            <p class="text-gray-600 text-lg mb-8">
                Here's an overview of SK activities and submissions as of {{ $overviewDate }}
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                @foreach ($cards as $card)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-sm text-gray-500">{{ $card['label'] }}</p>
                                <h2 class="text-4xl font-bold text-gray-900 leading-none">{{ $card['value'] }}</h2>
                            </div>
                            <div class="{{ $card['iconWrap'] }} p-3 rounded-xl">
                                <span class="{{ $card['iconClass'] }} text-xl">{{ $card['icon'] }}</span>
                            </div>
                        </div>
                        <div class="text-sm text-gray-500 leading-5 mb-3">
                            <p>{{ $card['subline1'] }}</p>
                            <p>{{ $card['subline2'] }}</p>
                        </div>
                        <p class="text-sm {{ $card['footerClass'] }}">{{ $card['footer'] }}</p>
                    </div>
                @endforeach
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
