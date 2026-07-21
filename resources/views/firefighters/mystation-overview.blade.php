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
