{{-- File guide: Blade view template for resources/views/sk_chairman/announcements.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK Chairman Announcements')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
@php
    $chairmanInitials=strtoupper(
        substr(auth()->user()->first_name ?? 'S',0,1).
        substr(auth()->user()->last_name ?? 'C',0,1)
    );
@endphp

<div class="flex h-screen bg-gray-100 overflow-hidden">
    @include('partials.app.sidebar')

    <div class="flex-1 flex flex-col overflow-hidden">
        @include('partials.app.topbar')

        <main class="flex-1 overflow-y-auto bg-gray-50">
            <div class="max-w-5xl mx-auto px-8 py-8">
                <div class="mb-8">
                    <span class="sk-eyebrow"><span class="sk-dot"></span>
                        SK 360° Community Feed
                    </span>

                    <h1 class="text-3xl font-black text-gray-900 mt-2">
                        Announcements
                    </h1>

                    <p class="text-gray-500 mt-1">
                        Official communications and updates from the SK Federation.
                    </p>
                </div>

                <form method="GET"
                      action="{{ route('sk_chairman.announcements') }}"
                      class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-7">

                    <div class="flex flex-col md:flex-row gap-3">
                        <div class="flex-1">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">
                                Search Announcements
                            </label>

                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                    <span class="inline-flex align-[-3px]">@include('partials.ui.icon', ['icon' => 'search', 'iconSize' => 16])</span>
                                </span>

                                <input type="text"
                                       name="q"
                                       value="{{ $search }}"
                                       placeholder="Search title, content, author or barangay..."
                                       class="w-full rounded-xl border border-gray-200 pl-11 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-200">
                            </div>
                        </div>

                        <div class="md:w-44">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">
                                Sort
                            </label>

                            <select name="sort"
                                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-200">

                                <option value="latest" {{ $sort==='latest' ? 'selected' : '' }}>
                                    Latest First
                                </option>

                                <option value="oldest" {{ $sort==='oldest' ? 'selected' : '' }}>
                                    Oldest First
                                </option>
                            </select>
                        </div>

                        <div class="md:self-end">
                            <button type="submit"
                                    class="w-full md:w-auto bg-red-600 text-white rounded-xl px-6 py-3 text-sm font-black hover:bg-red-700 transition">

                                Search
                            </button>
                        </div>
                    </div>

                    @if($search!=='')
                        <div class="mt-3 flex items-center justify-between">
                            <p class="text-xs text-gray-500">
                                Showing results for
                                <strong>"{{ $search }}"</strong>
                            </p>

                            <a href="{{ route('sk_chairman.announcements') }}"
                               class="text-xs font-black text-red-600">

                                Clear Search
                            </a>
                        </div>
                    @endif
                </form>

                <div class="flex items-center justify-between mb-5">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-red-600">
                            Community Feed
                        </p>

                        <h2 class="text-2xl font-black text-gray-900 mt-1">
                            Federation Updates
                        </h2>
                    </div>

                    <span class="text-xs text-gray-400">
                        {{ $announcements->total() }}
                        {{ $announcements->total()===1 ? 'announcement' : 'announcements' }}
                    </span>
                </div>

                <div class="space-y-5">
                    @forelse($announcements as $announcement)
                        <article id="announcement-{{ $announcement->announcement_id }}"
                                 class="announcement-card bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden transition-all duration-500"
                                 data-view-url="{{ route('public.announcements.view',$announcement->announcement_id) }}">

                            <div class="p-6">
                                <div class="flex items-start justify-between gap-4 mb-5">
                                    <div class="flex items-start gap-3">
                                        <div class="w-11 h-11 rounded-full bg-red-100 text-red-600 flex items-center justify-center font-black">
                                            {{ strtoupper(substr($announcement->author_name,0,1)) }}
                                        </div>

                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-black text-gray-900">
                                                    {{ $announcement->author_name }}
                                                </p>

                                                <span class="rounded-full bg-red-50 px-2 py-1 text-[9px] font-black uppercase text-red-600">
                                                    {{ $announcement->role_label }}
                                                </span>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-2 mt-1 text-[11px] text-gray-400">
                                                @if($announcement->barangay_name)
                                                    <span>
                                                        Barangay {{ $announcement->barangay_name }}
                                                    </span>

                                                    <span>•</span>
                                                @endif

                                                <span>
                                                    {{ \Carbon\Carbon::parse($announcement->created_at)->diffForHumans() }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    @if($announcement->visibility==='officials_only')
                                        <span class="shrink-0 rounded-full bg-amber-100 px-3 py-1 text-[9px] font-black uppercase text-amber-700">
                                            <span class="inline-flex align-[-3px]">@include('partials.ui.icon', ['icon' => 'lock', 'iconSize' => 16])</span> Officials Only
                                        </span>
                                    @else
                                        <span class="shrink-0 rounded-full bg-green-100 px-3 py-1 text-[9px] font-black uppercase text-green-700">
                                            <span class="inline-flex align-[-3px]">@include('partials.ui.icon', ['icon' => 'globe', 'iconSize' => 16])</span> Public
                                        </span>
                                    @endif
                                </div>

                                <h2 class="text-xl font-black text-gray-900 mb-3">
                                    {{ $announcement->title }}
                                </h2>

                                <div class="text-sm leading-relaxed text-gray-600 whitespace-pre-line">
                                    {{ $announcement->content }}
                                </div>
                            </div>

                            <div class="border-t border-gray-100 px-6 py-4">
                                <div class="flex flex-wrap items-center gap-6 text-xs font-bold text-gray-500">
                                    <button type="button"
                                            class="official-like-btn flex items-center gap-1 transition {{ $announcement->liked_by_current_user ? 'text-red-600' : 'text-gray-500 hover:text-red-600' }}"
                                            data-like-url="{{ route('public.announcements.like',$announcement->announcement_id) }}">

                                        <span data-like-icon class="text-base">
                                            {{ $announcement->liked_by_current_user ? '♥' : '♡' }}
                                        </span>

                                        <strong data-like-count>
                                            {{ $announcement->likes_count }}
                                        </strong>

                                        <span>
                                            Likes
                                        </span>
                                    </button>

                                    <button type="button"
                                            class="official-comment-btn flex items-center gap-1 hover:text-red-600 transition"
                                            data-announcement-id="{{ $announcement->announcement_id }}"
                                            data-announcement-title="{{ $announcement->title }}"
                                            data-feedback-list-url="{{ route('public.announcements.feedback-list',$announcement->announcement_id) }}"
                                            data-feedback-submit-url="{{ route('public.announcements.feedback',$announcement->announcement_id) }}">

                                        <span class="text-base">
                                            <span class="inline-flex align-[-3px]">@include('partials.ui.icon', ['icon' => 'message-circle', 'iconSize' => 16])</span>
                                        </span>

                                        <strong data-feedback-count>
                                            {{ $announcement->feedback_count }}
                                        </strong>

                                        <span>
                                            Comments
                                        </span>
                                    </button>

                                    <div class="flex items-center gap-1">
                                        <span><span class="inline-flex align-[-3px]">@include('partials.ui.icon', ['icon' => 'eye', 'iconSize' => 16])</span></span>

                                        <strong data-view-count>
                                            {{ $announcement->views_count }}
                                        </strong>

                                        <span>
                                            Views
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="bg-white rounded-2xl border border-dashed border-gray-200 p-12 text-center">
                            <div class="text-4xl mb-3">
                                <span class="inline-flex align-[-3px]">@include('partials.ui.icon', ['icon' => 'megaphone', 'iconSize' => 16])</span>
                            </div>

                            <p class="font-black text-gray-700">
                                No announcements found
                            </p>

                            <p class="text-sm text-gray-400 mt-1">
                                Federation announcements will appear here.
                            </p>
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

<div id="commentModal"
     class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] items-center justify-center p-4">

    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-start justify-between gap-4">
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-red-600">
                    Discussion
                </p>

                <h3 id="commentAnnouncementTitle"
                    class="text-xl font-black text-gray-900 mt-1">

                    Comments
                </h3>

                <p class="text-xs text-gray-400 mt-1">
                    Comments from public visitors and SK officials.
                </p>
            </div>

            <button id="closeCommentModal"
                    type="button"
                    class="w-9 h-9 rounded-full bg-gray-100 hover:bg-red-50 hover:text-red-600 transition font-black">

                ×
            </button>
        </div>

        <div class="flex flex-col h-[560px] max-h-[75vh]">
            <div id="commentsContainer"
                 class="flex-1 overflow-y-auto px-6 py-5 space-y-4">

                <div class="text-center py-10 text-sm text-gray-400">
                    Loading comments...
                </div>
            </div>

            <div class="border-t border-gray-100 bg-gray-50 p-5">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 shrink-0 rounded-full bg-red-100 text-red-600 flex items-center justify-center font-black">
                        {{ $chairmanInitials }}
                    </div>

                    <div class="flex-1">
                        <textarea id="commentComposer"
                                  rows="2"
                                  maxlength="1000"
                                  placeholder="Write a comment..."
                                  class="w-full resize-none rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-200"></textarea>

                        <div id="commentMessage"
                             class="hidden mt-2 rounded-xl px-3 py-2 text-xs font-semibold">
                        </div>

                        <div class="flex items-center justify-between mt-3">
                            <p class="text-[10px] text-gray-400">
                                Posting as {{ $fullName }} • SK Chairman
                            </p>

                            <button id="sendCommentBtn"
                                    type="button"
                                    class="bg-red-600 hover:bg-red-700 text-white rounded-xl px-5 py-2.5 text-xs font-black transition">

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
    const csrfToken=
        document.querySelector('meta[name="csrf-token"]')?.content;

    const notifBtn=
        document.getElementById('notifBtn');

    const notifDropdown=
        document.getElementById('notifDropdown');

    const userMenuBtn=
        document.getElementById('userMenuBtn');

    const userDropdown=
        document.getElementById('userDropdown');

    const commentModal=
        document.getElementById('commentModal');

    const closeCommentModal=
        document.getElementById('closeCommentModal');

    const commentAnnouncementTitle=
        document.getElementById('commentAnnouncementTitle');

    const commentsContainer=
        document.getElementById('commentsContainer');

    const commentComposer=
        document.getElementById('commentComposer');

    const commentMessage=
        document.getElementById('commentMessage');

    const sendCommentBtn=
        document.getElementById('sendCommentBtn');

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
        if(
            !notifBtn.contains(e.target)
            &&
            !notifDropdown.contains(e.target)
        ){
            notifDropdown.classList.add('hidden');
        }

        if(
            !userMenuBtn.contains(e.target)
            &&
            !userDropdown.contains(e.target)
        ){
            userDropdown.classList.add('hidden');
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
            commentMessage.classList.add(
                'bg-green-50',
                'text-green-700'
            );
        }else{
            commentMessage.classList.add(
                'bg-red-50',
                'text-red-600'
            );
        }
    }

    function hideCommentMessage(){
        commentMessage.classList.add('hidden');
    }

    function renderComments(data){
        if(!data.feedbacks || data.feedbacks.length===0){
            commentsContainer.innerHTML=`
                <div class="text-center py-12">
                    <div class="text-3xl mb-3">
                        💬
                    </div>

                    <p class="font-bold text-gray-600">
                        No comments yet
                    </p>

                    <p class="text-xs text-gray-400 mt-1">
                        Be the first to join the discussion.
                    </p>
                </div>
            `;

            return;
        }

        commentsContainer.innerHTML=
            data.feedbacks.map(comment=>{
                const role=
                    comment.is_official && comment.role_label
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

                const barangay=
                    comment.is_official && comment.barangay_name
                        ? `
                            <span>
                                Barangay ${escapeHtml(comment.barangay_name)}
                            </span>
                        `
                        : '';

                const initial=
                    escapeHtml(
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
                                    <p class="text-sm font-black text-gray-800">
                                        ${escapeHtml(comment.name)}
                                    </p>

                                    ${role}
                                </div>

                                ${
                                    barangay
                                        ? `
                                            <p class="text-[10px] text-gray-400 mt-1">
                                                ${barangay}
                                            </p>
                                        `
                                        : ''
                                }

                                <p class="text-sm text-gray-600 leading-relaxed mt-2 whitespace-pre-line">${escapeHtml(comment.comment)}</p>
                            </div>

                            <p class="text-[10px] text-gray-400 mt-1 ml-2">
                                ${escapeHtml(comment.created_at_human)}
                            </p>
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
                const count=
                    currentCommentButton.querySelector(
                        '[data-feedback-count]'
                    );

                if(count){
                    count.textContent=data.total;
                }
            }

        }catch(error){
            commentsContainer.innerHTML=`
                <div class="text-center py-10">
                    <p class="text-sm font-bold text-red-600">
                        Unable to load comments.
                    </p>

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

            currentFeedbackListUrl=
                button.dataset.feedbackListUrl;

            currentFeedbackSubmitUrl=
                button.dataset.feedbackSubmitUrl;

            commentAnnouncementTitle.textContent=
                button.dataset.announcementTitle;

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

    closeCommentModal.addEventListener(
        'click',
        closeComments
    );

    commentModal.addEventListener('click',event=>{
        if(event.target===commentModal){
            closeComments();
        }
    });

    commentComposer.addEventListener('input',()=>{
        commentComposer.style.height='auto';

        commentComposer.style.height=
            Math.min(
                commentComposer.scrollHeight,
                112
            )+'px';

        hideCommentMessage();
    });

    sendCommentBtn.addEventListener('click',async()=>{
        const comment=
            commentComposer.value.trim();

        if(comment===''){
            showCommentMessage(
                'Write a comment before sending.'
            );

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
                let message=
                    data.message ||
                    'Unable to post comment.';

                if(data.errors){
                    message=
                        Object.values(
                            data.errors
                        ).flat()[0] || message;
                }

                throw new Error(message);
            }

            commentComposer.value='';
            commentComposer.style.height='auto';

            if(
                currentCommentButton
                &&
                data.feedback_count!==undefined
            ){
                const count=
                    currentCommentButton.querySelector(
                        '[data-feedback-count]'
                    );

                if(count){
                    count.textContent=
                        data.feedback_count;
                }
            }

            showCommentMessage(
                'Comment posted successfully.',
                'success'
            );

            await loadComments();

        }catch(error){
            showCommentMessage(
                error.message
            );

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

                button.querySelector(
                    '[data-like-count]'
                ).textContent=
                    data.likes_count;

                button.querySelector(
                    '[data-like-icon]'
                ).textContent=
                    data.liked
                        ? '♥'
                        : '♡';

                button.classList.toggle(
                    'text-red-600',
                    data.liked
                );

                button.classList.toggle(
                    'text-gray-500',
                    !data.liked
                );

            }catch(error){
                alert(error.message);

            }finally{
                button.disabled=false;
            }
        });
    });

    const viewObserver=
        new IntersectionObserver(
            entries=>{
                entries.forEach(async entry=>{
                    if(!entry.isIntersecting){
                        return;
                    }

                    const card=
                        entry.target;

                    viewObserver.unobserve(card);

                    try{
                        const response=
                            await fetch(
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

                        const data=
                            await response.json();

                        const counter=
                            card.querySelector(
                                '[data-view-count]'
                            );

                        if(counter){
                            counter.textContent=
                                data.views_count;
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

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION DEEP LINK
    |--------------------------------------------------------------------------
    */

    const focusParams=
        new URLSearchParams(
            window.location.search
        );

    const focusId=
        focusParams.get('focus_id');

    if(focusId){
        const focusCard=
            document.getElementById(
                'announcement-'+focusId
            );

        if(focusCard){
            setTimeout(()=>{
                focusCard.scrollIntoView({
                    behavior:'smooth',
                    block:'center'
                });

                focusCard.classList.add(
                    'ring-4',
                    'ring-yellow-300',
                    'shadow-xl'
                );

                setTimeout(()=>{
                    focusCard.classList.remove(
                        'ring-4',
                        'ring-yellow-300',
                        'shadow-xl'
                    );
                },4000);
            },250);
        }
    }
</script>
@endpush