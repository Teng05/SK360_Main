{{--
    "Calendar Preview" card for the home dashboards. Expects $upcomingEvents
    (title, start_datetime, location, type_label, type_badge) and $calendarUrl.
--}}
<section class="sk-card p-5" data-sk-tone="yellow">
    <div class="flex items-center justify-between gap-3 mb-4">
        <div>
            <p class="sk-overline">Events Calendar</p>
            <h2 class="sk-section-title !text-[17px] mt-1">Calendar Preview</h2>
        </div>
        <a href="{{ $calendarUrl }}" class="sk-icon-btn sk-icon-btn--filled" title="Open calendar" aria-label="Open calendar">
            @include('partials.ui.icon', ['icon' => 'calendar-days', 'iconSize' => 18])
        </a>
    </div>

    <div class="space-y-3">
        @forelse ($upcomingEvents as $skEvent)
            @php $skEventDate = \Carbon\Carbon::parse($skEvent->start_datetime); @endphp
            <div class="flex gap-3 rounded-2xl border border-gray-100 bg-[#f8f9fb] p-3 transition hover:border-gray-200 hover:bg-white">
                <div class="flex h-14 w-12 shrink-0 flex-col items-center justify-center rounded-xl border border-red-100 bg-white">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-red-600">{{ $skEventDate->format('M') }}</span>
                    <span class="text-lg font-extrabold leading-none text-gray-900">{{ $skEventDate->format('d') }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm font-bold leading-snug text-gray-900">{{ $skEvent->title }}</p>
                        <span data-sk-event="{{ $skEvent->event_type ?? '' }}" class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $skEvent->type_badge }}">
                            {{ $skEvent->type_label }}
                        </span>
                    </div>
                    <p class="mt-1 flex items-center gap-1 text-xs font-semibold text-gray-500">
                        @include('partials.ui.icon', ['icon' => 'clock', 'iconSize' => 13])
                        {{ $skEventDate->format('M d, Y h:i A') }}
                    </p>
                    <p class="mt-0.5 flex items-center gap-1 text-xs text-gray-400">
                        @include('partials.ui.icon', ['icon' => 'map-pin', 'iconSize' => 13])
                        {{ $skEvent->location ?: 'No location provided' }}
                    </p>
                </div>
            </div>
        @empty
            <div class="sk-empty !py-8">
                <span class="sk-icon-tile sk-icon-tile--gray">
                    @include('partials.ui.icon', ['icon' => 'calendar-days', 'iconSize' => 22])
                </span>
                <p class="sk-empty__text">No scheduled events.</p>
            </div>
        @endforelse
    </div>
</section>
