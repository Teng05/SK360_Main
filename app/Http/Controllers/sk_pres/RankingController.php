<?php

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Concerns\BuildsRankingsData;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class RankingController extends Controller
{
    use BuildsRankingsData;

    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';
        $leaderboard = $this->rankingsLeaderboard();

        return view('sk_pres.rankings', [
            'fullName' => $fullName,
            'roleLabel' => 'SK President',
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
            ['link' => route('sk_pres.home'), 'icon' => '&#127968;', 'label' => 'Home'],
            ['link' => route('sk_pres.dashboard'), 'icon' => '&#128202;', 'label' => 'Dashboard'],
            ['link' => route('sk_pres.consolidation'), 'icon' => '&#128193;', 'label' => 'Consolidation'],
            ['link' => route('sk_pres.module'), 'icon' => '&#9881;&#65039;', 'label' => 'Module Management'],
            ['link' => route('sk_pres.announcements'), 'icon' => '&#128226;', 'label' => 'Announcements'],
            ['link' => route('sk_pres.calendar'), 'icon' => '&#128197;', 'label' => 'Calendar'],
            ['link' => route('sk_pres.chat'), 'icon' => '&#128172;', 'label' => 'Chat'],
            ['link' => route('sk_pres.meetings'), 'icon' => '&#128222;', 'label' => 'Meetings'],
            ['link' => route('sk_pres.rankings'), 'icon' => '&#127942;', 'label' => 'Rankings'],
            ['link' => route('sk_pres.analytics'), 'icon' => '&#128200;', 'label' => 'Analytics'],
            ['link' => route('sk_pres.leadership'), 'icon' => '&#128101;', 'label' => 'Leadership'],
            ['link' => route('sk_pres.archive'), 'icon' => '&#128450;&#65039;', 'label' => 'Archive'],
            ['link' => route('sk_pres.user-management'), 'icon' => '&#128100;', 'label' => 'User Management'],
        ];
    }
}
