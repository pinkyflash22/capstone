# Live map demo for screenshots

The demo compares **Geoapify, OSRM and OpenRouteService**. Google Routes is no longer used. The existing application map, routing implementation and real `.env` remain unchanged.

For the separate **mapping library** comparison (Leaflet, MapLibre GL JS and OpenLayers), open http://127.0.0.1:8000/gis-demo/mapping and see `MAPPING-DEMO.md`. The instructions below describe the routing comparison.

## Open the demo

The separate forecast/historical weather comparison is at http://127.0.0.1:8000/gis-demo/weather. See `WEATHER-DEMO.md` for setup, methodology and coverage limits.

The AI text-assistance comparison is at http://127.0.0.1:8000/gis-demo/ai. See `AI-DEMO.md` for exact model IDs, evidence snapshots, availability and the distinction from numerical forecasting.

Start the application normally, or run `php artisan serve --host=127.0.0.1 --port=8000` from the project root. Sign in with your existing account.

- Compare all three: http://127.0.0.1:8000/gis-demo
- Current API, Geoapify: http://127.0.0.1:8000/gis-demo/current
- OSRM: http://127.0.0.1:8000/gis-demo/alternative
- OpenRouteService: http://127.0.0.1:8000/gis-demo/openrouteservice

## One-key OpenRouteService setup

1. Open the current account dashboard at https://account.heigit.org/ and sign in or register there. The older OpenRouteService login can return you to the API documentation instead of this account dashboard.
2. Obtain an API key from the developer dashboard.
3. Add it to your local `.env` (see `openrouteservice-demo.env.example`):

```dotenv
OPENROUTESERVICE_API_KEY=your_key_here
```

`ORS_API_KEY` is also supported as a fallback. The key stays on the server. No browser map key, Google billing setup, or Google outbound-IP configuration is used by this demo. If Laravel configuration is cached, refresh it using your usual development configuration workflow and restart the server. No migrations or seeders are required.

OpenRouteService was verified with one live sample route after the key was configured: the sample station to Pasibi East returned approximately 8.04 km and 14.07 minutes, with route geometry. The separate result is saved in `openrouteservice-sample-result.json` and can be refreshed using `python docs/gis-comparison/openrouteservice_sample.py`. This is not part of the earlier two-provider benchmark. Missing or rejected keys and quota errors are displayed explicitly; route results are never fabricated. Existing `results.json` and the infographic remain the earlier measured Geoapify/OSRM comparison. Older Valhalla sample files are disconnected from this demo.

## Capture screenshots

1. Select a starting marker and destination. Defaults prefer a visible station and a Pasibi East marker when available.
2. Click **Compare routes**. The comparison sends identical coordinates to all three providers; a single-provider page requests only that provider.
3. All three maps use the same authenticated marker feed, saved boundaries, basemap and common route viewport. **Sync map views** synchronizes panning and zooming.
4. Click **Screenshot view** to hide selectors and enlarge the maps. Use your usual screenshot tool; click **Show controls** to return.
5. Include the provider, distance, driving estimate and explanatory footer. A failed provider keeps blank metrics and shows its error.

## Data and isolation

Markers are fetched from `/map/markers/feed`, the same authenticated endpoint used by the original map. Existing viewer visibility rules remain in effect. Saved boundaries come from `/api/shapes`; unavailable boundaries are labeled. All providers use Leaflet and OpenStreetMap tiles. This focused demo does not duplicate dispatch, real-time vehicles, forecasts, editing or media tools.

- Geoapify calls the unchanged `/api/routing/calculate` endpoint.
- OSRM calls `/gis-demo/osrm-route`, which proxies the fixed FOSSGIS car endpoint and uses a shared two-second request gate.
- OpenRouteService calls `/gis-demo/openrouteservice-route`, which proxies `https://api.heigit.org/openrouteservice/v2/directions/driving-car/geojson` with its server-side Authorization header. Coordinates are longitude/latitude, distances are normalized from meters, and durations from seconds.

The demo routes require authentication, CSRF protection for POSTs and request throttling. Only one route-registration line was added to the existing `routes/web.php`; demo logic lives in separate files. No database records are edited by the demo features.

## Measurement limits

**Request elapsed** measures browser request completion through JSON parsing, including application, network and API overhead. It is not directly equivalent to the earlier standalone Python benchmark. Geoapify may return its existing stale fallback route cache, labeled on the page. Provider driving durations are estimates, not observed emergency response times. A shorter estimate does not prove better accuracy.

## Verification

PHP and JavaScript syntax checks and route inspection were performed. Basic local HTTP checks confirmed sign-in protection and static asset delivery. No migrations, seeders, automated tests or destructive database commands were run. Signed-in visual verification remains pending because browser automation was unavailable.

The demo's stylesheet and script URLs include content hashes so service-worker asset caches do not replay the older two-provider implementation. After an update, refresh the page to load the new URLs. The OpenRouteService GeoJSON endpoint requires an Accept header allowing `application/geo+json`; the demo and sample both send it.

## Primary sources

- https://openrouteservice.org/log-in/
- https://giscience.github.io/openrouteservice/api-reference/endpoints/directions/requests-and-return-types
- https://apidocs.geoapify.com/docs/routing/
- https://project-osrm.org/docs/v5.24.0/api/
- https://routing.openstreetmap.de/about.html
