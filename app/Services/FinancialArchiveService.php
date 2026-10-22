<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class FinancialArchiveService
{
    private function budgets(User $actor)
    {
        abort_unless(in_array($actor->role, ['sk_president', 'sk_chairman'], true), 403);
        if ($actor->role === 'sk_chairman') abort_unless($actor->barangay_id, 403);
        return DB::table('annual_budgets as a')->join('administration_terms as t', 't.term_id', '=', 'a.recorded_term_id')
            ->join('barangays as b', 'b.barangay_id', '=', 'a.barangay_id')
            ->when($actor->role === 'sk_chairman', fn($q) => $q->where('a.barangay_id', $actor->barangay_id))
            ->where(function ($q) {
                $q->where('t.status', 'completed')->orWhereExists(function ($deleted) {
                    $deleted->selectRaw('1')->from('project_expenses as e')->join('budget_projects as p', 'p.project_id', '=', 'e.project_id')
                        ->whereColumn('p.annual_budget_id', 'a.annual_budget_id')->whereIn('e.status', ['cancelled', 'voided']);
                });
            });
    }

    public function records(User $actor, array $filters = []): Collection
    {
        if (!in_array($filters['type'] ?? '', ['', 'budget', 'finance'], true)) return collect();
        $rows = $this->budgets($actor)
            ->when($filters['term_id'] ?? null, fn($q,$id) => $q->where('a.recorded_term_id', $id))
            ->when($filters['barangay_id'] ?? null, fn($q,$id) => $q->where('a.barangay_id', $id))
            ->when($filters['year'] ?? null, fn($q,$year) => $q->where('a.fiscal_year', $year))
            ->select('a.annual_budget_id', 'a.barangay_id', 'a.fiscal_year', 'a.created_at', 't.start_year', 't.end_year', 't.status as term_status', 'b.barangay_name')
            ->orderByDesc('a.fiscal_year')->orderBy('b.barangay_name')->get();
        $search = mb_strtolower($filters['q'] ?? '');
        return $rows->filter(function ($row) use ($search) {
            if ($search === '' || str_contains(mb_strtolower($row->barangay_name.' '.$row->fiscal_year.' '.$row->start_year.'-'.$row->end_year.' financial records'), $search)) return true;
            return DB::table('budget_projects as p')->leftJoin('project_expenses as e', 'e.project_id', '=', 'p.project_id')
                ->where('p.annual_budget_id', $row->annual_budget_id)
                ->when($row->term_status !== 'completed', fn($q) => $q->whereIn('e.status', ['cancelled', 'voided']))
                ->get(['p.name','p.category','e.description'])->contains(fn($item) => str_contains(mb_strtolower($item->name.' '.$item->category.' '.$item->description), $search));
        })->values();
    }

    public function detail(User $actor, int $id): array
    {
        $budget = $this->budgets($actor)->where('a.annual_budget_id', $id)
            ->select('a.*', 'b.barangay_name', 't.start_year', 't.end_year', 't.status as term_status')->first();
        abort_unless($budget, 404);
        $expenses = DB::table('project_expenses as e')->join('budget_projects as p', 'p.project_id', '=', 'e.project_id')
            ->where('p.annual_budget_id', $id)->when($budget->term_status !== 'completed', fn($q) => $q->whereIn('e.status', ['cancelled', 'voided']))
            ->select('e.*', 'p.name as project_name', 'p.category')->orderByDesc('e.expense_id')->get();
        foreach ($expenses as $expense) $expense->proofs = DB::table('financial_attachments')->where('expense_id', $expense->expense_id)->orderBy('owner_revision_no')->get();
        $projects = DB::table('budget_projects')->where('annual_budget_id', $id)
            ->when($budget->term_status !== 'completed', fn($q) => $q->whereIn('project_id', $expenses->pluck('project_id')))->get();
        $events = DB::table('financial_events')->where(function ($q) use ($budget, $expenses, $projects, $id) {
            if ($budget->term_status === 'completed') {
                $q->where(fn($q) => $q->where('subject_type', 'annual_budget')->where('subject_id', $id))
                    ->orWhere(fn($q) => $q->where('subject_type', 'budget_project')->whereIn('subject_id', $projects->pluck('project_id')));
            }
            $q->orWhere(fn($q) => $q->where('subject_type', 'project_expense')->whereIn('subject_id', $expenses->pluck('expense_id')));
        })->orderByDesc('created_at')->orderByDesc('financial_event_id')->get();
        $names = DB::table('users')->whereIn('user_id', $events->pluck('actor_id'))->get(['user_id','first_name','last_name'])
            ->mapWithKeys(fn($user) => [$user->user_id => trim(($user->first_name ?? '').' '.($user->last_name ?? ''))]);
        $effectiveAmount = app(BudgetMonitoringService::class)->allotted($budget);
        return compact('budget', 'expenses', 'projects', 'events', 'names', 'effectiveAmount');
    }

    public function proofLinks(User $actor, int $budgetId): Collection
    {
        $budget = $this->budgets($actor)->where('a.annual_budget_id', $budgetId)->select('t.status as term_status')->first();
        abort_unless($budget, 404);
        return DB::table('financial_attachments as f')->join('project_expenses as e', 'e.expense_id', '=', 'f.expense_id')
            ->join('budget_projects as p', 'p.project_id', '=', 'e.project_id')->where('p.annual_budget_id', $budgetId)
            ->when($budget->term_status !== 'completed', fn($q) => $q->whereIn('e.status', ['cancelled','voided']))
            ->where('f.storage_disk', 'local')->whereNull('f.budget_report_id')->whereNull('f.fund_release_id')
            ->orderByDesc('f.owner_revision_no')->get(['f.attachment_id','f.original_name','f.owner_revision_no','e.description','f.storage_path','f.mime_type'])
            ->filter(fn($file) => str_starts_with($file->storage_path, 'financial/expenses/') && !str_contains($file->storage_path, '..')
                && \Illuminate\Support\Facades\Storage::disk('local')->exists($file->storage_path)
                && in_array($file->mime_type, ['application/pdf','image/jpeg','image/png'], true))
            ->map(fn($file) => (object)['label'=>$file->description.' — Revision '.$file->owner_revision_no,
                'view_url'=>route('archive.financial-proof', $file->attachment_id),
                'download_url'=>route('archive.financial-proof.download', $file->attachment_id)])->values();
    }

    public function proof(User $actor, int $attachment)
    {
        // Reuse budget authorization, then restrict current-term proof access to deleted expenses.
        $owner = DB::table('financial_attachments as f')->join('project_expenses as e', 'e.expense_id', '=', 'f.expense_id')
            ->join('budget_projects as p', 'p.project_id', '=', 'e.project_id')->where('f.attachment_id', $attachment)
            ->select('p.annual_budget_id', 'e.status')->first();
        abort_unless($owner, 404);
        $budget = $this->budgets($actor)->where('a.annual_budget_id', $owner->annual_budget_id)->select('t.status as term_status')->first();
        abort_unless($budget && ($budget->term_status === 'completed' || in_array($owner->status, ['cancelled','voided'], true)), 404);
        $file = DB::table('financial_attachments')->where('attachment_id', $attachment)->first();
        return app(FinancialProofService::class)->response($file);
    }
}
