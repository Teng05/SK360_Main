<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_pres/HomeController.php.

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Concerns\BuildsWallFeed;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    use BuildsWallFeed;

    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';

        $menuItems = [
            ['link' => route('sk_pres.home'), 'icon' => '&#127968;', 'label' => 'Home'],
            ['link' => route('sk_pres.dashboard'), 'icon' => '&#128202;', 'label' => 'Dashboard'],
            ['link' => route('sk_pres.consolidation'), 'icon' => '&#128193;', 'label' => 'Consolidation'],
            ['link' => route('sk_pres.module'), 'icon' => '&#9881;&#65039;', 'label' => 'Module Management'],
            ['link' => route('sk_pres.announcements'), 'icon' => '&#128226;', 'label' => 'Announcements'],
            ['link' => route('sk_pres.calendar'), 'icon' => '&#128197;', 'label' => 'Calendar'],
            ['link' => route('sk_pres.chat'), 'icon' => '&#128172;', 'label' => 'Chat'],
            ['link' => route('sk_pres.meetings'), 'icon' => '&#128222;', 'label' => 'Meetings'],
            ['link' => route('sk_pres.rankings'), 'icon' => '&#127942;', 'label' => 'Rankings'],
            
            ['link' => route('sk_pres.leadership'), 'icon' => '&#128101;', 'label' => 'Leadership'],
            ['link' => route('sk_pres.archive'), 'icon' => '&#128450;&#65039;', 'label' => 'Archive'],
            ['link' => route('sk_pres.user-management'), 'icon' => '&#128100;', 'label' => 'User Management'],
        ];

        $summaryCards = $this->summaryCards();

        return view('sk_pres.home', [
            'fullName' => $fullName,
            'menuItems' => $menuItems,
            'currentUrl' => url()->current(),
            'summaryCards' => $summaryCards,
            'upcomingEvents' => $this->upcomingEvents(),
            'feedPosts' => $this->wallFeedPosts(),
            'defaultPostCategory' => 'update',
        ]);
    }

    protected function upcomingEvents()
    {
        return DB::table('events')
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
    }

    protected function summaryCards(): array
    {
        $accomplishmentReports = DB::table('accomplishment_reports')->count();
        $budgetReports = DB::table('budget_reports')->count();
        $reportsSubmitted = $accomplishmentReports + $budgetReports;

        $barangayCount = DB::table('barangays')->count();
        $submittedBarangays = DB::table('accomplishment_reports')
            ->whereNotNull('barangay_id')
            ->pluck('barangay_id')
            ->merge(
                DB::table('budget_reports')
                    ->whereNotNull('barangay_id')
                    ->pluck('barangay_id')
            )
            ->unique()
            ->count();

        $communityEngagement = $barangayCount > 0
            ? (int) round(($submittedBarangays / $barangayCount) * 100)
            : 0;

        $pendingReviews = DB::table('accomplishment_reports')
            ->where('status', 'submitted')
            ->count()
            + DB::table('budget_reports')
                ->where('status', 'submitted')
                ->count();

        $upcomingEvents = DB::table('events')
            ->where('visibility', 'public')
            ->where('end_datetime', '>=', now())
            ->count();

        return [
            ['value' => (string) $reportsSubmitted, 'label' => 'Reports Submitted', 'classes' => 'bg-red-500 text-white'],
            ['value' => "{$communityEngagement}%", 'label' => 'Community Engagement', 'classes' => 'bg-blue-500 text-white'],
            ['value' => (string) $pendingReviews, 'label' => 'Pending Reviews', 'classes' => 'bg-yellow-500 text-white'],
            ['value' => (string) $upcomingEvents, 'label' => 'Upcoming Events', 'classes' => 'bg-green-500 text-white'],
        ];
    }
}
