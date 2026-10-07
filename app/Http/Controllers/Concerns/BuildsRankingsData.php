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
        if(!$value)return $this->currentRankingPeriod();
        try{
            $date=Carbon::createFromFormat('Y-m',$value)->startOfMonth();
            if($date->format('Y-m')!==$value||$date->isFuture())return $this->currentRankingPeriod();
            $period=$date->format('F Y');
            return $this->rankingPeriods()->contains($period)?$period:$this->currentRankingPeriod();
        }catch(\Throwable){
            return $this->currentRankingPeriod();
        }
    }

    protected function selectedRankingPeriodValue(): string
    {
        try{return Carbon::createFromFormat('F Y',$this->selectedRankingPeriod())->format('Y-m');}
        catch(\Throwable){return now()->format('Y-m');}
    }

    protected function latestRankingPeriod(): ?string
    {
        return $this->selectedRankingPeriod();
    }

    protected function rankingsLeaderboard(): Collection
    {
        if(!Schema::hasTable('barangays')||!Schema::hasTable('rankings')||!Schema::hasTable('administration_terms'))return collect();
        $currentTermId=$this->currentTermId();
        if(!$currentTermId)return collect();
        $latestPeriod=$this->latestRankingPeriod();
        if(!$latestPeriod)return collect();

        try{$previousPeriod=Carbon::createFromFormat('F Y',$latestPeriod)->subMonthNoOverflow()->format('F Y');}
        catch(\Throwable){$previousPeriod=now()->copy()->subMonthNoOverflow()->format('F Y');}

        $previousRanks=$this->rankingPositionsForPeriod($previousPeriod);

        $rows=DB::table('barangays as b')
            ->leftJoin('rankings as r',function($join) use($latestPeriod,$currentTermId){
                $join->on('r.barangay_id','=','b.barangay_id')
                    ->where('r.term_id','=',$currentTermId)
                    ->where('r.reporting_period','=',$latestPeriod);
            })
            ->select(
                'b.barangay_id',
                'b.barangay_name as name',
                DB::raw('COALESCE(r.total_points,0) as points'),
                DB::raw('COALESCE(r.timely_submission_points,0) as timely_submission_points'),
                DB::raw('COALESCE(r.completeness_points,0) as completeness_points'),
                DB::raw('COALESCE(r.participation_points,0) as participation_points')
            )
            ->orderByRaw('COALESCE(r.total_points,0) DESC')
            ->orderBy('b.barangay_name')
            ->get()
            ->values();

        if($rows->isEmpty())return collect();

        $previousPoints=null;
        $currentRank=0;
        $rankedIndex=0;

        return $rows->map(function($row) use(&$previousPoints,&$currentRank,&$rankedIndex,$previousRanks){
            $points=(int)$row->points;
            $row->submission_score=(int)$row->timely_submission_points;
            $row->document_score=(int)$row->completeness_points;
            $row->meeting_score=(int)$row->participation_points;

            if($points!==0){
                $rankedIndex++;
                if($previousPoints===null||$points!==$previousPoints)$currentRank=$rankedIndex;
                $row->rank=$currentRank;
                $previousPoints=$points;
                $row->previous_rank=$previousRanks[(int)$row->barangay_id]??null;
                $row->trend=match(true){
                    $row->previous_rank===null=>'new',
                    $row->rank<$row->previous_rank=>'up',
                    $row->rank>$row->previous_rank=>'down',
                    default=>'same',
                };
            }else{
                $row->rank=null;
                $row->previous_rank=$previousRanks[(int)$row->barangay_id]??null;
                $row->trend='unranked';
            }

            return $row;
        })->values();
    }

    protected function topRankings(Collection $leaderboard): Collection
    {
        return $leaderboard->filter(fn($row)=>(int)$row->points>0&&$row->rank!==null)->take(3)->values()->map(function($row){
            $icon=match((int)$row->rank){1=>'🥇',2=>'🥈',3=>'🥉',default=>'🏅'};
            $color=match((int)$row->rank){1=>'border-yellow-400',2=>'border-gray-300',3=>'border-orange-400',default=>'border-gray-200'};
            return[
                'name'=>$row->name,
                'points'=>(int)$row->points,
                'rank'=>(int)$row->rank,
                'color'=>$color,
                'icon'=>$icon,
                'badges'=>[
                    'Rank #'.$row->rank,
                    $row->rank===1?'Highest Current Score':'Top Ranking Barangay',
                ],
            ];
        });
    }

    protected function rankingPointSystem(): array
    {
        return collect(app(RankingPointsService::class)->rules())->map(function($rule){
            return['label'=>$rule['label'],'points'=>(int)$rule['points'],'type'=>$rule['type']];
        })->sortByDesc(fn($rule)=>(int)$rule['points'])->values()->all();
    }

    protected function rankingPointBreakdowns(Collection $leaderboard): array
    {
        $ids=$leaderboard->pluck('barangay_id')->map(fn($id)=>(int)$id)->all();
        $empty=['submission_score'=>[],'document_score'=>[],'meeting_score'=>[]];
        $result=array_fill_keys($ids,$empty);
        $termId=$this->currentTermId();
        $period=$this->latestRankingPeriod();
        if(!$ids||!$termId||!$period||!Schema::hasTable('ranking_point_logs'))return$result;

        $columns=[
            'timely_submission_points'=>'submission_score',
            'completeness_points'=>'document_score',
            'participation_points'=>'meeting_score',
        ];
        $rules=app(RankingPointsService::class)->rules();
        $logs=DB::table('ranking_point_logs')
            ->where('term_id',$termId)
            ->where('reporting_period',$period)
            ->whereIn('barangay_id',$ids)
            ->get(['barangay_id','action','points']);

        foreach($logs as $log){
            $rule=$rules[$log->action]??null;
            $metric=$rule?($columns[$rule['column']??'']??null):null;
            if(!$metric)continue;
            $result[(int)$log->barangay_id][$metric][]=[
                'label'=>$rule['label'],
                'points'=>(int)$log->points,
            ];
        }
        return$result;
    }

    protected function rankingComparisonData(Collection $leaderboard): array
    {
        $ranked=$leaderboard->filter(fn($row)=>$row->rank!==null&&(int)$row->points!==0)->take(10)->values();
        $empty=['labels'=>[],'series'=>['overall'=>[],'submission'=>[],'document'=>[],'meeting'=>[]]];
        $termId=$this->currentTermId();
        if($ranked->isEmpty()||!$termId||!Schema::hasTable('rankings'))return$empty;
        try{$selected=Carbon::createFromFormat('F Y',$this->latestRankingPeriod())->startOfMonth();}
        catch(\Throwable){return$empty;}

        $historyRows=DB::table('rankings')
            ->where('term_id',$termId)
            ->whereNotNull('reporting_period')
            ->get(['barangay_id','reporting_period','total_points','timely_submission_points','completeness_points','participation_points']);
        $periods=$historyRows->pluck('reporting_period')->unique()
            ->map(function($period){
                try{$date=Carbon::createFromFormat('F Y',trim((string)$period))->startOfMonth();return['value'=>(string)$period,'date'=>$date];}
                catch(\Throwable){return null;}
            })
            ->filter(fn($period)=>$period&&$period['date']->lte($selected))
            ->sortBy(fn($period)=>$period['date']->timestamp)
            ->values();
        if($periods->isEmpty())return$empty;

        $ids=$ranked->pluck('barangay_id')->map(fn($id)=>(int)$id)->all();
        $periodValues=$periods->pluck('value')->all();
        $history=$historyRows
            ->whereIn('barangay_id',$ids)
            ->whereIn('reporting_period',$periodValues)
            ->keyBy(fn($row)=>(int)$row->barangay_id.'|'.$row->reporting_period);
        $metrics=[
            'overall'=>'total_points',
            'submission'=>'timely_submission_points',
            'document'=>'completeness_points',
            'meeting'=>'participation_points',
        ];
        $colors=['#c92336','#2563eb','#16a34a','#d97706','#7c3aed','#0891b2','#db2777','#4f46e5','#65a30d','#ea580c'];
        $series=[];
        foreach($metrics as $metric=>$column){
            $series[$metric]=$ranked->map(function($barangay,$index)use($periodValues,$history,$column,$colors){
                $id=(int)$barangay->barangay_id;
                $values=array_map(function($period)use($history,$id,$column){
                    $row=$history[$id.'|'.$period]??null;
                    return$row?(int)$row->{$column}:0;
                },$periodValues);
                return['label'=>$barangay->name,'data'=>$values,'borderColor'=>$colors[$index],'backgroundColor'=>$colors[$index],'tension'=>0.25];
            })->all();
        }
        return['labels'=>$periods->pluck('value')->all(),'series'=>$series];
    }

    protected function rankingPeriods(): Collection
    {
        $periods=collect([$this->currentRankingPeriod()]);
        $currentTermId=$this->currentTermId();
        if($currentTermId&&Schema::hasTable('rankings')){
            $periods=$periods->merge(DB::table('rankings')->where('term_id',$currentTermId)->whereNotNull('reporting_period')->pluck('reporting_period'));
        }
        return $periods->filter()->unique()->sortByDesc(function($period){
            $timestamp=strtotime('1 '.trim((string)$period));
            return $timestamp?:0;
        })->values();
    }

    protected function rankingPeriodOptions(): Collection
    {
        return $this->rankingPeriods()->map(function($period){
            try{
                $date=Carbon::createFromFormat('F Y',$period);
                return['value'=>$date->format('Y-m'),'label'=>$date->format('F Y')];
            }catch(\Throwable){return null;}
        })->filter()->values();
    }

    protected function rankingPositionsForPeriod(string $period): array
    {
        if(!Schema::hasTable('barangays')||!Schema::hasTable('rankings'))return[];
        $currentTermId=$this->currentTermId();
        if(!$currentTermId)return[];

        $rows=DB::table('rankings as r')
            ->join('barangays as b','b.barangay_id','=','r.barangay_id')
            ->where('r.term_id',$currentTermId)
            ->where('r.reporting_period',$period)
            ->where('r.total_points','!=',0)
            ->select('b.barangay_id','b.barangay_name',DB::raw('COALESCE(r.total_points,0) as total_points'))
            ->orderByDesc('r.total_points')
            ->orderBy('b.barangay_name')
            ->get()
            ->values();

        $ranks=[];
        $previousPoints=null;
        $currentRank=0;

        foreach($rows as $index=>$row){
            $points=(int)$row->total_points;
            if($previousPoints===null||$points!==$previousPoints)$currentRank=$index+1;
            $ranks[(int)$row->barangay_id]=$currentRank;
            $previousPoints=$points;
        }

        return $ranks;
    }

    protected function rankingMetrics(int $barangayId,string $period): array
    {
        if(!Schema::hasTable('ranking_point_logs'))return['on_time'=>0,'completion'=>0];
        $currentTermId=$this->currentTermId();
        if(!$currentTermId)return['on_time'=>0,'completion'=>0];

        $submissionLogs=DB::table('ranking_point_logs')
            ->where('term_id',$currentTermId)
            ->where('barangay_id',$barangayId)
            ->where('reporting_period',$period)
            ->whereIn('action',[RankingPointsService::ON_TIME_REPORT_SUBMISSION,RankingPointsService::LATE_SUBMISSION])
            ->get(['action','source_type','source_id']);

        $submissionCount=$submissionLogs->count();
        $onTimeCount=$submissionLogs->where('action',RankingPointsService::ON_TIME_REPORT_SUBMISSION)->count();
        $onTimeRate=$submissionCount>0?(int)round(($onTimeCount/$submissionCount)*100):0;

        $qualityLogs=DB::table('ranking_point_logs')
            ->where('term_id',$currentTermId)
            ->where('barangay_id',$barangayId)
            ->where('reporting_period',$period)
            ->where('action',RankingPointsService::QUALITY_DOCUMENTATION)
            ->get(['source_type','source_id']);

        $submissionSources=$submissionLogs->map(fn($log)=>$log->source_type.':'.$log->source_id)->unique();
        $qualitySources=$qualityLogs->map(fn($log)=>$log->source_type.':'.$log->source_id)->unique();
        $completionRate=$submissionSources->isNotEmpty()?(int)round(($qualitySources->intersect($submissionSources)->count()/$submissionSources->count())*100):0;

        return[
            'on_time'=>max(0,min(100,$onTimeRate)),
            'completion'=>max(0,min(100,$completionRate)),
        ];
    }

    protected function currentTermId(): ?int
    {
        if(!Schema::hasTable('administration_terms'))return null;
        $termId=DB::table('administration_terms')->where('status','current')->orderByDesc('term_id')->value('term_id');
        return $termId?(int)$termId:null;
    }
}
