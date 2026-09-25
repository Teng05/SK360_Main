<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_pres/MeetingsController.php.

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\User;
use App\Services\RankingPointsService;
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

        $meetings=$currentTermId
            ? Meeting::query()
                ->where('term_id',$currentTermId)
                ->orderByDesc('meeting_date')
                ->orderByDesc('meeting_time')
                ->get()
                ->map(function(Meeting $meeting) use($currentTermId){
                    return $this->decorateAttendance(
                        $this->decorateMeeting($meeting),
                        $currentTermId
                    );
                })
            : collect();

        $upcomingMeetings=$meetings
            ->filter(
                fn(Meeting $meeting)=>
                    $meeting->status==='scheduled'
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
            'attendanceBarangays'=>$this->attendanceBarangays($currentTermId),
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

        $validated=$this->validateMeeting($request);

        $meeting=new Meeting();
        $meeting->term_id=$currentTermId;
        $meeting->title=$validated['title'];
        $meeting->agenda=$validated['agenda'] ?? null;
        $meeting->meeting_date=$validated['meeting_date'];
        $meeting->meeting_time=$validated['meeting_time'].':00';
        $meeting->location_or_link=$validated['location_or_link'] ?? null;
        $meeting->dyte_meeting_id=null;
        $meeting->created_by=auth()->user()->user_id;
        $meeting->status='scheduled';
        $meeting->ended_at=null;
        $meeting->created_at=now();
        $meeting->updated_at=now();
        $meeting->save();

        return redirect()
            ->route('sk_pres.meetings')
            ->with('status','Meeting scheduled successfully.');
    }

    public function update(Request $request,Meeting $meeting): RedirectResponse
    {
        abort_unless(
            auth()->check()
            &&
            auth()->user()->role==='sk_president',
            403
        );

        $this->ensureCurrentTermMeeting($meeting);

        if($meeting->status!=='scheduled'){
            return back()->with(
                'warning',
                'Only scheduled or ongoing meetings can be updated.'
            );
        }

        $validated=$this->validateMeeting($request);

        $meeting->title=$validated['title'];
        $meeting->agenda=$validated['agenda'] ?? null;
        $meeting->meeting_date=$validated['meeting_date'];
        $meeting->meeting_time=$validated['meeting_time'].':00';
        $meeting->location_or_link=$validated['location_or_link'] ?? null;
        $meeting->updated_at=now();
        $meeting->save();

        return redirect()
            ->route('sk_pres.meetings')
            ->with('status','Meeting updated successfully.');
    }

    protected function validateMeeting(Request $request): array
    {
        return $request->validate([
            'title'=>['required','string','max:255'],
            'agenda'=>['nullable','string'],
            'location_or_link'=>['nullable','string','max:255'],
            'meeting_date'=>['required','date'],
            'meeting_time'=>['required','date_format:H:i'],
        ]);
    }

    public function finish(Meeting $meeting): RedirectResponse
    {
        abort_unless(
            auth()->check()
            &&
            auth()->user()->role==='sk_president',
            403
        );

        $this->ensureCurrentTermMeeting($meeting);

        if($meeting->status==='cancelled'){
            return back()->with('warning','A cancelled meeting cannot be finished.');
        }

        if($meeting->status==='completed'){
            return back()->with('warning','This meeting has already been finished.');
        }

        $scheduledAt=$meeting->scheduled_at;

        if($scheduledAt->isFuture()){
            return back()->with('warning','The meeting cannot be finished before its scheduled start time.');
        }

        DB::table('meetings')
            ->where('meeting_id',$meeting->meeting_id)
            ->update([
                'status'=>'completed',
                'ended_at'=>now(),
                'updated_at'=>now(),
            ]);

        return redirect()
            ->route('sk_pres.meetings')
            ->with('status','Meeting finished successfully. Attendance can now be recorded.');
    }

    public function joinCallAttendance(Meeting $meeting): JsonResponse
    {
        abort_unless(
            auth()->check()
            &&
            in_array(
                auth()->user()->role,
                ['sk_president','sk_chairman','sk_secretary'],
                true
            ),
            403
        );

        $this->ensureCurrentTermMeeting($meeting);
        $meeting=$this->decorateMeeting($meeting);
        $callError=$this->callUnavailableMessage($meeting);

        if($callError){
            return response()->json([
                'message'=>$callError,
            ],422);
        }

        $user=auth()->user();

        if(!$user->barangay_id){
            return response()->json([
                'recorded'=>false,
                'message'=>'Video call joined successfully.',
            ]);
        }

        $existing=DB::table('meeting_call_attendance')
            ->where('meeting_id',$meeting->meeting_id)
            ->where('user_id',$user->user_id)
            ->first();

        if($existing){
            DB::table('meeting_call_attendance')
                ->where('attendance_id',$existing->attendance_id)
                ->update([
                    'left_at'=>null,
                    'updated_at'=>now(),
                ]);
        }else{
            DB::table('meeting_call_attendance')->insert([
                'meeting_id'=>$meeting->meeting_id,
                'term_id'=>$meeting->term_id,
                'user_id'=>$user->user_id,
                'barangay_id'=>$user->barangay_id,
                'joined_at'=>now(),
                'left_at'=>null,
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
        }

        return response()->json([
            'recorded'=>true,
            'message'=>'Video call attendance recorded.',
        ]);
    }

    public function leaveCallAttendance(Meeting $meeting): JsonResponse
    {
        abort_unless(
            auth()->check()
            &&
            in_array(
                auth()->user()->role,
                ['sk_president','sk_chairman','sk_secretary'],
                true
            ),
            403
        );

        $this->ensureCurrentTermMeeting($meeting);

        DB::table('meeting_call_attendance')
            ->where('meeting_id',$meeting->meeting_id)
            ->where('user_id',auth()->user()->user_id)
            ->update([
                'left_at'=>now(),
                'updated_at'=>now(),
            ]);

        return response()->json([
            'message'=>'Video call exit recorded.',
        ]);
    }

    public function recordAttendance(Request $request,Meeting $meeting,RankingPointsService $points): RedirectResponse
    {
        abort_unless(
            auth()->check()
            &&
            auth()->user()->role==='sk_president',
            403
        );

        $this->ensureCurrentTermMeeting($meeting);

        if($meeting->status==='cancelled'){
            return back()->with('warning','Attendance cannot be recorded for a cancelled meeting.');
        }

        if($meeting->status!=='completed'){
            return back()->with('warning','Finish the meeting first before recording attendance.');
        }

        $validated=$request->validate([
            'present_barangays'=>['nullable','array'],
            'present_barangays.*'=>['integer','distinct'],
        ]);

        $termId=(int)$meeting->term_id;

        $eligibleIds=$this->attendanceBarangays($termId)
            ->pluck('barangay_id')
            ->map(fn($id)=>(int)$id)
            ->values();

        if($eligibleIds->isEmpty()){
            return back()->with('warning','No current barangay officials are available for attendance recording.');
        }

        $faceToFaceIds=collect($validated['present_barangays'] ?? [])
            ->map(fn($id)=>(int)$id)
            ->unique()
            ->values();

        if($faceToFaceIds->diff($eligibleIds)->isNotEmpty()){
            return back()->with('warning','One or more selected barangays are not part of the current administration.');
        }

        $videoCallIds=DB::table('meeting_call_attendance')
            ->where('meeting_id',$meeting->meeting_id)
            ->where('term_id',$termId)
            ->pluck('barangay_id')
            ->map(fn($id)=>(int)$id)
            ->filter(fn($id)=>$eligibleIds->contains($id))
            ->unique()
            ->values();

        $presentIds=$faceToFaceIds
            ->merge($videoCallIds)
            ->unique()
            ->values();

        $attendanceExists=DB::table('ranking_point_logs')
            ->where('term_id',$termId)
            ->where('source_type','meeting')
            ->where('source_id',(string)$meeting->meeting_id)
            ->whereIn('action',[
                RankingPointsService::MEETING_ATTENDANCE,
                RankingPointsService::MISSED_MEETING,
            ])
            ->exists();

        if($attendanceExists){
            return back()->with('warning','Attendance for this meeting has already been recorded.');
        }

        $period=Carbon::parse($meeting->meeting_date)->format('F Y');

        DB::transaction(function() use($eligibleIds,$presentIds,$meeting,$points,$period){
            foreach($eligibleIds as $barangayId){
                $points->award(
                    (int)$barangayId,
                    $presentIds->contains($barangayId)
                        ? RankingPointsService::MEETING_ATTENDANCE
                        : RankingPointsService::MISSED_MEETING,
                    'meeting',
                    (int)$meeting->meeting_id,
                    null,
                    $period
                );
            }
        });

        return redirect()
            ->route('sk_pres.meetings')
            ->with('status','Meeting attendance recorded successfully.');
    }

    public function call(Meeting $meeting): View|RedirectResponse
    {
        [$fullName,$menuItems]=$this->pageContext();

        $this->ensureCurrentTermMeeting($meeting);

        $meeting=$this->decorateMeeting($meeting);
        $callError=$this->callUnavailableMessage($meeting);

        if($callError){
            return redirect()
                ->route('sk_pres.meetings')
                ->with('warning',$callError);
        }

        return view('sk_pres.video-call',[
            'fullName'=>$fullName,
            'menuItems'=>$menuItems,
            'currentUrl'=>route('sk_pres.meetings'),
            'meeting'=>$meeting,
            'channelName'=>$this->channelName($meeting),
            'participantNames'=>$this->participantNames(),
        ]);
    }

    public function token(Request $request,Meeting $meeting): JsonResponse
    {
        abort_unless(
            auth()->check()
            &&
            auth()->user()->role==='sk_president',
            403
        );

        $this->ensureCurrentTermMeeting($meeting);

        $meeting=$this->decorateMeeting($meeting);
        $callError=$this->callUnavailableMessage($meeting);

        if($callError){
            return response()->json([
                'message'=>$callError,
            ],422);
        }

        $appId=config('services.agora.app_id');
        $appCertificate=config('services.agora.app_certificate');

        if(!filled($appId) || !filled($appCertificate)){
            return response()->json([
                'message'=>'Agora is not configured. Set AGORA_APP_ID and AGORA_APP_CERTIFICATE in .env.',
            ],500);
        }

        $uid=(int)(auth()->user()->user_id ?? 0);

        if($uid<=0){
            $uid=random_int(1000,999999);
        }

        try{
            $expireAt=now()->addHours(4)->timestamp;

            $token=RtcTokenBuilder::buildTokenWithUid(
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
                'message'=>'Failed to generate Agora RTC token.',
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
        $fullName=trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';

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

        return [$fullName,$menuItems];
    }

    protected function currentUserName(): string
    {
        $user=auth()->user();
        return trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';
    }

    protected function participantNames(): array
    {
        return User::query()
            ->whereIn('role',['sk_president','sk_chairman','sk_secretary'])
            ->where(function($query){
                $query->whereNull('status')->orWhere('status','active');
            })
            ->where(function($query){
                $query->whereNull('archived_at');
            })
            ->get(['user_id','first_name','last_name','email'])
            ->mapWithKeys(function(User $user){
                $name=trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->email ?: 'User');
                return [(string)$user->user_id=>$name];
            })
            ->all();
    }

    protected function attendanceBarangays(?int $termId)
    {
        if(!$termId){
            return collect();
        }

        return DB::table('official_terms as ot')
            ->join('barangays as b','b.barangay_id','=','ot.barangay_id')
            ->where('ot.term_id',$termId)
            ->whereIn('ot.role',['sk_chairman','sk_secretary'])
            ->where('ot.status','current')
            ->whereNotNull('ot.barangay_id')
            ->select('b.barangay_id','b.barangay_name')
            ->distinct()
            ->orderBy('b.barangay_name')
            ->get();
    }

    protected function decorateAttendance(Meeting $meeting,int $termId): Meeting
    {
        $eligibleIds=$this->attendanceBarangays($termId)
            ->pluck('barangay_id')
            ->map(fn($id)=>(int)$id)
            ->values();

        $logs=DB::table('ranking_point_logs')
            ->where('term_id',$termId)
            ->where('source_type','meeting')
            ->where('source_id',(string)$meeting->meeting_id)
            ->whereIn('action',[
                RankingPointsService::MEETING_ATTENDANCE,
                RankingPointsService::MISSED_MEETING,
            ])
            ->get(['barangay_id','action']);

        $presentIds=$logs
            ->where('action',RankingPointsService::MEETING_ATTENDANCE)
            ->pluck('barangay_id')
            ->map(fn($id)=>(int)$id)
            ->unique()
            ->values();

        $absentIds=$logs
            ->where('action',RankingPointsService::MISSED_MEETING)
            ->pluck('barangay_id')
            ->map(fn($id)=>(int)$id)
            ->unique()
            ->values();

        $recordedIds=$presentIds->merge($absentIds)->unique()->values();

        $meeting->attendance_present_count=$presentIds->count();
        $meeting->attendance_absent_count=$absentIds->count();
        $meeting->attendance_recorded_count=$recordedIds->count();
        $meeting->attendance_total_barangays=$eligibleIds->count();
        $meeting->attendance_finalized=$eligibleIds->isNotEmpty() && $eligibleIds->diff($recordedIds)->isEmpty();
        $meeting->attendance_present_barangay_ids=$presentIds->all();

        $meeting->syncOriginalAttributes([
            'attendance_present_count',
            'attendance_absent_count',
            'attendance_recorded_count',
            'attendance_total_barangays',
            'attendance_finalized',
            'attendance_present_barangay_ids',
        ]);

        return $meeting;
    }

    protected function decorateMeeting(Meeting $meeting): Meeting
    {
        $scheduledAt=$meeting->scheduled_at;

        $meeting->display_datetime=$scheduledAt->format('Y-m-d h:i A');
        $meeting->preview_datetime=$scheduledAt->format('M d, Y h:i A');
        $meeting->is_today=$scheduledAt->isToday();

        $meeting->status_label=match($meeting->status){
            'completed'=>'Completed',
            'cancelled'=>'Cancelled',
            default=>$scheduledAt->isFuture() ? 'Upcoming' : 'Ongoing',
        };

        $meeting->can_finish=$meeting->status==='scheduled' && $scheduledAt->isPast();
        $meeting->can_record_attendance=$meeting->status==='completed';

        $meeting->syncOriginalAttributes([
            'display_datetime',
            'preview_datetime',
            'is_today',
            'status_label',
            'can_finish',
            'can_record_attendance',
        ]);

        return $meeting;
    }

    protected function callUnavailableMessage(Meeting $meeting): ?string
    {
        if($meeting->status==='cancelled'){
            return 'This meeting has been cancelled.';
        }

        if($meeting->status==='completed'){
            return 'This meeting has already ended.';
        }

        if($meeting->status!=='scheduled'){
            return 'This meeting is not available.';
        }

        if($meeting->scheduled_at->isFuture()){
            return 'The meeting has not started yet.';
        }

        return null;
    }

    protected function ensureCurrentTermMeeting(Meeting $meeting): void
    {
        $currentTermId=$this->currentTermId();

        abort_unless(
            $currentTermId
            &&
            (int)$meeting->term_id===$currentTermId,
            404
        );
    }

    protected function currentTermId(): ?int
    {
        $termId=DB::table('administration_terms')
            ->where('status','current')
            ->orderByDesc('term_id')
            ->value('term_id');

        return $termId ? (int)$termId : null;
    }

    protected function channelName(Meeting $meeting): string
    {
        return 'meeting-'.$meeting->meeting_id;
    }
}
