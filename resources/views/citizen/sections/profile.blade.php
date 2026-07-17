@php
    $profileUser = $citizenUser ?? auth()->user();
    $profileAddress = $profileUser?->address;
    $profileBarangay = $profileAddress?->barangay;
    $profileCity = $profileBarangay?->city;
    $profileName = trim(($profileUser->first_name ?? '') . ' ' . ($profileUser->last_name ?? ''));
    $profileName = $profileName !== '' ? $profileName : ($profileUser->name ?? 'Citizen');
    $profileInitials = strtoupper(substr($profileUser->first_name ?? 'C', 0, 1) . substr($profileUser->last_name ?? ($profileUser->first_name ?? 'P'), 0, 1));
    $selectedCityId = old('city_id', $profileCity?->city_id);
    $barangayOptions = collect();
    $memberSince = $profileUser?->created_at?->format('F j, Y') ?? 'Not available';
    $lastLogin = $profileUser?->last_login_at
        ? (is_string($profileUser->last_login_at) ? $profileUser->last_login_at : $profileUser->last_login_at->format('F j, Y g:i A'))
        : 'Today';
    $profileStreet = $profileAddress?->street ?? '';
    $profileFullAddress = trim(($profileStreet ? $profileStreet . ', ' : '') . ($profileBarangay?->barangay_name ?? 'No barangay yet') . ', ' . ($profileCity?->city_name ?? 'No city yet'));
    $profileReports = collect($citizenCombinedReports ?? [])->values();
    $profileFireReports = $profileReports->filter(fn ($report) => ($report['report_type'] ?? null) === 'fire' || ($report['type_label'] ?? null) === 'Fire Incident')->values();
    $profileRiskReports = $profileReports->filter(fn ($report) => ($report['report_kind'] ?? null) || ($report['report_type'] ?? null) === 'risk_hazard' || ($report['type_label'] ?? null) === 'Risk/Hazard Factor')->values();
    $profileFsicRequests = collect($fsicRequests ?? [])->values();
    $profilePendingFsic = $profileFsicRequests->where('status', 'pending')->count();
    $profileApprovedFsic = $profileFsicRequests->where('status', 'approved')->count();
    $profileRecentActivity = $profileReports
        ->map(function ($report) {
            return [
                'title' => ($report['type_label'] ?? $report['report_type_label'] ?? 'Citizen Report'),
                'meta' => ($report['status_label'] ?? $report['review_status_label'] ?? $report['status'] ?? 'Pending') . ' • ' . ($report['location_label'] ?? $report['barangay_name'] ?? 'Location pending'),
                'time' => $report['created_at_human'] ?? '',
                'kind' => (($report['report_type'] ?? null) === 'fire' || ($report['type_label'] ?? null) === 'Fire Incident') ? 'fire' : 'risk',
            ];
        })
        ->take(4)
        ->values();

    if ($selectedCityId) {
        $barangayOptions = optional($cities->firstWhere('city_id', (int) $selectedCityId))->barangays ?? collect();
    }
@endphp

<style>
    #section-profile.profile-page {
        --profile-red: #c63d26;
        --profile-red-dark: #9f2f1d;
        --profile-orange: #e39b17;
        --profile-ink: #132033;
        --profile-muted: #64748b;
        width: min(100%, 1240px);
        margin-inline: auto;
        padding: clamp(0.85rem, 1.45vw, 1.35rem);
        padding-bottom: calc(7rem + env(safe-area-inset-bottom, 0px));
        color: var(--profile-ink);
    }

    #section-profile .profile-shell {
        display: grid;
        gap: clamp(1rem, 2vw, 1.35rem);
    }

    #section-profile .profile-hero,
    #section-profile .profile-card {
        border: 1px solid rgba(120, 53, 15, 0.11);
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 18px 40px rgba(88, 43, 20, 0.075);
    }

    #section-profile .profile-hero {
        overflow: hidden;
        border-color: rgba(198, 61, 38, 0.16);
        border-radius: 2rem;
        background:
            radial-gradient(circle at 92% 12%, rgba(227, 155, 23, 0.24), transparent 16rem),
            linear-gradient(135deg, rgba(255, 248, 237, 0.98), rgba(255, 255, 255, 0.96));
        padding: clamp(1.1rem, 3vw, 1.75rem);
    }

    #section-profile .profile-hero-grid {
        display: grid;
        gap: 1rem;
    }

    #section-profile .profile-eyebrow {
        display: inline-flex;
        width: fit-content;
        align-items: center;
        gap: 0.45rem;
        border-radius: 999px;
        background: rgba(198, 61, 38, 0.1);
        color: var(--profile-red-dark);
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: 0.13em;
        text-transform: uppercase;
        padding: 0.45rem 0.7rem;
    }

    #section-profile .profile-title {
        margin-top: 0.8rem;
        color: var(--profile-ink);
        font-size: clamp(1.85rem, 5vw, 2.85rem);
        font-weight: 900;
        line-height: 1;
        letter-spacing: -0.055em;
    }

    #section-profile .profile-subtitle {
        max-width: 44rem;
        margin-top: 0.75rem;
        color: var(--profile-muted);
        font-size: clamp(0.94rem, 2.4vw, 1.03rem);
        line-height: 1.7;
    }

    #section-profile .profile-hero-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
    }

    #section-profile .profile-primary-btn,
    #section-profile .profile-secondary-btn,
    #section-profile .profile-security-btn {
        display: inline-flex;
        min-height: 46px;
        align-items: center;
        justify-content: center;
        gap: 0.55rem;
        border-radius: 1rem;
        border: 0;
        padding: 0.8rem 1rem;
        font-size: 0.9rem;
        font-weight: 850;
        cursor: pointer;
        text-decoration: none;
    }

    #section-profile .profile-primary-btn {
        background: linear-gradient(135deg, var(--profile-red), var(--profile-orange));
        color: #fff;
        box-shadow: 0 16px 34px rgba(198, 61, 38, 0.24);
    }

    #section-profile .profile-secondary-btn {
        border: 1px solid rgba(148, 163, 184, 0.22);
        background: #fff;
        color: #475569;
    }

    #section-profile .profile-security-btn {
        background: #fff7ed;
        color: #9a3412;
    }

    #section-profile .profile-layout {
        display: grid;
        grid-template-columns: minmax(280px, 0.82fr) minmax(0, 1.18fr);
        gap: 1.1rem;
        align-items: start;
        max-width: 1120px;
        margin-inline: auto;
    }

    #section-profile .profile-card {
        overflow: hidden;
        border-radius: 1.6rem;
    }

    #section-profile .profile-card-head {
        border-bottom: 1px solid rgba(148, 163, 184, 0.16);
        background: linear-gradient(135deg, rgba(255, 248, 237, 0.74), rgba(255, 255, 255, 0.96));
        padding: 1.1rem;
    }

    #section-profile .profile-card-body {
        padding: 1.1rem;
    }

    #section-profile .profile-card-title {
        margin-top: 0.45rem;
        color: var(--profile-ink);
        font-size: 1.15rem;
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    #section-profile .profile-card-copy {
        margin-top: 0.28rem;
        color: var(--profile-muted);
        font-size: 0.86rem;
        line-height: 1.55;
    }

    #section-profile .profile-summary-card {
        position: relative;
        top: auto;
    }

    #section-profile .profile-insights-card {
        align-self: stretch;
    }

    #section-profile .profile-insights-card .profile-card-body {
        display: grid;
        gap: 1rem;
    }

    #section-profile .profile-summary-card::before {
        content: '';
        display: block;
        height: 8.5rem;
        margin: -1.1rem -1.1rem 0;
        background:
            radial-gradient(circle at 85% 10%, rgba(255, 255, 255, 0.36), transparent 10rem),
            linear-gradient(135deg, #c63d26, #e39b17);
    }

    #section-profile .profile-summary-card .profile-avatar {
        margin-top: -3rem;
        border: 5px solid #fff;
        position: relative;
        z-index: 1;
    }

    #section-profile .profile-avatar {
        width: 6rem;
        height: 6rem;
        border-radius: 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.9rem;
        color: #fff;
        background: linear-gradient(135deg, var(--profile-red), var(--profile-orange));
        font-size: 2.2rem;
        font-weight: 950;
        box-shadow: 0 18px 36px rgba(198, 61, 38, 0.22);
    }

    #section-profile .profile-summary-name {
        color: var(--profile-ink);
        text-align: center;
        font-size: 1.25rem;
        font-weight: 900;
    }

    #section-profile .profile-summary-email {
        margin-top: 0.25rem;
        color: var(--profile-muted);
        text-align: center;
        font-size: 0.88rem;
        overflow-wrap: anywhere;
    }

    #section-profile .profile-badge-row {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.45rem;
        margin-top: 0.85rem;
    }

    #section-profile .profile-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        border-radius: 999px;
        background: #ecfdf5;
        color: #047857;
        padding: 0.42rem 0.7rem;
        font-size: 0.75rem;
        font-weight: 850;
    }

    #section-profile .profile-badge--role {
        background: #fff7ed;
        color: #c2410c;
    }

    #section-profile .profile-summary-list,
    #section-profile .profile-info-grid {
        display: grid;
        gap: 0.75rem;
    }

    #section-profile .profile-summary-list {
        margin-top: 1.1rem;
    }

    #section-profile .profile-social-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.75rem;
        margin-top: 1rem;
    }

    #section-profile .profile-social-stat {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 1.15rem;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), var(--profile-stat-bg, rgba(255, 248, 237, 0.75)));
        padding: 0.9rem;
    }

    #section-profile .profile-social-stat::after {
        content: '';
        position: absolute;
        right: -1.75rem;
        bottom: -2.25rem;
        width: 5.5rem;
        height: 5.5rem;
        border-radius: 999px;
        background: var(--profile-stat-glow, rgba(198, 61, 38, 0.1));
    }

    #section-profile .profile-social-icon {
        position: relative;
        z-index: 1;
        display: inline-flex;
        width: 2.35rem;
        height: 2.35rem;
        align-items: center;
        justify-content: center;
        border-radius: 0.85rem;
        background: var(--profile-stat-icon-bg, #fff7ed);
        color: var(--profile-stat-icon-color, #c2410c);
    }

    #section-profile .profile-social-value {
        position: relative;
        z-index: 1;
        margin-top: 0.75rem;
        color: var(--profile-ink);
        font-size: 1.8rem;
        font-weight: 950;
        letter-spacing: -0.05em;
        line-height: 1;
    }

    #section-profile .profile-social-label {
        position: relative;
        z-index: 1;
        margin-top: 0.3rem;
        color: var(--profile-muted);
        font-size: 0.76rem;
        font-weight: 800;
        line-height: 1.25;
    }

    #section-profile .profile-feed-card {
        margin-top: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 1.2rem;
        background: #fff;
        padding: 1rem;
    }

    #section-profile .profile-feed-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 0.85rem;
    }

    #section-profile .profile-feed-title {
        color: var(--profile-ink);
        font-size: 1rem;
        font-weight: 900;
    }

    #section-profile .profile-feed-subtitle {
        color: var(--profile-muted);
        font-size: 0.78rem;
    }

    #section-profile .profile-activity-list {
        display: grid;
        gap: 0.65rem;
    }

    #section-profile .profile-activity-item {
        display: flex;
        gap: 0.75rem;
        align-items: flex-start;
        border-radius: 1rem;
        background: linear-gradient(135deg, #fff7ed, #fff);
        padding: 0.75rem;
    }

    #section-profile .profile-activity-icon {
        display: inline-flex;
        width: 2.35rem;
        height: 2.35rem;
        flex: 0 0 2.35rem;
        align-items: center;
        justify-content: center;
        border-radius: 0.85rem;
        background: var(--profile-activity-bg, #fef2f2);
        color: var(--profile-activity-color, #dc2626);
    }

    #section-profile .profile-activity-title {
        color: var(--profile-ink);
        font-size: 0.9rem;
        font-weight: 850;
    }

    #section-profile .profile-activity-meta {
        margin-top: 0.12rem;
        color: var(--profile-muted);
        font-size: 0.76rem;
        line-height: 1.35;
    }

    #section-profile .profile-info-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    #section-profile .profile-info-grid--single {
        grid-template-columns: 1fr;
    }

    #section-profile .profile-info-item {
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 1.05rem;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(255, 248, 237, 0.64));
        padding: 0.9rem;
    }

    #section-profile .profile-label {
        color: #94a3b8;
        font-size: 0.72rem;
        font-weight: 850;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    #section-profile .profile-value {
        margin-top: 0.28rem;
        color: var(--profile-ink);
        font-size: 0.94rem;
        font-weight: 750;
        line-height: 1.45;
        overflow-wrap: anywhere;
    }

    #section-profile .profile-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.9rem;
    }

    #section-profile .profile-field--wide {
        grid-column: 1 / -1;
    }

    #section-profile .profile-form-label {
        display: block;
        margin-bottom: 0.35rem;
        color: #475569;
        font-size: 0.82rem;
        font-weight: 800;
    }

    #section-profile .profile-input {
        width: 100%;
        min-height: 46px;
        border: 1px solid rgba(148, 163, 184, 0.24);
        border-radius: 1rem;
        background: #fff;
        color: var(--profile-ink);
        outline: none;
        padding: 0.75rem 0.9rem;
    }

    #section-profile .profile-input:focus {
        border-color: rgba(198, 61, 38, 0.42);
        box-shadow: 0 0 0 3px rgba(198, 61, 38, 0.1);
    }

    #section-profile .profile-card-actions,
    #section-profile .profile-form-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 0.65rem;
        margin-top: 1rem;
    }

    #section-profile .profile-edit-form,
    #section-profile .profile-account-form {
        display: none;
    }

    #section-profile .profile-layout > main.profile-shell {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 3600;
        overflow-y: auto;
        align-content: start;
        justify-items: center;
        gap: 0.85rem;
        background: rgba(15, 23, 42, 0.48);
        padding: clamp(1rem, 3vw, 2rem);
        padding-bottom: calc(2rem + env(safe-area-inset-bottom, 0px));
    }

    #section-profile.is-editing .profile-layout > main.profile-shell,
    #section-profile.is-account-settings .profile-layout > main.profile-shell {
        display: grid;
    }

    #section-profile .profile-layout > main.profile-shell > * {
        width: min(760px, 100%);
    }

    #section-profile.is-editing .profile-layout > main.profile-shell > section.profile-card,
    #section-profile.is-account-settings .profile-layout > main.profile-shell > form {
        display: none;
    }

    #section-profile.is-editing .profile-layout > main.profile-shell > form,
    #section-profile.is-account-settings .profile-layout > main.profile-shell > section.profile-card {
        display: block;
    }

    #section-profile .profile-modal-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        border: 1px solid rgba(120, 53, 15, 0.11);
        border-radius: 1.35rem;
        background: rgba(255, 255, 255, 0.98);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
        padding: 0.9rem 1rem;
    }

    #section-profile .profile-modal-kicker {
        color: #c2410c;
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }

    #section-profile .profile-modal-title {
        color: var(--profile-ink);
        font-size: 1.1rem;
        font-weight: 900;
    }

    #section-profile .profile-modal-close {
        display: inline-flex;
        width: 2.5rem;
        height: 2.5rem;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 999px;
        background: #fff7ed;
        color: #c2410c;
        cursor: pointer;
    }

    #section-profile.is-editing .profile-readonly {
        display: none;
    }

    #section-profile.is-editing .profile-edit-form,
    #section-profile .profile-account-form.is-open {
        display: block;
    }

    #section-profile.is-editing .profile-card {
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
    }

    #section-profile .profile-settings-list {
        display: grid;
        gap: 0.75rem;
    }

    #section-profile .profile-setting-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 1.05rem;
        background: #fff;
        padding: 0.9rem;
    }

    #section-profile .profile-setting-title {
        color: var(--profile-ink);
        font-size: 0.95rem;
        font-weight: 850;
    }

    #section-profile .profile-setting-copy {
        margin-top: 0.15rem;
        color: var(--profile-muted);
        font-size: 0.78rem;
        line-height: 1.4;
    }

    #section-profile .profile-password-wrap {
        position: relative;
    }

    #section-profile .profile-password-toggle {
        position: absolute;
        right: 0.65rem;
        top: 50%;
        transform: translateY(-50%);
        border: 0;
        background: transparent;
        color: #c2410c;
        font-size: 0.78rem;
        font-weight: 850;
        cursor: pointer;
    }

    #section-profile .profile-password-wrap .profile-input {
        padding-right: 3.6rem;
    }

    #section-profile .profile-message {
        display: none;
        margin-top: 0.85rem;
        border-radius: 1rem;
        padding: 0.75rem 0.9rem;
        font-size: 0.86rem;
        font-weight: 750;
    }

    #section-profile .profile-message.is-visible {
        display: block;
    }

    #section-profile .profile-message.is-error {
        background: #fef2f2;
        color: #b91c1c;
    }

    #section-profile .profile-message.is-success {
        background: #ecfdf5;
        color: #047857;
    }

    #section-profile .profile-mobile-bottom-space {
        display: none;
    }

    @media (min-width: 900px) {
        #section-profile .profile-hero-grid {
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: start;
        }
    }

    @media (max-width: 1024px) {
        #section-profile .profile-layout {
            grid-template-columns: 1fr;
        }

        #section-profile .profile-summary-card {
            position: static;
        }
    }

    @media (max-width: 640px) {
        #section-profile.profile-page {
            width: 100%;
            padding: 1rem;
            padding-bottom: calc(7.5rem + env(safe-area-inset-bottom, 0px));
        }

        #section-profile .profile-hero,
        #section-profile .profile-card {
            border-radius: 1.25rem;
            box-shadow: none;
        }

        #section-profile .profile-title {
            max-width: 17rem;
            font-size: 2rem;
            line-height: 1.05;
        }

        #section-profile .profile-subtitle {
            max-width: 19rem;
            font-size: 0.93rem;
            line-height: 1.58;
        }

        #section-profile .profile-hero-actions,
        #section-profile .profile-primary-btn,
        #section-profile .profile-secondary-btn,
        #section-profile .profile-security-btn {
            width: 100%;
        }

        #section-profile .profile-layout {
            max-width: none;
        }

        #section-profile .profile-layout > main.profile-shell {
            padding: 0.75rem;
            padding-bottom: calc(1rem + env(safe-area-inset-bottom, 0px));
        }

        #section-profile.is-editing .profile-layout > main.profile-shell,
        #section-profile.is-account-settings .profile-layout > main.profile-shell {
            align-content: start;
            grid-template-rows: auto minmax(0, 1fr);
            height: 100dvh;
            max-height: 100dvh;
            overflow: hidden;
        }

        #section-profile.is-editing .profile-layout > main.profile-shell > form,
        #section-profile.is-account-settings .profile-layout > main.profile-shell > section.profile-card {
            min-height: 0;
            max-height: calc(100dvh - 6.25rem - env(safe-area-inset-bottom, 0px));
            overflow-y: auto;
            overscroll-behavior: contain;
        }

        #section-profile.is-account-settings .profile-layout > main.profile-shell > section.profile-card {
            display: flex;
            flex-direction: column;
        }

        #section-profile.is-account-settings .profile-card-head {
            flex: 0 0 auto;
            padding: 1rem;
        }

        #section-profile.is-account-settings .profile-card-body {
            min-height: 0;
            overflow-y: auto;
            padding: 1rem;
            padding-bottom: calc(1.4rem + env(safe-area-inset-bottom, 0px));
        }

        #section-profile.is-account-settings .profile-card-copy,
        #section-profile.is-account-settings .profile-setting-copy {
            font-size: 0.76rem;
            line-height: 1.35;
        }

        #section-profile.is-account-settings .profile-settings-list {
            gap: 0.65rem;
        }

        #section-profile.is-account-settings .profile-setting-row {
            gap: 0.65rem;
            padding: 0.85rem;
            border-radius: 1rem;
        }

        #section-profile.is-account-settings .profile-account-form.is-open {
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 1rem;
            background: #fff;
            padding: 0.85rem;
        }

        #section-profile.is-account-settings .profile-form-label {
            font-size: 0.78rem;
        }

        #section-profile.is-account-settings .profile-input {
            min-height: 46px;
            font-size: 0.9rem;
        }

        #section-profile.is-account-settings .profile-password-toggle {
            right: 0.75rem;
            font-size: 0.72rem;
        }

        #section-profile .profile-info-grid,
        #section-profile .profile-social-grid,
        #section-profile .profile-form-grid {
            grid-template-columns: 1fr;
        }

        #section-profile .profile-social-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        #section-profile .profile-setting-row {
            display: grid;
        }

        #section-profile .profile-card-actions,
        #section-profile .profile-form-actions {
            display: grid;
            grid-template-columns: 1fr;
        }

        #section-profile .profile-mobile-bottom-space {
            display: block;
            height: calc(2.25rem + env(safe-area-inset-bottom, 0px));
        }
    }
</style>

<div id="section-profile" class="content-section profile-page">
    <div class="profile-shell">
        <section class="profile-hero">
            <div class="profile-hero-grid">
                <div>
                    <span class="profile-eyebrow"><i class="fa-solid fa-user-shield"></i> Citizen account</span>
                    <h1 class="profile-title">My Profile</h1>
                    <p class="profile-subtitle">Review your personal details, address, and account security settings for the PyroSense citizen portal.</p>
                </div>
                <div class="profile-hero-actions">
                    <button type="button" class="profile-primary-btn" data-profile-edit-open>
                        <i class="fa-solid fa-pen-to-square"></i>
                        Edit Information
                    </button>
                    <button type="button" class="profile-secondary-btn" data-profile-settings-open>
                        <i class="fa-solid fa-shield-halved"></i>
                        Account Settings
                    </button>
                </div>
            </div>
        </section>

        @if ($errors->any())
            <div class="profile-message is-visible is-error">
                Please fix the highlighted profile fields.
            </div>
        @endif

        <div class="profile-layout">
            <aside class="profile-card profile-summary-card">
                <div class="profile-card-body">
                    <div id="citizenProfileCardInitials" class="profile-avatar">{{ $profileInitials }}</div>
                    <h2 id="citizenProfileCardName" class="profile-summary-name">{{ $profileName }}</h2>
                    <p id="citizenProfileCardEmail" class="profile-summary-email">{{ $profileUser->email }}</p>
                    <div class="profile-badge-row">
                        <span class="profile-badge"><i class="fa-solid fa-circle-check"></i> Verified Citizen</span>
                        <span id="citizenProfileRole" class="profile-badge profile-badge--role">{{ $profileUser->role->role ?? 'Citizen' }}</span>
                    </div>

                    <div class="profile-summary-list">
                        <div class="profile-info-item">
                            <p class="profile-label">Member Since</p>
                            <p class="profile-value">{{ $memberSince }}</p>
                        </div>
                        <div class="profile-info-item">
                            <p class="profile-label">Last Login</p>
                            <p class="profile-value">{{ $lastLogin }}</p>
                        </div>
                        <div class="profile-info-item">
                            <p class="profile-label">Current Address</p>
                            <p id="citizenProfileCurrentAddress" class="profile-value">{{ $profileFullAddress }}</p>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="profile-card profile-insights-card">
                <div class="profile-card-head">
                    <span class="profile-eyebrow"><i class="fa-solid fa-chart-simple"></i> Citizen contributions</span>
                    <h2 class="profile-card-title">Activity Overview</h2>
                    <p class="profile-card-copy">A quick look at this citizen's reports, FSIC requests, and recent safety activity.</p>
                </div>
                <div class="profile-card-body">
                    <div class="profile-social-grid" aria-label="Citizen contribution summary">
                        <div class="profile-social-stat" style="--profile-stat-bg:rgba(254,242,242,0.78);--profile-stat-glow:rgba(220,38,38,0.12);--profile-stat-icon-bg:#fef2f2;--profile-stat-icon-color:#dc2626;">
                            <span class="profile-social-icon"><i class="fa-solid fa-fire-flame-curved"></i></span>
                            <p class="profile-social-value">{{ $profileFireReports->count() }}</p>
                            <p class="profile-social-label">Fire reports</p>
                        </div>
                        <div class="profile-social-stat" style="--profile-stat-bg:rgba(255,251,235,0.82);--profile-stat-glow:rgba(245,158,11,0.14);--profile-stat-icon-bg:#fffbeb;--profile-stat-icon-color:#d97706;">
                            <span class="profile-social-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                            <p class="profile-social-value">{{ $profileRiskReports->count() }}</p>
                            <p class="profile-social-label">Risk / hazard</p>
                        </div>
                        <div class="profile-social-stat" style="--profile-stat-bg:rgba(239,246,255,0.82);--profile-stat-glow:rgba(37,99,235,0.1);--profile-stat-icon-bg:#eff6ff;--profile-stat-icon-color:#2563eb;">
                            <span class="profile-social-icon"><i class="fa-solid fa-file-shield"></i></span>
                            <p class="profile-social-value">{{ $profileFsicRequests->count() }}</p>
                            <p class="profile-social-label">FSIC requests</p>
                        </div>
                        <div class="profile-social-stat" style="--profile-stat-bg:rgba(236,253,245,0.82);--profile-stat-glow:rgba(5,150,105,0.12);--profile-stat-icon-bg:#ecfdf5;--profile-stat-icon-color:#059669;">
                            <span class="profile-social-icon"><i class="fa-solid fa-bell"></i></span>
                            <p class="profile-social-value">{{ $activeAlerts ?? 0 }}</p>
                            <p class="profile-social-label">Nearby alerts</p>
                        </div>
                    </div>

                    <div class="profile-feed-card">
                        <div class="profile-feed-head">
                            <div>
                                <h3 class="profile-feed-title">Citizen Activity</h3>
                                <p class="profile-feed-subtitle">Reports and requests connected to this account.</p>
                            </div>
                            <span class="profile-badge profile-badge--role">{{ $profileReports->count() + $profileFsicRequests->count() }} total</span>
                        </div>

                        <div class="profile-info-grid">
                            <div class="profile-info-item">
                                <p class="profile-label">Pending FSIC</p>
                                <p class="profile-value">{{ $profilePendingFsic }} request{{ $profilePendingFsic === 1 ? '' : 's' }}</p>
                            </div>
                            <div class="profile-info-item">
                                <p class="profile-label">Approved FSIC</p>
                                <p class="profile-value">{{ $profileApprovedFsic }} request{{ $profileApprovedFsic === 1 ? '' : 's' }}</p>
                            </div>
                        </div>

                        <div class="profile-activity-list mt-3">
                            @forelse ($profileRecentActivity as $activity)
                                <div class="profile-activity-item">
                                    <span class="profile-activity-icon" style="{{ $activity['kind'] === 'fire' ? '--profile-activity-bg:#fef2f2;--profile-activity-color:#dc2626;' : '--profile-activity-bg:#fffbeb;--profile-activity-color:#d97706;' }}">
                                        <i class="fa-solid {{ $activity['kind'] === 'fire' ? 'fa-fire-flame-curved' : 'fa-triangle-exclamation' }}"></i>
                                    </span>
                                    <div>
                                        <p class="profile-activity-title">{{ $activity['title'] }}</p>
                                        <p class="profile-activity-meta">{{ $activity['meta'] }}</p>
                                        @if($activity['time'])
                                            <p class="profile-activity-meta">{{ $activity['time'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="profile-activity-item">
                                    <span class="profile-activity-icon" style="--profile-activity-bg:#f8fafc;--profile-activity-color:#64748b;">
                                        <i class="fa-regular fa-clipboard"></i>
                                    </span>
                                    <div>
                                        <p class="profile-activity-title">No citizen reports yet</p>
                                        <p class="profile-activity-meta">Fire reports, risk/hazard reports, and requests will appear here once submitted.</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

            <main class="profile-shell">
                <div class="profile-modal-bar">
                    <div>
                        <p class="profile-modal-kicker">Profile controls</p>
                        <h2 id="profileModalTitle" class="profile-modal-title">Edit Information</h2>
                    </div>
                    <button type="button" class="profile-modal-close" data-profile-modal-close aria-label="Close profile modal">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form id="citizenProfileForm" method="POST" action="{{ route('citizen.profile.update') }}">
                    @csrf
                    @method('PUT')
                    <input id="citizen_email" name="email" type="hidden" value="{{ old('email', $profileUser->email) }}">

                    <section class="profile-card">
                        <div class="profile-card-head">
                            <span class="profile-eyebrow"><i class="fa-solid fa-id-card"></i> Profile details</span>
                            <h2 class="profile-card-title">Personal Information</h2>
                            <p class="profile-card-copy">Your citizen identity details are shown first. Use edit mode when updates are needed.</p>
                        </div>
                        <div class="profile-card-body">
                            <div class="profile-readonly profile-info-grid">
                                <div class="profile-info-item">
                                    <p class="profile-label">First Name</p>
                                    <p id="profileDisplayFirstName" class="profile-value">{{ $profileUser->first_name ?: 'Not provided' }}</p>
                                </div>
                                <div class="profile-info-item">
                                    <p class="profile-label">Middle Name</p>
                                    <p id="profileDisplayMiddleName" class="profile-value">{{ $profileUser->middle_name ?: 'Not provided' }}</p>
                                </div>
                                <div class="profile-info-item">
                                    <p class="profile-label">Last Name</p>
                                    <p id="profileDisplayLastName" class="profile-value">{{ $profileUser->last_name ?: 'Not provided' }}</p>
                                </div>
                                <div class="profile-info-item">
                                    <p class="profile-label">Phone Number</p>
                                    <p id="profileDisplayContactNumber" class="profile-value">{{ $profileUser->contact_number ?: 'Not provided' }}</p>
                                </div>
                                <div class="profile-info-item profile-field--wide">
                                    <p class="profile-label">Email Address</p>
                                    <p id="profileDisplayEmail" class="profile-value">{{ $profileUser->email }}</p>
                                </div>
                            </div>

                            <div class="profile-edit-form">
                                <div class="profile-form-grid">
                                    <div>
                                        <label for="citizen_first_name" class="profile-form-label">First Name</label>
                                        <input id="citizen_first_name" name="first_name" type="text" required value="{{ old('first_name', $profileUser->first_name) }}" class="profile-input">
                                    </div>
                                    <div>
                                        <label for="citizen_middle_name" class="profile-form-label">Middle Name</label>
                                        <input id="citizen_middle_name" name="middle_name" type="text" value="{{ old('middle_name', $profileUser->middle_name) }}" class="profile-input">
                                    </div>
                                    <div>
                                        <label for="citizen_last_name" class="profile-form-label">Last Name</label>
                                        <input id="citizen_last_name" name="last_name" type="text" required value="{{ old('last_name', $profileUser->last_name) }}" class="profile-input">
                                    </div>
                                    <div>
                                        <label for="citizen_contact_number" class="profile-form-label">Phone Number</label>
                                        <input id="citizen_contact_number" name="contact_number" type="text" value="{{ old('contact_number', $profileUser->contact_number) }}" class="profile-input">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="profile-card mt-4">
                        <div class="profile-card-head">
                            <span class="profile-eyebrow"><i class="fa-solid fa-location-dot"></i> Address</span>
                            <h2 class="profile-card-title">Address Information</h2>
                            <p class="profile-card-copy">Used for nearby alerts, responder context, and citizen report location support.</p>
                        </div>
                        <div class="profile-card-body">
                            <div class="profile-readonly profile-info-grid">
                                <div class="profile-info-item">
                                    <p class="profile-label">City</p>
                                    <p id="profileDisplayCity" class="profile-value">{{ $profileCity?->city_name ?? 'No city yet' }}</p>
                                </div>
                                <div class="profile-info-item">
                                    <p class="profile-label">Barangay</p>
                                    <p id="profileDisplayBarangay" class="profile-value">{{ $profileBarangay?->barangay_name ?? 'No barangay yet' }}</p>
                                </div>
                                <div class="profile-info-item">
                                    <p class="profile-label">Street Address</p>
                                    <p id="profileDisplayStreet" class="profile-value">{{ $profileStreet ?: 'Not provided' }}</p>
                                </div>
                                <div class="profile-info-item">
                                    <p class="profile-label">Full Address</p>
                                    <p id="profileDisplayFullAddress" class="profile-value">{{ $profileFullAddress }}</p>
                                </div>
                            </div>

                            <div class="profile-edit-form">
                                <div class="profile-form-grid">
                                    <div>
                                        <label for="citizen_city_id" class="profile-form-label">City</label>
                                        <select id="citizen_city_id" name="city_id" required class="profile-input">
                                            <option value="">Select City</option>
                                            @foreach ($cities as $cityOption)
                                                <option value="{{ $cityOption->city_id }}" @selected((string) old('city_id', $profileCity?->city_id) === (string) $cityOption->city_id)>
                                                    {{ $cityOption->city_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="citizen_barangay_id" class="profile-form-label">Barangay</label>
                                        <select id="citizen_barangay_id" name="barangay_id" required class="profile-input">
                                            <option value="">Select Barangay</option>
                                            @foreach ($barangayOptions as $barangayOption)
                                                <option value="{{ $barangayOption->barangay_id }}" @selected((string) old('barangay_id', $profileBarangay?->barangay_id) === (string) $barangayOption->barangay_id)>
                                                    {{ $barangayOption->barangay_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="profile-field--wide">
                                        <label for="citizen_street" class="profile-form-label">Street Address</label>
                                        <input id="citizen_street" name="street" type="text" required value="{{ old('street', $profileStreet) }}" class="profile-input" placeholder="Enter street address">
                                    </div>
                                </div>
                                <div id="profileInfoMessage" class="profile-message"></div>
                                <div class="profile-form-actions">
                                    <button id="citizenProfileCancelBtn" type="button" class="profile-secondary-btn">Cancel</button>
                                    <button id="citizenProfileSaveBtn" type="submit" class="profile-primary-btn">Save Changes</button>
                                </div>
                            </div>
                        </div>
                    </section>
                </form>

                <section class="profile-card">
                    <div class="profile-card-head">
                        <span class="profile-eyebrow"><i class="fa-solid fa-lock"></i> Security</span>
                        <h2 class="profile-card-title">Account Settings</h2>
                        <p class="profile-card-copy">Manage sensitive account details separately from your profile information.</p>
                    </div>
                    <div class="profile-card-body">
                        <div class="profile-settings-list">
                            <div class="profile-setting-row">
                                <div>
                                    <p class="profile-setting-title">Change Email Address</p>
                                    <p class="profile-setting-copy">Update your sign-in email after confirming your current password.</p>
                                </div>
                                <button type="button" class="profile-security-btn" data-account-form-toggle="email">Change Email</button>
                            </div>
                            <form id="citizenEmailForm" class="profile-account-form" action="{{ route('citizen.profile.email.update') }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="profile-form-grid">
                                    <div>
                                        <label for="profile_new_email" class="profile-form-label">New Email Address</label>
                                        <input id="profile_new_email" name="email" type="email" class="profile-input" placeholder="name@example.com">
                                    </div>
                                    <div>
                                        <label for="profile_email_password" class="profile-form-label">Current Password</label>
                                        <div class="profile-password-wrap">
                                            <input id="profile_email_password" name="current_password" type="password" class="profile-input">
                                            <button type="button" class="profile-password-toggle" data-password-toggle="profile_email_password">Show</button>
                                        </div>
                                    </div>
                                </div>
                                <div id="profileEmailMessage" class="profile-message"></div>
                                <div class="profile-form-actions">
                                    <button type="button" class="profile-secondary-btn" data-account-cancel="email">Cancel</button>
                                    <button type="submit" class="profile-primary-btn">Save Email</button>
                                </div>
                            </form>

                            <div class="profile-setting-row">
                                <div>
                                    <p class="profile-setting-title">Change Password</p>
                                    <p class="profile-setting-copy">Use at least 8 characters and confirm the new password.</p>
                                </div>
                                <button type="button" class="profile-security-btn" data-account-form-toggle="password">Change Password</button>
                            </div>
                            <form id="citizenPasswordForm" class="profile-account-form" action="{{ route('citizen.profile.password.update') }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="profile-form-grid">
                                    <div>
                                        <label for="profile_current_password" class="profile-form-label">Current Password</label>
                                        <div class="profile-password-wrap">
                                            <input id="profile_current_password" name="current_password" type="password" class="profile-input">
                                            <button type="button" class="profile-password-toggle" data-password-toggle="profile_current_password">Show</button>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="profile_new_password" class="profile-form-label">New Password</label>
                                        <div class="profile-password-wrap">
                                            <input id="profile_new_password" name="password" type="password" class="profile-input">
                                            <button type="button" class="profile-password-toggle" data-password-toggle="profile_new_password">Show</button>
                                        </div>
                                    </div>
                                    <div class="profile-field--wide">
                                        <label for="profile_confirm_password" class="profile-form-label">Confirm New Password</label>
                                        <div class="profile-password-wrap">
                                            <input id="profile_confirm_password" name="password_confirmation" type="password" class="profile-input">
                                            <button type="button" class="profile-password-toggle" data-password-toggle="profile_confirm_password">Show</button>
                                        </div>
                                    </div>
                                </div>
                                <div id="profilePasswordMessage" class="profile-message"></div>
                                <div class="profile-form-actions">
                                    <button type="button" class="profile-secondary-btn" data-account-cancel="password">Cancel</button>
                                    <button type="submit" class="profile-primary-btn">Save Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>
            </main>
        </div>

        <div class="profile-mobile-bottom-space" aria-hidden="true"></div>
    </div>
</div>

<div id="citizenProfileLoadingOverlay" class="fixed inset-0 bg-slate-950/35 backdrop-blur-sm hidden items-center justify-center z-[120]">
    <div class="bg-white rounded-2xl shadow-xl px-6 py-5 flex items-center gap-3 text-slate-700">
        <span class="inline-fa-icon icon-spin text-orange-600" style="width: 1.25rem; height: 1.25rem;">
            <svg viewBox="0 0 24 24"><path d="M12 4a8 8 0 1 0 8 8" fill="none"></path></svg>
        </span>
        <div>
            <p class="font-semibold">Saving changes</p>
            <p class="text-sm text-slate-500">Updating your account...</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const section = document.getElementById('section-profile');
    const profileForm = document.getElementById('citizenProfileForm');
    const citySelect = document.getElementById('citizen_city_id');
    const barangaySelect = document.getElementById('citizen_barangay_id');
    const selectedBarangayId = @json((string) old('barangay_id', $profileBarangay?->barangay_id));
    const loadingOverlay = document.getElementById('citizenProfileLoadingOverlay');
    const saveBtn = document.getElementById('citizenProfileSaveBtn');
    const cancelBtn = document.getElementById('citizenProfileCancelBtn');
    const toast = document.getElementById('toast');
    const infoMessage = document.getElementById('profileInfoMessage');
    const modalTitle = document.getElementById('profileModalTitle');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    if (!section || !profileForm || !citySelect || !barangaySelect) {
        return;
    }

    let originalProfile = {};

    function getFormSnapshot() {
        return Object.fromEntries(new FormData(profileForm).entries());
    }

    function captureOriginalProfile() {
        originalProfile = getFormSnapshot();
    }

    function restoreOriginalProfile() {
        Object.entries(originalProfile).forEach(([name, value]) => {
            const field = profileForm.querySelector(`[name="${name}"]`);
            if (field) field.value = value ?? '';
        });
        if (citySelect.value) {
            loadCitizenBarangays(citySelect.value, originalProfile.barangay_id || '');
        }
    }

    function hasProfileChanges() {
        const current = getFormSnapshot();
        return Object.keys(originalProfile).some((key) => String(originalProfile[key] ?? '') !== String(current[key] ?? ''));
    }

    function showProfileToast(message, isError = false) {
        if (!toast) return;

        toast.textContent = message;
        toast.style.background = isError ? 'linear-gradient(135deg, #b91c1c 0%, #dc2626 100%)' : '';
        toast.classList.add('show');

        window.clearTimeout(window.citizenProfileToastTimer);
        window.citizenProfileToastTimer = window.setTimeout(() => {
            toast.classList.remove('show');
            if (!isError) toast.style.background = '';
        }, 2800);
    }

    function setMessage(node, message = '', isError = false) {
        if (!node) return;
        node.textContent = message;
        node.classList.toggle('is-visible', Boolean(message));
        node.classList.toggle('is-error', Boolean(message && isError));
        node.classList.toggle('is-success', Boolean(message && !isError));
    }

    function clearProfileErrors(scope = profileForm) {
        scope.querySelectorAll('.border-red-400').forEach((field) => {
            field.classList.remove('border-red-400', 'ring-2', 'ring-red-100');
        });
        scope.querySelectorAll('.citizen-profile-error').forEach((node) => node.remove());
    }

    function applyFieldErrors(errors, scope = profileForm) {
        Object.entries(errors || {}).forEach(([field, messages]) => {
            const input = scope.querySelector(`[name="${field}"]`);
            if (!input) return;

            input.classList.add('border-red-400', 'ring-2', 'ring-red-100');
            const error = document.createElement('p');
            error.className = 'citizen-profile-error mt-1 text-sm text-red-600';
            error.textContent = Array.isArray(messages) ? messages[0] : messages;
            input.closest('.profile-password-wrap')?.insertAdjacentElement('afterend', error) || input.insertAdjacentElement('afterend', error);
        });
    }

    function setText(id, value) {
        const node = document.getElementById(id);
        if (node) node.textContent = value || 'Not provided';
    }

    function updateCitizenProfileUI(user) {
        const fullName = user.full_name || [user.first_name, user.last_name].filter(Boolean).join(' ').trim();
        const currentAddress = `${user.street ? `${user.street}, ` : ''}${user.barangay || 'No barangay yet'}, ${user.city || 'No city yet'}`;

        const map = {
            citizenProfileCardInitials: user.initials,
            citizenProfileCardName: fullName,
            citizenProfileCardEmail: user.email,
            citizenProfileRole: user.role,
            citizenProfileCurrentAddress: currentAddress,
            profileDisplayFirstName: user.first_name,
            profileDisplayMiddleName: user.middle_name || 'Not provided',
            profileDisplayLastName: user.last_name,
            profileDisplayContactNumber: user.contact_number || 'Not provided',
            profileDisplayEmail: user.email,
            profileDisplayCity: user.city || 'No city yet',
            profileDisplayBarangay: user.barangay || 'No barangay yet',
            profileDisplayStreet: user.street || 'Not provided',
            profileDisplayFullAddress: currentAddress,
        };

        Object.entries(map).forEach(([id, value]) => setText(id, value));

        document.getElementById('citizen_email').value = user.email || document.getElementById('citizen_email').value;

        document.querySelectorAll('#userDropdownBtn .w-8.h-8, .px-4.py-4.border-b .w-12.h-12').forEach((node) => {
            node.textContent = user.initials;
        });

        const topbarName = document.querySelector('#userDropdownBtn span.hidden.md\\:inline');
        if (topbarName) topbarName.textContent = fullName;

        const sidebarName = document.querySelector('.px-4.py-4.border-b p.font-semibold.text-gray-800');
        if (sidebarName) sidebarName.textContent = fullName;

        const sidebarEmail = document.querySelector('.px-4.py-4.border-b p.text-xs.text-gray-500');
        if (sidebarEmail) {
            sidebarEmail.innerHTML = `<span class="inline-fa-icon" style="margin-right:0.25rem;"><svg viewBox="0 0 24 24"><rect x="4" y="6" width="16" height="12" rx="2" fill="none"></rect><path d="M5 8l7 5 7-5"></path></svg></span>${user.email}`;
        }
    }

    async function loadCitizenBarangays(cityId, preferredBarangayId = '') {
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
        if (!cityId) return;

        try {
            const response = await fetch(`/api/barangays/${cityId}`);
            const barangays = await response.json();

            barangays.forEach((barangay) => {
                const option = document.createElement('option');
                option.value = barangay.barangay_id;
                option.textContent = barangay.barangay_name;
                if (preferredBarangayId && String(preferredBarangayId) === String(barangay.barangay_id)) option.selected = true;
                barangaySelect.appendChild(option);
            });
        } catch (error) {
            console.error('Error loading citizen barangays:', error);
        }
    }

    function openEditMode() {
        clearProfileErrors(profileForm);
        setMessage(infoMessage);
        captureOriginalProfile();
        section.classList.remove('is-account-settings');
        section.classList.add('is-editing');
        if (modalTitle) modalTitle.textContent = 'Edit Information';
    }

    function closeEditMode() {
        clearProfileErrors(profileForm);
        setMessage(infoMessage);
        restoreOriginalProfile();
        section.classList.remove('is-editing');
    }

    function openSettingsMode() {
        section.classList.remove('is-editing');
        section.classList.add('is-account-settings');
        if (modalTitle) modalTitle.textContent = 'Account Settings';
    }

    function closeProfileModal() {
        clearProfileErrors(profileForm);
        setMessage(infoMessage);
        restoreOriginalProfile();
        section.classList.remove('is-editing', 'is-account-settings');

        document.querySelectorAll('#section-profile .profile-account-form').forEach((form) => {
            form.reset();
            form.classList.remove('is-open');
            clearProfileErrors(form);
            setMessage(form.querySelector('.profile-message'));
        });
    }

    document.querySelectorAll('[data-profile-edit-open]').forEach((button) => button.addEventListener('click', openEditMode));
    document.querySelectorAll('[data-profile-settings-open]').forEach((button) => button.addEventListener('click', openSettingsMode));
    document.querySelectorAll('[data-profile-modal-close]').forEach((button) => button.addEventListener('click', closeProfileModal));
    cancelBtn?.addEventListener('click', closeEditMode);
    section.querySelector('.profile-layout > main.profile-shell')?.addEventListener('click', (event) => {
        if (event.target === event.currentTarget) closeProfileModal();
    });

    citySelect.addEventListener('change', function () {
        loadCitizenBarangays(this.value);
    });

    if (citySelect.value) {
        loadCitizenBarangays(citySelect.value, selectedBarangayId).then(captureOriginalProfile);
    } else {
        captureOriginalProfile();
    }

    profileForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        clearProfileErrors(profileForm);
        setMessage(infoMessage);

        if (!hasProfileChanges()) {
            setMessage(infoMessage, 'No changes were made.', true);
            return;
        }

        loadingOverlay?.classList.remove('hidden');
        loadingOverlay?.classList.add('flex');
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        try {
            const response = await fetch(profileForm.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: new FormData(profileForm),
            });

            const payload = await response.json();

            if (!response.ok) {
                if (response.status === 422 && payload.errors) {
                    applyFieldErrors(payload.errors, profileForm);
                    setMessage(infoMessage, 'Please check the highlighted fields.', true);
                    return;
                }
                throw new Error(payload.message || 'Unable to update profile.');
            }

            updateCitizenProfileUI(payload.user || {});
            captureOriginalProfile();
            section.classList.remove('is-editing');
            showProfileToast(payload.message || 'Profile updated successfully.');
        } catch (error) {
            console.error('Citizen profile update failed:', error);
            setMessage(infoMessage, error.message || 'Failed to update profile.', true);
        } finally {
            loadingOverlay?.classList.add('hidden');
            loadingOverlay?.classList.remove('flex');
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Changes';
        }
    });

    document.querySelectorAll('[data-account-form-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = button.dataset.accountFormToggle;
            document.getElementById(`citizen${target === 'email' ? 'Email' : 'Password'}Form`)?.classList.toggle('is-open');
        });
    });

    document.querySelectorAll('[data-account-cancel]').forEach((button) => {
        button.addEventListener('click', () => {
            const form = button.closest('form');
            form?.reset();
            form?.classList.remove('is-open');
            clearProfileErrors(form);
            setMessage(form?.querySelector('.profile-message'));
        });
    });

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            button.textContent = input.type === 'password' ? 'Show' : 'Hide';
        });
    });

    async function submitAccountForm(form, messageNode, successCallback) {
        clearProfileErrors(form);
        setMessage(messageNode);

        const submitButton = form.querySelector('[type="submit"]');
        const originalLabel = submitButton?.textContent || 'Save';
        submitButton.disabled = true;
        submitButton.textContent = 'Saving...';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: new FormData(form),
            });
            const payload = await response.json();

            if (!response.ok || payload.success === false) {
                if (response.status === 422 && payload.errors) applyFieldErrors(payload.errors, form);
                throw new Error(payload.message || 'Unable to save account setting.');
            }

            successCallback?.(payload);
            form.reset();
            form.classList.remove('is-open');
            setMessage(messageNode, payload.message || 'Account setting updated.');
            showProfileToast(payload.message || 'Account setting updated.');
        } catch (error) {
            console.error('Citizen account setting update failed:', error);
            setMessage(messageNode, error.message || 'Unable to save account setting.', true);
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = originalLabel;
        }
    }

    document.getElementById('citizenEmailForm')?.addEventListener('submit', function (event) {
        event.preventDefault();
        submitAccountForm(this, document.getElementById('profileEmailMessage'), (payload) => {
            const email = payload.user?.email;
            if (!email) return;
            updateCitizenProfileUI({
                first_name: document.getElementById('citizen_first_name').value,
                middle_name: document.getElementById('citizen_middle_name').value,
                last_name: document.getElementById('citizen_last_name').value,
                contact_number: document.getElementById('citizen_contact_number').value,
                full_name: document.getElementById('citizenProfileCardName').textContent,
                initials: document.getElementById('citizenProfileCardInitials').textContent,
                role: document.getElementById('citizenProfileRole').textContent,
                email,
                street: document.getElementById('profileDisplayStreet').textContent === 'Not provided' ? '' : document.getElementById('profileDisplayStreet').textContent,
                barangay: document.getElementById('profileDisplayBarangay').textContent,
                city: document.getElementById('profileDisplayCity').textContent,
            });
            captureOriginalProfile();
        });
    });

    document.getElementById('citizenPasswordForm')?.addEventListener('submit', function (event) {
        event.preventDefault();
        const password = this.querySelector('[name="password"]').value;
        const confirmation = this.querySelector('[name="password_confirmation"]').value;
        const messageNode = document.getElementById('profilePasswordMessage');

        if (password !== confirmation) {
            setMessage(messageNode, 'New password and confirmation do not match.', true);
            return;
        }

        submitAccountForm(this, messageNode);
    });
});
</script>
