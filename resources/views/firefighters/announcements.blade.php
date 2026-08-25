<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PyroSense - Announcements</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/css/firefighters/portal.css'])

    @php
        $authName = trim((auth()->user()->first_name ?? '') . ' ' . (auth()->user()->last_name ?? '')) ?: 'Firefighter';
        $authInitials = strtoupper(substr(auth()->user()->first_name ?? 'F', 0, 1) . substr(auth()->user()->last_name ?? 'F', 0, 1));
        $authProfileImageUrl = \App\Support\MediaPath::url(auth()->user()?->image);
    @endphp
    @include('partials.pwa-head')
</head>
<body class="firefighter-portal-shell staff-announcements" data-staff-announcements="firefighter">
    @include('firefighters.partials.shell-sidebar', ['active' => 'announcements'])

    <div data-ff-page-root="announcements">
    <main class="main-content">
        @include('firefighters.partials.shell-topbar', [
            'title' => 'Announcements',
            'subtitle' => 'Station communication, updates, polls, and media posts'
        ])

        <div class="page-shell staff-announcements__workspace">
            <div class="top-bar staff-announcements__heading">
                <div class="page-title">
                    <h1>Announcements</h1>
                    <p>Post updates, alerts, polls, and internal media for your station.</p>
                </div>
                <div class="action-buttons">
                    <button class="btn btn-outline staff-announcements__refresh" onclick="window.announcementSystem?.loadPosts(true)" aria-label="Refresh announcements">
                        <i class="fa-solid fa-rotate-right"></i>
                        <span>Refresh</span>
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
                            <div class="composer-role">Firefighter &middot; Station feed</div>
                        </div>
                    </div>
                    <button class="composer-clear" type="button" id="composerClearButton" onclick="window.announcementSystem?.clearPost()">
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
