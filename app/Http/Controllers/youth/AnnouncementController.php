<?php

namespace App\Http\Controllers\Youth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'youth', 403);

        $user = auth()->user();
        $userName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';

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

        return view('youth.announcements', [
            'userName' => $userName,
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
            'announcements' => $announcements,
        ]);
    }

    protected function menuItems(): array
    {
        return [
            ['link' => route('youth.home'), 'icon' => '🏠', 'label' => 'Home'],
            ['link' => route('youth.announcements'), 'icon' => '📢', 'label' => 'Announcements'],
            ['link' => route('youth.calendar'), 'icon' => '📅', 'label' => 'Event Calendar'],
            ['link' => route('youth.rankings'), 'icon' => '🏆', 'label' => 'Rankings'],
            ['link' => route('youth.leadership'), 'icon' => '👥', 'label' => 'Leadership'],
        ];
    }
}
