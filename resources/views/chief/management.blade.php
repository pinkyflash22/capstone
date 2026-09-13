<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.pyrosense-favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <title>PyroSense · Management</title>
    <style>
         aside.legacy-chief-sidebar .nav-item i {
            width: 20px;
            font-size: 16px;
        }

        .lector-top-bar {
            background: rgba(255, 255, 255, 0.8) !important;
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            height: 70px;
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 16px;
            z-index: 40;
            margin: 16px 24px 0;
            border-radius: 24px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }

        .user-dropdown {
            position: relative;
        }

        .user-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            border-radius: 99px;
            background: #fffbeb;
            border: 1px solid #fef3c7;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .user-dropdown-toggle:hover {
            background: #fef3c7;
        }

        .user-dropdown-menu {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            width: 260px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .user-dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #1e293b;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .user-dropdown-item:hover {
            background: #fff0e8;
            color: #c63d26;
        }

        .fa-solid,
        .fas,
        .fa-regular,
        .far,
        .fa-brands,
        .fab,
        .fa-solid::before,
        .fas::before {
            font-family: "Font Awesome 6 Free" !important;
            font-style: normal !important;
            font-variant: normal !important;
            text-rendering: auto !important;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .fa-solid,
        .fas,
        .fa-solid::before,
        .fas::before {
            font-weight: 900 !important;
        }

        .fa-regular,
        .far,
        .fa-regular::before,
        .far::before {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 400 !important;
        }

        .fa-brands,
        .fab,
        .fa-brands::before,
        .fab::before {
            font-family: "Font Awesome 6 Brands" !important;
            font-weight: 400 !important;
        }

        .nav-badge {
            margin-left: auto;
            background: #ef4444;
            color: white;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 600;
        }

        .nav-badge.secondary {
            background: #f59e0b;
        }

        .management-station-hero {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            align-items: flex-start;
            margin: 20px 0 24px;
            padding: 28px 30px;
            border-radius: 26px;
            background: linear-gradient(135deg, #fff7ed 0%, #fffdf7 100%);
            border: 1px solid rgba(245, 158, 11, 0.14);
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.05);
        }

        .management-station-copy {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .management-eyebrow {
            font-size: 11px;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: 800;
        }

        .management-station-hero h2 {
            margin: 0;
            font-size: 2rem;
            line-height: 1.05;
            color: #0f172a;
            font-weight: 800;
        }

        .management-station-subcopy {
            margin: 0;
            color: #64748b;
            font-size: 0.98rem;
        }

        .management-station-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 4px;
        }

        .management-station-meta span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 38px;
            padding: 0 14px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid rgba(245, 158, 11, 0.1);
            color: #9a3412;
            font-size: 0.84rem;
            font-weight: 700;
        }

        .management-station-badge {
            min-width: 180px;
            padding: 18px 20px;
            border-radius: 20px;
            background: #ffffff;
            border: 1px solid rgba(245, 158, 11, 0.16);
            text-align: right;
        }

        .management-station-badge-label {
            display: block;
            margin-bottom: 8px;
            color: #94a3b8;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .management-station-badge strong {
            color: #16a34a;
            font-size: 1rem;
            font-weight: 800;
        }
        .management-station-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 12px;
        }
        .management-station-actions .btn-outline {
            min-width: 150px;
        }
        .station-media-panel {
            display: grid;
            grid-template-columns: minmax(260px, 320px) minmax(0, 1fr);
            gap: 24px;
            margin-bottom: 24px;
            padding: 24px;
            border-radius: 24px;
            background: #fff;
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.04);
        }
        .station-media-profile {
            position: relative;
            border-radius: 22px;
            overflow: hidden;
            min-height: 320px;
            padding: 18px;
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            border: 1px solid rgba(245, 158, 11, 0.14);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #c2410c;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75);
        }
        .station-media-profile img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.94);
        }
        .station-media-profile i {
            font-size: 3rem;
        }
        .station-media-tools {
            display: grid;
            gap: 18px;
        }
        .station-media-card {
            padding: 18px;
            border-radius: 20px;
            background: linear-gradient(135deg, #fffaf6 0%, #fff 100%);
            border: 1px solid rgba(245, 158, 11, 0.12);
        }
        .station-media-card h3 {
            margin: 0 0 8px;
            color: #0f172a;
            font-size: 1rem;
            font-weight: 800;
        }
        .station-media-card p {
            margin: 0 0 14px;
            color: #64748b;
            font-size: 0.88rem;
            line-height: 1.6;
        }
        .station-media-card form {
            display: grid;
            gap: 12px;
        }
        .station-upload-field {
            display: grid;
            gap: 8px;
        }
        .station-upload-label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #9a3412;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .station-upload-box {
            position: relative;
            display: grid;
            gap: 8px;
            padding: 16px 16px 14px;
            border-radius: 18px;
            border: 1px dashed rgba(245, 158, 11, 0.32);
            background: linear-gradient(135deg, #fffaf5 0%, #ffffff 100%);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }
        .station-upload-box:hover {
            border-color: rgba(217, 119, 6, 0.42);
            box-shadow: 0 10px 22px rgba(245, 158, 11, 0.08);
            transform: translateY(-1px);
        }
        .station-upload-box input[type="file"] {
            margin: 0;
            padding: 0;
            border: 0;
            background: transparent;
        }
        .station-upload-box input[type="file"]::file-selector-button {
            margin-right: 12px;
            padding: 10px 14px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, #d14b24 0%, #dd8a11 100%);
            color: #fff;
            font-size: 0.8rem;
            font-weight: 800;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 10px 20px rgba(209, 75, 36, 0.18);
        }
        .station-upload-box input[type="file"]::file-selector-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 24px rgba(209, 75, 36, 0.24);
        }
        .station-upload-help {
            color: #94a3b8;
            font-size: 0.78rem;
            line-height: 1.45;
        }
        .station-upload-submit {
            position: relative;
        }
        .station-upload-submit.is-loading {
            pointer-events: none;
            opacity: 0.92;
        }
        .station-upload-submit.is-loading i {
            animation: stationUploadSpin 0.9s linear infinite;
        }
        .station-upload-progress {
            display: none;
            gap: 8px;
            padding: 12px 14px;
            border-radius: 16px;
            background: linear-gradient(135deg, #fff7ed 0%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.18);
        }
        .station-upload-progress.is-visible {
            display: grid;
        }
        .station-upload-progress-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            color: #9a3412;
            font-size: 0.78rem;
            font-weight: 800;
        }
        .station-upload-progress-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .station-upload-progress-bar {
            height: 10px;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(226, 232, 240, 0.95);
        }
        .station-upload-progress-fill {
            width: 0%;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(135deg, #d14b24 0%, #dd8a11 100%);
            transition: width 0.18s ease;
        }
        .station-upload-progress-copy {
            color: #64748b;
            font-size: 0.76rem;
            line-height: 1.4;
        }
        @keyframes stationUploadSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .station-media-card input[type="file"],
        .station-media-card input[type="text"] {
            width: 100%;
            padding: 12px 14px;
            border-radius: 14px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            background: #fff;
            margin-bottom: 12px;
        }
        .station-gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(170px, 220px));
            justify-content: flex-start;
            gap: 12px;
        }
        .station-gallery-item {
            position: relative;
            aspect-ratio: 1 / 1;
            width: 100%;
            max-width: 220px;
            border-radius: 20px;
            overflow: visible;
            background: #f8fafc;
            border: 1px solid rgba(226, 232, 240, 0.95);
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.07);
        }
        .station-gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            cursor: pointer;
            transition: transform 0.22s ease, filter 0.22s ease;
        }
        .station-gallery-item:hover img {
            transform: scale(1.03);
            filter: saturate(1.04);
        }
        .station-gallery-trigger {
            border: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background: transparent;
            display: block;
            border-radius: inherit;
            overflow: hidden;
        }
        .station-gallery-menu {
            position: absolute;
            top: 8px;
            right: 8px;
            z-index: 8;
        }
        .station-gallery-menu-button {
            width: 32px;
            height: 32px;
            border: 0;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: transparent;
            color: #fff;
            cursor: pointer;
            text-shadow: 0 2px 10px rgba(15, 23, 42, 0.58);
            transition: transform 0.18s ease, opacity 0.18s ease;
        }
        .station-gallery-menu-button:hover {
            transform: scale(1.06);
        }
        .station-gallery-menu-dropdown {
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            min-width: 136px;
            padding: 6px;
            border-radius: 12px;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.96);
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.16);
            display: none;
            z-index: 12;
        }
        .station-gallery-menu.is-open .station-gallery-menu-dropdown {
            display: block;
        }
        .station-gallery-menu-dropdown form {
            margin: 0;
        }
        .station-gallery-menu-item {
            width: 100%;
            border: 0;
            background: transparent;
            border-radius: 9px;
            padding: 9px 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #334155;
            font-size: 0.78rem;
            font-weight: 700;
            text-align: left;
            cursor: pointer;
        }
        .station-gallery-menu-item:hover {
            background: #fff7ed;
            color: #b45309;
        }
        .station-gallery-menu-item.delete {
            color: #dc2626;
        }
        .station-gallery-meta {
            position: absolute;
            inset-inline: 0;
            bottom: 0;
            padding: 22px 12px 10px;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0) 0%, rgba(15, 23, 42, 0.78) 100%);
            pointer-events: none;
        }
        .station-gallery-meta-title {
            display: block;
            color: #fff;
            font-size: 0.76rem;
            font-weight: 800;
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .station-gallery-preview {
            display: grid;
            gap: 14px;
        }
        .station-gallery-preview-frame {
            overflow: hidden;
            border-radius: 18px;
            background: linear-gradient(135deg, #fff7ed 0%, #fff 100%);
            border: 1px solid rgba(245, 158, 11, 0.14);
        }
        .station-gallery-preview-frame img {
            width: 100%;
            max-height: min(60vh, 560px);
            object-fit: contain;
            display: block;
            background: #fff;
        }
        .station-gallery-preview-info {
            display: grid;
            gap: 10px;
        }
        .station-gallery-preview-row {
            display: grid;
            grid-template-columns: 100px minmax(0, 1fr);
            gap: 10px;
            align-items: start;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.9);
        }
        .station-gallery-preview-row:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }
        .station-gallery-preview-label {
            color: #64748b;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .station-gallery-preview-value {
            color: #0f172a;
            font-size: 0.92rem;
            line-height: 1.45;
            word-break: break-word;
        }
        .toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            left: auto;
            bottom: auto;
            width: min(360px, calc(100vw - 32px));
            z-index: 9999;
            display: grid;
            gap: 10px;
        }
        .toast {
            min-width: 0;
            width: 100%;
            padding: 14px 16px;
            border-radius: 18px;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.95);
            box-shadow: 0 22px 40px rgba(15, 23, 42, 0.16);
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 10px;
            align-items: center;
        }
        .toast.success {
            border-color: rgba(34, 197, 94, 0.2);
        }
        .toast.error {
            border-color: rgba(239, 68, 68, 0.2);
        }
        .toast.info {
            border-color: rgba(245, 158, 11, 0.2);
        }
        .toast-message {
            color: #0f172a;
            font-size: 0.86rem;
            line-height: 1.45;
            font-weight: 700;
        }
        .toast-close {
            border: 0;
            background: transparent;
            color: #64748b;
            cursor: pointer;
        }
        .station-flash {
            display: none;
        }
        .station-flash.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .station-flash.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        @media (max-width: 768px) {
            .lector-top-bar {
                top: 12px;
                margin: 12px 14px 0;
                padding: 0 16px;
                border-radius: 20px;
            }

            .management-station-hero {
                flex-direction: column;
                padding: 22px 18px;
                margin-top: 16px;
            }

            .management-station-hero h2 {
                font-size: 1.55rem;
            }

            .management-station-badge {
                width: 100%;
                text-align: left;
            }
            .station-media-panel {
                grid-template-columns: 1fr;
                padding: 20px 18px;
            }
        }

        .management-container {
            padding: 6px 24px 32px;
            display: flex;
            flex-direction: column;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            margin-bottom: 16px;
            padding: 28px 30px;
            border-radius: 26px;
            background: linear-gradient(135deg, #fff8ef 0%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.14);
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.05);
        }

        .management-container > .management-station-hero { order: 1; }
        .management-container > .page-header { order: 2; display: none; }
        .management-container > .stats-grid { order: 3; }
        .management-container > .tab-navigation { order: 4; }
        .management-container > .search-filter-bar { order: 5; }
        .management-container > .loading-state,
        .management-container > .table-card { order: 6; }

        .page-header h1 {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 0;
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
        }

        .page-header h1 i,
        .table-header h2 i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 46px;
            height: 46px;
            border-radius: 16px;
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.18);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75);
        }

        .page-header .date {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 44px;
            padding: 0 16px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(245, 158, 11, 0.12);
            color: #9a3412;
            font-size: 0.86rem;
            font-weight: 700;
        }

        .alert-banner {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 18px;
            padding: 18px 22px;
            border-radius: 22px;
            background: linear-gradient(135deg, rgba(255, 247, 237, 0.92) 0%, rgba(254, 252, 232, 0.96) 100%);
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.04);
        }

        .alert-banner > i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 14px;
            background: linear-gradient(135deg, #f97316 0%, #f59e0b 100%);
            color: #fff;
            flex-shrink: 0;
        }

        .alert-banner .content {
            color: #7c2d12;
            line-height: 1.55;
            font-size: 0.96rem;
            flex: 1;
        }

        .alert-banner .date {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 40px;
            padding: 0 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(245, 158, 11, 0.12);
            color: #9a3412;
            white-space: nowrap;
            font-size: 0.83rem;
            font-weight: 700;
        }

        .tab-navigation {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 22px;
            padding: 16px;
            border-radius: 24px;
            background: linear-gradient(135deg, #fff8ef 0%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.04);
        }

        .tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 999px;
            border: 1px solid transparent;
            background: transparent;
            color: #64748b;
            font-size: 0.92rem;
            font-weight: 700;
            transition: all 0.25s ease;
        }

        .tab-btn i {
            color: #94a3b8;
        }

        .tab-btn:hover {
            background: #fff7ed;
            color: #c2410c;
            border-color: rgba(245, 158, 11, 0.14);
        }

        .tab-btn:hover i,
        .tab-btn.active i {
            color: inherit;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #d14b24 0%, #dd8a11 100%);
            color: #fff;
            box-shadow: 0 10px 22px rgba(209, 75, 36, 0.18);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 22px;
        }

        .management-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .management-summary-card {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            padding: 14px;
            border-radius: 20px;
            background: linear-gradient(135deg, #fffaf4 0%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 16px 28px rgba(15, 23, 42, 0.05);
        }

        .management-summary-icon {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: linear-gradient(135deg, #cc4c1d 0%, #dd8a11 100%);
            color: #fff;
            box-shadow: 0 12px 22px rgba(221, 138, 17, 0.18);
        }

        .management-summary-copy {
            min-width: 0;
        }

        .management-summary-label {
            display: block;
            color: #94a3b8;
            font-size: 0.72rem;
            font-weight: 900;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .management-summary-value {
            display: block;
            margin-top: 2px;
            color: #172554;
            font-size: 1.5rem;
            font-weight: 900;
            line-height: 1;
        }

        .management-summary-meta {
            display: block;
            margin-top: 4px;
            color: #64748b;
            font-size: 0.8rem;
            line-height: 1.25;
        }

        .stat-card {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 24px 22px;
            border-radius: 24px;
            background: linear-gradient(135deg, #fffaf4 0%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.05);
        }

        .stat-icon {
            width: 72px;
            height: 72px;
            border-radius: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #cc4c1d 0%, #dd8a11 100%) !important;
            color: #fff;
            box-shadow: 0 18px 30px rgba(221, 138, 17, 0.22);
        }

        .stat-icon i {
            font-size: 1.2rem;
            color: #fff !important;
        }

        .stat-info h3 {
            margin: 0 0 10px;
            color: #64748b;
            font-size: 0.8rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            font-weight: 800;
        }

        .stat-number {
            color: #0f172a;
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
        }

        .stat-change {
            margin-top: 8px;
            color: #9a3412;
            font-weight: 700;
            font-size: 0.84rem;
        }

        .stat-change.positive {
            color: #16a34a;
        }

        .search-filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
            padding: 18px 20px;
            border-radius: 24px;
            background: linear-gradient(135deg, #fff9f2 0%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.04);
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 52px;
            flex: 1 1 320px;
            padding: 0 18px;
            border-radius: 18px;
            background: #fff;
            border: 1px solid rgba(245, 158, 11, 0.14);
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .search-box i {
            color: #d97706;
        }

        .search-box input {
            width: 100%;
            border: 0;
            outline: 0;
            background: transparent;
            color: #0f172a;
            font-size: 0.95rem;
        }

        .filter-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .filter-select,
        .btn-filter,
        .btn-primary,
        .btn-outline {
            min-height: 46px;
            border-radius: 16px !important;
            font-weight: 700;
            transition: all 0.25s ease;
        }

        .filter-select {
            min-width: 148px;
            padding: 0 14px;
            background: #fff;
            border: 1px solid rgba(245, 158, 11, 0.14);
            color: #475569;
        }

        .btn-filter {
            padding: 0 16px;
            border: 1px solid rgba(245, 158, 11, 0.14);
            background: #fff7ed;
            color: #b45309;
        }

        .btn-filter:hover,
        .btn-outline:hover {
            background: #ffedd5;
            border-color: rgba(245, 158, 11, 0.24);
        }

        .btn-primary {
            padding: 0 18px;
            background: linear-gradient(135deg, #d14b24 0%, #dd8a11 100%) !important;
            border: 0 !important;
            color: #fff !important;
            box-shadow: 0 16px 28px rgba(221, 138, 17, 0.24);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 20px 34px rgba(221, 138, 17, 0.32);
        }

        .btn-outline {
            padding: 0 16px;
            border: 1px solid rgba(245, 158, 11, 0.16) !important;
            background: #fff !important;
            color: #b45309 !important;
        }

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            border: 1px solid rgba(245, 158, 11, 0.14);
            background: #fff;
            color: #b45309;
            transition: all 0.22s ease;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            background: #fff7ed;
            border-color: rgba(245, 158, 11, 0.24);
        }

        .action-btn.delete {
            color: #dc2626;
            border-color: rgba(220, 38, 38, 0.14);
        }

        .action-btn.delete:hover {
            background: #fef2f2;
            border-color: rgba(220, 38, 38, 0.22);
        }

        .modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(15, 23, 42, 0.48);
            backdrop-filter: blur(8px);
            z-index: 3200;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            width: min(640px, calc(100vw - 40px));
            max-height: min(calc(100vh - 48px), 760px);
            overflow: auto;
            border-radius: 28px;
            background: linear-gradient(135deg, #fff8ef 0%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.14);
            box-shadow: 0 30px 60px rgba(15, 23, 42, 0.16);
        }

        #viewModal .modal-content,
        #formModal .modal-content,
        #ecatModal .modal-content {
            max-width: 620px;
        }

        #deleteModal .modal-content {
            max-width: 460px;
        }

        #editStationModal .modal-content {
            max-width: 560px !important;
        }

        .modal-content > form {
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 20px 22px 14px;
            border-bottom: 1px solid rgba(245, 158, 11, 0.12);
        }

        .modal-header h2 {
            margin: 0;
            color: #0f172a;
            font-size: 1.35rem;
            font-weight: 800;
            min-width: 0;
        }

        .modal-close {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            min-width: 40px;
            min-height: 40px;
            border: 1px solid rgba(245, 158, 11, 0.12);
            border-radius: 14px;
            background: #fff;
            color: #b45309;
        }

        .modal-body {
            padding: 20px 22px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 0 22px 22px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #9a3412;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            border-radius: 16px;
            border: 1px solid rgba(245, 158, 11, 0.14);
            background: #fff;
            color: #334155;
            padding: 14px 16px;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color: rgba(217, 119, 6, 0.4);
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.12);
        }

        .detail-container .status-badge-large,
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            padding: 0 12px;
            border-radius: 999px;
            background: #fff7ed;
            border: 1px solid rgba(245, 158, 11, 0.14);
            color: #b45309;
            font-size: 0.8rem;
            font-weight: 700;
        }

        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .modal {
                align-items: flex-end;
                padding: 8px 8px calc(env(safe-area-inset-bottom, 0px) + 84px);
                overflow: hidden;
            }

            .modal-content {
                width: 100%;
                max-width: 100% !important;
                max-height: min(calc(100dvh - env(safe-area-inset-bottom, 0px) - 94px), 720px);
                border-radius: 22px 22px 16px 16px;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                margin: 0 auto;
            }

            .modal-content::before {
                content: "";
                display: block;
                width: 52px;
                height: 5px;
                border-radius: 999px;
                background: #cbd5e1;
                margin: 12px auto 0;
                flex-shrink: 0;
            }

            .modal-header {
                align-items: flex-start;
                gap: 12px;
                padding: 12px 14px 10px;
                flex-shrink: 0;
            }

            .modal-header h2 {
                flex: 1 1 auto;
                min-width: 0;
                font-size: 0.94rem;
                line-height: 1.28;
            }

            .modal-header h2 i {
                font-size: 0.86rem;
            }

            .modal-close {
                width: 34px;
                height: 34px;
                min-width: 34px;
                min-height: 34px;
                border-radius: 11px;
                font-size: 0.86rem;
            }

            .modal-body {
                flex: 1 1 auto;
                min-height: 0;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
                overscroll-behavior: contain;
                padding: 10px 14px 12px;
            }

            .modal-footer {
                flex-shrink: 0;
                flex-wrap: wrap;
                gap: 8px;
                padding: 10px 14px calc(env(safe-area-inset-bottom, 0px) + 10px);
                background: linear-gradient(180deg, rgba(255,255,255,0.92) 0%, #ffffff 30%);
                border-top: 1px solid rgba(245, 158, 11, 0.12);
            }

            .modal-footer > * {
                flex: 1 1 100%;
            }

            .modal-footer .btn-outline,
            .modal-footer .btn-primary {
                width: 100%;
                min-height: 40px;
                font-size: 0.8rem;
                justify-content: center;
            }

            .form-group {
                margin-bottom: 12px;
            }

            .form-label {
                font-size: 0.74rem;
                margin-bottom: 5px;
            }

            .form-input,
            .form-select,
            .form-textarea {
                padding: 11px 13px;
                border-radius: 13px;
                font-size: 0.84rem;
            }
        }

        .loading-state,
        .table-card {
            border-radius: 28px;
            background: linear-gradient(135deg, #fffaf4 0%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 22px 40px rgba(15, 23, 42, 0.05);
        }

        .loading-state {
            margin-bottom: 18px;
            padding: 42px 24px;
            min-height: 170px;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            text-align: center;
        }

        .loading-state p {
            margin: 0;
            color: #334155;
            font-size: 1rem;
            font-weight: 700;
        }

        .loading-spinner {
            border-top-color: #d97706 !important;
            border-right-color: rgba(245, 158, 11, 0.18) !important;
            border-bottom-color: rgba(245, 158, 11, 0.18) !important;
            border-left-color: rgba(245, 158, 11, 0.18) !important;
        }

        .table-header {
            padding: 22px 24px 16px;
            border-bottom: 1px solid rgba(245, 158, 11, 0.1);
        }

        .table-header h2 {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 0;
            color: #0f172a;
            font-size: 1.25rem;
            font-weight: 800;
        }

        .table-responsive {
            padding: 0 8px 8px;
        }

        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .data-table thead th {
            padding: 18px 18px;
            background: #fffaf0;
            border-bottom: 1px solid rgba(245, 158, 11, 0.12);
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .data-table thead th:first-child {
            border-top-left-radius: 18px;
        }

        .data-table thead th:last-child {
            border-top-right-radius: 18px;
        }

        .data-table tbody td {
            padding: 18px;
            border-bottom: 1px solid rgba(241, 245, 249, 0.95);
            color: #334155;
            font-size: 0.94rem;
            background: rgba(255, 255, 255, 0.88);
        }

        .data-table tbody tr:hover td {
            background: #fff7ed;
        }

        .empty-state {
            padding: 52px 24px !important;
            text-align: center;
            color: #64748b;
        }

        .empty-state i {
            font-size: 2rem;
            color: #f59e0b;
            margin-bottom: 12px;
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 16px 24px 24px;
            color: #64748b;
        }

        .pagination-info {
            font-size: 0.9rem;
            font-weight: 600;
        }

        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pagination-btn {
            min-width: 42px;
            height: 42px;
            padding: 0 14px;
            border-radius: 14px;
            border: 1px solid rgba(245, 158, 11, 0.12);
            background: #fff;
            color: #b45309;
            font-weight: 700;
        }

        .pagination-btn.active {
            background: linear-gradient(135deg, #d14b24 0%, #dd8a11 100%);
            border-color: transparent;
            color: #fff;
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .management-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {
            .management-container {
                padding: 6px 14px 24px;
            }

            .page-header,
            .alert-banner,
            .search-filter-bar,
            .pagination {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-actions {
                width: 100%;
            }

            .filter-select,
            .btn-filter,
            .btn-primary,
            .btn-outline {
                width: 100%;
            }
        }

        @media (max-width: 640px) {
            .management-container {
                padding: 6px 12px calc(env(safe-area-inset-bottom, 0px) + 92px);
            }

            .management-station-hero {
                padding: 18px 16px;
                gap: 16px;
                border-radius: 22px;
            }

            .management-eyebrow {
                font-size: 0.62rem;
            }

            .management-station-hero h2 {
                font-size: 1.3rem;
                line-height: 1.08;
            }

            .management-station-subcopy {
                font-size: 0.85rem;
            }

            .management-station-meta {
                gap: 8px;
            }

            .management-station-meta span {
                width: 100%;
                min-height: 34px;
                padding: 8px 12px;
                font-size: 0.75rem;
                justify-content: flex-start;
            }

            .management-station-badge {
                padding: 14px 16px;
                border-radius: 16px;
            }

            .management-station-badge strong {
                font-size: 0.92rem;
            }

            .management-station-actions .btn-outline {
                min-width: 0;
            }

            .station-media-panel {
                gap: 16px;
                padding: 16px;
                border-radius: 22px;
            }

            .station-media-profile {
                min-height: 210px;
                padding: 12px;
                border-radius: 18px;
            }

            .station-media-card {
                padding: 14px;
                border-radius: 18px;
            }

            .station-media-card h3 {
                font-size: 0.92rem;
            }

            .station-media-card p {
                font-size: 0.78rem;
            }

            .station-gallery-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .station-gallery-item {
                border-radius: 16px;
                max-width: none;
            }

            .station-gallery-menu {
                top: 6px;
                right: 6px;
            }

            .station-gallery-menu-button {
                width: 26px;
                height: 26px;
                font-size: 0.72rem;
            }

            .station-gallery-menu-dropdown {
                top: calc(100% + 4px);
                right: -6px;
                min-width: 112px;
                padding: 5px;
                border-radius: 10px;
            }

            .station-gallery-menu-item {
                padding: 8px 9px;
                font-size: 0.72rem;
                gap: 6px;
            }

            .station-gallery-meta {
                padding: 18px 8px 8px;
            }

            .station-gallery-meta-title {
                font-size: 0.66rem;
            }

            .station-gallery-preview {
                gap: 10px;
            }

            .station-gallery-preview-frame {
                border-radius: 14px;
            }

            .station-gallery-preview-frame img {
                max-height: 34vh;
            }

            .station-gallery-preview-info {
                gap: 8px;
            }

            .station-gallery-preview-row {
                grid-template-columns: 68px minmax(0, 1fr);
                gap: 8px;
                padding-bottom: 8px;
            }

            .station-gallery-preview-label {
                font-size: 0.62rem;
            }

            .station-gallery-preview-value {
                font-size: 0.76rem;
            }

            .toast-container {
                top: auto !important;
                right: auto !important;
                left: 50% !important;
                bottom: calc(env(safe-area-inset-bottom, 0px) + 78px) !important;
                transform: translateX(-50%);
                width: min(calc(100vw - 24px), 360px);
            }

            .toast {
                padding: 12px 14px;
                border-radius: 16px;
            }

            .toast-message {
                font-size: 0.78rem;
            }

            .station-upload-box {
                padding: 12px;
                border-radius: 16px;
            }

            .station-upload-box input[type="file"]::file-selector-button {
                width: 100%;
                margin: 0 0 10px;
                display: block;
            }

            .station-upload-help {
                font-size: 0.72rem;
            }

            .station-upload-progress {
                padding: 10px 12px;
                border-radius: 14px;
            }

            .station-upload-progress-head {
                font-size: 0.72rem;
            }

            .station-upload-progress-copy {
                font-size: 0.72rem;
            }

            .management-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                margin-bottom: 16px;
            }

            .management-summary-card {
                gap: 10px;
                padding: 12px;
                border-radius: 18px;
                align-items: center;
            }

            .management-summary-icon {
                width: 36px;
                height: 36px;
                flex-basis: 36px;
                border-radius: 12px;
                box-shadow: none;
            }

            .management-summary-icon i {
                font-size: 0.82rem;
            }

            .management-summary-label {
                font-size: 0.62rem;
                letter-spacing: 0.06em;
            }

            .management-summary-value {
                font-size: 1.18rem;
            }

            .management-summary-meta {
                font-size: 0.72rem;
                line-height: 1.2;
            }

            .page-header {
                padding: 20px 16px;
                border-radius: 22px;
            }

            .page-header h1 {
                font-size: 1.35rem;
            }

            .tab-navigation {
                gap: 8px;
                padding: 10px;
                margin-bottom: 16px;
                border-radius: 20px;
            }

            .tab-btn {
                width: 100%;
                justify-content: center;
                min-height: 42px;
                font-size: 0.82rem;
                gap: 8px;
            }

            .search-filter-bar {
                gap: 12px;
                margin-bottom: 16px;
                padding: 14px;
                border-radius: 20px;
            }

            .search-box {
                min-height: 46px;
                padding: 0 14px;
                border-radius: 16px;
                gap: 10px;
            }

            .search-box input {
                font-size: 0.84rem;
            }

            .filter-actions {
                display: grid;
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .filter-select,
            .btn-filter,
            .btn-primary,
            .btn-outline {
                min-height: 44px;
                font-size: 0.82rem;
                border-radius: 14px !important;
            }

            .table-card {
                border-radius: 22px;
            }

            .table-header {
                padding: 16px 16px 12px;
            }

            .table-header h2 {
                gap: 10px;
                font-size: 1rem;
            }

            .table-header h2 i {
                width: 38px;
                height: 38px;
                border-radius: 14px;
                font-size: 0.9rem;
            }

            .table-responsive {
                display: flex;
                justify-content: center;
                padding: 0 10px 10px;
            }

            .data-table,
            .data-table thead,
            .data-table tbody,
            .data-table tr,
            .data-table th,
            .data-table td {
                display: block;
                width: 100%;
            }

            .data-table {
                min-width: 0;
                border-collapse: separate;
            }

            .data-table thead {
                display: none;
            }

            .data-table tbody {
                display: grid;
                gap: 10px;
            }

            .data-table tbody tr {
                border: 1px solid rgba(245, 158, 11, 0.12);
                border-radius: 18px;
                background: linear-gradient(180deg, #fffdf9 0%, #ffffff 100%);
                box-shadow: 0 10px 22px rgba(15, 23, 42, 0.05);
                overflow: hidden;
                padding: 4px 0;
            }

            .data-table tbody td {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 12px;
                padding: 10px 12px;
                font-size: 0.78rem;
                line-height: 1.45;
                text-align: right;
                border-bottom: 1px solid rgba(241, 245, 249, 0.95);
                background: transparent;
            }

            .data-table tbody td::before {
                content: attr(data-label);
                flex: 0 0 42%;
                max-width: 42%;
                color: #64748b;
                font-size: 0.64rem;
                font-weight: 800;
                letter-spacing: 0.1em;
                text-transform: uppercase;
                text-align: left;
            }

            .data-table tbody td:last-child {
                border-bottom: none;
            }

            .data-table tbody td[data-label="Actions"] {
                display: block;
                text-align: left;
            }

            .data-table tbody td[data-label="Actions"]::before {
                display: block;
                max-width: none;
                margin-bottom: 8px;
            }

            .data-table tbody td .cell-content,
            .data-table tbody td .location-cell,
            .data-table tbody td .avatar-group {
                width: 100%;
                justify-content: flex-end;
            }

            .data-table tbody td .location-cell {
                gap: 8px;
            }

            .data-table tbody td .action-buttons {
                justify-content: flex-start;
                gap: 8px;
            }

            .action-btn {
                width: 34px;
                height: 34px;
                border-radius: 10px;
            }

            .empty-state {
                display: block !important;
                padding: 32px 18px !important;
                text-align: center !important;
            }

            .empty-state::before {
                display: none !important;
                content: none !important;
            }

            .empty-state h3 {
                font-size: 1rem;
            }

            .empty-state p {
                font-size: 0.82rem;
                line-height: 1.5;
            }

            .pagination {
                flex-direction: column;
                align-items: center;
                gap: 10px;
                padding: 12px 16px 18px;
                text-align: center;
            }

            .pagination-info {
                font-size: 0.8rem;
            }

            .pagination-controls {
                flex-wrap: wrap;
                justify-content: center;
            }

            .pagination-btn {
                min-width: 38px;
                height: 38px;
                border-radius: 12px;
                padding: 0 12px;
            }

            .toast-container {
                top: auto !important;
                right: auto !important;
                left: 50% !important;
                bottom: calc(env(safe-area-inset-bottom, 0px) + 78px) !important;
                transform: translateX(-50%);
                width: min(calc(100vw - 24px), 360px);
                z-index: 9999;
            }

            .toast {
                min-width: 0;
                width: 100%;
                margin-bottom: 0;
                padding: 12px 14px;
                border-radius: 18px;
                box-shadow: 0 18px 36px rgba(15, 23, 42, 0.16);
                display: grid;
                grid-template-columns: auto 1fr auto;
                gap: 10px;
                align-items: center;
                animation: slideInUp 0.24s ease;
            }

            .toast i {
                font-size: 1rem;
            }

            .toast-message {
                font-size: 0.78rem;
                line-height: 1.4;
            }

            .toast-close {
                width: 28px;
                height: 28px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 999px;
                padding: 0;
            }
        }

        @keyframes slideInUp {
            from {
                transform: translateY(16px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 640px) {
            .tab-navigation {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 6px;
                padding: 8px;
                border-radius: 22px;
            }

            .tab-btn {
                width: auto;
                min-height: 50px;
                padding: 8px 4px;
                border-radius: 14px;
                font-size: 0.64rem;
                line-height: 1.15;
                flex-direction: column;
                justify-content: center;
                gap: 4px;
                text-align: center;
            }

            .tab-btn i {
                font-size: 0.82rem;
            }

            .tab-btn.active {
                box-shadow: 0 8px 18px rgba(209, 75, 36, 0.16);
            }

            .search-filter-bar {
                padding: 12px;
                border-radius: 20px;
            }

            .search-box {
                min-height: 42px;
                padding: 0 12px;
            }

            .search-box i {
                font-size: 0.88rem;
            }

            .search-box input {
                font-size: 0.8rem;
            }

            .filter-actions {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 42px 42px;
                gap: 8px;
                align-items: stretch;
            }

            .filter-select {
                min-width: 0;
                width: 100%;
                padding: 0 10px;
                font-size: 0.74rem;
            }

            .btn-filter,
            .btn-outline {
                width: 42px;
                min-width: 42px;
                padding: 0;
                justify-content: center;
            }

            .btn-filter .btn-label,
            .btn-outline .btn-label {
                display: none;
            }

            .btn-filter i,
            .btn-outline i {
                margin: 0;
                font-size: 0.9rem;
            }

            .table-card {
                border-radius: 22px;
                overflow: hidden;
            }

            .table-header {
                padding: 14px 16px 10px;
            }

            .table-header h2 {
                font-size: 0.96rem;
            }

            .table-header h2 i {
                width: 34px;
                height: 34px;
                border-radius: 12px;
                font-size: 0.84rem;
            }

            .table-responsive {
                padding: 0 12px 12px;
                display: block;
            }

            .data-table tbody {
                gap: 8px;
            }

            .data-table tbody tr:not(.table-empty-row) {
                display: block;
                border-radius: 16px;
                padding: 4px 0;
                box-shadow: 0 8px 18px rgba(15, 23, 42, 0.045);
            }

            .data-table tbody tr:not(.table-empty-row) td {
                display: grid;
                grid-template-columns: 84px minmax(0, 1fr);
                align-items: start;
                gap: 8px;
                padding: 8px 12px;
                font-size: 0.74rem;
                text-align: left;
            }

            .data-table tbody tr:not(.table-empty-row) td::before {
                flex-basis: auto;
                max-width: none;
                font-size: 0.58rem;
                line-height: 1.35;
            }

            .data-table tbody tr.table-empty-row {
                display: block;
                background: transparent;
                border: none;
                box-shadow: none;
                padding: 0;
            }

            .data-table tbody tr.table-empty-row td {
                display: block;
                width: 100%;
                padding: 0;
                border: none;
                background: transparent;
                text-align: left;
            }

            .empty-state {
                border: 1px solid rgba(245, 158, 11, 0.16);
                border-radius: 18px;
                background: linear-gradient(180deg, #fffdf9 0%, #ffffff 100%);
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
                padding: 24px 16px !important;
            }

            .empty-state i {
                font-size: 1.2rem;
                margin-bottom: 8px;
            }

            .empty-state h3 {
                font-size: 0.96rem;
                margin-bottom: 6px;
            }

            .empty-state p {
                font-size: 0.78rem;
                margin-bottom: 0;
            }

            .pagination {
                padding: 8px 16px 16px;
                gap: 8px;
            }

            .pagination-info {
                font-size: 0.76rem;
            }

            .pagination-btn {
                min-width: 34px;
                height: 34px;
                padding: 0 10px;
                border-radius: 10px;
            }
        }

        @media (max-width: 640px) {
            .management-station-hero {
                margin: 8px 0 8px;
            }

            .tab-navigation {
                margin-bottom: 4px;
            }

            .management-summary-grid {
                margin-bottom: 4px;
            }

            .search-filter-bar {
                display: flex !important;
                align-items: center !important;
                gap: 8px !important;
                padding: 10px !important;
                margin: 0 0 4px !important;
                flex-direction: row !important;
            }

            .search-box {
                flex: 1 1 auto;
                min-width: 0;
                min-height: 42px;
                margin: 0;
                padding: 0 12px;
                width: auto;
            }

            .search-box input {
                min-width: 0;
                font-size: 0.8rem;
            }

            .filter-actions {
                flex: 0 0 auto;
                display: grid !important;
                grid-template-columns: repeat(4, 42px);
                gap: 8px;
                align-items: center;
                width: auto !important;
            }

            .mobile-filter-slot {
                position: relative;
                width: 42px;
                height: 42px;
                min-width: 42px;
            }

            .mobile-filter-icon {
                position: absolute;
                inset: 0;
                display: grid;
                place-items: center;
                color: #b45309;
                pointer-events: none;
                z-index: 1;
                font-size: 0.88rem;
            }

            .mobile-filter-slot .filter-select {
                width: 42px;
                min-width: 42px;
                height: 42px;
                min-height: 42px;
                padding: 0;
                border-radius: 14px !important;
                appearance: none;
                -webkit-appearance: none;
                color: transparent;
                font-size: 0.82rem;
                text-indent: 150%;
                white-space: nowrap;
                overflow: hidden;
                background: #fff;
                border: 1px solid rgba(245, 158, 11, 0.14);
                box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04);
                cursor: pointer;
            }

            .mobile-filter-slot .filter-select:focus {
                color: transparent;
            }

            .mobile-filter-slot .filter-select option {
                color: #334155;
                font-size: 0.82rem;
                text-indent: 0;
            }

            .filter-actions > .btn-filter,
            .filter-actions > .btn-outline {
                width: 42px !important;
                min-width: 42px !important;
                height: 42px;
                min-height: 42px !important;
                padding: 0 !important;
                border-radius: 14px !important;
                justify-content: center;
                flex: 0 0 42px !important;
            }

            .filter-actions > .btn-filter .btn-label,
            .filter-actions > .btn-outline .btn-label {
                display: none !important;
            }

            .filter-actions > .btn-filter i,
            .filter-actions > .btn-outline i {
                margin: 0;
                font-size: 0.9rem;
            }

            .table-card {
                margin-top: 0;
            }

            .station-media-panel {
                margin-bottom: 12px;
            }

            .management-container > .search-filter-bar,
            .management-container > .table-card,
            .management-container > .loading-state {
                margin-top: 0 !important;
                margin-bottom: 4px !important;
            }

            .management-container > .table-card:last-child,
            .management-container > .loading-state:last-child {
                margin-bottom: 0 !important;
            }

            .management-container > .management-station-hero,
            .management-container > .station-media-panel,
            .management-container > .management-summary-grid,
            .management-container > .tab-navigation {
                margin-bottom: 4px !important;
            }

            .management-container > .tab-navigation + .search-filter-bar,
            .management-container > .search-filter-bar + .table-card {
                margin-top: 0 !important;
            }

            .management-station-actions .btn-outline,
            .station-media-card .btn-outline,
            .station-media-card .btn-primary {
                width: 100% !important;
                min-width: 0 !important;
                min-height: 42px !important;
                padding: 0 14px !important;
                justify-content: center;
                border-radius: 14px !important;
            }

            .station-media-card form {
                gap: 10px;
            }

            .data-table tbody td[data-label="Actions"] {
                display: grid;
                grid-template-columns: 84px minmax(0, 1fr);
                align-items: center;
                gap: 8px;
                text-align: left;
            }

            .data-table tbody td[data-label="Actions"]::before {
                display: block;
                margin-bottom: 0;
            }

            .data-table tbody td .cell-content,
            .data-table tbody td .location-cell,
            .data-table tbody td .avatar-group,
            .data-table tbody td .action-buttons {
                width: 100%;
                justify-content: flex-start;
                text-align: left;
            }

            .data-table tbody td .avatar-group {
                gap: 8px;
            }

            .data-table tbody td .avatar-group .cell-copy {
                min-width: 0;
            }

            .data-table tbody td .action-buttons {
                flex-wrap: nowrap;
                gap: 6px;
            }

            .data-table tbody td .action-buttons .action-btn {
                width: 32px;
                height: 32px;
                min-width: 32px;
            }

            .pagination {
                padding-top: 6px;
            }
        }

        /* Final management overrides */
        #viewModal .modal-content,
        #formModal .modal-content,
        #ecatModal .modal-content {
            width: min(560px, calc(100vw - 48px));
            max-width: 560px;
            max-height: min(calc(100vh - 56px), 680px);
        }

        #deleteModal .modal-content {
            width: min(420px, calc(100vw - 48px));
            max-width: 420px;
            max-height: min(calc(100vh - 56px), 420px);
        }

        #editStationModal .modal-content {
            width: min(560px, calc(100vw - 48px));
            max-width: 560px !important;
            max-height: min(calc(100vh - 56px), 700px);
        }

        #galleryPreviewModal .modal-content {
            width: min(680px, calc(100vw - 56px));
            max-width: 680px;
            max-height: min(calc(100vh - 56px), 720px);
        }

        #galleryEditModal .modal-content,
        #galleryDeleteModal .modal-content {
            width: min(420px, calc(100vw - 48px));
            max-width: 420px;
            max-height: min(calc(100vh - 56px), 440px);
        }

        .modal-header {
            padding: 18px 20px 14px;
        }

        .modal-header h2 {
            font-size: 1.08rem;
        }

        .modal-body {
            padding: 16px 20px;
        }

        .modal-footer {
            padding: 0 20px 18px;
        }

        #viewModal .detail-container {
            gap: 14px;
        }

        #viewModal .detail-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            padding-bottom: 12px;
            margin-bottom: 10px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.9);
        }

        #viewModal .personnel-title,
        #viewModal .detail-header h2 {
            margin: 0;
            font-size: 1.15rem;
            line-height: 1.2;
        }

        #viewModal .personnel-detail-grid,
        #viewModal .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        #viewModal .info-card {
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(226, 232, 240, 0.95);
            background: #fff;
        }

        #viewModal .card-title {
            padding: 14px 16px;
        }

        #viewModal .card-content,
        #viewModal .info-list {
            padding: 12px 16px;
        }

        #viewModal .detail-row,
        #viewModal .info-row {
            padding: 10px 0;
        }

        @media (max-width: 640px) {
            .modal {
                align-items: flex-end;
                padding: 10px 8px calc(env(safe-area-inset-bottom, 0px) + 156px);
            }

            #viewModal .modal-content,
            #formModal .modal-content,
            #deleteModal .modal-content,
            #editStationModal .modal-content,
            #ecatModal .modal-content {
                width: 100%;
                max-width: 100% !important;
                max-height: min(calc(100dvh - env(safe-area-inset-bottom, 0px) - 178px), 500px);
                border-radius: 14px 14px 10px 10px;
                margin: 0 auto 16px;
            }

            .modal-header {
                gap: 6px;
                padding: 8px 8px 6px;
            }

            .modal-header h2 {
                flex: 1 1 auto;
                min-width: 0;
                font-size: 0.78rem;
                line-height: 1.1;
                white-space: normal;
                overflow-wrap: anywhere;
            }

            .modal-body {
                padding: 6px 8px 10px;
                overflow-y: auto;
            }

            .modal-footer {
                gap: 6px;
                padding: 6px 8px calc(env(safe-area-inset-bottom, 0px) + 22px);
                position: sticky;
                bottom: 0;
                background: linear-gradient(180deg, rgba(255,255,255,0.92) 0%, #ffffff 32%);
            }

            .modal-close {
                width: 28px;
                height: 28px;
                min-width: 28px;
                min-height: 28px;
                border-radius: 9px;
                font-size: 0.72rem;
                flex: 0 0 28px;
            }

            .modal-footer .btn-outline,
            .modal-footer .btn-primary {
                min-height: 34px;
                font-size: 0.72rem;
                border-radius: 10px !important;
                padding: 0 10px !important;
            }

            .form-group {
                margin-bottom: 8px;
            }

            .form-label {
                margin-bottom: 3px;
                font-size: 0.66rem;
                line-height: 1.2;
            }

            .form-label i {
                font-size: 0.62rem;
            }

            .form-input,
            .form-select,
            .form-textarea {
                padding: 8px 9px;
                border-radius: 10px;
                font-size: 0.72rem;
                line-height: 1.2;
            }

            .form-textarea {
                min-height: 74px;
            }

            .form-row {
                gap: 8px;
            }

            #viewModal .detail-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
                padding-bottom: 4px;
                margin-bottom: 2px;
            }

            #viewModal .personnel-title,
            #viewModal .detail-header h2 {
                font-size: 0.82rem;
            }

            #viewModal .personnel-detail-grid,
            #viewModal .detail-grid {
                grid-template-columns: 1fr;
                gap: 4px;
            }

            #viewModal .detail-container {
                gap: 4px;
            }

            #viewModal .personnel-id,
            #viewModal .incident-id {
                font-size: 0.66rem;
            }

            #viewModal .info-card {
                border: none;
                border-radius: 0;
                background: transparent;
                box-shadow: none;
                overflow: visible;
            }

            #viewModal .card-title {
                gap: 6px;
                padding: 1px 0 3px;
                border: none;
                background: transparent;
            }

            #viewModal .card-title i {
                font-size: 0.72rem;
            }

            #viewModal .card-title h3 {
                font-size: 0.7rem;
            }

            #viewModal .card-content,
            #viewModal .info-list {
                padding: 0;
                border: none;
                background: transparent;
            }

            #viewModal .info-row,
            #viewModal .detail-row {
                display: grid;
                grid-template-columns: 68px minmax(0, 1fr);
                gap: 6px;
                align-items: start;
                padding: 5px 0;
                border-bottom: 1px solid rgba(226, 232, 240, 0.85);
            }

            #viewModal .info-row:last-child,
            #viewModal .detail-row:last-child {
                border-bottom: none;
            }

            #viewModal .info-label,
            #viewModal .detail-label {
                font-size: 0.62rem;
                color: #64748b;
                font-weight: 700;
            }

            #viewModal .info-value,
            #viewModal .detail-value {
                font-size: 0.7rem;
                color: #1e293b;
                text-align: left;
                min-width: 0;
                word-break: break-word;
            }

            #viewModal .status-display {
                width: 100%;
            }

            #viewModal .status-badge-large {
                min-height: 24px;
                padding: 0 8px;
                font-size: 0.6rem;
                border-radius: 999px;
            }

            #deleteModal .modal-body p {
                margin: 8px 0 0 !important;
                font-size: 0.72rem;
                line-height: 1.35;
            }

            #deleteModal .modal-body i {
                font-size: 1.45rem !important;
                margin-bottom: 6px !important;
            }

            /* force mobile table back to compact single-row display */
            .table-responsive {
                display: block !important;
                overflow-x: auto;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
                padding: 0 8px 8px !important;
            }

            .data-table {
                display: table !important;
                width: 100%;
                min-width: 760px;
                border-collapse: separate;
                border-spacing: 0;
            }

            .data-table thead {
                display: table-header-group !important;
            }

            .data-table tbody {
                display: table-row-group !important;
            }

            .data-table tr {
                display: table-row !important;
            }

            .data-table th,
            .data-table td {
                display: table-cell !important;
                width: auto !important;
                vertical-align: middle;
                text-align: left;
                white-space: nowrap;
            }

            .data-table thead th {
                position: sticky;
                top: 0;
                z-index: 2;
                padding: 12px 10px;
                font-size: 0.66rem;
            }

            .data-table tbody tr {
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                background: transparent !important;
            }

            .data-table tbody td {
                padding: 10px;
                font-size: 0.72rem;
                line-height: 1.35;
                border-bottom: 1px solid rgba(241, 245, 249, 0.95);
                background: rgba(255, 255, 255, 0.88);
            }

            .data-table tbody td::before {
                content: none !important;
                display: none !important;
            }

            .data-table tbody td .cell-content,
            .data-table tbody td .location-cell,
            .data-table tbody td .avatar-group,
            .data-table tbody td .action-buttons {
                width: auto !important;
                justify-content: flex-start !important;
                text-align: left !important;
            }

            .data-table tbody td .avatar-group {
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }

            .data-table tbody td .action-buttons {
                display: inline-flex;
                flex-wrap: nowrap;
                gap: 6px;
            }

            .data-table tbody td .action-buttons .action-btn {
                width: 30px;
                height: 30px;
                min-width: 30px;
            }

            .data-table tbody tr.table-empty-row,
            .data-table tbody tr.table-empty-row td {
                display: table-row !important;
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
                white-space: normal;
            }

            .data-table tbody tr.table-empty-row td {
                display: table-cell !important;
                padding: 0 !important;
            }
        }

        /* Compact, responsive management dialogs. Keep this block last so the
           older generic modal rules cannot enlarge these station dialogs. */
        #viewModal .modal-content,
        #formModal .modal-content,
        #editStationModal .modal-content {
            display: flex;
            flex-direction: column;
            width: min(520px, calc(100vw - 32px));
            max-width: 520px !important;
            max-height: min(82dvh, 680px);
            overflow: hidden;
            border-radius: 20px;
        }

        #editStationModal .modal-content {
            width: min(500px, calc(100vw - 32px));
            max-width: 500px !important;
            height: min(82dvh, 680px);
            padding: 0 !important;
        }

        #editStationModal .modal-content > form {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            width: 100%;
            min-height: 0;
            overflow: hidden;
        }

        #deleteModal .modal-content {
            display: flex;
            flex-direction: column;
            width: min(360px, calc(100vw - 32px));
            max-width: 360px !important;
            max-height: min(78dvh, 330px);
            overflow: hidden;
            border-radius: 20px;
        }

        #viewModal .modal-header,
        #formModal .modal-header,
        #editStationModal .modal-header,
        #deleteModal .modal-header {
            flex: 0 0 auto;
            padding: 14px 16px 12px;
        }

        #viewModal .modal-header h2,
        #formModal .modal-header h2,
        #editStationModal .modal-header h2,
        #deleteModal .modal-header h2 {
            font-size: 1rem;
            line-height: 1.25;
        }

        #viewModal .modal-close,
        #formModal .modal-close,
        #editStationModal .modal-close,
        #deleteModal .modal-close {
            width: 32px;
            height: 32px;
            min-width: 32px;
            min-height: 32px;
            border-radius: 10px;
        }

        #viewModal .modal-body,
        #formModal .modal-body,
        #editStationModal .modal-body,
        #deleteModal .modal-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
            padding: 14px 16px;
        }

        #viewModal .modal-footer,
        #formModal .modal-footer,
        #editStationModal .modal-footer,
        #deleteModal .modal-footer {
            position: static;
            flex: 0 0 auto;
            padding: 10px 16px 14px;
            border-top: 1px solid rgba(226, 232, 240, 0.85);
            background: rgba(255, 255, 255, 0.96);
        }

        #editStationModal .form-group {
            margin-bottom: 12px;
        }

        #editStationModal .form-label {
            margin-bottom: 5px;
            font-size: 0.76rem;
        }

        #editStationModal .form-input {
            padding: 10px 12px;
            border-radius: 12px;
            font-size: 0.82rem;
        }

        #editStationModal .modal-body {
            margin: 0;
            -webkit-overflow-scrolling: touch;
        }

        #editStationModal .modal-header,
        #editStationModal .modal-footer {
            margin: 0;
        }

        #editStationModal .modal-footer {
            z-index: 2;
            box-shadow: 0 -8px 22px rgba(15, 23, 42, 0.06);
        }

        .management-delete-prompt {
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr);
            align-items: center;
            gap: 12px;
            margin: 0;
            color: #475569;
            font-size: 0.84rem;
            line-height: 1.45;
            text-align: left;
        }

        .management-delete-prompt__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 13px;
            background: #fef2f2;
            color: #dc2626;
            font-size: 1.1rem;
        }

        @media (max-width: 640px) {
            #viewModal.modal,
            #formModal.modal,
            #editStationModal.modal,
            #deleteModal.modal {
                align-items: center;
                padding: 12px;
            }

            #viewModal .modal-content,
            #formModal .modal-content,
            #editStationModal .modal-content {
                width: 100%;
                max-width: 100% !important;
                max-height: calc(100dvh - 24px);
                margin: 0;
                border-radius: 16px;
            }

            #deleteModal .modal-content {
                width: min(100%, 340px);
                max-width: 340px !important;
                max-height: calc(100dvh - 24px);
                margin: 0;
                border-radius: 16px;
            }

            #viewModal .modal-content::before,
            #formModal .modal-content::before,
            #editStationModal .modal-content::before,
            #deleteModal .modal-content::before {
                display: none;
            }

            #viewModal .modal-header,
            #formModal .modal-header,
            #editStationModal .modal-header,
            #deleteModal .modal-header {
                padding: 11px 12px 9px;
            }

            #viewModal .modal-header h2,
            #formModal .modal-header h2,
            #editStationModal .modal-header h2,
            #deleteModal .modal-header h2 {
                font-size: 0.9rem;
            }

            #viewModal .modal-body,
            #formModal .modal-body,
            #editStationModal .modal-body,
            #deleteModal .modal-body {
                padding: 10px 12px;
            }

            #viewModal .modal-footer,
            #formModal .modal-footer,
            #editStationModal .modal-footer,
            #deleteModal .modal-footer {
                padding: 9px 12px calc(10px + env(safe-area-inset-bottom, 0px));
            }

            #editStationModal .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .management-delete-prompt {
                grid-template-columns: 36px minmax(0, 1fr);
                gap: 10px;
                font-size: 0.76rem;
            }

            .management-delete-prompt__icon {
                width: 36px;
                height: 36px;
                border-radius: 11px;
                font-size: 0.95rem !important;
                margin: 0 !important;
            }
        }

        @media (max-width: 640px), (display-mode: standalone) and (max-width: 1024px) {
            #editStationModal.modal {
                align-items: flex-end;
                padding: max(8px, env(safe-area-inset-top, 0px)) 8px 0;
            }

            #editStationModal .modal-content {
                width: 100%;
                max-width: 100% !important;
                height: min(92dvh, 760px);
                max-height: calc(100dvh - max(8px, env(safe-area-inset-top, 0px)));
                margin: 0;
                border-radius: 20px 20px 0 0;
            }

            #editStationModal .modal-header {
                padding: 12px 13px 10px;
            }

            #editStationModal .modal-body {
                padding: 12px;
            }

            #editStationModal .modal-footer {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1.35fr);
                gap: 8px;
                padding: 10px 12px max(12px, env(safe-area-inset-bottom, 0px));
                background: #fff;
            }

            #editStationModal .modal-footer .btn-outline,
            #editStationModal .modal-footer .btn-primary {
                width: 100%;
                min-width: 0;
                min-height: 44px;
                justify-content: center;
            }
        }
        </style>
    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css', 'resources/css/admin/announcements.css', 'resources/css/admin/management.css'])
    @vite(['resources/css/admin/resource-management.css'])

    @include('chief.partials.station-management-presentation')
</head>
@php
    $currentStation = ($stations ?? collect())->first();
    $currentStationMarker = $currentStation?->marker;
    $currentRoleName = strtolower(auth()->user()->role->role ?? '');
    $currentStationProfilePath = $currentStation?->profileMedia?->path;
    $currentStationProfileUrl = \App\Support\MediaPath::url($currentStationProfilePath);
    $coverageCities = collect($managedBarangays ?? [])->pluck('city')->filter()->unique('city_id')->values();
    $suggestedStationCity = !$currentStation?->address_id && $coverageCities->count() === 1
        ? $coverageCities->first()
        : null;
    $stationAddressCity = $currentStation?->address?->barangay?->city ?? $suggestedStationCity;
    $stationAddressProvince = $stationAddressCity?->province;
    $currentStationGallery = collect($currentStation?->galleryMedia ?? [])->map(function ($media) {
        $path = $media->path ?? '';
        $parsedPath = parse_url($path, PHP_URL_PATH) ?: $path;
        $fileStem = pathinfo($parsedPath, PATHINFO_FILENAME);

        return [
            'id' => $media->station_media_id,
            'caption' => $media->caption,
            'url' => \App\Support\MediaPath::url($media->path),
            'file_name' => $fileStem ?: ('station-photo-' . $media->station_media_id),
        ];
    });
@endphp
<body class="chief-legacy-layout station-management-page">
    @include('chief.partials.sidebar', ['active' => 'management'])
    <aside class="sidebar legacy-chief-sidebar" style="display:none;">
        <div class="sidebar-header">
            <div class="sidebar-header-content">
               <div class="logo-area">
                    <div class="logo-icon">
                        @include('partials.app-logo')
                    </div>
                    <div class="logo-text">
                        <h2>PyroSense</h2>
                        <p>command center</p>
                    </div>
                </div>
                <button id="closeSidebar"><i class="fa-solid fa-xmark"></i></button>
                <div class="user-info">
                    <div class="user-avatar">
                        <span>{{ substr(auth()->user()->first_name ?? 'JD', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'D', 0, 1) }}</span>
                    </div>
                    <div class="user-details">
                        <h4>{{ auth()->user()->first_name ?? 'John' }} {{ auth()->user()->last_name ?? 'Doe' }}</h4>
                        <span><i class="fa-solid fa-circle"></i> {{ auth()->user()->role->role ?? 'Chief Administrator' }}</span>
                        <span style="font-size: 11px; opacity: 0.6; margin-top: 2px;">
                            <i class="fa-regular fa-clock"></i> {{ now()->format('H:i · d M Y') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">MAIN</div>
                <a href="{{ route('station-admin.dashboard') }}" class="nav-item "><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a>
                <a href="{{ route('station-admin.fsic') }}" class="nav-item flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition group" data-section="alerts">
                            <i class="fa-solid fa-bell w-5 text-gray-400 group-hover:text-orange-600"></i>
                            <span class="flex-1">Requests</span>
                            <span class="bg-red-100 text-red-800 text-xs font-semibold px-2 py-1 rounded-full">{{ $activeAlerts ?? 2 }}</span>
                        </a>
                <a href="{{ route('station-admin.announcements') }}" class="nav-item"><i class="fa-solid fa-bullhorn"></i><span>Announcements</span></a>
                <a href="{{ route('station-admin.management') }}" class="nav-item active"><i class="fa-solid fa-map-location-dot"></i><span>Management</span></a>
                <a href="{{ route('station-admin.reports') }}" class="nav-item"><i class="fa-solid fa-chart-pie"></i><span>Reports</span></a>
                <a href="{{ route('station-admin.resource-management.index') }}" class="nav-item"><i class="fa-solid fa-toolbox"></i><span>Resources</span></a>
                
            </div>
            <div class="nav-section">
                <div class="nav-section-title">ANALYTICS</div>
                <a href="{{ route('station-admin.analytics') }}" class="nav-item"><i class="fa-solid fa-chart-line"></i><span>Fire Risk Forecast</span></a>
                {{-- <a href="#" class="nav-item"><i class="fa-solid fa-clock-rotate-left"></i><span>History</span></a>
                <a href="#" class="nav-item"><i class="fa-solid fa-bell"></i><span>Alerts</span></a> --}}
            </div>
        </nav>
        <div class="logout-container">
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="logout-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Logout</span></button></form>
        </div>
    </aside>

    <!-- BACKDROP for mobile -->
    <div id="sidebarBackdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden transition-opacity duration-300"></div>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <nav class="lector-top-bar">
            <div class="flex items-center gap-4">
                <button id="openSidebar" class="lg:hidden text-slate-600">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div class="hidden lg:block">
                    <h1 class="text-xl font-bold text-slate-800">Station Management</h1>
                    <p class="text-xs text-slate-500 font-medium">Command Resources And Registry • {{ now()->format('l, F j') }}</p>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <div class="user-dropdown">
                    <button id="userDropdownBtn" class="user-dropdown-toggle">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white text-[10px] font-bold shadow-sm shadow-amber-200">
                            {{ substr(auth()->user()->first_name ?? 'C', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'A', 0, 1) }}
                        </div>
                        <span class="text-sm font-bold text-slate-700 hidden sm:block">{{ auth()->user()->first_name }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 ml-1"></i>
                    </button>

                    <div id="userDropdownMenu" class="user-dropdown-menu">
                        <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                            <p class="text-sm font-bold text-slate-800">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
                            <p class="text-[10px] text-slate-500 font-medium">{{ auth()->user()->email }}</p>
                        </div>
                        <div class="p-2">
                            <a href="{{ route('station-admin.profile') }}" class="user-dropdown-item">
                                <i class="fa-regular fa-user"></i>
                                <span>Commander Profile</span>
                            </a>
                            <a href="#" class="user-dropdown-item chief-open-logs">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                                <span>My Logs</span>
                            </a>
                            <div class="h-px bg-slate-100 my-2"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="user-dropdown-item text-red-600 w-full text-left">
                                    <i class="fa-solid fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- MANAGEMENT CONTENT -->
        <div class="management-container chief-theme-shell station-management-workspace">
            @if(session('success'))
                <div class="station-flash success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="station-flash error">{{ $errors->first() }}</div>
            @endif

            <section class="chief-theme-hero">
                <div>
                    <span class="chief-theme-hero__eyebrow">
                        <i class="fa-solid fa-building-shield"></i>
                        Current Command Station
                    </span>
                    <h2 class="chief-theme-hero__title">{{ $currentStation?->station_name ?? 'Station Not Assigned' }}</h2>
                    <p class="chief-theme-hero__copy">
                        {{ $currentStation?->station_code ? 'Code: '.$currentStation->station_code.' • ' : '' }}
                        {{ $currentStationMarker?->title ?? 'No mapped station location available yet.' }}
                    </p>
                    <div class="chief-theme-badges">
                        <span class="chief-theme-badge"><i class="fa-solid fa-phone"></i> {{ $currentStation?->contact_number ?? 'No contact number' }}</span>
                        <span class="chief-theme-badge"><i class="fa-solid fa-envelope"></i> {{ $currentStation?->email ?? 'No email registered' }}</span>
                        <span class="chief-theme-badge"><i class="fa-solid fa-location-dot"></i> {{ collect([$currentStation?->address?->street, $currentStation?->address?->barangay?->barangay_name, $currentStation?->address?->barangay?->city?->city_name])->filter()->join(', ') ?: 'Station address not configured' }}</span>
                        <span class="chief-theme-badge"><i class="fa-solid fa-users"></i> {{ $personnel->count() }} personnel records loaded</span>
                    </div>
                </div>
                <div class="chief-theme-summary-grid">
                    <article class="chief-theme-summary-card">
                        <div class="chief-theme-summary-label">Gallery photos</div>
                        <div class="chief-theme-summary-value">{{ $currentStationGallery->count() }}</div>
                        <div class="chief-theme-summary-copy">Photos saved for this station.</div>
                    </article>
                    @if($currentRoleName === 'chief' && $currentStation)
                        <article class="chief-theme-summary-card">
                            <div class="chief-theme-summary-label">Command Action</div>
                            <div class="chief-theme-actions">
                                <button type="button" class="btn-outline" id="editStationBtn">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    Edit Station
                                </button>
                            </div>
                        </article>
                    @endif
                </div>
            </section>

            @if($currentStation && in_array($currentRoleName, ['chief', 'station admin'], true))
                <section class="station-media-panel" aria-label="Station profile and gallery">
                    <div class="station-media-tools">
                        <div class="station-media-card station-profile-card">
                            <h3>Station profile</h3>
                            <p>The main photo shown on the firefighter station page.</p>
                            <div class="station-media-profile">
                                @if($currentStationProfileUrl)
                                    <img src="{{ $currentStationProfileUrl }}" alt="{{ $currentStation->station_name }}" decoding="async">
                                @else
                                    <div class="station-photo-empty"><i class="fa-solid fa-building-shield" aria-hidden="true"></i><span>No profile photo yet</span></div>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('station-admin.stations.media.profile', $currentStation->station_id) }}" enctype="multipart/form-data" class="station-upload-form" data-upload-label="Profile image">
                                @csrf
                                <div class="station-upload-field">
                                    <label class="station-upload-label" for="profileImageUpload">
                                        <i class="fa-solid fa-image"></i>
                                        Choose profile image
                                    </label>
                                    <div class="station-upload-box">
                                        <input id="profileImageUpload" type="file" name="profile_image" accept="image/jpeg,image/png,image/webp" required>
                                        <span class="station-upload-help">JPG, PNG, or WEBP · Up to 5 MB. Choose a clear station photo.</span>
                                    </div>
                                </div>
                                <div class="station-upload-progress" data-upload-progress>
                                    <div class="station-upload-progress-head">
                                        <span class="station-upload-progress-label"><i class="fa-solid fa-cloud-arrow-up"></i> Uploading profile image</span>
                                        <span data-upload-percent>0%</span>
                                    </div>
                                    <div class="station-upload-progress-bar">
                                        <div class="station-upload-progress-fill" data-upload-progress-fill></div>
                                    </div>
                                    <div class="station-upload-progress-copy" data-upload-progress-copy>Preparing file for upload...</div>
                                </div>
                                <button type="submit" class="btn-primary station-upload-submit" data-upload-submit>
                                    <i class="fa-solid fa-upload"></i>
                                    <span data-upload-submit-label>Save Profile Image</span>
                                </button>
                            </form>
                        </div>

                        <div class="station-media-card station-gallery-upload-card">
                            <h3>Add gallery photos</h3>
                            <p>Show the station grounds, apparatus bay, and facilities.</p>
                            <form method="POST" action="{{ route('station-admin.stations.media.gallery', $currentStation->station_id) }}" enctype="multipart/form-data" class="station-upload-form" data-upload-label="Gallery photos">
                                @csrf
                                <div class="station-upload-field">
                                    <label class="station-upload-label" for="galleryImagesUpload">
                                        <i class="fa-solid fa-images"></i>
                                        Choose gallery photos
                                    </label>
                                    <div class="station-upload-box">
                                        <input id="galleryImagesUpload" type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple required>
                                        <span class="station-upload-help">Up to 6 photos per upload · JPG, PNG, or WEBP · 5 MB each.</span>
                                    </div>
                                </div>
                                <label class="station-upload-label" for="stationGalleryCaption">Caption <span>(optional)</span></label>
                                <input id="stationGalleryCaption" type="text" name="caption" maxlength="255" placeholder="Describe these photos">
                                <div class="station-upload-progress" data-upload-progress>
                                    <div class="station-upload-progress-head">
                                        <span class="station-upload-progress-label"><i class="fa-solid fa-images"></i> Uploading gallery photos</span>
                                        <span data-upload-percent>0%</span>
                                    </div>
                                    <div class="station-upload-progress-bar">
                                        <div class="station-upload-progress-fill" data-upload-progress-fill></div>
                                    </div>
                                    <div class="station-upload-progress-copy" data-upload-progress-copy>Preparing files for upload...</div>
                                </div>
                                <button type="submit" class="btn-outline station-upload-submit" data-upload-submit>
                                    <i class="fa-solid fa-images"></i>
                                    <span data-upload-submit-label>Add Gallery Photos</span>
                                </button>
                            </form>
                        </div>

                        <div class="station-media-card station-gallery-library">
                            <h3>Photo gallery <span class="station-gallery-count">{{ $currentStationGallery->count() }}</span></h3>
                            <p>Open a photo to view it in full. Use its menu to edit the caption or delete it.</p>
                            @if($currentStationGallery->isEmpty())
                                <div class="station-photo-empty"><i class="fa-solid fa-images" aria-hidden="true"></i><strong>Your station gallery starts here</strong><span>Add photos using the upload form above.</span></div>
                            @else
                                <div class="station-gallery-grid">
                                    @foreach($currentStationGallery as $photo)
                                        <div class="station-gallery-item">
                                            <button
                                                type="button"
                                                class="station-gallery-trigger"
                                                data-gallery-open
                                                data-gallery-image="{{ $photo['url'] }}"
                                                data-gallery-caption="{{ e($photo['caption'] ?: 'No caption added.') }}"
                                                data-gallery-name="{{ e($photo['file_name']) }}"
                                            >
                                                <img src="{{ $photo['url'] }}" alt="{{ $photo['caption'] ?: 'Station photo '.$loop->iteration }}" loading="lazy" decoding="async">
                                                <div class="station-gallery-meta">
                                                    <span class="station-gallery-meta-title">{{ $photo['caption'] ?: 'Station photo '.$loop->iteration }}</span>
                                                    <small>{{ $photo['file_name'] }}</small>
                                                </div>
                                            </button>
                                            <div class="station-gallery-menu" data-gallery-menu>
                                                <button type="button" class="station-gallery-menu-button" data-gallery-menu-toggle aria-label="Gallery actions">
                                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                                </button>
                                                <div class="station-gallery-menu-dropdown">
                                                    <button
                                                        type="button"
                                                        class="station-gallery-menu-item"
                                                        data-gallery-edit
                                                        data-gallery-id="{{ $photo['id'] }}"
                                                        data-gallery-caption="{{ e($photo['caption']) }}"
                                                    >
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                        Edit
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="station-gallery-menu-item delete"
                                                        data-gallery-delete
                                                        data-gallery-id="{{ $photo['id'] }}"
                                                        data-gallery-name="{{ e($photo['file_name']) }}"
                                                    >
                                                        <i class="fa-solid fa-trash"></i>
                                                        Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </section>
            @endif

            <!-- Page Header -->
            <div class="page-header">
                <h1>
                    <i class="fa-solid fa-table"></i>
                    Station Registry
                </h1>
                <div class="date">
                    <i class="fa-regular fa-calendar"></i> {{ now()->format('d M Y · H:i') }} hrs
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="tab-navigation">
                <button class="tab-btn active" data-tab="incidents">
                    <i class="fa-solid fa-fire"></i>
                    Incidents
                </button>
                <button class="tab-btn" data-tab="reports">
                    <i class="fa-solid fa-file-lines"></i>
                    Reports
                </button>
                <button class="tab-btn" data-tab="personnel">
                    <i class="fa-solid fa-users"></i>
                    Personnel
                </button>
                <button class="tab-btn" data-tab="equipment">
                    <i class="fa-solid fa-toolbox"></i>
                    Equipment
                </button>
            </div>

            <!-- Stats Grid -->
            <div class="management-summary-grid">
                <div class="management-summary-card">
                    <div class="management-summary-icon">
                        <i class="fa-solid fa-fire"></i>
                    </div>
                    <div class="management-summary-copy">
                        <span class="management-summary-label">Total Incidents</span>
                        <strong class="management-summary-value" id="totalIncidents">{{$totalIncidents->count() ?? 0 }}</strong>
                        <small class="management-summary-meta" id="activeIncidents">0 active</small>
                    </div>
                </div>

                <div class="management-summary-card">
                    <div class="management-summary-icon">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>
                    <div class="management-summary-copy">
                        <span class="management-summary-label">Reports</span>
                        <strong class="management-summary-value" id="totalReports">{{ $reports->count() ?? 0 }}</strong>
                        <small class="management-summary-meta" id="pendingReports">0 filed</small>
                    </div>
                </div>

                <div class="management-summary-card">
                    <div class="management-summary-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="management-summary-copy">
                        <span class="management-summary-label">Personnel</span>
                        <strong class="management-summary-value" id="totalPersonnel">{{ $personnel->count() ?? 0 }}</strong>
                        <small class="management-summary-meta">station roster</small>
                    </div>
                </div>

                <div class="management-summary-card">
                    <div class="management-summary-icon">
                        <i class="fa-solid fa-toolbox"></i>
                    </div>
                    <div class="management-summary-copy">
                        <span class="management-summary-label">Equipment</span>
                        <strong class="management-summary-value" id="totalEquipment">{{ $equipment->count() ?? 0 }}</strong>
                        <small class="management-summary-meta">station assigned</small>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Bar -->
            <div class="search-filter-bar">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" placeholder="Search...">
                </div>
                <div class="filter-actions">
                    <div class="mobile-filter-slot mobile-filter-slot--status" title="Filter by status">
                        <span class="mobile-filter-icon" aria-hidden="true">
                            <i class="fa-solid fa-filter"></i>
                        </span>
                        <select class="filter-select" id="filterSelect" aria-label="Filter by status">
                            <option value="">All Status</option>
                        </select>
                    </div>
                    <div class="mobile-filter-slot mobile-filter-slot--date" title="Filter by date">
                        <span class="mobile-filter-icon" aria-hidden="true">
                            <i class="fa-solid fa-calendar-days"></i>
                        </span>
                        <select class="filter-select" id="dateFilter" aria-label="Filter by date">
                            <option value="">All Dates</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                        </select>
                    </div>
                    <button class="btn-filter" id="refreshBtn" title="Refresh">
                        <i class="fa-solid fa-rotate"></i>
                        <span class="btn-label">Refresh</span>
                    </button>
                    <button class="btn-outline" id="exportBtn" title="Export">
                        <i class="fa-solid fa-download"></i>
                        <span class="btn-label">Export</span>
                    </button>
                    <button class="btn-primary" id="addNewBtn" title="Add historical fire record">
                        <i class="fa-solid fa-plus"></i>
                        <span class="btn-label">Add Historical Fire</span>
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="loading-state" style="display: none;">
                <div class="loading-spinner"></div>
                <p>Loading data...</p>
            </div>

            <!-- Data Table -->
            <div class="table-card" id="dataTable">
                <div class="table-header">
                    <h2>
                        <i class="fa-solid fa-list"></i>
                        <span id="tableTitle">Incidents</span>
                    </h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead id="tableHeader">
                            <tr>
                                {{-- <th>ID</th> --}}
                                {{-- <th>Title</th>
                                <th>Location</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Reported By</th>
                                <th>Date</th>
                                <th>Actions</th> --}}
                            </tr>
                        </thead>
                       <script>
                            // Pass data to JavaScript
                               window.rolesData = @json($roles);
                            window.incidentDb = @json($incidents);
                            window.reportDb = @json($reports);
                            window.personnelDb = @json($personnel);
                            window.equipmentDb = @json($equipment);
                            window.stationDb = @json($stations);
                            window.currentStationData = @json($currentStation);
                            window.managedBarangays = @json($managedBarangays ?? []);
                            window.csrfToken = '{{ csrf_token() }}';
                        </script>
                            <tbody id="tableBody">
                            
                           {{-- @if(!$totalIncidents)
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <i class="fa-regular fa-folder-open"></i>
                                    <h3>No data available</h3>
                                    <p>Click refresh to load data or add new entries.</p>
                                </td>
                            </tr>
                           @endif --}}
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div id="pagination" class="pagination">
                    <div class="pagination-info">Showing 0 to 0 of 0 entries</div>
                    <div class="pagination-controls">
                        <button class="pagination-btn" disabled><i class="fa-solid fa-chevron-left"></i></button>
                        <button class="pagination-btn active">1</button>
                        <button class="pagination-btn" disabled><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- View Modal -->
    <div id="viewModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="viewModalTitle">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="viewModalTitle">View Details</h2>
                <button type="button" class="modal-close" id="viewModalClose" aria-label="Close details">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="viewContent">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button class="btn-outline" onclick="management.closeModal('viewModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Add/Edit Form Modal -->
    <div id="formModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Item</h2>
                <button type="button" class="modal-close" id="formModalClose" aria-label="Close form">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="dataForm">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-heading"></i>
                            Title
                        </label>
                        <input type="text" class="form-input" placeholder="Enter title">
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                                                  <i class="fa-solid fa-align-left"></i>
                            Description
                        </label>
                        <textarea class="form-textarea" placeholder="Enter description" rows="4"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fa-solid fa-tag"></i>
                                Status
                            </label>
                            <select class="form-select">
                                <option>Active</option>
                                <option>Pending</option>
                                <option>Completed</option>
                                <option>Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fa-solid fa-calendar"></i>
                                Date
                            </label>
                            <input type="date" class="form-input">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-outline" id="cancelFormBtn">Cancel</button>
                <button class="btn-primary" id="saveFormBtn">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal" role="alertdialog" aria-modal="true" aria-labelledby="deleteModalTitle" aria-describedby="deleteModalDescription">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="deleteModalTitle">Delete item?</h2>
                <button type="button" class="modal-close" id="deleteModalClose" aria-label="Close confirmation">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="management-delete-prompt" id="deleteModalDescription">
                    <i class="fa-solid fa-triangle-exclamation management-delete-prompt__icon" aria-hidden="true"></i>
                    <span>This item will be permanently removed. This action cannot be undone.</span>
                </p>
            </div>
            <div class="modal-footer">
                <button class="btn-outline" id="cancelDeleteBtn">Cancel</button>
                <button class="btn-primary" style="background: #dc2626;" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
    @if($currentRoleName === 'chief' && $currentStation)
    <div id="editStationModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="editStationModalTitle">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="editStationModalTitle"><i class="fa-solid fa-building"></i> Edit Station Information</h2>
                <button type="button" class="modal-close" id="editStationModalClose" aria-label="Close station form">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <form id="editStationForm">
                <div class="modal-body">
                    <input type="hidden" id="editStationId" value="{{ $currentStation->station_id }}">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-building"></i> Station Name</label>
                            <input type="text" class="form-input" id="editStationName" value="{{ $currentStation->station_name }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-barcode"></i> Station Code</label>
                            <input type="text" class="form-input" id="editStationCode" value="{{ $currentStation->station_code }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-phone"></i> Contact Number</label>
                            <input type="text" class="form-input" id="editStationContactNumber" value="{{ $currentStation->contact_number }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-envelope"></i> Email Address</label>
                            <input type="email" class="form-input" id="editStationEmail" value="{{ $currentStation->email }}">
                        </div>
                    </div>
                    <div class="management-form-section">
                        <div class="management-form-section__heading">
                            <i class="fa-solid fa-location-dot"></i>
                            <div><strong>Station address</strong><small>Controls the municipality and barangays used by station-scoped pages.</small></div>
                        </div>
                        @if($suggestedStationCity)
                            <div class="station-address-suggestion" role="note" data-station-address-suggestion>
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                                <div><strong>Suggested from active coverage</strong><span>{{ $suggestedStationCity->city_name }} was selected because all {{ $managedBarangays->count() }} covered barangays belong to this municipality. Choose the station barangay and verify the street before saving.</span></div>
                            </div>
                        @endif
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="editStationProvince">Province</label>
                                <select class="form-select" id="editStationProvince" required>
                                    <option value="">Select province</option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province->province_id }}" @selected((int) $stationAddressProvince?->province_id === (int) $province->province_id)>{{ $province->province_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="editStationCity">City / Municipality</label>
                                <select class="form-select" id="editStationCity" required @disabled(!$stationAddressCity?->city_id) data-address-suggested="{{ $suggestedStationCity ? 'true' : 'false' }}">
                                    <option value="">Select city</option>
                                    @if($stationAddressCity)
                                        <option value="{{ $stationAddressCity->city_id }}" selected>{{ $stationAddressCity->city_name }}</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="editStationBarangay">Barangay</label>
                                <select class="form-select" id="editStationBarangay" required @disabled(!$currentStation?->address?->barangay_id)>
                                    <option value="">Select barangay</option>
                                    @if($currentStation?->address?->barangay)
                                        <option value="{{ $currentStation->address->barangay->barangay_id }}" selected>{{ $currentStation->address->barangay->barangay_name }}</option>
                                    @endif
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="editStationStreet">Street / Building</label>
                                <input type="text" class="form-input" id="editStationStreet" maxlength="255" value="{{ $currentStation?->address?->street }}" placeholder="Station building, road, or sitio" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" id="cancelEditStationBtn">Cancel</button>
                    <button type="submit" class="btn-primary" id="saveStationBtn">Save Station</button>
                </div>
            </form>
        </div>
    </div>
    @endif
    {{-- equipment modal --}}
    <div id="ecatModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Equipment Category</h2>
                <button class="modal-close" id="ecatModalClose">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="ecatDataForm">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fa-solid fa-heading"></i>
                            Title
                        </label>
                        <input type="text" name="category_name" class="form-input" placeholder="Enter title">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-outline" id="ecatCancelBtn">Cancel</button>
                <button class="btn-primary" id="ecatSaveFormBtn">Save Changes</button>
            </div>
        </div>
    </div>
    <div id="galleryPreviewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Gallery Image</h2>
                <button class="modal-close" type="button" data-gallery-preview-close>
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="station-gallery-preview">
                    <div class="station-gallery-preview-frame">
                        <img src="" alt="Gallery preview" id="galleryPreviewImage">
                    </div>
                    <div class="station-gallery-preview-info">
                        <div class="station-gallery-preview-row">
                            <span class="station-gallery-preview-label">File name</span>
                            <span class="station-gallery-preview-value" id="galleryPreviewName"></span>
                        </div>
                        <div class="station-gallery-preview-row">
                            <span class="station-gallery-preview-label">Caption</span>
                            <span class="station-gallery-preview-value" id="galleryPreviewCaption"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" data-gallery-preview-close>Close</button>
            </div>
        </div>
    </div>
    <div id="galleryEditModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Gallery Details</h2>
                <button class="modal-close" type="button" data-gallery-edit-close>
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <form method="POST" id="galleryEditForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="galleryEditCaption">
                            <i class="fa-solid fa-align-left"></i>
                            Caption
                        </label>
                        <textarea class="form-textarea" id="galleryEditCaption" name="caption" rows="4" placeholder="Add a caption for this image"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" data-gallery-edit-close>Cancel</button>
                    <button type="submit" class="btn-primary">Save Caption</button>
                </div>
            </form>
        </div>
    </div>
    <div id="galleryDeleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Delete Gallery Image</h2>
                <button class="modal-close" type="button" data-gallery-delete-close>
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <form method="POST" id="galleryDeleteForm">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p style="text-align: center; margin: 8px 0 0;">
                        <i class="fa-solid fa-triangle-exclamation" style="font-size: 42px; color: #dc2626; margin-bottom: 12px;"></i>
                        <br>
                        Do you want to remove this image?
                        <br>
                        <strong id="galleryDeleteName"></strong>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline" data-gallery-delete-close>Cancel</button>
                    <button type="submit" class="btn-primary" style="background:#dc2626;">Delete</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Toast Container -->

    <script>
        // Mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const openBtn = document.getElementById('openSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            const userDropdownBtn = document.getElementById('userDropdownBtn');
            const userDropdownMenu = document.getElementById('userDropdownMenu');

            if (openBtn) {
                openBtn.addEventListener('click', function() {
                    sidebar.classList.add('open');
                    backdrop.classList.remove('hidden');
                    document.body.classList.add('menu-open');
                });
            }

            if (backdrop) {
                backdrop.addEventListener('click', function() {
                    sidebar.classList.remove('open');
                    backdrop.classList.add('hidden');
                    document.body.classList.remove('menu-open');
                });
            }

            if (userDropdownBtn && userDropdownMenu) {
                userDropdownBtn.addEventListener('click', function(event) {
                    event.stopPropagation();
                    userDropdownMenu.classList.toggle('show');
                });

                document.addEventListener('click', function(event) {
                    if (!userDropdownBtn.contains(event.target) && !userDropdownMenu.contains(event.target)) {
                        userDropdownMenu.classList.remove('show');
                    }
                });
            }

            document.querySelectorAll('.station-upload-form').forEach((form) => {
                form.addEventListener('submit', function(event) {
                    event.preventDefault();

                    const fileInput = form.querySelector('input[type="file"]');
                    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                        return;
                    }

                    const submitBtn = form.querySelector('[data-upload-submit]');
                    const submitLabel = form.querySelector('[data-upload-submit-label]');
                    const progressCard = form.querySelector('[data-upload-progress]');
                    const progressFill = form.querySelector('[data-upload-progress-fill]');
                    const percentText = form.querySelector('[data-upload-percent]');
                    const progressCopy = form.querySelector('[data-upload-progress-copy]');
                    const uploadLabel = form.dataset.uploadLabel || 'File';
                    const originalLabel = submitLabel ? submitLabel.textContent : '';

                    if (submitBtn) {
                        submitBtn.classList.add('is-loading');
                        submitBtn.disabled = true;
                    }

                    const icon = submitBtn?.querySelector('i');
                    if (icon) {
                        icon.className = 'fa-solid fa-spinner';
                    }

                    if (submitLabel) {
                        submitLabel.textContent = 'Uploading...';
                    }

                    if (progressCard) {
                        progressCard.classList.add('is-visible');
                    }

                    if (progressFill) {
                        progressFill.style.width = '0%';
                    }

                    if (percentText) {
                        percentText.textContent = '0%';
                    }

                    if (progressCopy) {
                        progressCopy.textContent = `Uploading ${uploadLabel.toLowerCase()} to the station page...`;
                    }

                    const request = new XMLHttpRequest();
                    request.open(form.method || 'POST', form.action, true);
                    request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    request.setRequestHeader('Accept', 'application/json, text/html;q=0.9,*/*;q=0.8');

                    request.upload.addEventListener('progress', function(progressEvent) {
                        if (!progressEvent.lengthComputable) {
                            return;
                        }

                        const percent = Math.max(1, Math.min(100, Math.round((progressEvent.loaded / progressEvent.total) * 100)));
                        if (progressFill) {
                            progressFill.style.width = `${percent}%`;
                        }
                        if (percentText) {
                            percentText.textContent = `${percent}%`;
                        }
                        if (progressCopy) {
                            progressCopy.textContent = percent >= 100
                                ? 'Finishing upload and saving on the server...'
                                : `Uploading ${uploadLabel.toLowerCase()}... ${percent}% complete`;
                        }
                    });

                    request.addEventListener('load', function() {
                        const succeeded = request.status >= 200 && request.status < 400;

                        if (succeeded) {
                            const payload = (() => {
                                try { return JSON.parse(request.responseText || '{}'); }
                                catch (error) { return {}; }
                            })();
                            if (progressFill) {
                                progressFill.style.width = '100%';
                            }
                            if (percentText) {
                                percentText.textContent = '100%';
                            }
                            if (progressCopy) {
                                progressCopy.textContent = `${uploadLabel} uploaded successfully.`;
                            }

                            if (payload.data?.url) {
                                const profile = document.querySelector('.station-media-profile');
                                if (profile) profile.innerHTML = `<img src="${payload.data.url}" alt="Station profile">`;
                            }
                            if (Array.isArray(payload.data) && payload.data.length) {
                                const gallery = document.querySelector('.station-gallery-grid');
                                const empty = document.querySelector('.station-photo-empty');
                                empty?.remove();
                                payload.data.forEach((item) => {
                                    const image = document.createElement('button');
                                    image.type = 'button';
                                    image.className = 'station-gallery-trigger';
                                    image.dataset.galleryOpen = '';
                                    image.dataset.galleryImage = item.url || '';
                                    image.dataset.galleryCaption = item.caption || 'No caption added.';
                                    image.dataset.galleryName = item.file_name || 'Station photo';
                                    image.innerHTML = `<img src="${item.url || ''}" alt="${item.caption || 'Station photo'}">`;
                                    image.addEventListener('click', function () {
                                        if (galleryPreviewImage) galleryPreviewImage.src = this.dataset.galleryImage || '';
                                        if (galleryPreviewName) galleryPreviewName.textContent = this.dataset.galleryName || 'Station photo';
                                        if (galleryPreviewCaption) galleryPreviewCaption.textContent = this.dataset.galleryCaption || 'No caption added.';
                                        openModalElement(galleryPreviewModal);
                                    });
                                    (gallery || document.querySelector('.station-gallery-library'))?.appendChild(image);
                                });
                                const count = document.querySelector('.station-gallery-count');
                                if (count) count.textContent = String(Number(count.textContent || 0) + payload.data.length);
                            }
                            form.reset();
                            if (submitBtn) {
                                submitBtn.classList.remove('is-loading');
                                submitBtn.disabled = false;
                            }
                            if (submitLabel) submitLabel.textContent = originalLabel;
                            renderInlineToast(payload.message || `${uploadLabel} uploaded successfully.`, 'success');
                            return;
                        }

                        if (submitBtn) {
                            submitBtn.classList.remove('is-loading');
                            submitBtn.disabled = false;
                        }
                        if (submitLabel) {
                            submitLabel.textContent = originalLabel;
                        }
                        if (icon) {
                            icon.className = submitBtn.classList.contains('btn-outline')
                                ? 'fa-solid fa-images'
                                : 'fa-solid fa-upload';
                        }
                        if (progressCopy) {
                            progressCopy.textContent = `Upload failed. Please try again.`;
                        }
                    });

                    request.addEventListener('error', function() {
                        if (submitBtn) {
                            submitBtn.classList.remove('is-loading');
                            submitBtn.disabled = false;
                        }
                        if (submitLabel) {
                            submitLabel.textContent = originalLabel;
                        }
                        if (icon) {
                            icon.className = submitBtn.classList.contains('btn-outline')
                                ? 'fa-solid fa-images'
                                : 'fa-solid fa-upload';
                        }
                        if (progressCopy) {
                            progressCopy.textContent = 'Network error while uploading. Please try again.';
                        }
                    });

                    request.send(new FormData(form));
                });
            });

            const galleryPreviewModal = document.getElementById('galleryPreviewModal');
            const galleryPreviewImage = document.getElementById('galleryPreviewImage');
            const galleryPreviewName = document.getElementById('galleryPreviewName');
            const galleryPreviewCaption = document.getElementById('galleryPreviewCaption');
            const galleryEditModal = document.getElementById('galleryEditModal');
            const galleryEditForm = document.getElementById('galleryEditForm');
            const galleryEditCaption = document.getElementById('galleryEditCaption');
            const galleryDeleteModal = document.getElementById('galleryDeleteModal');
            const galleryDeleteForm = document.getElementById('galleryDeleteForm');
            const galleryDeleteName = document.getElementById('galleryDeleteName');
            const closeModalElement = (modal) => {
                if (!modal) return;
                modal.classList.remove('active');
                document.body.style.overflow = 'auto';
            };

            const openModalElement = (modal) => {
                if (!modal) return;
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            };

            const renderInlineToast = (message, type = 'info') => {
                window.PyroNotify?.notify(message, type, 5000);
            };

            const flashSuccess = @json(session('success'));
            const flashError = @json($errors->any() ? $errors->first() : null);
            if (flashSuccess) {
                renderInlineToast(flashSuccess, 'success');
            }
            if (flashError) {
                renderInlineToast(flashError, 'error');
            }

            document.querySelectorAll('[data-gallery-open]').forEach((trigger) => {
                trigger.addEventListener('click', function() {
                    if (galleryPreviewImage) {
                        galleryPreviewImage.src = this.dataset.galleryImage || '';
                    }
                    if (galleryPreviewName) {
                        galleryPreviewName.textContent = this.dataset.galleryName || 'Untitled image';
                    }
                    if (galleryPreviewCaption) {
                        galleryPreviewCaption.textContent = this.dataset.galleryCaption || 'No caption added.';
                    }
                    openModalElement(galleryPreviewModal);
                });
            });

            document.querySelectorAll('[data-gallery-preview-close]').forEach((button) => {
                button.addEventListener('click', () => closeModalElement(galleryPreviewModal));
            });

            document.querySelectorAll('[data-gallery-menu-toggle]').forEach((button) => {
                button.addEventListener('click', function(event) {
                    event.stopPropagation();
                    const menu = this.closest('[data-gallery-menu]');
                    document.querySelectorAll('[data-gallery-menu]').forEach((item) => {
                        if (item !== menu) {
                            item.classList.remove('is-open');
                        }
                    });
                    menu?.classList.toggle('is-open');
                });
            });

            document.querySelectorAll('[data-gallery-edit]').forEach((button) => {
                button.addEventListener('click', function() {
                    const galleryId = this.dataset.galleryId;
                    const caption = this.dataset.galleryCaption || '';
                    if (galleryEditForm) {
                        galleryEditForm.action = `{{ url('/station-admin/station-media') }}/${galleryId}`;
                    }
                    if (galleryEditCaption) {
                        galleryEditCaption.value = caption;
                    }
                    document.querySelectorAll('[data-gallery-menu]').forEach((item) => item.classList.remove('is-open'));
                    openModalElement(galleryEditModal);
                });
            });

            document.querySelectorAll('[data-gallery-edit-close]').forEach((button) => {
                button.addEventListener('click', () => closeModalElement(galleryEditModal));
            });

            document.querySelectorAll('[data-gallery-delete]').forEach((button) => {
                button.addEventListener('click', function() {
                    const galleryId = this.dataset.galleryId;
                    const fileName = this.dataset.galleryName || 'this image';
                    if (galleryDeleteForm) {
                        galleryDeleteForm.action = `{{ url('/station-admin/station-media') }}/${galleryId}`;
                    }
                    if (galleryDeleteName) {
                        galleryDeleteName.textContent = fileName;
                    }
                    document.querySelectorAll('[data-gallery-menu]').forEach((item) => item.classList.remove('is-open'));
                    openModalElement(galleryDeleteModal);
                });
            });

            document.querySelectorAll('[data-gallery-delete-close]').forEach((button) => {
                button.addEventListener('click', () => closeModalElement(galleryDeleteModal));
            });

            [galleryEditForm, galleryDeleteForm].forEach((form) => {
                form?.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    const submit = event.submitter || form.querySelector('button[type="submit"]');
                    if (submit) submit.disabled = true;

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            body: new FormData(form),
                        });
                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok || payload.success === false) {
                            throw new Error(Object.values(payload.errors || {}).flat()[0] || payload.message || 'Unable to update the station gallery.');
                        }

                        const mediaId = form.action.split('/').filter(Boolean).pop();
                        if (form === galleryDeleteForm) {
                            document.querySelector(`[data-gallery-delete][data-gallery-id="${CSS.escape(mediaId)}"]`)?.closest('.station-gallery-item')?.remove();
                            const count = document.querySelector('.station-gallery-count');
                            if (count) count.textContent = String(Math.max(0, Number(count.textContent || 0) - 1));
                            closeModalElement(galleryDeleteModal);
                        } else {
                            const caption = payload.data?.caption || 'No caption added.';
                            const editButton = document.querySelector(`[data-gallery-edit][data-gallery-id="${CSS.escape(mediaId)}"]`);
                            const item = editButton?.closest('.station-gallery-item');
                            if (editButton) editButton.dataset.galleryCaption = payload.data?.caption || '';
                            const trigger = item?.querySelector('[data-gallery-open]');
                            if (trigger) trigger.dataset.galleryCaption = caption;
                            const title = item?.querySelector('.station-gallery-meta-title');
                            if (title) title.textContent = payload.data?.caption || title.textContent;
                            closeModalElement(galleryEditModal);
                        }
                        renderInlineToast(payload.message || 'Station gallery updated.', 'success');
                    } catch (error) {
                        renderInlineToast(error.message || 'Unable to update the station gallery.', 'error');
                    } finally {
                        if (submit) submit.disabled = false;
                    }
                });
            });

            [galleryPreviewModal, galleryEditModal, galleryDeleteModal].forEach((modal) => {
                modal?.addEventListener('click', function(event) {
                    if (event.target === modal) {
                        closeModalElement(modal);
                    }
                });
            });

            document.addEventListener('click', function(event) {
                document.querySelectorAll('[data-gallery-menu]').forEach((menu) => {
                    if (!menu.contains(event.target)) {
                        menu.classList.remove('is-open');
                    }
                });
            });
        });
    </script>

    @include('chief.partials.logs-modal')
    @include('chief.partials.logs-script')
    @include('chief.partials.logout-confirmation')
    @vite(['resources/js/chief/management.js'])
</body>
</html>
