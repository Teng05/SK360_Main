<?php

// File guide: Handles route logic and page data for app/Http/Controllers/ProfileSettingsController.php.

namespace App\Http\Controllers;

use App\Models\Barangay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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

    /**
     * Saves the name fields, the profile photo, or both.
     *
     * The photo sits in its own small form in the page header, so the name
     * rules are `sometimes`: a photo-only submit must not fail on fields it
     * never sent. Errors go to the `profile` bag so the password modal can
     * keep its own.
     */
    public function update(Request $request, string $role): RedirectResponse
    {
        $config = $this->authorizeRole($role);
        $user = auth()->user();
        $canUploadPhoto = Schema::hasColumn('users', 'profile_pic');

        $rules = [
            'first_name' => ['sometimes', 'required', 'string', 'max:50'],
            'last_name' => ['sometimes', 'required', 'string', 'max:50'],
        ];

        if ($canUploadPhoto) {
            $rules['profile_pic'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];
        }

        $validated = $request->validateWithBag('profile', $rules, [
            'first_name.required' => 'Please enter your first name.',
            'last_name.required' => 'Please enter your last name.',
            'profile_pic.image' => 'The photo must be an image file.',
            'profile_pic.mimes' => 'The photo must be a JPG, PNG or WEBP file.',
            'profile_pic.max' => 'The photo must be 2 MB or smaller.',
        ]);

        $changes = array_intersect_key($validated, array_flip(['first_name', 'last_name']));
        $status = 'Profile updated.';
        $currentPhoto = $user->profile_pic;

        if ($canUploadPhoto) {
            if ($request->boolean('remove_photo')) {
                $changes['profile_pic'] = null;
                $status = 'Profile photo removed.';
            } elseif ($request->hasFile('profile_pic')) {
                $changes['profile_pic'] = $this->storePhoto($request->file('profile_pic'), $user->user_id);
                $status = 'Profile photo updated.';
            }
        }

        if ($changes === []) {
            return redirect()->route($config['prefix'].'.profile');
        }

        $user->update($changes);

        // Only clear the old file once the new filename is safely stored.
        if (array_key_exists('profile_pic', $changes)
            && ! empty($currentPhoto)
            && $changes['profile_pic'] !== $currentPhoto) {
            $this->deletePhoto($currentPhoto);
        }

        return redirect()->route($config['prefix'].'.profile')->with('status', $status);
    }

    public function updatePassword(Request $request, string $role): RedirectResponse
    {
        $config = $this->authorizeRole($role);
        $user = auth()->user();

        $validated = $request->validateWithBag('password', [
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', 'min:8', 'different:current_password'],
        ], [
            'current_password.required' => 'Please enter your current password.',
            'password.required' => 'Please enter a new password.',
            'password.min' => 'The new password must be at least 8 characters.',
            'password.confirmed' => 'The two new passwords do not match.',
            'password.different' => 'The new password must be different from your current one.',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(
                ['current_password' => 'That is not your current password.'],
                'password'
            );
        }

        $user->update(['password' => $validated['password']]);

        return redirect()->route($config['prefix'].'.profile')->with('status', 'Password updated.')->with('tab', 'security');
    }

    /**
     * Moves an uploaded avatar next to the other public uploads and returns the
     * bare filename, which is what the sidebar, topbar and portal already read.
     */
    protected function storePhoto(UploadedFile $file, int|string $userId): string
    {
        $directory = public_path('uploads/profile_pics');
        File::ensureDirectoryExists($directory);

        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $filename = 'AVA_'.$userId.'_'.now()->format('YmdHis').'_'.Str::lower(Str::random(6)).'.'.$extension;

        $file->move($directory, $filename);

        return $filename;
    }

    protected function deletePhoto(?string $filename): void
    {
        if (empty($filename)) {
            return;
        }

        $path = public_path('uploads/profile_pics/'.basename($filename));

        if (is_file($path)) {
            File::delete($path);
        }
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
