{{-- File guide: Blade view template for resources/views/sk_pres/announcement.blade.php. --}}
@extends('layouts.app')

@section('title', 'Announcements')

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
                <a href="{{ $item['link'] }}" class="flex items-center gap-2 p-2 rounded-lg {{ $item['link'] === $currentUrl ? 'bg-red-500' : 'hover:bg-red-500 transition' }}">
                    <span class="{{ $item['link'] === $currentUrl ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded text-sm">{{ $item['icon'] }}</span>
                    <span class="{{ $item['link'] === $currentUrl ? 'text-yellow-300 font-semibold' : '' }} text-xs">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="flex-1 flex flex-col">
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

        <main class="flex-1 overflow-y-auto p-10 bg-gray-100">
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-2">Announcements</h1>
                <p class="text-gray-600 text-lg">Official communications and updates for SK federation</p>
            </div>

            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="flex justify-end mb-6">
                <button id="openAnnouncementModalBtn" type="button" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl text-sm font-medium flex items-center gap-2">
                    <span>+</span>
                    <span>New Announcement</span>
                </button>
            </div>

            <div id="announcementModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50 px-4">
                <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl p-8 relative">
                    <button id="closeAnnouncementModalBtn" type="button" class="absolute top-4 right-5 text-gray-500 hover:text-red-600 text-2xl font-bold">
                        &times;
                    </button>

                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Create Announcement</h2>
                    <p class="text-gray-600 mb-8">Publish an update for the SK federation.</p>

                    <form method="POST" action="{{ route('sk_pres.announcements.store') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Title</label>
                            <input
                                type="text"
                                name="title"
                                value="{{ old('title') }}"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-red-400"
                                placeholder="Enter announcement title"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Content</label>
                            <textarea
                                name="content"
                                rows="5"
                                class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-red-400 resize-none"
                                placeholder="Write the announcement details"
                            >{{ old('content') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Visibility</label>
                            <select name="visibility" class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-red-400">
                                <option value="public" @selected(old('visibility') === 'public')>Public</option>
                                <option value="officials_only" @selected(old('visibility') === 'officials_only')>Officials Only</option>
                            </select>
                        </div>

                        <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white py-3 rounded-xl font-semibold">
                            Publish Announcement
                        </button>
                    </form>
                </div>
            </div>

            <div class="space-y-4">
                @forelse ($announcements as $announcement)
                    <article class="bg-white rounded-2xl shadow p-6">
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">{{ $announcement->title }}</h2>
                                <p class="text-sm text-gray-500">
                                    Posted by {{ $announcement->author_name }} on {{ \Illuminate\Support\Carbon::parse($announcement->created_at)->format('M d, Y h:i A') }}
                                </p>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-red-50 px-3 py-1 text-xs font-medium text-red-600">
                                {{ $announcement->visibility === 'officials_only' ? 'Officials Only' : 'Public' }}
                            </span>
                        </div>

                        <p class="text-gray-700 whitespace-pre-line">{{ $announcement->content }}</p>
                    </article>
                @empty
                    <div class="bg-white rounded-2xl shadow p-10 text-center text-gray-400">
                        <div class="text-4xl mb-2">📢</div>
                        <p class="text-lg font-semibold text-gray-600">No announcements yet</p>
                        <p class="text-sm text-gray-400">Create your first announcement to get started</p>
                    </div>
                @endforelse
            </div>
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
    const openAnnouncementModalBtn = document.getElementById('openAnnouncementModalBtn');
    const closeAnnouncementModalBtn = document.getElementById('closeAnnouncementModalBtn');
    const announcementModal = document.getElementById('announcementModal');

    notifBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        notifDropdown.classList.toggle('hidden');
        userDropdown.classList.add('hidden');
    });

    userMenuBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        userDropdown.classList.toggle('hidden');
        notifDropdown.classList.add('hidden');
    });

    document.addEventListener('click', function(e) {
        if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
            notifDropdown.classList.add('hidden');
        }

        if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.classList.add('hidden');
        }
    });

    openAnnouncementModalBtn.addEventListener('click', function () {
        announcementModal.classList.remove('hidden');
        announcementModal.classList.add('flex');
    });

    closeAnnouncementModalBtn.addEventListener('click', function () {
        announcementModal.classList.add('hidden');
        announcementModal.classList.remove('flex');
    });

    announcementModal.addEventListener('click', function (e) {
        if (e.target === announcementModal) {
            announcementModal.classList.add('hidden');
            announcementModal.classList.remove('flex');
        }
    });

    @if ($errors->any())
        announcementModal.classList.remove('hidden');
        announcementModal.classList.add('flex');
    @endif
</script>
@endpush
