{{-- Existing public destinations, presented as a wrapping web navigation bar. --}}
<nav class="sk-public-nav" aria-label="Public portal">
    <a href="{{ route('public.home') }}" @if(request()->routeIs('public.home')) aria-current="page" @endif>Home</a>
    <a href="{{ route('public.announcements') }}" @if(request()->routeIs('public.announcements')) aria-current="page" @endif>Announcements</a>
    <a href="{{ route('public.calendar') }}" @if(request()->routeIs('public.calendar')) aria-current="page" @endif>Calendar</a>
    <a href="{{ route('public.leadership') }}" @if(request()->routeIs('public.leadership')) aria-current="page" @endif>Leadership</a>
    <a href="{{ route('public.budgets') }}" @if(request()->routeIs('public.budgets')) aria-current="page" @endif>Annual Budget &amp; LYDP</a>
</nav>
