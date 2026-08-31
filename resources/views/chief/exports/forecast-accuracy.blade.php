<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forecast Accuracy Report</title>
    <style>
        @page { margin: 28px; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 10px; }
        h1 { margin: 0 0 5px; color: #9f1239; font-size: 21px; }
        .meta { color: #64748b; margin-bottom: 18px; }
        .metrics { width: 100%; margin-bottom: 18px; border-collapse: separate; border-spacing: 7px; }
        .metrics td { width: 20%; padding: 11px; border: 1px solid #fed7aa; background: #fff7ed; border-radius: 7px; }
        .metrics small { display: block; color: #9a3412; font-weight: bold; text-transform: uppercase; }
        .metrics strong { display: block; margin-top: 5px; font-size: 16px; }
        table.records { width: 100%; border-collapse: collapse; }
        .records th { padding: 8px; background: #9f1239; color: white; text-align: left; }
        .records td { padding: 7px 8px; border-bottom: 1px solid #e2e8f0; }
        .records tr:nth-child(even) td { background: #f8fafc; }
        .empty { padding: 24px; border: 1px dashed #cbd5e1; color: #64748b; text-align: center; }
        .note { margin-top: 15px; color: #64748b; line-height: 1.5; }
    </style>
</head>
<body>
    <h1>Real-world Forecast Accuracy</h1>
    <div class="meta">
        {{ $station->station_name }} ·
        {{ $filters['year'] === 'all' ? 'All years' : $filters['year'] }} ·
        {{ $filters['quarter'] === 'all' ? 'All quarters' : 'Q'.$filters['quarter'] }} ·
        Generated {{ $generatedAt->format('M d, Y h:i A') }}
    </div>

    <table class="metrics"><tr>
        <td><small>Completed months</small><strong>{{ $metrics['sample_size'] }}</strong></td>
        <td><small>Average error</small><strong>{{ $metrics['mae'] ?? 'N/A' }}</strong></td>
        <td><small>Large-error score</small><strong>{{ $metrics['rmse'] ?? 'N/A' }}</strong></td>
        <td><small>Forecast bias</small><strong>{{ $metrics['mean_error'] ?? 'N/A' }}</strong></td>
        <td><small>Within 1 incident</small><strong>{{ isset($metrics['within_one_incident_percent']) ? $metrics['within_one_incident_percent'].'%' : 'N/A' }}</strong></td>
    </tr></table>

    @if(count($outcomes))
        <table class="records">
            <thead><tr><th>Target month</th><th>Actual incidents</th><th>Predicted incidents</th><th>Error</th></tr></thead>
            <tbody>
            @foreach($outcomes as $outcome)
                <tr>
                    <td>{{ $outcome['label'] }}</td>
                    <td>{{ $outcome['actual'] }}</td>
                    <td>{{ $outcome['predicted'] }}</td>
                    <td>{{ round($outcome['predicted'] - $outcome['actual'], 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">No completed forecast outcomes match these filters.</div>
    @endif

    <p class="note">Only forecasts whose complete target month has ended are evaluated. Positive error means the forecast was higher than the recorded result; negative error means it was lower.</p>
</body>
</html>
