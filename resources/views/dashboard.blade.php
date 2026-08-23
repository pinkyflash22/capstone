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
                <button type="button" class="mobile-map-modal__chip" data-mobile-map-filter="incidents">
                    <i class="fa-solid fa-fire-flame-curved"></i>
                    Incidents
                </button>
                <button type="button" class="mobile-map-modal__chip" data-mobile-map-filter="barangay">
                    <i class="fa-solid fa-people-roof"></i>
                    Barangay
                </button>
                <button type="button" class="mobile-map-modal__chip" data-mobile-map-filter="risks">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    Risk areas
                </button>
                <button type="button" class="mobile-map-modal__chip" data-mobile-map-filter="other">
                    <i class="fa-solid fa-location-dot"></i>
                    Other
                </button>
            </div>
            <div id="mobileMapPreview" class="mobile-map-modal__map"></div>
            <p class="mobile-map-modal__hint">Preview only: resources, incidents, boundaries, and fire-risk areas. No firefighter routes shown.</p>
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
                        <i class="fa-solid fa-shield"></i>
                        Trusted by 25+ Municipalities
                    </div>
                    
                    <h1 class="hero-title reveal-on-scroll" data-reveal="up" data-reveal-delay="120">
                        Find Your Nearest<br>
                        <span>Fire Station</span> Instantly
                    </h1>
                    
                    <p class="hero-description reveal-on-scroll" data-reveal="up" data-reveal-delay="200">
                        Real-time location tracking, emergency alerts, and quick access 
                        to fire stations and hydrants in Pangasinan. Your safety is our priority.
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
                            <div class="stat-value" id="totalStations">{{ $totalStations ?? 0 }}</div>
                            <div class="stat-label">Active Stations</div>
                        </div>
                        <div class="stat-card reveal-on-scroll" data-reveal="up" data-reveal-delay="420">
                            <div class="stat-value" id="totalHydrants">{{ $totalHydrants ?? 0 }}</div>
                            <div class="stat-label">Fire Hydrants</div>
                        </div>
                        <div class="stat-card reveal-on-scroll" data-reveal="up" data-reveal-delay="500">
                            <div class="stat-value">24/7</div>
                            <div class="stat-label">Emergency</div>
                        </div>
                    </div>
                </div>

                <div class="hero-image reveal-on-scroll hero-parallax-target" data-reveal="right">
                    <img src="https://images.unsplash.com/photo-1582139329536-e7284fece509?w=800" alt="Firefighter">
                    <div class="hero-image-badge">
                        <p><i class="fa-solid fa-check-circle" style="color: #dc2626;"></i> 4.2min Average Response</p>
                        <small>Across all stations</small>
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
                    Interactive map showing stations, hydrants, incidents, barangay halls, risk areas, boundaries, and other resources. 
                    Click on any marker for details.
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
                <button class="map-toggle-btn" type="button" data-landing-map-filter="incidents">
                    <i class="fa-solid fa-fire-flame-curved"></i>
                    Incidents
                </button>
                <button class="map-toggle-btn" type="button" data-landing-map-filter="barangay">
                    <i class="fa-solid fa-people-roof"></i>
                    Barangay
                </button>
                <button class="map-toggle-btn" type="button" data-landing-map-filter="risks">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    Risk areas
                </button>
                <button class="map-toggle-btn" type="button" data-landing-map-filter="other">
                    <i class="fa-solid fa-location-dot"></i>
                    Other
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
                <h2 class="section-title">Our Services</h2>
                <p class="section-subtitle">
                    Comprehensive fire protection and emergency response services
                </p>
            </div>

            <div class="features-grid">
                <div class="feature-card reveal-on-scroll" data-reveal="up" data-reveal-delay="0" onclick="openAboutModal('rapidResponse')">
                    <div class="feature-icon">
                        <i class="fa-solid fa-truck"></i>
                    </div>
                    <h3 class="feature-title">Rapid Response</h3>
                    <p class="feature-description">
                        Emergency dispatch within minutes with real-time tracking and optimized routing.
                    </p>
                </div>

                <div class="feature-card reveal-on-scroll" data-reveal="up" data-reveal-delay="100" onclick="openAboutModal('prevention')">
                    <div class="feature-icon">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <h3 class="feature-title">Fire Prevention</h3>
                    <p class="feature-description">
                        Regular inspections, safety audits, and community education programs.
                    </p>
                </div>

                <div class="feature-card reveal-on-scroll" data-reveal="up" data-reveal-delay="180" onclick="openAboutModal('medical')">
                    <div class="feature-icon">
                        <i class="fa-solid fa-kit-medical"></i>
                    </div>
                    <h3 class="feature-title">Emergency Medical</h3>
                    <p class="feature-description">
                        First response medical assistance and rescue operations.
                    </p>
                </div>

                <div class="feature-card reveal-on-scroll" data-reveal="up" data-reveal-delay="260" onclick="openAboutModal('training')">
                    <div class="feature-icon">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <h3 class="feature-title">Safety Training</h3>
                    <p class="feature-description">
                        Fire safety seminars and certification programs for communities.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container reveal-on-scroll" data-reveal="up">
            <h2 class="cta-title">Ready to Ensure Your Safety?</h2>
            <p class="cta-description">
                Join thousands of residents using PyroSense for real-time fire alerts and emergency response.
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
                <!-- Emergency Contact -->
                <div class="footer-col">
                    <h4>Emergency</h4>
                    <ul class="footer-links">
                        <li><a href="tel:911" data-emergency-call data-pyro-landing-agora-call><i class="fa-solid fa-phone-alt"></i> 911 (Emergency)</a></li>
                        <li><a href="tel:+63253091234"><i class="fa-solid fa-phone"></i> (075) 530-1234</a></li>
                        <li><a href="mailto:emergency@pyrosense.gov"><i class="fa-solid fa-envelope"></i> emergency@pyrosense.gov</a></li>
                    </ul>
                </div>

                <!-- About -->
                <div class="footer-col">
                    <h4>About</h4>
                    <ul class="footer-links">
                        <li><a href="#" onclick="openAboutModal('history'); return false;"><i class="fa-solid fa-timeline"></i> Our History</a></li>
                        <li><a href="#" onclick="openAboutModal('mission'); return false;"><i class="fa-solid fa-bullseye"></i> Mission & Vision</a></li>
                        <li><a href="#" onclick="openAboutModal('leadership'); return false;"><i class="fa-solid fa-users"></i> Leadership</a></li>
                        <li><a href="#" onclick="openAboutModal('careers'); return false;"><i class="fa-solid fa-briefcase"></i> Careers</a></li>
                        <li><a href="#" onclick="openAboutModal('faq'); return false;"><i class="fa-solid fa-question-circle"></i> FAQ</a></li>
                    </ul>
                </div>

                <!-- Connect -->
                <div class="footer-col">
                    <h4>Connect</h4>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fa-brands fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fa-brands fa-linkedin-in"></i></a>
                    </div>
                </div>

                <!-- Locations -->
                <div class="footer-col">
                    <h4>Headquarters</h4>
                    <ul class="footer-links">
                        <li><i class="fa-solid fa-location-dot"></i> Lingayen, Pangasinan</li>
                        <li><i class="fa-solid fa-clock"></i> 24/7 Operations</li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>© {{ date('Y') }} PyroSense Emergency Response System. All Rights Reserved.</p>
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
