<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.pyrosense-favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <title>PyroSense - Announcements</title>

    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css'])

    @php
        $authName = trim((auth()->user()->first_name ?? '') . ' ' . (auth()->user()->last_name ?? '')) ?: 'Chief';
        $authInitials = strtoupper(substr(auth()->user()->first_name ?? 'C', 0, 1) . substr(auth()->user()->last_name ?? 'C', 0, 1));
        $authProfileImageUrl = \App\Support\MediaPath::url(auth()->user()?->image);
    @endphp
</head>
<body class="chief-legacy-layout staff-announcements" data-staff-announcements="chief">
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
                        <p class="text-xs text-slate-500 font-medium">Station updates and command communications &bull; {{ now()->format('l, F j') }}</p>
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

            <div class="page-shell staff-announcements__workspace">
                <div class="top-bar staff-announcements__heading">
                    <div class="page-title">
                        <h1>Announcements</h1>
                        <p>Station communication, updates, polls, and media posts.</p>
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
                                <div class="composer-role">Station Chief &middot; Station feed</div>
                            </div>
                        </div>
                        <button class="composer-clear" type="button" onclick="window.announcementSystem?.clearPost()">Clear</button>
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
                            <label class="attachment-btn image" for="imageUpload" data-attachment-action="image" aria-label="Add photos">
                                <i class="fa-solid fa-image"></i>
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
