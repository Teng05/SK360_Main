<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_secretary/RankingController.php.

namespace App\Http\Controllers\sk_secretary;

use App\Http\Controllers\Concerns\BuildsRankingsData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class RankingController extends Controller
{
    use BuildsRankingsData;

    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_secretary', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';
        $leaderboard = $this->rankingsLeaderboard();

        return view('sk_secretary.rankings', [
            'fullName' => $fullName,
            'roleLabel' => 'SK Secretary',
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
            'topRankings' => $this->topRankings($leaderboard),
            'leaderboard' => $leaderboard,
            'latestPeriod' => $this->latestRankingPeriod(),
            'pointSystem' => $this->rankingPointSystem(),
            'profileRoute' => route('sk_secretary.profile'),
            'rankingsLiveRoute' => route('sk_secretary.rankings.live'),
        ]);
    }

    public function live(): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_secretary', 403);

        $leaderboard = $this->rankingsLeaderboard();

        return response()->json([
            'topRankings' => $this->topRankings($leaderboard)->values(),
            'leaderboard' => $leaderboard->values(),
            'latestPeriod' => $this->latestRankingPeriod(),
            'pointSystem' => $this->rankingPointSystem(),
            'updatedAt' => now()->format('M d, Y h:i A'),
        ]);
    }

    protected function menuItems(): array
    {
        return [
            ['link' => route('sk_secretary.home'), 'icon' => '🏠', 'label' => 'Home'],
            ['link' => route('sk_secretary.reports'), 'icon' => '📊', 'label' => 'Reports'],
            ['link' => route('sk_secretary.budget'), 'icon' => '💰', 'label' => 'Budget'],
            ['link' => route('sk_secretary.announcements'), 'icon' => '📢', 'label' => 'Announcements'],
            ['link' => route('sk_secretary.calendar'), 'icon' => '📅', 'label' => 'Calendar'],
            ['link' => route('sk_secretary.chat'), 'icon' => '💬', 'label' => 'Chat'],
            ['link' => route('sk_secretary.meetings'), 'icon' => '📞', 'label' => 'Meetings'],
            ['link' => route('sk_secretary.rankings'), 'icon' => '🏆', 'label' => 'Rankings'],
            ['link' => route('sk_secretary.leadership'), 'icon' => '👥', 'label' => 'Leadership'],
        ];
    }
}
