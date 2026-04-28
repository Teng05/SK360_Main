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
        // Safer auth check with clearer debugging support
        if (!auth()->check()) {
            abort(403, 'Not authenticated');
        }

        $user = auth()->user();

        // Normalize role (removes accidental spaces)
        $role = trim($user->role ?? '');

        if ($role !== 'sk_pres') {
            abort(403, 'Unauthorized: invalid role (' . $role . ')');
        }

        $userName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';

        // Federation President (current logged in user)
        $president = [
            'name' => $userName,
            'email' => $user->email,
            'phone' => $user->phone_number ?? 'N/A',
            'term' => '2024-2026'
        ];

        // Get ALL barangays with their councils
        $barangays = Barangay::all()->map(function ($barangay) {
            return [
                'barangay_name' => $barangay->barangay_name,
                'members' => $this->councilMembers($barangay->barangay_id),
            ];
        });

        return view('leadership', [
            'userName' => $userName,
            'president' => $president,
            'barangays' => $barangays,
        ]);
    }

    protected function councilMembers(int $barangayId): Collection
    {
        if ($barangayId <= 0) {
            return collect();
        }

        $members = collect();

        // Get chairman & secretary from users table
        $leaders = DB::table('users')
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

        $members = $members->merge($leaders);

        // Get kagawads or other council members
        if (Schema::hasTable('sk_council')) {
            $council = DB::table('sk_council')
                ->where('barangay_id', $barangayId)
                ->select('name', 'position', 'email', 'phone', 'term')
                ->get()
                ->map(fn ($row) => (array) $row);

            $members = $members->merge($council);
        }

        return $members
            ->unique(fn ($m) => strtolower(($m['name'] ?? '') . '|' . ($m['position'] ?? '')))
            ->values();
    }
}