{{-- File guide: Blade view template for resources/views/sk_chairman/archive.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 Archive')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
<div class="flex h-screen bg-gray-100">
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
        <div class="flex items-center gap-3 mb-4">
    <img src="{{ asset('images/logo.png') }}" class="w-8 h-8 rounded-full object-cover"  alt="logo">
    <div class="leading-tight">
        <h2 class="text-lg font-extrabold tracking-wide">SK 360°</h2>
        <p class="text-[10px] opacity-80">Management System</p>
    </div>
</div>

        <div class="bg-red-500 rounded-lg p-2 flex items-center gap-2 mb-3 shadow text-xs">
            <div class="bg-yellow-400 text-red-600 p-1 rounded-full text-sm">&#128100;</div>
            <div>
                <p class="font-semibold text-xs">{{ $fullName }}</p>
                <p class="text-xs opacity-80">SK Chairman - {{ $barangayName }}</p>
            </div>
        </div>

        <nav class="space-y-1 text-xs">
            @foreach ($menuItems as $item)
                @php $isActive = $item['link'] === $currentUrl; @endphp
                <a href="{{ $item['link'] }}" class="flex items-center gap-2 p-2 rounded-lg {{ $isActive ? 'bg-red-500' : 'hover:bg-red-500 transition' }}">
                    <span class="{{ $isActive ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded text-sm">{!! $item['icon'] !!}</span>
                    <span class="{{ $isActive ? 'text-yellow-300 font-semibold' : '' }} text-xs">{{ $item['label'] }}</span>
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
                <button class="hover:opacity-80">&#128276;</button>
                <div class="relative">
                    <button id="profileDropdownBtn" type="button" class="flex items-center gap-2 font-semibold focus:outline-none hover:opacity-80 transition">
                        <span>{{ $fullName }}</span>
                        <span class="text-[10px]">&#9660;</span>
                    </button>
                    <div id="profileMenu" class="absolute right-0 mt-3 w-48 bg-white rounded-xl shadow-2xl py-2 z-[9999] hidden border border-gray-100">
                        <div class="px-4 py-3 border-b border-gray-50">
                            <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest">Account Settings</p>
                        </div>
                        <a href="{{ route('sk_chairman.profile') }}" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 text-xs flex items-center gap-2 transition">
                            <span>&#128100;</span> View Profile
                        </a>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-3 text-red-600 hover:bg-red-50 text-xs font-bold flex items-center gap-2 transition">
                                <span>&#128682;</span> Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <main class="flex-1 overflow-y-auto bg-gray-50 p-8">
            <div class="mb-6">
                <h1 class="text-4xl font-bold text-gray-900">Document Archive</h1>
                <p class="text-gray-600 text-lg">Long-term record preservation for Barangay {{ $barangayName }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6 mb-8">
                @foreach ($archiveCards as $card)
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 text-center shadow-sm">
                        <div class="mb-2 text-3xl text-gray-700">{!! $card['icon'] !!}</div>
                        <h3 class="text-[11px] font-black text-gray-800 uppercase leading-4">{{ $card['label'] }}</h3>
                        <div class="mt-3 inline-flex rounded-full bg-gray-100 px-3 py-1 text-[10px] font-bold text-gray-600">
                            {{ $card['count'] }} files
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">All Documents</h2>
                        <p class="text-sm text-gray-500">Search and filter archived documents</p>
                    </div>
                    <a href="{{ route('sk_chairman.archive.bulk-download', request()->query()) }}" class="rounded-xl bg-gray-100 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-200 transition">
                        &#128229; Bulk Download
                    </a>
                </div>

                @if (session('archive_error'))
                    <div class="mb-4 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-xs font-bold text-red-600">
                        {{ session('archive_error') }}
                    </div>
                @endif

                <form method="GET" action="{{ route('sk_chairman.archive') }}" class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <label class="sr-only" for="archive_year">Filter by year</label>
                        <select
                            id="archive_year"
                            name="year"
                            onchange="this.form.submit()"
                            class="min-w-[180px] rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-300"
                        >
                            <option value="">All Years</option>
                            @foreach ($filterYears as $year)
                                <option value="{{ $year }}" {{ $filters['year'] === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>

                        <label class="sr-only" for="archive_type">Filter by document type</label>
                        <select
                            id="archive_type"
                            name="type"
                            onchange="this.form.submit()"
                            class="min-w-[200px] rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-300"
                        >
                            <option value="">All Types</option>
                            @foreach ($typeOptions as $value => $label)
                                <option value="{{ $value }}" {{ $filters['type'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($filters['year'] !== '' || $filters['type'] !== '')
                        <a href="{{ route('sk_chairman.archive') }}" class="text-xs font-bold text-red-600 hover:text-red-700">Clear filters</a>
                    @endif
                </form>

                <div class="mb-4 flex items-center justify-between text-xs text-gray-400">
                    <span>Showing {{ $documentCount }} documents</span>
                </div>

                <div class="space-y-3">
                    @forelse ($documents as $document)
                        <div class="flex items-center justify-between rounded-2xl border border-gray-100 bg-white px-4 py-4 hover:bg-gray-50 transition">
                            <div class="flex items-start gap-3">
                                <div class="pt-1 text-red-400">{!! $document->icon !!}</div>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-800">{{ $document->title }}</h3>
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-[10px]">
                                        <span class="rounded-full bg-blue-50 px-2 py-1 font-bold text-blue-600">{{ $document->category }}</span>
                                        <span class="rounded-full bg-gray-100 px-2 py-1 font-bold text-gray-600">{{ $document->badge }}</span>
                                        <span class="rounded-full bg-gray-100 px-2 py-1 font-bold text-gray-600">{{ $document->owner ?: $barangayName }}</span>
                                        <span class="text-gray-400">{{ $document->size }}</span>
                                        <span class="text-gray-400">{{ $document->formatted_date }}</span>
                                    </div>
                                </div>
                            </div>
                            @if ($document->downloadable)
                                <a href="{{ route('sk_chairman.archive.download', [$document->source_type, $document->source_id]) }}" class="text-gray-500 hover:text-red-500 transition" title="Download">
                                    &#128229;
                                </a>
                            @else
                                <span class="cursor-not-allowed text-gray-300" title="No downloadable file">
                                    &#128229;
                                </span>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 italic">No archived documents found.</p>
                    @endforelse
                </div>
            </div>

            <div class="mt-6 rounded-2xl border border-purple-100 bg-purple-50 p-5 text-sm text-purple-700">
                <h3 class="mb-2 font-black">Archive Information</h3>
                <p>This archive shows documents tied to Barangay {{ $barangayName }} plus federation-wide records that apply to SK Chairmen.</p>
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const archiveDropdownBtn = document.getElementById('profileDropdownBtn');
    const archiveProfileMenu = document.getElementById('profileMenu');

    if (archiveDropdownBtn && archiveProfileMenu) {
        archiveDropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            archiveProfileMenu.classList.toggle('hidden');
        });

        window.addEventListener('click', (e) => {
            if (!archiveProfileMenu.contains(e.target) && !archiveDropdownBtn.contains(e.target)) {
                archiveProfileMenu.classList.add('hidden');
            }
        });
    }
</script>
@endpush

