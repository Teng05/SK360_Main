@extends('layouts.app')
@section('title', 'SK 360 | Financial Archive')
@section('page_css')
<script src="https://cdn.tailwindcss.com"></script>
@endsection
@section('content')
<div class="flex h-screen overflow-hidden bg-gray-100">
    @include('partials.app.sidebar')
    <div class="flex-1 min-w-0 flex flex-col overflow-hidden">
        @include('partials.app.topbar', ['bindBell'=>true,'bindAccount'=>true])
        <main class="flex-1 overflow-y-auto p-8">
            <div class="sk-page-head"><div><h1 class="sk-page-title">Financial Archive</h1><p class="sk-page-subtitle">{{ $budget->barangay_name }} &middot; FY {{ $budget->fiscal_year }} &middot; Administration {{ $budget->start_year }}–{{ $budget->end_year }}</p></div><a class="sk-btn sk-btn--secondary" href="{{ route($prefix.'.archive', ['term_id'=>$budget->term_status === 'completed' ? $budget->recorded_term_id : null]) }}">Back to Archive</a></div>
            <p class="mb-5 text-sm text-gray-600">Read-only preserved records. {{ $budget->term_status === 'completed' ? 'This administration has ended.' : 'Only deleted expenses from this active administration are shown here.' }}</p>
            <section class="sk-card p-5 mb-5"><h2 class="sk-section-title">Annual Allocation Record</h2><p class="mt-2">Recorded allotment after corrections: PHP {{ number_format((float)$effectiveAmount, 2) }}</p><p class="text-sm mt-2">Original amount: PHP {{ number_format((float)$budget->approved_amount, 2) }}</p><p class="text-sm text-gray-500">Approval reference: {{ $budget->approval_reference ?: 'Not supplied' }}</p></section>
            <section class="sk-card p-5 mb-5"><h2 class="sk-section-title">Preserved Projects</h2>@forelse($projects as $project)<p class="mt-3 font-semibold">{{ $project->name }} <span class="text-sm font-normal text-gray-500">&middot; {{ $project->category }}</span></p>@empty<p class="text-sm text-gray-500 mt-3">No related projects.</p>@endforelse</section>
            <section class="sk-card p-5 mb-5"><h2 class="sk-section-title">Expense Records and Supporting Documents</h2>
                @forelse($expenses as $expense)
                    <article class="mt-4 border border-gray-200 rounded-xl p-4"><div class="flex flex-wrap justify-between gap-3"><h3 class="font-bold">{{ $expense->description }}</h3><span class="text-sm font-semibold">{{ in_array($expense->status, ['cancelled','voided'], true) ? 'Deleted / excluded' : 'Reported' }}</span></div><p class="text-sm text-gray-500 mt-2">{{ $expense->project_name }} &middot; {{ $expense->category }} &middot; {{ $expense->expense_date }}</p><p class="font-bold mt-2">PHP {{ number_format((float)$expense->amount, 2) }}</p><p class="text-sm mt-2">Receipt / reference: {{ $expense->document_reference ?: 'Not supplied' }}</p>
                        <div class="flex flex-wrap gap-3 mt-3">@foreach($expense->proofs as $proof)<a class="sk-btn sk-btn--secondary" target="_blank" rel="noopener noreferrer" href="{{ route('archive.financial-proof', $proof->attachment_id) }}">View Proof &middot; Revision {{ $proof->owner_revision_no }}</a><a class="sk-btn sk-btn--secondary" href="{{ route('archive.financial-proof.download', $proof->attachment_id) }}">Download Proof &middot; Revision {{ $proof->owner_revision_no }}</a>@endforeach</div>
                    </article>
                @empty<p class="text-sm text-gray-500 mt-3">No preserved expenses.</p>@endforelse
            </section>
            <section class="sk-card p-5"><h2 class="sk-section-title">Financial History</h2>@forelse($events as $event)<div class="border-t border-gray-100 mt-3 pt-3"><p class="font-semibold">{{ ucfirst(str_replace('_',' ', $event->event_type)) }}</p><p class="text-sm text-gray-500">{{ $event->created_at }} &middot; {{ $names->get($event->actor_id) ?: 'Official name unavailable' }}</p>@if($event->remarks)<p class="text-sm mt-2">{{ $event->remarks }}</p>@endif</div>@empty<p class="text-sm text-gray-500 mt-3">No financial history.</p>@endforelse</section>
        </main>
    </div>
</div>
@endsection
