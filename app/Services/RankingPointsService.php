<?php

namespace App\Services;

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
        self::ON_TIME_REPORT_SUBMISSION => ['points' => 50, 'column' => 'timely_submission_points'],
        self::MEETING_ATTENDANCE => ['points' => 30, 'column' => 'participation_points'],
        self::COMMUNITY_ENGAGEMENT => ['points' => 25, 'column' => 'participation_points'],
        self::QUALITY_DOCUMENTATION => ['points' => 20, 'column' => 'completeness_points'],
        self::EVENT_PARTICIPATION => ['points' => 15, 'column' => 'participation_points'],
        self::LATE_SUBMISSION => ['points' => -25, 'column' => 'timely_submission_points'],
        self::MISSED_MEETING => ['points' => -30, 'column' => 'participation_points'],
    ];

    public function award(
        int $barangayId,
        string $action,
        string $sourceType,
        string|int $sourceId,
        ?int $userId = null,
        ?string $period = null
    ): bool {
        if ($barangayId <= 0) {
            return false;
        }

        $rule = $this->rules[$action] ?? null;

        if (! $rule) {
            throw new InvalidArgumentException("Unknown ranking point action [{$action}].");
        }

        $period ??= now()->format('F Y');
        $sourceId = (string) $sourceId;

        return DB::transaction(function () use ($barangayId, $action, $sourceType, $sourceId, $userId, $period, $rule) {
            if (Schema::hasTable('ranking_point_logs')) {
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
            }

            $this->ensureRankingRow($barangayId, $period);
            $this->incrementRanking($barangayId, $period, $rule['column'], (int) $rule['points']);

            return true;
        });
    }

    public function rules(): array
    {
        return $this->rules;
    }

    public function recordMissedMeetings(?string $period = null): void
    {
        if (! Schema::hasTable('meetings') || ! Schema::hasTable('users')) {
            return;
        }

        $completedMeetings = DB::table('meetings')
            ->where('status', 'completed')
            ->get(['meeting_id', 'meeting_date']);

        if ($completedMeetings->isEmpty()) {
            return;
        }

        $barangayIds = DB::table('users')
            ->whereIn('role', ['sk_chairman', 'sk_secretary'])
            ->whereNotNull('barangay_id')
            ->distinct()
            ->pluck('barangay_id');

        foreach ($completedMeetings as $meeting) {
            $meetingId = (int) $meeting->meeting_id;
            $meetingPeriod = $period ?: \Carbon\Carbon::parse($meeting->meeting_date)->format('F Y');

            foreach ($barangayIds as $barangayId) {
                $attended = Schema::hasTable('ranking_point_logs')
                    && DB::table('ranking_point_logs')
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

    protected function ensureRankingRow(int $barangayId, string $period): void
    {
        $exists = DB::table('rankings')
            ->where('barangay_id', $barangayId)
            ->where('reporting_period', $period)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('rankings')->insert([
            'barangay_id' => $barangayId,
            'reporting_period' => $period,
            'total_points' => 0,
            'timely_submission_points' => 0,
            'completeness_points' => 0,
            'participation_points' => 0,
            'created_at' => now(),
        ]);
    }

    protected function incrementRanking(int $barangayId, string $period, string $column, int $points): void
    {
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
        return in_array((string) ($exception->errorInfo[1] ?? ''), ['1062'], true);
    }
}
