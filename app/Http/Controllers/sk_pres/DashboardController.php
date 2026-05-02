<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_pres/DashboardController.php.

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';

        $menuItems = [
            ['link' => route('sk_pres.home'), 'icon' => '🏠', 'label' => 'Home'],
            ['link' => route('sk_pres.dashboard'), 'icon' => '📊', 'label' => 'Dashboard'],
            ['link' => route('sk_pres.consolidation'), 'icon' => '📁', 'label' => 'Consolidation'],
            ['link' => route('sk_pres.module'), 'icon' => '⚙️', 'label' => 'Module Management'],
            ['link' => route('sk_pres.announcements'), 'icon' => '📢', 'label' => 'Announcements'],
            ['link' => route('sk_pres.calendar'), 'icon' => '📅', 'label' => 'Calendar'],
            ['link' => route('sk_pres.chat'), 'icon' => '💬', 'label' => 'Chat'],
            ['link' => route('sk_pres.meetings'), 'icon' => '📞', 'label' => 'Meetings'],
            ['link' => route('sk_pres.rankings'), 'icon' => '🏆', 'label' => 'Rankings'],
            
            ['link' => route('sk_pres.leadership'), 'icon' => '👥', 'label' => 'Leadership'],
            ['link' => route('sk_pres.archive'), 'icon' => '🗂️', 'label' => 'Archive'],
            ['link' => route('sk_pres.user-management'), 'icon' => '👤', 'label' => 'User Management'],
        ];

        $userStats = DB::table('users')
            ->selectRaw('COUNT(*) as total_users')
            ->selectRaw("SUM(role IN ('sk_president','sk_chairman','sk_secretary')) as officials")
            ->selectRaw("SUM(role = 'youth') as youth")
            ->selectRaw("SUM(status = 'active') as active_users")
            ->selectRaw("SUM(role = 'sk_chairman') as chairmen")
            ->selectRaw("SUM(role = 'sk_secretary') as secretaries")
            ->selectRaw('SUM(MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())) as new_this_month')
            ->selectRaw("SUM(role = 'youth' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())) as youth_signups_this_month")
            ->first();

        $totalBarangays = DB::table('barangays')->count();

        $totalUsers = (int) ($userStats->total_users ?? 0);
        $officials = (int) ($userStats->officials ?? 0);
        $youth = (int) ($userStats->youth ?? 0);
        $activeUsers = (int) ($userStats->active_users ?? 0);
        $chairmen = (int) ($userStats->chairmen ?? 0);
        $secretaries = (int) ($userStats->secretaries ?? 0);
        $newThisMonth = (int) ($userStats->new_this_month ?? 0);
        $youthSignupsThisMonth = (int) ($userStats->youth_signups_this_month ?? 0);

        $chairmanCoverage = $totalBarangays > 0 ? round(($chairmen / $totalBarangays) * 100) : 0;
        $secretaryCoverage = $totalBarangays > 0 ? round(($secretaries / $totalBarangays) * 100) : 0;
        $remainingSecretaries = max($totalBarangays - $secretaries, 0);

        $cards = [
            [
                'label' => 'Total Users',
                'value' => $totalUsers,
                'subline1' => "{$officials} officials,",
                'subline2' => "{$youth} youth",
                'footer' => "↗ +{$newThisMonth} this month",
                'footerClass' => 'text-green-500',
                'iconWrap' => 'bg-red-100',
                'iconClass' => 'text-red-500',
                'icon' => '👥',
            ],
            [
                'label' => 'Lipa Youth',
                'value' => $youth,
                'subline1' => "{$activeUsers} active",
                'subline2' => 'members',
                'footer' => "↗ +{$youthSignupsThisMonth} new signups",
                'footerClass' => 'text-green-500',
                'iconWrap' => 'bg-yellow-100',
                'iconClass' => 'text-yellow-500',
                'icon' => '👤',
            ],
            [
                'label' => 'SK Chairmen',
                'value' => $chairmen,
                'subline1' => "Across {$totalBarangays}",
                'subline2' => 'barangays',
                'footer' => "↗ {$chairmanCoverage}% coverage",
                'footerClass' => 'text-green-500',
                'iconWrap' => 'bg-green-100',
                'iconClass' => 'text-green-500',
                'icon' => '🛡️',
            ],
            [
                'label' => 'SK Secretaries',
                'value' => $secretaries,
                'subline1' => "{$remainingSecretaries}",
                'subline2' => 'remaining',
                'footer' => "↗ {$secretaryCoverage}% staffed",
                'footerClass' => 'text-green-500',
                'iconWrap' => 'bg-blue-100',
                'iconClass' => 'text-blue-500',
                'icon' => '📄',
            ],
        ];

        return view('sk_pres.dashboard', [
            'fullName' => $fullName,
            'menuItems' => $menuItems,
            'currentUrl' => url()->current(),
            'cards' => $cards,
            'overviewDate' => now()->format('n/j/Y'),
        ]);
    }
}
