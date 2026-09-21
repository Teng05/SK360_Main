<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /*
    |--------------------------------------------------------------------------
    | ANNOUNCEMENT CREATED
    |--------------------------------------------------------------------------
    */

    public function notifyAnnouncementCreated(
        object $announcement,
        User $actor
    ): void
    {
        $roles=[
            'sk_chairman',
            'sk_secretary',
        ];

        $this->createForRoles(
            $roles,
            $actor,
            [
                'type'=>'announcement',
                'title'=>'New announcement',
                'message'=>
                    $actor->first_name.
                    ' posted: '.
                    $announcement->title,
                'announcement_id'=>
                    (int)($announcement->announcement_id ?? 0),
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SUBMISSION SLOT CREATED
    |--------------------------------------------------------------------------
    */

    public function notifySubmissionSlotCreated(
        array $slot,
        User $actor
    ): void
    {
        $roles=match($slot['role']){
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

        $targetType=
            $slot['submission_type']===
            'budget_report'
                ? 'budget_slot'
                : 'report_slot';

        $this->createForRoles(
            $roles,
            $actor,
            [
                'type'=>$targetType,
                'title'=>'New submission slot',
                'message'=>
                    $slot['title'].
                    ' is now open from '.
                    $slot['start_date'].
                    ' to '.
                    $slot['end_date'],
                'slot_id'=>(int)($slot['slot_id'] ?? 0),
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | NEW SUBMISSION RECEIVED BY PRESIDENT
    |--------------------------------------------------------------------------
    */

    public function notifySubmissionReceived(
        object $submission,
        string $sourceType,
        User $actor
    ): void
    {
        $this->notifyPresidentOfSubmission(
            $submission,
            $sourceType,
            $actor,
            false
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CORRECTED SUBMISSION RECEIVED BY PRESIDENT
    |--------------------------------------------------------------------------
    */

    public function notifySubmissionResubmitted(
        object $submission,
        string $sourceType,
        User $actor
    ): void
    {
        $this->notifyPresidentOfSubmission(
            $submission,
            $sourceType,
            $actor,
            true
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SUBMISSION NEEDS REVISION
    |--------------------------------------------------------------------------
    */

    public function notifySubmissionNeedsRevision(
        object $submission,
        string $sourceType,
        User $actor,
        string $remarks
    ): void
    {
        if(empty($submission->user_id)){
            return;
        }

        $recipient=User::query()
            ->where(
                'user_id',
                (int)$submission->user_id
            )
            ->whereIn(
                'role',
                [
                    'sk_chairman',
                    'sk_secretary',
                ]
            )
            ->where(
                'status',
                'active'
            )
            ->whereNull(
                'archived_at'
            )
            ->first([
                'user_id',
                'role',
            ]);

        if(!$recipient){
            return;
        }

        $isBudget=
            $sourceType===
            'budget_report';

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

    /*
    |--------------------------------------------------------------------------
    | EVENT CREATED
    |--------------------------------------------------------------------------
    */

    public function notifyEventCreated(
        object $event,
        User $actor
    ): void
    {
        $roles=match($event->visibility){

            'chairman_only'=>[
                'sk_chairman',
            ],

            'secretary_only'=>[
                'sk_secretary',
            ],

            'officials_only'=>[
                'sk_chairman',
                'sk_secretary',
            ],

            default=>[
                'sk_chairman',
                'sk_secretary',
            ],
        };

        $this->createForRoles(
            $roles,
            $actor,
            [
                'type'=>'event',
                'title'=>'New calendar event',
                'message'=>
                    $event->title.
                    ' on '.
                    optional(
                        $event->start_datetime
                    )->format('M d, Y'),
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | NOTIFY PRESIDENT OF SUBMISSION
    |--------------------------------------------------------------------------
    */

    protected function notifyPresidentOfSubmission(
        object $submission,
        string $sourceType,
        User $actor,
        bool $isResubmission
    ): void
    {
        $isBudget=
            $sourceType===
            'budget_report';

        $type=$isBudget
            ? ($isResubmission
                ? 'budget_resubmission'
                : 'budget_submission')
            : ($isResubmission
                ? 'report_resubmission'
                : 'report_submission');

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

    /*
    |--------------------------------------------------------------------------
    | SUBMISSION SENDER LABEL
    |--------------------------------------------------------------------------
    */

    protected function submissionSenderLabel(
        User $actor
    ): string
    {
        $barangayName=trim(
            (string)($actor->barangay?->barangay_name ?? '')
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

    /*
    |--------------------------------------------------------------------------
    | CREATE NOTIFICATIONS BY ROLE
    |--------------------------------------------------------------------------
    */

    protected function createForRoles(
        array $roles,
        User $actor,
        array $payload
    ): void
    {
        $recipients=User::query()
            ->whereIn('role',$roles)
            ->where('status','active')
            ->whereNull('archived_at')
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

    /*
    |--------------------------------------------------------------------------
    | CREATE NOTIFICATIONS
    |--------------------------------------------------------------------------
    */

    protected function createForUsers(
        Collection $recipients,
        User $actor,
        array $payload
    ): void
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
            Notification::insert($rows);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION URL BY OFFICIAL ROLE
    |--------------------------------------------------------------------------
    */

    protected function resolveUrlForRole(
        string $role,
        array $payload
    ): string
    {
        $type=$payload['type'];

        return match($role){

            'sk_president'=>match($type){

                'budget_submission',
                'report_submission',
                'budget_resubmission',
                'report_resubmission'=>
                    route(
                        'sk_pres.consolidation',
                        [
                            'year'=>(int)($payload['year'] ?? now()->year),
                            'period'=>'all',
                            'focus_type'=>$payload['source_type'] ?? '',
                            'focus_id'=>(int)($payload['source_id'] ?? 0),
                        ]
                    ),

                default=>
                    route(
                        'sk_pres.home'
                    ),
            },

            'sk_chairman'=>match($type){

                'announcement'=>
                    route(
                        'sk_chairman.announcements',
                        [
                            'focus_id'=>
                                (int)($payload['announcement_id'] ?? 0),
                        ]
                    ),

                'event'=>
                    route(
                        'sk_chairman.calendar'
                    ),

                'budget_slot'=>
                    route(
                        'sk_chairman.budget',
                        [
                            'focus_slot'=>
                                (int)($payload['slot_id'] ?? 0),
                        ]
                    ),

                'report_slot'=>
                    route(
                        'sk_chairman.reports',
                        [
                            'focus_slot'=>
                                (int)($payload['slot_id'] ?? 0),
                        ]
                    ),

                'budget_revision'=>
                    route(
                        'sk_chairman.budget',
                        [
                            'focus_id'=>
                                (int)($payload['source_id'] ?? 0),
                        ]
                    ),

                'report_revision'=>
                    route(
                        'sk_chairman.reports',
                        [
                            'focus_id'=>
                                (int)($payload['source_id'] ?? 0),
                        ]
                    ),

                default=>
                    route(
                        'sk_chairman.home'
                    ),
            },

            'sk_secretary'=>match($type){

                'announcement'=>
                    route(
                        'sk_secretary.announcements',
                        [
                            'focus_id'=>
                                (int)($payload['announcement_id'] ?? 0),
                        ]
                    ),

                'event'=>
                    route(
                        'sk_secretary.calendar'
                    ),

                'budget_slot'=>
                    route(
                        'sk_secretary.budget',
                        [
                            'focus_slot'=>
                                (int)($payload['slot_id'] ?? 0),
                        ]
                    ),

                'report_slot'=>
                    route(
                        'sk_secretary.reports',
                        [
                            'focus_slot'=>
                                (int)($payload['slot_id'] ?? 0),
                        ]
                    ),

                'budget_revision'=>
                    route(
                        'sk_secretary.budget',
                        [
                            'focus_id'=>
                                (int)($payload['source_id'] ?? 0),
                        ]
                    ),

                'report_revision'=>
                    route(
                        'sk_secretary.reports',
                        [
                            'focus_id'=>
                                (int)($payload['source_id'] ?? 0),
                        ]
                    ),

                default=>
                    route(
                        'sk_secretary.home'
                    ),
            },

            default=>'/',
        };
    }
}