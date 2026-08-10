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
    @if(session('success'))
        <div class="panel-card" style="margin-bottom:16px;border-color:rgba(16,185,129,0.24);background:#ecfdf5;color:#065f46;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="panel-card" style="margin-bottom:16px;border-color:rgba(239,68,68,0.2);background:#fef2f2;color:#991b1b;">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="panel-card" style="margin-bottom:16px;border-color:rgba(239,68,68,0.2);background:#fff7ed;color:#9a3412;">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="panel-grid two" style="margin-bottom:20px;">
        <article class="panel-card">
            <div class="panel-eyebrow">Request Support</div>
            <h3 class="panel-title">Ask the station for a seminar or orientation</h3>
            <p style="color:#64748b;margin-bottom:16px;">Submit a request when your barangay needs BFP support for orientations, seminars, drills, inspections, or other community preparedness activities.</p>
            <form method="POST" action="{{ route('barangay.community-activity-requests.store') }}" class="list-stack">
                @csrf
                <label>
                    <span style="display:block;font-weight:700;margin-bottom:6px;">Request type</span>
                    <select name="request_type" style="width:100%;border:1px solid #e2e8f0;border-radius:12px;padding:12px;">
                        <option value="orientation">Orientation</option>
                        <option value="seminar">Seminar</option>
                        <option value="drill">Drill</option>
                        <option value="inspection">Inspection</option>
                        <option value="community_meeting">Community meeting</option>
                        <option value="other">Other</option>
                    </select>
                </label>
                <label>
                    <span style="display:block;font-weight:700;margin-bottom:6px;">Title</span>
                    <input type="text" name="title" value="{{ old('title') }}" placeholder="Example: Household fire safety orientation" style="width:100%;border:1px solid #e2e8f0;border-radius:12px;padding:12px;">
                </label>
                <label>
                    <span style="display:block;font-weight:700;margin-bottom:6px;">Reason for request</span>
                    <textarea name="description" rows="4" placeholder="Describe the audience, problem, and support needed." style="width:100%;border:1px solid #e2e8f0;border-radius:12px;padding:12px;">{{ old('description') }}</textarea>
                </label>
                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;">
                    <label>
                        <span style="display:block;font-weight:700;margin-bottom:6px;">Preferred date</span>
                        <input type="date" name="preferred_date" value="{{ old('preferred_date') }}" min="2026-08-10" style="width:100%;border:1px solid #e2e8f0;border-radius:12px;padding:12px;">
                    </label>
                    <label>
                        <span style="display:block;font-weight:700;margin-bottom:6px;">Start</span>
                        <input type="time" name="preferred_start_time" value="{{ old('preferred_start_time') }}" style="width:100%;border:1px solid #e2e8f0;border-radius:12px;padding:12px;">
                    </label>
                    <label>
                        <span style="display:block;font-weight:700;margin-bottom:6px;">End</span>
                        <input type="time" name="preferred_end_time" value="{{ old('preferred_end_time') }}" style="width:100%;border:1px solid #e2e8f0;border-radius:12px;padding:12px;">
                    </label>
                </div>
                <label>
                    <span style="display:block;font-weight:700;margin-bottom:6px;">Contact number</span>
                    <input type="text" name="contact_number" value="{{ old('contact_number', auth()->user()->contact_number) }}" placeholder="Optional contact number" style="width:100%;border:1px solid #e2e8f0;border-radius:12px;padding:12px;">
                </label>
                <button type="submit" class="hero-btn hero-btn-primary" style="justify-content:center;">
                    <i class="fa-solid fa-paper-plane"></i>
                    Submit activity request
                </button>
            </form>
        </article>

        <article class="panel-card">
            <div class="panel-eyebrow">Request Status</div>
            <h3 class="panel-title">Recent barangay requests</h3>
            <div class="list-stack">
                @forelse(($communityActivityRequests ?? collect()) as $requestItem)
                    <div class="list-item">
                        <h4>{{ $requestItem->title }}</h4>
                        <p>{{ \Illuminate\Support\Str::limit($requestItem->description, 140) }}</p>
                        <div class="tag-row">
                            <span class="tag medium">{{ strtoupper(str_replace('_', ' ', $requestItem->request_type)) }}</span>
                            <span class="tag {{ $requestItem->status === 'approved' ? 'low' : ($requestItem->status === 'rejected' ? 'high' : 'medium') }}">{{ strtoupper(str_replace('_', ' ', $requestItem->status)) }}</span>
                            <span class="tag low">{{ $requestItem->station?->station_name ?? 'Station 1' }}</span>
                        </div>
                        @if($requestItem->convertedEvent)
                            <p style="margin-top:10px;color:#475569;">Scheduled as {{ $requestItem->convertedEvent->event }} on {{ optional($requestItem->convertedEvent->event_date)->format('M d, Y') }} {{ $requestItem->convertedEvent->schedule ? '· ' . $requestItem->convertedEvent->schedule : '' }}</p>
                        @elseif($requestItem->review_notes)
                            <p style="margin-top:10px;color:#475569;">Review note: {{ $requestItem->review_notes }}</p>
                        @endif
                    </div>
                @empty
                    <div class="empty-state">No community activity requests have been submitted from this barangay yet.</div>
                @endforelse
            </div>
        </article>
    </section>

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
