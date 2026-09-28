<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\sk_pres\ArchiveController;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

// Signed mobile previews include current-term documents; web archive rules stay unchanged.
class MobileDocumentController extends ArchiveController
{
    public function mobileView(string $sourceType, int $sourceId)
    {
        // Signed mobile links also preview reports from the current term.
        $document = $this->downloadableDocument($sourceType, $sourceId, false);

        abort_unless($document, 404);

        $filePath = $this->publicFilePath($document->uploaded_file_path ?? null)
            ?: $this->publicFilePath($document->generated_pdf_path ?? null);
        if ($filePath) {
            return response()->file($filePath);
        }

        if ($sourceType === 'budget_report' && ! empty($document->template_data)) {
            $data = json_decode($document->template_data, true) ?: [];
            $paper = ($data['report_type'] ?? 'quarterly') === 'monthly' ? 'portrait' : 'landscape';

            return Pdf::loadView('shared.budget-template-download', [
                'data' => $data,
                'barangayName' => $document->barangay_name ?? 'Barangay',
            ])->setPaper('a4', $paper)->stream('budget-template-'.($document->budget_report_id ?? time()).'.pdf');
        }

        abort(404);
    }

    protected function downloadableDocument(string $sourceType, int $sourceId, bool $completedOnly = true): ?object
    {
        $completedTermIds = $completedOnly ? $this->completedTermIds() : collect();

        if ($completedOnly && $completedTermIds->isEmpty()) {
            return null;
        }

        if ($sourceType === 'accomplishment_report') {
            return DB::table('accomplishment_reports')
                ->when($completedOnly, fn ($query) => $query->whereIn('term_id', $completedTermIds))
                ->where('report_id', $sourceId)
                ->first();
        }

        if ($sourceType === 'budget_report') {
            return DB::table('budget_reports as br')
                ->leftJoin('barangays as b', 'br.barangay_id', '=', 'b.barangay_id')
                ->when($completedOnly, fn ($query) => $query->whereIn('br.term_id', $completedTermIds))
                ->where('br.budget_report_id', $sourceId)
                ->select('br.*', 'b.barangay_name')
                ->first();
        }

        return null;
    }
}
