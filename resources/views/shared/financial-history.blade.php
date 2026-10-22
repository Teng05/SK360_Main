@php
    $history = collect();
    foreach ($visibleBudgets as $owner) {
        foreach ($owner->events as $event) $history->push(['event' => $event, 'label' => $owner->barangay_name.' — Annual allocation']);
        foreach ($owner->expenses as $record) foreach ($record->events as $event) $history->push(['event' => $event, 'label' => $owner->barangay_name.' — '.$record->description]);
    }
@endphp
<h2 class="sk-section-title">Financial history</h2><p class="sk-section-subtitle mb-5">Allocation changes, expense submissions, corrections, review decisions, cancellations, and publication actions.</p>
<ol class="budget-timeline">@forelse($history->sortByDesc(fn($row) => $row['event']->created_at.' '.str_pad((string)$row['event']->financial_event_id, 20, '0', STR_PAD_LEFT)) as $row)<li><p class="font-bold">{{ ucfirst(str_replace('_', ' ', $row['event']->event_type)) }}</p><p class="text-sm text-gray-600 mt-1 budget-wrap">{{ $row['label'] }}</p>@if($row['event']->remarks)<p class="text-sm mt-2 budget-wrap">{{ $row['event']->remarks }}</p>@endif<p class="text-xs text-gray-500 mt-2">{{ $row['event']->created_at }}</p></li>@empty<li class="text-sm text-gray-500">No financial history for the selected records.</li>@endforelse</ol>
@foreach($visibleBudgets as $owner)<p class="text-sm text-gray-500 my-3">{{ $owner->barangay_name }} &middot; Original recorded allocation: PHP {{ number_format((float)$owner->approved_amount, 2) }}</p>@if($isPresident)<div class="budget-setting"><div><h3 class="font-bold">{{ $owner->barangay_name }} — Public summary</h3><p class="sk-section-subtitle">Publish a dated summary of verified totals. Private receipts and payee details stay private.</p>@if($owner->published_at)<p class="text-xs text-gray-500 mt-2">Last authorized: {{ $owner->published_at }}</p>@endif</div><button type="button" class="sk-btn sk-btn--secondary" data-open-dialog="publication-dialog-{{ $owner->annual_budget_id }}">Review &amp; publish</button></div>@endif @endforeach
