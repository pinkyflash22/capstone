"""Fetch one demo route without Laravel or database access; never print the key."""
import datetime as dt
import json
import os
from pathlib import Path
import urllib.error
import urllib.request

from compare_routing import ROOT, ORIGIN, DESTINATIONS

key = os.environ.get('OPENROUTESERVICE_API_KEY') or os.environ.get('ORS_API_KEY')
if not key:
    values = {}
    for line in (ROOT / '.env').read_text(encoding='utf-8-sig').splitlines():
        name, sep, value = line.strip().partition('=')
        if sep and name in ('OPENROUTESERVICE_API_KEY', 'ORS_API_KEY'):
            values[name] = value.strip().strip('"\'')
    key = values.get('OPENROUTESERVICE_API_KEY') or values.get('ORS_API_KEY')
if not key:
    raise SystemExit('OpenRouteService key is missing.')

destination = DESTINATIONS[-1]
request = urllib.request.Request(
    'https://api.heigit.org/openrouteservice/v2/directions/driving-car/geojson',
    data=json.dumps({'coordinates': [[ORIGIN['lon'], ORIGIN['lat']], [destination['lon'], destination['lat']]], 'units': 'm', 'instructions': True}).encode(),
    headers={'Authorization': key, 'Content-Type': 'application/json', 'Accept': 'application/geo+json, application/json'},
    method='POST',
)
try:
    with urllib.request.urlopen(request, timeout=25) as response:
        payload = json.load(response)
    feature = payload['features'][0]
    summary = feature['properties']['summary']
    output = {'provider': 'OpenRouteService', 'timestamp': dt.datetime.now(dt.timezone.utc).isoformat(),
              'origin': ORIGIN, 'destination': destination, 'distance_km': summary['distance'] / 1000,
              'travel_minutes': summary['duration'] / 60, 'geometry': feature['geometry']}
    Path(__file__).with_name('openrouteservice-sample-result.json').write_text(json.dumps(output, indent=2), encoding='utf-8')
    print(f"Live OpenRouteService route: {output['distance_km']:.2f} km, {output['travel_minutes']:.2f} min; geometry received.")
except urllib.error.HTTPError as error:
    raise SystemExit(f'OpenRouteService HTTP {error.code}; no credentials or response body printed.')
except (OSError, ValueError, KeyError, IndexError):
    raise SystemExit('Network failure or unusable route response; no credentials printed.')
