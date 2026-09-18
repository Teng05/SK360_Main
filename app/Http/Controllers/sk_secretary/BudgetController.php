<?php

namespace App\Http\Controllers\sk_secretary;

use App\Http\Controllers\Controller;
use App\Services\RankingPointsService;
use App\Services\SubmissionSlotService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_secretary', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';
        $barangayName = $user->barangay->barangay_name ?? 'Barangay';

        $slots = app(SubmissionSlotService::class)
            ->secretaryBudgetSlots((int) $user->barangay_id);

        $submissions = DB::table('budget_reports')
            ->leftJoin(
                'submission_slots',
                'submission_slots.slot_id',
                '=',
                'budget_reports.slot_id'
            )
            ->where('budget_reports.barangay_id', $user->barangay_id)
            ->select([
                'budget_reports.*',
                'submission_slots.budget_category as slot_budget_category',
                'submission_slots.fiscal_year as slot_fiscal_year',
                'submission_slots.budget_period_type as slot_budget_period_type',
                'submission_slots.fiscal_month as slot_fiscal_month',
                'submission_slots.fiscal_quarter as slot_fiscal_quarter',
                'submission_slots.fiscal_half as slot_fiscal_half',
            ])
            ->orderByDesc('budget_reports.submitted_at')
            ->orderByDesc('budget_reports.created_at')
            ->get()
            ->map(function ($submission) {
                $status = strtolower((string) ($submission->status ?? 'submitted'));

                $method = strtolower((string) $submission->submission_method)
                    === 'file_upload'
                        ? 'pdf'
                        : 'template';

                $submission->submitted_at = $submission->submitted_at
                    ? Carbon::parse($submission->submitted_at)
                    : Carbon::parse($submission->created_at);

                $submission->status_badge = match ($status) {
                    'archived', 'recorded' => 'bg-green-100 text-green-600',
                    'draft' => 'bg-gray-100 text-gray-600',
                    default => 'bg-yellow-100 text-yellow-600',
                };

                $submission->method_badge = $method === 'pdf'
                    ? 'bg-purple-100 text-purple-600'
                    : 'bg-blue-100 text-blue-600';

                $submission->method_label = $method === 'pdf'
                    ? 'PDF Upload'
                    : 'Template';

                $submission->period_label = $this->periodLabel($submission);

                $submission->download_url =
                    $method === 'pdf' && !empty($submission->uploaded_file_path)
                        ? asset($submission->uploaded_file_path)
                        : route(
                            'sk_secretary.budget.template.download',
                            $submission->budget_report_id
                        );

                $submission->view_url =
                    $method === 'pdf' && !empty($submission->uploaded_file_path)
                        ? asset($submission->uploaded_file_path)
                        : route(
                            'sk_secretary.budget.template.view',
                            $submission->budget_report_id
                        );

                return $submission;
            });

        return view('sk_secretary.budget', [
            'fullName' => $fullName,
            'barangayName' => $barangayName,
            'initials' => strtoupper(
                substr($user->first_name ?? 'S', 0, 1).
                substr($user->last_name ?? 'K', 0, 1)
            ),
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
            'pageTitle' => 'Budget Submissions',
            'pageDescription' => 'Submit budget and financial documents only through active slots created by the SK President.',
            'slotSectionTitle' => 'Active Budget Slots',
            'slotEmptyMessage' => 'No active budget submission slots right now.',
            'slotActionLabel' => 'Submit Budget File',
            'roleLabel' => 'SK Secretary',
            'slots' => $slots,
            'submissions' => $submissions,
            'submissionType' => 'budget',
            'storeRoute' => route('sk_secretary.budget.store'),
            'profileRoute' => route('sk_secretary.profile'),
            'allowResubmission' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_secretary', 403);

        $validated = $request->validate([
            'slot_id' => ['required', 'integer'],
            'sub_method' => ['required', 'in:template,pdf'],
            'annual_budget_amount' => ['nullable', 'numeric', 'min:0.01'],
            'actual_expenditure' => ['nullable', 'numeric', 'min:0.01'],
            'report_file' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $slot = app(SubmissionSlotService::class)
            ->resolveOpenSlot(
                (int) $validated['slot_id'],
                'budget_report',
                ['SK Secretary', 'Both']
            );

        if (!$slot) {
            return back()->with(
                'report_error',
                'That budget submission slot is no longer available.'
            );
        }

        $isAnnualBudget =
            ($slot->budget_category ?? null) === 'annual_budget';

        $isAnnualCoa =
            ($slot->budget_category ?? null) === 'coa_report' &&
            ($slot->budget_period_type ?? null) === 'annual';

        if (
            $isAnnualBudget &&
            !$request->filled('annual_budget_amount')
        ) {
            return back()
                ->withErrors([
                    'annual_budget_amount' =>
                        'Please enter the Annual Budget amount.',
                ])
                ->withInput();
        }

        if (
            $isAnnualCoa &&
            !$request->filled('actual_expenditure')
        ) {
            return back()
                ->withErrors([
                    'actual_expenditure' =>
                        'Please enter the Actual Expenditure.',
                ])
                ->withInput();
        }

        if ($validated['sub_method'] === 'template') {
            if (!$this->templateAvailableForSlot($slot)) {
                return back()->with(
                    'report_error',
                    'The SK360 system template is not available for this submission type. Please upload a PDF.'
                );
            }

            $params = [
                'slot_id' => $slot->slot_id,
            ];

            if ($isAnnualCoa) {
                $params['actual_expenditure'] =
                    $validated['actual_expenditure'];
            }

            return redirect()->route(
                'sk_secretary.budget.template.create',
                $params
            );
        }

        if (!$request->hasFile('report_file')) {
            return back()
                ->withErrors([
                    'report_file' => 'A PDF file is required for PDF Upload.'
                ])
                ->withInput();
        }

        $directory = public_path('uploads/budget_reports');
        File::ensureDirectoryExists($directory);

        $filename = 'BUD_'
            .time()
            .'_'
            .auth()->user()->barangay_id
            .'.pdf';

        $request->file('report_file')->move(
            $directory,
            $filename
        );

        $data = [
            'user_id' => auth()->user()->user_id,
            'barangay_id' => auth()->user()->barangay_id,
            'slot_id' => $slot->slot_id,
            'submission_method' => 'file_upload',
            'document_type' => 'financial_record',
            'fiscal_year' => $slot->fiscal_year ?? now()->year,
            'title' => $slot->title,
            'generated_pdf_path' => null,
            'uploaded_file_name' => $request
                ->file('report_file')
                ->getClientOriginalName(),
            'uploaded_file_path' =>
                'uploads/budget_reports/'.$filename,

            'total_amount' => $isAnnualBudget
                ? (float) $validated['annual_budget_amount']
                : 0,

            'actual_expenditure' => $isAnnualCoa
                ? (float) $validated['actual_expenditure']
                : null,

            'status' => 'recorded',
            'submitted_at' => now(),
            'created_at' => now(),
        ];

        $data = $this->applySlotMetadata(
            $data,
            $slot
        );

        $budgetReportId = $this->saveBudgetSubmission(
            $data,
            (int) $slot->slot_id
        );

        $this->scoreSubmission(
            $slot,
            $budgetReportId,
            'budget_report'
        );

        return redirect()
            ->route('sk_secretary.budget')
            ->with(
                'report_success',
                $isAnnualBudget
                    ? 'Annual Budget submitted successfully.'
                    : ($isAnnualCoa
                        ? 'Annual COA Report submitted successfully.'
                        : 'Budget document submitted successfully.')
            );
    }

    public function createTemplate(Request $request): View|RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_secretary', 403);

        $slotId = (int) $request->query('slot_id');

        $slot = app(SubmissionSlotService::class)
            ->resolveOpenSlot(
                $slotId,
                'budget_report',
                ['SK Secretary', 'Both']
            );

        if (!$slot) {
            return redirect()
                ->route('sk_secretary.budget')
                ->with(
                    'report_error',
                    'That budget submission slot is no longer available.'
                );
        }

        if (!$this->templateAvailableForSlot($slot)) {
            return redirect()
                ->route('sk_secretary.budget')
                ->with(
                    'report_error',
                    'The SK360 system template is not available for this submission type. Please upload a PDF.'
                );
        }

        $isAnnualCoa =
            ($slot->budget_category ?? null) === 'coa_report' &&
            ($slot->budget_period_type ?? null) === 'annual';

        $actualExpenditure = $isAnnualCoa
            ? $request->query('actual_expenditure')
            : null;

        if (
            $isAnnualCoa &&
            (!is_numeric($actualExpenditure) || (float) $actualExpenditure <= 0)
        ) {
            return redirect()
                ->route('sk_secretary.budget')
                ->with(
                    'report_error',
                    'Please enter a valid Actual Expenditure.'
                );
        }

        $user = auth()->user();

        return view('shared.budget-template-form', [
            'slot' => $slot,
            'submitRoute' => route('sk_secretary.budget.template.store'),
            'backRoute' => route('sk_secretary.budget'),
            'fullName' => trim(
                ($user->first_name ?? '').' '.($user->last_name ?? '')
            ) ?: 'User',
            'barangayName' => $user->barangay->barangay_name ?? 'Barangay',
            'reportType' => $slot->budget_period_type,
            'reportingYear' => (int) $slot->fiscal_year,
            'reportingMonth' => (int) ($slot->fiscal_month ?: now()->month),
            'reportingQuarter' => $slot->fiscal_quarter ?: 'Q1',
            'reportingHalf' => $slot->fiscal_half,
            'actualExpenditure' => $actualExpenditure,
        ]);
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_secretary', 403);

        $validated = $request->validate([
            'slot_id' => ['required', 'integer'],
            'actual_expenditure' => ['nullable', 'numeric', 'min:0.01'],

            'monitoring_officer' => ['nullable', 'string', 'max:255'],
            'sheet_no' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'program_project_activity' => ['nullable', 'string', 'max:255'],

            'object_headers' => ['nullable', 'array'],
            'object_headers.*' => ['nullable', 'string', 'max:255'],

            'rows' => ['nullable', 'array'],
            'rows.*.particulars' => ['nullable', 'string', 'max:255'],
            'rows.*.date' => ['nullable', 'date'],
            'rows.*.reference' => ['nullable', 'string', 'max:255'],
            'rows.*.total_amount' => ['nullable', 'numeric'],
            'rows.*.object_1' => ['nullable', 'numeric'],
            'rows.*.object_2' => ['nullable', 'numeric'],
            'rows.*.object_3' => ['nullable', 'numeric'],
            'rows.*.object_4' => ['nullable', 'numeric'],

            'spf_carried_forward' => ['nullable', 'numeric'],
            'commitments_carried_forward' => ['nullable', 'numeric'],
            'payments_carried_forward' => ['nullable', 'numeric'],
            'available_balance' => ['nullable', 'numeric'],
            'unpaid_commitments' => ['nullable', 'numeric'],
            'certified_date' => ['nullable', 'date'],

            'bank_name' => ['nullable', 'string', 'max:255'],
            'branch' => ['nullable', 'string', 'max:255'],
            'current_account_no' => ['nullable', 'string', 'max:255'],
            'prepared_by' => ['nullable', 'string', 'max:255'],
            'approved_by' => ['nullable', 'string', 'max:255'],

            'bank_rows' => ['nullable', 'array'],
            'bank_rows.*.particulars' => ['nullable', 'string', 'max:255'],
            'bank_rows.*.rcb' => ['nullable', 'string', 'max:255'],
            'bank_rows.*.bank' => ['nullable', 'string', 'max:255'],
            'bank_rows.*.comment' => ['nullable', 'string', 'max:500'],

            'accountable_officer' => ['nullable', 'string', 'max:255'],
            'official_designation' => ['nullable', 'string', 'max:255'],
            'assumption_date' => ['nullable', 'date'],

            'inventory_rows' => ['nullable', 'array'],
            'inventory_rows.*.article' => ['nullable', 'string', 'max:255'],
            'inventory_rows.*.description' => ['nullable', 'string', 'max:255'],
            'inventory_rows.*.property_no' => ['nullable', 'string', 'max:255'],
            'inventory_rows.*.unit' => ['nullable', 'string', 'max:255'],
            'inventory_rows.*.unit_cost' => ['nullable', 'numeric'],
            'inventory_rows.*.balance' => ['nullable', 'numeric'],
            'inventory_rows.*.on_hand' => ['nullable', 'numeric'],
            'inventory_rows.*.shortage_quantity' => ['nullable', 'numeric'],
            'inventory_rows.*.shortage_value' => ['nullable', 'numeric'],
            'inventory_rows.*.remarks' => ['nullable', 'string', 'max:255'],

            'committee_members' => ['nullable', 'array'],
            'committee_members.*' => ['nullable', 'string', 'max:255'],
            'chairperson_name' => ['nullable', 'string', 'max:255'],
        ]);

        $slot = app(SubmissionSlotService::class)
            ->resolveOpenSlot(
                (int) $validated['slot_id'],
                'budget_report',
                ['SK Secretary', 'Both']
            );

        if (!$slot) {
            return redirect()
                ->route('sk_secretary.budget')
                ->with(
                    'report_error',
                    'That budget submission slot is no longer available.'
                );
        }

        if (!$this->templateAvailableForSlot($slot)) {
            return redirect()
                ->route('sk_secretary.budget')
                ->with(
                    'report_error',
                    'The SK360 system template is not available for this submission type.'
                );
        }

        $isAnnualCoa =
            ($slot->budget_category ?? null) === 'coa_report' &&
            ($slot->budget_period_type ?? null) === 'annual';

        if (
            $isAnnualCoa &&
            empty($validated['actual_expenditure'])
        ) {
            return redirect()
                ->route('sk_secretary.budget')
                ->with(
                    'report_error',
                    'Actual Expenditure is required for Annual COA.'
                );
        }

        $templateData = array_merge($validated, [
            'report_type' => $slot->budget_period_type,
            'reporting_year' => $slot->fiscal_year,
            'reporting_month' => $slot->fiscal_month,
            'reporting_quarter' => $slot->fiscal_quarter,
            'reporting_half' => $slot->fiscal_half,
            'budget_category' => $slot->budget_category,
        ]);

        $data = [
            'user_id' => auth()->user()->user_id,
            'barangay_id' => auth()->user()->barangay_id,
            'slot_id' => $slot->slot_id,
            'submission_method' => 'direct_input',
            'document_type' => 'financial_record',
            'fiscal_year' => $slot->fiscal_year ?? now()->year,
            'title' => $slot->title,
            'generated_pdf_path' => 'TEMPLATE_GEN',
            'template_data' => json_encode(
                $templateData,
                JSON_UNESCAPED_UNICODE
            ),
            'uploaded_file_name' => null,
            'uploaded_file_path' => null,

            'total_amount' =>
                collect($validated['rows'] ?? [])
                    ->sum(
                        fn ($row) =>
                            (float) ($row['total_amount'] ?? 0)
                    )
                +
                collect($validated['inventory_rows'] ?? [])
                    ->sum(
                        fn ($row) =>
                            (float) ($row['shortage_value'] ?? 0)
                    ),

            'actual_expenditure' => $isAnnualCoa
                ? (float) $validated['actual_expenditure']
                : null,

            'status' => 'recorded',
            'submitted_at' => now(),
            'created_at' => now(),
        ];

        $data = $this->applySlotMetadata($data, $slot);

        $budgetReportId = $this->saveBudgetSubmission(
            $data,
            (int) $slot->slot_id
        );

        $this->scoreSubmission(
            $slot,
            $budgetReportId,
            'budget_report'
        );

        return redirect()
            ->route('sk_secretary.budget')
            ->with(
                'report_success',
                $isAnnualCoa
                    ? 'Annual COA template submitted successfully.'
                    : 'Budget template submitted successfully.'
            );
    }

    public function downloadTemplate(int $budgetReportId): Response|RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_secretary', 403);

        $submission = DB::table('budget_reports')
            ->where('budget_report_id', $budgetReportId)
            ->where('barangay_id', auth()->user()->barangay_id)
            ->first();

        if (!$submission || empty($submission->template_data)) {
            return redirect()
                ->route('sk_secretary.budget')
                ->with(
                    'report_error',
                    'Template submission not found.'
                );
        }

        $data = json_decode(
            $submission->template_data,
            true
        ) ?: [];

        $paper = ($data['report_type'] ?? 'quarterly') === 'monthly'
            ? 'portrait'
            : 'landscape';

        $pdf = Pdf::loadView(
            'shared.budget-template-download',
            [
                'data' => $data,
                'barangayName' => auth()->user()->barangay->barangay_name ?? 'Barangay',
            ]
        )->setPaper('a4', $paper);

        return $pdf->download(
            'budget-template-'.$budgetReportId.'.pdf'
        );
    }

    public function viewTemplate(int $budgetReportId): Response|RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_secretary', 403);

        $submission = DB::table('budget_reports')
            ->where('budget_report_id', $budgetReportId)
            ->where('barangay_id', auth()->user()->barangay_id)
            ->first();

        if (!$submission || empty($submission->template_data)) {
            return redirect()
                ->route('sk_secretary.budget')
                ->with(
                    'report_error',
                    'Template submission not found.'
                );
        }

        $data = json_decode(
            $submission->template_data,
            true
        ) ?: [];

        $paper = ($data['report_type'] ?? 'quarterly') === 'monthly'
            ? 'portrait'
            : 'landscape';

        $pdf = Pdf::loadView(
            'shared.budget-template-download',
            [
                'data' => $data,
                'barangayName' => auth()->user()->barangay->barangay_name ?? 'Barangay',
            ]
        )->setPaper('a4', $paper);

        return $pdf->stream(
            'budget-template-'.$budgetReportId.'.pdf'
        );
    }

    protected function templateAvailableForSlot(object $slot): bool
    {
        return ($slot->budget_category ?? null) === 'coa_report'
            && in_array(
                $slot->budget_period_type ?? null,
                ['monthly', 'quarterly', 'annual'],
                true
            );
    }

    protected function applySlotMetadata(array $data, object $slot): array
    {
        if (Schema::hasColumn('budget_reports', 'budget_category')) {
            $data['budget_category'] = $slot->budget_category;
        }

        if (Schema::hasColumn('budget_reports', 'budget_period_type')) {
            $data['budget_period_type'] =
                ($slot->budget_category ?? null) === 'coa_report'
                    ? $slot->budget_period_type
                    : null;
        }

        if (Schema::hasColumn('budget_reports', 'fiscal_month')) {
            $data['fiscal_month'] =
                ($slot->budget_period_type ?? null) === 'monthly'
                    ? $slot->fiscal_month
                    : null;
        }

        if (Schema::hasColumn('budget_reports', 'fiscal_quarter')) {
            $data['fiscal_quarter'] =
                ($slot->budget_period_type ?? null) === 'quarterly'
                    ? $slot->fiscal_quarter
                    : null;
        }

        if (Schema::hasColumn('budget_reports', 'fiscal_half')) {
            $data['fiscal_half'] =
                ($slot->budget_period_type ?? null) === 'semi_annual'
                    ? $slot->fiscal_half
                    : null;
        }

        return $data;
    }

    protected function menuItems(): array
    {
        return [
            ['link' => route('sk_secretary.home'), 'icon' => '&#127968;', 'label' => 'Home'],
            ['link' => route('sk_secretary.reports'), 'icon' => '&#128196;', 'label' => 'Reports'],
            ['link' => route('sk_secretary.budget'), 'icon' => '&#128229;', 'label' => 'Budget'],
            ['link' => route('sk_secretary.announcements'), 'icon' => '&#128226;', 'label' => 'Announcements'],
            ['link' => route('sk_secretary.calendar'), 'icon' => '&#128197;', 'label' => 'Calendar'],
            ['link' => route('sk_secretary.chat'), 'icon' => '&#128172;', 'label' => 'Chat'],
            ['link' => route('sk_secretary.meetings'), 'icon' => '&#128222;', 'label' => 'Meetings'],
            ['link' => route('sk_secretary.rankings'), 'icon' => '&#127942;', 'label' => 'Rankings'],
            ['link' => route('sk_secretary.leadership'), 'icon' => '&#128101;', 'label' => 'Leadership'],
        ];
    }

    protected function scoreSubmission(
        object $slot,
        int $sourceId,
        string $sourceType
    ): void {
        $user = auth()->user();

        $points = app(RankingPointsService::class);

        $isOnTime = now()->lessThanOrEqualTo(
            Carbon::parse($slot->end_date)->endOfDay()
        );

        $submissionAction = $isOnTime
            ? RankingPointsService::ON_TIME_REPORT_SUBMISSION
            : RankingPointsService::LATE_SUBMISSION;

        $points->award(
            (int) $user->barangay_id,
            $submissionAction,
            $sourceType,
            $sourceId,
            (int) $user->user_id
        );

        $points->award(
            (int) $user->barangay_id,
            RankingPointsService::QUALITY_DOCUMENTATION,
            $sourceType,
            $sourceId,
            (int) $user->user_id
        );
    }

    protected function saveBudgetSubmission(
        array $data,
        int $slotId
    ): int {
        $existing = DB::table('budget_reports')
            ->where(
                'barangay_id',
                auth()->user()->barangay_id
            )
            ->where('slot_id', $slotId)
            ->first();

        if ($existing) {
            DB::table('budget_reports')
                ->where(
                    'budget_report_id',
                    $existing->budget_report_id
                )
                ->update($data);

            return (int) $existing->budget_report_id;
        }

        return (int) DB::table('budget_reports')
            ->insertGetId(
                $data,
                'budget_report_id'
            );
    }

    protected function periodLabel(object $submission): string
    {
        $category = $submission->slot_budget_category ?? null;

        $year = $submission->slot_fiscal_year
            ?? $submission->fiscal_year
            ?? '';

        if ($category === 'annual_budget') {
            return 'Annual Budget FY '.$year;
        }

        if ($category === 'supplemental_budget') {
            return 'Supplemental Budget FY '.$year;
        }

        $periodType = $submission->slot_budget_period_type
            ?? $submission->budget_period_type
            ?? 'annual';

        return match ($periodType) {
            'monthly' => Carbon::create(
                (int) $year,
                (int) (
                    $submission->slot_fiscal_month
                    ?? $submission->fiscal_month
                    ?? 1
                ),
                1
            )->format('F Y'),

            'quarterly' => (
                $submission->slot_fiscal_quarter
                ?? $submission->fiscal_quarter
                ?? 'Quarterly'
            ).' '.$year,

            'semi_annual' => (
                ($submission->slot_fiscal_half ?? null) === 'H1'
                    ? 'First Half '
                    : 'Second Half '
            ).$year,

            default => 'Annual '.$year,
        };
    }
}