<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Announcements | SK360 Public Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 text-gray-800">

{{-- HEADER --}}
<header class="sticky top-0 z-40 bg-red-600 text-white shadow">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">

        <a href="{{ route('public.home') }}" class="flex items-center gap-3">
            <img src="{{ asset('images/sk logo.png') }}"
                class="w-10 h-10 rounded-full object-cover"
                alt="SK360 Logo">

            <div>
                <h1 class="text-xl font-black">SK 360°</h1>
                <p class="text-[10px] opacity-80 uppercase tracking-widest">
                    Public Information Portal
                </p>
            </div>
        </a>

        <a href="{{ route('public.home') }}"
            class="text-xs font-bold hover:text-yellow-300 transition">
            ← Public Portal
        </a>

    </div>
</header>

<main>

{{-- PAGE HEADER --}}
<section class="bg-gradient-to-br from-red-600 to-red-700 text-white">
    <div class="max-w-4xl mx-auto px-6 py-12">

        <p class="text-xs font-black uppercase tracking-[0.2em] text-red-100">
            Public Information
        </p>

        <h2 class="text-4xl font-black mt-2">
            Announcements
        </h2>

        <p class="text-red-100 text-sm mt-3 max-w-2xl leading-relaxed">
            Stay informed with the latest public announcements and community updates
            from the Sangguniang Kabataan Federation of Lipa City.
        </p>

    </div>
</section>

{{-- SEARCH --}}
<section class="max-w-4xl mx-auto px-6 pt-8">

    <form method="GET"
        action="{{ route('public.announcements') }}"
        class="bg-white border border-gray-100 rounded-2xl shadow-sm p-4">

        <div class="flex flex-col md:flex-row gap-3">

            <div class="flex-1">

                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">
                    Search Announcements
                </label>

                <div class="relative">

                    <input type="text"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Search title, content, or official..."
                        class="w-full rounded-xl border border-gray-200 pl-10 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-300">

                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                        🔎
                    </span>

                </div>

            </div>

            <div class="md:w-44">

                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">
                    Sort
                </label>

                <select name="sort"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-300">

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

                <a href="{{ route('public.announcements') }}"
                    class="text-xs font-black text-red-600">
                    Clear Search
                </a>

            </div>
        @endif

    </form>

</section>

{{-- ANNOUNCEMENTS --}}
<section class="max-w-4xl mx-auto px-6 py-8">

    <div class="flex items-center justify-between mb-5">

        <div>
            <p class="text-xs font-black uppercase tracking-widest text-red-600">
                Community Feed
            </p>

            <h2 class="text-2xl font-black mt-1">
                Public Updates
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
                class="announcement-card bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
                data-view-url="{{ route('public.announcements.view',$announcement->announcement_id) }}">

                {{-- POST --}}
                <div class="p-6">

                    <div class="flex items-start justify-between gap-4 mb-4">

                        <div class="flex gap-3">

                            <div class="w-11 h-11 bg-red-100 text-red-600 rounded-full flex items-center justify-center shrink-0">
                                📢
                            </div>

                            <div>

                                <h3 class="font-black text-gray-800">
                                    {{ $announcement->author_name }}
                                </h3>

                                <p class="text-[10px] text-gray-400">

                                    {{ $announcement->role_label }}

                                    @if($announcement->barangay_name)
                                        • Barangay {{ $announcement->barangay_name }}
                                    @endif

                                </p>

                            </div>

                        </div>

                        <span class="text-[10px] text-gray-400 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($announcement->created_at)->diffForHumans() }}
                        </span>

                    </div>

                    <span class="inline-block bg-red-50 text-red-600 px-3 py-1 rounded-full text-[9px] font-black uppercase mb-3">
                        {{ $announcement->title }}
                    </span>

                    <p class="text-sm text-gray-700 whitespace-pre-line leading-relaxed break-words">
                        {{ $announcement->content }}
                    </p>

                    <p class="text-[10px] text-gray-400 mt-5">
                        {{ \Carbon\Carbon::parse($announcement->created_at)->format('F d, Y • h:i A') }}
                    </p>

                </div>

                {{-- INTERACTIONS --}}
                <div class="border-t border-gray-100 px-6 py-3 flex items-center gap-6 text-xs text-gray-500">

                    {{-- LIKE --}}
                    <button type="button"
                        data-like-url="{{ route('public.announcements.like',$announcement->announcement_id) }}"
                        class="public-like-btn flex items-center gap-1 font-semibold transition {{ $announcement->liked_by_visitor ? 'text-red-600' : 'text-gray-500 hover:text-red-600' }}">

                        <span data-like-icon class="text-base">
                            {{ $announcement->liked_by_visitor ? '♥' : '♡' }}
                        </span>

                        <strong data-like-count>
                            {{ $announcement->likes_count }}
                        </strong>

                        <span class="hidden sm:inline">
                            Likes
                        </span>

                    </button>

                    {{-- FEEDBACK --}}
                    <button type="button"
                        class="public-feedback-btn flex items-center gap-1 hover:text-red-600 transition"
                        data-announcement-id="{{ $announcement->announcement_id }}"
                        data-announcement-title="{{ $announcement->title }}"
                        data-feedback-list-url="{{ route('public.announcements.feedback-list',$announcement->announcement_id) }}"
                        data-feedback-submit-url="{{ route('public.announcements.feedback',$announcement->announcement_id) }}">

                        💬

                        <strong data-feedback-count>
                            {{ $announcement->feedback_count }}
                        </strong>

                        <span class="hidden sm:inline">
                            Feedback
                        </span>

                    </button>

                    {{-- VIEWS --}}
                    <span class="flex items-center gap-1">

                        👁

                        <strong data-view-count>
                            {{ $announcement->views_count }}
                        </strong>

                        <span class="hidden sm:inline">
                            Views
                        </span>

                    </span>

                </div>

            </article>

        @empty

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm text-center px-6 py-16">

                <div class="text-4xl mb-4">
                    📭
                </div>

                <h3 class="font-black text-gray-700">
                    No announcements found
                </h3>

                @if($search!=='')
                    <p class="text-sm text-gray-400 mt-2">
                        Try a different search term.
                    </p>

                    <a href="{{ route('public.announcements') }}"
                        class="inline-block mt-5 text-sm font-black text-red-600">
                        View All Announcements
                    </a>
                @else
                    <p class="text-sm text-gray-400 mt-2">
                        There are no public announcements available yet.
                    </p>
                @endif

            </div>

        @endforelse

    </div>

    @if($announcements->hasPages())
        <div class="mt-8">
            {{ $announcements->links() }}
        </div>
    @endif

</section>

</main>

{{-- FEEDBACK MODAL --}}
<div id="feedbackModal"
    class="hidden fixed inset-0 z-[9999] bg-black/50 items-center justify-center p-4">

    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh]">

        {{-- HEADER --}}
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 shrink-0">

            <div>
                <h3 class="font-black text-lg text-gray-800">
                    Feedback
                </h3>

                <p id="feedbackAnnouncementTitle"
                    class="text-[10px] text-gray-400 mt-1">
                </p>
            </div>

            <button id="closeFeedbackModal"
                type="button"
                class="w-9 h-9 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-xl transition">
                &times;
            </button>

        </div>

        {{-- COMMENTS --}}
        <div id="feedbackCommentsStep"
            class="flex flex-col min-h-0 flex-1">

            <div id="feedbackCommentsContainer"
                class="overflow-y-auto flex-1 px-5 py-5 space-y-4">

                <div class="text-center py-10 text-sm text-gray-400">
                    Loading feedback...
                </div>

            </div>

            <div id="feedbackComposerMessage"
                class="hidden mx-5 mb-2 rounded-xl px-4 py-2 text-xs font-bold">
            </div>

            <div class="border-t border-gray-100 p-4 shrink-0">

                <div class="flex items-end gap-3">

                    <div class="w-9 h-9 bg-red-100 text-red-600 rounded-full flex items-center justify-center shrink-0 font-black">
                        ?
                    </div>

                    <div class="flex-1 bg-gray-100 rounded-2xl px-4 py-2">

                        <textarea id="feedbackComposer"
                            rows="1"
                            maxlength="1000"
                            placeholder="Write a feedback..."
                            class="w-full bg-transparent resize-none outline-none text-sm text-gray-700 max-h-28"></textarea>

                    </div>

                    <button id="openIdentityStepBtn"
                        type="button"
                        class="w-10 h-10 shrink-0 rounded-full bg-red-600 text-white flex items-center justify-center font-black hover:bg-red-700 transition">
                        ➤
                    </button>

                </div>

                <p class="text-[9px] text-gray-400 ml-12 mt-2">
                    Email verification is required before your feedback is posted.
                </p>

            </div>

        </div>

        {{-- IDENTITY --}}
        <div id="feedbackIdentityStep"
            class="hidden overflow-y-auto">

            <div class="p-6">

                <button id="backToCommentsBtn"
                    type="button"
                    class="text-xs font-bold text-gray-500 hover:text-red-600 mb-5">
                    ← Back to feedback
                </button>

                <p class="text-xs font-black uppercase tracking-widest text-red-600">
                    Almost Done
                </p>

                <h4 class="text-xl font-black text-gray-800 mt-1">
                    Verify before posting
                </h4>

                <p class="text-xs text-gray-500 mt-2 leading-relaxed">
                    Your email will only be used to send the verification code.
                    It will not be displayed publicly.
                </p>

                <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4 my-5">

                    <p class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">
                        Your Feedback
                    </p>

                    <p id="feedbackCommentPreview"
                        class="text-sm text-gray-700 whitespace-pre-line break-words">
                    </p>

                </div>

                <form id="feedbackIdentityForm"
                    class="space-y-4">

                    <div>

                        <label class="block text-xs font-bold text-gray-600 mb-1">
                            Your Name
                        </label>

                        <input id="feedbackName"
                            type="text"
                            maxlength="150"
                            required
                            placeholder="Enter your name"
                            class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-300">

                    </div>

                    <div>

                        <label class="block text-xs font-bold text-gray-600 mb-1">
                            Email Address
                        </label>

                        <input id="feedbackEmail"
                            type="email"
                            maxlength="191"
                            required
                            placeholder="example@email.com"
                            class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-300">

                        <p class="text-[10px] text-gray-400 mt-1">
                            We will send a 6-digit verification code to this email.
                        </p>

                    </div>

                    <p id="feedbackIdentityError"
                        class="hidden text-xs font-bold text-red-600">
                    </p>

                    <button id="sendFeedbackOtpBtn"
                        type="submit"
                        class="w-full rounded-xl bg-red-600 py-3 text-sm font-black text-white hover:bg-red-700 transition">
                        Send Verification Code
                    </button>

                </form>

            </div>

        </div>

        {{-- OTP --}}
        <div id="feedbackOtpStep"
            class="hidden overflow-y-auto">

            <div class="p-6">

                <div class="text-center mb-6">

                    <div class="w-14 h-14 mx-auto rounded-full bg-red-50 text-red-600 flex items-center justify-center text-2xl mb-3">
                        ✉
                    </div>

                    <h4 class="text-xl font-black text-gray-800">
                        Check your email
                    </h4>

                    <p class="text-xs text-gray-500 mt-2">
                        We sent a 6-digit verification code to
                    </p>

                    <p id="feedbackMaskedEmail"
                        class="text-sm font-black text-gray-700 mt-1">
                    </p>

                    <p class="text-[10px] text-gray-400 mt-2">
                        The code expires in 10 minutes.
                    </p>

                </div>

                <form id="feedbackOtpForm">

                    <input id="feedbackOtp"
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        autocomplete="one-time-code"
                        required
                        placeholder="000000"
                        class="w-full text-center tracking-[0.45em] rounded-xl border border-gray-200 px-4 py-4 text-xl font-black focus:outline-none focus:ring-2 focus:ring-red-300">

                    <p id="otpError"
                        class="hidden text-xs font-bold text-red-600 text-center mt-3">
                    </p>

                    <p id="otpSuccess"
                        class="hidden text-xs font-bold text-green-600 text-center mt-3">
                    </p>

                    <button id="verifyFeedbackBtn"
                        type="submit"
                        class="w-full rounded-xl bg-red-600 py-3 mt-5 text-sm font-black text-white hover:bg-red-700 transition">
                        Verify & Post Feedback
                    </button>

                </form>

                <div class="text-center mt-4">

                    <span class="text-xs text-gray-400">
                        Didn't receive the code?
                    </span>

                    <button id="resendFeedbackBtn"
                        type="button"
                        class="text-xs font-black text-red-600 hover:text-red-700 ml-1 disabled:text-gray-400 disabled:cursor-not-allowed">
                        Resend Code
                    </button>

                </div>

            </div>

        </div>

    </div>
</div>

{{-- BACK TO TOP --}}
<button id="backToTopBtn"
    type="button"
    title="Back to top"
    class="hidden fixed bottom-6 right-6 z-50 w-12 h-12 rounded-full bg-red-600 text-white shadow-xl hover:bg-red-700 transition items-center justify-center text-xl">
    ↑
</button>

<footer class="bg-gray-900 text-gray-400">
    <div class="max-w-6xl mx-auto px-6 py-6 text-center text-xs">
        &copy; {{ date('Y') }} SK360 • Sangguniang Kabataan Federation of Lipa City
    </div>
</footer>

<script>
const csrfToken=document.querySelector('meta[name="csrf-token"]').content;
const publicBase=@json(url('/public-portal'));

/*
|--------------------------------------------------------------------------
| LIKE
|--------------------------------------------------------------------------
*/
document.querySelectorAll('.public-like-btn').forEach(button=>{
    button.addEventListener('click',async()=>{
        if(button.disabled)return;

        button.disabled=true;

        try{
            const response=await fetch(button.dataset.likeUrl,{
                method:'POST',
                headers:{
                    'X-CSRF-TOKEN':csrfToken,
                    'X-Requested-With':'XMLHttpRequest',
                    'Accept':'application/json'
                },
                credentials:'same-origin'
            });

            const data=await response.json();

            if(!response.ok){
                throw new Error(data.message||'Unable to update like.');
            }

            button.querySelector('[data-like-count]').textContent=
                data.likes_count;

            button.querySelector('[data-like-icon]').textContent=
                data.liked?'♥':'♡';

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

/*
|--------------------------------------------------------------------------
| VIEWS
|--------------------------------------------------------------------------
*/
const viewObserver=new IntersectionObserver(entries=>{
    entries.forEach(async entry=>{
        if(!entry.isIntersecting)return;

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

            if(!response.ok)return;

            const data=await response.json();

            const counter=
                card.querySelector('[data-view-count]');

            if(counter){
                counter.textContent=
                    data.views_count;
            }

        }catch(error){}
    });
},{
    threshold:0.5
});

document.querySelectorAll('.announcement-card').forEach(card=>{
    viewObserver.observe(card);
});

/*
|--------------------------------------------------------------------------
| FEEDBACK VARIABLES
|--------------------------------------------------------------------------
*/
const feedbackModal=
    document.getElementById('feedbackModal');

const closeFeedbackModal=
    document.getElementById('closeFeedbackModal');

const feedbackCommentsStep=
    document.getElementById('feedbackCommentsStep');

const feedbackIdentityStep=
    document.getElementById('feedbackIdentityStep');

const feedbackOtpStep=
    document.getElementById('feedbackOtpStep');

const feedbackCommentsContainer=
    document.getElementById('feedbackCommentsContainer');

const feedbackComposer=
    document.getElementById('feedbackComposer');

const feedbackComposerMessage=
    document.getElementById('feedbackComposerMessage');

const openIdentityStepBtn=
    document.getElementById('openIdentityStepBtn');

const backToCommentsBtn=
    document.getElementById('backToCommentsBtn');

const feedbackIdentityForm=
    document.getElementById('feedbackIdentityForm');

const feedbackCommentPreview=
    document.getElementById('feedbackCommentPreview');

const feedbackName=
    document.getElementById('feedbackName');

const feedbackEmail=
    document.getElementById('feedbackEmail');

const feedbackIdentityError=
    document.getElementById('feedbackIdentityError');

const sendFeedbackOtpBtn=
    document.getElementById('sendFeedbackOtpBtn');

const feedbackOtpForm=
    document.getElementById('feedbackOtpForm');

const feedbackOtp=
    document.getElementById('feedbackOtp');

const feedbackMaskedEmail=
    document.getElementById('feedbackMaskedEmail');

const otpError=
    document.getElementById('otpError');

const otpSuccess=
    document.getElementById('otpSuccess');

const verifyFeedbackBtn=
    document.getElementById('verifyFeedbackBtn');

const resendFeedbackBtn=
    document.getElementById('resendFeedbackBtn');

let currentAnnouncementId=null;
let currentFeedbackId=null;
let currentFeedbackButton=null;
let currentFeedbackListUrl=null;
let currentFeedbackSubmitUrl=null;
let pendingFeedbackComment='';

let resendCountdownInterval=null;

/*
|--------------------------------------------------------------------------
| FEEDBACK STEP
|--------------------------------------------------------------------------
*/
function showFeedbackStep(step){
    feedbackCommentsStep.classList.add('hidden');
    feedbackIdentityStep.classList.add('hidden');
    feedbackOtpStep.classList.add('hidden');

    feedbackCommentsStep.classList.remove('flex');

    if(step==='comments'){
        feedbackCommentsStep.classList.remove('hidden');
        feedbackCommentsStep.classList.add('flex');
    }

    if(step==='identity'){
        feedbackIdentityStep.classList.remove('hidden');
    }

    if(step==='otp'){
        feedbackOtpStep.classList.remove('hidden');
    }
}

/*
|--------------------------------------------------------------------------
| COMPOSER MESSAGE
|--------------------------------------------------------------------------
*/
function showComposerMessage(message,type='error'){
    feedbackComposerMessage.textContent=message;

    feedbackComposerMessage.classList.remove(
        'hidden',
        'bg-red-50',
        'text-red-600',
        'bg-green-50',
        'text-green-600'
    );

    if(type==='success'){
        feedbackComposerMessage.classList.add(
            'bg-green-50',
            'text-green-600'
        );
    }else{
        feedbackComposerMessage.classList.add(
            'bg-red-50',
            'text-red-600'
        );
    }
}

function hideComposerMessage(){
    feedbackComposerMessage.classList.add('hidden');
}

/*
|--------------------------------------------------------------------------
| RESEND COUNTDOWN
|--------------------------------------------------------------------------
*/
function resetResendCountdown(){
    if(resendCountdownInterval){
        clearInterval(resendCountdownInterval);
        resendCountdownInterval=null;
    }

    resendFeedbackBtn.disabled=false;
    resendFeedbackBtn.textContent='Resend Code';
}

function startResendCountdown(seconds=60){
    if(resendCountdownInterval){
        clearInterval(resendCountdownInterval);
    }

    let remaining=Math.max(
        1,
        parseInt(seconds)||60
    );

    resendFeedbackBtn.disabled=true;

    function updateCountdown(){
        if(remaining<=0){
            resetResendCountdown();
            return;
        }

        resendFeedbackBtn.textContent=
            `Resend in ${remaining}s`;

        remaining--;
    }

    updateCountdown();

    resendCountdownInterval=setInterval(
        updateCountdown,
        1000
    );
}

/*
|--------------------------------------------------------------------------
| ESCAPE HTML
|--------------------------------------------------------------------------
*/
function escapeHtml(value){
    const div=document.createElement('div');

    div.textContent=value??'';

    return div.innerHTML;
}

/*
|--------------------------------------------------------------------------
| RENDER FEEDBACK
|--------------------------------------------------------------------------
*/
function renderFeedbacks(data){
    const feedbacks=data.feedbacks||[];
    const total=data.total||0;

    if(currentFeedbackButton){
        const counter=
            currentFeedbackButton.querySelector(
                '[data-feedback-count]'
            );

        if(counter){
            counter.textContent=total;
        }
    }

    if(feedbacks.length===0){
        feedbackCommentsContainer.innerHTML=`
            <div class="text-center py-12">
                <div class="text-3xl mb-3">💬</div>

                <h4 class="font-black text-gray-700">
                    No feedback yet
                </h4>

                <p class="text-xs text-gray-400 mt-1">
                    Be the first to share your feedback.
                </p>
            </div>
        `;

        return;
    }

    feedbackCommentsContainer.innerHTML=
        feedbacks.map(feedback=>{

            const safeName=
                escapeHtml(feedback.name);

            const safeComment=
                escapeHtml(feedback.comment);

            const safeTime=
                escapeHtml(feedback.created_at_human);

            const rawName=
                feedback.name??'';

            const firstLetter=
                rawName.trim()
                    ?escapeHtml(
                        rawName.trim()
                            .charAt(0)
                            .toUpperCase()
                    )
                    :'?';

            return `
                <div class="flex items-start gap-3">

                    <div class="w-9 h-9 shrink-0 rounded-full bg-red-100 text-red-600 flex items-center justify-center font-black text-xs">
                        ${firstLetter}
                    </div>

                    <div class="flex-1 min-w-0">

                        <div class="w-fit max-w-[85%] bg-gray-100 rounded-2xl px-4 py-2.5">

                            <p class="text-xs font-black text-gray-800">${safeName}</p>

                            <p class="text-sm text-gray-700 mt-1 whitespace-pre-line break-words">${safeComment}</p>

                        </div>

                        <p class="text-[9px] text-gray-400 mt-1 ml-2">
                            ${safeTime}
                        </p>

                    </div>

                </div>
            `;

        }).join('');
}

/*
|--------------------------------------------------------------------------
| LOAD FEEDBACK
|--------------------------------------------------------------------------
*/
async function loadFeedbacks(){
    if(!currentFeedbackListUrl)return;

    feedbackCommentsContainer.innerHTML=`
        <div class="text-center py-10 text-sm text-gray-400">
            Loading feedback...
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
                data.message||
                'Unable to load feedback.'
            );
        }

        renderFeedbacks(data);

    }catch(error){
        feedbackCommentsContainer.innerHTML=`
            <div class="text-center py-10">

                <p class="text-sm font-bold text-red-600">
                    Unable to load feedback.
                </p>

                <button type="button"
                    onclick="loadFeedbacks()"
                    class="text-xs font-black text-red-600 mt-3">
                    Try Again
                </button>

            </div>
        `;
    }
}

/*
|--------------------------------------------------------------------------
| OPEN FEEDBACK
|--------------------------------------------------------------------------
*/
document.querySelectorAll('.public-feedback-btn').forEach(button=>{
    button.addEventListener('click',()=>{
        currentAnnouncementId=
            button.dataset.announcementId;

        currentFeedbackButton=
            button;

        currentFeedbackListUrl=
            button.dataset.feedbackListUrl;

        currentFeedbackSubmitUrl=
            button.dataset.feedbackSubmitUrl;

        currentFeedbackId=null;
        pendingFeedbackComment='';

        feedbackComposer.value='';
        feedbackComposer.style.height='auto';

        feedbackName.value='';
        feedbackEmail.value='';
        feedbackOtp.value='';

        feedbackIdentityError.classList.add('hidden');
        otpError.classList.add('hidden');
        otpSuccess.classList.add('hidden');

        resetResendCountdown();
        hideComposerMessage();

        document.getElementById(
            'feedbackAnnouncementTitle'
        ).textContent=
            button.dataset.announcementTitle;

        showFeedbackStep('comments');

        feedbackModal.classList.remove('hidden');
        feedbackModal.classList.add('flex');

        loadFeedbacks();

        setTimeout(()=>{
            feedbackComposer.focus();
        },200);
    });
});

/*
|--------------------------------------------------------------------------
| CLOSE FEEDBACK
|--------------------------------------------------------------------------
*/
function closeFeedback(){
    feedbackModal.classList.add('hidden');
    feedbackModal.classList.remove('flex');

    resetResendCountdown();
}

closeFeedbackModal.addEventListener(
    'click',
    closeFeedback
);

feedbackModal.addEventListener('click',event=>{
    if(event.target===feedbackModal){
        closeFeedback();
    }
});

/*
|--------------------------------------------------------------------------
| COMMENT TEXTAREA
|--------------------------------------------------------------------------
*/
feedbackComposer.addEventListener('input',()=>{
    feedbackComposer.style.height='auto';

    feedbackComposer.style.height=
        Math.min(
            feedbackComposer.scrollHeight,
            112
        )+'px';

    hideComposerMessage();
});

/*
|--------------------------------------------------------------------------
| COMMENT → IDENTITY
|--------------------------------------------------------------------------
*/
openIdentityStepBtn.addEventListener('click',()=>{
    const comment=
        feedbackComposer.value.trim();

    if(comment===''){
        showComposerMessage(
            'Write your feedback first before sending.'
        );

        feedbackComposer.focus();

        return;
    }

    pendingFeedbackComment=comment;

    feedbackCommentPreview.textContent=
        pendingFeedbackComment;

    feedbackIdentityError.classList.add('hidden');

    showFeedbackStep('identity');

    setTimeout(()=>{
        feedbackName.focus();
    },100);
});

/*
|--------------------------------------------------------------------------
| BACK TO COMMENTS
|--------------------------------------------------------------------------
*/
backToCommentsBtn.addEventListener('click',()=>{
    showFeedbackStep('comments');

    setTimeout(()=>{
        feedbackComposer.focus();
    },100);
});

/*
|--------------------------------------------------------------------------
| SEND FIRST OTP
|--------------------------------------------------------------------------
*/
feedbackIdentityForm.addEventListener('submit',async event=>{
    event.preventDefault();

    feedbackIdentityError.classList.add('hidden');

    sendFeedbackOtpBtn.disabled=true;
    sendFeedbackOtpBtn.textContent='Sending...';

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
                    name:feedbackName.value,
                    email:feedbackEmail.value,
                    comment:pendingFeedbackComment
                })
            }
        );

        const data=await response.json();

        if(!response.ok){
            let message=
                data.message||
                'Unable to submit feedback.';

            if(data.errors){
                message=
                    Object.values(
                        data.errors
                    ).flat()[0]||message;
            }

            throw new Error(message);
        }

        currentFeedbackId=
            data.feedback_id;

        feedbackMaskedEmail.textContent=
            data.masked_email;

        feedbackOtp.value='';

        otpError.classList.add('hidden');
        otpSuccess.classList.add('hidden');

        showFeedbackStep('otp');

        startResendCountdown(
            data.resend_after||60
        );

        setTimeout(()=>{
            feedbackOtp.focus();
        },100);

    }catch(error){
        feedbackIdentityError.textContent=
            error.message;

        feedbackIdentityError.classList.remove(
            'hidden'
        );

    }finally{
        sendFeedbackOtpBtn.disabled=false;

        sendFeedbackOtpBtn.textContent=
            'Send Verification Code';
    }
});

/*
|--------------------------------------------------------------------------
| VERIFY OTP
|--------------------------------------------------------------------------
*/
feedbackOtpForm.addEventListener('submit',async event=>{
    event.preventDefault();

    if(!currentFeedbackId)return;

    otpError.classList.add('hidden');
    otpSuccess.classList.add('hidden');

    verifyFeedbackBtn.disabled=true;
    verifyFeedbackBtn.textContent='Verifying...';

    try{
        const response=await fetch(
            `${publicBase}/feedback/${currentFeedbackId}/verify`,
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
                    otp:feedbackOtp.value
                })
            }
        );

        const data=await response.json();

        if(!response.ok){
            throw new Error(
                data.message||
                'Verification failed.'
            );
        }

        otpSuccess.textContent=
            data.message;

        otpSuccess.classList.remove('hidden');

        resetResendCountdown();

        setTimeout(async()=>{
            feedbackComposer.value='';
            feedbackComposer.style.height='auto';

            feedbackOtp.value='';

            pendingFeedbackComment='';
            currentFeedbackId=null;

            showFeedbackStep('comments');

            await loadFeedbacks();

            showComposerMessage(
                'Your feedback has been posted.',
                'success'
            );

        },700);

    }catch(error){
        otpError.textContent=
            error.message;

        otpError.classList.remove('hidden');

    }finally{
        verifyFeedbackBtn.disabled=false;

        verifyFeedbackBtn.textContent=
            'Verify & Post Feedback';
    }
});

/*
|--------------------------------------------------------------------------
| RESEND OTP
|--------------------------------------------------------------------------
*/
resendFeedbackBtn.addEventListener('click',async()=>{
    if(
        !currentFeedbackId||
        resendFeedbackBtn.disabled
    ){
        return;
    }

    otpError.classList.add('hidden');
    otpSuccess.classList.add('hidden');

    resendFeedbackBtn.disabled=true;
    resendFeedbackBtn.textContent='Sending...';

    try{
        const response=await fetch(
            `${publicBase}/feedback/${currentFeedbackId}/resend`,
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
            if(data.retry_after){
                startResendCountdown(
                    data.retry_after
                );
            }else{
                resetResendCountdown();
            }

            throw new Error(
                data.message||
                'Unable to resend code.'
            );
        }

        feedbackMaskedEmail.textContent=
            data.masked_email;

        otpSuccess.textContent=
            data.message;

        otpSuccess.classList.remove('hidden');

        feedbackOtp.value='';
        feedbackOtp.focus();

        startResendCountdown(
            data.resend_after||60
        );

    }catch(error){
        otpError.textContent=
            error.message;

        otpError.classList.remove('hidden');
    }
});

/*
|--------------------------------------------------------------------------
| BACK TO TOP
|--------------------------------------------------------------------------
*/
const backToTopBtn=
    document.getElementById('backToTopBtn');

window.addEventListener('scroll',()=>{
    if(window.scrollY>400){
        backToTopBtn.classList.remove('hidden');
        backToTopBtn.classList.add('flex');
    }else{
        backToTopBtn.classList.add('hidden');
        backToTopBtn.classList.remove('flex');
    }
});

backToTopBtn.addEventListener('click',()=>{
    window.scrollTo({
        top:0,
        behavior:'smooth'
    });
});
</script>

</body>
</html>