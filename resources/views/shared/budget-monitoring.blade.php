@extends('layouts.app')
@section('title', $isPresident ? 'SK 360 | Budget & Finance' : 'SK 360 | Budget')
@section('page_css')
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="{{ asset('css/budget-monitoring.css') }}?v={{ filemtime(public_path('css/budget-monitoring.css')) }}">
@endsection
@section('content')
@php
    $selectedBarangay = $isPresident ? (int) old('_barangay', request('barangay_id', 0)) : (int) auth()->user()->barangay_id;
    $visibleBudgets = $selectedBarangay ? $budgets->where('barangay_id', $selectedBarangay)->values() : $budgets;
    $budget = $visibleBudgets->count() === 1 ? $visibleBudgets->first() : null;
    $availableProjects = $budget ? $budget->projects->where('implementation_status', '!=', 'cancelled') : collect();
    $totals = [];
    foreach (['allotted', 'expenses', 'remaining'] as $key) $totals[$key] = \App\Services\BudgetMonitoringService::decimal($visibleBudgets->sum(fn($item) => \App\Services\BudgetMonitoringService::cents($item->summary[$key])));
    $utilization = (float)$totals['allotted'] > 0 ? ((float)$totals['expenses'] / (float)$totals['allotted']) * 100 : 0;
@endphp
<div class="flex h-screen overflow-hidden bg-gray-100 budget-monitoring" data-budget-page data-reopen="{{ $errors->any() ? old('_dialog', '') : '' }}">
    @include('partials.app.sidebar')
    <div class="flex-1 flex flex-col overflow-hidden min-w-0">
        @include('partials.app.topbar', ['bindBell' => true, 'bindAccount' => true])
        <main class="flex-1 overflow-y-auto p-8">
            @if(!$financialSchemaReady && $isPresident)<p role="status" class="sk-alert sk-alert--error mb-5">Allocation entry without a reference requires annual_budgets.approval_reference to allow NULL. Project/category creation and existing records remain available.</p>@endif
            <div class="sk-page-head">
                <div class="sk-page-head__text"><span class="sk-eyebrow"><span class="sk-dot"></span>Financial Management</span><h1 class="sk-page-title">{{ $isPresident ? 'Budget & Finance' : 'Budget' }}</h1><p class="sk-page-subtitle">{{ $isPresident ? 'Manage allotted budgets and monitor barangay expenditures.' : 'Manage your barangay budget, expenses, and financial submissions.' }}</p></div>
                <div class="sk-page-head__actions">
                    @if($isPresident)<button type="button" class="sk-btn sk-btn--primary" data-open-dialog="allocation-dialog">@include('partials.ui.icon', ['icon' => 'plus', 'iconSize' => 18]) Record annual allocation</button>
                    @elseif($budget && $financialSection !== 'submissions')<button type="button" class="sk-btn sk-btn--primary" data-open-dialog="create-expense">@include('partials.ui.icon', ['icon' => 'plus', 'iconSize' => 18]) Add Expense</button>@endif
                </div>
            </div>
            @if(session('success'))<div role="status" class="sk-alert sk-alert--success mb-5">{{ session('success') }}</div>@endif
            @if($errors->any() && $financialSection !== 'submissions')<div role="alert" class="sk-alert sk-alert--error budget-errors mb-5"><p class="font-bold">Please check your submission.</p><ul class="list-disc pl-5 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @include('shared.financial-section-nav')
            @if($financialSection !== 'submissions')
            <form method="get" class="sk-card p-5 mb-6 flex flex-wrap items-center gap-4" data-financial-filters data-default-year="{{ ($defaultFinancialYear ?? now()->year) }}">
                <input type="hidden" name="section" value="{{ $financialSection }}">
                <div class="flex items-center gap-3 mr-auto"><span class="sk-icon-tile sk-icon-tile--sm sk-icon-tile--gray">@include('partials.ui.icon', ['icon' => 'filter', 'iconSize' => 18])</span><div><h2 class="sk-section-title">{{ $isPresident ? 'Financial records' : ($barangays->firstWhere('barangay_id', auth()->user()->barangay_id)->barangay_name ?? 'Your barangay') }}</h2><p class="sk-section-subtitle">{{ $isPresident ? 'Filter by fiscal year and barangay.' : 'View your assigned barangay’s budget for the selected year.' }}</p></div></div>
                <div class="flex flex-wrap items-end justify-end gap-4 ml-auto">
                <label class="budget-year"><span class="sk-label">Fiscal year</span><select name="year" class="sk-control w-full" required>@foreach($fiscalYears as $fiscalYear)<option value="{{ $fiscalYear }}" @selected((int)$fiscalYear === $year)>{{ $fiscalYear }}</option>@endforeach</select></label>
                @if($isPresident)<label class="budget-selector"><span class="sk-label">Barangay</span><select name="barangay_id" class="sk-control w-full"><option value="0" @selected(!$selectedBarangay)>All Barangays</option>@foreach($barangays as $option)<option value="{{ $option->barangay_id }}" @selected($selectedBarangay === (int)$option->barangay_id)>{{ $option->barangay_name }}</option>@endforeach</select></label>@endif
                <button class="sk-btn sk-btn--secondary">Apply filters</button>
                <a data-clear-financial-filters href="{{ route(($isPresident ? 'sk_pres' : 'sk_chairman').'.finances', ['section' => $financialSection, 'year' => ($defaultFinancialYear ?? now()->year)]) }}" class="sk-btn sk-btn--secondary" @if($year === (int)($defaultFinancialYear ?? now()->year) && (!$isPresident || !$selectedBarangay)) hidden @endif>Clear</a>
                </div>
            </form>
            @endif
            @if($financialSection === 'submissions')
                <section id="panel-submissions" aria-label="Budget Submissions">
                    @include('shared.submission-slots-page', array_merge($submissionData, ['chairmanReportUi' => true, 'embeddedFinancial' => true]))
                </section>
            @elseif($financialSection === 'overview')
                <section id="panel-overview" aria-label="Budget overview">
                    <div class="grid grid-cols-1 md:grid-cols-3 auto-rows-fr gap-5 mb-5">
                        @foreach(['allotted' => [$isPresident && !$selectedBarangay ? 'Total Allotted Budget' : 'Allotted Budget', 'wallet', 'blue'], 'expenses' => [$isPresident && !$selectedBarangay ? 'Total Reported Expenses' : 'Reported Expenses', 'file-text', 'orange'], 'remaining' => [$isPresident && !$selectedBarangay ? 'Total Estimated Remaining Budget' : 'Estimated Remaining Budget', 'chart-column', 'green']] as $key => [$label, $icon, $tone])
                        <div class="sk-stat min-w-0 {{ $key === 'remaining' ? 'budget-remaining-card' : '' }}"><div class="sk-stat__top"><p class="sk-stat__label">{{ $label }}</p><span class="sk-icon-tile sk-icon-tile--{{ $tone }}">@include('partials.ui.icon', ['icon' => $icon, 'iconSize' => 21])</span></div><p class="sk-stat__value budget-value"><span>PHP</span> {{ number_format((float)$totals[$key], 2) }}</p>
                            @if($key === 'remaining')<progress class="budget-progress" max="100" value="{{ max(0, min(100, $utilization)) }}" aria-label="Percentage of allotted budget reported as expenses"></progress><p class="sk-stat__footer">{{ number_format($utilization, 1) }}% reported as expenses &middot; Monitoring estimate, not cash on hand.</p>
                            @else<p class="sk-stat__footer">{{ $key === 'allotted' ? 'Encoded by the SK Federation President' : 'Active reported expenses; deleted entries excluded' }}</p>@endif
                        </div>
                        @endforeach
                    </div>
                    <p class="budget-note mb-6">@include('partials.ui.icon', ['icon' => 'info', 'iconSize' => 17]) Estimated balances do not represent actual cash available. Cheque releases occur separately.</p>
                    @if($isPresident)
                    <section class="sk-card overflow-hidden"><div class="p-5"><h2 class="sk-section-title">Barangay Budget Overview</h2><p class="sk-section-subtitle">Fiscal year {{ $year }} &middot; {{ $visibleBudgets->count() }} allocation(s) recorded</p></div>
                        @include('shared.financial-allocation-table', ['allocationsMode' => false])
                    </section>
                    @else<section class="sk-card overflow-hidden"><div class="p-5 flex items-center justify-between gap-3"><h2 class="sk-section-title">Recent Expenses</h2><a class="budget-link" href="{{ route('sk_chairman.finances', ['year' => $year, 'section' => 'expenses']) }}">View all expenses</a></div>@include('shared.financial-expense-table', ['recent' => true])</section>@endif
                </section>
            @elseif($financialSection === 'allocations' && $isPresident)
                <section id="panel-allocations" class="sk-card overflow-hidden"><div class="p-5"><h2 class="sk-section-title">Annual allocations</h2><p class="sk-section-subtitle">Record and correct allocations while retaining the original financial history.</p></div>@include('shared.financial-allocation-table', ['allocationsMode' => true])</section>
            @elseif($financialSection === 'expenses')
                <section id="panel-expenses" class="sk-card overflow-hidden"><div class="p-5 flex flex-wrap items-center justify-between gap-4"><div><h2 class="sk-section-title">Expense records</h2><p class="sk-section-subtitle">{{ $isPresident ? 'Monitor reported expenses and supporting documents.' : 'Record and manage expenses with supporting proof.' }}</p></div></div>@include('shared.financial-expense-table', ['recent' => false])</section>
            @elseif($financialSection === 'transparency' && $isPresident)
                <section id="panel-transparency" class="sk-card p-5">@include('shared.financial-transparency')</section>
            @elseif($financialSection === 'history')
                <section id="panel-history" class="sk-card p-5">@include('shared.financial-history')</section>
            @endif
        </main>
    </div>
</div>
@if($isPresident)@include('shared.financial-budget-dialogs', ['budget' => null])@endif
@foreach($visibleBudgets as $recordBudget)
    @include('shared.financial-budget-dialogs', ['budget' => $recordBudget, 'includeAllocationDialog' => false])
    @foreach($recordBudget->expenses as $expense)@include('shared.financial-expense-detail', ['budget' => $recordBudget])@endforeach
@endforeach
@include('shared.financial-proof-preview')
@endsection
@push('scripts')
<script src="{{ asset('js/budget-monitoring.js') }}?v={{ filemtime(public_path('js/budget-monitoring.js')) }}" defer></script>
@endpush
@if($submissionData)@include('shared.budget-submission-scripts')@endif
