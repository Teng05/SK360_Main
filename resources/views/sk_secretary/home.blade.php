{{-- File guide: Blade view template for resources/views/sk_secretary/home.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK Secretary Dashboard')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
<div class="flex h-screen bg-gray-100 overflow-hidden">
    {{-- Shared secretary sidebar/topbar layout --}}
    @include('sk_secretary.partials.sidebar')

    <div class="flex-1 flex flex-col overflow-hidden min-w-0">
        @include('sk_secretary.partials.topbar')

        {{-- Dashboard content --}}
        <div class="flex-1 p-8 overflow-y-auto">
            <div class="sk-page-head">
                <div class="sk-page-head__text">
                    <span class="sk-eyebrow"><span class="sk-dot"></span>{{ now()->format('l, F j, Y') }}</span>
                    <h1 class="sk-page-title">Good morning, {{ $firstName }}!</h1>
                </div>

                {{-- Quick actions --}}
                <div class="sk-page-head__actions">
                    <a href="{{ route('sk_secretary.meetings') }}" class="sk-btn sk-btn--secondary">
                        @include('partials.ui.icon', ['icon' => 'video', 'iconSize' => 17])
                        Meeting
                    </a>
                    <a href="{{ route('sk_secretary.budget') }}" class="sk-btn sk-btn--secondary">
                        @include('partials.ui.icon', ['icon' => 'wallet', 'iconSize' => 17])
                        Budget
                    </a>
                    <a href="{{ route('sk_secretary.reports') }}" class="sk-btn sk-btn--primary">
                        @include('partials.ui.icon', ['icon' => 'file-text', 'iconSize' => 17])
                        Report
                    </a>
                </div>
            </div>

            @include('partials.app.summary-cards')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    @include('shared.wall-feed')
                </div>

                <div class="space-y-6">
                    @include('partials.app.calendar-preview', ['calendarUrl' => route('sk_secretary.calendar')])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('sk_secretary.partials.dropdown-scripts')
@endpush
