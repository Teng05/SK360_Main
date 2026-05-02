{{-- File guide: Blade view template for resources/views/sk_secretary/announcements.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK Secretary Announcements')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
<div class="flex h-screen bg-gray-100 overflow-hidden">
    {{-- Shared secretary sidebar/topbar layout --}}
    @include('sk_secretary.partials.sidebar')
    <div class="flex-1 flex flex-col overflow-hidden">
        @include('sk_secretary.partials.topbar')
        {{-- Announcements list --}}
        <div class="p-8 overflow-y-auto">
            <h1 class="text-3xl font-bold text-gray-800">Announcements</h1>
            <p class="text-gray-500 mb-8">Official communications and updates for SK federation</p>

            <div class="max-w-4xl space-y-6">
                @forelse ($announcements as $row)
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition relative">
                        <div class="absolute top-4 right-6 flex gap-2">
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase {{ $row->priority_badge }}">
                                {{ $row->priority }}
                            </span>
                            <span class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-[10px] font-bold uppercase">
                                {{ $row->visibility_label }}
                            </span>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="text-red-500 text-xl mt-1">&#128226;</div>
                            <div class="flex-1">
                                <h2 class="text-xl font-bold text-gray-800">{{ $row->title }}</h2>
                                <p class="text-xs text-gray-400 font-medium mb-4">
                                    By {{ trim($row->author_name) ?: 'SK Federation President' }} &bull; {{ \Carbon\Carbon::parse($row->created_at)->format('Y-m-d') }}
                                </p>

                                <p class="text-gray-600 text-sm leading-relaxed mb-4">
                                    {!! nl2br(e($row->content)) !!}
                                </p>

                                <div class="flex items-center text-gray-400 text-[10px] font-bold">
                                    <span>{{ $row->views }} views</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-400 italic">No announcements found.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('sk_secretary.partials.dropdown-scripts')
@endpush
