{{-- File guide: Blade view template for resources/views/sk_pres/home.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 Dashboard')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
@php
    // Captions describe how HomeController computes each card.
    $cardMeta = [
        'Reports Submitted' => 'Accomplishment and budget reports this term',
        'Community Engagement' => 'Barangays with a submission this term',
        'Pending Reviews' => 'Submitted reports awaiting review',
        'Upcoming Events' => 'Public events that have not ended',
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
                    <h1 class="sk-page-title">
                        Good morning, <span>{{ $fullName }}</span>!
                    </h1>
                </div>

                {{-- Quick actions --}}
                <div class="sk-page-head__actions">
                    <a href="{{ route('sk_pres.calendar') }}" class="sk-btn sk-btn--secondary">
                        @include('partials.ui.icon', ['icon' => 'calendar-days', 'iconSize' => 17])
                        Events
                    </a>
                    <a href="{{ route('sk_pres.meetings') }}" class="sk-btn sk-btn--secondary">
                        @include('partials.ui.icon', ['icon' => 'video', 'iconSize' => 17])
                        Meeting
                    </a>
                    <button id="postAnnouncement" type="button" class="sk-btn sk-btn--primary">
                        @include('partials.ui.icon', ['icon' => 'megaphone', 'iconSize' => 17])
                        Post
                    </button>
                </div>
            </div>

            @include('partials.app.summary-cards')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    @include('shared.wall-feed')
                </div>

                <div class="space-y-6">
                    @include('partials.app.calendar-preview', ['calendarUrl' => route('sk_pres.calendar')])
                </div>
            </div>

            <div id="announcementModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 px-4">
                <div class="bg-white w-full max-w-2xl rounded-[20px] p-6 md:p-7 relative max-h-[92vh] overflow-y-auto">
                    <button id="closeAnnouncementModal" type="button" class="sk-icon-btn sk-modal__close" aria-label="Close">
                        @include('partials.ui.icon', ['icon' => 'x', 'iconSize' => 20])
                    </button>

                    <div class="flex items-start gap-4 mb-6 pr-10">
                        <span class="sk-icon-tile">
                            @include('partials.ui.icon', ['icon' => 'megaphone', 'iconSize' => 21])
                        </span>
                        <div>
                            <h2 class="sk-modal__title">Create Post</h2>
                            <p class="sk-modal__subtitle">Share an update with the SK community</p>
                        </div>
                    </div>

                    <form action="{{ route('wall.posts.store') }}" method="POST" class="space-y-5">
                        @csrf
                        <input type="hidden" name="post_category" id="selectedPostCategory" value="announcement">

                        <div>
                            <label class="sk-label">Post Category</label>

                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    type="button"
                                    id="categoryAnnouncement"
                                    data-category="announcement"
                                    class="post-category-btn flex items-center justify-center gap-2 rounded-xl py-3 border font-semibold transition"
                                >
                                    @include('partials.ui.icon', ['icon' => 'megaphone', 'iconSize' => 17])
                                    <span>Announcement</span>
                                </button>

                                <button
                                    type="button"
                                    id="categoryAccomplishment"
                                    data-category="accomplishment"
                                    class="post-category-btn flex items-center justify-center gap-2 rounded-xl py-3 border font-semibold transition"
                                >
                                    @include('partials.ui.icon', ['icon' => 'award', 'iconSize' => 17])
                                    <span>Accomplishment</span>
                                </button>

                                <button
                                    type="button"
                                    id="categoryEvent"
                                    data-category="event"
                                    class="post-category-btn flex items-center justify-center gap-2 rounded-xl py-3 border font-semibold transition"
                                >
                                    @include('partials.ui.icon', ['icon' => 'calendar-days', 'iconSize' => 17])
                                    <span>Event</span>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="sk-label">Post Content</label>
                            <textarea
                                name="post_content"
                                rows="4"
                                placeholder="Share your thoughts..."
                                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-gray-700 resize-none"
                            ></textarea>
                        </div>

                        <div>
                            <label class="sk-label">Attach Media (Optional)</label>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <label class="flex items-center justify-center gap-2 rounded-xl border border-dashed border-gray-300 bg-[#f8f9fb] py-3 px-4 cursor-pointer hover:bg-white hover:border-gray-400 transition">
                                    @include('partials.ui.icon', ['icon' => 'image', 'iconSize' => 18, 'iconClass' => 'text-gray-500'])
                                    <span class="font-semibold text-gray-700">Add Image</span>
                                    <input type="file" name="post_image" accept="image/*" class="hidden">
                                </label>

                                <label class="flex items-center justify-center gap-2 rounded-xl border border-dashed border-gray-300 bg-[#f8f9fb] py-3 px-4 cursor-pointer hover:bg-white hover:border-gray-400 transition">
                                    @include('partials.ui.icon', ['icon' => 'paperclip', 'iconSize' => 18, 'iconClass' => 'text-gray-500'])
                                    <span class="font-semibold text-gray-700">Attach File</span>
                                    <input type="file" name="post_file" accept=".pdf" class="hidden">
                                </label>
                            </div>

                            <p class="text-xs text-gray-500 mt-2">
                                Supported: Images (JPG, PNG) and Documents (PDF)
                            </p>
                        </div>

                        <div>
                            <label class="sk-label">Post Visibility</label>

                            <div class="relative">
                                <select name="post_visibility" class="w-full appearance-none rounded-xl border border-gray-200 bg-white px-4 py-3 text-gray-800">
                                    <option value="all_sk_councils">All SK Councils</option>
                                    <option value="chairman_only">SK Chairman Only</option>
                                    <option value="secretary_only">All Users</option>
                                </select>
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                                    @include('partials.ui.icon', ['icon' => 'chevron-down', 'iconSize' => 17])
                                </span>
                            </div>

                            <p class="text-xs text-gray-500 mt-2">Visible to Chairman, Secretary, and Federation</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <button type="button" id="cancelAnnouncementModal" class="sk-btn sk-btn--secondary sk-btn--lg">
                                Cancel
                            </button>

                            <button type="submit" class="sk-btn sk-btn--primary sk-btn--lg">
                                @include('partials.ui.icon', ['icon' => 'plus', 'iconSize' => 18, 'iconStroke' => 2.4])
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
            button.className = 'post-category-btn flex items-center justify-center gap-2 rounded-xl py-3 border font-semibold transition bg-white text-gray-700 border-gray-200 hover:bg-gray-50';
        });

        if (category === 'announcement') {
            categoryAnnouncement.className = 'post-category-btn flex items-center justify-center gap-2 rounded-xl py-3 border font-semibold transition bg-yellow-50 border-yellow-300 text-yellow-800';
        } else if (category === 'accomplishment') {
            categoryAccomplishment.className = 'post-category-btn flex items-center justify-center gap-2 rounded-xl py-3 border font-semibold transition bg-red-50 border-red-300 text-red-700';
        } else if (category === 'event') {
            categoryEvent.className = 'post-category-btn flex items-center justify-center gap-2 rounded-xl py-3 border font-semibold transition bg-blue-50 border-blue-300 text-blue-700';
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
