<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>GIS routing comparison | Urbiztondo</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="{{ asset('css/gis-demo.css') }}?v={{ substr(hash_file('sha256', public_path('css/gis-demo.css')), 0, 12) }}">
    <script defer src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script defer src="{{ asset('js/gis-demo.js') }}?v={{ substr(hash_file('sha256', public_path('js/gis-demo.js')), 0, 12) }}"></script>
</head>
<body data-provider="{{ $provider }}" class="{{ $provider === 'both' ? '' : 'single single-'.$provider }}">
<main>
    <header>
        <div><div class="eyebrow">URBIZTONDO • GIS DEMONSTRATION</div><h1>{{ $provider === 'both' ? 'One map dataset. Three routing APIs.' : (['geoapify' => 'Geoapify', 'osrm' => 'OSRM', 'openrouteservice' => 'OpenRouteService'][$provider].' routing demonstration') }}</h1><p>The same map dataset and marker selections for a consistent routing comparison.</p></div>
        <button id="capture-mode" type="button">Screenshot view</button>
    </header>
    <nav aria-label="Demo views"><a href="{{ route('gis-demo.index') }}">Compare all three</a> · <a href="{{ route('gis-demo.current') }}">Geoapify</a> · <a href="{{ route('gis-demo.alternative') }}">OSRM</a> · <a href="{{ route('gis-demo.openrouteservice') }}">OpenRouteService</a> · <a href="{{ route('gis-demo.mapping') }}">Mapping comparison</a></nav>
    <section class="controls" aria-label="Route comparison controls">
        <label>Starting marker<select id="origin" disabled></select></label>
        <label>Destination marker<select id="destination" disabled></select></label>
        <button id="compare" type="button" disabled>Compare routes</button>
        <button id="refresh-data" type="button">Refresh map data</button>
        <label class="check"><input id="sync" type="checkbox" checked> Sync map views</label>
    </section>
    <div class="context"><span id="data-status" role="status">Loading your map data…</span><span id="route-context">Choose the same marker pair for every API.</span></div>
    <section class="maps" aria-label="Routing provider comparison">
        <article class="provider geoapify"><div class="panel-title"><div><span class="tag">CURRENT API</span><h2>Geoapify</h2></div><span class="route-state" id="geoapify-status">Ready</span></div>
            <div id="geoapify-map" class="map" aria-label="Geoapify route map"></div>
            <div class="metrics" id="geoapify-metrics"><div>Distance<strong>—</strong></div><div>Driving estimate<strong>—</strong></div><div>Request elapsed<strong>—</strong></div></div>
        </article>
        <article class="provider osrm"><div class="panel-title"><div><span class="tag">ALTERNATIVE API</span><h2>OSRM</h2></div><span class="route-state" id="osrm-status">Ready</span></div>
            <div id="osrm-map" class="map" aria-label="OSRM route map"></div>
            <div class="metrics" id="osrm-metrics"><div>Distance<strong>—</strong></div><div>Driving estimate<strong>—</strong></div><div>Request elapsed<strong>—</strong></div></div>
        </article>
        <article class="provider openrouteservice"><div class="panel-title"><div><span class="tag">ALTERNATIVE API</span><h2>OpenRouteService</h2></div><span class="route-state" id="openrouteservice-status">Ready</span></div>
            <div id="openrouteservice-map" class="map" aria-label="OpenRouteService route map"></div>
            <div class="metrics" id="openrouteservice-metrics"><div>Distance<strong>—</strong></div><div>Driving estimate<strong>—</strong></div><div>Request elapsed<strong>—</strong></div></div>
        </article>
    </section>
    <footer>
        <div class="legend"><span><i style="background:#274e93"></i>Station</span><span><i style="background:#dc4848"></i>Fire / incident</span><span><i style="background:#168cbb"></i>Hydrant</span><span><i style="background:#7b5ab7"></i>Barangay</span><span><i style="background:#c88b24"></i>Risk / other</span><span><b>A</b> Start</span><span><b>B</b> Destination</span></div>
        <p>Map data: your existing authenticated marker feed and saved boundaries. All panels use Leaflet + OpenStreetMap. Driving estimates are not measured emergency response times.</p>
        <p id="measurement-note">Request elapsed includes this application, network and API processing. Geoapify uses the existing routing endpoint and its fallback cache; OSRM uses the public FOSSGIS endpoint; OpenRouteService uses its driving-car directions API.</p>
        <p>Routing by <a href="https://openrouteservice.org/" target="_blank" rel="noopener">openrouteservice</a> / HeiGIT. Data © OpenStreetMap contributors.</p>
        <p>Map data © OpenStreetMap contributors · <a href="https://www.openstreetmap.org/" target="_blank" rel="noopener">Fix the map</a> · <a href="https://routing.openstreetmap.de/about.html" target="_blank" rel="noopener">OSRM service information</a> · <span id="captured-at"></span></p>
    </footer>
</main>
</body>
</html>
