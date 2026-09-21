<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_pres/ArchiveController.php.

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use ZipArchive;

class ArchiveController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';
        $barangays = Barangay::query()->orderBy('barangay_name')->get(['barangay_id', 'barangay_name']);
        $administrationTerms = $this->administrationTerms();
        $filters = $this->filters($request, $barangays, $administrationTerms);
        $allDocuments = $this->documents($filters['barangay_id'], $filters['term_id']);
        $documents = $this->applyFilters($allDocuments, $filters);

        return view('sk_pres.archive', [
            'fullName' => $fullName,
            'menuItems' => $this->menuItems(),
            'currentUrl' => url()->current(),
            'archiveCards' => $this->archiveCards($filters['barangay_id'], $filters['term_id']),
            'documents' => $documents,
            'documentCount' => $documents->count(),
            'filterYears' => $this->filterYears($allDocuments),
            'typeOptions' => $this->typeOptions(),
            'barangays' => $barangays,
            'administrationTerms' => $administrationTerms,
            'filters' => $filters,
        ]);
    }

    protected function archiveCards(?int $barangayId = null, ?int $termId = null): array
    {
        $completedTermIds = $this->completedTermIds($termId);

        if ($completedTermIds->isEmpty()) {
            return [
                ['icon' => '&#128196;', 'label' => 'Accomplishment Reports', 'count' => 0],
                ['icon' => '&#128176;', 'label' => 'Budget Documents', 'count' => 0],
                ['icon' => '&#128221;', 'label' => 'Meeting Minutes', 'count' => 0],
                ['icon' => '&#127881;', 'label' => 'Event Records', 'count' => 0],
                ['icon' => '&#128203;', 'label' => 'Policies & Guidelines', 'count' => 0],
                ['icon' => '&#128218;', 'label' => 'Training Materials', 'count' => 0],
            ];
        }

        $accomplishmentCount = DB::table('accomplishment_reports')
            ->whereIn('term_id', $completedTermIds)
            ->when($barangayId, fn ($query) => $query->where('barangay_id', $barangayId))
            ->count();

        $budgetCount = DB::table('budget_reports')
            ->whereIn('term_id', $completedTermIds)
            ->when($barangayId, fn ($query) => $query->where('barangay_id', $barangayId))
            ->count();

        $meetingCount = DB::table('meetings')
            ->whereIn('term_id', $completedTermIds)
            ->count()
            + DB::table('events')
                ->whereIn('term_id', $completedTermIds)
                ->where('event_type', 'meeting')
                ->count();

        $eventRecordCount = DB::table('events')
            ->whereIn('term_id', $completedTermIds)
            ->whereIn('event_type', ['program', 'other'])
            ->count();

        $policyCount = DB::table('announcements')
            ->whereIn('term_id', $completedTermIds)
            ->count();

        $trainingCount = DB::table('submission_slots')
            ->whereIn('term_id', $completedTermIds)
            ->count();

        return [
            ['icon' => '&#128196;', 'label' => 'Accomplishment Reports', 'count' => $accomplishmentCount],
            ['icon' => '&#128176;', 'label' => 'Budget Documents', 'count' => $budgetCount],
            ['icon' => '&#128221;', 'label' => 'Meeting Minutes', 'count' => $meetingCount],
            ['icon' => '&#127881;', 'label' => 'Event Records', 'count' => $eventRecordCount],
            ['icon' => '&#128203;', 'label' => 'Policies & Guidelines', 'count' => $policyCount],
            ['icon' => '&#128218;', 'label' => 'Training Materials', 'count' => $trainingCount],
        ];
    }

    protected function documents(?int $barangayId = null, ?int $termId = null): Collection
    {
        $completedTermIds = $this->completedTermIds($termId);

        if ($completedTermIds->isEmpty()) {
            return collect();
        }

        $accomplishmentReports = DB::table('accomplishment_reports as ar')
            ->leftJoin('barangays as b', 'ar.barangay_id', '=', 'b.barangay_id')
            ->whereIn('ar.term_id', $completedTermIds)
            ->when($barangayId, fn ($query) => $query->where('ar.barangay_id', $barangayId))
            ->select(
                DB::raw("'accomplishment_report' as source_type"),
                'ar.report_id as source_id',
                'ar.term_id',
                'ar.title',
                DB::raw("UPPER(ar.report_type) as badge"),
                DB::raw("'Report' as category"),
                DB::raw("'report' as type_key"),
                'ar.barangay_id',
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
            ->whereIn('br.term_id', $completedTermIds)
            ->when($barangayId, fn ($query) => $query->where('br.barangay_id', $barangayId))
            ->select(
                DB::raw("'budget_report' as source_type"),
                'br.budget_report_id as source_id',
                'br.term_id',
                'br.title',
                DB::raw("REPLACE(UPPER(br.document_type), '_', ' ') as badge"),
                DB::raw("'Budget' as category"),
                DB::raw("'budget' as type_key"),
                'br.barangay_id',
                'b.barangay_name as owner',
                'br.uploaded_file_path as file_path',
                'br.generated_pdf_path as generated_path',
                'br.template_data',
                'br.created_at as document_date'
            )
            ->get()
            ->map(function ($row) {
                $row->icon = '&#128196;';
                $row->size = $this->inferFileSize($row->file_path ?? $row->generated_path ?? null);

                return $row;
            });

        $meetings = DB::table('meetings')
            ->whereIn('term_id', $completedTermIds)
            ->select(
                DB::raw("'meeting' as source_type"),
                'meeting_id as source_id',
                'term_id',
                'title',
                DB::raw("'MEETING' as badge"),
                DB::raw("'Minutes' as category"),
                DB::raw("'minutes' as type_key"),
                DB::raw("NULL as barangay_id"),
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

        $events = DB::table('events')
            ->whereIn('term_id', $completedTermIds)
            ->select(
                DB::raw("'event' as source_type"),
                'event_id as source_id',
                'term_id',
                'title',
                DB::raw("REPLACE(UPPER(event_type), '_', ' ') as badge"),
                DB::raw("CASE WHEN event_type = 'meeting' THEN 'Minutes' ELSE 'Event' END as category"),
                DB::raw("CASE WHEN event_type = 'meeting' THEN 'minutes' ELSE 'event' END as type_key"),
                DB::raw("NULL as barangay_id"),
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
            ->whereIn('term_id', $completedTermIds)
            ->select(
                DB::raw("'announcement' as source_type"),
                'announcement_id as source_id',
                'term_id',
                'title',
                DB::raw("'PUBLIC' as badge"),
                DB::raw("'Policy' as category"),
                DB::raw("'policy' as type_key"),
                DB::raw("NULL as barangay_id"),
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

        $administrationLabels = $this->administrationTerms()
            ->keyBy('term_id')
            ->map(fn ($term) => $term->start_year.'-'.$term->end_year);

        return $accomplishmentReports
            ->merge($budgetReports)
            ->merge($meetings)
            ->merge($events)
            ->merge($announcements)
            ->sortByDesc('document_date')
            ->values()
            ->map(function ($row) use ($administrationLabels) {
                $row->formatted_date = $row->document_date ? date('Y-m-d', strtotime((string) $row->document_date)) : 'N/A';
                $row->document_year = $row->document_date ? date('Y', strtotime((string) $row->document_date)) : null;
                $row->administration_label = $administrationLabels->get($row->term_id, 'Unknown Administration');
                $row->downloadable = $this->isDownloadable($row);

                return $row;
            });
    }

    public function download(string $sourceType, int $sourceId)
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $document = $this->downloadableDocument($sourceType, $sourceId);

        if (!$document) {
            return redirect()->route('sk_pres.archive')->with('archive_error', 'This archive document is not available for download.');
        }

        return $this->downloadDocument($document);
    }

    public function bulkDownload(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        if (!class_exists(ZipArchive::class)) {
            return redirect()->route('sk_pres.archive', $request->query())->with('archive_error', 'Bulk download requires the PHP zip extension.');
        }

        $barangays = Barangay::query()->orderBy('barangay_name')->get(['barangay_id', 'barangay_name']);
        $administrationTerms = $this->administrationTerms();
        $filters = $this->filters($request, $barangays, $administrationTerms);
        $allDocuments = $this->documents($filters['barangay_id'], $filters['term_id']);
        $documents = $this->applyFilters($allDocuments, $filters)
            ->filter(fn ($document) => $this->isDownloadable($document))
            ->values();

        if ($documents->isEmpty()) {
            return redirect()->route('sk_pres.archive', $request->query())->with('archive_error', 'No downloadable archive documents match those filters.');
        }

        return $this->downloadZip($documents, 'sk-president-archive');
    }

    protected function filters(Request $request, Collection $barangays, Collection $administrationTerms): array
    {
        $year = (string) $request->query('year', '');
        $type = (string) $request->query('type', '');
        $barangayId = (int) $request->query('barangay_id', 0);
        $termId = (int) $request->query('term_id', 0);

        if ($barangayId <= 0 || !$barangays->contains('barangay_id', $barangayId)) {
            $barangayId = null;
        }

        if ($termId <= 0 || !$administrationTerms->contains('term_id', $termId)) {
            $termId = null;
        }

        return [
            'year' => preg_match('/^\d{4}$/', $year) ? $year : '',
            'type' => array_key_exists($type, $this->typeOptions()) ? $type : '',
            'barangay_id' => $barangayId,
            'term_id' => $termId,
        ];
    }

    protected function applyFilters(Collection $documents, array $filters): Collection
    {
        return $documents
            ->when($filters['year'] !== '', fn (Collection $items) => $items->where('document_year', $filters['year']))
            ->when($filters['type'] !== '', fn (Collection $items) => $items->where('type_key', $filters['type']))
            ->values();
    }

    protected function filterYears(Collection $documents): Collection
    {
        return $documents
            ->pluck('document_year')
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();
    }

    protected function typeOptions(): array
    {
        return [
            'report' => 'Accomplishment Reports',
            'budget' => 'Budget Documents',
            'minutes' => 'Meeting Minutes',
            'event' => 'Event Records',
            'policy' => 'Policies & Guidelines',
        ];
    }

    protected function downloadableDocument(string $sourceType, int $sourceId): ?object
    {
        $completedTermIds = $this->completedTermIds();

        if ($completedTermIds->isEmpty()) {
            return null;
        }

        if ($sourceType === 'accomplishment_report') {
            return DB::table('accomplishment_reports')
                ->whereIn('term_id', $completedTermIds)
                ->where('report_id', $sourceId)
                ->first();
        }

        if ($sourceType === 'budget_report') {
            return DB::table('budget_reports as br')
                ->leftJoin('barangays as b', 'br.barangay_id', '=', 'b.barangay_id')
                ->whereIn('br.term_id', $completedTermIds)
                ->where('br.budget_report_id', $sourceId)
                ->select('br.*', 'b.barangay_name')
                ->first();
        }

        return null;
    }

    protected function downloadDocument(object $document)
    {
        $filePath = $this->publicFilePath($document->uploaded_file_path ?? null);

        if ($filePath) {
            return response()->download($filePath, $document->uploaded_file_name ?: basename($filePath));
        }

        if (isset($document->template_data) && !empty($document->template_data)) {
            $data = json_decode($document->template_data, true) ?: [];
            $paper = ($data['report_type'] ?? 'quarterly') === 'monthly' ? 'portrait' : 'landscape';

            return Pdf::loadView('shared.budget-template-download', [
                'data' => $data,
                'barangayName' => $document->barangay_name ?? 'Barangay',
            ])->setPaper('a4', $paper)->download('budget-template-'.($document->budget_report_id ?? time()).'.pdf');
        }

        return redirect()->route('sk_pres.archive')->with('archive_error', 'This archive document is not available for download.');
    }

    protected function downloadZip(Collection $documents, string $prefix)
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'archive_');
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::OVERWRITE);

        foreach ($documents as $document) {
            $name = $this->downloadName($document);
            $filePath = $this->publicFilePath($document->file_path ?? null);

            if ($filePath) {
                $zip->addFile($filePath, $name);
                continue;
            }

            if ($document->source_type === 'budget_report' && !empty($document->template_data)) {
                $data = json_decode($document->template_data, true) ?: [];
                $paper = ($data['report_type'] ?? 'quarterly') === 'monthly' ? 'portrait' : 'landscape';
                $pdf = Pdf::loadView('shared.budget-template-download', [
                    'data' => $data,
                    'barangayName' => $document->owner ?: 'Barangay',
                ])->setPaper('a4', $paper);

                $zip->addFromString($name, $pdf->output());
            }
        }

        $zip->close();

        return response()->download($zipPath, $prefix.'-'.now()->format('Ymd-His').'.zip')->deleteFileAfterSend(true);
    }

    protected function isDownloadable(object $document): bool
    {
        if ($this->publicFilePath($document->file_path ?? $document->uploaded_file_path ?? null)) {
            return true;
        }

        return ($document->source_type ?? null) === 'budget_report' && !empty($document->template_data);
    }

    protected function publicFilePath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $fullPath = public_path(ltrim($path, '/\\'));
        $publicRoot = realpath(public_path());
        $realPath = realpath($fullPath);

        if (!$publicRoot || !$realPath || !Str::startsWith($realPath, $publicRoot) || !File::isFile($realPath)) {
            return null;
        }

        return $realPath;
    }

    protected function downloadName(object $document): string
    {
        $title = Str::slug($document->title ?: $document->source_type ?: 'archive-document') ?: 'archive-document';
        $extension = $this->publicFilePath($document->file_path ?? null)
            ? pathinfo($document->file_path, PATHINFO_EXTENSION)
            : 'pdf';

        return $title.'-'.$document->source_id.'.'.$extension;
    }

    protected function inferFileSize(?string $path): string
    {
        if (!$path) {
            return 'N/A';
        }

        return Str::endsWith(strtolower($path), '.pdf') ? 'PDF' : 'File';
    }

    protected function administrationTerms(): Collection
    {
        return DB::table('administration_terms')
            ->where('status', 'completed')
            ->orderByDesc('start_year')
            ->orderByDesc('term_id')
            ->get([
                'term_id',
                'start_year',
                'end_year',
            ]);
    }

    protected function completedTermIds(?int $termId = null): Collection
    {
        $query = DB::table('administration_terms')
            ->where('status', 'completed');

        if ($termId) {
            $query->where('term_id', $termId);
        }

        return $query
            ->orderByDesc('term_id')
            ->pluck('term_id')
            ->map(fn ($termId) => (int) $termId)
            ->values();
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
            ['link' => route('sk_pres.leadership'), 'icon' => '&#128101;', 'label' => 'Leadership'],
            ['link' => route('sk_pres.archive'), 'icon' => '&#128450;&#65039;', 'label' => 'Archive'],
            ['link' => route('sk_pres.user-management'), 'icon' => '&#128100;', 'label' => 'User Management'],
        ];
    }
}