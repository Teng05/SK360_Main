{{-- File guide: Blade view template for resources/views/sk_chairman/budget.blade.php. --}}
@extends('layouts.app')

@section('title', 'Budget | SK 360')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
    @include('shared.submission-slots-page')
@endsection

@push('scripts')
<script>
    const budgetTemplateRoute = @json(route('sk_chairman.budget.template.create'));

    function openSlotSubmission(slotId, title) {
        document.getElementById('slotIdField').value = slotId;
        document.getElementById('slotSubmissionTitle').textContent = title;
        document.getElementById('slotSubmissionModal').classList.remove('hidden');
        syncBudgetSubmissionMode();
    }

    function closeSlotSubmission() {
        document.getElementById('slotSubmissionModal').classList.add('hidden');
    }

    function toggleSlotFile(show) {
        document.getElementById('slotFileSection').classList.toggle('hidden', !show);
    }

    function syncBudgetSubmissionMode() {
        const selectedMethod = document.querySelector('input[name="sub_method"]:checked')?.value || 'template';
        const submitButton = document.getElementById('slotSubmitButton');
        const fileInput = document.querySelector('input[name="report_file"]');
        const fileName = document.getElementById('slotFileName');

        document.querySelectorAll('.slot-mode-label').forEach((label) => {
            const isActive = label.dataset.submissionMode === selectedMethod;
            label.classList.toggle('border-red-500', isActive);
            label.classList.toggle('bg-red-50', isActive);
            label.classList.toggle('text-red-600', isActive);
            label.classList.toggle('border-gray-100', !isActive);
            label.classList.toggle('bg-gray-50', !isActive);
        });

        toggleSlotFile(selectedMethod === 'pdf');

        if (fileInput) {
            fileInput.required = selectedMethod === 'pdf';

            if (selectedMethod !== 'pdf') {
                fileInput.value = '';
                if (fileName) {
                    fileName.textContent = '';
                }
            }
        }

        if (submitButton) {
            submitButton.textContent = selectedMethod === 'template' ? 'Continue to Template' : 'Submit Slot';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const fileInput = document.querySelector('input[name="report_file"]');
        const fileName = document.getElementById('slotFileName');
        if (fileInput && fileName) {
            fileInput.addEventListener('change', function () {
                fileName.textContent = this.files.length ? this.files[0].name : '';
            });
        }

        document.querySelectorAll('input[name="sub_method"]').forEach((input) => {
            input.addEventListener('change', syncBudgetSubmissionMode);
        });

        document.querySelectorAll('.slot-mode-label').forEach((label) => {
            label.addEventListener('click', function () {
                const input = this.querySelector('input[name="sub_method"]');
                if (input) {
                    input.checked = true;
                    syncBudgetSubmissionMode();
                }
            });
        });

        const form = document.querySelector('#slotSubmissionModal form');
        if (form) {
            form.addEventListener('submit', function (event) {
                const selectedMethod = document.querySelector('input[name="sub_method"]:checked')?.value;
                if (selectedMethod === 'template') {
                    event.preventDefault();
                    const slotId = document.getElementById('slotIdField').value;
                    const reportType = document.querySelector('select[name="report_type"]')?.value || 'quarterly';
                    const reportingYear = document.querySelector('input[name="reporting_year"]')?.value || new Date().getFullYear();
                    const reportingMonth = document.querySelector('select[name="reporting_month"]')?.value || '';
                    const reportingQuarter = document.querySelector('select[name="reporting_quarter"]')?.value || '';
                    const params = new URLSearchParams({
                        slot_id: slotId,
                        report_type: reportType,
                        reporting_year: reportingYear,
                        reporting_month: reportingMonth,
                        reporting_quarter: reportingQuarter
                    });
                    window.location.href = `${budgetTemplateRoute}?${params.toString()}`;
                    return;
                }

                const fileInput = document.querySelector('input[name="report_file"]');
                if (selectedMethod === 'pdf' && (!fileInput || fileInput.files.length === 0)) {
                    event.preventDefault();
                    window.alert('Please select a PDF file before submitting.');
                }
            });
        }

        syncBudgetSubmissionMode();
    });
</script>
@endpush
