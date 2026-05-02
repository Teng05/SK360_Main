<?php

namespace App\Console\Commands;

use App\Models\Meeting;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreatePastMeetingNotifications extends Command
{
    protected $signature = 'meetings:notify-past';
    protected $description = 'Create notifications for meetings that have just ended and mark them as completed';

    public function handle(): int
    {
        $now = Carbon::now();
        
        // Find meetings that have passed but are still marked as scheduled
        $pastMeetings = Meeting::where('status', 'scheduled')
            ->get()
            ->filter(function ($meeting) use ($now) {
                $scheduledAt = $meeting->scheduled_at;
                return $scheduledAt && $scheduledAt->isPast();
            });

        foreach ($pastMeetings as $meeting) {
            // Mark meeting as completed
            $meeting->update(['status' => 'completed']);
            
            // Create notification for the meeting creator
            $this->createNotificationForCreator($meeting);
        }

        $this->info("Processed {$pastMeetings->count()} meetings.");
        return Command::SUCCESS;
    }

    protected function createNotificationForCreator(Meeting $meeting): void
    {
        if (! $meeting->created_by) {
            return;
        }

        // Check if notification already exists
        $exists = Notification::where('user_id', $meeting->created_by)
            ->where('type', 'meeting_completed')
            ->where('message', 'like', '%Meeting ID: ' . $meeting->meeting_id . '%')
            ->exists();

        if (! $exists) {
            Notification::create([
                'user_id' => $meeting->created_by,
                'actor_id' => $meeting->created_by,
                'type' => 'meeting_completed',
                'title' => 'Meeting Completed',
                'message' => "Your meeting '{$meeting->title}' on " . $meeting->scheduled_at->format('M d, Y h:i A') . ' has been completed and moved to past meetings. Meeting ID: ' . $meeting->meeting_id,
                'url' => route('sk_pres.meetings'),
                'is_read' => false,
                'created_at' => now(),
            ]);
        }
    }
}