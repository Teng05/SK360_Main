<?php

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ArchiveController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';
        $documents = $this->documents();

        return view('sk_pres.archive', [
            'fullName' => $fullName,
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
            'archiveCards' => $this->archiveCards(),
            'documents' => $documents,
            'documentCount' => $documents->count(),
        ]);
    }

    protected function archiveCards(): array
    {
        $accomplishmentCount = DB::table('accomplishment_reports')->count();
        $budgetCount = DB::table('budget_reports')->count();
        $meetingCount = DB::table('events')->where('event_type', 'meeting')->count();
        $eventRecordCount = DB::table('events')->whereIn('event_type', ['program', 'other'])->count();
        $policyCount = DB::table('announcements')->count();
        $trainingCount = DB::table('submission_slots')->count();

        return [
            ['icon' => '&#128196;', 'label' => 'Accomplishment Reports', 'count' => $accomplishmentCount],
            ['icon' => '&#128176;', 'label' => 'Budget Documents', 'count' => $budgetCount],
            ['icon' => '&#128221;', 'label' => 'Meeting Minutes', 'count' => $meetingCount],
            ['icon' => '&#127881;', 'label' => 'Event Records', 'count' => $eventRecordCount],
            ['icon' => '&#128203;', 'label' => 'Policies & Guidelines', 'count' => $policyCount],
            ['icon' => '&#128218;', 'label' => 'Training Materials', 'count' => $trainingCount],
        ];
    }

    protected function documents(): Collection
    {
        $accomplishmentReports = DB::table('accomplishment_reports as ar')
            ->leftJoin('barangays as b', 'ar.barangay_id', '=', 'b.barangay_id')
            ->select(
                DB::raw("'accomplishment_report' as source_type"),
                'ar.report_id as source_id',
                'ar.title',
                DB::raw("UPPER(ar.report_type) as badge"),
                DB::raw("'Report' as category"),
                'b.barangay_name as owner',
                'ar.uploaded_file_path as file_path',
                'ar.generated_pdf_path as generated_path',
                'ar.created_at as document_date'
            )
            ->get()
            ->map(function ($row) {
                $row->icon = '&#128196;';
                $row->size = $this->inferFileSize($row->file_path ?? $row->generated_path ?? null);
                $row->date = optional($row->document_date)->format ? $row->document_date : $row->document_date;

                return $row;
            });

        $budgetReports = DB::table('budget_reports as br')
            ->leftJoin('barangays as b', 'br.barangay_id', '=', 'b.barangay_id')
            ->select(
                DB::raw("'budget_report' as source_type"),
                'br.budget_report_id as source_id',
                'br.title',
                DB::raw("REPLACE(UPPER(br.document_type), '_', ' ') as badge"),
                DB::raw("'Budget' as category"),
                'b.barangay_name as owner',
                'br.uploaded_file_path as file_path',
                'br.generated_pdf_path as generated_path',
                'br.created_at as document_date'
            )
            ->get()
            ->map(function ($row) {
                $row->icon = '&#128196;';
                $row->size = $this->inferFileSize($row->file_path ?? $row->generated_path ?? null);

                return $row;
            });

        $events = DB::table('events')
            ->select(
                DB::raw("'event' as source_type"),
                'event_id as source_id',
                'title',
                DB::raw("REPLACE(UPPER(event_type), '_', ' ') as badge"),
                DB::raw("CASE WHEN event_type = 'meeting' THEN 'Minutes' ELSE 'Event' END as category"),
                DB::raw("'Federation' as owner"),
                DB::raw("NULL as file_path"),
                DB::raw("NULL as generated_path"),
                'created_at as document_date'
            )
            ->get()
            ->map(function ($row) {
                $row->icon = '&#128196;';
                $row->size = 'Record';

                return $row;
            });

        $announcements = DB::table('announcements')
            ->select(
                DB::raw("'announcement' as source_type"),
                'announcement_id as source_id',
                'title',
                DB::raw("'PUBLIC' as badge"),
                DB::raw("'Policy' as category"),
                DB::raw("'Federation' as owner"),
                DB::raw("NULL as file_path"),
                DB::raw("NULL as generated_path"),
                'created_at as document_date'
            )
            ->get()
            ->map(function ($row) {
                $row->icon = '&#128196;';
                $row->size = 'Memo';

                return $row;
            });

        return $accomplishmentReports
            ->merge($budgetReports)
            ->merge($events)
            ->merge($announcements)
            ->sortByDesc('document_date')
            ->values()
            ->map(function ($row) {
                $row->formatted_date = $row->document_date ? date('Y-m-d', strtotime((string) $row->document_date)) : 'N/A';
                $row->downloadable = ! empty($row->file_path) || ! empty($row->generated_path);

                return $row;
            });
    }

    protected function inferFileSize(?string $path): string
    {
        if (! $path) {
            return 'N/A';
        }

        return Str::endsWith(strtolower($path), '.pdf') ? 'PDF' : 'File';
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
}
