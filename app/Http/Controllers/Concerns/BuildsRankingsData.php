<?php

// File guide: Handles route logic and page data for app/Http/Controllers/Concerns/BuildsRankingsData.php.

namespace App\Http\Controllers\Concerns;

use App\Services\RankingPointsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait BuildsRankingsData
{
    protected function currentRankingPeriod(): string
    {
        return now()->format('F Y');
    }

    protected function ensureCurrentRankingPeriod(): void
    {
        $period = $this->currentRankingPeriod();

        DB::table('barangays')->pluck('barangay_id')->each(function ($barangayId) use ($period) {
            $exists = DB::table('rankings')
                ->where('barangay_id', $barangayId)
                ->where('reporting_period', $period)
                ->exists();

            if (! $exists) {
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
        });
    }

    protected function rankingPeriods(): Collection
    {
        $this->ensureCurrentRankingPeriod();

        return DB::table('rankings')
            ->select('reporting_period')
            ->selectRaw('MAX(created_at) as latest_created_at')
            ->groupBy('reporting_period')
            ->orderByDesc('latest_created_at')
            ->pluck('reporting_period')
            ->values();
    }

    protected function latestRankingPeriod(): ?string
    {
        return $this->rankingPeriods()->first();
    }

    protected function rankingsLeaderboard(?string $period = null): Collection
    {
        app(RankingPointsService::class)->recordMissedMeetings();
        $periods = $this->rankingPeriods();

        $latestPeriod = $period ?: $periods->first();

        if (! $latestPeriod || ! $periods->contains($latestPeriod)) {
            return collect();
        }

        return DB::table('rankings as r')
            ->join('barangays as b', 'r.barangay_id', '=', 'b.barangay_id')
            ->select(
                'r.barangay_id',
                'b.barangay_name as name',
                'r.total_points as points',
                'r.timely_submission_points',
                'r.completeness_points',
                'r.participation_points'
            )
            ->where('r.reporting_period', $latestPeriod)
            ->orderByDesc('r.total_points')
            ->orderBy('r.barangay_id')
            ->get()
            ->values()
            ->map(function ($row, $index) {
                $row->rank = $index + 1;
                $row->on_time = $this->normalizeRankingMetric((int) $row->timely_submission_points);
                $row->completion = $this->normalizeRankingMetric((int) $row->completeness_points);
                $row->engagement = $this->normalizeRankingMetric((int) $row->participation_points);
                $row->trend = $index < 2 ? 'up' : 'down';

                return $row;
            });
    }

    protected function topRankings(Collection $leaderboard): Collection
    {
        return $leaderboard->take(3)->values()->map(function ($row, $index) {
            $icons = ['🥇', '🥈', '🥉'];
            $colors = ['border-yellow-400', 'border-gray-200', 'border-orange-400'];
            $badges = [
                1 => ['Top Performer', 'Highest Score'],
                2 => ['Strong Compliance', 'Consistent Performer'],
                3 => ['Rising Contender', 'Active Participation'],
            ];

            return [
                'name' => $row->name,
                'points' => $row->points,
                'color' => $colors[$index] ?? 'border-gray-200',
                'icon' => $icons[$index] ?? '🏅',
                'badges' => $badges[$index + 1] ?? ['Participant'],
            ];
        });
    }

    protected function rankingPointSystem(): array
    {
        return [
            ['label' => 'On-time Report Submission', 'points' => 50, 'type' => 'positive'],
            ['label' => 'Meeting Attendance', 'points' => 30, 'type' => 'positive'],
            ['label' => 'Community Engagement', 'points' => 25, 'type' => 'positive'],
            ['label' => 'Quality Documentation', 'points' => 20, 'type' => 'positive'],
            ['label' => 'Event Participation', 'points' => 15, 'type' => 'positive'],
            ['label' => 'Late Submission', 'points' => -25, 'type' => 'negative'],
            ['label' => 'Missed Meeting', 'points' => -30, 'type' => 'negative'],
        ];
    }

    protected function normalizeRankingMetric(int $value): int
    {
        return max(0, min(100, $value));
    }
}
