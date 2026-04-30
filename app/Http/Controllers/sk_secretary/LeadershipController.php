<?php

namespace App\Http\Controllers\sk_secretary;

use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class LeadershipController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_secretary', 403);

        $user = auth()->user();
        $userName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';
        $barangayId = (int) ($user->barangay_id ?? 0);
        $barangayName = $user->barangay->barangay_name ?? 'Barangay';
        $councilMembers = $this->councilMembers($barangayId);

        $executives = $councilMembers->filter(function ($member) {
            $position = strtolower((string) ($member['position'] ?? ''));

            return str_contains($position, 'chairman')
                || str_contains($position, 'secretary')
                || str_contains($position, 'treasurer')
                || str_contains($position, 'president');
        })->values();

        $kagawads = $councilMembers->filter(function ($member) {
            $position = strtolower((string) ($member['position'] ?? ''));

            return str_contains($position, 'councilor') || str_contains($position, 'kagawad');
        })->values();

        return view('youth.leadership', [
            'userName' => $userName,
            'roleLabel' => 'SK Secretary',
            'profileRoute' => null,
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
            'barangayName' => $barangayName,
            'councilMembers' => $councilMembers,
            'executives' => $executives,
            'kagawads' => $kagawads,
        ]);
    }

    protected function councilMembers(int $barangayId): Collection
    {
        if ($barangayId <= 0) {
            return collect();
        }

        $members = DB::table('users')
            ->where('barangay_id', $barangayId)
            ->whereIn('role', ['sk_chairman', 'sk_secretary'])
            ->select(
                DB::raw("CONCAT(first_name, ' ', last_name) as name"),
                DB::raw("
                    CASE
                        WHEN role = 'sk_chairman' THEN 'SK Chairman'
                        WHEN role = 'sk_secretary' THEN 'SK Secretary'
                        ELSE role
                    END as position
                "),
                'email',
                'phone_number as phone',
                DB::raw("'2024-2026' as term")
            )
            ->get()
            ->map(fn ($row) => (array) $row);

        if (Schema::hasTable('sk_council')) {
            $councilRows = DB::table('sk_council')
                ->where('barangay_id', $barangayId)
                ->select('name', 'position', 'email', 'phone', 'term')
                ->get()
                ->map(fn ($row) => (array) $row);

            $members = $members->merge($councilRows);
        }

        return $members
            ->unique(fn ($member) => strtolower(($member['name'] ?? '') . '|' . ($member['position'] ?? '')))
            ->values();
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
