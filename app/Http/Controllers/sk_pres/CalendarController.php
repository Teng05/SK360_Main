<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_pres/CalendarController.php.

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $user=auth()->user();
        $fullName=trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'User';
        $currentTermId=$this->currentTermId();

        $menuItems=[
            ['link'=>route('sk_pres.home'),'icon'=>'🏠','label'=>'Home'],
            ['link'=>route('sk_pres.dashboard'),'icon'=>'📊','label'=>'Dashboard'],
            ['link'=>route('sk_pres.consolidation'),'icon'=>'📁','label'=>'Consolidation'],
            ['link'=>route('sk_pres.module'),'icon'=>'⚙️','label'=>'Module Management'],
            ['link'=>route('sk_pres.announcements'),'icon'=>'📢','label'=>'Announcements'],
            ['link'=>route('sk_pres.calendar'),'icon'=>'📅','label'=>'Calendar'],
            ['link'=>route('sk_pres.chat'),'icon'=>'💬','label'=>'Chat'],
            ['link'=>route('sk_pres.meetings'),'icon'=>'📞','label'=>'Meetings'],
            ['link'=>route('sk_pres.rankings'),'icon'=>'🏆','label'=>'Rankings'],
            ['link'=>route('sk_pres.leadership'),'icon'=>'👥','label'=>'Leadership'],
            ['link'=>route('sk_pres.archive'),'icon'=>'🗂️','label'=>'Archive'],
            ['link'=>route('sk_pres.user-management'),'icon'=>'👤','label'=>'User Management'],
        ];

        $events=$currentTermId
            ? DB::table('events')
                ->where('term_id',$currentTermId)
                ->orderBy('start_datetime')
                ->get()
            : collect();

        $slotEvents=$currentTermId
            ? DB::table('submission_slots')
                ->where('term_id',$currentTermId)
                ->where('status','open')
                ->orderBy('start_date')
                ->get()
            : collect();

        $upcomingEvents=$currentTermId
            ? DB::table('events')
                ->where('term_id',$currentTermId)
                ->where('end_datetime','>=',now())
                ->orderBy('start_datetime')
                ->limit(5)
                ->get()
                ->map(function($event){
                    $event->type_label=match($event->event_type){
                        'meeting'=>'Meeting',
                        'program'=>'Event/Program',
                        'deadline'=>'Deadline',
                        default=>'Other Activity',
                    };

                    return $event;
                })
            : collect();

        $upcomingSlots=$slotEvents
            ->filter(
                fn($slot)=>
                    Carbon::parse($slot->end_date)
                        ->endOfDay()
                        ->greaterThanOrEqualTo(now())
            )
            ->take(5)
            ->map(function($slot){
                $slot->start_datetime=Carbon::parse(
                    $slot->start_date
                )->startOfDay();

                $slot->event_type=
                    $slot->submission_type==='budget_report'
                        ? 'budget_slot'
                        : 'report_slot';

                $slot->type_label=
                    $slot->submission_type==='budget_report'
                        ? 'Budget Slot'
                        : 'Report Slot';

                return $slot;
            });

        $calendarEvents=$events
            ->map(function($event){
                return [
                    'id'=>$event->event_id,
                    'title'=>$event->title,
                    'start'=>$event->start_datetime,
                    'end'=>$event->end_datetime,
                    'className'=>match($event->event_type){
                        'meeting'=>'bg-blue-700',
                        'program'=>'bg-green-600',
                        'deadline'=>'bg-red-600',
                        default=>'bg-fuchsia-500',
                    },
                    'extendedProps'=>[
                        'editable'=>true,
                        'event_type'=>$event->event_type,
                        'description'=>$event->description,
                        'location'=>$event->location,
                        'visibility'=>$event->visibility,
                    ],
                ];
            })
            ->merge(
                $slotEvents->map(function($slot){
                    return [
                        'id'=>'slot-'.$slot->slot_id,
                        'title'=>$slot->title,
                        'start'=>$slot->start_date,
                        'end'=>$slot->end_date,
                        'extendedProps'=>[
                            'editable'=>false,
                        ],
                        'className'=>
                            $slot->submission_type==='budget_report'
                                ? 'bg-amber-500'
                                : 'bg-indigo-600',
                    ];
                })
            )
            ->values();

        $typeColors=[
            'meeting'=>'bg-blue-700',
            'program'=>'bg-green-600',
            'deadline'=>'bg-red-600',
            'other'=>'bg-fuchsia-500',
            'report_slot'=>'bg-indigo-600',
            'budget_slot'=>'bg-amber-500',
        ];

        $legendItems=[
            ['bg-blue-700','Meeting'],
            ['bg-green-600','Event/Program'],
            ['bg-red-600','Deadline'],
            ['bg-indigo-600','Report Slot'],
            ['bg-amber-500','Budget Slot'],
            ['bg-fuchsia-500','Other Activities'],
        ];

        return view('sk_pres.calendar',[
            'fullName'=>$fullName,
            'menuItems'=>$menuItems,
            'currentUrl'=>url()->current(),
            'calendarEvents'=>$calendarEvents,
            'upcomingEvents'=>$upcomingEvents
                ->concat($upcomingSlots)
                ->sortBy('start_datetime')
                ->take(5)
                ->values(),
            'typeColors'=>$typeColors,
            'legendItems'=>$legendItems,
        ]);
    }

    public function live(): JsonResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        return response()->json(
            $this->calendarPayload()
        );
    }

    public function storeLive(
        Request $request,
        NotificationService $notifications
    ): JsonResponse {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $currentTermId=$this->currentTermId();

        if(!$currentTermId){
            return response()->json([
                'message'=>'There is no active administration term. Start a new administration term first.',
            ],422);
        }

        $validated=$this->validateEvent(
            $request
        );

        $event=$this->createEvent(
            $validated,
            $currentTermId
        );

        $notifications->notifyEventCreated(
            $event,
            auth()->user()
        );

        return response()->json(
            $this->calendarPayload()
        );
    }

    public function store(
        Request $request,
        NotificationService $notifications
    ): RedirectResponse {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $currentTermId=$this->currentTermId();

        if(!$currentTermId){
            return back()
                ->withInput()
                ->with(
                    'warning',
                    'There is no active administration term. Start a new administration term first.'
                );
        }

        $validated=$this->validateEvent(
            $request
        );

        $event=$this->createEvent(
            $validated,
            $currentTermId
        );

        $notifications->notifyEventCreated(
            $event,
            auth()->user()
        );

        return redirect()
            ->route('sk_pres.calendar')
            ->with(
                'status',
                'Event created successfully.'
            );
    }

    public function update(Request $request,int $eventId): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president',403);

        $currentTermId=$this->currentTermId();

        if(!$currentTermId){
            return back()->with(
                'warning',
                'There is no active administration term.'
            );
        }

        $event=DB::table('events')
            ->where('event_id',$eventId)
            ->where('term_id',$currentTermId)
            ->first();

        if(!$event){
            return back()->with(
                'warning',
                'Event was not found in the current administration.'
            );
        }

        $validated=$this->validateEvent($request);
        $eventData=$this->eventData($validated);

        DB::table('events')
            ->where('event_id',$eventId)
            ->where('term_id',$currentTermId)
            ->update([
                ...$eventData,
                'updated_at'=>now(),
            ]);

        return redirect()
            ->route('sk_pres.calendar')
            ->with('status','Event updated successfully.');
    }

    protected function validateEvent(Request $request): array
    {
        return $request->validate([
            'event_title'=>[
                'required',
                'string',
                'max:255',
            ],
            'event_type'=>[
                'required',
                'in:meeting,deadline,program,other',
            ],
            'description'=>[
                'nullable',
                'string',
            ],
            'location'=>[
                'nullable',
                'string',
                'max:255',
            ],
            'start_datetime'=>[
                'required',
                'date',
            ],
            'end_datetime'=>[
                'nullable',
                'date',
                'after_or_equal:start_datetime',
            ],
            'visibility'=>[
                'required',
                'in:public,officials_only,chairman_only,secretary_only',
            ],
        ]);
    }

    protected function createEvent(
        array $validated,
        int $termId
    ): object {
        $eventData=$this->eventData($validated);

        $eventId=DB::table('events')
            ->insertGetId([
                'term_id'=>$termId,
                'created_by'=>auth()->user()->user_id,
                ...$eventData,
                'created_at'=>now(),
            ],'event_id');

        return (object)[
            'event_id'=>$eventId,
            'term_id'=>$termId,
            'title'=>$validated['event_title'],
            'visibility'=>$validated['visibility'],
            'start_datetime'=>Carbon::parse($eventData['start_datetime']),
        ];
    }

    protected function eventData(array $validated): array
    {
        $start=Carbon::parse($validated['start_datetime']);
        $end=isset($validated['end_datetime']) && filled($validated['end_datetime'])
            ? Carbon::parse($validated['end_datetime'])
            : $start->copy()->addHour();

        return [
            'title'=>$validated['event_title'],
            'description'=>$validated['description'] ?? null,
            'location'=>$validated['location'] ?? null,
            'event_type'=>$validated['event_type'],
            'start_datetime'=>$start,
            'end_datetime'=>$end,
            'visibility'=>$validated['visibility'],
        ];
    }

    protected function calendarPayload(): array
    {
        $currentTermId=$this->currentTermId();

        if(!$currentTermId){
            return [
                'events'=>collect(),
                'upcomingEvents'=>collect(),
                'updatedAt'=>now()->format('M d, Y h:i A'),
            ];
        }

        $events=DB::table('events')
            ->where('term_id',$currentTermId)
            ->orderBy('start_datetime')
            ->get();

        $slotEvents=DB::table('submission_slots')
            ->where('term_id',$currentTermId)
            ->where('status','open')
            ->orderBy('start_date')
            ->get();

        $calendarEvents=$events
            ->map(function($event){
                return [
                    'id'=>$event->event_id,
                    'title'=>$event->title,
                    'start'=>$event->start_datetime,
                    'end'=>$event->end_datetime,
                    'type'=>$event->event_type,
                ];
            })
            ->merge(
                $slotEvents->map(function($slot){
                    return [
                        'id'=>'slot-'.$slot->slot_id,
                        'title'=>$slot->title,
                        'start'=>$slot->start_date,
                        'end'=>$slot->end_date,
                        'type'=>
                            $slot->submission_type==='budget_report'
                                ? 'budget_slot'
                                : 'report_slot',
                    ];
                })
            )
            ->values();

        return [
            'events'=>$calendarEvents,

            'upcomingEvents'=>$calendarEvents
                ->filter(
                    fn($event)=>
                        Carbon::parse(
                            $event['end']
                            ??
                            $event['start']
                        )
                            ->endOfDay()
                            ->greaterThanOrEqualTo(now())
                )
                ->sortBy('start')
                ->take(5)
                ->values(),

            'updatedAt'=>now()->format(
                'M d, Y h:i A'
            ),
        ];
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
