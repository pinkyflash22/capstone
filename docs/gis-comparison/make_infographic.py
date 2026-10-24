"""Render the saved live measurements; never calls an API."""
import datetime as dt
import json
import os
from pathlib import Path
import statistics
import textwrap
ROOT = Path(__file__).resolve().parents[2]
cache = ROOT / 'tmp/gis-matplotlib'
cache.mkdir(parents=True, exist_ok=True)
os.environ.setdefault('MPLCONFIGDIR', str(cache))
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import numpy as np

ROOT = Path(__file__).resolve().parents[2]
data = json.loads((Path(__file__).parent / 'results.json').read_text())
rows = data['results']
out = ROOT / 'output/pdf'
out.mkdir(parents=True, exist_ok=True)
providers = ['Geoapify', 'OSRM']
palette = ['#177E89', '#E58A39']
names = [x['name'] for x in data['destinations']]
labels = [n.replace(' barangay hall', '') for n in names]
plt.rcParams.update({'font.family': 'DejaVu Sans', 'font.size': 11, 'text.color': '#19364B', 'axes.labelcolor': '#19364B'})
fig = plt.figure(figsize=(14,10), facecolor='#F5F8FB')
fig.text(.055,.94,'GIS ROUTING | LIVE API COMPARISON',fontsize=24,weight='bold')
fig.text(.055,.905,'Urbiztondo, Pangasinan - identical route inputs, separate standalone sample',fontsize=12)
stamp = dt.datetime.fromisoformat(rows[0]['timestamp']).astimezone(dt.timezone(dt.timedelta(hours=8))).strftime('%Y-%m-%d %H:%M PHT')
fig.text(.055,.875,f"Measured {stamp} | {len(names)} routes x {data['rounds']} rounds x 2 APIs | passenger-car routing",fontsize=10)
for index,provider in enumerate(providers):
    pr = [r for r in rows if r['provider']==provider]
    ok = [r for r in pr if r['success']]
    attempted = sum(r.get('attempted',False) for r in pr)
    latency = f"{statistics.median(r['latency_ms'] for r in ok):,.0f} ms" if ok else 'N/A'
    x = .055 + index * .46
    fig.text(x,.82,provider,fontsize=18,color=palette[index],weight='bold')
    fig.text(x,.785,f"{len(ok)}/{attempted} successful requests  |  {latency} median latency",fontsize=13,weight='bold')
    fig.text(x,.76,'Live commercial API endpoint' if index==0 else 'FOSSGIS public OSRM car endpoint',fontsize=10)

def median(provider, destination, field):
    values=[r[field] for r in rows if r['provider']==provider and r['destination']==destination and r['success']]
    return statistics.median(values) if values else float('nan')

for chart,(field,title,ylabel) in enumerate([
    ('latency_ms','API response latency','Milliseconds (lower = faster response)'),
    ('distance_km','Returned route distance','Kilometers'),
    ('travel_minutes','Estimated driving time','Minutes (provider estimate)')]):
    ax=fig.add_axes([.065+chart*.315,.425,.265,.265],facecolor='#F5F8FB')
    positions=np.arange(len(names))
    for index,provider in enumerate(providers):
        vals=[median(provider,n,field) for n in names]
        bars=ax.bar(positions+(index-.5)*.34,vals,width=.32,color=palette[index],label=provider,zorder=3)
        for bar,value in zip(bars,vals):
            if np.isfinite(value):
                ax.annotate(f'{value:.0f}' if field=='latency_ms' else f'{value:.2f}',(bar.get_x()+bar.get_width()/2,value),xytext=(0,5),textcoords='offset points',ha='center',fontsize=8)
    ax.set_title(title,loc='left',weight='bold',fontsize=13,pad=16)
    ax.set_xticks(positions, labels,fontsize=9)
    ax.set_ylabel(ylabel,fontsize=9)
    ax.grid(axis='y',alpha=.15,zorder=0)
    ax.spines[['top','right']].set_visible(False)
    ax.set_ylim(bottom=0)
    ax.margins(y=.24)
    if chart==0:
        fig.legend(*ax.get_legend_handles_labels(), loc='center', bbox_to_anchor=(.5,.375), ncol=2, frameon=False, fontsize=10)

fig.text(.055,.35,'HOW TO READ THE RESULTS',fontsize=12,weight='bold')
notes=[
 'Latency measures the entire HTTPS response body, including connection and network overhead. Bars show medians of successful requests per route; failures are retained in results.json.',
 'Both APIs receive the same origin and destinations. Calls alternate provider order by round, are spaced at least 1.1 seconds apart, and use no application cache or automatic retries. Provider-side caches are uncontrolled.',
 'Distance and driving time describe each API\'s chosen route. Differences do not establish accuracy: no field-verified route or travel-time reference was collected. This small run cannot establish long-term reliability.',
 'Coordinates come from the project seeders: sample station (15.8222000, 120.3297000) to three barangay halls. These are demo coordinates, not verified fire incident locations. Estimated driving time is not emergency response time.',
]
y=.318
for note in notes:
    lines=textwrap.fill(note,160)
    fig.text(.055,y,lines,fontsize=9,linespacing=1.5,va='top')
    y-=.052 if len(lines.splitlines())==2 else .067
fig.text(.055,.065,'Sources: Geoapify Routing API documentation; OSRM HTTP API documentation; routing.openstreetmap.de usage policy.',fontsize=8)
fig.text(.055,.045,'Map data: OpenStreetMap contributors | Fix the map: https://www.openstreetmap.org/ | Endpoint details and full measurements accompany this figure.',fontsize=8)
png=out/'gis-routing-comparison.png'
fig.savefig(png,dpi=230,facecolor=fig.get_facecolor())
plt.close(fig)
print(f'Created {png}')
