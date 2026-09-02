<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar | SK360 Public Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.js"></script>

    <style>
        .fc{font-family:inherit;}
        .fc .fc-toolbar-title{
            font-size:1.15rem;
            font-weight:900;
            color:#1f2937;
        }
        .fc .fc-button{
            background:#dc2626 !important;
            border-color:#dc2626 !important;
            box-shadow:none !important;
            font-size:.75rem !important;
            font-weight:700 !important;
        }
        .fc .fc-button:hover{
            background:#b91c1c !important;
            border-color:#b91c1c !important;
        }
        .fc .fc-button-active{
            background:#991b1b !important;
            border-color:#991b1b !important;
        }
        .fc .fc-day-today{
            background:#fef2f2 !important;
        }
        .fc-event{
            cursor:pointer;
            border-radius:6px !important;
        }
        .fc-daygrid-event{
            padding:2px 3px;
            white-space:normal !important;
        }
        .fc-list-event{
            cursor:pointer;
        }

        @media(max-width:640px){
            .fc .fc-toolbar{
                flex-direction:column;
                gap:10px;
            }
            .fc .fc-toolbar-chunk{
                display:flex;
                justify-content:center;
            }
            .fc .fc-toolbar-title{
                font-size:1rem;
            }
            .fc .fc-button{
                font-size:.65rem !important;
                padding:.4rem .55rem !important;
            }
            .fc .fc-list-event-title,
            .fc .fc-list-event-time{
                font-size:.75rem;
            }
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800">

{{-- HEADER --}}
<header class="sticky top-0 z-40 bg-red-600 text-white shadow">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
        <a href="{{ route('public.home') }}" class="flex items-center gap-3">
            <img src="{{ asset('images/logo.png') }}"
                class="w-10 h-10 rounded-full object-cover"
                alt="SK360 Logo">

            <div>
                <h1 class="text-xl font-black">SK 360°</h1>
                <p class="text-[10px] opacity-80 uppercase tracking-widest">
                    Public Information Portal
                </p>
            </div>
        </a>

        <a href="{{ route('public.home') }}"
            class="text-xs font-bold hover:text-yellow-300 transition">
            ← Public Portal
        </a>
    </div>
</header>

<main>

{{-- PAGE HEADER --}}
<section class="bg-gradient-to-br from-red-600 to-red-700 text-white">
    <div class="max-w-4xl mx-auto px-6 py-12">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-red-100">
            Public Schedule
        </p>

        <h2 class="text-4xl font-black mt-2">
            Event Calendar
        </h2>

        <p class="text-red-100 text-sm mt-3 max-w-2xl leading-relaxed">
            View public programs, activities, and upcoming events from the
            Sangguniang Kabataan Federation of Lipa City.
        </p>
    </div>
</section>

{{-- SEARCH --}}
<section class="max-w-4xl mx-auto px-6 pt-8">
    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-4">
        <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">
            Search Events
        </label>

        <div class="relative">
            <input id="eventSearch"
                type="text"
                placeholder="Search event, location, or description..."
                class="w-full rounded-xl border border-gray-200 pl-10 pr-20 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-300">

            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                🔎
            </span>

            <button id="clearSearchBtn"
                type="button"
                class="hidden absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-red-600 hover:text-red-700">
                CLEAR
            </button>
        </div>

        <div class="border-t border-gray-100 mt-4 pt-3">
            <p id="calendarResultCount" class="text-[10px] text-gray-400">
                Showing {{ count($calendarEvents) }}
                {{ count($calendarEvents)===1 ? 'public event' : 'public events' }}
            </p>
        </div>
    </div>
</section>

{{-- CALENDAR --}}
<section class="max-w-4xl mx-auto px-6 py-8">
    <div class="bg-white border border-gray-100 rounded-3xl shadow-sm overflow-hidden">

        <div class="border-b border-gray-100 px-6 py-5">
            <p class="text-xs font-black uppercase tracking-widest text-red-600">
                Schedule
            </p>

            <h3 class="text-2xl font-black text-gray-800 mt-1">
                Public Calendar
            </h3>

            <p class="text-xs text-gray-400 mt-2">
                Click an event to view its complete details.
                Mobile devices automatically open in List view.
            </p>
        </div>

        <div class="p-4 md:p-6">
            <div id="calendar"></div>

            <div id="calendarNoResults" class="hidden py-12 text-center">
                <div class="text-3xl mb-3">🔎</div>

                <h4 class="font-black text-gray-700">
                    No matching events
                </h4>

                <p class="text-xs text-gray-400 mt-2">
                    Try another search term.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- UPCOMING ACTIVITIES --}}
<section class="max-w-4xl mx-auto px-6 pb-12">
    <div class="mb-5">
        <p class="text-xs font-black uppercase tracking-widest text-red-600">
            What's Next
        </p>

        <h3 class="text-2xl font-black mt-1">
            Upcoming Activities
        </h3>

        <p class="text-xs text-gray-400 mt-2">
            Public activities and events scheduled in the coming days.
        </p>
    </div>

    <div id="upcomingEventsContainer"
        class="grid grid-cols-1 md:grid-cols-2 gap-4">

        @forelse($upcomingEvents as $event)

            <button type="button"
                data-event-id="{{ $event->event_id }}"
                class="upcoming-event-card text-left bg-white border border-gray-100 rounded-2xl p-5 shadow-sm hover:shadow-md hover:border-red-100 transition">

                <div class="flex gap-4">

                    <div class="w-14 h-14 bg-red-50 text-red-600 rounded-xl shrink-0 flex flex-col items-center justify-center">

                        <span class="font-black text-lg leading-none">
                            {{ \Carbon\Carbon::parse($event->start_datetime)->format('d') }}
                        </span>

                        <span class="text-[9px] font-bold uppercase">
                            {{ \Carbon\Carbon::parse($event->start_datetime)->format('M') }}
                        </span>

                    </div>

                    <div class="flex-1 min-w-0">

                        <h4 class="font-black text-gray-800">
                            {{ $event->title }}
                        </h4>

                        <p class="text-xs text-gray-500 mt-2">
                            🕐 {{ \Carbon\Carbon::parse($event->start_datetime)->format('h:i A') }}

                            @if($event->end_datetime)
                                - {{ \Carbon\Carbon::parse($event->end_datetime)->format('h:i A') }}
                            @endif
                        </p>

                        <p class="text-xs text-gray-400 mt-1 truncate">
                            📍 {{ $event->location ?: 'Location not specified' }}
                        </p>

                    </div>

                </div>

            </button>

        @empty

            <div class="md:col-span-2 bg-white border border-gray-100 rounded-2xl px-6 py-12 text-center">
                <div class="text-4xl mb-3">📅</div>

                <h4 class="font-black text-gray-700">
                    No upcoming public events
                </h4>

                <p class="text-xs text-gray-400 mt-2">
                    New public activities will appear here once scheduled.
                </p>
            </div>

        @endforelse

    </div>

    @if($upcomingEvents->isNotEmpty())
        <div id="filteredUpcomingEmpty"
            class="hidden bg-white border border-gray-100 rounded-2xl px-6 py-12 text-center">

            <div class="text-3xl mb-3">🔎</div>

            <h4 class="font-black text-gray-700">
                No matching upcoming activities
            </h4>

            <p class="text-xs text-gray-400 mt-2">
                Try another search term.
            </p>
        </div>
    @endif

</section>

</main>

{{-- EVENT DETAILS MODAL --}}
<div id="eventModal"
    class="hidden fixed inset-0 z-[9999] bg-black/50 items-center justify-center p-4">

    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden max-h-[90vh] overflow-y-auto">

        {{-- MODAL HEADER --}}
        <div class="bg-red-600 text-white px-6 py-5">

            <div class="flex items-start justify-between gap-4">

                <div>
                    <p class="text-[9px] font-black uppercase tracking-widest text-red-100 mb-2">
                        Public Event
                    </p>

                    <h3 id="modalEventTitle" class="text-xl font-black">
                        Event Title
                    </h3>
                </div>

                <button id="closeEventModal"
                    type="button"
                    class="w-9 h-9 rounded-full bg-white/15 hover:bg-white/25 flex items-center justify-center text-xl shrink-0 transition">
                    &times;
                </button>

            </div>

        </div>

        {{-- MODAL CONTENT --}}
        <div class="p-6">

            <div class="space-y-4">

                {{-- DATE --}}
                <div class="flex gap-3">

                    <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center shrink-0">
                        📅
                    </div>

                    <div>
                        <p class="text-[9px] uppercase tracking-widest font-black text-gray-400">
                            Date
                        </p>

                        <p id="modalEventDate"
                            class="text-sm font-bold text-gray-700 mt-1">
                        </p>
                    </div>

                </div>

                {{-- TIME --}}
                <div class="flex gap-3">

                    <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center shrink-0">
                        🕐
                    </div>

                    <div>
                        <p class="text-[9px] uppercase tracking-widest font-black text-gray-400">
                            Time
                        </p>

                        <p id="modalEventTime"
                            class="text-sm font-bold text-gray-700 mt-1">
                        </p>
                    </div>

                </div>

                {{-- LOCATION --}}
                <div class="flex gap-3">

                    <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center shrink-0">
                        📍
                    </div>

                    <div class="min-w-0">
                        <p class="text-[9px] uppercase tracking-widest font-black text-gray-400">
                            Location
                        </p>

                        <p id="modalEventLocation"
                            class="text-sm font-bold text-gray-700 mt-1 break-words">
                        </p>
                    </div>

                </div>

            </div>

            {{-- DESCRIPTION --}}
            <div class="border-t border-gray-100 mt-6 pt-5">

                <p class="text-[9px] uppercase tracking-widest font-black text-gray-400 mb-2">
                    About This Event
                </p>

                <p id="modalEventDescription"
                    class="text-sm text-gray-600 leading-relaxed whitespace-pre-line break-words">
                </p>

            </div>

        </div>

    </div>

</div>

{{-- BACK TO TOP --}}
<button id="backToTopBtn"
    type="button"
    title="Back to top"
    class="hidden fixed bottom-6 right-6 z-50 w-12 h-12 rounded-full bg-red-600 text-white shadow-xl hover:bg-red-700 transition items-center justify-center text-xl">
    ↑
</button>

{{-- FOOTER --}}
<footer class="bg-gray-900 text-gray-400">
    <div class="max-w-6xl mx-auto px-6 py-6 text-center text-xs">
        &copy; {{ date('Y') }} SK360 • Sangguniang Kabataan Federation of Lipa City
    </div>
</footer>

<script>
const allCalendarEvents=@json($calendarEvents);

let calendar=null;
let eventSearchTerm='';

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/
function normalizeValue(value){
    return String(value??'')
        .toLowerCase()
        .trim();
}

function eventMatchesSearch(event){
    const props=event.extendedProps||{};

    const searchableText=normalizeValue([
        event.title,
        props.description,
        props.location
    ].join(' '));

    return eventSearchTerm==='' ||
        searchableText.includes(eventSearchTerm);
}

function getFilteredEvents(){
    return allCalendarEvents.filter(event=>{
        return eventMatchesSearch(event);
    });
}

function findEventById(eventId){
    return allCalendarEvents.find(event=>{
        return String(event.id)===String(eventId);
    });
}

/*
|--------------------------------------------------------------------------
| DATE / TIME FORMAT
|--------------------------------------------------------------------------
*/
function formatDate(date){
    if(!date){
        return 'Date not specified';
    }

    return new Intl.DateTimeFormat('en-PH',{
        month:'long',
        day:'numeric',
        year:'numeric'
    }).format(date);
}

function formatTime(date){
    if(!date){
        return null;
    }

    return new Intl.DateTimeFormat('en-PH',{
        hour:'numeric',
        minute:'2-digit',
        hour12:true
    }).format(date);
}

/*
|--------------------------------------------------------------------------
| EVENT MODAL
|--------------------------------------------------------------------------
*/
const eventModal=document.getElementById('eventModal');
const closeEventModal=document.getElementById('closeEventModal');

const modalEventTitle=document.getElementById('modalEventTitle');
const modalEventDate=document.getElementById('modalEventDate');
const modalEventTime=document.getElementById('modalEventTime');
const modalEventLocation=document.getElementById('modalEventLocation');
const modalEventDescription=document.getElementById('modalEventDescription');

function openEventModal(data){
    const start=data.start
        ? new Date(data.start)
        : null;

    const end=data.end
        ? new Date(data.end)
        : null;

    modalEventTitle.textContent=
        data.title||'Untitled Event';

    modalEventLocation.textContent=
        data.location||
        'Location not specified';

    modalEventDescription.textContent=
        data.description||
        'No additional description was provided for this event.';

    if(start && end){
        const sameDay=
            start.toDateString()===
            end.toDateString();

        if(sameDay){
            modalEventDate.textContent=
                formatDate(start);

            modalEventTime.textContent=
                `${formatTime(start)} - ${formatTime(end)}`;
        }else{
            modalEventDate.textContent=
                `${formatDate(start)} - ${formatDate(end)}`;

            modalEventTime.textContent=
                `${formatTime(start)} - ${formatTime(end)}`;
        }

    }else if(start){
        modalEventDate.textContent=
            formatDate(start);

        modalEventTime.textContent=
            formatTime(start)||
            'Time not specified';

    }else{
        modalEventDate.textContent=
            'Date not specified';

        modalEventTime.textContent=
            'Time not specified';
    }

    eventModal.classList.remove('hidden');
    eventModal.classList.add('flex');

    document.body.classList.add('overflow-hidden');
}

function closeModal(){
    eventModal.classList.add('hidden');
    eventModal.classList.remove('flex');

    document.body.classList.remove('overflow-hidden');
}

closeEventModal.addEventListener('click',closeModal);

eventModal.addEventListener('click',event=>{
    if(event.target===eventModal){
        closeModal();
    }
});

document.addEventListener('keydown',event=>{
    if(
        event.key==='Escape' &&
        !eventModal.classList.contains('hidden')
    ){
        closeModal();
    }
});

/*
|--------------------------------------------------------------------------
| APPLY SEARCH
|--------------------------------------------------------------------------
*/
function applySearch(){
    const filteredEvents=getFilteredEvents();

    if(calendar){
        calendar.removeAllEvents();
        calendar.addEventSource(filteredEvents);
    }

    const resultCount=
        document.getElementById('calendarResultCount');

    resultCount.textContent=
        `Showing ${filteredEvents.length} ${
            filteredEvents.length===1
                ? 'public event'
                : 'public events'
        }`;

    const noResults=
        document.getElementById('calendarNoResults');

    if(filteredEvents.length===0){
        noResults.classList.remove('hidden');
    }else{
        noResults.classList.add('hidden');
    }

    filterUpcomingEvents();
}

/*
|--------------------------------------------------------------------------
| FILTER UPCOMING ACTIVITIES
|--------------------------------------------------------------------------
*/
function filterUpcomingEvents(){
    const cards=
        document.querySelectorAll('.upcoming-event-card');

    if(cards.length===0){
        return;
    }

    let visibleCount=0;

    cards.forEach(card=>{
        const event=
            findEventById(card.dataset.eventId);

        const visible=
            event && eventMatchesSearch(event);

        card.classList.toggle(
            'hidden',
            !visible
        );

        if(visible){
            visibleCount++;
        }
    });

    const emptyMessage=
        document.getElementById('filteredUpcomingEmpty');

    const container=
        document.getElementById('upcomingEventsContainer');

    if(!emptyMessage){
        return;
    }

    if(visibleCount===0){
        container.classList.add('hidden');
        emptyMessage.classList.remove('hidden');
    }else{
        container.classList.remove('hidden');
        emptyMessage.classList.add('hidden');
    }
}

/*
|--------------------------------------------------------------------------
| SEARCH INPUT
|--------------------------------------------------------------------------
*/
const eventSearch=document.getElementById('eventSearch');
const clearSearchBtn=document.getElementById('clearSearchBtn');

eventSearch.addEventListener('input',()=>{
    eventSearchTerm=
        normalizeValue(eventSearch.value);

    if(eventSearchTerm!==''){
        clearSearchBtn.classList.remove('hidden');
    }else{
        clearSearchBtn.classList.add('hidden');
    }

    applySearch();
});

clearSearchBtn.addEventListener('click',()=>{
    eventSearch.value='';
    eventSearchTerm='';

    clearSearchBtn.classList.add('hidden');

    applySearch();

    eventSearch.focus();
});

/*
|--------------------------------------------------------------------------
| FULLCALENDAR
|--------------------------------------------------------------------------
*/
document.addEventListener('DOMContentLoaded',function(){
    const calendarElement=
        document.getElementById('calendar');

    const isMobile=
        window.innerWidth<=640;

    calendar=new FullCalendar.Calendar(
        calendarElement,
        {
            initialView:isMobile
                ? 'listMonth'
                : 'dayGridMonth',

            height:'auto',

            headerToolbar:{
                left:'prev,next today',
                center:'title',
                right:'dayGridMonth,timeGridWeek,listMonth'
            },

            buttonText:{
                today:'Today',
                month:'Month',
                week:'Week',
                list:'List'
            },

            events:allCalendarEvents,

            eventColor:'#dc2626',
            eventTextColor:'#ffffff',

            displayEventTime:true,

            eventTimeFormat:{
                hour:'numeric',
                minute:'2-digit',
                meridiem:'short'
            },

            dayMaxEvents:3,

            noEventsContent:
                'No public events for this period.',

            eventClick:function(info){
                const event=info.event;

                openEventModal({
                    title:event.title,
                    start:event.start,
                    end:event.end,
                    description:event.extendedProps.description,
                    location:event.extendedProps.location
                });
            }
        }
    );

    calendar.render();
});

/*
|--------------------------------------------------------------------------
| UPCOMING EVENT CARDS
|--------------------------------------------------------------------------
*/
document.querySelectorAll('.upcoming-event-card').forEach(card=>{
    card.addEventListener('click',()=>{
        const event=
            findEventById(card.dataset.eventId);

        if(!event){
            return;
        }

        openEventModal({
            title:event.title,
            start:event.start,
            end:event.end,
            description:event.extendedProps.description,
            location:event.extendedProps.location
        });
    });
});

/*
|--------------------------------------------------------------------------
| BACK TO TOP
|--------------------------------------------------------------------------
*/
const backToTopBtn=
    document.getElementById('backToTopBtn');

window.addEventListener('scroll',()=>{
    if(window.scrollY>400){
        backToTopBtn.classList.remove('hidden');
        backToTopBtn.classList.add('flex');
    }else{
        backToTopBtn.classList.add('hidden');
        backToTopBtn.classList.remove('flex');
    }
});

backToTopBtn.addEventListener('click',()=>{
    window.scrollTo({
        top:0,
        behavior:'smooth'
    });
});
</script>

</body>
</html>