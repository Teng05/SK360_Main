<?php

namespace App\Http\Controllers\sk_secretary;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_secretary', 403);

        $user=auth()->user();

        $fullName=trim(
            ($user->first_name ?? '').
            ' '.
            ($user->last_name ?? '')
        ) ?: 'User';

        $barangayName=$user->barangay->barangay_name ?? 'Barangay';

        $search=trim((string)$request->query('q',''));
        $sort=$request->query('sort','latest');

        if(!in_array($sort,['latest','oldest'],true)){
            $sort='latest';
        }

        $query=DB::table('announcements as a')
            ->leftJoin('users as u','a.user_id','=','u.user_id')
            ->leftJoin('barangays as b','u.barangay_id','=','b.barangay_id')
            ->whereIn('a.visibility',[
                'public',
                'officials_only',
            ])
            ->select(
                'a.announcement_id',
                'a.user_id',
                'a.title',
                'a.content',
                'a.visibility',
                'a.created_at',
                'u.role',
                'b.barangay_name',
                DB::raw(
                    "CONCAT(
                        COALESCE(u.first_name,''),
                        ' ',
                        COALESCE(u.last_name,'')
                    ) as author_name"
                )
            );

        if($search!==''){
            $query->where(function($q) use($search){
                $q->where('a.title','like','%'.$search.'%')
                    ->orWhere('a.content','like','%'.$search.'%')
                    ->orWhere('u.first_name','like','%'.$search.'%')
                    ->orWhere('u.last_name','like','%'.$search.'%')
                    ->orWhere('b.barangay_name','like','%'.$search.'%');
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

        $announcements->getCollection()->transform(function($announcement) use($user){
            $officialLikes=DB::table('wall_post_likes')
                ->where(
                    'announcement_id',
                    $announcement->announcement_id
                )
                ->count();

            $publicLikes=DB::table('public_wall_post_likes')
                ->where(
                    'announcement_id',
                    $announcement->announcement_id
                )
                ->count();

            $announcement->likes_count=
                $officialLikes+
                $publicLikes;

            $announcement->liked_by_current_user=
                DB::table('wall_post_likes')
                    ->where(
                        'announcement_id',
                        $announcement->announcement_id
                    )
                    ->where(
                        'user_id',
                        $user->user_id
                    )
                    ->exists();

            $announcement->views_count=
                DB::table('announcement_views')
                    ->where(
                        'announcement_id',
                        $announcement->announcement_id
                    )
                    ->count();

            $announcement->feedback_count=
                DB::table('announcement_feedback')
                    ->where(
                        'announcement_id',
                        $announcement->announcement_id
                    )
                    ->where(
                        'status',
                        'posted'
                    )
                    ->count();

            $announcement->author_name=
                trim(
                    (string)$announcement->author_name
                ) ?: 'SK Federation';

            $announcement->role_label=
                match($announcement->role){
                    'sk_president'=>'SK President',
                    'sk_chairman'=>'SK Chairman',
                    'sk_secretary'=>'SK Secretary',
                    default=>'SK Official',
                };

            $announcement->visibility_label=
                $announcement->visibility==='officials_only'
                    ? 'Officials Only'
                    : 'Public';

            return $announcement;
        });

        return view('sk_secretary.announcements',[
            'fullName'=>$fullName,
            'barangayName'=>$barangayName,
            'initials'=>strtoupper(
                substr(
                    $user->first_name ?? 'S',
                    0,
                    1
                ).
                substr(
                    $user->last_name ?? 'K',
                    0,
                    1
                )
            ),
            'menuItems'=>$this->menuItems(),
            'currentUrl'=>url()->current(),
            'announcements'=>$announcements,
            'search'=>$search,
            'sort'=>$sort,
        ]);
    }

    protected function menuItems(): array
    {
        return [
            [
                'link'=>route('sk_secretary.home'),
                'icon'=>'&#127968;',
                'label'=>'Home',
            ],
            [
                'link'=>route('sk_secretary.reports'),
                'icon'=>'&#128196;',
                'label'=>'Reports',
            ],
            [
                'link'=>route('sk_secretary.budget'),
                'icon'=>'&#128229;',
                'label'=>'Budget',
            ],
            [
                'link'=>route('sk_secretary.announcements'),
                'icon'=>'&#128226;',
                'label'=>'Announcements',
            ],
            [
                'link'=>route('sk_secretary.calendar'),
                'icon'=>'&#128197;',
                'label'=>'Calendar',
            ],
            [
                'link'=>route('sk_secretary.chat'),
                'icon'=>'&#128172;',
                'label'=>'Chat',
            ],
            [
                'link'=>route('sk_secretary.meetings'),
                'icon'=>'&#128222;',
                'label'=>'Meetings',
            ],
            [
                'link'=>route('sk_secretary.rankings'),
                'icon'=>'&#127942;',
                'label'=>'Rankings',
            ],
            [
                'link'=>route('sk_secretary.leadership'),
                'icon'=>'&#128101;',
                'label'=>'Leadership',
            ],
        ];
    }
}