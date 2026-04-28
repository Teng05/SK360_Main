<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait BuildsRankingsData
{
    protected function latestRankingPeriod(): ?string
    {
        return DB::table('rankings')
            ->orderByDesc('created_at')
            ->value('reporting_period');
    }

    protected function rankingsLeaderboard(): Collection
    {
        $latestPeriod = $this->latestRankingPeriod();

        if (! $latestPeriod) {
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

    protected function normalizeRankingMetric(int $value): int
    {
        return max(0, min(100, $value));
    }
}
