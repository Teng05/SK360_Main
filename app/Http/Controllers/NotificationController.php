<?php
// File guide: Handles route logic and page data for app/Http/Controllers/NotificationController.php.
namespace App\Http\Controllers;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class NotificationController extends Controller
{
    public function feed(Request $request,NotificationService $notifications): JsonResponse
    {
        abort_unless(
            auth()->check(),
            403
        );
        try{
            $notifications->notifyMeetingsStartingSoon();
        }catch(\Throwable $e){
            report($e);
        }
        try{
            $notifications->notifyDeadlinesSoon();
        }catch(\Throwable $e){
            report($e);
        }
        if(
            $request->boolean('only_status')
            &&
            $request->filled('meeting_id')
        ){
            return $this->meetingStatus(
                (int)$request->query(
                    'meeting_id'
                )
            );
        }
        $user=auth()->user();
        $notificationRows=Notification::query()
            ->where(
                'user_id',
                $user->user_id
            )
            ->orderByDesc(
                'created_at'
            )
            ->limit(20)
            ->get()
            ->map(
                function(Notification $notification){
                    return [
                        'id'=>$notification->notification_id,
                        'type'=>$notification->type,
                        'title'=>$notification->title,
                        'message'=>$notification->message,
                        'url'=>$notification->url,
                        'is_read'=>$notification->is_read,
                        'created_at'=>optional(
                            $notification->created_at
                        )->diffForHumans(),
                    ];
                }
            );
        return response()->json([
            'unread_count'=>Notification::query()
                ->where(
                    'user_id',
                    $user->user_id
                )
                ->where(
                    'is_read',
                    0
                )
                ->count(),
            'notifications'=>$notificationRows,
        ]);
    }
    public function markRead(Request $request,Notification $notification): JsonResponse
    {
        abort_unless(
            auth()->check(),
            403
        );
        abort_unless(
            (int)$notification->user_id===
            (int)auth()->user()->user_id,
            403
        );
        if(!$notification->is_read){
            $notification->update([
                'is_read'=>1,
                'read_at'=>now(),
            ]);
        }
        return response()->json([
            'ok'=>true,
        ]);
    }
    protected function meetingStatus(int $meetingId): JsonResponse
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
        if(!$termId){
            return response()->json([
                'message'=>'There is no active administration term.',
            ],404);
        }
        $meeting=DB::table(
            'meetings'
        )
            ->where(
                'meeting_id',
                $meetingId
            )
            ->where(
                'term_id',
                (int)$termId
            )
            ->first([
                'meeting_id',
                'status',
                'ended_at',
                'updated_at',
            ]);
        if(!$meeting){
            return response()->json([
                'message'=>'Meeting not found in the current administration.',
            ],404);
        }
        return response()->json([
            'meeting_id'=>(int)$meeting->meeting_id,
            'status'=>$meeting->status,
            'ended_at'=>$meeting->ended_at,
            'updated_at'=>$meeting->updated_at,
            'participants'=>$this->participantProfiles(),
        ]);
    }
    protected function participantProfiles(): array
    {
        return User::query()
            ->whereIn(
                'role',
                [
                    'sk_president',
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
            ->get([
                'user_id',
                'first_name',
                'last_name',
                'email',
                'profile_pic',
            ])
            ->mapWithKeys(
                function(User $user){
                    $name=trim(
                        ($user->first_name ?? '').
                        ' '.
                        ($user->last_name ?? '')
                    ) ?: (
                        $user->email
                        ?: 'User'
                    );
                    $parts=preg_split(
                        '/\s+/',
                        trim($name)
                    ) ?: [];
                    $initials=collect(
                        $parts
                    )
                        ->filter()
                        ->take(2)
                        ->map(
                            fn($part)=>strtoupper(
                                substr(
                                    $part,
                                    0,
                                    1
                                )
                            )
                        )
                        ->implode('');
                    $profilePic=$user->profile_pic
                        ?? null;
                    if(
                        $profilePic
                        &&
                        !str_starts_with(
                            $profilePic,
                            'http://'
                        )
                        &&
                        !str_starts_with(
                            $profilePic,
                            'https://'
                        )
                    ){
                        $profilePic=url(
                            ltrim(
                                $profilePic,
                                '/'
                            )
                        );
                    }
                    return [
                        (string)$user->user_id=>[
                            'name'=>$name,
                            'initials'=>$initials ?: 'SK',
                            'profile_pic'=>$profilePic,
                        ],
                    ];
                }
            )
            ->all();
    }
}