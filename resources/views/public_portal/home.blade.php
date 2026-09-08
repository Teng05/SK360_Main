<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SK360 Public Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 text-gray-800">

{{-- HEADER --}}
<header class="sticky top-0 z-40 bg-red-600 text-white shadow">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">

        <div class="flex items-center gap-3">
            <img src="{{ asset('images/logo.png') }}"
                class="w-10 h-10 rounded-full object-cover"
                alt="SK360 Logo">

            <div>
                <h1 class="text-xl font-black">
                    SK 360°
                </h1>

                <p class="text-[10px] opacity-80 uppercase tracking-widest">
                    Public Information Portal
                </p>
            </div>
        </div>

        <a href="{{ url('/') }}"
            class="text-xs font-bold hover:text-yellow-300 transition">
            ← Main Website
        </a>

    </div>
</header>

<main>

{{-- HERO --}}
<section class="bg-gradient-to-br from-red-600 to-red-700 text-white">

    <div class="max-w-4xl mx-auto px-6 py-14 text-center">

        <p class="text-xs font-black uppercase tracking-[0.2em] text-red-100 mb-3">
            City of Lipa
        </p>

        <h2 class="text-4xl md:text-5xl font-black mb-4">
            SK360 Public Portal
        </h2>

        <p class="max-w-2xl mx-auto text-red-100 leading-relaxed">
            Access official public information from the Sangguniang Kabataan
            Federation of Lipa City. Stay informed about announcements,
            upcoming activities, and current barangay SK leadership.
        </p>

        <div class="flex flex-wrap justify-center gap-3 mt-8">

            <a href="{{ route('public.announcements') }}"
                class="bg-white text-red-600 px-6 py-3 rounded-xl font-black text-sm hover:bg-red-50 transition">
                📢 Announcements
            </a>

            <a href="{{ route('public.calendar') }}"
                class="bg-white text-red-600 px-6 py-3 rounded-xl font-black text-sm hover:bg-red-50 transition">
                📅 Calendar
            </a>

            <a href="{{ route('public.leadership') }}"
                class="bg-white text-red-600 px-6 py-3 rounded-xl font-black text-sm hover:bg-red-50 transition">
                👥 Leadership
            </a>

        </div>

    </div>

</section>

{{-- LATEST UPDATES --}}
<section class="max-w-4xl mx-auto px-6 py-12">

    <div class="mb-6">

        <p class="text-xs font-black uppercase tracking-widest text-red-600">
            Latest Information
        </p>

        <h2 class="text-3xl font-black mt-1">
            Latest Public Updates
        </h2>

        <p class="text-sm text-gray-500 mt-2">
            Recent announcements and public activities from SK360.
        </p>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        @forelse(collect($latestUpdates)->take(4) as $item)

            @if($item->feed_type === 'announcement')

                {{-- ANNOUNCEMENT PREVIEW --}}
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">

                    <div class="flex items-start gap-3 mb-4">

                        <div class="w-11 h-11 bg-red-50 text-red-600 rounded-xl flex items-center justify-center shrink-0">
                            📢
                        </div>

                        <div class="min-w-0">

                            <p class="text-[9px] font-black uppercase tracking-widest text-red-600">
                                Announcement
                            </p>

                            <h3 class="font-black text-gray-800 mt-1">
                                {{ $item->title }}
                            </h3>

                        </div>

                    </div>

                    <p class="text-sm text-gray-600 leading-relaxed">
                        {{ \Illuminate\Support\Str::limit($item->content,180) }}
                    </p>

                    <div class="border-t border-gray-100 mt-5 pt-4">

                        <div class="flex items-center justify-between gap-3">

                            <div class="min-w-0">

                                <p class="text-xs font-bold text-gray-600 truncate">
                                    {{ $item->author_name }}
                                </p>

                                <p class="text-[10px] text-gray-400 mt-1">
                                    {{ \Carbon\Carbon::parse($item->created_at)->diffForHumans() }}
                                </p>

                            </div>

                            <a href="{{ route('public.announcements') }}"
                                class="text-xs font-black text-red-600 hover:text-red-700 whitespace-nowrap">
                                View →
                            </a>

                        </div>

                    </div>

                </div>

            @else

                {{-- EVENT PREVIEW --}}
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">

                    <div class="flex items-start gap-3 mb-4">

                        <div class="w-11 h-11 bg-red-50 text-red-600 rounded-xl flex items-center justify-center shrink-0">
                            📅
                        </div>

                        <div class="min-w-0">

                            <p class="text-[9px] font-black uppercase tracking-widest text-red-600">
                                Public Event
                            </p>

                            <h3 class="font-black text-gray-800 mt-1">
                                {{ $item->title }}
                            </h3>

                        </div>

                    </div>

                    @if($item->description)
                        <p class="text-sm text-gray-600 leading-relaxed">
                            {{ \Illuminate\Support\Str::limit($item->description,180) }}
                        </p>
                    @endif

                    <div class="space-y-1 mt-4 text-xs text-gray-500">

                        <p>
                            📅
                            {{ \Carbon\Carbon::parse($item->start_datetime)->format('M d, Y') }}
                        </p>

                        <p>
                            🕐
                            {{ \Carbon\Carbon::parse($item->start_datetime)->format('h:i A') }}
                        </p>

                        <p class="truncate">
                            📍
                            {{ $item->location ?: 'Location not specified' }}
                        </p>

                    </div>

                    <div class="border-t border-gray-100 mt-5 pt-4 text-right">

                        <a href="{{ route('public.calendar') }}"
                            class="text-xs font-black text-red-600 hover:text-red-700">
                            View Calendar →
                        </a>

                    </div>

                </div>

            @endif

        @empty

            <div class="md:col-span-2 bg-white border border-gray-100 rounded-2xl px-6 py-12 text-center">

                <div class="text-4xl mb-3">
                    📭
                </div>

                <h3 class="font-black text-gray-700">
                    No public updates available
                </h3>

                <p class="text-xs text-gray-400 mt-2">
                    Public announcements and activities will appear here when available.
                </p>

            </div>

        @endforelse

    </div>

</section>

{{-- PUBLIC INFORMATION NOTE --}}
<section class="bg-white border-y border-gray-100">

    <div class="max-w-4xl mx-auto px-6 py-10">

        <div class="flex flex-col md:flex-row md:items-center gap-5">

            <div class="w-14 h-14 bg-red-50 text-red-600 rounded-2xl flex items-center justify-center text-2xl shrink-0">
                🏛️
            </div>

            <div>

                <h2 class="text-xl font-black text-gray-800">
                    Public Information Access
                </h2>

                <p class="text-sm text-gray-500 leading-relaxed mt-2">
                    The SK360 Public Portal provides community members with
                    access to public announcements, activity schedules, and
                    current SK leadership information without requiring an account.
                </p>

            </div>

        </div>

    </div>

</section>

</main>

{{-- BACK TO TOP --}}
<button id="backToTopBtn"
    type="button"
    title="Back to top"
    class="hidden fixed bottom-6 right-6 z-50 w-12 h-12 rounded-full bg-red-600 text-white shadow-xl hover:bg-red-700 transition items-center justify-center text-xl">
    ↑
</button>

{{-- FOOTER --}}
<footer class="bg-gray-900 text-gray-400">

    <div class="max-w-6xl mx-auto px-6 py-6 text-center text-xs">
        &copy; {{ date('Y') }} SK360 • Sangguniang Kabataan Federation of Lipa City
    </div>

</footer>

<script>
const backToTopBtn=document.getElementById('backToTopBtn');

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