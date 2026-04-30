<?php

namespace App\Http\Controllers\sk_chairman;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman', 403);

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

        return view('sk_chairman.announcements', [
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
