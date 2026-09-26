<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_secretary/CalendarController.php.

namespace App\Http\Controllers\sk_secretary;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role==='sk_secretary',403);

        $user=auth()->user();
        $fullName=trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';
        $barangayName=$user->barangay->barangay_name ?? 'Barangay';
        $currentTermId=$this->currentTermId();

        $events=$currentTermId
            ? DB::table('events')
                ->where('term_id',$currentTermId)
                ->whereIn('visibility',[
                    'public',
                    'officials_only',
                    'secretary_only',
                ])
                ->orderBy('start_datetime')
                ->get()
            : collect();

        $slotEvents=$currentTermId
            ? DB::table('submission_slots')
                ->where('term_id',$currentTermId)
                ->where('status','open')
                ->where(function($query){
                    $query->where('role','SK Secretary')
                        ->orWhere('role','Both');
                })
                ->orderBy('start_date')
                ->get()
            : collect();

        $upcomingEvents=$events
            ->filter(fn($event)=>Carbon::parse($event->end_datetime)->greaterThanOrEqualTo(now()))
            ->take(5)
            ->map(function($event){
                $event->type_label=$this->eventTypeLabel($event->event_type);
                $event->type_badge=$this->eventTypeBadge($event->event_type);
                $event->visibility_label=$this->visibilityLabel($event->visibility);
                $event->source_type='event';
                return $event;
            });

        $upcomingSlots=$slotEvents
            ->filter(fn($slot)=>Carbon::parse($slot->end_date)->endOfDay()->greaterThanOrEqualTo(now()))
            ->take(5)
            ->map(function($slot){
                $slot->start_datetime=Carbon::parse($slot->start_date)->startOfDay();
                $slot->end_datetime=Carbon::parse($slot->end_date)->endOfDay();
                $slot->event_type=$slot->submission_type==='budget_report' ? 'budget_slot' : 'report_slot';
                $slot->type_label=$slot->submission_type==='budget_report' ? 'Budget Slot' : 'Report Slot';
                $slot->type_badge=$slot->submission_type==='budget_report'
                    ? 'bg-amber-100 text-amber-700'
                    : 'bg-indigo-100 text-indigo-700';
                $slot->visibility_label=$slot->role ?: 'Both';
                $slot->location='Online submission';
                $slot->source_type='slot';
                return $slot;
            });

        $calendarEvents=$events
            ->map(function($event){
                return [
                    'id'=>$event->event_id,
                    'title'=>$event->title,
                    'start'=>$event->start_datetime,
                    'end'=>$event->end_datetime,
                    'className'=>$this->eventTypeColor($event->event_type),
                    'extendedProps'=>[
                        'source_type'=>'event',
                        'editable'=>false,
                        'event_type'=>$event->event_type,
                        'type_label'=>$this->eventTypeLabel($event->event_type),
                        'description'=>$event->description,
                        'location'=>$event->location,
                        'visibility'=>$event->visibility,
                        'visibility_label'=>$this->visibilityLabel($event->visibility),
                        'start_value'=>Carbon::parse($event->start_datetime)->format('Y-m-d\TH:i'),
                        'end_value'=>Carbon::parse($event->end_datetime)->format('Y-m-d\TH:i'),
                    ],
                ];
            })
            ->merge(
                $slotEvents->map(function($slot){
                    $slotType=$slot->submission_type==='budget_report' ? 'budget_slot' : 'report_slot';

                    return [
                        'id'=>'slot-'.$slot->slot_id,
                        'title'=>$slot->title,
                        'start'=>$slot->start_date,
                        'end'=>Carbon::parse($slot->end_date)->addDay()->format('Y-m-d'),
                        'allDay'=>true,
                        'className'=>$slotType==='budget_slot' ? 'bg-amber-500' : 'bg-indigo-600',
                        'extendedProps'=>[
                            'source_type'=>'slot',
                            'editable'=>false,
                            'event_type'=>$slotType,
                            'type_label'=>$slotType==='budget_slot' ? 'Budget Slot' : 'Report Slot',
                            'description'=>$slot->description,
                            'location'=>'Online submission',
                            'visibility'=>$slot->role,
                            'visibility_label'=>$slot->role ?: 'Both',
                            'start_value'=>$slot->start_date,
                            'end_value'=>$slot->end_date,
                        ],
                    ];
                })
            )
            ->values();

        $legendItems=[
            ['bg-blue-700','Meeting'],
            ['bg-green-600','Event/Program'],
            ['bg-red-600','Deadline'],
            ['bg-indigo-600','Report Slot'],
            ['bg-amber-500','Budget Slot'],
            ['bg-fuchsia-500','Other Activities'],
        ];

        return view('sk_secretary.calendar',[
            'fullName'=>$fullName,
            'barangayName'=>$barangayName,
            'initials'=>strtoupper(
                substr($user->first_name ?? 'S',0,1).
                substr($user->last_name ?? 'K',0,1)
            ),
            'menuItems'=>$this->menuItems(),
            'currentUrl'=>url()->current(),
            'calendarEvents'=>$calendarEvents,
            'legendItems'=>$legendItems,
            'upcomingEvents'=>$upcomingEvents
                ->concat($upcomingSlots)
                ->sortBy('start_datetime')
                ->take(5)
                ->values(),
        ]);
    }

    protected function eventTypeColor(?string $eventType): string
    {
        return match($eventType){
            'meeting'=>'bg-blue-700',
            'program'=>'bg-green-600',
            'deadline'=>'bg-red-600',
            default=>'bg-fuchsia-500',
        };
    }

    protected function eventTypeLabel(?string $eventType): string
    {
        return match($eventType){
            'meeting'=>'Meeting',
            'program'=>'Event/Program',
            'deadline'=>'Deadline',
            default=>'Other Activity',
        };
    }

    protected function eventTypeBadge(?string $eventType): string
    {
        return match($eventType){
            'meeting'=>'bg-blue-100 text-blue-700',
            'program'=>'bg-green-100 text-green-700',
            'deadline'=>'bg-red-100 text-red-700',
            default=>'bg-fuchsia-100 text-fuchsia-700',
        };
    }

    protected function visibilityLabel(?string $visibility): string
    {
        return match($visibility){
            'public'=>'All Users / Public',
            'officials_only'=>'SK Chairman and SK Secretary',
            'chairman_only'=>'SK Chairman Only',
            'secretary_only'=>'SK Secretary Only',
            default=>'Unspecified',
        };
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

    protected function menuItems(): array
    {
        return [
            ['link'=>route('sk_secretary.home'),'icon'=>'&#127968;','label'=>'Home'],
            ['link'=>route('sk_secretary.reports'),'icon'=>'&#128196;','label'=>'Reports'],
            ['link'=>route('sk_secretary.budget'),'icon'=>'&#128229;','label'=>'Budget'],
            ['link'=>route('sk_secretary.announcements'),'icon'=>'&#128226;','label'=>'Announcements'],
            ['link'=>route('sk_secretary.calendar'),'icon'=>'&#128197;','label'=>'Calendar'],
            ['link'=>route('sk_secretary.chat'),'icon'=>'&#128172;','label'=>'Chat'],
            ['link'=>route('sk_secretary.meetings'),'icon'=>'&#128222;','label'=>'Meetings'],
            ['link'=>route('sk_secretary.rankings'),'icon'=>'&#127942;','label'=>'Rankings'],
            ['link'=>route('sk_secretary.leadership'),'icon'=>'&#128101;','label'=>'Leadership'],
        ];
    }
}