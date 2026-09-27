<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.pyrosense-favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PyroSense - Barangays</title>

    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, #fffdf8 0%, #f8fafc 100%);
            color: #0f172a;
        }

        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            padding: 20px 24px 36px;
        }

        .barangay-shell {
            max-width: 1220px;
            margin: 0 auto;
        }

        .lector-top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
            padding: 14px 18px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(226, 232, 240, 0.95);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
        }

        .user-dropdown {
            position: relative;
        }

        .user-dropdown-toggle {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            border: 1px solid #fcd9bd;
            border-radius: 999px;
            background: #fff7ed;
            color: #334155;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .user-dropdown-toggle:hover {
            background: #ffedd5;
        }

        .user-dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 260px;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 18px;
            box-shadow: 0 24px 56px rgba(15, 23, 42, 0.14);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.2s ease;
            overflow: hidden;
            z-index: 30;
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
            width: 100%;
            padding: 12px 18px;
            background: transparent;
            border: 0;
            color: #1e293b;
            text-decoration: none;
            text-align: left;
            font-size: 14px;
            cursor: pointer;
        }

        .user-dropdown-item:hover {
            background: #fff7ed;
            color: #c2410c;
        }

        .barangay-topbar,
        .barangay-card {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(226, 232, 240, 0.95);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
        }

        .barangay-topbar {
            border-radius: 24px;
            padding: 22px 26px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 22px;
        }

        .barangay-eyebrow {
            color: #c2410c;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin-bottom: 4px;
        }

        .barangay-title {
            font-size: clamp(28px, 4vw, 42px);
            font-weight: 900;
            color: #0f1d44;
            line-height: 1.05;
        }

        .barangay-subtitle {
            margin-top: 8px;
            color: #64748b;
            font-size: 15px;
            max-width: 680px;
        }

        .barangay-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 48px;
            padding: 0 20px;
            border-radius: 16px;
            background: linear-gradient(135deg, #d44222 0%, #df8a0b 100%);
            color: #fff;
            font-weight: 800;
            text-decoration: none;
            box-shadow: 0 16px 30px rgba(216, 92, 20, 0.18);
            white-space: nowrap;
        }

        .barangay-card {
            border-radius: 24px;
            overflow: hidden;
        }

        .barangay-list-header,
        .barangay-row {
            display: grid;
            grid-template-columns: minmax(180px, 1.3fr) minmax(180px, 1fr) 140px;
            gap: 14px;
            align-items: center;
            padding: 16px 20px;
        }

        .barangay-list-header {
            background: #fff7ed;
            color: #9a3412;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .barangay-row {
            border-top: 1px solid #edf2f7;
        }

        .barangay-row__identity,
        .barangay-row__place {
            min-width: 0;
        }

        .barangay-mobile-label {
            display: none;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .barangay-name {
            font-weight: 900;
            color: #0f1d44;
        }

        .barangay-muted {
            color: #64748b;
            font-size: 13px;
        }

        .barangay-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            width: fit-content;
            border-radius: 999px;
            border: 1px solid #fed7aa;
            background: #fff7ed;
            color: #9a3412;
            padding: 7px 10px;
            font-size: 12px;
            font-weight: 800;
        }

        .barangay-link {
            color: #c2410c;
            font-size: 13px;
            font-weight: 900;
            text-decoration: none;
        }

        .barangay-icon-button {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #fed7aa;
            border-radius: 999px;
            background: #fff7ed;
            color: #c2410c;
            cursor: pointer;
            transition: transform 0.18s ease, background 0.18s ease, border-color 0.18s ease;
        }

        .barangay-icon-button:hover {
            background: #ffedd5;
            transform: translateY(-1px);
        }

        .barangay-icon-button--danger {
            border-color: #fecaca;
            background: #fff1f2;
            color: #dc2626;
        }

        .barangay-icon-button--danger:hover {
            background: #ffe4e6;
        }

        .barangay-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }

        .barangay-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 24px;
            padding: 18px;
            overflow: visible;
        }

        .barangay-tile {
            position: relative;
            min-width: 0;
            min-height: 390px;
            border: 0;
            border-radius: 22px;
            background: #0f172a;
            box-shadow: 0 24px 42px rgba(15, 23, 42, 0.18);
            overflow: hidden;
            isolation: isolate;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .barangay-tile:hover {
            transform: translateY(-4px);
            box-shadow: 0 32px 54px rgba(15, 23, 42, 0.24);
        }

        .barangay-tile__open {
            position: absolute;
            inset: 0;
            z-index: 2;
            padding: 0;
            border: 0;
            background: transparent;
            cursor: pointer;
        }

        .barangay-tile__media {
            position: absolute;
            inset: 0;
            z-index: 0;
            background: linear-gradient(135deg, #dbeafe 0%, #fef3c7 55%, #fee2e2 100%);
        }

        .barangay-tile__media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .barangay-tile__media.is-empty::after,
        .barangay-tile__fallback {
            position: absolute;
            inset: 0;
            display: grid;
            place-items: center;
            color: rgba(255, 255, 255, 0.86);
            font-size: 42px;
            background:
                linear-gradient(145deg, rgba(22, 101, 52, 0.75), rgba(15, 23, 42, 0.46)),
                repeating-linear-gradient(45deg, rgba(255, 255, 255, 0.09) 0 1px, transparent 1px 18px);
        }

        .barangay-tile__media.is-empty::after {
            content: "\f5a0";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
        }

        .barangay-tile::after {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 1;
            background:
                linear-gradient(180deg, rgba(15, 23, 42, 0.04) 0%, rgba(15, 23, 42, 0.1) 36%, rgba(15, 23, 42, 0.72) 100%),
                linear-gradient(90deg, rgba(15, 23, 42, 0.22), rgba(15, 23, 42, 0));
            pointer-events: none;
        }

        .barangay-visually-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .barangay-tile__badge {
            position: absolute;
            left: 18px;
            top: 18px;
            z-index: 3;
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 0 10px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(10px);
            color: #fff;
            font-size: 11px;
            font-weight: 900;
        }

        .barangay-tile__body {
            position: absolute;
            left: 18px;
            right: 18px;
            bottom: 18px;
            z-index: 3;
            display: grid;
            gap: 9px;
            color: #fff;
            pointer-events: none;
        }

        .barangay-tile__heading {
            min-width: 0;
        }

        .barangay-tile__kicker {
            color: rgba(255, 255, 255, 0.78);
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .barangay-tile__heading h2 {
            margin: 3px 0 0;
            color: #fff;
            font-size: 25px;
            font-weight: 900;
            line-height: 1.12;
            overflow-wrap: anywhere;
            text-shadow: 0 2px 14px rgba(15, 23, 42, 0.35);
        }

        .barangay-tile__location,
        .barangay-tile__coordinates {
            display: grid;
            grid-template-columns: 18px minmax(0, 1fr);
            gap: 8px;
            align-items: start;
            color: rgba(255, 255, 255, 0.86);
            font-size: 13px;
            line-height: 1.35;
        }

        .barangay-tile__location i,
        .barangay-tile__coordinates i {
            color: rgba(255, 255, 255, 0.92);
            margin-top: 2px;
        }

        .barangay-tile__meta {
            color: rgba(255, 255, 255, 0.82);
            font-size: 13px;
            font-weight: 700;
            line-height: 1.35;
        }

        .barangay-tile__records {
            display: none;
        }

        .barangay-tile__edit {
            position: relative;
            z-index: 4;
            pointer-events: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            width: 100%;
            min-height: 52px;
            margin-top: 8px;
            padding: 0 17px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 15px;
            background: rgba(15, 23, 42, 0.36);
            color: #fff;
            font-size: 15px;
            font-weight: 850;
            backdrop-filter: blur(16px);
            cursor: pointer;
            transition: background 0.18s ease, transform 0.18s ease;
        }

        .barangay-tile__edit:hover {
            background: rgba(15, 23, 42, 0.5);
            transform: translateY(-1px);
        }

        .barangay-tile__delete {
            position: absolute;
            top: 14px;
            right: 14px;
            z-index: 4;
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.42);
            color: #fff;
            backdrop-filter: blur(12px);
            cursor: pointer;
            transition: background 0.18s ease, transform 0.18s ease;
        }

        .barangay-tile__delete:hover {
            background: rgba(220, 38, 38, 0.82);
            transform: scale(1.04);
        }

        .barangay-notice-layer {
            position: fixed;
            top: 22px;
            right: 24px;
            z-index: 1700;
            pointer-events: none;
        }

        .barangay-notice {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: min(360px, calc(100vw - 32px));
            max-width: min(420px, calc(100vw - 32px));
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid #bbf7d0;
            background: rgba(240, 253, 244, 0.98);
            color: #166534;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
            opacity: 0;
            transform: translateY(-14px);
            transition: opacity 0.22s ease, transform 0.22s ease;
            pointer-events: auto;
        }

        .barangay-notice.show {
            opacity: 1;
            transform: translateY(0);
        }

        .barangay-notice--error {
            border-color: #fecaca;
            background: rgba(254, 242, 242, 0.98);
            color: #b91c1c;
        }

        .barangay-notice__icon {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #dcfce7;
            color: #15803d;
            flex-shrink: 0;
        }

        .barangay-notice--error .barangay-notice__icon {
            background: #fee2e2;
            color: #dc2626;
        }

        .barangay-notice__content {
            min-width: 0;
        }

        .barangay-notice__title {
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .barangay-notice__message {
            margin-top: 2px;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.35;
        }

        .barangay-notice__close {
            width: 32px;
            height: 32px;
            margin-left: auto;
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: #166534;
            cursor: pointer;
        }

        .barangay-notice--error .barangay-notice__close {
            color: #b91c1c;
        }

        .barangay-empty {
            padding: 42px 20px;
            text-align: center;
            color: #64748b;
            grid-column: 1 / -1;
        }

        .barangay-modal-backdrop {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(6px);
            z-index: 1500;
        }

        .barangay-modal-backdrop.active {
            display: flex;
        }

        .barangay-modal {
            width: min(100%, 900px);
            max-height: min(90vh, 820px);
            overflow: auto;
            border-radius: 26px;
            background: linear-gradient(180deg, #fffdf8 0%, #ffffff 100%);
            border: 1px solid rgba(226, 232, 240, 0.95);
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
        }

        .barangay-modal--compact {
            width: min(100%, 560px);
        }

        .barangay-modal__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 22px 24px 16px;
            border-bottom: 1px solid #edf2f7;
            background: linear-gradient(135deg, rgba(255, 247, 237, 0.98), rgba(255, 255, 255, 0.98));
        }

        .barangay-modal__eyebrow {
            color: #c2410c;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .barangay-modal__title {
            color: #0f1d44;
            font-size: clamp(24px, 3vw, 34px);
            font-weight: 900;
            line-height: 1.08;
        }

        .barangay-modal__copy {
            margin-top: 8px;
            color: #64748b;
            font-size: 14px;
        }

        .barangay-modal__close {
            width: 44px;
            height: 44px;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            background: #fff;
            color: #64748b;
            cursor: pointer;
        }

        .barangay-modal__body {
            padding: 22px 24px 24px;
        }

        .barangay-field {
            margin-bottom: 18px;
        }

        .barangay-label {
            display: block;
            margin-bottom: 8px;
            color: #475569;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .barangay-input {
            width: 100%;
            min-height: 52px;
            border: 1px solid #dbe4ef;
            border-radius: 16px;
            padding: 0 16px;
            color: #0f1d44;
            font-size: 15px;
            font-weight: 700;
            background: #fff;
            outline: none;
        }

        .barangay-input--file {
            display: flex;
            align-items: center;
            padding: 12px 14px;
            height: auto;
            line-height: 1.3;
        }

        .barangay-field-note {
            margin-top: 7px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.4;
        }

        .barangay-image-preview {
            display: none;
            margin-top: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            background: #f8fafc;
        }

        .barangay-image-preview.is-visible {
            display: block;
        }

        .barangay-image-preview__frame {
            aspect-ratio: 16 / 9;
            background: linear-gradient(135deg, #f8fafc, #fff7ed);
        }

        .barangay-image-preview__frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .barangay-image-preview__caption {
            padding: 9px 12px;
            color: #475569;
            font-size: 12px;
            font-weight: 800;
        }

        .barangay-readonly {
            display: flex;
            align-items: center;
            min-height: 52px;
            padding: 0 16px;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #f8fafc;
            color: #334155;
            font-size: 14px;
            font-weight: 700;
        }

        .barangay-error {
            color: #dc2626;
            font-size: 12px;
            font-weight: 700;
            margin-top: 6px;
        }

        .barangay-modal__actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 22px;
        }

        .barangay-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 48px;
            padding: 0 20px;
            border-radius: 16px;
            border: 1px solid transparent;
            font-weight: 900;
            text-decoration: none;
            cursor: pointer;
        }

        .barangay-button[disabled] {
            cursor: wait;
        }

        .barangay-button--ghost {
            border-color: #e2e8f0;
            background: #fff;
            color: #475569;
        }

        .barangay-button--primary {
            background: linear-gradient(135deg, #d44222 0%, #df8a0b 100%);
            color: #fff;
            box-shadow: 0 16px 30px rgba(216, 92, 20, 0.18);
        }

        .barangay-button--danger {
            background: linear-gradient(135deg, #dc2626 0%, #f97316 100%);
            color: #fff;
            box-shadow: 0 16px 30px rgba(220, 38, 38, 0.18);
        }

        .barangay-button__spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.35);
            border-top-color: #fff;
            border-radius: 999px;
            animation: barangay-spin 0.7s linear infinite;
            display: none;
            flex-shrink: 0;
        }

        .barangay-button__label,
        .barangay-button__icon {
            display: inline-flex;
            align-items: center;
        }

        .barangay-button.is-loading {
            opacity: 1;
            filter: saturate(0.95);
        }

        .barangay-button.is-loading .barangay-button__spinner {
            display: inline-flex;
        }

        .barangay-button.is-loading .barangay-button__icon {
            display: none;
        }

        @keyframes barangay-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .barangay-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .barangay-details-stat {
            border: 1px solid #edf2f7;
            border-radius: 18px;
            background: #fff;
            padding: 16px 18px;
        }

        .barangay-details-stat__label {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .barangay-details-stat__value {
            color: #0f1d44;
            font-size: 26px;
            font-weight: 900;
        }

        .barangay-details-section {
            border: 1px solid #edf2f7;
            border-radius: 18px;
            background: #fff;
            padding: 18px;
        }

        .barangay-details-section + .barangay-details-section {
            margin-top: 16px;
        }

        .barangay-details-section h3 {
            margin: 0 0 12px;
            color: #0f1d44;
            font-size: 16px;
            font-weight: 900;
        }

        .barangay-details-list {
            display: grid;
            gap: 10px;
        }

        .barangay-details-item {
            border-radius: 14px;
            background: #f8fafc;
            padding: 12px 14px;
        }

        .barangay-details-item__title {
            color: #0f1d44;
            font-size: 14px;
            font-weight: 800;
        }

        .barangay-details-item__meta {
            margin-top: 4px;
            color: #64748b;
            font-size: 13px;
        }

        .barangay-details-empty,
        .barangay-details-loading {
            color: #64748b;
            font-size: 14px;
        }

        .barangay-delete-warning {
            border: 1px solid #fecaca;
            border-radius: 18px;
            background: #fff1f2;
            color: #991b1b;
            padding: 14px 16px;
        }

        .barangay-delete-warning__title {
            font-size: 13px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .barangay-delete-warning__copy {
            margin-top: 6px;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.45;
        }

        .barangay-delete-summary {
            margin-top: 16px;
            display: grid;
            gap: 8px;
        }

        .barangay-delete-summary__item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-radius: 14px;
            background: #f8fafc;
            padding: 10px 12px;
            color: #334155;
            font-size: 13px;
            font-weight: 800;
        }

        .barangay-delete-summary__count {
            color: #b45309;
            font-weight: 900;
        }

        .barangay-delete-note {
            margin-top: 14px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.4;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: calc(10px + env(safe-area-inset-top, 0px)) 10px calc(96px + env(safe-area-inset-bottom, 0px));
            }

            .barangay-shell {
                max-width: 100%;
            }

            .lector-top-bar {
                gap: 10px;
                margin-bottom: 12px;
                padding: 10px 12px;
                border-radius: 16px;
            }

            .user-dropdown-toggle {
                gap: 8px;
                padding: 6px 10px;
            }

            .user-dropdown-menu {
                width: min(240px, calc(100vw - 24px));
            }

            .barangay-topbar {
                align-items: stretch;
                flex-direction: column;
                gap: 12px;
                border-radius: 18px;
                padding: 14px;
                margin-bottom: 14px;
            }

            .barangay-title {
                font-size: 22px;
            }

            .barangay-subtitle {
                margin-top: 6px;
                font-size: 13px;
            }

            .barangay-action {
                width: 100%;
                min-height: 42px;
                padding: 0 14px;
                border-radius: 14px;
                font-size: 13px;
            }

            .barangay-card {
                background: transparent;
                border: 0;
                box-shadow: none;
                border-radius: 18px;
                overflow: visible;
            }

            .barangay-card-grid {
                grid-template-columns: 1fr;
                gap: 10px;
                padding: 0;
            }

            .barangay-tile {
                border-radius: 16px;
                min-height: 340px;
            }

            .barangay-tile__media {
                height: 132px;
            }

            .barangay-tile__body {
                padding: 12px;
                gap: 8px;
            }

            .barangay-tile__heading h2 {
                font-size: 17px;
            }

            .barangay-tile__location,
            .barangay-tile__coordinates {
                font-size: 12px;
            }

            .barangay-tile__records {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 6px;
            }

            .barangay-tile__records span {
                padding: 7px 6px;
                font-size: 9px;
                text-align: center;
            }

            .barangay-tile__records strong {
                font-size: 14px;
            }

            .barangay-tile__actions {
                padding: 0 12px 12px;
            }

            .barangay-list-header {
                display: none;
            }

            .barangay-row {
                grid-template-columns: minmax(0, 1fr) auto;
                grid-template-areas: "identity actions";
                gap: 10px;
                margin-bottom: 8px;
                padding: 10px 12px;
                border: 1px solid rgba(226, 232, 240, 0.96);
                border-radius: 14px;
                background: linear-gradient(180deg, rgba(255, 253, 248, 0.98) 0%, #ffffff 100%);
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
            }

            .barangay-name {
                font-size: 13px;
                line-height: 1.2;
            }

            .barangay-muted {
                font-size: 11px;
                line-height: 1.3;
            }

            .barangay-mobile-label {
                display: none;
            }

            .barangay-row__identity {
                grid-area: identity;
                min-width: 0;
            }

            .barangay-row__place {
                display: none;
            }

            .barangay-row__identity .barangay-muted {
                display: block;
                margin-top: 2px;
                font-size: 11px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .barangay-actions {
                grid-area: actions;
                flex-direction: row;
                align-items: center;
                justify-content: flex-end;
                gap: 6px;
                align-self: center;
            }

            .barangay-icon-button {
                width: 34px;
                height: 34px;
            }

            .barangay-notice-layer {
                top: auto;
                right: 10px;
                left: 10px;
                bottom: calc(84px + env(safe-area-inset-bottom, 0px));
            }

            .barangay-notice {
                min-width: 0;
                max-width: none;
                width: 100%;
                padding: 12px 14px;
                border-radius: 16px;
                transform: translateY(16px);
                box-shadow: 0 16px 32px rgba(15, 23, 42, 0.18);
            }

            .barangay-notice.show {
                transform: translateY(0);
            }

            .barangay-notice__icon {
                width: 34px;
                height: 34px;
            }

            .barangay-notice__title {
                font-size: 11px;
            }

            .barangay-notice__message {
                font-size: 12px;
            }

            .barangay-notice__close {
                width: 28px;
                height: 28px;
            }

            .barangay-empty {
                padding: 28px 14px;
                font-size: 12px;
            }

            .barangay-modal-backdrop {
                align-items: flex-end;
                padding: 8px 8px calc(86px + env(safe-area-inset-bottom, 0px));
            }

            .barangay-modal,
            .barangay-modal--compact {
                width: 100%;
                max-height: calc(100vh - 100px - env(safe-area-inset-top, 0px));
                border-radius: 18px;
            }

            .barangay-modal__header,
            .barangay-modal__body {
                padding: 14px;
            }

            .barangay-modal__header {
                gap: 10px;
            }

            .barangay-modal__title {
                font-size: 20px;
            }

            .barangay-modal__copy {
                margin-top: 4px;
                font-size: 12px;
            }

            .barangay-modal__close {
                width: 38px;
                height: 38px;
            }

            .barangay-modal__actions {
                flex-direction: column-reverse;
                gap: 8px;
                margin-top: 16px;
                position: sticky;
                bottom: 0;
                padding-top: 10px;
                background: linear-gradient(180deg, rgba(255, 253, 248, 0) 0%, rgba(255, 255, 255, 0.98) 28%);
            }

            .barangay-button {
                width: 100%;
                min-height: 42px;
                padding: 0 14px;
                border-radius: 14px;
                font-size: 13px;
            }

            .barangay-field {
                margin-bottom: 14px;
            }

            .barangay-label {
                margin-bottom: 6px;
                font-size: 11px;
            }

            .barangay-input,
            .barangay-readonly {
                min-height: 44px;
                padding: 0 12px;
                border-radius: 13px;
                font-size: 13px;
            }

            .barangay-details-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
                margin-bottom: 10px;
            }

            .barangay-details-stat {
                border-radius: 14px;
                padding: 9px 10px;
            }

            .barangay-details-stat__label {
                font-size: 10px;
                margin-bottom: 4px;
                line-height: 1.25;
            }

            .barangay-details-stat__value {
                font-size: 18px;
                line-height: 1.1;
            }

            .barangay-details-section {
                border-radius: 14px;
                padding: 10px;
            }

            .barangay-details-section + .barangay-details-section {
                margin-top: 8px;
            }

            .barangay-details-section h3 {
                margin-bottom: 7px;
                font-size: 13px;
            }

            .barangay-details-list {
                gap: 6px;
            }

            .barangay-details-item {
                border-radius: 11px;
                padding: 8px 9px;
            }

            .barangay-details-item__title {
                font-size: 12px;
                line-height: 1.3;
            }

            .barangay-details-item__meta,
            .barangay-details-empty,
            .barangay-details-loading,
            .barangay-error {
                font-size: 11px;
                line-height: 1.35;
            }
        }

        @media (max-width: 420px) {
            .main-content {
                padding: calc(8px + env(safe-area-inset-top, 0px)) 8px calc(92px + env(safe-area-inset-bottom, 0px));
            }

            .barangay-notice-layer {
                right: 8px;
                left: 8px;
                bottom: calc(82px + env(safe-area-inset-bottom, 0px));
            }

            .barangay-notice {
                padding: 11px 12px;
                border-radius: 14px;
            }

            .lector-top-bar,
            .barangay-topbar {
                padding-left: 10px;
                padding-right: 10px;
            }

            .barangay-eyebrow,
            .barangay-modal__eyebrow {
                font-size: 10px;
            }

            .barangay-title {
                font-size: 20px;
            }

            .barangay-subtitle {
                font-size: 12px;
            }

            .barangay-row {
                padding: 11px;
                border-radius: 16px;
            }

            .barangay-name {
                font-size: 12px;
            }

            .barangay-muted {
                font-size: 10px;
            }

            .barangay-actions {
                gap: 5px;
            }

            .barangay-action {
                min-height: 40px;
                font-size: 12px;
            }

            .barangay-pill {
                font-size: 9px;
                padding: 5px 6px;
            }

            .barangay-modal-backdrop {
                padding: 6px 6px calc(88px + env(safe-area-inset-bottom, 0px));
            }

            .barangay-modal,
            .barangay-modal--compact {
                max-height: calc(100vh - 96px - env(safe-area-inset-top, 0px));
                border-radius: 16px;
            }

            .barangay-modal__title {
                font-size: 18px;
            }

            .barangay-modal__copy {
                font-size: 11px;
            }

            .barangay-details-grid {
                grid-template-columns: 1fr;
            }

            .barangay-modal__header,
            .barangay-modal__body {
                padding-left: 12px;
                padding-right: 12px;
            }

            .barangay-details-stat__value {
                font-size: 17px;
            }
        }
    </style>
</head>
<body class="chief-legacy-layout">
    @php
        $barangayNoticeMessage = session('success') ?? session('error');
        $barangayNoticeType = session('error') ? 'error' : 'success';
        $barangayNoticeTitle = session('error') ? 'Action Needed' : 'Saved';
        $barangayNoticeIcon = session('error') ? 'fa-triangle-exclamation' : 'fa-check';
    @endphp
    @if ($barangayNoticeMessage)
        <div class="barangay-notice-layer" aria-live="polite" aria-atomic="true">
            <div id="barangayPageNotice" class="barangay-notice {{ $barangayNoticeType === 'error' ? 'barangay-notice--error' : '' }}" role="status">
                <div class="barangay-notice__icon">
                    <i class="fa-solid {{ $barangayNoticeIcon }}"></i>
                </div>
                <div class="barangay-notice__content">
                    <div class="barangay-notice__title">{{ $barangayNoticeTitle }}</div>
                    <div class="barangay-notice__message">{{ $barangayNoticeMessage }}</div>
                </div>
                <button type="button" class="barangay-notice__close" id="barangayPageNoticeClose" aria-label="Close notification">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
    @endif

    @include('chief.partials.sidebar', ['active' => 'barangays'])

    <main class="main-content">
        <div class="barangay-shell">
            <nav class="lector-top-bar">
                <div class="flex items-center gap-4">
                    <button id="openSidebar" class="lg:hidden text-slate-600" type="button">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>
                    <div class="hidden lg:block">
                        <h1 class="text-xl font-bold text-slate-800">Barangays</h1>
                        <p class="text-xs text-slate-500 font-medium">{{ now()->format('l, F j') }}</p>
                    </div>
                </div>

                <div class="user-dropdown">
                    <button id="userDropdownBtn" class="user-dropdown-toggle" type="button">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white text-[10px] font-bold shadow-sm shadow-amber-200">
                            {{ substr(auth()->user()->first_name ?? 'C', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'A', 0, 1) }}
                        </div>
                        <span class="text-sm font-bold text-slate-700 hidden sm:block">{{ auth()->user()->first_name }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
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
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="user-dropdown-item text-red-600">
                                    <i class="fa-solid fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>

            <header class="barangay-topbar">
                <div>
                    <p class="barangay-eyebrow">Coverage</p>
                    <h1 class="barangay-title">Barangays</h1>
                    <p class="barangay-subtitle">{{ $chiefCity?->city_name ?? 'Assigned city' }} barangays.</p>
                </div>
                <button type="button" class="barangay-action" id="openBarangayCreateModal">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    Add Barangay
                </button>
            </header>

            <section class="barangay-card barangay-card-grid" aria-label="Barangay cards">
                @forelse ($barangays as $barangay)
                    @php
                        $markerImage = trim((string) ($barangay->hall?->marker?->image ?? ''));
                        $barangayImageUrl = null;

                        if ($markerImage !== '') {
                            if (preg_match('/^(https?:)?\/\//i', $markerImage) || str_starts_with($markerImage, '/storage/')) {
                                $barangayImageUrl = $markerImage;
                            } else {
                                $barangayImageUrl = asset('storage/' . ltrim($markerImage, '/'));
                            }
                        }

                        $cityProvince = collect([
                            $barangay->city?->city_name,
                            $barangay->city?->province?->province_name,
                        ])->filter()->join(', ') ?: 'No city assigned';
                        $coordinates = $barangay->hall?->marker?->lat !== null && $barangay->hall?->marker?->lng !== null
                            ? $barangay->hall->marker->lat . ', ' . $barangay->hall->marker->lng
                            : 'Coordinates not set';
                        $locationLine = $barangay->hall?->address?->street
                            ? $barangay->hall->address->street . ', ' . $cityProvince
                            : $cityProvince;
                        $recordSummary = collect([
                            ((int) ($barangay->boundary_locations_count ?? 0)) . ' Boundaries',
                            ((int) ($barangay->stations_count ?? 0)) . ' Stations',
                            ((int) ($barangay->risk_areas_count ?? 0)) . ' Risks',
                        ])->join(' / ');
                        $deleteSummary = json_encode([
                            ['label' => 'Addresses', 'count' => (int) ($barangay->full_addresses_count ?? 0)],
                            ['label' => 'Boundary Records', 'count' => (int) ($barangay->boundary_locations_count ?? 0)],
                            ['label' => 'Station Coverage Links', 'count' => (int) ($barangay->station_coverage_count ?? 0)],
                            ['label' => 'Historical Incidents', 'count' => (int) ($barangay->historical_fire_incidents_count ?? 0)],
                            ['label' => 'Prediction Checklists', 'count' => (int) ($barangay->prediction_checklists_count ?? 0)],
                            ['label' => 'Barangay Hall', 'count' => (int) ($barangay->hall_count ?? 0)],
                            ['label' => 'Risk Areas', 'count' => (int) ($barangay->risk_areas_count ?? 0)],
                            ['label' => 'Fire Records', 'count' => (int) ($barangay->fire_records_count ?? 0)],
                            ['label' => 'Barangay Officers', 'count' => (int) ($barangay->barangay_officers_count ?? 0)],
                            ['label' => 'Mitigation Actions', 'count' => (int) ($barangay->mitigation_actions_count ?? 0)],
                            ['label' => 'Action Plan Checklists', 'count' => (int) ($barangay->ai_action_plan_checklists_count ?? 0)],
                            ['label' => 'Recommendation Suggestions', 'count' => (int) ($barangay->ai_recommendation_suggestions_count ?? 0)],
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                    @endphp
                    <article class="barangay-tile">
                        <button
                            type="button"
                            class="barangay-tile__open"
                            data-barangay-details-trigger
                            data-details-url="{{ route('station-admin.barangays.show', $barangay) }}"
                            aria-label="Open records for {{ $barangay->barangay_name }}"
                        >
                            <span class="barangay-visually-hidden">Open records for {{ $barangay->barangay_name }}</span>
                        </button>
                        <div class="barangay-tile__media">
                            @if ($barangayImageUrl)
                                <img src="{{ $barangayImageUrl }}" alt="{{ $barangay->barangay_name }} barangay photo" loading="lazy" onerror="this.remove(); this.closest('.barangay-tile__media').classList.add('is-empty');">
                            @else
                                <div class="barangay-tile__fallback" aria-hidden="true">
                                    <i class="fa-solid fa-map-location-dot"></i>
                                </div>
                            @endif
                        </div>
                        <span class="barangay-tile__badge">{{ $barangay->hall?->hall_name ? 'Hall linked' : 'No hall yet' }}</span>
                        <button
                            type="button"
                            class="barangay-tile__delete"
                            data-barangay-delete-trigger
                            data-delete-url="{{ route('station-admin.barangays.destroy', $barangay) }}"
                            data-barangay-name="{{ $barangay->barangay_name }}"
                            data-delete-summary="{{ $deleteSummary }}"
                            aria-label="Delete {{ $barangay->barangay_name }}"
                            title="Delete"
                        >
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                        <div class="barangay-tile__body">
                            <div class="barangay-tile__heading">
                                <span class="barangay-tile__kicker">Barangay</span>
                                <h2>{{ $barangay->barangay_name }}</h2>
                            </div>
                            <p class="barangay-tile__meta">{{ $recordSummary }}</p>
                            <div class="barangay-tile__location">
                                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                                <span>{{ $locationLine }}</span>
                            </div>
                            <div class="barangay-tile__coordinates">
                                <i class="fa-solid fa-crosshairs" aria-hidden="true"></i>
                                <span>{{ $coordinates }}</span>
                            </div>
                            <a
                                href="{{ route('station-admin.barangays.show', $barangay) }}"
                                class="barangay-tile__edit"
                                aria-label="Edit {{ $barangay->barangay_name }}"
                            >
                                <span>Edit</span>
                                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="barangay-empty">
                        <p class="font-bold text-slate-800">No barangays yet.</p>
                        <p>Add the first barangay to start building coverage data.</p>
                    </div>
                @endforelse

                @if ($barangays->total() > 0)
                    @include('chief.partials.pagination', [
                        'paginator' => $barangays,
                        'ariaLabel' => 'Barangay pagination',
                        'summaryLabel' => 'barangays',
                    ])
                @endif
            </section>
        </div>
    </main>

    <div id="barangayCreateModal" class="barangay-modal-backdrop" aria-hidden="true">
        <div class="barangay-modal barangay-modal--compact" role="dialog" aria-modal="true" aria-labelledby="barangayCreateModalTitle">
            <div class="barangay-modal__header">
                <div>
                    <div class="barangay-modal__eyebrow">Add</div>
                    <h2 id="barangayCreateModalTitle" class="barangay-modal__title">Add Barangay</h2>
                    <p class="barangay-modal__copy">Add a barangay.</p>
                </div>
                <button type="button" class="barangay-modal__close" data-barangay-modal-close="barangayCreateModal" aria-label="Close add barangay modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="barangay-modal__body">
                <form method="POST" action="{{ route('station-admin.barangays.store') }}" data-loading-submit-form>
                    @csrf
                    <input type="hidden" name="_modal_mode" value="create">
                    <div class="barangay-field">
                        <label class="barangay-label">City / Municipality</label>
                        <div class="barangay-readonly">
                            {{ $chiefCity?->city_name ?? 'No city assigned' }}{{ $chiefCity?->province?->province_name ? ' - ' . $chiefCity->province->province_name : '' }}
                        </div>
                    </div>

                    <div class="barangay-field">
                        <label for="barangay_name" class="barangay-label">Barangay name</label>
                        <input
                            id="barangay_name"
                            name="barangay_name"
                            type="text"
                            class="barangay-input"
                            value="{{ old('barangay_name') }}"
                            placeholder="Example: Poblacion"
                            required
                        >
                        @error('barangay_name')
                            <p class="barangay-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="barangay-modal__actions">
                        <button type="button" class="barangay-button barangay-button--ghost" data-barangay-modal-close="barangayCreateModal">Cancel</button>
                        <button type="submit" class="barangay-button barangay-button--primary" data-loading-submit-button>
                            <span class="barangay-button__spinner" aria-hidden="true"></span>
                            <span class="barangay-button__icon"><i class="fa-solid fa-check" aria-hidden="true"></i></span>
                            <span class="barangay-button__label">Save Barangay</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="barangayDetailsModal" class="barangay-modal-backdrop" aria-hidden="true">
        <div class="barangay-modal" role="dialog" aria-modal="true" aria-labelledby="barangayDetailsModalTitle">
            <div class="barangay-modal__header">
                <div>
                    <div class="barangay-modal__eyebrow">Details</div>
                    <h2 id="barangayDetailsModalTitle" class="barangay-modal__title">Details</h2>
                    <p id="barangayDetailsModalCopy" class="barangay-modal__copy">Quick details.</p>
                </div>
                <button type="button" class="barangay-modal__close" data-barangay-modal-close="barangayDetailsModal" aria-label="Close barangay details modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div id="barangayDetailsModalBody" class="barangay-modal__body">
                <p class="barangay-details-loading">Loading...</p>
            </div>
        </div>
    </div>

    <div id="barangayEditModal" class="barangay-modal-backdrop" aria-hidden="true">
        <div class="barangay-modal barangay-modal--compact" role="dialog" aria-modal="true" aria-labelledby="barangayEditModalTitle">
            <div class="barangay-modal__header">
                <div>
                    <div class="barangay-modal__eyebrow">Edit</div>
                    <h2 id="barangayEditModalTitle" class="barangay-modal__title">Edit</h2>
                    <p class="barangay-modal__copy">Update the name and card image.</p>
                </div>
                <button type="button" class="barangay-modal__close" data-barangay-modal-close="barangayEditModal" aria-label="Close edit barangay modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="barangay-modal__body">
                <form
                    method="POST"
                    id="barangayEditForm"
                    action="{{ route('station-admin.barangays.update', ['barangay' => '__BARANGAY__']) }}"
                    data-action-template="{{ route('station-admin.barangays.update', ['barangay' => '__BARANGAY__']) }}"
                    data-loading-submit-form
                    enctype="multipart/form-data"
                >
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_modal_mode" value="edit">
                    <input type="hidden" name="editing_barangay_id" id="editing_barangay_id" value="{{ old('editing_barangay_id') }}">
                    <div class="barangay-field">
                        <label class="barangay-label">City / Municipality</label>
                        <div class="barangay-readonly">
                            {{ $chiefCity?->city_name ?? 'No city assigned' }}{{ $chiefCity?->province?->province_name ? ' - ' . $chiefCity->province->province_name : '' }}
                        </div>
                    </div>

                    <div class="barangay-field">
                        <label for="edit_barangay_name" class="barangay-label">Barangay name</label>
                        <input
                            id="edit_barangay_name"
                            name="barangay_name"
                            type="text"
                            class="barangay-input"
                            value="{{ old('_modal_mode') === 'edit' ? old('barangay_name') : '' }}"
                            placeholder="Example: Poblacion"
                            required
                        >
                        @if (old('_modal_mode') === 'edit')
                            @error('barangay_name')
                                <p class="barangay-error">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <div class="barangay-field">
                        <label for="edit_barangay_card_image" class="barangay-label">Card image</label>
                        <input
                            id="edit_barangay_card_image"
                            name="barangay_card_image"
                            type="file"
                            class="barangay-input barangay-input--file"
                            accept="image/jpeg,image/png,image/webp"
                        >
                        <p class="barangay-field-note">JPG, PNG, or WebP up to 5 MB. This updates the barangay card photo.</p>
                        <div id="barangayImagePreview" class="barangay-image-preview" aria-live="polite">
                            <div class="barangay-image-preview__frame">
                                <img id="barangayImagePreviewImg" src="" alt="Selected barangay card image preview">
                            </div>
                            <div id="barangayImagePreviewCaption" class="barangay-image-preview__caption">Preview before saving</div>
                        </div>
                        @if (old('_modal_mode') === 'edit')
                            @error('barangay_card_image')
                                <p class="barangay-error">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <div class="barangay-modal__actions">
                        <button type="button" class="barangay-button barangay-button--ghost" data-barangay-modal-close="barangayEditModal">Cancel</button>
                        <button type="submit" class="barangay-button barangay-button--primary" data-loading-submit-button>
                            <span class="barangay-button__spinner" aria-hidden="true"></span>
                            <span class="barangay-button__icon"><i class="fa-solid fa-pen" aria-hidden="true"></i></span>
                            <span class="barangay-button__label">Save Changes</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="barangayDeleteModal" class="barangay-modal-backdrop" aria-hidden="true">
        <div class="barangay-modal barangay-modal--compact" role="dialog" aria-modal="true" aria-labelledby="barangayDeleteModalTitle">
            <div class="barangay-modal__header">
                <div>
                    <div class="barangay-modal__eyebrow">Delete</div>
                    <h2 id="barangayDeleteModalTitle" class="barangay-modal__title">Delete</h2>
                    <p id="barangayDeleteModalCopy" class="barangay-modal__copy">Check before delete.</p>
                </div>
                <button type="button" class="barangay-modal__close" data-barangay-modal-close="barangayDeleteModal" aria-label="Close delete barangay modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="barangay-modal__body">
                    <div id="barangayDeleteWarning" class="barangay-delete-warning">
                    <div class="barangay-delete-warning__title">Delete</div>
                    <p class="barangay-delete-warning__copy">This removes the barangay.</p>
                </div>

                <div id="barangayDeleteSummary" class="barangay-delete-summary"></div>
                <p id="barangayDeleteNote" class="barangay-delete-note">This cannot be undone.</p>

                <form method="POST" id="barangayDeleteForm" action="" data-loading-submit-form>
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="force_delete_related" id="force_delete_related" value="0">

                    <div class="barangay-modal__actions">
                        <button type="button" class="barangay-button barangay-button--ghost" data-barangay-modal-close="barangayDeleteModal">Cancel</button>
                        <button type="submit" class="barangay-button barangay-button--danger" data-loading-submit-button>
                            <span class="barangay-button__spinner" aria-hidden="true"></span>
                            <span class="barangay-button__icon"><i class="fa-solid fa-trash" aria-hidden="true"></i></span>
                            <span class="barangay-button__label" id="barangayDeleteSubmitLabel">Delete</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const dropdownButton = document.getElementById('userDropdownBtn');
            const dropdownMenu = document.getElementById('userDropdownMenu');
            const sidebar = document.getElementById('sidebar');
            const openSidebar = document.getElementById('openSidebar');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');
            const createModal = document.getElementById('barangayCreateModal');
            const detailsModal = document.getElementById('barangayDetailsModal');
            const editModal = document.getElementById('barangayEditModal');
            const deleteModal = document.getElementById('barangayDeleteModal');
            const openCreateModalButton = document.getElementById('openBarangayCreateModal');
            const detailsModalTitle = document.getElementById('barangayDetailsModalTitle');
            const detailsModalCopy = document.getElementById('barangayDetailsModalCopy');
            const detailsModalBody = document.getElementById('barangayDetailsModalBody');
            const editForm = document.getElementById('barangayEditForm');
            const editNameInput = document.getElementById('edit_barangay_name');
            const editImageInput = document.getElementById('edit_barangay_card_image');
            const editImagePreview = document.getElementById('barangayImagePreview');
            const editImagePreviewImg = document.getElementById('barangayImagePreviewImg');
            const editImagePreviewCaption = document.getElementById('barangayImagePreviewCaption');
            const editBarangayIdInput = document.getElementById('editing_barangay_id');
            const editFormActionTemplate = editForm?.dataset.actionTemplate || '';
            const deleteForm = document.getElementById('barangayDeleteForm');
            const deleteWarning = document.getElementById('barangayDeleteWarning');
            const deleteSummary = document.getElementById('barangayDeleteSummary');
            const deleteNote = document.getElementById('barangayDeleteNote');
            const deleteModalTitle = document.getElementById('barangayDeleteModalTitle');
            const deleteModalCopy = document.getElementById('barangayDeleteModalCopy');
            const deleteSubmitLabel = document.getElementById('barangayDeleteSubmitLabel');
            const forceDeleteRelatedInput = document.getElementById('force_delete_related');
            const pageNotice = document.getElementById('barangayPageNotice');
            const pageNoticeClose = document.getElementById('barangayPageNoticeClose');
            let editImagePreviewUrl = null;

            const clearEditImagePreview = () => {
                if (editImagePreviewUrl) {
                    URL.revokeObjectURL(editImagePreviewUrl);
                    editImagePreviewUrl = null;
                }

                editImagePreviewImg?.removeAttribute('src');
                if (editImagePreviewCaption) {
                    editImagePreviewCaption.textContent = 'Preview before saving';
                }
                editImagePreview?.classList.remove('is-visible');
            };

            const showEditImagePreview = (file) => {
                clearEditImagePreview();

                if (!file || !file.type.startsWith('image/')) {
                    return;
                }

                editImagePreviewUrl = URL.createObjectURL(file);
                if (editImagePreviewImg) {
                    editImagePreviewImg.src = editImagePreviewUrl;
                }
                if (editImagePreviewCaption) {
                    editImagePreviewCaption.textContent = `${file.name} (${Math.ceil(file.size / 1024)} KB)`;
                }
                editImagePreview?.classList.add('is-visible');
            };

            const openModal = (modal) => {
                if (!modal) {
                    return;
                }

                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('menu-open');
                window.setTimeout(() => {
                    modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')?.focus();
                }, 0);
            };

            const closeModal = (modal) => {
                if (!modal) {
                    return;
                }

                if (modal.contains(document.activeElement)) {
                    document.activeElement?.blur?.();
                }

                modal.classList.remove('active');
                modal.setAttribute('aria-hidden', 'true');
                if (modal === editModal) {
                    clearEditImagePreview();
                }
                if (!document.querySelector('.barangay-modal-backdrop.active') && !document.getElementById('chiefLogoutConfirmModal')?.classList.contains('active')) {
                    document.body.classList.remove('menu-open');
                }
            };

            const showNotice = (notice) => {
                if (!notice) {
                    return;
                }

                requestAnimationFrame(() => {
                    notice.classList.add('show');
                });

                const dismiss = () => {
                    notice.classList.remove('show');
                    window.setTimeout(() => {
                        notice.parentElement?.remove();
                    }, 220);
                };

                pageNoticeClose?.addEventListener('click', dismiss, { once: true });
                window.setTimeout(dismiss, window.innerWidth <= 768 ? 3200 : 3800);
            };

            const bindLoadingSubmit = (form) => {
                if (!form) {
                    return;
                }

                form.addEventListener('submit', (event) => {
                    const submitButton = event.submitter || form.querySelector('[data-loading-submit-button]');
                    if (!(submitButton instanceof HTMLButtonElement)) {
                        return;
                    }

                    if (form.dataset.submitting === 'true') {
                        event.preventDefault();
                        return;
                    }

                    form.dataset.submitting = 'true';
                    submitButton.disabled = true;
                    submitButton.classList.add('is-loading');
                });
            };

            const renderDeleteSummary = (summaryItems) => {
                if (!deleteSummary) {
                    return 0;
                }

                const normalizedItems = Array.isArray(summaryItems) ? summaryItems : [];
                const connectedItems = normalizedItems.filter((item) => Number(item?.count || 0) > 0);
                const connectedTotal = connectedItems.reduce((total, item) => total + Number(item.count || 0), 0);

                if (connectedItems.length === 0) {
                    deleteSummary.innerHTML = '';
                    return 0;
                }

                deleteSummary.innerHTML = connectedItems.map((item) => `
                    <div class="barangay-delete-summary__item">
                        <span>${escapeHtml(item.label || 'Connected record')}</span>
                        <span class="barangay-delete-summary__count">${escapeHtml(item.count || 0)}</span>
                    </div>
                `).join('');

                return connectedTotal;
            };

            const renderDetailsSection = (title, items, formatter) => {
                if (!Array.isArray(items) || items.length === 0) {
                    return `
                        <section class="barangay-details-section">
                            <h3>${title}</h3>
                            <p class="barangay-details-empty">No records available.</p>
                        </section>
                    `;
                }

                return `
                    <section class="barangay-details-section">
                        <h3>${title}</h3>
                        <div class="barangay-details-list">
                            ${items.map(formatter).join('')}
                        </div>
                    </section>
                `;
            };

            const renderAdditionalBarangaySections = (payload) => [
                renderDetailsSection('Barangay Officers', payload?.barangay_officers, (officer) => `
                    <div class="barangay-details-item">
                        <div class="barangay-details-item__title">${escapeHtml(officer.name || 'Assigned officer')}</div>
                        <div class="barangay-details-item__meta">${escapeHtml(officer.position || 'Barangay officer')} &middot; ${escapeHtml(officer.status || 'Unknown')}</div>
                    </div>
                `),
                renderDetailsSection('Mitigation Actions', payload?.mitigation_actions, (action) => `
                    <div class="barangay-details-item">
                        <div class="barangay-details-item__title">${escapeHtml(action.title || 'Mitigation action')}</div>
                        <div class="barangay-details-item__meta">${escapeHtml(action.priority || 'Normal')} priority &middot; ${escapeHtml(action.status || 'Unknown')} &middot; ${escapeHtml(action.target_date || 'No target date')}</div>
                    </div>
                `),
                renderDetailsSection('Action Plan Checklists', payload?.action_plan_checklists, (checklist) => `
                    <div class="barangay-details-item">
                        <div class="barangay-details-item__title">${escapeHtml(checklist.title || 'Action plan')}</div>
                        <div class="barangay-details-item__meta">${escapeHtml(checklist.priority || 'Normal')} priority &middot; ${escapeHtml(checklist.status || 'Unknown')} &middot; ${escapeHtml(checklist.target_date || 'No target date')}</div>
                    </div>
                `),
                renderDetailsSection('Recommendations', payload?.recommendations, (recommendation) => `
                    <div class="barangay-details-item">
                        <div class="barangay-details-item__title">${escapeHtml(recommendation.title || 'Recommendation')}</div>
                        <div class="barangay-details-item__meta">${escapeHtml(recommendation.status || 'Suggested')} &middot; ${escapeHtml(recommendation.updated_at_human || 'Unknown')}</div>
                    </div>
                `),
                renderDetailsSection('Addresses', payload?.addresses, (address) => `
                    <div class="barangay-details-item">
                        <div class="barangay-details-item__title">${escapeHtml(address.street || 'Unnamed address')}</div>
                        <div class="barangay-details-item__meta">${escapeHtml(address.residents ?? 0)} resident record${Number(address.residents || 0) === 1 ? '' : 's'}</div>
                    </div>
                `),
                renderDetailsSection('Fire Records', payload?.fire_records, (record) => `
                    <div class="barangay-details-item">
                        <div class="barangay-details-item__title">${escapeHtml(record.date || 'Unknown')}</div>
                        <div class="barangay-details-item__meta">${escapeHtml(record.classification || 'Fire record')} &middot; ${escapeHtml(record.cause || 'Unknown cause')} &middot; ${escapeHtml(record.count || 1)} incident${Number(record.count || 1) === 1 ? '' : 's'}</div>
                    </div>
                `),
            ].join('');

            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');

            const renderBarangayDetails = (payload) => {
                const summary = payload?.summary || {};
                const coordinates = payload?.hall_coordinates?.lat !== null && payload?.hall_coordinates?.lng !== null
                    ? `${payload.hall_coordinates.lat}, ${payload.hall_coordinates.lng}`
                    : 'No coordinates yet';

                detailsModalTitle.textContent = payload?.barangay_name || 'Details';
                detailsModalCopy.textContent = [payload?.city_name, payload?.province_name].filter(Boolean).join(', ') || 'No city assigned';
                detailsModalBody.innerHTML = `
                    <div class="barangay-details-grid">
                        <div class="barangay-details-stat"><div class="barangay-details-stat__label">Residents</div><div class="barangay-details-stat__value">${summary.resident_count ?? 0}</div></div>
                        <div class="barangay-details-stat"><div class="barangay-details-stat__label">Addresses</div><div class="barangay-details-stat__value">${summary.address_count ?? 0}</div></div>
                        <div class="barangay-details-stat"><div class="barangay-details-stat__label">Boundaries</div><div class="barangay-details-stat__value">${summary.boundary_count ?? 0}</div></div>
                        <div class="barangay-details-stat"><div class="barangay-details-stat__label">Stations</div><div class="barangay-details-stat__value">${summary.station_count ?? 0}</div></div>
                        <div class="barangay-details-stat"><div class="barangay-details-stat__label">Risks</div><div class="barangay-details-stat__value">${summary.risk_area_count ?? 0}</div></div>
                        <div class="barangay-details-stat"><div class="barangay-details-stat__label">Incidents</div><div class="barangay-details-stat__value">${summary.live_incident_count ?? 0}</div></div>
                    </div>
                    <section class="barangay-details-section">
                        <h3>Info</h3>
                        <div class="barangay-details-list">
                            <div class="barangay-details-item"><div class="barangay-details-item__title">Hall</div><div class="barangay-details-item__meta">${escapeHtml(payload?.hall_name || 'No hall linked yet')}</div></div>
                            <div class="barangay-details-item"><div class="barangay-details-item__title">Contact</div><div class="barangay-details-item__meta">${escapeHtml(payload?.hall_contact || 'Not available')}</div></div>
                            <div class="barangay-details-item"><div class="barangay-details-item__title">Email</div><div class="barangay-details-item__meta">${escapeHtml(payload?.hall_email || 'Not available')}</div></div>
                            <div class="barangay-details-item"><div class="barangay-details-item__title">Coords</div><div class="barangay-details-item__meta">${escapeHtml(coordinates)}</div></div>
                            ${payload?.boundary_editor_url ? `<a class="barangay-button barangay-button--primary" href="${escapeHtml(payload.boundary_editor_url)}"><i class="fa-solid fa-draw-polygon"></i>Open Editor</a>` : ''}
                        </div>
                    </section>
                    ${renderDetailsSection('Boundaries', payload?.boundaries, (boundary) => `
                        <div class="barangay-details-item">
                            <div class="barangay-details-item__title">${escapeHtml(boundary.name || 'Unnamed boundary')}</div>
                            <div class="barangay-details-item__meta">${escapeHtml(boundary.source || 'MANUAL')} · ${escapeHtml(boundary.type || 'barangay')} · ${escapeHtml(boundary.updated_at_human || 'Unknown')}</div>
                        </div>
                    `)}
                    ${renderDetailsSection('Stations', payload?.stations, (station) => `
                        <div class="barangay-details-item">
                            <div class="barangay-details-item__title">${escapeHtml(station.station_name || 'Unnamed station')}</div>
                            <div class="barangay-details-item__meta">${escapeHtml(station.coverage_type || 'primary')} · ${escapeHtml(station.source || 'system')} · ${escapeHtml(station.address || 'No street address')}</div>
                        </div>
                    `)}
                    ${renderDetailsSection('Risks', payload?.recent_risk_areas, (riskArea) => `
                        <div class="barangay-details-item">
                            <div class="barangay-details-item__title">${escapeHtml(riskArea.entry || 'Risk')}</div>
                            <div class="barangay-details-item__meta">${escapeHtml(riskArea.assessment || 'Not assessed')} · ${escapeHtml(riskArea.status || 'Unknown')} · ${escapeHtml(riskArea.updated_at_human || 'Unknown')}</div>
                        </div>
                    `)}
                    ${renderDetailsSection('History', payload?.recent_historical_incidents, (incident) => `
                        <div class="barangay-details-item">
                            <div class="barangay-details-item__title">${escapeHtml(incident.date || 'Unknown')}</div>
                            <div class="barangay-details-item__meta">${escapeHtml(incident.cause || 'Unknown')} · ${escapeHtml(incident.location || 'Unknown')} · ${escapeHtml(incident.damage || 'Not recorded')}</div>
                        </div>
                    `)}
                `;
                detailsModalBody.insertAdjacentHTML('beforeend', renderAdditionalBarangaySections(payload));
            };

            if (dropdownButton && dropdownMenu) {
                dropdownButton.addEventListener('click', () => {
                    dropdownMenu.classList.toggle('show');
                });

                document.addEventListener('click', (event) => {
                    if (!dropdownButton.contains(event.target) && !dropdownMenu.contains(event.target)) {
                        dropdownMenu.classList.remove('show');
                    }
                });
            }

            openCreateModalButton?.addEventListener('click', () => openModal(createModal));

            document.querySelectorAll('[data-barangay-modal-close]').forEach((button) => {
                button.addEventListener('click', () => {
                    const modalId = button.getAttribute('data-barangay-modal-close');
                    closeModal(document.getElementById(modalId));
                });
            });

            [createModal, detailsModal, editModal, deleteModal].forEach((modal) => {
                modal?.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        closeModal(modal);
                    }
                });
            });

            document.querySelectorAll('[data-barangay-details-trigger]').forEach((trigger) => {
                trigger.addEventListener('click', async () => {
                    const detailsUrl = trigger.getAttribute('data-details-url');
                    if (!detailsUrl || !detailsModalBody) {
                        return;
                    }

                    detailsModalTitle.textContent = 'Details';
                    detailsModalCopy.textContent = 'Loading...';
                    detailsModalBody.innerHTML = '<p class="barangay-details-loading">Loading...</p>';
                    openModal(detailsModal);

                    try {
                        const response = await fetch(detailsUrl, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) {
                            throw new Error(`Failed to load details (${response.status})`);
                        }

                        const payload = await response.json();
                        renderBarangayDetails(payload?.data || {});
                    } catch (error) {
                        console.error('Unable to load barangay details:', error);
                        detailsModalBody.innerHTML = '<p class="barangay-details-empty">Unable to load details.</p>';
                    }
                });
            });

            editImageInput?.addEventListener('change', () => {
                showEditImagePreview(editImageInput.files?.[0] || null);
            });

            document.querySelectorAll('[data-barangay-edit-trigger]').forEach((trigger) => {
                trigger.addEventListener('click', () => {
                    const barangayId = trigger.getAttribute('data-barangay-id');
                    const barangayName = trigger.getAttribute('data-barangay-name') || '';
                    if (!editForm || !editNameInput || !editBarangayIdInput || !barangayId) {
                        return;
                    }

                    editForm.action = editFormActionTemplate.replace('__BARANGAY__', barangayId);
                    editNameInput.value = barangayName;
                    if (editImageInput) {
                        editImageInput.value = '';
                    }
                    clearEditImagePreview();
                    editBarangayIdInput.value = barangayId;
                    openModal(editModal);
                });
            });

            document.querySelectorAll('[data-barangay-delete-trigger]').forEach((trigger) => {
                trigger.addEventListener('click', () => {
                    const deleteUrl = trigger.getAttribute('data-delete-url') || '';
                    const barangayName = trigger.getAttribute('data-barangay-name') || 'this barangay';
                    const summaryPayload = trigger.getAttribute('data-delete-summary') || '[]';
                    const summaryItems = JSON.parse(summaryPayload);
                    const connectedTotal = renderDeleteSummary(summaryItems);

                    if (!deleteForm || !deleteWarning || !deleteNote || !deleteSubmitLabel || !forceDeleteRelatedInput || !deleteModalTitle || !deleteModalCopy) {
                        return;
                    }

                    deleteForm.action = deleteUrl;
                    deleteModalTitle.textContent = `Delete ${barangayName}`;

                    if (connectedTotal > 0) {
                        deleteModalCopy.textContent = 'Connected records found.';
                        deleteWarning.innerHTML = `
                            <div class="barangay-delete-warning__title">Blocked</div>
                            <p class="barangay-delete-warning__copy">${escapeHtml(barangayName)} has linked records.</p>
                        `;
                        deleteNote.textContent = 'Proceed deletes linked barangay records too.';
                        deleteSubmitLabel.textContent = 'Proceed Deletion';
                        forceDeleteRelatedInput.value = '1';
                    } else {
                        deleteModalCopy.textContent = 'Ready to delete.';
                        deleteWarning.innerHTML = `
                            <div class="barangay-delete-warning__title">Clear</div>
                            <p class="barangay-delete-warning__copy">No linked records for ${escapeHtml(barangayName)}.</p>
                        `;
                        deleteNote.textContent = 'This cannot be undone.';
                        deleteSubmitLabel.textContent = 'Delete';
                        forceDeleteRelatedInput.value = '0';
                    }

                    openModal(deleteModal);
                });
            });

            if ({{ $errors->has('barangay_name') && old('_modal_mode') !== 'edit' ? 'true' : 'false' }}) {
                openModal(createModal);
            }

            if ({{ old('_modal_mode') === 'edit' && ($errors->has('barangay_name') || $errors->has('barangay_card_image')) ? 'true' : 'false' }}) {
                const editingBarangayId = '{{ old('editing_barangay_id') }}';
                if (editForm && editFormActionTemplate && editingBarangayId) {
                    editForm.action = editFormActionTemplate.replace('__BARANGAY__', editingBarangayId);
                    openModal(editModal);
                }
            }

            document.querySelectorAll('[data-loading-submit-form]').forEach(bindLoadingSubmit);
            showNotice(pageNotice);

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeModal(createModal);
                    closeModal(detailsModal);
                    closeModal(editModal);
                    closeModal(deleteModal);
                }
            });

            if (openSidebar && sidebar && sidebarBackdrop) {
                openSidebar.addEventListener('click', () => {
                    sidebar.classList.add('open');
                    sidebarBackdrop.classList.remove('hidden');
                    document.body.classList.add('menu-open');
                });

                sidebarBackdrop.addEventListener('click', () => {
                    sidebar.classList.remove('open');
                    sidebarBackdrop.classList.add('hidden');
                    document.body.classList.remove('menu-open');
                });
            }
        })();
    </script>
    @include('chief.partials.logout-confirmation')
</body>
</html>
