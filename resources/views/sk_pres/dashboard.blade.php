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
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
        <div class="flex items-center gap-3 mb-4">
            <img src="{{ asset('images/logo.png') }}" class="w-8 h-8 rounded-full object-cover" alt="logo">
            <div class="leading-tight">
                <h2 class="text-lg font-extrabold tracking-wide">SK 360°</h2>
                <p class="text-[10px] opacity-80">Management System</p>
            </div>
        </div>

        <div class="bg-red-500 rounded-lg p-2 flex items-center gap-2 mb-3 shadow text-xs">
            <div class="bg-yellow-400 text-red-600 p-1 rounded-full text-sm">👤</div>
            <div>
                <p class="font-semibold text-xs">SK President</p>
                <p class="text-xs opacity-80">Active Role</p>
            </div>
        </div>

        <nav class="space-y-1 text-xs">
            @foreach ($menuItems as $item)
                @php
                    $isActive = $item['link'] === $currentUrl;
                @endphp
                <a href="{{ $item['link'] }}" class="flex items-center gap-2 p-2 rounded-lg {{ $isActive ? 'bg-red-500' : 'hover:bg-red-500 transition' }}">
                    <span class="{{ $isActive ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded text-sm">{{ $item['icon'] }}</span>
                    <span class="{{ $isActive ? 'text-yellow-300 font-semibold' : '' }} text-xs">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="flex-1 flex flex-col overflow-hidden">
        <div class="bg-red-600 text-white px-6 py-3 flex justify-between items-center shadow">
            <input type="text" placeholder="Search..." class="px-4 py-2 rounded-full text-black w-1/3 focus:outline-none">

            <div class="flex items-center gap-3 relative">
                <div class="relative">
                    <button id="notifBtn" type="button" class="text-xl hover:bg-red-500 p-2 rounded-lg transition">
                        🔔
                    </button>

                    <div id="notifDropdown" class="hidden absolute right-0 mt-3 w-72 bg-white rounded-2xl shadow-xl border z-50 overflow-hidden">
                        <div class="px-4 py-3 font-semibold border-b text-gray-800">Notifications</div>
                        <div class="max-h-64 overflow-y-auto">
                            <div class="px-4 py-3 hover:bg-gray-100 text-sm text-gray-700">
                                No notifications yet
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <button id="userMenuBtn" type="button" class="flex items-center gap-2 hover:bg-red-500 px-3 py-2 rounded-lg transition">
                        <span class="font-semibold">{{ $fullName }}</span>
                    </button>

                    <div id="userDropdown" class="hidden absolute right-0 mt-3 w-64 bg-white rounded-2xl shadow-xl border overflow-hidden z-50">
                        <div class="px-5 py-4 font-semibold text-gray-800 border-b">
                            My Account
                        </div>

                        <a href="{{ route('sk_pres.profile') }}" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-100 transition">
                            <span>👤</span>
                            <span class="text-gray-700">Profile Settings</span>
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
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

        <div class="flex-1 bg-gray-100 p-8 overflow-y-auto">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">
                Welcome back, SK President
            </h1>

            <p class="text-gray-600 text-lg mb-8">
                Here's an overview of SK activities and submissions as of {{ $overviewDate }}
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                @foreach ($cards as $card)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-sm text-gray-500">{{ $card['label'] }}</p>
                                <h2 class="text-4xl font-bold text-gray-900 leading-none">{{ $card['value'] }}</h2>
                            </div>

                            <div class="{{ $card['iconWrap'] }} p-3 rounded-xl">
                                <span class="{{ $card['iconClass'] }} text-xl">{{ $card['icon'] }}</span>
                            </div>
                        </div>

                        <div class="text-sm text-gray-500 leading-5 mb-3">
                            <p>{{ $card['subline1'] }}</p>
                            <p>{{ $card['subline2'] }}</p>
                        </div>

                        <p class="text-sm {{ $card['footerClass'] }}">{{ $card['footer'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Submission & Budget Filters</h2>
                        <p class="text-sm text-gray-500">
                            Filter the submission and financial monitoring data below.
                        </p>
                    </div>

                    <form method="GET" action="{{ route('sk_pres.dashboard') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                        <div>
                            <label for="barangay_id" class="block text-xs font-semibold text-gray-500 mb-1">
                                Barangay
                            </label>

                            <select id="barangay_id" name="barangay_id" class="w-full sm:w-56 border border-gray-300 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                                <option value="">All Barangays</option>

                                @foreach ($barangays as $barangay)
                                    <option value="{{ $barangay->barangay_id }}" {{ $selectedBarangay == $barangay->barangay_id ? 'selected' : '' }}>
                                        {{ $barangay->barangay_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="fiscal_year" class="block text-xs font-semibold text-gray-500 mb-1">
                                Fiscal Year
                            </label>

                            <select id="fiscal_year" name="fiscal_year" class="w-full sm:w-48 border border-gray-300 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
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

                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-xl text-sm font-semibold transition">
                            Apply
                        </button>

                        @if ($selectedBarangay || $selectedYear)
                            <a href="{{ route('sk_pres.dashboard') }}" class="border border-gray-300 hover:bg-gray-50 text-gray-600 px-5 py-2 rounded-xl text-sm font-semibold text-center transition">
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
                                <p class="text-sm text-gray-500">Submission Rate</p>
                                <h2 class="text-4xl font-bold text-gray-900">
                                    {{ number_format($submissionKpis['submission_rate'], 1) }}%
                                </h2>
                            </div>

                            <div class="bg-green-100 p-3 rounded-xl">
                                <span class="text-green-600 text-xl">✓</span>
                            </div>
                        </div>

                        <p class="text-sm text-gray-500">
                            {{ $submissionKpis['submitted'] }} of {{ $submissionKpis['required'] }} required submissions received
                        </p>

                        <div class="mt-4 h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full bg-green-500 rounded-full" style="width: {{ min($submissionKpis['submission_rate'], 100) }}%"></div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-sm text-gray-500">On-Time Submission Rate</p>
                                <h2 class="text-4xl font-bold text-gray-900">
                                    {{ number_format($submissionKpis['on_time_rate'], 1) }}%
                                </h2>
                            </div>

                            <div class="bg-blue-100 p-3 rounded-xl">
                                <span class="text-blue-600 text-xl">⏱</span>
                            </div>
                        </div>

                        <p class="text-sm text-gray-500">
                            {{ $submissionKpis['on_time'] }} of {{ $submissionKpis['submitted'] }} submissions received on or before deadline
                        </p>

                        <div class="mt-4 h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full bg-blue-500 rounded-full" style="width: {{ min($submissionKpis['on_time_rate'], 100) }}%"></div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-sm text-gray-500">Pending Reports</p>
                                <h2 class="text-4xl font-bold text-gray-900">
                                    {{ $submissionKpis['pending'] }}
                                </h2>
                            </div>

                            <div class="bg-yellow-100 p-3 rounded-xl">
                                <span class="text-yellow-600 text-xl">⌛</span>
                            </div>
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

    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
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
        '#ef4444',
        'percent'
    );

    makeGroupedBarChart(
        'barangaySubmissionsChart',
        chartData.barangaySubmissions.labels,
        [
            {
                label: 'Accomplishment',
                data: chartData.barangaySubmissions.accomplishment,
                backgroundColor: '#f59e0b'
            },
            {
                label: 'Budget',
                data: chartData.barangaySubmissions.budget,
                backgroundColor: '#3b82f6'
            }
        ]
    );

    makeBarChart(
        'annualBudgetChart',
        chartData.annualBudget.labels,
        chartData.annualBudget.values,
        '#3b82f6',
        'money'
    );

    makeMultiLineChart(
        'engagementMetricsChart',
        chartData.engagementMetrics.labels,
        [
            {
                label: 'Events',
                data: chartData.engagementMetrics.events,
                borderColor: '#f59e0b',
                backgroundColor: '#f59e0b'
            },
            {
                label: 'Meetings',
                data: chartData.engagementMetrics.meetings,
                borderColor: '#2563eb',
                backgroundColor: '#2563eb'
            },
            {
                label: 'Reports',
                data: chartData.engagementMetrics.reports,
                borderColor: '#ef4444',
                backgroundColor: '#ef4444'
            }
        ]
    );
</script>
@endpush