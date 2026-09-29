<?php

namespace Tests\Feature;

use App\Models\Meeting;
use Carbon\Carbon;
use Tests\TestCase;

class MeetingStatusTest extends TestCase
{
    public function test_previous_days_are_completed_but_today_remains_available(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-29 12:00:00', 'Asia/Manila'));
        try {
            foreach ([
                ['2026-09-24', 'scheduled', 'completed'],
                ['2026-09-28', 'scheduled', 'completed'],
                ['2026-09-29', 'scheduled', 'scheduled'],
                ['2026-09-30', 'scheduled', 'scheduled'],
                ['2026-09-28', 'cancelled', 'cancelled'],
                ['2026-09-29', 'completed', 'completed'],
            ] as [$date, $stored, $expected]) {
                $meeting=new Meeting();
                $meeting->setRawAttributes([
                    'meeting_date'=>$date,
                    'meeting_time'=>'09:00:00',
                    'status'=>$stored,
                ], true);
                $this->assertSame($expected, $meeting->status);
                $this->assertSame($expected, $meeting->toArray()['status']);
                $this->assertFalse($meeting->isDirty());
            }
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_meeting_becomes_past_at_manila_midnight(): void
    {
        $meeting=new Meeting(['meeting_date'=>'2026-09-29', 'meeting_time'=>'23:00:00', 'status'=>'scheduled']);
        try {
            Carbon::setTestNow(Carbon::parse('2026-09-29 23:59:59', 'Asia/Manila'));
            $this->assertSame('scheduled', $meeting->status);
            Carbon::setTestNow(Carbon::parse('2026-09-30 00:00:00', 'Asia/Manila'));
            $this->assertSame('completed', $meeting->status);
        } finally {
            Carbon::setTestNow();
        }
    }
}
