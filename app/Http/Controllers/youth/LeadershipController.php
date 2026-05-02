<?php

// File guide: Handles route logic and page data for app/Http/Controllers/youth/LeadershipController.php.

namespace App\Http\Controllers\Youth;

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
        abort_unless(auth()->check() && auth()->user()->role === 'youth', 403);

        $user = auth()->user();
        $userName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';
        $barangayId = (int) ($user->barangay_id ?? 0);
        $barangayName = Barangay::where('barangay_id', $barangayId)->value('barangay_name') ?: 'Unknown Barangay';

        $councilMembers = $this->councilMembers($barangayId);

        $executives = $councilMembers->filter(function ($member) {
            $position = strtolower((string) $member['position']);
            return str_contains($position, 'chairman')
                || str_contains($position, 'secretary')
                || str_contains($position, 'treasurer')
                || str_contains($position, 'president');
        })->values();

        $kagawads = $councilMembers->filter(function ($member) {
            $position = strtolower((string) $member['position']);
            return str_contains($position, 'councilor') || str_contains($position, 'kagawad');
        })->values();

        return view('youth.leadership', [
            'userName' => $userName,
            'roleLabel' => 'Youth Member',
            'profileRoute' => route('youth.profile'),
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
