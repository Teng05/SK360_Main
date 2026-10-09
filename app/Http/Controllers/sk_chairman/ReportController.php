<?php
namespace App\Http\Controllers\sk_chairman;
use App\Http\Controllers\Controller;
use App\Services\NotificationService;
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
    public function index(Request $request): View
    {
        abort_unless(auth()->check() && auth()->user()->role==='sk_chairman',403);
        $user=auth()->user();
        $fullName=trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';
        $barangayName=$user->barangay->barangay_name ?? 'Barangay';
        $currentTermId=$this->currentTermId();
        $slots=app(SubmissionSlotService::class)->chairmanReportSlots((int)$user->barangay_id);
        $reportsQuery=DB::table('accomplishment_reports as ar')->where('ar.barangay_id',$user->barangay_id);
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
                $method=strtolower((string)$report->submission_method)==='file_upload' ? 'pdf' : 'manual';
                $qualityStatus=strtolower((string)($report->quality_status ?? 'pending'));
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
                $report->method_label=$method==='pdf' ? 'PDF Upload' : 'Manual';
                $report->period_label=$this->reportPeriodLabel($report);
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
                $report->download_url=$method==='pdf' && !empty($report->uploaded_file_path)
                    ? asset($report->uploaded_file_path)
                    : null;
                $report->view_url=$report->download_url;
                return $report;
            });
        $reportYears=$reports
            ->map(fn($report)=>(int)($report->reporting_year ?: $report->submitted_at->year))
            ->filter(fn($year)=>$year>=2000)
            ->unique()
            ->sortDesc()
            ->values();
        $reportFilters=$this->reportFilters($request,$reportYears);
        $reports=$reports->filter(fn($report)=>$this->matchesReportFilter($report,$reportFilters))->values();
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
            'reportFilters'=>$reportFilters,
            'reportYears'=>$reportYears,
            'reportMonths'=>$this->reportMonths(),
            'reportQuarters'=>['Q1','Q2','Q3','Q4'],
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
        abort_unless(auth()->check() && auth()->user()->role==='sk_chairman',403);
        $user=auth()->user();
        $validated=$request->validate([
            'slot_id'=>['required','integer'],
            'report_file'=>['bail','required','file','mimes:pdf'],
            'report_type'=>['nullable','in:monthly,quarterly,annual'],
            'reporting_year'=>['nullable','integer','min:2000','max:2100'],
            'reporting_month'=>['nullable','integer','min:1','max:12'],
            'reporting_quarter'=>['nullable','in:Q1,Q2,Q3,Q4'],
        ],[
            'slot_id.required'=>'The submission slot is required.',
            'slot_id.integer'=>'The selected submission slot is invalid.',
            'report_file.required'=>'Report file is required. Please select a PDF file before submitting.',
            'report_file.file'=>'The selected report must be a valid file.',
            'report_file.uploaded'=>'The report file could not be uploaded. Please select the file again and retry.',
            'report_file.mimes'=>'The report file must be a PDF.',
            'report_type.in'=>'The selected report type is invalid.',
            'reporting_year.integer'=>'The reporting year must be a valid year.',
            'reporting_year.min'=>'The reporting year must be 2000 or later.',
            'reporting_year.max'=>'The reporting year must not be later than 2100.',
            'reporting_month.integer'=>'The reporting month is invalid.',
            'reporting_month.min'=>'The reporting month is invalid.',
            'reporting_month.max'=>'The reporting month is invalid.',
            'reporting_quarter.in'=>'The reporting quarter is invalid.',
        ]);
        $slot=app(SubmissionSlotService::class)->resolveOpenSlot(
            (int)$validated['slot_id'],
            'accomplishment_report',
            ['SK Chairman','Both']
        );
        if(!$slot){
            return back()->withInput()->with(
                'report_error',
                'That accomplishment report slot is no longer available.'
            );
        }
        $termId=(int)($slot->term_id ?? 0);
        if($termId<=0){
            return back()->withInput()->with(
                'report_error',
                'The submission slot is not connected to an active administration term.'
            );
        }
        $existingReport=DB::table('accomplishment_reports')
            ->where('term_id',$termId)
            ->where('barangay_id',$user->barangay_id)
            ->where('slot_id',$slot->slot_id)
            ->first();
        $qualityStatus='pending';
        if($existingReport && Schema::hasTable('submission_quality_reviews')){
            $qualityReview=DB::table('submission_quality_reviews')
                ->where('source_type','accomplishment_report')
                ->where('source_id',$existingReport->report_id)
                ->first();
            $qualityStatus=strtolower((string)($qualityReview->status ?? 'pending'));
        }
        if($existingReport && $qualityStatus==='approved'){
            return back()->withInput()->with(
                'report_error',
                'This accomplishment report has already been approved for Quality Documentation and can no longer be replaced.'
            );
        }
        if($existingReport && $qualityStatus!=='needs_revision'){
            return back()->withInput()->with(
                'report_error',
                'This accomplishment report is already submitted and pending review. It can only be replaced after the SK President requests a revision.'
            );
        }
        $isResubmission=(bool)$existingReport;
        if($existingReport){
            $reportType=$existingReport->report_type ?? 'monthly';
            $reportingYear=(int)($existingReport->reporting_year ?? now()->year);
            $reportingMonth=$existingReport->reporting_month;
            $reportingQuarter=$existingReport->reporting_quarter;
        }else{
            $reportType=$validated['report_type'] ?? 'monthly';
            $reportingYear=(int)($validated['reporting_year'] ?? now()->year);
            $reportingMonth=$reportType==='monthly'
                ? (int)($validated['reporting_month'] ?? now()->month)
                : null;
            $reportingQuarter=$reportType==='quarterly'
                ? ($validated['reporting_quarter'] ?? 'Q'.ceil(now()->month/3))
                : null;
        }
        $directory=public_path('uploads/reports');
        File::ensureDirectoryExists($directory);
        $file=$request->file('report_file');
        $filename='REP_'.time().'_'.$user->barangay_id.'.pdf';
        $uploadName=$file->getClientOriginalName();
        $uploadPath='uploads/reports/'.$filename;
        $file->move($directory,$filename);
        $data=[
            'term_id'=>$termId,
            'user_id'=>$user->user_id,
            'barangay_id'=>$user->barangay_id,
            'slot_id'=>$slot->slot_id,
            'report_type'=>$reportType,
            'submission_method'=>'file_upload',
            'title'=>$slot->title,
            'reporting_year'=>$reportingYear,
            'reporting_month'=>$reportType==='monthly' ? $reportingMonth : null,
            'reporting_quarter'=>$reportType==='quarterly' ? $reportingQuarter : null,
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
                    $this->resetQualityReviewForResubmission($reportId);
                }
                return $reportId;
            });
        }catch(\Throwable $e){
            $this->deleteReportFile($uploadPath);
            return back()->withInput()->with(
                'report_error',
                'The report could not be saved. Please try again.'
            );
        }
        if(
            $existingReport &&
            !empty($existingReport->uploaded_file_path) &&
            $existingReport->uploaded_file_path!==$uploadPath
        ){
            $this->deleteReportFile($existingReport->uploaded_file_path);
        }
        $this->notifyPresidentOfReportSubmission(
            $reportId,
            $termId,
            $isResubmission
        );
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
    protected function saveReportSubmission(array $data,int $slotId,int $termId): int
    {
        $existing=DB::table('accomplishment_reports')
            ->where('term_id',$termId)
            ->where('barangay_id',auth()->user()->barangay_id)
            ->where('slot_id',$slotId)
            ->first();
        if($existing){
            unset($data['created_at']);
            DB::table('accomplishment_reports')
                ->where('report_id',$existing->report_id)
                ->where('term_id',$termId)
                ->update($data);
            return (int)$existing->report_id;
        }
        return (int)DB::table('accomplishment_reports')
            ->insertGetId(
                $data,
                'report_id'
            );
    }
    protected function resetQualityReviewForResubmission(int $reportId): void
    {
        if(!Schema::hasTable('submission_quality_reviews')){
            return;
        }
        $review=DB::table('submission_quality_reviews')
            ->where('source_type','accomplishment_report')
            ->where('source_id',$reportId)
            ->first();
        if(!$review || strtolower((string)$review->status)!=='needs_revision'){
            return;
        }
        DB::table('submission_quality_reviews')
            ->where('review_id',$review->review_id)
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
    protected function notifyPresidentOfReportSubmission(
        int $reportId,
        int $termId,
        bool $isResubmission
    ): void {
        $submission=DB::table('accomplishment_reports')
            ->where('report_id',$reportId)
            ->where('term_id',$termId)
            ->first();
        if(!$submission || !auth()->check()){
            return;
        }
        try{
            $notifications=app(NotificationService::class);
            $user=auth()->user();
            if($isResubmission){
                $notifications->notifySubmissionResubmitted(
                    $submission,
                    'accomplishment_report',
                    $user
                );
            }else{
                $notifications->notifySubmissionReceived(
                    $submission,
                    'accomplishment_report',
                    $user
                );
            }
        }catch(\Throwable $e){
            report($e);
        }
    }
    protected function reportFilters(Request $request,$years): array
    {
        $period=(string)$request->query('period','all');
        if(!in_array($period,['all','monthly','quarterly','annual'],true)) $period='all';
        $year=(string)$request->query('year','all');
        if($year!=='all'&&!$years->contains((int)$year)) $year='all';
        $month=(int)$request->query('month',now()->month);
        $quarter=(string)$request->query('quarter','Q'.(int)ceil(now()->month/3));
        return [
            'period'=>$period,
            'year'=>$year,
            'month'=>$month>=1&&$month<=12?$month:now()->month,
            'quarter'=>in_array($quarter,['Q1','Q2','Q3','Q4'],true)?$quarter:'Q'.(int)ceil(now()->month/3),
        ];
    }
    protected function reportMonths(): array
    {
        return [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
    }
    protected function matchesReportFilter(object $report,array $filters): bool
    {
        $type=strtolower((string)($report->report_type ?? ''));
        return ($filters['period']==='all'||$type===$filters['period'])
            && ($filters['year']==='all'||(int)($report->reporting_year ?: $report->submitted_at->year)===(int)$filters['year'])
            && ($filters['period']!=='monthly'||(int)($report->reporting_month ?? 0)===(int)$filters['month'])
            && ($filters['period']!=='quarterly'||(string)($report->reporting_quarter ?? '')===$filters['quarter']);
    }
    protected function reportPeriodLabel(object $report): string
    {
        $reportType=strtolower((string)($report->report_type ?? ''));
        $year=(int)($report->reporting_year ?? now()->year);
        if($reportType==='monthly'){
            $month=(int)($report->reporting_month ?? 0);
            return $month>=1 && $month<=12
                ? Carbon::create($year,$month,1)->format('F Y')
                : 'Monthly '.$year;
        }
        if($reportType==='quarterly'){
            return ($report->reporting_quarter ?: 'Quarterly').' '.$year;
        }
        if($reportType==='annual'){
            return 'Annual '.$year;
        }
        return 'Accomplishment Report';
    }
    protected function deleteReportFile(?string $path): void
    {
        if(!$path){
            return;
        }
        $path=str_replace('\\\\','/',ltrim($path,'/'));
        if(!str_starts_with($path,'uploads/reports/')){
            return;
        }
        $fullPath=public_path($path);
        if(File::exists($fullPath)){
            File::delete($fullPath);
        }
    }
    protected function currentTermId(): ?int
    {
        $termId=DB::table('administration_terms')
            ->where('status','current')
            ->orderByDesc('term_id')
            ->value('term_id');
        return $termId ? (int)$termId : null;
    }
    protected function menuItems(): array
    {
        return [
            ['link'=>route('sk_chairman.home'),'icon'=>'&#127968;','label'=>'Home'],
            ['link'=>route('sk_chairman.reports'),'icon'=>'&#128196;','label'=>'Reports'],
            ['link'=>route('sk_chairman.budget'),'icon'=>'&#128229;','label'=>'Budget'],
            ['link'=>route('sk_chairman.announcements'),'icon'=>'&#128226;','label'=>'Announcements'],
            ['link'=>route('sk_chairman.calendar'),'icon'=>'&#128197;','label'=>'Calendar'],
            ['link'=>route('sk_chairman.chat'),'icon'=>'&#128172;','label'=>'Chat'],
            ['link'=>route('sk_chairman.meetings'),'icon'=>'&#128222;','label'=>'Meetings'],
            ['link'=>route('sk_chairman.rankings'),'icon'=>'&#127942;','label'=>'Rankings'],
            ['link'=>route('sk_chairman.leadership'),'icon'=>'&#128101;','label'=>'Leadership'],
            ['link'=>route('sk_chairman.archive'),'icon'=>'&#128465;','label'=>'Archive'],
        ];
    }
}
