{{-- File guide: Blade view template for resources/views/sk_pres/consolidation-download.blade.php. --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Consolidated Reports</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 11px; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        .meta { color: #6b7280; margin-bottom: 18px; }
        .stats { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .stats td { border: 1px solid #e5e7eb; padding: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #dc2626; color: #fff; text-align: left; padding: 8px; }
        td { border: 1px solid #e5e7eb; padding: 7px; }
        .status { font-weight: bold; text-transform: uppercase; }
    </style>
</head>
<body>
    <h1>SK 360 Consolidated Barangay Reports</h1>
    <div class="meta">
        Reporting Year: {{ $filters['year'] }} |
        Period: {{ ucfirst($filters['period']) }}
        @if ($filters['period'] === 'monthly')
            | Month: {{ date('F', mktime(0, 0, 0, (int) $filters['month'], 1)) }}
        @elseif ($filters['period'] === 'quarterly')
            | Quarter: {{ $filters['quarter'] }}
        @endif
        | Generated: {{ $generatedAt->format('M d, Y h:i A') }}
    </div>

    <table class="stats">
        <tr>
            @foreach ($stats as $stat)
                <td>
                    <strong>{{ $stat['value'] }}</strong><br>
                    {{ $stat['label'] }}
                </td>
            @endforeach
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Barangay</th>
                <th>Monthly</th>
                <th>Quarterly</th>
                <th>Annual</th>
                <th>Last Submission</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($submissions as $submission)
                <tr>
                    <td>Barangay {{ $submission['barangay'] }}</td>
                    <td>{{ $submission['monthly'] }}</td>
                    <td>{{ $submission['quarterly'] }}</td>
                    <td>{{ $submission['annual'] }}</td>
                    <td>{{ $submission['last_submission'] }}</td>
                    <td class="status">{{ $submission['status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
