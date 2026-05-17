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
    @endphp

    <style>
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
    </style>
    @include('partials.pwa-head')
</head>
<body class="firefighter-portal-shell">
    @include('firefighters.partials.shell-sidebar', ['active' => 'announcements'])

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
                    <div class="post-avatar composer-avatar">{{ $authInitials }}</div>
                    <div class="composer-meta">
                        <div class="composer-name">{{ $authName }}</div>
                        <div class="composer-role">Firefighter Announcement Composer</div>
                    </div>
                </div>

                <div class="composer-controls">
                    <textarea
                        class="post-input"
                        rows="4"
                        id="postContent"
                        maxlength="5000"
                        placeholder="Share a station update, announcement, operational note, or media post..."
                    ></textarea>

                    <div class="post-attachments">
                        <label class="attachment-btn image" for="imageUpload">
                            <i class="fa-regular fa-image"></i>
                            Photo
                            <input type="file" id="imageUpload" accept="image/*" multiple hidden>
                        </label>
                        <label class="attachment-btn video" for="videoUpload">
                            <i class="fa-regular fa-video"></i>
                            Video
                            <input type="file" id="videoUpload" accept="video/*" hidden>
                        </label>
                        <button class="attachment-btn poll" type="button" id="togglePollBuilder">
                            <i class="fa-regular fa-square-poll-vertical"></i>
                            Poll
                        </button>
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
                    <button class="btn btn-outline" onclick="window.announcementSystem?.clearPost()">
                        <i class="fa-regular fa-trash-can"></i>
                        Clear
                    </button>
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

    @vite(['resources/js/firefighter/announcements.js'])
    @include('partials.pwa-body')
</body>
</html>
