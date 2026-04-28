<?php

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class LeadershipController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';
        $activeTab = request('tab') === 'transition' ? 'transition' : 'directory';

        $menuItems = [
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

        $barangays = Barangay::query()
            ->orderBy('barangay_name')
            ->get(['barangay_id', 'barangay_name']);

        $selectedBarangayId = (int) request('barangay_id');

        if ($selectedBarangayId <= 0 || ! $barangays->contains('barangay_id', $selectedBarangayId)) {
            $selectedBarangayId = (int) ($barangays->first()->barangay_id ?? 0);
        }

        $selectedBarangay = $barangays->firstWhere('barangay_id', $selectedBarangayId);
        $barangayName = $selectedBarangay->barangay_name ?? 'Unknown Barangay';

        $councilMembers = $this->councilMembers($selectedBarangayId);

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

        return view('sk_pres.leadership', [
            'fullName' => $fullName,
            'menuItems' => $menuItems,
            'currentUrl' => url()->current(),
            'activeTab' => $activeTab,
            'barangays' => $barangays,
            'selectedBarangayId' => $selectedBarangayId,
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

        $members = collect();

        $userLeaders = DB::table('users')
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

        $members = $members->merge($userLeaders);

        if (Schema::hasTable('sk_council')) {
            $councilRows = DB::table('sk_council')
                ->where('barangay_id', $barangayId)
                ->select('name', 'position', 'email', 'phone', 'term')
                ->get()
                ->map(fn ($row) => (array) $row);

            $members = $members->merge($councilRows);
        } elseif (Schema::hasTable('leadership_profiles')) {
            $leadershipRows = DB::table('leadership_profiles')
                ->where('barangay_id', $barangayId)
                ->where('status', 'current')
                ->leftJoin('users', 'leadership_profiles.user_id', '=', 'users.user_id')
                ->select(
                    DB::raw("COALESCE(leadership_profiles.full_name, CONCAT(users.first_name, ' ', users.last_name)) as name"),
                    DB::raw("
                        CASE
                            WHEN leadership_profiles.position = 'sk_president' THEN 'SK President'
                            WHEN leadership_profiles.position = 'sk_chairman' THEN 'SK Chairman'
                            WHEN leadership_profiles.position = 'sk_secretary' THEN 'SK Secretary'
                            ELSE leadership_profiles.position
                        END as position
                    "),
                    'users.email',
                    'users.phone_number as phone',
                    DB::raw("CONCAT(YEAR(leadership_profiles.term_start), '-', COALESCE(YEAR(leadership_profiles.term_end), YEAR(CURDATE()))) as term")
                )
                ->get()
                ->map(fn ($row) => (array) $row);

            $members = $members->merge($leadershipRows);
        }

        return $members
            ->unique(fn ($member) => strtolower(($member['name'] ?? '') . '|' . ($member['position'] ?? '')))
            ->values();
    }
}
