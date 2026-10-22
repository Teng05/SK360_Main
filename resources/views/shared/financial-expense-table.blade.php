@php
    $rows = collect();
    foreach ($visibleBudgets as $owner) foreach ($owner->expenses as $record) if(in_array($record->status, \App\Services\BudgetMonitoringService::ACTIVE_EXPENSES, true)) $rows->push(['budget' => $owner, 'expense' => $record]);
    $rows = $rows->sortByDesc(fn($row) => $row['expense']->expense_date.' '.str_pad((string)$row['expense']->expense_id, 20, '0', STR_PAD_LEFT));
    if ($recent) $rows = $rows->take(5);
@endphp
<div class="overflow-x-auto"><table class="budget-table"><thead><tr>@if($isPresident)<th>Barangay</th>@endif<th>Expense Description</th><th>Project / Category</th><th>Date</th><th class="budget-money">Amount</th><th>Receipt / Reference</th><th>Actions</th></tr></thead><tbody>
@forelse($rows as $row)@php($record = $row['expense'])
<tr>@if($isPresident)<td>{{ $row['budget']->barangay_name }}</td>@endif<td class="budget-description font-semibold">{{ $record->description }}</td><td>{{ $record->project_name }}<span class="block text-xs text-gray-500 mt-1">{{ $record->project_category }}</span></td><td>{{ \Carbon\Carbon::parse($record->expense_date)->format('M d, Y') }}</td><td class="budget-money">PHP {{ number_format((float)$record->amount, 2) }}</td><td>{{ $record->document_reference ?: 'Not supplied' }}</td><td><button type="button" class="sk-icon-btn border border-gray-200 rounded-lg" data-open-dialog="expense-{{ $record->expense_id }}" title="View Expense Details" aria-label="View Expense Details">@include('partials.ui.icon', ['icon' => 'eye', 'iconSize' => 18])</button></td></tr>
@empty<tr><td colspan="{{ $isPresident ? 7 : 6 }}"><div class="sk-empty"><h3 class="sk-section-title">No expenses recorded yet</h3><p class="sk-section-subtitle">{{ $isPresident ? 'Reported expenses will appear here for monitoring.' : 'Record an expense with its supporting proof.' }}</p></div></td></tr>@endforelse
</tbody></table></div>
