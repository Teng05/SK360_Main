<?php

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ModuleController extends Controller
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
            ['link' => route('sk_pres.analytics'), 'icon' => '📈', 'label' => 'Analytics'],
            ['link' => route('sk_pres.leadership'), 'icon' => '👥', 'label' => 'Leadership'],
            ['link' => route('sk_pres.archive'), 'icon' => '🗂️', 'label' => 'Archive'],
            ['link' => route('sk_pres.user-management'), 'icon' => '👤', 'label' => 'User Management'],
        ];

        $slots = DB::table('submission_slots')
            ->orderByDesc('created_at')
            ->get();

        $totalSlots = $slots->count();
        $openSlots = $slots->where('status', 'open')->count();
        $currentSubmissionValue = $openSlots.'/'.$totalSlots;
        $allTimeTotal = $totalSlots;

        return view('sk_pres.module', [
            'fullName' => $fullName,
            'menuItems' => $menuItems,
            'currentUrl' => url()->current(),
            'slots' => $slots,
            'summaryCards' => [
                ['label' => 'Total Slots', 'value' => $totalSlots, 'border' => 'border-red-400', 'iconBg' => 'bg-red-50', 'iconColor' => 'text-red-500', 'icon' => '📋'],
                ['label' => 'Open Slots', 'value' => $openSlots, 'border' => 'border-green-400', 'iconBg' => 'bg-green-50', 'iconColor' => 'text-green-500', 'icon' => '🔓'],
                ['label' => 'Current Submissions', 'value' => $currentSubmissionValue, 'border' => 'border-blue-400', 'iconBg' => 'bg-blue-50', 'iconColor' => 'text-blue-500', 'icon' => '☑️'],
                ['label' => 'All-Time Total', 'value' => $allTimeTotal, 'border' => 'border-yellow-400', 'iconBg' => 'bg-yellow-50', 'iconColor' => 'text-yellow-500', 'icon' => '👥'],
            ],
        ]);
    }

    public function store(Request $request, NotificationService $notifications): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $validated = $request->validate([
            'submission_type' => ['required', 'in:accomplishment_report,budget_report'],
            'submission_title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'submission_role' => ['required', 'in:SK Chairman,SK Secretary,Both'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        DB::table('submission_slots')->insert([
            'submission_type' => $validated['submission_type'],
            'title' => $validated['submission_title'],
            'description' => $validated['description'] ?? null,
            'role' => $validated['submission_role'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'open',
        ]);

        $notifications->notifySubmissionSlotCreated([
            'submission_type' => $validated['submission_type'],
            'title' => $validated['submission_title'],
            'role' => $validated['submission_role'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ], auth()->user());

        return redirect()->route('sk_pres.module')->with('status', 'Submission slot created successfully.');
    }

    public function destroy(int $slotId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        DB::table('submission_slots')->where('slot_id', $slotId)->delete();

        return redirect()->route('sk_pres.module')->with('status', 'Submission slot deleted successfully.');
    }
}
