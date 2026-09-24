{{-- File guide: Blade view template for resources/views/sk_pres/module.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 Dashboard')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('content')
@php
    $budgetCategoryLabels = [
        'annual_budget' => 'Annual Budget',
        'supplemental_budget' => 'Supplemental Budget',
        'coa_report' => 'Financial / COA Report',
    ];

    $budgetPeriodLabels = [
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'semi_annual' => 'Semi-Annual',
        'annual' => 'Annual',
    ];

    $monthNames = [
        1 => 'January',
        2 => 'February',
        3 => 'March',
        4 => 'April',
        5 => 'May',
        6 => 'June',
        7 => 'July',
        8 => 'August',
        9 => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December',
    ];

    // Presentation only: an icon and tint for each summary card the controller sends.
    $summaryStyles = [
        'Total Slots' => ['icon' => 'layout-grid', 'tone' => ''],
        'Open Slots' => ['icon' => 'lock-open', 'tone' => 'green'],
        'Past Deadline' => ['icon' => 'triangle-alert', 'tone' => 'yellow'],
        'Closed Slots' => ['icon' => 'lock', 'tone' => ''],
        'Current Submissions' => ['icon' => 'file-text', 'tone' => 'blue'],
        'All-Time Total' => ['icon' => 'archive', 'tone' => 'yellow'],
    ];

    $formatDate = fn ($value) => rescue(fn () => \Illuminate\Support\Carbon::parse($value)->format('M j, Y'), $value, false);

    $slots = isset($slots)
        ? collect($slots)
        : collect($slotGroups ?? [])
            ->flatMap(fn ($group) => collect(method_exists($group, 'items') ? $group->items() : $group))
            ->values();

    $openCount = $slots->where('status', 'open')->count();
    $closedCount = $slots->count() - $openCount;

    $fieldClass = 'w-full h-12 px-4 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none';
@endphp

<div class="flex h-screen bg-[#f1f5f9] overflow-hidden">
    @include('partials.app.sidebar')

    <div class="flex-1 flex flex-col min-w-0">
        @include('partials.app.topbar')

        <main class="flex-1 overflow-y-auto p-8">
            <div class="sk-page-head">
                <div class="sk-page-head__text">
                    <span class="sk-eyebrow"><span class="sk-dot"></span>Module Management</span>
                    <h1 class="sk-page-title">Submission Slot Management</h1>
                    <p class="sk-page-subtitle">
                        Create and manage submission periods for Accomplishment Reports and Budget & Financial Reports
                    </p>
                </div>

                <div class="sk-page-head__actions">
                    <button id="openModalBtn" type="button" class="sk-btn sk-btn--primary sk-btn--lg">
                        @include('partials.ui.icon', ['icon' => 'plus', 'iconSize' => 19, 'iconStroke' => 2.4])
                        Create Submission Slot
                    </button>
                </div>
            </div>

            @if (session('status'))
                <div class="sk-alert sk-alert--success mb-6">
                    @include('partials.ui.icon', ['icon' => 'circle-check', 'iconSize' => 18])
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="sk-alert sk-alert--error mb-6">
                    @include('partials.ui.icon', ['icon' => 'circle-alert', 'iconSize' => 18])
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <div id="submissionModal"
                class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50 px-4">

                <div class="bg-white w-full max-w-3xl max-h-[92vh] overflow-y-auto rounded-[20px] p-8 relative">

                    <button id="closeModalBtn"
                        type="button"
                        class="sk-icon-btn sk-modal__close"
                        aria-label="Close">
                        @include('partials.ui.icon', ['icon' => 'x', 'iconSize' => 20])
                    </button>

                    <div class="flex items-start gap-4 mb-7 pr-10">
                        <span class="sk-icon-tile">
                            @include('partials.ui.icon', ['icon' => 'file-plus', 'iconSize' => 22])
                        </span>
                        <div>
                            <h2 class="sk-modal__title">Create New Submission Slot</h2>
                            <p class="sk-modal__subtitle">Set up a new submission period for SK officials to submit reports</p>
                        </div>
                    </div>

                    <form id="slotForm"
                        action="{{ route('sk_pres.module.store') }}"
                        method="POST"
                        class="space-y-5">

                        @csrf

                        <div>
                            <label class="sk-label" for="submissionType">
                                Submission Type
                            </label>

                            <select id="submissionType"
                                name="submission_type"
                                class="{{ $fieldClass }}">

                                <option value="accomplishment_report"
                                    @selected(old('submission_type') === 'accomplishment_report')>
                                    Accomplishment Report
                                </option>

                                <option value="budget_report"
                                    @selected(old('submission_type') === 'budget_report')>
                                    Budget / Financial Report
                                </option>
                            </select>
                        </div>

                        <div id="budgetDetails" class="hidden space-y-5 rounded-2xl border border-gray-200 bg-[#f8f9fb] p-5">

                            <div>
                                <h3 class="text-base font-bold text-gray-900">
                                    Budget / Financial Report Details
                                </h3>

                                <p class="text-sm text-gray-500 mt-1">
                                    Specify what type of financial submission is expected from the barangay.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="sk-label" for="budgetCategory">
                                        Report Category
                                    </label>

                                    <select id="budgetCategory"
                                        name="budget_category"
                                        class="{{ $fieldClass }}">

                                        <option value="">Select category</option>

                                        <option value="annual_budget"
                                            @selected(old('budget_category') === 'annual_budget')>
                                            Annual Budget
                                        </option>

                                        <option value="supplemental_budget"
                                            @selected(old('budget_category') === 'supplemental_budget')>
                                            Supplemental Budget
                                        </option>

                                        <option value="coa_report"
                                            @selected(old('budget_category') === 'coa_report')>
                                            Financial / COA Report
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="sk-label" for="fiscalYear">
                                        Fiscal Year
                                    </label>

                                    <input id="fiscalYear"
                                        type="number"
                                        name="fiscal_year"
                                        min="2000"
                                        max="2100"
                                        value="{{ old('fiscal_year', now()->year) }}"
                                        class="{{ $fieldClass }}">
                                </div>
                            </div>

                            <div id="reportPeriodSection" class="hidden">
                                <label class="sk-label" for="budgetPeriodType">
                                    Reporting Period
                                </label>

                                <select id="budgetPeriodType"
                                    name="budget_period_type"
                                    class="{{ $fieldClass }}">

                                    <option value="">Select reporting period</option>

                                    <option value="monthly"
                                        @selected(old('budget_period_type') === 'monthly')>
                                        Monthly
                                    </option>

                                    <option value="quarterly"
                                        @selected(old('budget_period_type') === 'quarterly')>
                                        Quarterly
                                    </option>

                                    <option value="semi_annual"
                                        @selected(old('budget_period_type') === 'semi_annual')>
                                        Semi-Annual
                                    </option>

                                    <option value="annual"
                                        @selected(old('budget_period_type') === 'annual')>
                                        Annual
                                    </option>
                                </select>
                            </div>

                            <div id="monthlySection" class="hidden">
                                <label class="sk-label" for="fiscalMonth">
                                    Month
                                </label>

                                <select id="fiscalMonth"
                                    name="fiscal_month"
                                    class="{{ $fieldClass }}">

                                    <option value="">Select month</option>

                                    @foreach ($monthNames as $monthNumber => $monthName)

                                        <option value="{{ $monthNumber }}"
                                            @selected((string) old('fiscal_month') === (string) $monthNumber)>
                                            {{ $monthName }}
                                        </option>

                                    @endforeach
                                </select>
                            </div>

                            <div id="quarterlySection" class="hidden">
                                <label class="sk-label" for="fiscalQuarter">
                                    Quarter
                                </label>

                                <select id="fiscalQuarter"
                                    name="fiscal_quarter"
                                    class="{{ $fieldClass }}">

                                    <option value="">Select quarter</option>

                                    <option value="Q1" @selected(old('fiscal_quarter') === 'Q1')>
                                        Q1 - January to March
                                    </option>

                                    <option value="Q2" @selected(old('fiscal_quarter') === 'Q2')>
                                        Q2 - April to June
                                    </option>

                                    <option value="Q3" @selected(old('fiscal_quarter') === 'Q3')>
                                        Q3 - July to September
                                    </option>

                                    <option value="Q4" @selected(old('fiscal_quarter') === 'Q4')>
                                        Q4 - October to December
                                    </option>
                                </select>
                            </div>

                            <div id="semiAnnualSection" class="hidden">
                                <label class="sk-label" for="fiscalHalf">
                                    Semi-Annual Period
                                </label>

                                <select id="fiscalHalf"
                                    name="fiscal_half"
                                    class="{{ $fieldClass }}">

                                    <option value="">Select period</option>

                                    <option value="H1"
                                        @selected(old('fiscal_half') === 'H1')>
                                        First Half - January to June
                                    </option>

                                    <option value="H2"
                                        @selected(old('fiscal_half') === 'H2')>
                                        Second Half - July to December
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="sk-label" for="submissionTitle">
                                Submission Title
                            </label>

                            <input type="text"
                                id="submissionTitle"
                                name="submission_title"
                                class="{{ $fieldClass }}"
                                value="{{ old('submission_title') }}">
                        </div>

                        <div>
                            <label class="sk-label" for="submissionDescription">
                                Description
                            </label>

                            <input type="text"
                                id="submissionDescription"
                                name="description"
                                class="{{ $fieldClass }}"
                                value="{{ old('description') }}">
                        </div>

                        <div>
                            <label class="sk-label" for="submissionRole">
                                Who Can Submit
                            </label>

                            <select id="submissionRole"
                                name="submission_role"
                                class="{{ $fieldClass }}">

                                <option value="SK Chairman"
                                    @selected(old('submission_role') === 'SK Chairman')>
                                    SK Chairman
                                </option>

                                <option value="SK Secretary"
                                    @selected(old('submission_role') === 'SK Secretary')>
                                    SK Secretary
                                </option>

                                <option value="Both"
                                    @selected(old('submission_role') === 'Both')>
                                    SK Chairman & SK Secretary
                                </option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="sk-label" for="startDate">
                                    Start Date
                                </label>

                                <input type="date"
                                    id="startDate"
                                    name="start_date"
                                    class="{{ $fieldClass }}"
                                    value="{{ old('start_date') }}">
                            </div>

                            <div>
                                <label class="sk-label" for="endDate">
                                    Submission Deadline
                                </label>

                                <input type="date"
                                    id="endDate"
                                    name="end_date"
                                    class="{{ $fieldClass }}"
                                    value="{{ old('end_date') }}">
                            </div>
                        </div>

                        <button type="submit"
                            class="sk-btn sk-btn--primary sk-btn--lg w-full">
                            Create Slot
                        </button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
                @foreach ($summaryCards as $card)
                    @php $style = $summaryStyles[$card['label']] ?? ['icon' => 'layout-grid', 'tone' => '']; @endphp

                    <div class="sk-stat">
                        <div class="sk-stat__top">
                            <div>
                                <p class="sk-stat__label">{{ $card['label'] }}</p>
                                <p class="sk-stat__value">{{ $card['value'] }}</p>
                            </div>

                            <span class="sk-icon-tile {{ $style['tone'] ? 'sk-icon-tile--' . $style['tone'] : '' }}">
                                @include('partials.ui.icon', ['icon' => $style['icon'], 'iconSize' => 21])
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex flex-wrap items-end justify-between gap-3 mb-4">
                <div>
                    <h2 class="sk-section-title">Submission Slots</h2>
                    <p class="sk-section-subtitle">Every slot in the current term, newest first.</p>
                </div>
                <span class="sk-badge sk-badge--red">{{ $slots->count() }} {{ \Illuminate\Support\Str::plural('slot', $slots->count()) }}</span>
            </div>

            @if ($slots->isNotEmpty())
                {{-- Client-side only: narrows the cards already on the page. --}}
                <div class="sk-toolbar mb-5">
                    <label class="sk-search">
                        <span class="sr-only">Search slots</span>
                        @include('partials.ui.icon', ['icon' => 'search', 'iconSize' => 18])
                        <input type="text" id="slotSearch" placeholder="Search slot title, description or role..." autocomplete="off">
                    </label>

                    <div class="sk-pills" role="group" aria-label="Filter by status">
                        <button type="button" class="sk-pill is-active" data-slot-filter="all">All ({{ $slots->count() }})</button>
                        <button type="button" class="sk-pill" data-slot-filter="open">Open ({{ $openCount }})</button>
                        <button type="button" class="sk-pill" data-slot-filter="closed">Closed ({{ $closedCount }})</button>
                    </div>
                </div>
            @endif

            <div id="slotContainer"
                class="grid grid-cols-1 xl:grid-cols-2 gap-5">

                @forelse ($slots as $slot)
                    @php
                        $isBudget = $slot->submission_type === 'budget_report';
                        $isOpen = $slot->status === 'open';
                    @endphp

                    <article class="sk-record {{ $isOpen ? ($isBudget ? 'sk-record--blue' : '') : 'sk-record--muted' }}"
                        data-slot-card
                        data-status="{{ $isOpen ? 'open' : 'closed' }}"
                        data-search="{{ \Illuminate\Support\Str::lower($slot->title . ' ' . $slot->description . ' ' . $slot->role) }}">

                        <div class="flex items-start gap-4">
                            <span class="sk-thumb {{ $isOpen ? ($isBudget ? 'sk-thumb--blue' : '') : 'sk-thumb--muted' }}">
                                @include('partials.ui.icon', ['icon' => $isBudget ? 'wallet' : 'clipboard-list', 'iconSize' => 24])
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="sk-overline">
                                    {{ $isBudget ? 'Budget / Financial Report' : 'Accomplishment Report' }}
                                </p>

                                <h3 class="mt-1 text-[17px] font-bold leading-snug text-gray-900">
                                    {{ $slot->title }}
                                </h3>

                                @if (filled($slot->description))
                                    <p class="mt-1 text-sm leading-relaxed text-gray-500">
                                        {{ $slot->description }}
                                    </p>
                                @endif
                            </div>

                            <button type="button"
                                onclick="deleteSlot({{ $slot->slot_id }})"
                                class="sk-icon-btn text-gray-400 hover:!text-red-600 hover:!bg-red-50"
                                title="Delete slot"
                                aria-label="Delete {{ $slot->title }}">
                                @include('partials.ui.icon', ['icon' => 'trash-2', 'iconSize' => 18])
                            </button>
                        </div>

                        @if ($isBudget)

                            <div class="flex flex-wrap gap-2">

                                @if (!empty($slot->budget_category))
                                    <span class="sk-badge sk-badge--red">
                                        {{ $budgetCategoryLabels[$slot->budget_category] ?? $slot->budget_category }}
                                    </span>
                                @endif

                                @if (!empty($slot->fiscal_year))
                                    <span class="sk-badge sk-badge--blue">
                                        FY {{ $slot->fiscal_year }}
                                    </span>
                                @endif

                                @if ($slot->budget_category === 'coa_report' && !empty($slot->budget_period_type))
                                    <span class="sk-badge sk-badge--yellow">
                                        {{ $budgetPeriodLabels[$slot->budget_period_type] ?? $slot->budget_period_type }}

                                        @if ($slot->budget_period_type === 'monthly' && !empty($slot->fiscal_month))
                                            - {{ $monthNames[(int) $slot->fiscal_month] ?? '' }}
                                        @elseif ($slot->budget_period_type === 'quarterly' && !empty($slot->fiscal_quarter))
                                            - {{ $slot->fiscal_quarter }}
                                        @elseif ($slot->budget_period_type === 'semi_annual' && !empty($slot->fiscal_half))
                                            - {{ $slot->fiscal_half === 'H1' ? 'First Half' : 'Second Half' }}
                                        @endif
                                    </span>
                                @endif
                            </div>

                        @endif

                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4">
                            <div class="sk-meta">
                                <span class="sk-meta__item">
                                    @include('partials.ui.icon', ['icon' => 'calendar-days', 'iconSize' => 16])
                                    {{ $formatDate($slot->start_date) }} &ndash; {{ $formatDate($slot->end_date) }}
                                </span>
                                <span class="sk-meta__item">
                                    @include('partials.ui.icon', ['icon' => 'users', 'iconSize' => 16])
                                    {{ $slot->role }}
                                </span>
                            </div>

                            <span class="sk-badge sk-badge--dot {{ $isOpen ? 'sk-badge--green' : 'sk-badge--gray' }}">
                                {{ $isOpen ? 'Open' : 'Closed' }}
                            </span>
                        </div>

                        <form id="delete-slot-{{ $slot->slot_id }}"
                            method="POST"
                            action="{{ route('sk_pres.module.destroy', $slot->slot_id) }}"
                            class="hidden">
                            @csrf
                        </form>
                    </article>

                @empty

                    <div id="emptySlotState"
                        class="sk-card sk-empty xl:col-span-2">
                        <span class="sk-icon-tile">
                            @include('partials.ui.icon', ['icon' => 'inbox', 'iconSize' => 26])
                        </span>
                        <p class="sk-empty__title">No submission slots yet.</p>
                        <p class="sk-empty__text">Use Create Submission Slot to open a reporting window for barangay officials.</p>
                    </div>

                @endforelse

                @if ($slots->isNotEmpty())
                    <div id="slotNoMatches" class="sk-card sk-empty xl:col-span-2 hidden">
                        <span class="sk-icon-tile sk-icon-tile--gray">
                            @include('partials.ui.icon', ['icon' => 'search', 'iconSize' => 24])
                        </span>
                        <p class="sk-empty__title">No slots match</p>
                        <p class="sk-empty__text">Try another search term or status filter.</p>
                    </div>
                @endif
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
@vite(['resources/js/app.js'])

<script>
    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');

    const openModalBtn = document.getElementById('openModalBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const submissionModal = document.getElementById('submissionModal');

    const submissionType = document.getElementById('submissionType');

    const budgetDetails = document.getElementById('budgetDetails');
    const budgetCategory = document.getElementById('budgetCategory');
    const fiscalYear = document.getElementById('fiscalYear');

    const reportPeriodSection = document.getElementById('reportPeriodSection');
    const budgetPeriodType = document.getElementById('budgetPeriodType');

    const monthlySection = document.getElementById('monthlySection');
    const quarterlySection = document.getElementById('quarterlySection');
    const semiAnnualSection = document.getElementById('semiAnnualSection');

    const fiscalMonth = document.getElementById('fiscalMonth');
    const fiscalQuarter = document.getElementById('fiscalQuarter');
    const fiscalHalf = document.getElementById('fiscalHalf');

    function syncBudgetFields() {
        const isBudget = submissionType.value === 'budget_report';
        const isCoaReport = isBudget && budgetCategory.value === 'coa_report';

        budgetDetails.classList.toggle('hidden', !isBudget);

        budgetCategory.required = isBudget;
        fiscalYear.required = isBudget;

        reportPeriodSection.classList.toggle('hidden', !isCoaReport);
        budgetPeriodType.required = isCoaReport;

        const period = budgetPeriodType.value;

        const isMonthly = isCoaReport && period === 'monthly';
        const isQuarterly = isCoaReport && period === 'quarterly';
        const isSemiAnnual = isCoaReport && period === 'semi_annual';

        monthlySection.classList.toggle('hidden', !isMonthly);
        quarterlySection.classList.toggle('hidden', !isQuarterly);
        semiAnnualSection.classList.toggle('hidden', !isSemiAnnual);

        fiscalMonth.required = isMonthly;
        fiscalQuarter.required = isQuarterly;
        fiscalHalf.required = isSemiAnnual;
    }

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

    openModalBtn.addEventListener('click', function () {
        submissionModal.classList.remove('hidden');
        submissionModal.classList.add('flex');

        syncBudgetFields();
    });

    closeModalBtn.addEventListener('click', function () {
        submissionModal.classList.add('hidden');
        submissionModal.classList.remove('flex');
    });

    submissionModal.addEventListener('click', function (e) {
        if (e.target === submissionModal) {
            submissionModal.classList.add('hidden');
            submissionModal.classList.remove('flex');
        }
    });

    submissionType.addEventListener('change', syncBudgetFields);
    budgetCategory.addEventListener('change', syncBudgetFields);
    budgetPeriodType.addEventListener('change', syncBudgetFields);

    function deleteSlot(id) {
        Swal.fire({
            title: 'Delete Slot?',
            text: 'This will be permanently removed.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(`delete-slot-${id}`).submit();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        syncBudgetFields();
    });

    // Search and status pills only show or hide the slot cards already rendered.
    (function () {
        const search = document.getElementById('slotSearch');
        const pills = document.querySelectorAll('[data-slot-filter]');
        const cards = document.querySelectorAll('[data-slot-card]');
        const noMatches = document.getElementById('slotNoMatches');
        let status = 'all';

        if (!search || !cards.length) {
            return;
        }

        function applyFilters() {
            const query = search.value.trim().toLowerCase();
            let visible = 0;

            cards.forEach(function (card) {
                const matches = (status === 'all' || card.dataset.status === status)
                    && (!query || card.dataset.search.includes(query));
                card.classList.toggle('hidden', !matches);
                visible += matches ? 1 : 0;
            });

            noMatches?.classList.toggle('hidden', visible > 0);
        }

        pills.forEach(function (pill) {
            pill.addEventListener('click', function () {
                status = pill.dataset.slotFilter;
                pills.forEach((item) => item.classList.toggle('is-active', item === pill));
                applyFilters();
            });
        });

        search.addEventListener('input', applyFilters);
    })();

    @if ($errors->any())
        submissionModal.classList.remove('hidden');
        submissionModal.classList.add('flex');
        syncBudgetFields();
    @endif
</script>
@endpush
