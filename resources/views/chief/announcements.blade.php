<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <title>PyroSense · Announcements</title>

    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css', 'resources/css/admin/announcements.css'])

    @php
        $authName = trim((auth()->user()->first_name ?? '') . ' ' . (auth()->user()->last_name ?? '')) ?: 'Chief';
        $authInitials = strtoupper(substr(auth()->user()->first_name ?? 'C', 0, 1) . substr(auth()->user()->last_name ?? 'C', 0, 1));
    @endphp

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, #fffdf8 0%, #f8fafc 100%);
        }

        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            padding: 20px 24px 32px;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
            }
        }

        .announcements-shell {
            max-width: 1380px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .page-shell {
            max-width: 980px;
            margin: 0 auto;
        }

        .lector-top-bar {
            background: rgba(255, 255, 255, 0.84);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(245, 158, 11, 0.08);
            height: 72px;
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 16px;
            z-index: 40;
            border-radius: 24px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .user-dropdown { position: relative; }

        .user-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            border-radius: 999px;
            background: #fffbeb;
            border: 1px solid #fef3c7;
            cursor: pointer;
            transition: all 0.2s ease;
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
            border-radius: 18px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
            border: 1px solid rgba(245, 158, 11, 0.1);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.25s ease;
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
            padding: 12px 18px;
            color: #334155;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s ease;
            width: 100%;
        }

        .user-dropdown-item:hover {
            background: #fff7ed;
            color: #c2410c;
        }

        .announcements-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 28px 30px;
            border-radius: 28px;
            background: linear-gradient(135deg, #fff7ed 0%, #fffbeb 48%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.05);
        }

        .announcements-hero-copy h1 {
            margin: 0;
            font-size: 2.2rem;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.05;
        }

        .announcements-hero-copy p {
            margin: 10px 0 0;
            font-size: 1rem;
            color: #64748b;
            font-weight: 500;
        }

        .announcements-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 999px;
            background: #fff7ed;
            border: 1px solid rgba(245, 158, 11, 0.14);
            color: #c2410c;
            font-size: 0.86rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .create-post-card {
            border-radius: 28px;
            background: linear-gradient(135deg, #fffaf4 0%, #ffffff 100%);
            border: 1px solid rgba(245, 158, 11, 0.12);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.05);
        }

        .post-input {
            background: #fff;
            border: 1px solid rgba(245, 158, 11, 0.12);
        }

        .post-input:focus {
            border-color: rgba(198, 61, 38, 0.36);
            box-shadow: 0 0 0 4px rgba(198, 61, 38, 0.08);
        }

        .attachment-btn,
        .btn.btn-outline {
            background: #fff7ed;
            border-color: rgba(245, 158, 11, 0.14);
            color: #9a3412;
        }

        .attachment-btn:hover,
        .btn.btn-outline:hover {
            background: #ffedd5;
        }

        .btn.btn-primary {
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            border: 0;
            color: #fff;
        }

        .post-avatar {
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
            color: #fff;
            box-shadow: 0 14px 28px rgba(198, 61, 38, 0.18);
        }

        .feed {
            border-radius: 28px;
        }

        .composer-card,
        .post-card {
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
        }

        .composer-header {
            display: flex;
            gap: 14px;
            margin-bottom: 18px;
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

        .post-author-avatar,
        .comment-avatar {
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
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
</head>
<body>
    @include('chief.partials.sidebar', ['active' => 'announcements'])
    <div id="sidebarBackdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden transition-opacity duration-300"></div>

    <main class="main-content">
        <div class="announcements-shell">
            <nav class="lector-top-bar">
                <div class="flex items-center gap-4">
                    <button id="openSidebar" class="lg:hidden text-slate-600">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>
                    <div class="hidden lg:block">
                        <h1 class="text-xl font-bold text-slate-800">Announcements</h1>
                        <p class="text-xs text-slate-500 font-medium">Station updates and command communications • {{ now()->format('l, F j') }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <button class="btn btn-outline" onclick="window.announcementSystem?.loadPosts(true)">
                        <i class="fa-regular fa-rotate-right"></i>
                        Refresh
                    </button>

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
                                    <button type="submit" class="user-dropdown-item text-red-600 text-left">
                                        <i class="fa-solid fa-sign-out-alt"></i>
                                        <span>Logout</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <section class="announcements-hero">
                <div class="announcements-hero-copy">
                    <h1>Command Announcements</h1>
                    <p>Publish operational updates, reminders, and internal notices for your station personnel.</p>
                </div>
                <div class="announcements-pill">
                    <i class="fa-solid fa-bullhorn"></i>
                    Chief Broadcast Channel
                </div>
            </section>

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
                            <div class="composer-role">Chief Announcement Composer</div>
                        </div>
                    </div>

                    <div class="composer-controls">
                        <textarea
                            class="post-input"
                            rows="4"
                            id="postContent"
                            maxlength="5000"
                            placeholder="Share a station update, command directive, operational note, or media post..."
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

    @vite(['resources/js/chief/announcements.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const openSidebarBtn = document.getElementById('openSidebar');
            const sidebar = document.getElementById('sidebar');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');
            const userDropdownBtn = document.getElementById('userDropdownBtn');
            const userDropdownMenu = document.getElementById('userDropdownMenu');

            if (openSidebarBtn && sidebar && sidebarBackdrop) {
                openSidebarBtn.addEventListener('click', function () {
                    sidebar.classList.add('active');
                    sidebarBackdrop.classList.remove('hidden');
                });

                sidebarBackdrop.addEventListener('click', function () {
                    sidebar.classList.remove('active');
                    sidebarBackdrop.classList.add('hidden');
                });
            }

            if (userDropdownBtn && userDropdownMenu) {
                userDropdownBtn.addEventListener('click', function (event) {
                    event.stopPropagation();
                    userDropdownMenu.classList.toggle('show');
                });

                document.addEventListener('click', function (event) {
                    if (!userDropdownBtn.contains(event.target) && !userDropdownMenu.contains(event.target)) {
                        userDropdownMenu.classList.remove('show');
                    }
                });

                userDropdownMenu.addEventListener('click', function (event) {
                    event.stopPropagation();
                });
            }
        });
    </script>
    @include('chief.partials.logs-modal')
    @include('chief.partials.logs-script')
    @include('chief.partials.logout-confirmation')
</body>
</html>
