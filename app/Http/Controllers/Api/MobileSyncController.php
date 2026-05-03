<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailVerification;
use App\Models\Meeting;
use App\Models\MobileApiToken;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\RankingPointsService;
use App\Http\Controllers\Concerns\BuildsRankingsData;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use TaylanUnutmaz\AgoraTokenBuilder\RtcTokenBuilder;

class MobileSyncController extends Controller
{
     use BuildsRankingsData;
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

        $user = User::with('barangay')->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid email or password.'], 422);
        }

        if ($user->status !== 'active' || ! $user->is_verified) {
            return response()->json(['message' => 'Account is not active or verified.'], 403);
        }

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

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'phone_number' => ['required', 'regex:/^\d{10,11}$/'],
            'barangay_id' => ['required', 'exists:barangays,barangay_id'],
            'password' => ['required', 'confirmed', 'min:8', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/'],
        ], [
            'email.unique' => 'Email is already registered.',
            'phone_number.regex' => 'Phone number must be 10-11 digits.',
            'password.confirmed' => 'Passwords do not match.',
            'barangay_id.exists' => 'Selected barangay is invalid.',
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'first_name' => trim($validated['first_name']),
                'last_name' => trim($validated['last_name']),
                'email' => trim($validated['email']),
                'phone_number' => trim($validated['phone_number']),
                'password' => $validated['password'],
                'barangay_id' => $validated['barangay_id'],
                'role' => 'youth',
                'is_verified' => 0,
                'status' => 'inactive',
            ]);

            $verificationCode = (string) random_int(100000, 999999);

            EmailVerification::where('user_id', $user->user_id)->delete();
            EmailVerification::create([
                'user_id' => $user->user_id,
                'verification_code' => $verificationCode,
                'expires_at' => now()->addHour(),
                'created_at' => now(),
            ]);

            DB::commit();

            $this->sendVerificationCode($user, $verificationCode);

            return response()->json([
                'message' => 'Account created. Verification code sent.',
                'user_id' => $user->user_id,
                'email' => $user->email,
            ], 201);
        } catch (\Throwable $exception) {
            DB::rollBack();
            report($exception);

            return response()->json([
                'message' => 'Registration failed. Please try again.',
            ], 500);
        }
    }

    public function verifyRegistration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $verification = EmailVerification::where('user_id', $user->user_id)
            ->where('verification_code', $validated['code'])
            ->where('expires_at', '>', now())
            ->latest('verification_id')
            ->first();

        if (! $verification) {
            return response()->json(['message' => 'Invalid or expired code.'], 422);
        }

        $user->forceFill([
            'is_verified' => 1,
            'status' => 'active',
        ])->save();

        EmailVerification::where('user_id', $user->user_id)->delete();

        return response()->json([
            'message' => 'Account verified. You can now sign in.',
        ]);
    }

    public function resendVerificationCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($user->is_verified) {
            return response()->json(['message' => 'Account is already verified.']);
        }

        $verificationCode = (string) random_int(100000, 999999);

        EmailVerification::updateOrCreate(
            ['user_id' => $user->user_id],
            [
                'verification_code' => $verificationCode,
                'expires_at' => now()->addHour(),
                'created_at' => now(),
            ]
        );

        $this->sendVerificationCode($user, $verificationCode);

        return response()->json(['message' => 'Verification code resent.']);
    }

    public function requestPasswordReset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'method' => ['required', 'in:email,phone'],
            'email' => ['nullable', 'required_if:method,email', 'email'],
            'phone' => ['nullable', 'required_if:method,phone', 'string', 'max:30'],
        ]);

        if ($validated['method'] === 'email') {
            $user = User::where('email', $validated['email'])->first();
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
            'method' => ['required', 'in:email,phone'],
            'target' => ['required', 'string', 'max:255'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', 'min:8', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/'],
        ], [
            'password.confirmed' => 'Passwords do not match.',
        ]);

        if ($validated['method'] === 'email') {
            $user = User::where('email', $validated['target'])->first();
            $reset = DB::table('password_reset_tokens')->where('email', $validated['target'])->first();

            if (! $user || ! $reset || ! Hash::check($validated['code'], $reset->token) || now()->subMinutes(15)->greaterThan($reset->created_at)) {
                return response()->json(['message' => 'Invalid or expired reset code.'], 422);
            }

            $user->update(['password' => Hash::make($validated['password'])]);
            DB::table('password_reset_tokens')->where('email', $validated['target'])->delete();

            return response()->json(['message' => 'Your password has been reset. You can now log in.']);
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

        $user->update(['password' => Hash::make($validated['password'])]);

        return response()->json(['message' => 'Your password has been reset. You can now log in.']);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('mobile_api_token')?->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()->loadMissing('barangay')),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
        ]);

        $user = $request->user();
        $user->update([
            'first_name' => trim($validated['first_name']),
            'last_name' => trim($validated['last_name']),
        ]);

        return response()->json([
            'message' => 'Profile updated successfully.',
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

        $user->update(['password' => $validated['password']]);

        return response()->json(['message' => 'Password updated successfully.']);
    }

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
            'events' => $this->events($user, $since),
            'meetings' => $this->meetings($user, $since),
            'notifications' => $this->notifications($user, $since),
            'rankings' => $this->mobileRankings(),
'ranking_point_system' => $this->rankingPointSystem(),
'latest_ranking_period' => $this->latestRankingPeriod(),
            'submission_slots' => $this->tableRows('submission_slots', $since, 'slot_id'),
            'report_submissions' => $this->reportSubmissions($user, $since),
            'accomplishment_reports' => $this->accomplishmentReports($user, $since),
            'budget_reports' => $this->budgetReports($user, $since),
            'leadership_profiles' => $this->leadershipProfiles($user, $since),
            'archive_documents' => $this->archiveDocuments($user, $since),
        ]);
    }

    public function storeWallPost(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'post_content' => ['required', 'string', 'max:5000'],
            'post_category' => ['nullable', 'string', 'max:50'],
        ]);

        $category = strtolower($validated['post_category'] ?? 'update');
        if ($category === 'announcement' && ! $this->isPresident($request->user())) {
            return response()->json(['message' => 'Only SK President can create announcements.'], 403);
        }

        $title = match ($category) {
            'announcement' => 'Announcement',
            'event' => 'Event Update',
            'accomplishment' => 'Accomplishment',
            default => 'Community Update',
        };

        $announcementId = DB::table('announcements')->insertGetId([
            'user_id' => $request->user()->user_id,
            'title' => $title,
            'content' => $validated['post_content'],
            'visibility' => 'public',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'announcement_id');

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
            'announcement' => DB::table('announcements')->where('announcement_id', $announcementId)->first(),
        ], 201);
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
            'visibility' => ['nullable', 'in:public,officials_only'],
        ]);

        $eventId = DB::table('events')->insertGetId([
            'created_by' => $request->user()->user_id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
            'event_type' => $validated['event_type'] ?? 'event',
            'start_datetime' => Carbon::parse($validated['start_datetime']),
            'end_datetime' => isset($validated['end_datetime'])
                ? Carbon::parse($validated['end_datetime'])
                : Carbon::parse($validated['start_datetime'])->copy()->addHour(),
            'visibility' => $validated['visibility'] ?? 'public',
            'created_at' => now(),
        ], 'event_id');

        return response()->json([
            'message' => 'Event created.',
            'event' => DB::table('events')->where('event_id', $eventId)->first(),
        ], 201);
    }

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
            'title' => $validated['title'],
            'agenda' => $validated['agenda'] ?? null,
            'meeting_date' => $validated['meeting_date'],
            'meeting_time' => $validated['meeting_time'] . ':00',
            'location_or_link' => null,
            'dyte_meeting_id' => null,
            'created_by' => $request->user()->user_id,
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'meeting_id');

        $meeting = DB::table('meetings')->where('meeting_id', $meetingId)->first();
        $meeting->call_url = url("/sk_pres/meetings/{$meetingId}/call");

        return response()->json([
            'message' => 'Meeting scheduled successfully.',
            'meeting' => $meeting,
        ], 201);
    }

    public function meetingJoinUrl(Request $request, Meeting $meeting): JsonResponse
    {
        if (! $this->isOfficial($request->user())) {
            return response()->json(['message' => 'Only SK officials can join meetings.'], 403);
        }

        return response()->json([
            'join_url' => URL::temporarySignedRoute(
                'mobile.meetings.call',
                now()->addHours(4),
                ['meeting' => $meeting->meeting_id]
            ),
        ]);
    }

    public function mobileMeetingCall(Meeting $meeting): View
    {
        return view('sk_pres.video-call', [
            'fullName' => 'Mobile Participant',
            'menuItems' => [],
            'currentUrl' => url('/'),
            'meeting' => $this->decorateMobileMeeting($meeting),
            'channelName' => 'meeting-' . $meeting->meeting_id,
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

    public function meetingAgoraToken(Request $request, Meeting $meeting): JsonResponse
    {
        if (! $this->isOfficial($request->user())) {
            return response()->json(['message' => 'Only SK officials can join meetings.'], 403);
        }

        return $this->buildAgoraTokenResponse($meeting, (int) $request->user()->user_id);
    }

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
        $channel = 'meeting-' . $meeting->meeting_id;

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

            return response()->json(['message' => 'Failed to generate Agora RTC token.'], 500);
        }

        return response()->json([
            'appId' => $appId,
            'token' => $token,
            'channel' => $channel,
            'uid' => $uid,
            'title' => $meeting->title,
        ]);
    }

    public function chatUsers(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->query('search', ''));
        $user = $request->user();
        $chatRoles = $user->role === 'sk_president'
            ? ['sk_chairman', 'sk_secretary']
            : ['sk_president', 'sk_chairman', 'sk_secretary'];

        $query = DB::table('users as u')
            ->leftJoin('barangays as b', 'u.barangay_id', '=', 'b.barangay_id')
            ->select(
                'u.user_id',
                'u.first_name',
                'u.last_name',
                'u.email',
                'u.role',
                'b.barangay_name'
            )
            ->where('u.user_id', '!=', $user->user_id)
            ->where('u.status', '=', 'active')
            ->whereIn('u.role', $chatRoles);

        if ($keyword !== '') {
            $query->where(function ($query) use ($keyword) {
                $query
                    ->whereRaw("CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) LIKE ?", ["%{$keyword}%"])
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
                    'name' => trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')) ?: $row->email,
                    'email' => $row->email,
                    'role' => $row->role,
                    'barangay' => $row->barangay_name,
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
        ]);

        $councilId = DB::table('sk_council')->insertGetId([
            'barangay_id' => $user->barangay_id,
            'name' => trim($validated['name']),
            'position' => 'SK Councilor',
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'term' => filled($validated['term'] ?? null) ? $validated['term'] : '2023-2026',
            'profile_img' => 'default.png',
            'created_at' => now(),
        ], 'council_id');

        return response()->json([
            'message' => 'SK council member added.',
            'council_member' => DB::table('sk_council')->where('council_id', $councilId)->first(),
        ], 201);
    }

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

        $now = now();
        if (Carbon::parse($slot->start_date)->startOfDay()->gt($now) || Carbon::parse($slot->end_date)->endOfDay()->lt($now)) {
            return response()->json(['message' => 'That submission slot is not active today.'], 422);
        }

        $directoryName = $validated['submission_type'] === 'budget_report'
            ? 'budget_reports'
            : 'reports';
        $prefix = $validated['submission_type'] === 'budget_report' ? 'BUD' : 'REP';
        $directory = public_path("uploads/{$directoryName}");
        File::ensureDirectoryExists($directory);

        $file = $request->file('report_file');
        $filename = $prefix . '_' . time() . '_' . $user->barangay_id . '.pdf';
        $file->move($directory, $filename);
        $validated['uploaded_file_name'] = $file->getClientOriginalName();
        $validated['uploaded_file_path'] = "uploads/{$directoryName}/{$filename}";

        if ($validated['submission_type'] === 'budget_report') {
            $sourceId = $this->saveMobileBudgetSubmission($user, $slot, $validated);
            $sourceType = 'budget_report';
            $row = DB::table('budget_reports')->where('budget_report_id', $sourceId)->first();
        } else {
            $sourceId = $this->saveMobileAccomplishmentSubmission($user, $slot, $validated);
            $sourceType = 'accomplishment_report';
            $row = DB::table('accomplishment_reports')->where('report_id', $sourceId)->first();
        }

        $isOnTime = $now->lessThanOrEqualTo(Carbon::parse($slot->end_date)->endOfDay());
        $points->award((int) $user->barangay_id, $isOnTime ? RankingPointsService::ON_TIME_REPORT_SUBMISSION : RankingPointsService::LATE_SUBMISSION, $sourceType, $sourceId, (int) $user->user_id);
        $points->award((int) $user->barangay_id, RankingPointsService::QUALITY_DOCUMENTATION, $sourceType, $sourceId, (int) $user->user_id);

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
            'slot' => DB::table('submission_slots')->where('slot_id', $slotId)->first(),
        ], 201);
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

        return response()->json(['message' => 'Submission slot deleted.']);
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

        return response()->json(['message' => 'Notification marked as read.']);
    }

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
            'profile_pic_url' => $this->publicUrl($user->profile_pic ?? null),
        ];
    }

    protected function sendVerificationCode(User $user, string $verificationCode): void
    {
        Mail::send('email.verification-code', [
            'first_name' => $user->first_name,
            'verification_code' => $verificationCode,
        ], function ($message) use ($user) {
            $message->to($user->email, trim($user->first_name.' '.$user->last_name))
                ->subject('SK360 Verification Code');
        });
    }

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

        return User::whereNotNull('phone_number')->get()
            ->first(fn (User $user) => $this->phoneNumbersMatch($target, $this->phoneDigits((string) $user->phone_number)));
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

    protected function announcements(User $user, ?Carbon $since): array
    {
        if (! Schema::hasTable('announcements')) {
            return [];
        }

        $query = DB::table('announcements')
            ->where(function (Builder $query) use ($user) {
                $query->where('visibility', 'public')
                    ->orWhere('user_id', $user->user_id);

                if ($this->isOfficial($user)) {
                    $query->orWhere('visibility', 'officials_only');
                }
            });

        return $this->finish($query, 'announcements', $since, 'announcement_id');
    }

    protected function wallPosts(User $user, ?Carbon $since): array
    {
        if (! Schema::hasTable('announcements')) {
            return [];
        }

        $query = DB::table('announcements as a')
            ->leftJoin('users as u', 'a.user_id', '=', 'u.user_id')
            ->leftJoin('barangays as b', 'u.barangay_id', '=', 'b.barangay_id')
            ->where('a.visibility', 'public')
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

        $posts = $this->finish($query, 'announcements', $since, 'a.announcement_id');

        foreach ($posts as $post) {
            $post->likes_count = Schema::hasTable('wall_post_likes')
                ? DB::table('wall_post_likes')->where('announcement_id', $post->announcement_id)->count()
                : 0;
            $post->liked_by_current_user = Schema::hasTable('wall_post_likes')
                && DB::table('wall_post_likes')
                    ->where('announcement_id', $post->announcement_id)
                    ->where('user_id', $user->user_id)
                    ->exists();
            $post->author_name = trim((string) $post->author_name) ?: 'SK 360 User';
        }

        return $posts;
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
            });

        return $this->finish($query, 'events', $since, 'event_id');
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

        $meetings = $this->finish($query, 'meetings', $since, 'meeting_id');

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

        return $this->finish($query, 'notifications', $since, 'notification_id');
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

        $rows = $this->finish($query, 'report_submissions', $since, 'report_submission_id');

        return array_map(function ($row) {
            $row->report_file_url = $this->publicUrl($row->report_file_path ?? null);

            return $row;
        }, $rows);
    }

    protected function accomplishmentReports(User $user, ?Carbon $since): array
    {
        if (! Schema::hasTable('accomplishment_reports')) {
            return [];
        }

        $query = DB::table('accomplishment_reports')
            ->where(function (Builder $query) use ($user) {
                $query->where('user_id', $user->user_id);

                if ($this->isPresident($user)) {
                    $query->orWhereNotNull('user_id');
                } elseif ($this->isOfficial($user) && $user->barangay_id) {
                    $query->orWhere('barangay_id', $user->barangay_id);
                }
            });

        $rows = $this->finish($query, 'accomplishment_reports', $since, 'report_id');

        return array_map(function ($row) {
            $row->uploaded_file_url = $this->publicUrl($row->uploaded_file_path ?? null);
            $row->generated_pdf_url = $this->publicUrl($row->generated_pdf_path ?? null);

            return $row;
        }, $rows);
    }

    protected function budgetReports(User $user, ?Carbon $since): array
    {
        if (! Schema::hasTable('budget_reports')) {
            return [];
        }

        $query = DB::table('budget_reports')
            ->where(function (Builder $query) use ($user) {
                $query->where('user_id', $user->user_id);

                if ($this->isPresident($user)) {
                    $query->orWhereNotNull('user_id');
                } elseif ($this->isOfficial($user) && $user->barangay_id) {
                    $query->orWhere('barangay_id', $user->barangay_id);
                }
            });

        $rows = $this->finish($query, 'budget_reports', $since, 'budget_report_id');

        return array_map(function ($row) {
            $row->uploaded_file_url = $this->publicUrl($row->uploaded_file_path ?? null);
            $row->generated_pdf_url = $this->publicUrl($row->generated_pdf_path ?? null);

            return $row;
        }, $rows);
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

            $this->applySince($query, 'accomplishment_reports', $since);
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

            $this->applySince($query, 'budget_reports', $since);
            $documents = $documents->merge($query->get());
        }

        return $documents
            ->sortByDesc('created_at')
            ->take(500)
            ->map(function ($row) {
                $path = $row->uploaded_file_path ?? $row->generated_pdf_path ?? null;
                $row->file_url = $this->publicUrl($path);
                $row->document_type = $row->source_type === 'budget_report' ? 'Budget' : 'Report';

                return $row;
            })
            ->values()
            ->all();
    }

   protected function leadershipProfiles(User $user, ?Carbon $since): array
{
    $leaders = collect();

    $userLeaders = DB::table('users')
        ->whereIn('role', ['sk_chairman', 'sk_secretary'])
        ->whereNotNull('barangay_id')
        ->select(
            DB::raw('user_id as leadership_id'),
            'user_id',
            'barangay_id',
            DB::raw("CONCAT(first_name, ' ', last_name) as full_name"),
            DB::raw("
                CASE
                    WHEN role = 'sk_chairman' THEN 'sk_chairman'
                    WHEN role = 'sk_secretary' THEN 'sk_secretary'
                    ELSE role
                END as position
            "),
            DB::raw("'2024-2026' as term"),
            DB::raw("'current' as status")
        )
        ->get();

    $leaders = $leaders->merge($userLeaders);

    if (Schema::hasTable('sk_council')) {
        $councilRows = DB::table('sk_council')
            ->select(
                DB::raw('NULL as leadership_id'),
                DB::raw('NULL as user_id'),
                'barangay_id',
                DB::raw('name as full_name'),
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
                DB::raw("'current' as status")
            )
            ->get();

        $leaders = $leaders->merge($councilRows);
    }

    if (Schema::hasTable('leadership_profiles')) {
        $profileRows = DB::table('leadership_profiles')
            ->where('status', 'current')
            ->select(
                'leadership_id',
                'user_id',
                'barangay_id',
                'full_name',
                'position',
                DB::raw("
                    CASE
                        WHEN term_start IS NOT NULL AND term_end IS NOT NULL
                            THEN CONCAT(YEAR(term_start), '-', YEAR(term_end))
                        WHEN term_start IS NOT NULL
                            THEN CONCAT(YEAR(term_start), '-present')
                        ELSE '2024-2026'
                    END as term
                "),
                'status'
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
            ($leader->full_name ?? '') . '|' .
            ($leader->position ?? '') . '|' .
            ($leader->barangay_id ?? '')
        ))
        ->values()
        ->all();
}

    protected function tableRows(string $table, ?Carbon $since, string $orderColumn): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        return $this->finish(DB::table($table), $table, $since, $orderColumn);
    }

    protected function finish(Builder $query, string $table, ?Carbon $since, string $orderColumn): array
    {
        $this->applySince($query, $table, $since);

        return $query->orderBy($orderColumn)->limit(500)->get()->all();
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

        return url(ltrim($path, '/'));
    }

    protected function saveMobileAccomplishmentSubmission(User $user, object $slot, array $validated): int
    {
        $year = (int) ($validated['reporting_year'] ?? now()->year);
        $month = (int) ($validated['reporting_month'] ?? now()->month);
        $reportType = $validated['report_type'] ?? 'monthly';

        $data = [
            'user_id' => $user->user_id,
            'barangay_id' => $user->barangay_id,
            'slot_id' => $slot->slot_id,
            'report_type' => $reportType,
            'submission_method' => 'file_upload',
            'title' => $slot->title,
            'reporting_year' => $year,
            'reporting_month' => $reportType === 'monthly' ? $month : null,
            'reporting_quarter' => $reportType === 'quarterly' ? ($validated['reporting_quarter'] ?? 'Q1') : null,
            'generated_pdf_path' => null,
            'uploaded_file_name' => $validated['uploaded_file_name'] ?? null,
            'uploaded_file_path' => $validated['uploaded_file_path'] ?? null,
            'status' => 'submitted',
            'remarks' => $validated['remarks'] ?? null,
            'submitted_at' => now(),
            'created_at' => now(),
        ];

        $existing = DB::table('accomplishment_reports')
            ->where('barangay_id', $user->barangay_id)
            ->where('slot_id', $slot->slot_id)
            ->first();

        if ($existing) {
            DB::table('accomplishment_reports')->where('report_id', $existing->report_id)->update($data);
            return (int) $existing->report_id;
        }

        return (int) DB::table('accomplishment_reports')->insertGetId($data, 'report_id');
    }

    protected function saveMobileBudgetSubmission(User $user, object $slot, array $validated): int
    {
        $reportType = $validated['report_type'] ?? 'annual';
        $year = (int) ($validated['reporting_year'] ?? now()->year);
        $data = [
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
            $data['fiscal_month'] = $reportType === 'monthly' ? ($validated['reporting_month'] ?? now()->month) : null;
            $data['fiscal_quarter'] = $reportType === 'quarterly' ? ($validated['reporting_quarter'] ?? 'Q1') : null;
        }

        $existing = DB::table('budget_reports')
            ->where('barangay_id', $user->barangay_id)
            ->where('slot_id', $slot->slot_id)
            ->first();

        if ($existing) {
            DB::table('budget_reports')->where('budget_report_id', $existing->budget_report_id)->update($data);
            return (int) $existing->budget_report_id;
        }

        return (int) DB::table('budget_reports')->insertGetId($data, 'budget_report_id');
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

    protected function isOfficial(User $user): bool
    {
        return in_array($user->role, ['sk_president', 'sk_chairman', 'sk_secretary'], true);
    }

    protected function isPresident(User $user): bool
    {
        return $user->role === 'sk_president';
    }

    protected function consolidationFilters(Request $request): array
    {
        $year = (int) $request->query('year', now()->year);
        $period = (string) $request->query('period', 'all');
        $month = (int) $request->query('month', now()->month);
        $quarter = (string) $request->query('quarter', 'Q'.ceil(now()->month / 3));

        if (! in_array($period, ['all', 'monthly', 'quarterly', 'annual'], true)) {
            $period = 'all';
        }

        return [
            'year' => $year > 2000 && $year < 2100 ? $year : now()->year,
            'period' => $period,
            'month' => $month >= 1 && $month <= 12 ? $month : now()->month,
            'quarter' => in_array($quarter, ['Q1', 'Q2', 'Q3', 'Q4'], true) ? $quarter : 'Q'.ceil(now()->month / 3),
        ];
    }

    protected function consolidatedSubmissions(array $filters)
    {
        $reports = Schema::hasTable('accomplishment_reports')
            ? DB::table('accomplishment_reports')
                ->where('reporting_year', $filters['year'])
                ->when($filters['period'] === 'monthly', fn ($query) => $query
                    ->where('report_type', 'monthly')
                    ->where('reporting_month', $filters['month']))
                ->when($filters['period'] === 'quarterly', fn ($query) => $query
                    ->where('report_type', 'quarterly')
                    ->where('reporting_quarter', $filters['quarter']))
                ->when($filters['period'] === 'annual', fn ($query) => $query
                    ->where('report_type', 'annual'))
                ->get()
                ->groupBy('barangay_id')
            : collect();

        $hasBudgetPeriods = Schema::hasTable('budget_reports') && Schema::hasColumn('budget_reports', 'budget_period_type');
        $budgets = Schema::hasTable('budget_reports')
            ? DB::table('budget_reports')
                ->where('fiscal_year', $filters['year'])
                ->when($hasBudgetPeriods && $filters['period'] === 'monthly', fn ($query) => $query
                    ->where('budget_period_type', 'monthly')
                    ->where('fiscal_month', $filters['month']))
                ->when($hasBudgetPeriods && $filters['period'] === 'quarterly', fn ($query) => $query
                    ->where('budget_period_type', 'quarterly')
                    ->where('fiscal_quarter', $filters['quarter']))
                ->when($hasBudgetPeriods && $filters['period'] === 'annual', fn ($query) => $query
                    ->where('budget_period_type', 'annual'))
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

                $monthlyBudgets = $hasBudgetPeriods ? $budgetItems->where('budget_period_type', 'monthly')->count() : 0;
                $quarterlyBudgets = $hasBudgetPeriods ? $budgetItems->where('budget_period_type', 'quarterly')->count() : 0;
                $annualBudgets = $hasBudgetPeriods ? $budgetItems->where('budget_period_type', 'annual')->count() : $budgetItems->count();
                $allItems = $reportItems->merge($budgetItems);
                $lastSubmission = $allItems->sortByDesc('submitted_at')->first();

                return [
                    'barangay_id' => $barangay->barangay_id,
                    'barangay' => $barangay->barangay_name,
                    'monthly_count' => $monthlyReports + $monthlyBudgets,
                    'quarterly_count' => $quarterlyReports + $quarterlyBudgets,
                    'annual_count' => $annualReports + $annualBudgets,
                    'monthly' => $this->consolidationStatusLabel($monthlyReports + $monthlyBudgets, $monthlyReports, $monthlyBudgets),
                    'quarterly' => $this->consolidationStatusLabel($quarterlyReports + $quarterlyBudgets, $quarterlyReports, $quarterlyBudgets),
                    'annual' => $this->consolidationStatusLabel($annualReports + $annualBudgets, $annualReports, $annualBudgets),
                    'last_submission' => $lastSubmission?->submitted_at
                        ? date('M d, Y h:i A', strtotime((string) $lastSubmission->submitted_at))
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

    protected function mobileRankings(): array
{
    return $this->rankingsLeaderboard()
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
            ? DB::table('accomplishment_reports')->select('reporting_year')->distinct()->pluck('reporting_year')->map(fn ($year) => (int) $year)
            : collect();

        $budgetYears = Schema::hasTable('budget_reports')
            ? DB::table('budget_reports')->select('fiscal_year')->distinct()->pluck('fiscal_year')->map(fn ($year) => (int) $year)
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
}
