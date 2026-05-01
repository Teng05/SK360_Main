<?php

namespace App\Http\Controllers\sk_secretary;

use App\Http\Controllers\Controller;
use App\Services\SubmissionSlotService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_secretary', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';
        $barangayName = $user->barangay->barangay_name ?? 'Barangay';

        $slots = app(SubmissionSlotService::class)->secretaryBudgetSlots((int) $user->barangay_id);

        $submissions = DB::table('budget_reports')
            ->where('barangay_id', $user->barangay_id)
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($submission) {
                $status = strtolower((string) ($submission->status ?? 'submitted'));
                $method = strtolower((string) $submission->submission_method) === 'file_upload' ? 'pdf' : 'template';
                $submission->submitted_at = $submission->submitted_at
                    ? Carbon::parse($submission->submitted_at)
                    : Carbon::parse($submission->created_at);
                $submission->status_badge = match ($status) {
                    'archived', 'recorded' => 'bg-green-100 text-green-600',
                    'draft' => 'bg-gray-100 text-gray-600',
                    default => 'bg-yellow-100 text-yellow-600',
                };
                $submission->method_badge = $method === 'pdf'
                    ? 'bg-purple-100 text-purple-600'
                    : 'bg-blue-100 text-blue-600';
                $submission->method_label = $method === 'pdf' ? 'PDF Upload' : 'Template';
                $submission->download_url = $method === 'pdf' && !empty($submission->uploaded_file_path)
                    ? asset($submission->uploaded_file_path)
                    : null;

                return $submission;
            });

        return view('sk_secretary.budget', [
            'fullName' => $fullName,
            'barangayName' => $barangayName,
            'initials' => strtoupper(substr($user->first_name ?? 'S', 0, 1) . substr($user->last_name ?? 'K', 0, 1)),
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
            'pageTitle' => 'Budget Submissions',
            'pageDescription' => 'Submit budget documents only through active slots created by the SK President.',
            'slotSectionTitle' => 'Active Budget Slots',
            'slotEmptyMessage' => 'No active budget submission slots right now.',
            'slotActionLabel' => 'Submit Budget File',
            'roleLabel' => 'SK Secretary',
            'slots' => $slots,
            'submissions' => $submissions,
            'submissionType' => 'budget',
            'storeRoute' => route('sk_secretary.budget.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_secretary', 403);

        $validated = $request->validate([
            'slot_id' => ['required', 'integer'],
            'sub_method' => ['required', 'in:template,pdf'],
            'report_file' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $slotService = app(SubmissionSlotService::class);
        $slot = $slotService->resolveOpenSlot((int) $validated['slot_id'], 'budget_report', ['SK Secretary', 'Both']);

        if (!$slot) {
            return back()->with('report_error', 'That budget submission slot is no longer available.');
        }

        if ($slotService->barangayHasSubmissionForSlot('budget_reports', (int) auth()->user()->barangay_id, (int) $slot->slot_id)) {
            return back()->with('report_error', 'This slot has already been submitted by your barangay.');
        }

        if ($validated['sub_method'] === 'pdf' && !$request->hasFile('report_file')) {
            return back()->withErrors(['report_file' => 'A PDF file is required for PDF Upload.'])->withInput();
        }

        $uploadPath = null;
        $uploadName = null;
        $method = $validated['sub_method'] === 'pdf' ? 'file_upload' : 'direct_input';

        if ($request->hasFile('report_file')) {
            $directory = public_path('uploads/budget_reports');
            File::ensureDirectoryExists($directory);

            $filename = 'BUD_' . time() . '_' . auth()->user()->barangay_id . '.pdf';
            $request->file('report_file')->move($directory, $filename);
            $uploadName = $request->file('report_file')->getClientOriginalName();
            $uploadPath = 'uploads/budget_reports/' . $filename;
        }

        DB::table('budget_reports')->insert([
            'user_id' => auth()->user()->user_id,
            'barangay_id' => auth()->user()->barangay_id,
            'slot_id' => $slot->slot_id,
            'submission_method' => $method,
            'document_type' => 'financial_record',
            'fiscal_year' => now()->year,
            'title' => $slot->title,
            'generated_pdf_path' => $method === 'direct_input' ? 'SYSTEM_GEN' : null,
            'uploaded_file_name' => $uploadName,
            'uploaded_file_path' => $uploadPath,
            'total_amount' => 0,
            'status' => 'recorded',
            'submitted_at' => now(),
            'created_at' => now(),
        ]);

        return redirect()->route('sk_secretary.budget')->with('report_success', 'Budget document submitted successfully.');
    }

    protected function menuItems(): array
    {
        return [
            ['link' => route('sk_secretary.home'), 'icon' => '&#127968;', 'label' => 'Home'],
            ['link' => route('sk_secretary.reports'), 'icon' => '&#128196;', 'label' => 'Reports'],
            ['link' => route('sk_secretary.budget'), 'icon' => '&#128229;', 'label' => 'Budget'],
            ['link' => route('sk_secretary.announcements'), 'icon' => '&#128226;', 'label' => 'Announcements'],
            ['link' => route('sk_secretary.calendar'), 'icon' => '&#128197;', 'label' => 'Calendar'],
            ['link' => route('sk_secretary.chat'), 'icon' => '&#128172;', 'label' => 'Chat'],
            ['link' => route('sk_secretary.meetings'), 'icon' => '&#128222;', 'label' => 'Meetings'],
            ['link' => route('sk_secretary.rankings'), 'icon' => '&#127942;', 'label' => 'Rankings'],
            ['link' => route('sk_secretary.leadership'), 'icon' => '&#128101;', 'label' => 'Leadership'],
        ];
    }
}
