@php
    $proofFile = $proofAttachment ?? $expense->attachments->firstWhere('owner_revision_no', $expense->revision_no);
    $proofContext = $proofFile ? [
        'url' => route('finances.attachments', $proofFile->attachment_id),
        'description' => $expense->description, 'barangay' => $budget->barangay_name,
        'amount' => 'PHP '.number_format((float)$expense->amount, 2), 'date' => $expense->expense_date,
        'reference' => $expense->document_reference ?: 'Not supplied', 'filename' => $proofFile->original_name,
    ] : null;
@endphp
@if($proofFile)<button type="button" class="sk-btn sk-btn--secondary" data-view-proof="{{ json_encode($proofContext, JSON_THROW_ON_ERROR) }}" aria-haspopup="dialog">@include('partials.ui.icon', ['icon' => 'eye', 'iconSize' => 16]) {{ $proofLabel ?? 'View Proof' }}</button>@else<span class="text-xs text-gray-500">No proof available</span>@endif
