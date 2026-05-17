<!DOCTYPE html>
<html>
<head>
    <title>Leaflet Marker CRUD</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <style>#map { height: 600px; }</style>
</head>
<body>
<h1>Leaflet Marker CRUD</h1>

<div id="map"></div>

<script>
    const map =  L.map('map').setView([15.8222, 120.3297], 13); // center on your area

   var street = L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'

    }).addTo(map);

    //satellite image
     var satellite = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        {
            maxZoom: 13,
            attribution: 'Tiles © Esri'
        }
    );


     street.addTo(map);
         L.control.layers({
        "Street Map": street,
        "Satellite": satellite
    }).addTo(map);
    const markers = @json($markers);

    markers.forEach(m => {
        const marker = L.marker([m.lat, m.lng], { draggable: true }).addTo(map)
            .bindPopup(`<b>${m.title}</b><br>${m.description || ''}<br>
            <button onclick="deleteMarker(${m.id})">Delete</button>`);

        marker.on('dragend', function(e) {
            const {lat, lng} = e.target.getLatLng();
            fetch(`/markers/${m.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ title: m.title, description: m.description, lat, lng })
            }).then(() => alert('Marker updated'));
        });
    });

    map.on('click', function(e) {
        const title = prompt('Enter marker title:');
        if (!title) return;
        const description = prompt('Enter description:');
        fetch('/markers', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ title, description, lat: e.latlng.lat, lng: e.latlng.lng })
        }).then(() => location.reload());
    });

    function deleteMarker(id) {
        if (!confirm('Delete this marker?')) return;
        fetch(`/markers/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        }).then(() => location.reload());
    }
</script>
</body>
</html>
