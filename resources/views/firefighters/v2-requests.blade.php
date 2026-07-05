@extends('firefighters.v2-layout', [
    'active' => 'requests',
    'pageTitle' => 'Firefighter Requests V2',
    'pageSubtitle' => 'Assigned FSIC inspection requests in a cleaner responder workflow'
])

@section('title', 'PyroSense · Firefighter Requests V2')

@section('content')
    <section class="ff2-page-header">
        <span class="ff2-page-header__eyebrow"><i class="fa-solid fa-clipboard-check"></i> Inspection Queue</span>
        <h2>Assigned Requests.</h2>
        <p>This page reuses the assigned FSIC request dataset already tied to your station officer account. The presentation is simplified here so the queue is easier to scan before we rebuild the full inspection workflow.</p>
    </section>

    <section class="ff2-stats">
        <article class="ff2-stat"><div class="ff2-stat__label"><span>Total Queue</span><i class="fa-solid fa-layer-group text-[#c94b2a]"></i></div><div class="ff2-stat__value">{{ $assignedFsicRequests->count() }}</div><div class="ff2-stat__meta">Requests already assigned to your inspection load.</div></article>
        <article class="ff2-stat"><div class="ff2-stat__label"><span>For Inspection</span><i class="fa-solid fa-hourglass-half text-[#db9218]"></i></div><div class="ff2-stat__value">{{ $assignedFsicRequests->where('status', 'for_inspection')->count() }}</div><div class="ff2-stat__meta">Waiting for field inspection action.</div></article>
        <article class="ff2-stat"><div class="ff2-stat__label"><span>Inspected</span><i class="fa-solid fa-clipboard-list text-[#124e5d]"></i></div><div class="ff2-stat__value">{{ $assignedFsicRequests->where('status', 'inspected')->count() }}</div><div class="ff2-stat__meta">Already inspected and awaiting next step.</div></article>
        <article class="ff2-stat"><div class="ff2-stat__label"><span>Approved</span><i class="fa-solid fa-badge-check text-[#1d9b60]"></i></div><div class="ff2-stat__value">{{ $assignedFsicRequests->where('status', 'approved')->count() }}</div><div class="ff2-stat__meta">Approved inspection requests.</div></article>
    </section>

    <section class="ff2-card">
        <div class="ff2-card__body">
            <div style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;align-items:flex-end;">
                <div>
                    <h3 class="ff2-card__title">Inspection Queue Cards</h3>
                    <p class="ff2-card__subtitle">This v2 page keeps the current request logic intact while giving you a parallel, easier-to-scan queue preview.</p>
                </div>
                <a href="{{ route('firefighter.equipments') }}" class="ff2-button ff2-button--primary"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open current workflow</a>
            </div>
            <div class="ff2-list" style="margin-top:18px;">
                @forelse($assignedFsicRequests as $request)
                    <article class="ff2-list-item">
                        <div>
                            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                                <span class="ff2-pill"><i class="fa-solid fa-building"></i> {{ $request->business_name ?? 'Business request' }}</span>
                                <span class="ff2-pill"><i class="fa-solid fa-signal"></i> {{ strtoupper(str_replace('_', ' ', $request->status ?? 'pending')) }}</span>
                            </div>
                            <h3 style="margin-top:12px;">Request #{{ $request->id }}</h3>
                            <p>{{ $request->purpose ?? 'Inspection request linked to your station assignment.' }}</p>
                            <div class="ff2-meta">
                                <span>{{ trim(($request->user->first_name ?? '') . ' ' . ($request->user->last_name ?? '')) ?: 'Applicant' }}</span>
                                <span>{{ optional($request->date_requested)->format('M d, Y') ?: optional($request->created_at)->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="ff2-empty">No assigned requests are waiting in your queue.</div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
