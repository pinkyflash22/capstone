<section class="section-card station-photos-card" data-station-photo-count="{{ $stationGallery->count() }}">
    <div class="section-header station-section-header-compact">
        <div>
            <div class="section-title">
                <i class="fa-solid fa-image"></i>
                Station Photos <span class="station-officers-count">&middot; {{ $stationGallery->count() }}</span>
            </div>
        </div>
    </div>
    <div class="section-body">
        @if($stationGallery->isEmpty())
            <div class="empty-box station-empty-compact">
                <i class="fa-solid fa-image"></i>
                <div>No station photos added yet.</div>
            </div>
        @else
            <div class="station-gallery-grid">
                @foreach($stationGallery as $photo)
                    <figure class="station-gallery-item">
                        <img src="{{ $photo['url'] }}" alt="Station photo {{ $loop->iteration }}">
                        <figcaption class="station-gallery-caption">{{ $photo['caption'] ?: 'Station photo ' . $loop->iteration }}</figcaption>
                    </figure>
                @endforeach
            </div>
        @endif
    </div>
</section>
