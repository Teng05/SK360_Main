<?php

// File guide: Handles route logic and page data for app/Http/Controllers/youth/RankingController.php.

namespace App\Http\Controllers\Youth;

use App\Http\Controllers\Controller;
use App\Services\RankingPointsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RankingController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'youth', 403);

        $user = auth()->user();
        $userName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';

        $leaderboard = $this->leaderboard();
        $topRankings = $leaderboard->take(3)->values()->map(function ($row, $index) {
            $icons = ['🥇', '🥈', '🥉'];
            $colors = ['border-yellow-400', 'border-gray-200', 'border-orange-400'];
            $badges = [
                1 => ['Top Performer', 'Highest Score'],
                2 => ['Strong Compliance', 'Consistent Performer'],
                3 => ['Rising Contender', 'Active Participation'],
            ];

            return [
                'name' => $row->name,
                'points' => $row->points,
                'color' => $colors[$index] ?? 'border-gray-200',
                'icon' => $icons[$index] ?? '🏅',
                'badges' => $badges[$index + 1] ?? ['Participant'],
            ];
        });

        return view('youth.rankings', [
            'userName' => $userName,
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
            'topRankings' => $topRankings,
            'leaderboard' => $leaderboard,
            'latestPeriod' => $this->latestPeriod(),
            'pointSystem' => $this->pointSystem(),
            'rankingsLiveRoute' => route('youth.rankings.live'),
        ]);
    }

    public function live(): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'youth', 403);

        $leaderboard = $this->leaderboard();

        return response()->json([
            'topRankings' => $this->topRankings($leaderboard),
            'leaderboard' => $leaderboard->values(),
            'latestPeriod' => $this->latestPeriod(),
            'pointSystem' => $this->pointSystem(),
            'updatedAt' => now()->format('M d, Y h:i A'),
        ]);
    }

    protected function latestPeriod(): ?string
    {
        return DB::table('rankings')
            ->orderByDesc('created_at')
            ->value('reporting_period');
    }

    protected function leaderboard(): Collection
    {
        app(RankingPointsService::class)->recordMissedMeetings();

        $latestPeriod = $this->latestPeriod();

        if (! $latestPeriod) {
            return collect();
        }

        return DB::table('rankings as r')
            ->join('barangays as b', 'r.barangay_id', '=', 'b.barangay_id')
            ->select(
                'r.barangay_id',
                'b.barangay_name as name',
                'r.total_points as points',
                'r.timely_submission_points',
                'r.completeness_points',
                'r.participation_points'
            )
            ->where('r.reporting_period', $latestPeriod)
            ->orderByDesc('r.total_points')
            ->orderBy('r.barangay_id')
            ->get()
            ->values()
            ->map(function ($row, $index) {
                $row->rank = $index + 1;
                $row->on_time = $this->normalizeMetric((int) $row->timely_submission_points);
                $row->completion = $this->normalizeMetric((int) $row->completeness_points);
                $row->engagement = $this->normalizeMetric((int) $row->participation_points);
                $row->trend = $index < 2 ? 'up' : 'down';

                return $row;
            });
    }

    protected function topRankings(Collection $leaderboard): Collection
    {
        return $leaderboard->take(3)->values()->map(function ($row, $index) {
            $icons = ['1st', '2nd', '3rd'];
            $colors = ['border-yellow-400', 'border-gray-200', 'border-orange-400'];
            $badges = [
                1 => ['Top Performer', 'Highest Score'],
                2 => ['Strong Compliance', 'Consistent Performer'],
                3 => ['Rising Contender', 'Active Participation'],
            ];

            return [
                'name' => $row->name,
                'points' => $row->points,
                'color' => $colors[$index] ?? 'border-gray-200',
                'icon' => $icons[$index] ?? 'Ranked',
                'badges' => $badges[$index + 1] ?? ['Participant'],
            ];
        });
    }

    protected function normalizeMetric(int $value): int
    {
        return max(0, min(100, $value));
    }

    protected function pointSystem(): array
    {
        return [
            ['label' => 'On-time Report Submission', 'points' => 50, 'type' => 'positive'],
            ['label' => 'Meeting Attendance', 'points' => 30, 'type' => 'positive'],
            ['label' => 'Community Engagement', 'points' => 25, 'type' => 'positive'],
            ['label' => 'Quality Documentation', 'points' => 20, 'type' => 'positive'],
            ['label' => 'Event Participation', 'points' => 15, 'type' => 'positive'],
            ['label' => 'Late Submission', 'points' => -25, 'type' => 'negative'],
            ['label' => 'Missed Meeting', 'points' => -30, 'type' => 'negative'],
        ];
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
