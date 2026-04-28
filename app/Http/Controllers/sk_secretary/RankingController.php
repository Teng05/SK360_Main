<?php

namespace App\Http\Controllers\sk_secretary;

use App\Http\Controllers\Concerns\BuildsRankingsData;
use App\Http\Controllers\Controller;
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
        ]);
    }

    protected function menuItems(): array
    {
        return [
            ['link' => route('sk_secretary.home'), 'icon' => '&#127968;', 'label' => 'Home'],
            ['link' => route('sk_secretary.rankings'), 'icon' => '&#127942;', 'label' => 'Rankings'],
        ];
    }
}
