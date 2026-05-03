<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_chairman/ChatController.php.

namespace App\Http\Controllers\sk_chairman;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';

        return view('sk_chairman.chat', [
            'fullName' => $fullName,
            'currentUserId' => (string) ($user->user_id ?? ''),
            'currentUserRole' => 'sk_chairman',
            'groupMembers' => $this->groupMembers(),
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
        ]);
    }

    public function searchUsers(Request $request): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman', 403);

        $keyword = trim((string) $request->query('search', ''));

        if ($keyword === '') {
            return response()->json([]);
        }

        $currentUserId = (int) (auth()->user()->user_id ?? 0);
        $chatRoles = ['sk_president', 'sk_chairman', 'sk_secretary'];

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
            ->whereIn('u.role', $chatRoles)
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

    protected function groupMembers(): array
    {
        return DB::table('users')
            ->select('user_id', 'first_name', 'last_name', 'email', 'role')
            ->where('status', 'active')
            ->whereIn('role', ['sk_president', 'sk_chairman', 'sk_secretary'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => (string) $user->user_id,
                    'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->email,
                    'role' => $user->role,
                ];
            })
            ->values()
            ->all();
    }
}
