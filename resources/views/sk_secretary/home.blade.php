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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    @include('shared.wall-feed')
                </div>

                <div class="space-y-6">
                    <div class="bg-white p-4 rounded-xl shadow border">
                        <h2 class="font-bold text-gray-700 uppercase text-[10px] mb-3 tracking-widest border-b pb-2">Quick Actions</h2>
                        <div class="grid grid-cols-3 gap-2">
                            <a href="{{ route('sk_secretary.reports') }}" class="flex aspect-square flex-col items-center justify-center rounded-lg bg-red-600 p-3 text-white transition hover:bg-red-700">
                                <span class="text-xl">&#128196;</span>
                                <span class="mt-1 text-center text-[8px] font-bold">REPORT</span>
                            </a>
                            <a href="{{ route('sk_secretary.budget') }}" class="flex aspect-square flex-col items-center justify-center rounded-lg bg-blue-600 p-3 text-white transition hover:bg-blue-700">
                                <span class="text-xl">&#128229;</span>
                                <span class="mt-1 text-center text-[8px] font-bold">BUDGET</span>
                            </a>
                            <a href="{{ route('sk_secretary.meetings') }}" class="flex aspect-square flex-col items-center justify-center rounded-lg bg-yellow-500 p-3 text-white transition hover:bg-yellow-600">
                                <span class="text-xl">&#128222;</span>
                                <span class="mt-1 text-center text-[8px] font-bold">MEETING</span>
                            </a>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-xl shadow border">
                        <h2 class="font-bold text-gray-700 uppercase text-[10px] mb-3 tracking-widest border-b pb-2">Calendar Preview</h2>
                        <div class="space-y-4">
                            @forelse ($upcomingEvents as $event)
                                <div class="border-l-4 border-red-500 pl-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-[11px] font-black text-gray-800 uppercase leading-none">{{ $event->title }}</p>
                                        <span class="rounded-full px-2 py-1 text-[8px] font-bold uppercase {{ $event->type_badge }}">
                                            {{ $event->type_label }}
                                        </span>
                                    </div>
                                    <p class="text-[9px] text-gray-500 mt-2">{{ \Carbon\Carbon::parse($event->start_datetime)->format('M d, Y h:i A') }}</p>
                                    <p class="text-[9px] text-gray-400 mt-1">{{ $event->location ?: 'No location provided' }}</p>
                                </div>
                            @empty
                                <p class="text-[10px] text-gray-400 italic">No scheduled events.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('sk_secretary.partials.dropdown-scripts')
@endpush
