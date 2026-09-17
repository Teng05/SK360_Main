{{-- File guide: Blade view template for resources/views/sk_pres/module.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 Dashboard')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('content')
<div class="flex h-screen bg-[#f1f5f9] overflow-hidden">
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
        <div class="flex items-center gap-3 mb-4">
            <img src="{{ asset('images/logo.png') }}" class="w-8 h-8 rounded-full object-cover" alt="logo">

            <div class="leading-tight">
                <h2 class="text-lg font-extrabold tracking-wide">SK 360°</h2>
                <p class="text-[10px] opacity-80">Management System</p>
            </div>
        </div>

        <div class="bg-red-500 rounded-lg p-2 flex items-center gap-2 mb-3 shadow text-xs">
            <div class="bg-yellow-400 text-red-600 p-1 rounded-full text-sm">
                👤
            </div>

            <div>
                <p class="font-semibold text-xs">SK President</p>
                <p class="text-xs opacity-80">Active Role</p>
            </div>
        </div>

        <nav class="space-y-1 text-xs">
            @foreach ($menuItems as $item)
                <a href="{{ $item['link'] }}"
                    class="flex items-center gap-2 p-2 rounded-lg {{ $item['link'] === $currentUrl ? 'bg-red-500' : 'hover:bg-red-500 transition' }}">

                    <span class="{{ $item['link'] === $currentUrl ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded text-sm">
                        {{ $item['icon'] }}
                    </span>

                    <span class="{{ $item['link'] === $currentUrl ? 'text-yellow-300 font-semibold' : '' }} text-xs">
                        {{ $item['label'] }}
                    </span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="flex-1 flex flex-col">
        <div class="bg-red-600 text-white px-6 py-3 flex justify-between items-center shadow">
            <input type="text"
                placeholder="Search..."
                class="px-4 py-2 rounded-full text-black w-1/3 focus:outline-none">

            <div class="flex items-center gap-3 relative">
                <div class="relative">
                    <button id="notifBtn"
                        type="button"
                        class="text-xl hover:bg-red-500 p-2 rounded-lg transition">
                        🔔
                    </button>

                    <div id="notifDropdown"
                        class="hidden absolute right-0 mt-3 w-72 bg-white rounded-2xl shadow-xl border z-50 overflow-hidden">

                        <div class="px-4 py-3 font-semibold border-b text-gray-800">
                            Notifications
                        </div>

                        <div class="max-h-64 overflow-y-auto">
                            <div class="px-4 py-3 hover:bg-gray-100 text-sm text-gray-700">
                                No notifications yet
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <button id="userMenuBtn"
                        type="button"
                        class="flex items-center gap-2 hover:bg-red-500 px-3 py-2 rounded-lg transition">

                        <span class="font-semibold">
                            {{ $fullName }}
                        </span>
                    </button>

                    <div id="userDropdown"
                        class="hidden absolute right-0 mt-3 w-64 bg-white rounded-2xl shadow-xl border overflow-hidden z-50">

                        <div class="px-5 py-4 font-semibold text-gray-800 border-b">
                            My Account
                        </div>

                        <a href="{{ route('sk_pres.profile') }}"
                            class="flex items-center gap-3 px-5 py-3 hover:bg-gray-100 transition">

                            <span>👤</span>
                            <span class="text-gray-700">Profile Settings</span>
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <button type="submit"
                                class="w-full text-left flex items-center gap-3 px-5 py-3 text-red-500 hover:bg-gray-100 transition">

                                <span>↩️</span>
                                <span>Log Out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <main class="flex-1 overflow-y-auto p-8 bg-[#f8fafc]">
            <div class="flex items-start justify-between mb-8">
                <div>
                    <h2 class="text-[38px] font-bold text-gray-900 leading-tight">
                        Submission Slot Management
                    </h2>

                    <p class="text-gray-500 mt-2 text-base">
                        Create and manage submission periods for Accomplishment Reports and Budget & Financial Reports
                    </p>
                </div>

                <button id="openModalBtn"
                    class="bg-red-600 hover:bg-red-700 text-white px-5 py-3 rounded-lg text-sm font-semibold shadow-sm">
                    ＋ Create Submission Slot
                </button>
            </div>

            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div id="submissionModal"
                class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50 px-4">

                <div class="bg-white w-full max-w-3xl max-h-[92vh] overflow-y-auto rounded-[24px] border-2 border-blue-500 shadow-2xl p-8 relative">

                    <button id="closeModalBtn"
                        type="button"
                        class="absolute top-4 right-5 text-gray-500 hover:text-red-600 text-2xl font-bold">
                        &times;
                    </button>

                    <h2 class="text-4xl font-bold text-gray-900 mb-2">
                        Create New Submission Slot
                    </h2>

                    <p class="text-gray-600 mb-8 text-base">
                        Set up a new submission period for SK officials to submit reports
                    </p>

                    <form id="slotForm"
                        action="{{ route('sk_pres.module.store') }}"
                        method="POST"
                        class="space-y-6">

                        @csrf

                        <div>
                            <label class="block text-lg font-semibold text-gray-900 mb-2">
                                Submission Type
                            </label>

                            <select id="submissionType"
                                name="submission_type"
                                class="w-full h-14 px-4 rounded-xl border border-red-300 bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-400">

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

                        <div id="budgetDetails" class="hidden space-y-5 rounded-2xl border border-red-200 bg-red-50/40 p-5">

                            <div>
                                <h3 class="text-lg font-bold text-gray-900">
                                    Budget / Financial Report Details
                                </h3>

                                <p class="text-sm text-gray-500 mt-1">
                                    Specify what type of financial submission is expected from the barangay.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-900 mb-2">
                                        Report Category
                                    </label>

                                    <select id="budgetCategory"
                                        name="budget_category"
                                        class="w-full h-14 px-4 rounded-xl border border-red-300 bg-white focus:outline-none focus:ring-2 focus:ring-red-400">

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
                                    <label class="block text-sm font-semibold text-gray-900 mb-2">
                                        Fiscal Year
                                    </label>

                                    <input id="fiscalYear"
                                        type="number"
                                        name="fiscal_year"
                                        min="2000"
                                        max="2100"
                                        value="{{ old('fiscal_year', now()->year) }}"
                                        class="w-full h-14 px-4 rounded-xl border border-red-300 bg-white focus:outline-none focus:ring-2 focus:ring-red-400">
                                </div>
                            </div>

                            <div id="reportPeriodSection" class="hidden">
                                <label class="block text-sm font-semibold text-gray-900 mb-2">
                                    Reporting Period
                                </label>

                                <select id="budgetPeriodType"
                                    name="budget_period_type"
                                    class="w-full h-14 px-4 rounded-xl border border-red-300 bg-white focus:outline-none focus:ring-2 focus:ring-red-400">

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
                                <label class="block text-sm font-semibold text-gray-900 mb-2">
                                    Month
                                </label>

                                <select id="fiscalMonth"
                                    name="fiscal_month"
                                    class="w-full h-14 px-4 rounded-xl border border-red-300 bg-white focus:outline-none focus:ring-2 focus:ring-red-400">

                                    <option value="">Select month</option>

                                    @foreach ([
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
                                        12 => 'December'
                                    ] as $monthNumber => $monthName)

                                        <option value="{{ $monthNumber }}"
                                            @selected((string) old('fiscal_month') === (string) $monthNumber)>
                                            {{ $monthName }}
                                        </option>

                                    @endforeach
                                </select>
                            </div>

                            <div id="quarterlySection" class="hidden">
                                <label class="block text-sm font-semibold text-gray-900 mb-2">
                                    Quarter
                                </label>

                                <select id="fiscalQuarter"
                                    name="fiscal_quarter"
                                    class="w-full h-14 px-4 rounded-xl border border-red-300 bg-white focus:outline-none focus:ring-2 focus:ring-red-400">

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
                                <label class="block text-sm font-semibold text-gray-900 mb-2">
                                    Semi-Annual Period
                                </label>

                                <select id="fiscalHalf"
                                    name="fiscal_half"
                                    class="w-full h-14 px-4 rounded-xl border border-red-300 bg-white focus:outline-none focus:ring-2 focus:ring-red-400">

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
                            <label class="block text-lg font-semibold text-gray-900 mb-2">
                                Submission Title
                            </label>

                            <input type="text"
                                id="submissionTitle"
                                name="submission_title"
                                class="w-full h-14 px-4 rounded-xl border border-red-300 bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-400"
                                value="{{ old('submission_title') }}">
                        </div>

                        <div>
                            <label class="block text-lg font-semibold text-gray-900 mb-2">
                                Description
                            </label>

                            <input type="text"
                                id="submissionDescription"
                                name="description"
                                class="w-full h-14 px-4 rounded-xl border border-red-300 bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-400"
                                value="{{ old('description') }}">
                        </div>

                        <div>
                            <label class="block text-lg font-semibold text-gray-900 mb-2">
                                Who Can Submit
                            </label>

                            <select id="submissionRole"
                                name="submission_role"
                                class="w-full h-14 px-4 rounded-xl border border-red-300 bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-400">

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
                                <label class="block text-lg font-semibold text-gray-900 mb-2">
                                    Start Date
                                </label>

                                <input type="date"
                                    id="startDate"
                                    name="start_date"
                                    class="w-full h-14 px-4 rounded-xl border border-red-300 bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-400"
                                    value="{{ old('start_date') }}">
                            </div>

                            <div>
                                <label class="block text-lg font-semibold text-gray-900 mb-2">
                                    Submission Deadline
                                </label>

                                <input type="date"
                                    id="endDate"
                                    name="end_date"
                                    class="w-full h-14 px-4 rounded-xl border border-red-300 bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-400"
                                    value="{{ old('end_date') }}">
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full bg-red-600 hover:bg-red-700 text-white text-2xl font-bold py-4 rounded-2xl transition">
                            Create Slot
                        </button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-10">
                @foreach ($summaryCards as $card)

                    <div class="bg-white rounded-2xl border {{ $card['border'] }} p-5">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm text-gray-500 mb-2">
                                    {{ $card['label'] }}
                                </p>

                                <h3 class="text-4xl font-bold text-gray-900 leading-none">
                                    {{ $card['value'] }}
                                </h3>
                            </div>

                            <div class="w-12 h-12 rounded-xl {{ $card['iconBg'] }} flex items-center justify-center {{ $card['iconColor'] }} text-xl">
                                {{ $card['icon'] }}
                            </div>
                        </div>
                    </div>

                @endforeach
            </div>

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
            @endphp

            <div id="slotContainer"
                class="grid grid-cols-1 xl:grid-cols-2 gap-5">

                @forelse ($slots as $slot)

                    <div class="bg-white rounded-2xl border border-green-400 p-6 min-h-[280px]">

                        <div class="flex justify-between items-start mb-5">
                            <div>
                                <p class="text-xs text-gray-400">
                                    {{ $slot->submission_type === 'budget_report' ? 'Budget / Financial Report' : 'Accomplishment Report' }}
                                </p>

                                <h4 class="text-lg font-medium">
                                    {{ $slot->title }}
                                </h4>

                                <p class="text-sm text-gray-400">
                                    {{ $slot->description }}
                                </p>
                            </div>

                            <button type="button"
                                onclick="deleteSlot({{ $slot->slot_id }})"
                                class="text-red-500 text-xl hover:text-red-700">
                                ✕
                            </button>
                        </div>

                        @if ($slot->submission_type === 'budget_report')

                            <div class="mb-4 flex flex-wrap gap-2">

                                @if (!empty($slot->budget_category))
                                    <span class="text-xs bg-red-50 text-red-600 border border-red-100 px-3 py-1.5 rounded-full font-medium">
                                        {{ $budgetCategoryLabels[$slot->budget_category] ?? $slot->budget_category }}
                                    </span>
                                @endif

                                @if (!empty($slot->fiscal_year))
                                    <span class="text-xs bg-blue-50 text-blue-600 border border-blue-100 px-3 py-1.5 rounded-full font-medium">
                                        FY {{ $slot->fiscal_year }}
                                    </span>
                                @endif

                                @if ($slot->budget_category === 'coa_report' && !empty($slot->budget_period_type))
                                    <span class="text-xs bg-purple-50 text-purple-600 border border-purple-100 px-3 py-1.5 rounded-full font-medium">
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

                        <div class="text-sm text-gray-500 mb-4">
                            📅 {{ $slot->start_date }} - {{ $slot->end_date }}
                        </div>

                        <span class="text-xs bg-gray-100 px-2 py-1 rounded">
                            {{ $slot->role }}
                        </span>

                        <div class="mt-4 text-xs {{ $slot->status === 'open' ? 'text-green-600' : 'text-gray-500' }}">
                            {{ $slot->status === 'open' ? '🔓 Open' : '🔒 Closed' }}
                        </div>

                        <form id="delete-slot-{{ $slot->slot_id }}"
                            method="POST"
                            action="{{ route('sk_pres.module.destroy', $slot->slot_id) }}"
                            class="hidden">
                            @csrf
                        </form>
                    </div>

                @empty

                    <div id="emptySlotState"
                        class="bg-white rounded-2xl border border-dashed border-gray-300 p-8 text-center text-gray-400 xl:col-span-2">
                        No submission slots yet.
                    </div>

                @endforelse
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

    @if ($errors->any())
        submissionModal.classList.remove('hidden');
        submissionModal.classList.add('flex');
        syncBudgetFields();
    @endif
</script>
@endpush