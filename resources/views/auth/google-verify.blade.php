<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Verify Google Sign-In</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        :root {
            --primary: #c63d26;
            --primary-dark: #a9311d;
            --accent: #d18a16;
            --surface: rgba(255, 255, 255, 0.95);
            --surface-soft: #fff8f1;
            --surface-muted: #fff2e8;
            --text: #1f2937;
            --muted: #6b7280;
            --border: rgba(198, 61, 38, 0.14);
            --shadow: 0 28px 60px rgba(198, 61, 38, 0.14);
            --success-bg: #ecfdf3;
            --success-text: #166534;
            --error-bg: #fff1f2;
            --error-text: #b42318;
        }

        * {
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background:
                radial-gradient(circle at top right, rgba(239, 184, 74, 0.26), transparent 22%),
                radial-gradient(circle at left center, rgba(198, 61, 38, 0.1), transparent 26%),
                linear-gradient(180deg, #fdf7ef 0%, #fbf7ef 100%);
            color: var(--text);
        }

        .card {
            width: min(100%, 500px);
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 32px;
            padding: 2rem;
            box-shadow: var(--shadow);
            backdrop-filter: blur(16px);
        }

        h1 {
            margin: 0 0 0.8rem;
            font-size: 2rem;
            line-height: 1.15;
        }

        p {
            margin: 0;
            color: var(--muted);
            line-height: 1.65;
        }

        .email {
            color: var(--primary);
            font-weight: 800;
        }

        .field {
            margin-top: 1.2rem;
        }

        label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.94rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--text);
        }

        .otp-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 0.7rem;
        }

        .otp-box {
            width: 100%;
            aspect-ratio: 1 / 1.08;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: var(--surface-muted);
            text-align: center;
            font-size: 1.55rem;
            font-weight: 800;
            color: var(--text);
            outline: none;
            transition: border-color 0.2s, transform 0.2s, box-shadow 0.2s, background 0.2s;
        }

        .otp-box:focus {
            border-color: var(--primary);
            background: #fffdf9;
            box-shadow: 0 0 0 4px rgba(198, 61, 38, 0.12);
            transform: translateY(-1px);
        }

        .otp-help {
            margin-top: 0.75rem;
            font-size: 0.88rem;
            color: var(--muted);
        }

        .actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.35rem;
        }

        button {
            border: none;
            border-radius: 16px;
            padding: 0.95rem 1.1rem;
            font-weight: 800;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s, background 0.2s;
        }

        button:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            flex: 1;
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            color: white;
            box-shadow: 0 16px 28px rgba(198, 61, 38, 0.18);
        }

        .btn-secondary {
            width: 100%;
            background: #fff3ed;
            color: var(--primary-dark);
            border: 1px solid rgba(198, 61, 38, 0.1);
        }

        .message,
        .error-list {
            margin-top: 1rem;
            padding: 0.95rem 1rem;
            border-radius: 16px;
            font-size: 0.92rem;
        }

        .message {
            background: var(--success-bg);
            color: var(--success-text);
        }

        .error-list {
            background: var(--error-bg);
            color: var(--error-text);
        }

        .error-list ul {
            margin: 0;
            padding-left: 1rem;
        }

        @media (max-width: 560px) {
            .card {
                padding: 1.15rem;
                border-radius: 24px;
            }

            h1 {
                font-size: 1.45rem;
            }

            .otp-grid {
                gap: 0.45rem;
            }

            .otp-box {
                border-radius: 14px;
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Verify sign-in</h1>
        <p class="lead">Enter the 6-digit code sent to <span class="email">{{ $email }}</span>.</p>

        @if (session('success'))
            <div class="message">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-list">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('auth.google.verify.submit') }}" id="googleOtpForm">
            @csrf
            <div class="field">
                <label for="otp_digit_1">Verification Code</label>
                <div class="otp-grid" data-otp-grid>
                    <input id="otp_digit_1" class="otp-box" type="text" inputmode="numeric" maxlength="1" data-otp-digit autofocus>
                    <input class="otp-box" type="text" inputmode="numeric" maxlength="1" data-otp-digit>
                    <input class="otp-box" type="text" inputmode="numeric" maxlength="1" data-otp-digit>
                    <input class="otp-box" type="text" inputmode="numeric" maxlength="1" data-otp-digit>
                    <input class="otp-box" type="text" inputmode="numeric" maxlength="1" data-otp-digit>
                    <input class="otp-box" type="text" inputmode="numeric" maxlength="1" data-otp-digit>
                </div>
                <input id="otp_code" name="otp_code" type="hidden" value="{{ old('otp_code') }}">
            </div>

            <div class="actions">
                <button type="submit" class="btn-primary">Verify and Continue</button>
            </div>
        </form>

        <form method="POST" action="{{ route('auth.google.resend-otp') }}" style="margin-top: 0.8rem;">
            @csrf
            <button type="submit" class="btn-secondary">Resend Code</button>
        </form>
    </div>

    <script>
        (function () {
            const digitInputs = Array.from(document.querySelectorAll('[data-otp-digit]'));
            const hiddenInput = document.getElementById('otp_code');
            const form = document.getElementById('googleOtpForm');

            const syncOtpValue = () => {
                hiddenInput.value = digitInputs.map((input) => input.value).join('');
            };

            const seedFromExistingValue = () => {
                const existing = (hiddenInput.value || '').trim().slice(0, 6);
                if (!existing) {
                    return;
                }

                existing.split('').forEach((digit, index) => {
                    if (digitInputs[index]) {
                        digitInputs[index].value = digit;
                    }
                });

                syncOtpValue();
            };

            digitInputs.forEach((input, index) => {
                input.addEventListener('input', (event) => {
                    const value = event.target.value.replace(/\D/g, '').slice(0, 1);
                    event.target.value = value;
                    syncOtpValue();

                    if (value && digitInputs[index + 1]) {
                        digitInputs[index + 1].focus();
                        digitInputs[index + 1].select();
                    }
                });

                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Backspace' && !event.target.value && digitInputs[index - 1]) {
                        digitInputs[index - 1].focus();
                        digitInputs[index - 1].value = '';
                        syncOtpValue();
                    }

                    if (event.key === 'ArrowLeft' && digitInputs[index - 1]) {
                        event.preventDefault();
                        digitInputs[index - 1].focus();
                    }

                    if (event.key === 'ArrowRight' && digitInputs[index + 1]) {
                        event.preventDefault();
                        digitInputs[index + 1].focus();
                    }
                });

                input.addEventListener('paste', (event) => {
                    event.preventDefault();
                    const pasted = (event.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, 6);

                    if (!pasted) {
                        return;
                    }

                    pasted.split('').forEach((digit, pasteIndex) => {
                        if (digitInputs[pasteIndex]) {
                            digitInputs[pasteIndex].value = digit;
                        }
                    });

                    syncOtpValue();
                    const nextIndex = Math.min(pasted.length, digitInputs.length - 1);
                    digitInputs[nextIndex]?.focus();
                });
            });

            form.addEventListener('submit', () => {
                syncOtpValue();
            });

            seedFromExistingValue();
        })();
    </script>
</body>
</html>
