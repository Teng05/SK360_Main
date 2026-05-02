<?php

// File guide: Handles route logic and page data for app/Http/Controllers/ProfileSettingsController.php.

namespace App\Http\Controllers;

use App\Models\Barangay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ProfileSettingsController extends Controller
{
    protected array $roles = [
        'sk_president' => [
            'prefix' => 'sk_pres',
            'label' => 'SK President',
            'description' => 'Manage your profile and security settings for SK Federation.',
            'menu' => [
                ['route' => 'sk_pres.home', 'icon' => '&#127968;', 'label' => 'Home'],
                ['route' => 'sk_pres.dashboard', 'icon' => '&#128202;', 'label' => 'Dashboard'],
                ['route' => 'sk_pres.consolidation', 'icon' => '&#128193;', 'label' => 'Consolidation'],
                ['route' => 'sk_pres.module', 'icon' => '&#9881;', 'label' => 'Module Management'],
                ['route' => 'sk_pres.announcements', 'icon' => '&#128226;', 'label' => 'Announcements'],
                ['route' => 'sk_pres.calendar', 'icon' => '&#128197;', 'label' => 'Calendar'],
                ['route' => 'sk_pres.chat', 'icon' => '&#128172;', 'label' => 'Chat'],
                ['route' => 'sk_pres.meetings', 'icon' => '&#128222;', 'label' => 'Meetings'],
                ['route' => 'sk_pres.rankings', 'icon' => '&#127942;', 'label' => 'Rankings'],
                ['route' => 'sk_pres.leadership', 'icon' => '&#128101;', 'label' => 'Leadership'],
                ['route' => 'sk_pres.archive', 'icon' => '&#128450;', 'label' => 'Archive'],
                ['route' => 'sk_pres.user-management', 'icon' => '&#128100;', 'label' => 'User Management'],
                ['route' => 'sk_pres.profile', 'icon' => '&#128100;', 'label' => 'Profile'],
            ],
        ],
        'sk_chairman' => [
            'prefix' => 'sk_chairman',
            'label' => 'SK Chairman',
            'description' => 'Manage your profile and security settings for your barangay council.',
            'menu' => [
                ['route' => 'sk_chairman.home', 'icon' => '&#127968;', 'label' => 'Home'],
                ['route' => 'sk_chairman.reports', 'icon' => '&#128196;', 'label' => 'Reports'],
                ['route' => 'sk_chairman.budget', 'icon' => '&#128229;', 'label' => 'Budget'],
                ['route' => 'sk_chairman.announcements', 'icon' => '&#128226;', 'label' => 'Announcements'],
                ['route' => 'sk_chairman.calendar', 'icon' => '&#128197;', 'label' => 'Calendar'],
                ['route' => 'sk_chairman.chat', 'icon' => '&#128172;', 'label' => 'Chat'],
                ['route' => 'sk_chairman.meetings', 'icon' => '&#128222;', 'label' => 'Meetings'],
                ['route' => 'sk_chairman.rankings', 'icon' => '&#127942;', 'label' => 'Rankings'],
                ['route' => 'sk_chairman.leadership', 'icon' => '&#128101;', 'label' => 'Leadership'],
                ['route' => 'sk_chairman.archive', 'icon' => '&#128465;', 'label' => 'Archive'],
                ['route' => 'sk_chairman.profile', 'icon' => '&#128100;', 'label' => 'Profile'],
            ],
        ],
        'sk_secretary' => [
            'prefix' => 'sk_secretary',
            'label' => 'SK Secretary',
            'description' => 'Manage your profile and security settings for your barangay council.',
            'menu' => [
                ['route' => 'sk_secretary.home', 'icon' => '&#127968;', 'label' => 'Home'],
                ['route' => 'sk_secretary.reports', 'icon' => '&#128196;', 'label' => 'Reports'],
                ['route' => 'sk_secretary.budget', 'icon' => '&#128229;', 'label' => 'Budget'],
                ['route' => 'sk_secretary.announcements', 'icon' => '&#128226;', 'label' => 'Announcements'],
                ['route' => 'sk_secretary.calendar', 'icon' => '&#128197;', 'label' => 'Calendar'],
                ['route' => 'sk_secretary.chat', 'icon' => '&#128172;', 'label' => 'Chat'],
                ['route' => 'sk_secretary.meetings', 'icon' => '&#128222;', 'label' => 'Meetings'],
                ['route' => 'sk_secretary.rankings', 'icon' => '&#127942;', 'label' => 'Rankings'],
                ['route' => 'sk_secretary.leadership', 'icon' => '&#128101;', 'label' => 'Leadership'],
                ['route' => 'sk_secretary.profile', 'icon' => '&#128100;', 'label' => 'Profile'],
            ],
        ],
    ];

    public function show(string $role): View
    {
        $config = $this->authorizeRole($role);
        $user = auth()->user()->fresh();
        $userName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';
        $barangayName = Barangay::where('barangay_id', $user->barangay_id)->value('barangay_name') ?: 'Unknown Barangay';

        return view('shared.profile-settings', [
            'user' => $user,
            'userName' => $userName,
            'barangayName' => $barangayName,
            'roleLabel' => $config['label'],
            'pageDescription' => $config['description'],
            'profileRoute' => route($config['prefix'].'.profile'),
            'updateRoute' => route($config['prefix'].'.profile.update'),
            'passwordRoute' => route($config['prefix'].'.profile.password'),
            'menuItems' => $this->menuItems($config),
            'currentUrl' => url()->current(),
            'hasProfilePicColumn' => Schema::hasColumn('users', 'profile_pic'),
        ]);
    }

    public function update(Request $request, string $role): RedirectResponse
    {
        $config = $this->authorizeRole($role);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
        ]);

        auth()->user()->update($validated);

        return redirect()->route($config['prefix'].'.profile')->with('status', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request, string $role): RedirectResponse
    {
        $config = $this->authorizeRole($role);
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update(['password' => $validated['password']]);

        return redirect()->route($config['prefix'].'.profile')->with('status', 'Password updated successfully.');
    }

    protected function authorizeRole(string $role): array
    {
        abort_unless(auth()->check() && auth()->user()->role === $role && isset($this->roles[$role]), 403);

        return $this->roles[$role];
    }

    protected function menuItems(array $config): array
    {
        return collect($config['menu'])
            ->filter(fn (array $item) => \Illuminate\Support\Facades\Route::has($item['route']))
            ->map(fn (array $item) => [
                'link' => route($item['route']),
                'icon' => $item['icon'],
                'label' => $item['label'],
            ])
            ->values()
            ->all();
    }
}
