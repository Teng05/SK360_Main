<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_pres/CalendarController.php.

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';

        $menuItems = [
            ['link' => route('sk_pres.home'), 'icon' => '🏠', 'label' => 'Home'],
            ['link' => route('sk_pres.dashboard'), 'icon' => '📊', 'label' => 'Dashboard'],
            ['link' => route('sk_pres.consolidation'), 'icon' => '📁', 'label' => 'Consolidation'],
            ['link' => route('sk_pres.module'), 'icon' => '⚙️', 'label' => 'Module Management'],
            ['link' => route('sk_pres.announcements'), 'icon' => '📢', 'label' => 'Announcements'],
            ['link' => route('sk_pres.calendar'), 'icon' => '📅', 'label' => 'Calendar'],
            ['link' => route('sk_pres.chat'), 'icon' => '💬', 'label' => 'Chat'],
            ['link' => route('sk_pres.meetings'), 'icon' => '📞', 'label' => 'Meetings'],
            ['link' => route('sk_pres.rankings'), 'icon' => '🏆', 'label' => 'Rankings'],
            
            ['link' => route('sk_pres.leadership'), 'icon' => '👥', 'label' => 'Leadership'],
            ['link' => route('sk_pres.archive'), 'icon' => '🗂️', 'label' => 'Archive'],
            ['link' => route('sk_pres.user-management'), 'icon' => '👤', 'label' => 'User Management'],
        ];

        $events = DB::table('events')
            ->orderBy('start_datetime')
            ->get();

        $slotEvents = DB::table('submission_slots')
            ->where('status', 'open')
            ->orderBy('start_date')
            ->get();

        $upcomingEvents = DB::table('events')
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

                return $event;
            });

        $upcomingSlots = $slotEvents
            ->filter(fn ($slot) => Carbon::parse($slot->end_date)->endOfDay()->greaterThanOrEqualTo(now()))
            ->take(5)
            ->map(function ($slot) {
                $slot->start_datetime = Carbon::parse($slot->start_date)->startOfDay();
                $slot->event_type = $slot->submission_type === 'budget_report' ? 'budget_slot' : 'report_slot';
                $slot->type_label = $slot->submission_type === 'budget_report' ? 'Budget Slot' : 'Report Slot';

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

        $typeColors = [
            'meeting' => 'bg-blue-700',
            'program' => 'bg-green-600',
            'deadline' => 'bg-red-600',
            'other' => 'bg-fuchsia-500',
            'report_slot' => 'bg-indigo-600',
            'budget_slot' => 'bg-amber-500',
        ];

        $legendItems = [
            ['bg-blue-700', 'Meeting'],
            ['bg-green-600', 'Event/Program'],
            ['bg-red-600', 'Deadline'],
            ['bg-indigo-600', 'Report Slot'],
            ['bg-amber-500', 'Budget Slot'],
            ['bg-fuchsia-500', 'Other Activities'],
        ];

        return view('sk_pres.calendar', [
            'fullName' => $fullName,
            'menuItems' => $menuItems,
            'currentUrl' => url()->current(),
            'calendarEvents' => $calendarEvents,
            'upcomingEvents' => $upcomingEvents->concat($upcomingSlots)->sortBy('start_datetime')->take(5)->values(),
            'typeColors' => $typeColors,
            'legendItems' => $legendItems,
        ]);
    }

    public function store(Request $request, NotificationService $notifications): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $validated = $request->validate([
            'event_title' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'in:meeting,deadline,program,other'],
            'description' => ['nullable', 'string'],
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['nullable', 'date', 'after_or_equal:start_datetime'],
            'visibility' => ['required', 'in:public,officials_only,chairman_only,secretary_only'],
        ]);

        $start = $validated['start_datetime'].' 00:00:00';
        $end = ($validated['end_datetime'] ?? $validated['start_datetime']).' 23:59:59';

        $eventId = DB::table('events')->insertGetId([
            'created_by' => auth()->user()->user_id,
            'title' => $validated['event_title'],
            'description' => $validated['description'] ?? null,
            'event_type' => $validated['event_type'],
            'start_datetime' => $start,
            'end_datetime' => $end,
            'visibility' => $validated['visibility'],
            'created_at' => now(),
        ], 'event_id');

        $event = (object) [
            'event_id' => $eventId,
            'title' => $validated['event_title'],
            'visibility' => $validated['visibility'],
            'start_datetime' => Carbon::parse($start),
        ];

        $notifications->notifyEventCreated($event, auth()->user());

        return redirect()->route('sk_pres.calendar')->with('status', 'Event created successfully.');
    }
}
