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
        abort_unless(auth()->check(), 403);

        $validated = $request->validate([
            'post_content' => ['required', 'string', 'max:5000'],
            'post_category' => ['nullable', 'string', 'max:50'],
        ]);

        $category = strtolower($validated['post_category'] ?? 'update');
        $title = match ($category) {
            'announcement' => 'Announcement',
            'event' => 'Event Update',
            'accomplishment' => 'Accomplishment',
            default => 'Community Update',
        };

        $announcementId = DB::table('announcements')->insertGetId([
            'user_id' => auth()->user()->user_id,
            'title' => $title,
            'content' => $validated['post_content'],
            'visibility' => 'public',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'announcement_id');

        $user = auth()->user();
        if (! empty($user->barangay_id)) {
            app(RankingPointsService::class)->award(
                (int) $user->barangay_id,
                RankingPointsService::COMMUNITY_ENGAGEMENT,
                'wall_post',
                $announcementId,
                (int) $user->user_id
            );

            if ($category === 'event') {
                app(RankingPointsService::class)->award(
                    (int) $user->barangay_id,
                    RankingPointsService::EVENT_PARTICIPATION,
                    'wall_post',
                    $announcementId,
                    (int) $user->user_id
                );
            }
        }

        return back()->with('wall_status', 'Post published to everyone.');
    }

    public function toggleLike(int $announcementId): RedirectResponse
    {
        abort_unless(auth()->check(), 403);

        $postExists = DB::table('announcements')
            ->where('announcement_id', $announcementId)
            ->where('visibility', 'public')
            ->exists();

        abort_unless($postExists, 404);

        $existing = DB::table('wall_post_likes')
            ->where('announcement_id', $announcementId)
            ->where('user_id', auth()->user()->user_id)
            ->first();

        if ($existing) {
            DB::table('wall_post_likes')
                ->where('announcement_id', $announcementId)
                ->where('user_id', auth()->user()->user_id)
                ->delete();

            return back();
        }

        DB::table('wall_post_likes')->insert([
            'announcement_id' => $announcementId,
            'user_id' => auth()->user()->user_id,
            'created_at' => now(),
        ]);

        return back();
    }
}
