<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailVerification;
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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
            'budget_reports' => $this->budgetReports($user, $since),
            'leadership_profiles' => $this->leadershipProfiles($user, $since),
        ]);
    }

    public function storeWallPost(Request $request): JsonResponse
    {
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

    public function storeEvent(Request $request): JsonResponse
    {
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

        return $this->finish($query, 'meetings', $since, 'meeting_id');
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
