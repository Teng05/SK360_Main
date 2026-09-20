<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class RankingPointsService
{
    public const ON_TIME_REPORT_SUBMISSION = 'on_time_report_submission';
    public const MEETING_ATTENDANCE = 'meeting_attendance';
    public const COMMUNITY_ENGAGEMENT = 'community_engagement';
    public const QUALITY_DOCUMENTATION = 'quality_documentation';
    public const EVENT_PARTICIPATION = 'event_participation';
    public const LATE_SUBMISSION = 'late_submission';
    public const MISSED_MEETING = 'missed_meeting';

    protected array $rules = [
        self::ON_TIME_REPORT_SUBMISSION => [
            'label' => 'On-time Report Submission',
            'points' => 50,
            'column' => 'timely_submission_points',
            'type' => 'positive',
        ],
        self::MEETING_ATTENDANCE => [
            'label' => 'Meeting Attendance',
            'points' => 30,
            'column' => 'participation_points',
            'type' => 'positive',
        ],
        self::COMMUNITY_ENGAGEMENT => [
            'label' => 'Community Engagement',
            'points' => 25,
            'column' => 'participation_points',
            'type' => 'positive',
        ],
        self::QUALITY_DOCUMENTATION => [
            'label' => 'Quality Documentation',
            'points' => 20,
            'column' => 'completeness_points',
            'type' => 'positive',
        ],
        self::EVENT_PARTICIPATION => [
            'label' => 'Event Participation',
            'points' => 15,
            'column' => 'participation_points',
            'type' => 'positive',
        ],
        self::LATE_SUBMISSION => [
            'label' => 'Late Submission',
            'points' => -25,
            'column' => 'timely_submission_points',
            'type' => 'negative',
        ],
        self::MISSED_MEETING => [
            'label' => 'Missed Meeting',
            'points' => -30,
            'column' => 'participation_points',
            'type' => 'negative',
        ],
    ];

    public function award(
        int $barangayId,
        string $action,
        string $sourceType,
        string|int $sourceId,
        ?int $userId = null,
        ?string $period = null
    ): bool {
        if (
            $barangayId <= 0 ||
            !Schema::hasTable('rankings') ||
            !Schema::hasTable('ranking_point_logs')
        ) {
            return false;
        }

        $rule = $this->rules[$action] ?? null;

        if (!$rule) {
            throw new InvalidArgumentException("Unknown ranking point action [{$action}].");
        }

        $period ??= now()->format('F Y');
        $sourceId = (string) $sourceId;

        return DB::transaction(function () use (
            $barangayId,
            $action,
            $sourceType,
            $sourceId,
            $userId,
            $period,
            $rule
        ) {
            if ($this->alreadyAwarded(
                $barangayId,
                $action,
                $sourceType,
                $sourceId
            )) {
                return false;
            }

            if ($this->hasConflictingOutcome(
                $barangayId,
                $action,
                $sourceType,
                $sourceId
            )) {
                return false;
            }

            try {
                DB::table('ranking_point_logs')->insert([
                    'barangay_id' => $barangayId,
                    'user_id' => $userId,
                    'reporting_period' => $period,
                    'action' => $action,
                    'points' => $rule['points'],
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'created_at' => now(),
                ]);
            } catch (QueryException $exception) {
                if ($this->isDuplicateLog($exception)) {
                    return false;
                }

                throw $exception;
            }

            $this->ensureRankingRow($barangayId, $period);

            $this->incrementRanking(
                $barangayId,
                $period,
                $rule['column'],
                (int) $rule['points']
            );

            return true;
        });
    }

    public function rules(): array
    {
        return $this->rules;
    }

    public function recordMissedMeetings(?string $period = null): void
    {
        if (
            !Schema::hasTable('meetings') ||
            !Schema::hasTable('users') ||
            !Schema::hasTable('ranking_point_logs') ||
            !Schema::hasTable('rankings')
        ) {
            return;
        }

        $completedMeetings = DB::table('meetings')
            ->where('status', 'completed')
            ->whereNotNull('meeting_date')
            ->get([
                'meeting_id',
                'meeting_date',
            ]);

        if ($completedMeetings->isEmpty()) {
            return;
        }

        $barangayQuery = DB::table('users')
            ->whereIn('role', [
                'sk_chairman',
                'sk_secretary',
            ])
            ->whereNotNull('barangay_id');

        if (Schema::hasColumn('users', 'status')) {
            $barangayQuery->where('status', 'active');
        }

        if (Schema::hasColumn('users', 'archived_at')) {
            $barangayQuery->whereNull('archived_at');
        }

        $barangayIds = $barangayQuery
            ->distinct()
            ->pluck('barangay_id');

        foreach ($completedMeetings as $meeting) {
            $meetingId = (int) $meeting->meeting_id;

            $attendanceRecorded = DB::table('ranking_point_logs')
                ->where('action', self::MEETING_ATTENDANCE)
                ->where('source_type', 'meeting')
                ->where('source_id', (string) $meetingId)
                ->exists();

            if (!$attendanceRecorded) {
                continue;
            }

            $meetingPeriod = $period
                ?: Carbon::parse($meeting->meeting_date)->format('F Y');

            foreach ($barangayIds as $barangayId) {
                $attended = DB::table('ranking_point_logs')
                    ->where('barangay_id', $barangayId)
                    ->where('action', self::MEETING_ATTENDANCE)
                    ->where('source_type', 'meeting')
                    ->where('source_id', (string) $meetingId)
                    ->exists();

                if ($attended) {
                    continue;
                }

                $this->award(
                    (int) $barangayId,
                    self::MISSED_MEETING,
                    'meeting',
                    $meetingId,
                    null,
                    $meetingPeriod
                );
            }
        }
    }

    protected function alreadyAwarded(
        int $barangayId,
        string $action,
        string $sourceType,
        string $sourceId
    ): bool {
        return DB::table('ranking_point_logs')
            ->where('barangay_id', $barangayId)
            ->where('action', $action)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists();
    }

    protected function hasConflictingOutcome(
        int $barangayId,
        string $action,
        string $sourceType,
        string $sourceId
    ): bool {
        $conflictingActions = match ($action) {
            self::ON_TIME_REPORT_SUBMISSION => [
                self::LATE_SUBMISSION,
            ],
            self::LATE_SUBMISSION => [
                self::ON_TIME_REPORT_SUBMISSION,
            ],
            self::MEETING_ATTENDANCE => [
                self::MISSED_MEETING,
            ],
            self::MISSED_MEETING => [
                self::MEETING_ATTENDANCE,
            ],
            default => [],
        };

        if (empty($conflictingActions)) {
            return false;
        }

        return DB::table('ranking_point_logs')
            ->where('barangay_id', $barangayId)
            ->whereIn('action', $conflictingActions)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists();
    }

    protected function ensureRankingRow(int $barangayId, string $period): void
    {
        DB::table('rankings')->insertOrIgnore([
            'barangay_id' => $barangayId,
            'reporting_period' => $period,
            'total_points' => 0,
            'timely_submission_points' => 0,
            'completeness_points' => 0,
            'participation_points' => 0,
            'created_at' => now(),
        ]);
    }

    protected function incrementRanking(
        int $barangayId,
        string $period,
        string $column,
        int $points
    ): void {
        DB::table('rankings')
            ->where('barangay_id', $barangayId)
            ->where('reporting_period', $period)
            ->update([
                $column => DB::raw($column.' + '.$points),
                'total_points' => DB::raw('total_points + '.$points),
            ]);
    }

    protected function isDuplicateLog(QueryException $exception): bool
    {
        $driverCode = (string) ($exception->errorInfo[1] ?? '');
        $sqlState = (string) ($exception->errorInfo[0] ?? '');

        return $driverCode === '1062' || $sqlState === '23000';
    }
}