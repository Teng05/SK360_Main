{{-- File guide: Blade view template for resources/views/shared/budget-template-form.blade.php. --}}
@extends('layouts.app')

@section('title', 'Budget Template | SK 360')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .template-sheet table {
            border-collapse: collapse;
            width: 100%;
        }

        .template-sheet th,
        .template-sheet td {
            border: 1px solid #222;
            padding: 0;
            vertical-align: middle;
        }

        .template-sheet input {
            width: 100%;
            border: 0;
            outline: none;
            padding: 6px 8px;
            font-size: 12px;
            background: transparent;
        }

        .template-sheet .object-header-input {
            min-height: 72px;
            white-space: normal;
            text-align: center;
            font-weight: 700;
            line-height: 1.35;
        }

        .template-sheet .heading-cell {
            font-weight: 700;
            text-align: center;
            padding: 2px 0;
        }

        .template-sheet .label-cell {
            font-weight: 700;
            background: #f8fafc;
            padding: 4px 6px;
            font-size: 12px;
        }

        .template-sheet .meta-cell {
            font-size: 12px;
        }

        .template-sheet .section-row td {
            font-weight: 700;
            background: #f8fafc;
            padding: 4px 6px;
            font-size: 12px;
        }

        .template-sheet .summary-label {
            font-weight: 700;
            font-size: 12px;
            background: #f8fafc;
            padding: 4px 6px;
        }

        .template-sheet .plain-cell {
            padding: 4px 6px;
            font-size: 12px;
        }
    </style>
@endsection

@section('content')
// Quarterly Template
@php
    $reportType = $reportType ?? 'quarterly';
    $reportingYear = $reportingYear ?? now()->year;
    $reportingMonth = $reportingMonth ?? now()->month;
    $reportingQuarter = $reportingQuarter ?? 'Q1';
    $periodDate = \Carbon\Carbon::create((int) $reportingYear, max(1, (int) $reportingMonth), 1);
    $certifiedDate = old('certified_date', now()->toDateString());
@endphp
<div class="min-h-screen bg-slate-100 py-8">
    <div class="mx-auto max-w-7xl px-4">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-900">Budget Template</h1>
                <p class="mt-1 text-sm text-slate-500">{{ ucfirst($reportType) }} template for {{ $slot->title }} - {{ $barangayName }}</p>
            </div>
            <a href="{{ $backRoute }}" class="rounded-xl bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-300">Back</a>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ $submitRoute }}" class="template-sheet rounded-3xl border border-slate-300 bg-white p-6 shadow-sm">
            @csrf
            <input type="hidden" name="slot_id" value="{{ $slot->slot_id }}">
            <input type="hidden" name="report_type" value="{{ $reportType }}">
            <input type="hidden" name="reporting_year" value="{{ $reportingYear }}">
            <input type="hidden" name="reporting_month" value="{{ $reportingMonth }}">
            <input type="hidden" name="reporting_quarter" value="{{ $reportingQuarter }}">

            //Monthly Template

            @if ($reportType === 'monthly')
                @php
                    $bankRows = old('bank_rows', [
                        ['particulars' => 'Unadjusted Balances', 'rcb' => '', 'bank' => '', 'comment' => ''],
                        ['particulars' => 'Reconciling Items:', 'rcb' => '', 'bank' => '', 'comment' => ''],
                        ['particulars' => 'Check Issued not taken up:', 'rcb' => '', 'bank' => '', 'comment' => ''],
                        ['particulars' => '    In the Books', 'rcb' => '', 'bank' => '', 'comment' => ''],
                        ['particulars' => '    By the Bank', 'rcb' => '', 'bank' => '', 'comment' => ''],
                        ['particulars' => 'Check Issued Overstated:', 'rcb' => '', 'bank' => '', 'comment' => ''],
                        ['particulars' => '    In the Books', 'rcb' => '', 'bank' => '', 'comment' => ''],
                        ['particulars' => '    By the Bank', 'rcb' => '', 'bank' => '', 'comment' => ''],
                        ['particulars' => 'Check Issued Understated:', 'rcb' => '', 'bank' => '', 'comment' => ''],
                        ['particulars' => 'Deposits not taken up:', 'rcb' => '', 'bank' => '', 'comment' => ''],
                        ['particulars' => 'Deposits Overstated:', 'rcb' => '', 'bank' => '', 'comment' => ''],
                        ['particulars' => 'Deposit Understated:', 'rcb' => '', 'bank' => '', 'comment' => ''],
                        ['particulars' => 'Bank Debit/Credit Memos, Not taken up in the books:', 'rcb' => '', 'bank' => '', 'comment' => ''],
                        ['particulars' => '    Debit Memo', 'rcb' => '', 'bank' => '', 'comment' => ''],
                        ['particulars' => '    Credit Memo', 'rcb' => '', 'bank' => '', 'comment' => ''],
                        ['particulars' => 'Other Reconciling Items', 'rcb' => '', 'bank' => '', 'comment' => ''],
                        ['particulars' => 'Adjusted Balances', 'rcb' => '', 'bank' => '', 'comment' => ''],
                    ]);
                @endphp
                <div class="mb-4 flex justify-end">
                    <span class="text-sm font-medium text-slate-700">Annex 30</span>
                </div>
                <div class="mb-5 text-center">
                    <h2 class="text-xl font-black uppercase text-slate-900">Bank Reconciliation Statement</h2>
                    <p class="text-sm text-slate-700">For the month of {{ $periodDate->format('F Y') }}</p>
                </div>
                <table class="mb-4">
                    <tr>
                        <td colspan="2" class="plain-cell">SK of Barangay: {{ $barangayName }}</td>
                        <td colspan="2" class="plain-cell">Bank Name: <input type="text" name="bank_name" value="{{ old('bank_name', 'DBP') }}"></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="plain-cell">City/Municipality: <input type="text" name="city" value="{{ old('city', 'LIPA CITY') }}"></td>
                        <td colspan="2" class="plain-cell">Branch: <input type="text" name="branch" value="{{ old('branch', 'LIPA') }}"></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="plain-cell">Province: <input type="text" name="province" value="{{ old('province', 'BATANGAS') }}"></td>
                        <td colspan="2" class="plain-cell">Current Account No.: <input type="text" name="current_account_no" value="{{ old('current_account_no') }}"></td>
                    </tr>
                    <tr>
                        <td class="heading-cell">Particulars</td>
                        <td class="heading-cell">RCB</td>
                        <td class="heading-cell">Bank</td>
                        <td class="heading-cell">Explanatory Comment</td>
                    </tr>
                    @foreach ($bankRows as $index => $row)
                        <tr>
                            <td><input type="text" name="bank_rows[{{ $index }}][particulars]" value="{{ $row['particulars'] ?? '' }}"></td>
                            <td><input type="text" name="bank_rows[{{ $index }}][rcb]" value="{{ $row['rcb'] ?? '' }}"></td>
                            <td><input type="text" name="bank_rows[{{ $index }}][bank]" value="{{ $row['bank'] ?? '' }}"></td>
                            <td><input type="text" name="bank_rows[{{ $index }}][comment]" value="{{ $row['comment'] ?? '' }}"></td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="2" class="center" style="padding: 28px 8px;">
                            Prepared and Certified Correct by:<br><br>
                            <input type="text" name="prepared_by" value="{{ old('prepared_by', $fullName) }}" style="text-align:center; font-weight:700;">
                            <div>Signature over Printed Name<br>of SK Treasurer</div>
                            <input type="date" name="certified_date" value="{{ $certifiedDate }}" style="text-align:center; font-weight:700;">
                            <div>Date</div>
                        </td>
                        <td colspan="2" class="center" style="padding: 28px 8px;">
                            Approved by:<br><br>
                            <input type="text" name="approved_by" value="{{ old('approved_by') }}" style="text-align:center; font-weight:700;">
                            <div>Signature over Printed Name<br>of SK Chairperson</div>
                            <input type="date" value="{{ $certifiedDate }}" style="text-align:center; font-weight:700;">
                            <div>Date</div>
                        </td>
                    </tr>
                </table>

                //Anual Template
            @elseif ($reportType === 'annual')
                @php
                    $inventoryRows = old('inventory_rows', array_fill(0, 12, [
                        'article' => '', 'description' => '', 'property_no' => '', 'unit' => '', 'unit_cost' => '',
                        'balance' => '', 'on_hand' => '', 'shortage_quantity' => '', 'shortage_value' => '', 'remarks' => '',
                    ]));
                    $committeeMembers = old('committee_members', ['', '', '']);
                @endphp
                <div class="mb-4 flex justify-end">
                    <span class="text-sm font-medium text-slate-700">Annex 40</span>
                </div>
                <div class="mb-5 text-center">
                    <h2 class="text-xl font-black uppercase text-slate-900">Report on Inventory of Donated Property and Equipment</h2>
                    <p class="text-sm text-slate-700">As at {{ \Carbon\Carbon::parse($certifiedDate)->format('F d, Y') }}</p>
                </div>
                <div class="mb-4 grid gap-3 md:grid-cols-3">
                    <label class="text-xs font-bold text-slate-600">
                        City/Municipality
                        <input type="text" name="city" value="{{ old('city', 'LIPA CITY') }}" class="mt-1 rounded-xl border px-4 py-3 text-sm" placeholder="City/Municipality">
                    </label>
                    <label class="text-xs font-bold text-slate-600">
                        Province
                        <input type="text" name="province" value="{{ old('province', 'BATANGAS') }}" class="mt-1 rounded-xl border px-4 py-3 text-sm" placeholder="Province">
                    </label>
                    <label class="text-xs font-bold text-slate-600">
                        Report Date
                        <input type="date" name="certified_date" value="{{ $certifiedDate }}" class="mt-1 rounded-xl border px-4 py-3 text-sm">
                    </label>
                </div>

                <div class="mb-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-800">
                    <div class="flex flex-wrap items-end gap-2">
                        <span>For which</span>
                        <input type="text" name="accountable_officer" value="{{ old('accountable_officer', $fullName) }}" class="min-w-[220px] flex-1 border-0 border-b border-slate-500 bg-transparent px-2 py-1 font-bold uppercase outline-none" placeholder="Accountable officer name">
                        <span>,</span>
                        <input type="text" name="official_designation" value="{{ old('official_designation', 'SK Treasurer') }}" class="min-w-[160px] border-0 border-b border-slate-500 bg-transparent px-2 py-1 font-bold outline-none" placeholder="Official designation">
                        <span>is accountable, having assumed such accountability on</span>
                        <input type="date" name="assumption_date" value="{{ old('assumption_date', now()->toDateString()) }}" class="border-0 border-b border-slate-500 bg-transparent px-2 py-1 font-bold outline-none">
                        <span>.</span>
                    </div>
                </div>
                <table class="mb-4">
                    <tr>
                        <th>Article</th><th>Item Description</th><th>Property No.</th><th>Unit</th><th>Unit Cost</th><th>Balance Per RDPE</th><th>On Hand Per Count</th><th>Shortage Qty</th><th>Shortage Value</th><th>Remarks</th>
                    </tr>
                    @foreach ($inventoryRows as $index => $row)
                        <tr>
                            @foreach (['article', 'description', 'property_no', 'unit', 'unit_cost', 'balance', 'on_hand', 'shortage_quantity', 'shortage_value', 'remarks'] as $field)
                                <td><input type="{{ in_array($field, ['unit_cost', 'balance', 'on_hand', 'shortage_quantity', 'shortage_value'], true) ? 'number' : 'text' }}" step="0.01" name="inventory_rows[{{ $index }}][{{ $field }}]" value="{{ $row[$field] ?? '' }}"></td>
                            @endforeach
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="10" class="plain-cell">Prepared and Certified Correct by:</td>
                    </tr>
                    <tr>
                        <td colspan="10" style="padding: 18px 8px;">
                            <div class="grid gap-4 md:grid-cols-4">
                                @for ($i = 0; $i < 3; $i++)
                                    <div class="center">
                                        <input type="text" name="committee_members[]" value="{{ $committeeMembers[$i] ?? '' }}" style="text-align:center; font-weight:700;" placeholder="Committee Member">
                                        <div class="text-xs">Signature over Printed Name<br>Member, Inventory Committee</div>
                                    </div>
                                @endfor
                                <div class="center">
                                    <input type="text" name="chairperson_name" value="{{ old('chairperson_name') }}" style="text-align:center; font-weight:700;" placeholder="SK Chairperson">
                                    <div class="text-xs">Signature over Printed Name<br>SK Chairperson</div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            @else
            <div class="mb-4 flex justify-end">
                <span class="text-sm font-medium text-slate-700">Annex 3</span>
            </div>

            <div class="mb-4 text-center">
                <h2 class="text-2xl font-black uppercase text-slate-900">Registry of Specific Purpose Fund, Commitments, Payments and Balances</h2>
                <p class="text-xl font-black uppercase text-slate-900">Maintenance and Other Operating Expenses</p>
            </div>

            @php
                $rows = old('rows', [
                    ['particulars' => 'Totals brought forward', 'date' => '', 'reference' => '', 'total_amount' => '', 'object_1' => '', 'object_2' => '', 'object_3' => '', 'object_4' => ''],
                    ['particulars' => 'Specific Purpose Fund for the period:', 'date' => '', 'reference' => '', 'total_amount' => '', 'object_1' => '', 'object_2' => '', 'object_3' => '', 'object_4' => ''],
                    ['particulars' => 'Total Specific Purpose Fund carried forward', 'date' => '', 'reference' => '', 'total_amount' => '', 'object_1' => '', 'object_2' => '', 'object_3' => '', 'object_4' => ''],
                    ['particulars' => 'Totals brought forward', 'date' => '', 'reference' => '', 'total_amount' => '', 'object_1' => '', 'object_2' => '', 'object_3' => '', 'object_4' => ''],
                    ['particulars' => 'Commitments/Adjustments for the period:', 'date' => '', 'reference' => '', 'total_amount' => '', 'object_1' => '', 'object_2' => '', 'object_3' => '', 'object_4' => ''],
                    ['particulars' => 'Total Commitments carried forward', 'date' => '', 'reference' => '', 'total_amount' => '', 'object_1' => '', 'object_2' => '', 'object_3' => '', 'object_4' => ''],
                    ['particulars' => 'Totals brought forward', 'date' => '', 'reference' => '', 'total_amount' => '', 'object_1' => '', 'object_2' => '', 'object_3' => '', 'object_4' => ''],
                    ['particulars' => 'Payments/Adjustments for the period:', 'date' => '', 'reference' => '', 'total_amount' => '', 'object_1' => '', 'object_2' => '', 'object_3' => '', 'object_4' => ''],
                    ['particulars' => 'Total Payments carried forward', 'date' => '', 'reference' => '', 'total_amount' => '', 'object_1' => '', 'object_2' => '', 'object_3' => '', 'object_4' => ''],
                ]);
            @endphp

            <table class="mb-4">
                <tr>
                    <td colspan="5" class="meta-cell">
                        <div class="flex items-center">
                            <span class="px-2 text-[12px]">SK of Barangay:</span>
                            <input type="text" value="{{ $barangayName }}" readonly class="font-medium">
                        </div>
                    </td>
                    <td colspan="2" class="meta-cell">
                        <div class="flex items-center">
                            <span class="px-2 text-[12px]">City/Municipality:</span>
                            <input type="text" name="city" value="{{ old('city', 'LIPA CITY') }}">
                        </div>
                    </td>
                    <td class="meta-cell">
                        <div class="flex items-center">
                            <span class="px-2 text-[12px]">Sheet No.:</span>
                            <input type="text" name="sheet_no" value="{{ old('sheet_no', now()->format('Y').'-'.str_pad((string) $slot->slot_id, 3, '0', STR_PAD_LEFT)) }}">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="5" class="meta-cell">
                        <div class="flex items-center">
                            <span class="px-2 text-[12px]">Budget Monitoring Officer:</span>
                            <input type="text" name="monitoring_officer" value="{{ old('monitoring_officer', $fullName) }}">
                        </div>
                    </td>
                    <td colspan="3" class="meta-cell">
                        <div class="flex items-center">
                            <span class="px-2 text-[12px]">Province:</span>
                            <input type="text" name="province" value="{{ old('province', 'BATANGAS') }}">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="8" class="meta-cell">
                        <div class="flex items-center">
                            <span class="px-2 text-[12px]">Program/Project/Activity:</span>
                            <input type="text" name="program_project_activity" value="{{ old('program_project_activity', 'NOT APPLICABLE') }}">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td rowspan="2" class="heading-cell w-[28%]">Particulars</td>
                    <td rowspan="2" class="heading-cell w-[10%]">Date</td>
                    <td rowspan="2" class="heading-cell w-[10%]">Reference</td>
                    <td rowspan="2" class="heading-cell w-[12%]">Total Amount</td>
                    <td colspan="4" class="heading-cell">Breakdown of Object of Expenditures</td>
                </tr>
                <tr>
                    <td class="heading-cell w-[10%]">
                        <input type="text" name="object_headers[0]" value="{{ old('object_headers.0', 'Object 1') }}" placeholder="Object 1" class="object-header-input">
                    </td>
                    <td class="heading-cell w-[10%]">
                        <input type="text" name="object_headers[1]" value="{{ old('object_headers.1', 'Object 2') }}" placeholder="Object 2" class="object-header-input">
                    </td>
                    <td class="heading-cell w-[10%]">
                        <input type="text" name="object_headers[2]" value="{{ old('object_headers.2', 'Object 3') }}" placeholder="Object 3" class="object-header-input">
                    </td>
                    <td class="heading-cell w-[10%]">
                        <input type="text" name="object_headers[3]" value="{{ old('object_headers.3', 'Insert additional Object of Expenditures') }}" placeholder="Insert additional Object of Expenditures" class="object-header-input">
                    </td>
                </tr>

                <tr class="section-row"><td colspan="8">a. Specific Purpose Fund</td></tr>
                @for ($i = 0; $i <= 2; $i++)
                    <tr>
                        <td><input type="text" name="rows[{{ $i }}][particulars]" value="{{ $rows[$i]['particulars'] ?? '' }}"></td>
                        <td><input type="date" name="rows[{{ $i }}][date]" value="{{ $rows[$i]['date'] ?? '' }}"></td>
                        <td><input type="text" name="rows[{{ $i }}][reference]" value="{{ $rows[$i]['reference'] ?? '' }}"></td>
                        <td><input type="number" step="0.01" name="rows[{{ $i }}][total_amount]" value="{{ $rows[$i]['total_amount'] ?? '' }}"></td>
                        <td><input type="number" step="0.01" name="rows[{{ $i }}][object_1]" value="{{ $rows[$i]['object_1'] ?? '' }}"></td>
                        <td><input type="number" step="0.01" name="rows[{{ $i }}][object_2]" value="{{ $rows[$i]['object_2'] ?? '' }}"></td>
                        <td><input type="number" step="0.01" name="rows[{{ $i }}][object_3]" value="{{ $rows[$i]['object_3'] ?? '' }}"></td>
                        <td><input type="number" step="0.01" name="rows[{{ $i }}][object_4]" value="{{ $rows[$i]['object_4'] ?? '' }}"></td>
                    </tr>
                @endfor

                <tr class="section-row"><td colspan="8">b. Commitments</td></tr>
                @for ($i = 3; $i <= 5; $i++)
                    <tr>
                        <td><input type="text" name="rows[{{ $i }}][particulars]" value="{{ $rows[$i]['particulars'] ?? '' }}"></td>
                        <td><input type="date" name="rows[{{ $i }}][date]" value="{{ $rows[$i]['date'] ?? '' }}"></td>
                        <td><input type="text" name="rows[{{ $i }}][reference]" value="{{ $rows[$i]['reference'] ?? '' }}"></td>
                        <td><input type="number" step="0.01" name="rows[{{ $i }}][total_amount]" value="{{ $rows[$i]['total_amount'] ?? '' }}"></td>
                        <td><input type="number" step="0.01" name="rows[{{ $i }}][object_1]" value="{{ $rows[$i]['object_1'] ?? '' }}"></td>
                        <td><input type="number" step="0.01" name="rows[{{ $i }}][object_2]" value="{{ $rows[$i]['object_2'] ?? '' }}"></td>
                        <td><input type="number" step="0.01" name="rows[{{ $i }}][object_3]" value="{{ $rows[$i]['object_3'] ?? '' }}"></td>
                        <td><input type="number" step="0.01" name="rows[{{ $i }}][object_4]" value="{{ $rows[$i]['object_4'] ?? '' }}"></td>
                    </tr>
                @endfor

                <tr class="section-row"><td colspan="8">c. Payments</td></tr>
                @for ($i = 6; $i <= 8; $i++)
                    <tr>
                        <td><input type="text" name="rows[{{ $i }}][particulars]" value="{{ $rows[$i]['particulars'] ?? '' }}"></td>
                        <td><input type="date" name="rows[{{ $i }}][date]" value="{{ $rows[$i]['date'] ?? '' }}"></td>
                        <td><input type="text" name="rows[{{ $i }}][reference]" value="{{ $rows[$i]['reference'] ?? '' }}"></td>
                        <td><input type="number" step="0.01" name="rows[{{ $i }}][total_amount]" value="{{ $rows[$i]['total_amount'] ?? '' }}"></td>
                        <td><input type="number" step="0.01" name="rows[{{ $i }}][object_1]" value="{{ $rows[$i]['object_1'] ?? '' }}"></td>
                        <td><input type="number" step="0.01" name="rows[{{ $i }}][object_2]" value="{{ $rows[$i]['object_2'] ?? '' }}"></td>
                        <td><input type="number" step="0.01" name="rows[{{ $i }}][object_3]" value="{{ $rows[$i]['object_3'] ?? '' }}"></td>
                        <td><input type="number" step="0.01" name="rows[{{ $i }}][object_4]" value="{{ $rows[$i]['object_4'] ?? '' }}"></td>
                    </tr>
                @endfor

                <tr>
                    <td colspan="3" class="summary-label">Balance, Available Specific Purpose Fund (a-b)</td>
                    <td colspan="5"><input type="number" step="0.01" name="available_balance" value="{{ old('available_balance') }}"></td>
                </tr>
                <tr>
                    <td colspan="3" class="summary-label">Balance, Unpaid Commitments (b-c)</td>
                    <td colspan="5"><input type="number" step="0.01" name="unpaid_commitments" value="{{ old('unpaid_commitments') }}"></td>
                </tr>
                <tr>
                    <td colspan="8" style="padding: 6px; font-size: 12px;">Prepared and Certified Correct by:</td>
                </tr>
                <tr>
                    <td colspan="4" class="center" style="padding: 18px 8px 10px;">
                        <div class="font-bold uppercase">{{ old('monitoring_officer', $fullName) }}</div>
                        <div>Signature over Printed Name</div>
                        <div>Budget Monitoring Officer</div>
                    </td>
                    <td colspan="4" class="center" style="padding: 18px 8px 10px;">
                        <input type="date" name="certified_date" value="{{ old('certified_date', now()->toDateString()) }}" style="text-align:center; font-weight:700;">
                        <div>Date</div>
                    </td>
                </tr>
            </table>

            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-800">Total Specific Purpose Fund carried forward</label>
                    <input type="number" step="0.01" name="spf_carried_forward" value="{{ old('spf_carried_forward') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-800">Total Commitments carried forward</label>
                    <input type="number" step="0.01" name="commitments_carried_forward" value="{{ old('commitments_carried_forward') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-800">Total Payments carried forward</label>
                    <input type="number" step="0.01" name="payments_carried_forward" value="{{ old('payments_carried_forward') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                </div>
            </div>
            @endif

            <div class="mt-6 flex justify-end">
                <button type="submit" class="rounded-2xl bg-red-600 px-8 py-4 text-sm font-black uppercase tracking-wide text-white hover:bg-red-700">
                    Submit Budget Template
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
