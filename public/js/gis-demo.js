/* Isolated demo. Reads existing map feeds; never creates incidents or dispatches. */
(() => {
    'use strict';
    const byId = id => document.getElementById(id);
    if (!window.L) { byId('data-status').textContent = 'Map library could not load. Check your connection and reload.'; return; }
    const colors = {geoapify:'#177e89', osrm:'#e58a39', openrouteservice:'#7954b3'};
    const maps = {}, markerLayers = {}, routeLayers = {}, endpointLayers = {}, boundaryLayers = {};
    let markers = [], busy = false, syncing = false;
    const pageProvider = document.body.dataset.provider;
    const allProviders = ['geoapify','osrm','openrouteservice'];
    const providers = allProviders.includes(pageProvider) ? [pageProvider] : allProviders;
    const category = marker => String(marker.category || '').toLowerCase();
    const valid = marker => marker.lat !== null && marker.lng !== null && Number.isFinite(Number(marker.lat)) && Number.isFinite(Number(marker.lng)) && Math.abs(Number(marker.lat)) <= 90 && Math.abs(Number(marker.lng)) <= 180;
    const markerColor = marker => category(marker).includes('station') ? '#274e93' : category(marker).includes('fire') || category(marker).includes('incident') ? '#dc4848' : category(marker).includes('hydrant') ? '#168cbb' : category(marker).includes('barangay') ? '#7b5ab7' : '#c88b24';
    const endpointIcon = letter => L.divIcon({className:'endpoint-icon',html:`<span>${letter}</span>`,iconSize:[30,30],iconAnchor:[15,15]});
    providers.forEach(provider => {
        const map = maps[provider] = L.map(`${provider}-map`).setView([15.833,120.35],13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'}).addTo(map);
        boundaryLayers[provider] = L.layerGroup().addTo(map);
        markerLayers[provider] = L.layerGroup().addTo(map);
        routeLayers[provider] = L.layerGroup().addTo(map);
        endpointLayers[provider] = L.layerGroup().addTo(map);
        map.on('moveend', () => {
            if (providers.length < 2 || syncing || !byId('sync').checked) return;
            syncing = true;
            providers.filter(other => other !== provider).forEach(other => maps[other].setView(map.getCenter(),map.getZoom(),{animate:false}));
            syncing = false;
        });
    });

    async function jsonFetch(url, options = {}) {
        const response = await fetch(url,{credentials:'same-origin',...options,headers:{Accept:'application/json',...options.headers}});
        if (response.status === 401 || response.redirected) throw new Error('Please sign in to this application, then reload the demo.');
        const payload = await response.json().catch(() => null);
        if (!response.ok) throw new Error(payload?.message || `Request failed (${response.status}).`);
        return {payload,response};
    }
    function clearRoutes() {
        providers.forEach(provider => {
            routeLayers[provider].clearLayers(); endpointLayers[provider].clearLayers();
            byId(`${provider}-status`).textContent = 'Ready';
            byId(`${provider}-status`).classList.remove('error');
            byId(`${provider}-metrics`).querySelectorAll('strong').forEach(node => node.textContent='—');
        });
    }
    function lock(value) {
        busy = value;
        ['origin','destination','compare','refresh-data'].forEach(id => byId(id).disabled = value || (id !== 'refresh-data' && markers.length < 2));
    }
    async function loadData() {
        lock(true); clearRoutes();
        byId('data-status').textContent='Fetching your current map markers…';
        try {
            // Same endpoint as resources/js/map.js; viewer visibility is enforced there.
            const {payload} = await jsonFetch('/map/markers/feed');
            if (!Array.isArray(payload)) throw new Error('The marker feed returned an unexpected format.');
            markers = payload.filter(valid);
            providers.forEach(provider => {
                markerLayers[provider].clearLayers();
                markers.forEach(marker => {
                    const dot = L.marker([Number(marker.lat),Number(marker.lng)],{icon:L.divIcon({className:'demo-dot',html:`<span style="background:${markerColor(marker)}"></span>`,iconSize:[19,19],iconAnchor:[9,9]})});
                    const popup = document.createElement('div');
                    const title = document.createElement('strong'); title.textContent=marker.title || 'Map marker';
                    const detail = document.createElement('p'); detail.textContent=`${marker.category || 'Other'} • ${marker.status || 'Status unavailable'}`;
                    popup.append(title,detail); dot.bindPopup(popup).addTo(markerLayers[provider]);
                });
            });
            for (const id of ['origin','destination']) {
                byId(id).replaceChildren(...markers.map((marker,index) => new Option(`${marker.title || 'Marker '+marker.marker_id} (${marker.category || 'other'})`,String(index))));
            }
            const station = markers.findIndex(marker => category(marker).includes('station'));
            const dest = markers.findIndex((marker,index) => index !== (station < 0 ? 0 : station) && /pasibi east/i.test(marker.title || ''));
            byId('origin').value=String(station < 0 ? 0 : station);
            byId('destination').value=String(dest < 0 ? (byId('origin').value === '0' ? 1 : 0) : dest);
            if (markers.length) {
                const bounds=L.latLngBounds(markers.map(marker => [Number(marker.lat),Number(marker.lng)]));
                providers.forEach(provider => maps[provider].fitBounds(bounds,{padding:[20,20],maxZoom:14}));
            }
            let boundaryStatus='';
            try {
                const {payload:boundaryPayload} = await jsonFetch('/api/shapes');
                const shapes = Array.isArray(boundaryPayload) ? boundaryPayload : boundaryPayload?.data;
                if (!Array.isArray(shapes)) throw new Error('Unexpected boundary format');
                let count=0;
                providers.forEach(provider => boundaryLayers[provider].clearLayers());
                for (const shape of shapes) {
                    try {
                        const geojson=typeof shape.geojson === 'string' ? JSON.parse(shape.geojson) : shape.geojson;
                        if (!geojson) continue;
                        providers.forEach(provider => L.geoJSON(geojson,{interactive:false,style:{color:'#547994',weight:1,fillOpacity:.025}}).addTo(boundaryLayers[provider]));
                        count++;
                    } catch (_) { /* Leave malformed source boundaries out of both maps. */ }
                }
                boundaryStatus=` • ${count} saved boundaries`;
            } catch (_) { boundaryStatus=' • saved boundaries unavailable'; }
            byId('data-status').textContent=`Same live feed • ${markers.length} visible markers per map${boundaryStatus}`;
            byId('route-context').textContent=markers.length < 2 ? 'At least two visible markers are needed for a route.' : 'Choose markers, then compare routes.';
        } catch (error) {
            byId('data-status').textContent=error.message;
            markers=[];
        } finally { lock(false); }
    }
    async function route(provider, origin, destination) {
        const status=byId(`${provider}-status`);
        status.textContent='Calculating route…';
        const started=performance.now();
        try {
            const {payload,response}=await jsonFetch(provider==='geoapify' ? '/api/routing/calculate' : `/gis-demo/${provider}-route`,{
                method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
                body:JSON.stringify({start_lat:Number(origin.lat),start_lng:Number(origin.lng),end_lat:Number(destination.lat),end_lng:Number(destination.lng),mode:'driving'})
            });
            const elapsed=performance.now()-started;
            const feature=payload?.features?.[0];
            if (!feature?.geometry || !Number.isFinite(Number(feature.properties?.distance)) || !Number.isFinite(Number(feature.properties?.time))) throw new Error('The API did not return usable route geometry and metrics.');
            const layer=L.geoJSON(feature,{style:{color:colors[provider],weight:6,opacity:.9}}).addTo(routeLayers[provider]);
            const values=[`${(Number(feature.properties.distance)/1000).toFixed(2)} km`,`${(Number(feature.properties.time)/60).toFixed(2)} min`,`${Math.round(elapsed).toLocaleString()} ms`];
            byId(`${provider}-metrics`).querySelectorAll('strong').forEach((node,index) => node.textContent=values[index]);
            const cache=response.headers.get('X-Route-Cache');
            status.textContent=cache==='STALE' ? 'Saved fallback route (STALE)' : 'Live route received';
            // Use the union after both responses, so screenshots compare the same viewport.
            return layer.getBounds();
        } catch(error) { status.textContent=error.message; status.classList.add('error'); return null; }
    }
    byId('compare').addEventListener('click',async () => {
        if (busy) return;
        const origin=markers[Number(byId('origin').value)], destination=markers[Number(byId('destination').value)];
        if (!origin || !destination || byId('origin').value===byId('destination').value) { byId('route-context').textContent='Select two different markers.'; return; }
        lock(true); clearRoutes();
        byId('route-context').textContent=`${origin.title || 'Origin'} → ${destination.title || 'Destination'}`;
        providers.forEach(provider => {
            L.marker([Number(origin.lat),Number(origin.lng)],{icon:endpointIcon('A'),zIndexOffset:1000}).addTo(endpointLayers[provider]);
            L.marker([Number(destination.lat),Number(destination.lng)],{icon:endpointIcon('B'),zIndexOffset:1000}).addTo(endpointLayers[provider]);
        });
        const bounds=await Promise.all(providers.map(provider => route(provider,origin,destination)));
        const combined=L.latLngBounds([[Number(origin.lat),Number(origin.lng)],[Number(destination.lat),Number(destination.lng)]]);
        bounds.filter(Boolean).forEach(bound => combined.extend(bound));
        providers.forEach(provider => maps[provider].fitBounds(combined,{padding:[30,30],maxZoom:17}));
        byId('captured-at').textContent=new Date().toLocaleString('en-PH',{timeZone:'Asia/Manila'})+' PHT';
        lock(false);
    });
    ['origin','destination'].forEach(id => byId(id).addEventListener('change',() => {clearRoutes();byId('route-context').textContent='Selection changed. Click Compare routes to update the maps.';}));
    byId('refresh-data').addEventListener('click',loadData);
    byId('capture-mode').addEventListener('click',() => {
        const enabled=document.body.classList.toggle('capture');
        byId('capture-mode').textContent=enabled ? 'Show controls' : 'Screenshot view';
        requestAnimationFrame(() => providers.forEach(provider => maps[provider].invalidateSize()));
    });
    loadData();
})();
