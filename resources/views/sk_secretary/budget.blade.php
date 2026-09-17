{{-- File guide: Blade view template for resources/views/sk_secretary/budget.blade.php. --}}
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
    const budgetTemplateRoute = @json(route('sk_secretary.budget.template.create'));

    let currentBudgetCategory = null;

    function openSlotSubmission(
        slotId,
        title,
        category = null,
        fiscalYear = null,
        reportType = null,
        fiscalMonth = null,
        fiscalQuarter = null,
        fiscalHalf = null,
        templateAvailable = false
    ) {
        currentBudgetCategory = category;

        document.getElementById('slotIdField').value = slotId;
        document.getElementById('slotSubmissionTitle').textContent = title;

        updateBudgetSlotDetails(
            category,
            fiscalYear,
            reportType,
            fiscalMonth,
            fiscalQuarter,
            fiscalHalf
        );

        const annualBudgetAmountSection = document.getElementById('annualBudgetAmountSection');
        const annualBudgetAmount = document.getElementById('annualBudgetAmount');

        const isAnnualBudget = category === 'annual_budget';

        if (annualBudgetAmountSection) {
            annualBudgetAmountSection.classList.toggle('hidden', !isAnnualBudget);
        }

        if (annualBudgetAmount) {
            annualBudgetAmount.required = isAnnualBudget;

            if (!isAnnualBudget) {
                annualBudgetAmount.value = '';
            }
        }

        const templateLabel = document.getElementById('templateModeLabel');
        const templateRadio = document.querySelector('input[name="sub_method"][value="template"]');
        const pdfRadio = document.querySelector('input[name="sub_method"][value="pdf"]');
        const unavailableNote = document.getElementById('templateUnavailableNote');

        if (templateAvailable) {
            templateLabel?.classList.remove('hidden');
            unavailableNote?.classList.add('hidden');

            if (templateRadio) {
                templateRadio.checked = true;
            }
        } else {
            templateLabel?.classList.add('hidden');
            unavailableNote?.classList.remove('hidden');

            if (pdfRadio) {
                pdfRadio.checked = true;
            }
        }

        document.getElementById('slotSubmissionModal').classList.remove('hidden');

        syncBudgetSubmissionMode();
    }

    function closeSlotSubmission() {
        document.getElementById('slotSubmissionModal').classList.add('hidden');
    }

    function updateBudgetSlotDetails(
        category,
        fiscalYear,
        reportType,
        fiscalMonth,
        fiscalQuarter,
        fiscalHalf
    ) {
        const categoryLabels = {
            annual_budget: 'Annual Budget',
            supplemental_budget: 'Supplemental Budget',
            coa_report: 'Financial / COA Report'
        };

        const monthNames = [
            '',
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December'
        ];

        let periodText = '';

        if (category === 'coa_report') {
            if (reportType === 'monthly') {
                periodText = `Monthly - ${monthNames[Number(fiscalMonth)] || ''}`;
            } else if (reportType === 'quarterly') {
                periodText = `Quarterly - ${fiscalQuarter || ''}`;
            } else if (reportType === 'semi_annual') {
                periodText = fiscalHalf === 'H1'
                    ? 'Semi-Annual - First Half'
                    : 'Semi-Annual - Second Half';
            } else if (reportType === 'annual') {
                periodText = 'Annual';
            }
        }

        const categoryField = document.getElementById('modalBudgetCategory');
        const yearField = document.getElementById('modalFiscalYear');
        const periodField = document.getElementById('modalReportingPeriod');
        const periodRow = document.getElementById('modalPeriodRow');

        if (categoryField) {
            categoryField.textContent = categoryLabels[category] || 'Budget / Financial Report';
        }

        if (yearField) {
            yearField.textContent = fiscalYear ? `FY ${fiscalYear}` : '--';
        }

        if (periodRow) {
            periodRow.classList.toggle('hidden', category !== 'coa_report');
        }

        if (periodField) {
            periodField.textContent = periodText || '--';
        }
    }

    function toggleSlotFile(show) {
        document.getElementById('slotFileSection').classList.toggle('hidden', !show);
    }

    function syncBudgetSubmissionMode() {
        const selectedMethod = document.querySelector('input[name="sub_method"]:checked')?.value || 'pdf';
        const submitButton = document.getElementById('slotSubmitButton');
        const fileInput = document.querySelector('input[name="report_file"]');
        const fileName = document.getElementById('slotFileName');

        document.querySelectorAll('.slot-mode-label').forEach((label) => {
            if (label.classList.contains('hidden')) {
                return;
            }

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
            if (selectedMethod === 'template') {
                submitButton.textContent = 'Continue to Template';
            } else if (currentBudgetCategory === 'annual_budget') {
                submitButton.textContent = 'Submit Annual Budget';
            } else {
                submitButton.textContent = 'Submit Report';
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const fileInput = document.querySelector('input[name="report_file"]');
        const fileName = document.getElementById('slotFileName');

        if (fileInput && fileName) {
            fileInput.addEventListener('change', function () {
                fileName.textContent = this.files.length
                    ? this.files[0].name
                    : '';
            });
        }

        document.querySelectorAll('input[name="sub_method"]').forEach((input) => {
            input.addEventListener('change', syncBudgetSubmissionMode);
        });

        document.querySelectorAll('.slot-mode-label').forEach((label) => {
            label.addEventListener('click', function () {
                if (this.classList.contains('hidden')) {
                    return;
                }

                const input = this.querySelector('input[name="sub_method"]');

                if (input) {
                    input.checked = true;
                    syncBudgetSubmissionMode();
                }
            });
        });

        syncBudgetSubmissionMode();
    });
</script>
@endpush