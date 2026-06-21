<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Spot Investigation Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 26px 34px;
            line-height: 1.45;
        }

        .header {
            text-align: center;
            margin-bottom: 18px;
        }

        .header h1,
        .header h2,
        .header p {
            margin: 0;
        }

        .header h1 {
            font-size: 14px;
            font-weight: 700;
            margin-top: 6px;
        }

        .header h2 {
            font-size: 13px;
            font-weight: 700;
            margin-top: 2px;
        }

        .header-letterhead {
            font-size: 11px;
            font-weight: 700;
            white-space: pre-line;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 11px;
            margin-top: 2px;
        }

        .memo {
            margin-top: 10px;
            margin-bottom: 16px;
        }

        .memo-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }

        .memo-label,
        .memo-value {
            display: table-cell;
            vertical-align: top;
        }

        .memo-label {
            width: 82px;
            font-weight: 700;
        }

        .field-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .field-table td {
            padding: 5px 0;
            vertical-align: top;
        }

        .field-label {
            width: 205px;
            font-weight: 700;
        }

        .section-title {
            font-weight: 700;
            margin-top: 14px;
            margin-bottom: 6px;
        }

        .section-box {
            border: 1px solid #d1d5db;
            padding: 10px 12px;
            min-height: 88px;
            white-space: pre-line;
        }

        .signatures {
            margin-top: 30px;
            width: 100%;
        }

        .signature-block {
            width: 48%;
            display: inline-block;
            vertical-align: top;
            text-align: center;
            margin-right: 2%;
        }

        .signature-name {
            margin-top: 46px;
            border-top: 1px solid #111827;
            padding-top: 6px;
            font-weight: 700;
        }

        .signature-caption {
            font-size: 11px;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <p class="header-letterhead">{{ $sir['office_letterhead_line'] }}</p>
        <p>Republic of the Philippines</p>
        <p>Department of the Interior and Local Government</p>
        <h1>BUREAU OF FIRE PROTECTION</h1>
        <h2>NATIONAL HEADQUARTERS</h2>
        <p>Senator Miriam Defensor-Santiago Avenue, Brgy. Bagong Pag-asa, Quezon City</p>
    </div>

    <div class="memo">
        <div class="memo-row">
            <div class="memo-label">FOR</div>
            <div class="memo-value">: {{ $sir['memorandum_for'] }}</div>
        </div>
        <div class="memo-row">
            <div class="memo-label">SUBJECT</div>
            <div class="memo-value">: {{ $sir['subject'] }}</div>
        </div>
        <div class="memo-row">
            <div class="memo-label">DATE</div>
            <div class="memo-value">: {{ $sir['report_completed_display'] }}</div>
        </div>
    </div>

    <table class="field-table">
        <tr><td class="field-label">DTPO</td><td>: {{ $sir['dtpo_display'] }}</td></tr>
        <tr><td class="field-label">INVOLVED</td><td>: {{ $sir['involved_display'] }}</td></tr>
        <tr><td class="field-label">NAME OF ESTABLISHMENT</td><td>: {{ $sir['establishment_name'] }}</td></tr>
        <tr><td class="field-label">OWNER</td><td>: {{ $sir['owner'] }}</td></tr>
        <tr><td class="field-label">OCCUPANT</td><td>: {{ $sir['occupant'] }}</td></tr>
        <tr><td class="field-label">CASUALTY</td><td>: Fatality {{ $sir['dead_display'] }} | Injured {{ $sir['injured_display'] }}</td></tr>
        <tr><td class="field-label">ESTIMATED DAMAGE</td><td>: {{ $sir['estimated_damage_display'] }}</td></tr>
        <tr><td class="field-label">TIME FIRE STARTED</td><td>: {{ $sir['fire_started_display'] }}</td></tr>
        <tr><td class="field-label">TIME OF FIRE OUT</td><td>: {{ $sir['fire_out_display'] }}</td></tr>
        <tr><td class="field-label">ALARM</td><td>: {{ $sir['highest_alarm_level'] }}</td></tr>
        <tr><td class="field-label">ESTIMATED AREA</td><td>: {{ $sir['estimated_area_display'] }}</td></tr>
        <tr><td class="field-label">AFFECTED ESTABLISHMENTS</td><td>: {{ $sir['affected_establishments_count'] ?? 'Not specified' }}</td></tr>
        <tr><td class="field-label">WEATHER</td><td>: {{ $sir['weather_summary'] }}</td></tr>
        <tr><td class="field-label">FATALITY DETAILS</td><td>: {{ $sir['fatality_details'] }}</td></tr>
    </table>

    <div class="section-title">DETAILS OF INVESTIGATION</div>
    <div class="section-box">{{ $sir['details_of_investigation'] }}</div>

    <div class="section-title">DISPOSITION</div>
    <div class="section-box">{{ $sir['disposition'] }}</div>

    <div class="signatures">
        <div class="signature-block">
            <div class="signature-name">{{ $sir['investigator_name'] }}</div>
            <div class="signature-caption">Fire Arson Investigator</div>
        </div>
        <div class="signature-block">
            <div class="signature-name">{{ $sir['noted_by_name'] }}</div>
            <div class="signature-caption">Noted By</div>
        </div>
    </div>

    <div class="signatures">
        <div class="signature-block">
            <div class="signature-name">{{ $sir['approved_by_name'] }}</div>
            <div class="signature-caption">Approved for Submission</div>
        </div>
    </div>
</body>
</html>
