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
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <title>PyroSense - Login to Emergency Dashboard</title>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary: #c63d26;
            --primary-dark: #a9311d;
            --primary-light: #fff0e8;
            --accent: #d18a16;
            --accent-light: #fff5dc;
            --accent-soft: #efb84a;
            --surface: #ffffff;
            --surface-soft: #fffaf2;
            --surface-warm: #fff6ea;
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
            font-weight: 700;
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

        .nav-link.active {
            color: var(--primary);
            font-weight: 600;
        }

        .nav-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-login-link {
            color: var(--gray-600);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .nav-login-link:hover {
            color: var(--primary);
            background: var(--gradient-soft);
        }

        .nav-login-link.active {
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
            padding: 2.5rem;
        }

        .login-card {
            max-width: 1100px;
            width: 100%;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 30px;
            box-shadow: 0 24px 60px rgba(198, 61, 38, 0.12);
            border: 1px solid rgba(198, 61, 38, 0.08);
            backdrop-filter: blur(12px);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        /* Left Side - Branding */
        .brand-side {
            background: var(--gradient-main);
            padding: 3rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .brand-side::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at top right, rgba(255,255,255,0.16), transparent 28%),
                radial-gradient(circle at bottom left, rgba(255,255,255,0.08), transparent 24%);
        }

        .brand-content {
            position: relative;
            z-index: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .brand-icon {
            width: 110px;
            height: 76px;
            background: rgba(255, 255, 255, 0.96);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 14px 28px rgba(17, 24, 39, 0.14);
            border: 1px solid rgba(255,255,255,0.24);
            padding: 0.5rem 0.8rem;
        }

        .brand-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .brand-title h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .brand-title p {
            color: rgba(255, 255, 255, 0.82);
            font-size: 0.9rem;
        }

        .brand-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin: 2rem 0;
        }

        .stat-item {
            background: rgba(255, 255, 255, 0.14);
            border-radius: 16px;
            padding: 1rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.16);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.84);
        }

        .emergency-badge {
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 50px;
            padding: 1rem 1.5rem;
            display: inline-flex;
            align-items: center;
            gap: 1rem;
            margin-top: auto;
            backdrop-filter: blur(10px);
        }

        .emergency-badge i {
            font-size: 1.5rem;
            color: #fff6d8;
            animation: pulse 2s infinite;
        }

        .emergency-badge div p {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.82);
        }

        .emergency-badge div strong {
            font-size: 1.5rem;
            color: white;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Right Side - Form */
        .form-side {
            padding: 3rem;
            background: linear-gradient(180deg, rgba(255, 250, 242, 0.96), #ffffff);
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
        }

        .form-header p {
            color: var(--gray-500);
        }

        .alert-banner {
            background: var(--gradient-soft);
            border-left: 4px solid var(--accent);
            padding: 1rem;
            border-radius: 14px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-top: 1px solid rgba(198, 61, 38, 0.08);
            border-right: 1px solid rgba(198, 61, 38, 0.08);
            border-bottom: 1px solid rgba(198, 61, 38, 0.08);
        }

        .alert-banner i {
            color: var(--primary);
            font-size: 1.25rem;
        }

        .alert-banner p {
            color: var(--gray-700);
            font-size: 0.9rem;
        }

        .alert-banner strong {
            color: var(--primary);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .form-label i {
            color: var(--primary);
            margin-right: 0.5rem;
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
        }

        .form-input {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 3rem;
            border: 1px solid rgba(198, 61, 38, 0.14);
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.2s;
            background: var(--surface-soft);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(198, 61, 38, 0.12);
        }

        .form-input::placeholder {
            color: var(--gray-400);
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
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .remember-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .remember-checkbox input[type="checkbox"] {
            width: 16px;
            height: 16px;
            border: 1px solid var(--gray-300);
            border-radius: 4px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        .remember-checkbox label {
            font-size: 0.9rem;
            color: var(--gray-600);
        }

        .forgot-link {
            font-size: 0.9rem;
            color: var(--primary);
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            background: var(--gradient-main);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 14px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 14px 30px rgba(198, 61, 38, 0.18);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 34px rgba(198, 61, 38, 0.22);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-google {
            width: 100%;
            background: #ffffff;
            color: var(--gray-800);
            border: 1px solid rgba(17, 24, 39, 0.1);
            padding: 0.95rem 1rem;
            border-radius: 14px;
            font-weight: 600;
            font-size: 0.98rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: 0 10px 24px rgba(17, 24, 39, 0.06);
        }

        .btn-google:hover {
            transform: translateY(-1px);
            border-color: rgba(198, 61, 38, 0.18);
            box-shadow: 0 14px 28px rgba(17, 24, 39, 0.08);
        }

        .btn-google i {
            color: #ea4335;
            font-size: 1.1rem;
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
            background: var(--gray-200);
        }

        .divider span {
            position: relative;
            background: white;
            padding: 0 1rem;
            color: var(--gray-500);
            font-size: 0.9rem;
        }

        .quick-access {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .quick-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem;
            border: 1px solid rgba(198, 61, 38, 0.08);
            border-radius: 12px;
            background: var(--surface-soft);
            color: var(--gray-700);
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .quick-btn:hover {
            border-color: var(--primary);
            background: var(--gradient-soft);
            color: var(--primary);
        }

        .quick-btn i {
            color: var(--primary);
        }

        .register-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-200);
        }

        .register-link p {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .register-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .security-note {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--gray-500);
            font-size: 0.8rem;
        }

        .security-note i {
            color: var(--primary);
            margin-right: 0.25rem;
        }

        .intent-banner {
            background: linear-gradient(135deg, rgba(198, 61, 38, 0.08), rgba(209, 138, 22, 0.16));
            border: 1px solid rgba(198, 61, 38, 0.12);
            border-radius: 16px;
            padding: 1rem 1.1rem;
            margin-bottom: 1.5rem;
        }

        .intent-banner strong {
            display: block;
            color: var(--gray-800);
            margin-bottom: 0.25rem;
        }

        .intent-banner p {
            color: var(--gray-600);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .error-message {
            background: linear-gradient(135deg, #fff0e8, #ffe3da);
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 0.75rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .error-message ul {
            list-style: none;
            margin-left: 1.5rem;
        }

        .error-message li {
            margin-bottom: 0.25rem;
        }

        .error-message li:last-child {
            margin-bottom: 0;
        }

        /* Loading spinner */
        .loading-spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Footer */
        .footer {
            background: linear-gradient(180deg, rgba(255, 250, 242, 0.96), #ffffff);
            border-top: 1px solid rgba(198, 61, 38, 0.08);
            padding: 1.5rem 0;
            text-align: center;
            color: var(--gray-500);
            font-size: 0.85rem;
        }

        /* Responsive */
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

            .nav-login-link {
                display: none;
            }

            .btn-emergency {
                min-height: 44px;
                padding: 0.7rem 0.95rem;
                font-size: 0.88rem;
                box-shadow: 0 10px 18px rgba(198, 61, 38, 0.16);
            }

            .login-card {
                grid-template-columns: 1fr;
                border-radius: 28px;
                box-shadow: 0 18px 38px rgba(198, 61, 38, 0.12);
            }
            
            .brand-side {
                display: none;
            }
            
            .main-content {
                align-items: flex-start;
                justify-content: flex-start;
                padding: 1rem 0.9rem calc(1rem + env(safe-area-inset-bottom));
            }

            .form-side {
                padding: 1.3rem 1.1rem 1.4rem;
                background:
                    linear-gradient(180deg, rgba(255, 250, 242, 0.98), rgba(255, 255, 255, 0.98)),
                    #ffffff;
            }

            .form-header {
                text-align: left;
                margin-bottom: 1.25rem;
            }

            .form-header h1,
            .alert-banner.mobile-hide,
            .intent-banner,
            .quick-access-section {
                display: none !important;
            }

            .form-header h1 {
                font-size: 1.7rem;
                line-height: 1.15;
            }

            .form-header p {
                font-size: 0.92rem;
                line-height: 1.45;
            }

            .alert-banner,
            .intent-banner,
            .error-message {
                border-radius: 16px;
                padding: 0.9rem 0.95rem;
                margin-bottom: 1rem;
            }

            .form-group {
                margin-bottom: 1rem;
            }

            .form-label {
                font-size: 0.84rem;
                margin-bottom: 0.45rem;
            }

            .form-side {
                padding: 1.3rem 1.1rem 1.4rem;
            }

            .input-icon {
                left: 0.9rem;
            }

            .form-input {
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

            .form-options {
                margin-bottom: 1rem;
            }

            .remember-checkbox,
            .forgot-link {
                font-size: 0.84rem;
            }

            .btn-login,
            .btn-google,
            .quick-btn {
                min-height: 52px;
                border-radius: 16px;
            }

            .btn-login,
            .btn-google {
                font-size: 0.95rem;
            }

            .divider {
                margin: 1.25rem 0;
            }

            .quick-access {
                gap: 0.75rem;
            }

            .register-link {
                margin-top: 1.4rem;
                padding-top: 1.25rem;
            }

            .security-note {
                margin-top: 1rem;
                font-size: 0.78rem;
            }

            .footer {
                display: none;
            }

            .nav-menu {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .navbar .container {
                align-items: center;
            }

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

            .btn-emergency span,
            .btn-emergency-text {
                display: none;
            }

            .btn-emergency i {
                margin: 0;
            }

            .main-content {
                padding: 0.8rem 0.75rem calc(1rem + env(safe-area-inset-bottom));
            }

            .login-card {
                border-radius: 24px;
            }

            .form-side {
                padding: 1.15rem 0.95rem 1.25rem;
            }

            .quick-access {
                grid-template-columns: 1fr;
            }
            
            .form-options {
                flex-direction: column;
                gap: 0.85rem;
                align-items: flex-start;
            }
        }
    </style>
    @include('partials.pwa-head')
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="{{ route('home') }}" class="logo">
                <div class="logo-icon">
                    <img src="{{ asset('storage/defaults/pyrosense.png') }}" alt="PyroSense logo" class="logo-image">
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
                <a href="{{ route('register') }}" class="nav-login-link active">Register</a>
                <button class="btn-emergency" onclick="emergencyCall()">
                    <i class="fa-solid fa-phone-alt"></i>
                    <span class="btn-emergency-text">Emergency</span>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="login-card">
            <!-- Left Side - Branding -->
            <div class="brand-side">
                <div class="brand-content">
                    <div class="brand-header">
                        <div class="brand-icon">
                            <img src="{{ asset('storage/defaults/pyrosense.png') }}" alt="PyroSense logo">
                        </div>
                        <div class="brand-title">
                            <h2>PyroSense</h2>
                            <p>Emergency Response System</p>
                        </div>
                    </div>

                    <div class="brand-stats">
                        <div class="stat-item">
                            <div class="stat-value">24/7</div>
                            <div class="stat-label">Coverage</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">4.2min</div>
                            <div class="stat-label">Avg Response</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">25+</div>
                            <div class="stat-label">Stations</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">150+</div>
                            <div class="stat-label">Personnel</div>
                        </div>
                    </div>

                    <div class="emergency-badge">
                        <i class="fa-solid fa-phone-alt"></i>
                        <div>
                            <p>Emergency Hotline</p>
                            <strong>911</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Login Form -->
            <div class="form-side">
                <div class="form-header">
                    <h1>Welcome Back</h1>
                    <p>Sign in to your emergency dashboard</p>
                </div>

                <!-- Alert Banner -->
                <div class="alert-banner mobile-hide">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <p>For emergencies, call <strong>911</strong> immediately. This portal is for administrative access only.</p>
                </div>

                <!-- Error Messages -->
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

                @if (session('success'))
                    <div class="alert-banner">
                        <i class="fa-solid fa-circle-check"></i>
                        <p>{{ session('success') }}</p>
                    </div>
                @endif

                @if (request('post_login_action') === 'quick-report')
                    <div class="intent-banner">
                        <strong>Quick report requested from the PWA</strong>
                        <p>Sign in as a citizen and the incident report form will open automatically after login.</p>
                    </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('auth.authenticate') }}" id="loginForm">
                    @csrf
                    <input type="hidden" name="post_login_action" id="postLoginActionField" value="{{ request('post_login_action') }}">

                    <!-- Email Field -->
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fa-solid fa-envelope"></i>
                            Email Address
                        </label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-envelope input-icon"></i>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-input" 
                                placeholder="firefighter@pyrosense.gov"
                                value="{{ old('email') }}"
                                required
                                autofocus
                            >
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fa-solid fa-lock"></i>
                            Password
                        </label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input" 
                                placeholder="Enter your password"
                                required
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fa-solid fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="form-options">
                        <label class="remember-checkbox">
                            <input type="checkbox" name="remember" id="remember">
                            <label for="remember">Remember me</label>
                        </label>
                        <a href="#" class="forgot-link">Forgot password?</a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-login" id="submitBtn">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                        <span>Sign In to Dashboard</span>
                    </button>

                    <div class="divider">
                        <span>Or continue with</span>
                    </div>

                    <a
                        href="{{ route('auth.google.redirect', array_filter(['post_login_action' => request('post_login_action')])) }}"
                        class="btn-google"
                        id="googleLoginLink"
                    >
                        <i class="fa-brands fa-google"></i>
                        <span>Continue with Google</span>
                    </a>

                    <div class="quick-access-section">
                        <div class="divider">
                            <span>Quick Access</span>
                        </div>

                        <div class="quick-access">
                            <button type="button" class="quick-btn" onclick="fillCredentials('firefighter@pyrosense.gov', 'password123')">
                                <i class="fa-solid fa-user-firefighter"></i>
                                Firefighter
                            </button>
                            <button type="button" class="quick-btn" onclick="fillCredentials('admin@pyrosense.gov', 'admin123')">
                                <i class="fa-solid fa-shield"></i>
                                Administrator
                            </button>
                        </div>
                    </div>

                    <!-- Register Link -->
                    <div class="register-link">
                        <p>Don't have an account? <a href="{{ route('register') }}">Register as responder</a></p>
                    </div>

                    <div class="security-note">
                        <i class="fa-solid fa-shield"></i>
                        Secured by PyroSense Emergency System
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>© {{ date('Y') }} PyroSense Emergency Response System. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const password = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
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

        // Fill credentials for demo
        function fillCredentials(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
            
            // Highlight the fields
            document.getElementById('email').style.borderColor = '#10b981';
            document.getElementById('password').style.borderColor = '#10b981';
            
            setTimeout(() => {
                document.getElementById('email').style.borderColor = '';
                document.getElementById('password').style.borderColor = '';
            }, 1000);
        }

        // Emergency call
        function emergencyCall() {
            if (confirm('Call 911 for emergency services?')) {
                window.location.href = 'tel:911';
            }
        }

        // Form submission loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading-spinner"></span> Signing in...';
        });

        (function() {
            const postLoginActionField = document.getElementById('postLoginActionField');
            const googleLoginLink = document.getElementById('googleLoginLink');
            const initialAction = postLoginActionField?.value || '';
            const userAgent = navigator.userAgent || '';
            const isStandaloneDisplay = window.matchMedia('(display-mode: standalone)').matches
                || window.matchMedia('(display-mode: fullscreen)').matches
                || window.matchMedia('(display-mode: minimal-ui)').matches;
            const launchedFromAppShell = document.referrer.startsWith('android-app://');
            const isTouchViewport = window.matchMedia('(max-width: 768px)').matches
                && window.matchMedia('(pointer: coarse)').matches;
            const isMobileUserAgent = /Android|iPhone|iPad|iPod|Mobile|Windows Phone/i.test(userAgent);
            const isMobilePwaFlow = isStandaloneDisplay
                || window.navigator.standalone === true
                || launchedFromAppShell
                || (isTouchViewport && isMobileUserAgent);

            if (!isMobilePwaFlow || !postLoginActionField || initialAction === 'quick-report') {
                return;
            }

            postLoginActionField.value = 'citizen-mobile-hub';

            if (googleLoginLink) {
                const url = new URL(googleLoginLink.href, window.location.origin);
                url.searchParams.set('post_login_action', 'citizen-mobile-hub');
                googleLoginLink.href = url.toString();
            }
        })();

        // Prevent back button to login page after successful login
        (function() {
            history.pushState(null, null, location.href);
            
            window.addEventListener('popstate', function() {
                fetch('/check-auth', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.authenticated) {
                        window.location.replace('{{ route("citizen.dashboard") }}');
                    } else {
                        history.pushState(null, null, location.href);
                    }
                })
                .catch(() => {
                    history.pushState(null, null, location.href);
                });
            });

            window.addEventListener('pageshow', function(event) {
                if (event.persisted) {
                    fetch('/check-auth')
                        .then(response => response.json())
                        .then(data => {
                            if (data.authenticated) {
                                window.location.replace('{{ route("citizen.dashboard") }}');
                            }
                        });
                }
            });
        })();
    </script>
    @include('partials.pwa-body')
</body>
</html>
