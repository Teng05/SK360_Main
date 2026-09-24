{{-- File guide: Blade view template for resources/views/sk_pres/archive.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 Archive')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
<div class="flex h-screen bg-gray-100">
    @include('partials.app.sidebar')

    <div class="flex-1 flex flex-col">
        @include('partials.app.topbar', ['accountButtonId' => 'profileDropdownBtn', 'accountMenuId' => 'profileMenu', 'bindBell' => true])

        <main class="flex-1 overflow-y-auto bg-gray-50 p-8">
            <div class="mb-6">
                <span class="sk-eyebrow"><span class="sk-dot"></span>Archive</span>
                <h1 class="text-4xl font-bold text-gray-900">Document Archive</h1>
                <p class="text-gray-600 text-lg">Long-term record preservation and retrieval system</p>
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
                        <p class="text-sm text-gray-500">Search and filter archived documents by administration, barangay, year, and type</p>
                    </div>
                    <a href="{{ route('sk_pres.archive.bulk-download', request()->query()) }}" class="rounded-xl bg-gray-100 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-200 transition">
                        <span class="inline-flex align-[-3px]">@include('partials.ui.icon', ['icon' => 'download', 'iconSize' => 16])</span> Bulk Download
                    </a>
                </div>

                @if (session('archive_error'))
                    <div class="mb-4 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-xs font-bold text-red-600">
                        {{ session('archive_error') }}
                    </div>
                @endif

                <form method="GET" action="{{ route('sk_pres.archive') }}" class="mb-5 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:flex">
                        <label class="sr-only" for="archive_term">Filter by administration</label>
                        <select
                            id="archive_term"
                            name="term_id"
                            onchange="this.form.submit()"
                            class="min-w-[220px] rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-300"
                        >
                            <option value="">All Administrations</option>
                            @foreach ($administrationTerms as $term)
                                <option value="{{ $term->term_id }}" {{ (int) ($filters['term_id'] ?? 0) === (int) $term->term_id ? 'selected' : '' }}>
                                    Administration {{ $term->start_year }}-{{ $term->end_year }}
                                </option>
                            @endforeach
                        </select>

                        <label class="sr-only" for="archive_barangay">Filter by barangay</label>
                        <select
                            id="archive_barangay"
                            name="barangay_id"
                            onchange="this.form.submit()"
                            class="min-w-[240px] rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-300"
                        >
                            <option value="">All Barangays</option>
                            @foreach ($barangays as $barangay)
                                <option value="{{ $barangay->barangay_id }}" {{ (int) ($filters['barangay_id'] ?? 0) === (int) $barangay->barangay_id ? 'selected' : '' }}>
                                    Barangay {{ $barangay->barangay_name }}
                                </option>
                            @endforeach
                        </select>

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

                    @if (($filters['term_id'] ?? null) || ($filters['barangay_id'] ?? null) || $filters['year'] !== '' || $filters['type'] !== '')
                        <a href="{{ route('sk_pres.archive') }}" class="text-xs font-bold text-red-600 hover:text-red-700">Clear filters</a>
                    @endif
                </form>

                <div class="mb-4 flex items-center justify-between text-xs text-gray-400">
                    <span>Showing {{ $documentCount }} documents</span>

                    @if ($filters['term_id'] ?? null)
                        @php
                            $selectedAdministration = $administrationTerms->firstWhere('term_id', $filters['term_id']);
                        @endphp

                        @if ($selectedAdministration)
                            <span class="rounded-full bg-red-50 px-3 py-1 font-bold text-red-600">
                                Administration {{ $selectedAdministration->start_year }}-{{ $selectedAdministration->end_year }}
                            </span>
                        @endif
                    @else
                        <span>All completed administrations</span>
                    @endif
                </div>

                <div class="space-y-3">
                    @forelse ($documents as $document)
                        <div class="flex items-center justify-between rounded-2xl border border-gray-100 bg-white px-4 py-4 hover:bg-gray-50 transition">
                            <div class="flex items-start gap-3">
                                <div class="pt-1 text-red-400">{!! $document->icon !!}</div>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-800">{{ $document->title }}</h3>
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-[10px]">
                                        <span class="rounded-full bg-red-50 px-2 py-1 font-bold text-red-600">
                                            {{ $document->administration_label }}
                                        </span>
                                        <span class="rounded-full bg-blue-50 px-2 py-1 font-bold text-blue-600">{{ $document->category }}</span>
                                        <span class="rounded-full bg-gray-100 px-2 py-1 font-bold text-gray-600">{{ $document->badge }}</span>
                                        <span class="rounded-full bg-gray-100 px-2 py-1 font-bold text-gray-600">{{ $document->owner ?: 'Federation' }}</span>
                                        <span class="text-gray-400">{{ $document->size }}</span>
                                        <span class="text-gray-400">{{ $document->formatted_date }}</span>
                                    </div>
                                </div>
                            </div>
                            @if ($document->downloadable)
                                <a href="{{ route('sk_pres.archive.download', [$document->source_type, $document->source_id]) }}" class="text-gray-500 hover:text-red-500 transition" title="Download">
                                    <span class="inline-flex align-[-3px]">@include('partials.ui.icon', ['icon' => 'download', 'iconSize' => 16])</span>
                                </a>
                            @else
                                <span class="cursor-not-allowed text-gray-300" title="No downloadable file">
                                    <span class="inline-flex align-[-3px]">@include('partials.ui.icon', ['icon' => 'download', 'iconSize' => 16])</span>
                                </span>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 italic">No archived documents found for the selected filters.</p>
                    @endforelse
                </div>
            </div>

            <div class="mt-6 rounded-2xl border border-purple-100 bg-purple-50 p-5 text-sm text-purple-700">
                <h3 class="mb-2 font-black">Archive Information</h3>
                <p>Records from completed SK administrations are preserved here and organized by administration, barangay, year, and document type for easier historical retrieval.</p>
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