@php($financeArchivePrefix = auth()->user()->role === 'sk_president' ? 'sk_pres' : 'sk_chairman')
<section class="mb-6 border border-gray-200 rounded-2xl p-5" aria-label="Archived financial records">
    <h2 class="sk-section-title">Financial Records</h2>
    <p class="sk-section-subtitle mb-4">Previous administrations and deleted expenses. Read-only; original records and supporting documents are preserved.</p>
    @forelse($financialArchives as $financialArchive)
        <div class="flex flex-wrap items-center justify-between gap-3 py-3 border-t border-gray-100">
            <div><p class="font-bold">{{ $financialArchive->barangay_name }} &middot; FY {{ $financialArchive->fiscal_year }}</p><p class="text-sm text-gray-500">Administration {{ $financialArchive->start_year }}–{{ $financialArchive->end_year }} &middot; {{ $financialArchive->term_status === 'completed' ? 'Previous administration' : 'Deleted expenses only' }}</p></div>
            <a class="sk-btn sk-btn--secondary" href="{{ route($financeArchivePrefix.'.archive.finances', $financialArchive->annual_budget_id) }}">View Financial Records</a>
        </div>
    @empty
        <p class="text-sm text-gray-500">No financial archive records match these filters.</p>
    @endforelse
</section>
