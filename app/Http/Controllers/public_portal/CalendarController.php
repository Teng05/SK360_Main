<?php

namespace App\Http\Controllers\public_portal;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(): View
    {
        $events=DB::table('events')
            ->where('visibility','public')
            ->orderBy('start_datetime')
            ->get();

        $calendarEvents=$events->map(function($event){
            return [
                'id'=>$event->event_id,
                'title'=>$event->title,
                'start'=>$event->start_datetime
                    ? Carbon::parse($event->start_datetime)->format('Y-m-d\TH:i:s')
                    : null,
                'end'=>$event->end_datetime
                    ? Carbon::parse($event->end_datetime)->format('Y-m-d\TH:i:s')
                    : null,
                'extendedProps'=>[
                    'description'=>$event->description,
                    'location'=>$event->location,
                ],
            ];
        })->values();

        $upcomingEvents=DB::table('events')
            ->where('visibility','public')
            ->where(function($query){
                $query->where('end_datetime','>=',now())
                    ->orWhere(function($q){
                        $q->whereNull('end_datetime')
                            ->where('start_datetime','>=',now());
                    });
            })
            ->orderBy('start_datetime')
            ->limit(12)
            ->get();

        return view('public_portal.calendar',[
            'calendarEvents'=>$calendarEvents,
            'upcomingEvents'=>$upcomingEvents,
        ]);
    }
}