"""Standalone live API demonstration. Does not boot Laravel or access a database."""
import argparse
import datetime as dt
import json
import math
import os
from pathlib import Path
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[2]
HERE = Path(__file__).resolve().parent
ORIGIN = {"name": "Sample station marker", "lat": 15.8222, "lon": 120.3297}
DESTINATIONS = [
    {"name": "Poblacion barangay hall", "lat": 15.8230293, "lon": 120.3280002},
    {"name": "Bayaoas barangay hall", "lat": 15.8329772, "lon": 120.3536367},
    {"name": "Pasibi East barangay hall", "lat": 15.8573403, "lon": 120.3797199},
]

def geoapify_key():
    key = os.environ.get('GEOAPIFY_KEY', '').strip()
    if key:
        return key
    env = ROOT / '.env'
    if env.exists():
        for line in env.read_text(encoding='utf-8-sig').splitlines():
            name, sep, value = line.strip().partition('=')
            if sep and name.strip() == 'GEOAPIFY_KEY':
                value = value.strip()
                if value.startswith(('"', "'")):
                    return value[1:value.find(value[0], 1)]
                return value.split(' #', 1)[0].strip()
    return ''

def request_url(provider, dest, key):
    if provider == 'Geoapify':
        params = {'waypoints': f"{ORIGIN['lat']},{ORIGIN['lon']}|{dest['lat']},{dest['lon']}",
                  'mode': 'drive', 'format': 'geojson', 'details': 'instruction_details', 'apiKey': key}
        return 'https://api.geoapify.com/v1/routing?' + urllib.parse.urlencode(params)
    coords = f"{ORIGIN['lon']},{ORIGIN['lat']};{dest['lon']},{dest['lat']}"
    return f'https://routing.openstreetmap.de/routed-car/route/v1/driving/{coords}?overview=full&geometries=geojson&steps=true&generate_hints=false'

def fetch(provider, dest, key, round_no):
    result = {'provider': provider, 'destination': dest['name'], 'round': round_no,
              'timestamp': dt.datetime.now(dt.timezone.utc).isoformat(), 'success': False}
    if provider == 'Geoapify' and not key:
        return dict(result, error='Missing GEOAPIFY_KEY; request not attempted', attempted=False)
    start = time.perf_counter()
    try:
        req = urllib.request.Request(request_url(provider, dest, key), headers={
            'User-Agent': 'UrbiztondoCapstone-GISComparison/1.0 (small academic routing evaluation)',
            'Accept': 'application/json'})
        with urllib.request.urlopen(req, timeout=20) as response:
            body = response.read()
            result.update(http_status=response.status, response_bytes=len(body))
        result['latency_ms'] = round((time.perf_counter() - start) * 1000, 2)
        payload = json.loads(body)
        if provider == 'Geoapify':
            route = payload['features'][0]
            props = route['properties']
            distance, duration = float(props['distance']), float(props['time'])
            geometry = route['geometry']
        else:
            if payload.get('code') != 'Ok':
                raise ValueError('No successful route returned')
            route = payload['routes'][0]
            distance, duration = float(route['distance']), float(route['duration'])
            geometry = route['geometry']
        if not all(math.isfinite(x) and x >= 0 for x in (distance, duration)) or not geometry.get('coordinates'):
            raise ValueError('Invalid route metrics or geometry')
        result.update(success=True, distance_km=round(distance / 1000, 4),
                      travel_minutes=round(duration / 60, 4), geometry=geometry)
    except urllib.error.HTTPError as exc:
        result.update(http_status=exc.code, error=f'HTTP {exc.code}')
    except urllib.error.URLError:
        result['error'] = 'Network, DNS, TLS or connection failure'
    except (TimeoutError, OSError):
        result['error'] = 'Connection or response timeout/failure'
    except (ValueError, KeyError, IndexError, TypeError):
        result['error'] = 'Invalid JSON or unusable route response'
    result.setdefault('latency_ms', round((time.perf_counter() - start) * 1000, 2))
    return dict(result, attempted=True)

def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--rounds', type=int, default=2, choices=range(1, 6))
    args = parser.parse_args()
    key = geoapify_key()
    results = []
    for round_no in range(1, args.rounds + 1):
        for dest in DESTINATIONS:
            # Alternate order to reduce the bias of always calling one provider first.
            providers = ['Geoapify', 'OSRM'] if round_no % 2 else ['OSRM', 'Geoapify']
            for provider in providers:
                row = fetch(provider, dest, key, round_no)
                results.append(row)
                print(f"{provider} | {dest['name']} | round {round_no} | "
                      + (f"{row['distance_km']:.2f} km | {row['travel_minutes']:.2f} min | {row['latency_ms']:.0f} ms"
                         if row['success'] else row['error']), flush=True)
                time.sleep(1.1)  # Respect the OSRM public server's <=1 request/second policy.
    output = {'origin': ORIGIN, 'destinations': DESTINATIONS, 'rounds': args.rounds,
              'coordinate_sources': ['database/seeders/SampleDefaultDataSeeder.php',
                                     'database/seeders/UrbiztondoBarangayHallSeeder.php'],
              'method': 'Sequential direct HTTPS GET; full response-body latency includes DNS/TLS/network; no client cache or retries; first route; passenger-car profiles; provider defaults.',
              'limitations': 'Small sample from one machine; public endpoints and internal server caches differ. Route durations are estimates, not emergency response times. No road-truth accuracy assessment. Sample station and barangay hall coordinates are not historical incident locations.',
              'endpoints': {'Geoapify': 'https://api.geoapify.com/v1/routing',
                            'OSRM': 'https://routing.openstreetmap.de/routed-car/route/v1/driving/'},
              'results': results}
    (HERE / 'results.json').write_text(json.dumps(output, indent=2), encoding='utf-8')
    print('Saved docs/gis-comparison/results.json (no API keys or request URLs saved).')
    if not any(row['success'] for row in results):
        raise SystemExit(2)

if __name__ == '__main__':
    main()
