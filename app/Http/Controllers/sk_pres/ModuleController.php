<?php

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $user=auth()->user();
        $fullName=trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';
        $currentTermId=$this->currentTermId();

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

        $slotGroups=$this->paginatedSlotGroups($currentTermId);
        $counts=$this->slotStateCounts($currentTermId);

        return view('sk_pres.module',[
            'fullName'=>$fullName,
            'menuItems'=>$menuItems,
            'currentUrl'=>url()->current(),
            'slotGroups'=>$slotGroups,
            'ydpProgramTypes'=>$this->ydpProgramTypes(),
            'summaryCards'=>[
                [
                    'label'=>'Total Slots',
                    'value'=>$counts['total'],
                    'border'=>'border-red-400',
                    'iconBg'=>'bg-red-50',
                    'iconColor'=>'text-red-500',
                    'icon'=>'📋',
                ],
                [
                    'label'=>'Open Slots',
                    'value'=>$counts['open'],
                    'border'=>'border-green-400',
                    'iconBg'=>'bg-green-50',
                    'iconColor'=>'text-green-500',
                    'icon'=>'🔓',
                ],
                [
                    'label'=>'Past Deadline',
                    'value'=>$counts['past_deadline'],
                    'border'=>'border-orange-400',
                    'iconBg'=>'bg-orange-50',
                    'iconColor'=>'text-orange-500',
                    'icon'=>'⚠️',
                ],
                [
                    'label'=>'Closed Slots',
                    'value'=>$counts['closed'],
                    'border'=>'border-gray-400',
                    'iconBg'=>'bg-gray-50',
                    'iconColor'=>'text-gray-500',
                    'icon'=>'🔒',
                ],
            ],
        ]);
    }

    public function live(): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        return response()->json($this->modulePayload());
    }

    public function storeLive(Request $request,NotificationService $notifications): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $currentTermId=$this->currentTermId();

        if(!$currentTermId){
            return response()->json([
                'message'=>'There is no active administration term. Start a new administration term first.',
            ],422);
        }

        $validated=$this->validateSlot($request);

        $slotId=DB::table('submission_slots')->insertGetId(
            $this->slotData($validated,$currentTermId),
            'slot_id'
        );

        $notifications->notifySubmissionSlotCreated([
            'slot_id'=>$slotId,
            'submission_type'=>$validated['submission_type'],
            'title'=>$validated['submission_title'],
            'role'=>$validated['submission_role'],
            'start_date'=>$validated['start_date'],
            'end_date'=>$validated['end_date'],
        ],auth()->user());

        return response()->json($this->modulePayload());
    }

    public function closeLive(int $slotId): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $currentTermId=$this->currentTermId();

        if(!$currentTermId){
            return response()->json([
                'message'=>'There is no active administration term.',
            ],422);
        }

        $slot=DB::table('submission_slots')
            ->where('slot_id',$slotId)
            ->where('term_id',$currentTermId)
            ->first();

        if(!$slot){
            return response()->json([
                'message'=>'Submission slot was not found in the current administration.',
            ],404);
        }

        if($slot->status==='closed'){
            $payload=$this->modulePayload();
            $payload['message']='Submission slot is already closed.';

            return response()->json($payload);
        }

        DB::table('submission_slots')
            ->where('slot_id',$slotId)
            ->where('term_id',$currentTermId)
            ->where('status','open')
            ->update([
                'status'=>'closed',
            ]);

        $payload=$this->modulePayload();
        $payload['message']='Submission slot closed successfully.';

        return response()->json($payload);
    }

    public function destroyLive(int $slotId): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $currentTermId=$this->currentTermId();

        if(!$currentTermId){
            return response()->json([
                'message'=>'There is no active administration term.',
            ],422);
        }

        $deleted=DB::table('submission_slots')
            ->where('slot_id',$slotId)
            ->where('term_id',$currentTermId)
            ->delete();

        if(!$deleted){
            return response()->json([
                'message'=>'Submission slot was not found in the current administration.',
            ],404);
        }

        return response()->json($this->modulePayload());
    }

    public function store(Request $request,NotificationService $notifications): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $currentTermId=$this->currentTermId();

        if(!$currentTermId){
            return back()
                ->withInput()
                ->with('warning','There is no active administration term. Start a new administration term first.');
        }

        $validated=$this->validateSlot($request);

        $slotId=DB::table('submission_slots')->insertGetId(
            $this->slotData($validated,$currentTermId),
            'slot_id'
        );

        $notifications->notifySubmissionSlotCreated([
            'slot_id'=>$slotId,
            'submission_type'=>$validated['submission_type'],
            'title'=>$validated['submission_title'],
            'role'=>$validated['submission_role'],
            'start_date'=>$validated['start_date'],
            'end_date'=>$validated['end_date'],
        ],auth()->user());

        return redirect()
            ->route('sk_pres.module')
            ->with('status','Submission slot created successfully.');
    }

    public function close(int $slotId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $currentTermId=$this->currentTermId();

        if(!$currentTermId){
            return back()->with(
                'warning',
                'There is no active administration term.'
            );
        }

        $slot=DB::table('submission_slots')
            ->where('slot_id',$slotId)
            ->where('term_id',$currentTermId)
            ->first();

        if(!$slot){
            return back()->with(
                'warning',
                'Submission slot was not found in the current administration.'
            );
        }

        if($slot->status==='closed'){
            return back()->with(
                'warning',
                'Submission slot is already closed.'
            );
        }

        DB::table('submission_slots')
            ->where('slot_id',$slotId)
            ->where('term_id',$currentTermId)
            ->where('status','open')
            ->update([
                'status'=>'closed',
            ]);

        return redirect()
            ->route('sk_pres.module')
            ->with('status','Submission slot closed successfully.');
    }

    public function update(Request $request,int $slotId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $currentTermId=$this->currentTermId();

        if(!$currentTermId){
            return back()->with(
                'warning',
                'There is no active administration term.'
            );
        }

        $slot=DB::table('submission_slots')
            ->where('slot_id',$slotId)
            ->where('term_id',$currentTermId)
            ->first();

        if(!$slot){
            return back()->with(
                'warning',
                'Submission slot was not found in the current administration.'
            );
        }

        $validated=$this->validateSlot($request);
        $changes=$this->slotData($validated,$currentTermId);

        unset(
            $changes['term_id'],
            $changes['status'],
            $changes['created_at']
        );

        DB::table('submission_slots')
            ->where('slot_id',$slotId)
            ->where('term_id',$currentTermId)
            ->update($changes);

        return redirect()
            ->route('sk_pres.module')
            ->with('status','Submission slot updated successfully.');
    }

    public function destroy(int $slotId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $currentTermId=$this->currentTermId();

        if(!$currentTermId){
            return back()->with(
                'warning',
                'There is no active administration term.'
            );
        }

        $deleted=DB::table('submission_slots')
            ->where('slot_id',$slotId)
            ->where('term_id',$currentTermId)
            ->delete();

        if(!$deleted){
            return back()->with(
                'warning',
                'Submission slot was not found in the current administration.'
            );
        }

        return redirect()
            ->route('sk_pres.module')
            ->with('status','Submission slot deleted successfully.');
    }

    protected function validateSlot(Request $request): array
    {
        $isAccomplishment=$request->input('submission_type') === 'accomplishment_report';
        $isBudget=$request->input('submission_type') === 'budget_report';
        $accomplishmentCategory=$request->input('accomplishment_category');
        $isYdp=$isAccomplishment && $accomplishmentCategory === 'youth_development_program';
        $isCoaReport=$isBudget && $request->input('budget_category') === 'coa_report';
        $period=$request->input('budget_period_type');

        return $request->validate([
            'submission_type'=>[
                'required',
                'in:accomplishment_report,budget_report',
            ],

            'accomplishment_category'=>[
                Rule::requiredIf($isAccomplishment),
                'nullable',
                Rule::in([
                    'general',
                    'youth_development_program',
                    'kk_assembly',
                ]),
            ],

            'ydp_program_type'=>[
                Rule::requiredIf($isYdp),
                'nullable',
                Rule::in(array_keys($this->ydpProgramTypes())),
            ],

            'budget_category'=>[
                Rule::requiredIf($isBudget),
                'nullable',
                Rule::in([
                    'annual_budget',
                    'supplemental_budget',
                    'coa_report',
                ]),
            ],

            'fiscal_year'=>[
                Rule::requiredIf($isBudget),
                'nullable',
                'integer',
                'min:2000',
                'max:2100',
            ],

            'budget_period_type'=>[
                Rule::requiredIf($isCoaReport),
                'nullable',
                Rule::in([
                    'monthly',
                    'quarterly',
                    'semi_annual',
                    'annual',
                ]),
            ],

            'fiscal_month'=>[
                Rule::requiredIf($isCoaReport && $period === 'monthly'),
                'nullable',
                'integer',
                'min:1',
                'max:12',
            ],

            'fiscal_quarter'=>[
                Rule::requiredIf($isCoaReport && $period === 'quarterly'),
                'nullable',
                Rule::in([
                    'Q1',
                    'Q2',
                    'Q3',
                    'Q4',
                ]),
            ],

            'fiscal_half'=>[
                Rule::requiredIf($isCoaReport && $period === 'semi_annual'),
                'nullable',
                Rule::in([
                    'H1',
                    'H2',
                ]),
            ],

            'submission_title'=>[
                'required',
                'string',
                'max:255',
            ],

            'description'=>[
                'nullable',
                'string',
            ],

            'submission_role'=>[
                'required',
                'in:SK Chairman,SK Secretary,Both',
            ],

            'start_date'=>[
                'required',
                'date',
            ],

            'end_date'=>[
                'required',
                'date',
                'after_or_equal:start_date',
            ],
        ]);
    }

    protected function slotData(array $validated,int $termId): array
    {
        $isAccomplishment=$validated['submission_type'] === 'accomplishment_report';
        $isBudget=$validated['submission_type'] === 'budget_report';

        $accomplishmentCategory=$isAccomplishment
            ? ($validated['accomplishment_category'] ?? null)
            : null;

        $isYdp=$isAccomplishment &&
            $accomplishmentCategory === 'youth_development_program';

        $isCoaReport=$isBudget &&
            ($validated['budget_category'] ?? null) === 'coa_report';

        $period=$isCoaReport
            ? ($validated['budget_period_type'] ?? null)
            : null;

        return [
            'term_id'=>$termId,
            'submission_type'=>$validated['submission_type'],

            'accomplishment_category'=>$accomplishmentCategory,

            'ydp_program_type'=>$isYdp
                ? ($validated['ydp_program_type'] ?? null)
                : null,

            'budget_category'=>$isBudget
                ? ($validated['budget_category'] ?? null)
                : null,

            'fiscal_year'=>$isBudget
                ? ($validated['fiscal_year'] ?? null)
                : null,

            'budget_period_type'=>$isCoaReport
                ? $period
                : null,

            'fiscal_month'=>$isCoaReport && $period === 'monthly'
                ? ($validated['fiscal_month'] ?? null)
                : null,

            'fiscal_quarter'=>$isCoaReport && $period === 'quarterly'
                ? ($validated['fiscal_quarter'] ?? null)
                : null,

            'fiscal_half'=>$isCoaReport && $period === 'semi_annual'
                ? ($validated['fiscal_half'] ?? null)
                : null,

            'title'=>$validated['submission_title'],
            'description'=>$validated['description'] ?? null,
            'role'=>$validated['submission_role'],
            'start_date'=>$validated['start_date'],
            'end_date'=>$validated['end_date'],
            'status'=>'open',
            'created_at'=>now(),
        ];
    }

    protected function paginatedSlotGroups(?int $currentTermId): array
    {
        if(!$currentTermId){
            return [
                'past_deadline'=>$this->emptyPaginator('past_page'),
                'open'=>$this->emptyPaginator('open_page'),
                'upcoming'=>$this->emptyPaginator('upcoming_page'),
                'closed'=>$this->emptyPaginator('closed_page'),
            ];
        }

        $today=Carbon::now('Asia/Manila')->toDateString();

        $pastDeadline=DB::table('submission_slots')
            ->where('term_id',$currentTermId)
            ->where('status','open')
            ->whereDate('end_date','<',$today)
            ->orderByDesc('end_date')
            ->orderByDesc('created_at')
            ->paginate(
                6,
                ['*'],
                'past_page'
            )
            ->withQueryString();

        $open=DB::table('submission_slots')
            ->where('term_id',$currentTermId)
            ->where('status','open')
            ->whereDate('start_date','<=',$today)
            ->whereDate('end_date','>=',$today)
            ->orderBy('end_date')
            ->orderByDesc('created_at')
            ->paginate(
                6,
                ['*'],
                'open_page'
            )
            ->withQueryString();

        $upcoming=DB::table('submission_slots')
            ->where('term_id',$currentTermId)
            ->where('status','open')
            ->whereDate('start_date','>',$today)
            ->orderBy('start_date')
            ->orderBy('end_date')
            ->paginate(
                6,
                ['*'],
                'upcoming_page'
            )
            ->withQueryString();

        $closed=DB::table('submission_slots')
            ->where('term_id',$currentTermId)
            ->where('status','closed')
            ->orderByDesc('created_at')
            ->paginate(
                6,
                ['*'],
                'closed_page'
            )
            ->withQueryString();

        return [
            'past_deadline'=>$this->decoratePaginator(
                $pastDeadline,
                'past_deadline'
            ),
            'open'=>$this->decoratePaginator(
                $open,
                'open'
            ),
            'upcoming'=>$this->decoratePaginator(
                $upcoming,
                'upcoming'
            ),
            'closed'=>$this->decoratePaginator(
                $closed,
                'closed'
            ),
        ];
    }

    protected function slotStateCounts(?int $currentTermId): array
    {
        if(!$currentTermId){
            return [
                'total'=>0,
                'open'=>0,
                'past_deadline'=>0,
                'upcoming'=>0,
                'closed'=>0,
            ];
        }

        $today=Carbon::now('Asia/Manila')->toDateString();

        $base=DB::table('submission_slots')
            ->where('term_id',$currentTermId);

        $total=(clone $base)->count();

        $open=(clone $base)
            ->where('status','open')
            ->whereDate('start_date','<=',$today)
            ->whereDate('end_date','>=',$today)
            ->count();

        $pastDeadline=(clone $base)
            ->where('status','open')
            ->whereDate('end_date','<',$today)
            ->count();

        $upcoming=(clone $base)
            ->where('status','open')
            ->whereDate('start_date','>',$today)
            ->count();

        $closed=(clone $base)
            ->where('status','closed')
            ->count();

        return [
            'total'=>$total,
            'open'=>$open,
            'past_deadline'=>$pastDeadline,
            'upcoming'=>$upcoming,
            'closed'=>$closed,
        ];
    }

    protected function decoratePaginator(
        LengthAwarePaginator $paginator,
        string $state
    ): LengthAwarePaginator {
        $paginator->setCollection(
            $paginator->getCollection()
                ->map(
                    fn($slot)=>$this->decorateSlot(
                        $slot,
                        $state
                    )
                )
        );

        return $paginator;
    }

    protected function decorateSlot(
        object $slot,
        ?string $state=null
    ): object {
        $today=Carbon::now('Asia/Manila')->toDateString();

        if(!$state){
            if($slot->status==='closed'){
                $state='closed';
            }elseif($slot->start_date>$today){
                $state='upcoming';
            }elseif($slot->end_date<$today){
                $state='past_deadline';
            }else{
                $state='open';
            }
        }

        $slot->management_state=$state;

        if($state==='closed'){
            $slot->management_state_label='Closed';
            $slot->management_state_badge='bg-gray-100 text-gray-600';
        }elseif($state==='upcoming'){
            $slot->management_state_label='Upcoming';
            $slot->management_state_badge='bg-blue-100 text-blue-600';
        }elseif($state==='past_deadline'){
            $slot->management_state_label='Past Deadline';
            $slot->management_state_badge='bg-orange-100 text-orange-600';
        }else{
            $slot->management_state_label='Open';
            $slot->management_state_badge='bg-green-100 text-green-600';
        }

        return $slot;
    }

    protected function emptyPaginator(
        string $pageName
    ): LengthAwarePaginator {
        return new LengthAwarePaginator(
            collect(),
            0,
            6,
            1,
            [
                'path'=>request()->url(),
                'pageName'=>$pageName,
            ]
        );
    }

    protected function currentTermSlots(?int $currentTermId): Collection
    {
        if(!$currentTermId){
            return collect();
        }

        return DB::table('submission_slots')
            ->where('term_id',$currentTermId)
            ->orderByDesc('created_at')
            ->get()
            ->map(
                fn($slot)=>$this->decorateSlot($slot)
            )
            ->sortBy(function($slot){
                $order=match($slot->management_state){
                    'past_deadline'=>1,
                    'open'=>2,
                    'upcoming'=>3,
                    'closed'=>4,
                    default=>5,
                };

                return sprintf(
                    '%d-%s',
                    $order,
                    Carbon::parse($slot->end_date)->format('Ymd')
                );
            })
            ->values();
    }

    protected function groupSlots(Collection $slots): array
    {
        return [
            'past_deadline'=>$slots
                ->where('management_state','past_deadline')
                ->values(),

            'open'=>$slots
                ->where('management_state','open')
                ->values(),

            'upcoming'=>$slots
                ->where('management_state','upcoming')
                ->values(),

            'closed'=>$slots
                ->where('management_state','closed')
                ->values(),
        ];
    }

    protected function ydpProgramTypes(): array
    {
        return [
            'eba_sportsfest'=>'EBA Sportsfest',
            'barangay_league'=>'Barangay League',
            'disaster_preparedness'=>'Disaster Preparedness',
            'health_seminars'=>'Health Seminar/s',
            'scholarship_educational_financial_assistance'=>'Scholarship / Educational Financial Assistance',
            'educational_programs_school_supplies_donation_drives'=>'Educational Programs / School Supplies Donation Drives',
            'environmental_programs'=>'Environmental Programs',
            'lnk_programs'=>'LNK Programs',
            'anti_drug_abuse_programs'=>'Anti-Drug Abuse Programs',
            'gender_sensitivity_programs'=>'Gender Sensitivity Programs',
            'climate_change_adaptation_programs'=>'Climate Change Adaptation Programs',
            'youth_employment_livelihood'=>'Youth Employment and Livelihood',
        ];
    }

    protected function modulePayload(): array
    {
        $currentTermId=$this->currentTermId();
        $slots=$this->currentTermSlots($currentTermId);
        $slotGroups=$this->groupSlots($slots);

        return [
            'slots'=>$slots,
            'slotGroups'=>$slotGroups,

            'summary'=>[
                'totalSlots'=>$slots->count(),
                'openSlots'=>$slotGroups['open']->count(),
                'pastDeadlineSlots'=>$slotGroups['past_deadline']->count(),
                'upcomingSlots'=>$slotGroups['upcoming']->count(),
                'closedSlots'=>$slotGroups['closed']->count(),
            ],

            'updatedAt'=>now()->format('M d, Y h:i A'),
        ];
    }

    protected function currentTermId(): ?int
    {
        $termId=DB::table('administration_terms')
            ->where('status','current')
            ->orderByDesc('term_id')
            ->value('term_id');

        return $termId
            ? (int)$termId
            : null;
    }
}
