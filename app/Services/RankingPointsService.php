<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class RankingPointsService
{
    public const ON_TIME_REPORT_SUBMISSION='on_time_report_submission';
    public const COMMUNITY_ENGAGEMENT='community_engagement';
    public const QUALITY_DOCUMENTATION='quality_documentation';
    public const EVENT_PARTICIPATION='event_participation';

    public const LATE_SUBMISSION='late_submission';
    public const MEETING_ATTENDANCE='meeting_attendance';
    public const MISSED_MEETING='missed_meeting';

    public const YOUTH_DEVELOPMENT_PROGRAM_APPROVED='youth_development_program_approved';
    public const KK_ASSEMBLY_APPROVED='kk_assembly_approved';

    public const ANNUAL_BUDGET_APPROVED='annual_budget_approved';
    public const COA_MONTHLY_APPROVED='coa_monthly_approved';
    public const COA_QUARTERLY_APPROVED='coa_quarterly_approved';
    public const COA_SEMI_ANNUAL_APPROVED='coa_semi_annual_approved';
    public const COA_ANNUAL_APPROVED='coa_annual_approved';

    protected array $ignoredActions=[
        self::ON_TIME_REPORT_SUBMISSION,
        self::COMMUNITY_ENGAGEMENT,
        self::QUALITY_DOCUMENTATION,
        self::EVENT_PARTICIPATION,
    ];

    protected array $rules=[
        self::LATE_SUBMISSION=>[
            'label'=>'Late Required Submission',
            'points'=>-5,
            'column'=>'timely_submission_points',
            'type'=>'negative',
        ],
        self::MEETING_ATTENDANCE=>[
            'label'=>'Official Meeting Attendance',
            'points'=>5,
            'column'=>'participation_points',
            'type'=>'positive',
        ],
        self::MISSED_MEETING=>[
            'label'=>'Missed Official Meeting',
            'points'=>-5,
            'column'=>'participation_points',
            'type'=>'negative',
        ],
        self::YOUTH_DEVELOPMENT_PROGRAM_APPROVED=>[
            'label'=>'Youth Development Program',
            'points'=>5,
            'column'=>'completeness_points',
            'type'=>'positive',
        ],
        self::KK_ASSEMBLY_APPROVED=>[
            'label'=>'KK Assembly',
            'points'=>10,
            'column'=>'completeness_points',
            'type'=>'positive',
        ],
        self::ANNUAL_BUDGET_APPROVED=>[
            'label'=>'Annual Budget',
            'points'=>10,
            'column'=>'completeness_points',
            'type'=>'positive',
        ],
        self::COA_MONTHLY_APPROVED=>[
            'label'=>'Monthly COA Report',
            'points'=>5,
            'column'=>'completeness_points',
            'type'=>'positive',
        ],
        self::COA_QUARTERLY_APPROVED=>[
            'label'=>'Quarterly COA Report',
            'points'=>10,
            'column'=>'completeness_points',
            'type'=>'positive',
        ],
        self::COA_SEMI_ANNUAL_APPROVED=>[
            'label'=>'Semi-Annual COA Report',
            'points'=>10,
            'column'=>'completeness_points',
            'type'=>'positive',
        ],
        self::COA_ANNUAL_APPROVED=>[
            'label'=>'Annual COA Report',
            'points'=>20,
            'column'=>'completeness_points',
            'type'=>'positive',
        ],
    ];

    public function award(
        int $barangayId,
        string $action,
        string $sourceType,
        string|int $sourceId,
        ?int $userId=null,
        ?string $period=null
    ): bool {
        if(
            $barangayId<=0 ||
            !Schema::hasTable('rankings') ||
            !Schema::hasTable('ranking_point_logs') ||
            !Schema::hasTable('administration_terms')
        ){
            return false;
        }

        if(in_array($action,$this->ignoredActions,true)){
            return false;
        }

        $termId=$this->currentTermId();

        if(!$termId){
            return false;
        }

        $rule=$this->rules[$action] ?? null;

        if(!$rule){
            throw new InvalidArgumentException("Unknown ranking point action [{$action}].");
        }

        $period ??=now()->format('F Y');
        $sourceId=(string)$sourceId;

        return DB::transaction(function() use(
            $termId,
            $barangayId,
            $action,
            $sourceType,
            $sourceId,
            $userId,
            $period,
            $rule
        ){
            if($this->alreadyAwarded(
                $termId,
                $barangayId,
                $action,
                $sourceType,
                $sourceId
            )){
                return false;
            }

            if($this->hasConflictingOutcome(
                $termId,
                $barangayId,
                $action,
                $sourceType,
                $sourceId
            )){
                return false;
            }

            try{
                DB::table('ranking_point_logs')->insert([
                    'term_id'=>$termId,
                    'barangay_id'=>$barangayId,
                    'user_id'=>$userId,
                    'reporting_period'=>$period,
                    'action'=>$action,
                    'points'=>$rule['points'],
                    'source_type'=>$sourceType,
                    'source_id'=>$sourceId,
                    'created_at'=>now(),
                ]);
            }catch(QueryException $exception){
                if($this->isDuplicateLog($exception)){
                    return false;
                }

                throw $exception;
            }

            $this->ensureRankingRow(
                $termId,
                $barangayId,
                $period
            );

            $this->incrementRanking(
                $termId,
                $barangayId,
                $period,
                $rule['column'],
                (int)$rule['points']
            );

            return true;
        });
    }

    public function rules(): array
    {
        return $this->rules;
    }

    public function recordMissedSubmissions(?string $period=null): void
    {
        if(
            !Schema::hasTable('submission_slots') ||
            !Schema::hasTable('users') ||
            !Schema::hasTable('ranking_point_logs') ||
            !Schema::hasTable('rankings') ||
            !Schema::hasTable('administration_terms')
        ){
            return;
        }

        $termId=$this->currentTermId();

        if(!$termId){
            return;
        }

        $today=Carbon::now('Asia/Manila')->toDateString();

        $slots=DB::table('submission_slots')
            ->where('term_id',$termId)
            ->whereDate('end_date','<',$today)
            ->whereIn('submission_type',[
                'accomplishment_report',
                'budget_report',
            ])
            ->get();

        foreach($slots as $slot){
            $table=match($slot->submission_type){
                'accomplishment_report'=>'accomplishment_reports',
                'budget_report'=>'budget_reports',
                default=>null,
            };

            if(!$table || !Schema::hasTable($table)){
                continue;
            }

            $slotPeriod=$period
                ?:Carbon::parse($slot->end_date,'Asia/Manila')->format('F Y');

            foreach($this->eligibleBarangayIdsForSlot($slot) as $barangayId){
                if($this->hasOnTimeSubmission(
                    $table,
                    $slot,
                    (int)$barangayId
                )){
                    continue;
                }

                $this->award(
                    (int)$barangayId,
                    self::LATE_SUBMISSION,
                    'submission_slot',
                    (int)$slot->slot_id,
                    null,
                    $slotPeriod
                );
            }
        }
    }

    public function recordMissedMeetings(?string $period=null): void
    {
        if(
            !Schema::hasTable('meetings') ||
            !Schema::hasTable('users') ||
            !Schema::hasTable('ranking_point_logs') ||
            !Schema::hasTable('rankings') ||
            !Schema::hasTable('administration_terms')
        ){
            return;
        }

        $termId=$this->currentTermId();

        if(!$termId){
            return;
        }

        $completedMeetings=DB::table('meetings')
            ->where('term_id',$termId)
            ->where('status','completed')
            ->whereNotNull('meeting_date')
            ->get([
                'meeting_id',
                'meeting_date',
            ]);

        if($completedMeetings->isEmpty()){
            return;
        }

        $barangayQuery=DB::table('users')
            ->whereIn('role',[
                'sk_chairman',
                'sk_secretary',
            ])
            ->whereNotNull('barangay_id');

        if(Schema::hasColumn('users','status')){
            $barangayQuery->where('status','active');
        }

        if(Schema::hasColumn('users','archived_at')){
            $barangayQuery->whereNull('archived_at');
        }

        $barangayIds=$barangayQuery
            ->distinct()
            ->pluck('barangay_id');

        foreach($completedMeetings as $meeting){
            $meetingId=(int)$meeting->meeting_id;

            $attendanceRecorded=DB::table('ranking_point_logs')
                ->where('term_id',$termId)
                ->where('action',self::MEETING_ATTENDANCE)
                ->where('source_type','meeting')
                ->where('source_id',(string)$meetingId)
                ->exists();

            if(!$attendanceRecorded){
                continue;
            }

            $meetingPeriod=$period
                ?:Carbon::parse($meeting->meeting_date)->format('F Y');

            foreach($barangayIds as $barangayId){
                $attended=DB::table('ranking_point_logs')
                    ->where('term_id',$termId)
                    ->where('barangay_id',$barangayId)
                    ->where('action',self::MEETING_ATTENDANCE)
                    ->where('source_type','meeting')
                    ->where('source_id',(string)$meetingId)
                    ->exists();

                if($attended){
                    continue;
                }

                $this->award(
                    (int)$barangayId,
                    self::MISSED_MEETING,
                    'meeting',
                    $meetingId,
                    null,
                    $meetingPeriod
                );
            }
        }
    }

    protected function eligibleBarangayIdsForSlot(object $slot)
    {
        $roles=match($slot->role ?? 'Both'){
            'SK Chairman'=>['sk_chairman'],
            'SK Secretary'=>['sk_secretary'],
            default=>[
                'sk_chairman',
                'sk_secretary',
            ],
        };

        $query=DB::table('users')
            ->whereIn('role',$roles)
            ->whereNotNull('barangay_id');

        if(Schema::hasColumn('users','status')){
            $query->where('status','active');
        }

        if(Schema::hasColumn('users','archived_at')){
            $query->whereNull('archived_at');
        }

        return $query
            ->distinct()
            ->pluck('barangay_id')
            ->map(fn($id)=>(int)$id)
            ->values();
    }

    protected function hasOnTimeSubmission(
        string $table,
        object $slot,
        int $barangayId
    ): bool {
        $deadline=Carbon::parse(
            $slot->end_date,
            'Asia/Manila'
        )->endOfDay()->format('Y-m-d H:i:s');

        return DB::table($table)
            ->where('term_id',(int)$slot->term_id)
            ->where('barangay_id',$barangayId)
            ->where('slot_id',(int)$slot->slot_id)
            ->where(function($query) use($deadline){
                $query->where('created_at','<=',$deadline)
                    ->orWhere(function($query) use($deadline){
                        $query->whereNull('created_at')
                            ->where('submitted_at','<=',$deadline);
                    });
            })
            ->exists();
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

        return $termId
            ? (int)$termId
            : null;
    }

    protected function alreadyAwarded(
        int $termId,
        int $barangayId,
        string $action,
        string $sourceType,
        string $sourceId
    ): bool {
        return DB::table('ranking_point_logs')
            ->where('term_id',$termId)
            ->where('barangay_id',$barangayId)
            ->where('action',$action)
            ->where('source_type',$sourceType)
            ->where('source_id',$sourceId)
            ->exists();
    }

    protected function hasConflictingOutcome(
        int $termId,
        int $barangayId,
        string $action,
        string $sourceType,
        string $sourceId
    ): bool {
        $conflictingActions=match($action){
            self::LATE_SUBMISSION=>[
                self::ON_TIME_REPORT_SUBMISSION,
            ],
            self::MEETING_ATTENDANCE=>[
                self::MISSED_MEETING,
            ],
            self::MISSED_MEETING=>[
                self::MEETING_ATTENDANCE,
            ],
            default=>[],
        };

        if(empty($conflictingActions)){
            return false;
        }

        return DB::table('ranking_point_logs')
            ->where('term_id',$termId)
            ->where('barangay_id',$barangayId)
            ->whereIn('action',$conflictingActions)
            ->where('source_type',$sourceType)
            ->where('source_id',$sourceId)
            ->exists();
    }

    protected function ensureRankingRow(
        int $termId,
        int $barangayId,
        string $period
    ): void {
        DB::table('rankings')->insertOrIgnore([
            'term_id'=>$termId,
            'barangay_id'=>$barangayId,
            'reporting_period'=>$period,
            'total_points'=>0,
            'timely_submission_points'=>0,
            'completeness_points'=>0,
            'participation_points'=>0,
            'created_at'=>now(),
        ]);
    }

    protected function incrementRanking(
        int $termId,
        int $barangayId,
        string $period,
        string $column,
        int $points
    ): void {
        DB::table('rankings')
            ->where('term_id',$termId)
            ->where('barangay_id',$barangayId)
            ->where('reporting_period',$period)
            ->update([
                $column=>DB::raw($column.' + '.$points),
                'total_points'=>DB::raw('total_points + '.$points),
            ]);
    }

    protected function isDuplicateLog(QueryException $exception): bool
    {
        $driverCode=(string)($exception->errorInfo[1] ?? '');
        $sqlState=(string)($exception->errorInfo[0] ?? '');

        return $driverCode==='1062' || $sqlState==='23000';
    }
}