<?php

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';

        return view('sk_pres.chat', [
            'fullName' => $fullName,
            'currentUserId' => (string) ($user->user_id ?? ''),
            'currentUserRole' => 'sk_president',
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
        ]);
    }

    public function searchUsers(Request $request): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $keyword = trim((string) $request->query('search', ''));

        if ($keyword === '') {
            return response()->json([]);
        }

        $currentUserId = (int) (auth()->user()->user_id ?? 0);

        $users = DB::table('users as u')
            ->leftJoin('barangays as b', 'u.barangay_id', '=', 'b.barangay_id')
            ->select(
                'u.user_id',
                'u.first_name',
                'u.last_name',
                'u.email',
                'u.role',
                'b.barangay_name'
            )
            ->where('u.user_id', '!=', $currentUserId)
            ->where('u.status', '=', 'active')
            ->where(function ($query) use ($keyword) {
                $query
                    ->whereRaw("CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) LIKE ?", ["%{$keyword}%"])
                    ->orWhere('u.email', 'like', "%{$keyword}%")
                    ->orWhere('b.barangay_name', 'like', "%{$keyword}%");
            })
            ->orderBy('u.first_name')
            ->orderBy('u.last_name')
            ->limit(12)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => (string) $user->user_id,
                    'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                    'email' => $user->email,
                    'role' => $user->role,
                    'barangay' => $user->barangay_name,
                ];
            })
            ->values();

        return response()->json($users);
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
