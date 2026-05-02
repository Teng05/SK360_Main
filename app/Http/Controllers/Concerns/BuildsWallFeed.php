<?php

// File guide: Handles route logic and page data for app/Http/Controllers/Concerns/BuildsWallFeed.php.

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait BuildsWallFeed
{
    protected function wallFeedPosts(int $limit = 20): Collection
    {
        return DB::table('announcements as a')
            ->leftJoin('users as u', 'a.user_id', '=', 'u.user_id')
            ->leftJoin('barangays as b', 'u.barangay_id', '=', 'b.barangay_id')
            ->where('a.visibility', 'public')
            ->select(
                'a.announcement_id',
                'a.title',
                'a.content',
                'a.created_at',
                'u.role',
                'b.barangay_name',
                DB::raw("CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as author_name")
            )
            ->orderByDesc('a.created_at')
            ->limit($limit)
            ->get()
            ->map(function ($post) {
                $post->likes_count = DB::table('wall_post_likes')
                    ->where('announcement_id', $post->announcement_id)
                    ->count();
                $post->liked_by_current_user = auth()->check()
                    && DB::table('wall_post_likes')
                        ->where('announcement_id', $post->announcement_id)
                        ->where('user_id', auth()->user()->user_id)
                        ->exists();
                $post->author_name = trim((string) $post->author_name) ?: 'SK 360 User';
                $post->role_label = match ($post->role) {
                    'sk_president' => 'SK President',
                    'sk_chairman' => 'SK Chairman',
                    'sk_secretary' => 'SK Secretary',
                    'youth' => 'Youth Member',
                    default => 'User',
                };

                return $post;
            });
    }
}
