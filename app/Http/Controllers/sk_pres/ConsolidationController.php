<?php

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ConsolidationController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';

        $menuItems = [
            ['link' => route('sk_pres.home'), 'icon' => '🏠', 'label' => 'Home'],
            ['link' => route('sk_pres.dashboard'), 'icon' => '📊', 'label' => 'Dashboard'],
            ['link' => route('sk_pres.consolidation'), 'icon' => '📁', 'label' => 'Consolidation'],
            ['link' => route('sk_pres.module'), 'icon' => '⚙️', 'label' => 'Module Management'],
            ['link' => route('sk_pres.announcements'), 'icon' => '📢', 'label' => 'Announcements'],
            ['link' => route('sk_pres.calendar'), 'icon' => '📅', 'label' => 'Calendar'],
            ['link' => route('sk_pres.chat'), 'icon' => '💬', 'label' => 'Chat'],
            ['link' => route('sk_pres.meetings'), 'icon' => '📞', 'label' => 'Meetings'],
            ['link' => route('sk_pres.rankings'), 'icon' => '🏆', 'label' => 'Rankings'],
            ['link' => route('sk_pres.analytics'), 'icon' => '📈', 'label' => 'Analytics'],
            ['link' => route('sk_pres.leadership'), 'icon' => '👥', 'label' => 'Leadership'],
            ['link' => route('sk_pres.archive'), 'icon' => '🗂️', 'label' => 'Archive'],
            ['link' => route('sk_pres.user-management'), 'icon' => '👤', 'label' => 'User Management'],
        ];

        $stats = [
            ['label' => 'Total Barangays', 'value' => 0, 'valueClass' => 'text-gray-800'],
            ['label' => 'Submitted', 'value' => 0, 'valueClass' => 'text-green-500'],
            ['label' => 'Pending', 'value' => 0, 'valueClass' => 'text-yellow-500'],
            ['label' => 'Late', 'value' => 0, 'valueClass' => 'text-red-500'],
        ];

        $months = [
            'Month',
            'January 2026',
            'February 2026',
            'March 2026',
        ];

        $submissions = [];

        return view('sk_pres.consolidation', [
            'fullName' => $fullName,
            'menuItems' => $menuItems,
            'stats' => $stats,
            'months' => $months,
            'submissions' => $submissions,
            'currentUrl' => url()->current(),
        ]);
    }
}
