<?php

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
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
            ['link' => route('sk_pres.analytics'), 'icon' => '📈', 'label' => 'Analytics'],
            ['link' => route('sk_pres.leadership'), 'icon' => '👥', 'label' => 'Leadership'],
            ['link' => route('sk_pres.archive'), 'icon' => '🗂️', 'label' => 'Archive'],
            ['link' => route('sk_pres.user-management'), 'icon' => '👤', 'label' => 'User Management'],
        ];

        $events = DB::table('events')
            ->orderBy('start_datetime')
            ->get();

        $upcomingEvents = DB::table('events')
            ->where('start_datetime', '>=', now())
            ->orderBy('start_datetime')
            ->limit(5)
            ->get();

        $calendarEvents = $events->map(fn ($event) => [
            'id' => $event->event_id,
            'title' => $event->title,
            'start' => $event->start_datetime,
            'end' => $event->end_datetime,
        ]);

        $typeColors = [
            'meeting' => 'bg-blue-700',
            'program' => 'bg-green-600',
            'deadline' => 'bg-red-600',
            'other' => 'bg-fuchsia-500',
        ];

        $legendItems = [
            ['bg-blue-700', 'Meeting'],
            ['bg-green-600', 'Event'],
            ['bg-red-600', 'Deadline'],
            ['bg-fuchsia-500', 'Training'],
        ];

        return view('sk_pres.calendar', [
            'fullName' => $fullName,
            'menuItems' => $menuItems,
            'currentUrl' => url()->current(),
            'calendarEvents' => $calendarEvents,
            'upcomingEvents' => $upcomingEvents,
            'typeColors' => $typeColors,
            'legendItems' => $legendItems,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $validated = $request->validate([
            'event_title' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'in:meeting,deadline,program,other'],
            'description' => ['nullable', 'string'],
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['nullable', 'date', 'after_or_equal:start_datetime'],
        ]);

        $start = $validated['start_datetime'].' 00:00:00';
        $end = ($validated['end_datetime'] ?? $validated['start_datetime']).' 23:59:59';

        DB::table('events')->insert([
            'created_by' => auth()->user()->user_id,
            'title' => $validated['event_title'],
            'description' => $validated['description'] ?? null,
            'event_type' => $validated['event_type'],
            'start_datetime' => $start,
            'end_datetime' => $end,
            'visibility' => 'public',
            'created_at' => now(),
        ]);

        return redirect()->route('sk_pres.calendar')->with('status', 'Event created successfully.');
    }
}
