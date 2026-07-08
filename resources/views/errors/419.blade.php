<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Session Expired | PyroSense</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: Arial, sans-serif;
            background:
                radial-gradient(circle at top right, rgba(209, 138, 22, 0.16), transparent 30%),
                radial-gradient(circle at left center, rgba(198, 61, 38, 0.08), transparent 28%),
                #fbf7ef;
            color: #1f2937;
        }

        .card {
            width: min(100%, 520px);
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid rgba(198, 61, 38, 0.12);
            border-radius: 28px;
            box-shadow: 0 24px 60px rgba(198, 61, 38, 0.12);
            padding: 32px 28px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 999px;
            background: rgba(198, 61, 38, 0.08);
            color: #9a3412;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        h1 {
            margin: 18px 0 12px;
            font-size: 36px;
            line-height: 1.1;
        }

        p {
            margin: 0;
            color: #4b5563;
            font-size: 16px;
            line-height: 1.7;
        }

        .actions {
            margin-top: 24px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 999px;
            padding: 14px 20px;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
        }

        .button-primary {
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            color: #fff;
            box-shadow: 0 16px 30px rgba(198, 61, 38, 0.18);
        }

        .button-secondary {
            background: #fff7ed;
            color: #9a3412;
            border: 1px solid rgba(198, 61, 38, 0.14);
        }
    </style>
</head>
<body>
    <div class="card">
        <span class="eyebrow">Session Expired</span>
        <h1>Page Expired</h1>
        <p>Your session expired. Please sign in again.</p>
        <div class="actions">
            <a class="button button-primary" href="{{ url('/auth') }}">Go to Sign In</a>
            <button class="button button-secondary" type="button" onclick="window.location.reload()">Reload Page</button>
        </div>
    </div>
</body>
</html>
