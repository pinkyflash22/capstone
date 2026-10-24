# Panel review: map suitability for PyroSense

Purpose: select the map configuration that makes incidents, stations, hydrants and barangay boundaries easiest to interpret for the project's users. No winner is preassigned and no benchmark results are invented.

## Visual configurations to review

| Configuration | Evaluation focus | Limitation to check |
| --- | --- | --- |
| Leaflet + OpenStreetMap street tiles | Road/place names, incident-marker contrast, familiar existing workflow | Crowded labels and markers at municipality scale |
| MapLibre + OpenFreeMap Liberty vector style | Label presentation across zoom levels, incident prominence, style flexibility | WebGL availability and vector tile loading on the actual target devices |
| OpenLayers + Esri satellite imagery | Building/field context, visible access paths, GIS boundary overlays | Imagery age, missing street labels, marker contrast against imagery |

These are configurations, not exclusive capabilities. Basemap styles can be changed in each library. The current PyroSense application already uses Leaflet; adopting another renderer requires separate integration work, beyond this demo.

## Capture comparable evidence

Use the same signed-in account, viewport size, geographic extent, marker category and boundary toggles. Capture each configuration at municipality scale and at a closer incident/road scale. Include the source attribution. Use the same incident or marker popup for each screenshot.

Then switch to **Controlled: same basemap** to inspect differences in marker selection and map interaction without changing the basemap. Record any observed issue, including the browser and device. Do not describe an unmeasured feature as faster or more accurate.

## Review worksheet

Suggested scale: 1 = poor for the task, 3 = acceptable, 5 = excellent. Leave cells blank until assessed. Weights are a proposed discussion aid; the panel can agree on different weights before scoring.

| Criterion | Proposed weight | Leaflet configuration | MapLibre configuration | OpenLayers configuration |
| --- | ---: | --- | --- | --- |
| Incident-marker visibility | 25% | | | |
| Roads and place-name readability | 20% | | | |
| Barangay boundary clarity | 15% | | | |
| Useful location/access context | 15% | | | |
| Pan, zoom and popup usability on target device | 15% | | | |
| Visual preference for the operational task | 10% | | | |

Record implementation effort, device compatibility, provider access/usage conditions and maintenance separately from visual scores. A preferred satellite image does not by itself prove OpenLayers is the best library; a preferred vector style does not by itself prove MapLibre is the best renderer.

## Write the recommendation

Name the preferred configuration, cite the screenshots and completed review scores, explain the operational reasons, and list unresolved issues. Separate observed results from library capabilities documented by their maintainers. Quantitative performance comparisons require additional measurements; none have been performed for this mapping demo.
