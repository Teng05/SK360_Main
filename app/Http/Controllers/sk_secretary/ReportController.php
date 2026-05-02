<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_secretary/ReportController.php.

namespace App\Http\Controllers\sk_secretary;

use App\Http\Controllers\Controller;
use App\Services\RankingPointsService;
use App\Services\SubmissionSlotService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_secretary', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';
        $barangayName = $user->barangay->barangay_name ?? 'Barangay';
        $slots = app(SubmissionSlotService::class)->secretaryReportSlots((int) $user->barangay_id);

        $reports = DB::table('accomplishment_reports')
            ->where('barangay_id', $user->barangay_id)
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($report) {
                $status = strtolower((string) ($report->status ?? 'submitted'));
                $method = strtolower((string) $report->submission_method) === 'file_upload' ? 'pdf' : 'manual';

                $report->submitted_at = $report->submitted_at
                    ? Carbon::parse($report->submitted_at)
                    : Carbon::parse($report->created_at);
                $report->status_badge = match ($status) {
                    'approved' => 'bg-green-100 text-green-600',
                    'rejected' => 'bg-red-100 text-red-600',
                    'reviewed' => 'bg-blue-100 text-blue-600',
                    default => 'bg-yellow-100 text-yellow-600',
                };
                $report->method_badge = $method === 'pdf'
                    ? 'bg-purple-100 text-purple-600'
                    : 'bg-gray-100 text-gray-600';
                $report->method_label = $method === 'pdf' ? 'PDF Upload' : 'Manual';
                $report->period_label = 'Manual Report';
                $report->download_url = $method === 'pdf' && !empty($report->uploaded_file_path)
                    ? asset($report->uploaded_file_path)
                    : null;
                $report->view_url = $report->download_url;

                return $report;
            });

        return view('sk_secretary.reports', [
            'fullName' => $fullName,
            'barangayName' => $barangayName,
            'initials' => strtoupper(substr($user->first_name ?? 'S', 0, 1) . substr($user->last_name ?? 'K', 0, 1)),
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
            'slots' => $slots,
            'submissions' => $reports,
            'pageTitle' => 'Accomplishment Reports',
            'pageDescription' => 'Submit accomplishment reports only through active slots created by the SK President.',
            'slotSectionTitle' => 'Active Report Slots',
            'slotEmptyMessage' => 'No active accomplishment report slots right now.',
            'slotActionLabel' => 'Submit Report File',
            'roleLabel' => 'SK Secretary',
            'submissionType' => 'report',
            'storeRoute' => route('sk_secretary.reports.store'),
            'profileRoute' => route('sk_secretary.profile'),
            'allowResubmission' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_secretary', 403);

        $validated = $request->validate([
            'slot_id' => ['required', 'integer'],
            'report_file' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $slotService = app(SubmissionSlotService::class);
        $slot = $slotService->resolveOpenSlot(
            (int) $validated['slot_id'],
            'accomplishment_report',
            ['SK Secretary', 'Both']
        );

        if (!$slot) {
            return back()->with('report_error', 'That accomplishment report slot is no longer available.');
        }

        $uploadPath = null;
        $uploadName = null;
        $method = 'file_upload';

        if ($request->hasFile('report_file')) {
            $directory = public_path('uploads/reports');
            File::ensureDirectoryExists($directory);

            $filename = 'REP_' . time() . '_' . auth()->user()->barangay_id . '.pdf';
            $request->file('report_file')->move($directory, $filename);
            $uploadName = $request->file('report_file')->getClientOriginalName();
            $uploadPath = 'uploads/reports/' . $filename;
        }

        $data = [
            'user_id' => auth()->user()->user_id,
            'barangay_id' => auth()->user()->barangay_id,
            'slot_id' => $slot->slot_id,
            'report_type' => 'monthly',
            'submission_method' => $method,
            'title' => $slot->title,
            'reporting_year' => now()->year,
            'reporting_month' => now()->month,
            'reporting_quarter' => null,
            'generated_pdf_path' => null,
            'uploaded_file_name' => $uploadName,
            'uploaded_file_path' => $uploadPath,
            'status' => 'submitted',
            'remarks' => null,
            'submitted_at' => now(),
            'created_at' => now(),
        ];

        $reportId = $this->saveReportSubmission($data, (int) $slot->slot_id);

        $this->scoreSubmission($slot, $reportId, 'accomplishment_report');

        return redirect()->route('sk_secretary.reports')->with('report_success', 'Your report has been submitted successfully.');
    }

    protected function scoreSubmission(object $slot, int $sourceId, string $sourceType): void
    {
        $user = auth()->user();
        $points = app(RankingPointsService::class);
        $isOnTime = now()->lessThanOrEqualTo(Carbon::parse($slot->end_date)->endOfDay());
        $submissionAction = $isOnTime
            ? RankingPointsService::ON_TIME_REPORT_SUBMISSION
            : RankingPointsService::LATE_SUBMISSION;

        $points->award((int) $user->barangay_id, $submissionAction, $sourceType, $sourceId, (int) $user->user_id);
        $points->award((int) $user->barangay_id, RankingPointsService::QUALITY_DOCUMENTATION, $sourceType, $sourceId, (int) $user->user_id);
    }

    protected function saveReportSubmission(array $data, int $slotId): int
    {
        $existing = DB::table('accomplishment_reports')
            ->where('barangay_id', auth()->user()->barangay_id)
            ->where('slot_id', $slotId)
            ->first();

        if ($existing) {
            DB::table('accomplishment_reports')
                ->where('report_id', $existing->report_id)
                ->update($data);

            return (int) $existing->report_id;
        }

        return (int) DB::table('accomplishment_reports')->insertGetId($data, 'report_id');
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
