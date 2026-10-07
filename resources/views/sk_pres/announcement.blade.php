{{-- File guide: Blade view template for resources/views/sk_pres/announcement.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360° | Announcements')

@section('page_css')
<script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
@php
    $presInitials=strtoupper(
        substr(auth()->user()->first_name ?? 'S',0,1).
        substr(auth()->user()->last_name ?? 'P',0,1)
    );
@endphp

<div class="flex h-screen overflow-hidden bg-gray-100">
        @include('partials.app.sidebar')


    <div class="flex-1 flex flex-col overflow-hidden">
                @include('partials.app.topbar')


        <main class="flex-1 overflow-y-auto bg-gray-50">
            <div class="max-w-5xl mx-auto px-8 py-8">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-5 mb-8">
                    <div>
                        <span class="sk-eyebrow"><span class="sk-dot"></span>SK 360° Community Feed</span>
                        <h1 class="text-3xl font-black text-gray-900 mt-2">Announcements</h1>
                        <p class="text-gray-500 mt-1">Publish and manage official communications for the SK Federation.</p>
                    </div>

                    <button id="openAnnouncementModalBtn" type="button" class="bg-red-600 hover:bg-red-700 text-white px-5 py-3 rounded-xl text-sm font-black flex items-center justify-center gap-2 shadow-sm">
                        @include('partials.ui.icon', ['icon' => 'plus', 'iconSize' => 18, 'iconStroke' => 2.4])
                        <span>New Announcement</span>
                    </button>
                </div>

                @if(session('status'))
                    <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                @if(session('warning'))
                    <div class="mb-6 rounded-2xl border border-yellow-200 bg-yellow-50 px-5 py-4 text-sm font-semibold text-yellow-700">
                        {{ session('warning') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="GET" action="{{ route('sk_pres.announcements') }}" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-7">
                    <div class="flex flex-col md:flex-row gap-3">
                        <div class="flex-1">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Search Announcements</label>

                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">@include('partials.ui.icon', ['icon'=>'search','iconSize'=>16])</span>

                                <input type="text" name="q" value="{{ $search }}" placeholder="Search title, content, author or barangay..." class="w-full rounded-xl border border-gray-200 pl-11 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-200">
                            </div>
                        </div>

                        <div class="md:w-44">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Sort</label>

                            <select name="sort" onchange="this.form.submit()" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-200">
                                <option value="latest" {{ $sort==='latest' ? 'selected' : '' }}>Latest First</option>
                                <option value="oldest" {{ $sort==='oldest' ? 'selected' : '' }}>Oldest First</option>
                            </select>
                        </div>

                        <div class="md:self-end">
                            <button type="submit" class="w-full md:w-auto bg-red-600 text-white rounded-xl px-6 py-3 text-sm font-black hover:bg-red-700 transition">Search</button>
                        </div>
                    </div>

                    @if($search!=='')
                        <div class="mt-3 flex items-center justify-between">
                            <p class="text-xs text-gray-500">
                                Showing results for <strong>"{{ $search }}"</strong>
                            </p>

                            <a href="{{ route('sk_pres.announcements') }}" class="text-xs font-black text-red-600">Clear Search</a>
                        </div>
                    @endif
                </form>

                <div class="flex items-center justify-between mb-5">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-red-600">Community Feed</p>
                        <h2 class="text-2xl font-black text-gray-900 mt-1">Federation Updates</h2>
                    </div>

                    <span class="text-xs text-gray-400">
                        {{ $announcements->total() }}
                        {{ $announcements->total()===1 ? 'announcement' : 'announcements' }}
                    </span>
                </div>

                <div class="space-y-5">
                    @forelse($announcements as $announcement)
                        <article id="announcement-{{ $announcement->announcement_id }}"
                                 class="announcement-card bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
                                 data-view-url="{{ route('public.announcements.view',$announcement->announcement_id) }}">

                            <div class="p-6">
                                <div class="flex items-start justify-between gap-4 mb-5">
                                    <div class="flex items-start gap-3">
                                        <div class="w-11 h-11 rounded-full bg-red-100 text-red-600 flex items-center justify-center font-black">
                                            {{ strtoupper(substr($announcement->author_name,0,1)) }}
                                        </div>

                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-black text-gray-900">{{ $announcement->author_name }}</p>

                                                <span class="rounded-full bg-red-50 px-2 py-1 text-[9px] font-black uppercase text-red-600">
                                                    {{ $announcement->role_label }}
                                                </span>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-2 mt-1 text-[11px] text-gray-400">
                                                @if($announcement->barangay_name)
                                                    <span>Barangay {{ $announcement->barangay_name }}</span>
                                                    <span>•</span>
                                                @endif

                                                <span>{{ \Carbon\Carbon::parse($announcement->created_at)->diffForHumans() }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-2">
                                    @if($announcement->visibility==='officials_only')
                                        <span class="shrink-0 rounded-full bg-amber-100 px-3 py-1 text-[9px] font-black uppercase text-amber-700">
                                            @include('partials.ui.icon', ['icon'=>'lock','iconSize'=>14]) Officials Only
                                        </span>
                                    @else
                                        <span class="shrink-0 rounded-full bg-green-100 px-3 py-1 text-[9px] font-black uppercase text-green-700">
                                            @include('partials.ui.icon', ['icon'=>'globe','iconSize'=>14]) Public
                                        </span>
                                    @endif
                                @if((int) $announcement->user_id === (int) auth()->id())
                                <details class="announcement-actions relative shrink-0">
                                    <summary class="flex h-9 w-9 cursor-pointer list-none items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 [&::-webkit-details-marker]:hidden" aria-label="Announcement actions">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
                                    </summary>
                                    <div class="absolute right-0 top-full z-20 mt-2 w-40 rounded-xl border border-gray-100 bg-white p-1.5 shadow-lg">
                                    <button type="button"
                                            class="edit-announcement-btn flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition"
                                            data-announcement-id="{{ $announcement->announcement_id }}"
                                            data-update-url="{{ route('sk_pres.announcements.update',$announcement->announcement_id) }}"
                                            data-title="{{ $announcement->title }}"
                                            data-content="{{ $announcement->content }}"
                                            data-visibility="{{ $announcement->visibility }}">
                                        @include('partials.ui.icon', ['icon'=>'pencil','iconSize'=>16])
                                        <span>Edit</span>
                                    </button>
                                    <form method="POST" action="{{ route('sk_pres.announcements.destroy',$announcement->announcement_id) }}" onsubmit="return confirm('Delete this announcement and its comments permanently? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 transition" aria-label="Delete announcement: {{ $announcement->title }}">
                                            @include('partials.ui.icon', ['icon'=>'trash-2','iconSize'=>16])
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                    </div>
                                </details>
                                @endif
                                    </div>
                                </div>

                                <h2 class="text-xl font-black text-gray-900 mb-3">{{ $announcement->title }}</h2>

                                <div class="text-sm leading-relaxed text-gray-600 whitespace-pre-line">{{ $announcement->content }}</div>
                            </div>

                            <div class="border-t border-gray-100 px-6 py-4">
                                <div class="flex flex-wrap items-center gap-6 text-xs font-bold text-gray-500">
                                    <button type="button"
                                            class="official-like-btn flex items-center gap-1 transition {{ $announcement->liked_by_current_user ? 'text-red-600' : 'text-gray-500 hover:text-red-600' }}"
                                            data-like-url="{{ route('public.announcements.like',$announcement->announcement_id) }}">
                                        <span data-like-icon class="text-base">{{ $announcement->liked_by_current_user ? '♥' : '♡' }}</span>
                                        <strong data-like-count>{{ $announcement->likes_count }}</strong>
                                        <span>Likes</span>
                                    </button>

                                    <button type="button"
                                            class="official-comment-btn flex items-center gap-1 hover:text-red-600 transition"
                                            data-announcement-id="{{ $announcement->announcement_id }}"
                                            data-announcement-title="{{ $announcement->title }}"
                                            data-feedback-list-url="{{ route('public.announcements.feedback-list',$announcement->announcement_id) }}"
                                            data-feedback-submit-url="{{ route('public.announcements.feedback',$announcement->announcement_id) }}">
                                        @include('partials.ui.icon', ['icon'=>'message-square','iconSize'=>16])
                                        <strong data-feedback-count>{{ $announcement->feedback_count }}</strong>
                                        <span>Comments</span>
                                    </button>

                                    <div class="flex items-center gap-1">
                                        @include('partials.ui.icon', ['icon'=>'eye','iconSize'=>16])
                                        <strong data-view-count>{{ $announcement->views_count }}</strong>
                                        <span>Views</span>
                                    </div>


                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="bg-white rounded-2xl border border-dashed border-gray-200 p-12 text-center">
                            <div class="mb-3 flex justify-center"><span class="sk-icon-tile sk-icon-tile--lg">@include('partials.ui.icon', ['icon'=>'megaphone','iconSize'=>24])</span></div>
                            <p class="font-black text-gray-700">No announcements found</p>
                            <p class="text-sm text-gray-400 mt-1">Create an announcement to get started.</p>
                        </div>
                    @endforelse
                </div>

                @if($announcements->hasPages())
                    <div class="mt-8">
                        {{ $announcements->links() }}
                    </div>
                @endif
            </div>
        </main>
    </div>
</div>

{{-- CREATE ANNOUNCEMENT MODAL --}}
<div id="announcementModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-[100] px-4">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden">
        <div class="bg-red-600 px-7 py-6 text-white flex items-start justify-between gap-4">
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-red-100">SK Federation</p>
                <h2 class="text-2xl font-black mt-1">Create Announcement</h2>
                <p class="text-sm text-red-100 mt-1">Publish an official update for the SK Federation.</p>
            </div>

            <button id="closeAnnouncementModalBtn" type="button" class="w-9 h-9 rounded-full bg-red-500 hover:bg-red-400 transition text-xl font-black">×</button>
        </div>

        <form method="POST" action="{{ route('sk_pres.announcements.store') }}" class="p-7 space-y-5">
            @csrf
            <input type="hidden" name="form_context" value="create">

            @if(old('form_context')==='create' && $errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">Announcement Title</label>

                <input type="text"
                       name="title"
                       value="{{ old('title') }}"
                       maxlength="255"
                       required
                       placeholder="Enter announcement title"
                       class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-200">
            </div>

            <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">Content</label>

                <textarea name="content"
                          rows="6"
                          required
                          placeholder="Write the announcement details..."
                          class="w-full resize-none rounded-xl border border-gray-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-200">{{ old('content') }}</textarea>
            </div>

            <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">Publication / Audience</label>

                <select name="visibility"
                        required
                        class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-200">
                    <option value="public" {{ old('visibility','public')==='public' ? 'selected' : '' }}>Public</option>
                    <option value="officials_only" {{ old('visibility')==='officials_only' ? 'selected' : '' }}>Officials Only</option>
                </select>

                <div class="mt-3 rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">
                        <strong class="text-gray-700">Public</strong> announcements can be viewed in the Public Portal and by SK officials.
                    </p>

                    <p class="text-xs text-gray-500 mt-2">
                        <strong class="text-gray-700">Officials Only</strong> announcements are only available to logged-in SK President, Chairman, and Secretary accounts.
                    </p>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button id="cancelAnnouncementBtn" type="button" class="border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 px-5 py-3 rounded-xl text-sm font-black">Cancel</button>

                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl text-sm font-black shadow-sm">
                    Publish Announcement
                </button>
            </div>
        </form>
    </div>
</div>

{{-- EDIT ANNOUNCEMENT MODAL --}}
<div id="editAnnouncementModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-[105] px-4">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden">
        <div class="bg-red-600 px-7 py-6 text-white flex items-start justify-between gap-4">
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-red-100">SK Federation</p>
                <h2 class="text-2xl font-black mt-1">Edit Announcement</h2>
                <p class="text-sm text-red-100 mt-1">Update the official announcement details.</p>
            </div>

            <button id="closeEditAnnouncementModalBtn" type="button" class="w-9 h-9 rounded-full bg-red-500 hover:bg-red-400 transition text-xl font-black">×</button>
        </div>

        <form id="editAnnouncementForm" method="POST" action="#" class="p-7 space-y-5">
            @csrf
            @method('PUT')
            <input type="hidden" name="form_context" value="edit">
            <input id="editingAnnouncementId" type="hidden" name="editing_announcement_id" value="{{ old('editing_announcement_id') }}">

            @if(old('form_context')==='edit' && $errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">Announcement Title</label>

                <input id="editAnnouncementTitle"
                       type="text"
                       name="title"
                       maxlength="255"
                       required
                       class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-200">
            </div>

            <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">Content</label>

                <textarea id="editAnnouncementContent"
                          name="content"
                          rows="6"
                          required
                          class="w-full resize-none rounded-xl border border-gray-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-200"></textarea>
            </div>

            <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">Publication / Audience</label>

                <select id="editAnnouncementVisibility"
                        name="visibility"
                        required
                        class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-200">
                    <option value="public">Public</option>
                    <option value="officials_only">Officials Only</option>
                </select>

                <div class="mt-3 rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">
                        <strong class="text-gray-700">Public</strong> announcements can be viewed in the Public Portal and by SK officials.
                    </p>

                    <p class="text-xs text-gray-500 mt-2">
                        <strong class="text-gray-700">Officials Only</strong> announcements are only available to logged-in SK President, Chairman, and Secretary accounts.
                    </p>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button id="cancelEditAnnouncementBtn" type="button" class="border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 px-5 py-3 rounded-xl text-sm font-black">Cancel</button>

                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl text-sm font-black shadow-sm">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- COMMENTS MODAL --}}
<div id="commentModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[110] items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-start justify-between gap-4">
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-red-600">Discussion</p>
                <h3 id="commentAnnouncementTitle" class="text-xl font-black text-gray-900 mt-1">Comments</h3>
                <p class="text-xs text-gray-400 mt-1">Comments from public visitors and SK officials.</p>
            </div>

            <button id="closeCommentModal" type="button" class="w-9 h-9 rounded-full bg-gray-100 hover:bg-red-50 hover:text-red-600 transition font-black">×</button>
        </div>

        <div class="flex flex-col h-[560px] max-h-[75vh]">
            <div id="commentsContainer" class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                <div class="text-center py-10 text-sm text-gray-400">Loading comments...</div>
            </div>

            <div class="border-t border-gray-100 bg-gray-50 p-5">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 shrink-0 rounded-full bg-red-100 text-red-600 flex items-center justify-center font-black">
                        {{ $presInitials }}
                    </div>

                    <div class="flex-1">
                        <textarea id="commentComposer"
                                  rows="2"
                                  maxlength="1000"
                                  placeholder="Write a comment..."
                                  class="w-full resize-none rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-200"></textarea>

                        <div id="commentMessage" class="hidden mt-2 rounded-xl px-3 py-2 text-xs font-semibold"></div>

                        <div class="flex items-center justify-between mt-3">
                            <p class="text-[10px] text-gray-400">Posting as {{ $fullName }} • SK President</p>

                            <button id="sendCommentBtn" type="button" class="bg-red-600 hover:bg-red-700 text-white rounded-xl px-5 py-2.5 text-xs font-black transition">
                                Send Comment
                            </button>
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
const csrfToken=document.querySelector('meta[name="csrf-token"]')?.content;
const notifBtn=document.getElementById('notifBtn');
const notifDropdown=document.getElementById('notifDropdown');
const userMenuBtn=document.getElementById('userMenuBtn');
const userDropdown=document.getElementById('userDropdown');
const openAnnouncementModalBtn=document.getElementById('openAnnouncementModalBtn');
const closeAnnouncementModalBtn=document.getElementById('closeAnnouncementModalBtn');
const cancelAnnouncementBtn=document.getElementById('cancelAnnouncementBtn');
const announcementModal=document.getElementById('announcementModal');
const editAnnouncementModal=document.getElementById('editAnnouncementModal');
const closeEditAnnouncementModalBtn=document.getElementById('closeEditAnnouncementModalBtn');
const cancelEditAnnouncementBtn=document.getElementById('cancelEditAnnouncementBtn');
const editAnnouncementForm=document.getElementById('editAnnouncementForm');
const editAnnouncementTitle=document.getElementById('editAnnouncementTitle');
const editAnnouncementContent=document.getElementById('editAnnouncementContent');
const editAnnouncementVisibility=document.getElementById('editAnnouncementVisibility');
const editingAnnouncementId=document.getElementById('editingAnnouncementId');
const announcementUpdateBaseUrl=@js(url('/sk_pres/announcements'));
const commentModal=document.getElementById('commentModal');
const closeCommentModal=document.getElementById('closeCommentModal');
const commentAnnouncementTitle=document.getElementById('commentAnnouncementTitle');
const commentsContainer=document.getElementById('commentsContainer');
const commentComposer=document.getElementById('commentComposer');
const commentMessage=document.getElementById('commentMessage');
const sendCommentBtn=document.getElementById('sendCommentBtn');
let currentCommentButton=null;
let currentFeedbackListUrl=null;
let currentFeedbackSubmitUrl=null;

notifBtn.addEventListener('click',function(e){
    e.stopPropagation();
    notifDropdown.classList.toggle('hidden');
    userDropdown.classList.add('hidden');
});

userMenuBtn.addEventListener('click',function(e){
    e.stopPropagation();
    userDropdown.classList.toggle('hidden');
    notifDropdown.classList.add('hidden');
});

document.addEventListener('click',function(e){
    if(!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)){
        notifDropdown.classList.add('hidden');
    }

    if(!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)){
        userDropdown.classList.add('hidden');
    }
});

function openAnnouncementModal(){
    announcementModal.classList.remove('hidden');
    announcementModal.classList.add('flex');
}

function closeAnnouncementModal(){
    announcementModal.classList.add('hidden');
    announcementModal.classList.remove('flex');
}

openAnnouncementModalBtn.addEventListener('click',openAnnouncementModal);
closeAnnouncementModalBtn.addEventListener('click',closeAnnouncementModal);
cancelAnnouncementBtn.addEventListener('click',closeAnnouncementModal);

announcementModal.addEventListener('click',event=>{
    if(event.target===announcementModal){
        closeAnnouncementModal();
    }
});

function openEditAnnouncementModal(data){
    editAnnouncementForm.action=data.updateUrl;
    editingAnnouncementId.value=data.id || '';
    editAnnouncementTitle.value=data.title || '';
    editAnnouncementContent.value=data.content || '';
    editAnnouncementVisibility.value=data.visibility || 'public';

    editAnnouncementModal.classList.remove('hidden');
    editAnnouncementModal.classList.add('flex');
}

function closeEditAnnouncementModal(){
    editAnnouncementModal.classList.add('hidden');
    editAnnouncementModal.classList.remove('flex');
}

document.addEventListener('click',event=>{
    document.querySelectorAll('.announcement-actions[open]').forEach(menu=>{
        if(!menu.contains(event.target)) menu.removeAttribute('open');
    });
});
document.addEventListener('keydown',event=>{
    if(event.key==='Escape') document.querySelectorAll('.announcement-actions[open]').forEach(menu=>{
        menu.removeAttribute('open');
        menu.querySelector('summary').focus();
    });
});
document.querySelectorAll('.edit-announcement-btn').forEach(button=>{
    button.addEventListener('click',()=>button.closest('details').removeAttribute('open'));
    button.addEventListener('click',()=>{
        openEditAnnouncementModal({
            id:button.dataset.announcementId,
            updateUrl:button.dataset.updateUrl,
            title:button.dataset.title || '',
            content:button.dataset.content || '',
            visibility:button.dataset.visibility || 'public'
        });
    });
});

closeEditAnnouncementModalBtn.addEventListener('click',closeEditAnnouncementModal);
cancelEditAnnouncementBtn.addEventListener('click',closeEditAnnouncementModal);

editAnnouncementModal.addEventListener('click',event=>{
    if(event.target===editAnnouncementModal){
        closeEditAnnouncementModal();
    }
});

function escapeHtml(value){
    const div=document.createElement('div');
    div.textContent=value ?? '';
    return div.innerHTML;
}

function showCommentMessage(message,type='error'){
    commentMessage.textContent=message;

    commentMessage.classList.remove(
        'hidden',
        'bg-red-50',
        'text-red-600',
        'bg-green-50',
        'text-green-700'
    );

    if(type==='success'){
        commentMessage.classList.add('bg-green-50','text-green-700');
    }else{
        commentMessage.classList.add('bg-red-50','text-red-600');
    }
}

function hideCommentMessage(){
    commentMessage.classList.add('hidden');
}

function renderComments(data){
    if(!data.feedbacks || data.feedbacks.length===0){
        commentsContainer.innerHTML=`
            <div class="text-center py-12">
                <div class="mb-3 flex justify-center"><span class="sk-icon-tile sk-icon-tile--lg sk-icon-tile--blue">@include('partials.ui.icon', ['icon'=>'message-circle','iconSize'=>24])</span></div>
                <p class="font-bold text-gray-600">No comments yet</p>
                <p class="text-xs text-gray-400 mt-1">Be the first to join the discussion.</p>
            </div>
        `;

        return;
    }

    commentsContainer.innerHTML=data.feedbacks.map(comment=>{
        const role=comment.is_official && comment.role_label
            ? `
                <span class="rounded-full bg-red-50 px-2 py-1 text-[9px] font-black uppercase text-red-600">
                    ${escapeHtml(comment.role_label)}
                </span>
            `
            : `
                <span class="rounded-full bg-gray-100 px-2 py-1 text-[9px] font-black uppercase text-gray-500">
                    Public
                </span>
            `;

        const barangay=comment.is_official && comment.barangay_name
            ? `<span>Barangay ${escapeHtml(comment.barangay_name)}</span>`
            : '';

        const initial=escapeHtml(
            (comment.name || 'U')
                .charAt(0)
                .toUpperCase()
        );

        return `
            <div class="flex gap-3">
                <div class="w-10 h-10 shrink-0 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center font-black">
                    ${initial}
                </div>

                <div class="flex-1">
                    <div class="rounded-2xl bg-gray-50 px-4 py-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-black text-gray-800">${escapeHtml(comment.name)}</p>
                            ${role}
                        </div>

                        ${
                            barangay
                                ? `<p class="text-[10px] text-gray-400 mt-1">${barangay}</p>`
                                : ''
                        }

                        <p class="text-sm text-gray-600 leading-relaxed mt-2 whitespace-pre-line">${escapeHtml(comment.comment)}</p>
                    </div>

                    <p class="text-[10px] text-gray-400 mt-1 ml-2">${escapeHtml(comment.created_at_human)}</p>
                </div>
            </div>
        `;
    }).join('');
}

async function loadComments(){
    if(!currentFeedbackListUrl){
        return;
    }

    commentsContainer.innerHTML=`
        <div class="text-center py-10 text-sm text-gray-400">
            Loading comments...
        </div>
    `;

    try{
        const response=await fetch(
            currentFeedbackListUrl,
            {
                method:'GET',
                headers:{
                    'X-Requested-With':'XMLHttpRequest',
                    'Accept':'application/json'
                },
                credentials:'same-origin'
            }
        );

        const data=await response.json();

        if(!response.ok){
            throw new Error(
                data.message ||
                'Unable to load comments.'
            );
        }

        renderComments(data);

        if(currentCommentButton){
            const count=currentCommentButton.querySelector('[data-feedback-count]');

            if(count){
                count.textContent=data.total;
            }
        }
    }catch(error){
        commentsContainer.innerHTML=`
            <div class="text-center py-10">
                <p class="text-sm font-bold text-red-600">Unable to load comments.</p>

                <button type="button"
                        onclick="loadComments()"
                        class="text-xs font-black text-red-600 mt-3">
                    Try Again
                </button>
            </div>
        `;
    }
}

document.querySelectorAll('.official-comment-btn').forEach(button=>{
    button.addEventListener('click',()=>{
        currentCommentButton=button;
        currentFeedbackListUrl=button.dataset.feedbackListUrl;
        currentFeedbackSubmitUrl=button.dataset.feedbackSubmitUrl;
        commentAnnouncementTitle.textContent=button.dataset.announcementTitle;
        commentComposer.value='';
        commentComposer.style.height='auto';

        hideCommentMessage();

        commentModal.classList.remove('hidden');
        commentModal.classList.add('flex');

        loadComments();

        setTimeout(()=>{
            commentComposer.focus();
        },200);
    });
});

function closeComments(){
    commentModal.classList.add('hidden');
    commentModal.classList.remove('flex');
    currentCommentButton=null;
    currentFeedbackListUrl=null;
    currentFeedbackSubmitUrl=null;
}

closeCommentModal.addEventListener('click',closeComments);

commentModal.addEventListener('click',event=>{
    if(event.target===commentModal){
        closeComments();
    }
});

commentComposer.addEventListener('input',()=>{
    commentComposer.style.height='auto';
    commentComposer.style.height=Math.min(commentComposer.scrollHeight,112)+'px';
    hideCommentMessage();
});

sendCommentBtn.addEventListener('click',async()=>{
    const comment=commentComposer.value.trim();

    if(comment===''){
        showCommentMessage('Write a comment before sending.');
        commentComposer.focus();
        return;
    }

    if(!currentFeedbackSubmitUrl){
        return;
    }

    sendCommentBtn.disabled=true;
    sendCommentBtn.textContent='Posting...';
    hideCommentMessage();

    try{
        const response=await fetch(
            currentFeedbackSubmitUrl,
            {
                method:'POST',
                headers:{
                    'X-CSRF-TOKEN':csrfToken,
                    'X-Requested-With':'XMLHttpRequest',
                    'Accept':'application/json',
                    'Content-Type':'application/json'
                },
                credentials:'same-origin',
                body:JSON.stringify({
                    comment:comment
                })
            }
        );

        const data=await response.json();

        if(!response.ok){
            let message=data.message || 'Unable to post comment.';

            if(data.errors){
                message=Object.values(data.errors).flat()[0] || message;
            }

            throw new Error(message);
        }

        commentComposer.value='';
        commentComposer.style.height='auto';

        if(currentCommentButton && data.feedback_count!==undefined){
            const count=currentCommentButton.querySelector('[data-feedback-count]');

            if(count){
                count.textContent=data.feedback_count;
            }
        }

        showCommentMessage(
            'Comment posted successfully.',
            'success'
        );

        await loadComments();
    }catch(error){
        showCommentMessage(error.message);
    }finally{
        sendCommentBtn.disabled=false;
        sendCommentBtn.textContent='Send Comment';
    }
});

document.querySelectorAll('.official-like-btn').forEach(button=>{
    button.addEventListener('click',async()=>{
        if(button.disabled){
            return;
        }

        button.disabled=true;

        try{
            const response=await fetch(
                button.dataset.likeUrl,
                {
                    method:'POST',
                    headers:{
                        'X-CSRF-TOKEN':csrfToken,
                        'X-Requested-With':'XMLHttpRequest',
                        'Accept':'application/json'
                    },
                    credentials:'same-origin'
                }
            );

            const data=await response.json();

            if(!response.ok){
                throw new Error(
                    data.message ||
                    'Unable to update like.'
                );
            }

            button.querySelector('[data-like-count]').textContent=data.likes_count;
            button.querySelector('[data-like-icon]').textContent=data.liked ? '♥' : '♡';

            button.classList.toggle('text-red-600',data.liked);
            button.classList.toggle('text-gray-500',!data.liked);
        }catch(error){
            alert(error.message);
        }finally{
            button.disabled=false;
        }
    });
});

const viewObserver=new IntersectionObserver(
    entries=>{
        entries.forEach(async entry=>{
            if(!entry.isIntersecting){
                return;
            }

            const card=entry.target;
            viewObserver.unobserve(card);

            try{
                const response=await fetch(
                    card.dataset.viewUrl,
                    {
                        method:'POST',
                        headers:{
                            'X-CSRF-TOKEN':csrfToken,
                            'X-Requested-With':'XMLHttpRequest',
                            'Accept':'application/json'
                        },
                        credentials:'same-origin'
                    }
                );

                if(!response.ok){
                    return;
                }

                const data=await response.json();
                const counter=card.querySelector('[data-view-count]');

                if(counter){
                    counter.textContent=data.views_count;
                }
            }catch(error){
                //
            }
        });
    },
    {
        threshold:0.5
    }
);

document.querySelectorAll('.announcement-card').forEach(card=>{
    viewObserver.observe(card);
});

document.addEventListener('keydown',event=>{
    if(event.key!=='Escape'){
        return;
    }

    if(!commentModal.classList.contains('hidden')){
        closeComments();
        return;
    }

    if(!editAnnouncementModal.classList.contains('hidden')){
        closeEditAnnouncementModal();
        return;
    }

    if(!announcementModal.classList.contains('hidden')){
        closeAnnouncementModal();
    }
});

@if($errors->any())
    @if(old('form_context')==='edit' && old('editing_announcement_id'))
        openEditAnnouncementModal({
            id:@js(old('editing_announcement_id')),
            updateUrl:announcementUpdateBaseUrl+'/'+@js(old('editing_announcement_id')),
            title:@js(old('title','')),
            content:@js(old('content','')),
            visibility:@js(old('visibility','public'))
        });
    @else
        openAnnouncementModal();
    @endif
@endif
</script>
@endpush
