{{-- File guide: Blade view template for resources/views/sk_pres/consolidation.blade.php. --}}
@extends('layouts.app')

@section('title', 'Report Consolidation')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
@php
    // Presentation only: an icon and tint for each stat the controller sends.
    $statStyles = [
        'Total Barangays' => ['icon' => 'building-2', 'tone' => 'blue'],
        'Submitted' => ['icon' => 'circle-check', 'tone' => 'green'],
        'Pending' => ['icon' => 'hourglass', 'tone' => 'yellow'],
        'Late' => ['icon' => 'triangle-alert', 'tone' => ''],
    ];

    $qualityCriteria = [
        'complete_contents' => ['Complete Contents', 'Required content is present.'],
        'correct_document' => ['Correct Document', 'Matches the required submission.'],
        'correct_period' => ['Correct Period', 'Reporting period and details are correct.'],
        'readable_organized' => ['Readable / Organized', 'Document is readable and organized.'],
        'supporting_documents' => ['Supporting Documents', 'Check when applicable and complete.'],
    ];

    $filterSelect = 'w-full h-11 rounded-xl border border-gray-200 bg-white px-3.5 text-sm font-semibold text-gray-700';
@endphp

<div class="flex h-screen overflow-hidden bg-gray-100">
    @include('partials.app.sidebar')

    <div class="flex-1 flex flex-col min-w-0">
        @include('partials.app.topbar')

        <main class="flex-1 overflow-y-auto p-8">
            <div class="sk-page-head">
                <div class="sk-page-head__text">
                    <span class="sk-eyebrow"><span class="sk-dot"></span>Consolidation</span>
                    <h1 class="sk-page-title">Report Consolidation</h1>
                    <p class="sk-page-subtitle">
                        Automatically compile barangay reports into unified monthly, quarterly, and annual documents.
                    </p>
                </div>

                <div class="sk-page-head__actions">
                    <a href="{{ $downloadRoute }}" class="sk-btn sk-btn--primary sk-btn--lg">
                        @include('partials.ui.icon', ['icon' => 'download', 'iconSize' => 18])
                        Download Consolidated PDF
                    </a>
                </div>
            </div>

            @if (session('quality_status'))
                <div class="sk-alert sk-alert--success mb-6">
                    @include('partials.ui.icon', ['icon' => 'circle-check', 'iconSize' => 18])
                    <span>{{ session('quality_status') }}</span>
                </div>
            @endif

            @if ($errors->has('quality_review'))
                <div class="sk-alert sk-alert--error mb-6">
                    @include('partials.ui.icon', ['icon' => 'circle-alert', 'iconSize' => 18])
                    <span>{{ $errors->first('quality_review') }}</span>
                </div>
            @endif

            {{-- SUMMARY --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
                @foreach ($stats as $stat)
                    @php $style = $statStyles[$stat['label']] ?? ['icon' => 'layout-grid', 'tone' => '']; @endphp

                    <div class="sk-stat">
                        <div class="sk-stat__top">
                            <div>
                                <p class="sk-stat__label">{{ $stat['label'] }}</p>
                                <p class="sk-stat__value {{ $stat['valueClass'] }}">{{ $stat['value'] }}</p>
                            </div>
                            <span class="sk-icon-tile {{ $style['tone'] ? 'sk-icon-tile--' . $style['tone'] : '' }}">
                                @include('partials.ui.icon', ['icon' => $style['icon'], 'iconSize' => 21])
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- RECORDS --}}
            <section class="sk-card overflow-hidden">
                <div class="px-6 pt-6">
                    <h2 class="sk-section-title">Barangay Submissions</h2>
                    <p class="sk-section-subtitle">Review citywide report completion and archive consolidated outputs.</p>
                </div>

                {{-- FILTERS --}}
                <form method="GET" action="{{ route('sk_pres.consolidation') }}" class="mx-6 mt-5 flex flex-col xl:flex-row xl:items-end gap-4 rounded-2xl border border-gray-100 bg-[#f8f9fb] p-4">
                    <div class="w-full xl:max-w-xs">
                        <label for="barangaySearch" class="sk-overline block mb-2">Search Barangay</label>
                        <div class="sk-search">
                            @include('partials.ui.icon', ['icon' => 'search', 'iconSize' => 18])
                            <input id="barangaySearch" type="text" placeholder="Search barangay..." class="!bg-white !border-gray-200">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 flex-1">
                        <div>
                            <label class="sk-overline block mb-2">Year</label>
                            <select name="year" class="{{ $filterSelect }}">
                                @foreach ($years as $year)
                                    <option value="{{ $year }}" {{ (int) $filters['year'] === (int) $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="sk-overline block mb-2">Period</label>
                            <select name="period" id="periodFilter" class="{{ $filterSelect }}">
                                <option value="all" {{ $filters['period'] === 'all' ? 'selected' : '' }}>All Reports</option>
                                <option value="monthly" {{ $filters['period'] === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="quarterly" {{ $filters['period'] === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                <option value="annual" {{ $filters['period'] === 'annual' ? 'selected' : '' }}>Annual</option>
                            </select>
                        </div>

                        <div id="monthFilterWrap">
                            <label class="sk-overline block mb-2">Month</label>
                            <select name="month" class="{{ $filterSelect }}">
                                @foreach ($months as $number => $month)
                                    <option value="{{ $number }}" {{ (int) $filters['month'] === (int) $number ? 'selected' : '' }}>{{ $month }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="quarterFilterWrap">
                            <label class="sk-overline block mb-2">Quarter</label>
                            <select name="quarter" class="{{ $filterSelect }}">
                                @foreach ($quarters as $quarter)
                                    <option value="{{ $quarter }}" {{ $filters['quarter'] === $quarter ? 'selected' : '' }}>{{ $quarter }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="sk-btn sk-btn--primary">
                            @include('partials.ui.icon', ['icon' => 'filter', 'iconSize' => 16])
                            Apply
                        </button>
                        <a href="{{ route('sk_pres.consolidation') }}" class="sk-btn sk-btn--secondary">Reset</a>
                    </div>
                </form>

                <div class="sk-alert sk-alert--info mx-6 mt-4">
                    @include('partials.ui.icon', ['icon' => 'info', 'iconSize' => 18])
                    <span>This module compiles barangay accomplishment reports into one citywide view for monthly, quarterly, and annual monitoring.</span>
                </div>

                <div class="overflow-x-auto mt-5 border-t border-gray-100">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr>
                                <th class="px-6 py-3.5">Barangay</th>
                                <th class="px-6 py-3.5">Monthly</th>
                                <th class="px-6 py-3.5">Quarterly</th>
                                <th class="px-6 py-3.5">Annual</th>
                                <th class="px-6 py-3.5">Last Submission</th>
                                <th class="px-6 py-3.5 text-center">Status</th>
                            </tr>
                        </thead>

                        <tbody id="submissionRows">
                            @forelse ($submissions as $submission)
                                <tr data-barangay="{{ strtolower($submission['barangay']) }}">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <span class="sk-icon-tile sk-icon-tile--sm sk-icon-tile--gray">
                                                @include('partials.ui.icon', ['icon' => 'building-2', 'iconSize' => 16])
                                            </span>
                                            <span class="font-bold text-gray-900">Barangay {{ $submission['barangay'] }}</span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="sk-badge {{ $submission['monthly_count'] > 0 ? 'sk-badge--green' : 'sk-badge--yellow' }}">{{ $submission['monthly'] }}</span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="sk-badge {{ $submission['quarterly_count'] > 0 ? 'sk-badge--green' : 'sk-badge--yellow' }}">{{ $submission['quarterly'] }}</span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="sk-badge {{ $submission['annual_count'] > 0 ? 'sk-badge--green' : 'sk-badge--yellow' }}">{{ $submission['annual'] }}</span>
                                    </td>

                                    <td class="px-6 py-4 font-semibold text-gray-600 whitespace-nowrap">{{ $submission['last_submission'] }}</td>

                                    <td class="px-6 py-4 text-center">
                                        <span class="sk-badge sk-badge--dot {{ $submission['status'] === 'submitted' ? 'sk-badge--green' : 'sk-badge--yellow' }} capitalize">{{ $submission['status'] }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="sk-empty">
                                            <span class="sk-icon-tile sk-icon-tile--gray">
                                                @include('partials.ui.icon', ['icon' => 'inbox', 'iconSize' => 24])
                                            </span>
                                            <p class="sk-empty__text">No barangay submissions yet.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @php
                $focusType = (string) request()->query('focus_type', '');
                $focusId = (int) request()->query('focus_id', 0);
            @endphp

            {{-- DETAIL / INSPECTION --}}
            <section id="qualityDocumentationSection" class="sk-card p-6 mt-8 mb-10">
                <div class="flex items-start gap-3 mb-6">
                    <span class="sk-icon-tile">
                        @include('partials.ui.icon', ['icon' => 'shield-check', 'iconSize' => 21])
                    </span>
                    <div>
                        <h2 class="sk-section-title">Quality Documentation Review</h2>
                        <p class="sk-section-subtitle">
                            Review submitted documents before awarding Quality Documentation points.
                        </p>
                    </div>
                </div>

                <div class="space-y-5">
                    @forelse ($qualitySubmissions as $item)
                        @php
                            $status = $item->quality_status ?? 'pending';

                            $statusClass = match ($status) {
                                'approved' => 'sk-badge--green',
                                'needs_revision' => 'sk-badge--red',
                                default => 'sk-badge--yellow',
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
                            class="rounded-2xl border p-5 transition-all duration-500 {{ $isFocused ? 'border-yellow-300 bg-[#fffbeb] ring-4 ring-yellow-100 shadow-lg' : 'border-gray-200 bg-white hover:shadow-sm' }}"
                        >
                            @if ($isFocused)
                                <div class="sk-alert sk-alert--warning mb-4">
                                    @include('partials.ui.icon', ['icon' => 'bell', 'iconSize' => 17])
                                    <span>This is the submission from the notification you opened.</span>
                                </div>
                            @endif

                            <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-4 mb-5">
                                <div class="flex items-start gap-4 min-w-0">
                                    <span class="sk-thumb">
                                        @include('partials.ui.icon', ['icon' => 'file-text', 'iconSize' => 24])
                                    </span>

                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                            <h3 class="font-bold text-gray-900">
                                                Barangay {{ $item->barangay_name }}
                                            </h3>

                                            <span class="sk-badge sk-badge--dot {{ $statusClass }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </div>

                                        <p class="text-sm text-gray-700 font-semibold">
                                            {{ $item->title ?: $item->source_label }}
                                        </p>

                                        <div class="sk-meta mt-2">
                                            <span class="sk-meta__item">
                                                @include('partials.ui.icon', ['icon' => 'files', 'iconSize' => 14])
                                                {{ $item->source_label }}
                                            </span>
                                            <span class="sk-meta__item">
                                                @include('partials.ui.icon', ['icon' => 'calendar-days', 'iconSize' => 14])
                                                {{ $item->period_label }}
                                            </span>
                                            <span class="sk-meta__item">
                                                @include('partials.ui.icon', ['icon' => 'clock', 'iconSize' => 14])
                                                Submitted: {{ $item->submitted_label }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                @if ($item->file_url)
                                    <a href="{{ $item->file_url }}" target="_blank" rel="noopener noreferrer"
                                       class="sk-btn sk-btn--secondary shrink-0">
                                        @include('partials.ui.icon', ['icon' => 'eye', 'iconSize' => 16])
                                        View Document
                                    </a>
                                @else
                                    <span class="sk-btn sk-btn--ghost !bg-gray-100 !text-gray-400 cursor-default shrink-0">
                                        No File Available
                                    </span>
                                @endif
                            </div>

                            @if ($status === 'approved')
                                <div class="rounded-xl bg-green-50 border border-green-100 p-4">
                                    <p class="flex items-center gap-1.5 text-sm font-bold text-green-700">
                                        @include('partials.ui.icon', ['icon' => 'circle-check', 'iconSize' => 16])
                                        Quality Documentation Approved
                                    </p>

                                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-2 mt-3 text-xs font-semibold">
                                        @foreach ($qualityCriteria as $field => [$criterionLabel])
                                            <span class="{{ $item->{$field} ? 'text-green-700' : 'text-gray-400' }}">
                                                {{ $item->{$field} ? '✓' : '—' }} {{ $criterionLabel }}
                                            </span>
                                        @endforeach
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

                                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
                                        @foreach ($qualityCriteria as $field => [$criterionLabel, $criterionHint])
                                            <label class="flex items-start gap-2.5 rounded-xl border border-gray-200 bg-[#f8f9fb] p-3 cursor-pointer transition hover:bg-white hover:border-gray-300 has-[:checked]:border-red-200 has-[:checked]:bg-red-50/60">
                                                <input type="hidden" name="{{ $field }}" value="0">
                                                <input type="checkbox" name="{{ $field }}" value="1"
                                                       class="mt-0.5 h-4 w-4 rounded"
                                                       {{ $item->{$field} ? 'checked' : '' }}>
                                                <span>
                                                    <span class="block text-xs font-bold text-gray-800">{{ $criterionLabel }}</span>
                                                    <span class="text-[11px] leading-snug text-gray-500">{{ $criterionHint }}</span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>

                                    <div class="mt-4">
                                        <label class="sk-label">
                                            Review Remarks
                                        </label>

                                        <textarea name="remarks"
                                                  rows="3"
                                                  placeholder="Add remarks, especially when revision is needed..."
                                                  class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm">{{ $item->quality_remarks }}</textarea>
                                    </div>

                                    <div class="flex flex-wrap justify-end gap-3 mt-4">
                                        <button type="submit"
                                                name="status"
                                                value="needs_revision"
                                                class="sk-btn sk-btn--secondary !text-red-600 !border-red-200 hover:!bg-red-50">
                                            @include('partials.ui.icon', ['icon' => 'circle-alert', 'iconSize' => 16])
                                            Needs Revision
                                        </button>

                                        <button type="submit"
                                                name="status"
                                                value="approved"
                                                class="sk-btn !bg-green-600 hover:!bg-green-700 !text-white">
                                            @include('partials.ui.icon', ['icon' => 'circle-check', 'iconSize' => 16])
                                            Approve Quality
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="sk-empty rounded-2xl border border-dashed border-gray-200 bg-[#f8f9fb]">
                            <span class="sk-icon-tile sk-icon-tile--gray">
                                @include('partials.ui.icon', ['icon' => 'shield-check', 'iconSize' => 24])
                            </span>
                            <p class="sk-empty__text">
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

    searchInput.addEventListener('input', function () {
        const keyword = this.value.toLowerCase().trim();

        document.querySelectorAll('#submissionRows tr[data-barangay]').forEach((row) => {
            row.style.display = row.dataset.barangay.includes(keyword) ? '' : 'none';
        });
    });

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
