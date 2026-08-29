<div id="section-announcements" class="content-section announcements-page">
    <div class="citizen-announcements-shell">
        @if($errors->communityRequest->any())
            <div class="citizen-request-card" style="border-color:rgba(239,68,68,0.18);background:#fff7ed;color:#9a3412;">
                {{ $errors->communityRequest->first() }}
            </div>
        @endif

        <section class="citizen-announcements-hero">
            <div class="citizen-announcements-hero-grid">
                <div>
                    <span class="citizen-announcements-eyebrow"><i class="fa-solid fa-bullhorn"></i> Community feed</span>
                    <h1 class="citizen-announcements-title">Announcements</h1>
                    <p class="citizen-announcements-subtitle">Follow station updates, safety advisories, and community posts in one social-style feed.</p>
                </div>

                <div class="citizen-announcements-hero-actions">
                    <span class="citizen-announcements-pill">
                        <i class="fa-regular fa-newspaper"></i>
                        <span><span id="citizenAnnouncementsPostCount">0</span> Posts</span>
                    </span>
                    <button type="button" class="citizen-announcements-refresh" id="citizenAnnouncementsRefresh">
                        <i class="fa-solid fa-rotate-right"></i>
                        Refresh
                    </button>
                </div>
            </div>
        </section>

        <section class="citizen-announcements-stats" aria-label="Announcements summary">
            <div class="citizen-announcements-stat" style="--ann-glow:rgba(239,68,68,0.12);--ann-icon-bg:#fef2f2;--ann-icon-color:#dc2626;">
                <div class="citizen-announcements-stat-top">
                    <div>
                        <p class="citizen-announcements-stat-label">Important</p>
                        <p class="citizen-announcements-stat-value" id="citizenAnnouncementsImportantCount">0</p>
                    </div>
                    <div class="citizen-announcements-stat-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
                </div>
                <p class="citizen-announcements-stat-copy">Pinned advisories and high-priority updates.</p>
            </div>

            <div class="citizen-announcements-stat" style="--ann-glow:rgba(245,158,11,0.14);--ann-icon-bg:#fffbeb;--ann-icon-color:#d97706;">
                <div class="citizen-announcements-stat-top">
                    <div>
                        <p class="citizen-announcements-stat-label">Reactions</p>
                        <p class="citizen-announcements-stat-value" id="citizenAnnouncementsLikeCount">0</p>
                    </div>
                    <div class="citizen-announcements-stat-icon"><i class="fa-regular fa-thumbs-up"></i></div>
                </div>
                <p class="citizen-announcements-stat-copy">Community likes across visible posts.</p>
            </div>

            <div class="citizen-announcements-stat" style="--ann-glow:rgba(5,150,105,0.12);--ann-icon-bg:#ecfdf5;--ann-icon-color:#059669;">
                <div class="citizen-announcements-stat-top">
                    <div>
                        <p class="citizen-announcements-stat-label">Comments</p>
                        <p class="citizen-announcements-stat-value" id="citizenAnnouncementsCommentCount">0</p>
                    </div>
                    <div class="citizen-announcements-stat-icon"><i class="fa-regular fa-comment"></i></div>
                </div>
                <p class="citizen-announcements-stat-copy">Citizen discussion and feedback count.</p>
            </div>
        </section>

        <div class="citizen-announcements-workspace">
            <section class="citizen-announcements-stream" aria-label="Announcement posts">
                <div class="citizen-announcements-toolbar">
                    <div class="citizen-announcements-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="citizenAnnouncementsSearch" placeholder="Search by poster name or content">
                    </div>
                    <div class="citizen-announcements-filter-control" aria-label="Sort announcements">
                        <button type="button" class="citizen-announcements-filter-button" data-announcement-filter-toggle="sort" aria-label="Sort announcements" aria-expanded="false">
                            <i class="fa-solid fa-arrow-down-wide-short citizen-announcements-filter-icon" aria-hidden="true"></i>
                        </button>
                        <select id="citizenAnnouncementsSort" class="citizen-announcements-sort">
                            <option value="newest">Newest first</option>
                            <option value="oldest">Oldest first</option>
                            <option value="author">Author A-Z</option>
                            <option value="important">Important first</option>
                        </select>
                        <div class="citizen-announcements-filter-menu" data-announcement-filter-menu="sort" hidden>
                            <button type="button" class="citizen-announcements-filter-option" data-announcement-filter-target="citizenAnnouncementsSort" data-announcement-filter-value="newest">Newest first</button>
                            <button type="button" class="citizen-announcements-filter-option" data-announcement-filter-target="citizenAnnouncementsSort" data-announcement-filter-value="oldest">Oldest first</button>
                            <button type="button" class="citizen-announcements-filter-option" data-announcement-filter-target="citizenAnnouncementsSort" data-announcement-filter-value="author">Author A-Z</button>
                            <button type="button" class="citizen-announcements-filter-option" data-announcement-filter-target="citizenAnnouncementsSort" data-announcement-filter-value="important">Important first</button>
                        </div>
                    </div>
                </div>

                <div id="citizenAnnouncementsFeed" class="citizen-announcements-feed">
                    <div class="citizen-announcements-empty loading">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        <p>Loading announcements...</p>
                    </div>
                </div>
            </section>

            <aside class="citizen-request-card citizen-request-card--sidebar" aria-label="My community requests">
                <div class="citizen-request-head">
                    <div>
                        <span class="citizen-announcements-eyebrow"><i class="fa-solid fa-list-check"></i> My requests</span>
                        <h2 class="citizen-request-title">Community activity requests</h2>
                        <p class="citizen-request-copy">Track your requests or ask the station for an orientation, seminar, drill, or preparedness activity.</p>
                    </div>
                </div>
                <button type="button" class="citizen-request-submit citizen-request-open" data-community-request-open>
                    <i class="fa-solid fa-plus"></i>
                    New community request
                </button>
                <div class="citizen-request-list">
                    @forelse(($citizenCommunityActivityRequests ?? collect()) as $requestItem)
                        <div class="citizen-request-item">
                            <div class="citizen-request-item-top">
                                <strong>{{ $requestItem->title }}</strong>
                                <span class="citizen-request-status status-{{ $requestItem->status }}">{{ strtoupper(str_replace('_', ' ', $requestItem->status)) }}</span>
                            </div>
                            <p>{{ \Illuminate\Support\Str::limit($requestItem->description, 140) }}</p>
                            <div class="citizen-request-meta">
                                <span>{{ strtoupper(str_replace('_', ' ', $requestItem->request_type)) }}</span>
                                <span>{{ $requestItem->barangay?->barangay_name ?? 'No barangay linked' }}</span>
                                <span>{{ $requestItem->station?->station_name ?? 'Station 1' }}</span>
                            </div>
                            @if($requestItem->convertedEvent)
                                <p class="citizen-request-note">Scheduled as {{ $requestItem->convertedEvent->event }} on {{ optional($requestItem->convertedEvent->event_date)->format('M d, Y') }} {{ $requestItem->convertedEvent->schedule ? '· ' . $requestItem->convertedEvent->schedule : '' }}</p>
                            @elseif($requestItem->review_notes)
                                <p class="citizen-request-note">Review note: {{ $requestItem->review_notes }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="citizen-announcements-empty">
                            <i class="fa-solid fa-inbox"></i>
                            <p>No community activity requests yet.</p>
                        </div>
                    @endforelse
                </div>
            </aside>
        </div>
        <div class="citizen-announcements-mobile-bottom-space" aria-hidden="true"></div>
    </div>
</div>

<div class="citizen-request-modal" id="citizenCommunityRequestModal" aria-hidden="true" @if(!$errors->communityRequest->any()) hidden @endif>
    <button type="button" class="citizen-request-modal__backdrop" data-community-request-close aria-label="Close community request form"></button>
    <section class="citizen-request-modal__panel" role="dialog" aria-modal="true" aria-labelledby="citizenCommunityRequestTitle">
        <header class="citizen-request-modal__header">
            <div>
                <span class="citizen-announcements-eyebrow"><i class="fa-solid fa-people-roof"></i> Community request</span>
                <h2 class="citizen-request-title" id="citizenCommunityRequestTitle">Request a community activity</h2>
                <p class="citizen-request-copy">Send an orientation, seminar, drill, inspection, or community meeting request to your assigned station.</p>
            </div>
            <button type="button" class="citizen-request-modal__close" data-community-request-close aria-label="Close community request form">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </header>
        @if($errors->communityRequest->any())
            <div class="citizen-request-modal__error" role="alert">{{ $errors->communityRequest->first() }}</div>
        @endif
        <form method="POST" action="{{ route('citizen.community-activity-requests.store') }}" class="citizen-request-form">
            @csrf
            <div class="citizen-request-form__body">
                <div class="citizen-request-grid">
                    <label>
                        <span>Request type <span class="citizen-request-required" aria-hidden="true">*</span></span>
                        <select name="request_type" required>
                            @foreach(['orientation' => 'Orientation', 'seminar' => 'Seminar', 'drill' => 'Drill', 'inspection' => 'Inspection', 'community_meeting' => 'Community meeting', 'other' => 'Other'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('request_type', 'orientation') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Preferred date</span>
                        <input type="date" name="preferred_date" value="{{ old('preferred_date') }}" min="{{ now()->toDateString() }}">
                    </label>
                    <label>
                        <span>Start time</span>
                        <input type="time" name="preferred_start_time" value="{{ old('preferred_start_time') }}">
                    </label>
                    <label>
                        <span>End time</span>
                        <input type="time" name="preferred_end_time" value="{{ old('preferred_end_time') }}">
                    </label>
                </div>
                <label>
                    <span>Title <span class="citizen-request-required" aria-hidden="true">*</span></span>
                    <input type="text" name="title" value="{{ old('title') }}" placeholder="Example: Fire safety seminar for our neighborhood" required maxlength="255">
                </label>
                <label>
                    <span>Reason for request <span class="citizen-request-required" aria-hidden="true">*</span></span>
                    <textarea name="description" rows="4" placeholder="Describe the audience, concern, and support needed." required maxlength="2000">{{ old('description') }}</textarea>
                </label>
                <label>
                    <span>Contact number</span>
                    <input type="text" name="contact_number" value="{{ old('contact_number', auth()->user()->contact_number) }}" placeholder="Optional contact number" maxlength="60">
                </label>
            </div>
            <div class="citizen-request-modal__actions">
                <p class="citizen-request-submit-status" data-community-request-submit-status role="status" aria-live="polite" hidden></p>
                <button type="button" class="citizen-request-refresh-status" data-community-request-refresh hidden>
                    <i class="fa-solid fa-rotate-right"></i>
                    Refresh and check requests
                </button>
                <button type="button" class="citizen-request-modal__cancel" data-community-request-close>Cancel</button>
                <button type="submit" class="citizen-request-submit" data-community-request-submit>
                    <i class="fa-solid fa-paper-plane" data-community-request-submit-icon></i>
                    <span data-community-request-submit-label>Save and send request</span>
                </button>
            </div>
        </form>
    </section>
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
    #section-announcements.announcements-page {
        --ann-red: #c63d26;
        --ann-red-dark: #9f2f1d;
        --ann-orange: #e39b17;
        --ann-ink: #132033;
        --ann-muted: #64748b;
        width: min(100%, 1180px);
        margin-inline: auto;
        padding: clamp(0.85rem, 1.45vw, 1.35rem);
        padding-bottom: calc(7rem + env(safe-area-inset-bottom, 0px));
        color: var(--ann-ink);
    }
    .citizen-announcements-shell {
        max-width: 1040px;
        margin: 0 auto;
        display: grid;
        gap: clamp(1rem, 2vw, 1.35rem);
    }
    .citizen-announcements-hero,
    .citizen-announcements-stat,
    .citizen-announcements-toolbar,
    .citizen-announcement-card {
        border: 1px solid rgba(120, 53, 15, 0.11);
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 18px 40px rgba(88, 43, 20, 0.075);
    }
    .citizen-announcements-hero {
        position: relative;
        overflow: hidden;
        border-color: rgba(198, 61, 38, 0.16);
        border-radius: 2rem;
        background:
            radial-gradient(circle at 92% 12%, rgba(227, 155, 23, 0.24), transparent 16rem),
            linear-gradient(135deg, rgba(255, 248, 237, 0.98), rgba(255, 255, 255, 0.96));
        padding: clamp(1.1rem, 3vw, 1.75rem);
    }
    .citizen-announcements-hero-grid {
        display: grid;
        gap: 1rem;
    }
    .citizen-announcements-eyebrow {
        display: inline-flex;
        width: fit-content;
        align-items: center;
        gap: 0.45rem;
        border-radius: 999px;
        background: rgba(198, 61, 38, 0.1);
        color: var(--ann-red-dark);
        font-size: 0.72rem;
        font-weight: 900;
        letter-spacing: 0.13em;
        text-transform: uppercase;
        padding: 0.45rem 0.7rem;
    }
    .citizen-announcements-title {
        margin-top: 0.8rem;
        color: var(--ann-ink);
        font-size: clamp(1.85rem, 5vw, 2.85rem);
        font-weight: 900;
        line-height: 1;
        letter-spacing: -0.055em;
    }
    .citizen-announcements-subtitle {
        max-width: 44rem;
        margin-top: 0.75rem;
        color: var(--ann-muted);
        font-size: clamp(0.94rem, 2.4vw, 1.03rem);
        line-height: 1.7;
    }
    .citizen-announcements-hero-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
    }
    .citizen-announcements-pill,
    .citizen-announcements-refresh {
        display: inline-flex;
        min-height: 46px;
        align-items: center;
        justify-content: center;
        gap: 0.62rem;
        border-radius: 1rem;
        font-size: 0.9rem;
        font-weight: 850;
    }
    .citizen-announcements-refresh {
        border: 0;
        background: linear-gradient(135deg, var(--ann-red), var(--ann-orange));
        color: #fff;
        padding: 0.82rem 1.1rem;
        box-shadow: 0 16px 34px rgba(198, 61, 38, 0.24);
        cursor: pointer;
    }
    .citizen-announcements-pill {
        border: 1px solid rgba(245, 158, 11, 0.2);
        background: linear-gradient(135deg, #fffbeb, #fff7ed);
        color: #b45309;
        padding: 0.72rem 1rem;
    }
    .citizen-announcements-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.85rem;
    }
    .citizen-announcements-workspace {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(280px, 340px);
        align-items: start;
        gap: 1rem;
    }
    .citizen-announcements-stream {
        min-width: 0;
        display: grid;
        gap: 1rem;
    }
    .citizen-request-card {
        border: 1px solid rgba(120, 53, 15, 0.11);
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 18px 40px rgba(88, 43, 20, 0.075);
        border-radius: 1.7rem;
        padding: 1.1rem;
    }
    .citizen-request-head {
        margin-bottom: 0.9rem;
    }
    .citizen-request-card--sidebar {
        position: sticky;
        top: 1rem;
        display: grid;
        gap: 0.9rem;
        max-height: calc(100vh - 2rem);
        overflow: hidden;
    }
    .citizen-request-card--sidebar .citizen-request-head {
        margin-bottom: 0;
    }
    .citizen-request-card--sidebar .citizen-request-list {
        max-height: min(560px, calc(100vh - 250px));
        overflow-y: auto;
        overscroll-behavior: contain;
        padding-right: 0.2rem;
        scrollbar-width: thin;
    }
    .citizen-request-open {
        width: 100%;
    }
    .citizen-request-title {
        margin: 0.7rem 0 0;
        font-size: 1.18rem;
        font-weight: 850;
        color: var(--ann-ink);
    }
    .citizen-request-copy {
        margin-top: 0.45rem;
        color: var(--ann-muted);
        line-height: 1.6;
    }
    .citizen-request-form,
    .citizen-request-list {
        display: grid;
        gap: 0.8rem;
    }
    .citizen-request-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }
    .citizen-request-form label {
        display: grid;
        gap: 0.4rem;
        color: var(--ann-ink);
        font-size: 0.84rem;
        font-weight: 700;
    }
    .citizen-request-form input,
    .citizen-request-form select,
    .citizen-request-form textarea {
        width: 100%;
        border: 1px solid rgba(148, 163, 184, 0.4);
        border-radius: 1rem;
        padding: 0.82rem 0.95rem;
        font: inherit;
        color: var(--ann-ink);
        background: #fff;
    }
    .citizen-request-submit {
        border: 0;
        border-radius: 1rem;
        background: linear-gradient(135deg, var(--ann-red), var(--ann-orange));
        color: #fff;
        padding: 0.92rem 1.1rem;
        font-weight: 850;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
    }
    .citizen-request-submit:disabled {
        cursor: wait;
        opacity: 0.78;
        filter: saturate(0.82);
    }
    .citizen-request-required {
        color: var(--ann-red);
        font-weight: 900;
    }
    .citizen-request-item {
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 1.1rem;
        padding: 0.95rem;
        background: linear-gradient(180deg, #fffaf6 0%, #ffffff 100%);
    }
    .citizen-request-item-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.45rem;
    }
    .citizen-request-status {
        display: inline-flex;
        align-items: center;
        min-height: 30px;
        padding: 0 0.75rem;
        border-radius: 999px;
        font-size: 0.74rem;
        font-weight: 800;
    }
    .citizen-request-status.status-pending { background: #fffbeb; color: #b45309; }
    .citizen-request-status.status-approved { background: #ecfdf5; color: #047857; }
    .citizen-request-status.status-rejected { background: #fef2f2; color: #b91c1c; }
    .citizen-request-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.55rem;
        color: var(--ann-muted);
        font-size: 0.78rem;
        margin-top: 0.55rem;
    }
    .citizen-request-note {
        margin-top: 0.55rem;
        color: #475569;
        font-size: 0.84rem;
        line-height: 1.55;
    }
    .citizen-request-modal[hidden] {
        display: none;
    }
    .citizen-request-modal {
        position: fixed;
        inset: 0;
        z-index: 520;
        display: grid;
        place-items: center;
        padding: 1rem;
    }
    .citizen-request-modal__backdrop {
        position: absolute;
        inset: 0;
        width: 100%;
        border: 0;
        background: rgba(15, 23, 42, 0.58);
        backdrop-filter: blur(7px);
    }
    .citizen-request-modal__panel {
        position: relative;
        width: min(720px, 100%);
        max-height: min(90dvh, 820px);
        overflow: hidden;
        border: 1px solid rgba(198, 61, 38, 0.14);
        border-radius: 1.75rem;
        background: #fffdfa;
        box-shadow: 0 30px 80px rgba(15, 23, 42, 0.28);
        display: flex;
        flex-direction: column;
    }
    .citizen-request-modal__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
        padding: 1.25rem 1.25rem 0;
        flex: 0 0 auto;
    }
    .citizen-request-modal__close {
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        border: 0;
        border-radius: 999px;
        background: #fff1ea;
        color: var(--ann-red);
        cursor: pointer;
    }
    .citizen-request-modal__error {
        margin: 0 1.25rem 0.9rem;
        border: 1px solid rgba(239, 68, 68, 0.2);
        border-radius: 1rem;
        background: #fff1f2;
        color: #9f1239;
        padding: 0.8rem 0.9rem;
        font-size: 0.86rem;
        font-weight: 700;
    }
    .citizen-request-modal__actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 0.7rem;
        flex: 0 0 auto;
        border-top: 1px solid rgba(198, 61, 38, 0.12);
        background: rgba(255, 253, 250, 0.97);
        box-shadow: 0 -12px 28px rgba(88, 43, 20, 0.08);
        padding: 0.9rem 1.25rem 1.25rem;
    }
    .citizen-request-submit-status {
        flex: 1 0 100%;
        margin: 0;
        border-radius: 0.85rem;
        background: #fff7ed;
        color: #9a3412;
        padding: 0.7rem 0.85rem;
        font-size: 0.8rem;
        font-weight: 700;
        line-height: 1.45;
    }
    .citizen-request-refresh-status {
        border: 1px solid rgba(198, 61, 38, 0.24);
        border-radius: 1rem;
        background: #fff7ed;
        color: var(--ann-red-dark);
        padding: 0.9rem 1.1rem;
        font-weight: 800;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.55rem;
    }
    .citizen-request-submit-status[hidden],
    .citizen-request-refresh-status[hidden] {
        display: none;
    }
    .citizen-request-modal__cancel {
        border: 1px solid rgba(148, 163, 184, 0.35);
        border-radius: 1rem;
        background: #fff;
        color: #475569;
        padding: 0.9rem 1.1rem;
        font-weight: 800;
        cursor: pointer;
    }
    body.citizen-request-modal-open {
        overflow: hidden;
    }
    .citizen-request-modal .citizen-request-form {
        min-height: 0;
        overflow: hidden;
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        gap: 0;
    }
    .citizen-request-form__body {
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
        scrollbar-color: rgba(198, 61, 38, 0.45) transparent;
        display: grid;
        gap: 0.8rem;
        padding: 0 1.25rem 1rem;
    }
    .citizen-announcements-stat {
        position: relative;
        overflow: hidden;
        border-radius: 1.6rem;
        padding: 1.1rem;
    }
    .citizen-announcements-stat::after {
        content: '';
        position: absolute;
        inset: auto -2rem -3rem auto;
        width: 8rem;
        height: 8rem;
        border-radius: 999px;
        background: var(--ann-glow, rgba(198, 61, 38, 0.08));
    }
    .citizen-announcements-stat-top {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-between;
        gap: 0.8rem;
    }
    .citizen-announcements-stat-label {
        color: var(--ann-muted);
        font-size: 0.82rem;
        font-weight: 750;
    }
    .citizen-announcements-stat-value {
        margin-top: 0.42rem;
        color: var(--ann-ink);
        font-size: clamp(2rem, 6vw, 2.65rem);
        font-weight: 950;
        letter-spacing: -0.055em;
        line-height: 1;
    }
    .citizen-announcements-stat-icon {
        display: inline-flex;
        width: 3rem;
        height: 3rem;
        flex: 0 0 3rem;
        align-items: center;
        justify-content: center;
        border-radius: 1.05rem;
        background: var(--ann-icon-bg, #fff7ed);
        color: var(--ann-icon-color, #c2410c);
        font-size: 1.1rem;
    }
    .citizen-announcements-stat-copy {
        position: relative;
        z-index: 1;
        margin-top: 0.9rem;
        color: var(--ann-muted);
        font-size: 0.78rem;
        line-height: 1.55;
    }
    .citizen-announcements-toolbar {
        display: flex;
        gap: 0.9rem;
        flex-wrap: wrap;
        align-items: center;
        border-radius: 1.5rem;
        padding: 1rem;
        background: linear-gradient(135deg, rgba(255, 248, 237, 0.74), rgba(255, 255, 255, 0.96));
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
    .citizen-announcements-filter-control {
        position: relative;
        min-width: 0;
    }
    .citizen-announcements-filter-icon,
    .citizen-announcements-filter-button,
    .citizen-announcements-filter-menu {
        display: none;
    }
    .citizen-announcements-filter-option {
        font: inherit;
    }
    .citizen-announcements-feed {
        display: grid;
        gap: 1rem;
    }
    .citizen-announcement-card {
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
        width: 48px;
        height: 48px;
        flex-shrink: 0;
        border-radius: 999px;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        color: #fff;
        background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
    }
    .citizen-announcement-avatar img,
    .citizen-media-drawer-avatar img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
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
        background: linear-gradient(135deg, #f8fafc, #fff7ed);
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
        border-top: 1px solid rgba(226, 232, 240, 0.8);
        padding-top: 0.75rem;
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
        width: 44px;
        height: 44px;
        padding: 0;
        background: #ea580c;
        color: #fff;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.18s ease, background 0.18s ease;
    }
    .citizen-announcement-comment-submit:hover {
        background: #c2410c;
        transform: translateY(-1px);
    }
    .citizen-announcement-comment-submit:focus-visible,
    .citizen-announcement-comment-control:focus-visible {
        outline: 3px solid rgba(234, 88, 12, 0.22);
        outline-offset: 2px;
    }
    .citizen-announcements-mobile-bottom-space {
        display: none;
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
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 800;
        font-size: 0.75rem;
        background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
    }
    .citizen-announcement-comment-avatar img,
    .citizen-media-drawer-comment-avatar img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
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
    .citizen-announcement-comment-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }
    .citizen-announcement-comment-controls {
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
    }
    .citizen-announcement-comment-control {
        width: 30px;
        height: 30px;
        border: 0;
        border-radius: 999px;
        background: transparent;
        color: #64748b;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .citizen-announcement-comment-control:hover {
        background: #f1f5f9;
        color: #c2410c;
    }
    .citizen-announcement-comment-control.delete:hover {
        background: #fef2f2;
        color: #dc2626;
    }
    .citizen-announcement-comment-editor {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto auto;
        align-items: center;
        gap: 0.4rem;
        margin-top: 0.45rem;
    }
    .citizen-announcement-comment-edit-input {
        min-width: 0;
        border: 1px solid #fdba74;
        border-radius: 0.8rem;
        padding: 0.65rem 0.75rem;
        color: #172033;
        outline: none;
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
        position: relative;
        overflow: hidden;
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
        position: relative;
        overflow: hidden;
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
    @media (min-width: 760px) {
        .citizen-announcements-hero-grid {
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: start;
        }
    }
    @media (max-width: 640px) {
        #section-announcements.announcements-page {
            width: 100%;
            padding: 1rem;
            padding-bottom: calc(7.5rem + env(safe-area-inset-bottom, 0px));
        }
        .citizen-announcements-shell {
            gap: 1rem;
        }
        .citizen-announcements-hero {
            border-radius: 1.4rem;
            box-shadow: none;
            padding: 1.35rem 1rem 1.05rem;
        }
        .citizen-announcements-title {
            max-width: 17rem;
            font-size: 2rem;
            line-height: 1.05;
        }
        .citizen-announcements-subtitle {
            max-width: 18rem;
            font-size: 0.93rem;
            line-height: 1.58;
        }
        .citizen-announcements-hero-actions,
        .citizen-announcements-refresh,
        .citizen-announcements-pill {
            width: 100%;
        }
        .citizen-announcements-stats {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.55rem;
        }
        .citizen-announcements-workspace,
        .citizen-request-grid {
            grid-template-columns: 1fr;
        }
        .citizen-request-card--sidebar {
            position: static;
            order: -1;
            max-height: none;
        }
        .citizen-request-card--sidebar .citizen-request-list {
            max-height: 360px;
        }
        .citizen-request-modal {
            align-items: end;
            padding: 0;
        }
        .citizen-request-modal__panel {
            width: 100%;
            max-height: 92dvh;
            border-radius: 1.5rem 1.5rem 0 0;
        }
        .citizen-request-modal__header {
            padding: 1rem 1rem 0;
        }
        .citizen-request-modal__error {
            margin-inline: 1rem;
        }
        .citizen-request-form__body {
            padding: 0 1rem 0.9rem;
        }
        .citizen-request-modal__actions {
            display: grid;
            grid-template-columns: 1fr 1.35fr;
            padding: 0.8rem 1rem calc(0.8rem + env(safe-area-inset-bottom, 0px));
        }
        .citizen-request-submit-status,
        .citizen-request-refresh-status {
            grid-column: 1 / -1;
        }
        .citizen-announcements-stat {
            min-height: 8.25rem;
            border-radius: 1rem;
            padding: 0.72rem;
            box-shadow: none;
        }
        .citizen-announcements-stat-top {
            display: grid;
            gap: 0.55rem;
        }
        .citizen-announcements-stat-icon {
            order: -1;
            width: 2.25rem;
            height: 2.25rem;
            flex-basis: 2.25rem;
            border-radius: 0.78rem;
            font-size: 0.95rem;
        }
        .citizen-announcements-stat-label {
            font-size: 0.68rem;
            line-height: 1.18;
        }
        .citizen-announcements-stat-value {
            margin-top: 0.28rem;
            font-size: 1.7rem;
        }
        .citizen-announcements-stat-copy {
            display: none;
        }
        .citizen-announcements-toolbar {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 0.55rem;
            border-radius: 1.2rem;
            box-shadow: none;
            padding: 0.75rem;
        }
        .citizen-announcements-search {
            min-width: 0;
            flex-basis: auto;
            padding: 0.72rem 0.8rem;
        }
        .citizen-announcements-search input {
            font-size: 0.84rem;
        }
        .citizen-announcements-filter-control {
            min-height: 46px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            border-radius: 1rem;
            background: #fff;
            color: #c2410c;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: visible;
        }
        .citizen-announcements-filter-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }
        .citizen-announcements-filter-control .citizen-announcements-sort {
            position: absolute;
            width: 1px;
            height: 1px;
            min-width: 1px;
            opacity: 0;
            pointer-events: none;
        }
        .citizen-announcements-filter-button {
            display: inline-flex;
            width: 100%;
            height: 100%;
            min-height: 46px;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: inherit;
            background: transparent;
            color: inherit;
        }
        .citizen-announcements-filter-menu {
            display: block;
            position: absolute;
            right: 0;
            top: calc(100% + 0.5rem);
            z-index: 30;
            width: max-content;
            min-width: 11.5rem;
            max-width: calc(100vw - 2rem);
            border: 1px solid rgba(198, 61, 38, 0.14);
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 18px 40px rgba(88, 43, 20, 0.16);
            padding: 0.35rem;
        }
        .citizen-announcements-filter-menu[hidden] {
            display: none;
        }
        .citizen-announcements-filter-option {
            display: flex;
            width: 100%;
            align-items: center;
            justify-content: space-between;
            gap: 0.7rem;
            border: 0;
            border-radius: 0.75rem;
            background: transparent;
            color: #334155;
            padding: 0.68rem 0.78rem;
            text-align: left;
            font-size: 0.88rem;
            font-weight: 700;
        }
        .citizen-announcements-filter-option.is-active {
            background: #fff7ed;
            color: #c2410c;
        }
        .citizen-announcement-card {
            border-radius: 1.2rem;
            box-shadow: none;
        }
        .citizen-announcement-head {
            flex-direction: column;
            gap: 0.7rem;
            padding: 1rem 1rem 0;
        }
        .citizen-announcement-avatar {
            width: 42px;
            height: 42px;
        }
        .citizen-announcement-name {
            font-size: 0.94rem;
        }
        .citizen-announcement-role,
        .citizen-announcement-meta {
            font-size: 0.76rem;
        }
        .citizen-announcement-badges {
            justify-content: flex-start;
        }
        .citizen-announcement-body {
            padding: 0.85rem 1rem 1rem;
            gap: 0.75rem;
        }
        .citizen-announcement-text {
            font-size: 0.9rem;
            line-height: 1.62;
        }
        .citizen-announcement-media {
            margin-inline: -0.15rem;
            padding: 0.5rem;
            border-radius: 0.95rem;
        }
        .citizen-announcement-media img,
        .citizen-announcement-media video {
            max-height: 320px;
            border-radius: 0.8rem;
        }
        .citizen-announcement-stats {
            gap: 0.7rem;
            font-size: 0.78rem;
        }
        .citizen-announcement-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.55rem;
        }
        .citizen-announcement-action {
            justify-content: center;
            padding: 0.68rem 0.75rem;
            font-size: 0.8rem;
        }
        .citizen-announcement-comment-form {
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.5rem;
        }
        .citizen-announcement-comment-input {
            border-radius: 1rem;
            font-size: 0.86rem;
        }
        .citizen-announcements-mobile-bottom-space {
            display: block;
            height: calc(2.25rem + env(safe-area-inset-bottom, 0px));
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
    const postCountNode = document.getElementById('citizenAnnouncementsPostCount');
    const importantCountNode = document.getElementById('citizenAnnouncementsImportantCount');
    const likeCountNode = document.getElementById('citizenAnnouncementsLikeCount');
    const commentCountNode = document.getElementById('citizenAnnouncementsCommentCount');
    const requestModal = document.getElementById('citizenCommunityRequestModal');
    const requestOpenButtons = document.querySelectorAll('[data-community-request-open]');
    const requestCloseButtons = requestModal?.querySelectorAll('[data-community-request-close]') || [];
    const requestForm = requestModal?.querySelector('.citizen-request-form');
    const requestSubmitButton = requestForm?.querySelector('[data-community-request-submit]');
    const requestSubmitIcon = requestSubmitButton?.querySelector('[data-community-request-submit-icon]');
    const requestSubmitLabel = requestSubmitButton?.querySelector('[data-community-request-submit-label]');
    const requestSubmitStatus = requestForm?.querySelector('[data-community-request-submit-status]');
    const requestRefreshButton = requestForm?.querySelector('[data-community-request-refresh]');
    let requestModalTrigger = null;
    let requestSubmitting = false;
    let requestSubmissionTimer = null;

    const openRequestModal = (trigger = null) => {
        if (!requestModal) return;
        requestModalTrigger = trigger || document.activeElement;
        requestModal.hidden = false;
        requestModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('citizen-request-modal-open');
        window.requestAnimationFrame(() => {
            requestModal.querySelector('select, input, textarea, button')?.focus({ preventScroll: true });
        });
    };

    const closeRequestModal = () => {
        if (!requestModal) return;
        if (requestModal.contains(document.activeElement) && document.activeElement instanceof HTMLElement) {
            document.activeElement.blur();
        }
        requestModal.hidden = true;
        requestModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('citizen-request-modal-open');
        requestModalTrigger?.focus?.({ preventScroll: true });
    };

    requestOpenButtons.forEach((button) => button.addEventListener('click', () => openRequestModal(button)));
    requestCloseButtons.forEach((button) => button.addEventListener('click', closeRequestModal));
    requestForm?.addEventListener('submit', (event) => {
        if (requestSubmitting) {
            event.preventDefault();
            return;
        }

        requestSubmitting = true;
        requestForm.setAttribute('aria-busy', 'true');

        if (requestSubmitButton) {
            requestSubmitButton.disabled = true;
            requestSubmitButton.setAttribute('aria-disabled', 'true');
        }
        if (requestSubmitIcon) {
            requestSubmitIcon.className = 'fa-solid fa-spinner fa-spin';
        }
        if (requestSubmitLabel) {
            requestSubmitLabel.textContent = 'Sending request...';
        }

        requestSubmissionTimer = window.setTimeout(() => {
            if (!requestSubmitting) return;

            if (requestSubmitLabel) {
                requestSubmitLabel.textContent = 'Still sending...';
            }
            if (requestSubmitStatus) {
                requestSubmitStatus.hidden = false;
                requestSubmitStatus.textContent = 'This is taking longer than expected. Refresh to check whether your request was saved before trying again.';
            }
            if (requestRefreshButton) {
                requestRefreshButton.hidden = false;
            }
        }, 12000);
    });
    requestRefreshButton?.addEventListener('click', () => window.location.reload());
    window.addEventListener('pagehide', () => {
        if (requestSubmissionTimer) window.clearTimeout(requestSubmissionTimer);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && requestModal && !requestModal.hidden) closeRequestModal();
    });
    if (requestModal && !requestModal.hidden) openRequestModal();

    if (!feed || !searchInput || !sortSelect || !refreshButton) {
        return;
    }

    const announcementState = {
        posts: [],
        filteredPosts: [],
        loading: false,
        activeMediaPostId: null,
        openCommentPostIds: new Set(),
        editingCommentKey: null,
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

    function renderPosterAvatar(user = {}, className = 'citizen-announcement-avatar') {
        const name = getFullName(user);
        const initials = escapeHtml(getInitials(user));
        const imageUrl = String(user.profile_thumbnail_url || user.profile_image_url || '').trim();
        const image = imageUrl
            ? `<img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(name)} profile picture" loading="lazy" onerror="this.remove()">`
            : '';

        return `<div class="${className}" aria-label="${escapeHtml(name)}">${initials}${image}</div>`;
    }

    function renderCommenterAvatar(user = {}, className = 'citizen-announcement-comment-avatar') {
        return renderPosterAvatar(user, className);
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
                ${comments.map((comment) => {
                    const commentId = Number(comment.id ?? comment.comment_id);
                    const commentKey = `${post.id}:${commentId}`;
                    const isEditing = announcementState.editingCommentKey === commentKey;
                    const controls = comment.can_manage ? `
                        <div class="citizen-announcement-comment-controls" aria-label="Comment actions">
                            <button type="button" class="citizen-announcement-comment-control" title="Edit comment" aria-label="Edit comment" onclick="window.citizenAnnouncementSystem?.beginEditComment(${post.id}, ${commentId})">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button type="button" class="citizen-announcement-comment-control delete" title="Delete comment" aria-label="Delete comment" onclick="window.citizenAnnouncementSystem?.deleteComment(${post.id}, ${commentId})">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </div>
                    ` : '';

                    return `
                    <div class="citizen-announcement-comment">
                        ${renderCommenterAvatar(comment.user)}
                        <div class="citizen-announcement-comment-body">
                            <div class="citizen-announcement-comment-author">${escapeHtml(getFullName(comment.user))}</div>
                            ${isEditing ? `
                                <div class="citizen-announcement-comment-editor">
                                    <input type="text" id="citizenAnnouncementCommentEdit-${post.id}-${commentId}" class="citizen-announcement-comment-edit-input" maxlength="1000" value="${escapeHtml(comment.content || '')}" onkeydown="if(event.key === 'Enter'){ event.preventDefault(); window.citizenAnnouncementSystem?.saveComment(${post.id}, ${commentId}); }">
                                    <button type="button" class="citizen-announcement-comment-control" title="Save comment" aria-label="Save comment" onclick="window.citizenAnnouncementSystem?.saveComment(${post.id}, ${commentId})"><i class="fa-solid fa-check"></i></button>
                                    <button type="button" class="citizen-announcement-comment-control" title="Cancel editing" aria-label="Cancel editing" onclick="window.citizenAnnouncementSystem?.cancelEditComment()"><i class="fa-solid fa-xmark"></i></button>
                                </div>
                            ` : `<div class="citizen-announcement-comment-text">${escapeHtml(comment.content || '')}</div>`}
                            <div class="citizen-announcement-comment-meta">
                                <div class="citizen-announcement-comment-time">${escapeHtml(comment.time_ago || 'just now')}</div>
                                ${isEditing ? '' : controls}
                            </div>
                        </div>
                    </div>
                `}).join('')}
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

    function updateAnnouncementSummary() {
        const posts = announcementState.posts;
        const importantCount = posts.filter((post) => Boolean(post.is_important)).length;
        const likeCount = posts.reduce((total, post) => total + Number(post.likes_count || 0), 0);
        const commentCount = posts.reduce((total, post) => total + Number(post.comments_count || 0), 0);

        if (postCountNode) postCountNode.textContent = posts.length;
        if (importantCountNode) importantCountNode.textContent = importantCount;
        if (likeCountNode) likeCountNode.textContent = likeCount;
        if (commentCountNode) commentCountNode.textContent = commentCount;
    }

    function syncAnnouncementFilterMenus() {
        document.querySelectorAll('[data-announcement-filter-target]').forEach((option) => {
            const select = document.getElementById(option.dataset.announcementFilterTarget || '');
            option.classList.toggle('is-active', Boolean(select && String(select.value) === String(option.dataset.announcementFilterValue)));
        });
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

        updateAnnouncementSummary();
        syncAnnouncementFilterMenus();
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
                            ${renderPosterAvatar(post.user)}
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
                        <div class="citizen-announcement-comments ${announcementState.openCommentPostIds.has(Number(post.id)) ? 'active' : ''}" id="citizenAnnouncementComments-${post.id}">
                            <div class="citizen-announcement-comment-form">
                                <input type="text" id="citizenAnnouncementCommentInput-${post.id}" class="citizen-announcement-comment-input" maxlength="1000" placeholder="Write a comment..." onkeydown="if(event.key === 'Enter'){ event.preventDefault(); window.citizenAnnouncementSystem?.addComment(${post.id}); }">
                                <button type="button" class="citizen-announcement-comment-submit" title="Send comment" aria-label="Send comment" onclick="window.citizenAnnouncementSystem?.addComment(${post.id})">
                                    <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
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
            announcementState.openCommentPostIds.add(Number(postId));
        } else {
            announcementState.openCommentPostIds.delete(Number(postId));
        }
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
                ${renderPosterAvatar(post.user, 'citizen-media-drawer-avatar')}
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
                            ${renderCommenterAvatar(comment.user, 'citizen-media-drawer-comment-avatar')}
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
                post.comments_count = post.comments.length;
                input.value = '';
                announcementState.openCommentPostIds.add(Number(postId));
                applyFilters();
            }
        } catch (error) {
            console.error('Citizen announcement comment failed:', error);
            window.showCitizenToast?.(error.message || 'Failed to add comment.', true, 3200);
        }
    }

    function beginEditComment(postId, commentId) {
        announcementState.openCommentPostIds.add(Number(postId));
        announcementState.editingCommentKey = `${postId}:${commentId}`;
        applyFilters();
        requestAnimationFrame(() => {
            const input = document.getElementById(`citizenAnnouncementCommentEdit-${postId}-${commentId}`);
            input?.focus();
            input?.select();
        });
    }

    function cancelEditComment() {
        announcementState.editingCommentKey = null;
        applyFilters();
    }

    async function saveComment(postId, commentId) {
        const input = document.getElementById(`citizenAnnouncementCommentEdit-${postId}-${commentId}`);
        const content = input?.value.trim();
        if (!input || !content) {
            window.showCitizenToast?.('A comment cannot be empty.', true, 2800);
            return;
        }

        input.disabled = true;
        try {
            const response = await fetch(`/citizen/api/announcements/${postId}/comment/${commentId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ content })
            });
            const result = await response.json();
            if (!response.ok || result.success === false) {
                throw new Error(result.message || 'Failed to update comment.');
            }

            const post = announcementState.posts.find((item) => Number(item.id) === Number(postId));
            const index = post?.comments?.findIndex((comment) => Number(comment.id ?? comment.comment_id) === Number(commentId)) ?? -1;
            if (post && index >= 0) post.comments[index] = result.data;
            announcementState.editingCommentKey = null;
            applyFilters();
            window.showCitizenToast?.('Comment updated.', false, 2200);
        } catch (error) {
            input.disabled = false;
            console.error('Citizen comment update failed:', error);
            window.showCitizenToast?.(error.message || 'Failed to update comment.', true, 3200);
        }
    }

    async function deleteComment(postId, commentId) {
        if (!window.confirm('Delete this comment?')) return;

        try {
            const response = await fetch(`/citizen/api/announcements/${postId}/comment/${commentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const result = await response.json();
            if (!response.ok || result.success === false) {
                throw new Error(result.message || 'Failed to delete comment.');
            }

            const post = announcementState.posts.find((item) => Number(item.id) === Number(postId));
            if (post) {
                post.comments = (post.comments || []).filter((comment) => Number(comment.id ?? comment.comment_id) !== Number(commentId));
                post.comments_count = Number(result.comments_count ?? post.comments.length);
            }
            if (announcementState.editingCommentKey === `${postId}:${commentId}`) {
                announcementState.editingCommentKey = null;
            }
            applyFilters();
            window.showCitizenToast?.('Comment deleted.', false, 2200);
        } catch (error) {
            console.error('Citizen comment deletion failed:', error);
            window.showCitizenToast?.(error.message || 'Failed to delete comment.', true, 3200);
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

    document.querySelectorAll('[data-announcement-filter-toggle]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            const key = button.dataset.announcementFilterToggle || '';
            const menu = document.querySelector(`[data-announcement-filter-menu="${key}"]`);
            if (!menu) return;
            const willOpen = menu.hasAttribute('hidden');

            document.querySelectorAll('[data-announcement-filter-menu]').forEach((otherMenu) => {
                otherMenu.setAttribute('hidden', '');
            });
            document.querySelectorAll('[data-announcement-filter-toggle]').forEach((otherButton) => {
                otherButton.setAttribute('aria-expanded', 'false');
            });

            if (willOpen) {
                menu.removeAttribute('hidden');
                button.setAttribute('aria-expanded', 'true');
                syncAnnouncementFilterMenus();
            }
        });
    });

    document.querySelectorAll('[data-announcement-filter-target]').forEach((option) => {
        option.addEventListener('click', () => {
            const select = document.getElementById(option.dataset.announcementFilterTarget || '');
            if (!select) return;

            select.value = option.dataset.announcementFilterValue || '';
            select.dispatchEvent(new Event('change', { bubbles: true }));
            document.querySelectorAll('[data-announcement-filter-menu]').forEach((menu) => menu.setAttribute('hidden', ''));
            document.querySelectorAll('[data-announcement-filter-toggle]').forEach((button) => button.setAttribute('aria-expanded', 'false'));
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('#section-announcements .citizen-announcements-filter-control')) {
            document.querySelectorAll('[data-announcement-filter-menu]').forEach((menu) => menu.setAttribute('hidden', ''));
            document.querySelectorAll('[data-announcement-filter-toggle]').forEach((button) => button.setAttribute('aria-expanded', 'false'));
        }
    });

    document.getElementById('citizenAnnouncementMediaDrawer')?.addEventListener('click', (event) => {
        if (event.target.id === 'citizenAnnouncementMediaDrawer') {
            closeMediaDrawer();
        }
    });

    window.citizenAnnouncementSystem = {
        likePost,
        toggleComments,
        addComment,
        beginEditComment,
        cancelEditComment,
        saveComment,
        deleteComment,
        loadAnnouncements,
        openMediaDrawer,
        closeMediaDrawer,
    };

    loadAnnouncements();
});
</script>
