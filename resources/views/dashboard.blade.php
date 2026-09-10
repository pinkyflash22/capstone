<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="anonymous-user-id" content="{{ $anonymousUserId ?? '' }}">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <title>PyroSense - Fire Emergency Response System</title>
    
    @vite(['resources/css/landing/styles.css'])
    @include('partials.pwa-head')
</head>
<body>
    <section class="mobile-entry-page">
        <div class="mobile-entry-card">
            <div class="mobile-entry-brand">
                <div class="logo-icon mobile-entry-logo">
                    @include('partials.app-logo', ['class' => 'logo-image'])
                </div>
                <span class="mobile-entry-title">Pyro<span>Sense</span></span>
            </div>

            <p class="mobile-entry-copy">Quick access for mobile users.</p>

            <div class="mobile-entry-actions">
                <button type="button" class="mobile-entry-btn mobile-entry-btn--primary" data-anonymous-report-trigger>
                    <i class="fa-solid fa-user-secret"></i>
                    Report Anonymously
                </button>
                <a href="tel:911" class="mobile-entry-btn mobile-entry-btn--call" data-emergency-call data-pyro-landing-agora-call>
                    <i class="fa-solid fa-phone-volume"></i>
                    Call Emergency
                </a>
                <a href="{{ route('auth.login') }}" class="mobile-entry-btn mobile-entry-btn--secondary">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Sign In
                </a>
                <button type="button" class="mobile-entry-btn mobile-entry-btn--ghost" data-mobile-open-map>
                    <i class="fa-solid fa-map-location-dot"></i>
                    View Map
                </button>
            </div>
        </div>
    </section>

    <div id="emergencyCallConfirm" class="emergency-call-confirm" aria-hidden="true">
        <div class="emergency-call-confirm__backdrop" data-emergency-call-cancel></div>
        <section class="emergency-call-confirm__card" role="dialog" aria-modal="true" aria-labelledby="emergencyCallConfirmTitle">
            <div class="emergency-call-confirm__icon">
                <i class="fa-solid fa-phone-volume"></i>
            </div>
            <h2 id="emergencyCallConfirmTitle">Call emergency?</h2>
            <p id="emergencyCallConfirmMessage">This will open your phone app and dial 911.</p>
            <div id="emergencyCallLocationPanel" class="emergency-call-confirm__panel" hidden>
                <div class="emergency-call-confirm__detail">
                    <span class="emergency-call-confirm__label">Current coordinates</span>
                    <strong id="emergencyCallCoords">Unavailable</strong>
                </div>
                <div class="emergency-call-confirm__detail" id="emergencyCallCachedLocationWrap" hidden>
                    <span class="emergency-call-confirm__label">Cached area</span>
                    <strong id="emergencyCallCachedLocation">Unavailable</strong>
                </div>
                <div class="emergency-call-confirm__detail emergency-call-confirm__detail--warning">
                    <span class="emergency-call-confirm__label">Instruction</span>
                    <strong>Tell the BFP dispatcher this location during the call.</strong>
                </div>
                <p class="emergency-call-confirm__note">PyroSense cannot automatically transmit GPS through a normal phone call.</p>
            </div>
            <div class="emergency-call-confirm__actions">
                <button type="button" class="emergency-call-confirm__btn emergency-call-confirm__btn--ghost" data-emergency-call-cancel>
                    Cancel
                </button>
                <button type="button" class="emergency-call-confirm__btn emergency-call-confirm__btn--ghost" id="emergencyCallCopyCoords" hidden>
                    Copy Coordinates
                </button>
                <button type="button" class="emergency-call-confirm__btn emergency-call-confirm__btn--secondary" id="emergencyCallBfpButton" hidden>
                    Call BFP
                </button>
                <button type="button" class="emergency-call-confirm__btn emergency-call-confirm__btn--primary" data-emergency-call-confirm>
                    Call 911
                </button>
            </div>
        </section>
    </div>

    <div id="mobileMapModal" class="mobile-map-modal" aria-hidden="true">
        <div class="mobile-map-modal__backdrop" data-mobile-map-close></div>
        <section class="mobile-map-modal__sheet" role="dialog" aria-modal="true" aria-labelledby="mobileMapTitle">
            <header class="mobile-map-modal__header">
                <div>
                    <p class="mobile-map-modal__eyebrow">Emergency resources</p>
                    <h2 id="mobileMapTitle">Nearby map</h2>
                </div>
                <button type="button" class="mobile-map-modal__close" data-mobile-map-close aria-label="Close map preview">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </header>
            <div class="mobile-map-modal__controls" aria-label="Map resource filters">
                <button type="button" class="mobile-map-modal__chip is-active" data-mobile-map-filter="all">
                    <i class="fa-solid fa-layer-group"></i>
                    All
                </button>
                <button type="button" class="mobile-map-modal__chip" data-mobile-map-filter="stations">
                    <i class="fa-solid fa-fire-extinguisher"></i>
                    Stations
                </button>
                <button type="button" class="mobile-map-modal__chip" data-mobile-map-filter="hydrants">
                    <i class="fa-solid fa-fire-hydrant"></i>
                    Hydrants
                </button>
            </div>
            <div id="mobileMapPreview" class="mobile-map-modal__map"></div>
            <p class="mobile-map-modal__hint">Public preview of station and hydrant records. Operational incident details remain available only to authorized users.</p>
        </section>
    </div>

    <div class="desktop-landing-shell">
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="/" class="logo">
                <div class="logo-icon">
                    @include('partials.app-logo', ['class' => 'logo-image'])
                </div>
                <span class="logo-text">Pyro<span>Sense</span></span>
            </a>

            <div class="nav-menu">
                <a href="/" class="nav-link">Home</a>
                <a href="#" class="nav-link" onclick="openAboutModal('aboutBfpUrbiztondo'); return false;">About</a>
                <a href="#" class="nav-link" onclick="openAboutModal('faq'); return false;">FAQ</a>
                <a href="#" class="nav-link" onclick="openAboutModal('contact'); return false;">Contact</a>
            </div>

            <div class="nav-actions">
                <a href="{{ route('auth.login') }}" class="btn-login">Login</a>
                <a class="btn-emergency" href="tel:911" data-emergency-call data-pyro-landing-agora-call>
                    <i class="fa-solid fa-phone-alt"></i>
                    Emergency
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-grid">
                <div class="hero-content reveal-on-scroll" data-reveal="left">
                    <div class="hero-badge reveal-on-scroll" data-reveal="up" data-reveal-delay="0">
                        <span class="live-dot" aria-hidden="true"></span>
                        Live PyroSense operational map
                    </div>
                    
                    <h1 class="hero-title reveal-on-scroll" data-reveal="up" data-reveal-delay="120">
                        Fire response data,<br>
                        <span>grounded in your community.</span>
                    </h1>
                    
                    <p class="hero-description reveal-on-scroll" data-reveal="up" data-reveal-delay="200">
                        Explore verified records already stored in PyroSense—from mapped stations and hydrants
                        to reported incidents—then find the closest available public resource.
                    </p>

                    <div class="hero-actions reveal-on-scroll" data-reveal="up" data-reveal-delay="240">
                        <button type="button" class="hero-action-btn hero-action-btn--primary" data-anonymous-report-trigger>
                            <i class="fa-solid fa-user-secret"></i>
                            Report Anonymously
                        </button>
                        <a href="#map" class="hero-action-btn hero-action-btn--ghost">
                            <i class="fa-solid fa-map-location-dot"></i>
                            View Map
                        </a>
                    </div>

                    <!-- Search Card -->
                    <div class="search-card reveal-on-scroll" data-reveal="up" data-reveal-delay="280">
                        <div class="search-title">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            Find Fire Station Near You
                        </div>
                        
                        <div class="search-input-group">
                            <div class="search-input-wrapper">
                                <i class="fa-solid fa-location-dot search-input-icon"></i>
                                <input 
                                    type="text" 
                                    class="search-input" 
                                    id="locationSearch"
                                    placeholder="Enter your location (e.g., Lingayen)"
                                >
                            </div>
                            <button class="btn-search" id="searchBtn">
                                Search
                            </button>
                        </div>
                        
                        <button class="location-trigger" id="locationBtn">
                            <i class="fa-solid fa-crosshairs"></i>
                            Use my current location
                        </button>
                    </div>

                    <!-- Stats -->
                    <div class="stats-grid">
                        <div class="stat-card reveal-on-scroll" data-reveal="up" data-reveal-delay="340">
                            <div class="stat-icon"><i class="fa-solid fa-building-shield"></i></div>
                            <div><div class="stat-value" id="totalStations">{{ number_format($totalStations ?? 0) }}</div>
                            <div class="stat-label">Mapped stations</div></div>
                        </div>
                        <div class="stat-card reveal-on-scroll" data-reveal="up" data-reveal-delay="420">
                            <div class="stat-icon"><i class="fa-solid fa-fire-hydrant"></i></div>
                            <div><div class="stat-value" id="totalHydrants">{{ number_format($totalHydrants ?? 0) }}</div>
                            <div class="stat-label">Mapped hydrants</div></div>
                        </div>
                        <div class="stat-card reveal-on-scroll" data-reveal="up" data-reveal-delay="500">
                            <div class="stat-icon"><i class="fa-solid fa-fire-flame-curved"></i></div>
                            <div><div class="stat-value">{{ number_format($totalIncidents ?? 0) }}</div>
                            <div class="stat-label">Incident records</div></div>
                        </div>
                    </div>
                </div>

                <div class="hero-image reveal-on-scroll hero-parallax-target" data-reveal="right" data-parallax data-parallax-speed="0.075">
                    <span class="hero-orbit hero-orbit--one" data-parallax data-parallax-speed="-0.04" aria-hidden="true"></span>
                    <span class="hero-orbit hero-orbit--two" data-parallax data-parallax-speed="0.12" aria-hidden="true"></span>
                    <div class="landing-slider" data-landing-slider aria-label="Featured BFP media">
                        <div class="landing-slider__track">
                            @forelse($landingSlides ?? [] as $slide)
                                <article class="landing-slider__slide {{ $loop->first ? 'is-active' : '' }}" data-landing-slide aria-hidden="{{ $loop->first ? 'false' : 'true' }}">
                                    @if($slide->type === 'video')
                                        <video class="landing-slider__media" src="{{ $slide->media_url }}" @if($slide->poster_url) poster="{{ $slide->poster_url }}" @endif muted playsinline controls preload="metadata"></video>
                                        <span class="landing-slider__kind"><i class="fa-solid fa-play"></i> Featured video</span>
                                    @else
                                        <img class="landing-slider__media" src="{{ $slide->media_url }}" alt="{{ $slide->title }}" {{ $loop->first ? 'fetchpriority=high' : 'loading=lazy' }}>
                                    @endif
                                    <div class="landing-slider__caption">
                                        <h2>{{ $slide->title }}</h2>
                                        @if($slide->subtitle)<p>{{ $slide->subtitle }}</p>@endif
                                        @if($slide->button_label && $slide->button_url)
                                            <a href="{{ $slide->button_url }}" class="landing-slider__link" @if(str_starts_with($slide->button_url, 'http')) target="_blank" rel="noopener noreferrer" @endif>{{ $slide->button_label }} <i class="fa-solid fa-arrow-right"></i></a>
                                        @endif
                                    </div>
                                </article>
                            @empty
                                <article class="landing-slider__slide is-active" data-landing-slide aria-hidden="false">
                                    <img class="landing-slider__media" src="https://images.unsplash.com/photo-1582139329536-e7284fece509?w=800" alt="Firefighter" fetchpriority="high">
                                    <div class="landing-slider__caption"><h2>Prepared when every second matters.</h2><p>Public safety resources, connected through PyroSense.</p></div>
                                </article>
                            @endforelse
                        </div>
                        @if(($landingSlides ?? collect())->count() > 1)
                            <button type="button" class="landing-slider__arrow landing-slider__arrow--prev" data-slider-prev aria-label="Previous slide"><i class="fa-solid fa-chevron-left"></i></button>
                            <button type="button" class="landing-slider__arrow landing-slider__arrow--next" data-slider-next aria-label="Next slide"><i class="fa-solid fa-chevron-right"></i></button>
                            <div class="landing-slider__dots" role="tablist" aria-label="Choose featured media">
                                @foreach($landingSlides as $slide)<button type="button" class="landing-slider__dot {{ $loop->first ? 'is-active' : '' }}" data-slider-dot="{{ $loop->index }}" aria-label="Show slide {{ $loop->iteration }}"></button>@endforeach
                            </div>
                        @endif
                    </div>
                    <div class="hero-image-badge">
                        <span class="hero-image-badge__icon"><i class="fa-solid fa-database"></i></span>
                        <span>
                            <p>Connected to system records</p>
                            <small>{{ $latestRecordAt ? 'Latest update '.\Illuminate\Support\Carbon::parse($latestRecordAt)->diffForHumans() : 'Waiting for the first record' }}</small>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="container">
            <div class="section-header reveal-on-scroll" data-reveal="up">
                <h2 class="section-title">Emergency Resources Locator</h2>
                <p class="section-subtitle">
                    Browse current public station and hydrant records from PyroSense. Click any marker for the details available in its source record.
                </p>
            </div>

            <!-- Map Controls -->
            <div class="map-controls reveal-on-scroll" data-reveal="up" data-reveal-delay="80">
                <button class="map-toggle-btn active" type="button" data-landing-map-filter="all">
                    <i class="fa-solid fa-layer-group"></i>
                    All
                </button>
                <button class="map-toggle-btn" type="button" data-landing-map-filter="stations">
                    <i class="fa-solid fa-fire-extinguisher"></i>
                    Stations
                </button>
                <button class="map-toggle-btn" type="button" data-landing-map-filter="hydrants">
                    <i class="fa-solid fa-fire-hydrant"></i>
                    Hydrants
                </button>
            </div>

            <div class="map-wrapper reveal-on-scroll" data-reveal="up" data-reveal-delay="160">
                <div id="map"></div>
            </div>

            <!-- Nearby Stations -->
            <div class="nearby-stations reveal-on-scroll" data-reveal="up" data-reveal-delay="220">
                <div class="nearby-title">
                    <i class="fa-solid fa-location-dot" style="color: #dc2626;"></i>
                    Nearest Fire Stations
                </div>
                <div id="stationsLoading" class="loading-spinner" style="display: none;">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <p>Loading stations...</p>
                </div>
                <div class="station-list" id="nearbyStations">
                    <!-- Dynamically populated by JavaScript -->
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="section-header reveal-on-scroll" data-reveal="up">
                <span class="section-kicker">Built from operational records</span>
                <h2 class="section-title">What you can do in PyroSense</h2>
                <p class="section-subtitle">
                    These tools use the same mapped resources and reports maintained inside the platform.
                </p>
            </div>

            <div class="features-grid">
                <div class="feature-card reveal-on-scroll" data-reveal="up" data-reveal-delay="0" data-parallax data-parallax-speed="0.018">
                    <div class="feature-icon">
                        <i class="fa-solid fa-truck"></i>
                    </div>
                    <h3 class="feature-title">Report an incident</h3>
                    <p class="feature-description">
                        Send a fire report anonymously with a map location and supporting details.
                    </p>
                </div>

                <div class="feature-card reveal-on-scroll" data-reveal="up" data-reveal-delay="100" data-parallax data-parallax-speed="-0.014">
                    <div class="feature-icon">
                        <i class="fa-solid fa-map-location-dot"></i>
                    </div>
                    <h3 class="feature-title">Locate resources</h3>
                    <p class="feature-description">
                        Browse the station and hydrant records approved for the public map.
                    </p>
                </div>

                <div class="feature-card reveal-on-scroll" data-reveal="up" data-reveal-delay="180" data-parallax data-parallax-speed="0.012">
                    <div class="feature-icon">
                        <i class="fa-solid fa-location-crosshairs"></i>
                    </div>
                    <h3 class="feature-title">Find the nearest station</h3>
                    <p class="feature-description">
                        Use your location to sort real station records by straight-line distance.
                    </p>
                </div>

                <div class="feature-card reveal-on-scroll" data-reveal="up" data-reveal-delay="260" data-parallax data-parallax-speed="-0.018">
                    <div class="feature-icon">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h3 class="feature-title">Access your dashboard</h3>
                    <p class="feature-description">
                        Sign in to view the tools and records available for your assigned role.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container reveal-on-scroll" data-reveal="up">
            <h2 class="cta-title">One map. Real records. Faster context.</h2>
            <p class="cta-description">
                Open the public resource map now, or sign in to continue to your PyroSense workspace.
            </p>
            <div class="cta-buttons">
                <a href="{{ route('auth.login') }}" class="btn-primary">
                    <i class="fa-solid fa-user"></i>
                    Access Dashboard
                </a>
                <button class="btn-secondary" onclick="openAboutModal('contact'); return false;">
                    <i class="fa-solid fa-headset"></i>
                    Contact Us
                </button>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid reveal-on-scroll" data-reveal="up">
                <div class="footer-col">
                    <h4>Emergency</h4>
                    <ul class="footer-links">
                        <li><a href="tel:911" data-emergency-call data-pyro-landing-agora-call><i class="fa-solid fa-phone-alt"></i> 911 (Emergency)</a></li>
                        <li><a href="tel:09171891611"><i class="fa-solid fa-phone"></i> 0917-189-1611 (Urbiztondo FS)</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Platform</h4>
                    <ul class="footer-links">
                        <li><a href="#map"><i class="fa-solid fa-map"></i> Resource map</a></li>
                        <li><a href="{{ route('auth.login') }}"><i class="fa-solid fa-right-to-bracket"></i> Sign in</a></li>
                        <li><a href="#" onclick="openAboutModal('faq'); return false;"><i class="fa-solid fa-question-circle"></i> FAQ</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Data shown</h4>
                    <p class="footer-copy">Counts come from PyroSense records. The public map shows current station and hydrant entries without exposing protected operational layers.</p>
                </div>

                <div class="footer-col">
                    <h4>Public reference</h4>
                    <ul class="footer-links">
                        <li><i class="fa-solid fa-location-dot"></i> Urbiztondo, Pangasinan</li>
                        <li><a href="https://region1.bfp.gov.ph/contact-us/" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-arrow-up-right-from-square"></i> BFP Region 1 directory</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} PyroSense Emergency Response System. Data reflects the latest records available to the platform.</p>
            </div>
        </div>
    </footer>
    </div>

    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        window.PyroSenseVoiceCall = {
            configured: @json(filled(config('services.agora.app_id')) && filled(config('services.agora.app_certificate'))),
            role: @json($anonymousUserId ? 'anonymous' : ''),
            userId: @json($anonymousUserId ? (int) $anonymousUserId : null),
            stationId: null,
            pollIntervalMs: @json((int) config('services.voice_calls.poll_interval_ms', 2000)),
            routes: {
                store: @json(route('voice-calls.store')),
                incoming: @json(route('voice-calls.incoming')),
                stream: @json(route('voice-calls.stream')),
                show: @json(url('/voice-calls/__CALL__')),
                seen: @json(url('/voice-calls/__CALL__/seen')),
                accept: @json(url('/voice-calls/__CALL__/accept')),
                reject: @json(url('/voice-calls/__CALL__/reject')),
                end: @json(url('/voice-calls/__CALL__/end')),
                createIncident: @json(url('/voice-calls/__CALL__/create-incident')),
                flagPrank: @json(url('/voice-calls/__CALL__/flag-prank')),
                token: @json(url('/voice-calls/__CALL__/token')),
                anonymousSession: @json(route('voice-calls.anonymous-session')),
            },
        };
    </script>
    @vite(['resources/js/landing/main.js'])
    @vite(['resources/css/voice-calls.css', 'resources/js/voice-calls.js'])
    @include('partials.pwa-body', ['showPwaInstallButton' => true])
</body>
</html>
