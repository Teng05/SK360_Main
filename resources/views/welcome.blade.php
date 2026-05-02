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
            <a href="#home" class="logo">SK 360&deg;</a>

            <ul class="nav-links">
                <li><a href="#features">Features</a></li>
                <li><a href="#benefits">Benefits</a></li>
                <li><a href="#announcement">Announcement</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>

            <a href="#features" class="btn-yellow nav-cta">Get Started</a>
        </nav>

        <section class="hero-panel">
            <div class="hero-copy">
                <h1>SK 360&deg;</h1>
                <h2>Empowering the youth, transforming communities.</h2>
                <p>
                    A centralized digital platform for transparent reporting, real-time communication,
                    and coordinated engagement in Sangguniang Kabataan operations. Promoting efficiency,
                    accountability, and seamless leadership transitions.
                </p>

                <div class="hero-buttons">
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="btn-red">Login</a>
                    @endif

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn-white">Register</a>
                    @endif
                </div>
            </div>

            <div class="hero-visual">
                <div class="hero-image-wrap">
                    <img src="{{ asset('images/lipa-city.jpg') }}" alt="Lipa City">
                </div>
            </div>
        </section>
    </header>

    <section class="features-section" id="features">
        <div class="features-header">
            <h2>Platform Features</h2>
            <p>Comprehensive tools designed for efficient SK governance and youth engagement</p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="icon-box blue"></div>
                <h3>Accomplishment Reports</h3>
                <p>Streamlined submission and compilation of monthly, quarterly, and annual reports with auto-generation.</p>
            </div>

            <div class="feature-card">
                <div class="icon-box yellow"></div>
                <h3>Meeting Calendar</h3>
                <p>Integrated scheduling system for meetings, events, and activities with RSVP tracking.</p>
            </div>

            <div class="feature-card">
                <div class="icon-box red"></div>
                <h3>Real-Time Chat</h3>
                <p>Instant messaging and video calls for efficient communication and virtual meetings.</p>
            </div>

            <div class="feature-card">
                <div class="icon-box yellow"></div>
                <h3>Analytics Dashboard</h3>
                <p>Comprehensive insights on submissions, engagement, and performance metrics.</p>
            </div>

            <div class="feature-card">
                <div class="icon-box red"></div>
                <h3>Budget Documents</h3>
                <p>Secure storage and archiving of budget documents for leadership transitions.</p>
            </div>

            <div class="feature-card">
                <div class="icon-box blue"></div>
                <h3>Gamified Rankings</h3>
                <p>Leaderboards showcasing top-performing barangays based on engagement and submissions.</p>
            </div>
        </div>
    </section>

    <section class="benefits" id="benefits">
        <div class="benefits-header">
            <h3>Why SK 360&deg;?</h3>
            <p>Transforming youth governance through technology, transparency, and collaboration</p>
        </div>

        <div class="card-grid-3">
            <div class="card">
                <div class="icon blue"></div>
                <h4>Real-Time Coordination</h4>
                <p>Seamless communication and collaboration across all SK officials with instant messaging.</p>
            </div>

            <div class="card">
                <div class="icon yellow"></div>
                <h4>Gamified Engagement</h4>
                <p>Motivate youth participation through rankings, achievements, and recognition.</p>
            </div>

            <div class="card">
                <div class="icon red"></div>
                <h4>Secure Access</h4>
                <p>Role-based authentication ensures data security and proper access control.</p>
            </div>
        </div>
    </section>

    <section class="event-updates" id="announcement">
        <div class="update-box red-bg">
            <h3>Latest Updates</h3>

            <div class="class-update">
                <span class="update-date">May 15, 2025</span>
                <h4>New Reporting Guidelines Released</h4>
                <p>Updated templates and submission procedures for Q2 2025 reports are now available.</p>
            </div>

            <div class="class-update">
                <span class="update-date">May 20, 2025</span>
                <h4>Monthly Federation Meeting</h4>
                <p>All SK Chairmen are invited to attend the monthly coordination meeting via video call.</p>
            </div>

            <div class="class-update">
                <span class="update-date">May 25, 2025</span>
                <h4>Youth Leadership Training</h4>
                <p>Register now for the upcoming leadership development program for SK officials.</p>
            </div>

            <a href="#" class="btn-yellow">View All Announcements</a>
        </div>

        <div class="update-box white-bg">
            <h3 class="events-title">Upcoming Events</h3>

            <div class="event-update">
                <div class="event-date">
                    <span class="day">20</span>
                    <span class="month">MAY</span>
                </div>
                <div class="event-details">
                    <h4>Federation Meeting</h4>
                    <p>2:00 PM</p>
                </div>
            </div>

            <div class="event-update">
                <div class="event-date">
                    <span class="day">25</span>
                    <span class="month">MAY</span>
                </div>
                <div class="event-details">
                    <h4>Leadership Training</h4>
                    <p>9:00 AM</p>
                </div>
            </div>

            <div class="event-update">
                <div class="event-date">
                    <span class="day">30</span>
                    <span class="month">MAY</span>
                </div>
                <div class="event-details">
                    <h4>Report Deadline</h4>
                    <p>5:00 PM</p>
                </div>
            </div>

            <a href="#" class="btn-blue">View Full Calendar</a>
        </div>
    </section>

    <section class="cta-section">
        <h2>Ready to Transform Youth Governance?</h2>
        <p>
            Join SK 360&deg; today and experience seamless coordination, transparent reporting,
            and empowered youth leadership in Lipa City.
        </p>

        @if (Route::has('register'))
            <a href="{{ route('register') }}" class="btn-large-red">Get Started Now</a>
        @elseif (Route::has('login'))
            <a href="{{ route('login') }}" class="btn-large-red">Get Started Now</a>
        @endif
    </section>

    <footer class="footer" id="contact">
        <div class="footer-top">
            <div class="footer-column brand-info">
                <div class="footer-logo">
                    <img src="{{ asset('images/logo.png') }}" alt="SK 360 Logo">
                    <span class="logo-text">SK 360&deg;</span>
                </div>
                <p>Empowering youth governance through digital transformation and transparent coordination.</p>
            </div>

            <div class="footer-column">
                <h3>Platform</h3>
                <ul>
                    <li><a href="#">Dashboard</a></li>
                    <li><a href="#">Reports</a></li>
                    <li><a href="#">Calendar</a></li>
                    <li><a href="#">Chat</a></li>
                </ul>
            </div>

            <div class="footer-column">
                <h3>Support</h3>
                <ul>
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Guidelines</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">FAQs</a></li>
                </ul>
            </div>

            <div class="footer-column contact-info">
                <h3>Get in Touch</h3>
                <ul>
                    <li><span class="icon">&#9993;</span> sk360@lipacity.gov.ph</li>
                    <li><span class="icon">&#9742;</span> (043) 756-1234</li>
                    <li><span class="icon">&#128205;</span> City Hall, Lipa City, Batangas, Philippines</li>
                </ul>
            </div>
        </div>

        <hr class="footer-divider">

        <div class="footer-bottom">
            <p>&copy; 2026 SK 360&deg;. All rights reserved.</p>

            <div class="legal-links">
                <a href="#">Privacy Policy</a>
                <span class="separator">|</span>
                <a href="#">Terms of Service</a>
            </div>
        </div>
    </footer>
@endsection
