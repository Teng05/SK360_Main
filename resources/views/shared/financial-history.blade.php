@php
    $history = collect();
    foreach ($visibleBudgets as $owner) {
        foreach ($owner->events as $event) $history->push(['event' => $event, 'label' => $owner->barangay_name.' — Annual allocation']);
        foreach ($owner->expenses as $record) foreach ($record->events as $event) $history->push(['event' => $event, 'label' => $owner->barangay_name.' — '.$record->description]);
    }
@endphp
<h2 class="sk-section-title">Financial history</h2><p class="sk-section-subtitle mb-5">Allocation changes, expense submissions, edits, deletions, and publication actions.</p>
<ol class="budget-timeline">@forelse($history->sortByDesc(fn($row) => $row['event']->created_at.' '.str_pad((string)$row['event']->financial_event_id, 20, '0', STR_PAD_LEFT)) as $row)<li><p class="font-bold">{{ ucfirst(str_replace('_', ' ', $row['event']->event_type)) }}</p><p class="text-sm text-gray-600 mt-1 budget-wrap">{{ $row['label'] }}</p>@if($row['event']->remarks)<p class="text-sm mt-2 budget-wrap">{{ $row['event']->remarks }}</p>@endif<p class="text-xs text-gray-500 mt-2">{{ $row['event']->created_at }} &middot; {{ $actorNames->get($row['event']->actor_id, 'Official name unavailable') }}</p>@if($isPresident)@if($row['event']->subject_type === 'project_expense')<button type="button" class="budget-link" data-open-dialog="expense-{{ $row['event']->subject_id }}">View retained expense and proof</button>@endif
@endif
</li>
@empty<li class="text-sm text-gray-500">No financial history for the selected records.</li>@endforelse
</ol>
