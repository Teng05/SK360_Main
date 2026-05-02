<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_chairman/HomeController.php.

namespace App\Http\Controllers\sk_chairman;

use App\Http\Controllers\Concerns\BuildsWallFeed;
use App\Http\Controllers\Controller;
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

        $summaryCards = [
            ['value' => '0', 'label' => 'Programs', 'classes' => 'bg-blue-500 text-white'],
            ['value' => '0', 'label' => 'Ongoing Events', 'classes' => 'bg-yellow-500 text-white'],
            ['value' => '#0', 'label' => 'Your Ranking', 'classes' => 'bg-green-500 text-white'],
            ['value' => '0', 'label' => 'Pending Tasks', 'classes' => 'bg-red-500 text-white'],
        ];

        return view('sk_chairman.home', [
            'fullName' => $fullName,
            'barangayName' => $barangayName,
            'initials' => strtoupper(substr($user->first_name ?? 'S', 0, 1).substr($user->last_name ?? 'K', 0, 1)),
            'firstName' => $user->first_name ?? 'SK Chairman',
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
            'summaryCards' => $summaryCards,
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
}
