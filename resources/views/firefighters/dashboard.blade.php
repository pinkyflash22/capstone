{{-- resources/views/firefighter/dashboard.blade.php --}}
@php
    $dashboardProfileImageUrl = \App\Support\MediaPath::url(auth()->user()?->image);
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->user()->user_id ?? '' }}">
    <meta name="user-role" content="firefighter">
    <meta name="user-role-id" content="{{ auth()->user()->role_id ?? '' }}">
    <meta name="station-id" content="{{ $thisStation ?? '' }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    
    <title>PyroSense · Firefighter Dashboard</title>
    
    @vite(['resources/css/app.css', 'resources/css/firefighters/dashboard.css', 'resources/css/firefighters/dashboard-shell.css'])
    {{--
    <style>
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }

        .legend i {
            margin-right: 8px;
            width: 14px;
            display: inline-block;
        }

        .ff-shell-section-state {
            display: grid;
            gap: 12px;
        }

        .ff-shell-skeleton {
            position: relative;
            overflow: hidden;
            border-radius: 18px;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2f7 100%);
            border: 1px solid rgba(226, 232, 240, 0.9);
        }

        .ff-shell-skeleton::after {
            content: '';
            position: absolute;
            inset: 0;
            transform: translateX(-100%);
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.72), transparent);
            animation: ff-shell-shimmer 1.4s infinite;
        }

        .ff-shell-skeleton-line {
            height: 14px;
            border-radius: 999px;
        }

        .ff-shell-skeleton-card {
            height: 86px;
        }

        .ff-shell-skeleton-list {
            height: 74px;
        }

        .ff-shell-section-error {
            padding: 18px;
            border-radius: 18px;
            border: 1px solid rgba(239, 68, 68, 0.12);
            background: linear-gradient(135deg, #fff5f5 0%, #fffaf5 100%);
            color: #9a3412;
            font-size: 0.9rem;
        }

        .ff-shell-section-error button {
            margin-top: 10px;
            border: 0;
            border-radius: 999px;
            padding: 10px 14px;
            font-size: 0.78rem;
            font-weight: 800;
            color: #fff;
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            cursor: pointer;
        }

        .ff-map-load-note {
            margin-top: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(255, 247, 237, 0.9);
            border: 1px solid rgba(198, 61, 38, 0.12);
            color: #9a3412;
            font-size: 0.8rem;
            font-weight: 700;
        }

        @keyframes ff-shell-shimmer {
            100% { transform: translateX(100%); }
        }

        #dashboardCrewList[data-shell-loading="true"] > .crew-item {
            display: none;
        }
        
        .leaflet-div-icon {
            background: transparent;
            border: none;
        }

        .firefighter-mobile-welcome-toast,
        .firefighter-mobile-insights-panel,
        .firefighter-mobile-assigned-fab {
            display: none;
        }

        @media (max-width: 768px) {
            body {
                overflow-x: hidden;
            }

            .lector-top-bar {
                display: none !important;
            }

            .firefighter-mobile-welcome-toast {
                position: fixed;
                top: calc(env(safe-area-inset-top, 0px) + 14px);
                left: 14px;
                right: 14px;
                z-index: 1300;
                display: flex;
                align-items: center;
                gap: 12px;
                justify-content: space-between;
                padding: 14px 15px;
                border-radius: 24px;
                background: linear-gradient(135deg, rgba(255, 250, 240, 0.97), rgba(255, 255, 255, 0.94));
                border: 1px solid rgba(198, 61, 38, 0.12);
                box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
                opacity: 0;
                transform: translateY(-8px);
                pointer-events: auto;
                transition: opacity 0.24s ease, transform 0.24s ease;
                cursor: pointer;
            }

            .firefighter-mobile-welcome-toast.is-visible {
                opacity: 1;
                transform: translateY(0);
            }

            .firefighter-mobile-welcome-toast__icon {
                width: 42px;
                height: 42px;
                border-radius: 14px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
                color: #ffffff;
                flex-shrink: 0;
            }

            .firefighter-mobile-welcome-toast__copy {
                display: flex;
                flex-direction: column;
                gap: 3px;
                min-width: 0;
                flex: 1;
            }

            .firefighter-mobile-welcome-toast__copy strong {
                color: #7c2d12;
                font-size: 0.9rem;
                line-height: 1.2;
                font-weight: 800;
            }

            .firefighter-mobile-welcome-toast__copy span {
                color: #64748b;
                font-size: 0.74rem;
                line-height: 1.35;
            }

            .firefighter-mobile-welcome-toast::after {
                content: 'Open brief';
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 34px;
                padding: 0 12px;
                border-radius: 999px;
                background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
                color: #fff;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.02em;
                box-shadow: 0 10px 22px rgba(198, 61, 38, 0.18);
                flex-shrink: 0;
            }

            .firefighter-dashboard-welcome,
            [data-ff-insights-source] {
                display: none !important;
            }

            .firefighter-map-stage {
                width: 100vw;
                max-width: 100vw;
                margin: calc(-1 * env(safe-area-inset-top, 0px)) 0 0 calc(50% - 50vw) !important;
                padding: 0 !important;
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                background: transparent !important;
                position: relative;
                z-index: 1 !important;
                isolation: isolate;
            }

            .firefighter-map-stage__header,
            .firefighter-map-stage__legend {
                display: none !important;
            }

            .firefighter-map-stage .map-container {
                margin: 0 !important;
                border: none !important;
                border-radius: 0 !important;
                overflow: hidden;
            }

            #firefighterDashboardMap {
                width: 100vw !important;
                height: calc(100svh - (92px + env(safe-area-inset-bottom, 0px)) + env(safe-area-inset-top, 0px)) !important;
                min-height: calc(100svh - (92px + env(safe-area-inset-bottom, 0px)) + env(safe-area-inset-top, 0px)) !important;
            }

            .firefighter-mobile-insights-panel {
                position: fixed;
                inset: 0;
                z-index: 9996;
            }

            .firefighter-mobile-insights-panel[aria-hidden="true"] {
                display: none;
            }

            .firefighter-mobile-insights-panel[aria-hidden="false"] {
                display: block;
            }

            .firefighter-mobile-insights-panel__backdrop {
                position: absolute;
                inset: 0;
                background: rgba(15, 23, 42, 0.42);
                backdrop-filter: blur(8px);
            }

            .firefighter-mobile-insights-panel__sheet {
                position: absolute;
                left: 0;
                right: 0;
                bottom: 0;
                top: auto;
                background: linear-gradient(180deg, #fffaf4 0%, #fbf7ef 100%);
                border-radius: 30px 30px 0 0;
                padding: 14px 16px calc(22px + env(safe-area-inset-bottom, 0px));
                min-height: min(64svh, 700px);
                max-height: min(78svh, 760px);
                overflow-y: auto;
                box-shadow: 0 -24px 50px rgba(15, 23, 42, 0.18);
            }

            .firefighter-mobile-insights-panel__header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 16px;
                margin-bottom: 16px;
                position: relative;
                padding-top: 14px;
            }

            .firefighter-mobile-insights-panel__header::before {
                content: '';
                position: absolute;
                top: 0;
                left: 50%;
                transform: translateX(-50%);
                width: 56px;
                height: 5px;
                border-radius: 999px;
                background: rgba(148, 163, 184, 0.45);
            }

            .firefighter-mobile-insights-panel__eyebrow {
                margin: 0 0 4px;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: #d18a16;
            }

            .firefighter-mobile-insights-panel__title {
                margin: 0;
                font-size: 1.45rem;
                line-height: 1.1;
                font-weight: 800;
                color: #1e293b;
            }

            .firefighter-mobile-insights-panel__close {
                width: 42px;
                height: 42px;
                border: none;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.92);
                color: #64748b;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            }

            .firefighter-mobile-insights-panel__content [data-ff-insights-source] {
                display: block !important;
                margin: 0 0 14px !important;
            }

            .firefighter-mobile-insights-panel__content .kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 10px !important;
            }

            .firefighter-mobile-insights-panel__content .grid-4,
            .firefighter-mobile-insights-panel__content .grid-2 {
                grid-template-columns: 1fr !important;
            }

            .firefighter-mobile-insights-panel__content .text-center.text-xs.text-gray-400.mt-8.border-t.pt-4 {
                margin-top: 0 !important;
            }

            .firefighter-mobile-insights-panel__content .kpi-card,
            .firefighter-mobile-insights-panel__content .lector-card,
            .firefighter-mobile-insights-panel__content .stat-card,
            .firefighter-mobile-insights-panel__content .assigned-incidents-container,
            .firefighter-mobile-insights-panel__content .bg-white.rounded-xl.shadow {
                border-radius: 22px !important;
                box-shadow: 0 14px 32px rgba(15, 23, 42, 0.08) !important;
            }

            .firefighter-mobile-assigned-fab {
                position: fixed;
                right: 16px;
                bottom: calc(102px + env(safe-area-inset-bottom, 0px));
                z-index: 1320;
                display: inline-flex;
                align-items: center;
                gap: 10px;
                min-height: 58px;
                padding: 0 18px 0 16px;
                border: none;
                border-radius: 999px;
                background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
                color: #fff;
                box-shadow: 0 18px 34px rgba(198, 61, 38, 0.28);
            }

            .firefighter-mobile-assigned-fab__icon {
                width: 34px;
                height: 34px;
                border-radius: 999px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: rgba(255, 255, 255, 0.18);
                flex-shrink: 0;
            }

            .firefighter-mobile-assigned-fab__copy {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                min-width: 0;
            }

            .firefighter-mobile-assigned-fab__label {
                font-size: 0.74rem;
                font-weight: 900;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                line-height: 1.1;
            }

            .firefighter-mobile-assigned-fab__meta {
                font-size: 0.78rem;
                font-weight: 700;
                line-height: 1.15;
                opacity: 0.96;
            }

            .firefighter-mobile-assigned-fab__count {
                min-width: 24px;
                height: 24px;
                padding: 0 7px;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.22);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 0.74rem;
                font-weight: 900;
                line-height: 1;
                flex-shrink: 0;
            }
        }

    </style>
    --}}
    @include('partials.pwa-head')
    <style data-ff-dashboard-mobile-assigned-fab>
        .firefighter-mobile-assigned-fab {
            position: fixed;
            right: 20px;
            bottom: 30px;
            z-index: 1320;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            min-height: 52px;
            padding: 0 15px 0 14px;
            border: none;
            border-radius: 999px;
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            color: #fff;
            box-shadow: 0 18px 34px rgba(198, 61, 38, 0.24);
        }

        .firefighter-mobile-assigned-fab__icon {
            width: 30px;
            height: 30px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.18);
            flex-shrink: 0;
        }

        .firefighter-mobile-assigned-fab__copy {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            min-width: 0;
        }

        .firefighter-mobile-assigned-fab__label {
            font-size: 0.7rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            line-height: 1.1;
        }

        .firefighter-mobile-assigned-fab__meta {
            font-size: 0.74rem;
            font-weight: 700;
            line-height: 1.1;
            opacity: 0.96;
        }

        .firefighter-mobile-assigned-fab__count {
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.22);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.72rem;
            font-weight: 900;
            line-height: 1;
            flex-shrink: 0;
        }

        .firefighter-assigned-queue-modal {
            position: fixed;
            inset: 0;
            z-index: 9988;
            display: none;
        }

        .firefighter-assigned-queue-modal[aria-hidden="false"] {
            display: block;
        }

        .firefighter-assigned-queue-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.48);
            backdrop-filter: blur(8px);
        }

        .firefighter-assigned-queue-modal__dialog {
            position: absolute;
            inset: 50% auto auto 50%;
            transform: translate(-50%, -50%);
            width: min(1040px, calc(100vw - 40px));
            max-height: calc(100vh - 56px);
            background: linear-gradient(180deg, #fffaf4 0%, #ffffff 100%);
            border-radius: 28px;
            border: 1px solid rgba(226, 232, 240, 0.95);
            box-shadow: 0 32px 70px rgba(15, 23, 42, 0.22);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .firefighter-assigned-queue-modal__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 22px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.92);
        }

        .firefighter-assigned-queue-modal__header-actions {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .firefighter-assigned-queue-modal__eyebrow {
            margin: 0 0 4px;
            font-size: 0.72rem;
            font-weight: 900;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #d18a16;
        }

        .firefighter-assigned-queue-modal__title {
            margin: 0;
            color: #0f172a;
            font-size: 1.45rem;
            line-height: 1.08;
            font-weight: 900;
        }

        .firefighter-assigned-queue-modal__refresh,
        .firefighter-assigned-queue-modal__close {
            width: 42px;
            height: 42px;
            border: none;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.92);
            color: #64748b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .firefighter-assigned-queue-modal__content {
            padding: 20px 22px 22px;
            overflow-y: auto;
        }

        .firefighter-assigned-queue-modal__content [data-ff-insights-source] {
            display: block !important;
            margin: 0 !important;
        }

        .ff-assigned-incidents-inline {
            display: none;
        }

        .firefighter-assigned-queue-modal__content .ff-assigned-incidents-inline {
            display: block !important;
        }

        .firefighter-assigned-queue-modal__content .assigned-incidents-container.is-in-modal .section-header {
            display: none !important;
        }

        @media (max-width: 768px) {
            .firefighter-mobile-welcome-toast {
                z-index: 1300 !important;
            }

            .firefighter-mobile-assigned-fab {
                right: 14px;
                bottom: calc(96px + env(safe-area-inset-bottom, 0px));
                width: 56px;
                min-width: 56px;
                height: 56px;
                min-height: 56px;
                padding: 0;
                gap: 0;
                justify-content: center;
                border-radius: 18px;
                z-index: 1320 !important;
            }

            .firefighter-mobile-assigned-fab__icon {
                width: 34px;
                height: 34px;
            }

            .firefighter-mobile-assigned-fab__copy {
                display: none;
            }

            .firefighter-mobile-assigned-fab__count {
                position: absolute;
                top: -6px;
                right: -6px;
                min-width: 22px;
                height: 22px;
                padding: 0 5px;
                background: #fff7ed;
                color: #c2410c;
                border: 1px solid rgba(251, 146, 60, 0.4);
                box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
            }

            .firefighter-assigned-queue-modal__dialog {
                inset: auto 10px calc(10px + env(safe-area-inset-bottom, 0px)) 10px;
                transform: none;
                width: auto;
                max-height: min(76svh, 760px);
                border-radius: 24px;
            }

            .firefighter-assigned-queue-modal__header {
                padding: 16px 16px 14px;
                gap: 12px;
            }

            .firefighter-assigned-queue-modal__eyebrow {
                font-size: 0.64rem;
                margin-bottom: 3px;
            }

            .firefighter-assigned-queue-modal__title {
                font-size: 1.1rem;
                line-height: 1.08;
            }

            .firefighter-assigned-queue-modal__close {
                width: 38px;
                height: 38px;
                flex-shrink: 0;
            }

            .firefighter-assigned-queue-modal__refresh {
                width: 38px;
                height: 38px;
                flex-shrink: 0;
            }

            .firefighter-assigned-queue-modal__content {
                padding: 10px 12px calc(14px + env(safe-area-inset-bottom, 0px));
            }

            .firefighter-assigned-queue-modal__content .assigned-incidents-container {
                border-radius: 0 !important;
                border: 0 !important;
                box-shadow: none !important;
                background: transparent !important;
                padding: 0 !important;
            }

            .firefighter-assigned-queue-modal__content .assigned-incidents-container.is-in-modal .section-header {
                display: none !important;
            }

            .firefighter-assigned-queue-modal__content .section-header {
                display: none !important;
            }

            .firefighter-assigned-queue-modal__content .view-all,
            .firefighter-assigned-queue-modal__content #refreshAssignedIncidents {
                min-height: 32px;
                width: 32px;
                padding: 0;
                border-radius: 999px;
                font-size: 0.74rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .firefighter-assigned-queue-modal__content #refreshAssignedIncidents span,
            .firefighter-assigned-queue-modal__content #refreshAssignedIncidents .ff-refresh-label {
                display: none;
            }

            .firefighter-assigned-queue-modal__content .ff-assigned-incident-row {
                padding: 0.6rem 0;
            }

            .firefighter-assigned-queue-modal__content .ff-assigned-incident-row__main {
                grid-template-columns: 52px minmax(0, 1fr);
                gap: 0.6rem;
            }

            .firefighter-assigned-queue-modal__content .ff-assigned-incident-row__media {
                width: 46px;
                height: 46px;
                min-width: 46px;
                min-height: 46px;
                border-radius: 0.8rem;
            }

            .firefighter-assigned-queue-modal__content .ff-assigned-incident-row__title {
                font-size: 0.86rem;
            }

            .firefighter-assigned-queue-modal__content .ff-assigned-incident-row__summary {
                font-size: 0.64rem;
            }

            .firefighter-assigned-queue-modal__content .ff-assigned-incident-row__type,
            .firefighter-assigned-queue-modal__content .ff-assigned-incident-row__status {
                font-size: 0.58rem;
                padding: 0.2rem 0.42rem;
            }

            .firefighter-assigned-queue-modal__content .ff-assigned-incident-row__detail,
            .firefighter-assigned-queue-modal__content .ff-assigned-incident-row__meta {
                font-size: 0.65rem;
            }

            .firefighter-assigned-queue-modal__content .ff-assigned-incident-row__actions {
                padding-left: 0;
                justify-content: flex-end;
                align-self: center;
                gap: 0.3rem;
                flex-wrap: nowrap;
                max-width: 100%;
            }

            .firefighter-assigned-queue-modal__content .ff-assigned-incident-row__action {
                min-height: 28px;
                padding: 0 0.58rem;
                font-size: 0.62rem;
                flex: 0 0 auto;
                white-space: nowrap;
            }

            .firefighter-assigned-queue-modal__content .ff-assigned-incident-pagination {
                padding-top: 0.45rem;
                gap: 0.6rem;
            }

            .firefighter-assigned-queue-modal__content .ff-assigned-incident-pagination__summary,
            .firefighter-assigned-queue-modal__content .ff-assigned-incident-pagination__indicator {
                font-size: 0.72rem;
            }
        }
    </style>
    <style data-ff-dashboard-announcement-stories>
        #dashboardAnnouncementsList {
            overflow: visible !important;
            max-height: none !important;
        }

        .ff-dashboard-stories {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 0.9rem;
            align-items: stretch;
        }

        .ff-dashboard-story {
            position: relative;
            min-height: 220px;
            overflow: hidden;
            border-radius: 1.35rem;
            border: 1px solid rgba(226, 232, 240, 0.92);
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.08), rgba(15, 23, 42, 0.78));
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
            text-decoration: none;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .ff-dashboard-story:hover {
            transform: translateY(-3px);
            box-shadow: 0 22px 40px rgba(15, 23, 42, 0.14);
        }

        .ff-dashboard-story__media,
        .ff-dashboard-story__fallback {
            position: absolute;
            inset: 0;
        }

        .ff-dashboard-story__media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .ff-dashboard-story__fallback {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(145deg, #c63d26, #d18a16);
            color: rgba(255, 255, 255, 0.92);
            font-size: 2.1rem;
            font-weight: 800;
        }

        .ff-dashboard-story__overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.06) 0%, rgba(15, 23, 42, 0.24) 36%, rgba(15, 23, 42, 0.92) 100%);
        }

        .ff-dashboard-story__content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            padding: 0.95rem;
            color: #fff;
        }

        .ff-dashboard-story__badge {
            align-self: flex-end;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.94);
            color: #c63d26;
            padding: 0.35rem 0.65rem;
            font-size: 0.72rem;
            font-weight: 800;
        }

        .ff-dashboard-story__meta {
            display: grid;
            gap: 0.34rem;
        }

        .ff-dashboard-story__author {
            font-size: 0.92rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .ff-dashboard-story__time {
            font-size: 0.66rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.8);
        }

        .ff-dashboard-story__caption {
            font-size: 0.9rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .ff-dashboard-story--more {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(145deg, rgba(198, 61, 38, 0.12), rgba(209, 138, 22, 0.18));
            border: 1px dashed rgba(198, 61, 38, 0.28);
        }

        .ff-dashboard-story--more .ff-dashboard-story__overlay {
            background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.04));
        }

        .ff-dashboard-story--more .ff-dashboard-story__content {
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #1f2937;
            gap: 0.75rem;
        }

        .ff-dashboard-story__more-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #c63d26, #d18a16);
            color: #fff;
            font-size: 1rem;
            box-shadow: 0 12px 26px rgba(198, 61, 38, 0.18);
        }

        .ff-dashboard-story__more-title {
            font-size: 1rem;
            font-weight: 800;
            color: #1f2937;
        }

        .ff-dashboard-story__more-copy {
            font-size: 0.82rem;
            color: #64748b;
            line-height: 1.4;
        }

        @media (max-width: 1180px) {
            .ff-dashboard-stories {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 860px) {
            .ff-dashboard-stories {
                display: flex;
                overflow-x: auto;
                padding-bottom: 0.35rem;
                scroll-snap-type: x proximity;
            }

            .ff-dashboard-story {
                min-width: 180px;
                flex: 0 0 180px;
                scroll-snap-align: start;
            }
        }
    </style>
    <style data-ff-dashboard-assigned-incidents>
        .ff-assigned-incident-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.75rem;
            align-items: center;
            padding: 0.78rem 0;
            border-bottom: 1px solid rgba(226, 232, 240, 0.9);
            background: transparent;
            box-shadow: none;
            border-radius: 0;
        }

        .ff-assigned-incident-row:first-child {
            padding-top: 0.1rem;
        }

        .ff-assigned-incident-row:last-child {
            border-bottom: 0;
            padding-bottom: 0.1rem;
        }

        .ff-assigned-incident-row__main {
            appearance: none;
            border: 0;
            background: transparent;
            padding: 0;
            margin: 0;
            width: 100%;
            display: grid;
            grid-template-columns: 58px minmax(0, 1fr);
            gap: 0.75rem;
            align-items: start;
            text-align: left;
            cursor: pointer;
            min-width: 0;
        }

        .ff-assigned-incident-row__main:focus-visible {
            outline: 2px solid rgba(59, 130, 246, 0.22);
            outline-offset: 3px;
            border-radius: 0.9rem;
        }

        .ff-assigned-incident-row__media {
            width: 58px;
            height: 58px;
            min-width: 58px;
            min-height: 58px;
            border-radius: 0.9rem;
            overflow: hidden;
            background: linear-gradient(180deg, #fdf2e8 0%, #fff7ed 100%);
            border: 1px solid rgba(251, 191, 36, 0.16);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .ff-assigned-incident-row__media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .ff-assigned-incident-row__copy {
            min-width: 0;
            display: grid;
            gap: 0.26rem;
        }

        .ff-assigned-incident-row__badges {
            display: flex;
            align-items: center;
            gap: 0.38rem;
            flex-wrap: wrap;
        }

        .ff-assigned-incident-row__type,
        .ff-assigned-incident-row__status {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.22rem 0.5rem;
            border-radius: 999px;
            font-size: 0.62rem;
            line-height: 1;
            font-weight: 800;
            letter-spacing: 0.05em;
        }

        .ff-assigned-incident-row__type {
            background: rgba(255, 247, 237, 0.95);
            color: #c2410c;
        }

        .ff-assigned-incident-row__title {
            margin: 0;
            font-size: 0.96rem;
            line-height: 1.18;
            font-weight: 900;
            color: #0f172a;
        }

        .ff-assigned-incident-row__detail,
        .ff-assigned-incident-row__meta {
            display: flex;
            align-items: center;
            gap: 0.34rem;
            min-width: 0;
            color: #64748b;
            font-size: 0.74rem;
            line-height: 1.3;
        }

        .ff-assigned-incident-row__detail i {
            width: 0.8rem;
            color: #94a3b8;
            flex-shrink: 0;
            font-size: 0.72rem;
        }

        .ff-assigned-incident-row__detail span,
        .ff-assigned-incident-row__meta span {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ff-assigned-incident-row__meta {
            flex-wrap: wrap;
        }

        .ff-assigned-incident-row__meta-separator {
            color: #cbd5e1;
            font-size: 0.76rem;
            flex-shrink: 0;
        }

        .ff-assigned-incident-row__summary {
            margin: 0.08rem 0 0;
            color: #94a3b8;
            font-size: 0.72rem;
            line-height: 1.28;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .ff-assigned-incident-row__actions {
            display: flex;
            align-items: center;
            gap: 0.42rem;
            flex-wrap: nowrap;
            justify-content: flex-end;
            align-self: center;
            padding-left: 0.4rem;
            flex-shrink: 0;
            max-width: 100%;
        }

        .ff-assigned-incident-row__action {
            border: 1px solid rgba(226, 232, 240, 0.95);
            background: #fff;
            color: #0f172a;
            min-height: 34px;
            padding: 0 0.78rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.34rem;
            font-size: 0.72rem;
            font-weight: 800;
            line-height: 1;
            cursor: pointer;
            transition: background 0.16s ease, border-color 0.16s ease, transform 0.16s ease, opacity 0.16s ease;
            flex: 0 0 auto;
            white-space: nowrap;
            max-width: 100%;
        }

        .ff-assigned-incident-row__action:hover:not(:disabled) {
            transform: translateY(-1px);
        }

        .ff-assigned-incident-row__action:disabled {
            opacity: 0.62;
            cursor: not-allowed;
        }

        .ff-assigned-incident-row__action--primary {
            background: #10b981;
            border-color: #10b981;
            color: #fff;
        }

        .ff-assigned-incident-row__action--warn {
            border: 1px solid rgba(248, 113, 113, 0.28);
            background: linear-gradient(180deg, #fff5f5 0%, #fff 100%);
            color: #dc2626;
        }

        .ff-assigned-incident-row__action--muted {
            background: #f8fafc;
            color: #64748b;
        }

        .ff-assigned-incident-pagination {
            margin-top: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.8rem;
            flex-wrap: wrap;
            padding: 0.85rem 0.1rem 0;
        }

        .ff-assigned-incident-pagination__summary {
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .ff-assigned-incident-pagination__controls {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            flex-wrap: wrap;
        }

        .ff-assigned-incident-pagination__indicator {
            color: #334155;
            font-size: 0.76rem;
            font-weight: 800;
        }

        .ff-assigned-incident-pagination__button {
            border: 1px solid rgba(226, 232, 240, 0.95);
            background: #fff;
            color: #0f172a;
            border-radius: 999px;
            min-height: 40px;
            padding: 0 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.78rem;
            font-weight: 800;
            cursor: pointer;
            transition: transform 0.16s ease, box-shadow 0.16s ease, opacity 0.16s ease;
        }

        .ff-assigned-incident-pagination__button:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.08);
        }

        .ff-assigned-incident-pagination__button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            box-shadow: none;
        }

        .incident-media-fallback {
            display: grid;
            place-items: center;
            gap: 0.45rem;
            text-align: center;
            color: #c2410c;
            font-weight: 800;
            font-size: 0.8rem;
            padding: 1rem;
        }

        .incident-media-fallback i {
            font-size: 1.15rem;
        }

        @media (max-width: 1200px) {
            .ff-assigned-incident-row {
                grid-template-columns: 1fr;
                gap: 0.55rem;
            }

            .ff-assigned-incident-row__actions {
                justify-content: flex-start;
                padding-left: 66px;
            }
        }

        @media (max-width: 768px) {
            .ff-assigned-incident-row {
                gap: 0.5rem;
                padding: 0.62rem 0;
            }

            .ff-assigned-incident-row__main {
                grid-template-columns: 48px minmax(0, 1fr);
                gap: 0.58rem;
            }

            .ff-assigned-incident-row__media {
                width: 48px;
                height: 48px;
                border-radius: 0.72rem;
            }

            .ff-assigned-incident-row__title {
                font-size: 0.88rem;
            }

            .ff-assigned-incident-row__detail,
            .ff-assigned-incident-row__meta {
                font-size: 0.68rem;
            }

            .ff-assigned-incident-row__summary {
                font-size: 0.66rem;
                -webkit-line-clamp: 2;
            }

            .ff-assigned-incident-row__actions {
                padding-left: 56px;
                gap: 0.35rem;
            }

            .ff-assigned-incident-row__action {
                min-height: 30px;
                padding: 0 0.62rem;
                font-size: 0.66rem;
            }

            .ff-assigned-incident-row__action i {
                font-size: 0.64rem;
            }
        }
    </style>
</head>
<body class="firefighter-portal-shell firefighter-dashboard-page">
    @include('firefighters.partials.shell-sidebar', [
        'active' => 'dashboard',
        'sidebarId' => 'sidebar',
        'backdropId' => 'sidebarBackdrop',
    ])
    <div data-ff-page-root="dashboard">
    {{--
        <div class="sidebar-header-gradient px-6 py-8">
            <div class="ff-shell-brand flex items-center gap-3">
                <div class="ff-shell-brand-icon w-20 h-14 bg-white rounded-2xl flex items-center justify-center border border-white/60 shadow-sm px-2 py-1">
                    @include('partials.app-logo', ['class' => 'ff-shell-brand-logo w-full h-full object-contain'])
                </div>
                <div class="ff-shell-brand-copy">
                    <h2 class="ff-shell-brand-title text-white font-bold text-xl leading-tight">PyroSense</h2>
                    <p class="ff-shell-brand-subtitle text-white/70 text-xs uppercase tracking-widest">Responder Hub</p>
                </div>
            </div>
        </div>

        <nav class="mt-6 flex-1 px-4">
            <p class="px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Operations</p>
            <a href="{{ route('firefighter.dashboard') }}" class="nav-item active">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('firefighter.incidents') }}" class="nav-item">
                <i class="fas fa-fire-extinguisher"></i>
                <span>Incidents</span>
                @if($incidentCoords->count() > 0)
                <span class="ml-auto bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $incidentCoords->count() }}</span>
                @endif
            </a>
            <a href="{{ route('firefighter.my-station') }}" class="nav-item">
                <i class="fas fa-building"></i>
                <span>My Station</span>
            </a>
            <a href="{{ route('firefighter.equipments') }}" class="nav-item">
                <i class="fas fa-toolbox"></i>
                <span>Equipment</span>
            </a>

            <p class="px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-8 mb-4">Communications</p>
            <a href="{{ route('firefighter.announcements') }}" class="nav-item">
                <i class="fas fa-bullhorn"></i>
                <span>Announcements</span>
            </a>
            <a href="{{ route('firefighter.schedule') }}" class="nav-item">
                <i class="fas fa-calendar"></i>
                <span>My Schedule</span>
            </a>
        </nav>

        <div class="p-4 border-t border-slate-100">
            <div class="bg-red-50/50 rounded-2xl p-4 border border-red-100/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold border border-red-200">
                        {{ substr(auth()->user()->first_name ?? 'F', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'R', 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-slate-800 truncate">{{ auth()->user()->first_name }}</p>
                        <p class="text-[10px] text-slate-500 font-medium">On Duty • Station 7</p>
                    </div>
                </div>
            </div>
        </div>

    --}}
    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div id="firefighterMobileWelcomeToast" class="firefighter-mobile-welcome-toast" role="status" aria-live="polite">
            <div class="firefighter-mobile-welcome-toast__icon">
                <i class="fas fa-fire"></i>
            </div>
            <div class="firefighter-mobile-welcome-toast__copy">
                <strong>Welcome back, {{ auth()->user()->first_name }}!</strong>
                <span>Assigned calls and map updates are available.</span>
            </div>
        </div>
        <!-- TOP NAVIGATIONBAR -->
        <nav class="lector-top-bar">
            <div class="flex items-center gap-4">
                <button id="openSidebar" class="lg:hidden text-slate-600">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="hidden lg:block">
                    <h1 class="text-xl font-bold text-slate-800">Responder Command</h1>
                    <p class="text-xs text-slate-500 font-medium">Responder hub • {{ now()->format('l, F j') }}</p>
                </div>
            </div>

            <div class="flex items-center gap-4 sm:gap-5">
                <!-- Status Pill -->
                <div class="hidden sm:flex ff-topbar-status-pill">
                    <span>On Duty</span>
                </div>

                <!-- User Profile Dropdown -->
                <div class="user-dropdown">
                    <button id="userDropdownBtn" class="user-dropdown-toggle" type="button" aria-expanded="false" aria-controls="userDropdownMenu">
                        <div class="ff-topbar-profile-avatar">
                            @if ($dashboardProfileImageUrl)
                                <img src="{{ $dashboardProfileImageUrl }}" alt="{{ auth()->user()->first_name ?? 'Firefighter' }}">
                            @else
                                {{ substr(auth()->user()->first_name ?? 'F', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'R', 0, 1) }}
                            @endif
                        </div>
                        <span class="ff-topbar-profile-copy hidden sm:flex">
                            <span class="ff-topbar-profile-name">{{ auth()->user()->first_name ?? auth()->user()->name ?? 'Firefighter' }}</span>
                            <span class="ff-topbar-profile-role">Firefighter</span>
                        </span>
                        <i class="fas fa-chevron-down text-[10px] text-slate-400"></i>
                    </button>

                    <div id="userDropdownMenu" class="user-dropdown-menu">
                        <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                            <p class="text-sm font-bold text-slate-800">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
                            <p class="text-[10px] text-slate-500 font-medium">{{ auth()->user()->email }}</p>
                        </div>
                        <div class="p-2">
                            <a href="{{ route('firefighter.my-profile') }}" class="user-dropdown-item">
                                <i class="far fa-user"></i>
                                <span>My Profile</span>
                            </a>
                            <a href="#" class="user-dropdown-item" onclick="openMyLogsModal(); return false;">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                                <span>My Logs</span>
                            </a>
                            <div class="h-px bg-slate-100 my-2"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="user-dropdown-item text-red-600 w-full text-left">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        <!-- Stats Grid -->
        <div class="kpi-grid" data-ff-insights-source>
            <div class="lector-card kpi-card">
                <div class="kpi-icon bg-red-100 text-red-600">
                    <i class="fas fa-fire"></i>
                </div>
                <div class="kpi-info">
                    <h3>Active Incidents</h3>
                    <p id="activeIncidentsCount">--</p>
                </div>
            </div>

            <div class="lector-card kpi-card">
                <div class="kpi-icon bg-red-100 text-red-600">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="kpi-info">
                    <h3>Assigned to Me</h3>
                    <p id="assignedIncidentsCount">0</p>
                </div>
            </div>

            <div class="lector-card kpi-card">
                <div class="kpi-icon bg-red-100 text-red-600">
                    <i class="fas fa-toolbox"></i>
                </div>
                <div class="kpi-info">
                    <h3>Equipment</h3>
                    <p id="equipmentCount">--</p>
                </div>
            </div>

            <div class="lector-card kpi-card">
                <div class="kpi-icon bg-red-100 text-red-600">
                    <i class="fas fa-users"></i>
                </div>
                <div class="kpi-info">
                    <h3>Crew Online</h3>
                    <p id="crewOnlineCount">--</p>
                </div>
            </div>
        </div>
         <!-- Map Section -->
        <div class="lector-card mb-8 firefighter-map-stage">
            <div class="flex items-center justify-between mb-6 firefighter-map-stage__header">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center text-red-600 border border-red-100">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-800">Operations Map</h2>
                        <p class="text-xs text-slate-500 font-medium">Live incidents, stations, hydrants, and response markers</p>
                    </div>
                </div>
                <button id="refreshIncidents" class="px-3 py-1 bg-red-100 text-red-600 text-[10px] font-bold rounded-full uppercase tracking-wider border border-red-200 flex items-center gap-2 ff-map-sync-btn">
                    <i class="fas fa-sync-alt"></i> Sync Data
                </button>
            </div>
            <div class="map-container overflow-hidden rounded-2xl border border-slate-200">
                <div id="firefighterDashboardMap" class="bg-gray-200" style="height: 500px;"></div>
                <aside id="activeResponseUnitsPanel" class="ff-response-units" aria-live="polite" aria-label="Active response units">
                    <div class="ff-response-units__head"><span><i class="fas fa-truck-fast"></i> Response Units</span><strong id="activeResponseUnitsCount">0</strong></div>
                    <div class="ff-apparatus-tracking"><span id="apparatusTrackingState"><i class="fas fa-location-crosshairs"></i> Waiting for assigned driver run</span><button type="button" id="apparatusTrackingInstall" hidden><i class="fas fa-download"></i> Install app</button></div>
                    <div id="activeResponseUnitsList" class="ff-response-units__list"><p>No units currently dispatched.</p></div>
                </aside>
                <button type="button" class="ff-mobile-map-layers-button" data-ff-mobile-map-layers-button aria-label="Open map layers">
                    <i class="fas fa-layer-group" aria-hidden="true"></i>
                    <span data-ff-mobile-map-layers-count hidden>0</span>
                </button>
            </div>
            <div id="firefighterMapLoadState" class="ff-map-load-note">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Loading live map layers...</span>
            </div>
            <div id="firefighterMapLegend" class="ff-clean-map-legend firefighter-map-stage__legend"></div>
        </div>
        <!-- ASSIGNED INCIDENTS SECTION - DYNAMIC -->
        <div class="assigned-incidents-container ff-assigned-incidents-inline" data-ff-insights-source data-ff-insights-section="assigned">
            <div class="section-header">
                <h2>
                    <i class="fas fa-truck"></i>
                    Assigned Incidents
                </h2>
                <button id="refreshAssignedIncidents" class="view-all" aria-label="Refresh assigned incidents">
                    <i class="fas fa-rotate-right"></i><span class="ff-refresh-label">Refresh</span>
                </button>
            </div>
            <div id="assignedIncidentsList" class="incident-list">
                <div class="empty-incidents">
                    <i class="fas fa-truck text-4xl mb-2 opacity-50"></i>
                    <p>Loading assigned incidents...</p>
                </div>
            </div>
            <div id="assignedIncidentsPagination" class="ff-assigned-incident-pagination" hidden></div>
        </div>
<div id="routeInfoCard" class="route-info-card" style="display: none;" aria-live="polite">
        <div class="route-info-card__handle" aria-hidden="true"></div>
        <div class="route-card-header">
            <h4><i class="fas fa-route"></i> Route details</h4>
            <span id="routeSourceBadge" class="route-source-badge live">
                <i class="fas fa-location-crosshairs"></i><span>LIVE</span>
            </span>
        </div>
        <div class="route-info-card__body">
            <div class="info-row info-row--destination">
                <strong>Destination</strong> <span id="destinationTitle">Loading...</span>
            </div>
            <div class="route-info-card__stats">
                <div class="info-row">
                    <strong>Distance</strong> <span id="routeDistance">0 km</span>
                </div>
                <div class="info-row">
                    <strong>ETA</strong> <span id="routeETA">--:--</span>
                </div>
                <div class="info-row">
                    <strong>Duration</strong> <span id="routeDuration">0 min</span>
                </div>
            </div>
        </div>
        <div class="route-progress-container mt-3">
            <div id="routeProgress" class="route-progress-bar" style="width: 0%"></div>
        </div>
        <div class="speed-control mt-2">
            <button class="speed-btn" data-speed="slow">🐢 Slow</button>
            <button class="speed-btn active" data-speed="normal">🚒 Normal</button>
            <button class="speed-btn" data-speed="fast">🐇 Fast</button>
        </div>
        <button class="route-stop-btn btn btn-sm btn-outline mt-2 w-full" onclick="stopSimulation()">
            <i class="fas fa-stop"></i> Stop Simulation
        </button>
    </div>
        <!-- Route Information Card (hidden until route is calculated) -->
        {{-- <div id="routeInfoCard" class="route-info-card" style="display: none;">
            <div class="flex justify-between items-center">
                <h3 class="font-semibold">
                    <i class="fas fa-route text-blue-600"></i>
                    Active Route
                </h3>
                <button id="stopTracking" class="text-sm text-red-600 hover:text-red-700">
                    <i class="fas fa-stop"></i> Stop Tracking
                </button>
            </div>
            <div class="route-stats">
                <div class="route-stat">
                    <div class="route-stat-label">Distance</div>
                    <div class="route-stat-value" id="routeDistance">-- km</div>
                </div>
                <div class="route-stat">
                    <div class="route-stat-label">Duration</div>
                    <div class="route-stat-value" id="routeDuration">-- min</div>
                </div>
                <div class="route-stat">
                    <div class="route-stat-label">ETA</div>
                    <div class="route-stat-value" id="routeETA">--:--</div>
                </div>
            </div>
            <div class="travel-mode-buttons">
                <button class="travel-mode-btn active" data-mode="driving">
                    <i class="fas fa-car"></i> Driving
                </button>
                <button class="travel-mode-btn" data-mode="walking">
                    <i class="fas fa-walking"></i> Walking
                </button>
                <button class="travel-mode-btn" data-mode="cycling">
                    <i class="fas fa-bicycle"></i> Cycling
                </button>
            </div>
        </div> --}}

       
        <br>

<!-- Operations Analytics -->
<section class="ff-analytics-panel" data-ff-insights-source>
    <div class="ff-analytics-panel__header">
        <div>
            <span class="ff-section-eyebrow">Operations analytics</span>
            <h2>Incident overview</h2>
        </div>
        <p>Charts update from assigned incidents and live dashboard data.</p>
    </div>
    <div class="ff-analytics-grid">
        <article class="ff-chart-card">
            <div class="ff-chart-card__header">
                <div>
                    <span>Status mix</span>
                    <h3>Assigned incidents</h3>
                </div>
                <i class="fas fa-chart-pie"></i>
            </div>
            <canvas id="incidentStatusChart"></canvas>
            <p class="ff-chart-card__note">Reported, responding, controlled, and closed calls.</p>
        </article>

        <article class="ff-chart-card">
            <div class="ff-chart-card__header">
                <div>
                    <span>Last 7 days</span>
                    <h3>Response load</h3>
                </div>
                <i class="fas fa-chart-line"></i>
            </div>
            <canvas id="responseTimeChart"></canvas>
            <p class="ff-chart-card__note">Daily assigned fire incidents.</p>
        </article>

        <article class="ff-chart-card">
            <div class="ff-chart-card__header">
                <div>
                    <span>{{ now()->format('F Y') }}</span>
                    <h3>Monthly trend</h3>
                </div>
                <i class="fas fa-calendar-days"></i>
            </div>
            <canvas id="monthlyIncidentsChart"></canvas>
            <p class="ff-chart-card__note">Incidents grouped by week.</p>
        </article>

        <article class="ff-chart-card">
            <div class="ff-chart-card__header">
                <div>
                    <span>Readiness</span>
                    <h3>Action summary</h3>
                </div>
                <i class="fas fa-list-check"></i>
            </div>
            <canvas id="performanceChart"></canvas>
            <p class="ff-chart-card__note">Assigned, active, completed, and ready for report.</p>
        </article>
    </div>
</section>
        {{-- <div class="section-header">
            <h2>
                <i class="fas fa-fire"></i>
                Active Incidents
            </h2>
            @if ($incidentCoords->count() > 2)
            <a href="" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
            @endif
        </div> --}}

        {{-- <div class="grid-1">
            <div>Assigned Incidents</div>
            @foreach ($incidentCoords->take(2) as $incident)
            <div class="incident-card {{ strtolower($incident->priority ?? 'medium') }}">
                <div class="incident-header">
                    <span class="incident-type">
                        @if($incident->incidentType)
                            <i class="fas fa-fire"></i> {{ $incident->incidentType->type ?? 'Fire' }}
                        @else
                            <i class="fas fa-fire"></i> Fire
                        @endif
                    </span>
                    <span class="incident-priority priority-{{ strtolower($incident->priority ?? 'medium') }}">
                        {{ strtoupper($incident->priority ?? 'MEDIUM') }}
                    </span>
                </div>
                <h3 class="incident-title">{{ $incident->marker->title ?? 'Untitled Incident' }}</h3>
                <div class="incident-location">
                    <i class="fas fa-map-marker-alt"></i>
                    {{ $incident->marker->lat ?? 'N/A' }}, {{ $incident->marker->lng ?? 'N/A' }}
                </div>
                <div class="incident-meta">
                    <span><i class="far fa-clock"></i> {{ $incident->created_at->diffForHumans() }}</span>
                    <span><i class="fas fa-user"></i> {{ $incident->reporter ?? 'Dispatch' }}</span>
                </div>
                <div class="incident-actions">
                    <button class="btn btn-primary accept-incident" data-incident-id="{{ $incident->incident_id }}">
                        <i class="fas fa-check"></i> Accept & Navigate
                    </button>
                    <button class="btn btn-outline view-incident" data-incident-id="{{ $incident->incident_id }}">
                        <i class="far fa-eye"></i> Details
                    </button>
                </div>
            </div>
            @endforeach
        </div> --}}
        
        <!-- Announcements Section -->
        <div class="assigned-incidents-container" data-ff-insights-source>
            <div class="bg-white rounded-xl shadow p-5">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-bullhorn"></i>
                        Announcements
                    </h2>
                    <a href="{{ route('firefighter.announcements') }}" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div id="dashboardAnnouncementsList">
                    @php
                        $dashboardAnnouncementStories = collect($dashboardAnnouncementStories ?? [])->take(4)->values();
                    @endphp
                    @if($dashboardAnnouncementStories->isNotEmpty())
                        <div class="ff-dashboard-stories">
                            @foreach($dashboardAnnouncementStories as $post)
                                <a href="{{ route('firefighter.announcements') }}" class="ff-dashboard-story" aria-label="Open firefighter announcement from {{ $post['author_name'] ?? 'Station Update' }}">
                                    @if(!empty($post['image_url']))
                                        <div class="ff-dashboard-story__media">
                                            <img src="{{ $post['image_url'] }}" alt="{{ $post['author_name'] ?? 'Station Update' }}" loading="lazy">
                                        </div>
                                    @else
                                        <div class="ff-dashboard-story__fallback">{{ $post['author_initials'] ?? 'FF' }}</div>
                                    @endif
                                    <div class="ff-dashboard-story__overlay"></div>
                                    <div class="ff-dashboard-story__content">
                                        <span class="ff-dashboard-story__badge"><i class="fas fa-bullhorn"></i> Feed</span>
                                        <div class="ff-dashboard-story__meta">
                                            <div class="ff-dashboard-story__author">{{ $post['author_name'] ?? 'Station Update' }}</div>
                                            <div class="ff-dashboard-story__time">{{ $post['time'] ?? 'Just now' }}</div>
                                            <div class="ff-dashboard-story__caption">{{ \Illuminate\Support\Str::words($post['content'] ?? 'No content', 5, '...') }}</div>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                            <a href="{{ route('firefighter.announcements') }}" class="ff-dashboard-story ff-dashboard-story--more" aria-label="See more firefighter announcements">
                                <div class="ff-dashboard-story__overlay"></div>
                                <div class="ff-dashboard-story__content">
                                    <span class="ff-dashboard-story__more-icon"><i class="fas fa-arrow-right"></i></span>
                                    <div class="ff-dashboard-story__more-title">See more...</div>
                                    <div class="ff-dashboard-story__more-copy">Open the full station feed and view every announcement.</div>
                                </div>
                            </a>
                        </div>
                    @else
                        <div class="ff-shell-section-state">
                            <div class="ff-shell-skeleton ff-shell-skeleton-list"></div>
                            <div class="ff-shell-skeleton ff-shell-skeleton-list"></div>
                            <div class="ff-shell-skeleton ff-shell-skeleton-list"></div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Stations and Crew Grid -->
        <div class="grid-2" style="margin-top: 30px;" data-ff-insights-source>
            <!-- Nearby Stations -->
            <div>
                <div class="section-header">
                    <h2>
                        <i class="fas fa-building"></i>
                        Nearby Stations
                    </h2>
                    <a href="#" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div id="dashboardNearbyStationsList" style="grid-template-columns: 1fr;">
                    <div class="ff-shell-section-state">
                        <div class="ff-shell-skeleton ff-shell-skeleton-list"></div>
                        <div class="ff-shell-skeleton ff-shell-skeleton-list"></div>
                        <div class="ff-shell-skeleton ff-shell-skeleton-list"></div>
                    </div>
                </div>
            </div>

            <!-- Online Crew -->
            <div>
                <div class="section-header">
                    <h2>
                        <i class="fas fa-users"></i>
                        Online Crew
                    </h2>
                    <a href="#" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div id="dashboardCrewList" class="bg-white rounded-xl shadow p-5" data-shell-loading="true">
                    <div id="dashboardCrewShell" class="ff-shell-section-state">
                        <div class="ff-shell-skeleton ff-shell-skeleton-list"></div>
                        <div class="ff-shell-skeleton ff-shell-skeleton-list"></div>
                        <div class="ff-shell-skeleton ff-shell-skeleton-list"></div>
                    </div>
                    {{-- Crew list hydrates after the shell renders. --}}
                    @if(false)
                        <div class="crew-item">
                            <div class="crew-avatar">
                                {{ substr($crew->user->first_name ?? 'F', 0, 1) }}{{ substr($crew->user->last_name ?? 'F', 0, 1) }}
                                <span class="crew-status {{ $crew->status ?? 'online' }}"></span>
                            </div>
                            <div class="crew-info">
                                <div class="crew-name">{{ $crew->user->first_name ?? ' ' }}{{ $crew->user->last_name ?? 'Firefighter' }}</div>
                                <div class="crew-role">{{ $crew->rank ?? 'Firefighter' }} · Station {{$crew->station_id}}</div>
                            </div>
                            <i class="fas fa-chevron-right" style="color: #9ca3af;"></i>
                        </div>
                    @else
                        <div class="crew-item">
                            <div class="crew-avatar">
                                JD
                                <span class="crew-status online"></span>
                            </div>
                            <div class="crew-info">
                                <div class="crew-name">John Doe</div>
                                <div class="crew-role">Firefighter · Station 7</div>
                            </div>
                            <i class="fas fa-chevron-right" style="color: #9ca3af;"></i>
                        </div>
                        <div class="crew-item">
                            <div class="crew-avatar">
                                JS
                                <span class="crew-status online"></span>
                            </div>
                            <div class="crew-info">
                                <div class="crew-name">Jane Smith</div>
                                <div class="crew-role">Lieutenant · Station 7</div>
                            </div>
                            <i class="fas fa-chevron-right" style="color: #9ca3af;"></i>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-xs text-gray-400 mt-8 border-t pt-4" data-ff-insights-source>
            <i class="fas fa-user mr-1"></i> PyroSense · Firefighter Portal · Responding since {{ now()->format('H:i') }}
        </div>
    </main>

    <div class="firefighter-mobile-insights-panel md:hidden" id="firefighterMobileInsightsPanel" aria-hidden="true">
        <div class="firefighter-mobile-insights-panel__backdrop" data-ff-insights-close></div>
        <div class="firefighter-mobile-insights-panel__sheet">
            <div class="firefighter-mobile-insights-panel__header">
                <div>
                    <p class="firefighter-mobile-insights-panel__eyebrow" id="firefighterMobileInsightsEyebrow">Responder Hub</p>
                    <h2 class="firefighter-mobile-insights-panel__title" id="firefighterMobileInsightsTitle">Cards and Charts</h2>
                </div>
                <button type="button" class="firefighter-mobile-insights-panel__close" data-ff-insights-close aria-label="Close cards and charts">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            <div class="firefighter-mobile-insights-panel__content" id="firefighterMobileInsightsContent"></div>
        </div>
    </div>

    <button type="button" class="firefighter-mobile-assigned-fab" id="firefighterMobileAssignedFab" aria-label="Open assigned incidents queue">
        <span class="firefighter-mobile-assigned-fab__icon" aria-hidden="true"><i class="fas fa-truck-fast"></i></span>
        <span class="firefighter-mobile-assigned-fab__copy">
            <span class="firefighter-mobile-assigned-fab__label">Assigned Queue</span>
            <span class="firefighter-mobile-assigned-fab__meta">Open active incidents</span>
        </span>
        <span class="firefighter-mobile-assigned-fab__count" id="firefighterMobileAssignedFabCount">0</span>
    </button>

    <div class="firefighter-assigned-queue-modal" id="firefighterAssignedQueueModal" aria-hidden="true">
        <div class="firefighter-assigned-queue-modal__backdrop" data-ff-assigned-queue-close></div>
        <div class="firefighter-assigned-queue-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="firefighterAssignedQueueTitle">
            <div class="firefighter-assigned-queue-modal__header">
                <div>
                    <p class="firefighter-assigned-queue-modal__eyebrow">Active response queue</p>
                    <h2 class="firefighter-assigned-queue-modal__title" id="firefighterAssignedQueueTitle">Assigned Incidents</h2>
                </div>
                <div class="firefighter-assigned-queue-modal__header-actions">
                    <button type="button" class="firefighter-assigned-queue-modal__refresh" id="refreshAssignedIncidentsModal" aria-label="Refresh assigned incidents">
                        <i class="fas fa-rotate-right"></i>
                    </button>
                    <button type="button" class="firefighter-assigned-queue-modal__close" data-ff-assigned-queue-close aria-label="Close assigned incidents queue">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
            </div>
            <div class="firefighter-assigned-queue-modal__content" id="firefighterAssignedQueueModalContent"></div>
        </div>
    </div>

    <div class="firefighter-mobile-map-panel md:hidden" id="firefighterMobileMapLayersPanel" data-ff-map-layers-panel aria-hidden="true" inert>
        <div class="firefighter-mobile-map-panel__backdrop" data-ff-map-layers-close></div>
        <div class="firefighter-mobile-map-panel__sheet" role="dialog" aria-modal="false" aria-label="Map options">
            <div class="firefighter-mobile-map-panel__handle" aria-hidden="true"></div>
            <div class="firefighter-mobile-map-panel__header">
                <div>
                    <h2 class="firefighter-mobile-map-panel__title">Map options</h2>
                    <p class="firefighter-mobile-map-panel__subtitle">Show or hide details on the map</p>
                </div>
                <button type="button" class="firefighter-mobile-map-panel__close" data-ff-map-layers-close aria-label="Close map options">
                    <i class="fas fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <div class="firefighter-mobile-map-panel__body">
                <div class="firefighter-mobile-map-panel__base" role="radiogroup" aria-label="Map style">
                    <label class="firefighter-mobile-map-panel__base-item">
                        <input type="radio" name="firefighter_map_base" data-ff-map-base-toggle="Carto Light" checked>
                        <span>Carto</span>
                    </label>
                    <label class="firefighter-mobile-map-panel__base-item">
                        <input type="radio" name="firefighter_map_base" data-ff-map-base-toggle="OSM Standard">
                        <span>OSM</span>
                    </label>
                    <label class="firefighter-mobile-map-panel__base-item">
                        <input type="radio" name="firefighter_map_base" data-ff-map-base-toggle="OSM HOT">
                        <span>OSM HOT</span>
                    </label>
                    <label class="firefighter-mobile-map-panel__base-item">
                        <input type="radio" name="firefighter_map_base" data-ff-map-base-toggle="Satellite">
                        <span>Satellite</span>
                    </label>
                </div>

                <div class="firefighter-mobile-map-panel__layers">
                    <label class="firefighter-mobile-map-panel__chip">
                        <input type="checkbox" data-ff-map-layer-toggle="Fire Stations" checked>
                        <span class="firefighter-mobile-map-panel__chip-icon"><i class="fas fa-building-shield" aria-hidden="true"></i></span>
                        <span>Stations</span>
                        <i class="fas fa-check firefighter-mobile-map-panel__chip-check" aria-hidden="true"></i>
                    </label>
                    <label class="firefighter-mobile-map-panel__chip">
                        <input type="checkbox" data-ff-map-layer-toggle="Incidents" checked>
                        <span class="firefighter-mobile-map-panel__chip-icon"><i class="fas fa-fire-flame-curved" aria-hidden="true"></i></span>
                        <span>Incidents</span>
                        <i class="fas fa-check firefighter-mobile-map-panel__chip-check" aria-hidden="true"></i>
                    </label>
                    <label class="firefighter-mobile-map-panel__chip">
                        <input type="checkbox" data-ff-map-layer-toggle="Fire Hydrants" checked>
                        <span class="firefighter-mobile-map-panel__chip-icon"><i class="fas fa-droplet" aria-hidden="true"></i></span>
                        <span>Hydrants</span>
                        <i class="fas fa-check firefighter-mobile-map-panel__chip-check" aria-hidden="true"></i>
                    </label>
                    <label class="firefighter-mobile-map-panel__chip">
                        <input type="checkbox" data-ff-map-layer-toggle="Other Markers" checked>
                        <span class="firefighter-mobile-map-panel__chip-icon"><i class="fas fa-location-dot" aria-hidden="true"></i></span>
                        <span>Other</span>
                        <i class="fas fa-check firefighter-mobile-map-panel__chip-check" aria-hidden="true"></i>
                    </label>
                    <label class="firefighter-mobile-map-panel__chip">
                        <input type="checkbox" data-ff-map-layer-toggle="Fire Risk Areas" checked>
                        <span class="firefighter-mobile-map-panel__chip-icon"><i class="fas fa-map-location-dot" aria-hidden="true"></i></span>
                        <span>Risk areas</span>
                        <i class="fas fa-check firefighter-mobile-map-panel__chip-check" aria-hidden="true"></i>
                    </label>
                    <label class="firefighter-mobile-map-panel__chip">
                        <input type="checkbox" data-ff-map-layer-toggle="Water Reference">
                        <span class="firefighter-mobile-map-panel__chip-icon"><i class="fas fa-water" aria-hidden="true"></i></span>
                        <span>Water</span>
                        <i class="fas fa-check firefighter-mobile-map-panel__chip-check" aria-hidden="true"></i>
                    </label>
                </div>
                <details class="firefighter-mobile-map-panel__legend">
                    <summary>
                        <span>Map legend</span>
                        <i class="fas fa-chevron-down" aria-hidden="true"></i>
                    </summary>
                    <div class="firefighter-mobile-map-panel__legend-list">
                        <div class="firefighter-mobile-map-panel__legend-item">
                            <span class="firefighter-mobile-map-panel__legend-swatch firefighter-mobile-map-panel__legend-swatch-user"><i class="fas fa-user-shield" aria-hidden="true"></i></span>
                            <span>Your location</span>
                        </div>
                        <div class="firefighter-mobile-map-panel__legend-item">
                            <span class="firefighter-mobile-map-panel__legend-swatch"><img src="{{ asset('station.png') }}" alt="Fire station" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';"><i class="fas fa-building" aria-hidden="true" style="display:none;"></i></span>
                            <span>Fire station</span>
                        </div>
                        <div class="firefighter-mobile-map-panel__legend-item">
                            <span class="firefighter-mobile-map-panel__legend-swatch"><img src="{{ asset('hall.png') }}" alt="Barangay hall" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';"><i class="fas fa-users" aria-hidden="true" style="display:none;"></i></span>
                            <span>Barangay hall</span>
                        </div>
                        <div class="firefighter-mobile-map-panel__legend-item">
                            <span class="firefighter-mobile-map-panel__legend-swatch"><img src="{{ asset('fire.png') }}" alt="Incident" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';"><i class="fas fa-fire-flame-curved" aria-hidden="true" style="display:none;"></i></span>
                            <span>Incident</span>
                        </div>
                        <div class="firefighter-mobile-map-panel__legend-item">
                            <span class="firefighter-mobile-map-panel__legend-swatch"><img src="{{ asset('hydrant.png') }}" alt="Hydrant" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';"><i class="fas fa-droplet" aria-hidden="true" style="display:none;"></i></span>
                            <span>Hydrant</span>
                        </div>
                        <div class="firefighter-mobile-map-panel__legend-item">
                            <span class="firefighter-mobile-map-panel__legend-swatch firefighter-mobile-map-panel__legend-swatch-other"><i class="fas fa-location-dot" aria-hidden="true"></i></span>
                            <span>Other marker</span>
                        </div>
                        <div class="firefighter-mobile-map-panel__legend-item">
                            <span class="firefighter-mobile-map-panel__legend-swatch firefighter-mobile-map-panel__legend-swatch-other"><i class="fas fa-location-arrow" aria-hidden="true"></i></span>
                            <span>Navigation arrow</span>
                        </div>
                        <div class="firefighter-mobile-map-panel__legend-item">
                            <span class="firefighter-mobile-map-panel__legend-swatch"><img src="{{ asset('truck.png') }}" alt="Responding unit" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';"><i class="fas fa-truck-fast" aria-hidden="true" style="display:none;"></i></span>
                            <span>Dispatched apparatus</span>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </div>

    <!-- Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <h3>Update Status</h3>
            <div class="status-options">
                <button class="status-btn on-duty" data-status="on-duty">
                    <i class="fas fa-circle" style="color: #22c55e;"></i>
                    On Duty
                </button>
                <button class="status-btn on-break" data-status="on-break">
                    <i class="fas fa-mug-hot" style="color: #f97316;"></i>
                    On Break
                </button>
                <button class="status-btn off-duty" data-status="off-duty">
                    <i class="fas fa-clock" style="color: #ef4444;"></i>
                    Off Duty
                </button>
            </div>
            <button class="btn btn-outline" style="width: 100%; margin-top: 20px;" onclick="document.getElementById('statusModal').classList.remove('active')">
                Cancel
            </button>
        </div>
    </div>

    <script>
        window.firefighterDashboardConfig = {
            csrfToken: @json(csrf_token()),
            geoapifyKey: @json(env('GEOAPIFY_KEY')),
            userId: @json(auth()->user()->user_id ?? null),
            currentFirefighterId: @json(auth()->user()->user_id ?? null),
            currentUserId: @json(auth()->user()->user_id ?? null),
            userTeamId: @json(isset($userTeam) ? $userTeam->team_id : null),
            userStationId: @json(isset($thisStation) ? $thisStation : null),
            dashboardDataUrl: @json(route('firefighter.dashboard.data')),
            dashboardPostsUrl: @json(route('firefighter.dashboard.posts')),
            mapInstanceConfig: {
                containerId: 'firefighterDashboardMap',
                instanceKey: 'firefighterDashboard'
            }
        };
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @vite(['resources/js/map.js', 'resources/js/firefighter/dashboard-shell.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const mapLayersPanel = document.querySelector('[data-ff-map-layers-panel]');
            const mapLayerCloseButtons = document.querySelectorAll('[data-ff-map-layers-close]');
            const mobileLayerButtons = document.querySelectorAll('[data-ff-mobile-map-layers-button]');
            let lastLayerTapAt = 0;

            function closeFirefighterInlineInsightsPanel() {
                if (typeof window.closeFirefighterInsightsPanel === 'function') {
                    window.closeFirefighterInsightsPanel();
                    return;
                }

                const insightsPanel = document.getElementById('firefighterMobileInsightsPanel');
                if (!insightsPanel) return;

                if (insightsPanel.contains(document.activeElement)) {
                    document.activeElement?.blur?.();
                }

                insightsPanel.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('menu-open');
            }

            function openFirefighterInlineMapLayersPanel() {
                if (!mapLayersPanel || window.innerWidth > 768) return;

                document.querySelector('[data-ff-mobile-dock]')?.classList.remove('is-burst-open');
                document.querySelectorAll('[data-ff-notifications-panel], [data-ff-settings-panel]').forEach(function (panel) {
                    if (panel.contains(document.activeElement)) {
                        document.activeElement?.blur?.();
                    }

                    panel.classList.remove('is-open');
                    panel.setAttribute('aria-hidden', 'true');
                });

                closeFirefighterInlineInsightsPanel();
                window.syncPyroSharedMapLayerControls?.();
                mapLayersPanel.removeAttribute('inert');
                mapLayersPanel.setAttribute('aria-hidden', 'false');
                mapLayersPanel.classList.add('is-open');
            }

            function closeFirefighterInlineMapLayersPanel() {
                if (!mapLayersPanel) return;

                if (mapLayersPanel.contains(document.activeElement)) {
                    document.activeElement?.blur?.();
                }

                mapLayersPanel.classList.remove('is-open');
                mapLayersPanel.setAttribute('aria-hidden', 'true');
                mapLayersPanel.setAttribute('inert', '');
            }

            function handleFirefighterLeafletLayerTap(event) {
                if (window.innerWidth > 768 || !mapLayersPanel) return;

                const control = event.target?.closest?.('.firefighter-map-stage .leaflet-control-layers');
                if (!control) return;

                event.__ffMapLayerControlHandled = true;
                event.preventDefault();
                event.stopImmediatePropagation?.();
                event.stopPropagation();

                if (event.type === 'click' && Date.now() - lastLayerTapAt < 450) return;

                lastLayerTapAt = Date.now();
                control.classList.remove('leaflet-control-layers-expanded');
                openFirefighterInlineMapLayersPanel();
            }

            window.openFirefighterMapLayersPanel = openFirefighterInlineMapLayersPanel;
            window.closeFirefighterMapLayersPanel = closeFirefighterInlineMapLayersPanel;

            mapLayerCloseButtons.forEach(function (button) {
                button.addEventListener('click', closeFirefighterInlineMapLayersPanel);
            });

            mobileLayerButtons.forEach(function (button) {
                const openFromButton = function (event) {
                    if (window.innerWidth > 768) return;

                    event.preventDefault();
                    event.stopImmediatePropagation?.();
                    event.stopPropagation();
                    openFirefighterInlineMapLayersPanel();
                };

                button.addEventListener('pointerdown', openFromButton, true);
                button.addEventListener('touchstart', openFromButton, { capture: true, passive: false });
                button.addEventListener('click', openFromButton, true);
            });

            document.addEventListener('pointerdown', handleFirefighterLeafletLayerTap, true);
            document.addEventListener('touchstart', handleFirefighterLeafletLayerTap, { capture: true, passive: false });
            document.addEventListener('click', handleFirefighterLeafletLayerTap, true);
        });
    </script>
    {{--
    @vite(['resources/js/map.js'])

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Pass data to JavaScript
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    window.mapInstanceConfig = {
        containerId: 'firefighterDashboardMap',
        instanceKey: 'firefighterDashboard'
    };
    window.deferPyroMapInit = true;
    window.__ffAllowDashboardPrefetch = false;
    window.geoapifyKey = '{{ env("GEOAPIFY_KEY") }}';
    window.userId = {{ auth()->user()->user_id ?? 'null' }};
    window.userTeamId = {{ isset($userTeam) ? $userTeam->team_id : 'null' }};
    window.userStationId = {{ isset($thisStation) ? $thisStation : 'null' }};
    const dashboardDataUrl = @json(route('firefighter.dashboard.data'));
    const dashboardPostsUrl = @json(route('firefighter.dashboard.posts'));
    window.allMarkers = [];
    window.mapData = { marker: window.allMarkers };
    window.stationsData = [];
    window.incidentCoords = [];
    window.currentFirefighterId = {{ auth()->user()->user_id ?? 'null' }};
    window.currentUserId = {{ auth()->user()->user_id ?? 'null' }};
    const FF_LOCATION_PUSH_INTERVAL_MS = 60000;
    const FF_LOCATION_PULL_INTERVAL_MS = 90000;
    const FF_ASSIGNED_INCIDENTS_INTERVAL_MS = 30000;
    const FF_AUTO_ASSIGN_INTERVAL_MS = 300000;
    const FF_WIDGET_CACHE_KEY = `ff_dashboard_widgets_v2_${window.currentFirefighterId}`;
    const FF_POSTS_CACHE_KEY = `ff_dashboard_posts_v2_${window.currentFirefighterId}`;
    const FF_ASSIGNED_CACHE_KEY = `ff_dashboard_assigned_v2_${window.currentFirefighterId}`;
    const FF_REVERSE_GEOCODE_CACHE_KEY = `ff_dashboard_reverse_geocode_v1_${window.currentFirefighterId || 'guest'}`;
    let ffLocationPushInterval = null;
    let ffLocationPullInterval = null;
    let ffAssignedIncidentsInterval = null;
    let ffAutoAssignInterval = null;
    let ffDashboardWidgetsRequest = null;
    let ffDashboardPostsRequest = null;
    const FF_FETCH_TIMEOUT_MS = 10000;
    const FF_ASSIGNED_INCIDENTS_TIMEOUT_MS = 20000;
    let ffAssignedIncidentsRequest = null;
    let ffAutoAssignRequest = null;
    let ffAssignedIncidentsLastFetchedAt = 0;

    function firefighterDashboardIsVisible() {
        return document.visibilityState === 'visible';
    }

    function clearFirefighterDashboardIntervals() {
        if (ffLocationPushInterval) {
            clearInterval(ffLocationPushInterval);
            ffLocationPushInterval = null;
        }
        if (ffLocationPullInterval) {
            clearInterval(ffLocationPullInterval);
            ffLocationPullInterval = null;
        }
        if (ffAssignedIncidentsInterval) {
            clearInterval(ffAssignedIncidentsInterval);
            ffAssignedIncidentsInterval = null;
        }
        if (ffAutoAssignInterval) {
            clearInterval(ffAutoAssignInterval);
            ffAutoAssignInterval = null;
        }
    }

    function startFirefighterDashboardIntervals() {
        clearFirefighterDashboardIntervals();

        ffLocationPushInterval = setInterval(() => {
            if (!firefighterDashboardIsVisible()) return;

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    updateUserLocation(window.currentUserId, lat, lng);
                    sendUserLocationToServer(lat, lng);

                    if (window.userMarker) {
                        window.userMarker.setLatLng([lat, lng]);
                    }
                },
                function(error) {
                    console.error("Location update error:", error);
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }, FF_LOCATION_PUSH_INTERVAL_MS);

        ffLocationPullInterval = setInterval(() => {
            if (!firefighterDashboardIsVisible()) return;
            fetchAllFirefighterLocations();
        }, FF_LOCATION_PULL_INTERVAL_MS);

        ffAssignedIncidentsInterval = setInterval(() => {
            if (!firefighterDashboardIsVisible()) return;
            fetchAssignedIncidents();
        }, FF_ASSIGNED_INCIDENTS_INTERVAL_MS);

        ffAutoAssignInterval = setInterval(() => {
            if (!firefighterDashboardIsVisible()) return;
            window.runPendingIncidentAutoAssignment();
        }, FF_AUTO_ASSIGN_INTERVAL_MS);
    }

    function fetchFirefighterDashboardOnce(currentRequest, url, options = {}) {
        if (currentRequest) {
            return currentRequest;
        }

        const controller = new AbortController();
        const timeoutMs = Number(options.timeoutMs || FF_FETCH_TIMEOUT_MS);
        const { timeoutMs: _timeoutMs, ...fetchOptions } = options;
        const timeoutId = window.setTimeout(() => controller.abort(), timeoutMs);
        const requestPromise = fetch(url, {
            cache: 'no-store',
            ...fetchOptions,
            signal: controller.signal
        }).finally(() => {
            window.clearTimeout(timeoutId);
        });

        return requestPromise;
    }

    function readDashboardCache(key, fallback = null) {
        try {
            const raw = window.localStorage?.getItem(key);
            return raw ? JSON.parse(raw) : fallback;
        } catch (error) {
            console.warn('Unable to read dashboard cache:', key, error);
            return fallback;
        }
    }

    function writeDashboardCache(key, value) {
        try {
            window.localStorage?.setItem(key, JSON.stringify(value));
            window.pyroPwa?.updatePageSnapshot?.({
                key: 'firefighter-dashboard-v2',
                label: 'Firefighter Dashboard'
            });
        } catch (error) {
            console.warn('Unable to write dashboard cache:', key, error);
        }
    }

    function renderSectionError(targetId, message, retryHandler) {
        const target = document.getElementById(targetId);
        if (!target) return;

        target.innerHTML = `
            <div class="ff-shell-section-error">
                <div>${escapeHtml(message)}</div>
                <button type="button" data-retry-action="${escapeHtml(retryHandler)}">Retry</button>
            </div>
        `;

        target.querySelector('[data-retry-action]')?.addEventListener('click', function () {
            const actionName = this.getAttribute('data-retry-action');
            if (actionName && typeof window[actionName] === 'function') {
                window[actionName](true);
            }
        });
    }

    function renderDashboardAnnouncements(posts = []) {
        const container = document.getElementById('dashboardAnnouncementsList');
        if (!container) return;

        if (!Array.isArray(posts) || posts.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="far fa-comment-dots text-3xl mb-2"></i>
                    <p>No announcements yet.</p>
                </div>
            `;
            return;
        }

        const visiblePosts = posts.slice(0, 4);
        const fullFeedUrl = @json(route('firefighter.announcements'));

        const truncateWords = (value, limit = 5) => {
            const cleaned = String(value || '').replace(/\s+/g, ' ').trim();
            if (!cleaned) {
                return 'No update yet.';
            }

            const words = cleaned.split(' ');
            if (words.length <= limit) {
                return cleaned;
            }

            return `${words.slice(0, limit).join(' ')}...`;
        };

        const storyCards = visiblePosts.map((post) => {
            const imageUrl = post.image_url || null;
            const authorInitials = escapeHtml(post.author_initials || 'FF');
            const authorName = escapeHtml(post.author_name || 'Station Update');
            const time = escapeHtml(post.time || 'Just now');
            const caption = escapeHtml(truncateWords(post.content || 'No content', 5));

            const mediaMarkup = imageUrl
                ? `<div class="ff-dashboard-story__media"><img src="${escapeHtml(imageUrl)}" alt="${authorName}" loading="lazy" onerror="this.closest('.ff-dashboard-story__media')?.remove();"></div>`
                : `<div class="ff-dashboard-story__fallback">${authorInitials}</div>`;

            return `
                <a href="${fullFeedUrl}" class="ff-dashboard-story" aria-label="Open firefighter announcement from ${authorName}">
                    ${mediaMarkup}
                    <div class="ff-dashboard-story__overlay"></div>
                    <div class="ff-dashboard-story__content">
                        <span class="ff-dashboard-story__badge"><i class="fas fa-bullhorn"></i> Feed</span>
                        <div class="ff-dashboard-story__meta">
                            <div class="ff-dashboard-story__author">${authorName}</div>
                            <div class="ff-dashboard-story__time">${time}</div>
                            <div class="ff-dashboard-story__caption">${caption}</div>
                        </div>
                    </div>
                </a>
            `;
        });

        storyCards.push(`
            <a href="${fullFeedUrl}" class="ff-dashboard-story ff-dashboard-story--more" aria-label="See more firefighter announcements">
                <div class="ff-dashboard-story__overlay"></div>
                <div class="ff-dashboard-story__content">
                    <span class="ff-dashboard-story__more-icon"><i class="fas fa-arrow-right"></i></span>
                    <div class="ff-dashboard-story__more-title">See more...</div>
                    <div class="ff-dashboard-story__more-copy">Open the full station feed and view every announcement.</div>
                </div>
            </a>
        `);

        container.innerHTML = `<div class="ff-dashboard-stories">${storyCards.join('')}</div>`;
    }

    function renderDashboardNearbyStations(stations = []) {
        const container = document.getElementById('dashboardNearbyStationsList');
        if (!container) return;

        if (!Array.isArray(stations) || stations.length === 0) {
            container.innerHTML = `
                <div class="bg-white rounded-xl shadow p-5 text-center text-gray-500">
                    <p>No nearby stations available.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = stations.map((station) => `
            <div class="bg-white rounded-xl shadow p-5 mb-3">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold text-slate-800">${escapeHtml(station.station_name || 'Station')}</p>
                        <p class="text-xs text-slate-500 font-medium">Estimated distance ${escapeHtml(station.distance || '0.0')} km</p>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-bold text-emerald-700 border border-emerald-100">
                        <i class="fas fa-location-arrow"></i> Nearby
                    </span>
                </div>
            </div>
        `).join('');
    }

    function renderDashboardCrew(crew = []) {
        const shell = document.getElementById('dashboardCrewShell');
        const container = shell?.parentElement || document.getElementById('dashboardCrewList');
        if (!container) return;

        container.removeAttribute('data-shell-loading');

        if (!Array.isArray(crew) || crew.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <p>No crew records available.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = crew.map((member) => `
            <div class="crew-item">
                <div class="crew-avatar">
                    ${escapeHtml(member.initials || 'FF')}
                    <span class="crew-status ${escapeHtml(member.status || 'online')}"></span>
                </div>
                <div class="crew-info">
                    <div class="crew-name">${escapeHtml(member.name || 'Firefighter')}</div>
                    <div class="crew-role">${escapeHtml(member.role || 'Responder')}</div>
                </div>
                <i class="fas fa-chevron-right" style="color: #9ca3af;"></i>
            </div>
        `).join('');
    }

    function applyDashboardWidgetPayload(payload = {}) {
        const counts = payload.counts || {};
        const context = payload.context || {};
        if (context.station_id !== undefined && context.station_id !== null) {
            window.userStationId = context.station_id;
        }
        if (context.user_team_id !== undefined && context.user_team_id !== null) {
            window.userTeamId = context.user_team_id;
        }
        document.getElementById('activeIncidentsCount').textContent = counts.active_incidents ?? '--';
        document.getElementById('equipmentCount').textContent = counts.equipment ?? '--';
        document.getElementById('crewOnlineCount').textContent = counts.crew_online ?? '--';

        renderDashboardNearbyStations(payload.stations || []);
        renderDashboardCrew(payload.crew || []);
    }

    async function loadFirefighterDashboardWidgets(force = false) {
        try {
            if (!force && ffDashboardWidgetsRequest) {
                return ffDashboardWidgetsRequest;
            }

            ffDashboardWidgetsRequest = fetchFirefighterDashboardOnce(
                ffDashboardWidgetsRequest,
                dashboardDataUrl,
                { timeoutMs: 15000 }
            );

            const response = await ffDashboardWidgetsRequest;
            if (!response.ok) {
                throw new Error('Failed to load dashboard widgets');
            }

            const result = await response.json();
            const payload = result?.data || {};
            applyDashboardWidgetPayload(payload);
            writeDashboardCache(FF_WIDGET_CACHE_KEY, payload);
            return payload;
        } catch (error) {
            console.error('Error loading firefighter dashboard widgets:', error);
            renderSectionError('dashboardNearbyStationsList', 'Unable to refresh nearby stations right now.', 'loadFirefighterDashboardWidgets');
            renderSectionError('dashboardCrewList', 'Unable to refresh crew details right now.', 'loadFirefighterDashboardWidgets');
            return null;
        } finally {
            ffDashboardWidgetsRequest = null;
        }
    }

    window.loadFirefighterDashboardWidgets = loadFirefighterDashboardWidgets;

    async function loadFirefighterDashboardPosts(force = false) {
        try {
            if (!force && ffDashboardPostsRequest) {
                return ffDashboardPostsRequest;
            }

            ffDashboardPostsRequest = fetchFirefighterDashboardOnce(
                ffDashboardPostsRequest,
                dashboardPostsUrl,
                { timeoutMs: 15000 }
            );

            const response = await ffDashboardPostsRequest;
            if (!response.ok) {
                throw new Error('Failed to load dashboard posts');
            }

            const result = await response.json();
            const posts = Array.isArray(result?.data?.posts) ? result.data.posts : [];
            renderDashboardAnnouncements(posts);
            writeDashboardCache(FF_POSTS_CACHE_KEY, posts);
            return posts;
        } catch (error) {
            console.error('Error loading firefighter dashboard posts:', error);
            renderSectionError('dashboardAnnouncementsList', 'Unable to refresh announcements right now.', 'loadFirefighterDashboardPosts');
            return [];
        } finally {
            ffDashboardPostsRequest = null;
        }
    }

    window.loadFirefighterDashboardPosts = loadFirefighterDashboardPosts;

    // Function to fetch assigned incidents
    async function fetchAssignedIncidents(force = false) {
        try {
            if (!force && !firefighterDashboardIsVisible()) {
                return Array.isArray(window.assignedIncidentsData) ? window.assignedIncidentsData : [];
            }
            const now = Date.now();
            if (!force && ffAssignedIncidentsRequest) {
                return ffAssignedIncidentsRequest;
            }
            if (!force && ffAssignedIncidentsLastFetchedAt && (now - ffAssignedIncidentsLastFetchedAt) < FF_ASSIGNED_INCIDENTS_INTERVAL_MS) {
                return Array.isArray(window.assignedIncidentsData) ? window.assignedIncidentsData : [];
            }
            ffAssignedIncidentsRequest = fetchFirefighterDashboardOnce(
                ffAssignedIncidentsRequest,
                `/firefighter/assigned/${window.currentFirefighterId}`,
                { timeoutMs: FF_ASSIGNED_INCIDENTS_TIMEOUT_MS }
            );
            const response = await ffAssignedIncidentsRequest;
            if (!response.ok) throw new Error('Failed to fetch assigned incidents');
            
            const result = await response.json();
            console.log('API Response:', result);
            
            const incidents = result.data || [];
            console.log('Incidents data:', incidents);
            window.assignedIncidentsData = incidents;
            ffAssignedIncidentsLastFetchedAt = Date.now();
            writeDashboardCache(FF_ASSIGNED_CACHE_KEY, incidents);
            
            displayAssignedIncidents(incidents);
            if (typeof updateChartsWithData === 'function') {
                updateChartsWithData(incidents);
            }
            
            document.getElementById('assignedIncidentsCount').textContent = incidents.length;
            
            return incidents;
        } catch (error) {
            if (error?.name === 'AbortError') {
                console.warn('Assigned incidents request timed out before completion.');
                return Array.isArray(window.assignedIncidentsData) ? window.assignedIncidentsData : [];
            }
            console.error('Error fetching assigned incidents:', error);
            renderSectionError('assignedIncidentsList', 'Failed to load assigned incidents.', 'refreshFirefighterAssignedIncidents');
            return [];
        } finally {
            ffAssignedIncidentsRequest = null;
        }
    }

    window.refreshFirefighterAssignedIncidents = fetchAssignedIncidents;

    // Compatibility handler for the inline Stop Simulation button.
    // map.js exposes stopAllSimulations / stopSimulationForRoute globally.
    async function stopSimulation() {
        try {
            if (typeof window.stopAllSimulations === 'function') {
                await window.stopAllSimulations();
            } else if (typeof window.stopSimulationForRoute === 'function' && window.activeRoute?.route_id) {
                await window.stopSimulationForRoute(window.activeRoute.route_id);
            }
        } catch (error) {
            console.error('Error stopping simulation:', error);
        } finally {
            const routeCard = document.getElementById('routeInfoCard');
            if (routeCard) routeCard.style.display = 'none';
        }
    }
    
    function escapeHtml(value) {
        if (value === null || value === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(value);
        return div.innerHTML;
    }

    function formatHumanDateTime(value) {
        if (!value) return 'Unknown';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return 'Unknown';
        return date.toLocaleString(undefined, {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    function formatReporterLabel(incident) {
        const reporter = incident?.reporter;
        const reporterRole = String(reporter?.role?.role || '').trim().toLowerCase();
        const firstName = String(reporter?.first_name || '').trim();
        const lastName = String(reporter?.last_name || '').trim();
        const fullName = `${firstName} ${lastName}`.trim() || String(reporter?.name || '').trim();

        if (reporterRole === 'citizen' || (!fullName && incident?.reported_by)) {
            return 'Concerned Citizen';
        }

        return fullName || 'Concerned Citizen';
    }

    function resolveStorageUrl(path) {
        const normalizedPath = String(path || '').trim();
        if (!normalizedPath) return null;
        if (/^(https?:)?\/\//i.test(normalizedPath) || normalizedPath.startsWith('/storage/')) {
            return normalizedPath;
        }

        return `/storage/${normalizedPath.replace(/^\/+/, '')}`;
    }

    function getIncidentImageUrl(incident) {
        const multimediaPath = incident?.marker?.multimedia?.path;
        return resolveStorageUrl(multimediaPath);
    }

    function getIncidentTypeIconClass(typeName = '') {
        const normalized = String(typeName).trim().toLowerCase();
        if (normalized.includes('medical')) return 'fa-truck-medical';
        if (normalized.includes('rescue')) return 'fa-life-ring';
        if (normalized.includes('hazard')) return 'fa-radiation';
        if (normalized.includes('false')) return 'fa-times-circle';
        if (normalized.includes('accident')) return 'fa-car-crash';
        if (normalized.includes('disaster')) return 'fa-house-damage';
        return 'fa-fire';
    }

    function getIncidentStatusId(incident = {}) {
        if (incident?.incident_status_id !== undefined && incident?.incident_status_id !== null) {
            return Number(incident.incident_status_id);
        }

        const status = String(incident?.incident_status || incident?.status || '').trim().toLowerCase();
        if (status === 'en-route' || status === 'responding') return 2;
        if (status === 'on-scene' || status === 'on scene') return 3;
        if (status === 'completed' || status === 'controlled') return 4;
        if (status === 'closed') return 5;
        return 1;
    }

    function getIncidentTypeLabel(incident = {}) {
        return incident?.incident_type_name
            || incident?.incident_type
            || incident?.incidentType?.type
            || incident?.incident_type?.type
            || (incident?.marker?.category === 'fire' ? 'Fire' : 'Incident');
    }

    function getReverseGeocodeCache() {
        return readDashboardCache(FF_REVERSE_GEOCODE_CACHE_KEY, {}) || {};
    }

    function setReverseGeocodeCache(nextCache) {
        writeDashboardCache(FF_REVERSE_GEOCODE_CACHE_KEY, nextCache && typeof nextCache === 'object' ? nextCache : {});
    }

    function buildReverseGeocodeKey(lat, lng) {
        return `${Number(lat).toFixed(5)},${Number(lng).toFixed(5)}`;
    }

    async function reverseGeocodeIncidentLocation(lat, lng) {
        if (lat === null || lat === undefined || lng === null || lng === undefined) {
            return 'Location not specified';
        }

        const cacheKey = buildReverseGeocodeKey(lat, lng);
        const cache = getReverseGeocodeCache();
        if (cache[cacheKey]?.label) {
            return cache[cacheKey].label;
        }

        try {
            const response = await fetch('/api/reverse', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ lat, lon: lng }),
            });

            if (!response.ok) {
                throw new Error('Reverse geocode request failed');
            }

            const payload = await response.json();
            const formatted = payload?.features?.[0]?.properties?.formatted
                || payload?.features?.[0]?.properties?.address_line1
                || payload?.features?.[0]?.properties?.address_line2
                || `${lat}, ${lng}`;

            cache[cacheKey] = {
                label: formatted,
                saved_at: new Date().toISOString(),
            };
            setReverseGeocodeCache(cache);

            return formatted;
        } catch (error) {
            console.warn('Assigned incident reverse geocode failed:', error);
            return `${lat}, ${lng}`;
        }
    }

    async function hydrateAssignedIncidentLocations(incidents = []) {
        const tasks = incidents
            .filter((incident) => incident?.marker?.lat !== null && incident?.marker?.lat !== undefined && incident?.marker?.lng !== null && incident?.marker?.lng !== undefined)
            .map(async (incident) => {
                const label = await reverseGeocodeIncidentLocation(incident.marker.lat, incident.marker.lng);
                const target = document.querySelector(`[data-incident-location="${incident.incident_id}"]`);
                if (target && label) {
                    target.textContent = label;
                    target.setAttribute('title', label);
                }
            });

        await Promise.allSettled(tasks);
    }

    function setIncidentActionPendingState(incidentId, options = {}) {
        const card = document.querySelector(`.incident-card [data-incident-id="${incidentId}"]`)?.closest('.incident-card')
            || document.querySelector(`.incident-card .view-incident-details[data-incident-id="${incidentId}"]`)?.closest('.incident-card');
        if (!card) return null;

        const buttons = Array.from(card.querySelectorAll('button[data-incident-id]'));
        const statusBadge = card.querySelector('.incident-status-badge');
        const previous = {
            buttonStates: buttons.map((button) => ({
                button,
                disabled: button.disabled,
                html: button.innerHTML,
            })),
            statusText: statusBadge?.textContent || null,
            statusClass: statusBadge?.className || null,
        };

        buttons.forEach((button) => {
            button.disabled = true;
            button.dataset.pending = 'true';
            if (!button.classList.contains('view-incident-details')) {
                button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${options.buttonLabel || 'Updating...'}`;
            }
        });

        if (statusBadge) {
            statusBadge.textContent = options.badgeLabel || 'UPDATING';
            statusBadge.classList.add('opacity-80');
        }

        card.dataset.pendingIncidentAction = 'true';
        return previous;
    }

    function restoreIncidentActionPendingState(incidentId, previousState) {
        const card = document.querySelector(`.incident-card [data-incident-id="${incidentId}"]`)?.closest('.incident-card')
            || document.querySelector(`.incident-card .view-incident-details[data-incident-id="${incidentId}"]`)?.closest('.incident-card');
        if (!card || !previousState) return;

        previousState.buttonStates.forEach(({ button, disabled, html }) => {
            button.disabled = disabled;
            button.innerHTML = html;
            delete button.dataset.pending;
        });

        const statusBadge = card.querySelector('.incident-status-badge');
        if (statusBadge && previousState.statusClass) {
            statusBadge.className = previousState.statusClass;
            statusBadge.textContent = previousState.statusText || statusBadge.textContent;
        }

        delete card.dataset.pendingIncidentAction;
    }

    function displayAssignedIncidents(incidents) {
    const container = document.getElementById('assignedIncidentsList');
    
    if (!incidents || incidents.length === 0) {
        container.innerHTML = `
            <div class="empty-incidents">
                <i class="fas fa-truck text-4xl mb-2 opacity-50"></i>
                <p>No assigned incidents</p>
                <p class="text-sm">You haven't been assigned to any incidents yet.</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    incidents.forEach(incident => {
        const statusId = getIncidentStatusId(incident);
        let statusClass = 'status-reported';
        let statusText = 'REPORTED';
        let actionButton = '';
        let falseAlarmButton = '';
        let backupButton = '';
        
        if (statusId == 1) {
            statusClass = 'status-reported';
            statusText = 'REPORTED';
            actionButton = `
                <button type="button" class="respond-incident ff-assigned-incident-row__action ff-assigned-incident-row__action--primary" data-incident-id="${incident.incident_id}" data-status="${statusId}">
                    <i class="fas fa-truck-fast"></i><span>Respond</span>
                </button>
            `;
        } else if (statusId == 2) {
            statusClass = 'status-responding';
            statusText = 'RESPONDING';
            actionButton = `
                <button type="button" class="ff-assigned-incident-row__action ff-assigned-incident-row__action--muted" disabled>
                    <i class="fas fa-truck-fast"></i><span>En Route</span>
                </button>
            `;
        } else if (statusId == 3) {
            statusClass = 'status-resolved';
            statusText = 'ON SCENE';
            actionButton = `
                <button type="button" class="complete-incident ff-assigned-incident-row__action ff-assigned-incident-row__action--primary" data-incident-id="${incident.incident_id}" data-status="${statusId}">
                    <i class="fas fa-check-circle"></i><span>Complete</span>
                </button>
            `;
        } else if (statusId == 4) {
            statusClass = 'status-resolved';
            statusText = 'CONTROLLED';
            actionButton = `
                <button type="button" class="close-case ff-assigned-incident-row__action ff-assigned-incident-row__action--primary" data-incident-id="${incident.incident_id}" data-status="${statusId}">
                    <i class="fas fa-folder"></i><span>Close</span>
                </button>
            `;
        } else if (statusId == 5) {
            statusClass = 'status-closed';
            statusText = 'CLOSED';
            actionButton = `
                <button type="button" class="ff-assigned-incident-row__action ff-assigned-incident-row__action--muted" disabled>
                    <i class="fas fa-folder"></i><span>Closed</span>
                </button>
            `;
        }

        if ([1, 2].includes(parseInt(statusId))) {
            falseAlarmButton = `
                <button type="button" class="false-alarm-incident ff-assigned-incident-row__action ff-assigned-incident-row__action--warn" data-incident-id="${incident.incident_id}" data-status="${statusId}">
                    <i class="fas fa-triangle-exclamation"></i><span>False Alarm</span>
                </button>
            `;
        }
        
        const incidentType = getIncidentTypeLabel(incident);
        if ([2, 3].includes(parseInt(statusId)) && String(incidentType).toLowerCase().includes('fire')) {
            backupButton = `<button type="button" class="request-incident-backup ff-assigned-incident-row__action ff-assigned-incident-row__action--warn" data-incident-id="${incident.incident_id}"><i class="fas fa-tower-broadcast"></i><span>Request Backup</span></button>`;
        }
        const incidentTitle = incident.marker?.title || 'Emergency Incident';
        const lat = incident.marker?.lat;
        const lng = incident.marker?.lng;
        const description = incident.marker?.description || 'No description provided';
        const reporterLabel = formatReporterLabel(incident);
        const reportedAt = formatHumanDateTime(incident.created_at);
        const assignedAt = formatHumanDateTime(incident.updated_at || incident.created_at);
        const imageUrl = getIncidentImageUrl(incident);
        const incidentTypeIcon = getIncidentTypeIconClass(incidentType);
        const hasCoordinates = lat !== null && lat !== undefined && lng !== null && lng !== undefined;
        const locationLabel = hasCoordinates ? 'Resolving location...' : 'Location not specified';
        const descriptionSummary = String(description).replace(/\s+/g, ' ').trim();
        const imageMarkup = imageUrl
            ? `<img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(incidentTitle)}" onerror="this.remove(); this.parentElement.classList.add('no-image'); this.parentElement.innerHTML = '<div class=&quot;incident-media-fallback&quot;><i class=&quot;fas ${incidentTypeIcon}&quot;></i><span>No Image</span></div>';">`
            : `<div class="incident-media-fallback"><i class="fas ${incidentTypeIcon}"></i><span>No Image</span></div>`;
        
        html += `
            <article class="incident-card ff-assigned-incident-row" data-incident-card="${incident.incident_id}">
                <button type="button" class="ff-assigned-incident-row__main view-incident-details" data-incident-id="${incident.incident_id}" aria-label="Open incident details for ${escapeHtml(incidentTitle)}">
                    <div class="ff-assigned-incident-row__media">
                        ${imageMarkup}
                    </div>
                    <div class="ff-assigned-incident-row__copy">
                        <div class="ff-assigned-incident-row__badges">
                            <span class="ff-assigned-incident-row__type">
                                <i class="fas ${incidentTypeIcon}"></i>
                                ${escapeHtml(incidentType)}
                            </span>
                            <span class="incident-status-badge ff-assigned-incident-row__status ${statusClass}">
                                ${escapeHtml(statusText)}
                            </span>
                        </div>
                        <h3 class="incident-title ff-assigned-incident-row__title">${escapeHtml(incidentTitle)}</h3>
                        <div class="ff-assigned-incident-row__detail">
                            <i class="fas fa-location-dot" aria-hidden="true"></i>
                            <span data-incident-location="${incident.incident_id}">${escapeHtml(locationLabel)}</span>
                        </div>
                        <div class="ff-assigned-incident-row__detail">
                            <i class="fas fa-user" aria-hidden="true"></i>
                            <span>${escapeHtml(reporterLabel)}</span>
                        </div>
                        <div class="ff-assigned-incident-row__meta">
                            <span>${escapeHtml(reportedAt)}</span>
                            <span class="ff-assigned-incident-row__meta-separator">&bull;</span>
                            <span>Assigned ${escapeHtml(assignedAt)}</span>
                        </div>
                        <p class="ff-assigned-incident-row__summary">${escapeHtml(descriptionSummary)}</p>
                    </div>
                </button>
                <div class="ff-assigned-incident-row__actions">
                    ${actionButton}
                    ${backupButton}
                    ${falseAlarmButton}
                </div>
            </article>
        `;
    });
    
    container.innerHTML = html;
    
    // Event Listeners for View Details buttons
    document.querySelectorAll('.view-incident-details').forEach(btn => {
        btn.addEventListener('click', () => {
            const incidentId = btn.dataset.incidentId;
            const incident = incidents.find(i => i.incident_id == incidentId);
            if (incident) {
                showProfessionalIncidentModal(incident);
            }
        });
    });
    
    // Event Listeners for Respond buttons (status 1)
    document.querySelectorAll('.respond-incident').forEach(btn => {
        btn.addEventListener('click', async () => {
            const incidentId = btn.dataset.incidentId;
            const currentStatus = parseInt(btn.dataset.status);
            await respondToIncident(incidentId, currentStatus, btn);
        });
    });

    document.querySelectorAll('.request-incident-backup').forEach(btn => {
        btn.addEventListener('click', async () => requestIncidentBackup(btn.dataset.incidentId, btn));
    });

    // Event Listeners for Complete buttons (status 2)
    document.querySelectorAll('.complete-incident').forEach(btn => {
        btn.addEventListener('click', async () => {
            const incidentId = btn.dataset.incidentId;
            const currentStatus = parseInt(btn.dataset.status);
            await completeIncident(incidentId, currentStatus, btn);
        });
    });
    
    // Event Listeners for Close Case buttons (status 3)
    document.querySelectorAll('.close-case').forEach(btn => {
        btn.addEventListener('click', async () => {
            const incidentId = btn.dataset.incidentId;
            const currentStatus = parseInt(btn.dataset.status);
            await closeCase(incidentId, currentStatus, btn);
        });
    });

    document.querySelectorAll('.false-alarm-incident').forEach(btn => {
        btn.addEventListener('click', async () => {
            const incidentId = btn.dataset.incidentId;
            const currentStatus = parseInt(btn.dataset.status);
            await markIncidentFalseAlarm(incidentId, currentStatus, btn);
        });
    });

    hydrateAssignedIncidentLocations(incidents);
}
    // Professional Incident Details Modal
    function showProfessionalIncidentModal(incident) {
        let modal = document.getElementById('professionalIncidentModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'professionalIncidentModal';
            modal.className = 'professional-modal';
            modal.innerHTML = `
                <div class="professional-modal-overlay"></div>
                <div class="professional-modal-container">
                    <div class="professional-modal-header">
                        <div class="professional-modal-title">
                            <i class="fas fa-file-alt"></i>
                            Incident Report
                        </div>
                        <button class="professional-modal-close">&times;</button>
                    </div>
                    <div class="professional-modal-body" id="professionalIncidentContent"></div>
                    <div class="professional-modal-footer">
                        <button class="btn btn-outline modal-close-btn">Close</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            const closeBtn = modal.querySelector('.professional-modal-close');
            const overlay = modal.querySelector('.professional-modal-overlay');
            const closeModalBtn = modal.querySelector('.modal-close-btn');
            
            const closeModal = () => {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            };
            
            closeBtn.addEventListener('click', closeModal);
            overlay.addEventListener('click', closeModal);
            closeModalBtn.addEventListener('click', closeModal);
        }
        
        const content = document.getElementById('professionalIncidentContent');
        
        const statusId = getIncidentStatusId(incident);
        let statusBadgeClass = 'status-badge-reported';
        let statusText = 'REPORTED';
        let statusIcon = 'far fa-bell';
        
        if (statusId == 2) { 
            statusBadgeClass = 'status-badge-responding'; 
            statusText = 'RESPONDING';
            statusIcon = 'fas fa-spinner fa-pulse';
        } else if (statusId == 3) { 
            statusBadgeClass = 'status-badge-resolved'; 
            statusText = 'ON SCENE';
            statusIcon = 'fas fa-crosshairs';
        } else if (statusId == 4) { 
            statusBadgeClass = 'status-badge-resolved'; 
            statusText = 'CONTROLLED';
            statusIcon = 'fas fa-check-circle';
        } else if (statusId == 5) { 
            statusBadgeClass = 'status-badge-closed'; 
            statusText = 'CLOSED';
            statusIcon = 'fas fa-folder';
        }
        
        const incidentType = getIncidentTypeLabel(incident);
        const incidentTitle = incident.marker?.title || 'Emergency Incident';
        const lat = incident.marker?.lat;
        const lng = incident.marker?.lng;
        const description = incident.marker?.description || 'No description provided';
        const teamId = incident.team?.team_id;
        const reportedBy = incident.reported_by || 'Dispatch';
        const createdAt = incident.created_at ? new Date(incident.created_at).toLocaleString() : 'Unknown';
        
        content.innerHTML = `
            <div class="professional-incident-details">
                <!-- Header Status Bar -->
                <div class="details-status-header ${statusBadgeClass}">
                    <i class="${statusIcon}"></i>
                    <span>${statusText}</span>
                </div>
                
                <!-- Incident ID & Type Row -->
                <div class="details-info-row">
                    <div class="details-info-item">
                        <label>INCIDENT ID</label>
                        <div class="details-info-value">#${incident.incident_id}</div>
                    </div>
                    <div class="details-info-item">
                        <label>INCIDENT TYPE</label>
                        <div class="details-info-value">
                            <i class="fas fa-fire" style="color: #ef4444;"></i> ${escapeHtml(incidentType)}
                        </div>
                    </div>
                </div>
                
                <!-- Title Section -->
                <div class="details-title-section">
                    <label>TITLE / LOCATION</label>
                    <div class="details-title">${escapeHtml(incidentTitle)}</div>
                </div>
                
                <!-- Coordinates Section -->
                ${lat && lng ? `
                <div class="details-coordinates">
                    <div class="coord-box">
                        <label>LATITUDE</label>
                        <div class="coord-value">${lat}</div>
                    </div>
                    <div class="coord-box">
                        <label>LONGITUDE</label>
                        <div class="coord-value">${lng}</div>
                    </div>
                </div>
                ` : '<div class="details-info-item"><label>COORDINATES</label><div class="details-info-value">Not available</div></div>'}
                
                <!-- Description Section -->
                <div class="details-description">
                    <label><i class="fas fa-align-left"></i> INCIDENT DESCRIPTION</label>
                    <div class="description-text">${escapeHtml(description)}</div>
                </div>
                
                <!-- Report Information Grid -->
                <div class="details-grid-2">
                    <div class="details-info-item">
                        <label><i class="far fa-user"></i> REPORTED BY</label>
                        <div class="details-info-value">${escapeHtml(reportedBy)}</div>
                    </div>
                    <div class="details-info-item">
                        <label><i class="far fa-calendar"></i> REPORTED AT</label>
                        <div class="details-info-value">${createdAt}</div>
                    </div>
                </div>
                
                <!-- Team Information -->
                ${teamId ? `
                <div class="details-team-info">
                    <label><i class="fas fa-users"></i> ASSIGNED TEAM</label>
                    <div class="team-badge">Team #${teamId}</div>
                </div>
                ` : ''}
                
                <!-- Last Updated -->
                ${incident.updated_at ? `
                <div class="details-last-updated">
                    <i class="far fa-clock"></i> Last updated: ${new Date(incident.updated_at).toLocaleString()}
                </div>
                ` : ''}
            </div>
        `;
        
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
async function respondToIncident(incidentId, currentStatusId, triggerButton = null) {
    if (currentStatusId != 1) {
        showToast('Cannot respond to this incident - invalid status', 'error');
        return;
    }
    
    const routeMode = typeof window.promptRouteSourceSelection === 'function'
        ? await window.promptRouteSourceSelection({
            title: 'Choose how to start this response',
            description: 'Use your live device location for a real dispatch, or switch to demo mode for a simulated route.'
        })
        : 'real';

    if (!routeMode) return;

    const routeModeLabel = routeMode === 'demo' ? 'DEMO ROUTE' : 'LIVE GPS';
    if (!confirm(`Confirm responding to this incident? Your status will be updated to RESPONDING and navigation will use ${routeModeLabel}.`)) return;
    const pendingState = setIncidentActionPendingState(incidentId, {
        buttonLabel: 'Sending...',
        badgeLabel: 'RESPONDING...'
    });
    
    try {
        const newStatusId = 2;
        const response = await fetch(`/firefighter/incidents/${incidentId}/respond`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message || 'You are now responding to this incident!', 'success');
            await fetchAssignedIncidents(true);
            if (typeof window.calculateRouteToIncident === 'function') {
                await window.calculateRouteToIncident(incidentId, null, { source: routeMode });
            }
        } else {
            restoreIncidentActionPendingState(incidentId, pendingState);
            showToast(result.message || 'Failed to update incident status', 'error');
        }
    } catch (error) {
        restoreIncidentActionPendingState(incidentId, pendingState);
        console.error('Error responding to incident:', error);
        showToast('Failed to update incident status', 'error');
    }
}

async function requestIncidentBackup(incidentId, triggerButton = null) {
    if (!confirm('Request another available firetruck and crew? Your station will be checked first, then the nearest available station.')) return;
    const previous = triggerButton?.innerHTML;
    if (triggerButton) { triggerButton.disabled = true; triggerButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Finding unit...</span>'; }
    try {
        const response = await fetch(`/firefighter/incidents/${incidentId}/backup`, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken } });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'No backup unit is available.');
        showToast(result.message, 'success');
        await fetchAssignedIncidents(true);
    } catch (error) {
        showToast(error.message || 'Unable to dispatch backup.', 'error');
    } finally {
        if (triggerButton?.isConnected) { triggerButton.disabled = false; triggerButton.innerHTML = previous; }
    }
}

async function completeIncident(incidentId, currentStatusId, triggerButton = null) {
    if (currentStatusId != 3) {
        showToast('Cannot mark controlled until the team is on scene', 'error');
        return;
    }
    
    if (!confirm('Complete this incident and mark it as controlled?')) return;
    const pendingState = setIncidentActionPendingState(incidentId, {
        buttonLabel: 'Submitting...',
        badgeLabel: 'MARKING CONTROLLED...'
    });
    
    try {
        const newStatusId = 4;
        const response = await fetch(`/api/incidents/${incidentId}/${newStatusId}`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message || 'Incident completed and marked as controlled!', 'success');
            fetchAssignedIncidents(true); // Refresh the list
        } else {
            restoreIncidentActionPendingState(incidentId, pendingState);
            showToast(result.message || 'Failed to update incident', 'error');
        }
    } catch (error) {
        restoreIncidentActionPendingState(incidentId, pendingState);
        console.error('Error completing incident:', error);
        showToast('Failed to update incident', 'error');
    }
}

async function closeCase(incidentId, currentStatusId, triggerButton = null) {
    if (currentStatusId != 4) {
        showToast('Cannot close this case until the incident is controlled', 'error');
        return;
    }
    
    if (!confirm('Close this case? This action will archive the incident.')) return;
    const pendingState = setIncidentActionPendingState(incidentId, {
        buttonLabel: 'Closing...',
        badgeLabel: 'CLOSING...'
    });
    
    try {
        const newStatusId = 5;
        const response = await fetch(`/api/incidents/${incidentId}/${newStatusId}`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message || 'Case closed successfully!', 'success');
            fetchAssignedIncidents(true); // Refresh the list
        } else {
            restoreIncidentActionPendingState(incidentId, pendingState);
            showToast(result.message || 'Failed to close case', 'error');
        }
    } catch (error) {
        restoreIncidentActionPendingState(incidentId, pendingState);
        console.error('Error closing case:', error);
        showToast('Failed to close case', 'error');
    }
}

async function markIncidentFalseAlarm(incidentId, currentStatusId, triggerButton = null) {
    if (![1, 2].includes(currentStatusId)) {
        showToast('False alarm can only be set while the incident is still active', 'error');
        return;
    }

    const confirmed = await showFalseAlarmConfirmationToast();
    if (!confirmed) return;
    const pendingState = setIncidentActionPendingState(incidentId, {
        buttonLabel: 'Tagging...',
        badgeLabel: 'FLAGGING...'
    });

    try {
        const response = await fetch(`/api/incidents/${incidentId}/false-alarm`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });

        const result = await response.json();

        if (result.success) {
            showToast(result.message || 'Incident tagged as false alarm', 'success');
            fetchAssignedIncidents(true);
        } else {
            restoreIncidentActionPendingState(incidentId, pendingState);
            showToast(result.message || 'Failed to tag false alarm', 'error');
        }
    } catch (error) {
        restoreIncidentActionPendingState(incidentId, pendingState);
        console.error('Error tagging false alarm:', error);
        showToast('Failed to tag false alarm', 'error');
    }
}

function showFalseAlarmConfirmationToast() {
    return new Promise((resolve) => {
        const existing = document.getElementById('falseAlarmConfirmToast');
        if (existing) {
            existing.remove();
        }

        const toast = document.createElement('div');
        toast.id = 'falseAlarmConfirmToast';
        toast.style.cssText = `
            position: fixed;
            right: 24px;
            bottom: 24px;
            width: min(360px, calc(100vw - 32px));
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            border: 1px solid rgba(251, 146, 60, 0.28);
            border-left: 4px solid #ea580c;
            border-radius: 16px;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.18);
            padding: 16px;
            z-index: 5000;
            color: #7c2d12;
        `;

        toast.innerHTML = `
            <div style="display:flex; align-items:flex-start; gap:12px;">
                <div style="width:38px; height:38px; border-radius:999px; background:#fb923c; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-size:14px; font-weight:700; margin-bottom:4px;">Flag as False Alarm?</div>
                    <div style="font-size:13px; line-height:1.45; color:#9a3412;">
                        This will tag the incident as a false alarm and remove it from active response handling.
                    </div>
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:14px;">
                <button type="button" id="falseAlarmCancelBtn" style="padding:8px 12px; border-radius:10px; border:1px solid #fdba74; background:#fff; color:#9a3412; font-size:12px; font-weight:700; cursor:pointer;">
                    Cancel
                </button>
                <button type="button" id="falseAlarmConfirmBtn" style="padding:8px 12px; border-radius:10px; border:none; background:#ea580c; color:#fff; font-size:12px; font-weight:700; cursor:pointer;">
                    Confirm
                </button>
            </div>
        `;

        const cleanup = (value) => {
            if (toast.parentNode) {
                toast.remove();
            }
            resolve(value);
        };

        document.body.appendChild(toast);

        toast.querySelector('#falseAlarmCancelBtn')?.addEventListener('click', () => cleanup(false));
        toast.querySelector('#falseAlarmConfirmBtn')?.addEventListener('click', () => cleanup(true));

        setTimeout(() => {
            if (document.body.contains(toast)) {
                cleanup(false);
            }
        }, 10000);
    });
}
    
    // Show toast notification
    function showToast(message, type = 'info') {
        const toast = document.getElementById('toast');
        if (!toast) return;
        
        toast.textContent = message;
        toast.className = `toast toast-${type}`;
        toast.style.display = 'block';
        
        setTimeout(() => {
            toast.style.display = 'none';
        }, 3000);
    }
    
    function paintCurrentUserLocation(lat, lng) {
        if (!window.map || typeof L === 'undefined') {
            return;
        }

        if (window.userMarker) {
            window.map.removeLayer(window.userMarker);
        }

        window.userMarker = L.marker([lat, lng], {
            icon: L.divIcon({
                className: 'user-location-marker',
                html: `<div style="
                    width: 24px;
                    height: 24px;
                    background-color: #3b82f6;
                    border: 3px solid white;
                    border-radius: 50%;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                    animation: pulse 2s infinite;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">
                    <i class="fas fa-user" style="color: white; font-size: 12px;"></i>
                </div>`,
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            })
        }).bindPopup("You are here").addTo(window.map);

        if (window.userAccuracyCircle) {
            window.map.removeLayer(window.userAccuracyCircle);
        }

        window.userAccuracyCircle = L.circle([lat, lng], {
            color: '#3b82f6',
            fillColor: '#3b82f6',
            fillOpacity: 0.1,
            radius: 50
        }).addTo(window.map);
    }

    function initializeFirefighterGeolocation() {
        if (!navigator.geolocation) {
            console.warn("Geolocation not supported");
            updateUserLocation(window.currentUserId, 15.8222, 120.3297);
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                updateUserLocation(window.currentUserId, lat, lng);
                paintCurrentUserLocation(lat, lng);
                sendUserLocationToServer(lat, lng);
                document.dispatchEvent(new Event("locationReady"));
            },
            function(error) {
                console.error("Location error:", error);
                updateUserLocation(window.currentUserId, 15.8222, 120.3297);
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }

    function hydrateDashboardFromCache() {
        const cachedWidgets = readDashboardCache(FF_WIDGET_CACHE_KEY, null);
        if (cachedWidgets) {
            applyDashboardWidgetPayload(cachedWidgets);
        }

        const cachedAssigned = readDashboardCache(FF_ASSIGNED_CACHE_KEY, null);
        if (Array.isArray(cachedAssigned) && cachedAssigned.length > 0) {
            window.assignedIncidentsData = cachedAssigned;
            displayAssignedIncidents(cachedAssigned);
            document.getElementById('assignedIncidentsCount').textContent = cachedAssigned.length;
            if (typeof updateChartsWithData === 'function') {
                updateChartsWithData(cachedAssigned);
            }
        }

        const cachedPosts = readDashboardCache(FF_POSTS_CACHE_KEY, null);
        if (Array.isArray(cachedPosts)) {
            renderDashboardAnnouncements(cachedPosts);
        }
    }

    function startDeferredMapHydration() {
        const loadState = document.getElementById('firefighterMapLoadState');

        if (typeof window.initializePyroMapRuntime === 'function') {
            window.initializePyroMapRuntime();
        }

        window.setTimeout(async function () {
            if (loadState) {
                loadState.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Refreshing map layers...</span>';
            }

            try {
                initializeFirefighterGeolocation();
                await Promise.allSettled([
                    typeof fetchAllFirefighterLocations === 'function' ? fetchAllFirefighterLocations() : Promise.resolve(),
                    typeof loadShapes === 'function' ? loadShapes() : Promise.resolve(),
                ]);
            } finally {
                if (loadState) {
                    loadState.innerHTML = '<i class="fas fa-check-circle"></i><span>Map layers synced.</span>';
                    window.setTimeout(() => loadState.style.display = 'none', 2200);
                }
            }
        }, 180);
    }

    async function bootstrapFirefighterDashboard() {
        hydrateDashboardFromCache();

        await fetchAssignedIncidents(true);
        loadFirefighterDashboardWidgets();
        startDeferredMapHydration();
        window.setTimeout(() => {
            loadFirefighterDashboardPosts();
        }, 320);

        window.setTimeout(() => {
            window.__ffAllowDashboardPrefetch = true;
            if (typeof scheduleDashboardRoutePrefetch === 'function') {
                scheduleDashboardRoutePrefetch();
            }
        }, 1500);

        window.setTimeout(() => {
            window.runPendingIncidentAutoAssignment();
            startFirefighterDashboardIntervals();
        }, 2200);
    }

    // Sidebar toggle and location initialization
    document.addEventListener('DOMContentLoaded', function() {
        window.userCoords = {
            currentLatitude: null,
            currentLongitude: null,
            ready: false
        };
        window.incidentsData = [];
        
        const sidebar = document.querySelector('.sidebar');
        const openBtn = document.getElementById('openSidebar');
        const closeBtn = document.getElementById('closeSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        
        if (openBtn) {
            openBtn.addEventListener('click', function() {
                sidebar.classList.add('open');
                backdrop.classList.remove('hidden');
            });
        }
        
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                sidebar.classList.remove('open');
                backdrop.classList.add('hidden');
            });
        }
        
        if (backdrop) {
            backdrop.addEventListener('click', function() {
                sidebar.classList.remove('open');
                backdrop.classList.add('hidden');
            });
        }
        
        const refreshBtn = document.getElementById('refreshAssignedIncidents');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                fetchAssignedIncidents(true);
            });
        }

        const firefighterDashboardVisibilityHandler = function () {
            if (firefighterDashboardIsVisible()) {
                fetchAssignedIncidents(true);
                if (typeof fetchAllFirefighterLocations === 'function') {
                    fetchAllFirefighterLocations();
                }
                startFirefighterDashboardIntervals();
                return;
            }

            clearFirefighterDashboardIntervals();
        };

        bootstrapFirefighterDashboard();
        window.__ffCurrentPageCleanup = function () {
            clearFirefighterDashboardIntervals();
            document.removeEventListener('visibilitychange', firefighterDashboardVisibilityHandler);
            window.__ffAllowDashboardPrefetch = false;
            if (typeof window.destroyPyroMapRuntime === 'function') {
                window.destroyPyroMapRuntime();
            }
            if (typeof window.closeFirefighterInsightsPanel === 'function') {
                window.closeFirefighterInsightsPanel();
            }
        };

        document.addEventListener('visibilitychange', firefighterDashboardVisibilityHandler);
        
        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const status = this.dataset.status;
                const statusDot = document.querySelector('.status-dot');
                const statusButton = document.querySelector('.bg-blue-600');
                
                if (status === 'on-duty') {
                    statusDot.className = 'status-dot online';
                    if (statusButton) statusButton.innerHTML = '<i class="fas fa-circle"></i><span class="hidden sm:inline">On Duty</span>';
                } else if (status === 'on-break') {
                    statusDot.className = 'status-dot away';
                    if (statusButton) statusButton.innerHTML = '<i class="fas fa-mug-hot"></i><span class="hidden sm:inline">On Break</span>';
                } else {
                    statusDot.className = 'status-dot offline';
                    if (statusButton) statusButton.innerHTML = '<i class="fas fa-clock"></i><span class="hidden sm:inline">Off Duty</span>';
                }
                
                document.getElementById('statusModal').classList.remove('active');
                
                fetch('/api/firefighter/status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ status: status })
                });
            });
        });
    });
    // Chart instances
let incidentStatusChart = null;
let responseTimeChart = null;
let monthlyIncidentsChart = null;
let priorityChart = null;
let performanceChart = null;

// Function to initialize all charts
function initializeCharts() {
    // Incident Status Chart (Pie/Doughnut)
    const statusCtx = document.getElementById('incidentStatusChart')?.getContext('2d');
    if (statusCtx) {
        incidentStatusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Reported', 'Responding', 'Resolved', 'Closed'],
                datasets: [{
                    data: [0, 0, 0, 0],
                    backgroundColor: ['#e91e8c', '#7c3aed', '#1da1f2', '#f59e0b'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 11 }, usePointStyle: true, boxWidth: 8 }
                    },
                    tooltip: { callbacks: { label: (context) => `${context.label}: ${context.raw} incidents` } }
                }
            }
        });
    }

    // Response Time Trend Chart (Line)
    const responseCtx = document.getElementById('responseTimeChart')?.getContext('2d');
    if (responseCtx) {
        responseTimeChart = new Chart(responseCtx, {
            type: 'line',
            data: {
                labels: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                datasets: [{
                    label: 'Avg Response Time (minutes)',
                    data: [0, 2, 7, 5, 1, 0, 4],
                    borderColor: '#e91e8c',
                    backgroundColor: 'rgba(233, 30, 140, 0.16)',
                    tension: 0.45,
                    fill: true,
                    pointBackgroundColor: '#e91e8c',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 3,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top', labels: { font: { size: 11 } } },
                    tooltip: { callbacks: { label: (context) => `${context.raw} minutes` } }
                },
                scales: { y: { beginAtZero: true, title: { display: true, text: 'Minutes' } } }
            }
        });
    }

    // Monthly Incidents Chart (Bar)
    const monthlyCtx = document.getElementById('monthlyIncidentsChart')?.getContext('2d');
    if (monthlyCtx) {
        monthlyIncidentsChart = new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Incidents',
                    data: [0, 0, 0, 0],
                    backgroundColor: ['#f472b6', '#8b5cf6', '#38bdf8', '#f59e0b'],
                    borderRadius: 12,
                    barPercentage: 0.7,
                    categoryPercentage: 0.8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (context) => `${context.raw} incidents` } }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 11, weight: 600 } }
                    },
                    y: {
                        beginAtZero: true,
                        max: 10,
                        ticks: { stepSize: 2, color: '#94a3b8', font: { size: 11 } },
                        grid: { color: 'rgba(148, 163, 184, 0.25)', drawBorder: false }
                    }
                }
            }
        });
    }


    // Performance Metrics Chart (Radar or Gauge)
    const performanceCtx = document.getElementById('performanceChart')?.getContext('2d');
    if (performanceCtx) {
        performanceChart = new Chart(performanceCtx, {
            type: 'radar',
            data: {
                labels: ['Completion Rate', 'Response Time', 'Success Rate', 'Efficiency', 'Teamwork'],
                datasets: [{
                    label: 'Performance Score (%)',
                    data: [0, 0, 0, 0, 0],
                    backgroundColor: 'rgba(139, 92, 246, 0.18)',
                    borderColor: '#8b5cf6',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#ec4899',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4.5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top', labels: { font: { size: 10 }, usePointStyle: true, boxWidth: 8 } },
                    tooltip: { callbacks: { label: (context) => `${context.raw}%` } }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { stepSize: 20, backdropColor: 'transparent', color: '#94a3b8' },
                        angleLines: { color: 'rgba(148, 163, 184, 0.35)' },
                        grid: { color: 'rgba(148, 163, 184, 0.25)' },
                        pointLabels: { color: '#64748b', font: { size: 10, weight: 600 } }
                    }
                }
            }
        });
    }
}

// Function to update all charts with incident data
function updateChartsWithData(incidents) {
    if (!incidents || incidents.length === 0) {
        // Set default empty data
        if (incidentStatusChart) incidentStatusChart.data.datasets[0].data = [0, 0, 0, 0];
        if (priorityChart) priorityChart.data.datasets[0].data = [0, 0, 0, 0];
        if (performanceChart) performanceChart.data.datasets[0].data = [0, 0, 0, 0, 0];
        
        [incidentStatusChart, priorityChart, performanceChart].forEach(chart => {
            if (chart) chart.update();
        });
        return;
    }

    // Update Incident Status Chart
    const statusCounts = {
        reported: incidents.filter(i => getIncidentStatusId(i) === 1).length,
        responding: incidents.filter(i => getIncidentStatusId(i) === 2).length,
        resolved: incidents.filter(i => getIncidentStatusId(i) === 3).length,
        closed: incidents.filter(i => [4, 5].includes(getIncidentStatusId(i))).length
    };
    if (incidentStatusChart) {
        incidentStatusChart.data.datasets[0].data = [statusCounts.reported, statusCounts.responding, statusCounts.resolved, statusCounts.closed];
        incidentStatusChart.update();
    }

    // Update Priority Chart
    const priorityCounts = {
        critical: incidents.filter(i => i.priority === 'critical').length,
        high: incidents.filter(i => i.priority === 'high').length,
        medium: incidents.filter(i => i.priority === 'medium').length,
        low: incidents.filter(i => i.priority === 'low').length
    };
    if (priorityChart) {
        priorityChart.data.datasets[0].data = [priorityCounts.critical, priorityCounts.high, priorityCounts.medium, priorityCounts.low];
        priorityChart.update();
    }

    // Calculate average response times for last 7 days
    const last7Days = [];
    const responseTimes = [];
    for (let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        const dayStart = new Date(date.setHours(0, 0, 0, 0));
        const dayEnd = new Date(date.setHours(23, 59, 59, 999));
        
        const dayIncidents = incidents.filter(i => {
            const created = new Date(i.created_at);
            return created >= dayStart && created <= dayEnd && getIncidentStatusId(i) >= 2;
        });
        
        let avgTime = 0;
        if (dayIncidents.length > 0) {
            const totalTime = dayIncidents.reduce((sum, inc) => {
                const created = new Date(inc.created_at);
                const responded = inc.updated_at ? new Date(inc.updated_at) : new Date();
                return sum + (responded - created) / (1000 * 60);
            }, 0);
            avgTime = Math.round(totalTime / dayIncidents.length);
        }
        responseTimes.push(avgTime);
        last7Days.push(date.toLocaleDateString('en-US', { weekday: 'short' }));
    }
    
    if (responseTimeChart) {
        responseTimeChart.data.labels = last7Days;
        responseTimeChart.data.datasets[0].data = responseTimes;
        responseTimeChart.update();
    }

    // Update Monthly Incidents Chart (by week)
    const currentMonth = new Date().getMonth();
    const currentYear = new Date().getFullYear();
    const weeksData = [0, 0, 0, 0];
    
    incidents.forEach(incident => {
        const incidentDate = new Date(incident.created_at);
        if (incidentDate.getMonth() === currentMonth && incidentDate.getFullYear() === currentYear) {
            const weekNum = Math.min(Math.floor((incidentDate.getDate() - 1) / 7), 3);
            weeksData[weekNum]++;
        }
    });
    
    if (monthlyIncidentsChart) {
        monthlyIncidentsChart.data.datasets[0].data = weeksData;
        monthlyIncidentsChart.update();
    }

    // Update Performance Chart
    const totalIncidents = incidents.length;
    const completedIncidents = incidents.filter(i => getIncidentStatusId(i) >= 3).length;
    const completionRate = totalIncidents > 0 ? Math.round((completedIncidents / totalIncidents) * 100) : 0;
    
    const avgResponseTime = responseTimes.reduce((a, b) => a + b, 0) / responseTimes.filter(t => t > 0).length || 0;
    const responseScore = Math.max(0, Math.min(100, 100 - (avgResponseTime / 30) * 100));
    
    const successfulIncidents = incidents.filter(i => [4, 5].includes(getIncidentStatusId(i))).length;
    const successRate = successfulIncidents > 0 
        ? Math.round((successfulIncidents / completedIncidents) * 100) : 0;
    
    if (performanceChart) {
        performanceChart.data.datasets[0].data = [completionRate, Math.round(responseScore), successRate, Math.round((completionRate + responseScore + successRate) / 3), 85];
        performanceChart.update();
    }
}

// Add this to your displayAssignedIncidents function to update charts
// Call updateChartsWithData(incidents) after displaying incidents

document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    
    // User Profile Dropdown Toggle
    const userDropdownBtn = document.getElementById('userDropdownBtn');
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    
    if (userDropdownBtn && userDropdownMenu) {
        userDropdownBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdownMenu.classList.toggle('show');
            userDropdownBtn.setAttribute('aria-expanded', userDropdownMenu.classList.contains('show') ? 'true' : 'false');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userDropdownBtn.contains(e.target)) {
                userDropdownMenu.classList.remove('show');
                userDropdownBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Sidebar Mobile Toggle
    const openSidebarBtn = document.getElementById('openSidebar');
    const sidebar = document.getElementById('sidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    
    if (openSidebarBtn && sidebar && sidebarBackdrop) {
        openSidebarBtn.addEventListener('click', function() {
            sidebar.classList.add('active');
            sidebarBackdrop.classList.remove('hidden');
        });
        
        sidebarBackdrop.addEventListener('click', function() {
            sidebar.classList.remove('active');
            sidebarBackdrop.classList.add('hidden');
        });
    }
});

    window.runPendingIncidentAutoAssignment = async function() {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!token) {
            return;
        }

        try {
            ffAutoAssignRequest = fetchFirefighterDashboardOnce(ffAutoAssignRequest, '/api/incidents/auto-assign-recent-unassigned', {
                method: 'POST',
                timeoutMs: 20000,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            });
        const response = await ffAutoAssignRequest;
        const result = await response.json().catch(() => ({}));
        if (result?.success && Number(result.assigned) > 0) {
            await fetchAssignedIncidents(true);
        }
    } catch (error) {
        if (error?.name === 'AbortError') {
            console.warn('Background incident auto-assignment request timed out before completion.');
            return;
        }
        console.error('Background incident auto-assignment failed:', error);
    } finally {
        ffAutoAssignRequest = null;
    }
};
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const insightsPanel = document.getElementById('firefighterMobileInsightsPanel');
    const insightsContent = document.getElementById('firefighterMobileInsightsContent');
    const insightsCloseButtons = document.querySelectorAll('[data-ff-insights-close]');
    const welcomeToast = document.getElementById('firefighterMobileWelcomeToast');
    const insightSections = Array.from(document.querySelectorAll('[data-ff-insights-source]'));
    const insightPlaceholders = new Map();

    function moveInsightsIntoPanel() {
        if (!insightsContent) {
            return;
        }

        insightSections.forEach(function (section, index) {
            if (!insightPlaceholders.has(section)) {
                const placeholder = document.createElement('div');
                placeholder.dataset.ffInsightsPlaceholder = String(index);
                section.parentNode?.insertBefore(placeholder, section);
                insightPlaceholders.set(section, placeholder);
            }

            insightsContent.appendChild(section);
        });
    }

    function restoreInsightsFromPanel() {
        insightSections.forEach(function (section) {
            const placeholder = insightPlaceholders.get(section);
            if (placeholder && placeholder.parentNode) {
                placeholder.parentNode.insertBefore(section, placeholder);
                placeholder.remove();
            }
            insightPlaceholders.delete(section);
        });
    }

    function openFirefighterInsightsPanel() {
        if (!insightsPanel || window.innerWidth > 768) {
            return;
        }

        closeFirefighterInsightsPanel();
    }

    function closeFirefighterInsightsPanel() {
        if (!insightsPanel) {
            return;
        }

        restoreInsightsFromPanel();
        insightsPanel.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('menu-open');
    }

    window.openFirefighterInsightsPanel = openFirefighterInsightsPanel;
    window.closeFirefighterInsightsPanel = closeFirefighterInsightsPanel;

    insightsCloseButtons.forEach(function (button) {
        button.addEventListener('click', closeFirefighterInsightsPanel);
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
            closeFirefighterInsightsPanel();
        }
    });

    if (welcomeToast && window.innerWidth <= 768) {
        window.setTimeout(function () {
            welcomeToast.classList.add('is-visible');
            window.setTimeout(function () {
                welcomeToast.classList.remove('is-visible');
            }, 3200);
        }, 260);
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const mapLayersPanel = document.querySelector('[data-ff-map-layers-panel]');
    const mapLayerCloseButtons = document.querySelectorAll('[data-ff-map-layers-close]');
    let lastLayerTapAt = 0;

    function closeFirefighterInlineInsightsPanel() {
        if (typeof window.closeFirefighterInsightsPanel === 'function') {
            window.closeFirefighterInsightsPanel();
            return;
        }

        const insightsPanel = document.getElementById('firefighterMobileInsightsPanel');
        if (!insightsPanel) {
            return;
        }

        if (insightsPanel.contains(document.activeElement)) {
            document.activeElement?.blur?.();
        }

        insightsPanel.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('menu-open');
    }

    function openFirefighterInlineMapLayersPanel() {
        if (!mapLayersPanel || window.innerWidth > 768) {
            return;
        }

        document.querySelector('[data-ff-mobile-dock]')?.classList.remove('is-burst-open');
        document.querySelectorAll('[data-ff-notifications-panel], [data-ff-settings-panel]').forEach(function (panel) {
            if (panel.contains(document.activeElement)) {
                document.activeElement?.blur?.();
            }

            panel.classList.remove('is-open');
            panel.setAttribute('aria-hidden', 'true');
        });

        closeFirefighterInlineInsightsPanel();

        if (typeof window.syncPyroSharedMapLayerControls === 'function') {
            window.syncPyroSharedMapLayerControls();
        }

        mapLayersPanel.removeAttribute('inert');
        mapLayersPanel.setAttribute('aria-hidden', 'false');
        mapLayersPanel.classList.add('is-open');
    }

    function closeFirefighterInlineMapLayersPanel() {
        if (!mapLayersPanel) {
            return;
        }

        if (mapLayersPanel.contains(document.activeElement)) {
            document.activeElement?.blur?.();
        }

        mapLayersPanel.classList.remove('is-open');
        mapLayersPanel.setAttribute('aria-hidden', 'true');
        mapLayersPanel.setAttribute('inert', '');
    }

    function handleFirefighterLeafletLayerTap(event) {
        if (window.innerWidth > 768 || !mapLayersPanel) {
            return;
        }

        const control = event.target?.closest?.('.firefighter-map-stage .leaflet-control-layers');
        if (!control) {
            return;
        }

        event.__ffMapLayerControlHandled = true;
        event.preventDefault();
        event.stopImmediatePropagation?.();
        event.stopPropagation();

        if (event.type === 'click' && Date.now() - lastLayerTapAt < 450) {
            return;
        }

        lastLayerTapAt = Date.now();
        control.classList.remove('leaflet-control-layers-expanded');
        openFirefighterInlineMapLayersPanel();
    }

    window.openFirefighterMapLayersPanel = openFirefighterInlineMapLayersPanel;
    window.closeFirefighterMapLayersPanel = closeFirefighterInlineMapLayersPanel;

    mapLayerCloseButtons.forEach(function (button) {
        button.addEventListener('click', closeFirefighterInlineMapLayersPanel);
    });

    document.addEventListener('pointerdown', handleFirefighterLeafletLayerTap, true);
    document.addEventListener('touchstart', handleFirefighterLeafletLayerTap, { capture: true, passive: false });
    document.addEventListener('click', handleFirefighterLeafletLayerTap, true);
});
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    --}}
    </div>
    @include('partials.pwa-body', ['showPwaInstallButton' => false])
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        window.pyroPwa?.registerPageSnapshot?.({
            key: 'firefighter-dashboard-v2',
            label: 'Firefighter Dashboard'
        });
    });
    </script>
</body>
</html>

