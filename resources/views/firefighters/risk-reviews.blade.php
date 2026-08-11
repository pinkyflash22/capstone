<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PyroSense · {{ $pageTitle ?? 'Risk Reviews' }}</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/css/firefighters/portal.css'])
    @include('partials.pwa-head')

    <style data-ff-page-style="risk-reviews">
        body {
            background: #fbf7ef;
            overflow-x: hidden;
        }

        .main-content {
            padding: 24px;
        }

        .ff-review-shell {
            display: grid;
            gap: 24px;
        }

        .ff-review-hero {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) auto;
            gap: 18px;
            align-items: start;
            padding: 28px;
            border-radius: 28px;
            background: linear-gradient(135deg, #fff4ec 0%, #fff8f1 44%, #ffffff 100%);
            border: 1px solid rgba(198, 61, 38, 0.12);
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.06);
        }

        .ff-review-hero__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.88);
            color: #c2410c;
            border: 1px solid rgba(198, 61, 38, 0.1);
            font-size: .78rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .ff-review-hero h1 {
            margin: 14px 0 0;
            font-size: clamp(1.9rem, 2.2vw, 2.45rem);
            line-height: 1.05;
            font-weight: 900;
            color: #172033;
        }

        .ff-review-hero p {
            margin: 12px 0 0;
            max-width: 760px;
            color: #64748b;
            font-size: .97rem;
            line-height: 1.65;
        }

        .ff-review-hero__meta {
            display: grid;
            gap: 12px;
            min-width: min(280px, 100%);
        }

        .ff-review-meta-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            justify-content: space-between;
            padding: 12px 16px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(226, 232, 240, 0.95);
            color: #334155;
            font-size: .82rem;
            font-weight: 800;
        }

        .ff-review-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .ff-review-mobile-title {
            display: none;
        }

        .ff-review-stat {
            position: relative;
            overflow: hidden;
            padding: 20px;
            border-radius: 22px;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.92);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
        }

        .ff-review-stat::after {
            content: '';
            position: absolute;
            top: -24px;
            right: -24px;
            width: 90px;
            height: 90px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(198, 61, 38, 0.08), rgba(209, 138, 22, 0.12));
        }

        .ff-review-stat__label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            color: #64748b;
            font-size: .75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .ff-review-stat__label i {
            color: #c63d26;
            font-size: 1rem;
        }

        .ff-review-stat__value {
            margin-top: 16px;
            font-size: 2rem;
            line-height: 1;
            font-weight: 900;
            color: #172033;
        }

        .ff-review-stat__meta {
            margin-top: 8px;
            color: #94a3b8;
            font-size: .84rem;
        }

        .ff-review-toolbar,
        .ff-review-panel {
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.92);
            border-radius: 26px;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.05);
        }

        .ff-review-filter-shell {
            display: grid;
            gap: 12px;
            padding: 0 24px 18px;
        }

        .ff-review-toolbar {
            padding: 20px;
        }

        .ff-review-toolbar__grid {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) repeat(2, minmax(180px, .55fr)) auto;
            gap: 14px;
            align-items: center;
        }

        .ff-filter-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .ff-filter-tab {
            border: 0;
            cursor: pointer;
            padding: 10px 16px;
            border-radius: 999px;
            background: #fff7f3;
            color: #9a3412;
            font-size: .82rem;
            font-weight: 800;
            transition: all .2s ease;
        }

        .ff-filter-tab.active {
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            color: #fff;
            box-shadow: 0 10px 20px rgba(198,61,38,.2);
        }

        .ff-review-field,
        .ff-review-select {
            width: 100%;
            min-height: 48px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            background: #fffaf6;
            color: #172033;
            padding: 0 16px;
            outline: none;
            font-size: .92rem;
        }

        .ff-review-search-wrap,
        .ff-review-select-wrap {
            position: relative;
            min-width: 0;
        }

        .ff-review-search-wrap::before,
        .ff-review-select-wrap::before {
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            top: 50%;
            left: 16px;
            transform: translateY(-50%);
            color: #c63d26;
            pointer-events: none;
            z-index: 1;
            font-size: .9rem;
        }

        .ff-review-search-wrap::before {
            content: '\f002';
        }

        .ff-review-select-wrap::before {
            content: '\f0b0';
        }

        .ff-review-select-wrap::after {
            content: '\f078';
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            top: 50%;
            right: 16px;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            z-index: 1;
            font-size: .76rem;
        }

        .ff-review-search-wrap .ff-review-field,
        .ff-review-select-wrap .ff-review-select {
            padding-left: 42px;
        }

        .ff-review-select-wrap .ff-review-select {
            padding-right: 42px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        .ff-review-field:focus,
        .ff-review-select:focus,
        .ff-review-textarea:focus {
            border-color: rgba(198, 61, 38, 0.34);
            box-shadow: 0 0 0 4px rgba(198, 61, 38, 0.08);
        }

        .ff-review-toolbar__actions {
            display: inline-flex;
            gap: 10px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .ff-review-dynamic-root {
            position: relative;
            display: grid;
            gap: 24px;
        }

        .ff-review-dynamic-root.is-loading {
            pointer-events: none;
        }

        .ff-review-dynamic-root.is-loading::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 26px;
            background: rgba(251, 247, 239, 0.6);
            backdrop-filter: blur(2px);
            z-index: 5;
        }

        .ff-review-loading-chip {
            position: absolute;
            top: 14px;
            right: 16px;
            z-index: 6;
            display: none;
            align-items: center;
            gap: 8px;
            padding: 9px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(198, 61, 38, 0.12);
            color: #c63d26;
            font-size: .78rem;
            font-weight: 900;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
        }

        .ff-review-dynamic-root.is-loading .ff-review-loading-chip {
            display: inline-flex;
        }

        .ff-btn {
            min-height: 44px;
            border-radius: 15px;
            border: 0;
            padding: 0 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: .86rem;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .ff-btn:hover {
            transform: translateY(-1px);
        }

        .ff-btn--primary {
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            color: #fff;
            box-shadow: 0 14px 24px rgba(198, 61, 38, 0.22);
        }

        .ff-btn--ghost {
            background: #fff7f3;
            color: #9a3412;
            border: 1px solid rgba(198, 61, 38, 0.12);
        }

        .ff-btn--success {
            background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
            color: #fff;
        }

        .ff-btn--danger {
            background: linear-gradient(135deg, #dc2626 0%, #f97316 100%);
            color: #fff;
        }

        .ff-review-panel__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 24px 24px 12px;
        }

        .ff-review-panel__title {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 900;
            color: #172033;
        }

        .ff-review-panel__copy {
            margin: 6px 0 0;
            color: #64748b;
            font-size: .9rem;
        }

        .ff-results-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: #fff7f3;
            color: #c63d26;
            border: 1px solid rgba(198, 61, 38, 0.12);
            font-size: .8rem;
            font-weight: 800;
        }

        .ff-review-list {
            display: grid;
            gap: 14px;
            padding: 12px 24px 24px;
        }

        .ff-review-card {
            display: grid;
            grid-template-columns: 132px minmax(0, 1fr) auto;
            gap: 18px;
            padding: 18px;
            border-radius: 22px;
            background: linear-gradient(135deg, #ffffff 0%, #fff9f4 100%);
            border: 1px solid rgba(226, 232, 240, 0.92);
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.04);
        }

        .ff-review-card__media {
            width: 132px;
            min-height: 124px;
            border-radius: 20px;
            overflow: hidden;
            background: linear-gradient(135deg, #fff0e8 0%, #fff5dc 100%);
            border: 1px solid rgba(198, 61, 38, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #c63d26;
            font-size: 1.6rem;
            font-weight: 900;
        }

        .ff-review-card__media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ff-review-card__content {
            min-width: 0;
            display: grid;
            gap: 12px;
        }

        .ff-review-card__chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .ff-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 11px;
            border-radius: 999px;
            font-size: .74rem;
            font-weight: 900;
            letter-spacing: .04em;
        }

        .ff-chip--hazard {
            background: #fff7f3;
            color: #c63d26;
            border: 1px solid rgba(198, 61, 38, 0.12);
        }

        .ff-chip--risk {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid rgba(37, 99, 235, 0.12);
        }

        .ff-chip--pending {
            background: #fff7ed;
            color: #c2410c;
        }

        .ff-chip--approved {
            background: #ecfdf5;
            color: #047857;
        }

        .ff-chip--rejected {
            background: #fef2f2;
            color: #b91c1c;
        }

        .ff-chip--classification {
            background: #f8fafc;
            color: #475569;
            border: 1px solid rgba(148, 163, 184, 0.18);
        }

        .ff-chip--update {
            background: #f8fafc;
            color: #475569;
        }

        .ff-review-card__title {
            margin: 0;
            font-size: 1.05rem;
            line-height: 1.3;
            font-weight: 900;
            color: #172033;
        }

        .ff-review-card__desc {
            margin: 0;
            color: #64748b;
            font-size: .92rem;
            line-height: 1.58;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .ff-review-card__facts {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px 14px;
            color: #475569;
            font-size: .84rem;
            font-weight: 700;
            line-height: 1.45;
        }

        .ff-review-card__fact {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-width: 0;
        }

        .ff-review-card__fact i {
            color: #c63d26;
            font-size: .78rem;
            flex-shrink: 0;
        }

        .ff-review-card__fact span {
            min-width: 0;
            word-break: break-word;
        }

        .ff-review-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px 14px;
        }

        .ff-review-meta__item {
            padding: 12px 14px;
            border-radius: 16px;
            background: #f8fafc;
            border: 1px solid rgba(226, 232, 240, 0.92);
        }

        .ff-review-meta__label {
            display: block;
            margin-bottom: 4px;
            color: #94a3b8;
            font-size: .7rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .ff-review-meta__value {
            color: #334155;
            font-size: .88rem;
            font-weight: 800;
            line-height: 1.45;
            word-break: break-word;
        }

        .ff-review-card__actions {
            display: grid;
            gap: 10px;
            align-content: start;
            min-width: 168px;
        }

        .ff-empty {
            padding: 58px 20px;
            text-align: center;
            color: #94a3b8;
        }

        .ff-empty i {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 14px;
        }

        .ff-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            padding: 0 24px 24px;
        }

        .ff-pagination__summary {
            color: #64748b;
            font-size: .82rem;
            font-weight: 700;
        }

        .ff-pagination__controls {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .ff-modal {
            position: fixed;
            inset: 0;
            z-index: 10060;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background: rgba(15, 23, 42, 0.54);
            backdrop-filter: blur(8px);
        }

        .ff-modal.is-open {
            display: flex;
        }

        .ff-modal__dialog {
            width: min(980px, 100%);
            max-height: min(92vh, 920px);
            overflow: hidden;
            display: grid;
            grid-template-rows: auto 1fr;
            border-radius: 28px;
            background: #fffdfa;
            border: 1px solid rgba(226, 232, 240, 0.95);
            box-shadow: 0 28px 64px rgba(15, 23, 42, 0.24);
        }

        .ff-modal__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 24px 24px 18px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.86);
        }

        .ff-modal__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: #fff7f3;
            color: #c63d26;
            border: 1px solid rgba(198, 61, 38, 0.12);
            font-size: .75rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .ff-modal__title {
            margin: 14px 0 0;
            font-size: 1.4rem;
            line-height: 1.15;
            font-weight: 900;
            color: #172033;
        }

        .ff-modal__copy {
            margin: 8px 0 0;
            color: #64748b;
            font-size: .92rem;
            line-height: 1.55;
        }

        .ff-modal__close {
            width: 44px;
            height: 44px;
            border: 0;
            border-radius: 999px;
            background: #f8fafc;
            color: #64748b;
            cursor: pointer;
            flex-shrink: 0;
        }

        .ff-modal__body {
            overflow-y: auto;
            padding: 20px 24px 24px;
            display: grid;
            gap: 20px;
        }

        .ff-modal__steps {
            display: grid;
            gap: 14px;
        }

        .ff-modal__steps-head {
            display: grid;
            gap: 12px;
        }

        .ff-modal__step-progress {
            width: 100%;
            height: 10px;
            border-radius: 999px;
            background: #f1f5f9;
            overflow: hidden;
        }

        .ff-modal__step-progress-bar {
            width: 50%;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            transition: width .2s ease;
        }

        .ff-modal__step-indicators {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .ff-modal__step-indicator {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 18px;
            border: 1px solid rgba(226, 232, 240, 0.92);
            background: #fff;
            color: #64748b;
        }

        .ff-modal__step-indicator.is-active {
            border-color: rgba(198, 61, 38, 0.16);
            background: #fff7f3;
            color: #9a3412;
        }

        .ff-modal__step-indicator.is-complete {
            border-color: rgba(34, 197, 94, 0.18);
            background: #ecfdf5;
            color: #047857;
        }

        .ff-modal__step-number {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            font-size: .76rem;
            font-weight: 900;
            flex-shrink: 0;
        }

        .ff-modal__step-indicator.is-active .ff-modal__step-number {
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            color: #fff;
        }

        .ff-modal__step-indicator.is-complete .ff-modal__step-number {
            background: #16a34a;
            color: #fff;
        }

        .ff-modal__step-copy {
            display: grid;
            gap: 2px;
            min-width: 0;
        }

        .ff-modal__step-copy strong {
            color: #172033;
            font-size: .88rem;
            font-weight: 900;
        }

        .ff-modal__step-copy small {
            color: inherit;
            font-size: .76rem;
            line-height: 1.4;
        }

        .ff-modal__step-pane[hidden] {
            display: none !important;
        }

        .ff-step-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .ff-modal__grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(320px, .95fr);
            gap: 20px;
        }

        .ff-detail-card {
            border-radius: 22px;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.92);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
            padding: 18px;
        }

        .ff-detail-card__title {
            margin: 0 0 14px;
            font-size: .95rem;
            font-weight: 900;
            color: #172033;
        }

        .ff-detail-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
        }

        .ff-detail-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: .76rem;
            font-weight: 900;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .ff-detail-badge--hazard {
            background: #fff7f3;
            color: #c63d26;
            border: 1px solid rgba(198, 61, 38, 0.12);
        }

        .ff-detail-badge--risk {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid rgba(37, 99, 235, 0.12);
        }

        .ff-detail-badge--pending {
            background: #fff7ed;
            color: #c2410c;
        }

        .ff-detail-badge--approved {
            background: #ecfdf5;
            color: #047857;
        }

        .ff-detail-badge--rejected {
            background: #fef2f2;
            color: #b91c1c;
        }

        .ff-detail-badge--neutral {
            background: #f8fafc;
            color: #475569;
            border: 1px solid rgba(226, 232, 240, 0.92);
        }

        .ff-photo-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .ff-photo-tile {
            display: block;
            min-height: 106px;
            border-radius: 16px;
            overflow: hidden;
            background: linear-gradient(135deg, #fff0e8 0%, #fff5dc 100%);
            border: 1px solid rgba(198, 61, 38, 0.12);
        }

        .ff-photo-tile img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ff-photo-empty {
            min-height: 120px;
            border-radius: 16px;
            background: #f8fafc;
            border: 1px dashed rgba(148, 163, 184, 0.5);
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .88rem;
            font-weight: 700;
        }

        .ff-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .ff-detail-item {
            padding: 12px 14px;
            border-radius: 16px;
            background: #f8fafc;
            border: 1px solid rgba(226, 232, 240, 0.92);
        }

        .ff-detail-item--full {
            grid-column: 1 / -1;
        }

        .ff-detail-item__label {
            display: block;
            margin-bottom: 4px;
            color: #94a3b8;
            font-size: .7rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .ff-detail-item__value {
            color: #334155;
            font-size: .9rem;
            font-weight: 800;
            line-height: 1.48;
            word-break: break-word;
        }

        .ff-review-form {
            display: grid;
            gap: 14px;
        }

        .ff-review-form__row {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .ff-review-textarea {
            width: 100%;
            min-height: 118px;
            resize: vertical;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            background: #fffaf6;
            color: #172033;
            padding: 14px 16px;
            outline: none;
            font-size: .92rem;
            line-height: 1.6;
        }

        .ff-review-form__actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .ff-review-toast {
            position: fixed;
            top: 84px;
            right: 24px;
            z-index: 10090;
            min-width: min(360px, calc(100vw - 32px));
            max-width: 420px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 15px 18px;
            border-radius: 20px;
            border: 1px solid rgba(34, 197, 94, 0.22);
            background: rgba(248, 255, 250, 0.98);
            color: #166534;
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.16);
            opacity: 0;
            transform: translateY(-12px);
            pointer-events: none;
            transition: opacity .22s ease, transform .22s ease;
            backdrop-filter: blur(10px);
        }

        .ff-review-toast.is-visible {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .ff-review-toast__icon {
            flex-shrink: 0;
            width: 38px;
            height: 38px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(34, 197, 94, 0.12);
            color: #16a34a;
        }

        .ff-review-toast__body {
            min-width: 0;
            display: grid;
            gap: 3px;
            flex: 1;
        }

        .ff-review-toast__title {
            color: #14532d;
            font-size: .9rem;
            font-weight: 900;
            line-height: 1.2;
        }

        .ff-review-toast__message {
            color: #166534;
            font-size: .86rem;
            line-height: 1.45;
        }

        .ff-review-toast__close {
            width: 34px;
            height: 34px;
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: #166534;
            cursor: pointer;
            flex-shrink: 0;
        }

        @media (max-width: 1180px) {
            .ff-review-toolbar__grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .ff-review-toolbar__actions {
                grid-column: 1 / -1;
                justify-content: flex-start;
            }

            .ff-review-card {
                grid-template-columns: 112px minmax(0, 1fr);
            }

            .ff-review-card__actions {
                grid-column: 1 / -1;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .ff-modal__grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 900px) {
            .ff-review-hero,
            .ff-review-stats {
                grid-template-columns: 1fr;
            }

            .ff-review-hero__meta {
                min-width: 0;
            }

            .ff-review-meta,
            .ff-detail-grid,
            .ff-review-form__row,
            .ff-review-form__actions,
            .ff-modal__step-indicators {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .ff-page-shell {
                padding-bottom: calc(112px + env(safe-area-inset-bottom, 0px));
            }

            .main-content {
                padding: max(10px, env(safe-area-inset-top, 0px)) 10px calc(104px + env(safe-area-inset-bottom, 0px));
            }

            .ff-review-shell {
                gap: 10px;
            }

            .ff-review-hero,
            .ff-review-toolbar,
            .ff-review-panel__head,
            .ff-review-list,
            .ff-pagination,
            .ff-modal__head,
            .ff-modal__body {
                padding-left: 16px;
                padding-right: 16px;
            }

            .ff-review-mobile-title {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                padding: 12px 14px;
                border-radius: 18px;
                background: rgba(255,255,255,.94);
                border: 1px solid rgba(226,232,240,.9);
            }

            .ff-review-mobile-title__copy {
                min-width: 0;
            }

            .ff-review-mobile-title__eyebrow {
                margin: 0 0 2px;
                color: #c63d26;
                font-size: .62rem;
                font-weight: 900;
                letter-spacing: .12em;
                text-transform: uppercase;
            }

            .ff-review-mobile-title__heading {
                margin: 0;
                color: #172033;
                font-size: 1.05rem;
                font-weight: 900;
                line-height: 1.1;
            }

            .ff-review-mobile-title__count {
                flex: 0 0 auto;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 40px;
                height: 34px;
                padding: 0 10px;
                border-radius: 999px;
                background: #fff7f3;
                color: #c63d26;
                border: 1px solid rgba(198,61,38,.12);
                font-size: .72rem;
                font-weight: 900;
            }

            .ff-review-hero,
            .ff-review-stats {
                display: none !important;
            }

            .ff-review-toolbar {
                margin: 0;
                padding: 10px;
                gap: 8px;
                border-radius: 15px;
                box-shadow: none;
                border-color: rgba(226,232,240,.86);
            }

            .ff-review-filter-shell {
                padding: 0 10px 10px;
                gap: 10px;
            }

            .ff-filter-tabs {
                padding: 0;
                gap: 8px;
                overflow-x: auto;
                flex-wrap: nowrap;
                scrollbar-width: none;
            }

            .ff-filter-tabs::-webkit-scrollbar {
                display: none;
            }

            .ff-filter-tab {
                padding: 8px 12px;
                font-size: .72rem;
                white-space: nowrap;
                flex: 0 0 auto;
            }

            .ff-review-toolbar__grid {
                grid-template-columns: minmax(0, 1fr) 42px 42px 42px;
                gap: 8px;
            }

            .ff-review-field,
            .ff-review-select {
                min-height: 40px;
                height: 40px;
                border-radius: 12px;
                padding: 0 11px;
                font-size: .78rem;
            }

            .ff-review-select-wrap {
                width: 42px;
                height: 40px;
            }

            .ff-review-select-wrap::before {
                left: 50%;
                transform: translate(-50%, -50%);
                font-size: .82rem;
            }

            .ff-review-search-wrap .ff-review-field {
                padding-left: 36px;
            }

            .ff-review-select-wrap .ff-review-select {
                width: 42px;
                min-width: 42px;
                padding: 0;
                color: transparent;
                text-shadow: none;
                cursor: pointer;
                background: #fff7f3;
                border-color: rgba(198,61,38,.16);
            }

            .ff-review-select-wrap .ff-review-select option {
                color: #172033;
            }

            .ff-review-select-wrap::after {
                display: none;
            }

            .ff-review-toolbar__actions {
                width: 42px;
            }

            .ff-review-toolbar__actions .ff-btn {
                width: 42px;
                min-width: 42px;
                padding: 0;
                border-radius: 12px;
            }

            .ff-review-toolbar__actions .ff-btn span {
                display: none;
            }

            .ff-review-toolbar__actions .ff-btn i {
                font-size: .86rem;
                margin: 0;
            }

            .ff-review-panel {
                border-radius: 18px;
                box-shadow: none;
                border-color: rgba(226,232,240,.88);
                overflow: hidden;
            }

            .ff-review-card {
                grid-template-columns: 44px minmax(0, 1fr);
                gap: 8px;
                padding: 8px;
                border-radius: 16px;
                align-items: start;
            }

            .ff-review-card__media {
                width: 44px;
                min-height: 44px;
                height: 44px;
                border-radius: 12px;
                font-size: .82rem;
                align-self: start;
                aspect-ratio: 1 / 1;
                overflow: hidden;
            }

            .ff-review-card__media img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .ff-review-card__title {
                font-size: .8rem;
                line-height: 1.08;
            }

            .ff-review-card__desc {
                font-size: .67rem;
                line-height: 1.22;
                -webkit-line-clamp: 2;
            }

            .ff-chip {
                padding: 4px 7px;
                font-size: .55rem;
                gap: 3px;
            }

            .ff-review-card__facts {
                gap: 4px 10px;
                font-size: .62rem;
                line-height: 1.24;
            }

            .ff-review-card__fact {
                gap: 5px;
            }

            .ff-review-card__fact i {
                font-size: .58rem;
            }

            .ff-review-meta {
                grid-template-columns: 1fr;
                gap: 4px;
            }

            .ff-review-meta__item {
                padding: 0;
                background: transparent;
                border: 0;
            }

            .ff-review-meta__label {
                margin-bottom: 1px;
                font-size: .5rem;
            }

            .ff-review-meta__value {
                font-size: .62rem;
                line-height: 1.18;
            }

            .ff-review-card__actions {
                grid-column: 1 / -1;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 6px;
                min-width: 0;
            }

            .ff-review-panel__head {
                padding-top: 10px;
                padding-bottom: 8px;
                align-items: flex-start;
            }

            .ff-review-panel__title {
                font-size: .98rem;
            }

            .ff-review-panel__copy {
                display: none;
            }

            .ff-results-pill {
                padding: 6px 8px;
                gap: 4px;
                font-size: .62rem;
                white-space: nowrap;
            }

            .ff-results-pill i {
                font-size: .64rem;
            }

            .ff-review-list {
                padding-left: 8px;
                padding-right: 8px;
            }

            .ff-review-card__actions .ff-btn {
                min-height: 28px;
                padding: 0;
                border-radius: 9px;
                font-size: 0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .ff-review-card__actions .ff-btn i {
                display: inline-block;
                font-size: .74rem;
                line-height: 1;
                margin: 0;
                opacity: 1;
                visibility: visible;
            }

            .ff-review-card__actions .ff-btn--primary i {
                color: #ffffff;
            }

            .ff-review-card__actions .ff-btn--ghost i {
                color: #9a3412;
            }

            .ff-review-card__actions .ff-btn--ghost[style*="cursor:default"] i {
                color: #64748b;
            }

            .ff-pagination {
                padding-left: 8px;
                padding-right: 8px;
            }

            .ff-pagination {
                align-items: stretch;
            }

            .ff-pagination__controls {
                width: 100%;
                justify-content: space-between;
            }

            .ff-modal {
                padding: 0;
                align-items: flex-end;
            }

            .ff-modal__dialog {
                width: 100%;
                max-height: min(92vh, 100%);
                border-radius: 22px 22px 0 0;
            }

            .ff-modal__head {
                padding: 14px 16px 12px;
            }

            .ff-modal__title {
                font-size: 1.05rem;
                margin-top: 10px;
            }

            .ff-modal__copy {
                font-size: .82rem;
                line-height: 1.45;
            }

            .ff-modal__body {
                padding: 12px 16px 16px;
                gap: 14px;
            }

            .ff-photo-strip {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .ff-review-toast {
                top: auto;
                right: 12px;
                left: 12px;
                bottom: calc(96px + env(safe-area-inset-bottom, 0px));
                min-width: 0;
                max-width: none;
                border-radius: 18px;
                padding: 14px 16px;
                transform: translateY(14px);
            }

            .ff-review-toast.is-visible {
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="firefighter-portal-shell">
@php
    $summaryAll = (int) ($summaryCounts['all'] ?? 0);
    $summaryPending = (int) ($summaryCounts['pending'] ?? 0);
    $summaryApproved = (int) ($summaryCounts['approved'] ?? 0);
    $summaryRejected = (int) ($summaryCounts['rejected'] ?? 0);
    $statusMessage = session('status');
@endphp

@if ($statusMessage)
    <div
        id="ffReviewToast"
        class="ff-review-toast"
        role="status"
        aria-live="polite"
        aria-atomic="true"
        data-toast-message="{{ $statusMessage }}"
    >
        <div class="ff-review-toast__icon" aria-hidden="true">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="ff-review-toast__body">
            <div class="ff-review-toast__title">Review updated</div>
            <div class="ff-review-toast__message">{{ $statusMessage }}</div>
        </div>
        <button type="button" class="ff-review-toast__close" data-dismiss-review-toast aria-label="Close notification">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
@endif

<div class="dashboard-shell firefighter-shell ff-page-shell">
    @include('firefighters.partials.shell-sidebar', ['active' => 'risk-reviews'])

    <div data-ff-page-root="risk-reviews">
    <main class="main-content">
        @include('firefighters.partials.shell-topbar', [
            'title' => $pageTitle ?? 'Risk & Hazard Verification',
            'subtitle' => $pageSubtitle ?? 'Investigate pending submissions assigned to your station.',
        ])

            <div class="ff-review-shell">
                <section class="ff-review-hero">
                    <div>
                        <span class="ff-review-hero__eyebrow">
                            <i class="fa-solid fa-shield-halved"></i>
                            Station Review Queue
                        </span>
                        <h1>{{ $pageTitle ?? 'Risk & Hazard Verification' }}</h1>
                        <p>{{ $pageSubtitle ?? 'Investigate hazard and risk reports assigned to your station, review the attached evidence, and decide whether they should become verified live records.' }}</p>
                    </div>
                </section>

                <div class="ff-review-dynamic-root" data-review-dynamic-root>
                    <div class="ff-review-loading-chip" aria-hidden="true">
                        <i class="fa-solid fa-rotate fa-spin"></i>
                        Updating view
                    </div>

                    <section class="ff-review-mobile-title" aria-label="Risk review summary">
                        <div class="ff-review-mobile-title__copy">
                            <p class="ff-review-mobile-title__eyebrow">Responder verification</p>
                            <h1 class="ff-review-mobile-title__heading">Risk Reviews</h1>
                        </div>
                        <span class="ff-review-mobile-title__count">{{ $reviewQueue->total() }}</span>
                    </section>

                    <section class="ff-review-stats" aria-label="Review summary">
                        <article class="ff-review-stat">
                            <div class="ff-review-stat__label"><span>Total Records</span><i class="fa-solid fa-layer-group"></i></div>
                            <div class="ff-review-stat__value">{{ $summaryAll }}</div>
                            <div class="ff-review-stat__meta">Station-scoped hazard and risk records.</div>
                        </article>
                        <article class="ff-review-stat">
                            <div class="ff-review-stat__label"><span>Pending</span><i class="fa-solid fa-hourglass-half"></i></div>
                            <div class="ff-review-stat__value">{{ $summaryPending }}</div>
                            <div class="ff-review-stat__meta">Needs firefighter investigation and review.</div>
                        </article>
                        <article class="ff-review-stat">
                            <div class="ff-review-stat__label"><span>Approved</span><i class="fa-solid fa-circle-check"></i></div>
                            <div class="ff-review-stat__value">{{ $summaryApproved }}</div>
                            <div class="ff-review-stat__meta">Already verified as live records.</div>
                        </article>
                        <article class="ff-review-stat">
                            <div class="ff-review-stat__label"><span>Rejected</span><i class="fa-solid fa-ban"></i></div>
                            <div class="ff-review-stat__value">{{ $summaryRejected }}</div>
                            <div class="ff-review-stat__meta">Marked invalid or not actionable.</div>
                        </article>
                    </section>

                    <section class="ff-review-panel">
                        <div class="ff-review-panel__head">
                            <div>
                                <h2 class="ff-review-panel__title">Pending and reviewed submissions</h2>
                                <p class="ff-review-panel__copy">Open a record to inspect its evidence, reporter context, and final classification before approving or rejecting it.</p>
                            </div>
                            <span class="ff-results-pill">
                                <i class="fa-solid fa-list-check"></i>
                                {{ $reviewQueue->total() }} result{{ $reviewQueue->total() === 1 ? '' : 's' }}
                            </span>
                        </div>

                        <form method="GET" action="{{ route($indexRouteName) }}" class="ff-review-filter-shell" data-review-filter-form>
                            <input type="hidden" name="review_status" value="{{ $filters['review_status'] ?? 'pending' }}" data-review-status-input>

                            <div class="ff-filter-tabs" data-review-status-tabs>
                                <button type="button" class="ff-filter-tab {{ ($filters['review_status'] ?? 'pending') === 'all' ? 'active' : '' }}" data-review-status-tab="all">All</button>
                                <button type="button" class="ff-filter-tab {{ ($filters['review_status'] ?? 'pending') === 'pending' ? 'active' : '' }}" data-review-status-tab="pending">Pending</button>
                                <button type="button" class="ff-filter-tab {{ ($filters['review_status'] ?? '') === 'approved' ? 'active' : '' }}" data-review-status-tab="approved">Approved</button>
                                <button type="button" class="ff-filter-tab {{ ($filters['review_status'] ?? '') === 'rejected' ? 'active' : '' }}" data-review-status-tab="rejected">Rejected</button>
                            </div>

                            <div class="ff-review-toolbar">
                                <div class="ff-review-toolbar__grid">
                                    <div class="ff-review-search-wrap">
                                        <input
                                            type="search"
                                            name="search"
                                            value="{{ $filters['search'] ?? '' }}"
                                            class="ff-review-field"
                                            placeholder="Search location, reporter, or note"
                                            data-review-search
                                        >
                                    </div>

                                    <div class="ff-review-select-wrap">
                                        <select name="entry_type" class="ff-review-select" data-review-auto-filter aria-label="Filter by entry type" title="Filter by entry type">
                                            <option value="all" @selected(($filters['entry_type'] ?? 'all') === 'all')>All types</option>
                                            <option value="hazard" @selected(($filters['entry_type'] ?? '') === 'hazard')>Hazard</option>
                                            <option value="risk" @selected(($filters['entry_type'] ?? '') === 'risk')>Risk</option>
                                        </select>
                                    </div>

                                    <div class="ff-review-select-wrap">
                                        <select name="review_type" class="ff-review-select" data-review-auto-filter aria-label="Filter by request type" title="Filter by request type">
                                            <option value="all" @selected(($filters['review_type'] ?? 'all') === 'all')>All request types</option>
                                            <option value="create" @selected(($filters['review_type'] ?? '') === 'create')>Create</option>
                                            <option value="update" @selected(($filters['review_type'] ?? '') === 'update')>Update</option>
                                            <option value="delete" @selected(($filters['review_type'] ?? '') === 'delete')>Delete</option>
                                        </select>
                                    </div>

                                    <div class="ff-review-toolbar__actions">
                                        <a href="{{ route($indexRouteName) }}" class="ff-btn ff-btn--ghost" data-review-reset-link aria-label="Reset filters" title="Reset filters">
                                            <i class="fa-solid fa-rotate-left"></i>
                                            <span>Reset</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>

                    <div class="ff-review-list">
                        @forelse ($reviewQueue as $item)
                            @php
                                $entryType = strtolower((string) ($item->entry_type ?? 'risk'));
                                $reviewStatus = strtolower((string) ($item->review_status ?? 'pending'));
                                $reviewType = strtolower((string) ($item->review_type ?? 'create'));
                                $itemModalId = 'ffRiskReviewModal' . $item->risk_area_id;
                                $firstPhoto = $item->photos->first();
                                $photoUrl = $firstPhoto?->photo_path ? \App\Support\MediaPath::url($firstPhoto->photo_path) : null;
                                $title = trim((string) ($item->marker?->title ?? 'Reported area'));
                                $description = trim((string) ($item->marker?->description ?? $item->remarks ?? 'No narrative provided.'));
                                $barangayName = $item->barangayHall?->barangay?->barangay_name ?? 'Barangay pending';
                                $cityName = $item->barangayHall?->barangay?->city?->city_name ?? null;
                                $reporterName = trim((string) (($item->reporter?->first_name ?? '') . ' ' . ($item->reporter?->last_name ?? ''))) ?: 'Anonymous / system reporter';
                                $classificationLabel = $item->condition_label ?: ($item->risk_type ?: ($item->hazard_category ?: 'Unclassified'));
                                $addressLabel = collect([$item->street_or_sitio, $barangayName, $cityName])->filter()->join(', ');
                            @endphp

                            <article class="ff-review-card">
                                <div class="ff-review-card__media">
                                    @if ($photoUrl)
                                        <img src="{{ $photoUrl }}" alt="{{ $title }}">
                                    @else
                                        <div>
                                            <i class="fa-solid fa-camera"></i>
                                            <div style="margin-top:8px; font-size:.75rem; letter-spacing:.08em;">NO PHOTO</div>
                                        </div>
                                    @endif
                                </div>

                                <div class="ff-review-card__content">
                                    <div class="ff-review-card__chips">
                                        <span class="ff-chip {{ $entryType === 'hazard' ? 'ff-chip--hazard' : 'ff-chip--risk' }}">
                                            <i class="fa-solid {{ $entryType === 'hazard' ? 'fa-bolt' : 'fa-triangle-exclamation' }}"></i>
                                            {{ strtoupper($entryType) }}
                                        </span>
                                        <span class="ff-chip ff-chip--classification">
                                            <i class="fa-solid fa-tag"></i>
                                            {{ $classificationLabel }}
                                        </span>
                                        <span class="ff-chip ff-chip--{{ $reviewStatus }}">
                                            {{ strtoupper($reviewStatus) }}
                                        </span>
                                    </div>

                                    <div>
                                        <h3 class="ff-review-card__title">{{ $title }}</h3>
                                        <p class="ff-review-card__desc">{{ $description }}</p>
                                    </div>

                                    <div class="ff-review-card__facts" aria-label="Submission summary">
                                        <div class="ff-review-card__fact">
                                            <i class="fa-solid fa-location-dot"></i>
                                            <span>{{ collect([$barangayName, $cityName])->filter()->join(', ') }}</span>
                                        </div>
                                        <div class="ff-review-card__fact">
                                            <i class="fa-solid fa-calendar-days"></i>
                                            <span>{{ optional($item->created_at)->format('M d, Y h:i A') ?: 'Unknown' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="ff-review-card__actions">
                                    <button type="button" class="ff-btn ff-btn--primary" data-open-modal="{{ $itemModalId }}" aria-label="Open review details">
                                        <i class="fa-solid fa-eye"></i>
                                        <span>Review details</span>
                                    </button>
                                    @if ($reviewStatus === 'pending')
                                        <button type="button" class="ff-btn ff-btn--ghost" data-open-modal="{{ $itemModalId }}" aria-label="Investigate now">
                                            <i class="fa-solid fa-clipboard-check"></i>
                                            <span>Investigate now</span>
                                        </button>
                                    @else
                                        <span class="ff-btn ff-btn--ghost" style="cursor:default;">
                                            <i class="fa-solid fa-clock-rotate-left"></i>
                                            <span>Reviewed</span>
                                        </span>
                                    @endif
                                </div>
                            </article>

                            <div class="ff-modal" id="{{ $itemModalId }}" aria-hidden="true">
                                <div class="ff-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="{{ $itemModalId }}Title">
                                    <div class="ff-modal__head">
                                        <div>
                                            <span class="ff-modal__eyebrow">
                                                <i class="fa-solid fa-shield-halved"></i>
                                                Firefighter verification
                                            </span>
                                            <h3 class="ff-modal__title" id="{{ $itemModalId }}Title">{{ $title }}</h3>
                                            <p class="ff-modal__copy">Review the submission details and decide whether this pending hazard or risk should become a verified live record for your station.</p>
                                        </div>
                                        <button type="button" class="ff-modal__close" data-close-modal="{{ $itemModalId }}" aria-label="Close modal">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>

                                    <div class="ff-modal__body">
                                        <div class="ff-modal__steps" data-review-stepper>
                                            @if ($reviewStatus === 'pending')
                                                <div class="ff-modal__steps-head">
                                                    <div class="ff-modal__step-progress" aria-hidden="true">
                                                        <div class="ff-modal__step-progress-bar" data-review-step-progress></div>
                                                    </div>
                                                    <div class="ff-modal__step-indicators">
                                                        <div class="ff-modal__step-indicator is-active" data-review-step-indicator="1">
                                                            <span class="ff-modal__step-number">1</span>
                                                            <span class="ff-modal__step-copy">
                                                                <strong>Review Details</strong>
                                                                <small>Inspect photos, address, and citizen note first.</small>
                                                            </span>
                                                        </div>
                                                        <div class="ff-modal__step-indicator" data-review-step-indicator="2">
                                                            <span class="ff-modal__step-number">2</span>
                                                            <span class="ff-modal__step-copy">
                                                                <strong>Verify Risk / Hazard</strong>
                                                                <small>Confirm final classification and field assessment before approval.</small>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            <div class="ff-modal__step-pane" data-review-step-pane="1">
                                                <div class="ff-detail-card">
                                                    <h4 class="ff-detail-card__title">Step 1 - Review report details</h4>
                                                    <div class="ff-detail-badges">
                                                        <span class="ff-detail-badge {{ $entryType === 'hazard' ? 'ff-detail-badge--hazard' : 'ff-detail-badge--risk' }}">
                                                            <i class="fa-solid {{ $entryType === 'hazard' ? 'fa-bolt' : 'fa-triangle-exclamation' }}"></i>
                                                            {{ strtoupper($entryType) }}
                                                        </span>
                                                        <span class="ff-detail-badge ff-detail-badge--{{ $reviewStatus }}">
                                                            <i class="fa-solid fa-shield-halved"></i>
                                                            {{ strtoupper($reviewStatus) }}
                                                        </span>
                                                        <span class="ff-detail-badge ff-detail-badge--neutral">
                                                            <i class="fa-solid fa-user"></i>
                                                            {{ $reporterName }}
                                                        </span>
                                                    </div>

                                                    @if ($item->photos->isNotEmpty())
                                                        <div class="ff-photo-strip">
                                                            @foreach ($item->photos->take(6) as $photo)
                                                                @php $detailPhotoUrl = $photo->photo_path ? \App\Support\MediaPath::url($photo->photo_path) : null; @endphp
                                                                @if ($detailPhotoUrl)
                                                                    <a href="{{ $detailPhotoUrl }}" class="ff-photo-tile" target="_blank" rel="noopener noreferrer">
                                                                        <img src="{{ $detailPhotoUrl }}" alt="{{ $photo->photo_name ?? $title }}">
                                                                    </a>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <div class="ff-photo-empty">No submitted photos for this report.</div>
                                                    @endif

                                                    <div class="ff-detail-grid" style="margin-top:16px;">
                                                        <div class="ff-detail-item">
                                                            <span class="ff-detail-item__label">Classification</span>
                                                            <div class="ff-detail-item__value">{{ $classificationLabel }}</div>
                                                        </div>
                                                        <div class="ff-detail-item">
                                                            <span class="ff-detail-item__label">Submitted</span>
                                                            <div class="ff-detail-item__value">{{ optional($item->created_at)->format('M d, Y h:i A') ?: 'Unknown' }}</div>
                                                        </div>
                                                        <div class="ff-detail-item ff-detail-item--full">
                                                            <span class="ff-detail-item__label">Address</span>
                                                            <div class="ff-detail-item__value">{{ $addressLabel !== '' ? $addressLabel : 'Address not available' }}</div>
                                                        </div>
                                                        @if ($item->remarks || $description)
                                                            <div class="ff-detail-item ff-detail-item--full">
                                                                <span class="ff-detail-item__label">Citizen note</span>
                                                                <div class="ff-detail-item__value">{{ $item->remarks ?: $description }}</div>
                                                            </div>
                                                        @endif
                                                        <div class="ff-detail-item">
                                                            <span class="ff-detail-item__label">Current risk score</span>
                                                            <div class="ff-detail-item__value">{{ $item->risk_score !== null ? number_format((float) $item->risk_score, 1) . '/100' : 'Not set' }}</div>
                                                        </div>
                                                        <div class="ff-detail-item">
                                                            <span class="ff-detail-item__label">Current assessment</span>
                                                            <div class="ff-detail-item__value">{{ $item->assessment_level ? ucfirst($item->assessment_level) : 'Not set' }}</div>
                                                        </div>
                                                        <div class="ff-detail-item">
                                                            <span class="ff-detail-item__label">Estimated radius</span>
                                                            <div class="ff-detail-item__value">{{ $item->estimated_radius !== null ? number_format((float) $item->estimated_radius, 1) . ' m' : 'Not set' }}</div>
                                                        </div>
                                                        <div class="ff-detail-item">
                                                            <span class="ff-detail-item__label">Derived mode</span>
                                                            <div class="ff-detail-item__value">{{ $item->mode ? ucwords(str_replace('_', ' ', $item->mode)) : 'Not set' }}</div>
                                                        </div>
                                                    </div>

                                                    @if ($reviewStatus === 'pending')
                                                        <div class="ff-step-footer">
                                                            <span class="ff-results-pill"><i class="fa-solid fa-circle-info"></i>Step 1 of 2</span>
                                                            <button type="button" class="ff-btn ff-btn--primary" data-review-step-next>
                                                                <i class="fa-solid fa-arrow-right"></i>
                                                                Verify risk / hazard
                                                            </button>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="ff-modal__step-pane" data-review-step-pane="2" @if($reviewStatus === 'pending') hidden @endif>
                                                <div class="ff-detail-card">
                                                    <h4 class="ff-detail-card__title">{{ $reviewStatus === 'pending' ? 'Step 2 - Verify risk / hazard' : 'Verification decision' }}</h4>

                                                    @if ($reviewStatus === 'pending')
                                                        <form method="POST" action="{{ route($reviewRouteName, ['riskAreaId' => $item->risk_area_id]) }}" class="ff-review-form" data-review-form novalidate>
                                                            @csrf
                                                            @method('PATCH')

                                                            <input type="hidden" name="review_status" value="{{ $filters['review_status'] ?? 'pending' }}">
                                                            <input type="hidden" name="entry_type" value="{{ $filters['entry_type'] ?? 'all' }}">
                                                            <input type="hidden" name="review_type" value="{{ $filters['review_type'] ?? 'all' }}">
                                                            <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                                                            <input type="hidden" name="page" value="{{ $reviewQueue->currentPage() }}">

                                                            <div class="ff-review-form__row">
                                                                <div>
                                                                    <label class="ff-detail-item__label" for="finalEntryType{{ $item->risk_area_id }}">Final entry type</label>
                                                                    <select
                                                                        id="finalEntryType{{ $item->risk_area_id }}"
                                                                        name="final_entry_type"
                                                                        class="ff-review-select"
                                                                        data-review-entry-type
                                                                    >
                                                                        <option value="hazard" @selected($entryType === 'hazard')>Hazard</option>
                                                                        <option value="risk" @selected($entryType === 'risk')>Risk</option>
                                                                    </select>
                                                                </div>

                                                                <div data-risk-type-field @if($entryType !== 'risk') style="display:none;" @endif>
                                                                    <label class="ff-detail-item__label" for="finalRiskType{{ $item->risk_area_id }}">Final risk type</label>
                                                                    <select id="finalRiskType{{ $item->risk_area_id }}" name="final_risk_type" class="ff-review-select">
                                                                        <option value="">Select risk type</option>
                                                                        @foreach ($riskTypeOptions as $value => $label)
                                                                            <option value="{{ $value }}" @selected(($item->risk_type ?? '') === $value)>{{ $label }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>

                                                                <div data-hazard-type-field @if($entryType !== 'hazard') style="display:none;" @endif>
                                                                    <label class="ff-detail-item__label" for="finalHazardType{{ $item->risk_area_id }}">Final hazard category</label>
                                                                    <select id="finalHazardType{{ $item->risk_area_id }}" name="final_hazard_category" class="ff-review-select">
                                                                        <option value="">Select hazard category</option>
                                                                        @foreach ($hazardTypeOptions as $value => $label)
                                                                            <option value="{{ $value }}" @selected(($item->hazard_category ?? '') === $value)>{{ $label }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <div class="ff-review-form__row">
                                                                <div>
                                                                    <label class="ff-detail-item__label" for="estimatedRadius{{ $item->risk_area_id }}">Estimated radius (meters)</label>
                                                                    <input id="estimatedRadius{{ $item->risk_area_id }}" type="number" step="0.1" min="0" max="10000" name="estimated_radius" value="{{ $item->estimated_radius }}" class="ff-review-field" placeholder="Optional radius">
                                                                </div>
                                                                <div>
                                                                    <label class="ff-detail-item__label">System-derived risk score</label>
                                                                    <div class="ff-detail-item__value" style="padding:14px 16px; border-radius:16px; background:#fff7f3; border:1px solid rgba(198, 61, 38, 0.12);">
                                                                        The system calculates the risk score after verification using the final classification, condition severity, exposure, radius, access, and related field factors.
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="ff-review-form__row">
                                                                <div>
                                                                    <label class="ff-detail-item__label" for="assessmentLevel{{ $item->risk_area_id }}">Assessment level</label>
                                                                    <select id="assessmentLevel{{ $item->risk_area_id }}" name="assessment_level" class="ff-review-select">
                                                                        <option value="">Select assessment level</option>
                                                                        <option value="low" @selected(($item->assessment_level ?? '') === 'low')>Low</option>
                                                                        <option value="moderate" @selected(($item->assessment_level ?? '') === 'moderate')>Moderate</option>
                                                                        <option value="high" @selected(($item->assessment_level ?? '') === 'high')>High</option>
                                                                    </select>
                                                                </div>
                                                                <div>
                                                                    <label class="ff-detail-item__label" for="modePreview{{ $item->risk_area_id }}">Current derived mode</label>
                                                                    <input id="modePreview{{ $item->risk_area_id }}" type="text" value="{{ $item->mode ? ucwords(str_replace('_', ' ', $item->mode)) : 'Will derive from classification' }}" class="ff-review-field" readonly>
                                                                </div>
                                                            </div>

                                                            <input type="hidden" name="final_condition_code" value="{{ $item->condition_code }}">

                                                            <div>
                                                                <label class="ff-detail-item__label" for="assessmentDescription{{ $item->risk_area_id }}">Assessment description</label>
                                                                <textarea
                                                                    id="assessmentDescription{{ $item->risk_area_id }}"
                                                                    name="assessment_description"
                                                                    class="ff-review-textarea"
                                                                    data-review-text-field
                                                                    placeholder="Summarize field findings, severity, and observed exposure."
                                                                >{{ $item->assessment_description }}</textarea>
                                                            </div>

                                                            <div>
                                                                <label class="ff-detail-item__label" for="reviewNotes{{ $item->risk_area_id }}">Review notes</label>
                                                                <textarea
                                                                    id="reviewNotes{{ $item->risk_area_id }}"
                                                                    name="review_notes"
                                                                    class="ff-review-textarea"
                                                                    data-review-text-field
                                                                    placeholder="Add your investigation notes, field findings, or reason for approval/rejection."
                                                                ></textarea>
                                                            </div>

                                                            <div class="ff-step-footer">
                                                                <button type="button" class="ff-btn ff-btn--ghost" data-review-step-back>
                                                                    <i class="fa-solid fa-arrow-left"></i>
                                                                    Back to details
                                                                </button>
                                                                <span class="ff-results-pill"><i class="fa-solid fa-list-check"></i>Step 2 of 2</span>
                                                            </div>

                                                            <div class="ff-review-form__actions">
                                                                <button type="submit" name="decision" value="approved" class="ff-btn ff-btn--success">
                                                                    <i class="fa-solid fa-circle-check"></i>
                                                                    Approve record
                                                                </button>
                                                                <button type="submit" name="decision" value="rejected" class="ff-btn ff-btn--danger">
                                                                    <i class="fa-solid fa-ban"></i>
                                                                    Reject record
                                                                </button>
                                                            </div>
                                                        </form>
                                                    @else
                                                        <div class="ff-detail-grid">
                                                            <div class="ff-detail-item">
                                                                <span class="ff-detail-item__label">Decision</span>
                                                                <div class="ff-detail-item__value">
                                                                    <span class="ff-detail-badge ff-detail-badge--{{ $reviewStatus }}">{{ strtoupper($reviewStatus) }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="ff-detail-item">
                                                                <span class="ff-detail-item__label">Reviewed at</span>
                                                                <div class="ff-detail-item__value">{{ optional($item->reviewed_at)->format('M d, Y h:i A') ?: 'Unknown' }}</div>
                                                            </div>
                                                            <div class="ff-detail-item">
                                                                <span class="ff-detail-item__label">Risk score</span>
                                                                <div class="ff-detail-item__value">{{ $item->risk_score !== null ? number_format((float) $item->risk_score, 1) . '/100' : 'Not set' }}</div>
                                                            </div>
                                                            <div class="ff-detail-item">
                                                                <span class="ff-detail-item__label">Assessment level</span>
                                                                <div class="ff-detail-item__value">{{ $item->assessment_level ? ucfirst($item->assessment_level) : 'Not set' }}</div>
                                                            </div>
                                                            <div class="ff-detail-item ff-detail-item--full">
                                                                <span class="ff-detail-item__label">Assessment description</span>
                                                                <div class="ff-detail-item__value">{{ $item->assessment_description ?: 'No assessment description was recorded.' }}</div>
                                                            </div>
                                                            <div class="ff-detail-item ff-detail-item--full">
                                                                <span class="ff-detail-item__label">Reviewer notes</span>
                                                                <div class="ff-detail-item__value">{{ $item->review_notes ?: 'No review notes were recorded.' }}</div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="ff-empty">
                                <i class="fa-solid fa-inbox"></i>
                                <div style="font-size:1rem; font-weight:800; color:#475569;">No matching risk or hazard submissions.</div>
                                <div style="margin-top:6px;">Try changing the filters or wait for new pending reports assigned to your station.</div>
                            </div>
                        @endforelse
                    </div>

                    @if ($reviewQueue->hasPages())
                        @php
                            $from = $reviewQueue->firstItem() ?? 0;
                            $to = $reviewQueue->lastItem() ?? 0;
                        @endphp
                        <div class="ff-pagination">
                            <div class="ff-pagination__summary">
                                Showing {{ $from }} to {{ $to }} of {{ $reviewQueue->total() }} review record{{ $reviewQueue->total() === 1 ? '' : 's' }}.
                            </div>
                            <div class="ff-pagination__controls">
                                @if ($reviewQueue->onFirstPage())
                                    <span class="ff-btn ff-btn--ghost" style="cursor:default; opacity:.55;">Previous</span>
                                @else
                                    <a href="{{ $reviewQueue->previousPageUrl() }}" class="ff-btn ff-btn--ghost">Previous</a>
                                @endif

                                <span class="ff-results-pill">Page {{ $reviewQueue->currentPage() }} of {{ $reviewQueue->lastPage() }}</span>

                                @if ($reviewQueue->hasMorePages())
                                    <a href="{{ $reviewQueue->nextPageUrl() }}" class="ff-btn ff-btn--ghost">Next</a>
                                @else
                                    <span class="ff-btn ff-btn--ghost" style="cursor:default; opacity:.55;">Next</span>
                                @endif
                            </div>
                        </div>
                    @endif
                    </section>
                </div>
            </div>
    <script>
document.addEventListener('DOMContentLoaded', function () {
        let searchTimer = null;
        let currentRequest = null;

        const dynamicRoot = () => document.querySelector('[data-review-dynamic-root]');
        const filterForm = () => document.querySelector('[data-review-filter-form]');
        const reviewToast = () => document.getElementById('ffReviewToast');

        const closeModal = (modal) => {
            if (!modal) {
                return;
            }

            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        };

        const setReviewStep = (modal, step) => {
            if (!modal) {
                return;
            }

            const normalizedStep = String(step) === '2' ? '2' : '1';
            const progressBar = modal.querySelector('[data-review-step-progress]');

            modal.querySelectorAll('[data-review-step-pane]').forEach((pane) => {
                pane.hidden = pane.dataset.reviewStepPane !== normalizedStep;
            });

            modal.querySelectorAll('[data-review-step-indicator]').forEach((indicator) => {
                const indicatorStep = indicator.dataset.reviewStepIndicator;
                indicator.classList.remove('is-active', 'is-complete');

                if (indicatorStep === normalizedStep) {
                    indicator.classList.add('is-active');
                } else if (normalizedStep === '2' && indicatorStep === '1') {
                    indicator.classList.add('is-complete');
                }
            });

            if (progressBar) {
                progressBar.style.width = normalizedStep === '2' ? '100%' : '50%';
            }

            modal.dataset.reviewCurrentStep = normalizedStep;
        };

        const openModal = (modal) => {
            if (!modal) {
                return;
            }

            if (modal.querySelector('[data-review-step-pane="1"]')) {
                setReviewStep(modal, '1');
            }

            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        };

        const bindModalTriggers = () => {
            document.querySelectorAll('[data-open-modal]').forEach((button) => {
                if (button.dataset.boundModalOpen === 'true') {
                    return;
                }

                button.dataset.boundModalOpen = 'true';
                button.addEventListener('click', function () {
                    openModal(document.getElementById(button.dataset.openModal));
                });
            });

            document.querySelectorAll('[data-close-modal]').forEach((button) => {
                if (button.dataset.boundModalClose === 'true') {
                    return;
                }

                button.dataset.boundModalClose = 'true';
                button.addEventListener('click', function () {
                    closeModal(document.getElementById(button.dataset.closeModal));
                });
            });
        };

        const bindModalBackdrops = () => {
            document.querySelectorAll('.ff-modal').forEach((modal) => {
                if (modal.dataset.boundModalBackdrop === 'true') {
                    return;
                }

                modal.dataset.boundModalBackdrop = 'true';
                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        closeModal(modal);
                    }
                });
            });
        };

        const bindClassificationFields = () => {
            document.querySelectorAll('[data-review-entry-type]').forEach((select) => {
                const modal = select.closest('.ff-modal');
                const riskField = modal ? modal.querySelector('[data-risk-type-field]') : null;
                const hazardField = modal ? modal.querySelector('[data-hazard-type-field]') : null;

                const syncClassificationFields = () => {
                    const value = String(select.value || '').toLowerCase();

                    if (riskField) {
                        riskField.style.display = value === 'risk' ? '' : 'none';
                    }

                    if (hazardField) {
                        hazardField.style.display = value === 'hazard' ? '' : 'none';
                    }
                };

                syncClassificationFields();
                select.addEventListener('change', syncClassificationFields);
            });
        };

        const bindReviewSteppers = () => {
            document.querySelectorAll('.ff-modal').forEach((modal) => {
                if (modal.dataset.stepperBound === 'true') {
                    return;
                }

                modal.dataset.stepperBound = 'true';

                modal.querySelectorAll('[data-review-step-next]').forEach((button) => {
                    button.addEventListener('click', function () {
                        setReviewStep(modal, '2');
                    });
                });

                modal.querySelectorAll('[data-review-step-back]').forEach((button) => {
                    button.addEventListener('click', function () {
                        setReviewStep(modal, '1');
                    });
                });

                if (modal.querySelector('[data-review-step-pane="1"]')) {
                    setReviewStep(modal, modal.dataset.reviewCurrentStep || '1');
                }
            });
        };

        const bindReviewFieldValidation = () => {
            const allowedTextPattern = /^[\p{L}\p{N}\s\.,:\-\(\)]*$/u;
            const allowedTextMessage = 'Only letters, numbers, spaces, and these symbols are allowed: . , : - ( )';

            document.querySelectorAll('[data-review-text-field]').forEach((field) => {
                if (field.dataset.reviewTextValidationBound === 'true') {
                    return;
                }

                field.dataset.reviewTextValidationBound = 'true';

                const validateField = () => {
                    const value = field.value || '';

                    if (value !== '' && !allowedTextPattern.test(value)) {
                        field.setCustomValidity(allowedTextMessage);
                    } else {
                        field.setCustomValidity('');
                    }
                };

                validateField();
                field.addEventListener('input', validateField);
                field.addEventListener('blur', validateField);
            });

            document.querySelectorAll('input[name="estimated_radius"]').forEach((field) => {
                if (field.dataset.reviewRadiusValidationBound === 'true') {
                    return;
                }

                field.dataset.reviewRadiusValidationBound = 'true';

                const validateRadius = () => {
                    const rawValue = field.value;

                    if (rawValue === '') {
                        field.setCustomValidity('');
                        return;
                    }

                    const numericValue = Number(rawValue);
                    if (Number.isNaN(numericValue) || numericValue < 0) {
                        field.setCustomValidity('Estimated radius cannot be negative.');
                    } else {
                        field.setCustomValidity('');
                    }
                };

                validateRadius();
                field.addEventListener('input', validateRadius);
                field.addEventListener('blur', validateRadius);
            });
        };

        const bindReviewFormValidation = () => {
            document.querySelectorAll('[data-review-form]').forEach((form) => {
                if (form.dataset.reviewFormValidationBound === 'true') {
                    return;
                }

                form.dataset.reviewFormValidationBound = 'true';

                form.addEventListener('submit', function (event) {
                    form.querySelectorAll('[data-review-text-field]').forEach((field) => {
                        field.dispatchEvent(new Event('blur', { bubbles: true }));
                    });

                    form.querySelectorAll('input[name="estimated_radius"]').forEach((field) => {
                        field.dispatchEvent(new Event('blur', { bubbles: true }));
                    });

                    if (!form.checkValidity()) {
                        event.preventDefault();
                        form.reportValidity();
                    }
                });
            });
        };

        const bindToast = () => {
            const toast = reviewToast();
            if (!toast || toast.dataset.toastBound === 'true') {
                return;
            }

            toast.dataset.toastBound = 'true';

            const hideToast = () => {
                toast.classList.remove('is-visible');
            };

            toast.querySelector('[data-dismiss-review-toast]')?.addEventListener('click', hideToast);

            window.setTimeout(() => {
                toast.classList.add('is-visible');
            }, 120);

            window.setTimeout(hideToast, 4200);
        };

        const setDynamicLoading = (isLoading) => {
            const root = dynamicRoot();
            if (!root) {
                return;
            }

            root.classList.toggle('is-loading', isLoading);
        };

        const updateDynamicContentFromDocument = (doc) => {
            const currentRoot = dynamicRoot();
            const nextRoot = doc.querySelector('[data-review-dynamic-root]');

            if (!currentRoot || !nextRoot) {
                return false;
            }

            currentRoot.innerHTML = nextRoot.innerHTML;
            return true;
        };

        const buildFilterUrl = () => {
            const form = filterForm();
            if (!form) {
                return window.location.href;
            }

            const url = new URL(form.action, window.location.origin);
            const params = new URLSearchParams(new FormData(form));
            params.delete('page');
            url.search = params.toString();

            return url.toString();
        };

        const fetchReviewSection = async (url, pushState = true) => {
            try {
                if (currentRequest) {
                    currentRequest.abort();
                }

                currentRequest = new AbortController();
                setDynamicLoading(true);

                const response = await fetch(url, {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: currentRequest.signal,
                });

                if (!response.ok) {
                    throw new Error('Failed to refresh risk reviews.');
                }

                const html = await response.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');

                if (!updateDynamicContentFromDocument(doc)) {
                    window.location.href = url;
                    return;
                }

                if (pushState) {
                    window.history.pushState({ firefighterRiskReviews: true }, '', url);
                }

                rebindDynamicUi();
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error(error);
                    window.location.href = url;
                }
            } finally {
                currentRequest = null;
                setDynamicLoading(false);
            }
        };

        const bindPaginationLinks = () => {
            const root = dynamicRoot();
            if (!root) {
                return;
            }

            root.querySelectorAll('.ff-pagination a').forEach((link) => {
                if (link.dataset.boundAjaxPagination === 'true') {
                    return;
                }

                link.dataset.boundAjaxPagination = 'true';
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    fetchReviewSection(link.href, true);
                });
            });
        };

        const bindFilterControls = () => {
            const form = filterForm();
            if (!form || form.dataset.boundAjaxFilters === 'true') {
                return;
            }

            form.dataset.boundAjaxFilters = 'true';

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                fetchReviewSection(buildFilterUrl(), true);
            });

            const statusInput = form.querySelector('[data-review-status-input]');
            form.querySelectorAll('[data-review-status-tab]').forEach((button) => {
                button.addEventListener('click', function () {
                    const nextValue = button.dataset.reviewStatusTab || 'pending';

                    if (statusInput) {
                        statusInput.value = nextValue;
                    }

                    form.querySelectorAll('[data-review-status-tab]').forEach((tab) => {
                        tab.classList.toggle('active', tab === button);
                    });

                    fetchReviewSection(buildFilterUrl(), true);
                });
            });

            form.querySelectorAll('[data-review-auto-filter]').forEach((field) => {
                field.addEventListener('change', function () {
                    fetchReviewSection(buildFilterUrl(), true);
                });
            });

            const searchField = form.querySelector('[data-review-search]');
            if (searchField) {
                searchField.addEventListener('input', function () {
                    window.clearTimeout(searchTimer);
                    searchTimer = window.setTimeout(function () {
                        fetchReviewSection(buildFilterUrl(), true);
                    }, 280);
                });
            }

            const resetLink = form.querySelector('[data-review-reset-link]');
            if (resetLink) {
                resetLink.addEventListener('click', function (event) {
                    event.preventDefault();
                    fetchReviewSection(resetLink.href, true);
                });
            }
        };

        const rebindDynamicUi = () => {
            bindModalTriggers();
            bindModalBackdrops();
            bindClassificationFields();
            bindReviewSteppers();
            bindReviewFieldValidation();
            bindReviewFormValidation();
            bindToast();
            bindFilterControls();
            bindPaginationLinks();
        };

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('.ff-modal.is-open').forEach((modal) => closeModal(modal));
            }
        });

        window.addEventListener('popstate', function () {
            fetchReviewSection(window.location.href, false);
        });

        rebindDynamicUi();
});
    </script>
    </main>
    </div>
</div>
</body>
</html>
