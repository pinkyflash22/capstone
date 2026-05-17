<!DOCTYPE html>
<html>
<head>
    <title>Leaflet + Geoapify Routing</title>

    {{-- <meta name="viewport" content="width=device-width, initial-scale=1.0"> --}}
<meta name="csrf-token" content="{{ csrf_token() }}">

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet/dist/leaflet.css"
    />

    <style>
        body { margin: 0; }
        #map { height: 100vh; }
    </style>
</head>
<body>

<div id="map"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>   

<script>
    const map = L.map('map').setView([ 15.8516, 120.2919], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let startMarker = null;
    let endMarker = null;
    let routeLayer = null;

    const apiKey = "{{ env('GEOAPIFY_KEY') }}";

    map.on('click', function (e) {

        if (!startMarker) {
            startMarker = L.marker(e.latlng).addTo(map);
            return;
        }

        if (!endMarker) {
            endMarker = L.marker(e.latlng).addTo(map);
            getRoute(startMarker.getLatLng(), endMarker.getLatLng());
            return;
        }

        map.removeLayer(startMarker);
        map.removeLayer(endMarker);
        if (routeLayer) map.removeLayer(routeLayer);

        startMarker = null;
        endMarker = null;
        routeLayer = null;
    });

    function getRoute(start, end) {

        const url =
            "https://api.geoapify.com/v1/routing?" +
            `waypoints=${start.lat},${start.lng}|${end.lat},${end.lng}&` +
            "mode=drive&" +
            "type=balanced&" +
            `apiKey=${apiKey}`;

            fetch('/api/route', { // now it’s /route, not /api/route
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
         'X-CSRF-TOKEN': '{{ csrf_token() }}'
        // 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
    body: JSON.stringify({
        start: start.lat + "," + start.lng,
        end: end.lat + "," + end.lng
    })
})
.then(res => res.json())
.then(data => {
    if (!data.features || data.features.length === 0) return;


        console.log('API Data:', data); // view in browser console

        // Add markers from API data
        // data.forEach(item => {
        //     L.marker([item.lat, item.lng]).addTo(map)
        //      .bindPopup(item.name);
        // });
    const geometry = data.features[0].geometry;
    if (routeLayer) map.removeLayer(routeLayer);
    routeLayer = L.geoJSON(geometry, { color: 'blue', weight: 5 }).addTo(map);
});


    }
</script>

</body>
</html>
