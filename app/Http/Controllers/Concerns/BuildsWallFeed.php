<?php

// File guide: Handles shared wall feed data for official home pages.

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait BuildsWallFeed
{
    protected function wallFeedPosts(int $limit=20): Collection
    {
        $currentTermId=$this->wallFeedCurrentTermId();

        if(!$currentTermId){
            return collect();
        }

        return DB::table('announcements as a')
            ->leftJoin('users as u','a.user_id','=','u.user_id')
            ->leftJoin('barangays as b','u.barangay_id','=','b.barangay_id')
            ->where('a.term_id',$currentTermId)
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
            ->limit($limit)
            ->get()
            ->map(function($post){
                $officialLikes=DB::table('wall_post_likes')
                    ->where('announcement_id',$post->announcement_id)
                    ->count();

                $publicLikes=DB::table('public_wall_post_likes')
                    ->where('announcement_id',$post->announcement_id)
                    ->count();

                $post->likes_count=$officialLikes+$publicLikes;

                $post->liked_by_current_user=auth()->check()
                    && DB::table('wall_post_likes')
                        ->where('announcement_id',$post->announcement_id)
                        ->where('user_id',auth()->user()->user_id)
                        ->exists();

                $post->author_name=trim((string)$post->author_name) ?: 'SK 360 Official';

                $post->role_label=match($post->role){
                    'sk_president'=>'SK President',
                    'sk_chairman'=>'SK Chairman',
                    'sk_secretary'=>'SK Secretary',
                    default=>'SK Official',
                };

                return $post;
            });
    }

    protected function wallFeedCurrentTermId(): ?int
    {
        $termId=DB::table('administration_terms')
            ->where('status','current')
            ->orderByDesc('term_id')
            ->value('term_id');

        return $termId ? (int)$termId : null;
    }
}