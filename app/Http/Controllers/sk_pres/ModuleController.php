<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_pres/ModuleController.php.

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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

    public function live(): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        return response()->json($this->modulePayload());
    }

    public function toggle(int $slotId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $slot = DB::table('submission_slots')->where('slot_id', $slotId)->first();
        abort_unless($slot, 404);

        DB::table('submission_slots')
            ->where('slot_id', $slotId)
            ->update(['status' => $slot->status === 'open' ? 'closed' : 'open']);

        return redirect()->route('sk_pres.module')->with('status', 'Submission slot status updated.');
    }

    public function submissions(int $slotId): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $slot = DB::table('submission_slots')->where('slot_id', $slotId)->first();
        abort_unless($slot, 404);

        return response()->json([
            'slot' => $slot,
            'submissions' => $this->slotSubmissions($slot),
        ]);
    }

    public function submissionsPage(int $slotId): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $slot = DB::table('submission_slots')->where('slot_id', $slotId)->first();
        abort_unless($slot, 404);

        $slots = DB::table('submission_slots')->orderByDesc('created_at')->get();
        $fullName = trim((auth()->user()->first_name ?? '').' '.(auth()->user()->last_name ?? '')) ?: 'User';

        return view('sk_pres.module', [
            'fullName' => $fullName,
            'currentUrl' => url()->current(),
            'slots' => $slots,
            'summaryCards' => [],
            'submissionPage' => true,
            'submissionSlot' => $slot,
            'submissions' => $this->slotSubmissions($slot),
            'menuItems' => [
                ['link' => route('sk_pres.home'), 'icon' => '&#127968;', 'label' => 'Home'],
                ['link' => route('sk_pres.dashboard'), 'icon' => '&#128202;', 'label' => 'Dashboard'],
                ['link' => route('sk_pres.consolidation'), 'icon' => '&#128193;', 'label' => 'Consolidation'],
                ['link' => route('sk_pres.module'), 'icon' => '&#9881;', 'label' => 'Module Management'],
                ['link' => route('sk_pres.announcements'), 'icon' => '&#128226;', 'label' => 'Announcements'],
                ['link' => route('sk_pres.calendar'), 'icon' => '&#128197;', 'label' => 'Calendar'],
                ['link' => route('sk_pres.chat'), 'icon' => '&#128172;', 'label' => 'Chat'],
                ['link' => route('sk_pres.meetings'), 'icon' => '&#128222;', 'label' => 'Meetings'],
                ['link' => route('sk_pres.rankings'), 'icon' => '&#127942;', 'label' => 'Rankings'],
                ['link' => route('sk_pres.leadership'), 'icon' => '&#128101;', 'label' => 'Leadership'],
                ['link' => route('sk_pres.archive'), 'icon' => '&#128450;', 'label' => 'Archive'],
                ['link' => route('sk_pres.user-management'), 'icon' => '&#128100;', 'label' => 'User Management'],
            ],
        ]);
    }

    public function storeLive(Request $request, NotificationService $notifications): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $validated = $this->validateSlot($request);

        DB::table('submission_slots')->insert([
            'submission_type' => $validated['submission_type'],
            'title' => $validated['submission_title'],
            'description' => $validated['description'] ?? null,
            'role' => $validated['submission_role'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'open',
            'created_at' => now(),
        ]);

        $notifications->notifySubmissionSlotCreated([
            'submission_type' => $validated['submission_type'],
            'title' => $validated['submission_title'],
            'role' => $validated['submission_role'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ], auth()->user());

        return response()->json($this->modulePayload());
    }

    public function destroyLive(int $slotId): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        DB::table('submission_slots')->where('slot_id', $slotId)->delete();

        return response()->json($this->modulePayload());
    }

    public function store(Request $request, NotificationService $notifications): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $validated = $this->validateSlot($request);

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

    protected function validateSlot(Request $request): array
    {
        return $request->validate([
            'submission_type' => ['required', 'in:accomplishment_report,budget_report'],
            'submission_title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'submission_role' => ['required', 'in:SK Chairman,SK Secretary,Both'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);
    }

    protected function modulePayload(): array
    {
        $slots = DB::table('submission_slots')
            ->orderByDesc('created_at')
            ->get();

        return [
            'slots' => $slots,
            'summary' => [
                'totalSlots' => $slots->count(),
                'openSlots' => $slots->where('status', 'open')->count(),
                'closedSlots' => $slots->where('status', 'closed')->count(),
                'allTimeTotal' => $slots->count(),
            ],
            'updatedAt' => now()->format('M d, Y h:i A'),
        ];
    }

    protected function slotSubmissions(object $slot): array
    {
        $table = $slot->submission_type === 'budget_report'
            ? 'budget_reports'
            : 'accomplishment_reports';
        $idColumn = $table === 'budget_reports' ? 'budget_report_id' : 'report_id';

        $columns = [
            'b.barangay_id',
            'b.barangay_name',
            'r.'.$idColumn.' as submission_id',
            'r.title',
            'r.uploaded_file_name',
            'r.uploaded_file_path',
            'r.generated_pdf_path',
            'r.created_at as submitted_at',
        ];

        if (Schema::hasColumn($table, 'template_data')) {
            $columns[] = 'r.template_data';
        }

        return DB::table('barangays as b')
            ->leftJoin($table.' as r', function ($join) use ($slot) {
                $join->on('r.barangay_id', '=', 'b.barangay_id')
                    ->where('r.slot_id', '=', $slot->slot_id);
            })
            ->select($columns)
            ->orderBy('b.barangay_name')
            ->get()
            ->map(function ($row) use ($table) {
                $path = $row->uploaded_file_path ?: $row->generated_pdf_path;
                $row->file_url = $row->submission_id && (
                    ($path && !in_array($path, ['SYSTEM_GEN', 'TEMPLATE_GEN'], true))
                    || ($table === 'budget_reports' && !empty($row->template_data))
                )
                    ? route('sk_pres.archive.view', [
                        $table === 'budget_reports' ? 'budget_report' : 'accomplishment_report',
                        $row->submission_id,
                    ])
                    : null;
                $row->submitted = $row->submission_id !== null;
                $row->submitted_at_label = $row->submitted_at
                    ? Carbon::parse($row->submitted_at)->format('M d, Y h:i A')
                    : null;
                return $row;
            })
            ->all();
    }
}
