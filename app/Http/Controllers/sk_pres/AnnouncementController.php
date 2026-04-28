<?php

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnnouncementController extends Controller
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

        $announcements = DB::table('announcements')
            ->leftJoin('users', 'announcements.user_id', '=', 'users.user_id')
            ->select(
                'announcements.announcement_id',
                'announcements.title',
                'announcements.content',
                'announcements.visibility',
                'announcements.created_at',
                'users.first_name',
                'users.last_name'
            )
            ->orderByDesc('announcements.created_at')
            ->get()
            ->map(function ($announcement) {
                $announcement->author_name = trim(($announcement->first_name ?? '').' '.($announcement->last_name ?? '')) ?: 'Unknown User';
                return $announcement;
            });

        return view('sk_pres.announcement', [
            'fullName' => $fullName,
            'menuItems' => $menuItems,
            'currentUrl' => url()->current(),
            'announcements' => $announcements,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'visibility' => ['nullable', 'in:public,officials_only'],
        ]);

        DB::table('announcements')->insert([
            'user_id' => auth()->user()->user_id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'visibility' => $validated['visibility'] ?? 'public',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('sk_pres.announcements')->with('status', 'Announcement created successfully.');
    }
}
