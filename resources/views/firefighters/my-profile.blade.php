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
        $fallback = fn ($value, $text) => filled($value) ? $value : $text;
        $titleCase = fn ($value) => ucwords(str_replace(['-', '_'], ' ', (string) $value));
        $formatPhone = function ($value) {
            $digits = preg_replace('/\D+/', '', (string) $value);
            if ($digits === '') {
                return 'No contact number added';
            }
            if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
                $digits = '0' . $digits;
            }
            if (strlen($digits) === 11) {
                return substr($digits, 0, 4) . ' ' . substr($digits, 4, 3) . ' ' . substr($digits, 7);
            }
            return $value;
        };
        $formatServiceDuration = function ($date) {
            if (!$date) {
                return 'Not recorded';
            }

            $start = $date instanceof \Carbon\CarbonInterface ? $date : \Carbon\Carbon::parse($date);
            $diff = $start->diff(now());

            if ($diff->y > 0) {
                return $diff->y . ' ' . \Illuminate\Support\Str::plural('year', $diff->y)
                    . ($diff->m ? ', ' . $diff->m . ' ' . \Illuminate\Support\Str::plural('month', $diff->m) : '');
            }

            if ($diff->m > 0) {
                return $diff->m . ' ' . \Illuminate\Support\Str::plural('month', $diff->m);
            }

            if ($diff->d > 0) {
                return $diff->d . ' ' . \Illuminate\Support\Str::plural('day', $diff->d);
            }

            return 'Less than 1 day';
        };

        $fullName = trim(collect([$user->first_name, $user->middle_name, $user->last_name])->filter()->join(' '));
        $initials = strtoupper(substr($user->first_name ?? 'F', 0, 1) . substr($user->last_name ?? 'R', 0, 1));
        $profileImageUrl = \App\Support\MediaPath::url($user->image);
        $roleName = $user->role->role ?? 'Firefighter';
        $rank = $stationOfficer->rank ?? 'No rank assigned';
        $status = $stationOfficer->status ?? 'available';
        $stationName = $stationOfficer?->station?->station_name ?? 'No station assigned yet';
        $accountCode = $user->account_code ?? 'No account code';
        $contactNumber = $formatPhone($user->contact_number);
        $rawContactNumber = preg_replace('/\D+/', '', (string) $user->contact_number);
        $email = $user->email ? strtolower($user->email) : 'No email address added';
        $street = $user->address->street ?? null;
        $barangay = $user->address?->barangay?->barangay_name ?? null;
        $city = $user->address?->barangay?->city?->city_name ?? null;
        $fullAddress = collect([$street, $barangay, $city])->filter()->join(', ');
        $serviceStart = $stationOfficer?->created_at ?? $user->created_at;
        $serviceDuration = $formatServiceDuration($serviceStart);
        $memberSince = optional($serviceStart)->format('F j, Y') ?: 'Not recorded';
        $statusLabel = $titleCase($status);
        $statusClass = match (strtolower((string) $status)) {
            'available', 'on duty', 'on-duty' => 'is-available',
            'assigned', 'responding', 'busy' => 'is-assigned',
            'off duty', 'off-duty', 'inactive' => 'is-off',
            default => 'is-neutral',
        };
        $stationAddress = collect([
            $stationOfficer?->station?->address?->street,
            $stationOfficer?->station?->address?->barangay?->barangay_name,
            $stationOfficer?->station?->address?->barangay?->city?->city_name,
        ])->filter()->join(', ');
        $stationContact = $formatPhone($stationOfficer?->station?->contact_number);
        $profileErrors = $errors->getBag('profileUpdate');
        $emailErrors = $errors->getBag('emailUpdate');
        $passwordErrors = $errors->getBag('passwordUpdate');
        $modalToReopen = $profileErrors->any()
            ? 'profileEditModal'
            : ($emailErrors->any() ? 'profileEmailModal' : ($passwordErrors->any() ? 'profilePasswordModal' : null));
        $modalErrorMessage = $profileErrors->first()
            ?: ($emailErrors->first() ?: $passwordErrors->first());
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

        .profile-avatar-wrap {
            position: relative;
            width: fit-content;
        }

        .profile-avatar-image {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            cursor: zoom-in;
        }

        .profile-avatar-fallback {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-avatar-camera {
            position: absolute;
            right: 4px;
            bottom: 2px;
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 999px;
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 14px 28px rgba(198, 61, 38, 0.24);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
            z-index: 5;
        }

        .profile-avatar-camera:hover,
        .profile-avatar-camera:focus-visible {
            transform: translateY(-1px) scale(1.04);
            box-shadow: 0 18px 32px rgba(198, 61, 38, 0.28);
            outline: none;
        }

        .profile-avatar-camera i {
            font-size: 0.92rem;
        }

        .profile-photo-input {
            display: none;
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

        .profile-shell {
            gap: 18px;
            max-width: 1360px;
            margin: 0 auto;
        }

        .profile-summary {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 20px;
            align-items: center;
            padding: 22px;
            border-radius: 24px;
            background:
                radial-gradient(circle at top right, rgba(209, 138, 22, 0.16), transparent 34%),
                linear-gradient(135deg, #ffffff 0%, #fffaf5 100%);
            border: 1px solid rgba(226, 232, 240, 0.92);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.05);
        }

        .profile-summary-main {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .profile-avatar {
            width: 76px;
            height: 76px;
            min-width: 76px;
            margin: 0;
            border-width: 4px;
            font-size: 1.55rem;
        }

        .profile-summary-copy {
            min-width: 0;
        }

        .profile-name {
            margin: 0;
            font-size: clamp(1.35rem, 2vw, 1.9rem);
            line-height: 1.08;
        }

        .profile-meta-line {
            margin-top: 6px;
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .profile-summary-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .profile-status-badge,
        .profile-contact-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-width: 0;
            padding: 7px 10px;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 800;
            line-height: 1.2;
            text-decoration: none;
            white-space: normal;
            word-break: break-word;
        }

        .profile-status-badge {
            border: 1px solid rgba(148, 163, 184, 0.22);
            background: #f8fafc;
            color: #334155;
        }

        .profile-status-badge.is-available {
            background: #dcfce7;
            color: #166534;
            border-color: #bbf7d0;
        }

        .profile-status-badge.is-assigned {
            background: #fff7ed;
            color: #9a3412;
            border-color: #fed7aa;
        }

        .profile-status-badge.is-off {
            background: #f1f5f9;
            color: #475569;
            border-color: #e2e8f0;
        }

        .profile-contact-chip {
            max-width: 100%;
            border: 1px solid rgba(226, 232, 240, 0.92);
            background: rgba(255, 255, 255, 0.82);
            color: #334155;
        }

        .profile-summary-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
        }

        .profile-summary-actions .btn,
        .account-action {
            min-height: 44px;
            border-radius: 14px;
            font-weight: 800;
        }

        .profile-grid {
            grid-template-columns: minmax(0, 1.28fr) minmax(300px, 0.72fr);
            gap: 18px;
        }

        .profile-card,
        .profile-summary-card {
            border-radius: 20px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .profile-body,
        .stack-card {
            padding: 20px;
        }

        .info-section + .info-section {
            margin-top: 20px;
        }

        .info-section h3,
        .stack-title {
            font-size: 1rem;
            line-height: 1.25;
        }

        .info-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .info-item {
            min-width: 0;
            background: #ffffff;
            border-color: rgba(226, 232, 240, 0.95);
            padding: 12px;
        }

        .info-label,
        .mini-card-label {
            font-size: 0.68rem;
            color: #94a3b8;
        }

        .info-value,
        .activity-meta {
            overflow-wrap: anywhere;
            word-break: normal;
        }

        .info-value a,
        .activity-meta a {
            color: #0f172a;
            text-decoration: none;
        }

        .info-value a:hover,
        .activity-meta a:hover {
            color: #c63d26;
        }

        .stack-column {
            gap: 18px;
        }

        .mini-grid {
            gap: 10px;
        }

        .mini-card {
            padding: 12px;
            background: #fffdf9;
        }

        .mini-card-value {
            margin-top: 4px;
            font-size: 0.98rem;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .activity-item {
            align-items: center;
            padding: 12px;
        }

        .activity-icon {
            width: 34px;
            height: 34px;
            border-radius: 12px;
        }

        .account-action-list {
            display: grid;
            gap: 10px;
        }

        .account-action {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            border: 1px solid rgba(226, 232, 240, 0.95);
            background: #ffffff;
            color: #0f172a;
            cursor: pointer;
            text-align: left;
        }

        .account-action span {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .record-info {
            border-style: dashed;
            background: #f8fafc;
        }

        .profile-toast {
            position: fixed;
            top: 5.5rem;
            right: 1.25rem;
            z-index: 7000;
            display: none;
            max-width: min(360px, calc(100vw - 24px));
            padding: 12px 14px;
            border-radius: 16px;
            background: #0f172a;
            color: #ffffff;
            font-size: 0.86rem;
            font-weight: 800;
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.18);
        }

        .profile-toast.show {
            display: block;
            animation: profileToastIn 0.24s ease;
        }

        @keyframes profileToastIn {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .profile-modal {
            z-index: 9999;
            width: 100%;
            height: 100dvh;
            overflow: hidden;
        }

        .profile-modal-card {
            width: min(660px, calc(100vw - 32px));
            max-height: min(85vh, 760px);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .profile-modal-card--compact {
            width: min(520px, calc(100vw - 32px));
        }

        .profile-modal-header {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        .profile-sheet-handle {
            display: none;
        }

        .profile-modal-close {
            width: 44px;
            height: 44px;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            color: #9a3412;
            cursor: pointer;
        }

        .profile-photo-viewer {
            position: fixed;
            inset: 0;
            z-index: 11010;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(15, 23, 42, 0.72);
            backdrop-filter: blur(10px);
        }

        .profile-photo-viewer.active {
            display: flex;
        }

        .profile-photo-viewer img {
            max-width: min(92vw, 720px);
            max-height: 82vh;
            border-radius: 28px;
            object-fit: contain;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.35);
            background: #fff;
        }

        .profile-photo-viewer-close {
            position: absolute;
            top: 28px;
            right: 28px;
            width: 48px;
            height: 48px;
            border: 0;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.92);
            color: #c63d26;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.18);
        }

        #firefighterProfileCropModal {
            position: fixed;
            inset: 0;
            z-index: 11000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(15, 23, 42, 0.52);
            backdrop-filter: blur(10px);
        }

        #firefighterProfileCropModal.active {
            display: flex;
        }

        #firefighterProfileCropModal .crop-shell {
            width: min(100%, 680px);
            max-height: min(92vh, 880px);
            overflow: hidden;
            border-radius: 34px;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.92);
            box-shadow: 0 28px 60px rgba(15, 23, 42, 0.2);
            display: grid;
            grid-template-rows: auto 1fr;
        }

        #firefighterProfileCropModal .crop-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            padding: 22px 22px 18px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.9);
        }

        #firefighterProfileCropModal .crop-head h3 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
            color: #172033;
        }

        #firefighterProfileCropModal .crop-head p {
            margin: 8px 0 0;
            color: #475569;
            font-size: 0.92rem;
            line-height: 1.6;
        }

        #firefighterProfileCropModal .crop-close {
            width: 48px;
            height: 48px;
            border: 0;
            border-radius: 999px;
            background: #fff7f3;
            color: #c63d26;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        #firefighterProfileCropModal .crop-body {
            display: grid;
            gap: 18px;
            padding: 22px;
            overflow-y: auto;
        }

        #firefighterProfileCropViewport {
            position: relative;
            width: min(100%, 420px);
            aspect-ratio: 1 / 1;
            margin: 0 auto;
            border-radius: 28px;
            overflow: hidden;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.22);
            touch-action: none;
        }

        #firefighterProfileCropViewport::after {
            content: '';
            position: absolute;
            inset: 15%;
            border-radius: 999px;
            box-shadow: 0 0 0 9999px rgba(15, 23, 42, 0.34);
            border: 4px solid rgba(255, 255, 255, 0.96);
            pointer-events: none;
        }

        #firefighterProfileCropStage {
            position: absolute;
            inset: 0;
            cursor: grab;
            touch-action: none;
        }

        #firefighterProfileCropStage.is-dragging {
            cursor: grabbing;
        }

        #firefighterProfileCropImage {
            position: absolute;
            top: 0;
            left: 0;
            transform-origin: top left;
            user-select: none;
            -webkit-user-drag: none;
            max-width: none;
            pointer-events: none;
        }

        #firefighterProfileCropModal .crop-zoom-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            color: #172033;
            font-size: 0.9rem;
            font-weight: 700;
        }

        #firefighterProfileCropModal .crop-range {
            width: 100%;
            accent-color: #c63d26;
        }

        #firefighterProfileCropModal .crop-message {
            min-height: 1.2rem;
            margin: 0;
            color: #be123c;
            font-size: 0.84rem;
            font-weight: 600;
        }

        #firefighterProfileCropModal .crop-actions {
            display: flex;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 12px;
        }

        #firefighterProfileCropModal .crop-btn {
            min-width: 132px;
            min-height: 48px;
            border-radius: 999px;
            border: 0;
            padding: 0.88rem 1.2rem;
            font-size: 0.92rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            cursor: pointer;
        }

        #firefighterProfileCropModal .crop-btn--ghost {
            border: 1px solid rgba(198, 61, 38, 0.2);
            background: #fff;
            color: #a9311d;
        }

        #firefighterProfileCropModal .crop-btn--primary {
            color: #fff;
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            box-shadow: 0 16px 32px rgba(198, 61, 38, 0.2);
        }

        .profile-modal-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }

        .profile-modal-footer {
            flex: 0 0 auto;
        }

        .profile-form-hint {
            margin: 0;
            color: #64748b;
            font-size: 0.82rem;
            line-height: 1.45;
        }

        .profile-readonly-context {
            display: grid;
            gap: 5px;
            padding: 12px 14px;
            border: 1px solid #e7eaf0;
            border-radius: 14px;
            background: #fffaf5;
            color: #334155;
            min-width: 0;
        }

        .profile-readonly-context span:first-child {
            color: #94a3b8;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .profile-readonly-context span:last-child {
            font-size: 0.88rem;
            font-weight: 800;
            word-break: break-word;
        }

        .profile-field-error {
            color: #b91c1c;
            font-size: 0.76rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .profile-password-wrap {
            position: relative;
            min-width: 0;
        }

        .profile-password-wrap .profile-form-input {
            padding-right: 48px;
        }

        .profile-password-toggle {
            position: absolute;
            top: 50%;
            right: 6px;
            width: 38px;
            height: 38px;
            transform: translateY(-50%);
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: #64748b;
            cursor: pointer;
        }

        .profile-discard-confirm {
            position: fixed;
            inset: 0;
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background: rgba(15, 23, 42, 0.36);
        }

        .profile-discard-confirm.active {
            display: flex;
        }

        .profile-discard-card {
            width: min(360px, 100%);
            border-radius: 18px;
            border: 1px solid #fed7aa;
            background: #fff;
            padding: 18px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18);
        }

        .profile-discard-card h3 {
            margin: 0 0 6px;
            font-size: 1rem;
            color: #172033;
        }

        .profile-discard-card p {
            margin: 0;
            color: #64748b;
            font-size: 0.86rem;
            line-height: 1.45;
        }

        .profile-discard-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 16px;
        }

        body.profile-modal-open {
            overflow: hidden;
        }

        body.profile-modal-open .ff-mobile-dock,
        body.profile-modal-open .firefighter-bottom-nav {
            visibility: hidden;
            pointer-events: none;
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

        @media (max-width: 768px) {
            .profile-shell {
                gap: 12px;
                padding-bottom: calc(var(--ff-mobile-dock-h, 92px) + 28px + env(safe-area-inset-bottom, 0px));
            }

            .profile-summary,
            .profile-card,
            .stack-card {
                border-radius: 16px;
                box-shadow: none;
                margin-inline: 0;
            }

            .profile-summary,
            .profile-body,
            .stack-card {
                padding: 13px;
            }

            .profile-summary {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .profile-summary-main {
                align-items: flex-start;
                gap: 11px;
            }

            .profile-avatar {
                width: 54px;
                height: 54px;
                min-width: 54px;
                font-size: 1rem;
                border-width: 4px;
            }

            .profile-avatar-camera {
                width: 30px;
                height: 30px;
                right: -1px;
                bottom: -1px;
            }

            .profile-name {
                font-size: 1.28rem;
                line-height: 1.08;
            }

            .profile-meta-line {
                font-size: 0.78rem;
            }

            .profile-summary-tags {
                gap: 6px;
                margin-top: 9px;
            }

            .profile-status-badge,
            .profile-contact-chip {
                padding: 6px 9px;
                font-size: 0.7rem;
            }

            .profile-summary-actions {
                justify-content: stretch;
            }

            .profile-summary-actions .btn {
                width: 100%;
                justify-content: center;
            }

            .profile-grid,
            .info-grid,
            .mini-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .info-section + .info-section {
                margin-top: 16px;
            }

            .info-section h3,
            .stack-title {
                font-size: 0.94rem;
                margin-bottom: 10px;
            }

            .info-item,
            .mini-card,
            .activity-item {
                border-radius: 14px;
                padding: 11px;
            }

            .info-label,
            .mini-card-label {
                font-size: 0.66rem;
            }

            .info-value,
            .activity-text {
                font-size: 0.86rem;
            }

            .activity-meta,
            .mini-card-value {
                font-size: 0.8rem;
            }

            .profile-modal {
                align-items: flex-end;
                justify-content: center;
                padding: 0;
                background: rgba(15, 23, 42, 0.42);
            }

            .profile-modal-card {
                width: 100%;
                max-width: 100%;
                max-height: calc(100dvh - env(safe-area-inset-top, 0px));
                margin: 0;
                border-radius: 22px 22px 0 0;
                border-bottom: 0;
                box-shadow: 0 -16px 34px rgba(15, 23, 42, 0.16);
            }

            .profile-sheet-handle {
                display: block;
                width: 52px;
                height: 5px;
                border-radius: 999px;
                background: #cbd5e1;
                margin: 0 auto 10px;
            }

            .profile-modal-header {
                align-items: flex-start;
                padding: 10px 14px 12px;
            }

            .profile-modal-footer {
                display: grid;
                grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.2fr);
                gap: 10px;
                padding:
                    10px
                    max(12px, env(safe-area-inset-right, 0px))
                    calc(10px + env(safe-area-inset-bottom, 0px))
                    max(12px, env(safe-area-inset-left, 0px));
                background: #fff;
            }

            .profile-modal-footer .btn {
                width: 100%;
                min-width: 0;
                min-height: 44px;
                padding: 10px 12px;
            }

            .profile-modal-body {
                padding: 12px 14px 18px;
                gap: 12px;
            }

            .profile-form-grid {
                gap: 12px;
            }

            .profile-form-label {
                font-size: 0.72rem;
            }

            .profile-form-input {
                min-height: 44px;
                padding: 10px 12px;
                font-size: 16px;
                border-radius: 12px;
                box-sizing: border-box;
                max-width: 100%;
                min-width: 0;
            }

            .profile-form-hint,
            .profile-field-error {
                font-size: 0.74rem;
            }

            .profile-readonly-context {
                padding: 10px 12px;
                border-radius: 12px;
            }

            .profile-modal-close {
                flex: 0 0 44px;
            }

            #firefighterProfileCropModal {
                padding: 14px;
                align-items: flex-end;
            }

            #firefighterProfileCropModal .crop-shell {
                width: 100%;
                max-height: min(92vh, 860px);
                border-radius: 26px 26px 0 0;
            }

            #firefighterProfileCropViewport {
                width: min(100%, 360px);
            }

            #firefighterProfileCropModal .crop-actions {
                flex-direction: column-reverse;
            }

            #firefighterProfileCropModal .crop-btn {
                width: 100%;
            }

            .profile-photo-viewer {
                padding: 18px;
            }

            .profile-photo-viewer-close {
                top: 18px;
                right: 18px;
                width: 42px;
                height: 42px;
            }

            body.profile-modal-open .profile-toast {
                bottom: 12px;
            }

            .profile-toast {
                top: auto;
                left: 12px;
                right: 12px;
                bottom: calc(var(--ff-mobile-dock-h, 92px) + 12px + env(safe-area-inset-bottom, 0px));
                max-width: none;
                font-size: 0.78rem;
            }
        }

        @media (max-width: 319px) {
            .profile-modal-footer,
            .profile-discard-actions {
                grid-template-columns: minmax(0, 1fr);
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

        <input
            id="firefighterProfilePhotoInput"
            name="profile_image"
            type="file"
            class="profile-photo-input"
            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
        >

        <div class="profile-shell">
            <section class="profile-summary" aria-labelledby="profileSummaryTitle">
                <div class="profile-summary-main">
                    <div class="profile-avatar-wrap">
                        <div class="profile-avatar" data-firefighter-avatar-frame>
                            @if ($profileImageUrl)
                                <img
                                    id="firefighterProfileCardImage"
                                    class="profile-avatar-image"
                                    src="{{ $profileImageUrl }}"
                                    alt="{{ $fullName ?: 'Firefighter' }}"
                                    data-firefighter-avatar-image
                                    data-firefighter-profile-view-trigger
                                >
                            @endif
                            <div
                                id="firefighterProfileCardInitials"
                                class="profile-avatar-fallback"
                                @if ($profileImageUrl) style="display:none;" @endif
                                data-firefighter-avatar-fallback
                            >{{ $initials }}</div>
                        </div>
                        <button
                            id="firefighterProfilePhotoTrigger"
                            type="button"
                            class="profile-avatar-camera"
                            aria-label="Upload profile photo"
                            title="Upload profile photo"
                        >
                            <i class="fa-solid fa-camera"></i>
                        </button>
                    </div>
                    <div class="profile-summary-copy">
                        <h1 class="profile-name" id="profileSummaryTitle">{{ $fullName ?: 'Unnamed Firefighter' }}</h1>
                        <div class="profile-meta-line">{{ $rank }} &middot; {{ $roleName }}</div>
                        <div class="profile-summary-tags">
                            <span class="profile-status-badge {{ $statusClass }}">
                                <i class="fa-solid fa-circle" aria-hidden="true"></i>
                                {{ $statusLabel }}
                            </span>
                            <span class="profile-contact-chip">
                                <i class="fa-solid fa-building" aria-hidden="true"></i>
                                {{ $stationName }}
                            </span>
                            @if ($rawContactNumber)
                                <a class="profile-contact-chip" href="tel:{{ $rawContactNumber }}">
                                    <i class="fa-solid fa-phone" aria-hidden="true"></i>
                                    {{ $contactNumber }}
                                </a>
                            @else
                                <span class="profile-contact-chip">
                                    <i class="fa-solid fa-phone" aria-hidden="true"></i>
                                    {{ $contactNumber }}
                                </span>
                            @endif
                            @if ($user->email)
                                <a class="profile-contact-chip" href="mailto:{{ $email }}">
                                    <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                                    {{ $email }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="profile-summary-actions">
                    <button type="button" class="btn btn-primary" id="openProfileEditModal">
                        <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                        Edit Profile
                    </button>
                </div>
            </section>

            <div class="profile-grid">
                <section class="profile-card">
                    <div class="profile-body">
                        <div class="info-section">
                            <h3><i class="fa-solid fa-user"></i> Personal Information</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">First Name</div>
                                    <div class="info-value">{{ $fallback($user->first_name, 'No first name provided') }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Middle Name</div>
                                    <div class="info-value">{{ $fallback($user->middle_name, 'No middle name provided') }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Last Name</div>
                                    <div class="info-value">{{ $fallback($user->last_name, 'No last name provided') }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Email Address</div>
                                    <div class="info-value">
                                        @if ($user->email)
                                            <a href="mailto:{{ $email }}">{{ $email }}</a>
                                        @else
                                            {{ $email }}
                                        @endif
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Contact Number</div>
                                    <div class="info-value">
                                        @if ($rawContactNumber)
                                            <a href="tel:{{ $rawContactNumber }}">{{ $contactNumber }}</a>
                                        @else
                                            {{ $contactNumber }}
                                        @endif
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Home Address</div>
                                    <div class="info-value">{{ $fullAddress ?: 'No home address provided' }}</div>
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
                                    <div class="info-value">
                                        <span class="profile-status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Station Assignment</div>
                                    <div class="info-value">{{ $stationName }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Member Since</div>
                                    <div class="info-value">{{ $memberSince }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Years of Service</div>
                                    <div class="info-value">{{ $serviceDuration }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="stack-column">
                    <div class="profile-card stack-card">
                        <h3 class="stack-title"><i class="fa-solid fa-chart-line"></i> Assignment Snapshot</h3>
                        <div class="badge-row">
                            <div class="profile-pill {{ $statusClass }}">
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
                                <div class="mini-card-label">Member Since</div>
                                <div class="mini-card-value">{{ $memberSince }}</div>
                            </div>
                            <div class="mini-card">
                                <div class="mini-card-label">Service Time</div>
                                <div class="mini-card-value">{{ $serviceDuration }}</div>
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
                                    <div class="activity-meta">{{ $stationAddress ?: 'No station address available' }}</div>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon"><i class="fa-solid fa-phone"></i></div>
                                <div>
                                    <div class="activity-text">Station Contact</div>
                                    <div class="activity-meta">{{ $stationContact }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-card stack-card">
                        <h3 class="stack-title"><i class="fa-solid fa-shield-halved"></i> Account & Security</h3>
                        <div class="account-action-list">
                            <button type="button" class="account-action" id="openProfileEditModalSecondary">
                                <span><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i> Update profile details</span>
                                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="account-action" id="openProfileEmailModal">
                                <span><i class="fa-solid fa-envelope" aria-hidden="true"></i> Change email address</span>
                                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="account-action" id="openProfilePasswordModal">
                                <span><i class="fa-solid fa-lock" aria-hidden="true"></i> Change password</span>
                                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="profile-card stack-card record-info">
                        <h3 class="stack-title"><i class="fa-solid fa-database"></i> Record Information</h3>
                        <div class="mini-grid">
                            <div class="mini-card">
                                <div class="mini-card-label">Account Record</div>
                                <div class="mini-card-value">{{ $user->user_id }}</div>
                            </div>
                            <div class="mini-card">
                                <div class="mini-card-label">Station Officer Record</div>
                                <div class="mini-card-value">{{ $stationOfficer?->station_officer_id ?? 'Not assigned' }}</div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <div class="profile-modal" id="profileEditModal" aria-hidden="true">
        <section class="profile-modal-card" role="dialog" aria-modal="true" aria-labelledby="profileEditModalTitle">
            <div class="profile-modal-header">
                <div>
                    <div class="profile-sheet-handle" aria-hidden="true"></div>
                    <h3 class="stack-title" id="profileEditModalTitle" style="margin: 0;">
                        <i class="fa-regular fa-pen-to-square"></i>
                        Edit Profile
                    </h3>
                    <p class="profile-form-hint">Update your visible personal contact details.</p>
                </div>
                <button type="button" class="profile-modal-close" data-profile-modal-close aria-label="Close edit profile">
                    <i class="fa-solid fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('firefighter.my-profile.update') }}" data-profile-form>
                @csrf
                @method('PUT')
                <div class="profile-modal-body">
                    <div class="profile-form-grid">
                        <div class="profile-form-group">
                            <label class="profile-form-label" for="profile_first_name">First Name</label>
                            <input id="profile_first_name" name="first_name" type="text" class="profile-form-input" value="{{ old('first_name', $user->first_name) }}" autocomplete="given-name" required>
                            @error('first_name', 'profileUpdate')<div class="profile-field-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="profile-form-group">
                            <label class="profile-form-label" for="profile_last_name">Last Name</label>
                            <input id="profile_last_name" name="last_name" type="text" class="profile-form-input" value="{{ old('last_name', $user->last_name) }}" autocomplete="family-name" required>
                            @error('last_name', 'profileUpdate')<div class="profile-field-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="profile-form-group full">
                            <label class="profile-form-label" for="profile_middle_name">Middle Name</label>
                            <input id="profile_middle_name" name="middle_name" type="text" class="profile-form-input" value="{{ old('middle_name', $user->middle_name) }}" autocomplete="additional-name">
                            @error('middle_name', 'profileUpdate')<div class="profile-field-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="profile-form-group full">
                            <label class="profile-form-label" for="profile_contact_number">Contact Number</label>
                            <input id="profile_contact_number" name="contact_number" type="tel" class="profile-form-input" value="{{ old('contact_number', $user->contact_number) }}" autocomplete="tel" required>
                            @error('contact_number', 'profileUpdate')<div class="profile-field-error">{{ $message }}</div>@enderror
                        </div>
                        <div class="profile-form-group full">
                            <div class="profile-readonly-context">
                                <span>Home Address</span>
                                <span>{{ $fullAddress ?: 'No home address provided' }}</span>
                            </div>
                            <p class="profile-form-hint">Station-controlled details, role, rank, status, and email are managed separately.</p>
                        </div>
                    </div>
                </div>
                <div class="profile-modal-footer">
                    <button type="button" class="btn btn-outline" data-profile-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-primary" data-submit-label="Save Changes" data-loading-label="Saving...">Save Changes</button>
                </div>
            </form>
        </section>
    </div>

    <div class="profile-modal" id="profileEmailModal" aria-hidden="true">
        <section class="profile-modal-card profile-modal-card--compact" role="dialog" aria-modal="true" aria-labelledby="profileEmailModalTitle">
            <div class="profile-modal-header">
                <div>
                    <div class="profile-sheet-handle" aria-hidden="true"></div>
                    <h3 class="stack-title" id="profileEmailModalTitle" style="margin: 0;">
                        <i class="fa-solid fa-envelope"></i>
                        Change Email Address
                    </h3>
                    <p class="profile-form-hint">Confirm your password before changing your sign-in email.</p>
                </div>
                <button type="button" class="profile-modal-close" data-profile-modal-close aria-label="Close change email">
                    <i class="fa-solid fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('firefighter.my-profile.email.update') }}" data-profile-form data-clear-passwords>
                @csrf
                @method('PUT')
                <input type="hidden" name="current_email" value="{{ $user->email }}">
                <div class="profile-modal-body">
                    <div class="profile-readonly-context">
                        <span>Current email</span>
                        <span>{{ $email }}</span>
                    </div>
                    <div class="profile-form-group">
                        <label class="profile-form-label" for="profile_new_email">New Email Address</label>
                        <input id="profile_new_email" name="new_email" type="email" class="profile-form-input" value="{{ old('new_email') }}" autocomplete="email" required>
                        @error('new_email', 'emailUpdate')<div class="profile-field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="profile-form-group">
                        <label class="profile-form-label" for="profile_email_current_password">Current Password</label>
                        <div class="profile-password-wrap">
                            <input id="profile_email_current_password" name="current_password" type="password" class="profile-form-input" autocomplete="current-password" required>
                            <button type="button" class="profile-password-toggle" data-toggle-password aria-label="Show current password">
                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        @error('current_password', 'emailUpdate')<div class="profile-field-error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="profile-modal-footer">
                    <button type="button" class="btn btn-outline" data-profile-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-primary" data-submit-label="Update Email" data-loading-label="Updating...">Update Email</button>
                </div>
            </form>
        </section>
    </div>

    <div class="profile-modal" id="profilePasswordModal" aria-hidden="true">
        <section class="profile-modal-card profile-modal-card--compact" role="dialog" aria-modal="true" aria-labelledby="profilePasswordModalTitle">
            <div class="profile-modal-header">
                <div>
                    <div class="profile-sheet-handle" aria-hidden="true"></div>
                    <h3 class="stack-title" id="profilePasswordModalTitle" style="margin: 0;">
                        <i class="fa-solid fa-lock"></i>
                        Change Password
                    </h3>
                    <p class="profile-form-hint">Use at least 8 characters and avoid reusing your current password.</p>
                </div>
                <button type="button" class="profile-modal-close" data-profile-modal-close aria-label="Close change password">
                    <i class="fa-solid fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('firefighter.my-profile.password.update') }}" data-profile-form data-clear-passwords>
                @csrf
                @method('PUT')
                <div class="profile-modal-body">
                    <div class="profile-form-group">
                        <label class="profile-form-label" for="profile_password_current">Current Password</label>
                        <div class="profile-password-wrap">
                            <input id="profile_password_current" name="current_password" type="password" class="profile-form-input" autocomplete="current-password" required>
                            <button type="button" class="profile-password-toggle" data-toggle-password aria-label="Show current password">
                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        @error('current_password', 'passwordUpdate')<div class="profile-field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="profile-form-group">
                        <label class="profile-form-label" for="profile_password_new">New Password</label>
                        <div class="profile-password-wrap">
                            <input id="profile_password_new" name="password" type="password" class="profile-form-input" minlength="8" autocomplete="new-password" required>
                            <button type="button" class="profile-password-toggle" data-toggle-password aria-label="Show new password">
                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        @error('password', 'passwordUpdate')<div class="profile-field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="profile-form-group">
                        <label class="profile-form-label" for="profile_password_confirmation">Confirm New Password</label>
                        <div class="profile-password-wrap">
                            <input id="profile_password_confirmation" name="password_confirmation" type="password" class="profile-form-input" minlength="8" autocomplete="new-password" required>
                            <button type="button" class="profile-password-toggle" data-toggle-password aria-label="Show password confirmation">
                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="profile-modal-footer">
                    <button type="button" class="btn btn-outline" data-profile-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-primary" data-submit-label="Update Password" data-loading-label="Updating...">Update Password</button>
                </div>
            </form>
        </section>
    </div>

    <div class="profile-discard-confirm" id="profileDiscardConfirm" aria-hidden="true">
        <div class="profile-discard-card" role="dialog" aria-modal="true" aria-labelledby="profileDiscardTitle">
            <h3 id="profileDiscardTitle">Discard changes?</h3>
            <p>Your unsaved changes will be lost.</p>
            <div class="profile-discard-actions">
                <button type="button" class="btn btn-outline" id="profileKeepEditing">Keep Editing</button>
                <button type="button" class="btn btn-primary" id="profileDiscardChanges">Discard</button>
            </div>
        </div>
    </div>

    <div id="firefighterProfileCropModal" aria-hidden="true">
        <div class="crop-shell" role="dialog" aria-modal="true" aria-labelledby="firefighterProfileCropTitle">
            <div class="crop-head">
                <div>
                    <h3 id="firefighterProfileCropTitle">Adjust profile photo</h3>
                    <p>Drag to center your photo inside the circle, then use zoom if needed.</p>
                </div>
                <button id="firefighterProfileCropClose" type="button" class="crop-close" aria-label="Close photo cropper">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="crop-body">
                <div id="firefighterProfileCropViewport">
                    <div id="firefighterProfileCropStage">
                        <img id="firefighterProfileCropImage" alt="Profile crop preview">
                    </div>
                </div>
                <div class="crop-zoom">
                    <div class="crop-zoom-label">
                        <span>Zoom</span>
                        <span id="firefighterProfileZoomValue">100%</span>
                    </div>
                    <input id="firefighterProfileZoomRange" type="range" class="crop-range" min="1" max="3" step="0.01" value="1">
                </div>
                <p id="firefighterProfileCropMessage" class="crop-message"></p>
                <div class="crop-actions">
                    <button id="firefighterProfileCropCancel" type="button" class="crop-btn crop-btn--ghost">Cancel</button>
                    <button id="firefighterProfileCropApply" type="button" class="crop-btn crop-btn--primary">
                        <i class="fa-solid fa-circle-check"></i>
                        Use this photo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="profile-photo-viewer" id="firefighterProfileViewerModal" aria-hidden="true">
        <button id="firefighterProfileViewerClose" type="button" class="profile-photo-viewer-close" aria-label="Close profile photo preview">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <img id="firefighterProfileViewerImage" alt="Profile photo preview">
    </div>

    <div class="profile-toast" id="profileToast" role="status" aria-live="polite"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toast = document.getElementById('profileToast');
            const modals = Array.from(document.querySelectorAll('.profile-modal'));
            const discardConfirm = document.getElementById('profileDiscardConfirm');
            const keepEditingBtn = document.getElementById('profileKeepEditing');
            const discardChangesBtn = document.getElementById('profileDiscardChanges');
            const modalToReopen = @json($modalToReopen);
            const firstErrorMessage = @json($modalErrorMessage);
            let activeModal = null;
            let pendingCloseModal = null;
            let lastTrigger = null;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const profilePhotoInput = document.getElementById('firefighterProfilePhotoInput');
            const profilePhotoTrigger = document.getElementById('firefighterProfilePhotoTrigger');
            const profileCardFallback = document.getElementById('firefighterProfileCardInitials');
            const cropModal = document.getElementById('firefighterProfileCropModal');
            const cropViewport = document.getElementById('firefighterProfileCropViewport');
            const cropStage = document.getElementById('firefighterProfileCropStage');
            const cropImage = document.getElementById('firefighterProfileCropImage');
            const cropRange = document.getElementById('firefighterProfileZoomRange');
            const cropZoomValue = document.getElementById('firefighterProfileZoomValue');
            const cropMessage = document.getElementById('firefighterProfileCropMessage');
            const cropClose = document.getElementById('firefighterProfileCropClose');
            const cropCancel = document.getElementById('firefighterProfileCropCancel');
            const cropApply = document.getElementById('firefighterProfileCropApply');
            const profilePhotoUploadUrl = @json(route('firefighter.my-profile.photo.update'));
            const profileViewerModal = document.getElementById('firefighterProfileViewerModal');
            const profileViewerImage = document.getElementById('firefighterProfileViewerImage');
            const profileViewerClose = document.getElementById('firefighterProfileViewerClose');
            let savedProfileImageUrl = @json($profileImageUrl);
            let cropObjectUrl = null;
            let uploadInFlight = false;
            let cropImageState = {
                sourceWidth: 0,
                sourceHeight: 0,
                baseScale: 1,
                scale: 1,
                minScale: 1,
                maxScale: 3,
                offsetX: 0,
                offsetY: 0,
            };
            let dragState = null;

            function updateAvatarState(imageUrl, initials) {
                const safeInitials = initials || profileCardFallback?.textContent?.trim() || 'FR';

                document.querySelectorAll('[data-firefighter-avatar-frame]').forEach((frame) => {
                    let image = frame.querySelector('[data-firefighter-avatar-image]');
                    let fallback = frame.querySelector('[data-firefighter-avatar-fallback], .profile-avatar-fallback');

                    if (imageUrl) {
                        if (!image) {
                            image = document.createElement('img');
                            image.className = 'profile-avatar-image h-full w-full object-cover';
                            image.alt = 'Firefighter profile';
                            image.setAttribute('data-firefighter-avatar-image', '');
                            if (frame.classList.contains('profile-avatar')) {
                                image.setAttribute('data-firefighter-profile-view-trigger', '');
                                image.addEventListener('click', () => {
                                    if (savedProfileImageUrl) {
                                        openProfileViewer(savedProfileImageUrl);
                                    }
                                });
                            }
                            frame.insertBefore(image, fallback || null);
                        }
                        image.src = imageUrl;
                        image.style.display = '';
                        if (fallback) fallback.style.display = 'none';
                    } else {
                        if (image) {
                            image.removeAttribute('src');
                            image.style.display = 'none';
                        }
                        if (fallback) {
                            fallback.textContent = safeInitials;
                            fallback.style.display = '';
                        }
                    }
                });
            }

            function revokeCropObjectUrl() {
                if (cropObjectUrl) {
                    URL.revokeObjectURL(cropObjectUrl);
                    cropObjectUrl = null;
                }
            }

            function openProfileViewer(src) {
                if (!profileViewerModal || !profileViewerImage || !src) return;
                profileViewerImage.src = src;
                profileViewerModal.classList.add('active');
                profileViewerModal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('profile-modal-open');
            }

            function closeProfileViewer() {
                if (!profileViewerModal || !profileViewerImage) return;
                profileViewerModal.classList.remove('active');
                profileViewerModal.setAttribute('aria-hidden', 'true');
                profileViewerImage.removeAttribute('src');
                if (!document.querySelector('.profile-modal.active') && !cropModal?.classList.contains('active')) {
                    document.body.classList.remove('profile-modal-open');
                }
            }

            function setCropMessage(message = '') {
                if (cropMessage) {
                    cropMessage.textContent = message;
                }
            }

            function updateCropImageTransform() {
                if (!cropImage || !cropViewport) return;
                const displayedScale = cropImageState.baseScale * cropImageState.scale;
                cropImage.style.transform = `translate(${cropImageState.offsetX}px, ${cropImageState.offsetY}px) scale(${displayedScale})`;
                if (cropZoomValue) {
                    cropZoomValue.textContent = `${Math.round(cropImageState.scale * 100)}%`;
                }
            }

            function clampCropOffsets() {
                if (!cropViewport) return;
                const viewportRect = cropViewport.getBoundingClientRect();
                const displayedWidth = cropImageState.sourceWidth * cropImageState.baseScale * cropImageState.scale;
                const displayedHeight = cropImageState.sourceHeight * cropImageState.baseScale * cropImageState.scale;
                const minX = viewportRect.width - displayedWidth;
                const minY = viewportRect.height - displayedHeight;
                cropImageState.offsetX = Math.min(0, Math.max(minX, cropImageState.offsetX));
                cropImageState.offsetY = Math.min(0, Math.max(minY, cropImageState.offsetY));
            }

            function resetCropState() {
                cropImageState = {
                    sourceWidth: 0,
                    sourceHeight: 0,
                    baseScale: 1,
                    scale: 1,
                    minScale: 1,
                    maxScale: 3,
                    offsetX: 0,
                    offsetY: 0,
                };
                dragState = null;
                cropStage?.classList.remove('is-dragging');
                if (cropRange) cropRange.value = '1';
                if (cropZoomValue) cropZoomValue.textContent = '100%';
                setCropMessage('');
            }

            function openCropModalWithFile(file) {
                if (!cropModal || !cropImage || !cropViewport) return;
                revokeCropObjectUrl();
                resetCropState();
                cropObjectUrl = URL.createObjectURL(file);
                cropImage.onload = function () {
                    const viewportRect = cropViewport.getBoundingClientRect();
                    const sourceWidth = cropImage.naturalWidth || 1;
                    const sourceHeight = cropImage.naturalHeight || 1;
                    // Leave a little movement room on both axes even at 100% zoom.
                    const baseScale = Math.max(viewportRect.width / sourceWidth, viewportRect.height / sourceHeight) * 1.08;
                    cropImageState.sourceWidth = sourceWidth;
                    cropImageState.sourceHeight = sourceHeight;
                    cropImageState.baseScale = baseScale;
                    cropImageState.scale = 1;
                    cropImageState.minScale = 1;
                    cropImageState.maxScale = 3;
                    cropImageState.offsetX = (viewportRect.width - (sourceWidth * baseScale)) / 2;
                    cropImageState.offsetY = (viewportRect.height - (sourceHeight * baseScale)) / 2;
                    clampCropOffsets();
                    updateCropImageTransform();
                };
                cropImage.src = cropObjectUrl;
                cropModal.classList.add('active');
                cropModal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('profile-modal-open');
            }

            function closeCropModal() {
                if (!cropModal) return;
                cropModal.classList.remove('active');
                cropModal.setAttribute('aria-hidden', 'true');
                if (profilePhotoInput) {
                    profilePhotoInput.value = '';
                }
                revokeCropObjectUrl();
                if (cropImage) {
                    cropImage.removeAttribute('src');
                }
                resetCropState();
                if (!document.querySelector('.profile-modal.active') && !profileViewerModal?.classList.contains('active')) {
                    document.body.classList.remove('profile-modal-open');
                }
            }

            async function cropCurrentImageToBlob() {
                const viewportRect = cropViewport?.getBoundingClientRect();
                if (!viewportRect || !cropImageState.sourceWidth || !cropImageState.sourceHeight) {
                    throw new Error('Profile crop is not ready yet.');
                }

                const outputSize = 720;
                const canvas = document.createElement('canvas');
                canvas.width = outputSize;
                canvas.height = outputSize;
                const context = canvas.getContext('2d');
                if (!context) {
                    throw new Error('Unable to prepare the image crop.');
                }

                const scale = cropImageState.baseScale * cropImageState.scale;
                const sourceX = Math.max(0, (-cropImageState.offsetX) / scale);
                const sourceY = Math.max(0, (-cropImageState.offsetY) / scale);
                const sourceWidth = Math.min(cropImageState.sourceWidth, viewportRect.width / scale);
                const sourceHeight = Math.min(cropImageState.sourceHeight, viewportRect.height / scale);

                context.drawImage(
                    cropImage,
                    sourceX,
                    sourceY,
                    sourceWidth,
                    sourceHeight,
                    0,
                    0,
                    outputSize,
                    outputSize
                );

                return await new Promise((resolve, reject) => {
                    canvas.toBlob((blob) => {
                        if (!blob) {
                            reject(new Error('Unable to export the cropped image.'));
                            return;
                        }
                        resolve(blob);
                    }, 'image/jpeg', 0.92);
                });
            }

            async function uploadProfilePhoto(blob) {
                if (uploadInFlight) return;
                uploadInFlight = true;
                cropApply.disabled = true;
                cropApply.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading...';
                setCropMessage('');

                try {
                    const formData = new FormData();
                    formData.append('profile_image', blob, 'firefighter-profile.jpg');

                    const response = await fetch(profilePhotoUploadUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: formData,
                        credentials: 'same-origin',
                    });

                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        const message = payload?.message
                            || payload?.errors?.profile_image?.[0]
                            || 'Unable to upload the profile photo right now.';
                        throw new Error(message);
                    }

                    const user = payload.user || {};
                    savedProfileImageUrl = user.image_url || null;
                    updateAvatarState(savedProfileImageUrl, user.initials || profileCardFallback?.textContent?.trim() || 'FR');
                    closeCropModal();
                    showProfileToast(payload.message || 'Profile photo updated.');
                } catch (error) {
                    setCropMessage(error.message || 'Unable to upload the profile photo.');
                } finally {
                    uploadInFlight = false;
                    cropApply.disabled = false;
                    cropApply.innerHTML = '<i class="fa-solid fa-circle-check"></i> Use this photo';
                }
            }

            function showProfileToast(message, tone = 'success') {
                if (!toast || !message) return;
                toast.textContent = message;
                toast.style.background = tone === 'error'
                    ? 'linear-gradient(135deg, #be123c 0%, #ef4444 100%)'
                    : 'linear-gradient(135deg, #166534 0%, #22c55e 100%)';
                toast.classList.add('show');
                window.clearTimeout(showProfileToast.timer);
                showProfileToast.timer = window.setTimeout(() => {
                    toast.classList.remove('show');
                }, 3200);
            }

            function serializeForm(form) {
                if (!form) return '';
                return Array.from(new FormData(form).entries())
                    .filter(([key]) => !['_token', '_method'].includes(key))
                    .map(([key, value]) => `${key}:${String(value)}`)
                    .join('|');
            }

            function storePristineState(form) {
                if (form) {
                    form.dataset.pristine = serializeForm(form);
                }
            }

            function isFormDirty(form) {
                return Boolean(form && form.dataset.submitted !== 'true' && form.dataset.pristine !== serializeForm(form));
            }

            function getFocusableElements(container) {
                if (!container) return [];
                return Array.from(container.querySelectorAll(
                    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
                )).filter((element) => element.offsetParent !== null);
            }

            function focusFirstUsefulField(modal) {
                const invalidField = modal.querySelector('.profile-field-error')
                    ?.closest('.profile-form-group')
                    ?.querySelector('input, select, textarea');
                const target = invalidField || modal.querySelector('input:not([type="hidden"]), select, textarea, button[data-profile-modal-close]');
                window.setTimeout(() => target?.focus({ preventScroll: true }), 80);
            }

            function resetPasswordFields(modal) {
                if (!modal?.querySelector('[data-clear-passwords]')) return;
                modal.querySelectorAll('.profile-password-wrap input').forEach((input) => {
                    input.type = 'password';
                    input.value = '';
                });
                modal.querySelectorAll('[data-toggle-password]').forEach((button) => {
                    button.setAttribute('aria-label', button.getAttribute('aria-label')?.replace('Hide', 'Show') || 'Show password');
                    const icon = button.querySelector('i');
                    icon?.classList.remove('fa-eye-slash');
                    icon?.classList.add('fa-eye');
                });
            }

            function openModal(modalId, trigger = null) {
                const modal = document.getElementById(modalId);
                if (!modal) return;
                if (activeModal && activeModal !== modal) {
                    closeModal(activeModal, { force: true, restoreFocus: false });
                }
                lastTrigger = trigger || document.activeElement;
                activeModal = modal;
                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('profile-modal-open');
                const scrollBody = modal.querySelector('.profile-modal-body');
                if (scrollBody) scrollBody.scrollTop = 0;
                storePristineState(modal.querySelector('form[data-profile-form]'));
                focusFirstUsefulField(modal);
            }

            function showDiscardConfirm(modal) {
                if (!discardConfirm) return false;
                pendingCloseModal = modal;
                discardConfirm.classList.add('active');
                discardConfirm.setAttribute('aria-hidden', 'false');
                window.setTimeout(() => keepEditingBtn?.focus({ preventScroll: true }), 50);
                return true;
            }

            function hideDiscardConfirm() {
                discardConfirm?.classList.remove('active');
                discardConfirm?.setAttribute('aria-hidden', 'true');
                pendingCloseModal = null;
            }

            function closeModal(modal, options = {}) {
                if (!modal) return;
                const form = modal.querySelector('form[data-profile-form]');
                if (!options.force && isFormDirty(form) && showDiscardConfirm(modal)) {
                    return;
                }
                modal.classList.remove('active');
                modal.setAttribute('aria-hidden', 'true');
                resetPasswordFields(modal);
                if (form) {
                    form.dataset.submitted = 'false';
                    storePristineState(form);
                }
                if (activeModal === modal) activeModal = null;
                if (!document.querySelector('.profile-modal.active')) {
                    document.body.classList.remove('profile-modal-open');
                }
                if (options.restoreFocus !== false) {
                    window.setTimeout(() => lastTrigger?.focus?.({ preventScroll: true }), 50);
                }
            }

            document.getElementById('openProfileEditModal')?.addEventListener('click', (event) => {
                openModal('profileEditModal', event.currentTarget);
            });
            document.getElementById('openProfileEditModalSecondary')?.addEventListener('click', (event) => {
                openModal('profileEditModal', event.currentTarget);
            });
            document.getElementById('openProfileEmailModal')?.addEventListener('click', (event) => {
                openModal('profileEmailModal', event.currentTarget);
            });
            document.getElementById('openProfilePasswordModal')?.addEventListener('click', (event) => {
                openModal('profilePasswordModal', event.currentTarget);
            });

            document.querySelectorAll('[data-profile-modal-close]').forEach((button) => {
                button.addEventListener('click', () => closeModal(button.closest('.profile-modal')));
            });

            modals.forEach((modal) => {
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) closeModal(modal);
                });
                storePristineState(modal.querySelector('form[data-profile-form]'));
            });

            keepEditingBtn?.addEventListener('click', () => {
                const modal = pendingCloseModal;
                hideDiscardConfirm();
                modal?.querySelector('[data-profile-modal-close]')?.focus({ preventScroll: true });
            });

            discardChangesBtn?.addEventListener('click', () => {
                const modal = pendingCloseModal;
                hideDiscardConfirm();
                closeModal(modal, { force: true });
            });

            document.querySelectorAll('[data-toggle-password]').forEach((button) => {
                button.addEventListener('click', () => {
                    const input = button.closest('.profile-password-wrap')?.querySelector('input');
                    if (!input) return;
                    const shouldShow = input.type === 'password';
                    input.type = shouldShow ? 'text' : 'password';
                    button.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
                    const icon = button.querySelector('i');
                    icon?.classList.toggle('fa-eye', !shouldShow);
                    icon?.classList.toggle('fa-eye-slash', shouldShow);
                    input.focus({ preventScroll: true });
                });
            });

            document.querySelectorAll('form[data-profile-form]').forEach((form) => {
                form.addEventListener('submit', () => {
                    form.dataset.submitted = 'true';
                    const submitButton = form.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.textContent = submitButton.dataset.loadingLabel || 'Saving...';
                    }
                });
            });

            profilePhotoTrigger?.addEventListener('click', (event) => {
                event.preventDefault();
                profilePhotoInput?.click();
            });

            profilePhotoInput?.addEventListener('change', (event) => {
                const file = event.target.files?.[0];
                if (!file) return;

                const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    showProfileToast('Use a JPG, PNG, or WEBP image only.', 'error');
                    profilePhotoInput.value = '';
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    showProfileToast('Profile photo must be 5 MB or smaller.', 'error');
                    profilePhotoInput.value = '';
                    return;
                }

                openCropModalWithFile(file);
            });

            cropRange?.addEventListener('input', () => {
                const nextScale = Number(cropRange.value || 1);
                cropImageState.scale = Number.isFinite(nextScale) ? nextScale : 1;
                clampCropOffsets();
                updateCropImageTransform();
            });

            cropStage?.addEventListener('pointerdown', (event) => {
                if (!cropModal?.classList.contains('active') || !event.isPrimary) return;
                event.preventDefault();
                dragState = {
                    startX: event.clientX,
                    startY: event.clientY,
                    originX: cropImageState.offsetX,
                    originY: cropImageState.offsetY,
                };
                cropStage.classList.add('is-dragging');
                cropStage.setPointerCapture?.(event.pointerId);
            });

            cropStage?.addEventListener('pointermove', (event) => {
                if (!dragState || !event.isPrimary) return;
                event.preventDefault();
                cropImageState.offsetX = dragState.originX + (event.clientX - dragState.startX);
                cropImageState.offsetY = dragState.originY + (event.clientY - dragState.startY);
                clampCropOffsets();
                updateCropImageTransform();
            });

            ['pointerup', 'pointercancel', 'lostpointercapture'].forEach((eventName) => {
                cropStage?.addEventListener(eventName, () => {
                    dragState = null;
                    cropStage.classList.remove('is-dragging');
                });
            });

            cropClose?.addEventListener('click', closeCropModal);
            cropCancel?.addEventListener('click', closeCropModal);
            cropModal?.addEventListener('click', (event) => {
                if (event.target === cropModal) {
                    closeCropModal();
                }
            });

            cropApply?.addEventListener('click', async () => {
                try {
                    const blob = await cropCurrentImageToBlob();
                    await uploadProfilePhoto(blob);
                } catch (error) {
                    setCropMessage(error.message || 'Unable to prepare the selected image.');
                }
            });

            document.querySelectorAll('[data-firefighter-profile-view-trigger]').forEach((trigger) => {
                trigger.addEventListener('click', () => {
                    if (savedProfileImageUrl) {
                        openProfileViewer(savedProfileImageUrl);
                    }
                });
            });

            profileViewerClose?.addEventListener('click', closeProfileViewer);
            profileViewerModal?.addEventListener('click', (event) => {
                if (event.target === profileViewerModal) {
                    closeProfileViewer();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    if (profileViewerModal?.classList.contains('active')) {
                        closeProfileViewer();
                        return;
                    }
                    if (cropModal?.classList.contains('active')) {
                        closeCropModal();
                        return;
                    }
                    if (discardConfirm?.classList.contains('active')) {
                        hideDiscardConfirm();
                        activeModal?.querySelector('[data-profile-modal-close]')?.focus({ preventScroll: true });
                        return;
                    }
                    if (activeModal) closeModal(activeModal);
                }

                if (event.key !== 'Tab') return;
                const trapContainer = discardConfirm?.classList.contains('active') ? discardConfirm : activeModal;
                const focusable = getFocusableElements(trapContainer);
                if (!focusable.length) return;
                const first = focusable[0];
                const last = focusable[focusable.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            });

            @if (session('success'))
                showProfileToast(@json(session('success')));
            @endif

            if (modalToReopen) {
                openModal(modalToReopen);
                showProfileToast(firstErrorMessage, 'error');
            }

            updateAvatarState(savedProfileImageUrl, profileCardFallback?.textContent?.trim() || 'FR');
        });
    </script>
    </div>
    @include('partials.pwa-body')
</body>
</html>
