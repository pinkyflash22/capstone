@extends('barangay.layouts.app')

@section('active_nav', 'assessment')
@section('page_title', 'Risk Assessment and Zones')
@section('page_subtitle', 'Priority analysis, compliance pressure, and action zoning cues')
@section('hero_title', 'Turn incident history into zone-level prevention priorities.')
@section('hero_copy', 'This view organizes risk by priority and compliance state so the barangay can identify where stricter zoning, awareness, and recurring inspections should be concentrated.')

@section('hero_badges')
    <span class="hero-badge"><i class="fa-solid fa-layer-group"></i> Zone prioritization</span>
    <span class="hero-badge"><i class="fa-solid fa-chart-column"></i> Checklist pressure</span>
    <span class="hero-badge"><i class="fa-solid fa-bullseye"></i> Targeted action zones</span>
@endsection

@section('content')
    <section class="panel-grid three">
        @foreach($priorityMix as $label => $count)
            <article class="panel-card">
                <div class="panel-eyebrow">Priority Level</div>
                <h3 class="panel-title">{{ strtoupper($label) }}</h3>
                <p class="panel-copy">{{ $count }} checklist item(s) are grouped in this band for the barangay.</p>
            </article>
        @endforeach
    </section>

    <section class="panel-grid two">
        <article class="panel-card">
            <div class="panel-eyebrow">Compliance View</div>
            <h3 class="panel-title">Status mix</h3>
            <div class="list-stack">
                @foreach($statusMix as $label => $count)
                    <div class="stat-line">
                        <strong>{{ str_replace('_', ' ', ucfirst($label)) }}</strong>
                        <span class="tag {{ $label }}">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="panel-card">
            <div class="panel-eyebrow">Zone Guidance</div>
            <h3 class="panel-title">Suggested local response framing</h3>
            <div class="list-stack">
                <div class="list-item">
                    <h4>Critical watch zone</h4>
                    <p>Use this for clusters with unresolved critical or high checklist items, repeated fire causes, and recent incident recurrence.</p>
                </div>
                <div class="list-item">
                    <h4>Inspection reinforcement zone</h4>
                    <p>Use this where compliance is pending or in progress, but incidents remain moderate and manageable through patrols.</p>
                </div>
                <div class="list-item">
                    <h4>Community awareness zone</h4>
                    <p>Use this where incident frequency is lower but risk drivers suggest education and household preparedness campaigns should be intensified.</p>
                </div>
            </div>
        </article>
    </section>
@endsection
