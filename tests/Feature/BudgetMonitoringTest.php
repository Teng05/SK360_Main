<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BudgetMonitoringService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

class BudgetMonitoringTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $t) {
            $t->integer('user_id')->primary(); $t->string('first_name')->nullable(); $t->string('last_name')->nullable();
        });
        $this->withoutMiddleware(\App\Http\Middleware\EnsureAccountIsActive::class);
        Schema::create('barangays', function (Blueprint $t) { $t->integer('barangay_id')->primary(); $t->string('barangay_name'); });
        Schema::create('administration_terms', function (Blueprint $t) { $t->integer('term_id')->primary(); $t->string('status'); $t->integer('start_year')->default(2026); $t->integer('end_year')->default(2028); });
        Schema::create('annual_budgets', function (Blueprint $t) {
            $t->bigIncrements('annual_budget_id'); $t->integer('barangay_id'); $t->integer('recorded_term_id'); $t->integer('fiscal_year');
            $t->decimal('approved_amount', 15, 2); $t->date('approved_on'); $t->string('approval_reference')->nullable(); $t->string('status');
            $t->integer('created_by'); $t->integer('verified_by')->nullable(); $t->dateTime('verified_at')->nullable();
            $t->integer('published_by')->nullable(); $t->dateTime('published_at')->nullable(); $t->integer('revision_no'); $t->timestamps();
            $t->unique(['barangay_id', 'fiscal_year']);
        });
        Schema::create('financial_events', function (Blueprint $t) {
            $t->bigIncrements('financial_event_id'); $t->string('subject_type'); $t->bigInteger('subject_id'); $t->integer('sequence_no');
            $t->string('event_type'); $t->integer('actor_id'); $t->string('request_id'); $t->text('before_snapshot')->nullable();
            $t->text('after_snapshot'); $t->text('remarks')->nullable(); $t->dateTime('created_at'); $t->unique(['subject_type', 'subject_id', 'sequence_no']);
        });
        Schema::create('budget_projects', function (Blueprint $t) {
            $t->bigIncrements('project_id'); $t->bigInteger('annual_budget_id'); $t->string('project_code'); $t->string('name');
            $t->string('category'); $t->decimal('planned_amount', 15, 2); $t->string('implementation_status');
            $t->integer('created_by'); $t->integer('updated_by'); $t->integer('revision_no'); $t->timestamps();
        });
        Schema::create('project_expenses', function (Blueprint $t) {
            $t->bigIncrements('expense_id'); $t->bigInteger('project_id'); $t->decimal('amount', 15, 2); $t->string('status');
            $t->string('expense_reference'); $t->string('client_request_id')->unique(); $t->date('expense_date'); $t->text('description');
            $t->string('payee_name')->nullable(); $t->string('document_reference')->nullable();
            foreach (['created_by', 'updated_by', 'verified_by', 'published_by'] as $column) $t->integer($column)->nullable();
            foreach (['submitted_at', 'verified_at', 'published_at'] as $column) $t->dateTime($column)->nullable();
            $t->integer('revision_no'); $t->timestamps(); $t->unique(['project_id', 'expense_reference']);
        });
        Schema::create('financial_attachments', function (Blueprint $t) {
            $t->bigIncrements('attachment_id'); $t->bigInteger('expense_id'); $t->integer('budget_report_id')->nullable(); $t->integer('fund_release_id')->nullable(); $t->integer('owner_revision_no');
            foreach (['document_kind', 'storage_disk', 'storage_path', 'original_name', 'mime_type', 'sha256'] as $column) $t->string($column);
            $t->integer('published_by')->nullable(); $t->dateTime('published_at')->nullable(); $t->bigInteger('size_bytes'); $t->integer('uploaded_by'); $t->dateTime('created_at');
        });
        Schema::create('budget_reports', function (Blueprint $t) {
            $t->increments('budget_report_id'); $t->integer('barangay_id'); $t->integer('term_id'); $t->integer('fiscal_year');
            $t->integer('slot_id')->nullable();
            $t->integer('user_id')->nullable(); $t->string('submission_method')->nullable(); $t->string('document_type')->nullable();
            $t->string('generated_pdf_path')->nullable(); $t->text('template_data')->nullable(); $t->decimal('actual_expenditure', 15, 2)->nullable();
            $t->string('budget_category'); $t->string('status'); $t->string('title'); $t->integer('revision_no')->default(1);
            $t->integer('annual_budget_id')->nullable(); $t->integer('project_id')->nullable(); $t->string('report_purpose')->nullable();
            $t->string('budget_period_type')->nullable(); $t->integer('fiscal_month')->nullable(); $t->string('fiscal_quarter')->nullable(); $t->string('fiscal_half')->nullable();
            $t->date('coverage_start')->nullable(); $t->date('coverage_end')->nullable();
            $t->integer('published_by')->nullable(); $t->dateTime('published_at')->nullable(); $t->dateTime('submitted_at')->nullable();
            $t->decimal('total_amount', 15, 2)->default(0); $t->string('uploaded_file_name')->nullable(); $t->string('uploaded_file_path')->nullable(); $t->timestamps();
        });
        Schema::create('submission_quality_reviews', function (Blueprint $t) {
            $t->increments('review_id'); $t->string('source_type'); $t->integer('source_id'); $t->string('status'); $t->text('remarks')->nullable();
            $t->dateTime('reviewed_at')->nullable();
        });
        Schema::create('submission_slots', function (Blueprint $t) {
            $t->increments('slot_id'); $t->integer('term_id');
            foreach (['submission_type', 'role', 'status', 'title', 'description', 'budget_category', 'budget_period_type', 'fiscal_quarter', 'fiscal_half'] as $column) $t->string($column)->nullable();
            $t->integer('fiscal_year')->nullable(); $t->integer('fiscal_month')->nullable(); $t->date('start_date')->nullable(); $t->date('end_date')->nullable();
        });
        Schema::create('budget_report_expenses', function (Blueprint $t) {
            $t->integer('budget_report_id'); $t->integer('expense_id'); $t->dateTime('created_at'); $t->primary(['budget_report_id', 'expense_id']);
        });
        Storage::fake('local');
        DB::table('barangays')->insert([['barangay_id' => 1, 'barangay_name' => 'Adya'], ['barangay_id' => 2, 'barangay_name' => 'Other']]);
        DB::table('administration_terms')->insert(['term_id' => 1, 'status' => 'current']);
    }

    protected function actor(string $role = 'sk_president', int $barangay = 1, int $id = 1): User
    {
        $user = new User();
        $user->forceFill(['user_id' => $id, 'role' => $role, 'barangay_id' => $barangay]);
        $this->actingAs($user);
        return $user;
    }

    protected function allocation(): array
    {
        return ['barangay_id' => 1, 'fiscal_year' => 2026, 'amount' => '150000.00', 'approved_on' => '2026-01-01', 'approval_reference' => 'Session 1'];
    }

    public function test_president_records_allocation_and_corrections_preserve_original(): void
    {
        $this->actor();
        $this->post(route('sk_pres.finances.store'), $this->allocation())->assertSessionHasNoErrors()->assertRedirect();
        $this->patch(route('sk_pres.finances.update', 1), ['amount' => '160000.00', 'revision_no' => 1, 'reason' => 'Corrected session record'])->assertSessionHasNoErrors();
        $budget = DB::table('annual_budgets')->first();
        $this->assertEquals(150000, $budget->approved_amount);
        $this->assertSame('160000.00', app(BudgetMonitoringService::class)->allotted($budget));
        $this->assertSame(2, DB::table('financial_events')->count());
        $this->patch(route('sk_pres.finances.update', 1), ['amount' => '170000.00', 'revision_no' => 1, 'reason' => 'Stale'])->assertSessionHasErrors('revision_no');
        $this->post(route('sk_pres.finances.store'), $this->allocation())->assertSessionHasErrors('allocation');
    }

    public function test_chairman_only_sees_own_allocation_and_cannot_encode(): void
    {
        app(BudgetMonitoringService::class)->allocate($this->allocation(), $this->actor());
        $this->actor('sk_chairman');
        $this->get(route('sk_chairman.finances', ['year' => 2026]))->assertOk()->assertSee('150,000.00')->assertDontSee('Record annual allocation');
        $this->post(route('sk_pres.finances.store'), $this->allocation())->assertForbidden();
        $this->actor('sk_chairman', 2);
        $this->get(route('sk_chairman.finances', ['year' => 2026]))->assertOk()->assertDontSee('150,000.00');
        $this->actor('sk_secretary');
        $this->get(route('sk_pres.finances'))->assertForbidden();
    }

    protected function expenseData(): array
    {
        return ['project_id' => 1, 'client_request_id' => (string) Str::uuid(), 'description' => 'Sports supplies',
            'amount' => '30000.00', 'expense_date' => '2026-01-10', 'document_reference' => 'Receipt 123'];
    }

    private function pdf(string $name = 'receipt.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
    }

    protected function prepareProject(): void
    {
        app(BudgetMonitoringService::class)->allocate($this->allocation(), $this->actor());
        $this->actor('sk_chairman', 1, 2);
        $this->post(route('sk_chairman.finances.projects.store', 1), ['name' => 'Sports program', 'category' => 'Sports', 'planned_amount' => '50000.00'])->assertSessionHasNoErrors();
    }

    public function test_mandatory_proof_private_delivery_and_repeat_submission(): void
    {
        $this->prepareProject();
        $data = $this->expenseData();
        $this->post(route('sk_chairman.finances.expenses.store'), $data)->assertSessionHasErrors('proof');
        $this->post(route('sk_chairman.finances.expenses.store'), $data + ['proof' => $this->pdf('receipt.pdf')])->assertSessionHasNoErrors();
        $this->post(route('sk_chairman.finances.expenses.store'), $data + ['proof' => $this->pdf('receipt.pdf')])->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('project_expenses')->count());
        $this->assertSame(1, DB::table('financial_attachments')->count());
        $this->get(route('sk_chairman.finances', ['year' => 2026]))->assertOk()->assertSee('120,000.00');
        $this->get(route('finances.attachments', 1))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actor('sk_president');
        $this->get(route('finances.attachments', 1))->assertOk();
        $this->post(route('sk_chairman.finances.expenses.store'), $data)->assertForbidden();
        $this->actor('sk_chairman', 2, 3);
        $this->get(route('finances.attachments', 1))->assertNotFound();
        $this->post(route('sk_chairman.finances.expenses.store'), $data + ['proof' => $this->pdf('receipt.pdf')])->assertNotFound();
        $this->actor('sk_secretary');
        $this->get(route('finances.attachments', 1))->assertForbidden();
        auth()->logout();
        $this->get(route('finances.attachments', 1))->assertRedirect(route('login'));
    }

    public function test_corrections_returns_and_cancellation_count_one_active_amount(): void
    {
        $this->prepareProject();
        $this->post(route('sk_chairman.finances.expenses.store'), $this->expenseData() + ['proof' => $this->pdf('receipt.pdf')])->assertSessionHasNoErrors();
        $this->actor();
        $this->patch(route('sk_pres.finances.expenses.status', 1), ['status' => 'needs_revision', 'revision_no' => 1, 'reason' => 'Clarify total'])->assertSessionHasNoErrors();
        $service = app(BudgetMonitoringService::class);
        $budget = DB::table('annual_budgets')->first();
        $this->assertSame('120000.00', $service->summary($budget)['remaining']);
        $this->actor('sk_chairman', 1, 2);
        $correction = ['project_id' => 1, 'description' => 'Corrected supplies', 'amount' => '25000.00', 'expense_date' => '2026-01-10', 'revision_no' => 1, 'reason' => 'Receipt corrected'];
        $this->post(route('sk_chairman.finances.expenses.update', 1), $correction + ['proof' => $this->pdf('corrected.pdf')])->assertSessionHasNoErrors();
        $this->assertSame('125000.00', $service->summary($budget)['remaining']);
        $this->assertSame(2, DB::table('financial_attachments')->count());
        $this->post(route('sk_chairman.finances.expenses.update', 1), $correction + ['proof' => $this->pdf('stale.pdf')])->assertSessionHasErrors('revision_no');
        $this->actor();
        $this->patch(route('sk_pres.finances.expenses.status', 1), ['status' => 'verified', 'revision_no' => 2, 'reason' => 'Receipt checked'])->assertForbidden();
        $this->assertEquals(25000, $service->summary($budget)['expenses']);
        $this->actor('sk_chairman', 1, 2);
        $this->patch(route('sk_chairman.finances.expenses.status', 1), ['status' => 'cancelled', 'revision_no' => 2, 'reason' => 'Cancelled'])->assertSessionHasNoErrors();
        $this->assertSame('150000.00', $service->summary($budget)['remaining']);
        $this->assertSame(1, DB::table('project_expenses')->count());
    }

    public function test_liquidation_reports_reference_one_expense_without_duplicate_deduction(): void
    {
        $this->prepareProject();
        $this->post(route('sk_chairman.finances.expenses.store'), $this->expenseData() + ['proof' => $this->pdf('receipt.pdf')])->assertSessionHasNoErrors();
        foreach ([1 => 'Monthly report', 2 => 'Quarterly report'] as $id => $title) {
            DB::table('budget_reports')->insert(['budget_report_id' => $id, 'barangay_id' => 1, 'term_id' => 1, 'fiscal_year' => 2026, 'budget_category' => 'coa_report', 'status' => 'recorded', 'title' => $title, 'budget_period_type' => $id === 1 ? 'monthly' : 'quarterly', 'fiscal_month' => 1, 'fiscal_quarter' => 'Q1']);
            $this->post(route('sk_chairman.finances.reports.link', $id), ['expense_ids' => [1]])->assertSessionHasNoErrors();
        }
        $this->assertSame(2, DB::table('budget_report_expenses')->count());
        $this->assertSame('120000.00', app(BudgetMonitoringService::class)->summary(DB::table('annual_budgets')->first())['remaining']);
        DB::table('submission_quality_reviews')->insert(['source_type' => 'budget_report', 'source_id' => 1, 'status' => 'approved']);
        $this->post(route('sk_chairman.finances.reports.link', 1), ['expense_ids' => [1]])->assertSessionHasErrors('report');
        $this->actor('sk_chairman', 2, 3);
        $this->post(route('sk_chairman.finances.reports.link', 2), ['expense_ids' => [1]])->assertNotFound();
    }

    public function test_public_reported_expenses_are_automatic_but_allocation_and_proof_need_authorization(): void
    {
        $this->prepareProject();
        $this->post(route('sk_chairman.finances.expenses.store'), $this->expenseData() + ['proof' => $this->pdf('receipt-private.pdf')])->assertSessionHasNoErrors();
        auth()->logout();
        $this->get(route('public.budgets', ['year' => 2026]))->assertOk()->assertSee('30,000.00')->assertDontSee('150,000.00')->assertDontSee('receipt-private.pdf');
        $this->getJson(route('public.budgets.summary', 1))->assertOk()->assertJsonPath('allotted', null)->assertJsonPath('remaining_estimate', null)->assertJsonPath('reported_expenses', '30000.00')->assertJsonPath('expenses.0.proof_url', null)->assertDontSee('storage_path')->assertDontSee('Receipt 123');
        $this->get(route('public.budgets.proof', 1))->assertNotFound();
        $this->actor();
        $this->post(route('sk_pres.finances.publish', 1))->assertSessionHasNoErrors();
        auth()->logout();
        $this->getJson(route('public.budgets.summary', 1))->assertOk()->assertJsonPath('allotted', '150000.00')->assertJsonPath('remaining_estimate', '120000.00')->assertJsonPath('itemized_available', true);
    }

    public function test_public_snapshots_require_current_authorization_and_preserve_year_and_pagination(): void
    {
        $service = app(BudgetMonitoringService::class);
        $actor = $this->actor();
        $service->allocate($this->allocation(), $actor);
        auth()->logout();
        $this->getJson(route('public.budgets.summary', 1))->assertNotFound();
        DB::table('budget_reports')->insert(['barangay_id' => 1, 'term_id' => 1, 'fiscal_year' => 2026,
            'budget_category' => 'annual_budget', 'status' => 'submitted', 'title' => 'Legacy',
            'total_amount' => 999999, 'published_by' => 1, 'published_at' => now()]);
        $this->get(route('public.budgets', ['year' => 2026]))->assertOk()->assertSee('Financial Summary Not Available')->assertDontSee('999,999.00');
        $service->publishSummary(1, $actor);
        $this->getJson(route('public.budgets.summary', 1))->assertOk()->assertJsonPath('reported_expenses', '0.00');
        $event = DB::table('financial_events')->where('event_type', 'summary_published')->first();
        $snapshot = json_decode($event->after_snapshot, true);
        $snapshot['private_payee'] = 'PRIVATE PAYEE'; $snapshot['storage_path'] = 'financial/expenses/secret.pdf';
        DB::table('financial_events')->where('financial_event_id', $event->financial_event_id)->update(['after_snapshot' => json_encode($snapshot)]);
        $this->getJson(route('public.budgets.summary', 1))->assertOk()->assertDontSee('PRIVATE PAYEE')->assertDontSee('secret.pdf')->assertJsonCount(12);
        $this->get(route('public.budgets', ['year' => 2027]))->assertOk()->assertSee('FY 2027')->assertDontSee('150,000.00');
        foreach (range(3, 12) as $id) DB::table('barangays')->insert(['barangay_id' => $id, 'barangay_name' => 'Zone '.$id]);
        $this->get(route('public.budgets', ['year' => 2026, 'budget_page' => 2]))->assertOk()->assertViewHas('budgetItems', fn ($items) => $items->count() === 3 && $items->total() === 12);
        DB::table('annual_budgets')->where('annual_budget_id', 1)->update(['published_at' => null, 'published_by' => null]);
        $this->getJson(route('public.budgets.summary', 1))->assertNotFound();
        $this->get(route('public.budgets', ['year' => 2026]))->assertDontSee('150,000.00');
    }

    public function test_expense_ownership_amount_date_and_proof_validation(): void
    {
        $this->prepareProject();
        $data = $this->expenseData();
        $this->post(route('sk_chairman.finances.expenses.store'), array_replace($data, ['amount' => '0', 'proof' => UploadedFile::fake()->create('proof.txt', 1, 'text/plain')]))->assertSessionHasErrors(['amount', 'proof']);
        $this->post(route('sk_chairman.finances.expenses.store'), array_replace($data, ['expense_date' => '2025-01-01', 'proof' => $this->pdf('proof.pdf')]))->assertSessionHasErrors('expense_date');
        $this->assertSame(0, DB::table('project_expenses')->count());
        $this->post(route('sk_chairman.finances.expenses.store'), $data + ['proof' => $this->pdf('proof.pdf')])->assertSessionHasNoErrors();
        $this->actor('sk_chairman', 1, 99);
        $this->post(route('sk_chairman.finances.expenses.update', 1), $data + ['revision_no' => 1, 'reason' => 'Attempt', 'proof' => $this->pdf('proof.pdf')])->assertNotFound();
        $this->patch(route('sk_chairman.finances.expenses.status', 1), ['revision_no' => 1, 'status' => 'cancelled', 'reason' => 'Attempt'])->assertForbidden();
        $this->patch(route('sk_pres.finances.expenses.status', 1), ['revision_no' => 1, 'status' => 'verified', 'reason' => 'Attempt'])->assertForbidden();
        $this->actor('sk_chairman', 2, 3);
        $this->post(route('sk_chairman.finances.projects.store', 1), ['name' => 'Other project', 'category' => 'Other', 'planned_amount' => '0.00'])->assertNotFound();
    }

    public function test_drafts_voids_and_cancelled_expenses_are_excluded_from_monitoring(): void
    {
        $this->prepareProject();
        $this->post(route('sk_chairman.finances.expenses.store'), $this->expenseData() + ['proof' => $this->pdf('proof.pdf')])->assertSessionHasNoErrors();
        $expense = (array) DB::table('project_expenses')->first();
        unset($expense['expense_id']);
        foreach (['draft', 'cancelled', 'voided'] as $status) {
            DB::table('project_expenses')->insert(array_replace($expense, ['status' => $status, 'expense_reference' => $status, 'client_request_id' => (string) Str::uuid(), 'amount' => '10000.00']));
        }
        $summary = app(BudgetMonitoringService::class)->summary(DB::table('annual_budgets')->first());
        $this->assertSame('120000.00', $summary['remaining']);
        $this->assertSame('30000.00', $summary['pending']);
        $this->get(route('sk_chairman.finances', ['year' => 2026]))->assertOk()->assertSee('120,000.00');
    }

    public function test_monitoring_focuses_selected_barangay_and_recovers_invalid_expense_form(): void
    {
        $this->prepareProject();
        $this->actor();
        $this->post(route('sk_pres.finances.store'), array_replace($this->allocation(), ['barangay_id' => 2, 'amount' => '99000.00']))
            ->assertRedirect(route('sk_pres.finances', ['year' => 2026, 'barangay_id' => 2]));
        $response = $this->get(route('sk_pres.finances', ['year' => 2026, 'barangay_id' => 2]))->assertOk();
        $document = new \DOMDocument();
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame(1, $xpath->query('//select[@name="barangay_id"]/option[@value="2" and @selected]')->length);
        $this->assertSame(1, $xpath->query('//table[contains(@class,"budget-table")]/tbody/tr')->length);
        $this->actor('sk_chairman', 1, 2);
        $url = route('sk_chairman.finances', ['year' => 2026]);
        $this->from($url)->post(route('sk_chairman.finances.expenses.store'), $this->expenseData() + ['_dialog' => 'create-expense', '_barangay' => 1])
            ->assertSessionHasErrors('proof')->assertRedirect($url);
        $response = $this->get($url)->assertOk();
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame('30000.00', $xpath->query('//dialog[@id="create-expense"]//input[@name="amount"]')->item(0)->getAttribute('value'));
        $this->assertStringContainsString('Please select the file again', $response->getContent());
        $this->assertSame(0, DB::table('project_expenses')->count());
    }

    public function test_inline_project_category_creation_is_atomic_and_idempotent(): void
    {
        app(BudgetMonitoringService::class)->allocate($this->allocation(), $this->actor());
        $this->actor('sk_chairman', 1, 2);
        $data = array_replace($this->expenseData(), ['project_id' => 'new', 'annual_budget_id' => 1,
            'project_name' => 'School supplies', 'category' => 'new', 'new_category' => '  Youth   Learning  ', 'planned_amount' => '40000.00']);
        $this->post(route('sk_chairman.finances.expenses.store'), $data + ['proof' => $this->pdf()])->assertSessionHasNoErrors();
        $this->post(route('sk_chairman.finances.expenses.store'), $data + ['proof' => $this->pdf()])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('budget_projects', 1);
        $this->assertDatabaseCount('project_expenses', 1);
        $this->assertDatabaseHas('budget_projects', ['category' => 'Youth Learning', 'planned_amount' => 40000, 'implementation_status' => 'proposed']);
        $this->assertContains('Youth Learning', app(\App\Services\FinancialCategoryService::class)->options()->all());
        $data['client_request_id'] = (string) Str::uuid(); $data['new_category'] = 'youth learning';
        $this->post(route('sk_chairman.finances.expenses.store'), $data + ['proof' => $this->pdf()])->assertSessionHasNoErrors();
        $this->assertSame(1, app(\App\Services\FinancialCategoryService::class)->options()->filter(fn($name) => mb_strtolower($name) === 'youth learning')->count());
        $this->assertSame('90000.00', app(BudgetMonitoringService::class)->summary(DB::table('annual_budgets')->first())['remaining']);
    }

    public function test_inline_project_can_be_created_without_a_planned_budget(): void
    {
        app(BudgetMonitoringService::class)->allocate($this->allocation(), $this->actor());
        $this->actor('sk_chairman', 1, 2);
        $data = array_replace($this->expenseData(), ['project_id' => 'new', 'annual_budget_id' => 1,
            'project_name' => 'Youth activity', 'category' => 'Education']);
        $this->post(route('sk_chairman.finances.expenses.store'), $data + ['proof' => $this->pdf()])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('budget_projects', ['name' => 'Youth activity', 'planned_amount' => 0, 'implementation_status' => 'proposed']);
        $this->assertSame('120000.00', app(BudgetMonitoringService::class)->summary(DB::table('annual_budgets')->first())['remaining']);
        $this->get(route('sk_chairman.finances', ['year' => 2026]))->assertOk()->assertDontSee('Planned Project Budget');
    }

    public function test_inline_project_and_category_roll_back_when_proof_is_invalid(): void
    {
        app(BudgetMonitoringService::class)->allocate($this->allocation(), $this->actor());
        $actor = $this->actor('sk_chairman', 1, 2);
        $data = array_replace($this->expenseData(), ['project_id' => 'new', 'annual_budget_id' => 1,
            'project_name' => 'Rollback project', 'category' => 'new', 'new_category' => 'Rollback category', 'planned_amount' => '1000.00']);
        try {
            app(BudgetMonitoringService::class)->submitExpense($data, UploadedFile::fake()->createWithContent('bad.pdf', 'not a PDF'), $actor);
            $this->fail('Invalid proof should fail.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('proof', $exception->errors());
        }
        $this->assertDatabaseCount('budget_projects', 0);
        $this->assertNotContains('Rollback category', app(\App\Services\FinancialCategoryService::class)->options()->all());
        $this->assertDatabaseCount('project_expenses', 0);
        $this->assertDatabaseCount('financial_events', 1);
    }

    public function test_edit_without_replacement_retains_proof_and_public_totals_then_delete_revokes_it(): void
    {
        $this->prepareProject();
        $this->post(route('sk_chairman.finances.expenses.store'), $this->expenseData() + ['public_proof' => 1, 'proof' => $this->pdf()])->assertSessionHasNoErrors();
        $this->actor(); $this->post(route('sk_pres.finances.publish', 1))->assertSessionHasNoErrors(); auth()->logout();
        $this->get(route('public.budgets.proof', 1))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actor('sk_chairman', 1, 2);
        $data = array_replace($this->expenseData(), ['amount' => '20000.00', 'description' => 'Updated supplies', 'revision_no' => 1, 'public_proof' => 1]);
        $this->post(route('sk_chairman.finances.expenses.update', 1), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('project_expenses', 1);
        $this->assertDatabaseCount('financial_attachments', 2);
        $this->assertSame(DB::table('financial_attachments')->where('attachment_id', 1)->value('storage_path'), DB::table('financial_attachments')->where('attachment_id', 2)->value('storage_path'));
        $this->getJson(route('public.budgets.summary', 1))->assertOk()->assertJsonPath('reported_expenses', '20000.00')->assertJsonPath('remaining_estimate', '130000.00')->assertJsonPath('expenses.0.description', 'Updated supplies');
        $this->get(route('public.budgets.proof', 1))->assertNotFound();
        $this->get(route('public.budgets.proof', 2))->assertOk();
        $this->patch(route('sk_chairman.finances.expenses.status', 1), ['status' => 'cancelled', 'revision_no' => 2])->assertSessionHasNoErrors();
        $this->getJson(route('public.budgets.summary', 1))->assertJsonPath('reported_expenses', '0.00')->assertJsonPath('remaining_estimate', '150000.00')->assertJsonCount(0, 'expenses');
        $this->get(route('public.budgets.proof', 2))->assertNotFound();
        $this->assertDatabaseHas('annual_budgets', ['approved_amount' => 150000]);
        $this->assertDatabaseHas('financial_events', ['event_type' => 'expense_edited']);
        $this->assertDatabaseHas('financial_events', ['event_type' => 'expense_deleted']);
        $this->patch(route('sk_chairman.finances.expenses.status', 1), ['status' => 'cancelled', 'revision_no' => 3])->assertSessionHasErrors('revision_no');
        $this->assertSame(1, DB::table('financial_events')->where('event_type', 'expense_deleted')->count());
        $page = $this->get(route('sk_chairman.finances', ['year' => 2026, 'section' => 'expenses']))->assertOk();
        $xpath = $this->navigationDocument($page->getContent());
        $this->assertSame(0, $xpath->query('//table//button[@data-open-dialog="expense-1"]')->length);
        $this->actor();
        $this->get(route('sk_pres.finances', ['year' => 2026, 'section' => 'history']))->assertOk()->assertSee('Expense deleted')->assertDontSee('Audit values');
    }

    public function test_replacement_proof_is_private_unless_explicitly_authorized_and_other_barangay_cannot_edit(): void
    {
        $this->prepareProject();
        $this->post(route('sk_chairman.finances.expenses.store'), $this->expenseData() + ['public_proof' => 1, 'proof' => $this->pdf()])->assertSessionHasNoErrors();
        $this->post(route('sk_chairman.finances.expenses.update', 1), $this->expenseData() + ['revision_no' => 1, 'proof' => $this->pdf('replacement.pdf')])->assertSessionHasNoErrors();
        $this->get(route('public.budgets.proof', 1))->assertNotFound();
        $this->get(route('public.budgets.proof', 2))->assertNotFound();
        $this->assertDatabaseCount('financial_attachments', 2);
        $this->actor('sk_chairman', 2, 3);
        $this->post(route('sk_chairman.finances.expenses.update', 1), $this->expenseData() + ['revision_no' => 2])->assertNotFound();
        $this->patch(route('sk_chairman.finances.expenses.status', 1), ['status' => 'cancelled', 'revision_no' => 2])->assertForbidden();
    }

    public function test_allocation_needs_no_reference_and_new_ui_has_year_dropdown_and_transparency_tab(): void
    {
        $this->actor(); $data = $this->allocation(); unset($data['approval_reference']);
        $this->post(route('sk_pres.finances.store'), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('annual_budgets', ['approval_reference' => null, 'approved_amount' => 150000]);
        $page = $this->get(route('sk_pres.finances', ['year' => 2026, 'section' => 'transparency']))->assertOk()->assertSee('Supporting Document Visibility');
        $xpath = $this->navigationDocument($page->getContent());
        $this->assertSame(1, $xpath->query('//select[@name="year"]/option[@value="2026" and @selected]')->length);
        $this->assertSame(0, $xpath->query('//input[@name="approval_reference"]')->length);
    }

    public function test_categories_use_existing_projects_without_registry_or_historical_rewrites(): void
    {
        $this->prepareProject();
        $original = DB::table('budget_projects')->where('project_id', 1)->first();
        foreach (['  Sports  ', 'SPORTS', '  Youth   Outreach ', 'youth outreach'] as $name) {
            DB::table('budget_projects')->insert(array_replace((array)$original, ['project_id' => null, 'project_code' => 'PRJ-'.Str::uuid(), 'category' => $name]));
        }
        $categories = app(\App\Services\FinancialCategoryService::class);
        $this->assertSame(1, $categories->options()->filter(fn($name) => mb_strtolower($name) === 'sports')->count());
        $this->assertSame(1, $categories->options()->filter(fn($name) => mb_strtolower($name) === 'youth outreach')->count());
        $this->assertSame('Education', $categories->resolve('  EDUCATION  '));
        $this->assertDatabaseHas('budget_projects', ['category' => '  Youth   Outreach ']);
        $this->assertFalse(Schema::hasTable('financial_categories'));
        $this->actor();
        $this->get(route('sk_pres.finances', ['year' => 2026]))->assertOk()->assertViewHas('financialSchemaReady', true);
    }

    public function test_public_project_totals_use_project_identity_and_follow_edits_and_deletions(): void
    {
        $this->prepareProject();
        $this->post(route('sk_chairman.finances.expenses.store'), $this->expenseData() + ['proof' => $this->pdf()])->assertSessionHasNoErrors();
        $project = (array)DB::table('budget_projects')->first();
        DB::table('budget_projects')->insert(array_replace($project, ['project_id' => 2, 'project_code' => 'ANOTHER-PROJECT']));
        $this->post(route('sk_chairman.finances.expenses.store'), array_replace($this->expenseData(), ['project_id' => 2, 'amount' => '10000.00']) + ['proof' => $this->pdf()])->assertSessionHasNoErrors();
        $this->post(route('sk_chairman.finances.expenses.store'), array_replace($this->expenseData(), ['amount' => '20000.00']) + ['proof' => $this->pdf()])->assertSessionHasNoErrors();
        $response = $this->getJson(route('public.budgets.summary', 1))->assertOk()->assertJsonCount(2, 'projects')->assertJsonPath('reported_expenses', '60000.00');
        $totals = collect($response->json('projects'))->keyBy('project_id');
        $this->assertSame('50000.00', $totals[1]['reported_expenses']);
        $this->assertSame('10000.00', $totals[2]['reported_expenses']);
        $this->assertSame($totals[1]['name'], $totals[2]['name']);
        $this->post(route('sk_chairman.finances.expenses.update', 2), array_replace($this->expenseData(), ['amount' => '10000.00', 'revision_no' => 1]))->assertSessionHasNoErrors();
        $this->getJson(route('public.budgets.summary', 1))->assertJsonCount(1, 'projects')->assertJsonPath('projects.0.reported_expenses', '60000.00');
        $this->patch(route('sk_chairman.finances.expenses.status', 1), ['status' => 'cancelled', 'revision_no' => 1])->assertSessionHasNoErrors();
        $this->getJson(route('public.budgets.summary', 1))->assertJsonPath('projects.0.reported_expenses', '30000.00')->assertJsonPath('projects.0.expense_count', 2);
        $this->assertDatabaseCount('budget_projects', 2);
    }

    public function test_financial_history_displays_actor_names_instead_of_user_ids(): void
    {
        $this->prepareProject();
        DB::table('users')->insert([
            ['user_id' => 1, 'first_name' => 'Maria Clara', 'last_name' => 'De Leon'],
            ['user_id' => 2, 'first_name' => 'Juan Miguel', 'last_name' => 'Dela Cruz'],
        ]);
        $this->post(route('sk_chairman.finances.expenses.store'), $this->expenseData() + ['proof' => $this->pdf()])->assertSessionHasNoErrors();
        $this->actor();
        $this->get(route('sk_pres.finances', ['year' => 2026, 'section' => 'history']))->assertOk()
            ->assertSee('Maria Clara De Leon')->assertSee('Juan Miguel Dela Cruz')->assertDontSee('Official #');
    }

    private function startNextFinancialTerm(int $startYear = 2027): void
    {
        DB::table('administration_terms')->where('term_id', 1)->update(['status' => 'completed']);
        DB::table('administration_terms')->insert(['term_id'=>2,'status'=>'current','start_year'=>$startYear,'end_year'=>$startYear+3]);
    }

    public function test_term_transition_moves_finances_to_read_only_archive_and_blocks_old_writes(): void
    {
        $this->prepareProject();
        $this->post(route('sk_chairman.finances.expenses.store'), $this->expenseData() + ['proof'=>$this->pdf()])->assertSessionHasNoErrors();
        $this->startNextFinancialTerm();
        $this->get(route('sk_chairman.finances', ['year'=>2026]))->assertNotFound();
        $this->get(route('sk_chairman.finances'))->assertOk()->assertViewHas('budgets', fn($budgets)=>$budgets->isEmpty())
            ->assertViewHas('fiscalYears', fn($years)=>!$years->contains(2026));
        $this->post(route('sk_chairman.finances.expenses.store'), $this->expenseData()+['proof'=>$this->pdf()])->assertNotFound();
        $this->post(route('sk_chairman.finances.expenses.update', 1), $this->expenseData()+['revision_no'=>1])->assertNotFound();
        $this->post(route('sk_chairman.finances.projects.store', 1), ['name'=>'Forbidden old project','category'=>'Health','planned_amount'=>'0.00'])->assertNotFound();
        $this->patch(route('sk_chairman.finances.expenses.status', 1), ['status'=>'cancelled','revision_no'=>1])->assertNotFound();
        $this->get(route('finances.attachments', 1))->assertNotFound();
        $this->get(route('sk_chairman.archive.finances', 1))->assertOk()->assertSee('Sports supplies')->assertSee('Read-only preserved records')->assertDontSee('Edit Expense');
        $this->get(route('archive.financial-proof', 1))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actor();
        $this->patch(route('sk_pres.finances.update', 1), ['amount'=>'90000.00','revision_no'=>1,'reason'=>'Old term'])->assertNotFound();
        $this->post(route('sk_pres.finances.publish', 1))->assertNotFound();
        $this->get(route('sk_pres.archive.finances', 1))->assertOk()->assertSee('Sports supplies');
        $this->assertDatabaseHas('project_expenses', ['expense_id'=>1,'status'=>'submitted','revision_no'=>1]);
        $this->assertDatabaseCount('annual_budgets', 1);
    }

    public function test_current_deleted_expenses_are_archived_without_exposing_active_or_other_barangay_records(): void
    {
        $this->prepareProject();
        $this->post(route('sk_chairman.finances.expenses.store'), $this->expenseData()+['proof'=>$this->pdf()])->assertSessionHasNoErrors();
        $this->get(route('sk_chairman.archive.finances', 1))->assertNotFound();
        $this->get(route('archive.financial-proof', 1))->assertNotFound();
        $this->patch(route('sk_chairman.finances.expenses.status', 1), ['status'=>'cancelled','revision_no'=>1])->assertSessionHasNoErrors();
        $data = array_replace($this->expenseData(), ['description'=>'Still active expense']);
        $this->post(route('sk_chairman.finances.expenses.store'), $data+['proof'=>$this->pdf()])->assertSessionHasNoErrors();
        $this->get(route('sk_chairman.archive.finances', 1))->assertOk()->assertSee('Sports supplies')->assertSee('Deleted / excluded')->assertDontSee('Still active expense');
        $this->get(route('archive.financial-proof', 1))->assertOk();
        $this->get(route('archive.financial-proof', 2))->assertNotFound();
        $records = app(\App\Services\FinancialArchiveService::class)->records(auth()->user());
        $this->assertCount(1, $records);
        $this->actor('sk_chairman', 2, 3);
        $this->get(route('sk_chairman.archive.finances', 1))->assertNotFound();
        $this->get(route('archive.financial-proof', 1))->assertNotFound();
        $this->get(route('sk_pres.archive.finances', 1))->assertForbidden();
        $this->actor('sk_secretary');
        $this->get(route('archive.financial-proof', 1))->assertForbidden();
        auth()->logout();
        $this->get(route('archive.financial-proof', 1))->assertRedirect(route('login'));
    }

    public function test_archive_filters_and_new_term_allocation_do_not_reuse_old_same_year_budget(): void
    {
        $this->prepareProject();
        $this->startNextFinancialTerm(2026);
        $this->actor();
        $this->get(route('sk_pres.finances', ['year'=>2026]))->assertOk()->assertViewHas('budgets', fn($budgets)=>$budgets->isEmpty());
        $this->post(route('sk_pres.finances.store'), $this->allocation())->assertSessionHasErrors('allocation');
        $archive = app(\App\Services\FinancialArchiveService::class);
        $this->assertCount(1, $archive->records(auth()->user(), ['term_id'=>1,'year'=>'2026','barangay_id'=>1,'type'=>'finance']));
        $this->assertCount(0, $archive->records(auth()->user(), ['barangay_id'=>2]));
        $this->assertCount(0, $archive->records(auth()->user(), ['year'=>'2027']));
        $data = array_replace($this->allocation(), ['fiscal_year'=>2027]);
        $this->post(route('sk_pres.finances.store'), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('annual_budgets', ['fiscal_year'=>2027,'recorded_term_id'=>2]);
        $this->assertDatabaseHas('annual_budgets', ['fiscal_year'=>2026,'recorded_term_id'=>1]);
    }

    public function test_existing_archive_pages_include_financial_records_and_filter_by_administration(): void
    {
        DB::connection()->getPdo()->sqliteCreateFunction('YEAR', fn($date)=>(int)substr($date ?? '',0,4));
        Schema::create('accomplishment_reports', function (Blueprint $t) {
            $t->increments('report_id'); $t->integer('term_id'); $t->integer('barangay_id'); $t->string('title'); $t->string('report_type');
            $t->string('uploaded_file_path')->nullable(); $t->string('generated_pdf_path')->nullable(); $t->integer('reporting_year');
            $t->dateTime('submitted_at')->nullable(); $t->dateTime('created_at')->nullable();
        });
        Schema::create('events', function (Blueprint $t) {
            $t->increments('event_id'); $t->integer('term_id'); $t->string('title'); $t->string('event_type'); $t->string('visibility');
            $t->dateTime('start_datetime');
        });
        $this->prepareProject();
        $this->post(route('sk_chairman.finances.expenses.store'), $this->expenseData()+['proof'=>$this->pdf()])->assertSessionHasNoErrors();
        $this->patch(route('sk_chairman.finances.expenses.status', 1), ['status'=>'cancelled','revision_no'=>1])->assertSessionHasNoErrors();
        $this->get(route('sk_chairman.archive', ['type'=>'finance','term_id'=>1]))->assertOk()
            ->assertViewHas('financialArchives', fn($rows)=>$rows->count()===1)->assertSee('FINANCIAL RECORD');
        $this->startNextFinancialTerm(); $this->actor();
        $this->get(route('sk_pres.archive', ['type'=>'finance','term_id'=>1]))->assertOk()
            ->assertViewHas('financialArchives', fn($rows)=>$rows->count()===1)->assertSee('View proof')->assertSee('Download proof');
        $proofView = $this->get(route('archive.financial-proof', 1))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('inline', $proofView->headers->get('Content-Disposition'));
        $proofDownload = $this->get(route('archive.financial-proof.download', 1))->assertOk();
        $this->assertStringStartsWith('attachment', $proofDownload->headers->get('Content-Disposition'));

        Storage::disk('local')->makeDirectory('archive-public');
        $legacyPdf = $this->pdf();
        Storage::disk('local')->put('archive-public/annual.pdf', file_get_contents($legacyPdf->getRealPath()));
        $this->app->usePublicPath(Storage::disk('local')->path('archive-public'));
        DB::table('budget_reports')->insert(['budget_report_id'=>1,'barangay_id'=>1,'term_id'=>1,'fiscal_year'=>2026,
            'budget_category'=>'annual_budget','status'=>'submitted','title'=>'Archived annual PDF','uploaded_file_path'=>'annual.pdf','uploaded_file_name'=>'annual.pdf']);
        $legacyView = $this->get(route('sk_pres.archive.view', ['budget_report',1]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('inline', $legacyView->headers->get('Content-Disposition'));
        $legacyDownload = $this->get(route('sk_pres.archive.download', ['budget_report',1]))->assertOk();
        $this->assertStringStartsWith('attachment', $legacyDownload->headers->get('Content-Disposition'));
        $this->actor('sk_chairman', 1, 2);
        $this->get(route('sk_chairman.archive.view', ['budget_report',1]))->assertOk();
        $this->get(route('sk_chairman.archive.download', ['budget_report',1]))->assertOk();
        $this->actor();
        $this->get(route('sk_pres.archive', ['type'=>'finance','barangay_id'=>2]))->assertOk()
            ->assertViewHas('financialArchives', fn($rows)=>$rows->isEmpty());
        $this->actor('sk_chairman', 2, 3);
        $this->get(route('archive.financial-proof.download', 1))->assertNotFound();
        $this->get(route('sk_chairman.archive.view', ['budget_report',1]))->assertRedirect(route('sk_chairman.archive'));
        $this->get(route('sk_chairman.archive', ['type'=>'finance']))->assertOk()
            ->assertViewHas('financialArchives', fn($rows)=>$rows->isEmpty());
    }

    public function test_dashboard_budget_charts_use_finance_allocations_and_active_expenses_only(): void
    {
        $this->prepareProject();
        $this->post(route('sk_chairman.finances.expenses.store'), $this->expenseData()+['proof'=>$this->pdf()])->assertSessionHasNoErrors();
        DB::table('budget_reports')->insert(['budget_report_id'=>1,'barangay_id'=>1,'term_id'=>1,'fiscal_year'=>2026,
            'budget_category'=>'annual_budget','status'=>'submitted','title'=>'Legacy amount','total_amount'=>999999,'actual_expenditure'=>999999]);
        DB::table('budget_report_expenses')->insert([['budget_report_id'=>1,'expense_id'=>1,'created_at'=>now()],['budget_report_id'=>2,'expense_id'=>1,'created_at'=>now()]]);
        $inactive = (array)DB::table('project_expenses')->first(); unset($inactive['expense_id']);
        foreach (['draft','cancelled','voided'] as $status) DB::table('project_expenses')->insert(array_replace($inactive,
            ['client_request_id'=>(string)Str::uuid(),'expense_reference'=>(string)Str::uuid(),'status'=>$status,'amount'=>999999]));
        $controller = app(\App\Http\Controllers\sk_pres\DashboardController::class);
        $annual = new \ReflectionMethod($controller, 'annualBudgetByBarangayChartData');
        $utilization = new \ReflectionMethod($controller, 'budgetUtilizationChartData');
        $this->assertSame([150000.0], $annual->invoke($controller, 2026)['values']);
        $this->assertSame([20.0], $utilization->invoke($controller, 2026)['values']);
        app(BudgetMonitoringService::class)->correctAllocation(1, ['amount'=>'200000.00','revision_no'=>1,'reason'=>'Session correction'], $this->actor());
        $this->assertSame([200000.0], $annual->invoke($controller, 2026, 1)['values']);
        $this->assertSame([15.0], $utilization->invoke($controller, 2026, 1)['values']);
        $this->assertSame([0], $annual->invoke($controller, 2027)['values']);
        $this->assertSame([0], $utilization->invoke($controller, 2026, 2)['values']);
        $this->startNextFinancialTerm(2026);
        $this->assertSame([0], $annual->invoke($controller, 2026)['values']);
        $this->assertSame([0], $utilization->invoke($controller, 2026)['values']);
    }

    public function test_annual_and_annual_coa_uploads_no_longer_require_duplicate_amounts_for_either_role(): void
    {
        foreach ([1=>'annual_budget',2=>'coa_report'] as $slotId=>$category) {
            DB::table('submission_slots')->insert(['slot_id'=>$slotId,'term_id'=>1,'submission_type'=>'budget_report','role'=>'Both',
                'status'=>'open','title'=>'Annual '.$category,'budget_category'=>$category,'budget_period_type'=>'annual','fiscal_year'=>2026,
                'start_date'=>now()->subDay()->toDateString(),'end_date'=>now()->addDay()->toDateString()]);
        }
        $this->mock(\App\Services\NotificationService::class, fn($mock)=>$mock->shouldReceive('notifySubmissionReceived')->andReturnNull());
        Storage::fake('annual-submission-public');
        $original = public_path();
        try {
            app()->usePublicPath(Storage::disk('annual-submission-public')->path(''));
            foreach (['sk_chairman','sk_secretary'] as $role) {
                $this->actor($role, 1, $role==='sk_chairman' ? 2 : 3);
                app()->usePublicPath($original);
                $this->get(route($role.'.budget'))->assertOk()->assertDontSee('name="annual_budget_amount"', false)->assertDontSee('name="actual_expenditure"', false);
                app()->usePublicPath(Storage::disk('annual-submission-public')->path(''));
                foreach ([1,2] as $slotId) $this->post(route($role.'.budget.store'),
                    ['slot_id'=>$slotId,'sub_method'=>'pdf','report_file'=>$this->pdf('annual-'.$role.'-'.$slotId.'.pdf')])->assertSessionHasNoErrors();
            }
            $this->assertDatabaseHas('budget_reports', ['slot_id'=>1,'total_amount'=>0]);
            $this->assertDatabaseHas('budget_reports', ['slot_id'=>2,'actual_expenditure'=>null]);
            foreach (['sk_chairman','sk_secretary'] as $role) {
                $this->actor($role, 1, $role==='sk_chairman' ? 2 : 3);
                app()->usePublicPath($original);
                $this->get(route($role.'.budget.template.create', ['slot_id'=>2]))->assertOk()->assertDontSee('name="actual_expenditure"', false);
                $this->post(route($role.'.budget.template.store'), ['slot_id'=>2,'rows'=>[['particulars'=>'Detailed report row','total_amount'=>'123.45']]])->assertSessionHasNoErrors();
            }
            $this->assertDatabaseHas('budget_reports', ['slot_id'=>2,'total_amount'=>123.45,'actual_expenditure'=>null,'submission_method'=>'direct_input']);
        } finally { app()->usePublicPath($original); }
    }

    private function navigationDocument(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        @$document->loadHTML($html);
        return new \DOMXPath($document);
    }

    public function test_president_and_chairman_have_one_active_financial_sidebar_entry(): void
    {
        foreach (['sk_president' => 'sk_pres', 'sk_chairman' => 'sk_chairman'] as $role => $prefix) {
            $this->actor($role);
            $response = $this->get(route($prefix.'.finances'))->assertOk();
            $xpath = $this->navigationDocument($response->getContent());
            $label = $role === 'sk_president' ? 'Budget & Finance' : 'Budget';
            $this->assertSame(1, $xpath->query('//aside//a[normalize-space(.)="'.$label.'"]')->length);
            $this->assertSame(0, $xpath->query('//aside//a[normalize-space(.)="Budget Monitoring"]')->length);
            $this->assertSame(route($prefix.'.finances'), $xpath->query('//aside//a[@aria-current="page"]')->item(0)->getAttribute('href'));
            $this->assertSame(1, $xpath->query('//nav[@aria-label="'.$label.' sections"]//a[@aria-current="page" and normalize-space(.)="Overview"]')->length);
            $this->assertSame($role === 'sk_president' ? 5 : 4, $xpath->query('//nav[@aria-label="'.$label.' sections"]//a')->length);
            if ($role === 'sk_president') {
                foreach (['Consolidation', 'Module Management'] as $label) $this->assertSame(1, $xpath->query('//aside//a[normalize-space(.)="'.$label.'"]')->length);
            } else {
                $this->assertSame(route('sk_chairman.budget', ['year' => now()->year]), $xpath->query('//nav[@aria-label="Budget sections"]//a[normalize-space(.)="Budget Submissions"]')->item(0)->getAttribute('href'));
            }
        }
    }

    public function test_financial_section_links_show_existing_panels_without_changing_data(): void
    {
        app(BudgetMonitoringService::class)->allocate($this->allocation(), $this->actor());
        foreach (['overview', 'allocations', 'expenses', 'history'] as $section) {
            $response = $this->get(route('sk_pres.finances', ['year' => 2026, 'section' => $section]))->assertOk();
            $xpath = $this->navigationDocument($response->getContent());
            $this->assertSame(1, $xpath->query('//section[@id="panel-'.$section.'" and not(@hidden)]')->length);
            $this->assertSame(1, $xpath->query('//nav[@aria-label="Budget & Finance sections"]//a[@aria-current="page"]')->length);
        }
        $this->assertSame(1, DB::table('annual_budgets')->count());
        $this->assertSame(1, DB::table('financial_events')->count());
        $this->assertEquals(150000, DB::table('annual_budgets')->value('approved_amount'));
    }

    public function test_legacy_budget_urls_and_secretary_permissions_are_preserved(): void
    {
        $this->actor('sk_chairman');
        $response = $this->get('/sk_chairman/budget')->assertOk()->assertSee('Budget Submissions');
        $xpath = $this->navigationDocument($response->getContent());
        $this->assertSame(1, $xpath->query('//aside//a[@aria-current="page" and normalize-space(.)="Budget"]')->length);
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Budget sections"]//a[@aria-current="page" and normalize-space(.)="Budget Submissions"]')->length);
        $this->assertSame(1, $xpath->query('//aside[@id="skSidebar"]')->length);
        $this->assertSame(1, $xpath->query('//h1')->length);
        $this->assertSame(1, $xpath->query('//*[@id="slotSubmissionModal"]')->length);
        $this->get(route('sk_chairman.budget.template.create', ['slot_id' => 999]))->assertRedirect(route('sk_chairman.budget'));
        $this->actor('sk_secretary');
        $response = $this->get('/sk_secretary/budget')->assertOk()->assertSee('Budget Submissions');
        $xpath = $this->navigationDocument($response->getContent());
        $this->assertSame(1, $xpath->query('//aside//a[@aria-current="page" and normalize-space(.)="Budget"]')->length);
        $this->assertSame(0, $xpath->query('//aside//a[normalize-space(.)="Budget & Finance"]')->length);
        $this->get(route('sk_chairman.finances'))->assertForbidden();
        $this->get(route('sk_pres.finances'))->assertForbidden();
        $this->post(route('sk_pres.finances.store'), $this->allocation())->assertForbidden();
        $this->post(route('sk_chairman.finances.expenses.store'), $this->expenseData())->assertForbidden();
        $this->actor('sk_president');
        $this->get('/sk_chairman/budget')->assertForbidden();
        $this->get('/sk_secretary/budget')->assertForbidden();
    }

    public function test_proofs_are_inline_and_reject_missing_or_disguised_files(): void
    {
        $this->prepareProject();
        $this->post(route('sk_chairman.finances.expenses.store'), $this->expenseData() + ['proof' => $this->pdf()])->assertSessionHasNoErrors();
        $response = $this->get(route('finances.attachments', 1))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('inline;', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('public', $response->headers->get('Cache-Control'));
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $attachment = DB::table('financial_attachments')->first();
        Storage::disk('local')->put($attachment->storage_path, '<html><script>alert(1)</script></html>');
        $this->get(route('finances.attachments', 1))->assertStatus(415);
        Storage::disk('local')->delete($attachment->storage_path);
        $this->get(route('finances.attachments', 1))->assertNotFound();
        $this->get(route('finances.attachments', 999))->assertNotFound();
    }

    public function test_png_proof_is_inline_and_available_in_both_chairman_expense_views(): void
    {
        $this->prepareProject();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVR4nGM4oWwGAALYASINjlEXAAAAAElFTkSuQmCC');
        $this->post(route('sk_chairman.finances.expenses.store'), $this->expenseData() + ['proof' => UploadedFile::fake()->createWithContent('receipt.png', $png)])->assertSessionHasNoErrors();
        $response = $this->get(route('finances.attachments', 1))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertStringStartsWith('inline;', $response->headers->get('Content-Disposition'));
        foreach (['overview', 'expenses'] as $section) {
            $page = $this->get(route('sk_chairman.finances', ['year' => 2026, 'section' => $section]))->assertOk()->assertSee('View Proof')->assertDontSee('financial/expenses/');
            $xpath = $this->navigationDocument($page->getContent());
            $this->assertSame(1, $xpath->query('//dialog[@id="expense-proof"]')->length);
            $this->assertSame(1, $xpath->query('//iframe[@id="proof-pdf" and @title]')->length);
            $this->assertSame(1, $xpath->query('//img[@id="proof-image" and @alt]')->length);
        }
        $this->actor();
        $this->get(route('sk_pres.finances', ['year' => 2026, 'section' => 'expenses']))->assertOk()->assertSee('View Proof')->assertDontSee('Verify expense')->assertDontSee('Add Expense');
    }

    public function test_president_all_barangays_overview_aggregates_existing_summaries(): void
    {
        $this->prepareProject();
        $this->post(route('sk_chairman.finances.expenses.store'), $this->expenseData() + ['proof' => $this->pdf()])->assertSessionHasNoErrors();
        app(BudgetMonitoringService::class)->allocate(array_replace($this->allocation(), ['barangay_id' => 2, 'amount' => '200000.00']), $this->actor());
        $response = $this->get(route('sk_pres.finances', ['year' => 2026]))->assertOk()->assertSee('350,000.00')->assertSee('320,000.00')->assertSee('All Barangays');
        $xpath = $this->navigationDocument($response->getContent());
        $this->assertSame(2, $xpath->query('//table[contains(@class,"budget-table")]/tbody/tr')->length);
        foreach (['allocations', 'expenses', 'history'] as $section) {
            $response = $this->get(route('sk_pres.finances', ['year' => 2026, 'section' => $section]))->assertOk();
            $xpath = $this->navigationDocument($response->getContent());
            $ids = [];
            foreach ($xpath->query('//*[@id]') as $element) $ids[] = $element->getAttribute('id');
            $this->assertSame(count($ids), count(array_unique($ids)), 'Dialog and form IDs must be unique across barangays.');
        }
        $this->get(route('sk_pres.finances', ['year' => 2026, 'barangay_id' => 2]))->assertOk()->assertSee('200,000.00')->assertDontSee('350,000.00');
    }

    public function test_chairman_submissions_embed_once_and_existing_upload_endpoint_remains_functional(): void
    {
        $this->actor('sk_chairman');
        foreach ([route('sk_chairman.budget'), route('sk_chairman.finances', ['section' => 'submissions'])] as $url) {
            $response = $this->get($url)->assertOk()->assertSee('Active Slots')->assertSee('Pending Review');
            $xpath = $this->navigationDocument($response->getContent());
            $this->assertSame(1, $xpath->query('//aside[@id="skSidebar"]')->length);
            $this->assertSame(1, $xpath->query('//h1[normalize-space(.)="Budget"]')->length);
            $this->assertSame(1, $xpath->query('//*[@id="slotSubmissionModal"]')->length);
            $this->assertSame(1, substr_count($response->getContent(), 'let currentBudgetCategory'));
            $this->assertSame(1, $xpath->query('//*[@id="submissionSearch"]')->length);
            $this->assertSame(1, $xpath->query('//form[@id="budgetFilterForm"]//input[@name="section" and @value="submissions"]')->length);
        }
        DB::table('submission_slots')->insert(['slot_id' => 1, 'term_id' => 1, 'submission_type' => 'budget_report', 'role' => 'Both', 'status' => 'open', 'title' => 'Annual Budget', 'budget_category' => 'annual_budget', 'fiscal_year' => 2026, 'start_date' => now()->subDay()->toDateString(), 'end_date' => now()->addDay()->toDateString()]);
        $this->mock(\App\Services\NotificationService::class, fn ($mock) => $mock->shouldReceive('notifySubmissionReceived')->once()->andReturnNull());
        Storage::fake('submission-public');
        $originalPublicPath = public_path();
        try {
            app()->usePublicPath(Storage::disk('submission-public')->path(''));
            $this->post(route('sk_chairman.budget.store'), ['slot_id' => 1, 'sub_method' => 'pdf', 'annual_budget_amount' => '150000.00', 'report_file' => $this->pdf('annual.pdf')])->assertSessionHasNoErrors()->assertRedirect(route('sk_chairman.budget'));
        } finally { app()->usePublicPath($originalPublicPath); }
        $this->assertSame(1, DB::table('budget_reports')->count());
        $this->assertSame(0, DB::table('annual_budgets')->count());
        $this->get(route('sk_chairman.budget'))->assertOk()->assertSee('Annual Budget');
    }
}
