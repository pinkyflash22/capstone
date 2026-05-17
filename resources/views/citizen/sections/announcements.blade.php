<div id="section-announcements" class="content-section">
    <div class="citizen-announcements-shell">
        <div class="citizen-announcements-topbar">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">Announcements</h1>
                <p class="text-sm text-gray-500 mt-1">View station and community updates, then react and join the discussion.</p>
            </div>
            <button type="button" class="citizen-announcements-refresh" id="citizenAnnouncementsRefresh">
                <i class="fa-solid fa-rotate-right"></i>
                Refresh
            </button>
        </div>

        <div class="citizen-announcements-toolbar">
            <div class="citizen-announcements-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="citizenAnnouncementsSearch" placeholder="Search by poster name or content">
            </div>
            <select id="citizenAnnouncementsSort" class="citizen-announcements-sort">
                <option value="newest">Newest first</option>
                <option value="oldest">Oldest first</option>
                <option value="author">Author A-Z</option>
                <option value="important">Important first</option>
            </select>
        </div>

        <div id="citizenAnnouncementsFeed" class="citizen-announcements-feed">
            <div class="citizen-announcements-empty loading">
                <i class="fa-solid fa-spinner fa-spin"></i>
                <p>Loading announcements...</p>
            </div>
        </div>
    </div>
</div>

<div class="citizen-media-drawer" id="citizenAnnouncementMediaDrawer">
    <div class="citizen-media-drawer-panel">
        <div class="citizen-media-drawer-visual" id="citizenAnnouncementMediaDrawerVisual"></div>
        <div class="citizen-media-drawer-side">
            <div class="citizen-media-drawer-head" id="citizenAnnouncementMediaDrawerHead"></div>
            <div class="citizen-media-drawer-body" id="citizenAnnouncementMediaDrawerBody"></div>
            <div class="citizen-media-drawer-stats" id="citizenAnnouncementMediaDrawerStats"></div>
            <div class="citizen-media-drawer-actions" id="citizenAnnouncementMediaDrawerActions"></div>
        </div>
    </div>
</div>

<style>
    .citizen-announcements-shell {
        max-width: 980px;
        margin: 0 auto;
        display: grid;
        gap: 1.25rem;
    }
    .citizen-announcements-topbar {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 0.25rem;
    }
    .citizen-announcements-refresh {
        border: 1px solid rgba(226, 232, 240, 0.95);
        background: #fff;
        color: #334155;
        border-radius: 999px;
        padding: 0.8rem 1rem;
        font-size: 0.875rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
    }
    .citizen-announcements-toolbar {
        display: flex;
        gap: 0.9rem;
        flex-wrap: wrap;
        align-items: center;
        background: linear-gradient(135deg, #fff8f2 0%, #ffffff 100%);
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 1.5rem;
        padding: 1rem;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
    }
    .citizen-announcements-search {
        flex: 1 1 320px;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        background: #fff;
        border: 1px solid rgba(226, 232, 240, 0.95);
        border-radius: 999px;
        padding: 0.8rem 1rem;
        color: #94a3b8;
    }
    .citizen-announcements-search input,
    .citizen-announcements-sort {
        border: 0;
        outline: none;
        width: 100%;
        background: transparent;
        color: #0f172a;
        font-size: 0.95rem;
    }
    .citizen-announcements-sort {
        width: auto;
        min-width: 180px;
        background: #fff;
        border: 1px solid rgba(226, 232, 240, 0.95);
        border-radius: 999px;
        padding: 0.8rem 1rem;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
    }
    .citizen-announcements-feed {
        display: grid;
        gap: 1rem;
    }
    .citizen-announcement-card {
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
        background: #fff;
        border-radius: 1.5rem;
        overflow: hidden;
    }
    .citizen-announcement-head {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.15rem 1.15rem 0;
    }
    .citizen-announcement-author {
        display: flex;
        gap: 0.85rem;
        min-width: 0;
    }
    .citizen-announcement-avatar {
        width: 46px;
        height: 46px;
        flex-shrink: 0;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        color: #fff;
        background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
    }
    .citizen-announcement-name {
        color: #172033;
        font-weight: 800;
    }
    .citizen-announcement-role,
    .citizen-announcement-meta {
        color: #64748b;
        font-size: 0.83rem;
        line-height: 1.45;
    }
    .citizen-announcement-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        justify-content: flex-end;
    }
    .citizen-announcement-badge {
        padding: 0.3rem 0.65rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        background: #fff7ed;
        color: #c2410c;
    }
    .citizen-announcement-body {
        padding: 0.95rem 1.15rem 1.15rem;
        display: grid;
        gap: 0.9rem;
    }
    .citizen-announcement-text {
        color: #334155;
        line-height: 1.7;
        white-space: pre-wrap;
    }
    .citizen-announcement-media {
        display: grid;
        gap: 0.65rem;
        background: #f8fafc;
        padding: 0.75rem;
        border-radius: 1rem;
    }
    .citizen-announcement-media img,
    .citizen-announcement-media video {
        width: 100%;
        max-height: 420px;
        object-fit: contain;
        border-radius: 0.9rem;
        background: #0f172a;
        cursor: pointer;
    }
    .citizen-announcement-media.media-error {
        min-height: 120px;
        place-items: center;
        color: #94a3b8;
        font-size: 0.9rem;
    }
    .citizen-announcement-media.media-error::before {
        content: 'Media unavailable';
    }
    .citizen-announcement-poll {
        padding: 0.9rem;
        border-radius: 1rem;
        background: #fffaf6;
        border: 1px solid rgba(226, 232, 240, 0.9);
    }
    .citizen-announcement-poll-title {
        color: #172033;
        font-weight: 700;
        margin-bottom: 0.65rem;
    }
    .citizen-announcement-poll-options {
        display: grid;
        gap: 0.55rem;
    }
    .citizen-announcement-poll-option {
        padding: 0.75rem 0.9rem;
        border-radius: 0.85rem;
        border: 1px solid rgba(226, 232, 240, 0.9);
        background: #fff;
        color: #475569;
        display: flex;
        justify-content: space-between;
        gap: 1rem;
    }
    .citizen-announcement-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        color: #64748b;
        font-size: 0.86rem;
    }
    .citizen-announcement-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    .citizen-announcement-action {
        border: 0;
        background: #fff7ed;
        color: #9a3412;
        border-radius: 999px;
        padding: 0.7rem 1rem;
        font-size: 0.86rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
    }
    .citizen-announcement-action.liked {
        background: #fee2e2;
        color: #b91c1c;
    }
    .citizen-announcement-comments {
        display: none;
        gap: 0.9rem;
        border-top: 1px solid rgba(226, 232, 240, 0.9);
        padding-top: 0.95rem;
    }
    .citizen-announcement-comments.active {
        display: grid;
    }
    .citizen-announcement-comment-form {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 0.75rem;
        align-items: center;
    }
    .citizen-announcement-comment-input {
        border: 1px solid rgba(226, 232, 240, 0.95);
        border-radius: 999px;
        padding: 0.8rem 1rem;
        outline: none;
        color: #0f172a;
        background: #fff;
    }
    .citizen-announcement-comment-submit {
        border: 0;
        border-radius: 999px;
        padding: 0.8rem 1rem;
        background: #ea580c;
        color: #fff;
        font-weight: 700;
        cursor: pointer;
    }
    .citizen-announcement-comment-list {
        display: grid;
        gap: 0.75rem;
    }
    .citizen-announcement-comment {
        display: flex;
        gap: 0.75rem;
    }
    .citizen-announcement-comment-avatar {
        width: 34px;
        height: 34px;
        flex-shrink: 0;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 800;
        font-size: 0.75rem;
        background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
    }
    .citizen-announcement-comment-body {
        flex: 1;
        min-width: 0;
        background: #fff;
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 1rem;
        padding: 0.8rem 0.9rem;
    }
    .citizen-announcement-comment-author {
        color: #172033;
        font-weight: 700;
        font-size: 0.84rem;
    }
    .citizen-announcement-comment-text {
        margin-top: 0.3rem;
        color: #475569;
        line-height: 1.55;
        white-space: pre-wrap;
    }
    .citizen-announcement-comment-time {
        margin-top: 0.35rem;
        color: #94a3b8;
        font-size: 0.76rem;
    }
    .citizen-announcement-comment-empty {
        color: #94a3b8;
        font-size: 0.86rem;
    }
    .citizen-announcements-empty {
        text-align: center;
        padding: 3rem 1rem;
        color: #94a3b8;
        background: #fff;
        border: 1px dashed rgba(203, 213, 225, 0.95);
        border-radius: 1.5rem;
    }
    .citizen-announcements-empty i {
        font-size: 1.75rem;
        margin-bottom: 0.75rem;
    }
    .citizen-media-drawer {
        position: fixed;
        inset: 0;
        display: none;
        justify-content: flex-end;
        background: rgba(15, 23, 42, 0.45);
        z-index: 3400;
    }
    .citizen-media-drawer.active {
        display: flex;
    }
    .citizen-media-drawer-panel {
        width: min(1120px, 100%);
        height: 100vh;
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(360px, 420px);
        background: #fff;
        box-shadow: -18px 0 40px rgba(15, 23, 42, 0.16);
    }
    .citizen-media-drawer-visual {
        background: #0f172a;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 28px;
    }
    .citizen-media-drawer-visual img,
    .citizen-media-drawer-visual video {
        width: 100%;
        max-height: calc(100vh - 56px);
        object-fit: contain;
        border-radius: 18px;
        background: #020617;
    }
    .citizen-media-drawer-side {
        display: grid;
        grid-template-rows: auto 1fr auto auto;
        min-height: 0;
        background: linear-gradient(180deg, #fffaf5 0%, #ffffff 100%);
    }
    .citizen-media-drawer-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        padding: 20px 22px;
        border-bottom: 1px solid rgba(226, 232, 240, 0.92);
    }
    .citizen-media-drawer-author {
        display: flex;
        gap: 12px;
        min-width: 0;
    }
    .citizen-media-drawer-avatar {
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
    .citizen-media-drawer-name {
        color: #172033;
        font-weight: 800;
    }
    .citizen-media-drawer-meta {
        margin-top: 4px;
        color: #64748b;
        font-size: 0.83rem;
        line-height: 1.5;
    }
    .citizen-media-drawer-close {
        width: 38px;
        height: 38px;
        border: 0;
        border-radius: 999px;
        background: #f8fafc;
        color: #475569;
        cursor: pointer;
    }
    .citizen-media-drawer-body {
        min-height: 0;
        overflow-y: auto;
        padding: 18px 22px 20px;
        display: grid;
        gap: 16px;
    }
    .citizen-media-drawer-content {
        color: #1f2937;
        line-height: 1.6;
        white-space: pre-wrap;
    }
    .citizen-media-drawer-stats {
        display: flex;
        gap: 18px;
        padding: 0 22px 16px;
        border-top: 1px solid rgba(226, 232, 240, 0.92);
        border-bottom: 1px solid rgba(226, 232, 240, 0.92);
        color: #64748b;
        font-size: 0.88rem;
        align-items: center;
    }
    .citizen-media-drawer-actions {
        display: flex;
        gap: 10px;
        padding: 14px 22px 18px;
    }
    .citizen-media-drawer-comments {
        display: grid;
        gap: 12px;
    }
    .citizen-media-drawer-comment {
        display: flex;
        gap: 10px;
    }
    .citizen-media-drawer-comment-avatar {
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
    .citizen-media-drawer-comment-body {
        min-width: 0;
        padding: 10px 12px;
        border-radius: 14px;
        background: #fff;
        border: 1px solid rgba(226, 232, 240, 0.9);
    }
    .citizen-media-drawer-comment-author {
        color: #172033;
        font-weight: 700;
        font-size: 0.85rem;
    }
    .citizen-media-drawer-comment-text {
        margin-top: 4px;
        color: #475569;
        line-height: 1.55;
        white-space: pre-wrap;
    }
    .citizen-media-drawer-comment-time {
        margin-top: 6px;
        color: #94a3b8;
        font-size: 0.76rem;
    }
    .citizen-media-drawer-empty {
        color: #94a3b8;
        font-size: 0.9rem;
    }
    @media (max-width: 640px) {
        .citizen-announcements-topbar {
            flex-direction: column;
        }
        .citizen-announcement-head {
            flex-direction: column;
        }
        .citizen-announcement-badges {
            justify-content: flex-start;
        }
    }
    @media (max-width: 980px) {
        .citizen-media-drawer-panel {
            grid-template-columns: 1fr;
            grid-template-rows: minmax(280px, 44vh) 1fr;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const feed = document.getElementById('citizenAnnouncementsFeed');
    const searchInput = document.getElementById('citizenAnnouncementsSearch');
    const sortSelect = document.getElementById('citizenAnnouncementsSort');
    const refreshButton = document.getElementById('citizenAnnouncementsRefresh');

    if (!feed || !searchInput || !sortSelect || !refreshButton) {
        return;
    }

    const announcementState = {
        posts: [],
        filteredPosts: [],
        loading: false,
        activeMediaPostId: null,
    };
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function getFullName(user = {}) {
        const fullName = [user.first_name, user.last_name].filter(Boolean).join(' ').trim();
        return fullName || user.full_name || user.name || 'PyroSense Staff';
    }

    function getInitials(user = {}) {
        const name = getFullName(user);
        return name
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map(part => part.charAt(0).toUpperCase())
            .join('') || 'PS';
    }

    function getRole(user = {}) {
        return user?.role?.role
            ? String(user.role.role).replace(/\b\w/g, (char) => char.toUpperCase())
            : 'Personnel';
    }

    function normalizeMediaUrl(url = '') {
        if (!url) return '';
        return url.startsWith('http') ? url : url;
    }

    function renderPoll(pollData) {
        if (!pollData?.question || !Array.isArray(pollData.options) || pollData.options.length === 0) {
            return '';
        }

        return `
            <div class="citizen-announcement-poll">
                <div class="citizen-announcement-poll-title">${escapeHtml(pollData.question)}</div>
                <div class="citizen-announcement-poll-options">
                    ${pollData.options.map((option) => `
                        <div class="citizen-announcement-poll-option">
                            <span>${escapeHtml(option.text || '')}</span>
                            <span>${Number(option.votes || 0)} vote(s)</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    function renderMedia(post) {
        const images = Array.isArray(post.image_urls) ? post.image_urls : [];
        const videoUrl = post.video_url || '';

        if (!images.length && !videoUrl) {
            return '';
        }

        return `
            <div class="citizen-announcement-media">
                ${images.map((url) => {
                    const normalized = normalizeMediaUrl(url);
                    return `<img src="${normalized}" alt="Announcement media" loading="lazy" onclick="window.citizenAnnouncementSystem?.openMediaDrawer(${post.id}, 'image', '${normalized}')" onerror="this.style.display='none'; this.closest('.citizen-announcement-media')?.classList.add('media-error');">`;
                }).join('')}
                ${videoUrl ? `<video controls preload="metadata" src="${normalizeMediaUrl(videoUrl)}" onclick="window.citizenAnnouncementSystem?.openMediaDrawer(${post.id}, 'video', '${normalizeMediaUrl(videoUrl)}')"></video>` : ''}
            </div>
        `;
    }

    function renderComments(post) {
        const comments = Array.isArray(post.comments) ? post.comments : [];
        if (!comments.length) {
            return '<div class="citizen-announcement-comment-empty">No comments yet.</div>';
        }

        return `
            <div class="citizen-announcement-comment-list">
                ${comments.map((comment) => `
                    <div class="citizen-announcement-comment">
                        <div class="citizen-announcement-comment-avatar">${escapeHtml(getInitials(comment.user))}</div>
                        <div class="citizen-announcement-comment-body">
                            <div class="citizen-announcement-comment-author">${escapeHtml(getFullName(comment.user))}</div>
                            <div class="citizen-announcement-comment-text">${escapeHtml(comment.content || '')}</div>
                            <div class="citizen-announcement-comment-time">${escapeHtml(comment.time_ago || 'just now')}</div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    function comparePosts(left, right, sortValue) {
        if (sortValue === 'oldest') {
            return new Date(left.created_at || 0) - new Date(right.created_at || 0);
        }
        if (sortValue === 'author') {
            return getFullName(left.user).localeCompare(getFullName(right.user));
        }
        if (sortValue === 'important') {
            const importantDiff = Number(Boolean(right.is_important)) - Number(Boolean(left.is_important));
            if (importantDiff !== 0) return importantDiff;
        }
        return new Date(right.created_at || 0) - new Date(left.created_at || 0);
    }

    function applyFilters() {
        const query = searchInput.value.trim().toLowerCase();
        const sortValue = sortSelect.value;

        announcementState.filteredPosts = [...announcementState.posts]
            .filter((post) => {
                if (!query) return true;
                const author = getFullName(post.user).toLowerCase();
                const content = String(post.content || '').toLowerCase();
                return author.includes(query) || content.includes(query);
            })
            .sort((left, right) => comparePosts(left, right, sortValue));

        renderFeed();
    }

    function renderFeed() {
        if (!announcementState.filteredPosts.length) {
            feed.innerHTML = `
                <div class="citizen-announcements-empty">
                    <i class="fa-regular fa-newspaper"></i>
                    <p>No announcements matched your current search.</p>
                </div>
            `;
            return;
        }

        feed.innerHTML = announcementState.filteredPosts.map((post) => {
            const authorName = getFullName(post.user);
            const authorRole = getRole(post.user);
            const timeAgo = post.time_ago || 'Recently';
            const badges = [
                post.post_type ? `<span class="citizen-announcement-badge">${escapeHtml(String(post.post_type).replace(/\b\w/g, (char) => char.toUpperCase()))}</span>` : '',
                post.is_important ? '<span class="citizen-announcement-badge" style="background:#fee2e2;color:#b91c1c;">Important</span>' : '',
            ].filter(Boolean).join('');

            return `
                <article class="citizen-announcement-card">
                    <div class="citizen-announcement-head">
                        <div class="citizen-announcement-author">
                            <div class="citizen-announcement-avatar">${escapeHtml(getInitials(post.user))}</div>
                            <div>
                                <div class="citizen-announcement-name">${escapeHtml(authorName)}</div>
                                <div class="citizen-announcement-role">${escapeHtml(authorRole)}</div>
                                <div class="citizen-announcement-meta">${escapeHtml(timeAgo)}</div>
                            </div>
                        </div>
                        <div class="citizen-announcement-badges">${badges}</div>
                    </div>
                    <div class="citizen-announcement-body">
                        ${post.content ? `<div class="citizen-announcement-text">${escapeHtml(post.content).replace(/\n/g, '<br>')}</div>` : ''}
                        ${renderMedia(post)}
                        ${renderPoll(post.poll_data)}
                        <div class="citizen-announcement-stats">
                            <span><i class="fa-regular fa-thumbs-up"></i> ${Number(post.likes_count || 0)}</span>
                            <span><i class="fa-regular fa-comment"></i> ${Number(post.comments_count || 0)} comments</span>
                            <span><i class="fa-regular fa-share-from-square"></i> ${Number(post.shares_count || 0)} shares</span>
                        </div>
                        <div class="citizen-announcement-actions">
                            <button type="button" class="citizen-announcement-action ${post.liked_by_user ? 'liked' : ''}" onclick="window.citizenAnnouncementSystem?.likePost(${post.id})">
                                <i class="fa-regular fa-thumbs-up"></i>
                                ${post.liked_by_user ? 'Liked' : 'Like'}
                            </button>
                            <button type="button" class="citizen-announcement-action" onclick="window.citizenAnnouncementSystem?.toggleComments(${post.id})">
                                <i class="fa-regular fa-comment"></i>
                                Comment
                            </button>
                        </div>
                        <div class="citizen-announcement-comments" id="citizenAnnouncementComments-${post.id}">
                            <div class="citizen-announcement-comment-form">
                                <input type="text" id="citizenAnnouncementCommentInput-${post.id}" class="citizen-announcement-comment-input" maxlength="1000" placeholder="Write a comment...">
                                <button type="button" class="citizen-announcement-comment-submit" onclick="window.citizenAnnouncementSystem?.addComment(${post.id})">
                                    Post
                                </button>
                            </div>
                            ${renderComments(post)}
                        </div>
                    </div>
                </article>
            `;
        }).join('');

    }

    function toggleComments(postId) {
        const container = document.getElementById(`citizenAnnouncementComments-${postId}`);
        if (!container) return;
        container.classList.toggle('active');
        if (container.classList.contains('active')) {
            document.getElementById(`citizenAnnouncementCommentInput-${postId}`)?.focus();
        }
    }

    function openMediaDrawer(postId, mediaType, mediaSrc) {
        const post = announcementState.posts.find((item) => Number(item.id) === Number(postId));
        const drawer = document.getElementById('citizenAnnouncementMediaDrawer');
        if (!post || !drawer) return;

        const normalizedSrc = normalizeMediaUrl(mediaSrc || post.image_urls?.[0] || post.video_url || '');
        const visual = document.getElementById('citizenAnnouncementMediaDrawerVisual');
        const head = document.getElementById('citizenAnnouncementMediaDrawerHead');
        const body = document.getElementById('citizenAnnouncementMediaDrawerBody');
        const stats = document.getElementById('citizenAnnouncementMediaDrawerStats');
        const actions = document.getElementById('citizenAnnouncementMediaDrawerActions');

        announcementState.activeMediaPostId = postId;

        visual.innerHTML = mediaType === 'video'
            ? `<video controls autoplay preload="metadata" src="${normalizedSrc}"></video>`
            : `<img src="${normalizedSrc}" alt="Announcement media">`;

        head.innerHTML = `
            <div class="citizen-media-drawer-author">
                <div class="citizen-media-drawer-avatar">${escapeHtml(getInitials(post.user))}</div>
                <div>
                    <div class="citizen-media-drawer-name">${escapeHtml(getFullName(post.user))}</div>
                    <div class="citizen-media-drawer-meta">${escapeHtml(getRole(post.user))} | ${escapeHtml(post.time_ago || 'Recently')}</div>
                </div>
            </div>
            <button type="button" class="citizen-media-drawer-close" id="closeCitizenMediaDrawerButton"><i class="fa-solid fa-times"></i></button>
        `;

        const commentsHtml = (post.comments || []).length > 0
            ? `
                <div class="citizen-media-drawer-comments">
                    ${(post.comments || []).map((comment) => `
                        <div class="citizen-media-drawer-comment">
                            <div class="citizen-media-drawer-comment-avatar">${escapeHtml(getInitials(comment.user))}</div>
                            <div class="citizen-media-drawer-comment-body">
                                <div class="citizen-media-drawer-comment-author">${escapeHtml(getFullName(comment.user))}</div>
                                <div class="citizen-media-drawer-comment-text">${escapeHtml(comment.content || '')}</div>
                                <div class="citizen-media-drawer-comment-time">${escapeHtml(comment.time_ago || 'just now')}</div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `
            : '<div class="citizen-media-drawer-empty">No comments yet.</div>';

        body.innerHTML = `
            ${post.content ? `<div class="citizen-media-drawer-content">${escapeHtml(post.content)}</div>` : ''}
            ${post.poll_data ? renderPoll(post.poll_data) : ''}
            ${commentsHtml}
        `;

        stats.innerHTML = `
            <span><i class="fa-regular fa-thumbs-up"></i> ${Number(post.likes_count || 0)}</span>
            <span><i class="fa-regular fa-comment"></i> ${Number(post.comments_count || 0)} comments</span>
        `;

        actions.innerHTML = `
            <button class="citizen-announcement-action ${post.liked_by_user ? 'liked' : ''}" type="button" id="citizenDrawerLikeButton">
                <i class="fa-regular fa-thumbs-up"></i>
                ${post.liked_by_user ? 'Liked' : 'Like'}
            </button>
            <button class="citizen-announcement-action" type="button" id="citizenDrawerCommentButton">
                <i class="fa-regular fa-comment"></i>
                Comment
            </button>
        `;

        document.getElementById('closeCitizenMediaDrawerButton')?.addEventListener('click', closeMediaDrawer);
        document.getElementById('citizenDrawerLikeButton')?.addEventListener('click', async () => {
            await likePost(postId);
            const refreshed = announcementState.posts.find((item) => Number(item.id) === Number(postId));
            if (refreshed) {
                openMediaDrawer(postId, mediaType, normalizedSrc);
            }
        });
        document.getElementById('citizenDrawerCommentButton')?.addEventListener('click', () => {
            closeMediaDrawer();
            toggleComments(postId);
            setTimeout(() => document.getElementById(`citizenAnnouncementCommentInput-${postId}`)?.focus(), 80);
        });

        drawer.classList.add('active');
    }

    function closeMediaDrawer() {
        announcementState.activeMediaPostId = null;
        document.getElementById('citizenAnnouncementMediaDrawer')?.classList.remove('active');
        document.getElementById('citizenAnnouncementMediaDrawerVisual').innerHTML = '';
        document.getElementById('citizenAnnouncementMediaDrawerHead').innerHTML = '';
        document.getElementById('citizenAnnouncementMediaDrawerBody').innerHTML = '';
        document.getElementById('citizenAnnouncementMediaDrawerStats').innerHTML = '';
        document.getElementById('citizenAnnouncementMediaDrawerActions').innerHTML = '';
    }

    async function likePost(postId) {
        try {
            const response = await fetch(`/citizen/api/announcements/${postId}/like`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();
            if (!response.ok || result.success === false) {
                throw new Error(result.message || 'Failed to process like.');
            }

            const post = announcementState.posts.find((item) => Number(item.id) === Number(postId));
            if (post) {
                post.liked_by_user = Boolean(result.liked);
                post.likes_count = Number(result.likes_count || 0);
                applyFilters();
            }
        } catch (error) {
            console.error('Citizen announcement like failed:', error);
            window.showCitizenToast?.(error.message || 'Failed to process like.', true, 3200);
        }
    }

    async function addComment(postId) {
        const input = document.getElementById(`citizenAnnouncementCommentInput-${postId}`);
        const content = input?.value.trim();
        if (!input || !content) {
            return;
        }

        try {
            const response = await fetch(`/citizen/api/announcements/${postId}/comment`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ content })
            });

            const result = await response.json();
            if (!response.ok || result.success === false) {
                throw new Error(result.message || 'Failed to add comment.');
            }

            const post = announcementState.posts.find((item) => Number(item.id) === Number(postId));
            if (post) {
                post.comments = Array.isArray(post.comments) ? post.comments : [];
                post.comments.push(result.data);
                post.comments_count = Number(post.comments_count || 0) + 1;
                input.value = '';
                applyFilters();
                setTimeout(() => {
                    document.getElementById(`citizenAnnouncementComments-${postId}`)?.classList.add('active');
                }, 0);
            }
        } catch (error) {
            console.error('Citizen announcement comment failed:', error);
            window.showCitizenToast?.(error.message || 'Failed to add comment.', true, 3200);
        }
    }

    async function loadAnnouncements() {
        if (announcementState.loading) return;
        announcementState.loading = true;
        feed.innerHTML = `
            <div class="citizen-announcements-empty loading">
                <i class="fa-solid fa-spinner fa-spin"></i>
                <p>Loading announcements...</p>
            </div>
        `;

        try {
            let page = 1;
            let hasMore = true;
            const collectedPosts = [];

            while (hasMore) {
                const response = await fetch(`/citizen/api/announcements?page=${page}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`Failed to load announcements (${response.status})`);
                }

                const result = await response.json();
                const batch = Array.isArray(result?.data?.data) ? result.data.data : [];
                collectedPosts.push(...batch);
                hasMore = Boolean(result.has_more);
                page += 1;
            }

            announcementState.posts = collectedPosts;
            applyFilters();
        } catch (error) {
            console.error('Citizen announcements load failed:', error);
            feed.innerHTML = `
                <div class="citizen-announcements-empty">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <p>Failed to load announcements.</p>
                </div>
            `;
        } finally {
            announcementState.loading = false;
        }
    }

    searchInput.addEventListener('input', applyFilters);
    sortSelect.addEventListener('change', applyFilters);
    refreshButton.addEventListener('click', loadAnnouncements);
    document.getElementById('citizenAnnouncementMediaDrawer')?.addEventListener('click', (event) => {
        if (event.target.id === 'citizenAnnouncementMediaDrawer') {
            closeMediaDrawer();
        }
    });

    window.citizenAnnouncementSystem = {
        likePost,
        toggleComments,
        addComment,
        loadAnnouncements,
        openMediaDrawer,
        closeMediaDrawer,
    };

    loadAnnouncements();
});
</script>
