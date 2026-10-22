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
        if ($request->routeIs('sk_pres.*')) abort_unless($role === 'sk_president', 403);
        if ($request->routeIs('sk_chairman.*')) abort_unless($role === 'sk_chairman', 403);
        if ($role === 'sk_chairman') abort_unless($request->user()->barangay_id, 403);
        return $role;
    }

    public function index(Request $request, BudgetMonitoringService $service)
    {
        $role = $this->role($request);
        $request->validate(['year' => 'nullable|integer|between:2000,2100']);
        $currentTerm = app(\App\Services\FinancialTermScope::class)->current();
        $defaultYear = $currentTerm ? max((int)$currentTerm->start_year, min((int)$currentTerm->end_year, (int)now()->year)) : (int)now()->year;
        $year = (int)$request->query('year', $defaultYear);
        if ($currentTerm && ($year < (int)$currentTerm->start_year || $year > (int)$currentTerm->end_year)) abort(404, 'Previous administrations are available in Archive.');
        $budgets = DB::table('annual_budgets as a')->join('barangays as b', 'b.barangay_id', '=', 'a.barangay_id')
            ->where('a.fiscal_year', $year)->where('a.status', 'verified')->where('a.recorded_term_id', $currentTerm->term_id ?? 0)
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
        $actorIds = $budgets->flatMap(fn ($budget) => $budget->events->pluck('actor_id')
            ->merge($budget->expenses->flatMap(fn ($expense) => $expense->events->pluck('actor_id'))))->unique();
        $actorNames = $actorIds->isEmpty() ? collect() : DB::table('users')->whereIn('user_id', $actorIds)
            ->get(['user_id', 'first_name', 'last_name'])->mapWithKeys(fn ($user) => [
                $user->user_id => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'Official name unavailable',
            ]);
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
        $sections = $role === 'sk_president' ? ['overview', 'allocations', 'expenses', 'transparency', 'history'] : ['overview', 'expenses', 'submissions', 'history'];
        if (!in_array($section, $sections, true)) $section = 'overview';
        $submissionData = $role === 'sk_chairman' && $section === 'submissions'
            ? app(\App\Http\Controllers\sk_chairman\BudgetController::class)->submissionData($request) : null;
        return view('shared.budget-monitoring', [
            'budgets' => $budgets, 'year' => $year, 'isPresident' => $role === 'sk_president',
            'barangays' => DB::table('barangays')->orderBy('barangay_name')->get(),
            'categories' => app(\App\Services\FinancialCategoryService::class)->options(),
            'financialSchemaReady' => (bool)(collect(\Illuminate\Support\Facades\Schema::getColumns('annual_budgets'))->firstWhere('name', 'approval_reference')['nullable'] ?? false),
            'fiscalYears' => $currentTerm ? collect(range((int)$currentTerm->start_year, (int)$currentTerm->end_year))->sortDesc()->values() : collect([$defaultYear]),
            'defaultFinancialYear' => $defaultYear,
            'actorNames' => $actorNames,
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
            'approved_on' => 'required|date_format:Y-m-d', 'approval_reference' => 'nullable|string|max:150',
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
        $data = $request->validate(['name' => 'required|string|max:255', 'category' => 'required|string|max:100', 'planned_amount' => 'required|numeric|min:0|max:9999999999999.99']);
        $service->createProject($budget, $data, $request->user());
        return back()->with('success', 'Project/category recorded. Select it when submitting an expense.');
    }

    public function expense(Request $request, BudgetMonitoringService $service, ?int $expense = null)
    {
        abort_unless($this->role($request) === 'sk_chairman', 403);
        $data = $request->validate([
            'project_id' => ['required', 'regex:/^(new|[0-9]+)$/'],
            'annual_budget_id' => 'required_if:project_id,new|nullable|integer',
            'project_name' => 'required_if:project_id,new|nullable|string|max:255',
            'category' => 'required_if:project_id,new|nullable|string|max:100',
            'new_category' => 'required_if:category,new|nullable|string|max:100',
            'planned_amount' => 'nullable|numeric|min:0|max:9999999999999.99',
            'public_proof' => 'nullable|boolean', 'client_request_id' => ($expense ? 'nullable' : 'required').'|uuid',
            'description' => 'required|string|max:5000',
            'amount' => ['required', 'regex:/^\d{1,13}(\.\d{1,2})?$/', 'numeric', 'min:0.01'],
            'expense_date' => 'required|date_format:Y-m-d|before_or_equal:today', 'document_reference' => 'nullable|string|max:150',
            'payee_name' => 'nullable|string|max:255', 'proof' => ($expense ? 'nullable' : 'required').'|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'revision_no' => ($expense ? 'required' : 'nullable').'|integer|min:1',
            'reason' => 'nullable|string|max:2000',
        ]);
        $service->submitExpense($data, $request->file('proof'), $request->user(), $expense);
        return back()->with('success', 'Expense submitted. Monitoring balance recalculated.');
    }

    public function transition(Request $request, int $expense, BudgetMonitoringService $service)
    {
        $this->role($request);
        $data = $request->validate(['status' => 'required|in:verified,needs_revision,cancelled', 'revision_no' => 'required|integer|min:1', 'reason' => 'nullable|string|max:2000']);
        $service->transitionExpense($expense, $data, $request->user());
        return back()->with('success', 'Expense deleted from active records. Audit history retained.');
    }

    public function attachment(Request $request, int $attachment)
    {
        $role = $this->role($request);
        $file = DB::table('financial_attachments as f')->join('project_expenses as e', 'e.expense_id', '=', 'f.expense_id')
            ->join('budget_projects as p', 'p.project_id', '=', 'e.project_id')->join('annual_budgets as a', 'a.annual_budget_id', '=', 'p.annual_budget_id')
            ->where('f.attachment_id', $attachment)->where('a.recorded_term_id', app(\App\Services\FinancialTermScope::class)->current()->term_id ?? 0)->when($role === 'sk_chairman', fn ($q) => $q->where('a.barangay_id', $request->user()->barangay_id))
            ->select('f.*')->first();
        return app(\App\Services\FinancialProofService::class)->response($file);
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
        return back()->with('success', 'Annual allocation authorized for public viewing. Reported expenses update automatically.');
    }
}
