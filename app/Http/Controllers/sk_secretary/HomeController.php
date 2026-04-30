<?php

namespace App\Http\Controllers\sk_secretary;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_secretary', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';
        $barangayName = $user->barangay->barangay_name ?? 'Barangay';

        $summaryCards = [
            ['value' => '0', 'label' => 'Programs', 'classes' => 'bg-blue-500 text-white'],
            ['value' => '0', 'label' => 'Ongoing Events', 'classes' => 'bg-yellow-500 text-white'],
            ['value' => '#0', 'label' => 'Your Ranking', 'classes' => 'bg-green-500 text-white'],
            ['value' => '0', 'label' => 'Pending Tasks', 'classes' => 'bg-red-500 text-white'],
        ];

        return view('sk_secretary.home', [
            'fullName' => $fullName,
            'barangayName' => $barangayName,
            'initials' => strtoupper(substr($user->first_name ?? 'S', 0, 1).substr($user->last_name ?? 'K', 0, 1)),
            'firstName' => $user->first_name ?? 'SK Secretary',
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
            'summaryCards' => $summaryCards,
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
