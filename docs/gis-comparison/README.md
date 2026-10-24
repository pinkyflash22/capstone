# Standalone GIS routing comparison

This folder is separate from the Laravel application. It does not boot Laravel, query the database, modify `.env`, or replace any application provider. The live sample compares the current Geoapify API with the alternative OSRM API.

## Run the sample

From the project root, using Python 3 (standard library only):

```powershell
python docs/gis-comparison/compare_routing.py --rounds 2
```

The script reads `GEOAPIFY_KEY` from the process environment or the root `.env`. It never prints or saves the key or a complete authenticated request URL. OSRM requires no key for this small public-endpoint demonstration. Every run replaces `results.json`; copy that file first if you want to preserve a prior run.

Three routes, two rounds, and two providers produce 12 requests. Requests are sequential and spaced by 1.1 seconds. No automatic retries or client response caching are used. Provider order alternates per round. All failed requests remain in the result file. Missing credentials are marked unattempted. HTTP errors and network errors are recorded separately from successful routes.

## Inputs and output meanings

- Origin: sample station marker at latitude 15.8222, longitude 120.3297, from `database/seeders/SampleDefaultDataSeeder.php`.
- Destinations: Poblacion, Bayaoas, and Pasibi East barangay hall coordinates from `database/seeders/UrbiztondoBarangayHallSeeder.php`.
- These are existing sample coordinates, not independently verified station locations or historical incident coordinates.
- Geoapify uses `mode=drive`; OSRM uses the FOSSGIS car-routing endpoint. Both request route geometry and turn instructions and use their own default routing assumptions. The first returned route is selected.
- `latency_ms`: elapsed time until the entire response body is received, including DNS, TLS, network and transfer time; it excludes subsequent JSON parsing. Errors include time until failure.
- `distance_km`: returned route distance normalized to kilometers.
- `travel_minutes`: returned estimated driving duration normalized to minutes.
- `geometry`: returned route shape, retained for personal inspection or later map overlays.
- Timestamps in JSON use UTC; the infographic displays Philippine time.

The recorded endpoint is part of the comparison: this measures Geoapify's hosted API versus FOSSGIS's public OSRM deployment, not just underlying algorithms. A small sample cannot establish accuracy, production capacity, or general reliability. Route time is not API latency or emergency response time. Synthetic historical incident response times are not used in this comparison.

## Rebuild the infographic

The rendering scripts read saved results and make no API calls. The chart builder requires matplotlib and numpy; the PDF packager requires reportlab and pypdfium2. On this machine, use system Python for the chart and bundled Python for the PDF:

```powershell
python docs/gis-comparison/make_infographic.py
& 'C:/Users/Aaron A. Aquino/.cache/codex-runtimes/codex-primary-runtime/dependencies/python/python.exe' docs/gis-comparison/render_pdf.py
```

Outputs are `output/pdf/gis-routing-comparison.pdf` and `output/pdf/gis-routing-comparison.png`. Bars use per-route medians across successful rounds; missing measurements remain missing. Success counts use attempted requests only.

## Documentation wording

Describe this as a limited comparative routing API demonstration with identical coordinate inputs, not a validated emergency response experiment. Report measured values from `results.json`, the execution timestamp, sample size and failures. Do not describe a shorter estimated route time as proof of better accuracy.

## Primary sources

- Geoapify routing inputs, response schema, distance and time: https://apidocs.geoapify.com/docs/routing/
- OSRM coordinate order, route response and distance/duration units: https://project-osrm.org/docs/v5.24.0/api/
- FOSSGIS endpoint policy (valid User-Agent, at most one request per second, no heavy usage): https://routing.openstreetmap.de/about.html
- Map data attribution: OpenStreetMap contributors, https://www.openstreetmap.org/copyright
- Fix the map: https://www.openstreetmap.org/
