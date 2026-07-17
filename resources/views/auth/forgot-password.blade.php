<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <title>PyroSense - Password Recovery</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary: #c63d26;
            --accent: #d18a16;
            --gradient-main: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            --gradient-soft: linear-gradient(135deg, #fff0e8 0%, #fff5dc 100%);
            --gray-50: #fbf7ef;
            --gray-200: #e5e7eb;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 1rem;
            color: var(--gray-800);
            background:
                radial-gradient(circle at top right, rgba(209, 138, 22, 0.2), transparent 28%),
                radial-gradient(circle at left center, rgba(198, 61, 38, 0.1), transparent 30%),
                var(--gray-50);
        }

        .recovery-shell {
            width: min(100%, 440px);
        }

        .recovery-card {
            padding: 1.35rem;
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid rgba(198, 61, 38, 0.1);
            box-shadow: 0 24px 60px rgba(198, 61, 38, 0.13);
            backdrop-filter: blur(16px);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1.25rem;
            text-decoration: none;
        }

        .brand-logo {
            width: 54px;
            height: 54px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #fff;
            border: 1px solid rgba(198, 61, 38, 0.14);
            box-shadow: 0 12px 24px rgba(198, 61, 38, 0.16);
            padding: 0.65rem;
        }

        .brand-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .brand span {
            color: var(--gray-900);
            font-size: 1.35rem;
            font-weight: 800;
        }

        .brand span strong {
            color: var(--primary);
        }

        .recovery-icon {
            width: 54px;
            height: 54px;
            display: grid;
            place-items: center;
            margin-bottom: 0.9rem;
            border-radius: 18px;
            color: var(--primary);
            background: var(--gradient-soft);
            font-size: 1.25rem;
        }

        h1 {
            color: var(--gray-900);
            font-size: 1.65rem;
            line-height: 1.15;
            margin-bottom: 0.45rem;
        }

        .lead {
            color: var(--gray-600);
            line-height: 1.55;
            margin-bottom: 1.2rem;
        }

        .notice,
        .error-box {
            padding: 0.85rem 0.95rem;
            margin-bottom: 1rem;
            border-radius: 16px;
            font-size: 0.9rem;
            line-height: 1.45;
        }

        .notice {
            color: #7c2d12;
            background: #fff7ed;
            border: 1px solid rgba(209, 138, 22, 0.18);
        }

        .error-box {
            color: #991b1b;
            background: #fff1f2;
            border: 1px solid #fecaca;
        }

        .field {
            margin-bottom: 1rem;
        }

        .field label {
            display: block;
            margin-bottom: 0.45rem;
            color: var(--gray-700);
            font-weight: 700;
            font-size: 0.9rem;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap i {
            position: absolute;
            left: 1rem;
            top: 50%;
            color: var(--gray-400);
            transform: translateY(-50%);
        }

        .input {
            width: 100%;
            min-height: 52px;
            padding: 0.95rem 1rem 0.95rem 2.9rem;
            border: 1px solid rgba(198, 61, 38, 0.14);
            border-radius: 16px;
            color: var(--gray-900);
            background: #fffaf2;
            font-size: 0.95rem;
            outline: none;
        }

        .input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(198, 61, 38, 0.12);
        }

        .btn {
            width: 100%;
            min-height: 52px;
            border: 0;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            color: #fff;
            background: var(--gradient-main);
            font-weight: 800;
            font-size: 0.98rem;
            cursor: pointer;
            box-shadow: 0 14px 30px rgba(198, 61, 38, 0.2);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            width: 100%;
            margin-top: 1rem;
            color: var(--gray-600);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
        }

        @media (max-width: 480px) {
            .recovery-card {
                padding: 1.1rem;
                border-radius: 24px;
            }

            h1 {
                font-size: 1.42rem;
            }
        }
    </style>
</head>
<body>
    <main class="recovery-shell">
        <section class="recovery-card">
            <a href="{{ route('home') }}" class="brand">
                <span class="brand-logo">@include('partials.app-logo', ['class' => 'logo-image'])</span>
                <span>Pyro<strong>Sense</strong></span>
            </a>

            <div class="recovery-icon">
                <i class="fa-solid {{ ! empty($resetMode) ? 'fa-key' : 'fa-magnifying-glass' }}"></i>
            </div>

            <h1>{{ ! empty($resetMode) ? 'Enter reset code' : 'Find your account' }}</h1>
            <p class="lead">
                @if (! empty($resetMode))
                    We sent a 6-digit reset code to {{ $maskedEmail ?? 'your email' }}. Enter it below with your new password.
                @else
                    Enter the email address or contact number connected to your PyroSense account.
                @endif
            </p>

            @if (session('success'))
                <div class="notice">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="error-box">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @if (! empty($resetMode))
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <div class="field">
                        <label for="otp_code">Reset code</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-shield-halved"></i>
                            <input id="otp_code" class="input" type="text" name="otp_code" inputmode="numeric" maxlength="6" placeholder="6-digit code" required autofocus>
                        </div>
                    </div>
                    <div class="field">
                        <label for="password">New password</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-lock"></i>
                            <input id="password" class="input" type="password" name="password" placeholder="At least 8 characters" required>
                        </div>
                    </div>
                    <div class="field">
                        <label for="password_confirmation">Confirm password</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-lock"></i>
                            <input id="password_confirmation" class="input" type="password" name="password_confirmation" placeholder="Repeat new password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn">
                        <i class="fa-solid fa-check"></i>
                        Reset password
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="field">
                        <label for="account_identifier">Email or contact number</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-user-magnifying-glass"></i>
                            <input id="account_identifier" class="input" type="text" name="account_identifier" value="{{ old('account_identifier') }}" placeholder="email@example.com or 09XXXXXXXXX" required autofocus>
                        </div>
                    </div>
                    <button type="submit" class="btn">
                        <i class="fa-solid fa-paper-plane"></i>
                        Search and send code
                    </button>
                </form>
            @endif

            <a href="{{ route('auth.login') }}" class="back-link">
                <i class="fa-solid fa-arrow-left"></i>
                Back to login
            </a>
        </section>
    </main>
</body>
</html>
