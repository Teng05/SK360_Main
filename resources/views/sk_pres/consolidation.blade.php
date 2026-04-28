@extends('layouts.app')

@section('title', 'Report Consolidation')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
<div class="flex h-screen overflow-hidden bg-gray-100">
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
                <a href="{{ $item['link'] }}"
                   class="flex items-center gap-2 p-2 rounded-lg {{ $item['link'] === $currentUrl ? 'bg-red-500' : 'hover:bg-red-500 transition' }}">
                    <span class="{{ $item['link'] === $currentUrl ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded text-sm">{{ $item['icon'] }}</span>
                    <span class="{{ $item['link'] === $currentUrl ? 'text-yellow-300 font-semibold' : '' }} text-xs">{{ $item['label'] }}</span>
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

        <main class="flex-1 overflow-y-auto p-10 bg-gray-100">
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-2">Report Consolidation</h1>
                <p class="text-gray-600 text-lg">
                    Centralized view of all barangay submissions and report compilation
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
                @foreach ($stats as $stat)
                    <div class="bg-white rounded-2xl p-5 shadow-sm">
                        <p class="text-sm text-gray-500 mb-2">{{ $stat['label'] }}</p>
                        <h2 class="text-4xl font-bold {{ $stat['valueClass'] }}">{{ $stat['value'] }}</h2>
                    </div>
                @endforeach
            </div>

            <section class="bg-white rounded-2xl shadow-sm p-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Barangay Submissions</h2>
                        <p class="text-gray-500 text-sm">
                            Track and consolidate reports from all barangays
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-xl text-sm font-medium">
                            📄 Generate Report
                        </button>
                        <button class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl text-sm font-medium">
                            ⬇ Download All
                        </button>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-5">
                    <div class="w-full max-w-sm">
                        <div class="border rounded-xl px-4 py-3 flex items-center gap-3">
                            <span class="text-gray-400">🔎</span>
                            <input
                                type="text"
                                placeholder="Search barangay..."
                                class="w-full outline-none text-sm"
                            >
                        </div>
                    </div>

                    <div class="w-full md:w-48">
                        <select class="w-full border rounded-xl px-4 py-3 text-sm text-gray-600 outline-none bg-white">
                            @foreach ($months as $month)
                                <option>{{ $month }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border-separate border-spacing-y-2">
                        <thead>
                            <tr class="text-gray-500">
                                <th class="px-4 py-3">Barangay</th>
                                <th class="px-4 py-3">Monthly</th>
                                <th class="px-4 py-3">Quarterly</th>
                                <th class="px-4 py-3">Annual</th>
                                <th class="px-4 py-3">Last Submission</th>
                                <th class="px-4 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($submissions as $submission)
                                <tr class="bg-gray-50">
                                    <td class="px-4 py-4 rounded-l-xl">{{ $submission['barangay'] }}</td>
                                    <td class="px-4 py-4">{{ $submission['monthly'] }}</td>
                                    <td class="px-4 py-4">{{ $submission['quarterly'] }}</td>
                                    <td class="px-4 py-4">{{ $submission['annual'] }}</td>
                                    <td class="px-4 py-4">{{ $submission['last_submission'] }}</td>
                                    <td class="px-4 py-4 rounded-r-xl text-center">{{ $submission['actions'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                                        No barangay submissions yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
