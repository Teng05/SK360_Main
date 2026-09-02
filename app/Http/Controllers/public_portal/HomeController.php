<?php

namespace App\Http\Controllers\public_portal;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $visitorToken=$this->visitorToken();

        $announcementUpdates=DB::table('announcements as a')
            ->leftJoin('users as u','a.user_id','=','u.user_id')
            ->leftJoin('barangays as b','u.barangay_id','=','b.barangay_id')
            ->where('a.visibility','public')
            ->select(
                'a.announcement_id',
                'a.title',
                'a.content',
                'a.created_at',
                'u.role',
                'b.barangay_name',
                DB::raw("CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) as author_name")
            )
            ->orderByDesc('a.created_at')
            ->limit(10)
            ->get()
            ->map(function($post) use($visitorToken){
                $officialLikes=DB::table('wall_post_likes')
                    ->where('announcement_id',$post->announcement_id)
                    ->count();

                $publicLikes=DB::table('public_wall_post_likes')
                    ->where('announcement_id',$post->announcement_id)
                    ->count();

                $post->likes_count=$officialLikes+$publicLikes;

                if(auth()->check()){
                    $post->liked_by_visitor=DB::table('wall_post_likes')
                        ->where('announcement_id',$post->announcement_id)
                        ->where('user_id',auth()->user()->user_id)
                        ->exists();
                }else{
                    $post->liked_by_visitor=DB::table('public_wall_post_likes')
                        ->where('announcement_id',$post->announcement_id)
                        ->where('visitor_token',$visitorToken)
                        ->exists();
                }

                $post->views_count=DB::table('announcement_views')
                    ->where('announcement_id',$post->announcement_id)
                    ->count();

                $post->feedback_count=DB::table('announcement_feedback')
                    ->where('announcement_id',$post->announcement_id)
                    ->where('status','posted')
                    ->count();

                $post->feedbacks=DB::table('announcement_feedback')
                    ->where('announcement_id',$post->announcement_id)
                    ->where('status','posted')
                    ->select('feedback_id','name','comment','created_at')
                    ->orderByDesc('created_at')
                    ->limit(3)
                    ->get();

                $post->author_name=trim((string)$post->author_name) ?: 'SK Federation';

                $post->role_label=match($post->role){
                    'sk_president'=>'SK President',
                    'sk_chairman'=>'SK Chairman',
                    'sk_secretary'=>'SK Secretary',
                    default=>'SK Official',
                };

                $post->feed_type='announcement';
                $post->update_date=$post->created_at;

                return $post;
            });

        $eventUpdates=DB::table('events')
            ->where('visibility','public')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function($event){
                $event->feed_type='event';
                $event->update_date=$event->created_at;

                return $event;
            });

        $latestUpdates=$announcementUpdates
            ->merge($eventUpdates)
            ->sortByDesc(function($item){
                return strtotime((string)$item->update_date);
            })
            ->take(10)
            ->values();

        $upcomingEvents=DB::table('events')
            ->where('visibility','public')
            ->where('end_datetime','>=',now())
            ->orderBy('start_datetime')
            ->limit(5)
            ->get();

        return view('public_portal.home',[
            'latestUpdates'=>$latestUpdates,
            'upcomingEvents'=>$upcomingEvents,
        ]);
    }

    protected function visitorToken(): string
    {
        $token=(string)request()->cookie('sk360_public_visitor','');

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
}