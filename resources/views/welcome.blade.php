{{-- File guide: Blade view template for resources/views/welcome.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 | Welcome')

@section('page_css')
    <style>
        {!! file_get_contents(resource_path('css/index.css')) !!}
    </style>
@endsection

@section('content')
    <header class="hero-shell" id="home">
        <nav class="navbar">
            <div class="footer-column brand-info">
                <div class="footer-logo">
                    <img src="{{ asset('images/logo.png') }}"
                        class="w-8 h-8 rounded-full object-cover"
                        alt="SK 360 Logo">

                    <span class="logo-text">
                        SK 360&deg;
                    </span>
                </div>
            </div>

            <ul class="nav-links">
                <li>
                    <a href="#features">
                        Features
                    </a>
                </li>

                <li>
                    <a href="#benefits">
                        Benefits
                    </a>
                </li>

                <li>
                    <a href="#contact">
                        Contact
                    </a>
                </li>
            </ul>

            @if(Route::has('login'))
                <a href="{{ route('login') }}"
                    class="btn-yellow nav-cta">
                    Official Login
                </a>
            @endif
        </nav>

        <section class="hero-panel">

            <div class="hero-copy">

                <h1>
                    SK 360&deg;
                </h1>

                <h2>
                    Empowering the youth, transforming communities.
                </h2>

                <p>
                    A centralized digital platform for transparent reporting,
                    real-time communication, and coordinated engagement in
                    Sangguniang Kabataan operations. Stay informed through
                    public announcements, activities, and current SK leadership
                    across Lipa City.
                </p>

                <div class="hero-buttons">

                    @if(Route::has('public.home'))
                        <a href="{{ route('public.home') }}"
                            class="btn-red">
                            Explore Public Portal
                        </a>
                    @endif

                </div>

            </div>

            <div class="hero-visual">
                <div class="hero-image-wrap">
                    <img src="{{ asset('images/lipacity.png') }}"
                        alt="Lipa City">
                </div>
            </div>

        </section>
    </header>

    {{-- FEATURES --}}
    <section class="features-section" id="features">

        <div class="features-header">

            <h2>
                Platform Features
            </h2>

            <p>
                Comprehensive tools designed for efficient SK governance
                and public access to information
            </p>

        </div>

        <div class="features-grid">

            <div class="feature-card">
                <div class="icon-box blue"></div>

                <h3>
                    Accomplishment Reports
                </h3>

                <p>
                    Streamlined submission and compilation of monthly,
                    quarterly, and annual reports with auto-generation.
                </p>
            </div>

            <div class="feature-card">
                <div class="icon-box yellow"></div>

                <h3>
                    Meeting Calendar
                </h3>

                <p>
                    Integrated scheduling system for meetings, events,
                    and public activities.
                </p>
            </div>

            <div class="feature-card">
                <div class="icon-box red"></div>

                <h3>
                    Real-Time Chat
                </h3>

                <p>
                    Instant messaging and video calls for efficient
                    communication among SK officials.
                </p>
            </div>

            <div class="feature-card">
                <div class="icon-box yellow"></div>

                <h3>
                    Analytics Dashboard
                </h3>

                <p>
                    Comprehensive insights on submissions, engagement,
                    and performance metrics.
                </p>
            </div>

            <div class="feature-card">
                <div class="icon-box red"></div>

                <h3>
                    Budget Documents
                </h3>

                <p>
                    Secure storage and archiving of budget documents
                    for leadership transitions.
                </p>
            </div>

            <div class="feature-card">
                <div class="icon-box blue"></div>

                <h3>
                    Public Information
                </h3>

                <p>
                    Access public announcements, event schedules,
                    and current barangay SK leadership without an account.
                </p>
            </div>

        </div>

    </section>

    {{-- BENEFITS --}}
    <section class="benefits" id="benefits">

        <div class="benefits-header">

            <h3>
                Why SK 360&deg;?
            </h3>

            <p>
                Transforming youth governance through technology,
                transparency, and collaboration
            </p>

        </div>

        <div class="card-grid-3">

            <div class="card">
                <div class="icon blue"></div>

                <h4>
                    Real-Time Coordination
                </h4>

                <p>
                    Seamless communication and collaboration across
                    SK officials through digital tools.
                </p>
            </div>

            <div class="card">
                <div class="icon yellow"></div>

                <h4>
                    Public Transparency
                </h4>

                <p>
                    Provide the community with easy access to public
                    announcements, activities, and current SK leadership.
                </p>
            </div>

            <div class="card">
                <div class="icon red"></div>

                <h4>
                    Secure Access
                </h4>

                <p>
                    Role-based authentication ensures that administrative
                    tools and private information remain protected.
                </p>
            </div>

        </div>

    </section>

    {{-- CTA --}}
    <section class="cta-section">

        <h2>
            Stay Connected with SK 360&deg;
        </h2>

        <p>
            Explore public announcements, upcoming activities,
            and the current Sangguniang Kabataan leadership
            across Lipa City.
        </p>

    </section>

    {{-- FOOTER --}}
    <footer class="footer" id="contact">

        <div class="footer-top">

            <div class="footer-column brand-info">

                <div class="footer-logo">

                    <img src="{{ asset('images/logo.png') }}"
                        alt="SK 360 Logo">

                    <span class="logo-text">
                        SK 360&deg;
                    </span>

                </div>

                <p>
                    Empowering youth governance through digital
                    transformation, transparency, and coordinated leadership.
                </p>

            </div>

            <div class="footer-column">

                <h3>
                    Public Portal
                </h3>

                <ul>

                    @if(Route::has('public.home'))
                        <li>
                            <a href="{{ route('public.home') }}">
                                Public Home
                            </a>
                        </li>
                    @endif

                    @if(Route::has('public.announcements'))
                        <li>
                            <a href="{{ route('public.announcements') }}">
                                Announcements
                            </a>
                        </li>
                    @endif

                    @if(Route::has('public.calendar'))
                        <li>
                            <a href="{{ route('public.calendar') }}">
                                Calendar
                            </a>
                        </li>
                    @endif

                    @if(Route::has('public.leadership'))
                        <li>
                            <a href="{{ route('public.leadership') }}">
                                Leadership
                            </a>
                        </li>
                    @endif

                </ul>

            </div>

            <div class="footer-column">

                <h3>
                    Officials
                </h3>

                <ul>

                    @if(Route::has('login'))
                        <li>
                            <a href="{{ route('login') }}">
                                Official Login
                            </a>
                        </li>
                    @endif

                    <li>
                        <a href="#features">
                            Platform Features
                        </a>
                    </li>

                    <li>
                        <a href="#benefits">
                            About SK 360&deg;
                        </a>
                    </li>

                    <li>
                        <a href="#contact">
                            Contact
                        </a>
                    </li>

                </ul>

            </div>

            <div class="footer-column contact-info">

                <h3>
                    Get in Touch
                </h3>

                <ul>

                    <li>
                        <span class="icon">
                            &#9993;
                        </span>

                        sk360@lipacity.gov.ph
                    </li>

                    <li>
                        <span class="icon">
                            &#9742;
                        </span>

                        (043) 756-1234
                    </li>

                    <li>
                        <span class="icon">
                            &#128205;
                        </span>

                        City Hall, Lipa City, Batangas, Philippines
                    </li>

                </ul>

            </div>

        </div>

        <hr class="footer-divider">

        <div class="footer-bottom">

            <p>
                &copy; {{ date('Y') }} SK 360&deg;. All rights reserved.
            </p>

            <div class="legal-links">

                <a href="#">
                    Privacy Policy
                </a>

                <span class="separator">
                    |
                </span>

                <a href="#">
                    Terms of Service
                </a>

            </div>

        </div>

    </footer>
@endsection