@php
    $financialRole = auth()->user()->role;
    $financialPrefix = $financialRole === 'sk_president' ? 'sk_pres' : 'sk_chairman';
    $financialYear = $year ?? request('year', now()->year);
    $financialContext = ['year' => $financialYear];
    if (request()->filled('barangay_id')) $financialContext['barangay_id'] = request('barangay_id');
    $financialLinks = $financialRole === 'sk_president'
        ? ['overview' => 'Overview', 'allocations' => 'Allocations', 'expenses' => 'Expenses', 'transparency' => 'Public Transparency', 'history' => 'History']
        : ['overview' => 'Overview', 'expenses' => 'Expenses', 'submissions' => 'Budget Submissions', 'history' => 'History'];
@endphp
@if(in_array($financialRole, ['sk_president', 'sk_chairman'], true))
<nav class="budget-tabs sk-card mb-6" aria-label="{{ $financialRole === 'sk_president' ? 'Budget & Finance' : 'Budget' }} sections">
    @foreach($financialLinks as $financialKey => $financialLabel)
        @php
            $financialHref = $financialKey === 'submissions' && $financialRole === 'sk_chairman'
                ? route('sk_chairman.budget', ['year' => $financialYear])
                : route($financialPrefix.'.finances', $financialContext + ['section' => $financialKey]);
            $financialActive = ($financialSection ?? 'overview') === $financialKey;
        @endphp
        <a href="{{ $financialHref }}" @if($financialActive) aria-current="page" @endif>{{ $financialLabel }}</a>
    @endforeach
</nav>
@endif
