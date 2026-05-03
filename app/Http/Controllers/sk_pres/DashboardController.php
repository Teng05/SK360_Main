<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_pres/DashboardController.php.

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
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

        $userStats = DB::table('users')
            ->selectRaw('COUNT(*) as total_users')
            ->selectRaw("SUM(role IN ('sk_president','sk_chairman','sk_secretary')) as officials")
            ->selectRaw("SUM(role = 'youth') as youth")
            ->selectRaw("SUM(status = 'active') as active_users")
            ->selectRaw("SUM(role = 'sk_chairman') as chairmen")
            ->selectRaw("SUM(role = 'sk_secretary') as secretaries")
            ->selectRaw('SUM(MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())) as new_this_month')
            ->selectRaw("SUM(role = 'youth' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())) as youth_signups_this_month")
            ->first();

        $totalBarangays = DB::table('barangays')->count();

        $totalUsers = (int) ($userStats->total_users ?? 0);
        $officials = (int) ($userStats->officials ?? 0);
        $youth = (int) ($userStats->youth ?? 0);
        $activeUsers = (int) ($userStats->active_users ?? 0);
        $chairmen = (int) ($userStats->chairmen ?? 0);
        $secretaries = (int) ($userStats->secretaries ?? 0);
        $newThisMonth = (int) ($userStats->new_this_month ?? 0);
        $youthSignupsThisMonth = (int) ($userStats->youth_signups_this_month ?? 0);

        $chairmanCoverage = $totalBarangays > 0 ? round(($chairmen / $totalBarangays) * 100) : 0;
        $secretaryCoverage = $totalBarangays > 0 ? round(($secretaries / $totalBarangays) * 100) : 0;
        $remainingSecretaries = max($totalBarangays - $secretaries, 0);

        $cards = [
            [
                'label' => 'Total Users',
                'value' => $totalUsers,
                'subline1' => "{$officials} officials,",
                'subline2' => "{$youth} youth",
                'footer' => "↗ +{$newThisMonth} this month",
                'footerClass' => 'text-green-500',
                'iconWrap' => 'bg-red-100',
                'iconClass' => 'text-red-500',
                'icon' => '👥',
            ],
            [
                'label' => 'Lipa Youth',
                'value' => $youth,
                'subline1' => "{$activeUsers} active",
                'subline2' => 'members',
                'footer' => "↗ +{$youthSignupsThisMonth} new signups",
                'footerClass' => 'text-green-500',
                'iconWrap' => 'bg-yellow-100',
                'iconClass' => 'text-yellow-500',
                'icon' => '👤',
            ],
            [
                'label' => 'SK Chairmen',
                'value' => $chairmen,
                'subline1' => "Across {$totalBarangays}",
                'subline2' => 'barangays',
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
                'subline2' => 'remaining',
                'footer' => "↗ {$secretaryCoverage}% staffed",
                'footerClass' => 'text-green-500',
                'iconWrap' => 'bg-blue-100',
                'iconClass' => 'text-blue-500',
                'icon' => '📄',
            ],
        ];

        $monthLabels = collect(range(5, 0))
            ->map(fn (int $monthsAgo) => now()->subMonths($monthsAgo)->format('M'))
            ->values();

        $reportCounts = [
            'Accomplishment' => $this->tableCount('accomplishment_reports'),
            'Budget' => $this->tableCount('budget_reports'),
        ];

        $budgetTemplateChart = $this->budgetTemplateChartData();
        $barangaySubmissions = $this->barangaySubmissionChartData();
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
                'labels' => ['Youth', 'Chairmen', 'Secretaries', 'President'],
                'values' => [
                    $youth,
                    $chairmen,
                    $secretaries,
                    (int) DB::table('users')->where('role', 'sk_president')->count(),
                ],
            ],
            'barangaySubmissions' => [
                'labels' => $barangaySubmissions['labels'],
                'accomplishment' => $barangaySubmissions['accomplishment'],
                'budget' => $barangaySubmissions['budget'],
            ],
            'budgetTemplate' => [
                'labels' => $budgetTemplateChart['labels'],
                'values' => $budgetTemplateChart['values'],
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
            'chartData' => $chartData,
            'overviewDate' => now()->format('n/j/Y'),
        ]);
    }

    protected function tableCount(string $table): int
    {
        return Schema::hasTable($table) ? DB::table($table)->count() : 0;
    }

    protected function budgetTemplateChartData(): array
    {
        if (! Schema::hasTable('budget_reports') || ! Schema::hasColumn('budget_reports', 'template_data')) {
            return [
                'labels' => ['No template data'],
                'values' => [0],
            ];
        }

        $totals = [];

        DB::table('budget_reports')
            ->whereNotNull('template_data')
            ->where('template_data', '!=', '')
            ->orderByDesc('submitted_at')
            ->limit(100)
            ->get(['template_data'])
            ->each(function ($report) use (&$totals) {
                $data = json_decode((string) $report->template_data, true);

                if (! is_array($data)) {
                    return;
                }

                $headers = collect($data['object_headers'] ?? [])
                    ->map(fn ($header) => trim((string) $header))
                    ->values();

                foreach (($data['rows'] ?? []) as $row) {
                    if (! is_array($row)) {
                        continue;
                    }

                    for ($index = 1; $index <= 4; $index++) {
                        $amount = $this->moneyValue($row["object_{$index}"] ?? 0);

                        if ($amount <= 0) {
                            continue;
                        }

                        $label = $headers[$index - 1] ?? null;
                        $label = $label !== '' && $label !== null ? $label : "Budget Object {$index}";
                        $totals[$label] = ($totals[$label] ?? 0) + $amount;
                    }
                }
            });

        arsort($totals);
        $totals = array_slice($totals, 0, 8, true);

        if ($totals === []) {
            return [
                'labels' => ['No encoded budget amounts'],
                'values' => [0],
            ];
        }

        return [
            'labels' => array_keys($totals),
            'values' => array_values($totals),
        ];
    }

    protected function moneyValue(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        return (float) preg_replace('/[^0-9.-]/', '', (string) $value);
    }

    protected function barangaySubmissionChartData(): array
    {
        if (! Schema::hasTable('barangays')) {
            return [
                'labels' => ['No barangay data'],
                'accomplishment' => [0],
                'budget' => [0],
            ];
        }

        $accomplishmentCounts = $this->submissionCountsByBarangay('accomplishment_reports');
        $budgetCounts = $this->submissionCountsByBarangay('budget_reports');

        $rows = DB::table('barangays')
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

    protected function submissionCountsByBarangay(string $table): array
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'barangay_id')) {
            return [];
        }

        return DB::table($table)
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
                $total += DB::table('accomplishment_reports')->whereBetween($dateColumn, [$start, $end])->count();
            }
        }

        if (Schema::hasTable('budget_reports')) {
            $dateColumn = $this->dateColumnFor('budget_reports');

            if ($dateColumn) {
                $total += DB::table('budget_reports')->whereBetween($dateColumn, [$start, $end])->count();
            }
        }

        return $total;
    }

    protected function eventCountForMonth(Carbon $month): int
    {
        if (! Schema::hasTable('events')) {
            return 0;
        }

        $dateColumn = Schema::hasColumn('events', 'start_datetime')
            ? 'start_datetime'
            : $this->dateColumnFor('events');

        if (! $dateColumn) {
            return 0;
        }

        return DB::table('events')
            ->whereBetween($dateColumn, [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
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
                    ->whereBetween($dateColumn, [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()])
                    ->count();
            }
        }

        if (Schema::hasTable('events') && Schema::hasColumn('events', 'event_type')) {
            $dateColumn = Schema::hasColumn('events', 'start_datetime')
                ? 'start_datetime'
                : $this->dateColumnFor('events');

            if ($dateColumn) {
                $total += DB::table('events')
                    ->where('event_type', 'meeting')
                    ->whereBetween($dateColumn, [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
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

        return Schema::hasColumn($table, 'created_at') ? 'created_at' : null;
    }
}
