@extends('barangay.layouts.app')

@section('active_nav', 'awareness')
@section('page_title', 'Community Awareness')
@section('page_subtitle', 'Preparedness messaging, reminders, and local public-facing guidance')
@section('hero_title', 'Translate risk records into messages the community can actually use.')
@section('hero_copy', 'Preparedness works better when awareness follows the real hazard pattern. This page keeps recent advisories and local campaign cues in one place for barangay-level communication.')

@section('hero_badges')
    <span class="hero-badge"><i class="fa-solid fa-bullhorn"></i> Public messaging</span>
    <span class="hero-badge"><i class="fa-solid fa-people-group"></i> Community drills</span>
    <span class="hero-badge"><i class="fa-solid fa-house-chimney-user"></i> Household readiness</span>
@endsection

@section('content')
    <section class="panel-grid two">
        <article class="panel-card">
            <div class="panel-eyebrow">Preparedness Feed</div>
            <h3 class="panel-title">Recent system announcements</h3>
            @if($latestPosts->isEmpty())
                <div class="empty-state">No awareness posts are available yet.</div>
            @else
                <div class="list-stack">
                    @foreach($latestPosts as $post)
                        <div class="list-item">
                            <h4>{{ \Illuminate\Support\Str::limit(strip_tags($post->content ?? 'Barangay announcement'), 72) }}</h4>
                            <p>{{ \Illuminate\Support\Str::limit(strip_tags($post->content ?? ''), 180) }}</p>
                            <div class="tag-row">
                                <span class="tag low">{{ $post->user?->first_name ?? 'System' }}</span>
                                <span class="tag medium">{{ optional($post->created_at)->format('M d, Y') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>

        <article class="panel-card">
            <div class="panel-eyebrow">Campaign Cues</div>
            <h3 class="panel-title">Awareness topics suggested by local risk</h3>
            <div class="list-stack">
                @forelse($predictionChecklists->take(5) as $item)
                    <div class="list-item">
                        <h4>{{ $item->title }}</h4>
                        <p>{{ $item->ai_output }}</p>
                        <div class="tag-row">
                            <span class="tag {{ $item->priority_level }}">{{ strtoupper($item->priority_level) }}</span>
                            <span class="tag {{ $item->status }}">{{ str_replace('_', ' ', strtoupper($item->status)) }}</span>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">No AI or barangay checklist guidance is available yet for awareness planning.</div>
                @endforelse
            </div>
        </article>
    </section>
@endsection
