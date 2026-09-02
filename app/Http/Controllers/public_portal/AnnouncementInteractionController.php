<?php

namespace App\Http\Controllers\public_portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AnnouncementInteractionController extends Controller
{
    public function toggleLike(Request $request,int $announcementId): JsonResponse|RedirectResponse
    {
        $this->ensurePublicAnnouncement($announcementId);

        if(auth()->check()){
            $userId=(int)auth()->user()->user_id;

            $existing=DB::table('wall_post_likes')
                ->where('announcement_id',$announcementId)
                ->where('user_id',$userId)
                ->exists();

            if($existing){
                DB::table('wall_post_likes')
                    ->where('announcement_id',$announcementId)
                    ->where('user_id',$userId)
                    ->delete();

                $liked=false;
            }else{
                DB::table('wall_post_likes')->insert([
                    'announcement_id'=>$announcementId,
                    'user_id'=>$userId,
                    'created_at'=>now(),
                ]);

                $liked=true;
            }
        }else{
            $visitorToken=$this->visitorToken($request);

            $existing=DB::table('public_wall_post_likes')
                ->where('announcement_id',$announcementId)
                ->where('visitor_token',$visitorToken)
                ->exists();

            if($existing){
                DB::table('public_wall_post_likes')
                    ->where('announcement_id',$announcementId)
                    ->where('visitor_token',$visitorToken)
                    ->delete();

                $liked=false;
            }else{
                DB::table('public_wall_post_likes')->insert([
                    'announcement_id'=>$announcementId,
                    'visitor_token'=>$visitorToken,
                    'created_at'=>now(),
                ]);

                $liked=true;
            }
        }

        $likesCount=$this->likesCount($announcementId);

        if($request->expectsJson()){
            return response()->json([
                'liked'=>$liked,
                'likes_count'=>$likesCount,
            ]);
        }

        return back();
    }

    public function trackView(Request $request,int $announcementId): JsonResponse
    {
        $this->ensurePublicAnnouncement($announcementId);

        $visitorToken=$this->visitorToken($request);

        DB::table('announcement_views')->insertOrIgnore([
            'announcement_id'=>$announcementId,
            'visitor_token'=>$visitorToken,
            'viewed_at'=>now(),
        ]);

        return response()->json([
            'views_count'=>DB::table('announcement_views')
                ->where('announcement_id',$announcementId)
                ->count(),
        ]);
    }

    public function feedbackList(int $announcementId): JsonResponse
    {
        $this->ensurePublicAnnouncement($announcementId);

        $total=DB::table('announcement_feedback')
            ->where('announcement_id',$announcementId)
            ->where('status','posted')
            ->count();

        $feedbacks=DB::table('announcement_feedback')
            ->where('announcement_id',$announcementId)
            ->where('status','posted')
            ->select(
                'feedback_id',
                'name',
                'comment',
                'created_at'
            )
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function($feedback){
                return [
                    'feedback_id'=>$feedback->feedback_id,
                    'name'=>$feedback->name,
                    'comment'=>$feedback->comment,
                    'created_at_human'=>Carbon::parse($feedback->created_at)->diffForHumans(),
                ];
            });

        return response()->json([
            'total'=>$total,
            'feedbacks'=>$feedbacks,
        ]);
    }

    public function submitFeedback(Request $request,int $announcementId): JsonResponse
    {
        $this->ensurePublicAnnouncement($announcementId);

        $validated=$request->validate([
            'name'=>['required','string','max:150'],
            'email'=>['required','email','max:191'],
            'comment'=>['required','string','max:1000'],
        ]);

        $validated['name']=trim($validated['name']);
        $validated['email']=strtolower(trim($validated['email']));
        $validated['comment']=trim($validated['comment']);

        $otp=(string)random_int(100000,999999);

        DB::beginTransaction();

        try{
            $feedbackId=DB::table('announcement_feedback')
                ->insertGetId([
                    'announcement_id'=>$announcementId,
                    'name'=>$validated['name'],
                    'email'=>$validated['email'],
                    'comment'=>$validated['comment'],
                    'status'=>'pending',
                    'verified_at'=>null,
                    'created_at'=>now(),
                ],'feedback_id');

            DB::table('feedback_verifications')->insert([
                'feedback_id'=>$feedbackId,
                'email'=>$validated['email'],
                'otp_hash'=>Hash::make($otp),
                'attempts'=>0,
                'expires_at'=>now()->addMinutes(10),
                'created_at'=>now(),
            ]);

            Mail::raw(
                "Your SK360 feedback verification code is: {$otp}\n\n".
                "This code will expire in 10 minutes.\n".
                "Enter this code in the SK360 Public Portal to post your feedback.",
                function($message) use($validated){
                    $message->to(
                        $validated['email'],
                        $validated['name']
                    )->subject(
                        'SK360 Feedback Verification Code'
                    );
                }
            );

            DB::commit();

            return response()->json([
                'message'=>'Verification code sent.',
                'feedback_id'=>$feedbackId,
                'masked_email'=>$this->maskEmail($validated['email']),
                'resend_after'=>60,
            ]);

        }catch(\Throwable $e){
            DB::rollBack();

            Log::error(
                'Public feedback submission error: '.$e->getMessage()
            );

            return response()->json([
                'message'=>'Unable to send the verification code. Please try again.',
            ],500);
        }
    }

    public function verifyFeedback(Request $request,int $feedbackId): JsonResponse
    {
        $validated=$request->validate([
            'otp'=>['required','digits:6'],
        ]);

        $feedback=DB::table('announcement_feedback')
            ->where('feedback_id',$feedbackId)
            ->where('status','pending')
            ->first();

        if(!$feedback){
            return response()->json([
                'message'=>'Feedback request not found or already verified.',
            ],404);
        }

        $verification=DB::table('feedback_verifications')
            ->where('feedback_id',$feedbackId)
            ->first();

        if(!$verification){
            return response()->json([
                'message'=>'Verification code not found. Please submit your feedback again.',
            ],422);
        }

        if(Carbon::parse($verification->expires_at)->isPast()){
            DB::table('feedback_verifications')
                ->where('feedback_id',$feedbackId)
                ->delete();

            return response()->json([
                'message'=>'Verification code has expired.',
            ],422);
        }

        if((int)$verification->attempts>=5){
            return response()->json([
                'message'=>'Too many incorrect attempts. Please request a new code.',
            ],429);
        }

        if(!Hash::check($validated['otp'],$verification->otp_hash)){
            $attempts=(int)$verification->attempts+1;

            DB::table('feedback_verifications')
                ->where('feedback_id',$feedbackId)
                ->update([
                    'attempts'=>$attempts,
                ]);

            return response()->json([
                'message'=>$attempts>=5
                    ? 'Too many incorrect attempts. Please request a new code.'
                    : 'Incorrect verification code.',
                'attempts_remaining'=>max(0,5-$attempts),
            ],422);
        }

        DB::transaction(function() use($feedbackId){
            DB::table('announcement_feedback')
                ->where('feedback_id',$feedbackId)
                ->update([
                    'status'=>'posted',
                    'verified_at'=>now(),
                ]);

            DB::table('feedback_verifications')
                ->where('feedback_id',$feedbackId)
                ->delete();
        });

        return response()->json([
            'message'=>'Your feedback has been verified and posted.',
        ]);
    }

    public function resendFeedback(int $feedbackId): JsonResponse
    {
        $feedback=DB::table('announcement_feedback')
            ->where('feedback_id',$feedbackId)
            ->where('status','pending')
            ->first();

        if(!$feedback){
            return response()->json([
                'message'=>'Feedback request not found or already verified.',
            ],404);
        }

        $verification=DB::table('feedback_verifications')
            ->where('feedback_id',$feedbackId)
            ->first();

        if($verification && $verification->created_at){
            $nextAllowed=Carbon::parse($verification->created_at)
                ->addSeconds(60);

            if(now()->lt($nextAllowed)){
                $retryAfter=(int)ceil(
                    now()->diffInSeconds($nextAllowed)
                );

                return response()->json([
                    'message'=>'Please wait before requesting another verification code.',
                    'retry_after'=>max(1,$retryAfter),
                ],429);
            }
        }

        $otp=(string)random_int(100000,999999);

        DB::beginTransaction();

        try{
            DB::table('feedback_verifications')
                ->updateOrInsert(
                    [
                        'feedback_id'=>$feedbackId,
                    ],
                    [
                        'email'=>$feedback->email,
                        'otp_hash'=>Hash::make($otp),
                        'attempts'=>0,
                        'expires_at'=>now()->addMinutes(10),
                        'created_at'=>now(),
                    ]
                );

            Mail::raw(
                "Your new SK360 feedback verification code is: {$otp}\n\n".
                "This code will expire in 10 minutes.\n".
                "Your previous verification code is no longer valid.",
                function($message) use($feedback){
                    $message->to(
                        $feedback->email,
                        $feedback->name
                    )->subject(
                        'New SK360 Feedback Verification Code'
                    );
                }
            );

            DB::commit();

            return response()->json([
                'message'=>'A new verification code has been sent.',
                'masked_email'=>$this->maskEmail($feedback->email),
                'resend_after'=>60,
            ]);

        }catch(\Throwable $e){
            DB::rollBack();

            Log::error(
                'Public feedback resend error: '.$e->getMessage()
            );

            return response()->json([
                'message'=>'Unable to resend the verification code.',
            ],500);
        }
    }

    protected function ensurePublicAnnouncement(int $announcementId): void
    {
        $exists=DB::table('announcements')
            ->where('announcement_id',$announcementId)
            ->where('visibility','public')
            ->exists();

        abort_unless($exists,404);
    }

    protected function visitorToken(Request $request): string
    {
        $token=(string)$request->cookie(
            'sk360_public_visitor',
            ''
        );

        if($token===''){
            $token=(string)Str::uuid();

            Cookie::queue(cookie(
                'sk360_public_visitor',
                $token,
                60*24*365,
                '/',
                null,
                (bool)config('session.secure',false),
                true,
                false,
                'Lax'
            ));
        }

        return $token;
    }

    protected function likesCount(int $announcementId): int
    {
        $official=DB::table('wall_post_likes')
            ->where('announcement_id',$announcementId)
            ->count();

        $public=DB::table('public_wall_post_likes')
            ->where('announcement_id',$announcementId)
            ->count();

        return $official+$public;
    }

    protected function maskEmail(string $email): string
    {
        [$name,$domain]=array_pad(
            explode('@',$email,2),
            2,
            ''
        );

        $visible=substr(
            $name,
            0,
            min(2,strlen($name))
        );

        return $visible
            .str_repeat(
                '*',
                max(2,strlen($name)-strlen($visible))
            )
            .'@'.$domain;
    }
}