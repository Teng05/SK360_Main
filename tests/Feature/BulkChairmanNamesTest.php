<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BulkChairmanNamesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->withoutMiddleware(\App\Http\Middleware\EnsureAccountIsActive::class);
        Mail::fake();
        // DNS is simulated; these tests never depend on public DNS or real mailboxes.
        $this->mock(\App\Services\EmailDomainService::class, function ($mock) {
            $mock->shouldReceive('acceptsMail')->andReturnUsing(fn ($domain) => $domain === 'example.test');
        });
        Schema::create('administration_terms', function (Blueprint $t) {
            $t->increments('term_id'); $t->string('status'); $t->integer('start_year'); $t->integer('end_year');
        });
        Schema::create('barangays', function (Blueprint $t) { $t->increments('barangay_id'); });
        Schema::create('users', function (Blueprint $t) {
            $t->increments('user_id');
            foreach (['first_name', 'last_name', 'email', 'phone_number', 'password', 'role', 'status'] as $field) $t->string($field)->nullable();
            $t->integer('barangay_id'); $t->boolean('is_verified');
            foreach (['term_start', 'term_end', 'archived_at'] as $field) $t->dateTime($field)->nullable();
        });
        Schema::create('official_terms', function (Blueprint $t) {
            foreach (['user_id', 'term_id', 'barangay_id'] as $field) $t->integer($field);
            $t->string('role'); $t->string('status'); $t->dateTime('started_at')->nullable(); $t->dateTime('completed_at')->nullable();
        });
        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email')->primary(); $t->string('token'); $t->dateTime('created_at');
        });
        DB::table('administration_terms')->insert(['status' => 'current', 'start_year' => 2028, 'end_year' => 2031]);
        DB::table('barangays')->insert([['barangay_id' => 1], ['barangay_id' => 2]]);
        $this->actingAs((new User())->forceFill(['user_id' => 99, 'role' => 'sk_president']));
    }

    public function test_bulk_creation_preserves_separate_multiword_names(): void
    {
        $this->post(route('sk_pres.user-management.store-bulk-officials'), ['officials' => [
            ['first_name' => 'Juan Miguel', 'last_name' => 'Dela Cruz', 'email' => 'juan@example.test', 'phone_number' => '', 'barangay_id' => 1],
            ['first_name' => 'Maria Clara', 'last_name' => 'De Leon', 'email' => 'maria@example.test', 'phone_number' => '', 'barangay_id' => 2],
        ]])->assertSessionHasNoErrors()->assertRedirect(route('sk_pres.user-management'));
        $this->assertDatabaseHas('users', ['first_name' => 'Juan Miguel', 'last_name' => 'Dela Cruz', 'role' => 'sk_chairman']);
        $this->assertDatabaseHas('users', ['email' => 'juan@example.test', 'is_verified' => false, 'status' => 'inactive']);
        $this->assertDatabaseHas('users', ['first_name' => 'Maria Clara', 'last_name' => 'De Leon']);
        $this->assertDatabaseCount('official_terms', 2);
    }

    public function test_both_names_are_required_before_any_account_is_created(): void
    {
        $this->post(route('sk_pres.user-management.store-bulk-officials'), ['officials' => [
            ['first_name' => '', 'last_name' => 'Dela Cruz', 'email' => 'juan@example.test', 'barangay_id' => 1],
            ['first_name' => 'Maria', 'last_name' => '', 'email' => 'maria@example.test', 'barangay_id' => 2],
        ]])->assertSessionHasErrors(['officials.0.first_name', 'officials.1.last_name'], null, 'bulkAdd');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_invalid_syntax_and_unverified_mail_domains_cannot_create_accounts(): void
    {
        foreach (['juan@', 'juan@@example.test', 'juan@example.invalid'] as $email) {
            $this->post(route('sk_pres.user-management.store-bulk-officials'), ['officials' => [
                ['first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'email' => $email, 'barangay_id' => 1],
            ]])->assertSessionHasErrors(['officials.0.email'], null, 'bulkAdd');
        }
        $this->assertDatabaseCount('users', 0);
        Mail::assertNothingSent();
    }

    public function test_pending_account_email_cannot_be_registered_again(): void
    {
        $official = ['first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'email' => 'juan@example.test', 'phone_number' => '', 'barangay_id' => 1];
        $this->post(route('sk_pres.user-management.store-bulk-officials'), ['officials' => [$official]])->assertSessionHasNoErrors();
        $official['barangay_id'] = 2;
        $this->post(route('sk_pres.user-management.store-bulk-officials'), ['officials' => [$official]])
            ->assertSessionHasErrors(['officials'], null, 'bulkAdd');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_bulk_invalid_phone_is_rejected_without_creating_any_accounts(): void
    {
        $this->post(route('sk_pres.user-management.store-bulk-officials'), ['officials'=>[
            ['first_name'=>'Juan', 'last_name'=>'Cruz', 'email'=>'juan@example.test', 'phone_number'=>'08123456789', 'barangay_id'=>1],
        ]])->assertSessionHasErrors(['officials.0.phone_number'], null, 'bulkAdd');
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('official_terms', 0);
    }

    public function test_bulk_accepts_both_philippine_mobile_formats(): void
    {
        $this->post(route('sk_pres.user-management.store-bulk-officials'), ['officials'=>[
            ['first_name'=>'Juan', 'last_name'=>'Cruz', 'email'=>'juan@example.test', 'phone_number'=>'09123456789', 'barangay_id'=>1],
            ['first_name'=>'Maria', 'last_name'=>'Reyes', 'email'=>'maria@example.test', 'phone_number'=>'+639987654321', 'barangay_id'=>2],
        ]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email'=>'juan@example.test', 'phone_number'=>'09123456789']);
        $this->assertDatabaseHas('users', ['email'=>'maria@example.test', 'phone_number'=>'+639987654321']);
    }

    private function pendingAccounts(int $count): void
    {
        for ($id=1; $id<=$count; $id++) {
            DB::table('users')->insert(['user_id'=>$id, 'first_name'=>'Pending', 'last_name'=>'Official', 'email'=>'pending'.$id.'@example.test', 'role'=>'sk_chairman', 'barangay_id'=>$id, 'is_verified'=>false, 'status'=>'inactive']);
            DB::table('official_terms')->insert(['user_id'=>$id, 'term_id'=>1, 'barangay_id'=>$id, 'role'=>'sk_chairman', 'status'=>'pending']);
        }
    }

    public function test_bulk_resend_button_is_visible_only_above_three_pending_accounts(): void
    {
        foreach ([0, 1, 3, 4, 72] as $count) {
            $html = view('sk_pres.partials.resend-pending-setup', ['pendingSetupCount'=>$count])->render();
            $this->assertSame($count>3, str_contains($html, 'Resend Setup Links'));
        }
    }

    public function test_bulk_resend_below_threshold_does_not_replace_tokens_or_send_mail(): void
    {
        $this->pendingAccounts(3);
        Mail::shouldReceive('send')->never();
        $this->post(route('sk_pres.user-management.resend-pending-setup-links'))->assertSessionHas('warning');
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_bulk_resend_sends_once_per_eligible_account_and_excludes_other_accounts(): void
    {
        $this->pendingAccounts(8);
        DB::table('users')->where('user_id',5)->update(['is_verified'=>true]);
        DB::table('users')->where('user_id',6)->update(['archived_at'=>now()]);
        DB::table('official_terms')->where('user_id',7)->update(['term_id'=>2]);
        DB::table('users')->where('user_id',8)->update(['role'=>'sk_secretary']);
        DB::table('official_terms')->where('user_id',8)->update(['role'=>'sk_secretary']);
        DB::table('official_terms')->insert(['user_id'=>1,'term_id'=>1,'barangay_id'=>1,'role'=>'sk_chairman','status'=>'pending']);
        DB::table('password_reset_tokens')->insert(['email'=>'pending1@example.test','token'=>'old-token','created_at'=>now()->subDays(2)]);
        $recipients=[];
        Mail::shouldReceive('send')->times(4)->andReturnUsing(function ($view, $data, $callback) use (&$recipients) {
            $this->assertSame('email.account-setup', $view);
            $this->assertStringContainsString('/set-password/', $data['setupLink']);
            $recipients[]=$data['user']->email;
        });
        $this->post(route('sk_pres.user-management.resend-pending-setup-links'))->assertSessionHas('warning');
        $this->assertSame(['pending1@example.test','pending2@example.test','pending3@example.test','pending4@example.test'], $recipients);
        $this->assertDatabaseCount('password_reset_tokens', 4);
        $this->assertNotSame('old-token', DB::table('password_reset_tokens')->where('email','pending1@example.test')->value('token'));
        $this->assertDatabaseCount('users', 8);
    }

    public function test_bulk_resend_reports_partial_delivery_failures_and_continues(): void
    {
        $this->pendingAccounts(4);
        $calls=0;
        Mail::shouldReceive('send')->times(4)->andReturnUsing(function () use (&$calls) {
            if (++$calls===2) throw new \RuntimeException('Simulated mail failure');
        });
        $this->post(route('sk_pres.user-management.resend-pending-setup-links'))->assertSessionHas('warning', fn ($message)=>str_contains($message, '3 setup link(s) sent.') && str_contains($message, '1 could not be sent'));
    }

    public function test_chairman_cannot_trigger_president_bulk_resend(): void
    {
        Mail::shouldReceive('send')->never();
        $this->actingAs((new User())->forceFill(['user_id'=>98, 'role'=>'sk_chairman']));
        $this->post(route('sk_pres.user-management.resend-pending-setup-links'))->assertForbidden();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_visible_pending_count_includes_unverified_accounts_with_current_assignments(): void
    {
        $this->pendingAccounts(4);
        DB::table('official_terms')->where('user_id',4)->update(['status'=>'current']);
        $controller = new class extends \App\Http\Controllers\sk_pres\UserManagementController {
            public function visibleCount(): int { return $this->pendingSetupDisplayCount(); }
        };
        $this->assertSame(4, $controller->visibleCount());
        Mail::shouldReceive('send')->times(3);
        $this->post(route('sk_pres.user-management.resend-pending-setup-links'))->assertSessionHas('warning', fn ($message)=>str_contains($message, '3 setup link(s) sent.') && str_contains($message, '1 pending account(s) skipped'));
        $this->assertDatabaseCount('password_reset_tokens', 3);
    }
}
