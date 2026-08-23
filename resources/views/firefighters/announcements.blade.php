<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PyroSense - Announcements</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/css/admin/announcements.css', 'resources/css/firefighters/portal.css'])

    @php
        $authName = trim((auth()->user()->first_name ?? '') . ' ' . (auth()->user()->last_name ?? '')) ?: 'Firefighter';
        $authInitials = strtoupper(substr(auth()->user()->first_name ?? 'F', 0, 1) . substr(auth()->user()->last_name ?? 'F', 0, 1));
        $authProfileImageUrl = \App\Support\MediaPath::url(auth()->user()?->image);
    @endphp

    <style data-ff-page-style="announcements">
        .main-content {
            max-width: none;
        }
        .page-shell {
            max-width: 980px;
            margin: 0 auto;
        }
        .composer-card,
        .post-card {
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
        }
        .composer-card {
            background: linear-gradient(135deg, #fff8f2 0%, #ffffff 100%);
        }
        .composer-header {
            display: flex;
            gap: 14px;
            margin-bottom: 18px;
        }
        .composer-avatar,
        .post-author-avatar,
        .comment-avatar {
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
        }
        .composer-avatar img,
        .post-author-avatar img,
        .comment-avatar img,
        .media-drawer-avatar img,
        .media-drawer-comment-avatar img {
            width: 100%;
            height: 100%;
            display: block;
            border-radius: inherit;
            object-fit: cover;
        }
        .composer-meta {
            display: grid;
            gap: 6px;
        }
        .composer-name {
            color: #172033;
            font-weight: 800;
        }
        .composer-role {
            color: #64748b;
            font-size: 0.84rem;
        }
        .composer-controls {
            display: grid;
            gap: 14px;
        }
        .poll-builder {
            display: none;
            gap: 12px;
            padding: 14px;
            border-radius: 14px;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.9);
        }
        .poll-builder.active {
            display: grid;
        }
        .poll-option-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 10px;
        }
        .video-preview {
            display: none;
            padding: 12px;
            border-radius: 14px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            background: #fff;
        }
        .video-preview.active {
            display: block;
        }
        .video-preview video {
            width: 100%;
            max-height: 360px;
            border-radius: 12px;
            background: #000;
            object-fit: contain;
        }
        .post-header {
            align-items: flex-start;
        }
        .post-header-main {
            display: flex;
            gap: 12px;
            flex: 1;
            min-width: 0;
        }
        .post-author-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            width: 100%;
        }
        .post-author-role {
            color: #64748b;
            font-size: 0.83rem;
        }
        .post-menu {
            position: relative;
        }
        .post-menu-trigger {
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 999px;
            color: #64748b;
            background: #f8fafc;
            cursor: pointer;
        }
        .post-menu-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            display: none;
            min-width: 160px;
            padding: 8px;
            border-radius: 14px;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 18px 30px rgba(15, 23, 42, 0.12);
            z-index: 20;
        }
        .post-menu-dropdown.active {
            display: grid;
            gap: 6px;
        }
        .post-menu-dropdown button {
            padding: 10px 12px;
            border: 0;
            border-radius: 10px;
            text-align: left;
            background: #fff;
            color: #334155;
            font-weight: 600;
            cursor: pointer;
        }
        .post-menu-dropdown button:hover {
            background: #fff7f3;
            color: #9a3412;
        }
        .post-media {
            display: grid;
            gap: 10px;
            background: #f8fafc;
            padding: 10px;
        }
        .post-media img,
        .post-media video {
            width: 100%;
            max-height: 520px;
            object-fit: contain;
            border-radius: 12px;
            background: #0f172a;
        }
        .post-media.media-error {
            min-height: 120px;
            place-items: center;
            color: #94a3b8;
            font-size: 0.9rem;
        }
        .post-media.media-error::before {
            content: 'Media unavailable';
        }
        .post-poll {
            margin-top: 12px;
            padding: 14px;
            border-radius: 14px;
            background: #fffaf6;
            border: 1px solid rgba(226, 232, 240, 0.9);
        }
        .post-poll-question {
            color: #172033;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .post-poll-options {
            display: grid;
            gap: 8px;
        }
        .post-poll-option {
            padding: 10px 12px;
            border-radius: 12px;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.9);
            color: #475569;
        }
        .editor-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(15, 23, 42, 0.55);
            z-index: 3200;
        }
        .editor-modal.active {
            display: flex;
        }
        .editor-panel {
            width: min(760px, 100%);
            max-height: calc(100vh - 40px);
            overflow: hidden;
            border-radius: 24px;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 24px 56px rgba(15, 23, 42, 0.18);
        }
        .editor-header,
        .editor-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 20px 22px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.9);
        }
        .editor-footer {
            justify-content: flex-end;
            border-top: 1px solid rgba(226, 232, 240, 0.9);
            border-bottom: 0;
        }
        .editor-body {
            max-height: calc(100vh - 180px);
            overflow-y: auto;
            padding: 22px;
            display: grid;
            gap: 14px;
        }
        .editor-close {
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 999px;
            background: #f8fafc;
            color: #475569;
            cursor: pointer;
        }
        .media-drawer {
            position: fixed;
            inset: 0;
            display: none;
            justify-content: flex-end;
            background: rgba(15, 23, 42, 0.45);
            z-index: 3400;
        }
        .media-drawer.active {
            display: flex;
        }
        .media-drawer-panel {
            width: min(1120px, 100%);
            height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(360px, 420px);
            background: #fff;
            box-shadow: -18px 0 40px rgba(15, 23, 42, 0.16);
        }
        .media-drawer-visual {
            background: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px;
        }
        .media-drawer-visual img,
        .media-drawer-visual video {
            width: 100%;
            max-height: calc(100vh - 56px);
            object-fit: contain;
            border-radius: 18px;
            background: #020617;
        }
        .media-drawer-side {
            display: grid;
            grid-template-rows: auto 1fr auto auto;
            min-height: 0;
            background: linear-gradient(180deg, #fffaf5 0%, #ffffff 100%);
        }
        .media-drawer-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            padding: 20px 22px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.92);
        }
        .media-drawer-author {
            display: flex;
            gap: 12px;
            min-width: 0;
        }
        .media-drawer-avatar {
            width: 44px;
            height: 44px;
            flex-shrink: 0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: #fff;
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
        }
        .media-drawer-name {
            color: #172033;
            font-weight: 800;
        }
        .media-drawer-meta {
            margin-top: 4px;
            color: #64748b;
            font-size: 0.83rem;
            line-height: 1.5;
        }
        .media-drawer-close {
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 999px;
            background: #f8fafc;
            color: #475569;
            cursor: pointer;
        }
        .media-drawer-body {
            min-height: 0;
            overflow-y: auto;
            padding: 18px 22px 20px;
            display: grid;
            gap: 16px;
        }
        .media-drawer-content {
            color: #1f2937;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        .media-drawer-stats {
            display: flex;
            gap: 18px;
            padding: 0 22px 16px;
            border-top: 1px solid rgba(226, 232, 240, 0.92);
            border-bottom: 1px solid rgba(226, 232, 240, 0.92);
            color: #64748b;
            font-size: 0.88rem;
            align-items: center;
        }
        .media-drawer-actions {
            display: flex;
            gap: 10px;
            padding: 14px 22px 18px;
        }
        .media-drawer-comments {
            display: grid;
            gap: 12px;
        }
        .media-drawer-comment {
            display: flex;
            gap: 10px;
        }
        .media-drawer-comment-avatar {
            width: 34px;
            height: 34px;
            flex-shrink: 0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.74rem;
            font-weight: 800;
            color: #fff;
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
        }
        .media-drawer-comment-body {
            min-width: 0;
            padding: 10px 12px;
            border-radius: 14px;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.9);
        }
        .media-drawer-comment-author {
            color: #172033;
            font-weight: 700;
            font-size: 0.85rem;
        }
        .media-drawer-comment-text {
            margin-top: 4px;
            color: #475569;
            line-height: 1.55;
            white-space: pre-wrap;
        }
        .media-drawer-comment-time {
            margin-top: 6px;
            color: #94a3b8;
            font-size: 0.76rem;
        }
        .media-drawer-empty {
            color: #94a3b8;
            font-size: 0.9rem;
        }
        @media (max-width: 980px) {
            .media-drawer-panel {
                grid-template-columns: 1fr;
                grid-template-rows: minmax(280px, 44vh) 1fr;
            }
        }

        @media (max-width: 768px) {
            .page-shell {
                width: min(100%, 760px);
                display: grid;
                gap: 14px;
            }

            .composer-card,
            .post-card {
                border-radius: 0;
                box-shadow: none;
                border-left: 0;
                border-right: 0;
                margin-inline: -14px;
            }

            .composer-card {
                padding: 16px;
            }

            .composer-header,
            .post-header-main,
            .post-author-meta,
            .post-actions {
                gap: 10px;
            }

            .post-author-meta,
            .post-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .post-actions .btn,
            .action-buttons .btn {
                width: 100%;
                justify-content: center;
            }

            .post-media img,
            .post-media video {
                max-height: 260px;
            }

            .editor-modal,
            .media-drawer {
                align-items: flex-end;
                padding: 10px;
            }

            .editor-panel,
            .media-drawer-panel {
                width: 100%;
                max-width: 100%;
                max-height: min(84dvh, 760px);
                border-radius: 24px 24px 18px 18px;
            }

            .editor-panel::before,
            .media-drawer-panel::before {
                content: "";
                display: block;
                width: 52px;
                height: 5px;
                border-radius: 999px;
                background: #cbd5e1;
                margin: 14px auto 0;
            }

            .editor-header,
            .editor-body,
            .editor-footer,
            .media-drawer-side {
                padding-left: 16px;
                padding-right: 16px;
            }
        }

        [data-ff-page-root="announcements"] {
            --announcement-orange: #d74a24;
            --announcement-amber: #df8a10;
            --announcement-navy: #101b35;
            --announcement-muted: #64748b;
            --announcement-line: rgba(226, 232, 240, 0.92);
            --announcement-soft: #fff7ed;
        }

        [data-ff-page-root="announcements"] .main-content {
            min-height: 100dvh;
        }

        [data-ff-page-root="announcements"] .page-shell {
            width: min(calc(100% - 48px), 780px);
            max-width: 780px;
            padding: 18px 0 44px;
            display: grid;
            gap: 14px;
            margin-inline: auto;
        }

        [data-ff-page-root="announcements"] .top-bar {
            align-items: center;
            gap: 12px;
            margin-bottom: 0;
        }

        [data-ff-page-root="announcements"] .page-title h1 {
            font-size: clamp(1.45rem, 3vw, 2rem);
            line-height: 1.1;
            color: var(--announcement-navy);
        }

        [data-ff-page-root="announcements"] .page-title p {
            margin-top: 4px;
            color: var(--announcement-muted);
            font-size: 0.92rem;
        }

        [data-ff-page-root="announcements"] .composer-card,
        [data-ff-page-root="announcements"] .post-card,
        [data-ff-page-root="announcements"] .empty-state {
            border-radius: 18px;
            border: 1px solid var(--announcement-line);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.045);
        }

        [data-ff-page-root="announcements"] .composer-card {
            padding: 14px;
            display: grid;
            gap: 12px;
            background: #fff;
        }

        [data-ff-page-root="announcements"] .composer-header {
            align-items: center;
            margin-bottom: 0;
        }

        [data-ff-page-root="announcements"] .composer-meta {
            gap: 2px;
            min-width: 0;
        }

        [data-ff-page-root="announcements"] .composer-name {
            font-size: 0.95rem;
            line-height: 1.2;
        }

        [data-ff-page-root="announcements"] .composer-role {
            font-size: 0.78rem;
            line-height: 1.3;
        }

        [data-ff-page-root="announcements"] .composer-header-main {
            display: flex;
            align-items: center;
            gap: 11px;
            min-width: 0;
            flex: 1;
        }

        [data-ff-page-root="announcements"] .composer-clear {
            min-height: 36px;
            padding: 0 10px;
            border: 0;
            background: transparent;
            color: var(--announcement-orange);
            font-weight: 800;
            font-size: 0.82rem;
            cursor: pointer;
        }

        [data-ff-page-root="announcements"] .composer-clear[hidden] {
            display: none !important;
        }

        [data-ff-page-root="announcements"] .composer-avatar {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            font-size: 0.86rem;
        }

        [data-ff-page-root="announcements"] .post-input {
            width: 100%;
            border-radius: 14px;
            border: 1px solid var(--announcement-line);
            background: #fff;
            color: var(--announcement-navy);
            font-size: 0.94rem;
        }

        [data-ff-page-root="announcements"] textarea.post-input {
            min-height: 88px;
            max-height: 180px;
            resize: none;
            overflow-y: auto;
            line-height: 1.5;
        }

        [data-ff-page-root="announcements"] .post-attachments {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        [data-ff-page-root="announcements"] .attachment-btn {
            min-height: 42px;
            padding: 9px 10px;
            border-radius: 13px;
            border: 1px solid var(--announcement-line);
            background: #f8fafc;
            color: #475569;
            font-size: 0.86rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: none;
        }

        [data-ff-page-root="announcements"] .attachment-btn.is-active,
        [data-ff-page-root="announcements"] .attachment-btn[aria-pressed="true"] {
            background: #fff3e8;
            border-color: rgba(215, 74, 36, 0.35);
            color: var(--announcement-orange);
        }

        [data-ff-page-root="announcements"] .composer-inline-error {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 11px;
            border-radius: 12px;
            background: #fff1f2;
            color: #be123c;
            border: 1px solid #fecdd3;
            font-size: 0.83rem;
            font-weight: 700;
        }

        [data-ff-page-root="announcements"] .composer-inline-error[hidden] {
            display: none !important;
        }

        [data-ff-page-root="announcements"] .image-preview {
            display: none;
            gap: 8px;
        }

        [data-ff-page-root="announcements"] .image-preview.has-items {
            display: grid;
        }

        [data-ff-page-root="announcements"] .image-preview-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        [data-ff-page-root="announcements"] .image-preview-grid.count-1 {
            grid-template-columns: 1fr;
        }

        [data-ff-page-root="announcements"] .preview-summary {
            color: var(--announcement-muted);
            font-size: 0.78rem;
            font-weight: 700;
        }

        [data-ff-page-root="announcements"] .preview-item {
            position: relative;
            overflow: hidden;
            border-radius: 14px;
            aspect-ratio: 1 / 0.72;
            background: #f1f5f9;
            border: 1px solid var(--announcement-line);
        }

        [data-ff-page-root="announcements"] .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        [data-ff-page-root="announcements"] .remove-preview {
            position: absolute;
            top: 7px;
            right: 7px;
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.78);
            color: #fff;
            display: grid;
            place-items: center;
            cursor: pointer;
        }

        [data-ff-page-root="announcements"] .video-preview {
            padding: 10px;
            border-radius: 14px;
            box-shadow: none;
        }

        [data-ff-page-root="announcements"] .video-preview video {
            max-height: 260px;
            border-radius: 12px;
        }

        [data-ff-page-root="announcements"] .video-preview-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding-top: 9px;
            color: #475569;
            font-size: 0.82rem;
            font-weight: 700;
        }

        [data-ff-page-root="announcements"] .video-preview-remove {
            min-height: 36px;
            padding: 0 10px;
            border-radius: 999px;
            border: 1px solid #fed7aa;
            background: #fff7ed;
            color: var(--announcement-orange);
            font-weight: 800;
            cursor: pointer;
        }

        [data-ff-page-root="announcements"] .poll-builder {
            padding: 11px;
            gap: 9px;
            border-radius: 14px;
            background: #fffaf5;
        }

        [data-ff-page-root="announcements"] .poll-option-row {
            gap: 8px;
        }

        [data-ff-page-root="announcements"] .poll-option-row .btn {
            min-width: 44px;
            width: 44px;
            padding-inline: 0;
        }

        [data-ff-page-root="announcements"] .post-actions {
            justify-content: flex-end;
            padding-top: 0;
            margin-top: 0;
            border-top: 0;
        }

        [data-ff-page-root="announcements"] .post-actions .btn-primary {
            min-height: 44px;
            padding: 0 18px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--announcement-orange), var(--announcement-amber));
        }

        [data-ff-page-root="announcements"] .feed {
            display: grid;
            gap: 12px;
        }

        [data-ff-page-root="announcements"] .post-card {
            background: #fff;
            overflow: hidden;
        }

        [data-ff-page-root="announcements"] .post-author-avatar,
        [data-ff-page-root="announcements"] .comment-avatar {
            width: 40px;
            height: 40px;
            flex: 0 0 40px;
            font-size: 0.82rem;
        }

        [data-ff-page-root="announcements"] .post-author-name {
            color: var(--announcement-navy);
            font-size: 0.95rem;
            font-weight: 800;
        }

        [data-ff-page-root="announcements"] .post-text {
            color: #1f2937;
            line-height: 1.55;
            font-size: 0.95rem;
        }

        [data-ff-page-root="announcements"] .post-media {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 4px;
            padding: 0;
            border-radius: 14px;
            overflow: hidden;
            background: #e2e8f0;
        }

        [data-ff-page-root="announcements"] .post-media.post-media--count-1 {
            grid-template-columns: 1fr;
        }

        [data-ff-page-root="announcements"] .post-media img,
        [data-ff-page-root="announcements"] .post-media video {
            height: 240px;
            max-height: none;
            border-radius: 0;
            object-fit: cover;
            background: #0f172a;
            cursor: pointer;
        }

        [data-ff-page-root="announcements"] .post-media.post-media--count-1 img,
        [data-ff-page-root="announcements"] .post-media.post-media--count-1 video {
            height: 360px;
        }

        [data-ff-page-root="announcements"] .post-media-more {
            position: relative;
            display: grid;
            min-height: 160px;
            overflow: hidden;
        }

        [data-ff-page-root="announcements"] .post-media-more::after {
            content: attr(data-more-label);
            position: absolute;
            inset: 0;
            display: grid;
            place-items: center;
            background: rgba(15, 23, 42, 0.54);
            color: #fff;
            font-size: 1.45rem;
            font-weight: 900;
        }

        [data-ff-page-root="announcements"] .post-poll {
            padding: 12px;
            border-radius: 14px;
        }

        [data-ff-page-root="announcements"] .post-poll-option {
            min-height: 40px;
            padding: 9px 11px;
            font-size: 0.9rem;
        }

        [data-ff-page-root="announcements"] .empty-state {
            min-height: 126px;
            padding: 18px;
            display: grid;
            place-items: center;
            text-align: center;
            background: #fff;
        }

        [data-ff-page-root="announcements"] .empty-state-icon {
            width: 38px;
            height: 38px;
            font-size: 1rem;
        }

        [data-ff-page-root="announcements"] .empty-state h3 {
            margin: 8px 0 4px;
            font-size: 1rem;
        }

        [data-ff-page-root="announcements"] .empty-state p {
            margin: 0;
            font-size: 0.86rem;
        }

        [data-ff-page-root="announcements"] .empty-state button {
            display: none;
        }

        .announcement-toast-stack {
            position: fixed;
            top: 96px;
            right: 20px;
            z-index: 9999;
            display: grid;
            gap: 10px;
            width: min(360px, calc(100vw - 32px));
            pointer-events: none;
        }

        .announcement-toast {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 10px;
            min-height: 48px;
            padding: 11px 12px;
            border-radius: 14px;
            background: #fff;
            border: 1px solid var(--announcement-line);
            color: var(--announcement-navy);
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.14);
            font-size: 0.88rem;
            font-weight: 800;
            pointer-events: auto;
        }

        .announcement-toast--success .announcement-toast__icon { color: #15803d; }
        .announcement-toast--warning .announcement-toast__icon { color: #d97706; }
        .announcement-toast--error .announcement-toast__icon { color: #dc2626; }
        .announcement-toast--info .announcement-toast__icon { color: #2563eb; }

        .announcement-toast__close {
            width: 32px;
            height: 32px;
            border: 0;
            border-radius: 999px;
            background: #f8fafc;
            color: #64748b;
            cursor: pointer;
        }

        .announcement-confirm {
            position: fixed;
            inset: 0;
            z-index: 10020;
            display: grid;
            place-items: center;
            padding: 20px;
            background: rgba(15, 23, 42, 0.44);
            backdrop-filter: blur(3px);
        }

        .announcement-confirm[hidden] {
            display: none !important;
        }

        .announcement-confirm__panel {
            width: min(360px, 100%);
            border-radius: 22px;
            border: 1px solid rgba(254, 215, 170, 0.9);
            background:
                radial-gradient(circle at top right, rgba(251, 191, 36, 0.14), transparent 42%),
                #fffaf5;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
            padding: 18px;
        }

        .announcement-confirm__eyebrow {
            color: var(--announcement-orange);
            font-size: 0.72rem;
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .announcement-confirm__title {
            margin: 5px 0 6px;
            color: var(--announcement-navy);
            font-size: 1.12rem;
            font-weight: 900;
            line-height: 1.2;
        }

        .announcement-confirm__message {
            margin: 0;
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.45;
        }

        .announcement-confirm__actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 16px;
        }

        .announcement-confirm__button {
            min-height: 44px;
            border-radius: 14px;
            border: 1px solid rgba(226, 232, 240, 0.95);
            background: #fff;
            color: #475569;
            font-weight: 900;
            cursor: pointer;
        }

        .announcement-confirm__button--primary {
            border-color: transparent;
            background: linear-gradient(135deg, var(--announcement-orange), var(--announcement-amber));
            color: #fff;
            box-shadow: 0 12px 22px rgba(215, 74, 36, 0.2);
        }

        .announcement-confirm__button:focus-visible {
            outline: 3px solid rgba(215, 74, 36, 0.25);
            outline-offset: 2px;
        }

        @media (max-width: 768px) {
            [data-ff-page-root="announcements"] .main-content {
                padding: 0 10px calc(var(--ff-mobile-bottom-h, 92px) + env(safe-area-inset-bottom) + 16px) !important;
                min-height: 100dvh;
                align-content: start;
            }

            [data-ff-page-root="announcements"] .page-shell {
                width: 100%;
                max-width: none;
                padding: 6px 0 calc(var(--ff-mobile-bottom-h, 92px) + env(safe-area-inset-bottom) + 18px);
                gap: 10px;
            }

            [data-ff-page-root="announcements"] .top-bar {
                display: none;
            }

            [data-ff-page-root="announcements"] .page-title h1 {
                font-size: 1.2rem;
            }

            [data-ff-page-root="announcements"] .page-title p {
                display: none;
            }

            [data-ff-page-root="announcements"] .action-buttons .btn {
                width: 44px;
                min-width: 44px;
                height: 44px;
                padding: 0;
                border-radius: 14px;
                font-size: 0;
            }

            [data-ff-page-root="announcements"] .action-buttons .btn i {
                font-size: 0.95rem;
            }

            [data-ff-page-root="announcements"] .composer-card,
            [data-ff-page-root="announcements"] .post-card,
            [data-ff-page-root="announcements"] .empty-state {
                margin-inline: 0;
                border-radius: 16px;
                border-left: 1px solid var(--announcement-line);
                border-right: 1px solid var(--announcement-line);
            }

            [data-ff-page-root="announcements"] .composer-card {
                padding: 12px;
            }

            [data-ff-page-root="announcements"] textarea.post-input {
                min-height: 76px;
                max-height: 132px;
                font-size: 16px;
            }

            [data-ff-page-root="announcements"] .attachment-btn {
                min-height: 44px;
                padding: 8px 6px;
                gap: 5px;
                font-size: 0.78rem;
            }

            [data-ff-page-root="announcements"] .post-actions {
                display: block;
            }

            [data-ff-page-root="announcements"] .post-actions .btn-primary {
                width: 100%;
                justify-content: center;
                min-height: 46px;
            }

            [data-ff-page-root="announcements"] .post-author-meta {
                flex-direction: row;
                align-items: flex-start;
            }

            [data-ff-page-root="announcements"] .post-media img,
            [data-ff-page-root="announcements"] .post-media video {
                height: 152px;
            }

            [data-ff-page-root="announcements"] .post-media.post-media--count-1 img,
            [data-ff-page-root="announcements"] .post-media.post-media--count-1 video {
                height: 220px;
            }

            [data-ff-page-root="announcements"] .post-engagement {
                gap: 6px;
            }

            [data-ff-page-root="announcements"] .engagement-btn {
                min-height: 40px;
                font-size: 0.84rem;
            }

            .announcement-toast-stack {
                top: auto;
                right: 12px;
                left: 12px;
                bottom: calc(var(--ff-mobile-bottom-h, 92px) + env(safe-area-inset-bottom) + 12px);
                width: auto;
            }

            .announcement-toast {
                min-height: 44px;
                border-radius: 14px;
                box-shadow: 0 12px 24px rgba(15, 23, 42, 0.16);
            }

            .announcement-confirm {
                align-items: end;
                padding: 12px 12px calc(var(--ff-mobile-bottom-h, 92px) + env(safe-area-inset-bottom) + 12px);
            }

            .announcement-confirm__panel {
                border-radius: 22px;
                padding: 16px;
            }

            .announcement-confirm__title {
                font-size: 1rem;
            }

            .announcement-confirm__message {
                font-size: 0.84rem;
            }
        }

        @media (max-width: 320px) {
            [data-ff-page-root="announcements"] .composer-avatar,
            [data-ff-page-root="announcements"] .post-author-avatar {
                width: 38px;
                height: 38px;
                flex-basis: 38px;
            }

            [data-ff-page-root="announcements"] .attachment-btn {
                font-size: 0.72rem;
            }
        }
    </style>
    @include('partials.pwa-head')
</head>
<body class="firefighter-portal-shell">
    @include('firefighters.partials.shell-sidebar', ['active' => 'announcements'])

    <div data-ff-page-root="announcements">
    <main class="main-content">
        @include('firefighters.partials.shell-topbar', [
            'title' => 'Announcements',
            'subtitle' => 'Station communication, updates, polls, and media posts'
        ])

        <div class="page-shell">
            <div class="top-bar">
                <div class="page-title">
                    <h1>Announcements</h1>
                    <p>Post updates, alerts, polls, and internal media for your station.</p>
                </div>
                <div class="action-buttons">
                    <button class="btn btn-outline" onclick="window.announcementSystem?.loadPosts(true)">
                        <i class="fa-solid fa-rotate-right"></i>
                        Refresh
                    </button>
                </div>
            </div>

            <div class="create-post-card composer-card">
                <div class="composer-header">
                    <div class="composer-header-main">
                        <div class="post-avatar composer-avatar">
                            @if ($authProfileImageUrl)
                                <img src="{{ $authProfileImageUrl }}" alt="{{ $authName }}">
                            @else
                                {{ $authInitials }}
                            @endif
                        </div>
                        <div class="composer-meta">
                            <div class="composer-name">{{ $authName }}</div>
                            <div class="composer-role">Firefighter · Station feed</div>
                        </div>
                    </div>
                    <button class="composer-clear" type="button" id="composerClearButton" onclick="window.announcementSystem?.clearPost()" hidden>
                        Clear
                    </button>
                </div>

                <div class="composer-controls">
                    <textarea
                        class="post-input"
                        rows="3"
                        id="postContent"
                        maxlength="5000"
                        placeholder="Share an update with your station..."
                    ></textarea>

                    <div class="post-attachments">
                        <label class="attachment-btn image" for="imageUpload" data-attachment-action="image" aria-label="Add photos">
                            <i class="fa-regular fa-image"></i>
                            Photo
                            <input type="file" id="imageUpload" accept="image/*" multiple hidden>
                        </label>
                        <label class="attachment-btn video" for="videoUpload" data-attachment-action="video" aria-label="Add video">
                            <i class="fa-solid fa-video"></i>
                            Video
                            <input type="file" id="videoUpload" accept="video/*" hidden>
                        </label>
                        <button class="attachment-btn poll" type="button" id="togglePollBuilder" data-attachment-action="poll" aria-pressed="false" aria-controls="pollBuilder">
                            <i class="fa-solid fa-square-poll-vertical"></i>
                            Poll
                        </button>
                    </div>

                    <div class="composer-inline-error" id="composerInlineError" hidden>
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span></span>
                    </div>

                    <div class="image-preview" id="imagePreview"></div>

                    <div class="video-preview" id="videoPreview"></div>

                    <div class="poll-builder" id="pollBuilder">
                        <input class="post-input" id="pollQuestion" maxlength="255" placeholder="Poll question">
                        <div class="poll-option-row">
                            <input class="post-input" data-poll-option maxlength="120" placeholder="Option 1">
                            <button class="btn btn-outline" type="button" id="addPollOption"><i class="fa-solid fa-plus"></i></button>
                        </div>
                        <div id="pollOptionsContainer">
                            <div class="poll-option-row">
                                <input class="post-input" data-poll-option maxlength="120" placeholder="Option 2">
                                <button class="btn btn-outline" type="button" disabled><i class="fa-solid fa-grip-lines"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="post-actions">
                    <button class="btn btn-primary" onclick="window.announcementSystem?.createPost()" id="submitAnnouncementButton">
                        <i class="fa-regular fa-paper-plane"></i>
                        Post Announcement
                    </button>
                </div>
            </div>

            <div class="feed" id="feed"></div>
            <div id="loadingSpinner" class="loading-spinner" style="display: none;"></div>
        </div>
    </main>

    <div class="editor-modal" id="postEditorModal">
        <div class="editor-panel">
            <div class="editor-header">
                <div>
                    <h3 id="postEditorTitle">Edit Post</h3>
                </div>
                <button type="button" class="editor-close" id="closePostEditor"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="editor-body" id="postEditorBody"></div>
            <div class="editor-footer" id="postEditorFooter"></div>
        </div>
    </div>

    <div class="media-drawer" id="postMediaDrawer">
        <div class="media-drawer-panel">
            <div class="media-drawer-visual" id="postMediaDrawerVisual"></div>
            <div class="media-drawer-side">
                <div class="media-drawer-head" id="postMediaDrawerHead"></div>
                <div class="media-drawer-body" id="postMediaDrawerBody"></div>
                <div class="media-drawer-stats" id="postMediaDrawerStats"></div>
                <div class="media-drawer-actions" id="postMediaDrawerActions"></div>
            </div>
        </div>
    </div>

    <div class="announcement-confirm" id="announcementConfirm" hidden aria-hidden="true">
        <div class="announcement-confirm__panel" role="dialog" aria-modal="true" aria-labelledby="announcementConfirmTitle" aria-describedby="announcementConfirmMessage">
            <div class="announcement-confirm__eyebrow" id="announcementConfirmEyebrow">Confirm action</div>
            <h2 class="announcement-confirm__title" id="announcementConfirmTitle">Discard announcement?</h2>
            <p class="announcement-confirm__message" id="announcementConfirmMessage">Your text and selected media will be removed.</p>
            <div class="announcement-confirm__actions">
                <button class="announcement-confirm__button" type="button" data-announcement-confirm-cancel>Keep editing</button>
                <button class="announcement-confirm__button announcement-confirm__button--primary" type="button" data-announcement-confirm-ok>Discard</button>
            </div>
        </div>
    </div>

    @vite(['resources/js/firefighter/announcements.js'])
    </div>
    @include('partials.pwa-body')
</body>
</html>
