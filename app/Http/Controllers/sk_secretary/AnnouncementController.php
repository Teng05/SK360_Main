<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_secretary/AnnouncementController.php.

namespace App\Http\Controllers\sk_secretary;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_secretary', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';
        $barangayName = $user->barangay->barangay_name ?? 'Barangay';

        $announcements = DB::table('announcements as a')
            ->leftJoin('users as u', 'a.user_id', '=', 'u.user_id')
            ->select(
                'a.announcement_id',
                'a.title',
                'a.content',
                'a.visibility',
                'a.created_at',
                DB::raw("COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'SK Federation President') as author_name")
            )
            ->where('a.visibility', 'public')
            ->orderByDesc('a.created_at')
            ->get()
            ->map(function ($announcement) {
                $announcement->priority = 'Low';
                $announcement->priority_badge = 'bg-blue-100 text-blue-600';
                $announcement->visibility_label = $announcement->visibility === 'officials_only' ? 'Officials Only' : 'Public';
                $announcement->views = 0;

                return $announcement;
            });

        return view('sk_secretary.announcements', [
            'fullName' => $fullName,
            'barangayName' => $barangayName,
            'initials' => strtoupper(substr($user->first_name ?? 'S', 0, 1).substr($user->last_name ?? 'K', 0, 1)),
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
            'announcements' => $announcements,
        ]);
    }

    protected function menuItems(): array
    {
        return [
            ['link' => route('sk_secretary.home'), 'icon' => '&#127968;', 'label' => 'Home'],
            ['link' => route('sk_secretary.reports'), 'icon' => '&#128196;', 'label' => 'Reports'],
            ['link' => route('sk_secretary.budget'), 'icon' => '&#128229;', 'label' => 'Budget'],
            ['link' => route('sk_secretary.announcements'), 'icon' => '&#128226;', 'label' => 'Announcements'],
            ['link' => route('sk_secretary.calendar'), 'icon' => '&#128197;', 'label' => 'Calendar'],
            ['link' => route('sk_secretary.chat'), 'icon' => '&#128172;', 'label' => 'Chat'],
            ['link' => route('sk_secretary.meetings'), 'icon' => '&#128222;', 'label' => 'Meetings'],
            ['link' => route('sk_secretary.rankings'), 'icon' => '&#127942;', 'label' => 'Rankings'],
            ['link' => route('sk_secretary.leadership'), 'icon' => '&#128101;', 'label' => 'Leadership'],
        ];
    }
}
