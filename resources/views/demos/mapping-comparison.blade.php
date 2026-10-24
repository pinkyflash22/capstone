<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mapping library comparison | PyroSense</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@6.14.0/dist/maplibre-gl.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@10.11.0/ol.css">
    <link rel="stylesheet" href="{{ asset('css/mapping-demo.css') }}?v={{ substr(hash_file('sha256', public_path('css/mapping-demo.css')), 0, 12) }}">
    <script type="module" src="{{ asset('js/mapping-demo.js') }}?v={{ substr(hash_file('sha256', public_path('js/mapping-demo.js')), 0, 12) }}"></script>
</head>
<body data-library="{{ $library }}" data-presentation="{{ $presentation }}">
<main>
    <header><div><div class="eyebrow">PYROSENSE • MAPPING LIBRARY COMPARISON</div><h1>{{ $library === 'all' ? 'Three map experiences. One incident dataset.' : ['leaflet'=>'Leaflet + OpenStreetMap', 'maplibre'=>'MapLibre GL JS', 'openlayers'=>'OpenLayers'][$library] }}</h1><p>{{ $presentation === 'showcase' ? 'Street, vector and satellite views for evaluating visual clarity and operational fit.' : 'Controlled comparison: the same OpenStreetMap raster basemap in each library.' }}</p></div><button id="screenshot" type="button">Screenshot view</button></header>
    <nav aria-label="Mapping demo views"><a href="{{ route('gis-demo.mapping') }}">All three</a><a href="{{ route('gis-demo.mapping', ['library'=>'leaflet']) }}">Leaflet + OSM</a><a href="{{ route('gis-demo.mapping', ['library'=>'maplibre']) }}">MapLibre GL JS</a><a href="{{ route('gis-demo.mapping', ['library'=>'openlayers']) }}">OpenLayers</a><a href="{{ route('gis-demo.index') }}">Routing comparison</a><a href="{{ route('gis-demo.weather') }}">Weather comparison</a></nav>
    <section class="controls" aria-label="Map controls"><label>Comparison view<select id="presentation"><option value="showcase" @selected($presentation === 'showcase')>Distinct styles: street / vector / satellite</option><option value="matched" @selected($presentation === 'matched')>Controlled: same basemap</option></select></label><label>Marker category<select id="category"><option value="all">All categories</option></select></label><label><input type="checkbox" id="show-markers" checked> Markers</label><label><input type="checkbox" id="show-boundaries" checked> Saved boundaries</label><label><input type="checkbox" id="sync" checked> Sync map views</label><button id="fit" type="button">Fit data</button><button id="refresh" type="button">Refresh data</button></section>
    <p class="data-status" id="data-status" role="status">Loading mapping libraries and your map data…</p>
    <section class="maps {{ $library === 'all' ? '' : 'single' }}" aria-label="Map display comparison">
        @foreach (['leaflet'=>['Leaflet + OpenStreetMap','CURRENT LIBRARY','Leaflet 1.9.4 • raster tiles'], 'maplibre'=>['MapLibre GL JS','ALTERNATIVE LIBRARY','MapLibre 6.14.0 • WebGL'], 'openlayers'=>['OpenLayers','ALTERNATIVE LIBRARY','OpenLayers 10.11.0 • 2D layers']] as $id=>$details)
        @if ($library === 'all' || $library === $id)
        <article class="panel {{ $id }}"><div class="panel-head"><div><span class="tag">{{ $details[1] }}</span><h2>{{ $details[0] }}</h2></div><span class="panel-status" id="{{ $id }}-status" role="status">Loading…</span></div><div class="map" id="{{ $id }}-map" aria-label="{{ $details[0] }} map"></div><div class="panel-foot"><span>{{ $presentation === 'matched' ? 'OpenStreetMap standard • raster' : ['leaflet'=>'OpenStreetMap standard • street', 'maplibre'=>'OpenFreeMap Liberty • vector', 'openlayers'=>'Esri World Imagery • satellite'][$id] }}</span><strong id="{{ $id }}-count">—</strong></div><div class="evaluation"><b>Evaluate for PyroSense</b><p>{{ ['leaflet'=>'Street and incident-marker readability; suitability for your existing map workflow.', 'maplibre'=>'Vector labels and styling at different zoom levels; incident visibility on a modern street map.', 'openlayers'=>'Building, field and access context in imagery; clarity of saved GIS boundary overlays.'][$id] }}</p></div></article>
        @endif
        @endforeach
    </section>
    <footer><div class="legend"><span><i style="background:#274e93"></i>Station</span><span><i style="background:#dc4848"></i>Fire / incident</span><span><i style="background:#168cbb"></i>Hydrant</span><span><i style="background:#7b5ab7"></i>Barangay</span><span><i style="background:#c88b24"></i>Risk / other</span></div><p>{{ $presentation === 'showcase' ? 'Distinct styles compare visual configurations as well as libraries. Street maps, vector basemaps and imagery are not exclusive to these libraries. Provider detail and imagery age can differ. Use Controlled: same basemap when assessing library interaction alone.' : 'Controlled mode matches the basemap across libraries; vector styling and imagery are shown in Distinct styles mode.' }} Same incident coordinates and category colors are retained. No routing calls or measured performance ranking.</p><p>Sources: existing authenticated marker feed and saved boundaries. Map data © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap contributors</a>. Vector basemap: <a href="https://openfreemap.org/" target="_blank" rel="noopener">OpenFreeMap</a> / OpenMapTiles. Satellite tiles: Esri and imagery contributors (see map attribution). <span id="loaded-at"></span></p></footer>
    <div id="ol-popup" class="popup" hidden></div>
</main>
</body>
</html>
