@extends('layouts.app')

@section('title', 'SK Secretary Announcements')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
<div class="flex h-screen bg-gray-100">
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
        <div class="flex items-center gap-2 mb-3">
            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" class="w-7 h-7" alt="logo">
            <h2 class="text-base font-bold text-white">SK 360&deg;</h2>
        </div>

        <div class="bg-red-500 rounded-lg p-2 flex items-center gap-2 mb-3 shadow text-xs">
            <div class="bg-yellow-400 text-red-600 h-9 w-9 rounded-full flex items-center justify-center font-bold border-2 border-red-400 overflow-hidden shadow-inner flex-shrink-0">
                <span class="text-xs">{{ $initials }}</span>
            </div>
            <div>
                <p class="font-semibold text-[11px] truncate">{{ $fullName }}</p>
                <p class="opacity-80 text-[10px] uppercase font-black tracking-tighter truncate">SK Secretary - {{ $barangayName }}</p>
            </div>
        </div>

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
        <div class="bg-red-600 text-white px-6 py-3 flex justify-between items-center shadow relative">
            <div class="w-1/4"></div>
            <div class="w-1/3">
                <input type="text" placeholder="Search..." class="w-full px-4 py-2 rounded-full text-black focus:outline-none text-sm">
            </div>
            <div class="w-1/4 flex justify-end items-center gap-5 text-sm">
                <button class="hover:opacity-80">🔔</button>
                <div class="relative">
                    <button id="userMenuBtn" class="flex items-center gap-2 font-semibold focus:outline-none hover:opacity-80 transition">
                        <span>{{ $fullName }}</span>
                    </button>

                    <div id="userDropdown" class="hidden absolute right-0 mt-3 w-64 bg-white rounded-2xl shadow-xl border overflow-hidden z-50">
                        <div class="px-5 py-4 font-semibold text-gray-800 border-b">
                            My Account
                        </div>
                        <a href="#" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-100 transition">
                            <span>👤</span>
                            <span class="text-gray-700">Profile Settings</span>
                        </a>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full text-left flex items-center gap-3 px-5 py-3 text-red-500 hover:bg-gray-100 transition">
                                <span>↩️</span>
                                <span>Log Out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

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
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');

    userMenuBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        userDropdown.classList.toggle('hidden');
    });

    document.addEventListener('click', function (e) {
        if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.classList.add('hidden');
        }
    });
</script>
@endpush
