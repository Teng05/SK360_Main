<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_pres/ConsolidationController.php.

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ConsolidationController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';
        $filters = $this->filters($request);
        $submissions = $this->submissions($filters);
        $stats = $this->stats($submissions);

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

        return view('sk_pres.consolidation', [
            'fullName' => $fullName,
            'menuItems' => $menuItems,
            'stats' => $stats,
            'submissions' => $submissions,
            'filters' => $filters,
            'years' => $this->availableYears(),
            'months' => $this->months(),
            'quarters' => ['Q1', 'Q2', 'Q3', 'Q4'],
            'downloadRoute' => route('sk_pres.consolidation.download', $filters),
            'currentUrl' => url()->current(),
        ]);
    }

    public function download(Request $request): Response
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $filters = $this->filters($request);
        $submissions = $this->submissions($filters);

        $pdf = Pdf::loadView('sk_pres.consolidation-download', [
            'filters' => $filters,
            'submissions' => $submissions,
            'stats' => $this->stats($submissions),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('consolidated-reports-'.$filters['year'].'-'.$filters['period'].'.pdf');
    }

    protected function filters(Request $request): array
    {
        $year = (int) $request->query('year', now()->year);
        $period = (string) $request->query('period', 'all');
        $month = (int) $request->query('month', now()->month);
        $quarter = (string) $request->query('quarter', 'Q'.ceil(now()->month / 3));

        if (! in_array($period, ['all', 'monthly', 'quarterly', 'annual'], true)) {
            $period = 'all';
        }

        return [
            'year' => $year > 2000 && $year < 2100 ? $year : now()->year,
            'period' => $period,
            'month' => $month >= 1 && $month <= 12 ? $month : now()->month,
            'quarter' => in_array($quarter, ['Q1', 'Q2', 'Q3', 'Q4'], true) ? $quarter : 'Q'.ceil(now()->month / 3),
        ];
    }

    protected function submissions(array $filters): Collection
    {
        $reports = DB::table('accomplishment_reports')
            ->where('reporting_year', $filters['year'])
            ->when($filters['period'] === 'monthly', fn ($query) => $query
                ->where('report_type', 'monthly')
                ->where('reporting_month', $filters['month']))
            ->when($filters['period'] === 'quarterly', fn ($query) => $query
                ->where('report_type', 'quarterly')
                ->where('reporting_quarter', $filters['quarter']))
            ->when($filters['period'] === 'annual', fn ($query) => $query
                ->where('report_type', 'annual'))
            ->get()
            ->groupBy('barangay_id');

        $hasBudgetPeriods = Schema::hasColumn('budget_reports', 'budget_period_type');
        $budgets = DB::table('budget_reports')
            ->where('fiscal_year', $filters['year'])
            ->when($hasBudgetPeriods && $filters['period'] === 'monthly', fn ($query) => $query
                ->where('budget_period_type', 'monthly')
                ->where('fiscal_month', $filters['month']))
            ->when($hasBudgetPeriods && $filters['period'] === 'quarterly', fn ($query) => $query
                ->where('budget_period_type', 'quarterly')
                ->where('fiscal_quarter', $filters['quarter']))
            ->when($hasBudgetPeriods && $filters['period'] === 'annual', fn ($query) => $query
                ->where('budget_period_type', 'annual'))
            ->get()
            ->groupBy('barangay_id');

        return DB::table('barangays')
            ->orderBy('barangay_name')
            ->get(['barangay_id', 'barangay_name'])
            ->map(function ($barangay) use ($reports, $budgets, $hasBudgetPeriods) {
                $reportItems = $reports->get($barangay->barangay_id, collect());
                $budgetItems = $budgets->get($barangay->barangay_id, collect());

                $monthlyReports = $reportItems->where('report_type', 'monthly')->count();
                $quarterlyReports = $reportItems->where('report_type', 'quarterly')->count();
                $annualReports = $reportItems->where('report_type', 'annual')->count();

                $monthlyBudgets = $hasBudgetPeriods ? $budgetItems->where('budget_period_type', 'monthly')->count() : 0;
                $quarterlyBudgets = $hasBudgetPeriods ? $budgetItems->where('budget_period_type', 'quarterly')->count() : 0;
                $annualBudgets = $hasBudgetPeriods ? $budgetItems->where('budget_period_type', 'annual')->count() : $budgetItems->count();

                $allItems = $reportItems->merge($budgetItems);
                $lastSubmission = $allItems->sortByDesc('submitted_at')->first();

                return [
                    'barangay_id' => $barangay->barangay_id,
                    'barangay' => $barangay->barangay_name,
                    'monthly_count' => $monthlyReports + $monthlyBudgets,
                    'quarterly_count' => $quarterlyReports + $quarterlyBudgets,
                    'annual_count' => $annualReports + $annualBudgets,
                    'monthly' => $this->statusLabel($monthlyReports + $monthlyBudgets, $monthlyReports, $monthlyBudgets),
                    'quarterly' => $this->statusLabel($quarterlyReports + $quarterlyBudgets, $quarterlyReports, $quarterlyBudgets),
                    'annual' => $this->statusLabel($annualReports + $annualBudgets, $annualReports, $annualBudgets),
                    'last_submission' => $lastSubmission?->submitted_at
                        ? date('M d, Y h:i A', strtotime((string) $lastSubmission->submitted_at))
                        : 'No submission',
                    'status' => $allItems->isNotEmpty() ? 'submitted' : 'pending',
                ];
            });
    }

    protected function stats(Collection $submissions): array
    {
        $total = $submissions->count();
        $submitted = $submissions->where('status', 'submitted')->count();
        $late = 0;

        return [
            ['label' => 'Total Barangays', 'value' => $total, 'valueClass' => 'text-gray-800'],
            ['label' => 'Submitted', 'value' => $submitted, 'valueClass' => 'text-green-500'],
            ['label' => 'Pending', 'value' => max($total - $submitted - $late, 0), 'valueClass' => 'text-yellow-500'],
            ['label' => 'Late', 'value' => $late, 'valueClass' => 'text-red-500'],
        ];
    }

    protected function statusLabel(int $count, int $reportCount = 0, int $budgetCount = 0): string
    {
        if ($count <= 0) {
            return 'Pending';
        }

        return $count.' submitted (R: '.$reportCount.', B: '.$budgetCount.')';
    }

    protected function availableYears(): array
    {
        $reportYears = DB::table('accomplishment_reports')
            ->select('reporting_year')
            ->distinct()
            ->pluck('reporting_year')
            ->map(fn ($year) => (int) $year);

        $budgetYears = DB::table('budget_reports')
            ->select('fiscal_year')
            ->distinct()
            ->pluck('fiscal_year')
            ->map(fn ($year) => (int) $year);

        $years = $reportYears
            ->merge($budgetYears)
            ->filter()
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        return $years ?: [now()->year];
    }

    protected function months(): array
    {
        return [
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
    }
}
