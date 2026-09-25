<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\BuildsRankingsData;
use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\MobileApiToken;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\RankingPointsService;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;
use TaylanUnutmaz\AgoraTokenBuilder\RtcTokenBuilder;

class MobileSyncController extends Controller
{
    use BuildsRankingsData;

    private const OFFICIAL_ROLES = [
        'sk_president',
        'sk_chairman',
        'sk_secretary',
    ];

    // Authentication and password recovery.
    public function barangays(): JsonResponse
    {
        return response()->json([
            'barangays' => DB::table('barangays')
                ->orderBy('barangay_name')
                ->get(['barangay_id', 'barangay_name']),
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::with('barangay')
            ->where('email', $credentials['email'])
            ->whereIn('role', self::OFFICIAL_ROLES)
            ->whereNull('archived_at')
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid email or password.'], 422);
        }

        if ($user->status !== 'active' || ! $user->is_verified) {
            return response()->json(['message' => 'Account is not active or verified.'], 403);
        }

        /** @var User $user */
        $plainToken = Str::random(80);

        MobileApiToken::create([
            'user_id' => $user->user_id,
            'name' => $credentials['device_name'] ?? 'mobile',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(90),
            'created_at' => now(),
        ]);

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $plainToken,
            'expires_at' => now()->addDays(90)->toISOString(),
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('mobile_api_token')?->delete();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    public function requestPasswordReset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'method' => ['required', 'in:email'],
            'email' => ['required', 'email'],
        ]);

        if ($validated['method'] === 'email') {
            $user = $this->findOfficialByEmail($validated['email']);

            if (! $user) {
                return response()->json(['message' => 'No account found with this email.'], 404);
            }

            $code = (string) random_int(100000, 999999);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($code),
                    'created_at' => now(),
                ]
            );

            Mail::send('email.password-reset', [
                'first_name' => $user->first_name,
                'reset_code' => $code,
            ], function ($message) use ($user) {
                $message->to($user->email, trim($user->first_name.' '.$user->last_name))
                    ->subject('SK360 Password Reset');
            });

            return response()->json([
                'message' => 'Reset code sent. Please check your email.',
                'method' => 'email',
                'target' => $user->email,
            ]);
        }

        $user = $this->findUserByPhone($validated['phone']);

        if (! $user) {
            return response()->json(['message' => 'No account found with this phone number.'], 404);
        }

        $phone = $this->toE164Phone($validated['phone']);

        if (! $phone) {
            return response()->json(['message' => 'Use a valid Philippine phone number like +639123456789.'], 422);
        }

        $response = $this->twilioRequest('Verification', [
            'To' => $phone,
            'Channel' => 'sms',
        ]);

        if (! ($response['ok'] ?? false)) {
            return response()->json([
                'message' => $response['message'] ?? 'Failed to send SMS reset code.',
            ], 500);
        }

        return response()->json([
            'message' => 'Reset code sent. Please check your phone.',
            'method' => 'phone',
            'target' => $phone,
        ]);
    }

    public function verifyPasswordReset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'method' => ['required', 'in:email'],
            'target' => ['required', 'string', 'max:255'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', 'min:8', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/'],
        ], [
            'password.confirmed' => 'Passwords do not match.',
        ]);

        if ($validated['method'] === 'email') {
            $user = $this->findOfficialByEmail($validated['target']);

            $reset = DB::table('password_reset_tokens')
                ->where('email', $validated['target'])
                ->first();

            if (! $user || ! $reset || ! Hash::check($validated['code'], $reset->token) || now()->subMinutes(15)->greaterThan($reset->created_at)) {
                return response()->json(['message' => 'Invalid or expired reset code.'], 422);
            }

            $user->update([
                'password' => Hash::make($validated['password']),
            ]);

            DB::table('password_reset_tokens')
                ->where('email', $validated['target'])
                ->delete();

            return response()->json([
                'message' => 'Your password has been reset. You can now log in.',
            ]);
        }

        $phone = $this->toE164Phone($validated['target']);

        if (! $phone) {
            return response()->json(['message' => 'Use a valid Philippine phone number like +639123456789.'], 422);
        }

        $user = $this->findUserByPhone($phone);

        if (! $user) {
            return response()->json(['message' => 'No account found with this phone number.'], 404);
        }

        $response = $this->twilioRequest('VerificationCheck', [
            'To' => $phone,
            'Code' => $validated['code'],
        ]);

        if (! ($response['ok'] ?? false) || ($response['json']['status'] ?? null) !== 'approved') {
            return response()->json(['message' => 'Invalid or expired reset code.'], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Your password has been reset. You can now log in.',
        ]);
    }

    // Profile, contact verification, and password changes.
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()->loadMissing('barangay')),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => ['nullable', 'string', 'max:50'],
            'last_name' => ['nullable', 'string', 'max:50'],
            'profile_pic' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = $request->user();

        if ($request->hasFile('profile_pic') && Schema::hasColumn('users', 'profile_pic')) {
            $directory = public_path('uploads/profile_pics');
            File::ensureDirectoryExists($directory);
            $filename = $user->user_id.'-'.Str::random(20).'.'.$request->file('profile_pic')->extension();
            $request->file('profile_pic')->move($directory, $filename);
            $user->update(['profile_pic' => 'uploads/profile_pics/'.$filename]);
        }

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $this->userPayload($user->fresh('barangay')),
        ]);
    }

    public function requestContactChange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:email,phone'],
            'value' => ['required', 'string', 'max:255'],
        ]);
        $user = $request->user();
        $type = $validated['type'];
        $value = trim($validated['value']);

        if ($type === 'email') {
            $request->validate(['value' => ['email', 'max:255']]);
            $value = strtolower($value);
            if (strcasecmp($value, (string) $user->email) === 0) {
                return response()->json(['message' => 'This is already your email address.'], 422);
            }
            if (User::where('email', $value)->where('user_id', '!=', $user->user_id)->exists()) {
                return response()->json(['message' => 'That email address is already in use.'], 422);
            }
            $deliveryEmail = $value;
        } else {
            if (! preg_match('/^09\d{9}$/', $value)) {
                return response()->json(['message' => 'Phone number must be 11 digits and start with 09.'], 422);
            }
            if ($value === (string) $user->phone_number) {
                return response()->json(['message' => 'This is already your phone number.'], 422);
            }
            $deliveryEmail = (string) $user->email;
        }

        $code = (string) random_int(100000, 999999);
        Cache::put($this->contactChangeCacheKey((int) $user->user_id), [
            'change_type' => $type,
            'new_value' => $value,
            'token' => Hash::make($code),
        ], now()->addMinutes(15));

        $label = $type === 'email' ? 'email address' : 'phone number';
        Mail::raw(
            "Your SK360 verification code for changing your {$label} is: {$code}\n\nThis code expires in 15 minutes.",
            function ($message) use ($deliveryEmail, $user, $label) {
                $message->to($deliveryEmail, trim($user->first_name.' '.$user->last_name))
                    ->subject('SK360 '.ucfirst($label).' Change Verification');
            }
        );

        return response()->json([
            'message' => $type === 'email'
                ? 'Verification code sent to your new email address.'
                : 'Verification code sent to your registered email address.',
            'delivery_email' => $deliveryEmail,
        ]);
    }

    public function verifyContactChange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:email,phone'],
            'value' => ['required', 'string', 'max:255'],
            'code' => ['required', 'digits:6'],
        ]);
        $user = $request->user();
        $value = trim($validated['value']);
        if ($validated['type'] === 'email') {
            $value = strtolower($value);
        }

        $cacheKey = $this->contactChangeCacheKey((int) $user->user_id);
        $change = Cache::get($cacheKey);

        if (! is_array($change)
            || ($change['change_type'] ?? null) !== $validated['type']
            || ($change['new_value'] ?? null) !== $value
            || ! Hash::check($validated['code'], $change['token'] ?? '')) {
            return response()->json(['message' => 'Invalid or expired verification code.'], 422);
        }

        if ($validated['type'] === 'email') {
            if (User::where('email', $value)->where('user_id', '!=', $user->user_id)->exists()) {
                return response()->json(['message' => 'That email address is already in use.'], 422);
            }
            $changes = ['email' => $value];
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $changes['email_verified_at'] = now();
            }
        } else {
            $changes = ['phone_number' => $value];
        }

        $user->update($changes);
        Cache::forget($cacheKey);

        return response()->json([
            'message' => ucfirst($validated['type']).' updated successfully.',
            'user' => $this->userPayload($user->fresh('barangay')),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->update([
            'password' => $validated['password'],
        ]);

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }

    public function requestPasswordChange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', 'min:8', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/'],
        ], [
            'password.confirmed' => 'Passwords do not match.',
        ]);

        $user = $request->user();
        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $code = (string) random_int(100000, 999999);
        DB::table('mobile_password_changes')->updateOrInsert(
            ['user_id' => $user->user_id],
            [
                'token' => Hash::make($code),
                'password' => Hash::make($validated['password']),
                'created_at' => now(),
            ]
        );

        Mail::send('email.password-change', [
            'first_name' => $user->first_name,
            'verification_code' => $code,
        ], function ($message) use ($user) {
            $message->to($user->email, trim($user->first_name.' '.$user->last_name))
                ->subject('SK360 Password Change Verification');
        });

        return response()->json(['message' => 'Verification code sent to your registered email.']);
    }

    public function verifyPasswordChange(Request $request): JsonResponse
    {
        $validated = $request->validate(['code' => ['required', 'digits:6']]);
        $user = $request->user();
        $change = DB::table('mobile_password_changes')
            ->where('user_id', $user->user_id)
            ->first();

        if (! $change || ! Hash::check($validated['code'], $change->token) || now()->subMinutes(15)->greaterThan($change->created_at)) {
            return response()->json(['message' => 'Invalid or expired verification code.'], 422);
        }

        $user->update(['password' => $change->password]);
        DB::table('mobile_password_changes')->where('user_id', $user->user_id)->delete();

        return response()->json(['message' => 'Password updated successfully.']);
    }

    // Download the records used by the mobile app.
    // Sends the records currently shared between web and mobile.
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'since' => ['nullable', 'date'],
        ]);

        $since = isset($validated['since']) ? Carbon::parse($validated['since']) : null;
        $user = $request->user()->loadMissing('barangay');

        return response()->json([
            'server_time' => now()->toISOString(),
            'user' => $this->userPayload($user),
            'barangays' => $this->tableRows('barangays', $since, 'barangay_id'),
            'announcements' => $this->announcements($user, $since),
            'wall_posts' => $this->wallPosts($user, $since),
            'feedback' => $this->feedback($user),
            'events' => $this->events($user, $since),
            'meetings' => $this->meetings($user, $since),
            'notifications' => $this->notifications($user, $since),
            'rankings' => $this->mobileRankings(),
            'ranking_periods' => $this->rankingPeriods()->values()->all(),
            'ranking_history' => $this->mobileRankingHistory(),
            'ranking_point_system' => $this->rankingPointSystem(),
            'latest_ranking_period' => $this->latestRankingPeriod(),
            'submission_slots' => $this->tableRows('submission_slots', $since, 'slot_id'),
            'report_submissions' => $this->reportSubmissions($user, $since),
            'accomplishment_reports' => $this->accomplishmentReports($user, $since),
            'budget_reports' => $this->budgetReports($user, $since),
            'leadership_profiles' => $this->leadershipProfiles($user, $since),
            'leadership_history' => $this->leadershipHistory($user),
            'archive_documents' => $this->archiveDocuments($user, $since),
        ]);
    }

    // Community posts and feedback.
    public function storeWallPost(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'post_content' => ['required', 'string', 'max:5000'],
            'post_category' => ['nullable', 'string', 'max:50'],
            'audience' => ['nullable', 'in:public,officials_only'],
            'status' => ['nullable', 'in:draft,published'],
        ]);

        $category = strtolower($validated['post_category'] ?? 'update');

        if ($category === 'announcement' && ! $this->isPresident($request->user())) {
            return response()->json(['message' => 'Only SK President can create announcements.'], 403);
        }

        $title = $validated['title'] ?? match ($category) {
            'announcement' => 'Announcement',
            'event' => 'Event Update',
            'accomplishment' => 'Accomplishment',
            default => 'Community Update',
        };

        $announcementData = [
            ...$this->mobileTermData('announcements'),
            'user_id' => $request->user()->user_id,
            'title' => $title,
            'content' => $validated['post_content'],
            'visibility' => $validated['audience'] ?? 'public',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('announcements', 'status')) {
            $announcementData['status'] = $validated['status'] ?? 'published';
        }
        $announcementId = DB::table('announcements')->insertGetId($announcementData, 'announcement_id');

        $user = $request->user();

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

        return response()->json([
            'message' => 'Post published.',
            'announcement' => DB::table('announcements')
                ->where('announcement_id', $announcementId)
                ->first(),
        ], 201);
    }

    public function updateWallPost(Request $request, int $announcementId): JsonResponse
    {
        $announcement = DB::table('announcements')
            ->where('announcement_id', $announcementId)
            ->first();

        if (! $announcement) {
            return response()->json(['message' => 'Announcement not found.'], 404);
        }

        if ((int) $announcement->user_id !== (int) $request->user()->user_id && ! $this->isPresident($request->user())) {
            return response()->json(['message' => 'You are not allowed to edit this announcement.'], 403);
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'post_content' => ['required', 'string', 'max:5000'],
            'audience' => ['nullable', 'in:public,officials_only'],
            'visibility' => ['nullable', 'in:public,officials_only'],
            'status' => ['nullable', 'in:draft,published'],
        ]);

        $changes = [
            'content' => $validated['post_content'],
            'updated_at' => now(),
        ];
        if (filled($validated['title'] ?? null)) {
            $changes['title'] = $validated['title'];
        }
        $changes['visibility'] = $validated['audience']
            ?? $validated['visibility']
            ?? ($announcement->visibility ?? 'public');
        if (Schema::hasColumn('announcements', 'status')) {
            $changes['status'] = $validated['status'] ?? ($announcement->status ?? 'published');
        }

        DB::table('announcements')->where('announcement_id', $announcementId)->update($changes);

        return response()->json([
            'message' => 'Announcement updated.',
            'announcement_id' => $announcementId,
        ]);
    }

    public function toggleWallLike(Request $request, int $announcementId): JsonResponse
    {
        $postExists = DB::table('announcements')
            ->where('announcement_id', $announcementId)
            ->where('visibility', 'public')
            ->exists();

        if (! $postExists) {
            return response()->json(['message' => 'Post not found.'], 404);
        }

        $existing = DB::table('wall_post_likes')
            ->where('announcement_id', $announcementId)
            ->where('user_id', $request->user()->user_id)
            ->first();

        if ($existing) {
            DB::table('wall_post_likes')
                ->where('announcement_id', $announcementId)
                ->where('user_id', $request->user()->user_id)
                ->delete();
        } else {
            DB::table('wall_post_likes')->insert([
                'announcement_id' => $announcementId,
                'user_id' => $request->user()->user_id,
                'created_at' => now(),
            ]);
        }

        return response()->json([
            'message' => $existing ? 'Post unliked.' : 'Post liked.',
            'liked' => ! $existing,
        ]);
    }

    public function wallPostComments(Request $request, int $announcementId): JsonResponse
    {
        if (! Schema::hasTable('announcement_feedback')) {
            return response()->json(['comments' => [], 'total' => 0]);
        }

        $exists = DB::table('announcements')
            ->where('announcement_id', $announcementId)
            ->where('visibility', 'public')
            ->exists();
        if (! $exists) {
            return response()->json(['message' => 'Post not found.'], 404);
        }

        $comments = DB::table('announcement_feedback as f')
            ->leftJoin('users as u', 'f.user_id', '=', 'u.user_id')
            ->leftJoin('barangays as b', 'u.barangay_id', '=', 'b.barangay_id')
            ->where('f.announcement_id', $announcementId)
            ->where('f.status', 'posted')
            ->select('f.feedback_id', 'f.user_id', 'f.name', 'f.comment', 'f.created_at', 'u.role', 'b.barangay_name')
            ->orderBy('f.created_at')
            ->limit(100)
            ->get()
            ->map(fn ($row) => [
                'comment_id' => $row->feedback_id,
                'feedback_id' => $row->feedback_id,
                'user_id' => $row->user_id,
                'name' => trim((string) $row->name) ?: 'Anonymous',
                'comment' => $row->comment,
                'created_at' => $row->created_at,
                'role' => $row->role,
                'barangay_name' => $row->barangay_name,
            ])->values();

        return response()->json(['comments' => $comments, 'total' => $comments->count()]);
    }

    public function storeWallPostComment(Request $request, int $announcementId): JsonResponse
    {
        if (! Schema::hasTable('announcement_feedback')) {
            return response()->json(['message' => 'Comments are not available.'], 404);
        }
        $exists = DB::table('announcements')
            ->where('announcement_id', $announcementId)
            ->where('visibility', 'public')
            ->exists();
        if (! $exists) {
            return response()->json(['message' => 'Post not found.'], 404);
        }
        $validated = $request->validate(['comment' => ['required', 'string', 'max:1000']]);
        $user = $request->user();
        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'SK Official';
        $feedbackId = DB::table('announcement_feedback')->insertGetId([
            'announcement_id' => $announcementId,
            'user_id' => $user->user_id,
            'name' => $name,
            'email' => strtolower(trim((string) $user->email)),
            'comment' => trim($validated['comment']),
            'status' => 'posted',
            'verified_at' => now(),
            'created_at' => now(),
        ], 'feedback_id');
        return response()->json(['message' => 'Your comment has been posted.', 'comment_id' => $feedbackId], 201);
    }

    // Allows officials to hide or restore public feedback.
    public function updateFeedback(Request $request, int $feedbackId): JsonResponse
    {
        if (! $this->isOfficial($request->user())) {
            return response()->json(['message' => 'Only SK officials can manage feedback.'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:posted,hidden'],
        ]);

        if (! Schema::hasTable('announcement_feedback')) {
            return response()->json(['message' => 'Feedback is not available.'], 404);
        }

        $updated = DB::table('announcement_feedback')
            ->where('feedback_id', $feedbackId)
            ->update(['status' => $validated['status']]);

        if ($updated === 0) {
            return response()->json(['message' => 'Feedback not found.'], 404);
        }

        return response()->json(['message' => 'Feedback status updated.']);
    }

    // Calendar events.
    public function storeEvent(Request $request): JsonResponse
    {
        if (! $this->isPresident($request->user())) {
            return response()->json(['message' => 'Only SK President can schedule calendar events.'], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'event_type' => ['nullable', 'string', 'max:50'],
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['nullable', 'date', 'after_or_equal:start_datetime'],
            'visibility' => ['nullable', 'in:public,officials_only,chairman_only,secretary_only'],
        ]);

        $start = Carbon::parse($validated['start_datetime']);
        $end = isset($validated['end_datetime'])
            ? Carbon::parse($validated['end_datetime'])
            : $start->copy()->addHour();

        $conflict = DB::table('events')
            ->where('start_datetime', '<', $end)
            ->where('end_datetime', '>', $start)
            ->exists();
        if ($conflict) {
            return response()->json([
                'message' => 'This schedule overlaps an existing event.',
            ], 422);
        }

        $eventId = DB::table('events')->insertGetId([
            ...$this->mobileTermData('events'),
            'created_by' => $request->user()->user_id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
            'event_type' => $validated['event_type'] ?? 'event',
            'start_datetime' => $start,
            'end_datetime' => $end,
            'visibility' => $validated['visibility'] ?? 'public',
            'created_at' => now(),
        ], 'event_id');

        return response()->json([
            'message' => 'Event created.',
            'event' => DB::table('events')
                ->where('event_id', $eventId)
                ->first(),
        ], 201);
    }

    public function updateEvent(Request $request, int $eventId): JsonResponse
    {
        if (! $this->isPresident($request->user())) {
            return response()->json(['message' => 'Only SK President can edit calendar events.'], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'event_type' => ['nullable', 'string', 'max:50'],
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['required', 'date', 'after:start_datetime'],
            'visibility' => ['nullable', 'in:public,officials_only,chairman_only,secretary_only'],
        ]);

        if (! DB::table('events')->where('event_id', $eventId)->exists()) {
            return response()->json(['message' => 'Event not found.'], 404);
        }

        $start = Carbon::parse($validated['start_datetime']);
        $end = Carbon::parse($validated['end_datetime']);

        DB::table('events')->where('event_id', $eventId)->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
            'event_type' => $validated['event_type'] ?? 'event',
            'start_datetime' => $start,
            'end_datetime' => $end,
            'visibility' => $validated['visibility'] ?? 'public',
        ]);

        return response()->json(['message' => 'Event updated.', 'event_id' => $eventId]);
    }

    // Meeting schedules and video calls.
    public function storeMeeting(Request $request): JsonResponse
    {
        if (! $this->isPresident($request->user())) {
            return response()->json(['message' => 'Only SK President can create meetings.'], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'agenda' => ['nullable', 'string', 'max:5000'],
            'meeting_date' => ['required', 'date'],
            'meeting_time' => ['required', 'date_format:H:i'],
        ]);

        $meetingId = DB::table('meetings')->insertGetId([
            ...$this->mobileTermData('meetings'),
            'title' => $validated['title'],
            'agenda' => $validated['agenda'] ?? null,
            'meeting_date' => $validated['meeting_date'],
            'meeting_time' => $validated['meeting_time'].':00',
            'location_or_link' => null,
            'dyte_meeting_id' => null,
            'created_by' => $request->user()->user_id,
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'meeting_id');

        $meeting = DB::table('meetings')
            ->where('meeting_id', $meetingId)
            ->first();

        $meeting->call_url = url("/sk_pres/meetings/{$meetingId}/call");

        return response()->json([
            'message' => 'Meeting scheduled successfully.',
            'meeting' => $meeting,
        ], 201);
    }

    public function updateMeeting(Request $request, int $meetingId): JsonResponse
    {
        if (! $this->isPresident($request->user())) {
            return response()->json(['message' => 'Only SK President can edit meetings.'], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'agenda' => ['nullable', 'string', 'max:5000'],
            'meeting_date' => ['required', 'date'],
            'meeting_time' => ['required', 'date_format:H:i'],
        ]);

        $meeting = DB::table('meetings')->where('meeting_id', $meetingId)->first();
        if (! $meeting) {
            return response()->json(['message' => 'Meeting not found.'], 404);
        }

        if ($meeting->status !== 'scheduled') {
            return response()->json(['message' => 'Only scheduled meetings can be edited.'], 422);
        }

        DB::table('meetings')->where('meeting_id', $meetingId)->update([
            'title' => $validated['title'],
            'agenda' => $validated['agenda'] ?? null,
            'meeting_date' => $validated['meeting_date'],
            'meeting_time' => $validated['meeting_time'].':00',
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Meeting updated.', 'meeting_id' => $meetingId]);
    }

    public function endMeeting(Request $request, Meeting $meeting): JsonResponse
    {
        if (! $this->isPresident($request->user())) {
            return response()->json(['message' => 'Only SK President can end meetings.'], 403);
        }

        if ($meeting->status !== 'completed') {
            $meeting->status = 'completed';
            $meeting->updated_at = now();
            $meeting->save();
        }

        return response()->json([
            'message' => 'Meeting ended successfully.',
            'meeting_id' => $meeting->meeting_id,
            'status' => $meeting->status,
        ]);
    }

    public function meetingJoinUrl(Request $request, Meeting $meeting): JsonResponse
    {
        if (! $this->isOfficial($request->user())) {
            return response()->json(['message' => 'Only SK officials can join meetings.'], 403);
        }

        if ($this->meetingUnavailable($meeting)) {
            return response()->json(['message' => 'This meeting is no longer available.'], 422);
        }

        return response()->json([
            'join_url' => URL::temporarySignedRoute(
                'mobile.meetings.call',
                now()->addHours(4),
                ['meeting' => $meeting->meeting_id]
            ),
        ]);
    }

    public function meetingAgoraToken(Request $request, Meeting $meeting): JsonResponse
    {
        if (! $this->isOfficial($request->user())) {
            return response()->json(['message' => 'Only SK officials can join meetings.'], 403);
        }

        if ($this->meetingUnavailable($meeting)) {
            return response()->json(['message' => 'This meeting is no longer available.'], 422);
        }

        return $this->buildAgoraTokenResponse(
            $meeting,
            (int) $request->user()->user_id
        );
    }

    public function mobileMeetingCall(Meeting $meeting): View
    {
        return view('sk_pres.video-call', [
            'fullName' => 'Mobile Participant',
            'menuItems' => [],
            'currentUrl' => url('/'),
            'meeting' => $this->decorateMobileMeeting($meeting),
            'channelName' => 'meeting-'.$meeting->meeting_id,
            'backRoute' => url('/'),
            'tokenRoute' => URL::temporarySignedRoute(
                'mobile.meetings.agora.token',
                now()->addHours(4),
                ['meeting' => $meeting->meeting_id]
            ),
        ]);
    }

    public function mobileMeetingToken(Meeting $meeting): JsonResponse
    {
        return $this->buildAgoraTokenResponse($meeting);
    }

    // Chat contacts and barangay officials.
    public function chatUsers(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->query('search', ''));
        $user = $request->user();

        $chatRoles = $user->role === 'sk_president'
            ? ['sk_chairman', 'sk_secretary']
            : self::OFFICIAL_ROLES;

        $query = DB::table('users as u')
            ->leftJoin('barangays as b', 'u.barangay_id', '=', 'b.barangay_id')
            ->select(
                'u.user_id',
                'u.first_name',
                'u.last_name',
                'u.email',
                'u.role',
                'u.profile_pic',
                'b.barangay_name'
            )
            ->where('u.user_id', '!=', $user->user_id)
            ->where('u.status', 'active')
            ->whereIn('u.role', $chatRoles);

        if ($keyword !== '') {
            $query->where(function ($query) use ($keyword) {
                $query
                    ->whereRaw(
                        "CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) LIKE ?",
                        ["%{$keyword}%"]
                    )
                    ->orWhere('u.email', 'like', "%{$keyword}%")
                    ->orWhere('b.barangay_name', 'like', "%{$keyword}%");
            });
        }

        return response()->json([
            'users' => $query
                ->orderBy('u.first_name')
                ->orderBy('u.last_name')
                ->limit(30)
                ->get()
                ->map(fn ($row) => [
                    'id' => (string) $row->user_id,
                    'name' => trim(($row->first_name ?? '').' '.($row->last_name ?? '')) ?: $row->email,
                    'email' => $row->email,
                    'role' => $row->role,
                    'barangay' => $row->barangay_name,
                    'profile_pic_url' => $row->profile_pic
                        ? $this->publicUrl(Str::startsWith($row->profile_pic, 'uploads/')
                            ? $row->profile_pic
                            : 'uploads/profile_pics/'.$row->profile_pic)
                        : null,
                ])
                ->values(),
        ]);
    }

    public function storeCouncilMember(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'sk_chairman') {
            return response()->json(['message' => 'Only SK Chairman can add SK council members.'], 403);
        }

        if (! Schema::hasTable('sk_council')) {
            return response()->json(['message' => 'SK council table is not available.'], 500);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'term' => ['nullable', 'string', 'max:50'],
            'profile_img' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $profileImage = 'default.png';
        if ($request->hasFile('profile_img')) {
            $directory = public_path('uploads/council_profiles');
            File::ensureDirectoryExists($directory);
            $profileImage = 'uploads/council_profiles/'.Str::random(32).'.'.$request->file('profile_img')->extension();
            $request->file('profile_img')->move(public_path('uploads/council_profiles'), basename($profileImage));
        }

        $currentTerm = DB::table('administration_terms')
            ->where('status', 'current')
            ->orderByDesc('term_id')
            ->first();
        if (! $currentTerm) {
            return response()->json(['message' => 'There is no active administration term.'], 422);
        }

        $targetTerm = $this->resolveMobileLeadershipTerm($validated['term'] ?? null) ?: $currentTerm;
        $isCurrentTerm = (int) $targetTerm->term_id === (int) $currentTerm->term_id
            && $targetTerm->status === 'current';
        $termLabel = $targetTerm->start_year.'-'.$targetTerm->end_year;

        $councilId = DB::table('sk_council')->insertGetId([
            'barangay_id' => $user->barangay_id,
            'term_id' => $targetTerm->term_id,
            'name' => trim($validated['name']),
            'position' => 'SK Councilor',
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'term' => $termLabel,
            'status' => $isCurrentTerm ? 'current' : 'completed',
            'profile_img' => $profileImage,
            'created_at' => now(),
            'completed_at' => $isCurrentTerm ? null : ($targetTerm->completed_at ?: now()),
        ], 'council_id');

        return response()->json([
            'message' => 'SK council member added.',
            'council_member' => DB::table('sk_council')
                ->where('council_id', $councilId)
                ->first(),
        ], 201);
    }

    public function updateCouncilMember(Request $request, int $councilId): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'sk_chairman') {
            return response()->json(['message' => 'Only SK Chairman can edit SK council members.'], 403);
        }

        $member = DB::table('sk_council')
            ->where('council_id', $councilId)
            ->where('barangay_id', $user->barangay_id)
            ->where(function ($query) {
                $query->whereRaw('LOWER(position) LIKE ?', ['%councilor%'])
                    ->orWhereRaw('LOWER(position) LIKE ?', ['%kagawad%']);
            })
            ->first();
        if (! $member) {
            return response()->json(['message' => 'SK council member not found.'], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'term' => ['nullable', 'string', 'max:50'],
            'profile_img' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $currentTerm = $this->mobileCurrentTerm();
        $targetTerm = $this->resolveMobileLeadershipTerm($validated['term'] ?? null)
            ?: ($member->term_id ? DB::table('administration_terms')->where('term_id', $member->term_id)->first() : null)
            ?: $currentTerm;
        $isCurrentTerm = $currentTerm
            && (int) $targetTerm->term_id === (int) $currentTerm->term_id
            && $targetTerm->status === 'current';
        $termLabel = $targetTerm->start_year.'-'.$targetTerm->end_year;

        $changes = [
            'term_id' => $targetTerm->term_id,
            'name' => trim($validated['name']),
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'term' => $termLabel,
            'status' => $isCurrentTerm ? 'current' : 'completed',
            'completed_at' => $isCurrentTerm ? null : ($targetTerm->completed_at ?: now()),
        ];
        if ($request->hasFile('profile_img')) {
            $directory = public_path('uploads/council_profiles');
            File::ensureDirectoryExists($directory);
            $path = 'uploads/council_profiles/'.Str::random(32).'.'.$request->file('profile_img')->extension();
            $request->file('profile_img')->move($directory, basename($path));
            $changes['profile_img'] = $path;
        }

        DB::table('sk_council')->where('council_id', $councilId)->update($changes);

        return response()->json([
            'message' => 'SK council member updated.',
            'council_member' => DB::table('sk_council')->where('council_id', $councilId)->first(),
        ]);
    }

    public function storeSecretaryAccount(Request $request): JsonResponse
    {
        $chairman = $request->user();
        if ($chairman->role !== 'sk_chairman') {
            return response()->json(['message' => 'Only SK Chairman can create a Secretary account.'], 403);
        }

        $currentTerm = DB::table('administration_terms')
            ->where('status', 'current')
            ->orderByDesc('term_id')
            ->first();
        if (! $currentTerm) {
            return response()->json(['message' => 'There is no active administration term.'], 422);
        }

        $assigned = DB::table('official_terms')
            ->where('user_id', $chairman->user_id)
            ->where('term_id', $currentTerm->term_id)
            ->where('role', 'sk_chairman')
            ->where('status', 'current')
            ->exists();
        if (! $assigned) {
            return response()->json(['message' => 'Your Chairman account is not connected to the current administration term.'], 422);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone_number'],
            'term' => ['nullable', 'string', 'max:50'],
        ]);

        $targetTerm = $this->resolveMobileLeadershipTerm($validated['term'] ?? null) ?: $currentTerm;
        $isCurrentTerm = (int) $targetTerm->term_id === (int) $currentTerm->term_id
            && $targetTerm->status === 'current';

        $existing = User::where('barangay_id', $chairman->barangay_id)
            ->where('role', 'sk_secretary')
            ->whereNull('archived_at')
            ->exists();
        if ($isCurrentTerm && $existing) {
            return response()->json(['message' => 'Your barangay already has a current or pending SK Secretary.'], 422);
        }

        $token = Str::random(64);
        $secretary = DB::transaction(function () use ($validated, $chairman, $targetTerm, $isCurrentTerm, $token) {
            $user = User::create([
                'first_name' => trim($validated['first_name']),
                'last_name' => trim($validated['last_name']),
                'email' => strtolower($validated['email']),
                'phone_number' => filled($validated['phone'] ?? null) ? trim($validated['phone']) : null,
                'barangay_id' => $chairman->barangay_id,
                'role' => 'sk_secretary',
                'password' => Hash::make(Str::random(64)),
                'is_verified' => $isCurrentTerm ? 0 : 1,
                'status' => $isCurrentTerm ? 'inactive' : 'active',
                'term_start' => $targetTerm->start_year.'-01-01',
                'term_end' => $targetTerm->end_year.'-12-31',
                'archived_at' => $isCurrentTerm ? null : ($targetTerm->completed_at ?: now()),
            ]);

            DB::table('official_terms')->insert([
                'user_id' => $user->user_id,
                'term_id' => $targetTerm->term_id,
                'barangay_id' => $chairman->barangay_id,
                'role' => 'sk_secretary',
                'status' => $isCurrentTerm ? 'pending' : 'completed',
                'started_at' => $isCurrentTerm ? null : now(),
                'completed_at' => $isCurrentTerm ? null : ($targetTerm->completed_at ?: now()),
            ]);

            if ($isCurrentTerm) {
                DB::table('password_reset_tokens')->updateOrInsert(
                    ['email' => $user->email],
                    ['token' => Hash::make($token), 'created_at' => now()]
                );
            }

            return $user;
        });

        if (! $isCurrentTerm) {
            return response()->json([
                'message' => 'SK Secretary historical record added.',
                'secretary_created' => true,
            ], 201);
        }

        $setupLink = route('password.setup', ['token' => $token, 'email' => $secretary->email]);
        try {
            Mail::send('email.account-setup', ['user' => $secretary, 'setupLink' => $setupLink], function ($message) use ($secretary) {
                $message->to($secretary->email, trim($secretary->first_name.' '.$secretary->last_name))
                    ->subject('Set Up Your SK360 Account');
            });
        } catch (\Throwable $exception) {
            \Log::error('Mobile Secretary setup email failed for '.$secretary->email.': '.$exception->getMessage());

            return response()->json([
                'message' => 'Secretary account created, but the setup email could not be sent. Use the web Leadership page to resend it.',
                'secretary_created' => true,
            ], 201);
        }

        return response()->json([
            'message' => 'SK Secretary account created. A password setup link was sent to '.$secretary->email.'.',
            'secretary_created' => true,
        ], 201);
    }

    public function storeChairmanAccount(Request $request): JsonResponse
    {
        $president = $request->user();
        if ($president->role !== 'sk_president') {
            return response()->json(['message' => 'Only the SK President can create a Chairman account.'], 403);
        }

        $currentTerm = $this->mobileCurrentTerm();
        if (! $currentTerm) {
            return response()->json(['message' => 'There is no active administration term.'], 422);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone_number'],
            'barangay_id' => ['required', 'integer', 'exists:barangays,barangay_id'],
        ]);

        $existing = DB::table('official_terms')
            ->where('term_id', $currentTerm->term_id)
            ->where('barangay_id', $validated['barangay_id'])
            ->where('role', 'sk_chairman')
            ->whereIn('status', ['pending', 'current'])
            ->exists();
        if ($existing) {
            return response()->json(['message' => 'That barangay already has a current or pending SK Chairman.'], 422);
        }

        $token = Str::random(64);
        $chairman = DB::transaction(function () use ($validated, $currentTerm, $token) {
            $user = User::create([
                'first_name' => trim($validated['first_name']),
                'last_name' => trim($validated['last_name']),
                'email' => strtolower($validated['email']),
                'phone_number' => filled($validated['phone'] ?? null) ? trim($validated['phone']) : null,
                'barangay_id' => $validated['barangay_id'],
                'role' => 'sk_chairman',
                'password' => Hash::make(Str::random(64)),
                'is_verified' => 0,
                'status' => 'inactive',
                'term_start' => $currentTerm->start_year.'-01-01',
                'term_end' => $currentTerm->end_year.'-12-31',
                'archived_at' => null,
            ]);

            DB::table('official_terms')->insert([
                'user_id' => $user->user_id,
                'term_id' => $currentTerm->term_id,
                'barangay_id' => $validated['barangay_id'],
                'role' => 'sk_chairman',
                'status' => 'pending',
                'started_at' => null,
                'completed_at' => null,
            ]);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );

            return $user;
        });

        $setupLink = route('password.setup', ['token' => $token, 'email' => $chairman->email]);
        try {
            Mail::send('email.account-setup', ['user' => $chairman, 'setupLink' => $setupLink], function ($message) use ($chairman) {
                $message->to($chairman->email, trim($chairman->first_name.' '.$chairman->last_name))
                    ->subject('Set Up Your SK360 Account');
            });
        } catch (\Throwable $exception) {
            \Log::error('Mobile Chairman setup email failed for '.$chairman->email.': '.$exception->getMessage());
            return response()->json([
                'message' => 'Chairman account created, but the setup email could not be sent. Use the web Leadership page to resend it.',
                'chairman_created' => true,
            ], 201);
        }

        return response()->json([
            'message' => 'SK Chairman account created. A password setup link was sent to '.$chairman->email.'.',
            'chairman_created' => true,
        ], 201);
    }

    // Report uploads, submission slots, and consolidation.
    public function storeOfficialSubmission(Request $request, RankingPointsService $points): JsonResponse
    {
        $user = $request->user();

        if (! in_array($user->role, ['sk_chairman', 'sk_secretary'], true)) {
            return response()->json(['message' => 'Only SK Chairman or SK Secretary can submit reports.'], 403);
        }

        $validated = $request->validate([
            'slot_id' => ['required', 'integer'],
            'submission_type' => ['required', 'in:accomplishment_report,budget_report'],
            'report_type' => ['nullable', 'in:monthly,quarterly,annual'],
            'reporting_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'reporting_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'reporting_quarter' => ['nullable', 'in:Q1,Q2,Q3,Q4'],
            'remarks' => ['nullable', 'string', 'max:5000'],
            'report_file' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $roleLabel = $user->role === 'sk_chairman' ? 'SK Chairman' : 'SK Secretary';

        $slot = DB::table('submission_slots')
            ->where('slot_id', $validated['slot_id'])
            ->where('submission_type', $validated['submission_type'])
            ->whereIn('role', [$roleLabel, 'Both'])
            ->where('status', 'open')
            ->first();

        if (! $slot) {
            return response()->json(['message' => 'That submission slot is no longer available.'], 422);
        }

        if ($validated['submission_type'] === 'budget_report'
            && Schema::hasColumn('submission_slots', 'budget_period_type')
            && filled($slot->budget_period_type)
            && ($validated['report_type'] ?? null) !== $slot->budget_period_type) {
            return response()->json([
                'message' => 'Select the reporting period required by this budget slot.',
            ], 422);
        }

        $now = now();

        if (Carbon::parse($slot->start_date)->startOfDay()->gt($now) || Carbon::parse($slot->end_date)->endOfDay()->lt($now)) {
            return response()->json(['message' => 'That submission slot is not active today.'], 422);
        }

        $sourceType = $validated['submission_type'];
        $reportTable = $sourceType === 'budget_report' ? 'budget_reports' : 'accomplishment_reports';
        $reportIdColumn = $sourceType === 'budget_report' ? 'budget_report_id' : 'report_id';
        $existingReport = DB::table($reportTable)
            ->where('barangay_id', $user->barangay_id)
            ->where('slot_id', $slot->slot_id)
            ->first();
        if ($existingReport && Schema::hasTable('submission_quality_reviews')) {
            $review = DB::table('submission_quality_reviews')
                ->where('source_type', $sourceType)
                ->where('source_id', $existingReport->{$reportIdColumn})
                ->first();
            if ($review && strtolower((string) $review->status) === 'approved') {
                return response()->json([
                    'message' => 'This report has already been approved and can no longer be replaced.',
                ], 422);
            }
        }

        $directoryName = $validated['submission_type'] === 'budget_report'
            ? 'budget_reports'
            : 'reports';

        $prefix = $validated['submission_type'] === 'budget_report' ? 'BUD' : 'REP';
        $directory = public_path("uploads/{$directoryName}");

        File::ensureDirectoryExists($directory);

        $file = $request->file('report_file');
        $filename = $prefix.'_'.time().'_'.$user->barangay_id.'.pdf';

        $file->move($directory, $filename);

        $validated['uploaded_file_name'] = $file->getClientOriginalName();
        $validated['uploaded_file_path'] = "uploads/{$directoryName}/{$filename}";

        if ($validated['submission_type'] === 'budget_report') {
            $sourceId = $this->saveMobileBudgetSubmission($user, $slot, $validated);
            $sourceType = 'budget_report';

            $row = DB::table('budget_reports')
                ->where('budget_report_id', $sourceId)
                ->first();
        } else {
            $sourceId = $this->saveMobileAccomplishmentSubmission($user, $slot, $validated);
            $sourceType = 'accomplishment_report';

            $row = DB::table('accomplishment_reports')
                ->where('report_id', $sourceId)
                ->first();
        }

        if ($existingReport && Schema::hasTable('submission_quality_reviews')) {
            DB::table('submission_quality_reviews')
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->where('status', 'needs_revision')
                ->update([
                    'reviewer_id' => null,
                    'status' => 'pending',
                    'complete_contents' => false,
                    'correct_document' => false,
                    'correct_period' => false,
                    'remarks' => null,
                    'reviewed_at' => null,
                    'updated_at' => now(),
                ]);
        }

        $isOnTime = $now->lessThanOrEqualTo(
            Carbon::parse($slot->end_date)->endOfDay()
        );

        $points->award(
            (int) $user->barangay_id,
            $isOnTime
                ? RankingPointsService::ON_TIME_REPORT_SUBMISSION
                : RankingPointsService::LATE_SUBMISSION,
            $sourceType,
            $sourceId,
            (int) $user->user_id
        );

        $points->award(
            (int) $user->barangay_id,
            RankingPointsService::QUALITY_DOCUMENTATION,
            $sourceType,
            $sourceId,
            (int) $user->user_id
        );

        return response()->json([
            'message' => 'Submission synced.',
            'submission' => $row,
        ], 201);
    }

    public function submissionSlots(Request $request): JsonResponse
    {
        if (! $this->isPresident($request->user())) {
            return response()->json(['message' => 'Only SK President can manage submission slots.'], 403);
        }

        $slots = DB::table('submission_slots')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'slots' => $slots,
            'summary' => [
                'total_slots' => $slots->count(),
                'open_slots' => $slots->where('status', 'open')->count(),
                'closed_slots' => $slots->where('status', 'closed')->count(),
            ],
        ]);
    }

    public function storeSubmissionSlot(Request $request, NotificationService $notifications): JsonResponse
    {
        if (! $this->isPresident($request->user())) {
            return response()->json(['message' => 'Only SK President can create submission slots.'], 403);
        }

        $validated = $request->validate([
            'submission_type' => ['required', 'in:accomplishment_report,budget_report'],
            'submission_title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'submission_role' => ['required', 'in:SK Chairman,SK Secretary,Both'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $slotId = DB::table('submission_slots')->insertGetId([
            ...$this->mobileTermData('submission_slots'),
            'submission_type' => $validated['submission_type'],
            'title' => $validated['submission_title'],
            'description' => $validated['description'] ?? null,
            'role' => $validated['submission_role'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'open',
            'created_at' => now(),
        ], 'slot_id');

        $notifications->notifySubmissionSlotCreated([
            'submission_type' => $validated['submission_type'],
            'title' => $validated['submission_title'],
            'role' => $validated['submission_role'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ], $request->user());

        return response()->json([
            'message' => 'Submission slot created.',
            'slot' => DB::table('submission_slots')
                ->where('slot_id', $slotId)
                ->first(),
        ], 201);
    }

    public function toggleSubmissionSlot(Request $request, int $slotId): JsonResponse
    {
        if (! $this->isPresident($request->user())) {
            return response()->json(['message' => 'Only SK President can manage submission slots.'], 403);
        }

        $slot = DB::table('submission_slots')->where('slot_id', $slotId)->first();
        if (! $slot) {
            return response()->json(['message' => 'Submission slot not found.'], 404);
        }

        DB::table('submission_slots')
            ->where('slot_id', $slotId)
            ->update(['status' => $slot->status === 'open' ? 'closed' : 'open']);

        return response()->json(['message' => 'Submission slot status updated.']);
    }

    public function submissionSlotSubmissions(Request $request, int $slotId): JsonResponse
    {
        if (! $this->isPresident($request->user())) {
            return response()->json(['message' => 'Only SK President can view submissions.'], 403);
        }

        $slot = DB::table('submission_slots')->where('slot_id', $slotId)->first();
        if (! $slot) {
            return response()->json(['message' => 'Submission slot not found.'], 404);
        }

        $table = $slot->submission_type === 'budget_report' ? 'budget_reports' : 'accomplishment_reports';
        $idColumn = $table === 'budget_reports' ? 'budget_report_id' : 'report_id';
        $rows = DB::table('barangays as b')
            ->leftJoin($table.' as r', function ($join) use ($slot) {
                $join->on('r.barangay_id', '=', 'b.barangay_id')
                    ->where('r.slot_id', '=', $slot->slot_id);
            })
            ->select('b.barangay_id', 'b.barangay_name', 'r.'.$idColumn.' as submission_id', 'r.title', 'r.uploaded_file_name', 'r.uploaded_file_path', 'r.generated_pdf_path', 'r.created_at as submitted_at')
            ->orderBy('b.barangay_name')
            ->get()
            ->map(function ($row) {
                $path = $row->uploaded_file_path ?: $row->generated_pdf_path;
                $row->file_url = $path && ! in_array($path, ['SYSTEM_GEN', 'TEMPLATE_GEN'], true) ? $this->publicUrl($path) : null;
                $row->submitted = $row->submission_id !== null;

                return $row;
            });

        return response()->json(['slot' => $slot, 'submissions' => $rows]);
    }

    public function deleteSubmissionSlot(Request $request, int $slotId): JsonResponse
    {
        if (! $this->isPresident($request->user())) {
            return response()->json(['message' => 'Only SK President can delete submission slots.'], 403);
        }

        $deleted = DB::table('submission_slots')
            ->where('slot_id', $slotId)
            ->delete();

        if ($deleted === 0) {
            return response()->json(['message' => 'Submission slot not found.'], 404);
        }

        return response()->json([
            'message' => 'Submission slot deleted.',
        ]);
    }

    public function consolidation(Request $request): JsonResponse
    {
        if (! $this->isPresident($request->user())) {
            return response()->json(['message' => 'Only SK President can view consolidated reports.'], 403);
        }

        $filters = $this->consolidationFilters($request);
        $submissions = $this->consolidatedSubmissions($filters);

        return response()->json([
            'filters' => $filters,
            'stats' => $this->consolidationStats($submissions),
            'submissions' => $submissions->values(),
            'years' => $this->consolidationYears(),
        ]);
    }

    // Notifications.
    public function markNotificationRead(Request $request, int $notificationId): JsonResponse
    {
        $updated = DB::table('notifications')
            ->where('notification_id', $notificationId)
            ->where('user_id', $request->user()->user_id)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        if ($updated === 0) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        return response()->json([
            'message' => 'Notification marked as read.',
        ]);
    }

    // Shared helpers: account data and role checks.
    protected function userPayload(User $user): array
    {
        return [
            'user_id' => $user->user_id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'role' => $user->role,
            'status' => $user->status,
            'is_verified' => (bool) $user->is_verified,
            'barangay_id' => $user->barangay_id,
            'barangay_name' => $user->barangay?->barangay_name,
            'profile_pic_url' => $user->profile_pic
                ? $this->publicUrl(Str::startsWith($user->profile_pic, 'uploads/')
                    ? $user->profile_pic
                    : 'uploads/profile_pics/'.$user->profile_pic)
                : null,
        ];
    }

    protected function isOfficial(User $user): bool
    {
        return in_array($user->role, self::OFFICIAL_ROLES, true);
    }

    protected function isPresident(User $user): bool
    {
        return $user->role === 'sk_president';
    }

    protected function contactChangeCacheKey(int $userId): string
    {
        return 'mobile_contact_change:'.$userId;
    }

    // Password reset only applies to non-archived official accounts.
    protected function findOfficialByEmail(string $email): ?User
    {
        return User::where('email', $email)
            ->whereIn('role', self::OFFICIAL_ROLES)
            ->whereNull('archived_at')
            ->first();
    }

    // Shared helpers: SMS verification and phone formatting.
    protected function twilioRequest(string $type, array $payload): array
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $serviceSid = config('services.twilio.verify_service_sid');

        if (! filled($sid) || ! filled($token) || ! filled($serviceSid)) {
            return [
                'ok' => false,
                'message' => 'Twilio is not configured. Add TWILIO_SID, TWILIO_AUTH_TOKEN, and TWILIO_VERIFY_SERVICE_SID to .env.',
            ];
        }

        $endpoint = $type === 'VerificationCheck'
            ? "https://verify.twilio.com/v2/Services/{$serviceSid}/VerificationCheck"
            : "https://verify.twilio.com/v2/Services/{$serviceSid}/Verifications";

        try {
            $response = Http::asForm()
                ->withBasicAuth($sid, $token)
                ->post($endpoint, $payload);

            return [
                'ok' => $response->successful(),
                'json' => $response->json() ?: [],
                'message' => $response->json('message') ?: 'Twilio request failed.',
            ];
        } catch (\Throwable $exception) {
            report($exception);

            return [
                'ok' => false,
                'message' => 'Failed to connect to Twilio. Please try again.',
            ];
        }
    }

    protected function findUserByPhone(string $phone): ?User
    {
        $target = $this->phoneDigits($phone);

        return User::whereIn('role', self::OFFICIAL_ROLES)
            ->whereNull('archived_at')
            ->whereNotNull('phone_number')
            ->get()
            ->first(fn (User $user) => $this->phoneNumbersMatch(
                $target,
                $this->phoneDigits((string) $user->phone_number)
            ));
    }

    protected function phoneNumbersMatch(string $target, string $stored): bool
    {
        if ($target === '' || $stored === '') {
            return false;
        }

        return $target === $stored || substr($target, -10) === substr($stored, -10);
    }

    protected function phoneDigits(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?: '';
    }

    protected function toE164Phone(string $phone): ?string
    {
        $digits = $this->phoneDigits($phone);

        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            return '+63'.substr($digits, 1);
        }

        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '+63'.$digits;
        }

        return null;
    }

    // Sync helpers: select and format each type of record.
    protected function announcements(User $user, ?Carbon $since): array
    {
        if (! Schema::hasTable('announcements')) {
            return [];
        }

        $query = DB::table('announcements')
            ->where(function (Builder $query) use ($user) {
                if (Schema::hasColumn('announcements', 'status')) {
                    $query->where(function (Builder $statusQuery) use ($user) {
                        $statusQuery->where('status', 'published')
                            ->orWhere('user_id', $user->user_id);
                    });
                }
                $query->where(function (Builder $visibilityQuery) use ($user) {
                    $visibilityQuery->where('visibility', 'public')
                        ->orWhere('user_id', $user->user_id);
                });

                if ($this->isOfficial($user)) {
                    $query->orWhere('visibility', 'officials_only');
                }
            });

        return $this->finish(
            $query,
            'announcements',
            $since,
            'announcement_id'
        );
    }

    protected function wallPosts(User $user, ?Carbon $since): array
    {
        if (! Schema::hasTable('announcements')) {
            return [];
        }

        $query = DB::table('announcements as a')
            ->leftJoin('users as u', 'a.user_id', '=', 'u.user_id')
            ->leftJoin('barangays as b', 'u.barangay_id', '=', 'b.barangay_id')
            ->where(function (Builder $visibilityQuery) use ($user) {
                $visibilityQuery->where('a.visibility', 'public');

                if ($this->isOfficial($user)) {
                    $visibilityQuery->orWhere('a.visibility', 'officials_only');
                }
            })
            ->when(Schema::hasColumn('announcements', 'status'), function (Builder $query) {
                $query->where('a.status', 'published');
            })
            ->select(
                'a.announcement_id',
                'a.user_id',
                'a.title',
                'a.content',
                'a.visibility',
                'a.created_at',
                'a.updated_at',
                'u.role',
                'b.barangay_name',
                DB::raw("CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as author_name")
            );

        $posts = $this->finish(
            $query,
            'announcements',
            $since,
            'a.announcement_id'
        );

        foreach ($posts as $post) {
            $post->likes_count = Schema::hasTable('wall_post_likes')
                ? DB::table('wall_post_likes')
                    ->where('announcement_id', $post->announcement_id)
                    ->count()
                : 0;

            $post->liked_by_current_user = Schema::hasTable('wall_post_likes')
                && DB::table('wall_post_likes')
                    ->where('announcement_id', $post->announcement_id)
                    ->where('user_id', $user->user_id)
                    ->exists();

            $post->author_name = trim((string) $post->author_name) ?: 'SK 360 Official';
        }

        return $posts;
    }

    // Keep email addresses out of mobile feedback responses for privacy.
    protected function feedback(User $user): array
    {
        if (! $this->isOfficial($user) || ! Schema::hasTable('announcement_feedback')) {
            return [];
        }

        return DB::table('announcement_feedback as f')
            ->leftJoin('announcements as a', 'a.announcement_id', '=', 'f.announcement_id')
            ->select(
                'f.feedback_id',
                'f.announcement_id',
                'f.name',
                'f.comment',
                'f.status',
                'f.verified_at',
                'f.created_at',
                'a.title as announcement_title'
            )
            ->whereIn('f.status', ['posted', 'hidden'])
            ->orderByDesc('f.created_at')
            ->limit(500)
            ->get()
            ->map(function ($row) {
                return [
                    'feedback_id' => $row->feedback_id,
                    'announcement_id' => $row->announcement_id,
                    'announcement_title' => $row->announcement_title ?: 'Announcement',
                    'name' => trim((string) $row->name) ?: 'Anonymous',
                    'comment' => $row->comment,
                    'status' => $row->status,
                    'verified_at' => $row->verified_at,
                    'created_at' => $row->created_at,
                ];
            })
            ->values()
            ->all();
    }

    protected function events(User $user, ?Carbon $since): array
    {
        if (! Schema::hasTable('events')) {
            return [];
        }

        $query = DB::table('events')
            ->where(function (Builder $query) use ($user) {
                $query->where('visibility', 'public')
                    ->orWhere('created_by', $user->user_id);

                if ($this->isOfficial($user)) {
                    $query->orWhere('visibility', 'officials_only');
                }
                if ($user->role === 'sk_chairman') {
                    $query->orWhere('visibility', 'chairman_only');
                }
                if ($user->role === 'sk_secretary') {
                    $query->orWhere('visibility', 'secretary_only');
                }
            });

        return $this->finish(
            $query,
            'events',
            $since,
            'event_id'
        );
    }

    protected function meetings(User $user, ?Carbon $since): array
    {
        if (! Schema::hasTable('meetings')) {
            return [];
        }

        $query = DB::table('meetings');

        if (! $this->isOfficial($user)) {
            $query->where('created_by', $user->user_id);
        }

        $meetings = $this->finish(
            $query,
            'meetings',
            $since,
            'meeting_id'
        );

        return array_map(function ($meeting) {
            $meeting->call_url = url("/sk_pres/meetings/{$meeting->meeting_id}/call");

            return $meeting;
        }, $meetings);
    }

    protected function notifications(User $user, ?Carbon $since): array
    {
        if (! Schema::hasTable('notifications')) {
            return [];
        }

        $query = DB::table('notifications')
            ->where('user_id', $user->user_id);

        return $this->finish(
            $query,
            'notifications',
            $since,
            'notification_id'
        );
    }

    protected function reportSubmissions(User $user, ?Carbon $since): array
    {
        if (! Schema::hasTable('report_submissions')) {
            return [];
        }

        $query = DB::table('report_submissions')
            ->where(function (Builder $query) use ($user) {
                $query->where('user_id', $user->user_id);

                if ($this->isPresident($user)) {
                    $query->orWhereNotNull('user_id');
                }
            });

        $rows = $this->finish(
            $query,
            'report_submissions',
            $since,
            'report_submission_id'
        );

        return array_map(function ($row) {
            $row->report_file_url = $this->publicUrl($row->report_file_path ?? null);

            return $row;
        }, $rows);
    }

    // Presidents see submitted reports; other officials also see their barangay's reports.
    protected function visibleReportQuery(string $table, User $user): Builder
    {
        return DB::table($table)
            ->select($table.'.*')
            ->where(function (Builder $query) use ($user, $table) {
                $query->where($table.'.user_id', $user->user_id);

                if ($this->isPresident($user)) {
                    $query->orWhereNotNull($table.'.user_id');
                } elseif ($this->isOfficial($user) && $user->barangay_id) {
                    $query->orWhere($table.'.barangay_id', $user->barangay_id);
                }
            });
    }

    protected function accomplishmentReports(User $user, ?Carbon $since): array
    {
        if (! Schema::hasTable('accomplishment_reports')) {
            return [];
        }

        $query = $this->visibleReportQuery('accomplishment_reports', $user);

        if (Schema::hasTable('submission_quality_reviews')) {
            $query->leftJoin('submission_quality_reviews as qr', function ($join) {
                $join->on('qr.source_id', '=', 'accomplishment_reports.report_id')
                    ->where('qr.source_type', '=', 'accomplishment_report');
            })->addSelect([
                'qr.status as quality_status',
                'qr.remarks as quality_remarks',
                'qr.reviewed_at as quality_reviewed_at',
            ]);
        }

        $rows = $this->finish(
            $query,
            'accomplishment_reports',
            $since,
            'report_id'
        );

        return array_map(function ($row) {
            $row->quality_status = $row->quality_status ?? 'pending';
            $row->uploaded_file_url = $this->publicUrl($row->uploaded_file_path ?? null);
            $row->generated_pdf_url = $this->publicUrl($row->generated_pdf_path ?? null);
            $reportId = $row->report_id ?? $row->accomplishment_report_id ?? null;
            $row->mobile_view_url = $reportId
                ? $this->mobileDocumentViewUrl('accomplishment_report', (int) $reportId)
                : null;

            return $row;
        }, $rows);
    }

    protected function budgetReports(User $user, ?Carbon $since): array
    {
        if (! Schema::hasTable('budget_reports')) {
            return [];
        }

        $query = $this->visibleReportQuery('budget_reports', $user);

        if (Schema::hasTable('submission_quality_reviews')) {
            $query->leftJoin('submission_quality_reviews as qr', function ($join) {
                $join->on('qr.source_id', '=', 'budget_reports.budget_report_id')
                    ->where('qr.source_type', '=', 'budget_report');
            })->addSelect([
                'qr.status as quality_status',
                'qr.remarks as quality_remarks',
                'qr.reviewed_at as quality_reviewed_at',
            ]);
        }

        $rows = $this->finish(
            $query,
            'budget_reports',
            $since,
            'budget_report_id'
        );

        return array_map(function ($row) {
            $row->quality_status = $row->quality_status ?? 'pending';
            $row->uploaded_file_url = $this->publicUrl($row->uploaded_file_path ?? null);
            $row->generated_pdf_url = $this->publicUrl($row->generated_pdf_path ?? null);

            return $row;
        }, $rows);
    }

    protected function leadershipProfiles(User $user, ?Carbon $since): array
    {
        $currentTerm = $this->mobileCurrentTerm();

        if (! $currentTerm) {
            return [];
        }

        $leaders = collect();
        $userProfilePicture = Schema::hasColumn('users', 'profile_pic')
            ? 'profile_pic'
            : DB::raw('NULL as profile_pic');

        $userLeaders = DB::table('users')
            ->join('official_terms as ot', 'ot.user_id', '=', 'users.user_id')
            ->where('ot.term_id', $currentTerm->term_id)
            ->whereIn('ot.status', ['pending', 'current'])
            ->whereColumn('ot.role', 'users.role')
            ->whereIn('users.role', ['sk_chairman', 'sk_secretary'])
            ->whereNotNull('users.barangay_id')
            ->select(
                DB::raw('ot.official_term_id as leadership_id'),
                'users.user_id',
                $userProfilePicture,
                'users.barangay_id',
                'users.email',
                DB::raw('users.phone_number as phone'),
                'users.is_verified',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as full_name"),
                DB::raw("
                    CASE
                        WHEN users.role = 'sk_chairman' THEN 'sk_chairman'
                        WHEN users.role = 'sk_secretary' THEN 'sk_secretary'
                        ELSE users.role
                    END as position
                "),
                DB::raw("'".$currentTerm->start_year.'-'.$currentTerm->end_year."' as term"),
                'ot.status'
            )
            ->get();

        $leaders = $leaders->merge($userLeaders);

        if (Schema::hasTable('sk_council')) {
            $councilRows = DB::table('sk_council')
                ->where('term_id', $currentTerm->term_id)
                ->where('status', 'current')
                ->select(
                    DB::raw('council_id as leadership_id'),
                    DB::raw('NULL as user_id'),
                    'profile_img as profile_pic',
                    'barangay_id',
                    DB::raw('name as full_name'),
                    'email',
                    'phone',
                    DB::raw("
                        CASE
                            WHEN LOWER(position) LIKE '%chairman%' THEN 'sk_chairman'
                            WHEN LOWER(position) LIKE '%secretary%' THEN 'sk_secretary'
                            WHEN LOWER(position) LIKE '%treasurer%' THEN 'sk_treasurer'
                            WHEN LOWER(position) LIKE '%councilor%' THEN 'sk_councilor'
                            WHEN LOWER(position) LIKE '%kagawad%' THEN 'sk_councilor'
                            ELSE LOWER(REPLACE(position, ' ', '_'))
                        END as position
                    "),
                    DB::raw("COALESCE(term, '2024-2026') as term"),
                    'status'
                )
                ->get();

            $leaders = $leaders->merge($councilRows);
        }

        if (Schema::hasTable('leadership_profiles')) {
            $joinedProfilePicture = Schema::hasColumn('users', 'profile_pic')
                ? 'u.profile_pic'
                : DB::raw('NULL as profile_pic');
            $profileRows = DB::table('leadership_profiles')
                ->leftJoin('users as u', 'leadership_profiles.user_id', '=', 'u.user_id')
                ->where('leadership_profiles.status', 'current')
                ->select(
                    'leadership_profiles.leadership_id',
                    'leadership_profiles.user_id',
                    'leadership_profiles.barangay_id',
                    'leadership_profiles.full_name',
                    'leadership_profiles.position',
                    'u.email',
                    DB::raw('u.phone_number as phone'),
                    'u.is_verified',
                    $joinedProfilePicture,
                    DB::raw("
                        CASE
                            WHEN leadership_profiles.term_start IS NOT NULL AND leadership_profiles.term_end IS NOT NULL
                                THEN CONCAT(YEAR(leadership_profiles.term_start), '-', YEAR(leadership_profiles.term_end))
                            WHEN leadership_profiles.term_start IS NOT NULL
                                THEN CONCAT(YEAR(leadership_profiles.term_start), '-present')
                            ELSE '2024-2026'
                        END as term
                    "),
                    'leadership_profiles.status'
                )
                ->get();

            $leaders = $leaders->merge($profileRows);
        }

        if (! $this->isPresident($user) && $user->barangay_id) {
            $leaders = $leaders->where('barangay_id', $user->barangay_id);
        }

        return $leaders
            ->filter(fn ($leader) => ! empty($leader->barangay_id))
            ->unique(fn ($leader) => strtolower(
                ($leader->full_name ?? '').'|'.
                ($leader->position ?? '').'|'.
                ($leader->barangay_id ?? '')
            ))
            ->values()
            ->map(function ($leader) {
                $path = $leader->profile_pic ?? null;
                $leader->profile_pic_url = $path && $path !== 'default.png'
                    ? $this->publicUrl(Str::startsWith($path, 'uploads/')
                        ? $path
                        : (($leader->user_id ?? null) !== null
                            ? 'uploads/profile_pics/'.$path
                            : 'uploads/council_profiles/'.$path))
                    : null;

                return $leader;
            })
            ->all();
    }

    protected function leadershipHistory(User $user): array
    {
        $terms = DB::table('administration_terms')
            ->where('status', 'completed')
            ->orderByDesc('start_year')
            ->orderByDesc('term_id')
            ->get();

        return $terms
            ->map(function ($term) use ($user) {
                $termLabel = $term->start_year.'-'.$term->end_year;
                $leaders = collect();

                $userProfilePicture = Schema::hasColumn('users', 'profile_pic')
                    ? 'users.profile_pic'
                    : DB::raw('NULL as profile_pic');

                $officials = DB::table('official_terms as ot')
                    ->join('users', 'ot.user_id', '=', 'users.user_id')
                    ->where('ot.term_id', $term->term_id)
                    ->whereIn('ot.role', ['sk_chairman', 'sk_secretary'])
                    ->select(
                        DB::raw('ot.official_term_id as leadership_id'),
                        'users.user_id',
                        $userProfilePicture,
                        'ot.barangay_id',
                        'users.email',
                        DB::raw('users.phone_number as phone'),
                        'users.is_verified',
                        DB::raw("CONCAT(users.first_name, ' ', users.last_name) as full_name"),
                        'ot.role as position',
                        DB::raw("'".$termLabel."' as term"),
                        'ot.status'
                    )
                    ->get();

                $leaders = $leaders->merge($officials);

                if (Schema::hasTable('sk_council')) {
                    $council = DB::table('sk_council')
                        ->where('term_id', $term->term_id)
                        ->select(
                            DB::raw('council_id as leadership_id'),
                            DB::raw('NULL as user_id'),
                            'profile_img as profile_pic',
                            'barangay_id',
                            DB::raw('name as full_name'),
                            'email',
                            'phone',
                            DB::raw("
                                CASE
                                    WHEN LOWER(position) LIKE '%chairman%' THEN 'sk_chairman'
                                    WHEN LOWER(position) LIKE '%secretary%' THEN 'sk_secretary'
                                    WHEN LOWER(position) LIKE '%treasurer%' THEN 'sk_treasurer'
                                    WHEN LOWER(position) LIKE '%councilor%' THEN 'sk_councilor'
                                    WHEN LOWER(position) LIKE '%kagawad%' THEN 'sk_councilor'
                                    ELSE LOWER(REPLACE(position, ' ', '_'))
                                END as position
                            "),
                            DB::raw("COALESCE(term, '".$termLabel."') as term"),
                            'status'
                        )
                        ->get();

                    $leaders = $leaders->merge($council);
                }

                if (! $this->isPresident($user) && $user->barangay_id) {
                    $leaders = $leaders->where('barangay_id', $user->barangay_id);
                }

                $members = $leaders
                    ->filter(fn ($leader) => ! empty($leader->barangay_id))
                    ->values()
                    ->map(function ($leader) {
                        $path = $leader->profile_pic ?? null;
                        $leader->profile_pic_url = $path && $path !== 'default.png'
                            ? $this->publicUrl(Str::startsWith($path, 'uploads/')
                                ? $path
                                : (($leader->user_id ?? null) !== null
                                    ? 'uploads/profile_pics/'.$path
                                    : 'uploads/council_profiles/'.$path))
                            : null;

                        return $leader;
                    });

                if ($members->isEmpty()) {
                    return null;
                }

                return [
                    'term_id' => $term->term_id,
                    'term' => $termLabel,
                    'status' => $term->status,
                    'completed_at' => $term->completed_at,
                    'members' => $members->all(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function resolveMobileLeadershipTerm(?string $term): ?object
    {
        $term = trim((string) $term);

        if ($term !== '' && preg_match('/(\\d{4})\\s*-\\s*(\\d{4})/', $term, $matches)) {
            $resolved = DB::table('administration_terms')
                ->where('start_year', $matches[1])
                ->where('end_year', $matches[2])
                ->first();

            if ($resolved) {
                return $resolved;
            }
        }

        return $this->mobileCurrentTerm();
    }

    protected function archiveDocuments(User $user, ?Carbon $since): array
    {
        $documents = collect();

        if (Schema::hasTable('accomplishment_reports')) {
            $query = DB::table('accomplishment_reports as ar')
                ->leftJoin('barangays as b', 'ar.barangay_id', '=', 'b.barangay_id')
                ->select(
                    DB::raw("'accomplishment_report' as source_type"),
                    'ar.report_id as source_id',
                    'ar.title',
                    'ar.barangay_id',
                    'b.barangay_name',
                    'ar.uploaded_file_path',
                    'ar.generated_pdf_path',
                    'ar.created_at'
                );

            if (! $this->isPresident($user) && $user->barangay_id) {
                $query->where('ar.barangay_id', $user->barangay_id);
            }

            $this->applySince(
                $query,
                'accomplishment_reports',
                $since
            );

            $documents = $documents->merge($query->get());
        }

        if (Schema::hasTable('budget_reports')) {
            $query = DB::table('budget_reports as br')
                ->leftJoin('barangays as b', 'br.barangay_id', '=', 'b.barangay_id')
                ->select(
                    DB::raw("'budget_report' as source_type"),
                    'br.budget_report_id as source_id',
                    'br.title',
                    'br.barangay_id',
                    'b.barangay_name',
                    'br.uploaded_file_path',
                    'br.generated_pdf_path',
                    'br.created_at'
                );

            if (! $this->isPresident($user) && $user->barangay_id) {
                $query->where('br.barangay_id', $user->barangay_id);
            }

            $this->applySince(
                $query,
                'budget_reports',
                $since
            );

            $documents = $documents->merge($query->get());
        }

        return $documents
            ->sortByDesc('created_at')
            ->take(500)
            ->map(function ($row) {
                $path = $row->uploaded_file_path ?? $row->generated_pdf_path ?? null;

                $row->file_url = $this->publicUrl($path);
                $row->document_type = $row->source_type === 'budget_report'
                    ? 'Budget'
                    : 'Report';

                return $row;
            })
            ->values()
            ->all();
    }

    // Shared helpers: sync dates, ordering, and file links.
    protected function tableRows(string $table, ?Carbon $since, string $orderColumn): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        return $this->finish(
            DB::table($table),
            $table,
            $since,
            $orderColumn
        );
    }

    protected function finish(Builder $query, string $table, ?Carbon $since, string $orderColumn): array
    {
        $this->applySince($query, $table, $since);

        if ($table === 'announcements') {
            $createdColumn = str_contains($orderColumn, '.')
                ? substr($orderColumn, 0, strrpos($orderColumn, '.')).'.created_at'
                : 'created_at';

            $query->orderByDesc($createdColumn)
                ->orderByDesc($orderColumn);
        } else {
            $query->orderBy($orderColumn);
        }

        return $query
            ->limit(500)
            ->get()
            ->all();
    }

    protected function applySince(Builder $query, string $table, ?Carbon $since): void
    {
        if (! $since) {
            return;
        }

        foreach (['updated_at', 'created_at', 'submitted_at', 'start_datetime'] as $column) {
            if (Schema::hasColumn($table, $column)) {
                $query->where($column, '>', $since);

                return;
            }
        }
    }

    protected function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        // Build the link from the current API request so a phone does not
        // receive a useless 127.0.0.1 URL from the server's APP_URL.
        return rtrim(request()->getSchemeAndHttpHost(), '/').'/'.ltrim($path, '/');
    }

    protected function mobileDocumentViewUrl(string $sourceType, int $sourceId): string
    {
        return URL::temporarySignedRoute(
            'mobile.document.view',
            now()->addMinutes(30),
            ['sourceType' => $sourceType, 'sourceId' => $sourceId],
        );
    }

    // Submission helpers: prepare each report and save it.
    protected function saveMobileAccomplishmentSubmission(User $user, object $slot, array $validated): int
    {
        $year = (int) ($validated['reporting_year'] ?? now()->year);
        $month = (int) ($validated['reporting_month'] ?? now()->month);
        $reportType = $validated['report_type'] ?? 'monthly';

        $data = [
            ...$this->mobileTermData('accomplishment_reports', $slot->term_id ?? null),
            'user_id' => $user->user_id,
            'barangay_id' => $user->barangay_id,
            'slot_id' => $slot->slot_id,
            'report_type' => $reportType,
            'submission_method' => 'file_upload',
            'title' => $slot->title,
            'reporting_year' => $year,
            'reporting_month' => $reportType === 'monthly' ? $month : null,
            'reporting_quarter' => $reportType === 'quarterly'
                ? ($validated['reporting_quarter'] ?? 'Q1')
                : null,
            'generated_pdf_path' => null,
            'uploaded_file_name' => $validated['uploaded_file_name'] ?? null,
            'uploaded_file_path' => $validated['uploaded_file_path'] ?? null,
            'status' => 'submitted',
            'remarks' => $validated['remarks'] ?? null,
            'submitted_at' => now(),
            'created_at' => now(),
        ];

        return $this->saveSlotReport('accomplishment_reports', 'report_id', $data);
    }

    protected function saveMobileBudgetSubmission(User $user, object $slot, array $validated): int
    {
        $reportType = $validated['report_type'] ?? 'annual';
        $year = (int) ($validated['reporting_year'] ?? now()->year);

        $data = [
            ...$this->mobileTermData('budget_reports', $slot->term_id ?? null),
            'user_id' => $user->user_id,
            'barangay_id' => $user->barangay_id,
            'slot_id' => $slot->slot_id,
            'submission_method' => 'file_upload',
            'document_type' => 'financial_record',
            'fiscal_year' => $year,
            'title' => $slot->title,
            'generated_pdf_path' => null,
            'template_data' => null,
            'uploaded_file_name' => $validated['uploaded_file_name'] ?? null,
            'uploaded_file_path' => $validated['uploaded_file_path'] ?? null,
            'total_amount' => 0,
            'status' => 'recorded',
            'submitted_at' => now(),
            'created_at' => now(),
        ];

        if (Schema::hasColumn('budget_reports', 'budget_period_type')) {
            $data['budget_period_type'] = $reportType;
            $data['fiscal_month'] = $reportType === 'monthly'
                ? ($validated['reporting_month'] ?? now()->month)
                : null;

            $data['fiscal_quarter'] = $reportType === 'quarterly'
                ? ($validated['reporting_quarter'] ?? 'Q1')
                : null;
        }

        return $this->saveSlotReport('budget_reports', 'budget_report_id', $data);
    }

    // New web pages filter by term; keep mobile-created records visible there.
    protected function mobileTermData(string $table, ?int $termId = null): array
    {
        if (! Schema::hasColumn($table, 'term_id')) {
            return [];
        }

        $termId ??= $this->currentTermId();
        abort_unless($termId, 422, 'There is no active administration term.');

        return ['term_id' => $termId];
    }

    protected function mobileCurrentTerm(): ?object
    {
        return DB::table('administration_terms')
            ->where('status', 'current')
            ->orderByDesc('term_id')
            ->first();
    }

    // Reuse the existing report ID when a barangay submits to the same slot again.
    protected function saveSlotReport(string $table, string $idColumn, array $data): int
    {
        $existing = DB::table($table)
            ->where('barangay_id', $data['barangay_id'])
            ->where('slot_id', $data['slot_id'])
            ->first();

        if ($existing) {
            DB::table($table)
                ->where($idColumn, $existing->{$idColumn})
                ->update($data);

            return (int) $existing->{$idColumn};
        }

        return (int) DB::table($table)->insertGetId($data, $idColumn);
    }

    // Meeting helpers: call tokens and display details.
    protected function buildAgoraTokenResponse(Meeting $meeting, ?int $uid = null): JsonResponse
    {
        $appId = config('services.agora.app_id');
        $appCertificate = config('services.agora.app_certificate');

        if (! filled($appId) || ! filled($appCertificate)) {
            return response()->json([
                'message' => 'Agora is not configured. Set AGORA_APP_ID and AGORA_APP_CERTIFICATE in .env.',
            ], 500);
        }

        $uid = $uid && $uid > 0 ? $uid : random_int(1000, 999999);
        $channel = 'meeting-'.$meeting->meeting_id;

        try {
            $token = RtcTokenBuilder::buildTokenWithUid(
                $appId,
                $appCertificate,
                $channel,
                $uid,
                RtcTokenBuilder::RolePublisher,
                now()->addHours(4)->timestamp
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Failed to generate Agora RTC token.',
            ], 500);
        }

        return response()->json([
            'appId' => $appId,
            'token' => $token,
            'channel' => $channel,
            'uid' => $uid,
            'title' => $meeting->title,
        ]);
    }

    protected function meetingUnavailable(Meeting $meeting): bool
    {
        // A scheduled meeting remains joinable after its start time until the
        // President explicitly ends it. Checking scheduled_at here prevented
        // participants from joining once the meeting actually began.
        return $meeting->status === 'completed';
    }

    protected function decorateMobileMeeting(Meeting $meeting): Meeting
    {
        $scheduledAt = $meeting->scheduled_at;

        $meeting->scheduled_at = $scheduledAt;
        $meeting->ends_at = $scheduledAt->copy()->addHour();
        $meeting->display_datetime = $scheduledAt->format('Y-m-d h:i A');
        $meeting->preview_datetime = $scheduledAt->format('M d, Y h:i A');

        $meeting->status_label = match ($meeting->status) {
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => $meeting->scheduled_at->isFuture() ? 'Upcoming' : 'Ready',
        };

        return $meeting;
    }

    // Consolidation helpers: filters, totals, and available years.
    protected function consolidationFilters(Request $request): array
    {
        $year = (int) $request->query('year', now()->year);
        $period = (string) $request->query('period', 'all');
        $month = (int) $request->query('month', now()->month);
        $quarter = (string) $request->query(
            'quarter',
            'Q'.ceil(now()->month / 3)
        );

        if (! in_array($period, ['all', 'monthly', 'quarterly', 'annual'], true)) {
            $period = 'all';
        }

        return [
            'year' => $year > 2000 && $year < 2100 ? $year : now()->year,
            'period' => $period,
            'month' => $month >= 1 && $month <= 12 ? $month : now()->month,
            'quarter' => in_array($quarter, ['Q1', 'Q2', 'Q3', 'Q4'], true)
                ? $quarter
                : 'Q'.ceil(now()->month / 3),
        ];
    }

    protected function consolidatedSubmissions(array $filters)
    {
        $reports = Schema::hasTable('accomplishment_reports')
            ? DB::table('accomplishment_reports')
                ->where('reporting_year', $filters['year'])
                ->when(
                    $filters['period'] === 'monthly',
                    fn ($query) => $query
                        ->where('report_type', 'monthly')
                        ->where('reporting_month', $filters['month'])
                )
                ->when(
                    $filters['period'] === 'quarterly',
                    fn ($query) => $query
                        ->where('report_type', 'quarterly')
                        ->where('reporting_quarter', $filters['quarter'])
                )
                ->when(
                    $filters['period'] === 'annual',
                    fn ($query) => $query->where('report_type', 'annual')
                )
                ->get()
                ->groupBy('barangay_id')
            : collect();

        $hasBudgetPeriods = Schema::hasTable('budget_reports')
            && Schema::hasColumn('budget_reports', 'budget_period_type');

        $budgets = Schema::hasTable('budget_reports')
            ? DB::table('budget_reports')
                ->where('fiscal_year', $filters['year'])
                ->when(
                    $hasBudgetPeriods && $filters['period'] === 'monthly',
                    fn ($query) => $query
                        ->where('budget_period_type', 'monthly')
                        ->where('fiscal_month', $filters['month'])
                )
                ->when(
                    $hasBudgetPeriods && $filters['period'] === 'quarterly',
                    fn ($query) => $query
                        ->where('budget_period_type', 'quarterly')
                        ->where('fiscal_quarter', $filters['quarter'])
                )
                ->when(
                    $hasBudgetPeriods && $filters['period'] === 'annual',
                    fn ($query) => $query->where('budget_period_type', 'annual')
                )
                ->get()
                ->groupBy('barangay_id')
            : collect();

        return DB::table('barangays')
            ->orderBy('barangay_name')
            ->get(['barangay_id', 'barangay_name'])
            ->map(function ($barangay) use ($reports, $budgets, $hasBudgetPeriods) {
                $reportItems = $reports->get($barangay->barangay_id, collect());
                $budgetItems = $budgets->get($barangay->barangay_id, collect());

                $monthlyReports = $reportItems->where('report_type', 'monthly')->count();
                $quarterlyReports = $reportItems->where('report_type', 'quarterly')->count();
                $annualReports = $reportItems->where('report_type', 'annual')->count();

                $monthlyBudgets = $hasBudgetPeriods
                    ? $budgetItems->where('budget_period_type', 'monthly')->count()
                    : 0;

                $quarterlyBudgets = $hasBudgetPeriods
                    ? $budgetItems->where('budget_period_type', 'quarterly')->count()
                    : 0;

                $annualBudgets = $hasBudgetPeriods
                    ? $budgetItems->where('budget_period_type', 'annual')->count()
                    : $budgetItems->count();

                $allItems = $reportItems->merge($budgetItems);
                $lastSubmission = $allItems->sortByDesc('submitted_at')->first();

                return [
                    'barangay_id' => $barangay->barangay_id,
                    'barangay' => $barangay->barangay_name,
                    'monthly_count' => $monthlyReports + $monthlyBudgets,
                    'quarterly_count' => $quarterlyReports + $quarterlyBudgets,
                    'annual_count' => $annualReports + $annualBudgets,
                    'monthly' => $this->consolidationStatusLabel(
                        $monthlyReports + $monthlyBudgets,
                        $monthlyReports,
                        $monthlyBudgets
                    ),
                    'quarterly' => $this->consolidationStatusLabel(
                        $quarterlyReports + $quarterlyBudgets,
                        $quarterlyReports,
                        $quarterlyBudgets
                    ),
                    'annual' => $this->consolidationStatusLabel(
                        $annualReports + $annualBudgets,
                        $annualReports,
                        $annualBudgets
                    ),
                    'last_submission' => $lastSubmission?->submitted_at
                        ? date(
                            'M d, Y h:i A',
                            strtotime((string) $lastSubmission->submitted_at)
                        )
                        : 'No submission',
                    'status' => $allItems->isNotEmpty() ? 'submitted' : 'pending',
                ];
            });
    }

    protected function consolidationStats($submissions): array
    {
        $total = $submissions->count();
        $submitted = $submissions->where('status', 'submitted')->count();

        return [
            'total_barangays' => $total,
            'submitted' => $submitted,
            'pending' => max($total - $submitted, 0),
            'late' => 0,
        ];
    }

    protected function consolidationStatusLabel(int $count, int $reportCount = 0, int $budgetCount = 0): string
    {
        if ($count <= 0) {
            return 'Pending';
        }

        return $count.' submitted (R: '.$reportCount.', B: '.$budgetCount.')';
    }

    protected function consolidationYears(): array
    {
        $reportYears = Schema::hasTable('accomplishment_reports')
            ? DB::table('accomplishment_reports')
                ->select('reporting_year')
                ->distinct()
                ->pluck('reporting_year')
                ->map(fn ($year) => (int) $year)
            : collect();

        $budgetYears = Schema::hasTable('budget_reports')
            ? DB::table('budget_reports')
                ->select('fiscal_year')
                ->distinct()
                ->pluck('fiscal_year')
                ->map(fn ($year) => (int) $year)
            : collect();

        $years = $reportYears
            ->merge($budgetYears)
            ->filter()
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        return $years ?: [now()->year];
    }

    // Ranking helpers: current leaderboard and past periods.
    protected function mobileRankings(): array
    {
        $rows = collect($this->mobileRankingRows($this->rankingsLeaderboard()));
        if (Schema::hasTable('barangays')) {
            $existing = $rows->pluck('barangay_id')->map(fn ($id) => (string) $id)->all();
            $missing = DB::table('barangays')
                ->orderBy('barangay_name')
                ->get(['barangay_id', 'barangay_name'])
                ->reject(fn ($barangay) => in_array((string) $barangay->barangay_id, $existing, true))
                ->map(fn ($barangay) => [
                    'barangay_id' => $barangay->barangay_id,
                    'barangay_name' => $barangay->barangay_name,
                    'rank' => null,
                    'total_points' => 0,
                    'timely_submission_points' => 0,
                    'completeness_points' => 0,
                    'participation_points' => 0,
                    'on_time' => 0,
                    'completion' => 0,
                    'engagement' => 0,
                    'trend' => '—',
                ]);
            $rows = $rows->concat($missing);
        }

        return $rows->values()->all();
    }

    protected function mobileRankingHistory(): array
    {
        return $this->rankingPeriods()
            ->map(fn (string $period) => [
                'period' => $period,
                'rankings' => array_slice(
                    $this->mobileRankingRows($this->rankingsLeaderboard($period)),
                    0,
                    3
                ),
            ])
            ->values()
            ->all();
    }

    protected function mobileRankingRows($leaderboard): array
    {
        return $leaderboard
            ->map(function ($row) {
                return [
                    'barangay_id' => $row->barangay_id,
                    'barangay_name' => $row->name,
                    'rank' => $row->rank,
                    'total_points' => $row->points,
                    'timely_submission_points' => $row->timely_submission_points,
                    'completeness_points' => $row->completeness_points,
                    'participation_points' => $row->participation_points,
                    'on_time' => $row->on_time,
                    'completion' => $row->completion,
                    'engagement' => $row->engagement,
                    'trend' => $row->trend,
                ];
            })
            ->values()
            ->all();
    }
}
