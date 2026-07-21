<section class="section-card">
    <div class="section-header">
        <div>
            <div class="section-title"><i class="fa-solid fa-images"></i> Station Photos</div>
        </div>
    </div>
    <div class="section-body">
        @if($stationGallery->isEmpty())
            <div class="empty-box">
                <i class="fa-solid fa-image"></i>
                <div>No station photos yet.</div>
            </div>
        @else
            <div class="station-gallery-grid">
                @foreach($stationGallery as $photo)
                    <div class="station-gallery-item">
                        <img src="{{ $photo['url'] }}" alt="Station photo {{ $loop->iteration }}">
                        <div class="station-gallery-caption">{{ $photo['caption'] ?: 'Station photo #' . $loop->iteration }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

<section class="station-grid">
    <div class="section-card">
        <div class="section-header">
        <div>
            <div class="section-title"><i class="fa-solid fa-chart-column"></i> Station Snapshot</div>
        </div>
        </div>
        <div class="section-body">
            <div class="stat-grid">
                <div class="stat-block"><div class="stat-block-label">Station Officers</div><div class="stat-block-value" id="stationOfficersCount">{{ $counts['station_officers'] ?? 0 }}</div></div>
                <div class="stat-block"><div class="stat-block-label">Crew Members</div><div class="stat-block-value" id="stationCrewCount">{{ $counts['crew_members'] ?? 0 }}</div></div>
                <div class="stat-block"><div class="stat-block-label">Equipment Lines</div><div class="stat-block-value" id="stationEquipmentCount">{{ $counts['equipment_lines'] ?? 0 }}</div></div>
                <div class="stat-block"><div class="stat-block-label">Spot Reports</div><div class="stat-block-value" id="spotReportsCount">{{ $counts['spot_reports'] ?? 0 }}</div></div>
                <div class="stat-block"><div class="stat-block-label">Progress Reports</div><div class="stat-block-value" id="progressReportsCount">{{ $counts['progress_reports'] ?? 0 }}</div></div>
                <div class="stat-block"><div class="stat-block-label">Final Reports</div><div class="stat-block-value" id="finalReportsCount">{{ $counts['final_reports'] ?? 0 }}</div></div>
            </div>
        </div>
    </div>
</section>
