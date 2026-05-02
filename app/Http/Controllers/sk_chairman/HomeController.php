<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_chairman/HomeController.php.

namespace App\Http\Controllers\sk_chairman;

use App\Http\Controllers\Concerns\BuildsWallFeed;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    use BuildsWallFeed;

    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';
        $barangayName = $user->barangay->barangay_name ?? 'Barangay';
        $barangayId = (int) ($user->barangay_id ?? 0);

        $summaryCards = [
            ['value' => (string) $this->submittedReports($barangayId), 'label' => 'Reports Submitted', 'classes' => 'bg-blue-500 text-white'],
            ['value' => (string) $this->budgetReports($barangayId), 'label' => 'Budget Documents', 'classes' => 'bg-yellow-500 text-white'],
            ['value' => $this->formatRank($this->resolveBarangayRank($barangayId)), 'label' => 'Your Ranking', 'classes' => 'bg-green-500 text-white'],
            ['value' => (string) $this->pendingTasks($barangayId, ['SK Chairman', 'Both']), 'label' => 'Pending Tasks', 'classes' => 'bg-red-500 text-white'],
        ];

        return view('sk_chairman.home', [
            'fullName' => $fullName,
            'barangayName' => $barangayName,
            'initials' => strtoupper(substr($user->first_name ?? 'S', 0, 1).substr($user->last_name ?? 'K', 0, 1)),
            'firstName' => $user->first_name ?? 'SK Chairman',
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
            'summaryCards' => $summaryCards,
            'upcomingEvents' => $this->upcomingEvents(),
            'feedPosts' => $this->wallFeedPosts(),
            'defaultPostCategory' => 'update',
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

    protected function submittedReports(int $barangayId): int
    {
        if ($barangayId <= 0) {
            return 0;
        }

        return DB::table('accomplishment_reports')
            ->where('barangay_id', $barangayId)
            ->count();
    }

    protected function budgetReports(int $barangayId): int
    {
        if ($barangayId <= 0) {
            return 0;
        }

        return DB::table('budget_reports')
            ->where('barangay_id', $barangayId)
            ->count();
    }

    protected function pendingTasks(int $barangayId, array $roles): int
    {
        if ($barangayId <= 0) {
            return 0;
        }

        return DB::table('submission_slots')
            ->where('status', 'open')
            ->whereIn('role', $roles)
            ->get()
            ->filter(function ($slot) use ($barangayId) {
                $table = $slot->submission_type === 'budget_report'
                    ? 'budget_reports'
                    : 'accomplishment_reports';

                return !DB::table($table)
                    ->where('barangay_id', $barangayId)
                    ->where('slot_id', $slot->slot_id)
                    ->exists();
            })
            ->count();
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

    protected function formatRank(?int $rank): string
    {
        return $rank ? "#{$rank}" : 'N/A';
    }
}
