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

        .identity-card {
            padding: 1rem;
            border-radius: 18px;
            background: #fffaf2;
            border: 1px solid rgba(198, 61, 38, 0.12);
            margin-bottom: 1rem;
        }

        .channel-option {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.95rem 1rem;
            border: 1px solid rgba(198, 61, 38, 0.12);
            border-radius: 16px;
            background: #fff;
            margin-bottom: 0.8rem;
        }

        .channel-option input[type="radio"] {
            margin-top: 0.2rem;
            accent-color: var(--primary);
        }

        .channel-option strong {
            display: block;
            color: var(--gray-900);
            margin-bottom: 0.15rem;
        }

        .channel-option span {
            color: var(--gray-600);
            font-size: 0.92rem;
        }

        .search-mode-group {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.8rem;
            margin-bottom: 1rem;
        }

        .search-mode-option {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.95rem 1rem;
            border: 1px solid rgba(198, 61, 38, 0.12);
            border-radius: 16px;
            background: #fff;
        }

        .search-mode-option input[type="radio"] {
            margin-top: 0.2rem;
            accent-color: var(--primary);
        }

        .search-mode-option strong {
            color: var(--gray-900);
        }

        @media (max-width: 480px) {
            .recovery-card {
                padding: 1.1rem;
                border-radius: 24px;
            }

            h1 {
                font-size: 1.42rem;
            }

            .search-mode-group {
                grid-template-columns: 1fr;
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
                    We sent a 6-digit reset code to {{ $maskedDestination ?? $maskedEmail ?? 'your recovery destination' }}. Enter it below with your new password.
                    @if (! empty($channelLabel))
                        This code was sent through your {{ strtolower($channelLabel) }}.
                    @endif
                @elseif (! empty($recoveryLookup))
                    We found a matching account. Confirm where you want us to send the recovery code.
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
                            <input id="otp_code" class="input" type="text" name="otp_code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6)" placeholder="6-digit code" required autofocus>
                        </div>
                    </div>
                    <div class="field">
                        <label for="password">New password</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-lock"></i>
                            <input id="password" class="input" type="password" name="password" placeholder="At least 8 characters" pattern="(?=.*[A-Z])(?=.*[0-9])[A-Za-z0-9@*.\-]{8,}" title="Password must be at least 8 characters, include 1 uppercase letter, 1 number, and only use @ - * . as symbols." oninput="this.value = this.value.replace(/[^A-Za-z0-9@*.\-]/g, '')" required>
                        </div>
                    </div>
                    <div class="field">
                        <label for="password_confirmation">Confirm password</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-lock"></i>
                            <input id="password_confirmation" class="input" type="password" name="password_confirmation" placeholder="Repeat new password" pattern="(?=.*[A-Z])(?=.*[0-9])[A-Za-z0-9@*.\-]{8,}" title="Password confirmation must match the same password format." oninput="this.value = this.value.replace(/[^A-Za-z0-9@*.\-]/g, '')" required>
                        </div>
                    </div>
                    <button type="submit" class="btn">
                        <i class="fa-solid fa-check"></i>
                        Reset password
                    </button>
                </form>
            @elseif (! empty($recoveryLookup))
                <div class="identity-card">
                    <strong style="display:block;margin-bottom:0.35rem;color:var(--gray-900);">Is this you?</strong>
                    <span style="color:var(--gray-600);font-size:0.92rem;">Choose a recovery destination for the one-time verification code.</span>
                </div>

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    @foreach (($recoveryLookup['channels'] ?? []) as $index => $channel)
                        <label class="channel-option" for="delivery_channel_{{ $channel['type'] }}">
                            <input
                                id="delivery_channel_{{ $channel['type'] }}"
                                type="radio"
                                name="delivery_channel"
                                value="{{ $channel['type'] }}"
                                {{ old('delivery_channel', $index === 0 ? $channel['type'] : null) === $channel['type'] ? 'checked' : '' }}
                                required
                            >
                            <span>
                                <strong>{{ $channel['label'] }}</strong>
                                <span>{{ $channel['masked'] }}</span>
                            </span>
                        </label>
                    @endforeach

                    <button type="submit" class="btn">
                        <i class="fa-solid fa-paper-plane"></i>
                        Confirm and send code
                    </button>
                </form>

                <a href="{{ route('password.request', ['clear_lookup' => 1]) }}" class="back-link" style="margin-top:0.85rem;">
                    <i class="fa-solid fa-arrow-left"></i>
                    Search a different account
                </a>
            @else
                <form method="POST" action="{{ route('password.lookup') }}">
                    @csrf
                    <div class="field">
                        <div class="search-mode-group">
                            <label class="search-mode-option" for="search_mode_identity">
                                <input
                                    id="search_mode_identity"
                                    type="radio"
                                    name="search_mode"
                                    value="identity"
                                    {{ old('search_mode', 'identity') === 'identity' ? 'checked' : '' }}
                                    required
                                >
                                <strong>Name or email</strong>
                            </label>

                            <label class="search-mode-option" for="search_mode_mobile">
                                <input
                                    id="search_mode_mobile"
                                    type="radio"
                                    name="search_mode"
                                    value="mobile"
                                    {{ old('search_mode') === 'mobile' ? 'checked' : '' }}
                                    required
                                >
                                <strong>Mobile number</strong>
                            </label>
                        </div>
                    </div>

                    <div class="field">
                        <label for="account_identifier" id="accountIdentifierLabel">Name or email</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-user-magnifying-glass"></i>
                            <input
                                id="account_identifier"
                                class="input"
                                type="text"
                                name="account_identifier"
                                value="{{ old('account_identifier') }}"
                                placeholder="Juan Dela Cruz or email@example.com"
                                required
                                autofocus
                            >
                        </div>
                    </div>
                    <button type="submit" class="btn">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        Search account
                    </button>
                </form>
            @endif

            <a href="{{ route('auth.login') }}" class="back-link">
                <i class="fa-solid fa-arrow-left"></i>
                Back to login
            </a>
        </section>
    </main>

    <script>
        (function () {
            const identityRadio = document.getElementById('search_mode_identity');
            const mobileRadio = document.getElementById('search_mode_mobile');
            const input = document.getElementById('account_identifier');
            const label = document.getElementById('accountIdentifierLabel');
            if (!identityRadio || !mobileRadio || !input || !label) {
                return;
            }

            const applySearchMode = () => {
                if (mobileRadio.checked) {
                    label.textContent = 'Mobile number';
                    input.placeholder = '09XXXXXXXXX';
                    input.inputMode = 'numeric';
                    input.maxLength = 11;
                    input.pattern = '09[0-9]{9}';
                    input.title = 'Mobile number must be 11 digits and start with 09.';
                    input.oninput = function () {
                        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);
                    };
                } else {
                    label.textContent = 'Name or email';
                    input.placeholder = 'Juan Dela Cruz or email@example.com';
                    input.inputMode = 'text';
                    input.maxLength = 255;
                    input.removeAttribute('pattern');
                    input.title = 'Enter your full name using letters and spaces, or a valid email address.';
                    input.oninput = function () {
                        this.value = this.value.replace(/[^A-Za-z0-9@. ]/g, '');
                    };
                }
            };

            identityRadio.addEventListener('change', applySearchMode);
            mobileRadio.addEventListener('change', applySearchMode);
            applySearchMode();
        })();
    </script>
</body>
</html>
