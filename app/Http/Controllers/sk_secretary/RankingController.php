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

        $rankingsLiveRoute = request()->filled('period')
            ? route('sk_secretary.rankings.live', ['period' => request('period')])
            : route('sk_secretary.rankings.live');

        return view('sk_secretary.rankings', [
            'fullName' => $fullName,
            'roleLabel' => 'SK Secretary',
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
            'topRankings' => $this->topRankings($leaderboard),
            'leaderboard' => $leaderboard,
            'latestPeriod' => $this->latestRankingPeriod(),
            'rankingPeriods' => $this->rankingPeriodOptions(),
            'selectedPeriod' => $this->selectedRankingPeriodValue(),
            'pointSystem' => $this->rankingPointSystem(),
            'profileRoute' => route('sk_secretary.profile'),
            'rankingsLiveRoute' => $rankingsLiveRoute,
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
            ['link' => route('sk_secretary.home'), 'icon' => '&#127968;', 'label' => 'Home'],
            ['link' => route('sk_secretary.reports'), 'icon' => '&#128203;', 'label' => 'Reports'],
            ['link' => route('sk_secretary.budget'), 'icon' => '&#128176;', 'label' => 'Budget'],
            ['link' => route('sk_secretary.announcements'), 'icon' => '&#128226;', 'label' => 'Announcements'],
            ['link' => route('sk_secretary.calendar'), 'icon' => '&#128197;', 'label' => 'Calendar'],
            ['link' => route('sk_secretary.chat'), 'icon' => '&#128172;', 'label' => 'Chat'],
            ['link' => route('sk_secretary.meetings'), 'icon' => '&#128222;', 'label' => 'Meetings'],
            ['link' => route('sk_secretary.rankings'), 'icon' => '&#127942;', 'label' => 'Rankings'],
            ['link' => route('sk_secretary.leadership'), 'icon' => '&#128101;', 'label' => 'Leadership'],
        ];
    }
}