{{-- File guide: Blade view template for resources/views/sk_chairman/announcements.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK Chairman Announcements')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
<div class="flex h-screen bg-gray-100">
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
        <div class="flex items-center gap-3 mb-4">
    <img src="{{ asset('images/sk logo.png') }}" class="w-8 h-8 rounded-full object-cover"  alt="logo">
    <div class="leading-tight">
        <h2 class="text-lg font-extrabold tracking-wide">SK 360°</h2>
        <p class="text-[10px] opacity-80">Management System</p>
    </div>
</div>


        @include('shared.sidebar-user-card')

        <nav class="space-y-1 text-xs">
            @foreach ($menuItems as $item)
                @php $isActive = $currentUrl === $item['link']; @endphp
                <a href="{{ $item['link'] }}" class="flex items-center gap-2 p-2 rounded-lg {{ $isActive ? 'bg-red-500' : 'hover:bg-red-500' }}">
                    <span class="{{ $isActive ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded">{!! $item['icon'] !!}</span>
                    <span class="{{ $isActive ? 'text-yellow-300 font-semibold' : '' }}">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="flex-1 flex flex-col">
        @include('shared.topbar', ['legacyAccountMenu' => false])

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
<script>
    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');

    notifBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        notifDropdown.classList.toggle('hidden');
        userDropdown.classList.add('hidden');
    });

    userMenuBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        userDropdown.classList.toggle('hidden');
        notifDropdown.classList.add('hidden');
    });

    document.addEventListener('click', function (e) {
        if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
            notifDropdown.classList.add('hidden');
        }

        if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.classList.add('hidden');
        }
    });
</script>
@endpush

