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
        $currentTermId=$this->currentTermId();

        $events=$currentTermId
            ? DB::table('events')
                ->where('term_id',$currentTermId)
                ->where('visibility','public')
                ->orderBy('start_datetime')
                ->get()
            : collect();

        $calendarEvents=$events
            ->map(function($event){
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
            })
            ->values();

        $upcomingEvents=$currentTermId
            ? DB::table('events')
                ->where('term_id',$currentTermId)
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
                ->get()
            : collect();

        return view('public_portal.calendar',[
            'calendarEvents'=>$calendarEvents,
            'upcomingEvents'=>$upcomingEvents,
        ]);
    }

    protected function currentTermId(): ?int
    {
        $termId=DB::table('administration_terms')
            ->where('status','current')
            ->orderByDesc('term_id')
            ->value('term_id');

        return $termId
            ? (int)$termId
            : null;
    }
}