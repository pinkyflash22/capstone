# Observed routing comparison

Run: October 18, 2026, Philippine time. See individual UTC timestamps in `results.json`.

The standalone sample made 12 requests: three route pairs, two rounds and two providers. Both Geoapify and the FOSSGIS public OSRM endpoint returned usable routes for all six of their requests. Overall median full-response latency was approximately **1,046 ms for Geoapify** and **900 ms for OSRM** in this run. This is a descriptive observation from a small sample, not a statistically established speed ranking.

| Destination from sample station | Geoapify distance (km) | OSRM distance (km) | Geoapify driving estimate (min) | OSRM driving estimate (min) |
| --- | ---: | ---: | ---: | ---: |
| Poblacion barangay hall | 0.27 | 0.27 | 0.60 | 0.72 |
| Bayaoas barangay hall | 3.04 | 3.04 | 4.69 | 5.86 |
| Pasibi East barangay hall | 8.04 | 8.03 | 12.15 | 15.16 |

Table values are rounded per-route medians across two successful calls. Exact normalized values, geometry and latency for every call are in `results.json`.

The returned distances were similar, while OSRM returned longer driving estimates on all three routes. Differences may reflect routing profiles, selected paths, graph versions or speed assumptions; this run does not isolate those causes. No actual journeys were timed, so neither provider can be declared more accurate from these results.

The requests bypassed Laravel entirely. The origin is an existing **sample station marker**, and the destinations are barangay hall coordinates from the seeders. The exercise did not use verified historical incident coordinates, synthetic incident response durations, database access or application caches. All measurements describe this machine, these endpoints, and this execution window.

For the method, endpoint references, reproducible commands and limitations, see `README.md`. Import `output/pdf/gis-routing-comparison.png` into Word or use the companion PDF as a standalone figure.
