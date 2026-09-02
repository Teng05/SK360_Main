<?php

namespace App\Http\Controllers\public_portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $visitorToken=$this->visitorToken();

        $search=trim((string)$request->query('q',''));
        $sort=$request->query('sort','latest');

        if(!in_array($sort,['latest','oldest'],true)){
            $sort='latest';
        }

        $query=DB::table('announcements as a')
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
            );

        if($search!==''){
            $query->where(function($q) use($search){
                $q->where('a.title','like','%'.$search.'%')
                    ->orWhere('a.content','like','%'.$search.'%')
                    ->orWhere('u.first_name','like','%'.$search.'%')
                    ->orWhere('u.last_name','like','%'.$search.'%');
            });
        }

        if($sort==='oldest'){
            $query->orderBy('a.created_at');
        }else{
            $query->orderByDesc('a.created_at');
        }

        $announcements=$query
            ->paginate(10)
            ->withQueryString();

        $announcements->getCollection()->transform(function($post) use($visitorToken){
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

            $post->author_name=trim((string)$post->author_name) ?: 'SK Federation';

            $post->role_label=match($post->role){
                'sk_president'=>'SK President',
                'sk_chairman'=>'SK Chairman',
                'sk_secretary'=>'SK Secretary',
                default=>'SK Official',
            };

            return $post;
        });

        return view('public_portal.announcements',[
            'announcements'=>$announcements,
            'search'=>$search,
            'sort'=>$sort,
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