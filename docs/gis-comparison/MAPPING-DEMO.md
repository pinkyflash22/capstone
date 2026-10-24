# Three mapping libraries, separate from routing

Selected from the requested list: **OpenStreetMap + Leaflet**, **MapLibre GL JS**, and **OpenLayers**. None of these demo integrations requires an API key. Default **Distinct styles** mode shows three visual configurations: Leaflet with OpenStreetMap street tiles, MapLibre with OpenFreeMap Liberty vector tiles, and OpenLayers with Esri World Imagery satellite tiles.

Use the **Comparison view** selector to switch between:

- **Distinct styles** (default): evaluate street readability, vector-map presentation, and satellite context with the same incident markers. This compares configured map experiences, including different providers, rather than attributing every visual difference to the library.
- **Controlled: same basemap**: all libraries use OpenStreetMap raster tiles, isolating marker display and interactions more closely. This mode is also directly accessible at http://127.0.0.1:8000/gis-demo/mapping?presentation=matched.

All three libraries can support multiple basemap styles. Satellite imagery is not exclusive to OpenLayers, and vector maps are not exclusive to MapLibre. The selected styles are concrete examples for evaluation. No performance or accuracy ranking has been measured. See `MAPPING-EVALUATION.md` for a panel-review worksheet.

## URLs

Start the existing application normally, then sign in with your usual account.

- Compare all three: http://127.0.0.1:8000/gis-demo/mapping
- Leaflet + OpenStreetMap: http://127.0.0.1:8000/gis-demo/mapping/leaflet
- MapLibre GL JS: http://127.0.0.1:8000/gis-demo/mapping/maplibre
- OpenLayers: http://127.0.0.1:8000/gis-demo/mapping/openlayers
- The existing routing demo remains at http://127.0.0.1:8000/gis-demo

## Screenshot workflow

1. Open the combined view. The page fetches one shared snapshot from `/map/markers/feed` and `/api/shapes`, then renders that same dataset in all available panels. Existing account visibility rules apply.
2. Choose a marker category, and toggle markers or boundaries if needed. Counts under each map show the displayed marker and boundary-feature counts. A boundary record containing multiple GeoJSON features can produce more than one boundary feature.
3. Click a marker to show its title, category and status. Marker colors are consistent across all three libraries.
4. Pan or zoom with **Sync map views** checked. The libraries receive equivalent centers and resolutions; small label/rounding differences can occur between renderers. Rotation is disabled for comparison.
5. Click **Fit data** to frame the selected marker dataset. Boundaries are displayed as context; large polygons do not force the map away from the marker locations.
6. Click **Screenshot view** to hide navigation and controls and enlarge the maps. Capture the library names, map data and footer together. For a larger individual screenshot, use one of the single-library URLs.

## Implementation

| Library | Pinned version | Demo display |
| --- | --- | --- |
| Leaflet | 1.9.4 | OpenStreetMap street tiles, circle markers, GeoJSON boundaries and popups |
| MapLibre GL JS | 6.14.0 | OpenFreeMap Liberty vector basemap, WebGL circle/boundary layers and popups |
| OpenLayers | 10.11.0 | Esri satellite imagery, projected vector features, highlighted boundaries and popups |

GeoJSON source coordinates are longitude/latitude in EPSG:4326. OpenLayers explicitly transforms them to its EPSG:3857 view. MapLibre uses a 512-pixel zoom convention, so the synchronization adapter offsets its zoom by one relative to the 256-pixel Leaflet/OpenLayers convention.

Libraries are downloaded from pinned public CDNs at page load. A failed library load displays an error without intentionally disabling the other panels. MapLibre requires working browser WebGL; if an embedded IDE browser cannot provide it, open the demo in Chrome or Edge. The public basemap and CDNs still require internet access.

Local script/style URLs include content hashes to avoid replaying outdated demo assets from the service-worker cache. There is no npm build step, database mutation, routing request, geolocation request or `.env` modification.

New files: `resources/views/demos/mapping-comparison.blade.php`, `public/js/mapping-demo.js`, and `public/css/mapping-demo.css`. The authenticated mapping route and view action are added only to the existing demo route file/controller. The actual application map implementation is untouched.

## Verification and limits

PHP/JavaScript syntax checks and route inspection passed. All three pinned library script URLs returned HTTP 200. The OpenFreeMap Liberty style returned HTTP 200 with 111 style layers; a sample Esri satellite tile returned HTTP 200. Imagery attribution was checked against the service metadata. Signed-in browser rendering has not been visually verified in this session. No migrations, seeders, automated tests or destructive database commands were run.

For documentation, identify which comparison mode each screenshot uses. Distinct-style screenshots compare complete visual configurations; controlled screenshots use the same raster basemap. Both retain the same application-data snapshot. Screenshots alone cannot establish general performance, data coverage or accuracy differences. Imagery age and available street labels vary by provider.

## Primary references

- Leaflet documentation: https://leafletjs.com/reference.html
- MapLibre official example and pinned import: https://maplibre.org/maplibre-gl-js/docs/examples/display-a-map/
- MapLibre style specification: https://maplibre.org/maplibre-style-spec/
- OpenLayers hosted development build: https://openlayers.org/download/
- OpenLayers examples: https://openlayers.org/en/latest/examples/
- OpenStreetMap attribution: https://www.openstreetmap.org/copyright
- OpenFreeMap vector style and attribution: https://openfreemap.org/quick_start/
- OpenLayers ArcGIS service support: https://openlayers.org/en/latest/examples/arcgis-tiled.html
- Satellite metadata and attribution: https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer?f=pjson
