<?php

namespace App\Http\Controllers\sk_chairman;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
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
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman',403);

        $user=auth()->user();
        $fullName=trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';
        $barangayName=$user->barangay->barangay_name ?? 'Barangay';
        $currentTermId=$this->currentTermId();

        $slots=app(SubmissionSlotService::class)
            ->chairmanReportSlots((int)$user->barangay_id);

        $reportsQuery=DB::table('accomplishment_reports as ar')
            ->where('ar.barangay_id',$user->barangay_id);

        if($currentTermId){
            $reportsQuery->where('ar.term_id',$currentTermId);
        }else{
            $reportsQuery->whereRaw('1 = 0');
        }

        if(Schema::hasTable('submission_quality_reviews')){
            $reportsQuery
                ->leftJoin('submission_quality_reviews as qr',function($join){
                    $join->on('qr.source_id','=','ar.report_id')
                        ->where('qr.source_type','=','accomplishment_report');
                })
                ->select(
                    'ar.*',
                    'qr.status as quality_status',
                    'qr.remarks as quality_remarks',
                    'qr.reviewed_at as quality_reviewed_at'
                );
        }else{
            $reportsQuery->select(
                'ar.*',
                DB::raw("'pending' as quality_status"),
                DB::raw('NULL as quality_remarks'),
                DB::raw('NULL as quality_reviewed_at')
            );
        }

        $reports=$reportsQuery
            ->orderByDesc('ar.submitted_at')
            ->orderByDesc('ar.created_at')
            ->get()
            ->map(function($report){
                $status=strtolower((string)($report->status ?? 'submitted'));

                $method=strtolower((string)$report->submission_method)==='file_upload'
                    ? 'pdf'
                    : 'manual';

                $qualityStatus=strtolower(
                    (string)($report->quality_status ?? 'pending')
                );

                $report->submitted_at=$report->submitted_at
                    ? Carbon::parse($report->submitted_at)
                    : Carbon::parse($report->created_at);

                $report->status_badge=match($status){
                    'approved'=>'bg-green-100 text-green-600',
                    'rejected'=>'bg-red-100 text-red-600',
                    'reviewed'=>'bg-blue-100 text-blue-600',
                    default=>'bg-yellow-100 text-yellow-600',
                };

                $report->method_badge=$method==='pdf'
                    ? 'bg-purple-100 text-purple-600'
                    : 'bg-gray-100 text-gray-600';

                $report->method_label=$method==='pdf'
                    ? 'PDF Upload'
                    : 'Manual';

                $report->period_label=$this->reportPeriodLabel(
                    $report
                );

                $report->quality_status=$qualityStatus;

                $report->quality_status_label=match($qualityStatus){
                    'approved'=>'Approved',
                    'needs_revision'=>'Needs Revision',
                    default=>'Pending Review',
                };

                $report->quality_status_badge=match($qualityStatus){
                    'approved'=>'bg-green-100 text-green-700',
                    'needs_revision'=>'bg-red-100 text-red-700',
                    default=>'bg-yellow-100 text-yellow-700',
                };

                $report->download_url=$method==='pdf'
                    && !empty($report->uploaded_file_path)
                        ? asset($report->uploaded_file_path)
                        : null;

                $report->view_url=$report->download_url;

                return $report;
            });

        return view('sk_chairman.reports',[
            'fullName'=>$fullName,
            'barangayName'=>$barangayName,
            'initials'=>strtoupper(
                substr($user->first_name ?? 'S',0,1).
                substr($user->last_name ?? 'K',0,1)
            ),
            'menuItems'=>$this->menuItems(),
            'currentUrl'=>url()->current(),
            'slots'=>$slots,
            'submissions'=>$reports,
            'pageTitle'=>'Accomplishment Reports',
            'pageDescription'=>'Submit accomplishment reports only through active slots created by the SK President.',
            'slotSectionTitle'=>'Active Report Slots',
            'slotEmptyMessage'=>'No active accomplishment report slots right now.',
            'slotActionLabel'=>'Submit Report File',
            'roleLabel'=>'SK Chairman',
            'submissionType'=>'report',
            'storeRoute'=>route('sk_chairman.reports.store'),
            'profileRoute'=>route('sk_chairman.profile'),
            'allowResubmission'=>true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman',403);

        $user=auth()->user();

        $validated=$request->validate([
            'slot_id'=>[
                'required',
                'integer',
            ],
            'report_file'=>[
                'required',
                'file',
                'mimes:pdf',
                'max:5120',
            ],
            'report_type'=>[
                'nullable',
                'in:monthly,quarterly,annual',
            ],
            'reporting_year'=>[
                'nullable',
                'integer',
                'min:2000',
                'max:2100',
            ],
            'reporting_month'=>[
                'nullable',
                'integer',
                'min:1',
                'max:12',
            ],
            'reporting_quarter'=>[
                'nullable',
                'in:Q1,Q2,Q3,Q4',
            ],
        ]);

        $slot=app(SubmissionSlotService::class)
            ->resolveOpenSlot(
                (int)$validated['slot_id'],
                'accomplishment_report',
                [
                    'SK Chairman',
                    'Both',
                ]
            );

        if(!$slot){
            return back()->with(
                'report_error',
                'That accomplishment report slot is no longer available.'
            );
        }

        $termId=(int)($slot->term_id ?? 0);

        if($termId<=0){
            return back()->with(
                'report_error',
                'The submission slot is not connected to an active administration term.'
            );
        }

        $existingReport=DB::table('accomplishment_reports')
            ->where('term_id',$termId)
            ->where('barangay_id',$user->barangay_id)
            ->where('slot_id',$slot->slot_id)
            ->first();

        $isResubmission=(bool)$existingReport;

        if(
            $existingReport
            &&
            Schema::hasTable('submission_quality_reviews')
        ){
            $qualityReview=DB::table('submission_quality_reviews')
                ->where(
                    'source_type',
                    'accomplishment_report'
                )
                ->where(
                    'source_id',
                    $existingReport->report_id
                )
                ->first();

            if(
                $qualityReview
                &&
                strtolower(
                    (string)$qualityReview->status
                )==='approved'
            ){
                return back()->with(
                    'report_error',
                    'This accomplishment report has already been approved for Quality Documentation and can no longer be replaced.'
                );
            }
        }

        if($existingReport){
            $reportType=$existingReport->report_type
                ?? 'monthly';

            $reportingYear=(int)(
                $existingReport->reporting_year
                ?? now()->year
            );

            $reportingMonth=
                $existingReport->reporting_month;

            $reportingQuarter=
                $existingReport->reporting_quarter;
        }else{
            $reportType=$validated['report_type']
                ?? 'monthly';

            $reportingYear=(int)(
                $validated['reporting_year']
                ?? now()->year
            );

            $reportingMonth=$reportType==='monthly'
                ? (int)(
                    $validated['reporting_month']
                    ?? now()->month
                )
                : null;

            $reportingQuarter=$reportType==='quarterly'
                ? (
                    $validated['reporting_quarter']
                    ?? 'Q'.ceil(now()->month/3)
                )
                : null;
        }

        $directory=public_path(
            'uploads/reports'
        );

        File::ensureDirectoryExists(
            $directory
        );

        $file=$request->file(
            'report_file'
        );

        $filename=
            'REP_'.
            time().
            '_'.
            $user->barangay_id.
            '.pdf';

        $uploadName=
            $file->getClientOriginalName();

        $uploadPath=
            'uploads/reports/'.
            $filename;

        $file->move(
            $directory,
            $filename
        );

        $data=[
            'term_id'=>$termId,
            'user_id'=>$user->user_id,
            'barangay_id'=>$user->barangay_id,
            'slot_id'=>$slot->slot_id,
            'report_type'=>$reportType,
            'submission_method'=>'file_upload',
            'title'=>$slot->title,
            'reporting_year'=>$reportingYear,

            'reporting_month'=>$reportType==='monthly'
                ? $reportingMonth
                : null,

            'reporting_quarter'=>$reportType==='quarterly'
                ? $reportingQuarter
                : null,

            'generated_pdf_path'=>null,
            'uploaded_file_name'=>$uploadName,
            'uploaded_file_path'=>$uploadPath,
            'status'=>'submitted',
            'remarks'=>null,
            'submitted_at'=>now(),
            'created_at'=>now(),
        ];

        try{
            $reportId=DB::transaction(function() use(
                $data,
                $slot,
                $termId,
                $existingReport
            ){
                $reportId=$this->saveReportSubmission(
                    $data,
                    (int)$slot->slot_id,
                    $termId
                );

                if($existingReport){
                    $this->resetQualityReviewForResubmission(
                        $reportId
                    );
                }

                return $reportId;
            });
        }catch(\Throwable $e){
            $this->deleteReportFile(
                $uploadPath
            );

            return back()->with(
                'report_error',
                'The report could not be saved. Please try again.'
            );
        }

        if(
            $existingReport
            &&
            !empty($existingReport->uploaded_file_path)
            &&
            $existingReport->uploaded_file_path!==$uploadPath
        ){
            $this->deleteReportFile(
                $existingReport->uploaded_file_path
            );
        }

        if(!$isResubmission){
            $this->scoreSubmission(
                $slot,
                $reportId,
                'accomplishment_report'
            );
        }

        $submission=DB::table('accomplishment_reports')
            ->where('report_id',$reportId)
            ->where('term_id',$termId)
            ->first();

        if($submission){
            $notifications=app(
                NotificationService::class
            );

            if($isResubmission){
                $notifications
                    ->notifySubmissionResubmitted(
                        $submission,
                        'accomplishment_report',
                        $user
                    );
            }else{
                $notifications
                    ->notifySubmissionReceived(
                        $submission,
                        'accomplishment_report',
                        $user
                    );
            }
        }

        if($isResubmission){
            return redirect()
                ->route('sk_chairman.reports')
                ->with(
                    'report_success',
                    'Your corrected report has been resubmitted successfully and returned to Pending Quality Review.'
                );
        }

        return redirect()
            ->route('sk_chairman.reports')
            ->with(
                'report_success',
                'Your report has been submitted successfully.'
            );
    }

    protected function scoreSubmission(
        object $slot,
        int $sourceId,
        string $sourceType
    ): void {
        $user=auth()->user();

        $points=app(
            RankingPointsService::class
        );

        $isOnTime=now()->lessThanOrEqualTo(
            Carbon::parse(
                $slot->end_date
            )->endOfDay()
        );

        $submissionAction=$isOnTime
            ? RankingPointsService::ON_TIME_REPORT_SUBMISSION
            : RankingPointsService::LATE_SUBMISSION;

        $points->award(
            (int)$user->barangay_id,
            $submissionAction,
            $sourceType,
            $sourceId,
            (int)$user->user_id
        );
    }

    protected function saveReportSubmission(
        array $data,
        int $slotId,
        int $termId
    ): int {
        $existing=DB::table('accomplishment_reports')
            ->where('term_id',$termId)
            ->where(
                'barangay_id',
                auth()->user()->barangay_id
            )
            ->where('slot_id',$slotId)
            ->first();

        if($existing){
            unset(
                $data['created_at']
            );

            DB::table('accomplishment_reports')
                ->where(
                    'report_id',
                    $existing->report_id
                )
                ->where(
                    'term_id',
                    $termId
                )
                ->update(
                    $data
                );

            return (int)$existing->report_id;
        }

        return (int)DB::table(
            'accomplishment_reports'
        )->insertGetId(
            $data,
            'report_id'
        );
    }

    protected function resetQualityReviewForResubmission(
        int $reportId
    ): void {
        if(
            !Schema::hasTable(
                'submission_quality_reviews'
            )
        ){
            return;
        }

        $review=DB::table(
            'submission_quality_reviews'
        )
            ->where(
                'source_type',
                'accomplishment_report'
            )
            ->where(
                'source_id',
                $reportId
            )
            ->first();

        if(
            !$review
            ||
            strtolower(
                (string)$review->status
            )!=='needs_revision'
        ){
            return;
        }

        DB::table(
            'submission_quality_reviews'
        )
            ->where(
                'review_id',
                $review->review_id
            )
            ->update([
                'reviewer_id'=>null,
                'status'=>'pending',
                'complete_contents'=>false,
                'correct_document'=>false,
                'correct_period'=>false,
                'readable_organized'=>false,
                'supporting_documents'=>false,
                'remarks'=>null,
                'reviewed_at'=>null,
                'updated_at'=>now(),
            ]);
    }

    protected function reportPeriodLabel(
        object $report
    ): string {
        $reportType=strtolower(
            (string)(
                $report->report_type
                ?? ''
            )
        );

        $year=(int)(
            $report->reporting_year
            ?? now()->year
        );

        if($reportType==='monthly'){
            $month=(int)(
                $report->reporting_month
                ?? 0
            );

            return $month>=1
                && $month<=12
                    ? Carbon::create(
                        $year,
                        $month,
                        1
                    )->format('F Y')
                    : 'Monthly '.$year;
        }

        if($reportType==='quarterly'){
            return (
                $report->reporting_quarter
                ?: 'Quarterly'
            ).' '.$year;
        }

        if($reportType==='annual'){
            return 'Annual '.$year;
        }

        return 'Accomplishment Report';
    }

    protected function deleteReportFile(
        ?string $path
    ): void {
        if(!$path){
            return;
        }

        $path=str_replace(
            '\\',
            '/',
            ltrim(
                $path,
                '/'
            )
        );

        if(
            !str_starts_with(
                $path,
                'uploads/reports/'
            )
        ){
            return;
        }

        $fullPath=public_path(
            $path
        );

        if(File::exists($fullPath)){
            File::delete(
                $fullPath
            );
        }
    }

    protected function currentTermId(): ?int
    {
        $termId=DB::table(
            'administration_terms'
        )
            ->where(
                'status',
                'current'
            )
            ->orderByDesc(
                'term_id'
            )
            ->value(
                'term_id'
            );

        return $termId
            ? (int)$termId
            : null;
    }

    protected function menuItems(): array
    {
        return [
            [
                'link'=>route('sk_chairman.home'),
                'icon'=>'&#127968;',
                'label'=>'Home',
            ],
            [
                'link'=>route('sk_chairman.reports'),
                'icon'=>'&#128196;',
                'label'=>'Reports',
            ],
            [
                'link'=>route('sk_chairman.budget'),
                'icon'=>'&#128229;',
                'label'=>'Budget',
            ],
            [
                'link'=>route('sk_chairman.announcements'),
                'icon'=>'&#128226;',
                'label'=>'Announcements',
            ],
            [
                'link'=>route('sk_chairman.calendar'),
                'icon'=>'&#128197;',
                'label'=>'Calendar',
            ],
            [
                'link'=>route('sk_chairman.chat'),
                'icon'=>'&#128172;',
                'label'=>'Chat',
            ],
            [
                'link'=>route('sk_chairman.meetings'),
                'icon'=>'&#128222;',
                'label'=>'Meetings',
            ],
            [
                'link'=>route('sk_chairman.rankings'),
                'icon'=>'&#127942;',
                'label'=>'Rankings',
            ],
            [
                'link'=>route('sk_chairman.leadership'),
                'icon'=>'&#128101;',
                'label'=>'Leadership',
            ],
            [
                'link'=>route('sk_chairman.archive'),
                'icon'=>'&#128465;',
                'label'=>'Archive',
            ],
        ];
    }
}