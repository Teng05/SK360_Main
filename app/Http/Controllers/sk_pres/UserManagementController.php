<?php

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';
        $search = trim((string) request('search', ''));

        return view('sk_pres.user-management', [
            'fullName' => $fullName,
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
            'stats' => $this->stats(),
            'userGroups' => $this->userGroups($search),
            'barangays' => Barangay::query()->orderBy('barangay_name')->get(['barangay_id', 'barangay_name']),
            'search' => $search,
        ]);
    }

    public function storeOfficial(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'max:20', 'unique:users,phone_number'],
            'barangay_id' => ['required', 'integer', 'exists:barangays,barangay_id'],
            'role' => ['required', 'in:sk_chairman,sk_secretary'],
        ]);

        [$firstName, $lastName] = $this->splitFullName($validated['full_name']);
        $temporaryPassword = Str::password(10, true, true, false, false);

        User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'] ?: null,
            'barangay_id' => $validated['barangay_id'],
            'role' => $validated['role'],
            'password' => $temporaryPassword,
            'is_verified' => 1,
            'status' => 'active',
        ]);

        return redirect()
            ->route('sk_pres.user-management')
            ->with('success', 'SK official account created successfully. Temporary password: ' . $temporaryPassword);
    }

    protected function stats(): array
    {
        return [
            [
                'label' => 'Total Users',
                'value' => DB::table('users')->count(),
                'border' => 'border-red-300',
                'iconBg' => 'bg-red-50',
                'icon' => '&#128101;',
                'iconColor' => 'text-red-500',
            ],
            [
                'label' => 'Lipa Youth',
                'value' => DB::table('users')->where('role', 'youth')->count(),
                'border' => 'border-yellow-300',
                'iconBg' => 'bg-yellow-50',
                'icon' => '&#128100;',
                'iconColor' => 'text-yellow-500',
            ],
            [
                'label' => 'SK Chairmen',
                'value' => DB::table('users')->where('role', 'sk_chairman')->count(),
                'border' => 'border-green-300',
                'iconBg' => 'bg-green-50',
                'icon' => '&#128737;',
                'iconColor' => 'text-green-500',
            ],
            [
                'label' => 'SK Secretaries',
                'value' => DB::table('users')->where('role', 'sk_secretary')->count(),
                'border' => 'border-blue-300',
                'iconBg' => 'bg-blue-50',
                'icon' => '&#128196;',
                'iconColor' => 'text-blue-500',
            ],
        ];
    }

    protected function userGroups(string $search = ''): array
    {
        return [
            [
                'label' => 'SK Federation President / System Admin',
                'description' => 'Single unified administrative account',
                'count' => DB::table('users')->where('role', 'sk_president')->count(),
                'badgeColor' => 'bg-red-500',
                'iconColor' => 'text-red-400',
                'icon' => '&#128198;',
                'users' => $this->usersByRole(['sk_president'], $search),
            ],
            [
                'label' => 'SK Chairmen',
                'description' => 'Barangay youth leaders with full access',
                'count' => DB::table('users')->where('role', 'sk_chairman')->count(),
                'badgeColor' => 'bg-green-500',
                'iconColor' => 'text-green-400',
                'icon' => '&#128737;',
                'users' => $this->usersByRole(['sk_chairman'], $search),
            ],
            [
                'label' => 'SK Secretaries',
                'description' => 'Documentation and support officers',
                'count' => DB::table('users')->where('role', 'sk_secretary')->count(),
                'badgeColor' => 'bg-blue-500',
                'iconColor' => 'text-blue-400',
                'icon' => '&#128196;',
                'users' => $this->usersByRole(['sk_secretary'], $search),
            ],
            [
                'label' => 'Lipa Youth',
                'description' => 'Self-registered youth with view access',
                'count' => DB::table('users')->where('role', 'youth')->count(),
                'badgeColor' => 'bg-yellow-500',
                'iconColor' => 'text-yellow-400',
                'icon' => '&#128100;',
                'users' => $this->usersByRole(['youth'], $search),
            ],
        ];
    }

    protected function usersByRole(array $roles, string $search = '')
    {
        $query = DB::table('users as u')
            ->leftJoin('barangays as b', 'u.barangay_id', '=', 'b.barangay_id')
            ->select(
                'u.user_id',
                'u.first_name',
                'u.last_name',
                'u.email',
                'u.phone_number',
                'u.role',
                'u.status',
                'u.created_at',
                'b.barangay_name'
            )
            ->whereIn('u.role', $roles);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->whereRaw("CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) LIKE ?", ["%{$search}%"])
                    ->orWhere('u.email', 'like', "%{$search}%")
                    ->orWhere('u.phone_number', 'like', "%{$search}%")
                    ->orWhere('b.barangay_name', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderBy('u.first_name')
            ->orderBy('u.last_name')
            ->get();
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

    protected function splitFullName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];

        if (count($parts) <= 1) {
            return [$parts[0] ?? $fullName, ''];
        }

        $firstName = array_shift($parts);
        $lastName = implode(' ', $parts);

        return [$firstName, $lastName];
    }
}
