<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_pres/DashboardController.php.

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';

        $menuItems = [
            ['link' => route('sk_pres.home'), 'icon' => '🏠', 'label' => 'Home'],
            ['link' => route('sk_pres.dashboard'), 'icon' => '📊', 'label' => 'Dashboard'],
            ['link' => route('sk_pres.consolidation'), 'icon' => '📁', 'label' => 'Consolidation'],
            ['link' => route('sk_pres.module'), 'icon' => '⚙️', 'label' => 'Module Management'],
            ['link' => route('sk_pres.announcements'), 'icon' => '📢', 'label' => 'Announcements'],
            ['link' => route('sk_pres.calendar'), 'icon' => '📅', 'label' => 'Calendar'],
            ['link' => route('sk_pres.chat'), 'icon' => '💬', 'label' => 'Chat'],
            ['link' => route('sk_pres.meetings'), 'icon' => '📞', 'label' => 'Meetings'],
            ['link' => route('sk_pres.rankings'), 'icon' => '🏆', 'label' => 'Rankings'],
            ['link' => route('sk_pres.leadership'), 'icon' => '👥', 'label' => 'Leadership'],
            ['link' => route('sk_pres.archive'), 'icon' => '🗂️', 'label' => 'Archive'],
            ['link' => route('sk_pres.user-management'), 'icon' => '👤', 'label' => 'User Management'],
        ];

        $barangays = DB::table('barangays')
            ->orderBy('barangay_name')
            ->get(['barangay_id','barangay_name']);

        $selectedBarangay = (int) $request->query('barangay_id', 0);

        if ($selectedBarangay && !$barangays->contains('barangay_id', $selectedBarangay)) {
            $selectedBarangay = 0;
        }

        $availableYears = $this->availableYears();

        $selectedYear = $request->filled('fiscal_year')
            ? (int) $request->query('fiscal_year')
            : null;

        if (
            $selectedYear &&
            (
                $selectedYear < 2000 ||
                $selectedYear > 2100 ||
                !$availableYears->contains($selectedYear)
            )
        ) {
            $selectedYear = null;
        }

        $submissionYear = $selectedYear ?: now()->year;

        $officialRoles = ['sk_president','sk_chairman','sk_secretary'];

        $userStats = DB::table('users')
            ->whereIn('role',$officialRoles)
            ->whereNull('archived_at')
            ->selectRaw('COUNT(*) as total_officials')
            ->selectRaw("SUM(status = 'active') as active_officials")
            ->selectRaw("SUM(status = 'inactive') as inactive_officials")
            ->selectRaw("SUM(role = 'sk_president') as presidents")
            ->selectRaw("SUM(role = 'sk_chairman') as chairmen")
            ->selectRaw("SUM(role = 'sk_secretary') as secretaries")
            ->selectRaw('SUM(MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())) as new_this_month')
            ->first();

        $totalBarangays = $barangays->count();

        $totalOfficials = (int) ($userStats->total_officials ?? 0);
        $activeOfficials = (int) ($userStats->active_officials ?? 0);
        $inactiveOfficials = (int) ($userStats->inactive_officials ?? 0);
        $presidents = (int) ($userStats->presidents ?? 0);
        $chairmen = (int) ($userStats->chairmen ?? 0);
        $secretaries = (int) ($userStats->secretaries ?? 0);
        $newThisMonth = (int) ($userStats->new_this_month ?? 0);

        $chairmanCoverage = $totalBarangays > 0 ? round(($chairmen / $totalBarangays) * 100) : 0;
        $secretaryCoverage = $totalBarangays > 0 ? round(($secretaries / $totalBarangays) * 100) : 0;
        $remainingChairmen = max($totalBarangays - $chairmen, 0);
        $remainingSecretaries = max($totalBarangays - $secretaries, 0);

        $cards = [
            [
                'label' => 'Total Officials',
                'value' => $totalOfficials,
                'subline1' => "{$activeOfficials} active",
                'subline2' => "{$inactiveOfficials} inactive / pending",
                'footer' => "↗ +{$newThisMonth} this month",
                'footerClass' => 'text-green-500',
                'iconWrap' => 'bg-red-100',
                'iconClass' => 'text-red-500',
                'icon' => '👥',
            ],
            [
                'label' => 'Active Accounts',
                'value' => $activeOfficials,
                'subline1' => "{$inactiveOfficials} inactive",
                'subline2' => 'or pending setup',
                'footer' => 'Official accounts only',
                'footerClass' => 'text-gray-500',
                'iconWrap' => 'bg-yellow-100',
                'iconClass' => 'text-yellow-500',
                'icon' => '✅',
            ],
            [
                'label' => 'SK Chairmen',
                'value' => $chairmen,
                'subline1' => "{$remainingChairmen}",
                'subline2' => 'remaining barangays',
                'footer' => "↗ {$chairmanCoverage}% coverage",
                'footerClass' => 'text-green-500',
                'iconWrap' => 'bg-green-100',
                'iconClass' => 'text-green-500',
                'icon' => '🛡️',
            ],
            [
                'label' => 'SK Secretaries',
                'value' => $secretaries,
                'subline1' => "{$remainingSecretaries}",
                'subline2' => 'remaining barangays',
                'footer' => "↗ {$secretaryCoverage}% staffed",
                'footerClass' => 'text-green-500',
                'iconWrap' => 'bg-blue-100',
                'iconClass' => 'text-blue-500',
                'icon' => '📄',
            ],
        ];

        $submissionKpis = $this->submissionKpis(
            $submissionYear,
            $selectedBarangay ?: null
        );

        $annualBudgetChart = $this->annualBudgetByBarangayChartData(
            $selectedYear,
            $selectedBarangay ?: null
        );

        $budgetUtilization = $this->budgetUtilizationChartData(
            $selectedYear,
            $selectedBarangay ?: null
        );

        $barangaySubmissions = $this->barangaySubmissionChartData(
            $submissionYear,
            $selectedBarangay ?: null
        );

        $monthLabels = collect(range(5, 0))
            ->map(fn (int $monthsAgo) => now()->subMonths($monthsAgo)->format('M'))
            ->values();

        $recentReportSeries = collect(range(5, 0))
            ->map(fn (int $monthsAgo) => $this->reportCountForMonth(now()->subMonths($monthsAgo)))
            ->values();

        $engagementMetrics = [
            'labels' => $monthLabels,
            'events' => collect(range(5, 0))
                ->map(fn (int $monthsAgo) => $this->eventCountForMonth(now()->subMonths($monthsAgo)))
                ->values(),
            'meetings' => collect(range(5, 0))
                ->map(fn (int $monthsAgo) => $this->meetingCountForMonth(now()->subMonths($monthsAgo)))
                ->values(),
            'reports' => $recentReportSeries,
        ];

        $chartData = [
            'roleMix' => [
                'labels' => ['President','Chairmen','Secretaries'],
                'values' => [$presidents,$chairmen,$secretaries],
            ],
            'barangaySubmissions' => [
                'labels' => $barangaySubmissions['labels'],
                'accomplishment' => $barangaySubmissions['accomplishment'],
                'budget' => $barangaySubmissions['budget'],
                'year' => $submissionYear,
            ],
            'annualBudget' => [
                'labels' => $annualBudgetChart['labels'],
                'values' => $annualBudgetChart['values'],
                'year' => $annualBudgetChart['year'],
            ],
            'budgetUtilization' => [
                'labels' => $budgetUtilization['labels'],
                'values' => $budgetUtilization['values'],
                'year' => $budgetUtilization['year'],
            ],
            'engagementMetrics' => [
                'labels' => $engagementMetrics['labels'],
                'events' => $engagementMetrics['events'],
                'meetings' => $engagementMetrics['meetings'],
                'reports' => $engagementMetrics['reports'],
            ],
        ];

        return view('sk_pres.dashboard', [
            'fullName' => $fullName,
            'menuItems' => $menuItems,
            'currentUrl' => url()->current(),
            'cards' => $cards,
            'submissionKpis' => $submissionKpis,
            'chartData' => $chartData,
            'overviewDate' => now()->format('n/j/Y'),
            'barangays' => $barangays,
            'availableYears' => $availableYears,
            'selectedBarangay' => $selectedBarangay,
            'selectedYear' => $selectedYear,
        ]);
    }

    protected function availableYears()
    {
        $years = collect();

        if (Schema::hasTable('submission_slots')) {
            if (Schema::hasColumn('submission_slots', 'fiscal_year')) {
                $years = $years->merge(
                    DB::table('submission_slots')
                        ->whereNotNull('fiscal_year')
                        ->distinct()
                        ->pluck('fiscal_year')
                );
            }

            if (Schema::hasColumn('submission_slots', 'start_date')) {
                $years = $years->merge(
                    DB::table('submission_slots')
                        ->whereNotNull('start_date')
                        ->selectRaw('YEAR(start_date) as year')
                        ->distinct()
                        ->pluck('year')
                );
            }
        }

        if (
            Schema::hasTable('accomplishment_reports') &&
            Schema::hasColumn('accomplishment_reports', 'reporting_year')
        ) {
            $years = $years->merge(
                DB::table('accomplishment_reports')
                    ->whereNotNull('reporting_year')
                    ->distinct()
                    ->pluck('reporting_year')
            );
        }

        if (
            Schema::hasTable('budget_reports') &&
            Schema::hasColumn('budget_reports', 'fiscal_year')
        ) {
            $years = $years->merge(
                DB::table('budget_reports')
                    ->whereNotNull('fiscal_year')
                    ->distinct()
                    ->pluck('fiscal_year')
            );
        }

        $years = $years
            ->map(fn ($year) => (int) $year)
            ->filter(fn ($year) => $year >= 2000 && $year <= 2100)
            ->unique()
            ->sortDesc()
            ->values();

        if ($years->isEmpty()) {
            $years->push(now()->year);
        }

        return $years;
    }

    protected function submissionKpis(int $year, ?int $barangayId = null): array
    {
        if (
            !Schema::hasTable('submission_slots') ||
            !Schema::hasTable('users')
        ) {
            return [
                'year' => $year,
                'required' => 0,
                'submitted' => 0,
                'on_time' => 0,
                'pending' => 0,
                'submission_rate' => 0,
                'on_time_rate' => 0,
            ];
        }

        $chairmanQuery = DB::table('users')
            ->where('role', 'sk_chairman')
            ->whereNotNull('barangay_id')
            ->whereNull('archived_at');

        $secretaryQuery = DB::table('users')
            ->where('role', 'sk_secretary')
            ->whereNotNull('barangay_id')
            ->whereNull('archived_at');

        if ($barangayId) {
            $chairmanQuery->where('barangay_id', $barangayId);
            $secretaryQuery->where('barangay_id', $barangayId);
        }

        $chairmanBarangays = $chairmanQuery
            ->pluck('barangay_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $secretaryBarangays = $secretaryQuery
            ->pluck('barangay_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $slotsQuery = DB::table('submission_slots')
            ->whereIn('submission_type', [
                'accomplishment_report',
                'budget_report',
            ])
            ->whereDate('start_date', '<=', now()->toDateString());

        if (Schema::hasColumn('submission_slots', 'fiscal_year')) {
            $slotsQuery->where(function ($query) use ($year) {
                $query->where('fiscal_year', $year)
                    ->orWhere(function ($query) use ($year) {
                        $query->whereNull('fiscal_year')
                            ->whereYear('start_date', $year);
                    });
            });
        } else {
            $slotsQuery->whereYear('start_date', $year);
        }

        $slots = $slotsQuery
            ->orderBy('start_date')
            ->get();

        if ($slots->isEmpty()) {
            return [
                'year' => $year,
                'required' => 0,
                'submitted' => 0,
                'on_time' => 0,
                'pending' => 0,
                'submission_rate' => 0,
                'on_time_rate' => 0,
            ];
        }

        $slotIds = $slots
            ->pluck('slot_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $submissions = collect();

        if (Schema::hasTable('accomplishment_reports')) {
            $query = DB::table('accomplishment_reports')
                ->whereIn('slot_id', $slotIds);

            if ($barangayId) {
                $query->where('barangay_id', $barangayId);
            }

            if (Schema::hasColumn('accomplishment_reports', 'status')) {
                $query->where('status', '!=', 'draft');
            }

            $submissions = $submissions->concat(
                $query->get([
                    'slot_id',
                    'barangay_id',
                    'submitted_at',
                    'created_at',
                ])->map(function ($row) {
                    $row->submission_type = 'accomplishment_report';
                    return $row;
                })
            );
        }

        if (Schema::hasTable('budget_reports')) {
            $query = DB::table('budget_reports')
                ->whereIn('slot_id', $slotIds);

            if ($barangayId) {
                $query->where('barangay_id', $barangayId);
            }

            if (Schema::hasColumn('budget_reports', 'status')) {
                $query->where('status', '!=', 'draft');
            }

            $submissions = $submissions->concat(
                $query->get([
                    'slot_id',
                    'barangay_id',
                    'submitted_at',
                    'created_at',
                ])->map(function ($row) {
                    $row->submission_type = 'budget_report';
                    return $row;
                })
            );
        }

        $submissionMap = [];

        foreach ($submissions as $submission) {
            $key = $submission->submission_type
                .':'.$submission->slot_id
                .':'.$submission->barangay_id;

            $submittedAt = $submission->submitted_at
                ?: $submission->created_at;

            if (!$submittedAt) {
                continue;
            }

            $submittedAt = Carbon::parse($submittedAt);

            if (
                !isset($submissionMap[$key]) ||
                $submittedAt->lt($submissionMap[$key])
            ) {
                $submissionMap[$key] = $submittedAt;
            }
        }

        $required = 0;
        $submitted = 0;
        $onTime = 0;

        foreach ($slots as $slot) {
            if ($slot->role === 'SK Chairman') {
                $barangayIds = $chairmanBarangays;
            } elseif ($slot->role === 'SK Secretary') {
                $barangayIds = $secretaryBarangays;
            } else {
                $barangayIds = $chairmanBarangays
                    ->merge($secretaryBarangays)
                    ->unique()
                    ->values();
            }

            foreach ($barangayIds as $barangayIdValue) {
                $required++;

                $key = $slot->submission_type
                    .':'.$slot->slot_id
                    .':'.$barangayIdValue;

                if (!isset($submissionMap[$key])) {
                    continue;
                }

                $submitted++;

                if (
                    $submissionMap[$key]->lessThanOrEqualTo(
                        Carbon::parse($slot->end_date)->endOfDay()
                    )
                ) {
                    $onTime++;
                }
            }
        }

        $pending = max($required - $submitted, 0);

        return [
            'year' => $year,
            'required' => $required,
            'submitted' => $submitted,
            'on_time' => $onTime,
            'pending' => $pending,
            'submission_rate' => $required > 0
                ? round(($submitted / $required) * 100, 1)
                : 0,
            'on_time_rate' => $submitted > 0
                ? round(($onTime / $submitted) * 100, 1)
                : 0,
        ];
    }

    protected function annualBudgetByBarangayChartData(
        ?int $year = null,
        ?int $barangayId = null
    ): array {
        if (
            !Schema::hasTable('budget_reports') ||
            !Schema::hasTable('barangays') ||
            !Schema::hasColumn('budget_reports', 'budget_category') ||
            !Schema::hasColumn('budget_reports', 'total_amount')
        ) {
            return [
                'labels' => ['No annual budget data'],
                'values' => [0],
                'year' => $year ?: now()->year,
            ];
        }

        if (!$year) {
            $year = DB::table('budget_reports')
                ->where('budget_category', 'annual_budget')
                ->whereIn('status', ['submitted', 'recorded', 'archived'])
                ->whereNotNull('fiscal_year')
                ->max('fiscal_year');

            $year = $year ?: now()->year;
        }

        $latestIdsQuery = DB::table('budget_reports')
            ->selectRaw('MAX(budget_report_id) as budget_report_id')
            ->where('budget_category', 'annual_budget')
            ->where('fiscal_year', $year)
            ->whereIn('status', ['submitted', 'recorded', 'archived']);

        if ($barangayId) {
            $latestIdsQuery->where('barangay_id', $barangayId);
        }

        $latestIds = $latestIdsQuery
            ->groupBy('barangay_id')
            ->pluck('budget_report_id');

        if ($latestIds->isEmpty()) {
            return [
                'labels' => ['No annual budget data'],
                'values' => [0],
                'year' => $year,
            ];
        }

        $rows = DB::table('budget_reports as br')
            ->join('barangays as b', 'b.barangay_id', '=', 'br.barangay_id')
            ->whereIn('br.budget_report_id', $latestIds)
            ->where('br.total_amount', '>', 0)
            ->orderBy('b.barangay_name')
            ->get([
                'b.barangay_name',
                'br.total_amount',
            ]);

        if ($rows->isEmpty()) {
            return [
                'labels' => ['No annual budget data'],
                'values' => [0],
                'year' => $year,
            ];
        }

        return [
            'labels' => $rows->pluck('barangay_name')->all(),
            'values' => $rows
                ->pluck('total_amount')
                ->map(fn ($amount) => (float) $amount)
                ->all(),
            'year' => $year,
        ];
    }

    protected function budgetUtilizationChartData(
        ?int $year = null,
        ?int $barangayId = null
    ): array {
        if (
            !Schema::hasTable('budget_reports') ||
            !Schema::hasTable('barangays') ||
            !Schema::hasColumn('budget_reports', 'budget_category') ||
            !Schema::hasColumn('budget_reports', 'actual_expenditure')
        ) {
            return [
                'labels' => ['No utilization data'],
                'values' => [0],
                'year' => $year ?: now()->year,
            ];
        }

        if (!$year) {
            $year = DB::table('budget_reports')
                ->where('budget_category', 'annual_budget')
                ->whereIn('status', ['submitted', 'recorded', 'archived'])
                ->whereNotNull('fiscal_year')
                ->max('fiscal_year');

            $year = $year ?: now()->year;
        }

        $annualBudgetQuery = DB::table('budget_reports')
            ->where('budget_category', 'annual_budget')
            ->where('fiscal_year', $year)
            ->whereIn('status', ['submitted', 'recorded', 'archived'])
            ->where('total_amount', '>', 0);

        $annualCoaQuery = DB::table('budget_reports')
            ->where('budget_category', 'coa_report')
            ->where('budget_period_type', 'annual')
            ->where('fiscal_year', $year)
            ->whereIn('status', ['submitted', 'recorded', 'archived'])
            ->whereNotNull('actual_expenditure');

        if ($barangayId) {
            $annualBudgetQuery->where('barangay_id', $barangayId);
            $annualCoaQuery->where('barangay_id', $barangayId);
        }

        $annualBudgets = $annualBudgetQuery
            ->orderByDesc('budget_report_id')
            ->get()
            ->unique('barangay_id')
            ->keyBy('barangay_id');

        $annualCoa = $annualCoaQuery
            ->orderByDesc('budget_report_id')
            ->get()
            ->unique('barangay_id')
            ->keyBy('barangay_id');

        $barangayQuery = DB::table('barangays')
            ->orderBy('barangay_name');

        if ($barangayId) {
            $barangayQuery->where('barangay_id', $barangayId);
        }

        $rows = $barangayQuery
            ->get(['barangay_id', 'barangay_name'])
            ->map(function ($barangay) use ($annualBudgets, $annualCoa) {
                $budget = $annualBudgets->get($barangay->barangay_id);
                $coa = $annualCoa->get($barangay->barangay_id);

                if (!$budget || !$coa) {
                    return null;
                }

                $annualBudget = (float) $budget->total_amount;
                $actualExpenditure = (float) $coa->actual_expenditure;

                if ($annualBudget <= 0) {
                    return null;
                }

                return [
                    'name' => $barangay->barangay_name,
                    'utilization' => round(
                        ($actualExpenditure / $annualBudget) * 100,
                        2
                    ),
                ];
            })
            ->filter()
            ->values();

        if ($rows->isEmpty()) {
            return [
                'labels' => ['No utilization data'],
                'values' => [0],
                'year' => $year,
            ];
        }

        return [
            'labels' => $rows->pluck('name')->all(),
            'values' => $rows->pluck('utilization')->all(),
            'year' => $year,
        ];
    }

    protected function barangaySubmissionChartData(
        int $year,
        ?int $barangayId = null
    ): array {
        if (!Schema::hasTable('barangays')) {
            return [
                'labels' => ['No barangay data'],
                'accomplishment' => [0],
                'budget' => [0],
            ];
        }

        $accomplishmentCounts = $this->submissionCountsByBarangay(
            'accomplishment_reports',
            $year,
            $barangayId
        );

        $budgetCounts = $this->submissionCountsByBarangay(
            'budget_reports',
            $year,
            $barangayId
        );

        $query = DB::table('barangays');

        if ($barangayId) {
            $query->where('barangay_id', $barangayId);
        }

        $rows = $query
            ->get(['barangay_id', 'barangay_name'])
            ->map(function ($barangay) use ($accomplishmentCounts, $budgetCounts) {
                $accomplishment = (int) ($accomplishmentCounts[$barangay->barangay_id] ?? 0);
                $budget = (int) ($budgetCounts[$barangay->barangay_id] ?? 0);

                return [
                    'name' => $barangay->barangay_name,
                    'accomplishment' => $accomplishment,
                    'budget' => $budget,
                    'total' => $accomplishment + $budget,
                ];
            })
            ->sortByDesc('total')
            ->take(8)
            ->values();

        if ($rows->isEmpty() || $rows->sum('total') === 0) {
            return [
                'labels' => ['No submissions yet'],
                'accomplishment' => [0],
                'budget' => [0],
            ];
        }

        return [
            'labels' => $rows->pluck('name')->all(),
            'accomplishment' => $rows->pluck('accomplishment')->all(),
            'budget' => $rows->pluck('budget')->all(),
        ];
    }

    protected function submissionCountsByBarangay(
        string $table,
        int $year,
        ?int $barangayId = null
    ): array {
        if (
            !Schema::hasTable($table) ||
            !Schema::hasColumn($table, 'barangay_id')
        ) {
            return [];
        }

        $query = DB::table($table);

        if ($barangayId) {
            $query->where('barangay_id', $barangayId);
        }

        if (Schema::hasColumn($table, 'status')) {
            $query->where('status', '!=', 'draft');
        }

        if (
            $table === 'accomplishment_reports' &&
            Schema::hasColumn($table, 'reporting_year')
        ) {
            $query->where('reporting_year', $year);
        } elseif (
            $table === 'budget_reports' &&
            Schema::hasColumn($table, 'fiscal_year')
        ) {
            $query->where('fiscal_year', $year);
        } else {
            $dateColumn = $this->dateColumnFor($table);

            if ($dateColumn) {
                $query->whereYear($dateColumn, $year);
            }
        }

        return $query
            ->select('barangay_id', DB::raw('COUNT(*) as total'))
            ->groupBy('barangay_id')
            ->pluck('total', 'barangay_id')
            ->all();
    }

    protected function reportCountForMonth(Carbon $month): int
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $total = 0;

        if (Schema::hasTable('accomplishment_reports')) {
            $dateColumn = $this->dateColumnFor('accomplishment_reports');

            if ($dateColumn) {
                $total += DB::table('accomplishment_reports')
                    ->whereBetween($dateColumn, [$start, $end])
                    ->count();
            }
        }

        if (Schema::hasTable('budget_reports')) {
            $dateColumn = $this->dateColumnFor('budget_reports');

            if ($dateColumn) {
                $total += DB::table('budget_reports')
                    ->whereBetween($dateColumn, [$start, $end])
                    ->count();
            }
        }

        return $total;
    }

    protected function eventCountForMonth(Carbon $month): int
    {
        if (!Schema::hasTable('events')) {
            return 0;
        }

        $dateColumn = Schema::hasColumn('events', 'start_datetime')
            ? 'start_datetime'
            : $this->dateColumnFor('events');

        if (!$dateColumn) {
            return 0;
        }

        return DB::table('events')
            ->whereBetween(
                $dateColumn,
                [
                    $month->copy()->startOfMonth(),
                    $month->copy()->endOfMonth(),
                ]
            )
            ->where(function ($query) {
                if (Schema::hasColumn('events', 'event_type')) {
                    $query->where('event_type', '!=', 'meeting');
                }
            })
            ->count();
    }

    protected function meetingCountForMonth(Carbon $month): int
    {
        $total = 0;

        if (Schema::hasTable('meetings')) {
            $dateColumn = Schema::hasColumn('meetings', 'meeting_date')
                ? 'meeting_date'
                : $this->dateColumnFor('meetings');

            if ($dateColumn) {
                $total += DB::table('meetings')
                    ->whereBetween(
                        $dateColumn,
                        [
                            $month->copy()->startOfMonth()->toDateString(),
                            $month->copy()->endOfMonth()->toDateString(),
                        ]
                    )
                    ->count();
            }
        }

        if (
            Schema::hasTable('events') &&
            Schema::hasColumn('events', 'event_type')
        ) {
            $dateColumn = Schema::hasColumn('events', 'start_datetime')
                ? 'start_datetime'
                : $this->dateColumnFor('events');

            if ($dateColumn) {
                $total += DB::table('events')
                    ->where('event_type', 'meeting')
                    ->whereBetween(
                        $dateColumn,
                        [
                            $month->copy()->startOfMonth(),
                            $month->copy()->endOfMonth(),
                        ]
                    )
                    ->count();
            }
        }

        return $total;
    }

    protected function dateColumnFor(string $table): ?string
    {
        if (Schema::hasColumn($table, 'submitted_at')) {
            return 'submitted_at';
        }

        return Schema::hasColumn($table, 'created_at')
            ? 'created_at'
            : null;
    }
}