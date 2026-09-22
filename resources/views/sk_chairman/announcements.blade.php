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

        <main class="flex-1 overflow-y-auto bg-slate-100 p-5 md:p-8">
            <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="mb-1 text-xs font-bold uppercase tracking-[0.18em] text-red-600">SK Federation</p>
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Announcements</h1>
                    <p class="mt-1 text-sm text-slate-500">Official communications and updates for SK federation</p>
                </div>
                <div class="rounded-full border border-red-100 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm">
                    {{ $announcements->count() }} {{ \Illuminate\Support\Str::plural('announcement', $announcements->count()) }}
                </div>
            </div>

            <div class="grid w-full grid-cols-1 gap-5 xl:grid-cols-2">
                @forelse ($announcements as $row)
                    <article class="relative rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md md:p-6">
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-50 text-xl text-red-600">&#128226;</span>
                                <div class="min-w-0">
                                    <h2 class="truncate text-lg font-bold text-slate-900">{{ $row->title }}</h2>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        By {{ trim($row->author_name) ?: 'SK Federation President' }} &bull; {{ \Carbon\Carbon::parse($row->created_at)->format('M d, Y') }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-wrap justify-end gap-2">
                            <span class="rounded-full px-3 py-1 text-[10px] font-bold uppercase {{ $row->priority_badge }}">
                                {{ $row->priority }}
                            </span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-bold uppercase text-slate-500">
                                {{ $row->visibility_label }}
                            </span>
                            </div>
                        </div>

                        <p class="border-t border-slate-100 pt-4 text-sm leading-6 text-slate-700">
                            {!! nl2br(e($row->content)) !!}
                        </p>
                    </article>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center text-sm text-slate-500">
                        No announcements found.
                    </div>
                @endforelse
            </div>
        </main>
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
