const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
// document.addEventListener('DOMContentLoaded', function() {


// });

// window.getAddress = function(lat, lon){
//         const url =
//             "https://api.geoapify.com/v1/geocode/reverse?" +
//             `lat=${lat}&lon=${lon}&` +
//             `apiKey=${apiKey}`;
//     // fetch('/api/reverse', {
//     let popupmarker =  L.popup() //add a loading address before displaying data
//     .setLatLng([lat, lon])
//         .setContent("Loading address...")
//         .openOn(map);

//     fetch('/api/reverse', {
//         method: 'POST',
//         headers: {
//             'Content-Type': 'application/json',
//             'X-CSRF-TOKEN': csrfToken
//         },
//         body: JSON.stringify({
//             lat: lat,
//             lon: lon
//         })
//     })
//     .then(res => res.json())
//     .then(data => {
//         if (!data.features || data.features.length === 0) return;

//         const address = data.features[0].properties.formatted;

//         // Show popup at the marker position
//     // const popupmarker =  L.popup()
//     popupmarker.setLatLng([lat, lon])
//     .setContent(`
//         <div>
//             <p>${address}</p>
//             <button id="popupActionBtn" style="background-color: #007bff; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">Add as Marker</button>
//         </div>
//     `)


//     if(popupmarker) {
//         popupmarker.openOn(map);
//     }
    
//     const btn = document.getElementById('popupActionBtn');
//     if(btn){
//         btn.addEventListener('click', function() {

//             // console.log('lat:', lat, 'lon:', lon);
//             //save logic

//             selectedLat = lat;
//             selectedLng = lon;

//             openModal();
//             // saveMarker(lat, lon);
           
//             // alert('Button inside popup clicked!');
//         });
//     }
    
//     //  map.removeLayer(popupmarker);
//         console.log('Reverse API:', data);

//           popupmarker.on('popupclose', function() {
//         map.removeLayer(popupmarker);
//         popupmarker = null; // Important: clear the reference
//     });
//     })
//     .catch(err => console.error(err));
// }