<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_secretary/MeetingsController.php.

namespace App\Http\Controllers\sk_secretary;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\User;
use App\Services\RankingPointsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use TaylanUnutmaz\AgoraTokenBuilder\RtcTokenBuilder;

class MeetingsController extends Controller
{
    public function index(): View
    {
        [$fullName, $menuItems] = $this->pageContext();
        $this->completeElapsedMeetings();

        $meetings = Meeting::query()
            ->orderByDesc('meeting_date')
            ->orderByDesc('meeting_time')
            ->get()
            ->map(fn (Meeting $meeting) => $this->decorateMeeting($meeting));

        $upcomingMeetings = $meetings
            ->filter(fn (Meeting $meeting) => in_array($meeting->status, ['scheduled'], true) && $meeting->scheduled_at->isFuture())
            ->sortBy(fn (Meeting $meeting) => $meeting->scheduled_at->timestamp)
            ->values();

        $activeMeetings = $meetings
            ->filter(fn (Meeting $meeting) => $meeting->status === 'scheduled' && $meeting->scheduled_at->isPast() && $meeting->ends_at->isFuture())
            ->sortBy(fn (Meeting $meeting) => $meeting->scheduled_at->timestamp)
            ->values();

        $pastMeetings = $meetings
            ->filter(fn (Meeting $meeting) => $meeting->status !== 'scheduled')
            ->sortByDesc(fn (Meeting $meeting) => $meeting->scheduled_at->timestamp)
            ->values();

        return view('sk_secretary.meetings', [
            'fullName' => $fullName,
            'menuItems' => $menuItems,
            'currentUrl' => route('sk_secretary.meetings'),
            'upcomingMeetings' => $upcomingMeetings,
            'activeMeetings' => $activeMeetings,
            'pastMeetings' => $pastMeetings,
        ]);
    }

    public function call(Meeting $meeting): View
    {
        [$fullName, $menuItems] = $this->pageContext();

        return view('sk_pres.video-call', [
            'fullName' => $fullName,
            'menuItems' => $menuItems,
            'currentUrl' => route('sk_secretary.meetings'),
            'meeting' => $this->decorateMeeting($meeting),
            'channelName' => $this->channelName($meeting),
            'backRoute' => route('sk_secretary.meetings'),
            'tokenRoute' => route('sk_secretary.meetings.agora.token', $meeting->meeting_id),
            'participantNames' => $this->participantNames(),
        ]);
    }

    public function token(Meeting $meeting): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_secretary', 403);

        $appId = config('services.agora.app_id');
        $appCertificate = config('services.agora.app_certificate');

        if (! filled($appId) || ! filled($appCertificate)) {
            return response()->json([
                'message' => 'Agora is not configured. Set AGORA_APP_ID and AGORA_APP_CERTIFICATE in .env.',
            ], 500);
        }

        $uid = (int) request('uid', (auth()->user()->user_id ?? 0));
        if ($uid <= 0) {
            $uid = random_int(1000, 999999);
        }

        try {
            $expireAt = now()->addHours(4)->timestamp;
            $token = RtcTokenBuilder::buildTokenWithUid(
                $appId,
                $appCertificate,
                $this->channelName($meeting),
                $uid,
                RtcTokenBuilder::RolePublisher,
                $expireAt
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Failed to generate Agora RTC token.',
            ], 500);
        }

        $this->scoreMeetingAttendance($meeting);

        return response()->json([
            'appId' => $appId,
            'token' => $token,
            'channel' => $this->channelName($meeting),
            'uid' => $uid,
            'name' => $this->currentUserName(),
            'title' => $meeting->title,
        ]);
    }

    protected function pageContext(): array
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_secretary', 403);

        // Shared topbar/sidebar data for the SK Secretary meetings page.
        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';

        $menuItems = [
            ['link' => route('sk_secretary.home'), 'icon' => '&#127968;', 'label' => 'Home'],
            ['link' => route('sk_secretary.reports'), 'icon' => '&#128196;', 'label' => 'Reports'],
            ['link' => route('sk_secretary.budget'), 'icon' => '&#128229;', 'label' => 'Budget'],
            ['link' => route('sk_secretary.announcements'), 'icon' => '&#128226;', 'label' => 'Announcements'],
            ['link' => route('sk_secretary.calendar'), 'icon' => '&#128197;', 'label' => 'Calendar'],
            ['link' => route('sk_secretary.chat'), 'icon' => '&#128172;', 'label' => 'Chat'],
            ['link' => route('sk_secretary.meetings'), 'icon' => '&#128222;', 'label' => 'Meetings'],
            ['link' => route('sk_secretary.rankings'), 'icon' => '&#127942;', 'label' => 'Rankings'],
            ['link' => route('sk_secretary.leadership'), 'icon' => '&#128101;', 'label' => 'Leadership'],
        ];

        return [$fullName, $menuItems];
    }

    protected function currentUserName(): string
    {
        $user = auth()->user();

        return trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';
    }

    protected function participantNames(): array
    {
        return User::query()
            ->whereIn('role', ['sk_president', 'sk_chairman', 'sk_secretary'])
            ->get(['user_id', 'first_name', 'last_name', 'email'])
            ->mapWithKeys(function (User $user) {
                $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->email ?: 'User');

                return [(string) $user->user_id => $name];
            })
            ->all();
    }

    protected function decorateMeeting(Meeting $meeting): Meeting
    {
        // Adds display-only fields used by the meeting cards.
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

    protected function completeElapsedMeetings(): void
    {
        // Moves meetings to Past Meetings after their one-hour meeting window ends.
        Meeting::query()
            ->where('status', 'scheduled')
            ->get()
            ->each(function (Meeting $meeting) {
                $scheduledAt = $meeting->scheduled_at;

                if ($scheduledAt->copy()->addHour()->isPast()) {
                    $meeting->status = 'completed';
                    $meeting->updated_at = now();
                    $meeting->save();
                }
            });
    }

    protected function channelName(Meeting $meeting): string
    {
        return 'meeting-' . $meeting->meeting_id;
    }

    protected function scoreMeetingAttendance(Meeting $meeting): void
    {
        $user = auth()->user();

        if (empty($user->barangay_id)) {
            return;
        }

        app(RankingPointsService::class)->award(
            (int) $user->barangay_id,
            RankingPointsService::MEETING_ATTENDANCE,
            'meeting',
            $meeting->meeting_id,
            (int) $user->user_id
        );
    }
}
