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

    <div class="flex-1 flex flex-col overflow-hidden">
        @include('sk_secretary.partials.topbar')

        {{-- Dashboard content --}}
        <div class="p-6 overflow-y-auto">
            <h1 class="text-2xl font-bold mb-4">Good morning, {{ $firstName }}!</h1>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                @foreach ($summaryCards as $card)
                    <div class="{{ $card['classes'] }} p-5 rounded-xl shadow">
                        <h2 class="text-2xl font-bold">{{ $card['value'] }}</h2>
                        <p class="text-sm">{{ $card['label'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="bg-white p-5 rounded-xl shadow mb-6">
                <h2 class="font-semibold mb-3">Quick Actions</h2>

                <div class="flex gap-3 flex-wrap">
                    <a href="{{ route('sk_secretary.reports') }}" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg">
                        Submit Report
                    </a>

                    <a href="{{ route('sk_secretary.budget') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                        Upload Files
                    </a>

                    <a href="{{ route('sk_secretary.meetings') }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg">
                        Join Meeting
                    </a>
                </div>
            </div>

            @include('shared.wall-feed')
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('sk_secretary.partials.dropdown-scripts')
@endpush
