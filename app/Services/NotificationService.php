<?php
namespace App\Services;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
class NotificationService
{
    public function notifyAnnouncementCreated(object $announcement,User $actor): void
    {
        $this->createForRoles(
            ['sk_chairman','sk_secretary'],
            $actor,
            [
                'type'=>'announcement',
                'title'=>'New announcement',
                'message'=>$actor->first_name.' posted: '.$announcement->title,
                'announcement_id'=>(int)($announcement->announcement_id ?? 0),
            ]
        );
    }
    public function notifySubmissionSlotCreated(array $slot,User $actor): void
    {
        $roles=$this->submissionRoles((string)($slot['role'] ?? 'Both'));
        $targetType=($slot['submission_type'] ?? '')==='budget_report'
            ? 'budget_slot'
            : 'report_slot';
        $this->createForRoles(
            $roles,
            $actor,
            [
                'type'=>$targetType,
                'title'=>'New submission slot',
                'message'=>($slot['title'] ?? 'Submission').' is now open from '.($slot['start_date'] ?? '').' to '.($slot['end_date'] ?? ''),
                'slot_id'=>(int)($slot['slot_id'] ?? 0),
            ]
        );
    }
    public function notifySubmissionReceived(object $submission,string $sourceType,User $actor): void
    {
        $this->notifyPresidentOfSubmission(
            $submission,
            $sourceType,
            $actor,
            false
        );
    }
    public function notifySubmissionResubmitted(object $submission,string $sourceType,User $actor): void
    {
        $this->notifyPresidentOfSubmission(
            $submission,
            $sourceType,
            $actor,
            true
        );
    }
    public function notifySubmissionNeedsRevision(object $submission,string $sourceType,User $actor,string $remarks): void
    {
        if(empty($submission->user_id)){
            return;
        }
        $recipient=User::query()
            ->where('user_id',(int)$submission->user_id)
            ->whereIn('role',['sk_chairman','sk_secretary'])
            ->where('status','active')
            ->whereNull('archived_at')
            ->first([
                'user_id',
                'role',
            ]);
        if(!$recipient){
            return;
        }
        $isBudget=$sourceType==='budget_report';
        $type=$isBudget
            ? 'budget_revision'
            : 'report_revision';
        $documentLabel=$isBudget
            ? 'Budget submission'
            : 'Accomplishment report';
        $documentTitle=trim(
            (string)($submission->title ?? '')
        );
        $message=$documentTitle!==''
            ? $documentTitle.' needs revision. President remark: '.trim($remarks)
            : $documentLabel.' needs revision. President remark: '.trim($remarks);
        $sourceId=$isBudget
            ? (int)($submission->budget_report_id ?? 0)
            : (int)($submission->report_id ?? 0);
        $this->createForUsers(
            collect([
                $recipient,
            ]),
            $actor,
            [
                'type'=>$type,
                'title'=>'Submission needs revision',
                'message'=>$message,
                'source_type'=>$sourceType,
                'source_id'=>$sourceId,
            ]
        );
    }
    public function notifyEventCreated(object $event,User $actor): void
    {
        $roles=$this->eventRoles(
            (string)($event->visibility ?? 'officials_only')
        );
        $this->createForRoles(
            $roles,
            $actor,
            [
                'type'=>'event',
                'title'=>'New calendar event',
                'message'=>$event->title.' on '.optional($event->start_datetime)->format('M d, Y'),
            ]
        );
    }
    public function notifyMeetingScheduled(object $meeting,User $actor): void
    {
        $termId=(int)($meeting->term_id ?? 0);
        if($termId<=0){
            return;
        }
        $scheduledAt=$this->meetingScheduledAt(
            $meeting
        );
        $this->createForUsers(
            $this->currentMeetingRecipients(
                $termId
            ),
            $actor,
            [
                'type'=>'meeting_scheduled',
                'title'=>'New meeting scheduled',
                'message'=>$meeting->title.' is scheduled for '.$scheduledAt->format('M d, Y h:i A').'.',
                'meeting_id'=>(int)$meeting->meeting_id,
            ]
        );
    }
    public function notifyMeetingUpdated(object $meeting,User $actor): void
    {
        $termId=(int)($meeting->term_id ?? 0);
        if($termId<=0){
            return;
        }
        $scheduledAt=$this->meetingScheduledAt(
            $meeting
        );
        $this->createForUsers(
            $this->currentMeetingRecipients(
                $termId
            ),
            $actor,
            [
                'type'=>'meeting_updated',
                'title'=>'Meeting schedule updated',
                'message'=>$meeting->title.' is now scheduled for '.$scheduledAt->format('M d, Y h:i A').'.',
                'meeting_id'=>(int)$meeting->meeting_id,
            ]
        );
    }
    public function notifyMeetingsStartingSoon(): void
    {
        $termId=$this->currentTermId();
        if(!$termId){
            return;
        }
        $now=Carbon::now(
            'Asia/Manila'
        );
        $windowStart=$now
            ->copy()
            ->subMinute();
        $windowEnd=$now
            ->copy()
            ->addMinutes(5);
        $meetings=DB::table('meetings')
            ->where('term_id',$termId)
            ->where('status','scheduled')
            ->whereDate(
                'meeting_date',
                '>=',
                $windowStart->toDateString()
            )
            ->whereDate(
                'meeting_date',
                '<=',
                $windowEnd->toDateString()
            )
            ->get([
                'meeting_id',
                'term_id',
                'title',
                'meeting_date',
                'meeting_time',
                'created_by',
            ]);
        if($meetings->isEmpty()){
            return;
        }
        $recipients=$this->currentMeetingRecipients(
            $termId
        );
        if($recipients->isEmpty()){
            return;
        }
        foreach($meetings as $meeting){
            $scheduledAt=$this->meetingScheduledAt(
                $meeting
            );
            if(
                $scheduledAt->lt($windowStart)
                ||
                $scheduledAt->gt($windowEnd)
            ){
                continue;
            }
            $actor=User::query()
                ->find(
                    (int)$meeting->created_by
                );
            if(!$actor){
                continue;
            }
            $this->createUniqueForUsers(
                $recipients,
                $actor,
                [
                    'type'=>'meeting_starting',
                    'title'=>'Meeting starting soon',
                    'message'=>$meeting->title.' starts at '.$scheduledAt->format('h:i A').'. Open Meetings to join the conference.',
                    'meeting_id'=>(int)$meeting->meeting_id,
                ]
            );
        }
    }
    public function notifyDeadlinesSoon(): void
    {
        $termId=$this->currentTermId();
        if(!$termId){
            return;
        }
        $actor=$this->systemActor();
        if(!$actor){
            return;
        }
        $now=Carbon::now(
            'Asia/Manila'
        );
        $today=$now
            ->copy()
            ->startOfDay();
        $tomorrow=$today
            ->copy()
            ->addDay();
        $slots=DB::table('submission_slots')
            ->where('term_id',$termId)
            ->where('status','open')
            ->whereDate(
                'end_date',
                '>=',
                $today->toDateString()
            )
            ->whereDate(
                'end_date',
                '<=',
                $tomorrow->toDateString()
            )
            ->get([
                'slot_id',
                'submission_type',
                'title',
                'role',
                'end_date',
            ]);
        foreach($slots as $slot){
            $deadline=Carbon::parse(
                $slot->end_date,
                'Asia/Manila'
            )->startOfDay();
            $isToday=$deadline->isSameDay(
                $today
            );
            $roles=$this->submissionRoles(
                (string)$slot->role
            );
            $recipients=$this->currentTermRecipientsForRoles(
                $termId,
                $roles
            );
            if($recipients->isEmpty()){
                continue;
            }
            $isBudget=$slot->submission_type==='budget_report';
            $this->createUniqueForUsers(
                $recipients,
                $actor,
                [
                    'type'=>$isBudget
                        ? 'budget_deadline'
                        : 'report_deadline',
                    'title'=>$isToday
                        ? 'Submission deadline today'
                        : 'Submission deadline tomorrow',
                    'message'=>$slot->title.' is due '.$deadline->format('M d, Y').'. Submit before the deadline.',
                    'slot_id'=>(int)$slot->slot_id,
                ]
            );
        }
        $calendarDeadlines=DB::table('events')
            ->where('term_id',$termId)
            ->where('event_type','deadline')
            ->where(
                'start_datetime',
                '>=',
                $now
            )
            ->where(
                'start_datetime',
                '<=',
                $tomorrow->copy()->endOfDay()
            )
            ->get([
                'event_id',
                'created_by',
                'title',
                'start_datetime',
                'visibility',
            ]);
        foreach($calendarDeadlines as $event){
            $deadline=Carbon::parse(
                $event->start_datetime,
                'Asia/Manila'
            );
            $isToday=$deadline->isSameDay(
                $today
            );
            $eventActor=User::query()
                ->find(
                    (int)$event->created_by
                ) ?: $actor;
            $roles=$this->eventRoles(
                (string)$event->visibility
            );
            $recipients=$this->currentTermRecipientsForRoles(
                $termId,
                $roles
            );
            if($recipients->isEmpty()){
                continue;
            }
            $this->createUniqueForUsers(
                $recipients,
                $eventActor,
                [
                    'type'=>'calendar_deadline',
                    'title'=>$isToday
                        ? 'Calendar deadline today'
                        : 'Calendar deadline tomorrow',
                    'message'=>$event->title.' is scheduled for '.$deadline->format('M d, Y h:i A').'.',
                    'event_id'=>(int)$event->event_id,
                ]
            );
        }
    }
    protected function notifyPresidentOfSubmission(
        object $submission,
        string $sourceType,
        User $actor,
        bool $isResubmission
    ): void
    {
        $isBudget=$sourceType==='budget_report';
        $type=$isBudget
            ? (
                $isResubmission
                    ? 'budget_resubmission'
                    : 'budget_submission'
            )
            : (
                $isResubmission
                    ? 'report_resubmission'
                    : 'report_submission'
            );
        $documentLabel=$isBudget
            ? 'budget report'
            : 'accomplishment report';
        $documentTitle=trim(
            (string)($submission->title ?? '')
        );
        $sender=$this->submissionSenderLabel(
            $actor
        );
        if($isResubmission){
            $title=$isBudget
                ? 'Budget report resubmitted'
                : 'Accomplishment report resubmitted';
            $message=$documentTitle!==''
                ? $sender.' resubmitted "'.$documentTitle.'" after revision. It is ready for review.'
                : $sender.' resubmitted a corrected '.$documentLabel.' after revision. It is ready for review.';
        }else{
            $title=$isBudget
                ? 'New budget report submitted'
                : 'New accomplishment report submitted';
            $message=$documentTitle!==''
                ? $sender.' submitted "'.$documentTitle.'" for review.'
                : $sender.' submitted a new '.$documentLabel.' for review.';
        }
        $sourceId=$isBudget
            ? (int)($submission->budget_report_id ?? 0)
            : (int)($submission->report_id ?? 0);
        $year=$isBudget
            ? (int)($submission->fiscal_year ?? now()->year)
            : (int)($submission->reporting_year ?? now()->year);
        $this->createForRoles(
            [
                'sk_president',
            ],
            $actor,
            [
                'type'=>$type,
                'title'=>$title,
                'message'=>$message,
                'source_type'=>$sourceType,
                'source_id'=>$sourceId,
                'year'=>$year,
            ]
        );
    }
    protected function submissionSenderLabel(User $actor): string
    {
        $barangayName=trim(
            (string)(
                $actor->barangay
                    ?->barangay_name
                ?? ''
            )
        );
        if($barangayName!==''){
            return str_starts_with(
                strtolower($barangayName),
                'barangay '
            )
                ? $barangayName
                : 'Barangay '.$barangayName;
        }
        $name=trim(
            ($actor->first_name ?? '').
            ' '.
            ($actor->last_name ?? '')
        );
        return $name!==''
            ? $name
            : 'An SK official';
    }
    protected function submissionRoles(string $role): array
    {
        return match($role){
            'SK Chairman'=>[
                'sk_chairman',
            ],
            'SK Secretary'=>[
                'sk_secretary',
            ],
            default=>[
                'sk_chairman',
                'sk_secretary',
            ],
        };
    }
    protected function eventRoles(string $visibility): array
    {
        return match($visibility){
            'chairman_only'=>[
                'sk_chairman',
            ],
            'secretary_only'=>[
                'sk_secretary',
            ],
            default=>[
                'sk_chairman',
                'sk_secretary',
            ],
        };
    }
    protected function currentMeetingRecipients(int $termId): Collection
    {
        return $this->currentTermRecipientsForRoles(
            $termId,
            [
                'sk_chairman',
                'sk_secretary',
            ]
        );
    }
    protected function currentTermRecipientsForRoles(int $termId,array $roles): Collection
    {
        return DB::table('official_terms as ot')
            ->join(
                'users as u',
                'u.user_id',
                '=',
                'ot.user_id'
            )
            ->where(
                'ot.term_id',
                $termId
            )
            ->where(
                'ot.status',
                'current'
            )
            ->whereIn(
                'ot.role',
                $roles
            )
            ->where(
                'u.status',
                'active'
            )
            ->whereNull(
                'u.archived_at'
            )
            ->select(
                'u.user_id',
                'u.role'
            )
            ->distinct()
            ->get();
    }
    protected function systemActor(): ?User
    {
        return User::query()
            ->where(
                'role',
                'sk_president'
            )
            ->where(
                'status',
                'active'
            )
            ->whereNull(
                'archived_at'
            )
            ->orderBy(
                'user_id'
            )
            ->first();
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
    protected function meetingScheduledAt(object $meeting): Carbon
    {
        return Carbon::parse(
            (string)$meeting->meeting_date.
            ' '.
            (string)$meeting->meeting_time,
            'Asia/Manila'
        );
    }
    protected function createForRoles(array $roles,User $actor,array $payload): void
    {
        $recipients=User::query()
            ->whereIn(
                'role',
                $roles
            )
            ->where(
                'status',
                'active'
            )
            ->whereNull(
                'archived_at'
            )
            ->where(
                'user_id',
                '!=',
                $actor->user_id
            )
            ->get([
                'user_id',
                'role',
            ]);
        $this->createForUsers(
            $recipients,
            $actor,
            $payload
        );
    }
    protected function createForUsers(Collection $recipients,User $actor,array $payload): void
    {
        $rows=[];
        $now=now();
        foreach($recipients as $recipient){
            $rows[]=[
                'user_id'=>$recipient->user_id,
                'actor_id'=>$actor->user_id,
                'type'=>$payload['type'],
                'title'=>$payload['title'],
                'message'=>$payload['message'],
                'url'=>$this->resolveUrlForRole(
                    $recipient->role,
                    $payload
                ),
                'is_read'=>0,
                'created_at'=>$now,
                'read_at'=>null,
            ];
        }
        if($rows!==[]){
            Notification::insert(
                $rows
            );
        }
    }
    protected function createUniqueForUsers(Collection $recipients,User $actor,array $payload): void
    {
        $rows=[];
        $now=now();
        foreach($recipients as $recipient){
            $url=$this->resolveUrlForRole(
                $recipient->role,
                $payload
            );
            $exists=Notification::query()
                ->where(
                    'user_id',
                    $recipient->user_id
                )
                ->where(
                    'type',
                    $payload['type']
                )
                ->where(
                    'message',
                    $payload['message']
                )
                ->where(
                    'url',
                    $url
                )
                ->exists();
            if($exists){
                continue;
            }
            $rows[]=[
                'user_id'=>$recipient->user_id,
                'actor_id'=>$actor->user_id,
                'type'=>$payload['type'],
                'title'=>$payload['title'],
                'message'=>$payload['message'],
                'url'=>$url,
                'is_read'=>0,
                'created_at'=>$now,
                'read_at'=>null,
            ];
        }
        if($rows!==[]){
            Notification::insert(
                $rows
            );
        }
    }
    protected function resolveUrlForRole(string $role,array $payload): string
    {
        $type=$payload['type'];
        return match($role){
            'sk_president'=>match($type){
                'budget_submission',
                'report_submission',
                'budget_resubmission',
                'report_resubmission'=>route(
                    'sk_pres.consolidation',
                    [
                        'year'=>(int)(
                            $payload['year']
                            ?? now()->year
                        ),
                        'period'=>'all',
                        'focus_type'=>$payload[
                            'source_type'
                        ] ?? '',
                        'focus_id'=>(int)(
                            $payload[
                                'source_id'
                            ] ?? 0
                        ),
                    ]
                ),
                default=>route(
                    'sk_pres.home'
                ),
            },
            'sk_chairman'=>match($type){
                'announcement'=>route(
                    'sk_chairman.announcements',
                    [
                        'focus_id'=>(int)(
                            $payload[
                                'announcement_id'
                            ] ?? 0
                        ),
                    ]
                ),
                'event',
                'calendar_deadline'=>route(
                    'sk_chairman.calendar'
                ),
                'budget_slot',
                'budget_deadline'=>route(
                    'sk_chairman.budget',
                    [
                        'focus_slot'=>(int)(
                            $payload[
                                'slot_id'
                            ] ?? 0
                        ),
                    ]
                ),
                'report_slot',
                'report_deadline'=>route(
                    'sk_chairman.reports',
                    [
                        'focus_slot'=>(int)(
                            $payload[
                                'slot_id'
                            ] ?? 0
                        ),
                    ]
                ),
                'budget_revision'=>route(
                    'sk_chairman.budget',
                    [
                        'focus_id'=>(int)(
                            $payload[
                                'source_id'
                            ] ?? 0
                        ),
                    ]
                ),
                'report_revision'=>route(
                    'sk_chairman.reports',
                    [
                        'focus_id'=>(int)(
                            $payload[
                                'source_id'
                            ] ?? 0
                        ),
                    ]
                ),
                'meeting_scheduled',
                'meeting_updated',
                'meeting_starting'=>route(
                    'sk_chairman.meetings',
                    [
                        'focus_meeting'=>(int)(
                            $payload[
                                'meeting_id'
                            ] ?? 0
                        ),
                    ]
                ),
                default=>route(
                    'sk_chairman.home'
                ),
            },
            'sk_secretary'=>match($type){
                'announcement'=>route(
                    'sk_secretary.announcements',
                    [
                        'focus_id'=>(int)(
                            $payload[
                                'announcement_id'
                            ] ?? 0
                        ),
                    ]
                ),
                'event',
                'calendar_deadline'=>route(
                    'sk_secretary.calendar'
                ),
                'budget_slot',
                'budget_deadline'=>route(
                    'sk_secretary.budget',
                    [
                        'focus_slot'=>(int)(
                            $payload[
                                'slot_id'
                            ] ?? 0
                        ),
                    ]
                ),
                'report_slot',
                'report_deadline'=>route(
                    'sk_secretary.reports',
                    [
                        'focus_slot'=>(int)(
                            $payload[
                                'slot_id'
                            ] ?? 0
                        ),
                    ]
                ),
                'budget_revision'=>route(
                    'sk_secretary.budget',
                    [
                        'focus_id'=>(int)(
                            $payload[
                                'source_id'
                            ] ?? 0
                        ),
                    ]
                ),
                'report_revision'=>route(
                    'sk_secretary.reports',
                    [
                        'focus_id'=>(int)(
                            $payload[
                                'source_id'
                            ] ?? 0
                        ),
                    ]
                ),
                'meeting_scheduled',
                'meeting_updated',
                'meeting_starting'=>route(
                    'sk_secretary.meetings',
                    [
                        'focus_meeting'=>(int)(
                            $payload[
                                'meeting_id'
                            ] ?? 0
                        ),
                    ]
                ),
                default=>route(
                    'sk_secretary.home'
                ),
            },
            default=>'/',
        };
    }
}