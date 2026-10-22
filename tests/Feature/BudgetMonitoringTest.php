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
        $this->withoutMiddleware(\App\Http\Middleware\EnsureAccountIsActive::class);
        Schema::create('barangays', function (Blueprint $t) { $t->integer('barangay_id')->primary(); $t->string('barangay_name'); });
        Schema::create('administration_terms', function (Blueprint $t) { $t->integer('term_id')->primary(); $t->string('status'); });
        Schema::create('annual_budgets', function (Blueprint $t) {
            $t->bigIncrements('annual_budget_id'); $t->integer('barangay_id'); $t->integer('recorded_term_id'); $t->integer('fiscal_year');
            $t->decimal('approved_amount', 15, 2); $t->date('approved_on'); $t->string('approval_reference'); $t->string('status');
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
            $t->bigIncrements('attachment_id'); $t->bigInteger('expense_id'); $t->integer('owner_revision_no');
            foreach (['document_kind', 'storage_disk', 'storage_path', 'original_name', 'mime_type', 'sha256'] as $column) $t->string($column);
            $t->bigInteger('size_bytes'); $t->integer('uploaded_by'); $t->dateTime('created_at');
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
        $this->post(route('sk_chairman.finances.projects.store', 1), ['name' => 'Sports program', 'category' => 'Sports'])->assertSessionHasNoErrors();
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
        $this->patch(route('sk_pres.finances.expenses.status', 1), ['status' => 'verified', 'revision_no' => 2, 'reason' => 'Receipt checked'])->assertSessionHasNoErrors();
        $this->assertEquals(25000, $service->summary($budget)['verified']);
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

    public function test_public_summary_requires_authorization_and_excludes_pending_expenses_and_proofs(): void
    {
        $this->prepareProject();
        $this->post(route('sk_chairman.finances.expenses.store'), $this->expenseData() + ['proof' => $this->pdf('receipt-private.pdf')])->assertSessionHasNoErrors();
        auth()->logout();
        $this->get(route('public.budgets', ['year' => 2026]))->assertOk()->assertDontSee('150,000.00');
        $this->actor();
        $this->post(route('sk_pres.finances.publish', 1))->assertSessionHasNoErrors();
        auth()->logout();
        $this->get(route('public.budgets', ['year' => 2026]))->assertOk()->assertSee('150,000.00')->assertDontSee('30,000.00')->assertDontSee('receipt-private.pdf')->assertDontSee('Receipt 123');
        $this->actor();
        $this->patch(route('sk_pres.finances.expenses.status', 1), ['status' => 'verified', 'revision_no' => 1, 'reason' => 'Proof checked']);
        $this->getJson(route('public.budgets.summary', 1))->assertOk()->assertJsonPath('verified_expenses', '0');
        $this->post(route('sk_pres.finances.publish', 1))->assertSessionHasNoErrors();
        auth()->logout();
        $this->get(route('public.budgets', ['year' => 2026]))->assertOk()->assertSee('30,000.00')->assertDontSee('receipt-private.pdf');
        $this->getJson(route('public.budgets.summary', 1))->assertOk()->assertJsonPath('remaining_after_verified', '120000.00')->assertJsonPath('itemized_available', false)->assertDontSee('storage_path')->assertDontSee('Receipt 123');
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
        $this->getJson(route('public.budgets.summary', 1))->assertOk()->assertJsonPath('verified_expenses', '0');
        $event = DB::table('financial_events')->where('event_type', 'summary_published')->first();
        $snapshot = json_decode($event->after_snapshot, true);
        $snapshot['private_payee'] = 'PRIVATE PAYEE'; $snapshot['storage_path'] = 'financial/expenses/secret.pdf';
        DB::table('financial_events')->where('financial_event_id', $event->financial_event_id)->update(['after_snapshot' => json_encode($snapshot)]);
        $this->getJson(route('public.budgets.summary', 1))->assertOk()->assertDontSee('PRIVATE PAYEE')->assertDontSee('secret.pdf')->assertJsonCount(9);
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
        $this->post(route('sk_chairman.finances.projects.store', 1), ['name' => 'Other project', 'category' => 'Other'])->assertNotFound();
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
            $this->assertSame(4, $xpath->query('//nav[@aria-label="'.$label.' sections"]//a')->length);
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
        $this->get(route('sk_pres.finances', ['year' => 2026, 'section' => 'expenses']))->assertOk()->assertSee('View Proof')->assertSee('Review')->assertDontSee('Add Expense');
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
