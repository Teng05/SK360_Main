<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_pres/ConsolidationController.php.

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Services\RankingPointsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
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
            'qualitySubmissions' => $this->qualitySubmissions($filters),
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

    public function reviewQuality(
        Request $request,
        RankingPointsService $points,
        NotificationService $notifications
    ): RedirectResponse {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $currentTermId = $this->currentTermId();
        abort_unless($currentTermId, 404);

        $validated = $request->validate([
            'source_type' => ['required', 'in:accomplishment_report,budget_report'],
            'source_id' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:approved,needs_revision'],
            'complete_contents' => ['nullable', 'boolean'],
            'correct_document' => ['nullable', 'boolean'],
            'correct_period' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'required_if:status,needs_revision', 'string', 'max:2000'],
        ]);

        $sourceType = $validated['source_type'];
        $sourceId = (int) $validated['source_id'];

        $table = $sourceType === 'accomplishment_report'
            ? 'accomplishment_reports'
            : 'budget_reports';

        $primaryKey = $sourceType === 'accomplishment_report'
            ? 'report_id'
            : 'budget_report_id';

        $submission = DB::table($table)
            ->where($primaryKey, $sourceId)
            ->where('term_id', $currentTermId)
            ->first();

        abort_unless($submission, 404);

        $completeContents = $request->boolean('complete_contents');
        $correctDocument = $request->boolean('correct_document');
        $correctPeriod = $request->boolean('correct_period');

        if (
            $validated['status'] === 'approved' &&
            (!$completeContents || !$correctDocument || !$correctPeriod)
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'quality_review' => 'Complete Contents, Correct Document, and Correct Period must be checked before approving Quality Documentation.',
                ]);
        }

        $existingReview = DB::table('submission_quality_reviews')
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();

        if (
            $existingReview &&
            $existingReview->status === 'approved' &&
            $validated['status'] === 'needs_revision'
        ) {
            return back()->withErrors([
                'quality_review' => 'This document has already been approved for Quality Documentation and cannot be changed to Needs Revision.',
            ]);
        }

        $submissionDate = $submission->submitted_at
            ?? $submission->created_at
            ?? null;

        $resubmittedAfterReview = false;

        if (
            $existingReview &&
            $existingReview->reviewed_at &&
            $submissionDate
        ) {
            $resubmittedAfterReview = Carbon::parse($submissionDate)
                ->gt(Carbon::parse($existingReview->reviewed_at));
        }

        $shouldNotifyRevision =
            $validated['status'] === 'needs_revision' &&
            (
                !$existingReview ||
                $existingReview->status !== 'needs_revision' ||
                $resubmittedAfterReview
            );

        $reviewData = [
            'barangay_id' => $submission->barangay_id,
            'reviewer_id' => auth()->user()->user_id,
            'status' => $validated['status'],
            'complete_contents' => $completeContents,
            'correct_document' => $correctDocument,
            'correct_period' => $correctPeriod,
            'readable_organized' => 0,
            'supporting_documents' => 0,
            'remarks' => $validated['remarks'] ?? null,
            'reviewed_at' => now(),
            'updated_at' => now(),
        ];

        DB::transaction(function () use (
            $existingReview,
            $reviewData,
            $sourceType,
            $sourceId,
            $validated,
            $submission,
            $points
        ) {
            if ($existingReview) {
                DB::table('submission_quality_reviews')
                    ->where('review_id', $existingReview->review_id)
                    ->update($reviewData);
            } else {
                DB::table('submission_quality_reviews')->insert([
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    ...$reviewData,
                    'created_at' => now(),
                ]);
            }

            if (
                $validated['status'] === 'approved' &&
                (!$existingReview || $existingReview->status !== 'approved')
            ) {
                $slot = !empty($submission->slot_id)
                    ? DB::table('submission_slots')
                        ->where('term_id', $submission->term_id)
                        ->where('slot_id', $submission->slot_id)
                        ->first()
                    : null;

                $action = $this->approvedRankingAction(
                    $sourceType,
                    $submission,
                    $slot
                );

                $period = $this->approvedRankingPeriod(
                    $sourceType,
                    $submission,
                    $slot
                );

                if ($action && $period) {
                    $points->award(
                        (int) $submission->barangay_id,
                        $action,
                        $sourceType,
                        $sourceId,
                        (int) auth()->user()->user_id,
                        $period
                    );
                }
            }
        });

        if ($shouldNotifyRevision) {
            $notifications->notifySubmissionNeedsRevision(
                $submission,
                $sourceType,
                auth()->user(),
                trim((string) $validated['remarks'])
            );
        }

        $message = $validated['status'] === 'approved'
            ? 'Quality Documentation approved.'
            : ($shouldNotifyRevision
                ? 'Document marked as Needs Revision. The submitter has been notified.'
                : 'Document remains marked as Needs Revision.');

        return back()->with('quality_status', $message);
    }

    protected function approvedRankingAction(
        string $sourceType,
        object $submission,
        ?object $slot
    ): ?string {
        if ($sourceType === 'accomplishment_report') {
            return match ($slot?->accomplishment_category) {
                'youth_development_program' => RankingPointsService::YOUTH_DEVELOPMENT_PROGRAM_APPROVED,
                'kk_assembly' => RankingPointsService::KK_ASSEMBLY_APPROVED,
                default => null,
            };
        }

        $budgetCategory = $submission->budget_category
            ?? $slot?->budget_category;

        if ($budgetCategory === 'annual_budget') {
            return RankingPointsService::ANNUAL_BUDGET_APPROVED;
        }

        if ($budgetCategory !== 'coa_report') {
            return null;
        }

        $periodType = $submission->budget_period_type
            ?? $slot?->budget_period_type;

        return match ($periodType) {
            'monthly' => RankingPointsService::COA_MONTHLY_APPROVED,
            'quarterly' => RankingPointsService::COA_QUARTERLY_APPROVED,
            'semi_annual' => RankingPointsService::COA_SEMI_ANNUAL_APPROVED,
            'annual' => RankingPointsService::COA_ANNUAL_APPROVED,
            default => null,
        };
    }

    protected function approvedRankingPeriod(
        string $sourceType,
        object $submission,
        ?object $slot
    ): ?string {
        if ($sourceType === 'budget_report') {
            $budgetCategory = $submission->budget_category
                ?? $slot?->budget_category;

            $periodType = $submission->budget_period_type
                ?? $slot?->budget_period_type;

            if ($budgetCategory === 'coa_report' && $periodType === 'monthly') {
                $year = (int) (
                    $submission->fiscal_year
                    ?? $slot?->fiscal_year
                    ?? 0
                );

                $month = (int) (
                    $submission->fiscal_month
                    ?? $slot?->fiscal_month
                    ?? 0
                );

                if ($year > 0 && $month >= 1 && $month <= 12) {
                    return Carbon::create($year, $month, 1)->format('F Y');
                }
            }
        }

        if ($slot && !empty($slot->end_date)) {
            return Carbon::parse($slot->end_date)->format('F Y');
        }

        $submittedAt = $submission->created_at
            ?? $submission->submitted_at
            ?? null;

        return $submittedAt
            ? Carbon::parse($submittedAt)->format('F Y')
            : null;
    }

    protected function filters(Request $request): array
    {
        $defaultYear = $this->defaultFilterYear();
        $year = (int) $request->query('year', $defaultYear);
        $period = (string) $request->query('period', 'all');
        $month = (int) $request->query('month', now()->month);
        $quarter = (string) $request->query('quarter', 'Q'.ceil(now()->month / 3));

        if (!in_array($period, ['all', 'monthly', 'quarterly', 'annual'], true)) {
            $period = 'all';
        }

        return [
            'year' => $year > 2000 && $year < 2100 ? $year : $defaultYear,
            'period' => $period,
            'month' => $month >= 1 && $month <= 12 ? $month : now()->month,
            'quarter' => in_array($quarter, ['Q1', 'Q2', 'Q3', 'Q4'], true)
                ? $quarter
                : 'Q'.ceil(now()->month / 3),
        ];
    }

    protected function submissions(array $filters): Collection
    {
        $currentTermId = $this->currentTermId();

        if (!$currentTermId) {
            return collect();
        }

        $reports = DB::table('accomplishment_reports')
            ->where('term_id', $currentTermId)
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
            ->where('term_id', $currentTermId)
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

                $monthlyBudgets = $hasBudgetPeriods
                    ? $budgetItems->where('budget_period_type', 'monthly')->count()
                    : 0;

                $quarterlyBudgets = $hasBudgetPeriods
                    ? $budgetItems->where('budget_period_type', 'quarterly')->count()
                    : 0;

                $annualBudgets = $hasBudgetPeriods
                    ? $budgetItems->where('budget_period_type', 'annual')->count()
                    : $budgetItems->count();

                $allItems = $reportItems->merge($budgetItems);
                $lastSubmission = $allItems->sortByDesc('submitted_at')->first();

                return [
                    'barangay_id' => $barangay->barangay_id,
                    'barangay' => $barangay->barangay_name,
                    'monthly_count' => $monthlyReports + $monthlyBudgets,
                    'quarterly_count' => $quarterlyReports + $quarterlyBudgets,
                    'annual_count' => $annualReports + $annualBudgets,
                    'monthly' => $this->statusLabel(
                        $monthlyReports + $monthlyBudgets,
                        $monthlyReports,
                        $monthlyBudgets
                    ),
                    'quarterly' => $this->statusLabel(
                        $quarterlyReports + $quarterlyBudgets,
                        $quarterlyReports,
                        $quarterlyBudgets
                    ),
                    'annual' => $this->statusLabel(
                        $annualReports + $annualBudgets,
                        $annualReports,
                        $annualBudgets
                    ),
                    'last_submission' => $lastSubmission?->submitted_at
                        ? date('M d, Y h:i A', strtotime((string) $lastSubmission->submitted_at))
                        : 'No submission',
                    'status' => $allItems->isNotEmpty() ? 'submitted' : 'pending',
                ];
            });
    }

    protected function qualitySubmissions(array $filters): Collection
    {
        $currentTermId = $this->currentTermId();

        if (!$currentTermId) {
            return collect();
        }

        $reportQuery = DB::table('accomplishment_reports as ar')
            ->leftJoin('barangays as b', 'ar.barangay_id', '=', 'b.barangay_id')
            ->leftJoin('submission_quality_reviews as qr', function ($join) {
                $join->on('qr.source_id', '=', 'ar.report_id')
                    ->where('qr.source_type', '=', 'accomplishment_report');
            })
            ->where('ar.term_id', $currentTermId)
            ->where('ar.reporting_year', $filters['year'])
            ->when($filters['period'] === 'monthly', fn ($query) => $query
                ->where('ar.report_type', 'monthly')
                ->where('ar.reporting_month', $filters['month']))
            ->when($filters['period'] === 'quarterly', fn ($query) => $query
                ->where('ar.report_type', 'quarterly')
                ->where('ar.reporting_quarter', $filters['quarter']))
            ->when($filters['period'] === 'annual', fn ($query) => $query
                ->where('ar.report_type', 'annual'));

        if (Schema::hasColumn('accomplishment_reports', 'status')) {
            $reportQuery->where('ar.status', '!=', 'draft');
        }

        $reports = $reportQuery->select(
            DB::raw("'accomplishment_report' as source_type"),
            'ar.report_id as source_id',
            'ar.barangay_id',
            'b.barangay_name',
            'ar.title',
            'ar.report_type as period_type',
            'ar.reporting_year as year',
            'ar.reporting_month as month',
            'ar.reporting_quarter as quarter',
            'ar.uploaded_file_path',
            'ar.generated_pdf_path',
            'ar.submitted_at',
            'ar.created_at',
            'qr.status as quality_status',
            'qr.complete_contents',
            'qr.correct_document',
            'qr.correct_period',
            'qr.remarks as quality_remarks',
            'qr.reviewed_at'
        )->get();

        $hasBudgetPeriods = Schema::hasColumn('budget_reports', 'budget_period_type');

        $budgetQuery = DB::table('budget_reports as br')
            ->leftJoin('barangays as b', 'br.barangay_id', '=', 'b.barangay_id')
            ->leftJoin('submission_quality_reviews as qr', function ($join) {
                $join->on('qr.source_id', '=', 'br.budget_report_id')
                    ->where('qr.source_type', '=', 'budget_report');
            })
            ->where('br.term_id', $currentTermId)
            ->where('br.fiscal_year', $filters['year'])
            ->when($hasBudgetPeriods && $filters['period'] === 'monthly', fn ($query) => $query
                ->where('br.budget_period_type', 'monthly')
                ->where('br.fiscal_month', $filters['month']))
            ->when($hasBudgetPeriods && $filters['period'] === 'quarterly', fn ($query) => $query
                ->where('br.budget_period_type', 'quarterly')
                ->where('br.fiscal_quarter', $filters['quarter']))
            ->when($hasBudgetPeriods && $filters['period'] === 'annual', fn ($query) => $query
                ->where('br.budget_period_type', 'annual'));

        if (Schema::hasColumn('budget_reports', 'status')) {
            $budgetQuery->where('br.status', '!=', 'draft');
        }

        $budgets = $budgetQuery->select(
            DB::raw("'budget_report' as source_type"),
            'br.budget_report_id as source_id',
            'br.barangay_id',
            'b.barangay_name',
            'br.title',
            DB::raw(($hasBudgetPeriods ? 'br.budget_period_type' : "'annual'").' as period_type'),
            'br.fiscal_year as year',
            DB::raw(($hasBudgetPeriods ? 'br.fiscal_month' : 'NULL').' as month'),
            DB::raw(($hasBudgetPeriods ? 'br.fiscal_quarter' : 'NULL').' as quarter'),
            'br.uploaded_file_path',
            'br.generated_pdf_path',
            'br.submitted_at',
            'br.created_at',
            'qr.status as quality_status',
            'qr.complete_contents',
            'qr.correct_document',
            'qr.correct_period',
            'qr.remarks as quality_remarks',
            'qr.reviewed_at'
        )->get();

        return $reports
            ->merge($budgets)
            ->sortByDesc(fn ($item) => $item->submitted_at ?? $item->created_at)
            ->values()
            ->map(function ($item) {
                $item->quality_status = $item->quality_status ?: 'pending';

                $item->source_label = $item->source_type === 'budget_report'
                    ? 'Budget Report'
                    : 'Accomplishment Report';

                $item->period_label = match ($item->period_type) {
                    'monthly' => isset($item->month)
                        ? Carbon::create((int) $item->year, (int) $item->month, 1)->format('F Y')
                        : 'Monthly '.$item->year,

                    'quarterly' => ($item->quarter ?: 'Quarterly').' '.$item->year,

                    default => 'Annual '.$item->year,
                };

                $path = $item->uploaded_file_path ?: $item->generated_pdf_path;

                $item->file_url = $path
                    ? asset(ltrim($path, '/'))
                    : null;

                $date = $item->submitted_at ?: $item->created_at;

                $item->submitted_label = $date
                    ? Carbon::parse($date)->format('M d, Y h:i A')
                    : 'Unknown';

                return $item;
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

    protected function defaultFilterYear(): int
    {
        $currentTermId = $this->currentTermId();

        if (!$currentTermId) {
            return $this->currentTermStartYear();
        }

        $reportYear = DB::table('accomplishment_reports')
            ->where('term_id', $currentTermId)
            ->max('reporting_year');

        $budgetYear = DB::table('budget_reports')
            ->where('term_id', $currentTermId)
            ->max('fiscal_year');

        $years = collect([$reportYear, $budgetYear])
            ->filter()
            ->map(fn ($year) => (int) $year)
            ->sortDesc()
            ->values();

        return $years->isNotEmpty()
            ? (int) $years->first()
            : $this->currentTermStartYear();
    }

    protected function availableYears(): array
    {
        $currentTermId = $this->currentTermId();
        $defaultYear = $this->defaultFilterYear();

        if (!$currentTermId) {
            return [$defaultYear];
        }

        $reportYears = DB::table('accomplishment_reports')
            ->where('term_id', $currentTermId)
            ->select('reporting_year')
            ->distinct()
            ->pluck('reporting_year')
            ->map(fn ($year) => (int) $year);

        $budgetYears = DB::table('budget_reports')
            ->where('term_id', $currentTermId)
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

        return $years ?: [$defaultYear];
    }

    protected function currentTermStartYear(): int
    {
        if (!Schema::hasTable('administration_terms')) {
            return now()->year;
        }

        $startYear = DB::table('administration_terms')
            ->where('status', 'current')
            ->orderByDesc('term_id')
            ->value('start_year');

        return $startYear ? (int) $startYear : now()->year;
    }

    protected function currentTermId(): ?int
    {
        if (!Schema::hasTable('administration_terms')) {
            return null;
        }

        $termId = DB::table('administration_terms')
            ->where('status', 'current')
            ->orderByDesc('term_id')
            ->value('term_id');

        return $termId ? (int) $termId : null;
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