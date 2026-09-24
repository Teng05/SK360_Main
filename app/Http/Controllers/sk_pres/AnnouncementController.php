<?php

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $user=auth()->user();
        $currentTermId=$this->currentTermId();

        $fullName=trim(
            ($user->first_name ?? '').
            ' '.
            ($user->last_name ?? '')
        ) ?: 'User';

        $menuItems=[
            ['link'=>route('sk_pres.home'),'icon'=>'🏠','label'=>'Home'],
            ['link'=>route('sk_pres.dashboard'),'icon'=>'📊','label'=>'Dashboard'],
            ['link'=>route('sk_pres.consolidation'),'icon'=>'📁','label'=>'Consolidation'],
            ['link'=>route('sk_pres.module'),'icon'=>'⚙️','label'=>'Module Management'],
            ['link'=>route('sk_pres.announcements'),'icon'=>'📢','label'=>'Announcements'],
            ['link'=>route('sk_pres.calendar'),'icon'=>'📅','label'=>'Calendar'],
            ['link'=>route('sk_pres.chat'),'icon'=>'💬','label'=>'Chat'],
            ['link'=>route('sk_pres.meetings'),'icon'=>'📞','label'=>'Meetings'],
            ['link'=>route('sk_pres.rankings'),'icon'=>'🏆','label'=>'Rankings'],
            ['link'=>route('sk_pres.leadership'),'icon'=>'👥','label'=>'Leadership'],
            ['link'=>route('sk_pres.archive'),'icon'=>'🗂️','label'=>'Archive'],
            ['link'=>route('sk_pres.user-management'),'icon'=>'👤','label'=>'User Management'],
        ];

        $search=trim(
            (string)$request->query(
                'q',
                ''
            )
        );

        $sort=$request->query(
            'sort',
            'latest'
        );

        if(
            !in_array(
                $sort,
                [
                    'latest',
                    'oldest',
                ],
                true
            )
        ){
            $sort='latest';
        }

        $query=DB::table('announcements as a')
            ->leftJoin(
                'users as u',
                'a.user_id',
                '=',
                'u.user_id'
            )
            ->leftJoin(
                'barangays as b',
                'u.barangay_id',
                '=',
                'b.barangay_id'
            )
            ->whereIn(
                'a.visibility',
                [
                    'public',
                    'officials_only',
                ]
            )
            ->select(
                'a.announcement_id',
                'a.term_id',
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

        if($currentTermId){
            $query->where(
                'a.term_id',
                $currentTermId
            );
        }else{
            $query->whereRaw(
                '1 = 0'
            );
        }

        if($search!==''){
            $query->where(function($q) use($search){
                $q->where(
                    'a.title',
                    'like',
                    '%'.$search.'%'
                )
                    ->orWhere(
                        'a.content',
                        'like',
                        '%'.$search.'%'
                    )
                    ->orWhere(
                        'u.first_name',
                        'like',
                        '%'.$search.'%'
                    )
                    ->orWhere(
                        'u.last_name',
                        'like',
                        '%'.$search.'%'
                    )
                    ->orWhere(
                        'b.barangay_name',
                        'like',
                        '%'.$search.'%'
                    );
            });
        }

        if($sort==='oldest'){
            $query->orderBy(
                'a.created_at'
            );
        }else{
            $query->orderByDesc(
                'a.created_at'
            );
        }

        $announcements=$query
            ->paginate(10)
            ->withQueryString();

        $announcements
            ->getCollection()
            ->transform(function($announcement) use($user){
                $officialLikes=DB::table(
                    'wall_post_likes'
                )
                    ->where(
                        'announcement_id',
                        $announcement->announcement_id
                    )
                    ->count();

                $publicLikes=DB::table(
                    'public_wall_post_likes'
                )
                    ->where(
                        'announcement_id',
                        $announcement->announcement_id
                    )
                    ->count();

                $announcement->likes_count=
                    $officialLikes+
                    $publicLikes;

                $announcement->liked_by_current_user=
                    DB::table(
                        'wall_post_likes'
                    )
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
                    DB::table(
                        'announcement_views'
                    )
                        ->where(
                            'announcement_id',
                            $announcement->announcement_id
                        )
                        ->count();

                $announcement->feedback_count=
                    DB::table(
                        'announcement_feedback'
                    )
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
                        (string)$announcement
                            ->author_name
                    ) ?: 'SK Federation';

                $announcement->role_label=
                    match($announcement->role){
                        'sk_president'=>
                            'SK President',

                        'sk_chairman'=>
                            'SK Chairman',

                        'sk_secretary'=>
                            'SK Secretary',

                        default=>
                            'SK Official',
                    };

                $announcement->visibility_label=
                    $announcement->visibility==='officials_only'
                        ? 'Officials Only'
                        : 'Public';

                return $announcement;
            });

        return view(
            'sk_pres.announcement',
            [
                'fullName'=>$fullName,
                'menuItems'=>$menuItems,
                'currentUrl'=>url()->current(),
                'announcements'=>$announcements,
                'search'=>$search,
                'sort'=>$sort,
            ]
        );
    }

    public function store(
        Request $request,
        NotificationService $notifications
    ): RedirectResponse {
        abort_unless(
            auth()->check()
            &&
            auth()->user()->role==='sk_president',
            403
        );

        $currentTermId=
            $this->currentTermId();

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

            'content'=>[
                'required',
                'string',
            ],

            'visibility'=>[
                'nullable',
                'in:public,officials_only',
            ],
        ]);

        $announcementId=
            DB::table(
                'announcements'
            )->insertGetId(
                [
                    'term_id'=>$currentTermId,
                    'user_id'=>
                        auth()->user()->user_id,

                    'title'=>
                        $validated['title'],

                    'content'=>
                        $validated['content'],

                    'visibility'=>
                        $validated['visibility']
                        ?? 'public',

                    'created_at'=>now(),
                    'updated_at'=>now(),
                ],
                'announcement_id'
            );

        $announcement=(object)[
            'announcement_id'=>
                $announcementId,

            'term_id'=>
                $currentTermId,

            'title'=>
                $validated['title'],

            'visibility'=>
                $validated['visibility']
                ?? 'public',
        ];

        $notifications
            ->notifyAnnouncementCreated(
                $announcement,
                auth()->user()
            );

        return redirect()
            ->route(
                'sk_pres.announcements'
            )
            ->with(
                'status',
                'Announcement created successfully.'
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
}