<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_pres/ConsolidationController.php.

namespace App\Http\Controllers\sk_pres;



use App\Http\Controllers\Controller;

use App\Services\NotificationService;

use App\Services\RankingPointsService;

use Barryvdh\DomPDF\Facade\Pdf;

use Carbon\Carbon;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Collection;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use setasign\Fpdi\Fpdi;

use Illuminate\Http\Response;

use Illuminate\View\View;



class ConsolidationController extends Controller

{

    public function index(Request $request): View

    {

        abort_unless(auth()->check() && auth()->user()->role==='sk_president',403);



        $user=auth()->user();

        $fullName=trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';

        $filters=$this->filters($request);

        $submissions=$this->submissions($filters);

        $qualitySubmissions=$this->qualitySubmissions($filters);

        $stats=$this->stats($submissions);
        $documentFilters=$this->documentFilters($request);
        $documentSubmissions=$this->consolidationDocuments($documentFilters);
        $term=DB::table('administration_terms')->where('term_id',$this->currentTermId())->first();



        $menuItems=[

            ['link'=>route('sk_pres.home'),'icon'=>'🏠','label'=>'Home'],

            ['link'=>route('sk_pres.dashboard'),'icon'=>'📊','label'=>'Dashboard'],

            ['link'=>route('sk_pres.consolidation'),'icon'=>'📁','label'=>'Consolidation'],

            ['link'=>route('sk_pres.module'),'icon'=>'⚙️','label'=>'Module Management'],

            ['link'=>route('sk_pres.announcements'),'icon'=>'📢','label'=>'Announcements'],

            ['link'=>route('sk_pres.calendar'),'icon'=>'📅','label'=>'Calendar'],

            ['link'=>route('sk_pres.chat'),'icon'=>'💬','label'=>'Chat'],

            ['link'=>route('sk_pres.meetings'),'icon'=>'📞','label'=>'Meetings'],

            ['link'=>route('sk_pres.rankings'),'icon'=>'🏆','label'=>'Rankings'],

            ['link'=>route('sk_pres.leadership'),'icon'=>'👥','label'=>'Leadership'],

            ['link'=>route('sk_pres.archive'),'icon'=>'🗂️','label'=>'Archive'],

            ['link'=>route('sk_pres.user-management'),'icon'=>'👤','label'=>'User Management'],

        ];



        return view('sk_pres.consolidation',[

            'fullName'=>$fullName,

            'menuItems'=>$menuItems,

            'stats'=>$stats,

            'submissions'=>$submissions,

            'qualitySubmissions'=>$qualitySubmissions,

            'qualityGroups'=>$this->qualityGroups($qualitySubmissions),

            'filters'=>$filters,

            'years'=>$this->availableYears(),

            'months'=>$this->months(),

            'quarters'=>['Q1','Q2','Q3','Q4'],

            'downloadRoute'=>route('sk_pres.consolidation.download',$filters),

            'currentUrl'=>url()->current(),
            'documentFilters'=>$documentFilters,
            'documentSubmissions'=>$documentSubmissions,
            'documentYears'=>$this->availableYears(),
            'documentTerm'=>$term,

        ]);

    }



    public function download(Request $request): Response|RedirectResponse

    {

        abort_unless(auth()->check() && auth()->user()->role==='sk_president',403);



        $mode=(string)$request->query('mode','legacy');
        if($mode==='annual_budget'){
            return $this->downloadAnnualBudget((int)$request->query('year',0));
        }
        if($mode==='annual_coa'){
            return $this->downloadAnnualFinancial((int)$request->query('year',0));
        }
        if($mode==='pdf'){
            return $this->downloadSelectedPdfs($request);
        }

        $filters=$this->filters($request);

        $submissions=$this->submissions($filters);



        $pdf=Pdf::loadView('sk_pres.consolidation-download',[

            'filters'=>$filters,

            'submissions'=>$submissions,

            'stats'=>$this->stats($submissions),

            'generatedAt'=>now(),

        ])->setPaper('a4','landscape');



        return $pdf->download('consolidated-reports-'.$filters['year'].'-'.$filters['period'].'.pdf');

    }

    protected function documentFilters(Request $request): array
    {
        $year=(int)$request->query('year',$this->defaultFilterYear());
        $tab=(string)$request->query('tab','accomplishment');
        $category=(string)$request->query('category','all');
        $budgetCategory=(string)$request->query('budget_category','all');
        $period=(string)$request->query('report_period','all');
        return [
            'tab'=>in_array($tab,['accomplishment','budget'],true)?$tab:'accomplishment',
            'year'=>$year>=2000&&$year<=2100?$year:$this->defaultFilterYear(),
            'category'=>in_array($category,['all','general','youth_development_program','kk_assembly'],true)?$category:'all',
            'budget_category'=>in_array($budgetCategory,['all','annual_budget','supplemental_budget','coa_report'],true)?$budgetCategory:'all',
            'period'=>in_array($period,['all','monthly','quarterly','semi_annual','annual'],true)?$period:'all',
            'month'=>max(1,min(12,(int)$request->query('month',now()->month))),
            'quarter'=>in_array($request->query('quarter'),['Q1','Q2','Q3','Q4'],true)?$request->query('quarter'):'Q'.ceil(now()->month/3),
            'half'=>in_array($request->query('half'),['H1','H2'],true)?$request->query('half'):'H1',
            'barangay'=>trim((string)$request->query('document_barangay','')),
        ];
    }

    protected function consolidationDocuments(array $filters): Collection
    {
        $termId=$this->currentTermId();
        if(!$termId){
            return collect();
        }

        $items=collect();
        if($filters['tab']==='accomplishment'){
            $items=DB::table('accomplishment_reports as ar')
                ->leftJoin('barangays as b','b.barangay_id','=','ar.barangay_id')
                ->leftJoin('submission_slots as ss',function($join){
                    $join->on('ss.slot_id','=','ar.slot_id')->on('ss.term_id','=','ar.term_id');
                })
                ->leftJoin('submission_quality_reviews as qr',function($join){
                    $join->on('qr.source_id','=','ar.report_id')->where('qr.source_type','=','accomplishment_report');
                })
                ->where('ar.term_id',$termId)->where('ar.reporting_year',$filters['year'])->where('ar.status','!=','draft')
                ->when($filters['category']!=='all',fn($q)=>$q->whereRaw("COALESCE(ss.accomplishment_category,'general') = ?",[$filters['category']]))
                ->when($filters['period']==='monthly',fn($q)=>$q->where('ar.report_type','monthly')->where('ar.reporting_month',$filters['month']))
                ->when($filters['period']==='quarterly',fn($q)=>$q->where('ar.report_type','quarterly')->where('ar.reporting_quarter',$filters['quarter']))
                ->when($filters['period']==='annual',fn($q)=>$q->where('ar.report_type','annual'))
                ->when($filters['period']==='semi_annual',fn($q)=>$q->whereRaw('1 = 0'))
                ->when($filters['barangay']!=='',fn($q)=>$q->where('b.barangay_name','like','%'.$filters['barangay'].'%'))
                ->select(DB::raw("'accomplishment_report' as source_type"),'ar.report_id as source_id','ar.barangay_id','b.barangay_name','ar.title',DB::raw("COALESCE(ss.accomplishment_category,'general') as category"),'ar.report_type as period_type','ar.reporting_year as year','ar.reporting_month as month','ar.reporting_quarter as quarter',DB::raw('NULL as half'),'ar.uploaded_file_path','ar.generated_pdf_path','ar.submitted_at','ar.created_at','qr.status as quality_status')
                ->get();
        }else{
            $items=DB::table('budget_reports as br')
                ->leftJoin('barangays as b','b.barangay_id','=','br.barangay_id')
                ->leftJoin('submission_slots as ss',function($join){
                    $join->on('ss.slot_id','=','br.slot_id')->on('ss.term_id','=','br.term_id');
                })
                ->leftJoin('submission_quality_reviews as qr',function($join){
                    $join->on('qr.source_id','=','br.budget_report_id')->where('qr.source_type','=','budget_report');
                })
                ->where('br.term_id',$termId)->where('br.fiscal_year',$filters['year'])->where('br.status','!=','draft')
                ->when($filters['budget_category']!=='all',fn($q)=>$q->whereRaw('COALESCE(br.budget_category,ss.budget_category) = ?',[$filters['budget_category']]))
                ->when($filters['budget_category']==='all'||($filters['budget_category']==='coa_report'&&$filters['period']==='all'),fn($q)=>$q->whereRaw("NOT (COALESCE(br.budget_category,ss.budget_category,'') = 'annual_budget' OR (COALESCE(br.budget_category,ss.budget_category,'') = 'coa_report' AND COALESCE(br.budget_period_type,ss.budget_period_type,'') = 'annual'))"))
                ->when($filters['budget_category']!=='annual_budget'&&$filters['period']!=='all',fn($q)=>$q->whereRaw('COALESCE(br.budget_period_type,ss.budget_period_type) = ?',[$filters['period']]))
                ->when($filters['budget_category']!=='annual_budget'&&$filters['period']==='monthly',fn($q)=>$q->whereRaw('COALESCE(br.fiscal_month,ss.fiscal_month) = ?',[$filters['month']]))
                ->when($filters['budget_category']!=='annual_budget'&&$filters['period']==='quarterly',fn($q)=>$q->whereRaw('COALESCE(br.fiscal_quarter,ss.fiscal_quarter) = ?',[$filters['quarter']]))
                ->when($filters['budget_category']!=='annual_budget'&&$filters['period']==='semi_annual',fn($q)=>$q->whereRaw('COALESCE(br.fiscal_half,ss.fiscal_half) = ?',[$filters['half']]))
                ->when($filters['barangay']!=='',fn($q)=>$q->where('b.barangay_name','like','%'.$filters['barangay'].'%'))
                ->select(DB::raw("'budget_report' as source_type"),'br.budget_report_id as source_id','br.barangay_id','b.barangay_name','br.title',DB::raw('COALESCE(br.budget_category,ss.budget_category) as category'),DB::raw('COALESCE(br.budget_period_type,ss.budget_period_type) as period_type'),'br.fiscal_year as year',DB::raw('COALESCE(br.fiscal_month,ss.fiscal_month) as month'),DB::raw('COALESCE(br.fiscal_quarter,ss.fiscal_quarter) as quarter'),DB::raw('COALESCE(br.fiscal_half,ss.fiscal_half) as half'),'br.uploaded_file_path','br.generated_pdf_path','br.submitted_at','br.created_at','qr.status as quality_status')
                ->get();
        }

        return $items->sortByDesc(fn($item)=>$item->submitted_at??$item->created_at)->values()->map(function($item){
            $item->quality_status=$item->quality_status?:'pending';
            $item->period_label=$item->source_type==='budget_report'&&$item->category!=='coa_report'
                ? 'FY '.$item->year
                : match($item->period_type){
                    'monthly'=>isset($item->month)?Carbon::create((int)$item->year,(int)$item->month,1)->format('F Y'):'Monthly '.$item->year,
                    'quarterly'=>($item->quarter?:'Quarterly').' '.$item->year,
                    'semi_annual'=>(($item->half==='H2')?'Second Half':'First Half').' '.$item->year,
                    'annual'=>'Annual '.$item->year,
                    default=>'FY '.$item->year,
                };
            $item->category_label=$item->source_type==='accomplishment_report'
                ? ['general'=>'General Accomplishment','youth_development_program'=>'Youth Development Program','kk_assembly'=>'KK Assembly'][$item->category]??'Accomplishment Report'
                : ['annual_budget'=>'Annual Budget','supplemental_budget'=>'Supplemental Budget','coa_report'=>'COA Report'][$item->category]??'Budget Report';
            $item->submitted_label=($item->submitted_at??$item->created_at)?Carbon::parse($item->submitted_at??$item->created_at)->format('M d, Y h:i A'):'Unknown';
            $item->view_url=route('sk_pres.module.submission.view',[$item->source_type,$item->source_id]);
            $item->is_data_consolidation=$item->source_type==='budget_report'&&($item->category==='annual_budget'||($item->category==='coa_report'&&$item->period_type==='annual'));
            $item->has_pdf=!empty($item->uploaded_file_path)||($item->source_type==='budget_report'&&$item->generated_pdf_path==='TEMPLATE_GEN');
            return $item;
        });
    }

    protected function downloadAnnualBudget(int $year): Response
    {
        abort_unless($year>=2000&&$year<=2100,404);
        $termId=$this->currentTermId();
        abort_unless($termId,404);
        $rows=$this->annualBudgetRows($year,$termId);
        $total=(float)$rows->sum(fn($row)=>(float)$row->total_amount);
        $expected=(int)DB::table('barangays')->count();
        $term=DB::table('administration_terms')->where('term_id',$termId)->first();
        return Pdf::loadView('sk_pres.consolidation-output',[
            'mode'=>'annual_budget','year'=>$year,'rows'=>$rows,'term'=>$term,'generatedAt'=>now(),
            'summary'=>['included'=>$rows->whereNotNull('total_amount')->count(),'expected'=>$expected,'total_budget'=>$total],
        ])->setPaper('a4','portrait')->download('consolidated-annual-budget-'.$year.'.pdf');
    }

    protected function downloadAnnualFinancial(int $year): Response
    {
        abort_unless($year>=2000&&$year<=2100,404);
        $termId=$this->currentTermId();
        abort_unless($termId,404);
        $budgets=$this->annualBudgetRows($year,$termId)->keyBy('barangay_id');
        $coa=$this->annualCoaRows($year,$termId)->keyBy('barangay_id');
        $ids=$budgets->keys()->merge($coa->keys())->unique();
        $rows=$ids->map(function($id)use($budgets,$coa){
            $budget=$budgets->get($id);$expenditure=$coa->get($id);
            $hasBudget=$budget&&$budget->total_amount!==null;
            $hasExpenditure=$expenditure&&$expenditure->actual_expenditure!==null;
            $budgetValue=$hasBudget?(float)$budget->total_amount:null;
            $expenditureValue=$hasExpenditure?(float)$expenditure->actual_expenditure:null;
            $complete=$hasBudget&&$hasExpenditure;
            return (object)[
                'barangay_id'=>(int)$id,'barangay_name'=>$budget->barangay_name??$expenditure->barangay_name??'Unknown Barangay',
                'budget'=>$budgetValue,'expenditure'=>$expenditureValue,'complete'=>$complete,
                'balance'=>$complete?$budgetValue-$expenditureValue:null,
                'utilization'=>$complete&&$budgetValue>0?($expenditureValue/$budgetValue)*100:($complete?0:null),
                'missing'=>$complete?null:(!$hasBudget?'Annual Budget missing':'Annual COA missing'),
            ];
        })->sortBy('barangay_name',SORT_NATURAL|SORT_FLAG_CASE)->values();
        $completeRows=$rows->where('complete',true);
        $pairedBudget=(float)$completeRows->sum('budget');
        $pairedExpenditure=(float)$completeRows->sum('expenditure');
        $term=DB::table('administration_terms')->where('term_id',$termId)->first();
        return Pdf::loadView('sk_pres.consolidation-output',[
            'mode'=>'annual_coa','year'=>$year,'rows'=>$rows,'term'=>$term,'generatedAt'=>now(),
            'summary'=>[
                'included'=>$rows->count(),'budget_total'=>(float)$rows->whereNotNull('budget')->sum('budget'),
                'expenditure_total'=>(float)$rows->whereNotNull('expenditure')->sum('expenditure'),
                'paired_budget'=>$pairedBudget,'paired_expenditure'=>$pairedExpenditure,
                'balance'=>$pairedBudget-$pairedExpenditure,'utilization'=>$pairedBudget>0?($pairedExpenditure/$pairedBudget)*100:0,
                'complete_pairs'=>$completeRows->count(),
            ],
        ])->setPaper('a4','landscape')->download('annual-financial-consolidation-'.$year.'.pdf');
    }

    protected function annualBudgetRows(int $year,int $termId): Collection
    {
        return DB::table('budget_reports as br')
            ->join('barangays as b','b.barangay_id','=','br.barangay_id')
            ->leftJoin('submission_slots as ss',function($join){$join->on('ss.slot_id','=','br.slot_id')->on('ss.term_id','=','br.term_id');})
            ->where('br.term_id',$termId)->where('br.fiscal_year',$year)->where('br.status','!=','draft')
            ->whereRaw("COALESCE(br.budget_category,ss.budget_category) = 'annual_budget'")
            ->select('br.budget_report_id','br.barangay_id','b.barangay_name','br.total_amount','br.submitted_at','br.created_at')
            ->orderBy('b.barangay_name')->orderByDesc('br.submitted_at')->orderByDesc('br.budget_report_id')
            ->get()->groupBy('barangay_id')->map(fn($records)=>$records->first())->values();
    }

    protected function annualCoaRows(int $year,int $termId): Collection
    {
        return DB::table('budget_reports as br')
            ->join('barangays as b','b.barangay_id','=','br.barangay_id')
            ->leftJoin('submission_slots as ss',function($join){$join->on('ss.slot_id','=','br.slot_id')->on('ss.term_id','=','br.term_id');})
            ->where('br.term_id',$termId)->where('br.fiscal_year',$year)->where('br.status','!=','draft')
            ->whereRaw("COALESCE(br.budget_category,ss.budget_category) = 'coa_report'")
            ->whereRaw("COALESCE(br.budget_period_type,ss.budget_period_type) = 'annual'")
            ->select('br.budget_report_id','br.barangay_id','b.barangay_name','br.actual_expenditure','br.submitted_at','br.created_at')
            ->orderBy('b.barangay_name')->orderByDesc('br.submitted_at')->orderByDesc('br.budget_report_id')
            ->get()->groupBy('barangay_id')->map(fn($records)=>$records->first())->values();
    }

    protected function downloadSelectedPdfs(Request $request): Response|RedirectResponse
    {
        $validated=$request->validate([
            'tab'=>['required','in:accomplishment,budget'],'year'=>['required','integer','min:2000','max:2100'],
            'sources'=>['required','array','min:1','max:100'],
            'sources.*'=>['required','regex:/^(accomplishment_report|budget_report):[1-9][0-9]*$/'],
        ]);
        $filters=$this->documentFilters($request);
        abort_if($filters['tab']==='budget'&&($filters['budget_category']==='annual_budget'||($filters['budget_category']==='coa_report'&&$filters['period']==='annual')),422,'Annual Budget and Annual COA use data consolidation.');
        $visible=$this->consolidationDocuments($filters)->keyBy(fn($item)=>$item->source_type.':'.$item->source_id);
        $selected=collect($validated['sources'])->unique()->map(function($key)use($visible){
            abort_unless($visible->has($key),404);
            return $visible->get($key);
        })->values();
        $termId=$this->currentTermId();
        abort_unless($termId,404);
        $temporary=[];
        try{
            $sourcePaths=[];
            foreach($selected as $item){
                $record=$this->resolveConsolidationSource($item,$termId);
                if(isset($record['temporary'])){$temporary[]=$record['path'];}
                $sourcePaths[]=$record['path'];
            }
            $term=DB::table('administration_terms')->where('term_id',$termId)->first();
            $cover=Pdf::loadView('sk_pres.consolidation-output',[
                'mode'=>'pdf','year'=>(int)$validated['year'],'rows'=>$selected,'term'=>$term,'generatedAt'=>now(),'summary'=>[],
            ])->setPaper('a4','portrait')->output();
            $coverPath=tempnam(sys_get_temp_dir(),'sk360-cover-');
            File::put($coverPath,$cover);
            $temporary[]=$coverPath;
            $pdf=new Fpdi();
            foreach(array_merge([$coverPath],$sourcePaths) as $path){
                $pageCount=$pdf->setSourceFile($path);
                for($page=1;$page<=$pageCount;$page++){
                    $template=$pdf->importPage($page);
                    $size=$pdf->getTemplateSize($template);
                    $pdf->AddPage($size['orientation'],[$size['width'],$size['height']]);
                    $pdf->useTemplate($template);
                }
            }
            $filename='consolidated-'.($filters['tab']==='budget'?'budget':'accomplishment').'-'.$validated['year'].'.pdf';
            return response($pdf->Output('S'),200,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="'.$filename.'"','X-Content-Type-Options'=>'nosniff']);
        }catch(\Throwable $exception){
            report($exception);
            return back()->with('consolidation_error','One or more selected PDFs could not be merged. Check that each source is a readable PDF.');
        }finally{
            foreach(array_unique($temporary) as $path){if(is_file($path))@unlink($path);}
        }
    }

    protected function resolveConsolidationSource(object $item,int $termId): array
    {
        if($item->source_type==='accomplishment_report'){
            $record=DB::table('accomplishment_reports')->where('report_id',$item->source_id)->where('term_id',$termId)->where('status','!=','draft')->first();
            abort_unless($record,404);
            $relative=$record->uploaded_file_path?:$record->generated_pdf_path;
            return ['path'=>$this->safeConsolidationPdfPath($relative,'uploads/reports')];
        }
        $record=DB::table('budget_reports')->where('budget_report_id',$item->source_id)->where('term_id',$termId)->where('status','!=','draft')->first();
        abort_unless($record,404);
        if(!empty($record->uploaded_file_path)){
            return ['path'=>$this->safeConsolidationPdfPath($record->uploaded_file_path,'uploads/budget_reports')];
        }
        abort_unless(($record->generated_pdf_path??null)==='TEMPLATE_GEN'&&!empty($record->template_data),404);
        $data=json_decode($record->template_data,true);
        abort_unless(is_array($data)&&$data,404);
        $barangayName=DB::table('barangays')->where('barangay_id',$record->barangay_id)->value('barangay_name')?:'Barangay';
        $paper=($data['report_type']??'quarterly')==='monthly'?'portrait':'landscape';
        $bytes=Pdf::loadView('shared.budget-template-download',['data'=>$data,'barangayName'=>$barangayName])->setPaper('a4',$paper)->output();
        $path=tempnam(sys_get_temp_dir(),'sk360-budget-');File::put($path,$bytes);
        return ['path'=>$path,'temporary'=>true];
    }

    protected function safeConsolidationPdfPath(?string $relative,string $directory): string
    {
        abort_unless($relative,404,'The submitted PDF is unavailable.');
        $relative=str_replace('\\','/',ltrim($relative,'/\\'));
        abort_unless(str_starts_with($relative,$directory.'/')&&strtolower(pathinfo($relative,PATHINFO_EXTENSION))==='pdf',404,'The submitted PDF is unavailable.');
        $public=realpath(public_path());$base=realpath(public_path($directory));$path=realpath(public_path($relative));
        abort_unless($public&&$base&&$path&&str_starts_with(strtolower($base),strtolower(rtrim($public,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR))&&str_starts_with(strtolower($path),strtolower(rtrim($base,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR))&&File::isFile($path),404,'The submitted PDF is unavailable.');
        return $path;
    }



    public function reviewQuality(

        Request $request,

        RankingPointsService $points,

        NotificationService $notifications

    ): RedirectResponse {

        abort_unless(auth()->check() && auth()->user()->role==='sk_president',403);



        $currentTermId=$this->currentTermId();

        abort_unless($currentTermId,404);



        $validated=$request->validate([

            'source_type'=>['required','in:accomplishment_report,budget_report'],

            'source_id'=>['required','integer','min:1'],

            'status'=>['required','in:approved,needs_revision'],

            'complete_contents'=>['nullable','boolean'],

            'correct_document'=>['nullable','boolean'],

            'correct_period'=>['nullable','boolean'],

            'remarks'=>['nullable','required_if:status,needs_revision','string','max:2000'],

        ]);



        $sourceType=$validated['source_type'];

        $sourceId=(int)$validated['source_id'];



        $table=$sourceType==='accomplishment_report'

            ? 'accomplishment_reports'

            : 'budget_reports';



        $primaryKey=$sourceType==='accomplishment_report'

            ? 'report_id'

            : 'budget_report_id';



        $submission=DB::table($table)

            ->where($primaryKey,$sourceId)

            ->where('term_id',$currentTermId)

            ->first();



        abort_unless($submission,404);



        $completeContents=$request->boolean('complete_contents');

        $correctDocument=$request->boolean('correct_document');

        $correctPeriod=$request->boolean('correct_period');



        if(

            $validated['status']==='approved' &&

            (!$completeContents || !$correctDocument || !$correctPeriod)

        ){

            return back()

                ->withInput()

                ->withErrors([

                    'quality_review'=>'Complete Contents, Correct Document, and Correct Period must be checked before approving this document.',

                ]);

        }



        $existingReview=DB::table('submission_quality_reviews')

            ->where('source_type',$sourceType)

            ->where('source_id',$sourceId)

            ->first();



        if(

            $existingReview &&

            $existingReview->status==='approved' &&

            $validated['status']==='needs_revision'

        ){

            return back()->withErrors([

                'quality_review'=>'This document has already been approved and cannot be changed to Needs Revision.',

            ]);

        }



        $submissionDate=$submission->submitted_at

            ?? $submission->created_at

            ?? null;



        $resubmittedAfterReview=false;



        if(

            $existingReview &&

            $existingReview->reviewed_at &&

            $submissionDate

        ){

            $resubmittedAfterReview=Carbon::parse($submissionDate)

                ->gt(Carbon::parse($existingReview->reviewed_at));

        }



        $shouldNotifyRevision=

            $validated['status']==='needs_revision' &&

            (

                !$existingReview ||

                $existingReview->status!=='needs_revision' ||

                $resubmittedAfterReview

            );



        $reviewData=[

            'barangay_id'=>$submission->barangay_id,

            'reviewer_id'=>auth()->user()->user_id,

            'status'=>$validated['status'],

            'complete_contents'=>$completeContents,

            'correct_document'=>$correctDocument,

            'correct_period'=>$correctPeriod,

            'readable_organized'=>0,

            'supporting_documents'=>0,

            'remarks'=>$validated['remarks'] ?? null,

            'reviewed_at'=>now(),

            'updated_at'=>now(),

        ];



        DB::transaction(function() use(

            $existingReview,

            $reviewData,

            $sourceType,

            $sourceId,

            $validated,

            $submission,

            $points

        ){

            if($existingReview){

                DB::table('submission_quality_reviews')

                    ->where('review_id',$existingReview->review_id)

                    ->update($reviewData);

            }else{

                DB::table('submission_quality_reviews')->insert([

                    'source_type'=>$sourceType,

                    'source_id'=>$sourceId,

                    ...$reviewData,

                    'created_at'=>now(),

                ]);

            }



            if(

                $validated['status']==='approved' &&

                (!$existingReview || $existingReview->status!=='approved')

            ){

                $slot=!empty($submission->slot_id)

                    ? DB::table('submission_slots')

                        ->where('term_id',$submission->term_id)

                        ->where('slot_id',$submission->slot_id)

                        ->first()

                    : null;



                $action=$this->approvedRankingAction(

                    $sourceType,

                    $submission,

                    $slot

                );



                $period=$this->approvedRankingPeriod(

                    $sourceType,

                    $submission,

                    $slot

                );



                if($action && $period){

                    $points->award(

                        (int)$submission->barangay_id,

                        $action,

                        $sourceType,

                        $sourceId,

                        (int)auth()->user()->user_id,

                        $period

                    );

                }

            }

        });



        if($shouldNotifyRevision){

            $notifications->notifySubmissionNeedsRevision(

                $submission,

                $sourceType,

                auth()->user(),

                trim((string)$validated['remarks'])

            );

        }



        $message=$validated['status']==='approved'

            ? 'Document approved.'

            : ($shouldNotifyRevision

                ? 'Document marked as Needs Revision. The submitter has been notified.'

                : 'Document remains marked as Needs Revision.');



        return back()->with('quality_status',$message);

    }



    protected function approvedRankingAction(

        string $sourceType,

        object $submission,

        ?object $slot

    ): ?string {

        if($sourceType==='accomplishment_report'){

            return match($slot?->accomplishment_category){

                'youth_development_program'=>RankingPointsService::YOUTH_DEVELOPMENT_PROGRAM_APPROVED,

                'kk_assembly'=>RankingPointsService::KK_ASSEMBLY_APPROVED,

                default=>null,

            };

        }



        $budgetCategory=$submission->budget_category

            ?? $slot?->budget_category;



        if($budgetCategory==='annual_budget'){

            return RankingPointsService::ANNUAL_BUDGET_APPROVED;

        }



        if($budgetCategory!=='coa_report'){

            return null;

        }



        $periodType=$submission->budget_period_type

            ?? $slot?->budget_period_type;



        return match($periodType){

            'monthly'=>RankingPointsService::COA_MONTHLY_APPROVED,

            'quarterly'=>RankingPointsService::COA_QUARTERLY_APPROVED,

            'semi_annual'=>RankingPointsService::COA_SEMI_ANNUAL_APPROVED,

            'annual'=>RankingPointsService::COA_ANNUAL_APPROVED,

            default=>null,

        };

    }
    protected function approvedRankingPeriod(
        string $sourceType,
        object $submission,
        ?object $slot
    ): ?string {
        $submittedAt=$submission->submitted_at
            ?? $submission->created_at
            ?? null;

        return $submittedAt
            ? Carbon::parse($submittedAt)->format('F Y')
            : null;
    }

    protected function filters(Request $request): array

    {

        $defaultYear=$this->defaultFilterYear();

        $year=(int)$request->query('year',$defaultYear);

        $period=(string)$request->query('period','all');

        $month=(int)$request->query('month',now()->month);

        $quarter=(string)$request->query('quarter','Q'.ceil(now()->month/3));



        if(!in_array($period,['all','monthly','quarterly','annual'],true)){

            $period='all';

        }



        return [

            'year'=>$year>2000 && $year<2100 ? $year : $defaultYear,
            'barangay'=>trim((string)$request->query('barangay','')),

            'period'=>$period,

            'month'=>$month>=1 && $month<=12 ? $month : now()->month,

            'quarter'=>in_array($quarter,['Q1','Q2','Q3','Q4'],true)

                ? $quarter

                : 'Q'.ceil(now()->month/3),

        ];

    }



    protected function submissions(array $filters): Collection

    {

        $currentTermId=$this->currentTermId();



        if(!$currentTermId){

            return collect();

        }



        $reports=DB::table('accomplishment_reports')

            ->where('term_id',$currentTermId)

            ->where('reporting_year',$filters['year'])

            ->when($filters['period']==='monthly',fn($query)=>$query

                ->where('report_type','monthly')

                ->where('reporting_month',$filters['month']))

            ->when($filters['period']==='quarterly',fn($query)=>$query

                ->where('report_type','quarterly')

                ->where('reporting_quarter',$filters['quarter']))

            ->when($filters['period']==='annual',fn($query)=>$query

                ->where('report_type','annual'))

            ->get()

            ->groupBy('barangay_id');



        $hasBudgetPeriods=Schema::hasColumn('budget_reports','budget_period_type');



        $budgets=DB::table('budget_reports')

            ->where('term_id',$currentTermId)

            ->where('fiscal_year',$filters['year'])

            ->when($hasBudgetPeriods && $filters['period']==='monthly',fn($query)=>$query

                ->where('budget_period_type','monthly')

                ->where('fiscal_month',$filters['month']))

            ->when($hasBudgetPeriods && $filters['period']==='quarterly',fn($query)=>$query

                ->where('budget_period_type','quarterly')

                ->where('fiscal_quarter',$filters['quarter']))

            ->when($hasBudgetPeriods && $filters['period']==='annual',fn($query)=>$query

                ->where('budget_period_type','annual'))

            ->get()

            ->groupBy('barangay_id');



        return DB::table('barangays')

            ->orderBy('barangay_name')

            ->get(['barangay_id','barangay_name'])
            ->filter(fn($barangay)=>str_contains(mb_strtolower($barangay->barangay_name),mb_strtolower($filters['barangay'] ?? '')))

            ->map(function($barangay) use($reports,$budgets,$hasBudgetPeriods){

                $reportItems=$reports->get($barangay->barangay_id,collect());

                $budgetItems=$budgets->get($barangay->barangay_id,collect());



                $monthlyReports=$reportItems->where('report_type','monthly')->count();

                $quarterlyReports=$reportItems->where('report_type','quarterly')->count();

                $annualReports=$reportItems->where('report_type','annual')->count();



                $monthlyBudgets=$hasBudgetPeriods

                    ? $budgetItems->where('budget_period_type','monthly')->count()

                    : 0;



                $quarterlyBudgets=$hasBudgetPeriods

                    ? $budgetItems->where('budget_period_type','quarterly')->count()

                    : 0;



                $annualBudgets=$hasBudgetPeriods

                    ? $budgetItems->where('budget_period_type','annual')->count()

                    : $budgetItems->count();



                $allItems=$reportItems->merge($budgetItems);

                $lastSubmission=$allItems->sortByDesc('submitted_at')->first();



                return [

                    'barangay_id'=>$barangay->barangay_id,

                    'barangay'=>$barangay->barangay_name,

                    'monthly_count'=>$monthlyReports+$monthlyBudgets,

                    'quarterly_count'=>$quarterlyReports+$quarterlyBudgets,

                    'annual_count'=>$annualReports+$annualBudgets,

                    'monthly'=>$this->statusLabel(

                        $monthlyReports+$monthlyBudgets,

                        $monthlyReports,

                        $monthlyBudgets

                    ),

                    'quarterly'=>$this->statusLabel(

                        $quarterlyReports+$quarterlyBudgets,

                        $quarterlyReports,

                        $quarterlyBudgets

                    ),

                    'annual'=>$this->statusLabel(

                        $annualReports+$annualBudgets,

                        $annualReports,

                        $annualBudgets

                    ),

                    'last_submission'=>$lastSubmission?->submitted_at

                        ? date('M d, Y h:i A',strtotime((string)$lastSubmission->submitted_at))

                        : 'No submission',

                    'status'=>$allItems->isNotEmpty() ? 'submitted' : 'pending',

                ];

            });

    }



    protected function qualitySubmissions(array $filters): Collection

    {

        $currentTermId=$this->currentTermId();



        if(!$currentTermId){

            return collect();

        }



        $reportQuery=DB::table('accomplishment_reports as ar')

            ->leftJoin('barangays as b','ar.barangay_id','=','b.barangay_id')

            ->leftJoin('submission_quality_reviews as qr',function($join){

                $join->on('qr.source_id','=','ar.report_id')

                    ->where('qr.source_type','=','accomplishment_report');

            })

            ->where('ar.term_id',$currentTermId)

            ->where('ar.reporting_year',$filters['year'])

            ->when($filters['period']==='monthly',fn($query)=>$query

                ->where('ar.report_type','monthly')

                ->where('ar.reporting_month',$filters['month']))

            ->when($filters['period']==='quarterly',fn($query)=>$query

                ->where('ar.report_type','quarterly')

                ->where('ar.reporting_quarter',$filters['quarter']))

            ->when($filters['period']==='annual',fn($query)=>$query

                ->where('ar.report_type','annual'));



        if(Schema::hasColumn('accomplishment_reports','status')){

            $reportQuery->where('ar.status','!=','draft');

        }



        $reports=$reportQuery->select(

            DB::raw("'accomplishment_report' as source_type"),

            'ar.report_id as source_id',

            'ar.barangay_id',

            'b.barangay_name',

            'ar.title',

            'ar.report_type as period_type',

            'ar.reporting_year as year',

            'ar.reporting_month as month',

            'ar.reporting_quarter as quarter',

            'ar.uploaded_file_path',

            'ar.generated_pdf_path',

            'ar.submitted_at',

            'ar.created_at',

            'qr.status as quality_status',

            'qr.complete_contents',

            'qr.correct_document',

            'qr.correct_period',

            'qr.remarks as quality_remarks',

            'qr.reviewed_at'

        )->get();



        $hasBudgetPeriods=Schema::hasColumn('budget_reports','budget_period_type');



        $budgetQuery=DB::table('budget_reports as br')

            ->leftJoin('barangays as b','br.barangay_id','=','b.barangay_id')

            ->leftJoin('submission_quality_reviews as qr',function($join){

                $join->on('qr.source_id','=','br.budget_report_id')

                    ->where('qr.source_type','=','budget_report');

            })

            ->where('br.term_id',$currentTermId)

            ->where('br.fiscal_year',$filters['year'])

            ->when($hasBudgetPeriods && $filters['period']==='monthly',fn($query)=>$query

                ->where('br.budget_period_type','monthly')

                ->where('br.fiscal_month',$filters['month']))

            ->when($hasBudgetPeriods && $filters['period']==='quarterly',fn($query)=>$query

                ->where('br.budget_period_type','quarterly')

                ->where('br.fiscal_quarter',$filters['quarter']))

            ->when($hasBudgetPeriods && $filters['period']==='annual',fn($query)=>$query

                ->where('br.budget_period_type','annual'));



        if(Schema::hasColumn('budget_reports','status')){

            $budgetQuery->where('br.status','!=','draft');

        }



        $budgets=$budgetQuery->select(

            DB::raw("'budget_report' as source_type"),

            'br.budget_report_id as source_id',

            'br.barangay_id',

            'b.barangay_name',

            'br.title',

            DB::raw(($hasBudgetPeriods ? 'br.budget_period_type' : "'annual'").' as period_type'),

            'br.fiscal_year as year',

            DB::raw(($hasBudgetPeriods ? 'br.fiscal_month' : 'NULL').' as month'),

            DB::raw(($hasBudgetPeriods ? 'br.fiscal_quarter' : 'NULL').' as quarter'),

            'br.uploaded_file_path',

            'br.generated_pdf_path',

            'br.submitted_at',

            'br.created_at',

            'qr.status as quality_status',

            'qr.complete_contents',

            'qr.correct_document',

            'qr.correct_period',

            'qr.remarks as quality_remarks',

            'qr.reviewed_at'

        )->get();



        return $reports

            ->merge($budgets)

            ->sortByDesc(fn($item)=>$item->submitted_at ?? $item->created_at)

            ->values()

            ->map(function($item){

                $item->quality_status=$item->quality_status ?: 'pending';



                $item->source_label=$item->source_type==='budget_report'

                    ? 'Budget Report'

                    : 'Accomplishment Report';



                $item->period_label=match($item->period_type){

                    'monthly'=>isset($item->month)

                        ? Carbon::create((int)$item->year,(int)$item->month,1)->format('F Y')

                        : 'Monthly '.$item->year,

                    'quarterly'=>($item->quarter ?: 'Quarterly').' '.$item->year,

                    default=>'Annual '.$item->year,

                };



                $path=$item->uploaded_file_path ?: $item->generated_pdf_path;



                $item->file_url=$path

                    ? asset(ltrim($path,'/'))

                    : null;



                $date=$item->submitted_at ?: $item->created_at;



                $item->submitted_label=$date

                    ? Carbon::parse($date)->format('M d, Y h:i A')

                    : 'Unknown';



                return $item;

            });

    }



    protected function qualityGroups(Collection $items): Collection

    {

        return $items

            ->groupBy('barangay_id')

            ->map(function($barangayItems,$barangayId){

                $barangayItems=$barangayItems->values();

                $first=$barangayItems->first();



                return [

                    'barangay_id'=>(int)$barangayId,

                    'barangay_name'=>$first?->barangay_name ?: 'Unknown Barangay',

                    'total'=>$barangayItems->count(),

                    'pending'=>$barangayItems->where('quality_status','pending')->count(),

                    'needs_revision'=>$barangayItems->where('quality_status','needs_revision')->count(),

                    'approved'=>$barangayItems->where('quality_status','approved')->count(),

                    'accomplishment_reports'=>$barangayItems->where('source_type','accomplishment_report')->values(),

                    'budget_reports'=>$barangayItems->where('source_type','budget_report')->values(),

                    'items'=>$barangayItems,

                ];

            })

            ->sortBy('barangay_name',SORT_NATURAL|SORT_FLAG_CASE)

            ->values();

    }



    protected function stats(Collection $submissions): array

    {

        $total=$submissions->count();

        $submitted=$submissions->where('status','submitted')->count();

        $late=0;



        return [

            ['label'=>'Total Barangays','value'=>$total,'valueClass'=>'text-gray-800'],

            ['label'=>'Submitted','value'=>$submitted,'valueClass'=>'text-green-500'],

            ['label'=>'Pending','value'=>max($total-$submitted-$late,0),'valueClass'=>'text-yellow-500'],

            ['label'=>'Late','value'=>$late,'valueClass'=>'text-red-500'],

        ];

    }



    protected function statusLabel(int $count,int $reportCount=0,int $budgetCount=0): string

    {

        if($count<=0){

            return 'Pending';

        }



        return $count.' submitted (R: '.$reportCount.', B: '.$budgetCount.')';

    }



    protected function defaultFilterYear(): int

    {

        $currentTermId=$this->currentTermId();



        if(!$currentTermId){

            return $this->currentTermStartYear();

        }



        $reportYear=DB::table('accomplishment_reports')

            ->where('term_id',$currentTermId)

            ->max('reporting_year');



        $budgetYear=DB::table('budget_reports')

            ->where('term_id',$currentTermId)

            ->max('fiscal_year');



        $years=collect([$reportYear,$budgetYear])

            ->filter()

            ->map(fn($year)=>(int)$year)

            ->sortDesc()

            ->values();



        return $years->isNotEmpty()

            ? (int)$years->first()

            : $this->currentTermStartYear();

    }



    protected function availableYears(): array

    {

        $currentTermId=$this->currentTermId();

        $defaultYear=$this->defaultFilterYear();



        if(!$currentTermId){

            return [$defaultYear];

        }



        $reportYears=DB::table('accomplishment_reports')

            ->where('term_id',$currentTermId)

            ->select('reporting_year')

            ->distinct()

            ->pluck('reporting_year')

            ->map(fn($year)=>(int)$year);



        $budgetYears=DB::table('budget_reports')

            ->where('term_id',$currentTermId)

            ->select('fiscal_year')

            ->distinct()

            ->pluck('fiscal_year')

            ->map(fn($year)=>(int)$year);



        $years=$reportYears

            ->merge($budgetYears)

            ->filter()

            ->unique()

            ->sortDesc()

            ->values()

            ->all();



        return $years ?: [$defaultYear];

    }



    protected function currentTermStartYear(): int

    {

        if(!Schema::hasTable('administration_terms')){

            return now()->year;

        }



        $startYear=DB::table('administration_terms')

            ->where('status','current')

            ->orderByDesc('term_id')

            ->value('start_year');



        return $startYear ? (int)$startYear : now()->year;

    }



    protected function currentTermId(): ?int

    {

        if(!Schema::hasTable('administration_terms')){

            return null;

        }



        $termId=DB::table('administration_terms')

            ->where('status','current')

            ->orderByDesc('term_id')

            ->value('term_id');



        return $termId ? (int)$termId : null;

    }



    protected function months(): array

    {

        return [

            1=>'January',

            2=>'February',

            3=>'March',

            4=>'April',

            5=>'May',

            6=>'June',

            7=>'July',

            8=>'August',

            9=>'September',

            10=>'October',

            11=>'November',

            12=>'December',

        ];

    }

}
