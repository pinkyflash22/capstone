import json, re, math, random, sys, subprocess, urllib.request, urllib.parse
from pathlib import Path
from datetime import datetime,timedelta,timezone
ROOT=Path(__file__).resolve().parents[2]
if len(sys.argv)>1 and sys.argv[1]=='fetch':
    key=''
    for line in (ROOT/'.env').read_text(encoding='utf-8-sig').splitlines():
        k,sep,v=line.partition('=')
        if sep and k.strip()=='GEOAPIFY_KEY': key=v.strip().strip('"\'').split(' #',1)[0]
    args=json.loads(sys.argv[2])
    q=urllib.parse.urlencode({'waypoints':f"{args['origin'][0]},{args['origin'][1]}|{args['destination'][0]},{args['destination'][1]}",'mode':'drive','format':'geojson','apiKey':key})
    try:
        with urllib.request.urlopen('https://api.geoapify.com/v1/routing?'+q,timeout=6) as f: data=json.load(f)
        prop=data['features'][0]['properties']
        print(json.dumps({'minutes':float(prop['time'])/60,'distance_km':float(prop['distance'])/1000}))
    except Exception: sys.exit(1)
    sys.exit(0)
source=(ROOT/'database/seeders/UrbiztondoBarangayHallSeeder.php').read_text()
coords={n:(float(a),float(b)) for n,a,b in re.findall(r"'([^']+)' => \['lat' => ([\d.]+), 'lng' => ([\d.]+)\]",source)}
snap=json.loads((ROOT/'tmp/pdfs/historical_database_snapshot.json').read_text())
bar={r['barangay_id']:r['barangay_name'] for r in snap['barangays']}
incidents=[r for r in snap['rows'] if r['historical_fire_incident_id'] in range(1,13)]
origin=(15.8222,120.3297)
origin_source='SampleDefaultDataSeeder.php station marker; not independently verified as actual station'
location_file=ROOT/'tmp/pdfs/timing_locations.json'
if location_file.exists():
    loc=json.loads(location_file.read_text())
    station=next((r for r in loc['stations'] if 'urbiztondo' in r['station_name'].lower()),None)
    if station:
        origin=(float(station['lat']),float(station['lng']));origin_source='Current database station marker '+station['station_name']
    for h in loc['halls']:
        if h['barangay_name'] in coords:coords[h['barangay_name']]=(float(h['lat']),float(h['lng']))
cache=json.loads((ROOT/'docs/gis-comparison/results.json').read_text())
route_cache={r['destination'].replace(' barangay hall',''):r for r in cache['results'] if r['provider']=='Geoapify' and r['success']}
def crow(a,b):
    x,y=map(math.radians,a);xx,yy=map(math.radians,b)
    z=math.sin((xx-x)/2)**2+math.cos(x)*math.cos(xx)*math.sin((yy-y)/2)**2
    return 6371*2*math.atan2(math.sqrt(z),math.sqrt(1-z))
travel={}
for name in dict.fromkeys(bar[r['barangay_id']] for r in incidents):
    dest=coords[name]; method=None
    if origin==(15.8222,120.3297) and name in route_cache:
        c=route_cache[name];result={'minutes':c['travel_minutes'],'distance_km':c['distance_km']};method='Saved Geoapify driving estimate, 2026-10-18'
    else:
        try:
            call=subprocess.run([sys.executable,__file__,'fetch',json.dumps({'origin':origin,'destination':dest})],capture_output=True,text=True,timeout=10)
            result=json.loads(call.stdout) if call.returncode==0 else None
        except (subprocess.TimeoutExpired,ValueError):result=None
        if result:method='Geoapify driving estimate fetched '+datetime.now(timezone.utc).date().isoformat()
        else:
            # Explicit fallback simulation, not a measured road route.
            modeled_km=crow(origin,dest)*1.4
            result={'minutes':modeled_km/30*60,'distance_km':modeled_km};method='Synthetic travel: great-circle km x 1.4 / assumed 30 km/h'
    travel[name]=dict(result,destination=dest,method=method)
    print(name,round(result['minutes'],2),method,flush=True)
rng=random.Random(20261021)
rows=[]
for r in incidents:
    name=bar[r['barangay_id']]; route=travel[name]; suppression=rng.randint(10,30)
    fireout=datetime.fromisoformat(r['incident_date']+'T'+r['incident_time']) if r['incident_time'] else None
    arrival=fireout-timedelta(minutes=suppression) if fireout else None
    dispatch=arrival-timedelta(minutes=route['minutes']) if arrival else None
    report=dispatch-timedelta(minutes=2) if dispatch else None
    fmt=lambda t:t.isoformat(sep=' ',timespec='seconds') if t else None
    rows.append({'historical_id':r['historical_fire_incident_id'],'date':r['incident_date'],'barangay':name,'location_description':r['incident_location'],'destination_lat':route['destination'][0],'destination_lng':route['destination'][1],'destination_policy':'Barangay hall proxy, zero metres from saved hall; proximity to historical fire site unverified','dispatch_to_arrival_minutes':round(route['minutes'],4),'travel_method':route['method'],'arrival_to_fire_out_minutes':suppression,'suppression_source':'Synthetic 10-30-minute scenario, random seed 20261021; user assumes low severity and one team','fire_out_as_clarified_by_user':fmt(fireout),'synthetic_arrival':fmt(arrival),'synthetic_dispatch':fmt(dispatch),'synthetic_report':fmt(report),'report_to_dispatch_assumed_minutes':2})
result={'notice':'SIMULATED / ESTIMATED DOCUMENTATION DATA. Not measured historical operational performance. No database changes.','origin':origin,'origin_source':origin_source,'assumptions':['Recorded incident time interpreted as fire-out per user clarification, not independently verified','All low severity and one responding team are user-supplied scenario assumptions','Arrival-to-fire-out values generated uniformly as integer minutes from 10 to 30','Two-minute report-to-dispatch delay excluded from both requested averages','Month-level placeholder dates retained; absent fire-out times remain null','Destination is barangay hall proxy; cannot assert actual historical site within 300m'],'records':rows,'average_dispatch_to_arrival_minutes':sum(r['dispatch_to_arrival_minutes'] for r in rows)/len(rows),'average_arrival_to_fire_out_minutes':sum(r['arrival_to_fire_out_minutes'] for r in rows)/len(rows),'count':len(rows)}
output=ROOT/'output/historical-incident-interval-estimates.json';output.parent.mkdir(exist_ok=True)
output.write_text(json.dumps(result,indent=2),encoding='utf-8')
print(json.dumps({k:result[k] for k in ['average_dispatch_to_arrival_minutes','average_arrival_to_fire_out_minutes','count']},indent=2))
