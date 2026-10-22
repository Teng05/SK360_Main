<?php

namespace Tests\Feature;

use App\Services\NotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SubmissionDeadlineReminderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-20 10:00:00', 'Asia/Manila'));
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('administration_terms', function (Blueprint $t) { $t->integer('term_id'); $t->string('status'); });
        Schema::create('users', function (Blueprint $t) {
            $t->integer('user_id'); $t->integer('barangay_id')->nullable(); $t->string('role'); $t->string('status'); $t->dateTime('archived_at')->nullable();
        });
        Schema::create('official_terms', function (Blueprint $t) {
            $t->integer('user_id'); $t->integer('term_id'); $t->string('role'); $t->string('status');
        });
        Schema::create('submission_slots', function (Blueprint $t) {
            $t->integer('slot_id'); $t->integer('term_id'); $t->string('submission_type'); $t->string('title');
            $t->string('role'); $t->string('status'); $t->date('start_date'); $t->date('end_date');
        });
        foreach (['budget_reports' => 'budget_report_id', 'accomplishment_reports' => 'report_id'] as $table => $key) {
            Schema::create($table, function (Blueprint $t) use ($key) {
                $t->integer($key); $t->integer('term_id'); $t->integer('slot_id'); $t->integer('barangay_id'); $t->string('status');
            });
        }
        Schema::create('submission_quality_reviews', function (Blueprint $t) {
            $t->integer('source_id'); $t->string('source_type'); $t->string('status');
        });
        Schema::create('events', function (Blueprint $t) {
            $t->integer('event_id'); $t->integer('term_id'); $t->integer('created_by'); $t->string('event_type');
            $t->string('title'); $t->dateTime('start_datetime'); $t->string('visibility');
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->increments('notification_id'); $t->integer('user_id'); $t->integer('actor_id'); $t->string('type');
            $t->string('title'); $t->text('message'); $t->string('url'); $t->boolean('is_read'); $t->dateTime('created_at'); $t->dateTime('read_at')->nullable();
        });
        DB::table('administration_terms')->insert(['term_id' => 1, 'status' => 'current']);
        DB::table('users')->insert([
            ['user_id' => 1, 'barangay_id' => null, 'role' => 'sk_president', 'status' => 'active'],
            ['user_id' => 2, 'barangay_id' => 1, 'role' => 'sk_chairman', 'status' => 'active'],
            ['user_id' => 3, 'barangay_id' => 2, 'role' => 'sk_chairman', 'status' => 'active'],
            ['user_id' => 4, 'barangay_id' => 1, 'role' => 'sk_secretary', 'status' => 'active'],
        ]);
        foreach ([2 => 'sk_chairman', 3 => 'sk_chairman', 4 => 'sk_secretary'] as $id => $role) {
            DB::table('official_terms')->insert(['user_id' => $id, 'role' => $role, 'term_id' => 1, 'status' => 'current']);
        }
        DB::table('submission_slots')->insert(['slot_id' => 10, 'term_id' => 1, 'submission_type' => 'budget_report',
            'title' => 'Pera', 'role' => 'SK Chairman', 'status' => 'open', 'start_date' => '2026-10-01', 'end_date' => '2026-10-23']);
    }

    public function test_daily_stages_are_distinct_and_repeated_polling_is_deduplicated(): void
    {
        $service = app(NotificationService::class);
        foreach ([20 => 'in 3 days', 21 => 'in 2 days', 22 => 'tomorrow', 23 => 'today'] as $day => $label) {
            $this->travelTo(\Carbon\Carbon::parse('2026-10-'.$day.' 10:00:00', 'Asia/Manila'));
            $service->notifyDeadlinesSoon(); $service->notifyDeadlinesSoon();
            $this->assertSame(2, DB::table('notifications')->where('title', 'Submission deadline '.$label)->count());
        }
        $this->assertDatabaseCount('notifications', 8);
        $this->assertDatabaseMissing('notifications', ['user_id' => 4]);
        $this->assertStringContainsString('focus_slot=10', DB::table('notifications')->value('url'));
        $this->travelTo(\Carbon\Carbon::parse('2026-10-24 10:00:00', 'Asia/Manila'));
        $service->notifyDeadlinesSoon();
        $this->assertDatabaseCount('notifications', 8);
    }

    public function test_submitted_barangay_is_skipped_but_returned_document_gets_reminders(): void
    {
        DB::table('budget_reports')->insert(['budget_report_id' => 5, 'term_id' => 1, 'slot_id' => 10, 'barangay_id' => 1, 'status' => 'submitted']);
        app(NotificationService::class)->notifyDeadlinesSoon();
        $this->assertDatabaseMissing('notifications', ['user_id' => 2]);
        $this->assertDatabaseHas('notifications', ['user_id' => 3]);
        DB::table('submission_quality_reviews')->insert(['source_id' => 5, 'source_type' => 'budget_report', 'status' => 'needs_revision']);
        app(NotificationService::class)->notifyDeadlinesSoon();
        $this->assertDatabaseHas('notifications', ['user_id' => 2]);
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_closed_upcoming_and_previous_term_slots_are_excluded(): void
    {
        foreach ([['status' => 'closed'], ['status' => 'open', 'start_date' => '2026-10-22'], ['start_date' => '2026-10-01', 'term_id' => 2]] as $changes) {
            DB::table('submission_slots')->update($changes);
            app(NotificationService::class)->notifyDeadlinesSoon();
        }
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_secretary_report_reminders_ignore_drafts_and_stop_after_submission(): void
    {
        DB::table('submission_slots')->update(['submission_type' => 'accomplishment_report', 'role' => 'SK Secretary']);
        DB::table('accomplishment_reports')->insert(['report_id' => 5, 'term_id' => 1, 'slot_id' => 10, 'barangay_id' => 1, 'status' => 'draft']);
        app(NotificationService::class)->notifyDeadlinesSoon();
        $this->assertDatabaseHas('notifications', ['user_id' => 4, 'type' => 'report_deadline']);
        $this->assertDatabaseCount('notifications', 1);
        DB::table('accomplishment_reports')->update(['status' => 'submitted']);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-21 10:00:00', 'Asia/Manila'));
        app(NotificationService::class)->notifyDeadlinesSoon();
        $this->assertDatabaseCount('notifications', 1);
    }
}
