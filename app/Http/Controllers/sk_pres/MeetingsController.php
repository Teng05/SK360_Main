<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_pres/MeetingsController.php.

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use TaylanUnutmaz\AgoraTokenBuilder\RtcTokenBuilder;

class MeetingsController extends Controller
{
    public function index(): View
    {
        [$fullName,$menuItems]=$this->pageContext();

        $currentTermId=$this->currentTermId();

        $this->completeElapsedMeetings(
            $currentTermId
        );

        $meetings=$currentTermId
            ? Meeting::query()
                ->where('term_id',$currentTermId)
                ->orderByDesc('meeting_date')
                ->orderByDesc('meeting_time')
                ->get()
                ->map(
                    fn(Meeting $meeting)=>
                        $this->decorateMeeting($meeting)
                )
            : collect();

        $upcomingMeetings=$meetings
            ->filter(
                fn(Meeting $meeting)=>
                    in_array(
                        $meeting->status,
                        ['scheduled'],
                        true
                    )
                    &&
                    $meeting->scheduled_at->isFuture()
            )
            ->sortBy(
                fn(Meeting $meeting)=>
                    $meeting->scheduled_at->timestamp
            )
            ->values();

        $activeMeetings=$meetings
            ->filter(
                fn(Meeting $meeting)=>
                    $meeting->status==='scheduled'
                    &&
                    $meeting->scheduled_at->isPast()
                    &&
                    $meeting->ends_at->isFuture()
            )
            ->sortBy(
                fn(Meeting $meeting)=>
                    $meeting->scheduled_at->timestamp
            )
            ->values();

        $pastMeetings=$meetings
            ->filter(
                fn(Meeting $meeting)=>
                    $meeting->status!=='scheduled'
            )
            ->sortByDesc(
                fn(Meeting $meeting)=>
                    $meeting->scheduled_at->timestamp
            )
            ->values();

        return view('sk_pres.meetings',[
            'fullName'=>$fullName,
            'menuItems'=>$menuItems,
            'currentUrl'=>route('sk_pres.meetings'),
            'upcomingMeetings'=>$upcomingMeetings,
            'activeMeetings'=>$activeMeetings,
            'pastMeetings'=>$pastMeetings,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(
            auth()->check()
            &&
            auth()->user()->role==='sk_president',
            403
        );

        $currentTermId=$this->currentTermId();

        if(!$currentTermId){
            return back()
                ->withInput()
                ->with(
                    'warning',
                    'There is no active administration term. Start a new administration term first.'
                );
        }

        $validated=$request->validate([
            'title'=>[
                'required',
                'string',
                'max:255',
            ],
            'agenda'=>[
                'nullable',
                'string',
            ],
            'meeting_date'=>[
                'required',
                'date',
            ],
            'meeting_time'=>[
                'required',
                'date_format:H:i',
            ],
        ]);

        $meeting=new Meeting();

        $meeting->term_id=$currentTermId;
        $meeting->title=$validated['title'];
        $meeting->agenda=$validated['agenda'] ?? null;
        $meeting->meeting_date=$validated['meeting_date'];
        $meeting->meeting_time=$validated['meeting_time'].':00';
        $meeting->location_or_link=null;
        $meeting->dyte_meeting_id=null;
        $meeting->created_by=auth()->user()->user_id;
        $meeting->status='scheduled';
        $meeting->created_at=now();
        $meeting->updated_at=now();

        $meeting->save();

        return redirect()
            ->route('sk_pres.meetings')
            ->with(
                'status',
                'Meeting scheduled successfully.'
            );
    }

    public function call(Meeting $meeting): View
    {
        [$fullName,$menuItems]=$this->pageContext();

        $this->ensureCurrentTermMeeting(
            $meeting
        );

        return view('sk_pres.video-call',[
            'fullName'=>$fullName,
            'menuItems'=>$menuItems,
            'currentUrl'=>route('sk_pres.meetings'),
            'meeting'=>$this->decorateMeeting($meeting),
            'channelName'=>$this->channelName($meeting),
            'participantNames'=>$this->participantNames(),
        ]);
    }

    public function token(
        Request $request,
        Meeting $meeting
    ): JsonResponse {
        abort_unless(
            auth()->check()
            &&
            auth()->user()->role==='sk_president',
            403
        );

        $this->ensureCurrentTermMeeting(
            $meeting
        );

        $appId=config(
            'services.agora.app_id'
        );

        $appCertificate=config(
            'services.agora.app_certificate'
        );

        if(
            !filled($appId)
            ||
            !filled($appCertificate)
        ){
            return response()->json([
                'message'=>
                    'Agora is not configured. Set AGORA_APP_ID and AGORA_APP_CERTIFICATE in .env.',
            ],500);
        }

        $uid=(int)(
            auth()->user()->user_id
            ?? 0
        );

        if($uid<=0){
            $uid=random_int(
                1000,
                999999
            );
        }

        try{
            $expireAt=now()
                ->addHours(4)
                ->timestamp;

            $token=
                RtcTokenBuilder::buildTokenWithUid(
                    $appId,
                    $appCertificate,
                    $this->channelName($meeting),
                    $uid,
                    RtcTokenBuilder::RolePublisher,
                    $expireAt
                );

        }catch(\Throwable $e){
            report($e);

            return response()->json([
                'message'=>
                    'Failed to generate Agora RTC token.',
            ],500);
        }

        return response()->json([
            'appId'=>$appId,
            'token'=>$token,
            'channel'=>$this->channelName($meeting),
            'uid'=>$uid,
            'name'=>$this->currentUserName(),
            'title'=>$meeting->title,
        ]);
    }

    protected function pageContext(): array
    {
        abort_unless(
            auth()->check()
            &&
            auth()->user()->role==='sk_president',
            403
        );

        $user=auth()->user();

        $fullName=trim(
            ($user->first_name ?? '').
            ' '.
            ($user->last_name ?? '')
        ) ?: 'User';

        $menuItems=[
            ['link'=>route('sk_pres.home'),'icon'=>'&#127968;','label'=>'Home'],
            ['link'=>route('sk_pres.dashboard'),'icon'=>'&#128202;','label'=>'Dashboard'],
            ['link'=>route('sk_pres.consolidation'),'icon'=>'&#128193;','label'=>'Consolidation'],
            ['link'=>route('sk_pres.module'),'icon'=>'&#9881;&#65039;','label'=>'Module Management'],
            ['link'=>route('sk_pres.announcements'),'icon'=>'&#128226;','label'=>'Announcements'],
            ['link'=>route('sk_pres.calendar'),'icon'=>'&#128197;','label'=>'Calendar'],
            ['link'=>route('sk_pres.chat'),'icon'=>'&#128172;','label'=>'Chat'],
            ['link'=>route('sk_pres.meetings'),'icon'=>'&#128222;','label'=>'Meetings'],
            ['link'=>route('sk_pres.rankings'),'icon'=>'&#127942;','label'=>'Rankings'],
            ['link'=>route('sk_pres.leadership'),'icon'=>'&#128101;','label'=>'Leadership'],
            ['link'=>route('sk_pres.archive'),'icon'=>'&#128450;&#65039;','label'=>'Archive'],
            ['link'=>route('sk_pres.user-management'),'icon'=>'&#128100;','label'=>'User Management'],
        ];

        return [
            $fullName,
            $menuItems,
        ];
    }

    protected function currentUserName(): string
    {
        $user=auth()->user();

        return trim(
            ($user->first_name ?? '').
            ' '.
            ($user->last_name ?? '')
        ) ?: 'User';
    }

    protected function participantNames(): array
    {
        return User::query()
            ->whereIn('role',[
                'sk_president',
                'sk_chairman',
                'sk_secretary',
            ])
            ->where(function($query){
                $query->whereNull('status')
                    ->orWhere('status','active');
            })
            ->where(function($query){
                $query->whereNull('archived_at');
            })
            ->get([
                'user_id',
                'first_name',
                'last_name',
                'email',
            ])
            ->mapWithKeys(function(User $user){
                $name=trim(
                    ($user->first_name ?? '').
                    ' '.
                    ($user->last_name ?? '')
                ) ?: (
                    $user->email
                    ?: 'User'
                );

                return [
                    (string)$user->user_id=>$name,
                ];
            })
            ->all();
    }

    protected function decorateMeeting(Meeting $meeting): Meeting
    {
        $scheduledAt=$meeting->scheduled_at;

        $meeting->scheduled_at=
            $scheduledAt;

        $meeting->ends_at=
            $scheduledAt
                ->copy()
                ->addHour();

        $meeting->display_datetime=
            $scheduledAt->format(
                'Y-m-d h:i A'
            );

        $meeting->preview_datetime=
            $scheduledAt->format(
                'M d, Y h:i A'
            );

        $meeting->is_today=
            $scheduledAt->isToday();

        $meeting->status_label=
            match($meeting->status){
                'completed'=>'Completed',
                'cancelled'=>'Cancelled',

                default=>
                    $meeting->scheduled_at->isFuture()
                        ? 'Upcoming'
                        : 'Ready',
            };

        return $meeting;
    }

    protected function completeElapsedMeetings(?int $termId): void
    {
        if(!$termId){
            return;
        }

        Meeting::query()
            ->where('term_id',$termId)
            ->where('status','scheduled')
            ->get()
            ->each(function(Meeting $meeting){
                $scheduledAt=
                    $meeting->scheduled_at;

                if(
                    $scheduledAt
                        ->copy()
                        ->addHour()
                        ->isPast()
                ){
                    $meeting->status='completed';
                    $meeting->updated_at=now();
                    $meeting->save();
                }
            });
    }

    protected function ensureCurrentTermMeeting(Meeting $meeting): void
    {
        $currentTermId=
            $this->currentTermId();

        abort_unless(
            $currentTermId
            &&
            (int)$meeting->term_id===
                $currentTermId,
            404
        );
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

    protected function channelName(Meeting $meeting): string
    {
        return
            'meeting-'.
            $meeting->meeting_id;
    }
}