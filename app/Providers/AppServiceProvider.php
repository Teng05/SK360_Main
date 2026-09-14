<?php

namespace App\Providers;

use App\Console\Commands\CreatePastMeetingNotifications;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('public-feedback-submit',function(Request $request){
            $email=strtolower(trim((string)$request->input('email')));
            $day=now('Asia/Manila')->format('Y-m-d');

            $emailKey=$email!=='' ? hash('sha256',$email) : 'missing-'.$request->ip();

            return [
                Limit::perDay(5)
                    ->by('public-feedback-email:'.$emailKey.':'.$day)
                    ->response(function(){
                        return response()->json([
                            'message'=>'This email has reached its daily feedback limit. You may submit feedback again tomorrow.',
                        ],429);
                    }),

                Limit::perDay(100)
                    ->by('public-feedback-ip:'.$request->ip().':'.$day)
                    ->response(function(){
                        return response()->json([
                            'message'=>'Too many feedback requests were sent from this network today. Please try again tomorrow.',
                        ],429);
                    }),
            ];
        });

        RateLimiter::for('public-feedback-verify',function(Request $request){
            $feedbackId=$request->route('feedbackId');

            return Limit::perMinute(10)
                ->by('public-feedback-verify:'.$feedbackId)
                ->response(function(){
                    return response()->json([
                        'message'=>'Too many verification attempts. Please wait a moment and try again.',
                    ],429);
                });
        });

        RateLimiter::for('public-feedback-resend',function(Request $request){
            $feedbackId=$request->route('feedbackId');

            return Limit::perHour(10)
                ->by('public-feedback-resend:'.$feedbackId)
                ->response(function(){
                    return response()->json([
                        'message'=>'Too many verification codes were requested for this feedback.',
                    ],429);
                });
        });
    }

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run every minute to check for past meetings and create notifications
        $schedule->command('meetings:notify-past')->everyMinute();
    }
}
