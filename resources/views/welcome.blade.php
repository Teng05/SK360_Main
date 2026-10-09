{{-- File guide: Blade view template for resources/views/welcome.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 | Welcome')

@section('page_css')
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/sk360-landing.css') }}?v={{ @filemtime(public_path('css/sk360-landing.css')) }}">
@endsection

@section('content')
@php
    $features = [
        ['icon' => 'file-text', 'tone' => 'blue', 'title' => 'Accomplishment Reports', 'text' => 'Streamlined submission and compilation of monthly, quarterly, and annual reports with auto-generation.'],
        ['icon' => 'calendar-days', 'tone' => 'yellow', 'title' => 'Meeting Calendar', 'text' => 'Integrated scheduling system for meetings, events, and public activities.'],
        ['icon' => 'message-square', 'tone' => '', 'title' => 'Real-Time Chat', 'text' => 'Instant messaging and video calls for efficient communication among SK officials.'],
        ['icon' => 'chart-column', 'tone' => 'blue', 'title' => 'Analytics Dashboard', 'text' => 'Comprehensive insights on submissions, engagement, and performance metrics.'],
        ['icon' => 'wallet', 'tone' => '', 'title' => 'Budget Documents', 'text' => 'Secure submission and archiving of budget and financial documents with Annual Budget transparency for the public.'],
        ['icon' => 'globe', 'tone' => 'blue', 'title' => 'Public Information', 'text' => 'Access public announcements, event schedules, current barangay SK leadership, and Annual Budget information without an account.'],
    ];

    $benefits = [
        ['icon' => 'users', 'tone' => 'blue', 'title' => 'Real-Time Coordination', 'text' => 'Seamless communication and collaboration across SK officials through digital tools.'],
        ['icon' => 'eye', 'tone' => 'yellow', 'title' => 'Public Transparency', 'text' => 'Provide the community with easy access to public announcements, activities, current SK leadership, and barangay Annual Budget information.'],
        ['icon' => 'shield-check', 'tone' => '', 'title' => 'Secure Access', 'text' => 'Role-based authentication ensures that administrative tools and private information remain protected.'],
    ];

    $teamMembers = [
        ['name'=>'Marielle O. Bautista','role'=>'Web System Development','image'=>'images/team/marielle.jpg','initials'=>'MB'],
        ['name'=>'Paul Vincent M. Monje','role'=>'Mobile Application Development','image'=>'images/team/paul.jpg','initials'=>'PM'],
        ['name'=>'Alexa E. Sumadsad','role'=>'Mobile UI/UX Design & Project Manager','image'=>'images/team/alexa.jpg','initials'=>'AS'],
        ['name'=>'Giorgia Schen C. Janda','role'=>'Project Documentation','image'=>'images/team/giorgia.jpg','initials'=>'GJ'],
    ];
@endphp

<div class="sk-landing" id="home">
    <header class="sk-lnav">
        <div class="sk-container sk-lnav__inner">
            <a href="#home" class="sk-lnav__brand">
                <img src="{{ asset('images/logo.png') }}?v={{ @filemtime(public_path('images/logo.png')) }}" alt="SK 360 Logo">
                <span>SK 360&deg;</span>
            </a>

            <nav class="sk-lnav__links" aria-label="Page sections">
                <a href="#features">Features</a>
                <a href="#benefits">Benefits</a>
                <a href="#contact">Contact</a>
                <button type="button" data-download-open aria-haspopup="dialog" aria-controls="skDownloadModal">Download App</button>
            </nav>

            @if(Route::has('login'))
                <a href="{{ route('login') }}" class="sk-btn sk-btn--primary sk-lnav__cta">
                    Official Login
                    @include('landing.icon', ['icon' => 'arrow-right', 'iconSize' => 16])
                </a>
            @endif

            <button type="button" class="sk-icon-btn sk-icon-btn--filled sk-lnav__toggle" aria-label="Open menu" data-sk-landing-menu>
                @include('landing.icon', ['icon' => 'menu', 'iconSize' => 21])
            </button>
        </div>
    </header>

    <section class="sk-hero">
        <div class="sk-container sk-hero__grid">
            <div>
                <h1>
                    <span class="sk-hero__title" style="display: block;">
                        Empowering the youth, <em>transforming communities.</em>
                    </span>
                </h1>

                <p class="sk-hero__lead">
                    A centralized digital platform for transparent reporting,
                    real-time communication, and coordinated engagement in
                    Sangguniang Kabataan operations. Stay informed through
                    public announcements, activities, current SK leadership,
                    and Annual Budget information across Lipa City.
                </p>

                <div class="sk-hero__actions">
                    @if(Route::has('public.home'))
                        <a href="{{ route('public.home') }}" class="sk-btn sk-btn--primary sk-btn--lg">
                            Explore Public Portal
                            @include('landing.icon', ['icon' => 'arrow-right', 'iconSize' => 18])
                        </a>
                    @endif

                    @if(Route::has('login'))
                        <a href="{{ route('login') }}" class="sk-btn sk-btn--secondary sk-btn--lg">
                            Official Login
                        </a>
                    @endif
                </div>
            </div>

            <div class="sk-preview" aria-hidden="true">
                <div class="sk-preview__window">
                    <div class="sk-preview__bar">
                        <i></i><i></i><i></i>
                        <span class="sk-preview__url">SK 360&deg; &middot; Lipa City</span>
                    </div>

                    <div class="sk-preview__body">
                        <div class="sk-preview__side">
                            <span class="is-active"></span>
                            <span></span>
                            <span style="width: 80%;"></span>
                            <span></span>
                            <span style="width: 70%;"></span>
                            <span></span>
                        </div>

                        <div class="sk-preview__main">
                            <div class="sk-preview__photo">
                                <img src="{{ asset('images/lipacity.png') }}" alt="">
                                <span class="sk-preview__caption">
                                    @include('landing.icon', ['icon' => 'map-pin', 'iconSize' => 14])
                                    Lipa City
                                </span>
                            </div>

                            <div class="sk-preview__tiles">
                                <div class="sk-preview__tile">
                                    <span class="sk-icon-tile sk-icon-tile--blue">@include('landing.icon', ['icon' => 'file-text', 'iconSize' => 16])</span>
                                    Reports
                                </div>
                                <div class="sk-preview__tile">
                                    <span class="sk-icon-tile sk-icon-tile--yellow">@include('landing.icon', ['icon' => 'calendar-days', 'iconSize' => 16])</span>
                                    Calendar
                                </div>
                                <div class="sk-preview__tile">
                                    <span class="sk-icon-tile">@include('landing.icon', ['icon' => 'message-square', 'iconSize' => 16])</span>
                                    Chat
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sk-float sk-float--a">
                    <span class="sk-icon-tile sk-icon-tile--sm sk-icon-tile--green">@include('landing.icon', ['icon' => 'wallet', 'iconSize' => 18])</span>
                    <span>
                        <b>Annual Budget</b>
                        <small>Public transparency</small>
                    </span>
                </div>

                <div class="sk-float sk-float--b">
                    <span class="sk-icon-tile sk-icon-tile--sm">@include('landing.icon', ['icon' => 'shield-check', 'iconSize' => 18])</span>
                    <span>
                        <b>Secure Access</b>
                        <small>Role-based authentication</small>
                    </span>
                </div>
            </div>
        </div>
    </section>

    {{-- FEATURES --}}
    <section class="sk-lsection sk-lsection--tint" id="features">
        <div class="sk-container">
            <div class="sk-lsection__head">
                <h2 class="sk-lsection__title">Platform Features</h2>
                <p class="sk-lsection__lead">
                    Comprehensive tools designed for efficient SK governance
                    and public access to information
                </p>
            </div>

            <div class="sk-feature-grid">
                @foreach ($features as $feature)
                    <div class="sk-feature">
                        <span class="sk-icon-tile sk-icon-tile--lg {{ $feature['tone'] ? 'sk-icon-tile--' . $feature['tone'] : '' }}">
                            @include('landing.icon', ['icon' => $feature['icon'], 'iconSize' => 24])
                        </span>

                        <h3>{{ $feature['title'] }}</h3>

                        <p>{{ $feature['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- BENEFITS --}}
    <section class="sk-lsection" id="benefits">
        <div class="sk-container">
            <div class="sk-lsection__head">
                <h3 class="sk-lsection__title">Why SK 360&deg;?</h3>
                <p class="sk-lsection__lead">
                    Transforming youth governance through technology,
                    transparency, and collaboration
                </p>
            </div>

            <div class="sk-benefit-grid">
                @foreach ($benefits as $benefit)
                    <div class="sk-benefit">
                        <span class="sk-benefit__index">0{{ $loop->iteration }}</span>

                        <span class="sk-icon-tile sk-icon-tile--lg {{ $benefit['tone'] ? 'sk-icon-tile--' . $benefit['tone'] : '' }}">
                            @include('landing.icon', ['icon' => $benefit['icon'], 'iconSize' => 24])
                        </span>

                        <h4>{{ $benefit['title'] }}</h4>

                        <p>{{ $benefit['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="sk-lsection" style="padding-top: 0;">
        <div class="sk-container">
            <div class="sk-cta">
                <h2>Stay Connected with SK 360&deg;</h2>

                <p>
                    Explore public announcements, upcoming activities,
                    current Sangguniang Kabataan leadership, and Annual Budget
                    information across Lipa City.
                </p>

                <div class="sk-cta__actions">
                    @if(Route::has('public.home'))
                        <a href="{{ route('public.home') }}" class="sk-btn sk-btn--white sk-btn--lg">
                            Explore Public Portal
                            @include('landing.icon', ['icon' => 'arrow-right', 'iconSize' => 18])
                        </a>
                    @endif

                    @if(Route::has('login'))
                        <a href="{{ route('login') }}" class="sk-btn sk-btn--outline-white sk-btn--lg">
                            Official Login
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- FOOTER --}}
    <footer class="sk-footer" id="contact">
        <div class="sk-container">
            <div class="sk-footer__grid">
                <div>
                    <a href="#home" class="sk-lnav__brand">
                        <img src="{{ asset('images/logo.png') }}?v={{ @filemtime(public_path('images/logo.png')) }}" alt="SK 360 Logo">
                        <span>SK 360&deg;</span>
                    </a>

                    <p class="sk-footer__about">
                        Empowering youth governance through digital
                        transformation, transparency, and coordinated leadership.
                    </p>
                </div>

                <div>
                    <h3>Public Portal</h3>

                    <ul>
                        @if(Route::has('public.home'))
                            <li><a href="{{ route('public.home') }}">Public Home</a></li>
                        @endif

                        @if(Route::has('public.announcements'))
                            <li><a href="{{ route('public.announcements') }}">Announcements</a></li>
                        @endif

                        @if(Route::has('public.calendar'))
                            <li><a href="{{ route('public.calendar') }}">Calendar</a></li>
                        @endif

                        @if(Route::has('public.leadership'))
                            <li><a href="{{ route('public.leadership') }}">Leadership</a></li>
                        @endif

                        @if(Route::has('public.budgets'))
                            <li><a href="{{ route('public.budgets') }}">Annual Budget</a></li>
                        @endif
                    </ul>
                </div>

                <div>
                    <h3>Officials</h3>

                    <ul>
                        @if(Route::has('login'))
                            <li><a href="{{ route('login') }}">Official Login</a></li>
                        @endif

                        <li><a href="#features">Platform Features</a></li>
                        <li><a href="#benefits">About SK 360&deg;</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>

                <div>
                    <h3>Get in Touch</h3>

                    <ul class="sk-footer__contact">
                        <li>
                            @include('landing.icon', ['icon' => 'mail', 'iconSize' => 17])
                            <a href="mailto:skfederationlipacity23@gmail.com">skfederationlipacity23@gmail.com</a>
                        </li>

                        {{-- Restore a telephone row here once the official office number is confirmed. --}}

                        <li>
                            @include('landing.icon', ['icon' => 'map-pin', 'iconSize' => 17])
                            <span>5th Floor, Left Wing, New Lipa City Hall,<br>Areza Estate, Barangay Bulacnin,<br>Lipa City, Batangas</span>
                        </li>

                        <li>
                            @include('landing.icon', ['icon' => 'globe', 'iconSize' => 17])
                            <a href="https://www.facebook.com/share/19osdKNcpx/?mibextid=wwXIfr" target="_blank" rel="noopener noreferrer">Sangguniang Kabataan Lipa City</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="sk-footer__bottom">
                <p>&copy; {{ date('Y') }} SK 360&deg;. All rights reserved.</p>

                <div class="sk-footer__legal">
                    <span class="sk-footer__credit">Developed by the SK 360&deg; Development Team<br>as an academic capstone project.</span>
                    <button type="button" class="sk-footer__team-trigger" data-team-open aria-haspopup="dialog" aria-controls="skTeamModal">Meet the Team</button>
                    <a href="{{ route('legal.privacy-policy') }}">Privacy Policy</a>
                    <a href="{{ route('legal.terms-of-service') }}">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <div class="sk-team-modal" id="skTeamModal" role="dialog" aria-modal="true" aria-labelledby="skTeamTitle" aria-hidden="true" hidden>
        <div class="sk-team-modal__backdrop" data-team-close></div>
        <section class="sk-team-modal__panel" tabindex="-1">
            <button type="button" class="sk-team-modal__close" data-team-close aria-label="Close team dialog">&times;</button>
            <span class="sk-team-modal__eyebrow">The people behind SK 360&deg;</span>
            <h2 id="skTeamTitle">Meet the Team</h2>
            <p class="sk-team-modal__intro">Developed as an academic capstone project.</p>
            <div class="sk-team-grid">
                @foreach($teamMembers as $member)
                    <article class="sk-team-card">
                        <div class="sk-team-card__photo">
                            <span class="sk-team-card__initials" aria-hidden="true">{{ $member['initials'] }}</span>
                            @if(file_exists(public_path($member['image'])))
                                <img src="{{ asset($member['image']) }}" alt="{{ $member['name'] }}" loading="lazy" onerror="this.remove()">
                            @endif
                        </div>
                        <h3>{{ $member['name'] }}</h3>
                        <p>{{ $member['role'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    </div>

    <div class="sk-download-modal" id="skDownloadModal" role="dialog" aria-modal="true" aria-labelledby="skDownloadTitle" aria-describedby="skDownloadDescription" aria-hidden="true" hidden tabindex="-1">
        <div class="sk-download-modal__backdrop" data-download-backdrop></div>
        <section class="sk-download-modal__panel">
            <button type="button" class="sk-download-modal__close" data-download-close aria-label="Close app download dialog">&times;</button>
            <div class="sk-download-modal__qr">
                @if(file_exists(public_path('images/mobile-app-qr.jpg')))
                    <img src="{{ asset('images/mobile-app-qr.jpg') }}" alt="QR code to download the SK360 Android APK" width="260" height="260">
                @else
                    <p class="sk-download-modal__missing">QR code image is not available yet. Use the download button or add the provided image at <code>public/images/mobile-app-qr.png</code>.</p>
                @endif
            </div>
            <div class="sk-download-modal__details">
                <span class="sk-download-modal__eyebrow">SK 360&deg; MOBILE APP</span>
                <h2 id="skDownloadTitle">Get SK360 for Android</h2>
                <p id="skDownloadDescription">Scan the QR code using your phone to access the SK360 app download.</p>
                <a class="sk-download-modal__button" href="https://drive.usercontent.google.com/download?id=194h2O9TwWd04J3XyswPvgQmW7fxHUA-9&amp;export=download&amp;authuser=0" target="_blank" rel="noopener noreferrer">Download Android APK</a>
                <span class="sk-download-modal__source">Android APK &middot; Google Drive</span>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Landing navbar: border once scrolled, and the small-screen menu toggle.
    (function () {
        const nav = document.querySelector('.sk-lnav');
        const toggle = document.querySelector('[data-sk-landing-menu]');

        if (!nav) {
            return;
        }

        const onScroll = () => nav.classList.toggle('is-scrolled', window.scrollY > 8);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        toggle?.addEventListener('click', () => nav.classList.toggle('is-open'));
        nav.querySelectorAll('.sk-lnav__links a').forEach((link) => {
            link.addEventListener('click', () => nav.classList.remove('is-open'));
        });

        const downloadModal = document.getElementById('skDownloadModal');
        const downloadOpen = document.querySelector('[data-download-open]');
        const downloadClose = downloadModal?.querySelector('[data-download-close]');
        let previousBodyOverflow = '';
        let downloadOpener = null;
        const downloadFocusable = () => [...downloadModal.querySelectorAll('button:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])')];
        const closeDownloadModal = () => {
            if (!downloadModal || downloadModal.hidden) return;
            downloadModal.hidden = true;
            downloadModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = previousBodyOverflow;
            downloadOpener?.focus();
        };
        downloadOpen?.addEventListener('click', () => {
            if (!downloadModal) return;
            downloadOpener = document.activeElement;
            previousBodyOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            downloadModal.hidden = false;
            downloadModal.setAttribute('aria-hidden', 'false');
            downloadClose?.focus();
        });
        downloadClose?.addEventListener('click', closeDownloadModal);
        downloadModal?.addEventListener('click', (event) => {
            if (event.target === downloadModal || event.target.hasAttribute('data-download-backdrop')) closeDownloadModal();
        });
        document.addEventListener('keydown', (event) => {
            if (!downloadModal || downloadModal.hidden) return;
            if (event.key === 'Escape') {
                closeDownloadModal();
                return;
            }
            if (event.key === 'Tab') {
                const focusable = downloadFocusable();
                const first = focusable[0];
                const last = focusable[focusable.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        });

        const modal = document.getElementById('skTeamModal');
        const opener = document.querySelector('[data-team-open]');
        const closeButtons = modal?.querySelectorAll('[data-team-close]');
        const closeModal = () => {
            if (!modal) return;
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            opener?.focus();
        };
        opener?.addEventListener('click', () => {
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            modal.querySelector('.sk-team-modal__close')?.focus();
        });
        closeButtons?.forEach((button) => button.addEventListener('click', closeModal));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal && !modal.hidden) closeModal();
        });
    })();
</script>
@endpush
