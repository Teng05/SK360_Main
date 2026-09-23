{{-- File guide: Blade view template for resources/views/sk_pres/consolidation.blade.php. --}}
@extends('layouts.app')

@section('title', 'Report Consolidation')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
<div class="flex h-screen overflow-hidden bg-gray-100">
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
        <div class="flex items-center gap-3 mb-4">
            <img src="{{ asset('images/logo.png') }}" class="w-8 h-8 rounded-full object-cover" alt="logo">
            <div class="leading-tight">
                <h2 class="text-lg font-extrabold tracking-wide">SK 360°</h2>
                <p class="text-[10px] opacity-80">Management System</p>
            </div>
        </div>

        <div class="bg-red-500 rounded-lg p-2 flex items-center gap-2 mb-3 shadow text-xs">
            <div class="bg-yellow-400 text-red-600 p-1 rounded-full text-sm">&#128100;</div>
            <div>
                <p class="font-semibold text-xs">SK President</p>
                <p class="text-xs opacity-80">Active Role</p>
            </div>
        </div>

        <nav class="space-y-1 text-xs">
            @foreach ($menuItems as $item)
                <a href="{{ $item['link'] }}"
                   class="flex items-center gap-2 p-2 rounded-lg {{ $item['link'] === $currentUrl ? 'bg-red-500' : 'hover:bg-red-500 transition' }}">
                    <span class="{{ $item['link'] === $currentUrl ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded text-sm">{!! $item['icon'] !!}</span>
                    <span class="{{ $item['link'] === $currentUrl ? 'text-yellow-300 font-semibold' : '' }} text-xs">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="flex-1 flex flex-col">
        <div class="bg-red-600 text-white px-6 py-3 flex justify-between items-center shadow">
            <input type="text" placeholder="Search..." class="px-4 py-2 rounded-full text-black w-1/3 focus:outline-none">

            <div class="flex items-center gap-3 relative">
                <div class="relative">
                    <button id="notifBtn" type="button" class="text-xl hover:bg-red-500 p-2 rounded-lg transition">&#128276;</button>

                    <div id="notifDropdown" class="hidden absolute right-0 mt-3 w-72 bg-white rounded-2xl shadow-xl border z-50 overflow-hidden">
                        <div class="px-4 py-3 font-semibold border-b text-gray-800">Notifications</div>
                        <div class="max-h-64 overflow-y-auto">
                            <div class="px-4 py-3 hover:bg-gray-100 text-sm text-gray-700">No notifications yet</div>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <button id="userMenuBtn" type="button" class="flex items-center gap-2 hover:bg-red-500 px-3 py-2 rounded-lg transition">
                        <span class="font-semibold">{{ $fullName }}</span>
                    </button>

                    <div id="userDropdown" class="hidden absolute right-0 mt-3 w-64 bg-white rounded-2xl shadow-xl border overflow-hidden z-50">
                        <div class="px-5 py-4 font-semibold text-gray-800 border-b">My Account</div>

                        <a href="{{ route('sk_pres.profile') }}" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-100 transition">
                            <span>&#128100;</span>
                            <span class="text-gray-700">Profile Settings</span>
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left flex items-center gap-3 px-5 py-3 text-red-500 hover:bg-gray-100 transition">
                                <span>&#8617;</span>
                                <span>Log Out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <main class="flex-1 overflow-y-auto p-10 bg-gray-100">
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-2">Report Consolidation</h1>
                <p class="text-gray-600 text-lg">
                    Automatically compile barangay reports into unified monthly, quarterly, and annual documents.
                </p>
            </div>

            @if (session('quality_status'))
                <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-700">
                    {{ session('quality_status') }}
                </div>
            @endif

            @if ($errors->has('quality_review'))
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                    {{ $errors->first('quality_review') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
                @foreach ($stats as $stat)
                    <div class="bg-white rounded-2xl p-5 shadow-sm">
                        <p class="text-sm text-gray-500 mb-2">{{ $stat['label'] }}</p>
                        <h2 class="text-4xl font-bold {{ $stat['valueClass'] }}">{{ $stat['value'] }}</h2>
                    </div>
                @endforeach
            </div>

            <section class="bg-white rounded-2xl shadow-sm p-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Barangay Submissions</h2>
                        <p class="text-gray-500 text-sm">Review citywide report completion and archive consolidated outputs.</p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a href="{{ $downloadRoute }}" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl text-sm font-medium">
                            &#11015; Download Consolidated PDF
                        </a>
                    </div>
                </div>

                <form method="GET" action="{{ route('sk_pres.consolidation') }}" class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-4 mb-5">
                    <div class="w-full max-w-sm">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Search Barangay</label>
                        <div class="border rounded-xl px-4 py-3 flex items-center gap-3">
                            <span class="text-gray-400">&#128269;</span>
                            <input id="barangaySearch" type="text" placeholder="Search barangay..." class="w-full outline-none text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 w-full xl:w-auto">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Year</label>
                            <select name="year" class="w-full border rounded-xl px-4 py-3 text-sm text-gray-600 outline-none bg-white">
                                @foreach ($years as $year)
                                    <option value="{{ $year }}" {{ (int) $filters['year'] === (int) $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Period</label>
                            <select name="period" id="periodFilter" class="w-full border rounded-xl px-4 py-3 text-sm text-gray-600 outline-none bg-white">
                                <option value="all" {{ $filters['period'] === 'all' ? 'selected' : '' }}>All Reports</option>
                                <option value="monthly" {{ $filters['period'] === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="quarterly" {{ $filters['period'] === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                <option value="annual" {{ $filters['period'] === 'annual' ? 'selected' : '' }}>Annual</option>
                            </select>
                        </div>

                        <div id="monthFilterWrap">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Month</label>
                            <select name="month" class="w-full border rounded-xl px-4 py-3 text-sm text-gray-600 outline-none bg-white">
                                @foreach ($months as $number => $month)
                                    <option value="{{ $number }}" {{ (int) $filters['month'] === (int) $number ? 'selected' : '' }}>{{ $month }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="quarterFilterWrap">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Quarter</label>
                            <select name="quarter" class="w-full border rounded-xl px-4 py-3 text-sm text-gray-600 outline-none bg-white">
                                @foreach ($quarters as $quarter)
                                    <option value="{{ $quarter }}" {{ $filters['quarter'] === $quarter ? 'selected' : '' }}>{{ $quarter }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-5 py-3 rounded-xl text-sm font-semibold">Apply</button>
                        <a href="{{ route('sk_pres.consolidation') }}" class="bg-white hover:bg-gray-50 text-gray-600 border px-5 py-3 rounded-xl text-sm font-semibold">Reset</a>
                    </div>
                </form>

                <div class="mb-4 rounded-2xl border border-blue-100 bg-blue-50 px-5 py-4 text-sm text-blue-800">
                    This module compiles barangay accomplishment reports into one citywide view for monthly, quarterly, and annual monitoring.
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border-separate border-spacing-y-2">
                        <thead>
                            <tr class="text-gray-500">
                                <th class="px-4 py-3">Barangay</th>
                                <th class="px-4 py-3">Monthly</th>
                                <th class="px-4 py-3">Quarterly</th>
                                <th class="px-4 py-3">Annual</th>
                                <th class="px-4 py-3">Last Submission</th>
                                <th class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>

                        <tbody id="submissionRows">
                            @forelse ($submissions as $submission)
                                <tr class="bg-gray-50" data-barangay="{{ strtolower($submission['barangay']) }}">
                                    <td class="px-4 py-4 rounded-l-xl font-semibold text-gray-800">Barangay {{ $submission['barangay'] }}</td>

                                    <td class="px-4 py-4">
                                        <span class="{{ $submission['monthly_count'] > 0 ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }} rounded-full px-3 py-1 text-xs font-bold">{{ $submission['monthly'] }}</span>
                                    </td>

                                    <td class="px-4 py-4">
                                        <span class="{{ $submission['quarterly_count'] > 0 ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }} rounded-full px-3 py-1 text-xs font-bold">{{ $submission['quarterly'] }}</span>
                                    </td>

                                    <td class="px-4 py-4">
                                        <span class="{{ $submission['annual_count'] > 0 ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }} rounded-full px-3 py-1 text-xs font-bold">{{ $submission['annual'] }}</span>
                                    </td>

                                    <td class="px-4 py-4 text-gray-600">{{ $submission['last_submission'] }}</td>

                                    <td class="px-4 py-4 rounded-r-xl text-center">
                                        <span class="{{ $submission['status'] === 'submitted' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }} rounded-full px-3 py-1 text-xs font-bold uppercase">{{ $submission['status'] }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-gray-400">No barangay submissions yet.</td>
                                </tr>
                            @endforelse

                            <tr id="submissionNoResults" class="hidden">
                                <td colspan="6" class="px-4 py-10 text-center text-gray-400">No barangay matched your search.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div id="submissionPagination" class="mt-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <p id="submissionPaginationInfo" class="text-xs text-gray-500"></p>

                    <div class="flex items-center gap-2">
                        <button id="submissionPrevPage" type="button"
                                class="border border-gray-200 bg-white hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed text-gray-600 px-3 py-2 rounded-lg text-xs font-semibold">
                            Previous
                        </button>

                        <div id="submissionPageButtons" class="flex items-center gap-1"></div>

                        <button id="submissionNextPage" type="button"
                                class="border border-gray-200 bg-white hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed text-gray-600 px-3 py-2 rounded-lg text-xs font-semibold">
                            Next
                        </button>
                    </div>
                </div>
            </section>

            @php
                $focusType = (string) request()->query('focus_type', '');
                $focusId = (int) request()->query('focus_id', 0);
            @endphp

            <section id="qualityDocumentationSection" class="bg-white rounded-2xl shadow-sm p-6 mt-8 mb-10">
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Quality Documentation Review</h2>
                    <p class="text-gray-500 text-sm mt-1">
                        Review submitted documents before awarding Quality Documentation points.
                    </p>
                </div>

                <div class="space-y-5">
                    @forelse ($qualitySubmissions as $item)
                        @php
                            $status = $item->quality_status ?? 'pending';

                            $statusClass = match ($status) {
                                'approved' => 'bg-green-100 text-green-700',
                                'needs_revision' => 'bg-red-100 text-red-700',
                                default => 'bg-yellow-100 text-yellow-700',
                            };

                            $statusLabel = match ($status) {
                                'approved' => 'Approved',
                                'needs_revision' => 'Needs Revision',
                                default => 'Pending Review',
                            };

                            $isFocused =
                                $focusType === (string) $item->source_type &&
                                $focusId === (int) $item->source_id;
                        @endphp

                        <div
                            id="quality-submission-{{ $item->source_type }}-{{ $item->source_id }}"
                            data-quality-submission
                            data-source-type="{{ $item->source_type }}"
                            data-source-id="{{ $item->source_id }}"
                            data-focus-target="{{ $isFocused ? '1' : '0' }}"
                            class="border rounded-2xl p-5 transition-all duration-500 {{ $isFocused ? 'border-yellow-400 bg-yellow-50 ring-4 ring-yellow-200 shadow-lg' : 'border-gray-200' }}"
                        >
                            @if ($isFocused)
                                <div class="mb-4 flex items-center gap-2 rounded-xl border border-yellow-200 bg-yellow-100 px-4 py-3 text-sm font-semibold text-yellow-800">
                                    <span>&#128276;</span>
                                    <span>This is the submission from the notification you opened.</span>
                                </div>
                            @endif

                            <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-4 mb-5">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        <h3 class="font-bold text-gray-900">
                                            Barangay {{ $item->barangay_name }}
                                        </h3>

                                        <span class="{{ $statusClass }} rounded-full px-3 py-1 text-[10px] font-bold uppercase">
                                            {{ $statusLabel }}
                                        </span>
                                    </div>

                                    <p class="text-sm text-gray-700 font-medium">
                                        {{ $item->title ?: $item->source_label }}
                                    </p>

                                    <div class="flex flex-wrap gap-x-5 gap-y-1 text-xs text-gray-500 mt-2">
                                        <span>{{ $item->source_label }}</span>
                                        <span>{{ $item->period_label }}</span>
                                        <span>Submitted: {{ $item->submitted_label }}</span>
                                    </div>
                                </div>

                                @if ($item->file_url)
                                    <a href="{{ $item->file_url }}" target="_blank" rel="noopener noreferrer"
                                       class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap">
                                        View Document
                                    </a>
                                @else
                                    <span class="bg-gray-100 text-gray-400 px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap">
                                        No File Available
                                    </span>
                                @endif
                            </div>

                            @if ($status === 'approved')
                                <div class="rounded-xl bg-green-50 border border-green-100 p-4">
                                    <p class="text-sm font-semibold text-green-700">
                                        Quality Documentation Approved
                                    </p>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mt-3 text-xs">
                                        <span class="{{ $item->complete_contents ? 'text-green-700' : 'text-gray-400' }}">
                                            {{ $item->complete_contents ? '✓' : '—' }} Complete Contents
                                        </span>

                                        <span class="{{ $item->correct_document ? 'text-green-700' : 'text-gray-400' }}">
                                            {{ $item->correct_document ? '✓' : '—' }} Correct Document
                                        </span>

                                        <span class="{{ $item->correct_period ? 'text-green-700' : 'text-gray-400' }}">
                                            {{ $item->correct_period ? '✓' : '—' }} Correct Period
                                        </span>
                                    </div>

                                    @if ($item->quality_remarks)
                                        <p class="text-xs text-gray-600 mt-3">
                                            Remarks: {{ $item->quality_remarks }}
                                        </p>
                                    @endif
                                </div>
                            @else
                                <form method="POST" action="{{ route('sk_pres.consolidation.quality-review') }}">
                                    @csrf

                                    <input type="hidden" name="source_type" value="{{ $item->source_type }}">
                                    <input type="hidden" name="source_id" value="{{ $item->source_id }}">

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <label class="flex items-start gap-2 border rounded-xl p-3 cursor-pointer hover:bg-gray-50">
                                            <input type="hidden" name="complete_contents" value="0">
                                            <input type="checkbox" name="complete_contents" value="1"
                                                   class="mt-1"
                                                   {{ $item->complete_contents ? 'checked' : '' }}>
                                            <span>
                                                <span class="block text-xs font-bold text-gray-700">Complete Contents</span>
                                                <span class="text-[10px] text-gray-400">Required content is present.</span>
                                            </span>
                                        </label>

                                        <label class="flex items-start gap-2 border rounded-xl p-3 cursor-pointer hover:bg-gray-50">
                                            <input type="hidden" name="correct_document" value="0">
                                            <input type="checkbox" name="correct_document" value="1"
                                                   class="mt-1"
                                                   {{ $item->correct_document ? 'checked' : '' }}>
                                            <span>
                                                <span class="block text-xs font-bold text-gray-700">Correct Document</span>
                                                <span class="text-[10px] text-gray-400">Matches the required submission.</span>
                                            </span>
                                        </label>

                                        <label class="flex items-start gap-2 border rounded-xl p-3 cursor-pointer hover:bg-gray-50">
                                            <input type="hidden" name="correct_period" value="0">
                                            <input type="checkbox" name="correct_period" value="1"
                                                   class="mt-1"
                                                   {{ $item->correct_period ? 'checked' : '' }}>
                                            <span>
                                                <span class="block text-xs font-bold text-gray-700">Correct Period</span>
                                                <span class="text-[10px] text-gray-400">Reporting period and details are correct.</span>
                                            </span>
                                        </label>
                                    </div>

                                    <div class="mt-4">
                                        <label class="block text-xs font-bold text-gray-600 mb-2">
                                            Review Remarks
                                        </label>

                                        <textarea name="remarks"
                                                  rows="3"
                                                  placeholder="Add remarks, especially when revision is needed..."
                                                  class="w-full border rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-red-100">{{ $item->quality_remarks }}</textarea>
                                    </div>

                                    <div class="flex flex-wrap justify-end gap-3 mt-4">
                                        <button type="submit"
                                                name="status"
                                                value="needs_revision"
                                                class="bg-white border border-red-200 hover:bg-red-50 text-red-600 px-4 py-2 rounded-xl text-sm font-semibold">
                                            Needs Revision
                                        </button>

                                        <button type="submit"
                                                name="status"
                                                value="approved"
                                                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-semibold">
                                            Approve Quality
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="border border-dashed border-gray-200 rounded-2xl p-10 text-center">
                            <p class="text-gray-400 text-sm">
                                No submitted documents available for Quality Documentation review.
                            </p>
                        </div>
                    @endforelse
                </div>
            </section>
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
    const searchInput = document.getElementById('barangaySearch');
    const periodFilter = document.getElementById('periodFilter');
    const monthFilterWrap = document.getElementById('monthFilterWrap');
    const quarterFilterWrap = document.getElementById('quarterFilterWrap');
    const submissionRows = Array.from(document.querySelectorAll('#submissionRows tr[data-barangay]'));
    const submissionNoResults = document.getElementById('submissionNoResults');
    const submissionPagination = document.getElementById('submissionPagination');
    const submissionPaginationInfo = document.getElementById('submissionPaginationInfo');
    const submissionPageButtons = document.getElementById('submissionPageButtons');
    const submissionPrevPage = document.getElementById('submissionPrevPage');
    const submissionNextPage = document.getElementById('submissionNextPage');
    const submissionPerPage = 10;
    let submissionCurrentPage = 1;

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

    function syncPeriodControls() {
        monthFilterWrap.classList.toggle('hidden', periodFilter.value !== 'monthly');
        quarterFilterWrap.classList.toggle('hidden', periodFilter.value !== 'quarterly');
    }

    periodFilter.addEventListener('change', syncPeriodControls);
    syncPeriodControls();

    function filteredSubmissionRows() {
        const keyword = searchInput.value.toLowerCase().trim();

        return submissionRows.filter((row) => {
            return row.dataset.barangay.includes(keyword);
        });
    }

    function renderSubmissionPagination() {
        const filteredRows = filteredSubmissionRows();
        const totalRows = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(totalRows / submissionPerPage));

        if (submissionCurrentPage > totalPages) {
            submissionCurrentPage = totalPages;
        }

        const startIndex = (submissionCurrentPage - 1) * submissionPerPage;
        const endIndex = Math.min(startIndex + submissionPerPage, totalRows);

        submissionRows.forEach((row) => {
            row.classList.add('hidden');
        });

        filteredRows.slice(startIndex, endIndex).forEach((row) => {
            row.classList.remove('hidden');
        });

        submissionNoResults.classList.toggle('hidden', totalRows !== 0);
        submissionPagination.classList.toggle('hidden', submissionRows.length === 0);

        submissionPaginationInfo.textContent = totalRows > 0
            ? `Showing ${startIndex + 1}-${endIndex} of ${totalRows} barangays`
            : 'No barangays to display';

        submissionPrevPage.disabled = submissionCurrentPage <= 1;
        submissionNextPage.disabled = submissionCurrentPage >= totalPages || totalRows === 0;

        submissionPageButtons.innerHTML = '';

        if (totalRows === 0) {
            return;
        }

        for (let page = 1; page <= totalPages; page++) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = page;
            button.className = page === submissionCurrentPage
                ? 'w-8 h-8 rounded-lg bg-red-500 text-white text-xs font-bold'
                : 'w-8 h-8 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 text-xs font-semibold';

            button.addEventListener('click', function () {
                submissionCurrentPage = page;
                renderSubmissionPagination();
            });

            submissionPageButtons.appendChild(button);
        }
    }

    submissionPrevPage.addEventListener('click', function () {
        if (submissionCurrentPage > 1) {
            submissionCurrentPage--;
            renderSubmissionPagination();
        }
    });

    submissionNextPage.addEventListener('click', function () {
        const totalPages = Math.max(1, Math.ceil(filteredSubmissionRows().length / submissionPerPage));

        if (submissionCurrentPage < totalPages) {
            submissionCurrentPage++;
            renderSubmissionPagination();
        }
    });

    searchInput.addEventListener('input', function () {
        submissionCurrentPage = 1;
        renderSubmissionPagination();
    });

    renderSubmissionPagination();

    document.addEventListener('DOMContentLoaded', function () {
        const focusedSubmission = document.querySelector('[data-focus-target="1"]');

        if (!focusedSubmission) {
            return;
        }

        setTimeout(() => {
            focusedSubmission.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });
        }, 250);
    });
</script>
@endpush