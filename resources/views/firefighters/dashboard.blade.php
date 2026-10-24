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
            width: min(720px, calc(100vw - 32px));
            max-height: min(82vh, 760px);
            background: linear-gradient(180deg, #fffaf4 0%, #ffffff 100%);
            border-radius: 22px;
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
            padding: 14px 16px;
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
            font-size: 1.18rem;
            line-height: 1.08;
            font-weight: 900;
        }

        .firefighter-assigned-queue-modal__refresh,
        .firefighter-assigned-queue-modal__close {
            width: 36px;
            height: 36px;
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
            padding: 12px 16px 16px;
            overflow-y: auto;
            overflow-x: hidden;
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

        .firefighter-assigned-queue-modal__content .assigned-incidents-container {
            width: 100%;
            min-width: 0;
            padding: 0 !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
        }

        .firefighter-assigned-queue-modal__content .ff-response-units--integrated {
            margin-bottom: 8px;
            border-radius: 12px;
        }

        .firefighter-assigned-queue-modal__content .ff-assigned-incident-row {
            grid-template-columns: minmax(0, 1fr);
            gap: 0.45rem;
            padding: 0.62rem 0;
        }

        .firefighter-assigned-queue-modal__content .ff-assigned-incident-row__main {
            grid-template-columns: 48px minmax(0, 1fr);
            gap: 0.62rem;
        }

        .firefighter-assigned-queue-modal__content .ff-assigned-incident-row__media {
            width: 48px;
            height: 48px;
            min-width: 48px;
            min-height: 48px;
            border-radius: 0.75rem;
        }

        .firefighter-assigned-queue-modal__content .ff-assigned-incident-row__actions {
            justify-content: flex-start;
            padding-left: 58px;
            gap: 0.35rem;
            flex-wrap: wrap;
        }

        .firefighter-assigned-queue-modal__content .ff-assigned-incident-row__action {
            min-height: 30px;
            padding: 0 0.64rem;
            font-size: 0.66rem;
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
                max-height: min(82svh, 720px);
                border-radius: 20px;
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
                padding-left: 52px;
                justify-content: flex-start;
                align-self: center;
                gap: 0.3rem;
                flex-wrap: wrap;
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
            flex-wrap: wrap;
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

        .ff-assigned-incident-row__action--backup {
            border-color: #f59e0b;
            background: #f59e0b;
            color: #fff;
        }

        .ff-assigned-incident-row__action--call {
            border-color: #2563eb;
            background: #2563eb;
            color: #fff;
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
                            <form method="POST" action="{{ route('logout') }}" data-firefighter-logout-form>
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
                <div class="ff-self-location-card" id="firefighterLocationCard" data-state="locating" aria-live="polite">
                    <span class="ff-self-location-card__icon" aria-hidden="true"><i class="fas fa-location-crosshairs"></i></span>
                    <span class="ff-self-location-card__copy">
                        <strong>Your location</strong>
                        <small id="firefighterLocationStatus">Locating your device...</small>
                    </span>
                    <button type="button" id="firefighterLocateMe" aria-label="Center map on your location" title="Center map on your location">
                        <i class="fas fa-crosshairs" aria-hidden="true"></i>
                    </button>
                </div>
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
            <div id="activeResponseUnitsPanel" class="ff-response-units ff-response-units--integrated" aria-live="polite" aria-label="Active response tracking">
                <div class="ff-response-units__head"><span><i class="fas fa-truck-fast"></i> Assigned response tracking</span><strong id="activeResponseUnitsCount">0</strong></div>
                <div class="ff-apparatus-tracking"><span id="apparatusTrackingState"><i class="fas fa-location-crosshairs"></i> Waiting for assigned driver run</span></div>
            </div>
            <div id="teamReadinessPanel" class="ff-team-readiness-panel" hidden></div>
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
            <div>
                <p class="route-info-card__eyebrow">Active navigation</p>
                <h4><i class="fas fa-route"></i> Route details</h4>
            </div>
            <span id="routeSourceBadge" class="route-source-badge live">
                <i class="fas fa-location-crosshairs"></i><span>LIVE</span>
            </span>
        </div>
        <div class="route-info-card__body">
            <div class="route-info-card__destination">
                <span class="route-info-card__destination-icon" aria-hidden="true"><i class="fas fa-location-dot"></i></span>
                <span class="route-info-card__destination-copy">
                    <strong>Destination</strong>
                    <span id="destinationTitle">Selected destination</span>
                </span>
            </div>
            <dl class="route-info-card__stats">
                <div><dt>Distance</dt><dd id="routeDistance">Calculating</dd></div>
                <div><dt>ETA</dt><dd id="routeETA">Calculating</dd></div>
                <div><dt>Travel time</dt><dd id="routeDuration">Calculating</dd></div>
            </dl>
            <div class="route-info-card__tracking">
                <i class="fas fa-satellite-dish" aria-hidden="true"></i>
                <span id="routeTrackingMode">Waiting for GPS route data</span>
            </div>
        </div>
        <div class="route-progress-container" aria-hidden="true">
            <div id="routeProgress" class="route-progress-bar" style="width: 0%"></div>
        </div>
        <button type="button" id="stopAllSimulationsBtn" class="route-stop-btn" data-stop-route-navigation>
            <i class="fas fa-stop" aria-hidden="true"></i>
            <span>Stop navigation</span>
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

    <div class="firefighter-assigned-queue-modal" id="firefighterAssignedQueueModal" aria-hidden="true" inert>
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
                        <input type="radio" name="firefighter_map_base" data-ff-map-base-toggle="OSM Standard" checked>
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
            userId: @json(auth()->user()->user_id ?? null),
            currentFirefighterId: @json(auth()->user()->user_id ?? null),
            currentUserId: @json(auth()->user()->user_id ?? null),
            userTeamId: @json(isset($userTeam) ? $userTeam->team_id : null),
            userTeamStatus: @json(isset($userTeam) ? $userTeam->status : null),
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

