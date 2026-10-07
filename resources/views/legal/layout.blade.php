{{-- Shared layout for public SK360 legal pages. --}}
@extends('layouts.app')

@section('page_css')
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/sk360-landing.css') }}?v={{ @filemtime(public_path('css/sk360-landing.css')) }}">
@endsection

@section('content')
<div class="sk-landing sk-legal">
    <header class="sk-legal__header">
        <div class="sk-container sk-legal__header-inner">
            <a href="{{ url('/') }}" class="sk-lnav__brand">
                <img src="{{ asset('images/logo.png') }}" alt="SK 360 Logo">
                <span>SK 360&deg;</span>
            </a>
            <a href="{{ url('/') }}" class="sk-legal__back">Back to Home</a>
        </div>
    </header>
    <main class="sk-container sk-legal__main">
        <article class="sk-legal__card">
            <div class="sk-legal__eyebrow">SK 360&deg; &middot; Public Information</div>
            <h1>@yield('legal-title')</h1>
            <p class="sk-legal__intro">@yield('legal-intro')</p>
            <p class="sk-legal__updated">Last Updated: October 7, 2026</p>
            <div class="sk-legal__content">@yield('legal-content')</div>
            <a href="{{ url('/') }}" class="sk-legal__back sk-legal__back--bottom">Back to Home</a>
        </article>
    </main>
    <footer class="sk-legal__footer">&copy; {{ date('Y') }} SK 360&deg; &middot; Sangguniang Kabataan Federation of Lipa City</footer>
</div>
@endsection
