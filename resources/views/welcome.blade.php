{{-- File guide: Blade view template for resources/views/welcome.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 | Welcome')

{{-- Styled by the shared design system (public/css/sk360-ui.css, .sk-landing). --}}
@section('page_css')
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
@endphp

<div class="sk-landing">
    <header class="sk-lnav" id="home">
        <div class="sk-container sk-lnav__inner">
            <a href="#home" class="sk-lnav__brand">
                <img src="{{ asset('images/logo.png') }}" alt="SK 360 Logo">
                <span>SK 360&deg;</span>
            </a>

            <nav class="sk-lnav__links" aria-label="Page sections">
                <a href="#features">Features</a>
                <a href="#benefits">Benefits</a>
                <a href="#contact">Contact</a>
            </nav>

            @if(Route::has('login'))
                <a href="{{ route('login') }}" class="sk-btn sk-btn--primary sk-lnav__cta">
                    Official Login
                    @include('partials.ui.icon', ['icon' => 'arrow-right', 'iconSize' => 16])
                </a>
            @endif

            <button type="button" class="sk-icon-btn sk-icon-btn--filled sk-lnav__toggle" aria-label="Open menu" data-sk-landing-menu>
                @include('partials.ui.icon', ['icon' => 'menu', 'iconSize' => 21])
            </button>
        </div>
    </header>

    <section class="sk-hero">
        <div class="sk-container sk-hero__grid">
            <div>
                <h1>
                    <span class="sk-hero__brand">
                        <span class="sk-hero__brand-dot">
                            @include('partials.ui.icon', ['icon' => 'sparkles', 'iconSize' => 14])
                        </span>
                        SK 360&deg;
                    </span>

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
                            @include('partials.ui.icon', ['icon' => 'arrow-right', 'iconSize' => 18])
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
                                    @include('partials.ui.icon', ['icon' => 'map-pin', 'iconSize' => 14])
                                    Lipa City
                                </span>
                            </div>

                            <div class="sk-preview__tiles">
                                <div class="sk-preview__tile">
                                    <span class="sk-icon-tile sk-icon-tile--blue">@include('partials.ui.icon', ['icon' => 'file-text', 'iconSize' => 16])</span>
                                    Reports
                                </div>
                                <div class="sk-preview__tile">
                                    <span class="sk-icon-tile sk-icon-tile--yellow">@include('partials.ui.icon', ['icon' => 'calendar-days', 'iconSize' => 16])</span>
                                    Calendar
                                </div>
                                <div class="sk-preview__tile">
                                    <span class="sk-icon-tile">@include('partials.ui.icon', ['icon' => 'message-square', 'iconSize' => 16])</span>
                                    Chat
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sk-float sk-float--a">
                    <span class="sk-icon-tile sk-icon-tile--sm sk-icon-tile--green">@include('partials.ui.icon', ['icon' => 'wallet', 'iconSize' => 18])</span>
                    <span>
                        <b>Annual Budget</b>
                        <small>Public transparency</small>
                    </span>
                </div>

                <div class="sk-float sk-float--b">
                    <span class="sk-icon-tile sk-icon-tile--sm">@include('partials.ui.icon', ['icon' => 'shield-check', 'iconSize' => 18])</span>
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
                <span class="sk-eyebrow"><span class="sk-dot"></span>Features</span>
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
                            @include('partials.ui.icon', ['icon' => $feature['icon'], 'iconSize' => 24])
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
                <span class="sk-eyebrow"><span class="sk-dot"></span>About SK 360&deg;</span>
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
                            @include('partials.ui.icon', ['icon' => $benefit['icon'], 'iconSize' => 24])
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
                            @include('partials.ui.icon', ['icon' => 'arrow-right', 'iconSize' => 18])
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
                        <img src="{{ asset('images/logo.png') }}" alt="SK 360 Logo">
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
                            @include('partials.ui.icon', ['icon' => 'mail', 'iconSize' => 17])
                            sk360@lipacity.gov.ph
                        </li>

                        <li>
                            @include('partials.ui.icon', ['icon' => 'phone', 'iconSize' => 17])
                            (043) 756-1234
                        </li>

                        <li>
                            @include('partials.ui.icon', ['icon' => 'map-pin', 'iconSize' => 17])
                            City Hall, Lipa City, Batangas, Philippines
                        </li>
                    </ul>
                </div>
            </div>

            <div class="sk-footer__bottom">
                <p>&copy; {{ date('Y') }} SK 360&deg;. All rights reserved.</p>

                <div class="sk-footer__legal">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>
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
    })();
</script>
@endpush
