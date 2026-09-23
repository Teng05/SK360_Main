<?php

// File guide: Handles shared ranking and leaderboard data.

namespace App\Http\Controllers\Concerns;

use App\Services\RankingPointsService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait BuildsRankingsData
{
    protected function currentRankingPeriod(): string
    {
        return now()->format('F Y');
    }

    protected function selectedRankingPeriod(): string
    {
        $value=request()->query('period');

        if(!$value){
            return $this->currentRankingPeriod();
        }

        try{
            $date=Carbon::createFromFormat('Y-m',$value)->startOfMonth();

            if($date->format('Y-m')!==$value || $date->isFuture()){
                return $this->currentRankingPeriod();
            }

            $period=$date->format('F Y');

            return $this->rankingPeriods()->contains($period)
                ? $period
                : $this->currentRankingPeriod();
        }catch(\Throwable){
            return $this->currentRankingPeriod();
        }
    }

    protected function selectedRankingPeriodValue(): string
    {
        try{
            return Carbon::createFromFormat(
                'F Y',
                $this->selectedRankingPeriod()
            )->format('Y-m');
        }catch(\Throwable){
            return now()->format('Y-m');
        }
    }

    protected function latestRankingPeriod(): ?string
    {
        return $this->selectedRankingPeriod();
    }

    protected function rankingsLeaderboard(): Collection
    {
        if(
            !Schema::hasTable('barangays')
            ||
            !Schema::hasTable('rankings')
            ||
            !Schema::hasTable('administration_terms')
        ){
            return collect();
        }

        $currentTermId=$this->currentTermId();

        if(!$currentTermId){
            return collect();
        }

        $latestPeriod=$this->latestRankingPeriod();

        if(!$latestPeriod){
            return collect();
        }

        try{
            $previousPeriod=Carbon::createFromFormat(
                'F Y',
                $latestPeriod
            )
                ->subMonthNoOverflow()
                ->format('F Y');
        }catch(\Throwable){
            $previousPeriod=now()
                ->copy()
                ->subMonthNoOverflow()
                ->format('F Y');
        }

        $previousRanks=$this->rankingPositionsForPeriod(
            $previousPeriod
        );

        $rows=DB::table('barangays as b')
            ->leftJoin('rankings as r',function($join) use(
                $latestPeriod,
                $currentTermId
            ){
                $join->on(
                    'r.barangay_id',
                    '=',
                    'b.barangay_id'
                )
                    ->where(
                        'r.term_id',
                        '=',
                        $currentTermId
                    )
                    ->where(
                        'r.reporting_period',
                        '=',
                        $latestPeriod
                    );
            })
            ->select(
                'b.barangay_id',
                'b.barangay_name as name',
                DB::raw(
                    'COALESCE(r.total_points, 0) as points'
                ),
                DB::raw(
                    'COALESCE(r.timely_submission_points, 0) as timely_submission_points'
                ),
                DB::raw(
                    'COALESCE(r.completeness_points, 0) as completeness_points'
                ),
                DB::raw(
                    'COALESCE(r.participation_points, 0) as participation_points'
                )
            )
            ->orderByRaw(
                'COALESCE(r.total_points, 0) DESC'
            )
            ->orderBy(
                'b.barangay_name'
            )
            ->get()
            ->values();

        if($rows->isEmpty()){
            return collect();
        }

        $maxParticipationPoints=max(
            1,
            (int)$rows->max(
                'participation_points'
            )
        );

        $previousPoints=null;
        $currentRank=0;

        return $rows->map(function($row,$index) use(
            &$previousPoints,
            &$currentRank,
            $previousRanks,
            $latestPeriod,
            $maxParticipationPoints
        ){
            $points=(int)$row->points;

            if(
                $previousPoints===null
                ||
                $points!==$previousPoints
            ){
                $currentRank=$index+1;
            }

            $row->rank=$currentRank;
            $previousPoints=$points;

            $metrics=$this->rankingMetrics(
                (int)$row->barangay_id,
                $latestPeriod
            );

            $row->on_time=$metrics['on_time'];
            $row->completion=$metrics['completion'];

            $participationPoints=max(
                0,
                (int)$row->participation_points
            );

            $row->engagement=(int)round(
                (
                    $participationPoints
                    /
                    $maxParticipationPoints
                )*100
            );

            $row->submission_score=(int)$row->timely_submission_points;
            $row->document_score=(int)$row->completeness_points;
            $row->meeting_score=(int)$row->participation_points;

            $row->previous_rank=
                $previousRanks[
                    (int)$row->barangay_id
                ] ?? null;

            $row->trend=match(true){
                $row->previous_rank===null=>'new',
                $row->rank<$row->previous_rank=>'up',
                $row->rank>$row->previous_rank=>'down',
                default=>'same',
            };

            return $row;
        })
            ->take(10)
            ->values();
    }

    protected function topRankings(Collection $leaderboard): Collection
    {
        return $leaderboard
            ->filter(
                fn($row)=>
                    (int)$row->points!==0
            )
            ->take(3)
            ->values()
            ->map(function($row){
                $icon=match((int)$row->rank){
                    1=>'🥇',
                    2=>'🥈',
                    3=>'🥉',
                    default=>'🏅',
                };

                $color=match((int)$row->rank){
                    1=>'border-yellow-400',
                    2=>'border-gray-300',
                    3=>'border-orange-400',
                    default=>'border-gray-200',
                };

                return [
                    'name'=>$row->name,
                    'points'=>(int)$row->points,
                    'rank'=>(int)$row->rank,
                    'color'=>$color,
                    'icon'=>$icon,
                    'badges'=>[
                        'Rank #'.$row->rank,
                        $row->rank===1
                            ? 'Highest Current Score'
                            : 'Top Ranking Barangay',
                    ],
                ];
            });
    }

    protected function rankingPointSystem(): array
    {
        return collect(
            app(RankingPointsService::class)->rules()
        )
            ->map(function($rule){
                return [
                    'label'=>$rule['label'],
                    'points'=>(int)$rule['points'],
                    'type'=>$rule['type'],
                ];
            })
            ->sortByDesc(
                fn($rule)=>
                    (int)$rule['points']
            )
            ->values()
            ->all();
    }

    protected function rankingPeriods(): Collection
    {
        $periods=collect([
            $this->currentRankingPeriod(),
        ]);

        $currentTermId=$this->currentTermId();

        if(
            $currentTermId
            &&
            Schema::hasTable('rankings')
        ){
            $periods=$periods->merge(
                DB::table('rankings')
                    ->where(
                        'term_id',
                        $currentTermId
                    )
                    ->whereNotNull(
                        'reporting_period'
                    )
                    ->pluck(
                        'reporting_period'
                    )
            );
        }

        return $periods
            ->filter()
            ->unique()
            ->sortByDesc(function($period){
                $timestamp=strtotime(
                    '1 '.trim(
                        (string)$period
                    )
                );

                return $timestamp ?: 0;
            })
            ->values();
    }

    protected function rankingPeriodOptions(): Collection
    {
        return $this->rankingPeriods()
            ->map(function($period){
                try{
                    $date=Carbon::createFromFormat(
                        'F Y',
                        $period
                    );

                    return [
                        'value'=>$date->format(
                            'Y-m'
                        ),
                        'label'=>$date->format(
                            'F Y'
                        ),
                    ];
                }catch(\Throwable){
                    return null;
                }
            })
            ->filter()
            ->values();
    }

    protected function rankingPositionsForPeriod(string $period): array
    {
        if(
            !Schema::hasTable('barangays')
            ||
            !Schema::hasTable('rankings')
        ){
            return [];
        }

        $currentTermId=$this->currentTermId();

        if(!$currentTermId){
            return [];
        }

        $hasRankingData=DB::table('rankings')
            ->where(
                'term_id',
                $currentTermId
            )
            ->where(
                'reporting_period',
                $period
            )
            ->exists();

        if(!$hasRankingData){
            return [];
        }

        $rows=DB::table('barangays as b')
            ->leftJoin('rankings as r',function($join) use(
                $period,
                $currentTermId
            ){
                $join->on(
                    'r.barangay_id',
                    '=',
                    'b.barangay_id'
                )
                    ->where(
                        'r.term_id',
                        '=',
                        $currentTermId
                    )
                    ->where(
                        'r.reporting_period',
                        '=',
                        $period
                    );
            })
            ->select(
                'b.barangay_id',
                DB::raw(
                    'COALESCE(r.total_points, 0) as total_points'
                )
            )
            ->orderByRaw(
                'COALESCE(r.total_points, 0) DESC'
            )
            ->orderBy(
                'b.barangay_name'
            )
            ->get()
            ->values();

        $ranks=[];
        $previousPoints=null;
        $currentRank=0;

        foreach($rows as $index=>$row){
            $points=(int)$row->total_points;

            if(
                $previousPoints===null
                ||
                $points!==$previousPoints
            ){
                $currentRank=$index+1;
            }

            $ranks[
                (int)$row->barangay_id
            ]=$currentRank;

            $previousPoints=$points;
        }

        return $ranks;
    }

    protected function rankingMetrics(
        int $barangayId,
        string $period
    ): array {
        if(
            !Schema::hasTable(
                'ranking_point_logs'
            )
        ){
            return [
                'on_time'=>0,
                'completion'=>0,
            ];
        }

        $currentTermId=$this->currentTermId();

        if(!$currentTermId){
            return [
                'on_time'=>0,
                'completion'=>0,
            ];
        }

        $submissionLogs=DB::table(
            'ranking_point_logs'
        )
            ->where(
                'term_id',
                $currentTermId
            )
            ->where(
                'barangay_id',
                $barangayId
            )
            ->where(
                'reporting_period',
                $period
            )
            ->whereIn('action',[
                RankingPointsService::ON_TIME_REPORT_SUBMISSION,
                RankingPointsService::LATE_SUBMISSION,
            ])
            ->get([
                'action',
                'source_type',
                'source_id',
            ]);

        $submissionCount=
            $submissionLogs->count();

        $onTimeCount=$submissionLogs
            ->where(
                'action',
                RankingPointsService::ON_TIME_REPORT_SUBMISSION
            )
            ->count();

        $onTimeRate=
            $submissionCount>0
                ? (int)round(
                    (
                        $onTimeCount
                        /
                        $submissionCount
                    )*100
                )
                : 0;

        $qualityLogs=DB::table(
            'ranking_point_logs'
        )
            ->where(
                'term_id',
                $currentTermId
            )
            ->where(
                'barangay_id',
                $barangayId
            )
            ->where(
                'reporting_period',
                $period
            )
            ->where(
                'action',
                RankingPointsService::QUALITY_DOCUMENTATION
            )
            ->get([
                'source_type',
                'source_id',
            ]);

        $submissionSources=
            $submissionLogs
                ->map(
                    fn($log)=>
                        $log->source_type
                        .':'
                        .$log->source_id
                )
                ->unique();

        $qualitySources=
            $qualityLogs
                ->map(
                    fn($log)=>
                        $log->source_type
                        .':'
                        .$log->source_id
                )
                ->unique();

        $completionRate=
            $submissionSources->isNotEmpty()
                ? (int)round(
                    (
                        $qualitySources
                            ->intersect(
                                $submissionSources
                            )
                            ->count()
                        /
                        $submissionSources
                            ->count()
                    )*100
                )
                : 0;

        return [
            'on_time'=>max(
                0,
                min(
                    100,
                    $onTimeRate
                )
            ),
            'completion'=>max(
                0,
                min(
                    100,
                    $completionRate
                )
            ),
        ];
    }

    protected function currentTermId(): ?int
    {
        if(
            !Schema::hasTable(
                'administration_terms'
            )
        ){
            return null;
        }

        $termId=DB::table(
            'administration_terms'
        )
            ->where(
                'status',
                'current'
            )
            ->orderByDesc(
                'term_id'
            )
            ->value(
                'term_id'
            );

        return $termId
            ? (int)$termId
            : null;
    }
}