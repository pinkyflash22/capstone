<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>PyroSense Fire Risk Analysis</title>
    <style>
        body{font-family:DejaVu Sans,sans-serif;color:#172033;font-size:11px;line-height:1.45;margin:28px}h1{margin:0;color:#b6401e;font-size:22px}h2{margin:22px 0 8px;font-size:14px;color:#7c2d12;border-bottom:1px solid #fed7aa;padding-bottom:5px}.meta{margin-top:5px;color:#64748b}.grid{width:100%;border-collapse:collapse;margin-top:10px}.grid th,.grid td{padding:7px;border:1px solid #e2e8f0;text-align:left;vertical-align:top}.grid th{background:#fff7ed;color:#9a3412}.pill{display:inline-block;padding:4px 8px;border-radius:12px;background:#ffedd5;color:#9a3412;font-weight:bold}.note{padding:10px;background:#f8fafc;border-left:3px solid #f97316;margin:8px 0}.footer{margin-top:26px;color:#94a3b8;font-size:9px}
    </style>
</head>
<body>
    <h1>PyroSense Fire Risk Analysis</h1>
    <div class="meta">{{ $analysis->station?->station_name ?: 'Station' }} · Analysis #{{ $analysis->analysis_id }} · Revision {{ $analysis->revision_number ?: 1 }} · {{ $analysis->created_at?->format('F j, Y g:i A') }}</div>

    <h2>Forecast summary</h2>
    <table class="grid"><tr><th>Scope</th><th>Period</th><th>Risk</th><th>Probability</th><th>Evidence incidents</th></tr><tr>
        <td>{{ str_replace('_', ' ', $rawData['prediction_scope'] ?? 'current month') }}</td>
        <td>{{ data_get($rawData, 'prediction_context.target_month_label', $analysis->period ?: 'Not recorded') }}</td>
        <td><span class="pill">{{ strtoupper($analysis->risk_level ?: 'Not available') }}</span></td>
        <td>{{ is_numeric($analysis->probability) ? number_format((float)$analysis->probability, 1).'%' : 'Not available' }}</td>
        <td>{{ number_format((int)$analysis->incident_count) }}</td>
    </tr></table>

    <h2>Barangay priorities</h2>
    @if($barangays->isEmpty())<div class="note">No barangay-level forecast was saved in this analysis.</div>@else
    <table class="grid"><thead><tr><th>Barangay</th><th>Risk level</th><th>Probability</th><th>Evidence</th></tr></thead><tbody>
    @foreach($barangays as $row)<tr><td>{{ $row['barangay_name'] ?? 'Unknown' }}</td><td>{{ strtoupper($row['risk_level'] ?? 'N/A') }}</td><td>{{ is_numeric($row['probability_percent'] ?? null) ? number_format((float)$row['probability_percent'],1).'%' : 'N/A' }}</td><td>{{ (int)data_get($row,'input.incident_count',0) }} historical · {{ (int)data_get($row,'input.verified_risk_count',0) }} verified risks · {{ (int)data_get($row,'input.verified_hazard_count',0) }} verified hazards</td></tr>@endforeach
    </tbody></table>@endif

    <h2>Recommendations</h2>
    @forelse($recommendations as $item)<div class="note"><strong>{{ $item['title'] ?? 'Recommendation' }}</strong><br>{{ $item['description'] ?? 'No description recorded.' }} @if(!empty($item['priority']))<em>Priority: {{ $item['priority'] }}</em>@endif</div>@empty<div class="note">No recommendations were saved for this analysis.</div>@endforelse

    <div class="footer">Generated {{ now()->format('F j, Y g:i A') }}. This report preserves the saved analysis result and should be interpreted with the listed evidence coverage.</div>
</body>
</html>
