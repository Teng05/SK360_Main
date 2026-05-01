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
    </style>
</head>
<body>
    @php
        $objectHeaders = $data['object_headers'] ?? [];
        $objectHeader1 = $objectHeaders[0] ?? 'Object 1';
        $objectHeader2 = $objectHeaders[1] ?? 'Object 2';
        $objectHeader3 = $objectHeaders[2] ?? 'Object 3';
        $objectHeader4 = $objectHeaders[3] ?? 'Insert additional Object of Expenditures';
    @endphp

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
</body>
</html>
