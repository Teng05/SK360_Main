<?php

namespace App\Http\Controllers\sk_chairman;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class LeadershipController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';
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

        return view('sk_chairman.leadership', [
            'fullName' => $fullName,
            'barangayName' => $barangayName,
            'initials' => strtoupper(substr($user->first_name ?? 'S', 0, 1) . substr($user->last_name ?? 'K', 0, 1)),
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
            'councilMembers' => $councilMembers,
            'executives' => $executives,
            'kagawads' => $kagawads,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman', 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'term' => ['nullable', 'string', 'max:50'],
        ]);

        DB::table('sk_council')->insert([
            'barangay_id' => auth()->user()->barangay_id,
            'name' => $validated['name'],
            'position' => 'SK Councilor',
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'term' => $validated['term'] ?: '2023-2026',
            'profile_img' => 'default.png',
            'created_at' => now(),
        ]);

        return redirect()->route('sk_chairman.leadership')->with('status', 'added');
    }

    public function destroy(int $councilId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman', 403);

        DB::table('sk_council')
            ->where('council_id', $councilId)
            ->where('barangay_id', auth()->user()->barangay_id)
            ->delete();

        return redirect()->route('sk_chairman.leadership')->with('status', 'deleted');
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
                DB::raw("'2024-2026' as term"),
                DB::raw('NULL as council_id')
            )
            ->get()
            ->map(fn ($row) => (array) $row);

        if (Schema::hasTable('sk_council')) {
            $councilRows = DB::table('sk_council')
                ->where('barangay_id', $barangayId)
                ->select('name', 'position', 'email', 'phone', 'term', 'council_id')
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
