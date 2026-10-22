<?php

namespace App\Http\Controllers;

use App\Services\BudgetMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BudgetMonitoringController extends Controller
{
    private function role(Request $request): string
    {
        $role = $request->user()->role;
        abort_unless(in_array($role, ['sk_president', 'sk_chairman'], true), 403);
        if ($role === 'sk_chairman') abort_unless($request->user()->barangay_id, 403);
        return $role;
    }

    public function index(Request $request, BudgetMonitoringService $service)
    {
        $role = $this->role($request);
        $request->validate(['year' => 'nullable|integer|between:2000,2100']);
        $year = (int) $request->query('year', now()->year);
        $budgets = DB::table('annual_budgets as a')->join('barangays as b', 'b.barangay_id', '=', 'a.barangay_id')
            ->where('a.fiscal_year', $year)->where('a.status', 'verified')
            ->when($role === 'sk_chairman', fn ($q) => $q->where('a.barangay_id', $request->user()->barangay_id))
            ->select('a.*', 'b.barangay_name')->orderBy('b.barangay_name')->get();
        foreach ($budgets as $budget) {
            $budget->summary = $service->summary($budget);
            $budget->projects = DB::table('budget_projects')->where('annual_budget_id', $budget->annual_budget_id)->get();
            $budget->expenses = DB::table('project_expenses as e')->join('budget_projects as p', 'p.project_id', '=', 'e.project_id')
                ->where('p.annual_budget_id', $budget->annual_budget_id)->select('e.*', 'p.name as project_name', 'p.category as project_category')->orderByDesc('e.expense_id')->get();
            $budget->events = DB::table('financial_events')->where('subject_type', 'annual_budget')->where('subject_id', $budget->annual_budget_id)->orderByDesc('sequence_no')->get();
            $budget->reports = DB::table('budget_reports as r')->leftJoin('submission_quality_reviews as q', function ($join) {
                $join->on('q.source_id', '=', 'r.budget_report_id')->where('q.source_type', 'budget_report');
            })->where('r.barangay_id', $budget->barangay_id)->where('r.fiscal_year', $budget->fiscal_year)->where('r.budget_category', 'coa_report')
                ->select('r.*', 'q.status as quality_status', 'q.remarks as quality_remarks')->get();
            foreach ($budget->reports as $report) {
                $report->linked = DB::table('budget_report_expenses')->where('budget_report_id', $report->budget_report_id)->pluck('expense_id')->all();
                $report->reconciliation = false;
                $snapshot = DB::table('financial_events')->where('subject_type', 'budget_report')->where('subject_id', $report->budget_report_id)->where('event_type', 'expenses_linked')->orderByDesc('sequence_no')->first();
                foreach ($snapshot ? json_decode($snapshot->after_snapshot, true)['expenses'] : [] as $recorded) {
                    $current = $budget->expenses->firstWhere('expense_id', $recorded['expense_id']);
                    if (!$current || (int)$current->revision_no !== (int)$recorded['revision_no'] || !in_array($current->status, BudgetMonitoringService::ACTIVE_EXPENSES, true)) $report->reconciliation = true;
                }
            }
            foreach ($budget->expenses as $expense) {
                $expense->attachments = DB::table('financial_attachments')->where('expense_id', $expense->expense_id)->get();
                $expense->events = DB::table('financial_events')->where('subject_type', 'project_expense')->where('subject_id', $expense->expense_id)->orderByDesc('sequence_no')->get();
            }
        }
        $prefix = $role === 'sk_president' ? 'sk_pres' : 'sk_chairman';
        $navigation = $role === 'sk_president'
            ? ['home' => 'Home', 'dashboard' => 'Dashboard', 'consolidation' => 'Consolidation', 'module' => 'Module Management',
                'announcements' => 'Announcements', 'calendar' => 'Calendar', 'chat' => 'Chat', 'meetings' => 'Meetings',
                'rankings' => 'Rankings', 'leadership' => 'Leadership', 'archive' => 'Archive', 'user-management' => 'User Management']
            : ['home' => 'Home', 'reports' => 'Reports', 'budget' => 'Budget', 'announcements' => 'Announcements',
                'calendar' => 'Calendar', 'chat' => 'Chat', 'meetings' => 'Meetings', 'rankings' => 'Rankings',
                'leadership' => 'Leadership', 'archive' => 'Archive'];
        $menuItems = [];
        foreach ($navigation as $route => $label) {
            $menuItems[] = ['label' => $label, 'link' => route($prefix.'.'.$route)];
        }
        $section = $request->routeIs('sk_chairman.budget') ? 'submissions' : (string) $request->query('section', 'overview');
        if ($section === 'documents') $section = $role === 'sk_chairman' ? 'submissions' : 'overview';
        $sections = $role === 'sk_president' ? ['overview', 'allocations', 'expenses', 'history'] : ['overview', 'expenses', 'submissions', 'history'];
        if (!in_array($section, $sections, true)) $section = 'overview';
        $submissionData = $role === 'sk_chairman' && $section === 'submissions'
            ? app(\App\Http\Controllers\sk_chairman\BudgetController::class)->submissionData($request) : null;
        return view('shared.budget-monitoring', [
            'budgets' => $budgets, 'year' => $year, 'isPresident' => $role === 'sk_president',
            'barangays' => DB::table('barangays')->orderBy('barangay_name')->get(),
            'menuItems' => $menuItems,
            'currentUrl' => url()->current(),
            'financialSection' => $section, 'submissionData' => $submissionData,
        ]);
    }

    public function store(Request $request, BudgetMonitoringService $service)
    {
        abort_unless($this->role($request) === 'sk_president', 403);
        $data = $request->validate([
            'barangay_id' => 'required|integer|exists:barangays,barangay_id', 'fiscal_year' => 'required|integer|between:2000,2100',
            'amount' => ['required', 'regex:/^\d{1,13}(\.\d{1,2})?$/', 'numeric', 'min:0.01'],
            'approved_on' => 'required|date_format:Y-m-d', 'approval_reference' => 'required|string|max:150',
        ]);
        $service->allocate($data, $request->user());
        return redirect()->route('sk_pres.finances', ['year' => $data['fiscal_year'], 'barangay_id' => $data['barangay_id']])->with('success', 'Annual allocation recorded.');
    }

    public function update(Request $request, int $budget, BudgetMonitoringService $service)
    {
        abort_unless($this->role($request) === 'sk_president', 403);
        $data = $request->validate([
            'amount' => ['required', 'regex:/^\d{1,13}(\.\d{1,2})?$/', 'numeric', 'min:0.01'],
            'revision_no' => 'required|integer|min:1', 'reason' => 'required|string|max:2000',
        ]);
        $service->correctAllocation($budget, $data, $request->user());
        return back()->with('success', 'Allocation corrected. Original amount and correction history retained.');
    }

    public function project(Request $request, int $budget, BudgetMonitoringService $service)
    {
        abort_unless($this->role($request) === 'sk_chairman', 403);
        $data = $request->validate(['name' => 'required|string|max:255', 'category' => 'required|string|max:100']);
        $service->createProject($budget, $data, $request->user());
        return back()->with('success', 'Project/category recorded. Select it when submitting an expense.');
    }

    public function expense(Request $request, BudgetMonitoringService $service, ?int $expense = null)
    {
        abort_unless($this->role($request) === 'sk_chairman', 403);
        $data = $request->validate([
            'project_id' => 'required|integer', 'client_request_id' => ($expense ? 'nullable' : 'required').'|uuid',
            'description' => 'required|string|max:5000',
            'amount' => ['required', 'regex:/^\d{1,13}(\.\d{1,2})?$/', 'numeric', 'min:0.01'],
            'expense_date' => 'required|date_format:Y-m-d|before_or_equal:today', 'document_reference' => 'nullable|string|max:150',
            'payee_name' => 'nullable|string|max:255', 'proof' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'revision_no' => ($expense ? 'required' : 'nullable').'|integer|min:1',
            'reason' => ($expense ? 'required' : 'nullable').'|string|max:2000',
        ]);
        $service->submitExpense($data, $request->file('proof'), $request->user(), $expense);
        return back()->with('success', 'Expense submitted. Monitoring balance recalculated.');
    }

    public function transition(Request $request, int $expense, BudgetMonitoringService $service)
    {
        $this->role($request);
        $data = $request->validate(['status' => 'required|in:verified,needs_revision,cancelled', 'revision_no' => 'required|integer|min:1', 'reason' => 'required|string|max:2000']);
        $service->transitionExpense($expense, $data, $request->user());
        return back()->with('success', 'Expense status recorded with audit history.');
    }

    public function attachment(Request $request, int $attachment)
    {
        $role = $this->role($request);
        $file = DB::table('financial_attachments as f')->join('project_expenses as e', 'e.expense_id', '=', 'f.expense_id')
            ->join('budget_projects as p', 'p.project_id', '=', 'e.project_id')->join('annual_budgets as a', 'a.annual_budget_id', '=', 'p.annual_budget_id')
            ->where('f.attachment_id', $attachment)->when($role === 'sk_chairman', fn ($q) => $q->where('a.barangay_id', $request->user()->barangay_id))
            ->select('f.*')->first();
        abort_unless($file && $file->storage_disk === 'local' && str_starts_with($file->storage_path, 'financial/expenses/') && !str_contains($file->storage_path, '..'), 404);
        abort_unless(Storage::disk('local')->exists($file->storage_path), 404);
        abort_if(($file->budget_report_id ?? null) !== null || ($file->fund_release_id ?? null) !== null, 404);
        $disk = Storage::disk('local');
        $path = realpath($disk->path($file->storage_path));
        $root = realpath($disk->path('financial/expenses'));
        abort_unless($path && $root && str_starts_with(str_replace('\\', '/', $path), rtrim(str_replace('\\', '/', $root), '/').'/'), 404);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'application/pdf'], true) && $mime === $file->mime_type, 415, 'Unsupported proof format.');
        return response()->file($path, [
            'Content-Type' => $mime, 'Cache-Control' => 'private, no-store, max-age=0, must-revalidate',
            'Pragma' => 'no-cache', 'Expires' => '0', 'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "frame-ancestors 'self'", 'Referrer-Policy' => 'no-referrer',
        ])->setContentDisposition('inline', basename($file->original_name))->setPrivate();
    }

    public function linkReport(Request $request, int $report, BudgetMonitoringService $service)
    {
        abort_unless($this->role($request) === 'sk_chairman', 403);
        $data = $request->validate(['expense_ids' => 'required|array|min:1|max:500', 'expense_ids.*' => 'required|integer|distinct']);
        $service->linkReport($report, $data['expense_ids'], $request->user());
        return back()->with('success', 'Expense references linked to the existing liquidation document. No expenses were duplicated.');
    }

    public function publish(Request $request, int $budget, BudgetMonitoringService $service)
    {
        abort_unless($this->role($request) === 'sk_president', 403);
        $service->publishSummary($budget, $request->user());
        return back()->with('success', 'Verified financial summary snapshot authorized for public viewing.');
    }
}
