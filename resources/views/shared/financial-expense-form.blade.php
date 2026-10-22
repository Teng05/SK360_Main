@php
    $restore = old('_dialog') === $dialogId;
    $projectChoice = $restore ? old('project_id') : ($expense->project_id ?? '');
    $formProjects = $budget->projects->where('implementation_status', '!=', 'cancelled');
    $currentProof = $expense ? $expense->attachments->where('owner_revision_no', $expense->revision_no)->sortByDesc('attachment_id')->first() : null;
@endphp
<form data-expense-form method="post" enctype="multipart/form-data" action="{{ $expense ? route('sk_chairman.finances.expenses.update', $expense->expense_id) : route('sk_chairman.finances.expenses.store') }}">
    @csrf
    <input type="hidden" name="_dialog" value="{{ $dialogId }}"><input type="hidden" name="_barangay" value="{{ $budget->barangay_id }}"><input type="hidden" name="annual_budget_id" value="{{ $budget->annual_budget_id }}">
    @if($expense)<input type="hidden" name="revision_no" value="{{ $expense->revision_no }}">
    @else<input type="hidden" name="client_request_id" value="{{ $restore ? old('client_request_id', (string)\Illuminate\Support\Str::uuid()) : (string)\Illuminate\Support\Str::uuid() }}">@endif
    <div class="budget-form-grid p-6">
        <h3 class="budget-wide sk-section-title">Project Information</h3>
        <label class="budget-wide"><span class="sk-label">Related Project</span><select name="project_id" class="sk-control w-full" required data-project-select><option value="">Select a project</option>@foreach($formProjects as $project)<option value="{{ $project->project_id }}" data-category="{{ $project->category }}" @selected($projectChoice == $project->project_id)>{{ $project->name }}</option>@endforeach<option value="new" @selected($projectChoice === 'new')>+ Add New Project</option></select></label>
        <p class="budget-wide text-sm text-gray-600" data-existing-category></p>
        <div class="budget-wide budget-form-grid" data-new-project hidden>
            <label class="budget-wide"><span class="sk-label">Project Name</span><input name="project_name" maxlength="255" value="{{ $restore ? old('project_name') : '' }}" class="sk-control w-full"></label>
            <label><span class="sk-label">Project Category</span><select name="category" data-category-select class="sk-control w-full"><option value="">Select category</option>@foreach($categories as $category)<option @selected($restore && old('category') === $category)>{{ $category }}</option>@endforeach<option value="new" @selected($restore && old('category') === 'new')>+ Add New Category</option></select></label>
            <label class="budget-wide" data-new-category hidden><span class="sk-label">New Category Name</span><input name="new_category" maxlength="100" value="{{ $restore ? old('new_category') : '' }}" class="sk-control w-full"></label>
        </div>
        <h3 class="budget-wide sk-section-title">Expense Information</h3>
        <label><span class="sk-label">Expense Amount (PHP)</span><input name="amount" type="number" min="0.01" step="0.01" value="{{ $restore ? old('amount') : ($expense->amount ?? '') }}" class="sk-control w-full" required></label>
        <label><span class="sk-label">Expense Date</span><input name="expense_date" type="date" value="{{ $restore ? old('expense_date') : ($expense->expense_date ?? '') }}" max="{{ now()->toDateString() }}" class="sk-control w-full" required></label>
        <label class="budget-wide"><span class="sk-label">Expense Description</span><textarea name="description" class="sk-control w-full" rows="3" maxlength="5000" required>{{ $restore ? old('description') : ($expense->description ?? '') }}</textarea></label>
        <label><span class="sk-label">Receipt / Reference (optional)</span><input name="document_reference" maxlength="150" value="{{ $restore ? old('document_reference') : ($expense->document_reference ?? '') }}" class="sk-control w-full"></label>
        <label><span class="sk-label">Supplier / Payee (optional)</span><input name="payee_name" maxlength="255" value="{{ $restore ? old('payee_name') : ($expense->payee_name ?? '') }}" class="sk-control w-full"></label>
        <h3 class="budget-wide sk-section-title">Supporting Document</h3>
        @if($restore)<p class="budget-wide text-sm text-red-700">Please select the file again if a new or replacement proof is required.</p>@endif
        <label class="budget-wide budget-upload"><span class="sk-label">Proof of Expense {{ $expense ? '(optional replacement)' : '(required)' }}</span><span class="block text-sm text-gray-500 mb-3">PDF, JPEG or PNG, up to 5 MB. {{ $expense ? 'Keep the current proof by leaving this empty.' : 'Upload a receipt or supporting document.' }}</span><input name="proof" type="file" accept="application/pdf,image/jpeg,image/png" class="block w-full text-sm" @required(!$expense)></label>
        <label class="budget-wide flex items-start gap-3 text-sm"><input type="checkbox" name="public_proof" value="1" @checked($restore ? old('public_proof') : ($currentProof && $currentProof->published_at))><span>I confirm the current or replacement proof is suitable for public disclosure and contains no sensitive personal, banking, or confidential information. Leave unchecked to keep the proof private. Expense details and amounts are publicly reported.</span></label>
    </div>
    <div class="budget-dialog-footer"><button type="button" class="sk-btn sk-btn--secondary" data-close-dialog>Cancel</button><button class="sk-btn sk-btn--primary">{{ $expense ? 'Save Changes' : 'Save Expense' }}</button></div>
</form>
