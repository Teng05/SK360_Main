{{-- Shared public portal footer. Pages may pass $footerNote for page-specific notes. --}}
<footer class="sk-footer !pt-12 mt-12">
    <div class="max-w-6xl mx-auto px-6">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-8">
            <div class="max-w-sm">
                <a href="{{ route('public.home') }}" class="sk-lnav__brand">
                    <img src="{{ asset('images/logo.png') }}" alt="SK360 Logo">
                    <span class="leading-tight">
                        <span class="block">SK 360&deg;</span>
                        <span class="block text-[10.5px] font-extrabold uppercase tracking-[0.12em] text-gray-500">Public Information Portal</span>
                    </span>
                </a>

                @if (!empty($footerNote))
                    <p class="sk-footer__about">{{ $footerNote }}</p>
                @endif
            </div>

            <div>
                <h3>Public Portal</h3>
                <ul class="grid grid-cols-2 gap-x-10">
                    <li><a href="{{ route('public.home') }}">Home</a></li>
                    <li class="!mt-0"><a href="{{ route('public.announcements') }}">Announcements</a></li>
                    <li><a href="{{ route('public.calendar') }}">Calendar</a></li>
                    <li><a href="{{ route('public.leadership') }}">Leadership</a></li>
                    <li><a href="{{ route('public.budgets') }}">Annual Budget</a></li>
                    <li><a href="{{ url('/') }}">Main Website</a></li>
                </ul>
            </div>
        </div>

        <div class="sk-footer__bottom">
            <p>&copy; {{ date('Y') }} SK360 &bull; Sangguniang Kabataan Federation of Lipa City</p>
        </div>
    </div>
</footer>
