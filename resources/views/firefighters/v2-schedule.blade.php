@extends('firefighters.v2-layout', [
    'active' => 'schedule',
    'pageTitle' => 'Firefighter Schedule V2',
    'pageSubtitle' => 'Official chief schedules and firefighter personal schedules in one cleaner timeline'
])

@section('title', 'PyroSense · Firefighter Schedule V2')

@section('content')
    <section class="ff2-page-header">
        <span class="ff2-page-header__eyebrow"><i class="fa-solid fa-calendar-days"></i> Duty Calendar</span>
        <h2>My Schedule.</h2>
        <p>This v2 page already separates official station schedules from your personal firefighter entries using the same event data. It is meant to be the cleaner successor of the current schedule page.</p>
    </section>

    <section class="ff2-card">
        <div class="ff2-card__body">
            <div style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;align-items:flex-end;">
                <div>
                    <h3 class="ff2-card__title">Schedule Timeline</h3>
                    <p class="ff2-card__subtitle">{{ $stationName ? 'Official events for ' . $stationName . ' plus your personal firefighter schedule.' : 'Your official and personal schedule records.' }}</p>
                </div>
                <a href="{{ route('firefighter.schedule') }}" class="ff2-button ff2-button--primary"><i class="fa-solid fa-pen-to-square"></i> Open current scheduler</a>
            </div>
            <div class="ff2-list" style="margin-top:18px;">
                @forelse($scheduleEvents as $event)
                    <article class="ff2-list-item">
                        <div>
                            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                                <span class="ff2-pill"><i class="fa-solid fa-tag"></i> {{ $event['source_label'] }}</span>
                                <span class="ff2-pill"><i class="fa-solid fa-wave-square"></i> {{ ucfirst($event['status'] ?? 'planned') }}</span>
                            </div>
                            <h3 style="margin-top:12px;">{{ $event['title'] }}</h3>
                            <p>{{ $event['notes'] ?: 'No extra notes attached to this schedule entry.' }}</p>
                            <div class="ff2-meta">
                                <span>{{ $event['event_date'] ? \Carbon\Carbon::parse($event['event_date'])->format('M d, Y') : 'No date' }}</span>
                                <span>{{ $event['schedule'] }}</span>
                                <span>{{ $event['barangay'] ?: 'No location' }}</span>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="ff2-empty">No schedule items are available yet.</div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
