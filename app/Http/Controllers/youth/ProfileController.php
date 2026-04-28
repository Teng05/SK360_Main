<?php

namespace App\Http\Controllers\Youth;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'youth', 403);

        $user = auth()->user()->fresh();
        $userName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';
        $barangayName = Barangay::where('barangay_id', $user->barangay_id)->value('barangay_name') ?: 'Unknown Barangay';

        return view('youth.profile', [
            'user' => $user,
            'userName' => $userName,
            'barangayName' => $barangayName,
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
            'hasProfilePicColumn' => Schema::hasColumn('users', 'profile_pic'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'youth', 403);

        $user = auth()->user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
        ]);

        $user->update($validated);

        return redirect()->route('youth.profile')->with('status', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'youth', 403);

        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'Current password is incorrect.',
            ]);
        }

        $user->update([
            'password' => $validated['password'],
        ]);

        return redirect()->route('youth.profile')->with('status', 'Password updated successfully.');
    }

    protected function menuItems(): array
    {
        return [
            ['link' => route('youth.home'), 'icon' => '🏠', 'label' => 'Home'],
            ['link' => route('youth.announcements'), 'icon' => '📢', 'label' => 'Announcements'],
            ['link' => route('youth.calendar'), 'icon' => '📅', 'label' => 'Event Calendar'],
            ['link' => route('youth.rankings'), 'icon' => '🏆', 'label' => 'Rankings'],
            ['link' => route('youth.leadership'), 'icon' => '👥', 'label' => 'Leadership'],
            ['link' => route('youth.profile'), 'icon' => '👤', 'label' => 'Profile'],
        ];
    }
}
