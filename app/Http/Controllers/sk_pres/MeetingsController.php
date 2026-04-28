<?php

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use TaylanUnutmaz\AgoraTokenBuilder\RtcTokenBuilder;

class MeetingsController extends Controller
{
    public function index(): View
    {
        [$fullName, $menuItems] = $this->pageContext();

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
            ->filter(fn (Meeting $meeting) => $meeting->status === 'scheduled' && ($meeting->scheduled_at->isToday() || $meeting->scheduled_at->isPast()))
            ->sortBy(fn (Meeting $meeting) => $meeting->scheduled_at->timestamp)
            ->values();

        $pastMeetings = $meetings
            ->filter(fn (Meeting $meeting) => $meeting->status !== 'scheduled')
            ->sortByDesc(fn (Meeting $meeting) => $meeting->scheduled_at->timestamp)
            ->values();

        return view('sk_pres.meetings', [
            'fullName' => $fullName,
            'menuItems' => $menuItems,
            'currentUrl' => route('sk_pres.meetings'),
            'upcomingMeetings' => $upcomingMeetings,
            'activeMeetings' => $activeMeetings,
            'pastMeetings' => $pastMeetings,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'agenda' => ['nullable', 'string'],
            'meeting_date' => ['required', 'date'],
            'meeting_time' => ['required', 'date_format:H:i'],
        ]);

        Meeting::create([
            'title' => $validated['title'],
            'agenda' => $validated['agenda'] ?? null,
            'meeting_date' => $validated['meeting_date'],
            'meeting_time' => $validated['meeting_time'] . ':00',
            'location_or_link' => null,
            'dyte_meeting_id' => null,
            'created_by' => auth()->user()->user_id,
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('sk_pres.meetings')->with('status', 'Meeting scheduled successfully.');
    }

    public function call(Meeting $meeting): View
    {
        [$fullName, $menuItems] = $this->pageContext();

        return view('sk_pres.video-call', [
            'fullName' => $fullName,
            'menuItems' => $menuItems,
            'currentUrl' => route('sk_pres.meetings'),
            'meeting' => $this->decorateMeeting($meeting),
            'channelName' => $this->channelName($meeting),
        ]);
    }

    public function token(Request $request, Meeting $meeting): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $appId = config('services.agora.app_id');
        $appCertificate = config('services.agora.app_certificate');

        if (! filled($appId) || ! filled($appCertificate)) {
            return response()->json([
                'message' => 'Agora is not configured. Set AGORA_APP_ID and AGORA_APP_CERTIFICATE in .env.',
            ], 500);
        }

        $uid = (int) (auth()->user()->user_id ?? 0);
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

        return response()->json([
            'appId' => $appId,
            'token' => $token,
            'channel' => $this->channelName($meeting),
            'uid' => $uid,
            'title' => $meeting->title,
        ]);
    }

    protected function pageContext(): array
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        $user = auth()->user();
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'User';

        $menuItems = [
            ['link' => route('sk_pres.home'), 'icon' => '&#127968;', 'label' => 'Home'],
            ['link' => route('sk_pres.dashboard'), 'icon' => '&#128202;', 'label' => 'Dashboard'],
            ['link' => route('sk_pres.consolidation'), 'icon' => '&#128193;', 'label' => 'Consolidation'],
            ['link' => route('sk_pres.module'), 'icon' => '&#9881;&#65039;', 'label' => 'Module Management'],
            ['link' => route('sk_pres.announcements'), 'icon' => '&#128226;', 'label' => 'Announcements'],
            ['link' => route('sk_pres.calendar'), 'icon' => '&#128197;', 'label' => 'Calendar'],
            ['link' => route('sk_pres.chat'), 'icon' => '&#128172;', 'label' => 'Chat'],
            ['link' => route('sk_pres.meetings'), 'icon' => '&#128222;', 'label' => 'Meetings'],
            ['link' => route('sk_pres.rankings'), 'icon' => '&#127942;', 'label' => 'Rankings'],
            ['link' => route('sk_pres.analytics'), 'icon' => '&#128200;', 'label' => 'Analytics'],
            ['link' => route('sk_pres.leadership'), 'icon' => '&#128101;', 'label' => 'Leadership'],
            ['link' => route('sk_pres.archive'), 'icon' => '&#128450;&#65039;', 'label' => 'Archive'],
            ['link' => route('sk_pres.user-management'), 'icon' => '&#128100;', 'label' => 'User Management'],
        ];

        return [$fullName, $menuItems];
    }

    protected function decorateMeeting(Meeting $meeting): Meeting
    {
        $meeting->scheduled_at = Carbon::parse($meeting->meeting_date . ' ' . $meeting->meeting_time);
        $meeting->display_datetime = $meeting->scheduled_at->format('Y-m-d h:i A');
        $meeting->preview_datetime = $meeting->scheduled_at->format('M d, Y h:i A');
        $meeting->is_today = $meeting->scheduled_at->isToday();
        $meeting->status_label = match ($meeting->status) {
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => $meeting->scheduled_at->isFuture() ? 'Upcoming' : 'Ready',
        };

        return $meeting;
    }

    protected function channelName(Meeting $meeting): string
    {
        return 'meeting-' . $meeting->meeting_id;
    }
}
