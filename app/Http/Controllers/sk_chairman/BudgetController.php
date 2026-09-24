<?php

namespace App\Http\Controllers\sk_chairman;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Services\SubmissionSlotService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman',403);

        $user=auth()->user();
        $fullName=trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';
        $barangayName=$user->barangay->barangay_name ?? 'Barangay';
        $currentTermId=$this->currentTermId();

        $slots=app(SubmissionSlotService::class)
            ->chairmanBudgetSlots((int)$user->barangay_id);

        $submissionsQuery=DB::table('budget_reports as br')
            ->leftJoin('submission_slots as ss','ss.slot_id','=','br.slot_id')
            ->where('br.barangay_id',$user->barangay_id);

        if($currentTermId){
            $submissionsQuery->where('br.term_id',$currentTermId);
        }else{
            $submissionsQuery->whereRaw('1 = 0');
        }

        if(Schema::hasTable('submission_quality_reviews')){
            $submissionsQuery
                ->leftJoin('submission_quality_reviews as qr',function($join){
                    $join->on('qr.source_id','=','br.budget_report_id')
                        ->where('qr.source_type','=','budget_report');
                })
                ->select([
                    'br.*',
                    'ss.budget_category as slot_budget_category',
                    'ss.fiscal_year as slot_fiscal_year',
                    'ss.budget_period_type as slot_budget_period_type',
                    'ss.fiscal_month as slot_fiscal_month',
                    'ss.fiscal_quarter as slot_fiscal_quarter',
                    'ss.fiscal_half as slot_fiscal_half',
                    'qr.status as quality_status',
                    'qr.remarks as quality_remarks',
                    'qr.reviewed_at as quality_reviewed_at',
                ]);
        }else{
            $submissionsQuery->select([
                'br.*',
                'ss.budget_category as slot_budget_category',
                'ss.fiscal_year as slot_fiscal_year',
                'ss.budget_period_type as slot_budget_period_type',
                'ss.fiscal_month as slot_fiscal_month',
                'ss.fiscal_quarter as slot_fiscal_quarter',
                'ss.fiscal_half as slot_fiscal_half',
                DB::raw("'pending' as quality_status"),
                DB::raw('NULL as quality_remarks'),
                DB::raw('NULL as quality_reviewed_at'),
            ]);
        }

        $submissions=$submissionsQuery
            ->orderByDesc('br.submitted_at')
            ->orderByDesc('br.created_at')
            ->get()
            ->map(function($submission){
                $status=strtolower((string)($submission->status ?? 'submitted'));
                $method=strtolower((string)$submission->submission_method)==='file_upload' ? 'pdf' : 'template';
                $qualityStatus=strtolower((string)($submission->quality_status ?? 'pending'));

                $submission->submitted_at=$submission->submitted_at
                    ? Carbon::parse($submission->submitted_at)
                    : Carbon::parse($submission->created_at);

                $submission->status_badge=match($status){
                    'archived','recorded'=>'bg-green-100 text-green-600',
                    'draft'=>'bg-gray-100 text-gray-600',
                    default=>'bg-yellow-100 text-yellow-600',
                };

                $submission->method_badge=$method==='pdf'
                    ? 'bg-purple-100 text-purple-600'
                    : 'bg-blue-100 text-blue-600';

                $submission->method_label=$method==='pdf' ? 'PDF Upload' : 'Template';
                $submission->period_label=$this->periodLabel($submission);

                $submission->quality_status=$qualityStatus;
                $submission->quality_status_label=match($qualityStatus){
                    'approved'=>'Approved',
                    'needs_revision'=>'Needs Revision',
                    default=>'Pending Review',
                };

                $submission->quality_status_badge=match($qualityStatus){
                    'approved'=>'bg-green-100 text-green-700',
                    'needs_revision'=>'bg-red-100 text-red-700',
                    default=>'bg-yellow-100 text-yellow-700',
                };

                $submission->download_url=$method==='pdf' && !empty($submission->uploaded_file_path)
                    ? asset($submission->uploaded_file_path)
                    : route('sk_chairman.budget.template.download',$submission->budget_report_id);

                $submission->view_url=$method==='pdf' && !empty($submission->uploaded_file_path)
                    ? asset($submission->uploaded_file_path)
                    : route('sk_chairman.budget.template.view',$submission->budget_report_id);

                return $submission;
            });

        return view('sk_chairman.budget',[
            'fullName'=>$fullName,
            'barangayName'=>$barangayName,
            'initials'=>strtoupper(
                substr($user->first_name ?? 'S',0,1).
                substr($user->last_name ?? 'K',0,1)
            ),
            'menuItems'=>$this->menuItems(),
            'currentUrl'=>url()->current(),
            'pageTitle'=>'Budget Submissions',
            'pageDescription'=>'Submit budget and financial documents only through active slots created by the SK President.',
            'slotSectionTitle'=>'Active Budget Slots',
            'slotEmptyMessage'=>'No active budget submission slots right now.',
            'slotActionLabel'=>'Submit Budget File',
            'roleLabel'=>'SK Chairman',
            'slots'=>$slots,
            'submissions'=>$submissions,
            'submissionType'=>'budget',
            'storeRoute'=>route('sk_chairman.budget.store'),
            'profileRoute'=>route('sk_chairman.profile'),
            'allowResubmission'=>true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman',403);

        $user=auth()->user();

        $validated=$request->validate([
            'slot_id'=>['required','integer'],
            'sub_method'=>['required','in:template,pdf'],
            'annual_budget_amount'=>['nullable','numeric','min:0.01'],
            'actual_expenditure'=>['nullable','numeric','min:0.01'],
            'report_file'=>['nullable','file','mimes:pdf','max:5120'],
        ]);

        $slot=app(SubmissionSlotService::class)->resolveOpenSlot(
            (int)$validated['slot_id'],
            'budget_report',
            ['SK Chairman','Both']
        );

        if(!$slot){
            return back()->with(
                'report_error',
                'That budget submission slot is no longer available.'
            );
        }

        $termId=(int)($slot->term_id ?? 0);

        if($termId<=0){
            return back()->with(
                'report_error',
                'The budget submission slot is not connected to an active administration term.'
            );
        }

        $existing=$this->existingBudgetSubmission(
            (int)$slot->slot_id,
            $termId
        );

        if($existing && $this->isQualityApproved((int)$existing->budget_report_id)){
            return back()->with(
                'report_error',
                'This budget document has already been approved for Quality Documentation and can no longer be replaced.'
            );
        }

        $isResubmission=(bool)$existing;
        $isAnnualBudget=($slot->budget_category ?? null)==='annual_budget';

        $isAnnualCoa=($slot->budget_category ?? null)==='coa_report'
            && ($slot->budget_period_type ?? null)==='annual';

        if($isAnnualBudget && !$request->filled('annual_budget_amount')){
            return back()
                ->withErrors([
                    'annual_budget_amount'=>'Please enter the Annual Budget amount.',
                ])
                ->withInput();
        }

        if($isAnnualCoa && !$request->filled('actual_expenditure')){
            return back()
                ->withErrors([
                    'actual_expenditure'=>'Please enter the Actual Expenditure.',
                ])
                ->withInput();
        }

        if($validated['sub_method']==='template'){
            if(!$this->templateAvailableForSlot($slot)){
                return back()->with(
                    'report_error',
                    'The SK360 system template is not available for this submission type. Please upload a PDF.'
                );
            }

            $params=[
                'slot_id'=>$slot->slot_id,
            ];

            if($isAnnualCoa){
                $params['actual_expenditure']=$validated['actual_expenditure'];
            }

            return redirect()->route(
                'sk_chairman.budget.template.create',
                $params
            );
        }

        if(!$request->hasFile('report_file')){
            return back()
                ->withErrors([
                    'report_file'=>'A PDF file is required for PDF Upload.',
                ])
                ->withInput();
        }

        $directory=public_path('uploads/budget_reports');
        File::ensureDirectoryExists($directory);

        $file=$request->file('report_file');
        $filename='BUD_'.time().'_'.$user->barangay_id.'.pdf';
        $uploadPath='uploads/budget_reports/'.$filename;

        $file->move($directory,$filename);

        $data=[
            'term_id'=>$termId,
            'user_id'=>$user->user_id,
            'barangay_id'=>$user->barangay_id,
            'slot_id'=>$slot->slot_id,
            'submission_method'=>'file_upload',
            'document_type'=>'financial_record',
            'fiscal_year'=>$slot->fiscal_year ?? now()->year,
            'title'=>$slot->title,
            'generated_pdf_path'=>null,
            'template_data'=>null,
            'uploaded_file_name'=>$file->getClientOriginalName(),
            'uploaded_file_path'=>$uploadPath,
            'total_amount'=>$isAnnualBudget
                ? (float)$validated['annual_budget_amount']
                : 0,
            'actual_expenditure'=>$isAnnualCoa
                ? (float)$validated['actual_expenditure']
                : null,
            'status'=>'recorded',
            'submitted_at'=>now(),
            'created_at'=>now(),
        ];

        $data=$this->applySlotMetadata($data,$slot);

        try{
            $budgetReportId=DB::transaction(function() use($data,$slot,$termId,$existing){
                $budgetReportId=$this->saveBudgetSubmission(
                    $data,
                    (int)$slot->slot_id,
                    $termId
                );

                if($existing){
                    $this->resetQualityReviewForResubmission($budgetReportId);
                }

                return $budgetReportId;
            });
        }catch(\Throwable $e){
            $this->deleteBudgetFile($uploadPath);

            return back()->with(
                'report_error',
                'The budget document could not be saved. Please try again.'
            );
        }

        if(
            $existing &&
            !empty($existing->uploaded_file_path) &&
            $existing->uploaded_file_path!==$uploadPath
        ){
            $this->deleteBudgetFile($existing->uploaded_file_path);
        }

        $this->notifyPresidentOfBudgetSubmission(
            $budgetReportId,
            $termId,
            $isResubmission
        );

        if($isResubmission){
            return redirect()
                ->route('sk_chairman.budget')
                ->with(
                    'report_success',
                    'Your corrected budget document has been resubmitted successfully and returned to Pending Quality Review.'
                );
        }

        return redirect()
            ->route('sk_chairman.budget')
            ->with(
                'report_success',
                $isAnnualBudget
                    ? 'Annual Budget submitted successfully.'
                    : (
                        $isAnnualCoa
                            ? 'Annual COA Report submitted successfully.'
                            : 'Budget document submitted successfully.'
                    )
            );
    }

    public function createTemplate(Request $request): View|RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman',403);

        $slotId=(int)$request->query('slot_id');

        $slot=app(SubmissionSlotService::class)->resolveOpenSlot(
            $slotId,
            'budget_report',
            ['SK Chairman','Both']
        );

        if(!$slot){
            return redirect()
                ->route('sk_chairman.budget')
                ->with(
                    'report_error',
                    'That budget submission slot is no longer available.'
                );
        }

        $termId=(int)($slot->term_id ?? 0);

        if($termId<=0){
            return redirect()
                ->route('sk_chairman.budget')
                ->with(
                    'report_error',
                    'The budget submission slot is not connected to an active administration term.'
                );
        }

        $existing=$this->existingBudgetSubmission($slotId,$termId);

        if($existing && $this->isQualityApproved((int)$existing->budget_report_id)){
            return redirect()
                ->route('sk_chairman.budget')
                ->with(
                    'report_error',
                    'This budget document has already been approved for Quality Documentation and can no longer be replaced.'
                );
        }

        if(!$this->templateAvailableForSlot($slot)){
            return redirect()
                ->route('sk_chairman.budget')
                ->with(
                    'report_error',
                    'The SK360 system template is not available for this submission type. Please upload a PDF.'
                );
        }

        $isAnnualCoa=($slot->budget_category ?? null)==='coa_report'
            && ($slot->budget_period_type ?? null)==='annual';

        $actualExpenditure=$isAnnualCoa
            ? $request->query('actual_expenditure')
            : null;

        if(
            $isAnnualCoa &&
            (!is_numeric($actualExpenditure) || (float)$actualExpenditure<=0)
        ){
            return redirect()
                ->route('sk_chairman.budget')
                ->with(
                    'report_error',
                    'Please enter a valid Actual Expenditure.'
                );
        }

        $user=auth()->user();

        return view('shared.budget-template-form',[
            'slot'=>$slot,
            'submitRoute'=>route('sk_chairman.budget.template.store'),
            'backRoute'=>route('sk_chairman.budget'),
            'fullName'=>trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User',
            'barangayName'=>$user->barangay->barangay_name ?? 'Barangay',
            'reportType'=>$slot->budget_period_type,
            'reportingYear'=>(int)$slot->fiscal_year,
            'reportingMonth'=>(int)($slot->fiscal_month ?: now()->month),
            'reportingQuarter'=>$slot->fiscal_quarter ?: 'Q1',
            'reportingHalf'=>$slot->fiscal_half,
            'actualExpenditure'=>$actualExpenditure,
        ]);
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman',403);

        $validated=$request->validate([
            'slot_id'=>['required','integer'],
            'actual_expenditure'=>['nullable','numeric','min:0.01'],
            'monitoring_officer'=>['nullable','string','max:255'],
            'sheet_no'=>['nullable','string','max:255'],
            'city'=>['nullable','string','max:255'],
            'province'=>['nullable','string','max:255'],
            'program_project_activity'=>['nullable','string','max:255'],
            'object_headers'=>['nullable','array'],
            'object_headers.*'=>['nullable','string','max:255'],
            'rows'=>['nullable','array'],
            'rows.*.particulars'=>['nullable','string','max:255'],
            'rows.*.date'=>['nullable','date'],
            'rows.*.reference'=>['nullable','string','max:255'],
            'rows.*.total_amount'=>['nullable','numeric'],
            'rows.*.object_1'=>['nullable','numeric'],
            'rows.*.object_2'=>['nullable','numeric'],
            'rows.*.object_3'=>['nullable','numeric'],
            'rows.*.object_4'=>['nullable','numeric'],
            'spf_carried_forward'=>['nullable','numeric'],
            'commitments_carried_forward'=>['nullable','numeric'],
            'payments_carried_forward'=>['nullable','numeric'],
            'available_balance'=>['nullable','numeric'],
            'unpaid_commitments'=>['nullable','numeric'],
            'certified_date'=>['nullable','date'],
            'bank_name'=>['nullable','string','max:255'],
            'branch'=>['nullable','string','max:255'],
            'current_account_no'=>['nullable','string','max:255'],
            'prepared_by'=>['nullable','string','max:255'],
            'approved_by'=>['nullable','string','max:255'],
            'bank_rows'=>['nullable','array'],
            'bank_rows.*.particulars'=>['nullable','string','max:255'],
            'bank_rows.*.rcb'=>['nullable','string','max:255'],
            'bank_rows.*.bank'=>['nullable','string','max:255'],
            'bank_rows.*.comment'=>['nullable','string','max:500'],
            'accountable_officer'=>['nullable','string','max:255'],
            'official_designation'=>['nullable','string','max:255'],
            'assumption_date'=>['nullable','date'],
            'inventory_rows'=>['nullable','array'],
            'inventory_rows.*.article'=>['nullable','string','max:255'],
            'inventory_rows.*.description'=>['nullable','string','max:255'],
            'inventory_rows.*.property_no'=>['nullable','string','max:255'],
            'inventory_rows.*.unit'=>['nullable','string','max:255'],
            'inventory_rows.*.unit_cost'=>['nullable','numeric'],
            'inventory_rows.*.balance'=>['nullable','numeric'],
            'inventory_rows.*.on_hand'=>['nullable','numeric'],
            'inventory_rows.*.shortage_quantity'=>['nullable','numeric'],
            'inventory_rows.*.shortage_value'=>['nullable','numeric'],
            'inventory_rows.*.remarks'=>['nullable','string','max:255'],
            'committee_members'=>['nullable','array'],
            'committee_members.*'=>['nullable','string','max:255'],
            'chairperson_name'=>['nullable','string','max:255'],
        ]);

        $slot=app(SubmissionSlotService::class)->resolveOpenSlot(
            (int)$validated['slot_id'],
            'budget_report',
            ['SK Chairman','Both']
        );

        if(!$slot){
            return redirect()
                ->route('sk_chairman.budget')
                ->with(
                    'report_error',
                    'That budget submission slot is no longer available.'
                );
        }

        $termId=(int)($slot->term_id ?? 0);

        if($termId<=0){
            return redirect()
                ->route('sk_chairman.budget')
                ->with(
                    'report_error',
                    'The budget submission slot is not connected to an active administration term.'
                );
        }

        $existing=$this->existingBudgetSubmission(
            (int)$slot->slot_id,
            $termId
        );

        if($existing && $this->isQualityApproved((int)$existing->budget_report_id)){
            return redirect()
                ->route('sk_chairman.budget')
                ->with(
                    'report_error',
                    'This budget document has already been approved for Quality Documentation and can no longer be replaced.'
                );
        }

        $isResubmission=(bool)$existing;

        if(!$this->templateAvailableForSlot($slot)){
            return redirect()
                ->route('sk_chairman.budget')
                ->with(
                    'report_error',
                    'The SK360 system template is not available for this submission type.'
                );
        }

        $isAnnualCoa=($slot->budget_category ?? null)==='coa_report'
            && ($slot->budget_period_type ?? null)==='annual';

        if($isAnnualCoa && empty($validated['actual_expenditure'])){
            return redirect()
                ->route('sk_chairman.budget')
                ->with(
                    'report_error',
                    'Actual Expenditure is required for Annual COA.'
                );
        }

        $templateData=array_merge($validated,[
            'report_type'=>$slot->budget_period_type,
            'reporting_year'=>$slot->fiscal_year,
            'reporting_month'=>$slot->fiscal_month,
            'reporting_quarter'=>$slot->fiscal_quarter,
            'reporting_half'=>$slot->fiscal_half,
            'budget_category'=>$slot->budget_category,
        ]);

        $data=[
            'term_id'=>$termId,
            'user_id'=>auth()->user()->user_id,
            'barangay_id'=>auth()->user()->barangay_id,
            'slot_id'=>$slot->slot_id,
            'submission_method'=>'direct_input',
            'document_type'=>'financial_record',
            'fiscal_year'=>$slot->fiscal_year ?? now()->year,
            'title'=>$slot->title,
            'generated_pdf_path'=>'TEMPLATE_GEN',
            'template_data'=>json_encode(
                $templateData,
                JSON_UNESCAPED_UNICODE
            ),
            'uploaded_file_name'=>null,
            'uploaded_file_path'=>null,
            'total_amount'=>
                collect($validated['rows'] ?? [])->sum(
                    fn($row)=>(float)($row['total_amount'] ?? 0)
                )
                +
                collect($validated['inventory_rows'] ?? [])->sum(
                    fn($row)=>(float)($row['shortage_value'] ?? 0)
                ),
            'actual_expenditure'=>$isAnnualCoa
                ? (float)$validated['actual_expenditure']
                : null,
            'status'=>'recorded',
            'submitted_at'=>now(),
            'created_at'=>now(),
        ];

        $data=$this->applySlotMetadata($data,$slot);

        try{
            $budgetReportId=DB::transaction(function() use($data,$slot,$termId,$existing){
                $budgetReportId=$this->saveBudgetSubmission(
                    $data,
                    (int)$slot->slot_id,
                    $termId
                );

                if($existing){
                    $this->resetQualityReviewForResubmission($budgetReportId);
                }

                return $budgetReportId;
            });
        }catch(\Throwable $e){
            return redirect()
                ->route('sk_chairman.budget')
                ->with(
                    'report_error',
                    'The budget template could not be saved. Please try again.'
                );
        }

        if($existing && !empty($existing->uploaded_file_path)){
            $this->deleteBudgetFile($existing->uploaded_file_path);
        }

        $this->notifyPresidentOfBudgetSubmission(
            $budgetReportId,
            $termId,
            $isResubmission
        );

        if($isResubmission){
            return redirect()
                ->route('sk_chairman.budget')
                ->with(
                    'report_success',
                    'Your corrected budget document has been resubmitted successfully and returned to Pending Quality Review.'
                );
        }

        return redirect()
            ->route('sk_chairman.budget')
            ->with(
                'report_success',
                $isAnnualCoa
                    ? 'Annual COA template submitted successfully.'
                    : 'Budget template submitted successfully.'
            );
    }

    public function downloadTemplate(int $budgetReportId): Response|RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman',403);

        $currentTermId=$this->currentTermId();

        if(!$currentTermId){
            return redirect()
                ->route('sk_chairman.budget')
                ->with(
                    'report_error',
                    'There is no active administration term.'
                );
        }

        $submission=DB::table('budget_reports')
            ->where('budget_report_id',$budgetReportId)
            ->where('term_id',$currentTermId)
            ->where('barangay_id',auth()->user()->barangay_id)
            ->first();

        if(!$submission || empty($submission->template_data)){
            return redirect()
                ->route('sk_chairman.budget')
                ->with(
                    'report_error',
                    'Template submission not found.'
                );
        }

        $data=json_decode($submission->template_data,true) ?: [];
        $paper=($data['report_type'] ?? 'quarterly')==='monthly'
            ? 'portrait'
            : 'landscape';

        $pdf=Pdf::loadView('shared.budget-template-download',[
            'data'=>$data,
            'barangayName'=>auth()->user()->barangay->barangay_name ?? 'Barangay',
        ])->setPaper('a4',$paper);

        return $pdf->download('budget-template-'.$budgetReportId.'.pdf');
    }

    public function viewTemplate(int $budgetReportId): Response|RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman',403);

        $currentTermId=$this->currentTermId();

        if(!$currentTermId){
            return redirect()
                ->route('sk_chairman.budget')
                ->with(
                    'report_error',
                    'There is no active administration term.'
                );
        }

        $submission=DB::table('budget_reports')
            ->where('budget_report_id',$budgetReportId)
            ->where('term_id',$currentTermId)
            ->where('barangay_id',auth()->user()->barangay_id)
            ->first();

        if(!$submission || empty($submission->template_data)){
            return redirect()
                ->route('sk_chairman.budget')
                ->with(
                    'report_error',
                    'Template submission not found.'
                );
        }

        $data=json_decode($submission->template_data,true) ?: [];
        $paper=($data['report_type'] ?? 'quarterly')==='monthly'
            ? 'portrait'
            : 'landscape';

        $pdf=Pdf::loadView('shared.budget-template-download',[
            'data'=>$data,
            'barangayName'=>auth()->user()->barangay->barangay_name ?? 'Barangay',
        ])->setPaper('a4',$paper);

        return $pdf->stream('budget-template-'.$budgetReportId.'.pdf');
    }

    protected function templateAvailableForSlot(object $slot): bool
    {
        return ($slot->budget_category ?? null)==='coa_report'
            && in_array(
                $slot->budget_period_type ?? null,
                ['monthly','quarterly','annual'],
                true
            );
    }

    protected function applySlotMetadata(array $data,object $slot): array
    {
        if(Schema::hasColumn('budget_reports','budget_category')){
            $data['budget_category']=$slot->budget_category;
        }

        if(Schema::hasColumn('budget_reports','budget_period_type')){
            $data['budget_period_type']=($slot->budget_category ?? null)==='coa_report'
                ? $slot->budget_period_type
                : null;
        }

        if(Schema::hasColumn('budget_reports','fiscal_month')){
            $data['fiscal_month']=($slot->budget_period_type ?? null)==='monthly'
                ? $slot->fiscal_month
                : null;
        }

        if(Schema::hasColumn('budget_reports','fiscal_quarter')){
            $data['fiscal_quarter']=($slot->budget_period_type ?? null)==='quarterly'
                ? $slot->fiscal_quarter
                : null;
        }

        if(Schema::hasColumn('budget_reports','fiscal_half')){
            $data['fiscal_half']=($slot->budget_period_type ?? null)==='semi_annual'
                ? $slot->fiscal_half
                : null;
        }

        return $data;
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

    protected function saveBudgetSubmission(
        array $data,
        int $slotId,
        int $termId
    ): int {
        $existing=DB::table('budget_reports')
            ->where('term_id',$termId)
            ->where('barangay_id',auth()->user()->barangay_id)
            ->where('slot_id',$slotId)
            ->first();

        if($existing){
            unset($data['created_at']);

            DB::table('budget_reports')
                ->where('budget_report_id',$existing->budget_report_id)
                ->where('term_id',$termId)
                ->update($data);

            return (int)$existing->budget_report_id;
        }

        return (int)DB::table('budget_reports')
            ->insertGetId($data,'budget_report_id');
    }

    protected function existingBudgetSubmission(
        int $slotId,
        int $termId
    ): ?object {
        return DB::table('budget_reports')
            ->where('term_id',$termId)
            ->where('barangay_id',auth()->user()->barangay_id)
            ->where('slot_id',$slotId)
            ->first();
    }

    protected function isQualityApproved(int $budgetReportId): bool
    {
        if(!Schema::hasTable('submission_quality_reviews')){
            return false;
        }

        return DB::table('submission_quality_reviews')
            ->where('source_type','budget_report')
            ->where('source_id',$budgetReportId)
            ->where('status','approved')
            ->exists();
    }

    protected function resetQualityReviewForResubmission(int $budgetReportId): void
    {
        if(!Schema::hasTable('submission_quality_reviews')){
            return;
        }

        $review=DB::table('submission_quality_reviews')
            ->where('source_type','budget_report')
            ->where('source_id',$budgetReportId)
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

    protected function notifyPresidentOfBudgetSubmission(
        int $budgetReportId,
        int $termId,
        bool $isResubmission
    ): void {
        $submission=DB::table('budget_reports')
            ->where('budget_report_id',$budgetReportId)
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
                    'budget_report',
                    $user
                );
            }else{
                $notifications->notifySubmissionReceived(
                    $submission,
                    'budget_report',
                    $user
                );
            }
        }catch(\Throwable $e){
            report($e);
        }
    }

    protected function deleteBudgetFile(?string $path): void
    {
        if(!$path){
            return;
        }

        $path=str_replace('\\','/',ltrim($path,'/'));

        if(!str_starts_with($path,'uploads/budget_reports/')){
            return;
        }

        $fullPath=public_path($path);

        if(File::exists($fullPath)){
            File::delete($fullPath);
        }
    }

    protected function periodLabel(object $submission): string
    {
        $category=$submission->slot_budget_category ?? null;
        $year=$submission->slot_fiscal_year ?? $submission->fiscal_year ?? '';

        if($category==='annual_budget'){
            return 'Annual Budget FY '.$year;
        }

        if($category==='supplemental_budget'){
            return 'Supplemental Budget FY '.$year;
        }

        $periodType=$submission->slot_budget_period_type
            ?? $submission->budget_period_type
            ?? 'annual';

        return match($periodType){
            'monthly'=>Carbon::create(
                (int)$year,
                (int)(
                    $submission->slot_fiscal_month
                    ?? $submission->fiscal_month
                    ?? 1
                ),
                1
            )->format('F Y'),

            'quarterly'=>(
                $submission->slot_fiscal_quarter
                ?? $submission->fiscal_quarter
                ?? 'Quarterly'
            ).' '.$year,

            'semi_annual'=>(
                ($submission->slot_fiscal_half ?? null)==='H1'
                    ? 'First Half '
                    : 'Second Half '
            ).$year,

            default=>'Annual '.$year,
        };
    }

    protected function currentTermId(): ?int
    {
        $termId=DB::table('administration_terms')
            ->where('status','current')
            ->orderByDesc('term_id')
            ->value('term_id');

        return $termId ? (int)$termId : null;
    }
}