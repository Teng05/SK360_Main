<?php

use App\Services\RankingPointsService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function(){
    app(RankingPointsService::class)->recordMissedSubmissions();
})
->name('record-missed-submissions')
->dailyAt('00:05')
->timezone('Asia/Manila')
->withoutOverlapping();