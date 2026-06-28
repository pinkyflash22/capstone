<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Signing Out</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: linear-gradient(180deg, #fffaf4 0%, #fbf7ef 100%);
            color: #1f2937;
            font-family: Arial, sans-serif;
        }

        .logout-bridge-card {
            width: min(100%, 420px);
            background: #ffffff;
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 20px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.14);
            padding: 28px 24px;
            text-align: center;
        }

        .logout-bridge-card h1 {
            margin: 0 0 10px;
            font-size: 1.35rem;
        }

        .logout-bridge-card p {
            margin: 0 0 20px;
            line-height: 1.6;
            color: #64748b;
        }

        .logout-bridge-button {
            width: 100%;
            border: none;
            border-radius: 999px;
            padding: 14px 18px;
            font-size: 0.95rem;
            font-weight: 700;
            color: #ffffff;
            background: linear-gradient(135deg, #dc2626 0%, #f97316 100%);
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="logout-bridge-card">
        <h1>Signing You Out</h1>
        <p>Your session is being closed securely. If nothing happens, tap the button below.</p>

        <form id="logoutBridgeForm" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-bridge-button">Continue Logout</button>
        </form>
    </div>

    <script>
        window.addEventListener('load', function () {
            document.getElementById('logoutBridgeForm')?.submit();
        });
    </script>
</body>
</html>
