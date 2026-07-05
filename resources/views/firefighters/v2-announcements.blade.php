@extends('firefighters.v2-layout', [
    'active' => 'announcements',
    'pageTitle' => 'Firefighter Announcements V2',
    'pageSubtitle' => 'Community updates and station posts in a cleaner responder feed'
])

@section('title', 'PyroSense · Firefighter Announcements V2')

@section('content')
    <section class="ff2-page-header">
        <span class="ff2-page-header__eyebrow"><i class="fa-solid fa-bullhorn"></i> Station Feed</span>
        <h2>Announcements.</h2>
        <p>This parallel version keeps the firefighter announcement logic nearby while starting with a cleaner feed presentation. It is a safer staging ground before we fully migrate the current post composer and interactions.</p>
    </section>

    <section class="ff2-stats">
        <article class="ff2-stat"><div class="ff2-stat__label"><span>Total Posts</span><i class="fa-solid fa-newspaper text-[#c94b2a]"></i></div><div class="ff2-stat__value">{{ $postStats['total_posts'] ?? 0 }}</div><div class="ff2-stat__meta">All visible announcement records.</div></article>
        <article class="ff2-stat"><div class="ff2-stat__label"><span>Recent Week</span><i class="fa-solid fa-clock text-[#db9218]"></i></div><div class="ff2-stat__value">{{ $postStats['recent_posts'] ?? 0 }}</div><div class="ff2-stat__meta">Posts created during the last 7 days.</div></article>
        <article class="ff2-stat"><div class="ff2-stat__label"><span>Feed Mode</span><i class="fa-solid fa-rss text-[#124e5d]"></i></div><div class="ff2-stat__value">V2</div><div class="ff2-stat__meta">Parallel firefighter feed for UI testing.</div></article>
        <article class="ff2-stat"><div class="ff2-stat__label"><span>Composer</span><i class="fa-solid fa-pen text-[#1d9b60]"></i></div><div class="ff2-stat__value">Legacy</div><div class="ff2-stat__meta">Current post interactions still live in the existing module.</div></article>
    </section>

    <section class="ff2-card">
        <div class="ff2-card__body">
            <div style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;align-items:flex-end;">
                <div>
                    <h3 class="ff2-card__title">Recent Feed</h3>
                    <p class="ff2-card__subtitle">Latest firefighter posts rendered server-side for a lighter first version of the feed.</p>
                </div>
                <a href="{{ route('firefighter.announcements') }}" class="ff2-button ff2-button--primary"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open current feed</a>
            </div>

            <div class="ff2-list" style="margin-top:18px;">
                @forelse($recentPosts as $post)
                    <article class="ff2-list-item">
                        <div>
                            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                                <span class="ff2-pill"><i class="fa-solid fa-user"></i> {{ trim(($post->user->first_name ?? '') . ' ' . ($post->user->last_name ?? '')) ?: 'BFP Staff' }}</span>
                                <span class="ff2-pill"><i class="fa-solid fa-clock"></i> {{ optional($post->created_at)->diffForHumans() }}</span>
                            </div>
                            <h3 style="margin-top:12px;">{{ $post->title ?? 'Station Update' }}</h3>
                            <p>{{ \Illuminate\Support\Str::limit(strip_tags($post->content ?? $post->description ?? 'No content preview available.'), 220) }}</p>
                        </div>
                    </article>
                @empty
                    <div class="ff2-empty">No recent posts are available yet.</div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
