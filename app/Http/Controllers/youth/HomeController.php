<?php

// File guide: Handles route logic and page data for app/Http/Controllers/youth/HomeController.php.

namespace App\Http\Controllers\Youth;

use App\Http\Controllers\Concerns\BuildsWallFeed;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    use BuildsWallFeed;

    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'youth', 403);

        $user = auth()->user();
        $userName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';

        return view('youth.home', [
            'userName' => $userName,
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
            'latestAnnouncement' => $this->latestAnnouncement(),
            'upcomingEvents' => $this->upcomingEvents(),
            'eventsJoined' => 0,
            'participationRate' => 0,
            'barangayRank' => $this->resolveBarangayRank((int) ($user->barangay_id ?? 0)),
            'feedPosts' => $this->wallFeedPosts(),
            'defaultPostCategory' => 'update',
        ]);
    }

    protected function menuItems(): array
    {
        return [
            ['link' => route('youth.home'), 'icon' => '🏠', 'label' => 'Home'],
            ['link' => route('youth.announcements'), 'icon' => '📢', 'label' => 'Announcements'],
            ['link' => route('youth.calendar'), 'icon' => '📅', 'label' => 'Event Calendar'],
            ['link' => route('youth.rankings'), 'icon' => '🏆', 'label' => 'Rankings'],
            ['link' => route('youth.leadership'), 'icon' => '👥', 'label' => 'Leadership'],
        ];
    }

    protected function latestAnnouncement(): ?object
    {
        return DB::table('announcements')
            ->where('visibility', 'public')
            ->orderByDesc('created_at')
            ->first();
    }

    protected function upcomingEvents()
    {
        return DB::table('events')
            ->where('visibility', 'public')
            ->where('end_datetime', '>=', now())
            ->orderBy('start_datetime')
            ->get()
            ->map(function ($event) {
                $event->type_label = match ($event->event_type) {
                    'meeting' => 'Meeting',
                    'program' => 'Event/Program',
                    'deadline' => 'Deadline',
                    default => 'Other Activity',
                };

                $event->type_badge = match ($event->event_type) {
                    'meeting' => 'bg-blue-100 text-blue-700',
                    'program' => 'bg-green-100 text-green-700',
                    'deadline' => 'bg-red-100 text-red-700',
                    default => 'bg-fuchsia-100 text-fuchsia-700',
                };

                return $event;
            });
    }

    protected function resolveBarangayRank(int $barangayId): ?int
    {
        if ($barangayId <= 0) {
            return null;
        }

        $latestPeriod = DB::table('rankings')
            ->orderByDesc('created_at')
            ->value('reporting_period');

        if (!$latestPeriod) {
            return null;
        }

        $barangayIds = DB::table('rankings')
            ->where('reporting_period', $latestPeriod)
            ->orderByDesc('total_points')
            ->orderBy('barangay_id')
            ->pluck('barangay_id')
            ->values();

        $index = $barangayIds->search($barangayId);

        return $index === false ? null : $index + 1;
    }
}
