{{-- File guide: Blade view template for resources/views/shared/budget-template-download.blade.php. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Budget Template</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #111; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #222; padding: 6px; font-size: 12px; vertical-align: top; }
        .center { text-align: center; }
        .no-border { border: none !important; }
        .title { font-size: 26px; font-weight: 700; }
        .subtitle { font-size: 20px; font-weight: 700; }
        .section-title { font-weight: 700; background: #f3f4f6; }
        .small { font-size: 11px; }
        .top-note { text-align: right; font-size: 11px; margin-bottom: 28px; }
        .underline { text-decoration: underline; font-weight: 700; }
    </style>
</head>
<body>
    @php
        $templateType = $data['report_type'] ?? 'quarterly';
        $certifiedDate = $data['certified_date'] ?? now()->toDateString();
        $formattedDate = \Carbon\Carbon::parse($certifiedDate)->format('F d, Y');
        $periodDate = \Carbon\Carbon::create((int) ($data['reporting_year'] ?? now()->year), max(1, (int) ($data['reporting_month'] ?? now()->month)), 1);
        $objectHeaders = $data['object_headers'] ?? [];
        $objectHeader1 = $objectHeaders[0] ?? 'Object 1';
        $objectHeader2 = $objectHeaders[1] ?? 'Object 2';
        $objectHeader3 = $objectHeaders[2] ?? 'Object 3';
        $objectHeader4 = $objectHeaders[3] ?? 'Insert additional Object of Expenditures';
    @endphp

    @if ($templateType === 'monthly')
        <div class="top-note">Annex 30</div>
        <div class="center" style="margin-bottom: 22px;">
            <div style="font-size:16px; font-weight:700;">BANK RECONCILIATION STATEMENT</div>
            <div class="small">For the month of {{ $periodDate->format('F Y') }}</div>
        </div>

        <table>
            <tr>
                <td colspan="2">SK of Barangay: {{ $barangayName }}</td>
                <td colspan="2">Bank Name: {{ $data['bank_name'] ?? '' }}</td>
            </tr>
            <tr>
                <td colspan="2">City/Municipality: {{ $data['city'] ?? '' }}</td>
                <td colspan="2">Branch: {{ $data['branch'] ?? '' }}</td>
            </tr>
            <tr>
                <td colspan="2">Province: {{ $data['province'] ?? '' }}</td>
                <td colspan="2">Current Account No.: {{ $data['current_account_no'] ?? '' }}</td>
            </tr>
            <tr>
                <th>Particulars</th>
                <th>RCB</th>
                <th>Bank</th>
                <th>Explanatory Comment</th>
            </tr>
            @foreach (($data['bank_rows'] ?? []) as $row)
                <tr>
                    <td>{{ $row['particulars'] ?? '' }}</td>
                    <td class="center">{{ $row['rcb'] ?? '' }}</td>
                    <td class="center">{{ $row['bank'] ?? '' }}</td>
                    <td>{{ $row['comment'] ?? '' }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="2" class="center" style="height: 110px;">
                    <div>Prepared and Certified Correct by:</div>
                    <div style="margin-top:32px;" class="underline">{{ $data['prepared_by'] ?? '' }}</div>
                    <div class="small">Signature over Printed Name<br>of SK Treasurer</div>
                    <div style="margin-top:14px;" class="underline">{{ $formattedDate }}</div>
                    <div class="small">Date</div>
                </td>
                <td colspan="2" class="center" style="height: 110px;">
                    <div>Approved by:</div>
                    <div style="margin-top:32px;" class="underline">{{ $data['approved_by'] ?? '' }}</div>
                    <div class="small">Signature over Printed Name<br>of SK Chairperson</div>
                    <div style="margin-top:14px;" class="underline">{{ $formattedDate }}</div>
                    <div class="small">Date</div>
                </td>
            </tr>
        </table>
    @elseif ($templateType === 'annual')
        <div class="top-note">Annex 40</div>
        <div class="center" style="margin-bottom: 20px;">
            <div style="font-size:16px; font-weight:700;">REPORT ON INVENTORY OF DONATED PROPERTY AND EQUIPMENT</div>
            <div class="small">As at {{ $formattedDate }}</div>
        </div>

        <div style="font-size:12px; margin-bottom: 20px;">
            SK of Barangay: {{ $barangayName }}<br>
            City/Municipality: {{ $data['city'] ?? '' }}<br>
            Province: {{ $data['province'] ?? '' }}
        </div>

        <div style="font-size:12px; margin-bottom: 18px;">
            For which <span class="underline">{{ $data['accountable_officer'] ?? '' }}</span>,
            <span class="underline">{{ $data['official_designation'] ?? '' }}</span> is accountable, having assumed such accountability on
            <span class="underline">{{ !empty($data['assumption_date']) ? \Carbon\Carbon::parse($data['assumption_date'])->format('F d, Y') : '' }}</span>.
        </div>

        <table>
            <tr>
                <th>Article<br>(1)</th>
                <th>Item Description<br>(2)</th>
                <th>Property No.<br>(3)</th>
                <th>Unit of Measurement<br>(4)</th>
                <th>Unit Cost<br>(5)</th>
                <th>Balance Per RDPE<br>(Quantity)<br>(6)</th>
                <th>On Hand Per Count<br>(Quantity)<br>(7)</th>
                <th>Quantity<br>(8)</th>
                <th>Value<br>(9)</th>
                <th>Remarks<br>(10)</th>
            </tr>
            @foreach (($data['inventory_rows'] ?? []) as $row)
                <tr>
                    <td>{{ $row['article'] ?? '' }}</td>
                    <td>{{ $row['description'] ?? '' }}</td>
                    <td>{{ $row['property_no'] ?? '' }}</td>
                    <td>{{ $row['unit'] ?? '' }}</td>
                    <td>{{ $row['unit_cost'] ?? '' }}</td>
                    <td>{{ $row['balance'] ?? '' }}</td>
                    <td>{{ $row['on_hand'] ?? '' }}</td>
                    <td>{{ $row['shortage_quantity'] ?? '' }}</td>
                    <td>{{ $row['shortage_value'] ?? '' }}</td>
                    <td>{{ $row['remarks'] ?? '' }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="10" style="height: 100px;">
                    <div>Prepared and Certified Correct by: <span style="float:right; margin-right:180px;">Approved by:</span></div>
                    <table style="margin-top:24px;">
                        <tr>
                            @foreach (($data['committee_members'] ?? ['', '', '']) as $member)
                                <td class="no-border center">
                                    <div class="underline">{{ $member }}</div>
                                    <div class="small">Signature over Printed Name<br>Member, Inventory Committee<br>Date: {{ $formattedDate }}</div>
                                </td>
                            @endforeach
                            <td class="no-border center">
                                <div class="underline">{{ $data['chairperson_name'] ?? '' }}</div>
                                <div class="small">Signature over Printed Name<br>SK Chairperson<br>Date: {{ $formattedDate }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    @else
    <table>
        <tr>
            <td class="no-border"></td>
            <td class="no-border center" colspan="7">
                <div class="title">REGISTRY OF SPECIFIC PURPOSE FUND, COMMITMENTS, PAYMENTS AND BALANCES</div>
                <div class="subtitle">CAPITAL OUTLAY</div>
            </td>
            <td class="no-border" style="text-align:right;">Annex 4</td>
        </tr>
    </table>

    <table>
        <tr>
            <td colspan="4">SK of Barangay: {{ $barangayName }}</td>
            <td colspan="3">City/Municipality: {{ $data['city'] ?? '' }}</td>
            <td>Sheet No.: {{ $data['sheet_no'] ?? '' }}</td>
        </tr>
        <tr>
            <td colspan="4">Budget Monitoring Officer: {{ $data['monitoring_officer'] ?? '' }}</td>
            <td colspan="4">Province: {{ $data['province'] ?? '' }}</td>
        </tr>
        <tr>
            <td colspan="8">Program/Project/Activity: {{ $data['program_project_activity'] ?? '' }}</td>
        </tr>
        <tr>
            <th rowspan="2">Particulars</th>
            <th rowspan="2">Date</th>
            <th rowspan="2">Reference</th>
            <th rowspan="2">Total Amount</th>
            <th colspan="4">Breakdown of Object of Expenditures</th>
        </tr>
        <tr>
            <th>{{ $objectHeader1 }}</th>
            <th>{{ $objectHeader2 }}</th>
            <th>{{ $objectHeader3 }}</th>
            <th>{{ $objectHeader4 }}</th>
        </tr>

        @foreach (($data['rows'] ?? []) as $row)
            <tr>
                <td>{{ $row['particulars'] ?? '' }}</td>
                <td>{{ $row['date'] ?? '' }}</td>
                <td>{{ $row['reference'] ?? '' }}</td>
                <td>{{ $row['total_amount'] ?? '' }}</td>
                <td>{{ $row['object_1'] ?? '' }}</td>
                <td>{{ $row['object_2'] ?? '' }}</td>
                <td>{{ $row['object_3'] ?? '' }}</td>
                <td>{{ $row['object_4'] ?? '' }}</td>
            </tr>
        @endforeach

        <tr class="section-title">
            <td colspan="3">Total Specific Purpose Fund carried forward</td>
            <td colspan="5">{{ $data['spf_carried_forward'] ?? '' }}</td>
        </tr>
        <tr class="section-title">
            <td colspan="3">Total Commitments carried forward</td>
            <td colspan="5">{{ $data['commitments_carried_forward'] ?? '' }}</td>
        </tr>
        <tr class="section-title">
            <td colspan="3">Total Payments carried forward</td>
            <td colspan="5">{{ $data['payments_carried_forward'] ?? '' }}</td>
        </tr>
        <tr class="section-title">
            <td colspan="3">Balance, Available Specific Purpose Fund</td>
            <td colspan="5">{{ $data['available_balance'] ?? '' }}</td>
        </tr>
        <tr class="section-title">
            <td colspan="3">Balance, Unpaid Commitments</td>
            <td colspan="5">{{ $data['unpaid_commitments'] ?? '' }}</td>
        </tr>
        <tr>
            <td colspan="4">
                <div class="small">Prepared and Certified Correct by:</div>
                <div style="margin-top:18px; font-weight:700;">{{ $data['monitoring_officer'] ?? '' }}</div>
                <div class="small">Signature over Printed Name<br>Budget Monitoring Officer</div>
            </td>
            <td colspan="4" class="center">
                <div style="margin-top:28px; font-weight:700;">{{ $data['certified_date'] ?? '' }}</div>
                <div class="small">Date</div>
            </td>
        </tr>
    </table>
    @endif
</body>
</html>
