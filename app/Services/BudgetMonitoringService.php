<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class BudgetMonitoringService
{
    public const ACTIVE_EXPENSES = ['submitted', 'verified', 'needs_revision'];

    public static function cents(string|int $amount): int
    {
        $parts = explode('.', (string) $amount);
        return ((int) $parts[0] * 100) + (int) str_pad($parts[1] ?? '', 2, '0');
    }

    public static function decimal(int $cents): string
    {
        return ($cents < 0 ? '-' : '').intdiv(abs($cents), 100).'.'.str_pad((string) (abs($cents) % 100), 2, '0', STR_PAD_LEFT);
    }

    public function event(string $type, int $id, string $action, User $actor, ?object $before, object|array $after, ?string $remarks = null): void
    {
        // Call only while holding the subject row lock in the owning transaction.
        $sequence = (int) DB::table('financial_events')->where('subject_type', $type)->where('subject_id', $id)->max('sequence_no') + 1;
        DB::table('financial_events')->insert([
            'subject_type' => $type, 'subject_id' => $id, 'sequence_no' => $sequence,
            'event_type' => $action, 'actor_id' => $actor->user_id, 'request_id' => (string) Str::uuid(),
            'before_snapshot' => $before ? json_encode($before, JSON_THROW_ON_ERROR) : null,
            'after_snapshot' => json_encode($after, JSON_THROW_ON_ERROR), 'remarks' => $remarks, 'created_at' => now(),
        ]);
    }

    public function allotted(object $budget): string
    {
        $approvedAmount = $budget->approved_amount ?? $budget->original_amount ?? '0.00';
        if (!Schema::hasTable('financial_events')) return (string) $approvedAmount;

        $event = DB::table('financial_events')->where('subject_type', 'annual_budget')
            ->where('subject_id', $budget->annual_budget_id)->where('event_type', 'allocation_corrected')
            ->orderByDesc('sequence_no')->first();
        return $event ? (string) json_decode($event->after_snapshot, true, 512, JSON_THROW_ON_ERROR)['effective_amount'] : (string) $approvedAmount;
    }

    public function summary(object $budget): array
    {
        $query = DB::table('project_expenses as e')->join('budget_projects as p', 'p.project_id', '=', 'e.project_id')
            ->where('p.annual_budget_id', $budget->annual_budget_id);
        $total = (string) (clone $query)->whereIn('e.status', self::ACTIVE_EXPENSES)->sum('e.amount');
        $verified = (string) (clone $query)->where('e.status', 'verified')->sum('e.amount');
        $allotted = $this->allotted($budget);
        return ['allotted' => $allotted, 'expenses' => $total, 'verified' => $verified,
            'pending' => self::decimal(self::cents($total) - self::cents($verified)),
            'remaining' => self::decimal(self::cents($allotted) - self::cents($total))];
    }

    public function allocate(array $data, User $actor): int
    {
        abort_unless($actor->role === 'sk_president', 403);
        return DB::transaction(function () use ($data, $actor) {
            $term = DB::table('administration_terms')->where('status', 'current')->lockForUpdate()->first();
            if (!$term) throw ValidationException::withMessages(['allocation' => 'An active administration term is required.']);
            if (DB::table('annual_budgets')->where('barangay_id', $data['barangay_id'])->where('fiscal_year', $data['fiscal_year'])->exists()) {
                throw ValidationException::withMessages(['allocation' => 'This barangay already has an allocation for that year. Use the correction form.']);
            }
            $id = DB::table('annual_budgets')->insertGetId([
                'barangay_id' => $data['barangay_id'], 'recorded_term_id' => $term->term_id,
                'fiscal_year' => $data['fiscal_year'], 'approved_amount' => $data['amount'],
                'approved_on' => $data['approved_on'], 'approval_reference' => $data['approval_reference'],
                'status' => 'verified', 'created_by' => $actor->user_id, 'verified_by' => $actor->user_id,
                'verified_at' => now(), 'revision_no' => 1, 'created_at' => now(), 'updated_at' => now(),
            ], 'annual_budget_id');
            $this->event('annual_budget', $id, 'allocation_recorded', $actor, null, DB::table('annual_budgets')->where('annual_budget_id', $id)->first());
            return $id;
        });
    }

    public function correctAllocation(int $id, array $data, User $actor): void
    {
        abort_unless($actor->role === 'sk_president', 403);
        DB::transaction(function () use ($id, $data, $actor) {
            $budget = DB::table('annual_budgets')->where('annual_budget_id', $id)->lockForUpdate()->first();
            abort_unless($budget && $budget->status === 'verified', 404);
            if ((int) $budget->revision_no !== (int) $data['revision_no']) throw ValidationException::withMessages(['revision_no' => 'This allocation changed. Reload before correcting it.']);
            $before = (object) ['budget' => $budget, 'effective_amount' => $this->allotted($budget)];
            DB::table('annual_budgets')->where('annual_budget_id', $id)->update([
                'revision_no' => $budget->revision_no + 1, 'published_by' => null, 'published_at' => null, 'updated_at' => now(),
            ]);
            $this->event('annual_budget', $id, 'allocation_corrected', $actor, $before,
                ['budget' => DB::table('annual_budgets')->where('annual_budget_id', $id)->first(), 'effective_amount' => $data['amount']], $data['reason']);
        });
    }

    public function createProject(int $budgetId, array $data, User $actor): int
    {
        abort_unless($actor->role === 'sk_chairman', 403);
        return DB::transaction(function () use ($budgetId, $data, $actor) {
            $budget = DB::table('annual_budgets')->where('annual_budget_id', $budgetId)->lockForUpdate()->first();
            abort_unless($budget && $budget->status === 'verified' && (int) $budget->barangay_id === (int) $actor->barangay_id, 404);
            $id = DB::table('budget_projects')->insertGetId([
                'annual_budget_id' => $budgetId, 'project_code' => 'PRJ-'.Str::uuid(),
                'name' => $data['name'], 'category' => $data['category'], 'planned_amount' => '0.00',
                'implementation_status' => 'proposed', 'created_by' => $actor->user_id, 'updated_by' => $actor->user_id,
                'revision_no' => 1, 'created_at' => now(), 'updated_at' => now(),
            ], 'project_id');
            $this->event('budget_project', $id, 'project_recorded', $actor, null, DB::table('budget_projects')->where('project_id', $id)->first());
            return $id;
        });
    }

    public function expenseSnapshot(object $expense): object
    {
        return (object) ['expense' => $expense, 'attachment_ids' => DB::table('financial_attachments')
            ->where('expense_id', $expense->expense_id)->where('owner_revision_no', $expense->revision_no)->pluck('attachment_id')->all()];
    }

    public function submitExpense(array $data, UploadedFile $proof, User $actor, ?int $expenseId = null): int
    {
        abort_unless($actor->role === 'sk_chairman', 403);
        $storedPath = null;
        try {
            return DB::transaction(function () use ($data, $proof, $actor, $expenseId, &$storedPath) {
                $project = DB::table('budget_projects as p')->join('annual_budgets as a', 'a.annual_budget_id', '=', 'p.annual_budget_id')
                    ->where('p.project_id', $data['project_id'])->where('a.barangay_id', $actor->barangay_id)
                    ->where('a.status', 'verified')->where('p.implementation_status', '<>', 'cancelled')
                    ->select('p.*', 'a.fiscal_year')->first();
                abort_unless($project, 404);
                if ((int) substr($data['expense_date'], 0, 4) !== (int) $project->fiscal_year) {
                    throw ValidationException::withMessages(['expense_date' => 'Expense date must fall within the selected budget fiscal year.']);
                }
                // Serialize retries and writes within the annual budget, including concurrent identical requests.
                DB::table('annual_budgets')->where('annual_budget_id', $project->annual_budget_id)->lockForUpdate()->first();
                $before = null;
                if ($expenseId) {
                    $expense = DB::table('project_expenses')->where('expense_id', $expenseId)->lockForUpdate()->first();
                    abort_unless($expense && (int) $expense->created_by === (int) $actor->user_id && (int) $expense->project_id === (int) $project->project_id, 404);
                    if (!in_array($expense->status, self::ACTIVE_EXPENSES, true) || (int) $expense->revision_no !== (int) $data['revision_no']) {
                        throw ValidationException::withMessages(['revision_no' => 'This expense changed or is inactive. Reload before correcting it.']);
                    }
                    $before = $this->expenseSnapshot($expense);
                    $revision = $expense->revision_no + 1;
                } else {
                    $retry = DB::table('project_expenses')->where('client_request_id', $data['client_request_id'])->first();
                    if ($retry) {
                        abort_unless((int) $retry->created_by === (int) $actor->user_id && (int) $retry->project_id === (int) $project->project_id, 409);
                        if (self::cents((string) $retry->amount) !== self::cents((string) $data['amount']) || $retry->description !== $data['description'] || $retry->expense_date !== $data['expense_date']) {
                            throw ValidationException::withMessages(['client_request_id' => 'This submission was already recorded with different details. Use the correction form.']);
                        }
                        return (int) $retry->expense_id;
                    }
                    $revision = 1;
                }
                $storedPath = $proof->store('financial/expenses', 'local');
                if (!$storedPath) throw new \RuntimeException('Unable to store expense proof.');
                $row = [
                    'project_id' => $project->project_id, 'expense_date' => $data['expense_date'], 'description' => $data['description'],
                    'amount' => $data['amount'], 'document_reference' => $data['document_reference'] ?? null,
                    'payee_name' => $data['payee_name'] ?? null, 'status' => 'submitted', 'updated_by' => $actor->user_id,
                    'revision_no' => $revision, 'submitted_at' => now(), 'updated_at' => now(),
                    'verified_by' => null, 'verified_at' => null, 'published_by' => null, 'published_at' => null,
                ];
                if ($expenseId) DB::table('project_expenses')->where('expense_id', $expenseId)->update($row);
                else $expenseId = DB::table('project_expenses')->insertGetId($row + [
                    'expense_reference' => 'EXP-'.Str::uuid(), 'client_request_id' => $data['client_request_id'],
                    'created_by' => $actor->user_id, 'created_at' => now(),
                ], 'expense_id');
                DB::table('financial_attachments')->insert([
                    'expense_id' => $expenseId, 'owner_revision_no' => $revision, 'document_kind' => 'expense_proof',
                    'storage_disk' => 'local', 'storage_path' => $storedPath, 'original_name' => mb_substr(basename($proof->getClientOriginalName()), 0, 255),
                    'mime_type' => $proof->getMimeType(), 'size_bytes' => $proof->getSize(),
                    'sha256' => hash_file('sha256', $proof->getRealPath()), 'uploaded_by' => $actor->user_id, 'created_at' => now(),
                ]);
                $this->event('project_expense', $expenseId, $before ? 'expense_corrected' : 'expense_submitted', $actor, $before,
                    $this->expenseSnapshot(DB::table('project_expenses')->where('expense_id', $expenseId)->first()), $data['reason'] ?? null);
                return $expenseId;
            });
        } catch (\Throwable $e) {
            if ($storedPath) Storage::disk('local')->delete($storedPath);
            throw $e;
        }
    }

    public function transitionExpense(int $id, array $data, User $actor): void
    {
        DB::transaction(function () use ($id, $data, $actor) {
            $expense = DB::table('project_expenses')->where('expense_id', $id)->lockForUpdate()->first();
            abort_unless($expense, 404);
            $budget = DB::table('budget_projects as p')->join('annual_budgets as a', 'a.annual_budget_id', '=', 'p.annual_budget_id')->where('p.project_id', $expense->project_id)->select('a.*')->first();
            if ($data['status'] === 'cancelled') {
                abort_unless($actor->role === 'sk_chairman' && (int) $expense->created_by === (int) $actor->user_id && (int) $budget->barangay_id === (int) $actor->barangay_id, 403);
            } else abort_unless($actor->role === 'sk_president' && in_array($data['status'], ['verified', 'needs_revision'], true), 403);
            if (!in_array($expense->status, self::ACTIVE_EXPENSES, true) || (int) $expense->revision_no !== (int) $data['revision_no']) {
                throw ValidationException::withMessages(['revision_no' => 'This expense changed or is inactive. Reload first.']);
            }
            if ($data['status'] === 'verified' && !DB::table('financial_attachments')->where('expense_id', $id)->where('owner_revision_no', $expense->revision_no)->exists()) {
                throw ValidationException::withMessages(['proof' => 'Current expense proof is required before verification.']);
            }
            $before = $this->expenseSnapshot($expense);
            DB::table('project_expenses')->where('expense_id', $id)->update([
                'status' => $data['status'], 'updated_by' => $actor->user_id, 'updated_at' => now(),
                'verified_by' => $data['status'] === 'verified' ? $actor->user_id : null,
                'verified_at' => $data['status'] === 'verified' ? now() : null,
                'published_by' => null, 'published_at' => null,
            ]);
            $this->event('project_expense', $id, 'expense_'.$data['status'], $actor, $before,
                $this->expenseSnapshot(DB::table('project_expenses')->where('expense_id', $id)->first()), $data['reason']);
        });
    }

    public function linkReport(int $reportId, array $expenseIds, User $actor): void
    {
        abort_unless($actor->role === 'sk_chairman', 403);
        DB::transaction(function () use ($reportId, $expenseIds, $actor) {
            $report = DB::table('budget_reports')->where('budget_report_id', $reportId)->lockForUpdate()->first();
            abort_unless($report && (int) $report->barangay_id === (int) $actor->barangay_id && $report->budget_category === 'coa_report' && $report->status !== 'draft', 404);
            $term = DB::table('administration_terms')->where('status', 'current')->value('term_id');
            $review = DB::table('submission_quality_reviews')->where('source_type', 'budget_report')->where('source_id', $reportId)->first();
            if ((int) $report->term_id !== (int) $term || $review?->status === 'approved') {
                throw ValidationException::withMessages(['report' => 'Only current-term reports awaiting approval can have expense links changed.']);
            }
            $budget = DB::table('annual_budgets')->where('barangay_id', $actor->barangay_id)->where('fiscal_year', $report->fiscal_year)->where('status', 'verified')->first();
            abort_unless($budget, 404);
            DB::table('annual_budgets')->where('annual_budget_id', $budget->annual_budget_id)->lockForUpdate()->first();
            $expenses = DB::table('project_expenses as e')->join('budget_projects as p', 'p.project_id', '=', 'e.project_id')
                ->where('p.annual_budget_id', $budget->annual_budget_id)->whereIn('e.expense_id', $expenseIds)
                ->whereIn('e.status', self::ACTIVE_EXPENSES)->select('e.expense_id', 'e.revision_no', 'e.amount', 'e.expense_date', 'e.project_id')->get();
            if ($expenses->count() !== count(array_unique($expenseIds))) throw ValidationException::withMessages(['expense_ids' => 'All selected expenses must be active and belong to this barangay and fiscal year.']);
            $start = Carbon::create((int) $report->fiscal_year, 1, 1)->startOfDay();
            $end = $start->copy()->endOfYear();
            if ($report->budget_period_type === 'monthly' && $report->fiscal_month) {
                $start->month((int) $report->fiscal_month); $end = $start->copy()->endOfMonth();
            } elseif ($report->budget_period_type === 'quarterly' && $report->fiscal_quarter) {
                $start->month((((int) substr($report->fiscal_quarter, 1)) - 1) * 3 + 1); $end = $start->copy()->addMonths(2)->endOfMonth();
            } elseif ($report->budget_period_type === 'semi_annual' && $report->fiscal_half) {
                $start->month($report->fiscal_half === 'H2' ? 7 : 1); $end = $start->copy()->addMonths(5)->endOfMonth();
            }
            if ($report->coverage_start) $start = Carbon::parse($report->coverage_start)->startOfDay();
            if ($report->coverage_end) $end = Carbon::parse($report->coverage_end)->endOfDay();
            foreach ($expenses as $expense) {
                if (!Carbon::parse($expense->expense_date)->betweenIncluded($start, $end) || ($report->project_id && (int)$report->project_id !== (int)$expense->project_id)) {
                    throw ValidationException::withMessages(['expense_ids' => 'Selected expenses must match the report period and related project.']);
                }
            }
            $before = (object) ['report' => $report, 'expense_ids' => DB::table('budget_report_expenses')->where('budget_report_id', $reportId)->pluck('expense_id')->all(), 'review' => $review];
            DB::table('budget_report_expenses')->where('budget_report_id', $reportId)->delete();
            foreach ($expenses as $expense) DB::table('budget_report_expenses')->insert(['budget_report_id' => $reportId, 'expense_id' => $expense->expense_id, 'created_at' => now()]);
            DB::table('budget_reports')->where('budget_report_id', $reportId)->update([
                'annual_budget_id' => $budget->annual_budget_id, 'report_purpose' => 'liquidation',
                'revision_no' => $report->revision_no + 1, 'published_by' => null, 'published_at' => null, 'updated_at' => now(),
            ]);
            $this->event('budget_report', $reportId, 'expenses_linked', $actor, $before,
                ['report' => DB::table('budget_reports')->where('budget_report_id', $reportId)->first(), 'expenses' => $expenses->all(), 'review' => $review],
                'Expense references updated. Existing document review remains separate from expense verification.');
        });
    }

    public function publishSummary(int $budgetId, User $actor): void
    {
        abort_unless($actor->role === 'sk_president', 403);
        DB::transaction(function () use ($budgetId, $actor) {
            $budget = DB::table('annual_budgets')->where('annual_budget_id', $budgetId)->where('status', 'verified')->lockForUpdate()->first();
            abort_unless($budget, 404);
            $summary = $this->summary($budget);
            // Publish only an explicit snapshot of verified aggregate amounts, never receipt/payee metadata.
            $snapshot = ['allotted' => $summary['allotted'], 'verified_expenses' => $summary['verified'],
                'remaining_after_verified' => self::decimal(self::cents($summary['allotted']) - self::cents($summary['verified'])),
                'as_of' => now()->toDateTimeString()];
            $this->event('annual_budget', $budgetId, 'summary_published', $actor, null, $snapshot);
            DB::table('annual_budgets')->where('annual_budget_id', $budgetId)->update(['published_by' => $actor->user_id, 'published_at' => now(), 'updated_at' => now()]);
        });
    }
}
