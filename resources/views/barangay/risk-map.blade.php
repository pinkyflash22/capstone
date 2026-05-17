@extends('barangay.layouts.app')

@section('active_nav', 'risk-map')
@section('page_title', 'Risk Map')
@section('page_subtitle', 'Local marker inventory for area scanning and response context')
@section('hero_title', 'Keep a field view of the places and assets that shape barangay risk.')
@section('hero_copy', 'This page mirrors the system’s mapping context by surfacing relevant markers and hazard-linked points that can affect patrol routing and early warning decisions.')

@section('hero_badges')
    <span class="hero-badge"><i class="fa-solid fa-location-dot"></i> Marker inventory</span>
    <span class="hero-badge"><i class="fa-solid fa-school"></i> Exposure points</span>
    <span class="hero-badge"><i class="fa-solid fa-water"></i> Resource awareness</span>
@endsection

@section('content')
    <section class="panel-grid two">
        <article class="panel-card">
            <div class="panel-eyebrow">Map Context</div>
            <h3 class="panel-title">Barangay risk scan</h3>
            <div class="map-card">
                <p class="panel-copy">This barangay page uses the same system design context as the chief view, but keeps the content focused on local field points, response assets, and recent fire-linked markers. Hooking a full live map layer here can be done next on top of this layout.</p>
                <div class="two-col">
                    <div>
                        <div class="tiny-label">Visible Marker Types</div>
                        <div class="tag-row">
                            <span class="tag low">Barangay</span>
                            <span class="tag low">Fire</span>
                            <span class="tag low">School</span>
                        </div>
                    </div>
                    <div>
                        <div class="tiny-label">Support Types</div>
                        <div class="tag-row">
                            <span class="tag low">Hydrant</span>
                            <span class="tag low">Bridge</span>
                            <span class="tag low">Station</span>
                        </div>
                    </div>
                </div>
            </div>
        </article>

        <article class="panel-card">
            <div class="panel-eyebrow">Marker Feed</div>
            <h3 class="panel-title">Latest mapped points</h3>
            @if($markers->isEmpty())
                <div class="empty-state">No marker records are available yet.</div>
            @else
                <div class="list-stack">
                    @foreach($markers->take(7) as $marker)
                        <div class="list-item">
                            <h4>{{ $marker->title ?? 'Untitled marker' }}</h4>
                            <p>{{ $marker->description ?? 'No marker description provided.' }}</p>
                            <div class="tag-row">
                                <span class="tag low">{{ strtoupper($marker->category ?? 'UNKNOWN') }}</span>
                                <span class="tag {{ $marker->status === 'active' ? 'complied' : 'deferred' }}">{{ strtoupper($marker->status ?? 'UNKNOWN') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>
    </section>
@endsection
