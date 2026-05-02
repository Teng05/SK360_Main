<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_chairman/RankingController.php.

namespace App\Http\Controllers\sk_chairman;

use App\Http\Controllers\Concerns\BuildsRankingsData;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class RankingController extends Controller
{
    use BuildsRankingsData;

    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';
        $leaderboard = $this->rankingsLeaderboard();

        return view('sk_chairman.rankings', [
            'fullName' => $fullName,
            'roleLabel' => 'SK Chairman',
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
            'topRankings' => $this->topRankings($leaderboard),
            'leaderboard' => $leaderboard,
            'latestPeriod' => $this->latestRankingPeriod(),
            'pointSystem' => $this->rankingPointSystem(),
            'profileRoute' => route('sk_chairman.profile'),
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
