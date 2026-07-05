<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>PyroSense · My Profile</title>
    @vite(['resources/css/app.css', 'resources/css/firefighters/portal.css'])

    @php
        $fullName = trim(collect([$user->first_name, $user->middle_name, $user->last_name])->filter()->join(' '));
        $initials = strtoupper(substr($user->first_name ?? 'F', 0, 1) . substr($user->last_name ?? 'R', 0, 1));
        $roleName = $user->role->role ?? 'Firefighter';
        $rank = $stationOfficer->rank ?? 'Not assigned';
        $status = $stationOfficer->status ?? 'available';
        $stationName = $stationOfficer?->station?->station_name ?? 'Not assigned';
        $accountCode = $user->account_code ?? 'N/A';
        $contactNumber = $user->contact_number ?? 'N/A';
        $email = $user->email ?? 'N/A';
        $street = $user->address->street ?? null;
        $barangay = $user->address?->barangay?->barangay_name ?? null;
        $city = $user->address?->barangay?->city?->city_name ?? null;
        $fullAddress = collect([$street, $barangay, $city])->filter()->join(', ');
        $serviceStart = $stationOfficer?->created_at ?? $user->created_at;
        $yearsOfService = $serviceStart ? $serviceStart->diffInYears(now()) : 0;
        $statusLabel = ucwords(str_replace('-', ' ', $status));
        $stationAddress = collect([
            $stationOfficer?->station?->address?->street,
            $stationOfficer?->station?->address?->barangay?->barangay_name,
            $stationOfficer?->station?->address?->barangay?->city?->city_name,
        ])->filter()->join(', ');
    @endphp

    <style data-ff-page-style="profile">
        .profile-shell {
            display: grid;
            gap: 24px;
        }

        .profile-hero {
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(320px, 0.95fr);
            gap: 24px;
            padding: 28px;
            border-radius: 28px;
            background:
                radial-gradient(circle at top right, rgba(255, 205, 86, 0.22), transparent 34%),
                linear-gradient(135deg, #fff4ea 0%, #fff8f4 46%, #ffffff 100%);
            border: 1px solid rgba(198, 61, 38, 0.12);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
        }

        .profile-hero-copy {
            display: grid;
            gap: 18px;
            align-content: start;
        }

        .profile-chip {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 800;
            color: #9a3412;
            background: #fff7f1;
            border: 1px solid rgba(198, 61, 38, 0.14);
        }

        .profile-hero-title {
            margin: 0;
            font-size: 2rem;
            font-weight: 800;
            color: #172033;
        }

        .profile-hero-subtitle {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.7;
            max-width: 720px;
        }

        .profile-hero-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .hero-detail {
            padding: 16px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(226, 232, 240, 0.9);
        }

        .hero-detail-label {
            margin-bottom: 6px;
            color: #94a3b8;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .hero-detail-value {
            color: #172033;
            font-size: 0.92rem;
            font-weight: 700;
            line-height: 1.5;
        }

        .profile-summary-card,
        .profile-card {
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.92);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
        }

        .profile-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.12fr) minmax(320px, 0.88fr);
            gap: 24px;
        }

        .profile-summary-card {
            padding: 28px;
            display: grid;
            gap: 18px;
            align-content: start;
        }

        .profile-header {
            background: transparent;
            color: inherit;
            padding: 0;
            text-align: left;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            margin: 0 auto 18px;
            border-radius: 999px;
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            color: #c63d26;
            color: #fff;
            border: 6px solid #fff7f3;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            font-weight: 800;
            box-shadow: 0 16px 34px rgba(198, 61, 38, 0.24);
            margin: 0;
        }

        .profile-name {
            margin: 0 0 6px;
            font-size: 2rem;
            font-weight: 800;
            color: #172033;
        }

        .profile-role {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 800;
            background: #fff7f3;
            color: #a9311d;
            border: 1px solid rgba(198, 61, 38, 0.14);
        }

        .profile-body {
            padding: 24px;
        }

        .info-section + .info-section {
            margin-top: 24px;
        }

        .info-section h3 {
            margin: 0 0 14px;
            font-size: 16px;
            font-weight: 700;
            color: #172033;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .info-item {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #f8fafc;
            padding: 14px;
        }

        .info-label {
            margin-bottom: 5px;
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
            line-height: 1.45;
            word-break: break-word;
        }

        .stack-column {
            display: grid;
            gap: 24px;
        }

        .stack-card {
            padding: 22px;
        }

        .stack-title {
            margin: 0 0 14px;
            font-size: 18px;
            font-weight: 800;
            color: #172033;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .badge-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 18px;
        }

        .profile-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: #fff7f3;
            color: #a9311d;
            border: 1px solid rgba(198, 61, 38, 0.14);
        }

        .mini-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .mini-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 16px;
            background: #fff;
        }

        .mini-card-label {
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .mini-card-value {
            margin-top: 6px;
            font-size: 24px;
            font-weight: 800;
            color: #172033;
        }

        .activity-list {
            display: grid;
            gap: 12px;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            background: #fff;
        }

        .activity-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: linear-gradient(135deg, #fff0e8 0%, #fff5dc 100%);
            color: #c63d26;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .activity-text {
            font-size: 14px;
            font-weight: 600;
            color: #172033;
        }

        .activity-meta {
            margin-top: 4px;
            font-size: 12px;
            color: #64748b;
        }

        .profile-actions {
            margin-top: 22px;
            display: flex;
            justify-content: flex-end;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .stat-block {
            padding: 18px;
            border-radius: 18px;
            background: linear-gradient(135deg, #fffaf6 0%, #fff 100%);
            border: 1px solid rgba(226, 232, 240, 0.9);
        }

        .stat-label {
            color: #94a3b8;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .stat-value {
            margin-top: 8px;
            color: #172033;
            font-size: 1.65rem;
            font-weight: 800;
        }

        .stat-note {
            margin-top: 4px;
            color: #64748b;
            font-size: 0.82rem;
        }

        .profile-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.48);
            z-index: 2600;
            padding: 16px;
        }

        .profile-modal.active {
            display: flex;
        }

        .profile-modal-card {
            width: min(560px, 100%);
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.18);
            overflow: hidden;
        }

        .profile-modal-header,
        .profile-modal-footer {
            padding: 18px 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .profile-modal-footer {
            border-top: 1px solid #e5e7eb;
            border-bottom: 0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .profile-modal-footer .btn {
            padding: 12px 18px;
            min-width: 132px;
            justify-content: center;
        }

        .profile-modal-body {
            padding: 20px;
            display: grid;
            gap: 14px;
        }

        .profile-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .profile-form-group {
            display: grid;
            gap: 6px;
        }

        .profile-form-group.full {
            grid-column: 1 / -1;
        }

        .profile-form-label {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
        }

        .profile-form-input {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #dbe1ea;
            border-radius: 12px;
            background: #fff;
            color: #0f172a;
        }

        .profile-form-input:focus {
            outline: none;
            border-color: rgba(198, 61, 38, 0.4);
            box-shadow: 0 0 0 4px rgba(198, 61, 38, 0.08);
        }

        .alert-success,
        .alert-error {
            border-radius: 14px;
            padding: 12px 14px;
            margin-bottom: 18px;
            font-size: 14px;
            font-weight: 600;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        @media (max-width: 1024px) {
            .profile-hero,
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .profile-hero-grid,
            .stats-grid,
            .info-grid,
            .mini-grid,
            .profile-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    @include('partials.pwa-head')
</head>
<body class="firefighter-portal-shell">
    @include('firefighters.partials.shell-sidebar', ['active' => 'profile'])

    <div data-ff-page-root="profile">
    <main class="main-content">
        @include('firefighters.partials.shell-topbar', [
            'title' => 'My Profile',
            'subtitle' => 'Personal information and responder account details'
        ])

        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert-error">{{ $errors->first() }}</div>
        @endif

        <div class="profile-shell">
            <section class="profile-hero">
                <div class="profile-hero-copy">
                    <div>
                        <div class="profile-chip">
                            <i class="fa-solid fa-id-badge"></i>
                            Responder Profile
                        </div>
                        <h1 class="profile-hero-title">{{ $fullName ?: 'Unnamed Firefighter' }}</h1>
                        <p class="profile-hero-subtitle">
                            Keep your responder identity, station details, and account credentials up to date. This profile is aligned with your active firefighter assignment and portal access.
                        </p>
                    </div>

                    <div class="profile-hero-grid">
                        <div class="hero-detail">
                            <div class="hero-detail-label">Role</div>
                            <div class="hero-detail-value">{{ $roleName }}</div>
                        </div>
                        <div class="hero-detail">
                            <div class="hero-detail-label">Rank</div>
                            <div class="hero-detail-value">{{ $rank }}</div>
                        </div>
                        <div class="hero-detail">
                            <div class="hero-detail-label">Station Assignment</div>
                            <div class="hero-detail-value">{{ $stationName }}</div>
                        </div>
                        <div class="hero-detail">
                            <div class="hero-detail-label">Contact Number</div>
                            <div class="hero-detail-value">{{ $contactNumber }}</div>
                        </div>
                    </div>
                </div>

                <aside class="profile-summary-card">
                    <div class="profile-header">
                        <div class="profile-avatar">{{ $initials }}</div>
                        <h2 class="profile-name">{{ $fullName ?: 'Unnamed Firefighter' }}</h2>
                        <div class="profile-role">
                            <i class="fa-solid fa-user"></i>
                            {{ $roleName }}
                        </div>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-block">
                            <div class="stat-label">Years Served</div>
                            <div class="stat-value">{{ $yearsOfService }}</div>
                            <div class="stat-note">{{ \Illuminate\Support\Str::plural('year', $yearsOfService) }} in service</div>
                        </div>
                        <div class="stat-block">
                            <div class="stat-label">Status</div>
                            <div class="stat-value text-xl">{{ $statusLabel }}</div>
                            <div class="stat-note">Current assignment state</div>
                        </div>
                        <div class="stat-block">
                            <div class="stat-label">Station ID</div>
                            <div class="stat-value">{{ $stationOfficer->station_officer_id ?? 'N/A' }}</div>
                            <div class="stat-note">Responder record</div>
                        </div>
                    </div>

                    <div class="profile-actions">
                        <button type="button" class="btn btn-primary" id="openProfileEditModal">
                            <i class="fa-regular fa-pen-to-square"></i>
                            Edit Profile
                        </button>
                    </div>
                </aside>
            </section>

            <div class="profile-grid">
                <section class="profile-card">
                    <div class="profile-body">
                        <div class="info-section">
                            <h3><i class="fa-solid fa-user"></i> Personal Information</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">First Name</div>
                                    <div class="info-value">{{ $user->first_name ?: 'N/A' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Last Name</div>
                                    <div class="info-value">{{ $user->last_name ?: 'N/A' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Middle Name</div>
                                    <div class="info-value">{{ $user->middle_name ?: 'N/A' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Email Address</div>
                                    <div class="info-value">{{ $email }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Contact Number</div>
                                    <div class="info-value">{{ $contactNumber }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Home Address</div>
                                    <div class="info-value">{{ $fullAddress ?: 'No address on file' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="info-section">
                            <h3><i class="fa-solid fa-briefcase"></i> Professional Information</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Account Code</div>
                                    <div class="info-value">{{ $accountCode }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Role</div>
                                    <div class="info-value">{{ $roleName }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Rank</div>
                                    <div class="info-value">{{ $rank }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Status</div>
                                    <div class="info-value">{{ $statusLabel }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Station Assignment</div>
                                    <div class="info-value">{{ $stationName }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Years of Service</div>
                                    <div class="info-value">{{ $yearsOfService }} {{ \Illuminate\Support\Str::plural('year', $yearsOfService) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="stack-column">
                    <div class="profile-card stack-card">
                        <h3 class="stack-title"><i class="fa-solid fa-chart-line"></i> Assignment Snapshot</h3>
                        <div class="badge-row">
                            <div class="profile-pill">
                                <i class="fa-solid fa-circle"></i>
                                {{ $statusLabel }}
                            </div>
                            <div class="profile-pill">
                                <i class="fa-solid fa-building"></i>
                                {{ $stationName }}
                            </div>
                        </div>
                        <div class="mini-grid">
                            <div class="mini-card">
                                <div class="mini-card-label">Station Officer ID</div>
                                <div class="mini-card-value">{{ $stationOfficer->station_officer_id ?? 'N/A' }}</div>
                            </div>
                            <div class="mini-card">
                                <div class="mini-card-label">User ID</div>
                                <div class="mini-card-value">{{ $user->user_id }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-card stack-card">
                        <h3 class="stack-title"><i class="fa-solid fa-map-location-dot"></i> Station Details</h3>
                        <div class="activity-list">
                            <div class="activity-item">
                                <div class="activity-icon"><i class="fa-solid fa-building"></i></div>
                                <div>
                                    <div class="activity-text">Station Name</div>
                                    <div class="activity-meta">{{ $stationName }}</div>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon"><i class="fa-solid fa-location-dot"></i></div>
                                <div>
                                    <div class="activity-text">Station Address</div>
                                    <div class="activity-meta">{{ $stationAddress ?: 'No station address on file' }}</div>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon"><i class="fa-solid fa-calendar"></i></div>
                                <div>
                                    <div class="activity-text">Member Since</div>
                                    <div class="activity-meta">{{ optional($serviceStart)->format('F j, Y') ?: 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <div class="profile-modal" id="profileEditModal">
        <div class="profile-modal-card">
            <div class="profile-modal-header">
                <h3 class="stack-title" style="margin: 0;">
                    <i class="fa-regular fa-pen-to-square"></i>
                    Edit Profile
                </h3>
            </div>
            <form method="POST" action="{{ route('firefighter.my-profile.update') }}">
                @csrf
                @method('PUT')
                <div class="profile-modal-body">
                    <div class="profile-form-grid">
                        <div class="profile-form-group">
                            <label class="profile-form-label" for="first_name">First Name</label>
                            <input id="first_name" name="first_name" type="text" class="profile-form-input" value="{{ old('first_name', $user->first_name) }}" required>
                        </div>
                        <div class="profile-form-group">
                            <label class="profile-form-label" for="last_name">Last Name</label>
                            <input id="last_name" name="last_name" type="text" class="profile-form-input" value="{{ old('last_name', $user->last_name) }}" required>
                        </div>
                        <div class="profile-form-group full">
                            <label class="profile-form-label" for="middle_name">Middle Name</label>
                            <input id="middle_name" name="middle_name" type="text" class="profile-form-input" value="{{ old('middle_name', $user->middle_name) }}">
                        </div>
                        <div class="profile-form-group full">
                            <label class="profile-form-label" for="email">Email Address</label>
                            <input id="email" name="email" type="email" class="profile-form-input" value="{{ old('email', $user->email) }}">
                        </div>
                        <div class="profile-form-group full">
                            <label class="profile-form-label" for="contact_number">Contact Number</label>
                            <input id="contact_number" name="contact_number" type="text" class="profile-form-input" value="{{ old('contact_number', $user->contact_number) }}" required>
                        </div>
                        <div class="profile-form-group full">
                            <label class="profile-form-label" for="password">New Password</label>
                            <input id="password" name="password" type="password" class="profile-form-input" minlength="8" placeholder="Leave blank to keep your current password">
                        </div>
                        <div class="profile-form-group full">
                            <label class="profile-form-label" for="password_confirmation">Confirm New Password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" class="profile-form-input" minlength="8" placeholder="Retype the new password">
                        </div>
                    </div>
                </div>
                <div class="profile-modal-footer">
                    <button type="button" class="btn btn-outline" id="closeProfileEditModal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('profileEditModal');
            const openBtn = document.getElementById('openProfileEditModal');
            const closeBtn = document.getElementById('closeProfileEditModal');

            if (openBtn && modal) {
                openBtn.addEventListener('click', function () {
                    modal.classList.add('active');
                });
            }

            if (closeBtn && modal) {
                closeBtn.addEventListener('click', function () {
                    modal.classList.remove('active');
                });
            }

            if (modal) {
                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        modal.classList.remove('active');
                    }
                });
            }

            @if ($errors->any())
                if (modal) {
                    modal.classList.add('active');
                }
            @endif
        });
    </script>
    </div>
    @include('partials.pwa-body')
</body>
</html>
