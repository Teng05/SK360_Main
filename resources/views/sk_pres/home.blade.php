{{-- File guide: Blade view template for resources/views/sk_pres/home.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 Dashboard')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
<div class="flex h-screen bg-gray-100">
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
        <div class="flex items-center gap-3 mb-4">
    <img src="{{ asset('images/logo.png') }}" class="w-8 h-8 rounded-full object-cover"  alt="logo">
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
            @foreach ($menuItems as $item)
                @php
                    $isActive = $item['link'] === $currentUrl;
                @endphp
                <a href="{{ $item['link'] }}" class="flex items-center gap-2 p-2 rounded-lg {{ $isActive ? 'bg-red-500' : 'hover:bg-red-500 transition' }}">
                    <span class="{{ $isActive ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded text-sm">{!! $item['icon'] !!}</span>
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

        <div class="p-6 overflow-y-auto">
            <h1 class="text-2xl font-bold mb-4">
                Good morning, <span>{{ $fullName }}</span>!
            </h1>

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
                            <button id="postAnnouncement" type="button" class="flex aspect-square flex-col items-center justify-center rounded-lg bg-red-600 p-3 text-white transition hover:bg-red-700">
                                <span class="text-xl">&#128226;</span>
                                <span class="mt-1 text-center text-[8px] font-bold">POST</span>
                            </button>
                            <a href="{{ route('sk_pres.calendar') }}" class="flex aspect-square flex-col items-center justify-center rounded-lg bg-yellow-500 p-3 text-white transition hover:bg-yellow-600">
                                <span class="text-xl">&#128197;</span>
                                <span class="mt-1 text-center text-[8px] font-bold">EVENTS</span>
                            </a>
                            <a href="{{ route('sk_pres.meetings') }}" class="flex aspect-square flex-col items-center justify-center rounded-lg bg-blue-600 p-3 text-white transition hover:bg-blue-700">
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

            <div id="announcementModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 px-4">
                <div class="bg-white w-full max-w-2xl rounded-[28px] shadow-2xl p-6 md:p-7 relative">
                    <button id="closeAnnouncementModal" type="button" class="absolute top-5 right-5 text-gray-500 hover:text-red-500 text-2xl leading-none">
                        ×
                    </button>

                    <h2 class="text-3xl font-bold text-gray-900 mb-1">Create Post</h2>
                    <p class="text-gray-500 mb-7">Share an update with the SK community</p>

                    <form action="{{ route('wall.posts.store') }}" method="POST" class="space-y-5">
                        @csrf
                        <input type="hidden" name="post_category" id="selectedPostCategory" value="announcement">

                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-2">Post Category</label>

                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    type="button"
                                    id="categoryAnnouncement"
                                    data-category="announcement"
                                    class="post-category-btn flex items-center justify-center gap-2 rounded-lg py-3 border font-semibold transition"
                                >
                                    <span>📣</span>
                                    <span>Announcement</span>
                                </button>

                                <button
                                    type="button"
                                    id="categoryAccomplishment"
                                    data-category="accomplishment"
                                    class="post-category-btn flex items-center justify-center gap-2 rounded-lg py-3 border font-semibold transition"
                                >
                                    <span>🏆</span>
                                    <span>Accomplishment</span>
                                </button>

                                <button
                                    type="button"
                                    id="categoryEvent"
                                    data-category="event"
                                    class="post-category-btn flex items-center justify-center gap-2 rounded-lg py-3 border font-semibold transition"
                                >
                                    <span>📅</span>
                                    <span>Event</span>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-2">Post Content</label>
                            <textarea
                                name="post_content"
                                rows="4"
                                placeholder="Share your thoughts..."
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-400 resize-none"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-2">Attach Media (Optional)</label>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <label class="flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white py-3 px-4 cursor-pointer hover:bg-gray-50 transition">
                                    <span>🖼️</span>
                                    <span class="font-medium text-gray-700">Add Image</span>
                                    <input type="file" name="post_image" accept="image/*" class="hidden">
                                </label>

                                <label class="flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white py-3 px-4 cursor-pointer hover:bg-gray-50 transition">
                                    <span>📎</span>
                                    <span class="font-medium text-gray-700">Attach File</span>
                                    <input type="file" name="post_file" accept=".pdf" class="hidden">
                                </label>
                            </div>

                            <p class="text-xs text-gray-400 mt-2">
                                Supported: Images (JPG, PNG) and Documents (PDF)
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-2">Post Visibility</label>

                            <div class="relative">
                                <select name="post_visibility" class="w-full appearance-none rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-red-400">
                                    <option value="all_sk_councils">All SK Councils</option>
                                    <option value="chairman_only">SK Chairman Only</option>
                                    <option value="secretary_only">All Users</option>
                                </select>
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">⌄</span>
                            </div>

                            <p class="text-xs text-gray-400 mt-2">Visible to Chairman, Secretary, and Federation</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <button type="button" id="cancelAnnouncementModal" class="border border-gray-300 text-gray-700 font-semibold py-3 rounded-xl hover:bg-gray-50 transition">
                                Cancel
                            </button>

                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2">
                                <span>＋</span>
                                <span>Publish Post</span>
                            </button>
                        </div>
                    </form>
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

    const postAnnouncement = document.getElementById('postAnnouncement');

    const announcementModal = document.getElementById('announcementModal');
    const closeAnnouncementModal = document.getElementById('closeAnnouncementModal');
    const cancelAnnouncementModal = document.getElementById('cancelAnnouncementModal');

    const selectedPostCategory = document.getElementById('selectedPostCategory');
    const categoryAnnouncement = document.getElementById('categoryAnnouncement');
    const categoryAccomplishment = document.getElementById('categoryAccomplishment');
    const categoryEvent = document.getElementById('categoryEvent');
    const categoryButtons = document.querySelectorAll('.post-category-btn');

    function setActiveCategory(category) {
        selectedPostCategory.value = category;

        categoryButtons.forEach((button) => {
            button.className = 'post-category-btn flex items-center justify-center gap-2 rounded-lg py-3 border font-semibold transition bg-white text-gray-700 border-gray-300';
        });

        if (category === 'announcement') {
            categoryAnnouncement.className = 'post-category-btn flex items-center justify-center gap-2 rounded-lg py-3 border font-semibold transition bg-yellow-400 border-yellow-400 text-white';
        } else if (category === 'accomplishment') {
            categoryAccomplishment.className = 'post-category-btn flex items-center justify-center gap-2 rounded-lg py-3 border font-semibold transition bg-red-500 border-red-500 text-white';
        } else if (category === 'event') {
            categoryEvent.className = 'post-category-btn flex items-center justify-center gap-2 rounded-lg py-3 border font-semibold transition bg-blue-500 border-blue-500 text-white';
        }
    }

    function openPostModal(category) {
        setActiveCategory(category);
        announcementModal.classList.remove('hidden');
        announcementModal.classList.add('flex');
    }

    function closePostModal() {
        announcementModal.classList.add('hidden');
        announcementModal.classList.remove('flex');
    }

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

    postAnnouncement.addEventListener('click', function () {
        openPostModal('announcement');
    });

    categoryAnnouncement.addEventListener('click', function () {
        setActiveCategory('announcement');
    });

    categoryAccomplishment.addEventListener('click', function () {
        setActiveCategory('accomplishment');
    });

    categoryEvent.addEventListener('click', function () {
        setActiveCategory('event');
    });

    closeAnnouncementModal.addEventListener('click', closePostModal);
    cancelAnnouncementModal.addEventListener('click', closePostModal);

    announcementModal.addEventListener('click', function (e) {
        if (e.target === announcementModal) {
            closePostModal();
        }
    });

    document.addEventListener('click', function (e) {
        if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
            notifDropdown.classList.add('hidden');
        }

        if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.classList.add('hidden');
        }
    });

    setActiveCategory('announcement');
</script>

@endpush
