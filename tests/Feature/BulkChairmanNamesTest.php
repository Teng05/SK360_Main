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
}
