"""Small live Valhalla demo using the same seeded coordinates as the comparison."""
import datetime as dt
import json
from pathlib import Path
import time
import urllib.error
import urllib.parse
import urllib.request

from compare_routing import ORIGIN, DESTINATIONS

results = []
for destination in DESTINATIONS:
    query = {'locations': [{'lat':point['lat'], 'lon':point['lon']} for point in [ORIGIN,destination]], 'costing': 'auto', 'units': 'kilometers',
             'format': 'osrm', 'shape_format': 'geojson'}
    url = 'https://valhalla1.openstreetmap.de/route?' + urllib.parse.urlencode({'json': json.dumps(query, separators=(',', ':'))}, quote_via=urllib.parse.quote)
    row = {'provider': 'Valhalla', 'destination': destination['name'],
           'timestamp': dt.datetime.now(dt.timezone.utc).isoformat(), 'success': False}
    started = time.perf_counter()
    try:
        request = urllib.request.Request(url, headers={'User-Agent': 'UrbiztondoCapstone-GISDemo/1.0 (academic comparison)'})
        with urllib.request.urlopen(request, timeout=20) as response:
            raw = response.read()
            row['http_status'] = response.status
        row['latency_ms'] = round((time.perf_counter() - started) * 1000, 2)
        payload = json.loads(raw)
        route = payload['routes'][0]
        if payload.get('code') != 'Ok' or not route['geometry'].get('coordinates'):
            raise ValueError('No usable route')
        row.update(success=True, distance_km=route['distance'] / 1000,
                   travel_minutes=route['duration'] / 60, geometry=route['geometry'])
        print(f"Valhalla | {destination['name']} | {row['distance_km']:.2f} km | {row['travel_minutes']:.2f} min", flush=True)
    except urllib.error.HTTPError as error:
        row.update(http_status=error.code, error=f'HTTP {error.code}')
        print(row['error'], flush=True)
    except (OSError, ValueError, KeyError, IndexError, TypeError):
        row['error'] = 'Network failure or unusable response'
        print(row['error'], flush=True)
    results.append(row)
    time.sleep(1.1)

output = {'origin': ORIGIN, 'destinations': DESTINATIONS, 'results': results,
          'note': 'Separate demonstration run, one request per destination. Not part of the earlier two-provider benchmark or infographic. Driving durations are estimates.'}
Path(__file__).with_name('valhalla-results.json').write_text(json.dumps(output, indent=2), encoding='utf-8')
if not any(row['success'] for row in results):
    raise SystemExit(2)
