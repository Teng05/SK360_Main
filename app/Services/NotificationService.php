<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    public function notifyAnnouncementCreated(object $announcement, User $actor): void
    {
        $roles = $announcement->visibility === 'officials_only'
            ? ['sk_chairman', 'sk_secretary']
            : ['youth', 'sk_chairman', 'sk_secretary'];

        $this->createForRoles($roles, $actor, [
            'type' => 'announcement',
            'title' => 'New announcement',
            'message' => $actor->first_name.' posted: '.$announcement->title,
        ]);
    }

    public function notifySubmissionSlotCreated(array $slot, User $actor): void
    {
        $roles = match ($slot['role']) {
            'SK Chairman' => ['sk_chairman'],
            'SK Secretary' => ['sk_secretary'],
            default => ['sk_chairman', 'sk_secretary'],
        };

        $targetType = $slot['submission_type'] === 'budget_report' ? 'budget_slot' : 'report_slot';

        $this->createForRoles($roles, $actor, [
            'type' => $targetType,
            'title' => 'New submission slot',
            'message' => $slot['title'].' is now open from '.$slot['start_date'].' to '.$slot['end_date'],
        ]);
    }

    public function notifyEventCreated(object $event, User $actor): void
    {
        $roles = match ($event->visibility) {
            'chairman_only' => ['sk_chairman'],
            'secretary_only' => ['sk_secretary'],
            'officials_only' => ['sk_chairman', 'sk_secretary'],
            default => ['youth', 'sk_chairman', 'sk_secretary'],
        };

        $this->createForRoles($roles, $actor, [
            'type' => 'event',
            'title' => 'New calendar event',
            'message' => $event->title.' on '.optional($event->start_datetime)->format('M d, Y'),
        ]);
    }

    protected function createForRoles(array $roles, User $actor, array $payload): void
    {
        $recipients = User::query()
            ->whereIn('role', $roles)
            ->where('status', 'active')
            ->where('user_id', '!=', $actor->user_id)
            ->get(['user_id', 'role']);

        $this->createForUsers($recipients, $actor, $payload);
    }

    protected function createForUsers(Collection $recipients, User $actor, array $payload): void
    {
        $rows = [];
        $now = now();

        foreach ($recipients as $recipient) {
            $rows[] = [
                'user_id' => $recipient->user_id,
                'actor_id' => $actor->user_id,
                'type' => $payload['type'],
                'title' => $payload['title'],
                'message' => $payload['message'],
                'url' => $this->resolveUrlForRole($recipient->role, $payload['type']),
                'is_read' => 0,
                'created_at' => $now,
                'read_at' => null,
            ];
        }

        if ($rows !== []) {
            Notification::insert($rows);
        }
    }

    protected function resolveUrlForRole(string $role, string $type): string
    {
        return match ($role) {
            'youth' => match ($type) {
                'announcement' => route('youth.announcements'),
                'event' => route('youth.calendar'),
                default => route('youth.home'),
            },
            'sk_chairman' => match ($type) {
                'announcement' => route('sk_chairman.announcements'),
                'event' => route('sk_chairman.calendar'),
                'budget_slot' => route('sk_chairman.budget'),
                'report_slot' => route('sk_chairman.reports'),
                default => route('sk_chairman.home'),
            },
            'sk_secretary' => match ($type) {
                'announcement' => route('sk_secretary.announcements'),
                'event' => route('sk_secretary.calendar'),
                'budget_slot' => route('sk_secretary.budget'),
                'report_slot' => route('sk_secretary.reports'),
                default => route('sk_secretary.home'),
            },
            default => '/',
        };
    }
}
