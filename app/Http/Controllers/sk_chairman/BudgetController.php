<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_chairman/BudgetController.php.

namespace App\Http\Controllers\sk_chairman;

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
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';
        $barangayName = $user->barangay->barangay_name ?? 'Barangay';

        $slots = app(SubmissionSlotService::class)->chairmanBudgetSlots((int) $user->barangay_id);

        $submissions = DB::table('budget_reports')
            ->where('barangay_id', $user->barangay_id)
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($submission) {
                $status = strtolower((string) ($submission->status ?? 'submitted'));
                $method = strtolower((string) $submission->submission_method) === 'file_upload' ? 'pdf' : 'template';
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
                $submission->method_label = $method === 'pdf' ? 'PDF Upload' : 'Template';
                $submission->period_label = $this->periodLabel($submission);
                $submission->download_url = $method === 'pdf' && !empty($submission->uploaded_file_path)
                    ? asset($submission->uploaded_file_path)
                    : route('sk_chairman.budget.template.download', $submission->budget_report_id);
                $submission->view_url = $method === 'pdf' && !empty($submission->uploaded_file_path)
                    ? asset($submission->uploaded_file_path)
                    : route('sk_chairman.budget.template.view', $submission->budget_report_id);

                return $submission;
            });

        return view('sk_chairman.budget', [
            'fullName' => $fullName,
            'barangayName' => $barangayName,
            'initials' => strtoupper(substr($user->first_name ?? 'S', 0, 1) . substr($user->last_name ?? 'K', 0, 1)),
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
            'pageTitle' => 'Budget Submissions',
            'pageDescription' => 'Submit budget documents only through active slots created by the SK President.',
            'slotSectionTitle' => 'Active Budget Slots',
            'slotEmptyMessage' => 'No active budget submission slots right now.',
            'slotActionLabel' => 'Submit Budget File',
            'roleLabel' => 'SK Chairman',
            'slots' => $slots,
            'submissions' => $submissions,
            'submissionType' => 'budget',
            'storeRoute' => route('sk_chairman.budget.store'),
            'profileRoute' => route('sk_chairman.profile'),
            'allowResubmission' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman', 403);

        $validated = $request->validate([
            'slot_id' => ['required', 'integer'],
            'sub_method' => ['required', 'in:template,pdf'],
            'report_type' => ['required', 'in:monthly,quarterly,annual'],
            'reporting_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'reporting_month' => ['nullable', 'integer', 'min:1', 'max:12', 'required_if:report_type,monthly'],
            'reporting_quarter' => ['nullable', 'in:Q1,Q2,Q3,Q4', 'required_if:report_type,quarterly'],
            'report_file' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $slotService = app(SubmissionSlotService::class);
        $slot = $slotService->resolveOpenSlot((int) $validated['slot_id'], 'budget_report', ['SK Chairman', 'Both']);

        if (!$slot) {
            return back()->with('report_error', 'That budget submission slot is no longer available.');
        }

        if ($validated['sub_method'] === 'pdf' && !$request->hasFile('report_file')) {
            return back()->withErrors(['report_file' => 'A PDF file is required for PDF Upload.'])->withInput();
        }

        $uploadPath = null;
        $uploadName = null;
        $method = $validated['sub_method'] === 'pdf' ? 'file_upload' : 'direct_input';

        if ($request->hasFile('report_file')) {
            $directory = public_path('uploads/budget_reports');
            File::ensureDirectoryExists($directory);

            $filename = 'BUD_' . time() . '_' . auth()->user()->barangay_id . '.pdf';
            $request->file('report_file')->move($directory, $filename);
            $uploadName = $request->file('report_file')->getClientOriginalName();
            $uploadPath = 'uploads/budget_reports/' . $filename;
        }

        $data = [
            'user_id' => auth()->user()->user_id,
            'barangay_id' => auth()->user()->barangay_id,
            'slot_id' => $slot->slot_id,
            'submission_method' => $method,
            'document_type' => 'financial_record',
            'fiscal_year' => $validated['reporting_year'],
            'title' => $slot->title,
            'generated_pdf_path' => $method === 'direct_input' ? 'SYSTEM_GEN' : null,
            'uploaded_file_name' => $uploadName,
            'uploaded_file_path' => $uploadPath,
            'total_amount' => 0,
            'status' => 'recorded',
            'submitted_at' => now(),
            'created_at' => now(),
        ];

        if (Schema::hasColumn('budget_reports', 'budget_period_type')) {
            $data['budget_period_type'] = $validated['report_type'];
            $data['fiscal_month'] = $validated['report_type'] === 'monthly' ? $validated['reporting_month'] : null;
            $data['fiscal_quarter'] = $validated['report_type'] === 'quarterly' ? $validated['reporting_quarter'] : null;
        }

        $budgetReportId = $this->saveBudgetSubmission($data, (int) $slot->slot_id);

        $this->scoreSubmission($slot, $budgetReportId, 'budget_report');

        return redirect()->route('sk_chairman.budget')->with('report_success', 'Budget document submitted successfully.');
    }

    public function createTemplate(Request $request): View|RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman', 403);

        $slotId = (int) $request->query('slot_id');
        $slotService = app(SubmissionSlotService::class);
        $slot = $slotService->resolveOpenSlot($slotId, 'budget_report', ['SK Chairman', 'Both']);

        if (!$slot) {
            return redirect()->route('sk_chairman.budget')->with('report_error', 'That budget submission slot is no longer available.');
        }

        $user = auth()->user();
        $reportType = in_array($request->query('report_type'), ['monthly', 'quarterly', 'annual'], true)
            ? $request->query('report_type')
            : 'quarterly';

        return view('shared.budget-template-form', [
            'slot' => $slot,
            'submitRoute' => route('sk_chairman.budget.template.store'),
            'backRoute' => route('sk_chairman.budget'),
            'fullName' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User',
            'barangayName' => $user->barangay->barangay_name ?? 'Barangay',
            'reportType' => $reportType,
            'reportingYear' => (int) $request->query('reporting_year', now()->year),
            'reportingMonth' => (int) $request->query('reporting_month', now()->month),
            'reportingQuarter' => $request->query('reporting_quarter', 'Q1'),
        ]);
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman', 403);

        $validated = $request->validate([
            'slot_id' => ['required', 'integer'],
            'report_type' => ['required', 'in:monthly,quarterly,annual'],
            'reporting_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'reporting_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'reporting_quarter' => ['nullable', 'in:Q1,Q2,Q3,Q4'],
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

        $slotService = app(SubmissionSlotService::class);
        $slot = $slotService->resolveOpenSlot((int) $validated['slot_id'], 'budget_report', ['SK Chairman', 'Both']);

        if (!$slot) {
            return redirect()->route('sk_chairman.budget')->with('report_error', 'That budget submission slot is no longer available.');
        }

        $data = [
            'user_id' => auth()->user()->user_id,
            'barangay_id' => auth()->user()->barangay_id,
            'slot_id' => $slot->slot_id,
            'submission_method' => 'direct_input',
            'document_type' => 'financial_record',
            'fiscal_year' => $validated['reporting_year'],
            'title' => $slot->title,
            'generated_pdf_path' => 'TEMPLATE_GEN',
            'template_data' => json_encode($validated, JSON_UNESCAPED_UNICODE),
            'uploaded_file_name' => null,
            'uploaded_file_path' => null,
            'total_amount' => collect($validated['rows'] ?? [])->sum(fn ($row) => (float) ($row['total_amount'] ?? 0))
                + collect($validated['inventory_rows'] ?? [])->sum(fn ($row) => (float) ($row['shortage_value'] ?? 0)),
            'status' => 'recorded',
            'submitted_at' => now(),
            'created_at' => now(),
        ];

        if (Schema::hasColumn('budget_reports', 'budget_period_type')) {
            $data['budget_period_type'] = $validated['report_type'];
            $data['fiscal_month'] = $validated['report_type'] === 'monthly' ? ($validated['reporting_month'] ?? now()->month) : null;
            $data['fiscal_quarter'] = $validated['report_type'] === 'quarterly' ? ($validated['reporting_quarter'] ?? 'Q1') : null;
        }

        $budgetReportId = $this->saveBudgetSubmission($data, (int) $slot->slot_id);

        $this->scoreSubmission($slot, $budgetReportId, 'budget_report');

        return redirect()->route('sk_chairman.budget')->with('report_success', 'Budget template submitted successfully.');
    }

    public function downloadTemplate(int $budgetReportId): Response|RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman', 403);

        $submission = DB::table('budget_reports')
            ->where('budget_report_id', $budgetReportId)
            ->where('barangay_id', auth()->user()->barangay_id)
            ->first();

        if (!$submission || empty($submission->template_data)) {
            return redirect()->route('sk_chairman.budget')->with('report_error', 'Template submission not found.');
        }

        $data = json_decode($submission->template_data, true) ?: [];
        $paper = ($data['report_type'] ?? 'quarterly') === 'monthly' ? 'portrait' : 'landscape';
        $pdf = Pdf::loadView('shared.budget-template-download', [
            'data' => $data,
            'barangayName' => auth()->user()->barangay->barangay_name ?? 'Barangay',
        ])->setPaper('a4', $paper);

        return $pdf->download('budget-template-'.$budgetReportId.'.pdf');
    }

    public function viewTemplate(int $budgetReportId): Response|RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman', 403);

        $submission = DB::table('budget_reports')
            ->where('budget_report_id', $budgetReportId)
            ->where('barangay_id', auth()->user()->barangay_id)
            ->first();

        if (!$submission || empty($submission->template_data)) {
            return redirect()->route('sk_chairman.budget')->with('report_error', 'Template submission not found.');
        }

        $data = json_decode($submission->template_data, true) ?: [];
        $paper = ($data['report_type'] ?? 'quarterly') === 'monthly' ? 'portrait' : 'landscape';
        $pdf = Pdf::loadView('shared.budget-template-download', [
            'data' => $data,
            'barangayName' => auth()->user()->barangay->barangay_name ?? 'Barangay',
        ])->setPaper('a4', $paper);

        return $pdf->stream('budget-template-'.$budgetReportId.'.pdf');
    }

    protected function menuItems(): array
    {
        return [
            ['link' => route('sk_chairman.home'), 'icon' => '&#127968;', 'label' => 'Home'],
            ['link' => route('sk_chairman.reports'), 'icon' => '&#128196;', 'label' => 'Reports'],
            ['link' => route('sk_chairman.budget'), 'icon' => '&#128229;', 'label' => 'Budget'],
            ['link' => route('sk_chairman.announcements'), 'icon' => '&#128226;', 'label' => 'Announcements'],
            ['link' => route('sk_chairman.calendar'), 'icon' => '&#128197;', 'label' => 'Calendar'],
            ['link' => route('sk_chairman.chat'), 'icon' => '&#128172;', 'label' => 'Chat'],
            ['link' => route('sk_chairman.meetings'), 'icon' => '&#128222;', 'label' => 'Meetings'],
            ['link' => route('sk_chairman.rankings'), 'icon' => '&#127942;', 'label' => 'Rankings'],
            ['link' => route('sk_chairman.leadership'), 'icon' => '&#128101;', 'label' => 'Leadership'],
            ['link' => route('sk_chairman.archive'), 'icon' => '&#128465;', 'label' => 'Archive'],
        ];
    }

    protected function scoreSubmission(object $slot, int $sourceId, string $sourceType): void
    {
        $user = auth()->user();
        $points = app(RankingPointsService::class);
        $isOnTime = now()->lessThanOrEqualTo(Carbon::parse($slot->end_date)->endOfDay());
        $submissionAction = $isOnTime
            ? RankingPointsService::ON_TIME_REPORT_SUBMISSION
            : RankingPointsService::LATE_SUBMISSION;

        $points->award((int) $user->barangay_id, $submissionAction, $sourceType, $sourceId, (int) $user->user_id);
        $points->award((int) $user->barangay_id, RankingPointsService::QUALITY_DOCUMENTATION, $sourceType, $sourceId, (int) $user->user_id);
    }

    protected function saveBudgetSubmission(array $data, int $slotId): int
    {
        $existing = DB::table('budget_reports')
            ->where('barangay_id', auth()->user()->barangay_id)
            ->where('slot_id', $slotId)
            ->first();

        if ($existing) {
            DB::table('budget_reports')
                ->where('budget_report_id', $existing->budget_report_id)
                ->update($data);

            return (int) $existing->budget_report_id;
        }

        return (int) DB::table('budget_reports')->insertGetId($data, 'budget_report_id');
    }

    protected function periodLabel(object $submission): string
    {
        $periodType = $submission->budget_period_type ?? 'annual';

        return match ($periodType) {
            'monthly' => Carbon::create((int) $submission->fiscal_year, (int) ($submission->fiscal_month ?: 1), 1)->format('F Y'),
            'quarterly' => ($submission->fiscal_quarter ?: 'Quarterly').' '.$submission->fiscal_year,
            default => 'Annual '.$submission->fiscal_year,
        };
    }
}
