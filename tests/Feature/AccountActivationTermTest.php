<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountActivationTermTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('administration_terms', function (Blueprint $t) {
            $t->increments('term_id'); $t->integer('start_year'); $t->integer('end_year'); $t->string('status');
        });
        Schema::create('official_terms', function (Blueprint $t) {
            $t->increments('official_term_id'); $t->integer('user_id'); $t->integer('term_id');
            $t->string('role'); $t->string('status'); $t->dateTime('started_at')->nullable(); $t->dateTime('completed_at')->nullable();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->increments('user_id'); $t->string('email'); $t->string('password'); $t->string('role');
            $t->boolean('is_verified'); $t->string('status'); $t->dateTime('archived_at')->nullable();
            $t->date('term_start')->nullable(); $t->date('term_end')->nullable(); $t->dateTime('created_at');
        });
        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email')->primary(); $t->string('token'); $t->dateTime('created_at');
        });
        DB::table('administration_terms')->insert(['term_id' => 1, 'start_year' => 2028, 'end_year' => 2031, 'status' => 'current']);
    }

    private function invite(string $role): array
    {
        $email = $role.'@example.test';
        $id = DB::table('users')->insertGetId(['email' => $email, 'password' => Hash::make('OldPassword1'),
            'role' => $role, 'is_verified' => false, 'status' => 'inactive', 'created_at' => '2026-01-01 10:00:00'], 'user_id');
        DB::table('official_terms')->insert(['user_id' => $id, 'term_id' => 1, 'role' => $role, 'status' => 'pending']);
        DB::table('password_reset_tokens')->insert(['email' => $email, 'token' => Hash::make('invitation'), 'created_at' => now()]);
        return ['email' => $email, 'token' => 'invitation', 'password' => 'NewPassword1', 'password_confirmation' => 'NewPassword1'];
    }

    public function test_activation_populates_assigned_dates_for_all_official_roles(): void
    {
        foreach (['sk_chairman', 'sk_secretary', 'sk_president'] as $role) {
            $data = $this->invite($role);
            $this->post(route('password.setup.store'), $data)->assertSessionHasNoErrors()->assertRedirect(route('login'));
            $user = User::where('email', $data['email'])->first();
            $this->assertSame('2028-01-01', $user->term_start);
            $this->assertSame('2031-12-31', $user->term_end);
            $this->assertSame('2026-01-01 10:00:00', $user->created_at);
            $this->assertTrue(Hash::check('NewPassword1', $user->password));
            $this->assertDatabaseHas('official_terms', ['user_id' => $user->user_id, 'term_id' => 1, 'status' => 'current', 'started_at' => now()->toDateTimeString()]);
            $this->assertDatabaseMissing('password_reset_tokens', ['email' => $data['email']]);
        }
    }

    public function test_old_administration_invitation_cannot_activate_or_reassign_account(): void
    {
        $data = $this->invite('sk_chairman');
        DB::table('administration_terms')->where('term_id', 1)->update(['status' => 'completed']);
        DB::table('administration_terms')->insert(['term_id' => 2, 'start_year' => 2031, 'end_year' => 2034, 'status' => 'current']);
        $this->post(route('password.setup.store'), $data)->assertSessionHasErrors('email');
        $this->assertDatabaseHas('users', ['email' => $data['email'], 'status' => 'inactive', 'is_verified' => false, 'term_start' => null, 'term_end' => null]);
        $this->assertDatabaseHas('official_terms', ['term_id' => 1, 'status' => 'pending', 'started_at' => null]);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $data['email']]);
    }

    public function test_missing_assignment_does_not_activate_account(): void
    {
        $data = $this->invite('sk_secretary');
        DB::table('official_terms')->delete();
        $this->post(route('password.setup.store'), $data)->assertSessionHasErrors('email');
        $this->assertDatabaseHas('users', ['email' => $data['email'], 'status' => 'inactive', 'term_start' => null]);
    }
}
