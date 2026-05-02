<?php

// File guide: Handles route logic and page data for app/Http/Controllers/youth/CalendarController.php.

namespace App\Http\Controllers\Youth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'youth', 403);

        $user = auth()->user();
        $userName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';

        $events = DB::table('events')
            ->where('visibility', 'public')
            ->orderBy('start_datetime')
            ->get();

        $upcomingEvents = DB::table('events')
            ->where('visibility', 'public')
            ->where('end_datetime', '>=', now())
            ->orderBy('start_datetime')
            ->limit(5)
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

        $calendarEvents = $events->map(function ($event) {
            return [
                'id' => $event->event_id,
                'title' => $event->title,
                'start' => $event->start_datetime,
                'end' => $event->end_datetime,
                'className' => match ($event->event_type) {
                    'meeting' => 'bg-blue-700',
                    'program' => 'bg-green-600',
                    'deadline' => 'bg-red-600',
                    default => 'bg-fuchsia-500',
                },
            ];
        })->values();

        $legendItems = [
            ['bg-blue-700', 'Meeting'],
            ['bg-green-600', 'Event/Program'],
            ['bg-red-600', 'Deadline'],
            ['bg-fuchsia-500', 'Other Activities'],
        ];

        return view('youth.calendar', [
            'userName' => $userName,
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
            'calendarEvents' => $calendarEvents,
            'legendItems' => $legendItems,
            'upcomingEvents' => $upcomingEvents,
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
}
