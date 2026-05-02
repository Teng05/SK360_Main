<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_chairman/CalendarController.php.

namespace App\Http\Controllers\sk_chairman;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';
        $barangayName = $user->barangay->barangay_name ?? 'Barangay';

        $events = DB::table('events')
            ->where('visibility', 'public')
            ->orderBy('start_datetime')
            ->get();

        $slotEvents = DB::table('submission_slots')
            ->where('status', 'open')
            ->where(function ($query) {
                $query->where('role', 'SK Chairman')->orWhere('role', 'Both');
            })
            ->orderBy('start_date')
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

        $upcomingSlots = $slotEvents
            ->filter(fn ($slot) => Carbon::parse($slot->end_date)->endOfDay()->greaterThanOrEqualTo(now()))
            ->take(5)
            ->map(function ($slot) {
                $slot->title = $slot->title;
                $slot->start_datetime = Carbon::parse($slot->start_date)->startOfDay();
                $slot->type_label = $slot->submission_type === 'budget_report' ? 'Budget Slot' : 'Report Slot';
                $slot->type_badge = $slot->submission_type === 'budget_report'
                    ? 'bg-amber-100 text-amber-700'
                    : 'bg-indigo-100 text-indigo-700';
                $slot->location = 'Submission slot';

                return $slot;
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
        })->merge($slotEvents->map(function ($slot) {
            return [
                'id' => 'slot-'.$slot->slot_id,
                'title' => $slot->title,
                'start' => $slot->start_date,
                'end' => $slot->end_date,
                'className' => $slot->submission_type === 'budget_report' ? 'bg-amber-500' : 'bg-indigo-600',
            ];
        }))->values();

        $legendItems = [
            ['bg-blue-700', 'Meeting'],
            ['bg-green-600', 'Event/Program'],
            ['bg-red-600', 'Deadline'],
            ['bg-indigo-600', 'Report Slot'],
            ['bg-amber-500', 'Budget Slot'],
            ['bg-fuchsia-500', 'Other Activities'],
        ];

        return view('sk_chairman.calendar', [
            'fullName' => $fullName,
            'barangayName' => $barangayName,
            'initials' => strtoupper(substr($user->first_name ?? 'S', 0, 1).substr($user->last_name ?? 'K', 0, 1)),
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
            'calendarEvents' => $calendarEvents,
            'legendItems' => $legendItems,
            'upcomingEvents' => $upcomingEvents->concat($upcomingSlots)->sortBy('start_datetime')->take(5)->values(),
        ]);
    }

    protected function menuItems(): array
    {
        return [
            ['link' => route('sk_chairman.home'), 'icon' => '&#127968;', 'label' => 'Home'],
            ['link' => route('sk_chairman.reports'), 'icon' => '&#128196;', 'label' => 'Reports'],
            ['link' => route('sk_chairman.budget'), 'icon' => '&#128229;', 'label' => 'Budget'],
            ['link' => route('sk_chairman.announcements'), 'icon' => '&#128226;', 'label' => 'Announcements'],
            ['link' => route('sk_chairman.calendar'), 'icon' => '&#128197;', 'label' => 'Calendar'],
            ['link' => route('sk_chairman.chat'), 'icon' => '&#128172;', 'label' => 'Chat'],
            ['link' => route('sk_chairman.meetings'), 'icon' => '&#128222;', 'label' => 'Meetings'],
            ['link' => route('sk_chairman.rankings'), 'icon' => '&#127942;', 'label' => 'Rankings'],
            ['link' => route('sk_chairman.leadership'), 'icon' => '&#128101;', 'label' => 'Leadership'],
            ['link' => route('sk_chairman.archive'), 'icon' => '&#128465;', 'label' => 'Archive'],
        ];
    }
}
