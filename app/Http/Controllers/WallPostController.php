<?php

// File guide: Handles route logic and page data for app/Http/Controllers/WallPostController.php.

namespace App\Http\Controllers;

use App\Services\RankingPointsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WallPostController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->check(),403);

        $currentTermId=$this->currentTermId();

        if(!$currentTermId){
            return back()->with('wall_status','There is no active administration term.');
        }

        $validated=$request->validate([
            'post_content'=>['required','string','max:5000'],
            'post_category'=>['nullable','in:update,announcement,event,accomplishment'],
        ]);

        $category=strtolower($validated['post_category'] ?? 'update');

        $title=match($category){
            'announcement'=>'Announcement',
            'event'=>'Event Update',
            'accomplishment'=>'Accomplishment',
            default=>'Community Update',
        };

        $announcementId=DB::table('announcements')->insertGetId([
            'term_id'=>$currentTermId,
            'user_id'=>auth()->user()->user_id,
            'title'=>$title,
            'content'=>$validated['post_content'],
            'visibility'=>'public',
            'created_at'=>now(),
            'updated_at'=>now(),
        ],'announcement_id');

        $user=auth()->user();

        if(!empty($user->barangay_id)){
            app(RankingPointsService::class)->award(
                (int)$user->barangay_id,
                RankingPointsService::COMMUNITY_ENGAGEMENT,
                'wall_post',
                $announcementId,
                (int)$user->user_id
            );
        }

        return back()->with('wall_status','Post published to everyone.');
    }

    public function toggleLike(int $announcementId): RedirectResponse
    {
        abort_unless(auth()->check(),403);

        $currentTermId=$this->currentTermId();

        abort_unless($currentTermId,404);

        $postExists=DB::table('announcements')
            ->where('announcement_id',$announcementId)
            ->where('term_id',$currentTermId)
            ->where('visibility','public')
            ->exists();

        abort_unless($postExists,404);

        $existing=DB::table('wall_post_likes')
            ->where('announcement_id',$announcementId)
            ->where('user_id',auth()->user()->user_id)
            ->first();

        if($existing){
            DB::table('wall_post_likes')
                ->where('announcement_id',$announcementId)
                ->where('user_id',auth()->user()->user_id)
                ->delete();

            return back();
        }

        DB::table('wall_post_likes')->insert([
            'announcement_id'=>$announcementId,
            'user_id'=>auth()->user()->user_id,
            'created_at'=>now(),
        ]);

        return back();
    }

    public function update(Request $request, int $announcementId): RedirectResponse
    {
        abort_unless(auth()->check(), 403);
        $termId = $this->currentTermId();
        $post = DB::table('announcements')->where('announcement_id', $announcementId)->where('term_id', $termId)->first();
        abort_unless($post, 404);
        abort_unless((int) $post->user_id === (int) auth()->id(), 403);
        $validated = $request->validate(['post_content' => ['required', 'string', 'max:5000']]);
        DB::table('announcements')->where('announcement_id', $announcementId)->update([
            'content' => $validated['post_content'], 'updated_at' => now(),
        ]);
        return back()->with('wall_status', 'Post updated successfully.');
    }

    public function destroy(int $announcementId): RedirectResponse
    {
        abort_unless(auth()->check(), 403);
        $termId = $this->currentTermId();
        DB::transaction(function () use ($announcementId, $termId) {
            $post = DB::table('announcements')->where('announcement_id', $announcementId)->where('term_id', $termId)->lockForUpdate()->first();
            abort_unless($post, 404);
            abort_unless((int) $post->user_id === (int) auth()->id(), 403);
            foreach (['announcement_feedback', 'announcement_views', 'public_wall_post_likes', 'wall_post_likes'] as $table) {
                DB::table($table)->where('announcement_id', $announcementId)->delete();
            }
            DB::table('announcements')->where('announcement_id', $announcementId)->delete();
        });
        return back()->with('wall_status', 'Post deleted successfully.');
    }

    public function comment(Request $request, int $announcementId): RedirectResponse
    {
        abort_unless(auth()->check(), 403);
        abort_unless(DB::table('announcements')->where('announcement_id', $announcementId)->where('term_id', $this->currentTermId())->exists(), 404);
        $validated = $request->validate(['comment' => ['required', 'string', 'max:2000']]);
        DB::table('announcement_feedback')->insert([
            'announcement_id' => $announcementId, 'user_id' => auth()->id(),
            'name' => trim((auth()->user()->first_name ?? '').' '.(auth()->user()->last_name ?? '')) ?: 'SK Official',
            'email' => strtolower((string) auth()->user()->email), 'comment' => $validated['comment'],
            'status' => 'posted', 'verified_at' => now(), 'created_at' => now(),
        ]);
        return back()->with('wall_status', 'Comment added.');
    }

    protected function currentTermId(): ?int
    {
        $termId=DB::table('administration_terms')
            ->where('status','current')
            ->orderByDesc('term_id')
            ->value('term_id');

        return $termId ? (int)$termId : null;
    }
}
