<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <title>PyroSense - Create Account</title>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        :root {
            --primary: #c63d26;
            --primary-dark: #a9311d;
            --primary-light: #fff0e8;
            --accent: #d18a16;
            --accent-light: #fff5dc;
            --gradient-main: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            --gradient-soft: linear-gradient(135deg, #fff0e8 0%, #fff5dc 100%);
            --gray-50: #fbf7ef;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --success: #10b981;
            --info: #3b82f6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background:
                radial-gradient(circle at top right, rgba(239, 184, 74, 0.18), transparent 24%),
                radial-gradient(circle at left center, rgba(198, 61, 38, 0.08), transparent 28%),
                var(--gray-50);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--gray-800);
            overflow-x: hidden;
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 251, 245, 0.88);
            border-bottom: 1px solid rgba(198, 61, 38, 0.08);
            backdrop-filter: blur(14px);
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 1rem 0;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }

        .logo-icon {
            width: 76px;
            height: 56px;
            background: #ffffff;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
            box-shadow: 0 12px 24px rgba(198, 61, 38, 0.18);
            border: 1px solid rgba(198, 61, 38, 0.14);
            padding: 0.35rem 0.55rem;
        }

        .logo-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .logo:hover .logo-icon {
            transform: scale(1.05);
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--gray-800);
        }

        .logo-text span {
            color: var(--primary);
        }

        .nav-menu {
            display: flex;
            gap: 2.5rem;
            align-items: center;
        }

        .nav-link {
            color: var(--gray-600);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.2s;
            cursor: pointer;
        }

        .nav-link:hover {
            color: var(--primary);
        }

        .nav-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-auth-link {
            color: var(--gray-600);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .nav-auth-link:hover {
            color: var(--primary);
            background: var(--gradient-soft);
        }

        .nav-auth-link.active {
            color: var(--primary);
            background: var(--gradient-soft);
            font-weight: 600;
        }

        .btn-emergency {
            background: var(--gradient-main);
            color: white;
            border: none;
            padding: 0.6rem 1.25rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 10px 22px rgba(198, 61, 38, 0.2);
        }

        .btn-emergency:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 26px rgba(198, 61, 38, 0.24);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
        }

        .register-container {
            max-width: 1000px;
            width: 100%;
            margin: 0 auto;
        }

        .register-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 32px;
            box-shadow: 0 24px 60px rgba(198, 61, 38, 0.12);
            border: 1px solid rgba(198, 61, 38, 0.08);
            backdrop-filter: blur(12px);
            overflow: hidden;
        }

        .register-intro {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            margin-bottom: 1.75rem;
        }

        .register-intro-badge {
            width: fit-content;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            background: var(--gradient-soft);
            color: var(--primary);
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .register-intro h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--gray-900);
            line-height: 1.1;
        }

        .register-intro p {
            max-width: 34rem;
            color: var(--gray-600);
            font-size: 0.98rem;
            line-height: 1.6;
        }

        /* Form Section */
        .card-body {
            padding: 2.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .form-label i {
            color: var(--primary);
            margin-right: 0.5rem;
        }

        .required-star {
            color: var(--primary);
            margin-left: 0.25rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 1rem;
            pointer-events: none;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.75rem;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.2s;
            background: white;
            font-weight: 500;
        }

        .form-select {
            appearance: none;
            padding-right: 2.5rem;
            cursor: pointer;
        }

        .select-arrow {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            pointer-events: none;
            font-size: 0.875rem;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
        }

        .form-input:disabled, .form-select:disabled {
            background: var(--gray-50);
            cursor: not-allowed;
            opacity: 0.7;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            padding: 0.25rem;
            z-index: 2;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        .form-hint {
            margin-top: 0.5rem;
            font-size: 0.75rem;
            color: var(--gray-500);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding: 0.5rem;
            background: var(--gray-50);
            border-radius: 12px;
            transition: all 0.2s;
        }

        .checkbox-group:hover {
            background: var(--gray-100);
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            border: 2px solid var(--gray-300);
            border-radius: 6px;
            cursor: pointer;
            accent-color: var(--primary);
            margin-top: 2px;
        }

        .checkbox-group label {
            font-size: 0.875rem;
            color: var(--gray-600);
            line-height: 1.5;
            cursor: pointer;
        }

        .checkbox-group a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .checkbox-group a:hover {
            text-decoration: underline;
        }

        .consent-hint {
            margin-top: 0.75rem;
            font-size: 0.75rem;
            color: var(--gray-500);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .policy-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.62);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            z-index: 1100;
        }

        .policy-modal {
            width: min(720px, 100%);
            max-height: 90vh;
            overflow: hidden;
            background: white;
            border-radius: 28px;
            box-shadow: 0 24px 60px rgba(17, 24, 39, 0.2);
            border: 1px solid rgba(198, 61, 38, 0.12);
            display: flex;
            flex-direction: column;
        }

        .policy-modal-header {
            padding: 1.5rem 1.75rem 1rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .policy-modal-step {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: var(--primary-light);
            color: var(--primary);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            margin-bottom: 0.85rem;
        }

        .policy-modal-header h3 {
            font-size: 1.35rem;
            color: var(--gray-900);
            margin-bottom: 0.4rem;
        }

        .policy-modal-header p {
            color: var(--gray-600);
            font-size: 0.92rem;
            line-height: 1.6;
        }

        .policy-modal-body {
            padding: 1.5rem 1.75rem;
            overflow-y: auto;
            color: var(--gray-700);
            line-height: 1.7;
        }

        .policy-modal-body h4 {
            color: var(--gray-900);
            font-size: 1rem;
            margin-bottom: 0.45rem;
        }

        .policy-modal-body section + section {
            margin-top: 1.25rem;
        }

        .policy-modal-footer {
            padding: 1rem 1.75rem 1.5rem;
            border-top: 1px solid var(--gray-200);
            background: #fffdfa;
        }

        .policy-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .policy-btn {
            border: none;
            border-radius: 12px;
            padding: 0.85rem 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .policy-btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
        }

        .policy-btn-primary {
            background: var(--gradient-main);
            color: white;
            box-shadow: 0 12px 24px rgba(198, 61, 38, 0.2);
        }

        .policy-btn:hover:not(:disabled) {
            transform: translateY(-1px);
        }

        .policy-btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        .btn-register {
            width: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-register:hover::before {
            left: 100%;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(220, 38, 38, 0.4);
        }

        .divider {
            position: relative;
            text-align: center;
            margin: 2rem 0;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gray-200), transparent);
        }

        .divider span {
            position: relative;
            background: white;
            padding: 0 1rem;
            color: var(--gray-500);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .demo-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .demo-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            background: white;
            color: var(--gray-700);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .demo-btn:hover {
            border-color: var(--primary);
            background: var(--primary-light);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .login-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-200);
        }

        .login-link p {
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .login-link a {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
        }

        .security-note {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--gray-500);
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .error-message {
            background: linear-gradient(135deg, #fee2e2 0%, #fff5f5 100%);
            border-left: 4px solid var(--primary);
            color: #991b1b;
            padding: 1rem 1.5rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .loading-spinner {
            display: inline-block;
            width: 1.25rem;
            height: 1.25rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .address-section {
            background: var(--gray-50);
            border-radius: 16px;
            padding: 1.5rem;
            margin-top: 0.5rem;
        }

        .address-section h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .address-section h3 i {
            color: var(--primary);
        }

        .postal-display {
            background: linear-gradient(135deg, var(--info) 0%, #2563eb 100%);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }

        .postal-display.hidden {
            display: none;
        }

        .postal-label {
            color: white;
            font-size: 0.75rem;
            font-weight: 500;
            opacity: 0.9;
        }

        .postal-value {
            color: white;
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .postal-value i {
            margin-right: 0.5rem;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            body {
                background:
                    radial-gradient(circle at top, rgba(239, 184, 74, 0.18), transparent 32%),
                    linear-gradient(180deg, #fffaf2 0%, #fff7ed 50%, #ffffff 100%);
            }

            .navbar {
                position: sticky;
                top: 0;
                padding: calc(env(safe-area-inset-top) + 0.75rem) 0 0.85rem;
                background: rgba(255, 251, 245, 0.94);
                backdrop-filter: blur(18px);
            }

            .container {
                padding: 0 1rem;
            }

            .navbar .container {
                gap: 0.75rem;
            }

            .logo {
                min-width: 0;
                gap: 0.65rem;
            }

            .logo-icon {
                width: 52px;
                height: 52px;
                border-radius: 16px;
                padding: 0.35rem;
                flex-shrink: 0;
            }

            .logo-text {
                font-size: 1.15rem;
                line-height: 1.1;
            }

            .nav-actions {
                margin-left: auto;
                gap: 0.5rem;
            }

            .nav-auth-link {
                display: none;
            }

            .btn-emergency {
                min-height: 44px;
                padding: 0.7rem 0.95rem;
                font-size: 0.88rem;
                box-shadow: 0 10px 18px rgba(198, 61, 38, 0.16);
            }

            .main-content {
                align-items: flex-start;
                justify-content: flex-start;
                padding: 1rem 0.9rem calc(1rem + env(safe-area-inset-bottom));
            }

            .register-container {
                max-width: 100%;
            }

            .register-card {
                border-radius: 28px;
                box-shadow: 0 18px 38px rgba(198, 61, 38, 0.12);
            }

            .card-body {
                padding: 1.15rem 1rem 1.25rem;
            }

            .error-message,
            .address-section {
                border-radius: 16px;
            }

            .error-message {
                padding: 0.9rem 0.95rem;
                margin-bottom: 1rem;
            }

            .register-intro {
                gap: 0.65rem;
                margin-bottom: 1.2rem;
            }

            .register-intro h1 {
                font-size: 1.55rem;
                line-height: 1.15;
            }

            .register-intro p {
                font-size: 0.9rem;
                line-height: 1.5;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .form-group.full-width {
                grid-column: span 1;
            }

            .form-label {
                font-size: 0.84rem;
                margin-bottom: 0.45rem;
            }

            .input-icon {
                left: 0.9rem;
            }

            .form-input, .form-select {
                min-height: 52px;
                padding: 0.95rem 0.95rem 0.95rem 2.8rem;
                border-radius: 14px;
                font-size: 0.94rem;
            }

            .password-toggle {
                right: 0.85rem;
                width: 36px;
                height: 36px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .form-hint,
            .consent-hint,
            .security-note {
                font-size: 0.78rem;
                line-height: 1.45;
            }

            .checkbox-group {
                gap: 0.65rem;
                padding: 0.8rem 0.85rem;
                border-radius: 14px;
            }

            .checkbox-group label {
                font-size: 0.84rem;
            }

            .policy-modal-overlay {
                padding: 0.85rem;
            }

            .policy-modal {
                width: 100%;
                max-height: min(88vh, 720px);
                border-radius: 24px;
            }

            .policy-modal-header {
                padding: 1.15rem 1rem 0.85rem;
            }

            .policy-modal-header h3 {
                font-size: 1.1rem;
            }

            .policy-modal-header p,
            .policy-modal-body,
            .policy-modal-body h4 {
                font-size: 0.9rem;
            }

            .policy-modal-body {
                padding: 1rem;
            }

            .policy-modal-footer {
                padding: 0.9rem 1rem 1rem;
            }

            .policy-modal-actions {
                gap: 0.6rem;
            }

            .policy-btn,
            .btn-register,
            .demo-btn {
                min-height: 52px;
                border-radius: 16px;
            }

            .btn-register {
                margin-top: 1.1rem;
                font-size: 0.95rem;
            }

            .divider {
                margin: 1.25rem 0;
            }

            .address-section {
                padding: 1rem;
                margin-top: 0.25rem;
            }

            .address-section h3 {
                font-size: 0.95rem;
                margin-bottom: 0.9rem;
            }

            .postal-display {
                padding: 0.75rem 0.85rem;
                border-radius: 14px;
            }

            .nav-menu {
                display: none;
            }

            .demo-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .login-link {
                margin-top: 1.4rem;
                padding-top: 1.2rem;
            }

            .footer {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .logo-text {
                font-size: 1.05rem;
            }

            .btn-emergency {
                width: 44px;
                height: 44px;
                padding: 0;
                border-radius: 14px;
                justify-content: center;
            }

            .btn-emergency-text {
                display: none;
            }

            .main-content {
                padding: 0.8rem 0.75rem calc(1rem + env(safe-area-inset-bottom));
            }

            .register-card {
                border-radius: 24px;
            }

            .card-body {
                padding: 1rem 0.9rem 1.15rem;
            }
        }
    </style>
    @include('partials.pwa-head')
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="{{ route('home') }}" class="logo">
                <div class="logo-icon">
                    @include('partials.app-logo', ['class' => 'logo-image'])
                </div>
                <span class="logo-text">Pyro<span>Sense</span></span>
            </a>

            <div class="nav-menu">
                <a href="{{ route('home') }}" class="nav-link">Home</a>
                <a href="{{ route('home') }}" class="nav-link">About</a>
                <a href="{{ route('home') }}" class="nav-link">FAQ</a>
                <a href="{{ route('home') }}" class="nav-link">Contact</a>
            </div>

            <div class="nav-actions">
                <a href="{{ route('auth.login') }}" class="nav-auth-link active">Login</a>
                <button class="btn-emergency" onclick="emergencyCall()">
                    <i class="fa-solid fa-phone-alt"></i>
                    <span class="btn-emergency-text">Emergency</span>
                </button>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="register-container">
            <div class="register-card">
                <div class="card-body">
                    <div class="register-intro">
                        <span class="register-intro-badge">
                            <i class="fa-solid fa-user-plus"></i>
                            Citizen Registration
                        </span>
                        <h1>Create your account</h1>
                        <p>Set up your PyroSense citizen access with your contact details, password, and optional address information.</p>
                    </div>

                    @if ($errors->any())
                        <div class="error-message">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register.store') }}" id="registerForm">
                        @csrf
                        <input type="hidden" id="role_id" name="role_id" value="{{ old('role_id', $citizenRoleId) }}">

                        <div class="form-grid">
                            <!-- First Name -->
                            <div class="form-group">
                                <label for="first_name" class="form-label">
                                    <i class="fa-solid fa-user"></i>
                                    First Name <span class="required-star">*</span>
                                </label>
                                <div class="input-wrapper">
                                    <i class="fa-solid fa-user input-icon"></i>
                                    <input type="text" id="first_name" name="first_name" class="form-input" 
                                        placeholder="Enter your first name"
                                        value="{{ old('first_name') }}"
                                        pattern="[A-Za-z]+"
                                        title="First name must contain letters only."
                                        oninput="this.value = this.value.replace(/[^A-Za-z]/g, '')"
                                        required autofocus>
                                </div>
                            </div>

                            <!-- Middle Name (Optional) -->
                            <div class="form-group">
                                <label for="middle_name" class="form-label">
                                    <i class="fa-solid fa-user"></i>
                                    Middle Name
                                </label>
                                <div class="input-wrapper">
                                    <i class="fa-solid fa-user input-icon"></i>
                                    <input type="text" id="middle_name" name="middle_name" class="form-input" 
                                        placeholder="Enter your middle name (optional)"
                                        value="{{ old('middle_name') }}"
                                        pattern="[A-Za-z]+"
                                        title="Middle name must contain letters only."
                                        oninput="this.value = this.value.replace(/[^A-Za-z]/g, '')">
                                </div>
                            </div>

                            <!-- Last Name -->
                            <div class="form-group">
                                <label for="last_name" class="form-label">
                                    <i class="fa-solid fa-user"></i>
                                    Last Name <span class="required-star">*</span>
                                </label>
                                <div class="input-wrapper">
                                    <i class="fa-solid fa-user input-icon"></i>
                                    <input type="text" id="last_name" name="last_name" class="form-input" 
                                        placeholder="Enter your last name"
                                        value="{{ old('last_name') }}"
                                        pattern="[A-Za-z]+"
                                        title="Last name must contain letters only."
                                        oninput="this.value = this.value.replace(/[^A-Za-z]/g, '')"
                                        required>
                                </div>
                            </div>

                            <!-- Contact Number -->
                            <div class="form-group">
                                <label for="contact_number" class="form-label">
                                    <i class="fa-solid fa-phone"></i>
                                    Contact Number <span class="required-star">*</span>
                                </label>
                                <div class="input-wrapper">
                                    <i class="fa-solid fa-phone input-icon"></i>
                                   <input type="tel" id="contact_number" name="contact_number" class="form-input" 
    placeholder="09XX XXX XXXX"
    value="{{ old('contact_number') }}" 
    pattern="[0-9]*"
    inputmode="numeric"
    onkeypress="return event.charCode >= 48 && event.charCode <= 57"
    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
    maxlength="11"
    required>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="form-group full-width">
                                <label for="email" class="form-label">
                                    <i class="fa-solid fa-envelope"></i>
                                    Email Address <span class="required-star">*</span>
                                </label>
                                <div class="input-wrapper">
                                    <i class="fa-solid fa-envelope input-icon"></i>
                                    <input type="email" id="email" name="email" class="form-input" 
                                        placeholder="john.doe@example.com"
                                        value="{{ old('email') }}"
                                        pattern="[A-Za-z0-9.]+@[A-Za-z0-9.]+\.[A-Za-z]{2,}"
                                        title="Email may only use letters, numbers, @, and dots."
                                        oninput="this.value = this.value.replace(/[^A-Za-z0-9@.]/g, '')"
                                        required>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="form-group">
                                <label for="password" class="form-label">
                                    <i class="fa-solid fa-lock"></i>
                                    Password <span class="required-star">*</span>
                                </label>
                                <div class="input-wrapper">
                                    <i class="fa-solid fa-lock input-icon"></i>
                                    <input type="password" id="password" name="password" class="form-input" 
                                        placeholder="Create a strong password"
                                        pattern="(?=.*[A-Z])(?=.*[0-9])[A-Za-z0-9@*.\-]{8,}"
                                        title="Password must be at least 8 characters, include 1 uppercase letter, 1 number, and only use @ - * . as symbols."
                                        oninput="this.value = this.value.replace(/[^A-Za-z0-9@*.\-]/g, '')"
                                        required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                                        <i class="fa-solid fa-eye" id="toggleIcon1"></i>
                                    </button>
                                </div>
                                <div class="form-hint">
                                    <i class="fa-solid fa-info-circle"></i>
                                    Minimum 8 characters with at least one number, one uppercase letter, and only these symbols: @ - * .
                                </div>
                            </div>

                            <!-- Confirm Password -->
                            <div class="form-group">
                                <label for="password_confirmation" class="form-label">
                                    <i class="fa-solid fa-lock"></i>
                                    Confirm Password <span class="required-star">*</span>
                                </label>
                                <div class="input-wrapper">
                                    <i class="fa-solid fa-lock input-icon"></i>
                                    <input type="password" id="password_confirmation" name="password_confirmation" 
                                        class="form-input" placeholder="Re-enter your password"
                                        pattern="(?=.*[A-Z])(?=.*[0-9])[A-Za-z0-9@*.\-]{8,}"
                                        title="Password confirmation must match the same password format."
                                        oninput="this.value = this.value.replace(/[^A-Za-z0-9@*.\-]/g, '')"
                                        required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation', 'toggleIcon2')">
                                        <i class="fa-solid fa-eye" id="toggleIcon2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Address Section -->
                        <div class="address-section">
                            <h3><i class="fa-solid fa-location-dot"></i> Address Information (Optional)</h3>
                            
                            <div class="form-grid">
                                <!-- Province -->
                                <div class="form-group">
                                    <label for="province_id" class="form-label">
                                        <i class="fa-solid fa-map-marker-alt"></i>
                                        Province
                                    </label>
                                    <div class="input-wrapper">
                                        <i class="fa-solid fa-map-marker-alt input-icon"></i>
                                        <select id="province_id" name="province_id" class="form-select">
                                            <option value="">Select Province</option>
                                            @foreach($provinces as $province)
                                                <option value="{{ $province->province_id }}" {{ old('province_id') == $province->province_id ? 'selected' : '' }}>
                                                    {{ $province->province_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <i class="fa-solid fa-chevron-down select-arrow"></i>
                                    </div>
                                </div>

                                <!-- City -->
                                <div class="form-group">
                                    <label for="city_id" class="form-label">
                                        <i class="fa-solid fa-city"></i>
                                        City/Municipality
                                    </label>
                                    <div class="input-wrapper">
                                        <i class="fa-solid fa-city input-icon"></i>
                                        <select id="city_id" name="city_id" class="form-select" disabled>
                                            <option value="">Select City/Municipality</option>
                                        </select>
                                        <i class="fa-solid fa-chevron-down select-arrow"></i>
                                    </div>
                                </div>

                                <!-- Barangay -->
                                <div class="form-group">
                                    <label for="barangay_id" class="form-label">
                                        <i class="fa-solid fa-home"></i>
                                        Barangay
                                    </label>
                                    <div class="input-wrapper">
                                        <i class="fa-solid fa-home input-icon"></i>
                                        <select id="barangay_id" name="barangay_id" class="form-select" disabled>
                                            <option value="">Select Barangay</option>
                                        </select>
                                        <i class="fa-solid fa-chevron-down select-arrow"></i>
                                    </div>
                                </div>

                                <!-- Postal Code - Hidden Input -->
                                <input type="hidden" id="postal_id" name="postal_id" value="{{ old('postal_id') }}">

                                <!-- Street -->
                                <div class="form-group full-width">
                                    <label for="street" class="form-label">
                                        <i class="fa-solid fa-road"></i>
                                        Street Address
                                    </label>
                                    <div class="input-wrapper">
                                        <i class="fa-solid fa-road input-icon"></i>
                                        <input type="text" id="street" name="street" class="form-input" 
                                            placeholder="House number, street name, subdivision, etc."
                                            value="{{ old('street') }}">
                                    </div>
                                </div>
                            </div>

                            <!-- Auto-assigned Postal Code Display -->
                            <div id="postalDisplay" class="postal-display hidden">
                                <span class="postal-label">
                                    <i class="fa-solid fa-mail-bulk"></i> Auto-Assigned Postal Code
                                </span>
                                <span class="postal-value" id="postalCodeValue">
                                    <i class="fa-solid fa-location-dot"></i> 
                                    <span id="postalCodeText">-</span>
                                </span>
                            </div>
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="checkbox-group">
                            <input type="checkbox" id="terms" name="terms" {{ old('terms') ? 'checked' : '' }} required>
                            <label for="terms">
                                I agree to the <a href="#" id="termsOfServiceLink">Terms of Service</a> and <a href="#" id="privacyPolicyLink">Privacy Policy</a>
                            </label>
                        </div>
                        <div class="consent-hint">
                            <i class="fa-solid fa-circle-info"></i>
                            Review and accept both policy modals to continue.
                        </div>

                        <!-- Emergency Agreement -->
                        <div class="checkbox-group">
                            <input type="checkbox" id="emergency_agreement" name="emergency_agreement" {{ old('emergency_agreement') ? 'checked' : '' }} required>
                            <label for="emergency_agreement">
                                I confirm that I am a verified emergency responder and will follow all protocols
                            </label>
                        </div>

                        <button type="submit" class="btn-register" id="submitBtn">
                            <i class="fa-solid fa-user-plus"></i>
                            Create Account
                        </button>

                        <div class="login-link">
                            <p>Already have an account? <a href="{{ route('auth.login') }}">Sign in here</a></p>
                        </div>

                        <div class="security-note">
                            <i class="fa-solid fa-shield"></i>
                            Secured by PyroSense Emergency System
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>© {{ date('Y') }} PyroSense Emergency Response System. All rights reserved.</p>
        </div>
    </footer>

    <div id="termsModalOverlay" class="policy-modal-overlay">
        <div class="policy-modal" role="dialog" aria-modal="true" aria-labelledby="termsModalTitle">
            <div class="policy-modal-header">
                <div class="policy-modal-step">
                    <i class="fa-solid fa-file-contract"></i>
                    Step 1 of 2
                </div>
                <h3 id="termsModalTitle">Terms of Service</h3>
                <p>Please review the service rules before creating your PyroSense account.</p>
            </div>
            <div class="policy-modal-body">
                <section>
                    <h4>Authorized use</h4>
                    <p>PyroSense is intended for emergency coordination, reporting, and safety-related communication. You agree to provide accurate registration details and use the platform only for lawful and authorized public safety activity.</p>
                </section>
                <section>
                    <h4>Account responsibility</h4>
                    <p>You are responsible for keeping your login credentials secure and for activity that happens under your account. Any suspected misuse, unauthorized access, or inaccurate responder information should be reported immediately.</p>
                </section>
                <section>
                    <h4>Operational expectations</h4>
                    <p>You agree not to submit false incidents, disrupt response workflows, or misuse emergency features. PyroSense may suspend or remove access when activity threatens responders, the public, or system reliability.</p>
                </section>
            </div>
            <div class="policy-modal-footer">
                <div class="checkbox-group">
                    <input type="checkbox" id="termsModalCheck">
                    <label for="termsModalCheck">I have read and agree to the Terms of Service.</label>
                </div>
                <div class="policy-modal-actions">
                    <button type="button" class="policy-btn policy-btn-secondary" id="closeTermsModalBtn">Cancel</button>
                    <button type="button" class="policy-btn policy-btn-primary" id="proceedToPrivacyBtn" disabled>Next: Privacy Policy</button>
                </div>
            </div>
        </div>
    </div>

    <div id="privacyModalOverlay" class="policy-modal-overlay">
        <div class="policy-modal" role="dialog" aria-modal="true" aria-labelledby="privacyModalTitle">
            <div class="policy-modal-header">
                <div class="policy-modal-step">
                    <i class="fa-solid fa-shield-halved"></i>
                    Step 2 of 2
                </div>
                <h3 id="privacyModalTitle">Privacy Policy</h3>
                <p>This explains how PyroSense collects and uses your registration and emergency-response information.</p>
            </div>
            <div class="policy-modal-body">
                <section>
                    <h4>Information collected</h4>
                    <p>We collect the profile, contact, role, and location details you provide during registration, along with verification and security metadata needed to protect the platform and support account recovery.</p>
                </section>
                <section>
                    <h4>How data is used</h4>
                    <p>Your data is used to verify identity, support emergency communications, improve dispatch and response workflows, and maintain system security, auditing, and compliance requirements.</p>
                </section>
                <section>
                    <h4>Protection and disclosure</h4>
                    <p>Access to your data is limited to authorized personnel and system processes. Information may be disclosed when required for incident response, legal compliance, or protection of life, safety, and service integrity.</p>
                </section>
            </div>
            <div class="policy-modal-footer">
                <div class="checkbox-group">
                    <input type="checkbox" id="privacyModalCheck">
                    <label for="privacyModalCheck">I acknowledge and accept the Privacy Policy.</label>
                </div>
                <div class="policy-modal-actions">
                    <button type="button" class="policy-btn policy-btn-secondary" id="backToTermsBtn">Back</button>
                    <button type="button" class="policy-btn policy-btn-primary" id="acceptPoliciesBtn" disabled>Accept and Continue</button>
                </div>
            </div>
        </div>
    </div>

    <!-- OTP Verification Modal -->
<div id="otpModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 24px; padding: 2rem; max-width: 400px; width: 90%; margin: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="text-align: center;">
                <div style="width: 60px; height: 60px; background: var(--primary-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                <i class="fa-solid fa-envelope" style="font-size: 1.5rem; color: var(--primary);"></i>
            </div>
            <h3 style="margin-bottom: 0.5rem;">Verify Your Email Address</h3>
            <p style="color: var(--gray-600); margin-bottom: 1.5rem;">We've sent a 6-digit verification code to <strong id="otpEmailDisplay"></strong></p>
            
            <div style="margin-bottom: 1.5rem;">
                <div style="display: flex; gap: 0.5rem; justify-content: center;">
                    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="otp-input" style="width: 50px; height: 60px; text-align: center; font-size: 1.5rem; font-weight: bold; border: 2px solid var(--gray-200); border-radius: 12px; text-align: center;">
                    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="otp-input" style="width: 50px; height: 60px; text-align: center; font-size: 1.5rem; font-weight: bold; border: 2px solid var(--gray-200); border-radius: 12px; text-align: center;">
                    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="otp-input" style="width: 50px; height: 60px; text-align: center; font-size: 1.5rem; font-weight: bold; border: 2px solid var(--gray-200); border-radius: 12px; text-align: center;">
                    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="otp-input" style="width: 50px; height: 60px; text-align: center; font-size: 1.5rem; font-weight: bold; border: 2px solid var(--gray-200); border-radius: 12px; text-align: center;">
                    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="otp-input" style="width: 50px; height: 60px; text-align: center; font-size: 1.5rem; font-weight: bold; border: 2px solid var(--gray-200); border-radius: 12px; text-align: center;">
                    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="otp-input" style="width: 50px; height: 60px; text-align: center; font-size: 1.5rem; font-weight: bold; border: 2px solid var(--gray-200); border-radius: 12px; text-align: center;">
                </div>
                <div id="otpError" style="color: var(--primary); font-size: 0.875rem; margin-top: 0.5rem; display: none;"></div>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <button id="verifyOtpBtn" style="width: 100%; background: var(--primary); color: white; border: none; padding: 0.875rem; border-radius: 12px; font-weight: 600; cursor: pointer;">
                    Verify & Create Account
                </button>
            </div>
            
            <div>
                <p style="font-size: 0.875rem; color: var(--gray-600);">
                    Didn't receive the code?
                    <button id="resendOtpBtn" style="background: none; border: none; color: var(--primary); font-weight: 600; cursor: pointer;">Resend Code</button>
                </p>
                <p id="resendTimer" style="font-size: 0.75rem; color: var(--gray-500); margin-top: 0.5rem;"></p>
            </div>
            
            <button id="closeModalBtn" style="margin-top: 1rem; background: none; border: none; color: var(--gray-500); cursor: pointer; font-size: 0.875rem;">
                Cancel
            </button>
        </div>
    </div>
</div>

<style>
    .otp-input:focus {
        outline: none;
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
    }
</style>


    <script>
        // Replace the existing form submit handler with this
let otpRequestId = null;
let resendTimer = null;
let timeLeft = 60;
const termsCheckbox = document.getElementById('terms');
const termsOfServiceLink = document.getElementById('termsOfServiceLink');
const privacyPolicyLink = document.getElementById('privacyPolicyLink');
const termsModalOverlay = document.getElementById('termsModalOverlay');
const privacyModalOverlay = document.getElementById('privacyModalOverlay');
const termsModalCheck = document.getElementById('termsModalCheck');
const privacyModalCheck = document.getElementById('privacyModalCheck');
const proceedToPrivacyBtn = document.getElementById('proceedToPrivacyBtn');
const acceptPoliciesBtn = document.getElementById('acceptPoliciesBtn');

function openTermsModal() {
    termsModalCheck.checked = false;
    proceedToPrivacyBtn.disabled = true;
    termsModalOverlay.style.display = 'flex';
}

function closeTermsModal() {
    termsModalOverlay.style.display = 'none';
    termsModalCheck.checked = false;
    proceedToPrivacyBtn.disabled = true;
}

function openPrivacyModal() {
    privacyModalCheck.checked = false;
    acceptPoliciesBtn.disabled = true;
    privacyModalOverlay.style.display = 'flex';
}

function closePrivacyModal() {
    privacyModalOverlay.style.display = 'none';
    privacyModalCheck.checked = false;
    acceptPoliciesBtn.disabled = true;
}

termsOfServiceLink.addEventListener('click', (event) => {
    event.preventDefault();
    closePrivacyModal();
    openTermsModal();
});

privacyPolicyLink.addEventListener('click', (event) => {
    event.preventDefault();
    closeTermsModal();
    openTermsModal();
});

termsCheckbox.addEventListener('change', () => {
    if (termsCheckbox.checked) {
        termsCheckbox.checked = false;
        openTermsModal();
    }
});

termsModalCheck.addEventListener('change', () => {
    proceedToPrivacyBtn.disabled = !termsModalCheck.checked;
});

privacyModalCheck.addEventListener('change', () => {
    acceptPoliciesBtn.disabled = !privacyModalCheck.checked;
});

document.getElementById('closeTermsModalBtn').addEventListener('click', () => {
    closeTermsModal();
});

document.getElementById('proceedToPrivacyBtn').addEventListener('click', () => {
    closeTermsModal();
    openPrivacyModal();
});

document.getElementById('backToTermsBtn').addEventListener('click', () => {
    closePrivacyModal();
    openTermsModal();
});

document.getElementById('acceptPoliciesBtn').addEventListener('click', () => {
    termsCheckbox.checked = true;
    closePrivacyModal();
});

[termsModalOverlay, privacyModalOverlay].forEach((overlay) => {
    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            closeTermsModal();
            closePrivacyModal();
        }
    });
});

// Modified form submission - Step 1: Send OTP
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    if (!this.checkValidity()) {
        this.reportValidity();
        return;
    }
    
    // Validate form first
    const firstName = document.getElementById('first_name').value;
    const middleName = document.getElementById('middle_name').value;
    const lastName = document.getElementById('last_name').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('password_confirmation').value;
    const nameRegex = /^[A-Za-z]+$/;
    const emailRegex = /^[A-Za-z0-9.]+@[A-Za-z0-9.]+\.[A-Za-z]{2,}$/;
    const passwordRegex = /^(?=.*[A-Z])(?=.*\d)[A-Za-z\d@*.\-]{8,}$/;

    if (!nameRegex.test(firstName)) {
        alert('First name must contain letters only.');
        return;
    }

    if (middleName && !nameRegex.test(middleName)) {
        alert('Middle name must contain letters only.');
        return;
    }

    if (!nameRegex.test(lastName)) {
        alert('Last name must contain letters only.');
        return;
    }

    if (!emailRegex.test(email)) {
        alert('Email may only use letters, numbers, @, and dots.');
        return;
    }
    
    if (password !== confirmPassword) {
        alert('Passwords do not match!');
        return;
    }
    
    if (!passwordRegex.test(password)) {
        alert('Password must be at least 8 characters, include 1 uppercase letter, 1 number, and only use @ - * . as symbols.');
        return;
    }
    
    if (!document.getElementById('terms').checked) {
        alert('Please accept the Terms of Service');
        return;
    }
    
    if (!document.getElementById('emergency_agreement').checked) {
        alert('Please confirm emergency responder status');
        return;
    }
    
    // Get form data
    const formData = new FormData(this);
    
    // Show loading
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="loading-spinner"></span> Sending verification code...';
    
    try {
        // Step 1: Send OTP
        const response = await fetch('{{ route("register.send-otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                email: email,
                first_name: formData.get('first_name'),
                middle_name: formData.get('middle_name'),
                last_name: formData.get('last_name'),
                contact_number: formData.get('contact_number'),
                role_id: formData.get('role_id'),
                password: formData.get('password'),
                password_confirmation: formData.get('password_confirmation'),
                province_id: formData.get('province_id'),
                city_id: formData.get('city_id'),
                barangay_id: formData.get('barangay_id'),
                postal_id: formData.get('postal_id'),
                street: formData.get('street'),
                terms: formData.get('terms'),
                emergency_agreement: formData.get('emergency_agreement')
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            otpRequestId = data.otp_request_id;
            document.getElementById('otpEmailDisplay').textContent = email;
            document.getElementById('otpModal').style.display = 'flex';
            
            // Setup OTP input fields
            setupOtpInputs();
            
            // Start resend timer
            startResendTimer();
            
            // Reset submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        } else {
            alert(data.message || 'Failed to send verification code');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Network error. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});

// Setup OTP input auto-focus
function setupOtpInputs() {
    const inputs = document.querySelectorAll('.otp-input');
    
    inputs.forEach((input, index) => {
        input.value = '';
        input.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 1);

            if (e.target.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });
        
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && index > 0 && !e.target.value) {
                inputs[index - 1].focus();
            }
        });
        
        // Allow paste
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text');
            if (paste && paste.length === 6 && /^\d+$/.test(paste)) {
                const digits = paste.split('');
                inputs.forEach((inp, idx) => {
                    if (digits[idx]) inp.value = digits[idx];
                });
                inputs[5].focus();
            }
        });
    });
    
    inputs[0].focus();
}

// Verify OTP
// document.getElementById('verifyOtpBtn').addEventListener('click', async () => {
//     const inputs = document.querySelectorAll('.otp-input');
//     const otpCode = Array.from(inputs).map(input => input.value).join('');
    
//     if (otpCode.length !== 6) {
//         document.getElementById('otpError').textContent = 'Please enter the 6-digit code';
//         document.getElementById('otpError').style.display = 'block';
//         return;
//     }
    
//     document.getElementById('otpError').style.display = 'none';
//     const verifyBtn = document.getElementById('verifyOtpBtn');
//     const originalText = verifyBtn.innerHTML;
//     verifyBtn.disabled = true;
//     verifyBtn.innerHTML = '<span class="loading-spinner"></span> Verifying...';
    
//     try {
//         const response = await fetch('{{ route("register.verify-otp") }}', {
//             method: 'POST',
//             headers: {
//                 'Content-Type': 'application/json',
//                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
//             },
//             body: JSON.stringify({
//                 otp_request_id: otpRequestId,
//                 otp_code: otpCode
//             })
//         });
        
//         const data = await response.json();
        
//         if (data.success) {
//             alert(data.message);
//             if (data.redirect) {
//                 window.location.href = data.redirect;
//             }
//         } else {
//             document.getElementById('otpError').textContent = data.message || 'Invalid OTP';
//             document.getElementById('otpError').style.display = 'block';
//             verifyBtn.disabled = false;
//             verifyBtn.innerHTML = originalText;
//         }
//     } catch (error) {
//         console.error('Error:', error);
//         document.getElementById('otpError').textContent = 'Verification failed. Please try again.';
//         document.getElementById('otpError').style.display = 'block';
//         verifyBtn.disabled = false;
//         verifyBtn.innerHTML = originalText;
//     }
// });
// Verify OTP - Updated to send complete form data
document.getElementById('verifyOtpBtn').addEventListener('click', async () => {
    const inputs = document.querySelectorAll('.otp-input');
    const otpCode = Array.from(inputs).map(input => input.value).join('');
    
    if (otpCode.length !== 6) {
        document.getElementById('otpError').textContent = 'Please enter the 6-digit code';
        document.getElementById('otpError').style.display = 'block';
        return;
    }
    
    document.getElementById('otpError').style.display = 'none';
    const verifyBtn = document.getElementById('verifyOtpBtn');
    const originalText = verifyBtn.innerHTML;
    verifyBtn.disabled = true;
    verifyBtn.innerHTML = '<span class="loading-spinner"></span> Verifying...';
    
    try {
        // Get the complete form data from the original form
        const formData = new FormData(document.getElementById('registerForm'));
        const formDataObj = {};
        formData.forEach((value, key) => {
            formDataObj[key] = value;
        });
        
        // Send OTP verification with all form data
        const response = await fetch('{{ route("register.verify-otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                otp_request_id: otpRequestId,
                otp_code: otpCode,
                ...formDataObj  // Include all form data
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        } else {
            document.getElementById('otpError').textContent = data.message || 'Invalid OTP';
            document.getElementById('otpError').style.display = 'block';
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = originalText;
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('otpError').textContent = 'Verification failed. Please try again.';
        document.getElementById('otpError').style.display = 'block';
        verifyBtn.disabled = false;
        verifyBtn.innerHTML = originalText;
    }
});
// Resend OTP
document.getElementById('resendOtpBtn').addEventListener('click', async () => {
    if (timeLeft > 0) {
        alert(`Please wait ${timeLeft} seconds before requesting another OTP`);
        return;
    }
    
    const email = document.getElementById('email').value;
    const resendBtn = document.getElementById('resendOtpBtn');
    resendBtn.disabled = true;
    
    try {
        const response = await fetch('{{ route("register.resend-otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ email: email })
        });
        
        const data = await response.json();
        
        if (data.success) {
            otpRequestId = data.otp_request_id;
            alert('Verification code resent successfully!');
            startResendTimer();
            
            // Clear OTP inputs
            document.querySelectorAll('.otp-input').forEach(input => input.value = '');
            document.querySelectorAll('.otp-input')[0].focus();
        } else {
            alert(data.message || 'Failed to resend verification code');
            resendBtn.disabled = false;
        }
    } catch (error) {
        alert('Failed to resend verification code');
        resendBtn.disabled = false;
    }
});

// Start resend timer
function startResendTimer() {
    timeLeft = 60;
    const timerDisplay = document.getElementById('resendTimer');
    const resendBtn = document.getElementById('resendOtpBtn');
    
    if (resendTimer) clearInterval(resendTimer);
    
    resendBtn.disabled = true;
    timerDisplay.textContent = `Resend available in ${timeLeft} seconds`;
    
    resendTimer = setInterval(() => {
        timeLeft--;
        if (timeLeft <= 0) {
            clearInterval(resendTimer);
            timerDisplay.textContent = '';
            resendBtn.disabled = false;
        } else {
            timerDisplay.textContent = `Resend available in ${timeLeft} seconds`;
        }
    }, 1000);
}

// Close modal
document.getElementById('closeModalBtn').addEventListener('click', () => {
    document.getElementById('otpModal').style.display = 'none';
    if (resendTimer) clearInterval(resendTimer);
});
        // Toggle password visibility
        function togglePassword(inputId, iconId) {
            const password = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);
            
            if (password.type === 'password') {
                password.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Load cities when province changes
        document.getElementById('province_id').addEventListener('change', function() {
            const provinceId = this.value;
            const citySelect = document.getElementById('city_id');
            const barangaySelect = document.getElementById('barangay_id');
            const postalDisplay = document.getElementById('postalDisplay');
            const postalIdInput = document.getElementById('postal_id');
            
            if (!provinceId) {
                citySelect.disabled = true;
                citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                barangaySelect.disabled = true;
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                postalDisplay.classList.add('hidden');
                postalIdInput.value = '';
                return;
            }
            
            // Show loading state
            citySelect.disabled = true;
            citySelect.innerHTML = '<option value="">Loading...</option>';
            
            // Fetch cities
            fetch(`/api/cities/${provinceId}`)
                .then(response => response.json())
                .then(cities => {
                    citySelect.disabled = false;
                    citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                    cities.forEach(city => {
                        citySelect.innerHTML += `<option value="${city.city_id}">${city.city_name}</option>`;
                    });
                    
                    // Reset dependent dropdowns and postal display
                    barangaySelect.disabled = true;
                    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                    postalDisplay.classList.add('hidden');
                    postalIdInput.value = '';
                })
                .catch(error => {
                    console.error('Error:', error);
                    citySelect.innerHTML = '<option value="">Error loading cities</option>';
                });
        });
        
        // Load barangays and auto-assign postal code when city changes
        document.getElementById('city_id').addEventListener('change', function() {
            const cityId = this.value;
            const barangaySelect = document.getElementById('barangay_id');
            const postalDisplay = document.getElementById('postalDisplay');
            const postalIdInput = document.getElementById('postal_id');
            const postalCodeText = document.getElementById('postalCodeText');
            
            if (!cityId) {
                barangaySelect.disabled = true;
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                postalDisplay.classList.add('hidden');
                postalIdInput.value = '';
                return;
            }
            
            // Load barangays
            barangaySelect.disabled = true;
            barangaySelect.innerHTML = '<option value="">Loading...</option>';
            
            fetch(`/api/barangays/${cityId}`)
                .then(response => response.json())
                .then(barangays => {
                    barangaySelect.disabled = false;
                    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                    barangaySelect.innerHTML += '<option value="" disabled>──────────</option>';
                    barangays.forEach(barangay => {
                        barangaySelect.innerHTML += `<option value="${barangay.barangay_id}">${barangay.barangay_name}</option>`;
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    barangaySelect.innerHTML = '<option value="">Error loading barangays</option>';
                });
            
            // Auto-assign first postal code for the selected city
            fetch(`/api/default-postal/${cityId}`)
                .then(response => response.json())
                .then(postalCode => {
                    if (postalCode) {
                        postalIdInput.value = postalCode.postal_id;
                        postalCodeText.textContent = postalCode.postal_code;
                        postalDisplay.classList.remove('hidden');
                        
                        // Add animation effect
                        postalDisplay.style.animation = 'none';
                        setTimeout(() => {
                            postalDisplay.style.animation = 'fadeIn 0.5s ease';
                        }, 10);
                    } else {
                        postalDisplay.classList.add('hidden');
                        postalIdInput.value = '';
                    }
                })
                .catch(error => {
                    console.error('Error loading postal code:', error);
                    postalDisplay.classList.add('hidden');
                });
        });

        function emergencyCall() {
            if (confirm('Call 911 for emergency services?')) {
                window.location.href = 'tel:911';
            }
        }

        // Add animation style
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);

    </script>
    @include('partials.pwa-body')
</body>
</html>
