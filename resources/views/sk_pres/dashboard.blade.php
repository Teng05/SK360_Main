{{-- File guide: Blade view template for resources/views/sk_pres/dashboard.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 Dashboard')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('content')
@php
    $selectedBarangayName = $selectedBarangay
        ? optional($barangays->firstWhere('barangay_id', $selectedBarangay))->barangay_name
        : null;
@endphp

<div class="flex h-screen bg-gray-100">
    @include('partials.app.sidebar')

    <div class="flex-1 flex flex-col overflow-hidden">
        @include('partials.app.topbar')

        <div class="flex-1 bg-gray-100 p-8 overflow-y-auto">
            <span class="sk-eyebrow"><span class="sk-dot"></span>Dashboard</span>
            <h1 class="text-4xl font-bold text-gray-900 mb-2">
                Welcome back, SK President
            </h1>

            <p class="text-gray-600 text-lg mb-8">
                Here's an overview of SK activities and submissions as of {{ $overviewDate }}
            </p>

            @php
                // Presentation only: an outline icon and tint per card label.
                $cardStyles = [
                    'Total Officials' => ['icon' => 'users', 'tone' => ''],
                    'Active Accounts' => ['icon' => 'circle-check', 'tone' => 'green'],
                    'SK Chairmen' => ['icon' => 'id-card', 'tone' => ''],
                    'SK Secretaries' => ['icon' => 'file-text', 'tone' => 'blue'],
                ];
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                @foreach ($cards as $card)
                    @php $style = $cardStyles[$card['label']] ?? ['icon' => 'layout-grid', 'tone' => '']; @endphp

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

                        <div class="sk-stat__meta">
                            <p>{{ $card['subline1'] }}</p>
                            <p>{{ $card['subline2'] }}</p>
                        </div>

                        <p class="sk-stat__footer {{ $card['footerClass'] }}">{{ $card['footer'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 sk-card p-5">
                <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <span class="sk-icon-tile sk-icon-tile--sm sk-icon-tile--gray">
                            @include('partials.ui.icon', ['icon' => 'filter', 'iconSize' => 17])
                        </span>
                        <div>
                            <h2 class="sk-section-title !text-[17px]">Submission & Budget Filters</h2>
                            <p class="sk-section-subtitle">
                                Filter the submission and financial monitoring data below.
                            </p>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('sk_pres.dashboard') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                        <div>
                            <label for="barangay_id" class="block sk-overline mb-1.5">
                                Barangay
                            </label>

                            <select id="barangay_id" name="barangay_id" class="w-full sm:w-56 h-11 border border-gray-200 rounded-xl px-3 text-sm font-semibold bg-white">
                                <option value="">All Barangays</option>

                                @foreach ($barangays as $barangay)
                                    <option value="{{ $barangay->barangay_id }}" {{ $selectedBarangay == $barangay->barangay_id ? 'selected' : '' }}>
                                        {{ $barangay->barangay_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="fiscal_year" class="block sk-overline mb-1.5">
                                Fiscal Year
                            </label>

                            <select id="fiscal_year" name="fiscal_year" class="w-full sm:w-48 h-11 border border-gray-200 rounded-xl px-3 text-sm font-semibold bg-white">
                                <option value="" {{ !$selectedYear ? 'selected' : '' }}>
                                    Automatic
                                </option>

                                @foreach ($availableYears as $year)
                                    <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                                        FY {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="sk-btn sk-btn--primary">
                            Apply
                        </button>

                        @if ($selectedBarangay || $selectedYear)
                            <a href="{{ route('sk_pres.dashboard') }}" class="sk-btn sk-btn--secondary">
                                Reset
                            </a>
                        @endif
                    </form>
                </div>

                @if ($selectedBarangayName || $selectedYear)
                    <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap gap-2">
                        @if ($selectedBarangayName)
                            <span class="bg-red-50 text-red-600 px-3 py-1 rounded-full text-xs font-semibold">
                                Barangay: {{ $selectedBarangayName }}
                            </span>
                        @else
                            <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-semibold">
                                All Barangays
                            </span>
                        @endif

                        @if ($selectedYear)
                            <span class="bg-blue-50 text-blue-600 px-3 py-1 rounded-full text-xs font-semibold">
                                FY {{ $selectedYear }}
                            </span>
                        @endif
                    </div>
                @endif
            </div>

            <div class="mt-8">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Submission Compliance</h2>
                        <p class="text-sm text-gray-500">
                            @if ($selectedBarangayName)
                                {{ $selectedBarangayName }} —
                            @endif
                            Required submission status for {{ $submissionKpis['year'] }}
                        </p>
                    </div>

                    <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-600">
                        FY {{ $submissionKpis['year'] }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="sk-stat__label">Submission Rate</p>
                                <h2 class="text-4xl font-bold text-gray-900">
                                    {{ number_format($submissionKpis['submission_rate'], 1) }}%
                                </h2>
                            </div>

                            <span class="sk-icon-tile sk-icon-tile--green">@include('partials.ui.icon', ['icon' => 'circle-check', 'iconSize' => 21])</span>
                        </div>

                        <p class="text-sm text-gray-500">
                            {{ $submissionKpis['submitted'] }} of {{ $submissionKpis['required'] }} required submissions received
                        </p>

                        <div class="mt-4 sk-progress sk-progress--green">
                            <div class="h-full rounded-full" style="background: var(--sk-green); width: {{ min($submissionKpis['submission_rate'], 100) }}%"></div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="sk-stat__label">On-Time Submission Rate</p>
                                <h2 class="text-4xl font-bold text-gray-900">
                                    {{ number_format($submissionKpis['on_time_rate'], 1) }}%
                                </h2>
                            </div>

                            <span class="sk-icon-tile sk-icon-tile--blue">@include('partials.ui.icon', ['icon' => 'clock', 'iconSize' => 21])</span>
                        </div>

                        <p class="text-sm text-gray-500">
                            {{ $submissionKpis['on_time'] }} of {{ $submissionKpis['submitted'] }} submissions received on or before deadline
                        </p>

                        <div class="mt-4 sk-progress sk-progress--blue">
                            <div class="h-full rounded-full" style="background: var(--sk-blue); width: {{ min($submissionKpis['on_time_rate'], 100) }}%"></div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="sk-stat__label">Pending Reports</p>
                                <h2 class="text-4xl font-bold text-gray-900">
                                    {{ $submissionKpis['pending'] }}
                                </h2>
                            </div>

                            <span class="sk-icon-tile sk-icon-tile--yellow">@include('partials.ui.icon', ['icon' => 'hourglass', 'iconSize' => 21])</span>
                        </div>

                        <p class="text-sm text-gray-500">
                            Required submissions that have not yet been received
                        </p>

                        <p class="mt-4 text-xs font-semibold text-gray-400">
                            Started submission slots only
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-2">
                <section class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Budget Utilization</h2>
                            <p class="text-sm text-gray-500">
                                @if ($selectedBarangayName)
                                    {{ $selectedBarangayName }} —
                                @endif
                                Annual COA expenditure against Annual Budget for FY {{ $chartData['budgetUtilization']['year'] }}
                            </p>
                        </div>

                        <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-600">
                            Utilization
                        </span>
                    </div>

                    <div class="h-72 overflow-x-auto">
                        <div class="h-full" style="min-width: {{ max(700, count($chartData['budgetUtilization']['labels'] ?? []) * 75) }}px;">
                            <canvas id="budgetUtilizationChart"></canvas>
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Barangay Submission Activity</h2>

                            @if ($selectedBarangayName)
                                <p class="text-sm text-gray-500">
                                    Accomplishment and budget submissions for {{ $selectedBarangayName }} in {{ $chartData['barangaySubmissions']['year'] }}
                                </p>
                            @else
                                <p class="text-sm text-gray-500">
                                    Top barangays by report and budget submissions for {{ $chartData['barangaySubmissions']['year'] }}
                                </p>
                            @endif
                        </div>

                        <span class="rounded-full bg-green-50 px-3 py-1 text-xs font-bold text-green-600">
                            Activity
                        </span>
                    </div>

                    <div class="h-72">
                        <canvas id="barangaySubmissionsChart"></canvas>
                    </div>
                </section>

                <section class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Annual Budget by Barangay</h2>

                            @if ($selectedBarangayName)
                                <p class="text-sm text-gray-500">
                                    Annual Budget submitted by {{ $selectedBarangayName }} for FY {{ $chartData['annualBudget']['year'] }}
                                </p>
                            @else
                                <p class="text-sm text-gray-500">
                                    Annual Budget amounts submitted for FY {{ $chartData['annualBudget']['year'] }}
                                </p>
                            @endif
                        </div>

                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-600">
                            Annual Budget
                        </span>
                    </div>

                    <div class="h-72 overflow-x-auto">
                        <div class="h-full" style="min-width: {{ max(700, count($chartData['annualBudget']['labels'] ?? []) * 75) }}px;">
                            <canvas id="annualBudgetChart"></canvas>
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Submission Trends</h2>
                            <p class="text-sm text-gray-500">
                                Reports, events, and meetings over the last six months
                            </p>
                        </div>

                        <span class="rounded-full bg-yellow-50 px-3 py-1 text-xs font-bold text-yellow-600">
                            Trends
                        </span>
                    </div>

                    <div class="h-72">
                        <canvas id="engagementMetricsChart"></canvas>
                    </div>
                </section>
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

    const chartData = @json($chartData);
    const chartGridColor = 'rgba(148, 163, 184, 0.18)';
    const chartTextColor = '#64748b';

    Chart.defaults.font.family = 'Manrope, system-ui, sans-serif';
    Chart.defaults.color = chartTextColor;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;

    function makeBarChart(canvasId, labels, values, color, valueType = 'number') {
        new Chart(document.getElementById(canvasId), {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: color,
                    borderRadius: 8,
                    maxBarThickness: 42
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const value = Number(context.raw || 0);

                                if (valueType === 'money') {
                                    return '₱' + value.toLocaleString('en-PH', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    });
                                }

                                if (valueType === 'percent') {
                                    return value.toLocaleString('en-PH', {
                                        maximumFractionDigits: 2
                                    }) + '%';
                                }

                                return value;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                if (valueType === 'money') {
                                    if (value >= 1000000) {
                                        return '₱' + (value / 1000000) + 'M';
                                    }

                                    if (value >= 1000) {
                                        return '₱' + (value / 1000) + 'K';
                                    }

                                    return '₱' + value;
                                }

                                if (valueType === 'percent') {
                                    return value + '%';
                                }

                                return value;
                            }
                        },
                        grid: {
                            color: chartGridColor
                        }
                    }
                }
            }
        });
    }

    function makeGroupedBarChart(canvasId, labels, datasets) {
        new Chart(document.getElementById(canvasId), {
            type: 'bar',
            data: {
                labels,
                datasets: datasets.map((dataset) => ({
                    ...dataset,
                    borderRadius: 8,
                    maxBarThickness: 34
                }))
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        },
                        grid: {
                            color: chartGridColor
                        }
                    }
                }
            }
        });
    }

    function makeMultiLineChart(canvasId, labels, datasets) {
        new Chart(document.getElementById(canvasId), {
            type: 'line',
            data: {
                labels,
                datasets: datasets.map((dataset) => ({
                    ...dataset,
                    fill: false,
                    tension: 0.35,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderWidth: 2
                }))
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    x: {
                        grid: {
                            borderDash: [4, 4],
                            color: chartGridColor
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        },
                        grid: {
                            borderDash: [4, 4],
                            color: chartGridColor
                        }
                    }
                }
            }
        });
    }

    makeBarChart(
        'budgetUtilizationChart',
        chartData.budgetUtilization.labels,
        chartData.budgetUtilization.values,
        '#dc2626',
        'percent'
    );

    makeGroupedBarChart(
        'barangaySubmissionsChart',
        chartData.barangaySubmissions.labels,
        [
            {
                label: 'Accomplishment',
                data: chartData.barangaySubmissions.accomplishment,
                backgroundColor: '#d4a020'
            },
            {
                label: 'Budget',
                data: chartData.barangaySubmissions.budget,
                backgroundColor: '#2e62d1'
            }
        ]
    );

    makeBarChart(
        'annualBudgetChart',
        chartData.annualBudget.labels,
        chartData.annualBudget.values,
        '#2e62d1',
        'money'
    );

    makeMultiLineChart(
        'engagementMetricsChart',
        chartData.engagementMetrics.labels,
        [
            {
                label: 'Events',
                data: chartData.engagementMetrics.events,
                borderColor: '#d4a020',
                backgroundColor: '#d4a020'
            },
            {
                label: 'Meetings',
                data: chartData.engagementMetrics.meetings,
                borderColor: '#2e62d1',
                backgroundColor: '#2e62d1'
            },
            {
                label: 'Reports',
                data: chartData.engagementMetrics.reports,
                borderColor: '#dc2626',
                backgroundColor: '#dc2626'
            }
        ]
    );
</script>
@endpush
