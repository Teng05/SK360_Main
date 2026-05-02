<?php

// File guide: Handles route logic and page data for app/Http/Controllers/WelcomeController.php.

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Meeting;
use Illuminate\View\View;

class WelcomeController extends Controller
{
    public function index(): View
    {
        // Fetch latest 3 public announcements
        $latestAnnouncements = Announcement::where('visibility', 'public')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        // meetings stores separate meeting_date and meeting_time columns; scheduled_at is a model accessor.
        $upcomingEvents = Meeting::where('status', 'scheduled')
            ->get()
            ->filter(fn (Meeting $meeting) => $meeting->scheduled_at && $meeting->scheduled_at->isFuture())
            ->sortBy(fn (Meeting $meeting) => $meeting->scheduled_at->timestamp)
            ->take(3)
            ->values();

        return view('welcome', [
            'latestAnnouncements' => $latestAnnouncements,
            'upcomingEvents' => $upcomingEvents,
        ]);
    }
}
