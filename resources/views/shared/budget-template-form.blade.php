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
    </style>
@endsection

@section('content')
<div class="min-h-screen bg-slate-100 py-8">
    <div class="mx-auto max-w-7xl px-4">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-900">Budget Template</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $slot->title }} for {{ $barangayName }}</p>
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

            <div class="mt-6 flex justify-end">
                <button type="submit" class="rounded-2xl bg-red-600 px-8 py-4 text-sm font-black uppercase tracking-wide text-white hover:bg-red-700">
                    Submit Budget Template
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
