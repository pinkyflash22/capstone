 import './bootstrap';       // default Laravel bootstrap
// import './app.js';  
import { station } from './functions.js';
import { getRoute } from './functions.js';
import { setName } from './functions.js';
import {getStations} from './functions.js';
import {getRouteDistance} from './functions.js';

//  let map;
 let name = "";
let selectedLat = null;
let selectedLng = null;
let path, temp;
let title = null;
let description = null;
let existingMarkers = {};
let thisLat = null;
let i = 0;
let tempMarker = null;
// let stationLayer = L.layerGroup();
let stationLayer = L.featureGroup();

let incidentLayer = L.layerGroup();
let hydrantLayer = L.layerGroup();
let otherLayer = L.layerGroup();
let shapeChecker = false;

window.map =  L.map('map').setView([15.8222, 120.3297], 13);
const stationFeatureGroup = L.featureGroup().addTo(map);
const incidentFeatureGroup = L.featureGroup().addTo(map);
const hydrantFeatureGroup = L.featureGroup().addTo(map);
const otherFeatureGroup = L.featureGroup().addTo(map);
// window.setAddress = setName();
window.setName = setName;
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const apiKey = "83677a1086d44c4e82f903fd2fe2ea9b";
// somewhere in your main script
window.routesGroup = L.layerGroup().addTo(map);
document.addEventListener('DOMContentLoaded', function() {
// const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
//  const markermap =  window.mapData.marker || [];
//  const path =  window.mapData.path || [];
//   var street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
//         attribution: '&copy; OpenStreetMap contributors'
//     }).addTo(map);
  var street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
//satellite
var satellite = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        {
            maxZoom: 13,
            attribution: 'Tiles © Esri'
        }
    );
 
   var fire = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    stationLayer.addTo(map);

    L.control.layers({
        "Street Map": street,
        "Satellite": satellite,
        // "Fire": fire,
    },
{
     

        "🚉 Stations": stationFeatureGroup,
        "🔥 Incidents": incidentFeatureGroup,
        "💧 Hydrants": hydrantFeatureGroup,
        "📍 Other Markers": otherLayer
        // "🚉 Stations": stationLayer,
        // "🔥 Incidents": incidentLayer,
        // "💧 Hydrants": hydrantLayer,
        // "📍 Other Markers": otherLayer
    },
    {
        collapsed: false, // Set to true if you want it collapsed by default
        position: 'topright' // or 'topleft', 'bottomleft', 'bottomright'
    }).addTo(map);

    // console.log('Tile layers added to map control.: ', markermap); //dito display ng lahat data para test
//para sa mapa mag display yawa
let array = [];
window.getPath = function(paths,temps){

    temp = temps;
    path = paths;
      array.push(path);

}   

// async function fetchMarkers() {
//     try {
//         const response = await fetch('/api/markers/latest');
//         const markers = await response.json();

//         updateMapMarkers(markers);
//         console.log(markers); //check lang natin if gagana
//     } catch (error) {
//         console.error('Fetch error:', error);
//     }
// }

function clearAllMarkers() {
    // Clear each feature group
    stationFeatureGroup.clearLayers();
    incidentFeatureGroup.clearLayers();
    hydrantFeatureGroup.clearLayers();
    otherFeatureGroup.clearLayers();
    
    // Clear the existingMarkers object
    existingMarkers = {};
    
    console.log('All markers cleared');
}

fetchCoordinate();
async function fetchCoordinate() {
    try {
        const response = await fetch('/api/coordinate/latest');
        const coordinates = await response.json();

          // Clear existing markers before adding new ones
        clearAllMarkers();
        
        // Update with new coordinates
        await updateCoordinate(coordinates);
        // console.log(coordinates); //check lang natin if gagana
    } catch (error) {
        console.error('Fetch error:', error);
    }
}
// async function fetchCategory() { //nasa loob ung current updatecoordinate
//     try {
//         const response = await fetch('/api/markers/latest');
//         const markers = await response.json();

//         updateMapMarkers(markers);
//         console.log(markers); //check lang natin if gagana
//     } catch (error) {
//         console.error('Fetch error:', error);
//     }
// }
//coordm odel

// function updateCoordinate(coordinates) { //current 
//     coordinates.forEach(coord => {
      
//         if (existingMarkers[coord.marker.marker_id]) return;
//             if (coord.marker.lat && coord.marker.lng) {
//                 const popupContent = `
//             <div class="flex flex-col gap-2 w-48">
//                 <strong class="text-sm font-bold">${coord.marker.title || 'Marker ' + coord.marker.id}</strong>
//                 <p class="text-xs text-gray-600">${coord.marker.description || 'No description'}</p>

//                 <!-- Image container -->
//                 <div class="w-full h-24 bg-gray-100 flex items-center justify-center border rounded">
//                     ${coord.image.multimedia_id ? `<img src="${coord.image?.path ? '/storage/' + coord.image.path : '/images/default.png'}" class="object-cover w-full h-full rounded" />` 
//                                     : `<span class="text-gray-400 text-xs">No image</span>`}
//                     <input type='hidden' value='${coord.marker.marker_id}' id='mid'>
//                 </div>

//                 <!-- Action button -->
//                ${
//                     coord.marker.markerCategory_id == 3
//                     ? `<button id="respond${coord.marker.marker_id}"
//                         class="bg-blue-600 text-white text-xs px-2 py-1 rounded hover:bg-blue-700">
//                         Respond
//                     </button>
//                     `

//                     : `<div class="bg-white-600" style="display:flex; justify-content:space-evenly;"> <button style="width: 50%;" class="bg-green-600 text-white w-50 text-xs px-2 py-1 rounded hover:bg-blue-700" id="edit${coord.marker.marker_id}">Edit</button><button id="delete${coord.marker.marker_id}"
//                         class="bg-red-600 text-white text-xs px-2 py-1 rounded hover:bg-blue-700"  style="width: 40%;">
//                         Delete
//                     </button> </div>`
//                 }
//             </div>
//         `;
//     //   id="deleteone"  onclick="deleteMarker(${marker.id})">
//         //btn delete
//         let tempicon;
       
//         // console.log("datapd", datap);
//         if(coord.marker.markerCategory_id == 1 ){

//            tempicon = L.divIcon({
//                 className: '', // remove Leaflet default styling
//                 html: `
//                     <div class="relative w-6 h-6">
//                                         <!-- base square for station -->
//                                         <div class="absolute inset-0 bg-blue-600 rounded-sm border-2 border-white"></div>
//                                         <!-- antenna / top indicator -->
//                                         <div class="absolute top-0 left-1/2 transform -translate-x-1/2 w-1 h-2 bg-white rounded-sm"></div>
//                     </div>
//                 `,
//                 iconSize: [32, 32],      // size of DivIcon
//                 iconAnchor: [16, 16]     // center of DivIcon is map location
//             });
        
//         }else if(coord.marker.markerCategory_id == 3){
//             // tempicon = null;
//             tempicon = L.divIcon({
//                 className: '', // remove Leaflet default styling
//                 html: `
//                     <div class="relative w-8 h-8"> 
//                         <div class="absolute inset-0 bg-orange-600 rounded-full animate-ping opacity-75"></div>
//                         <div class="absolute inset-0 flex items-center justify-center">
//                             <div class="w-6 h-6 bg-orange-600 rounded-full border-2 "></div>
//                         </div>
//                     </div>
//                 `,
//                 iconSize: [32, 32],      // size of DivIcon
//                 iconAnchor: [16, 16]     // center of DivIcon is map location
//             });
//             //  fire.addLayer(routeLayer);
//             // getRoute(15.8268037, 120.3382279, marker.lat, marker.lng,'map');
//         }else if( coord.marker.markerCategory_id == 4){
//                  tempicon = L.divIcon({
//                 className: '', // remove Leaflet default styling
//                 html: `
//                     <div class="relative w-8 h-8"> 
                   
//                         <div class="absolute inset-0 flex items-center justify-center">
//                             <div class="w-6 h-6 bg-yellow-200 rounded-full border-2 "></div>
//                         </div>
//                     </div>
//                 `,
//                 iconSize: [32, 32],      // size of DivIcon
//                 iconAnchor: [16, 16]     // center of DivIcon is map location
//             });
//         }
//         else{
//                 // tempicon = L.marker();
//         }
       
//     //    const leafletmarker = L.marker([marker.lat, marker.lng], {icon:tempicon})
//     //         .addTo(map)
//     //      // .bindPopup(marker.title || 'Marker ' + marker.id);
//     //         .bindPopup(popupContent, {
//     //     // maxWidth: 250,
//     //         minWidth: 180
//     //     // className: 'custom-popup' // optional for extra styling
//     //         });
//         const newMarker = L.marker([coord.marker.lat, coord.marker.lng],{icon:tempicon})
//             .bindPopup(popupContent, {
//             minWidth: 100,
//             });
//             var accuracy = 5240;

//         const circular = L.circle([coord.marker.lat, coord.marker.lng], {radius:accuracy});
//               if (coord.marker.markerCategory_id == 1) {
//                 // stationLayer.addLayer(newMarker);
//                 stationLayer.addLayer(newMarker);
//                 // const featureGroup = L.featureGroup(stationLayer).addTo(map);
//                 // map.fitBounds(featureGroup.getBounds());
//             } else if (coord.marker.markerCategory_id == 3) {
//                 incidentLayer.addLayer(newMarker);
//             } else if (coord.marker.markerCategory_id == 4) {
//                 hydrantLayer.addLayer(newMarker);
//             } else {
//                 otherLayer.addLayer(newMarker);
//             }
//             // newMarker.addTo(map); //add to map kagad dito mag display kagad
//         newMarker.on('popupopen', function () {

//             map.setView([coord.marker.lat,coord.marker.lng, coord.marker.lng], 13);
//             if(coord.marker.markerCategory_id != 2 && coord.marker.markerCategory_id != 1){
//                 // console.log('hello');
//             // deleteBtn = document.getElementById('delete' + marker.marker_id);

//             }else{

//             }
//         const Btn = document.getElementById('delete' + coord.marker.marker_id);
//             if (Btn) {
//                 // console.log('true');
//                 Btn.addEventListener('click', function () {

//                     deleteMarker(coord.marker.marker_id);

//                 });
//             }
            
//              const edBtn = document.getElementById('edit' + coord.marker.marker_id);
//             if (edBtn) {
//                 // console.log('true');
//                 edBtn.addEventListener('click', function () {
//  // get the form elements
//                 const mId = document.getElementById('markerid');
//                 const title = document.getElementById('editTitle');
//                 const desc = document.getElementById('editDesc');
//                 const cat = document.getElementById('editCat');
//                 const modal = document.getElementById('updateModal'); // if you want to show it
                        
//                 if (!mId || !title || !desc || !cat || !modal) {
//                     console.error('Edit form or modal elements not found in DOM');
//                     return;
//                 }

//         // fill the form
//         mId.value = coord.marker.marker_id;
//         title.value = coord.marker.title;
//         desc.value = coord.marker.description;
//         cat.value = coord.marker.markerCategory_id;

//         // show the modal (depends on your modal library)
//         modal.style.display = 'block';
//                 });
//             }
//         const respondBtn = document.getElementById('respond' + coord.marker.marker_id);
//          if (respondBtn) {
//                 // console.log('true');
//                 respondBtn.addEventListener('click', function () {

//                     // respond logic here 

//                 });
//             }
//         })
           
//         ;

//         existingMarkers[coord.marker.marker_id] = newMarker;

             

        
//     } //marker end

//     });

//     // stationArray.forEach(element => {
//     //     console.log(element);
//     // });
// }

//marker model
// function updateMapMarkers(markers) {
// let stationArray =[];
// let stationLat = [];
// let stationLng = [];
//     markers.forEach(marker => {
//             //temporary here: center natin is ung sa urbiz sample
//         // prevent duplicate marker
//         if (existingMarkers[marker.marker_id]) return;
//             if (marker.lat && marker.lng) {
//                 const popupContent = `
//             <div class="flex flex-col gap-2 w-48">
//                 <strong class="text-sm font-bold">${marker.title || 'Marker ' + marker.id}</strong>
//                 <p class="text-xs text-gray-600">${marker.description || 'No description'}</p>

//                 <!-- Image container -->
//                 <div class="w-full h-24 bg-gray-100 flex items-center justify-center border rounded">
//                     ${marker.image ? `<img src="${marker.image}" class="object-cover w-full h-full rounded" />` 
//                                     : `<span class="text-gray-400 text-xs">No image</span>`}
//                     <input type='hidden' value='${marker.marker_id}' id='mid'>
//                 </div>

//                 <!-- Action button -->
//                ${
//                     marker.markerCategory_id == 3
//                     ? `<button id="respond${marker.marker_id}"
//                         class="bg-blue-600 text-white text-xs px-2 py-1 rounded hover:bg-blue-700">
//                         Respond
//                     </button>
//                     `

//                     : `<div class="bg-white-600" style="display:flex; justify-content:space-evenly;"> <button style="width: 50%;" class="bg-green-600 text-white w-50 text-xs px-2 py-1 rounded hover:bg-blue-700">Edit</button><button id="delete${marker.marker_id}"
//                         class="bg-red-600 text-white text-xs px-2 py-1 rounded hover:bg-blue-700"  style="width: 40%;">
//                         Delete
//                     </button> </div>`
//                 }
//             </div>
//         `;
//     //   id="deleteone"  onclick="deleteMarker(${marker.id})">
//         //btn delete
         
           

//         let tempicon;
       
//         // console.log("datapd", datap);
//         if(marker.markerCategory_id == 1){

//            tempicon = L.divIcon({
//                 className: '', // remove Leaflet default styling
//                 html: `
//                     <div class="relative w-6 h-6">
//                                         <!-- base square for station -->
//                                         <div class="absolute inset-0 bg-blue-600 rounded-sm border-2 border-white"></div>
//                                         <!-- antenna / top indicator -->
//                                         <div class="absolute top-0 left-1/2 transform -translate-x-1/2 w-1 h-2 bg-white rounded-sm"></div>
//                     </div>
//                 `,
//                 iconSize: [32, 32],      // size of DivIcon
//                 iconAnchor: [16, 16]     // center of DivIcon is map location
//             });
        
//         }else if(marker.markerCategory_id == 3){
//             // tempicon = null;
//             tempicon = L.divIcon({
//                 className: '', // remove Leaflet default styling
//                 html: `
//                     <div class="relative w-8 h-8"> 
//                         <div class="absolute inset-0 bg-orange-600 rounded-full animate-ping opacity-75"></div>
//                         <div class="absolute inset-0 flex items-center justify-center">
//                             <div class="w-6 h-6 bg-orange-600 rounded-full border-2 "></div>
//                         </div>
//                     </div>
//                 `,
//                 iconSize: [32, 32],      // size of DivIcon
//                 iconAnchor: [16, 16]     // center of DivIcon is map location
//             });
//             //  fire.addLayer(routeLayer);
//             // getRoute(15.8268037, 120.3382279, marker.lat, marker.lng,'map');
//         }else{
//                 tempicon = null;
//         }
        
//     //    const leafletmarker = L.marker([marker.lat, marker.lng], {icon:tempicon})
//     //         .addTo(map)
//     //      // .bindPopup(marker.title || 'Marker ' + marker.id);
//     //         .bindPopup(popupContent, {
//     //     // maxWidth: 250,
//     //         minWidth: 180
//     //     // className: 'custom-popup' // optional for extra styling
//     //         });
//         const newMarker = L.marker([marker.lat, marker.lng],{icon:tempicon})
//             .addTo(map)
//             // .bindPopup(marker.title);
//             .bindPopup(popupContent, {
//         // maxWidth: 250,
//             minWidth: 180
//         // className: 'custom-popup' // optional for extra styling
//             });
//         newMarker.on('popupopen', function () {

//             if(marker.markerCategory_id != 2 && marker.markerCategory_id != 1){
//                 // console.log('hello');
//             // deleteBtn = document.getElementById('delete' + marker.marker_id);

//             }else{

//             }
//         const Btn = document.getElementById('delete' + marker.marker_id);
//             if (Btn) {
//                 // console.log('true');
//                 Btn.addEventListener('click', function () {

//                     deleteMarker(marker.marker_id);

//                 });
//             }
//         const respondBtn = document.getElementById('respond' + marker.marker_id);
//          if (respondBtn) {
//                 // console.log('true');
//                 respondBtn.addEventListener('click', function () {

//                     // respond logic here 

//                 });
//             }
//         });

//         existingMarkers[marker.marker_id] = newMarker;

             

        
//     } //marker end

//     });

//     // stationArray.forEach(element => {
//     //     console.log(element);
//     // });
// }

// fetchMarkers();

// auto refresh every 10 sec
// async function updateCoordinate(coordinates) {

//       try {
//         const response = await fetch('/api/incident/latest');
//         if (!response.ok) {
//             console.error('Failed to fetch incidents');
//             return;
//         }
//         const incidents = await response.json();
//         console.log('Fetched incidents:', incidents); // Debug log
        
//           coordinates.forEach(coord => {
//         if (existingMarkers[coord.marker.marker_id]) return;

//         if (!coord.marker.lat || !coord.marker.lng) return;
// const popupContent = `
//     <div class="flex flex-col gap-2 w-48">
//         <strong class="text-sm font-bold">${coord.marker.title || 'Marker ' + coord.marker.marker_id}</strong>
//         <p class="text-xs text-gray-600">${coord.marker.description || 'No description'}</p>
//         <div class="w-full h-24 bg-gray-100 flex items-center justify-center border rounded">
//             ${coord.image?.multimedia_id
//                 ? `<img src="${coord.image.path ? '/storage/' + coord.image.path : '/images/default.png'}" class="object-cover w-full h-full rounded" />`
//                 : `<span class="text-gray-400 text-xs">No image</span>`}
//             <input type='hidden' value='${coord.marker.marker_id}' id='mid'>
//         </div>
//         ${
//             (() => {
//                 // First check if this is a category 3 marker
//                 if (coord.marker.markerCategory_id == 3) {
//                     // Check if there's ANY incident with matching marker_id
//                     const matchingIncident = incidents.find(incident => 
//                         incident.marker.marker_id === coord.marker.marker_id
//                     );
                    
//                     // Only show respond button if there's a matching incident AND status is "reported"
//                     if (matchingIncident && matchingIncident.status === "reported") {
//                         return `<button id="respond${coord.marker.marker_id}" class="bg-blue-600 text-white text-xs px-2 py-1 rounded hover:bg-blue-700">Respond</button>`;
//                     }
//                     return ""; // No matching incident or wrong status
//                 } else {
//                     // Non-category 3 markers - show Edit/Delete
//                     return `<div class="bg-white-600" style="display:flex; justify-content:space-evenly;">
//                         <button style="width: 50%;" class="bg-green-600 text-white text-xs px-2 py-1 rounded hover:bg-blue-700" id="edit${coord.marker.marker_id}">Edit</button>
//                         <button id="delete${coord.marker.marker_id}" class="bg-red-600 text-white text-xs px-2 py-1 rounded hover:bg-blue-700" style="width: 40%;">Delete</button>
//                     </div>`;
//                 }
//             })()
//         }
//     </div>
// `;
//         // const popupContent = `
//         //     <div class="flex flex-col gap-2 w-48">
//         //         <strong class="text-sm font-bold">${coord.marker.title || 'Marker ' + coord.marker.marker_id}</strong>
//         //         <p class="text-xs text-gray-600">${coord.marker.description || 'No description'}</p>
//         //         <div class="w-full h-24 bg-gray-100 flex items-center justify-center border rounded">
//         //             ${coord.image?.multimedia_id
//         //                 ? `<img src="${coord.image.path ? '/storage/' + coord.image.path : '/images/default.png'}" class="object-cover w-full h-full rounded" />`
//         //                 : `<span class="text-gray-400 text-xs">No image</span>`}
//         //             <input type='hidden' value='${coord.marker.marker_id}' id='mid'>
//         //         </div>
//         //         ${
                    
//         //             coord.marker.markerCategory_id == 3 ?
//         //               coord.marker.marker_id === incidents.marker.marker_id  ?
//         //              `<button id="respond${coord.marker.marker_id}" class="bg-blue-600 text-white text-xs px-2 py-1 rounded hover:bg-blue-700">Respond</button>`
//         //             : " "
//         //             :`<div class="bg-white-600" style="display:flex; justify-content:space-evenly;">
//         //                 <button style="width: 50%;" class="bg-green-600 text-white text-xs px-2 py-1 rounded hover:bg-blue-700" id="edit${coord.marker.marker_id}">Edit</button>
//         //                 <button id="delete${coord.marker.marker_id}" class="bg-red-600 text-white text-xs px-2 py-1 rounded hover:bg-blue-700" style="width: 40%;">Delete</button>
//         //               </div>`
//         //         }
//         //     </div>
//         // `;

//         // Create icon
//         let tempIcon;
//         switch (coord.marker.markerCategory_id) {
//             case 1: // Station
//                 tempIcon = L.divIcon({
//                     className: '',
//                     html: `<div class="relative w-6 h-6">
//                             <div class="absolute inset-0 bg-blue-600 rounded-sm border-2 border-white"></div>
//                             <div class="absolute top-0 left-1/2 transform -translate-x-1/2 w-1 h-2 bg-white rounded-sm"></div>
//                            </div>`,
//                     iconSize: [32, 32],
//                     iconAnchor: [16, 16]
//                 });
//                 break;
//             case 3: // Fire
//                 tempIcon = L.divIcon({
//                     className: '',
//                     html: `<div class="relative w-8 h-8">
//                             <div class="absolute inset-0 bg-orange-600 rounded-full animate-ping opacity-75"></div>
//                             <div class="absolute inset-0 flex items-center justify-center">
//                                 <div class="w-6 h-6 bg-orange-600 rounded-full border-2"></div>
//                             </div>
//                            </div>`,
//                     iconSize: [32, 32],
//                     iconAnchor: [16, 16]
//                 });
//                 break;
//             case 4: // Hydrant
//                 tempIcon = L.divIcon({
//                     className: '',
//                     html: `<div class="relative w-8 h-8">
//                             <div class="absolute inset-0 flex items-center justify-center">
//                                 <div class="w-6 h-6 bg-yellow-200 rounded-full border-2"></div>
//                             </div>
//                            </div>`,
//                     iconSize: [32, 32],
//                     iconAnchor: [16, 16]
//                 });
//                 break;
//             default:
//                 tempIcon = L.divIcon(); // default Leaflet marker
//         }

//         // Marker + Circle if needed
//         const newMarker = L.marker([coord.marker.lat, coord.marker.lng], {icon: tempIcon})
//             .bindPopup(popupContent, { minWidth: 100 });

//         // Optional: circle for accuracy or visual radius
//         const circle = L.circle([coord.marker.lat, coord.marker.lng], { radius: 200 });
//         // const nearestStation =  getStations(coord.marker.lat, coord.marker.lng);

//         // Add to respective FeatureGroup
//         if (coord.marker.markerCategory_id == 1) {
//             stationFeatureGroup.addLayer(newMarker);
//             // stationFeatureGroup.addLayer(circle);
//         } else if (coord.marker.markerCategory_id == 3) {
//             incidentFeatureGroup.addLayer(newMarker);
//             incidentFeatureGroup.addLayer(circle);

//             // L.Routing.control({ routing machine
//             //     waypoints: [
//             //         L.latLng(),
//             //         L.latlng()
//             //     ]
//             // });
//             // if (nearestStation) { sample
//         // const routeControl = L.Routing.control({
//         //     waypoints: [
//         //         L.latLng(15.804509703441, 120.32244885485),
//         //         L.latLng(coord.marker.lat, coord.marker.lng)
//         //     ],
//         //     show: false, // hide the default UI
//         //     addWaypoints: false,
//         //     lineOptions: {
//         //         styles: [{color: 'red', opacity: 0.7, weight: 4}]
//         //     },
//         //     createMarker: function() { return null; } // we already have markers
//         // }).on('routesfound',function(e){
//         //     console.log(e);
//         //     e.routes[0].coordinates.forEach(function(coord, index){
//         //         setTimeout(()=>{
//         //             newMarker.setLatLng([coord.lat, coord.lng])
//         //         }, 100 * index)
//         //     })
//         // })
//         // .addTo(map);

//         // Optional: store in global array if you want to manage later
//         // if (!window.routes) window.routes = [];
//         // window.routes.push(routeControl);
//     // }
//         } else if (coord.marker.markerCategory_id == 4) {
//             hydrantFeatureGroup.addLayer(newMarker);
//         } else {
//             otherFeatureGroup.addLayer(newMarker);
//         }

//         // Event listeners for popups
//         newMarker.on('popupopen', () => {
//             const deleteBtn = document.getElementById('delete' + coord.marker.marker_id);
//             const editBtn = document.getElementById('edit' + coord.marker.marker_id);
//             const respondBtn = document.getElementById('respond' + coord.marker.marker_id);

//             if (deleteBtn) deleteBtn.addEventListener('click', () => deleteMarker(coord.marker.marker_id));
//             if (editBtn) editBtn.addEventListener('click', () => openEditModal(coord.marker));
//             if (respondBtn) respondBtn.addEventListener('click', () => respondIncident(coord.marker));
//         });

//         existingMarkers[coord.marker.marker_id] = newMarker;
//         // map.fitBounds(stationFeatureGroup.getBounds()); //para mag fit lang
//     });
        
//     } catch (error) {
//         console.error('Error fetching incidents:', error);
//     }
        
  
// }
async function updateCoordinate(coordinates) {
    try {
        // Fetch incidents for status checking
        const incidentResponse = await fetch('/api/incident/latest');
        if (!incidentResponse.ok) {
            console.error('Failed to fetch incidents');
            return;
        }
        const incidents = await incidentResponse.json();
        console.log('Fetched incidents:', incidents); // Debug log
        
        // Process each coordinate
        coordinates.forEach(coord => {
            if (!coord.marker?.lat || !coord.marker?.lng) return;

            const popupContent = generatePopupContent(coord, incidents);
            const tempIcon = getMarkerIcon(coord.marker.markerCategory_id);
            
            // Create marker
            const newMarker = L.marker([coord.marker.lat, coord.marker.lng], {icon: tempIcon})
                .bindPopup(popupContent, { minWidth: 100 });

            // Add circle for incident markers
            if (coord.marker.markerCategory_id == 3) {
                const circle = L.circle([coord.marker.lat, coord.marker.lng], { radius: 200 });
                incidentFeatureGroup.addLayer(circle);
            }

            // Add to appropriate feature group
            addMarkerToFeatureGroup(coord.marker.markerCategory_id, newMarker);

            // Add event listeners
            newMarker.on('popupopen', () => addPopupEventListeners(coord));

            // Store marker reference
            existingMarkers[coord.marker.marker_id] = newMarker;
        });
        
    } catch (error) {
        console.error('Error updating coordinates:', error);
    }
}
function generatePopupContent(coord, incidents) {
    return `
        <div class="flex flex-col gap-2 w-48">
            <strong class="text-sm font-bold">${coord.marker.title || 'Marker ' + coord.marker.marker_id}</strong>
            <p class="text-xs text-gray-600">${coord.marker.description || 'No description'}</p>
            <div class="w-full h-24 bg-gray-100 flex items-center justify-center border rounded">
                ${coord.image?.multimedia_id
                    ? `<img src="${coord.image.path ? '/storage/' + coord.image.path : '/images/default.png'}" class="object-cover w-full h-full rounded" />`
                    : `<span class="text-gray-400 text-xs">No image</span>`}
                <input type='hidden' value='${coord.marker.marker_id}' id='mid'>
            </div>
            ${getActionButtons(coord, incidents)}
        </div>
    `;
}

function getActionButtons(coord, incidents) {
    // Category 3 (Incident) markers
    if (coord.marker.markerCategory_id == 3) {
        const matchingIncident = incidents.find(incident => 
            incident.marker?.marker_id === coord.marker.marker_id
        );
        
        // Only show respond button if status is "reported"
        if (matchingIncident && matchingIncident.status === "reported") {
            return `<button id="respond${coord.marker.marker_id}" 
                        class="bg-blue-600 text-white text-xs px-2 py-1 rounded hover:bg-blue-700">
                        Respond
                    </button>`;
        }
        return ""; // No button for non-reported incidents
    } 
    // Other categories - show Edit/Delete
    else {
        return `<div class="bg-white-600" style="display:flex; justify-content:space-evenly;">
                    <button style="width: 50%;" class="bg-green-600 text-white text-xs px-2 py-1 rounded hover:bg-blue-700" 
                        id="edit${coord.marker.marker_id}">Edit</button>
                    <button id="delete${coord.marker.marker_id}" 
                        class="bg-red-600 text-white text-xs px-2 py-1 rounded hover:bg-blue-700" 
                        style="width: 40%;">Delete</button>
                </div>`;
    }
}

function getMarkerIcon(categoryId) {
    switch (categoryId) {
        case 1: // Station
            return L.divIcon({
                className: '',
                html: `<div class="relative w-6 h-6">
                        <div class="absolute inset-0 bg-blue-600 rounded-sm border-2 border-white"></div>
                        <div class="absolute top-0 left-1/2 transform -translate-x-1/2 w-1 h-2 bg-white rounded-sm"></div>
                       </div>`,
                iconSize: [32, 32],
                iconAnchor: [16, 16]
            });
        case 3: // Fire
            return L.divIcon({
                className: '',
                html: `<div class="relative w-8 h-8">
                        <div class="absolute inset-0 bg-orange-600 rounded-full animate-ping opacity-75"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="w-6 h-6 bg-orange-600 rounded-full border-2"></div>
                        </div>
                       </div>`,
                iconSize: [32, 32],
                iconAnchor: [16, 16]
            });
        case 4: // Hydrant
            return L.divIcon({
                className: '',
                html: `<div class="relative w-8 h-8">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="w-6 h-6 bg-yellow-200 rounded-full border-2"></div>
                        </div>
                       </div>`,
                iconSize: [32, 32],
                iconAnchor: [16, 16]
            });
        default:
            return L.divIcon(); // default Leaflet marker
    }
}

function addMarkerToFeatureGroup(categoryId, marker) {
    switch (categoryId) {
        case 1:
            stationFeatureGroup.addLayer(marker);
            break;
        case 3:
            incidentFeatureGroup.addLayer(marker);
            break;
        case 4:
            hydrantFeatureGroup.addLayer(marker);
            break;
        default:
            otherFeatureGroup.addLayer(marker);
    }
}

function addPopupEventListeners(coord) {
    const deleteBtn = document.getElementById('delete' + coord.marker.marker_id);
    const editBtn = document.getElementById('edit' + coord.marker.marker_id);
    const respondBtn = document.getElementById('respond' + coord.marker.marker_id);

    if (deleteBtn) {
        deleteBtn.addEventListener('click', () => deleteMarker(coord.marker.marker_id));
    }
    
    if (editBtn) {
        editBtn.addEventListener('click', () => openEditModal(coord.marker));
    }
    
    if (respondBtn) {
        respondBtn.addEventListener('click', () => respondIncident(coord.marker));
    }
}
setInterval(fetchCoordinate, 10000); //every 10 secs
setInterval(async () => {
    const coords = await fetch('/api/coordinate/latest').then(r => r.json());
    // if(shapeChecker){loadShapes();} //load shapes if may value
    updateCoordinate(coords); // possibly adds markers to the map
}, 5000);
// const markerLayer = L.layerGroup().addTo(map);

// Fetch and render markers
// function loadMapMarkers() {
//     fetch('/api/markers')
//         .then(res => res.json())
//         .then(markers => {
//             markerLayer.clearLayers(); // clear only the markers

//             markers.forEach(marker => {
//                 const m = L.marker([marker.lat, marker.lng])
//                             .bindPopup(`<strong>${marker.title}</strong>
//                                         <br>
//                                         <button onclick="deleteMarker(${marker.marker_id})">Delete</button>`);
//                 markerLayer.addLayer(m);
//             });
//         });
// }

const drawnItems = new L.FeatureGroup();
map.addLayer(drawnItems);

// Draw control
const drawControl = new L.Control.Draw({
    edit: { featureGroup: drawnItems },
    draw: {
        polyline: true,   // draw lines
        polygon: true,    // draw polygons
        rectangle: true,  // draw rectangles
        circle: true,     // draw circles
        marker: true,     // draw points
        circlemarker: false
    }
});
map.addControl(drawControl);
// When a shape is created
// map.on(L.Draw.Event.CREATED, function (event) {
//     const layer = event.layer;
//     drawnItems.addLayer(layer); // add to map

//     // Get shape coordinates
//     if(layer instanceof L.Polyline && !(layer instanceof L.Polygon)){
//         // Line
//         console.log('Polyline coordinates:', layer.getLatLngs());
//     } else if(layer instanceof L.Polygon){
//         console.log('Polygon coordinates:', layer.getLatLngs());
//     } else if(layer instanceof L.Circle){
//         console.log('Circle center & radius:', layer.getLatLng(), layer.getRadius());
//     } else if(layer instanceof L.Rectangle){
//         console.log('Rectangle bounds:', layer.getBounds());
//     } else if(layer instanceof L.Marker){
//         console.log('Marker position:', layer.getLatLng());
//     }

//     const geojson = layer.toGeoJSON(); // converts drawn shape to GeoJSON
//     console.log(geojson);
// });
map.on(L.Draw.Event.CREATED, function (event) {
    const layer = event.layer;
    drawnItems.addLayer(layer);

    const geojson = layer.toGeoJSON();
    console.log('Drawn shape:', geojson);

    // Send to server
    fetch('/api/shapes', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            name: 'My Shape', // optional, or prompt user
            geojson: geojson
        })
    })
    .then(res => res.json())
    .then(data => console.log('Shape saved:', data))
    .catch(err => console.error(err));

    if(data){
    loadShapes();
    }
});
const colors = [
    "#ef4444",
    "#3b82f6",
    "#22c55e",
    "#f59e0b",
    "#8b5cf6",
    "#06b6d4"
];
// Load saved shapes
async function loadShapes() {
    // const response = await fetch('/api/shapes');
    // const shapes = await response.json();

    // shapes.forEach(shape => {
    //     // const layer = L.geoJSON(JSON.parse(shape.geojson));
    //     const layer = L.geoJSON(shape.geojson);
    //     layer.addTo(map);
    // });
    const response = await fetch('/api/shapes');
    const shapes = await response.json();

    shapes.forEach((shape,index) => {

        let geojson = shape.geojson;

        if (typeof geojson === "string") {
            geojson = JSON.parse(geojson);
        }

        const layer = L.geoJSON(geojson,{
             style: {
                color: colors[index % colors.length], // cycle colors
                weight: 3,
                fillOpacity: 0.4
            }
        }).addTo(map);
        if(layer){
            shapeChecker = true;
        }
        layer.bindTooltip(shape.name || "Area", {
            permanent: false,   // only show on hover
            direction: "top",   // where label appears
            sticky: true        // follows mouse
        });
    });
}

// Call this when map is ready
loadShapes();
// Handle edits
map.on('draw:edited', function (event) {
    const layers = event.layers;
    layers.eachLayer(function (layer) {
        console.log('Edited layer coordinates:', layer.getLatLngs ? layer.getLatLngs() : layer.getLatLng());
    });
});

// Handle deletion
map.on('draw:deleted', function (event) {
    const layers = event.layers;
    layers.eachLayer(function (layer) {
        console.log('Deleted layer');
    });
});
//handle sending data

//andito pati delete
// loadMarker();
//  function loadMarker(){
//           markermap.forEach(function(marker) {
      
//     if (marker.lat && marker.lng) {
        

//             ids = marker.marker_id;
//              const popupContent = `
//             <div class="flex flex-col gap-2 w-48">
//                 <strong class="text-sm font-bold">${marker.title || 'Marker ' + marker.id}</strong>
//                 <p class="text-xs text-gray-600">${marker.description || 'No description'}</p>

//                 <!-- Image container -->
//                 <div class="w-full h-24 bg-gray-100 flex items-center justify-center border rounded">
//                     ${marker.image ? `<img src="${marker.image}" class="object-cover w-full h-full rounded" />` 
//                                     : `<span class="text-gray-400 text-xs">No image</span>`}
//                     <input type='hidden' value='${marker.marker_id}' id='mid'>
//                 </div>

//                 <!-- Action button -->
               
//                 <button id="${'delete'+marker.marker_id}" class="bg-red-600 text-white text-xs px-2 py-1 rounded hover:bg-blue-700" >
//                     Delete
//                 </button>
//             </div>
//         `;
//     //   id="deleteone"  onclick="deleteMarker(${marker.id})">
//         //btn delete
         
//             // console.log(jsid);
//             if(marker.markerCategory_id == 1){
//              station(marker.marker_id, marker.description);
//             //  getRoute(marker.lat, marker.lng);
//             }
            

//         let tempicon;
       
//         // console.log("datapd", datap);
//         if(marker.markerCategory_id == 1){

//            tempicon = L.divIcon({
//                 className: '', // remove Leaflet default styling
//                 html: `
//                     <div class="relative w-6 h-6">
//                                         <!-- base square for station -->
//                                         <div class="absolute inset-0 bg-blue-600 rounded-sm border-2 border-white"></div>
//                                         <!-- antenna / top indicator -->
//                                         <div class="absolute top-0 left-1/2 transform -translate-x-1/2 w-1 h-2 bg-white rounded-sm"></div>
//                     </div>
//                 `,
//                 iconSize: [32, 32],      // size of DivIcon
//                 iconAnchor: [16, 16]     // center of DivIcon is map location
//             });
        
//         }else if(marker.markerCategory_id == 3){
//             // tempicon = null;
//             tempicon = L.divIcon({
//                 className: '', // remove Leaflet default styling
//                 html: `
//                     <div class="relative w-8 h-8"> 
//                         <div class="absolute inset-0 bg-orange-600 rounded-full animate-ping opacity-75"></div>
//                         <div class="absolute inset-0 flex items-center justify-center">
//                             <div class="w-6 h-6 bg-orange-600 rounded-full border-2 "></div>
//                         </div>
//                     </div>
//                 `,
//                 iconSize: [32, 32],      // size of DivIcon
//                 iconAnchor: [16, 16]     // center of DivIcon is map location
//             });
//         }else{
//                 tempicon = null;
//         }
        
//        const leafletmarker = L.marker([marker.lat, marker.lng], {icon:tempicon})
//             .addTo(map)
//          // .bindPopup(marker.title || 'Marker ' + marker.id);
//             .bindPopup(popupContent, {
//         // maxWidth: 250,
//             minWidth: 180
//         // className: 'custom-popup' // optional for extra styling
//             });

//                     leafletmarker.on('popupopen', function () {

//             const deleteBtn = document.getElementById('delete' + marker.marker_id);

//             if (deleteBtn) {
//                 console.log('true');
//                 deleteBtn.addEventListener('click', function () {

//                     deleteMarker(marker.marker_id);

//                 });
//             }
//         });
             

        
//     } //marker end
       
// });
//  }
// function deleteMarker(value){

//     fetch(`/api/markers/${value}`, {
//         method: 'DELETE',
//         headers: {
//             'Accept': 'application/json',
//             'Content-Type': 'application/json'
//         }
//     })
//     .then(res => {
//         if (!res.ok) {
//             throw new Error("Server error");
//         }
//         return res.json();
//     })
//     .then(data => {
//         console.log("deleted:", data);
//          if(existingMarkers[value]){
//             map.removeLayer(existingMarkers[value]);
//             delete existingMarkers[value];
//         }
//     })
//     .catch(err => console.error(err));

//     // loadMapMarkers();
// }
function deleteMarker(value) {
    fetch(`/api/markers/${value}`, {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(res => {
        if (!res.ok) {
            throw new Error("Server error");
        }
        return res.json();
    })
    .then(data => {
        console.log("deleted:", data);
        
        // Immediately refresh markers
        fetchCoordinate();
    })
    .catch(err => console.error(err));
}
    let startMarker = null;
    let endMarker = null;
    let routeLayer = null;

    // const apiKey = "{{ env('GEOAPIFY_KEY') }}";
//    var popup = L.popup()
//     .setLatLng(latlng)
//     .setContent('<p>Hello world!<br />This is a nice popup.</p>')
//     .openOn(map);

//     map.on('click', function (e) {
          
//         startMarker = e.latlng.lat;
//         endMarker = e.latlng.lng;
//            const marker = L.marker([startMarker, endMarker])
//         .addTo(map)
//         .bindPopup(`Start: ${startMarker}, End: ${endMarker}`)  // bind popup here
//         .openPopup();  

//         getName(startMarker, endMarker);
         

//         // popup.bindPopup(popupContent).openPopup();
// //   const title = prompt('Enter marker title:');
// //         if (!title) return;
// //         const description = prompt('Enter description:');
// //         fetch('/markers', {
// //             method: 'POST',
// //             headers: {
// //                 'Content-Type': 'application/json',
// //                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
// //             },
// //             body: JSON.stringify({ title, description, lat: e.latlng.lat, lng: e.latlng.lng })
// //         }).then(() => location.reload());
//     });

//markers showing
//kada click else ko dito siguro para sa drawing line
    map.on('click', function (e) { //getnaming to address
            // startMarker = L.marker(e.latlng).addTo(map);
            // getName(e.latlng.lat, e.latlng.lng);

            if(!startMarker) {
                startMarker = L.marker(e.latlng).addTo(map);
                getName(startMarker.getLatLng().lat, startMarker.getLatLng().lng);
                return;
            }
        map.removeLayer(startMarker);
            startMarker = L.marker(e.latlng).addTo(map);
            getName(startMarker.getLatLng().lat, startMarker.getLatLng().lng);
            
  });
        // if (!startMarker) {
        //     startMarker = L.marker(e.latlng).addTo(map);
        //     getName(startMarker.getLatLng().lat, startMarker.getLatLng().lng);
            
        //     return;
        // }

        // // if (!endMarker) {
        // //     endMarker = L.marker(e.latlng).addTo(map);
        // //     // getRoute(startMarker.getLatLng(), endMarker.getLatLng());
        
        // //     return;
        // // }
      

        // map.removeLayer(startMarker);
        // // map.removeLayer(endMarker);
        // if (routeLayer) map.removeLayer(routeLayer);

        // startMarker = null;
        // endMarker = null;
        // routeLayer = null;
    // }); end ng map.onclick
   


// function addMarker(lat, lon) {
//     // Add marker to map immediately
//     L.marker([lat, lon]).addTo(map);

//     // Save to database
//     fetch('/admin/markers', {
//         method: 'POST',
//         headers: {  
//             'Content-Type': 'application/json',
//             'X-CSRF-TOKEN': csrfToken
//         },
//         body: JSON.stringify({ 
//             latitude: lat,
//             longitude: lon,
//             name: 'Sample Location'
//         })  
//     })
//     .then(res => res.json())
//     .then(data => {
//         console.log('Marker saved:', data);
//         alert(data.message);
//     })
//     .catch(err => {
//         console.error('Error saving marker:', err);
//         alert('Failed to save marker. Check console.');
//     });
// }
     

//     function getRoute(start, end) {

//         const url =
//             "https://api.geoapify.com/v1/routing?" +
//             `waypoints=${start.lat},${start.lng}|${end.lat},${end.lng}&` +
//             "mode=drive&" +
//             "type=balanced&" +
//             `apiKey=${apiKey}`;

//             fetch('/api/route', { // now it’s /route, not /api/route
//     method: 'POST',
//     headers: {
//         'Content-Type': 'application/json',
//          'X-CSRF-TOKEN': csrfToken
//         //  'X-CSRF-TOKEN': '{{ csrf_token() }}'
//         // 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
//     },
//     body: JSON.stringify({
//         start: start.lat + "," + start.lng,
//         end: end.lat + "," + end.lng
//     })
// })
// .then(res => res.json())
// .then(data => {
//     if (!data.features || data.features.length === 0) return;


//         console.log('API Data:', data); // view in browser console

//         // Add markers from API data
//         // data.forEach(item => {
//         //     L.marker([item.lat, item.lng]).addTo(map)
//         //      .bindPopup(item.name);
//         // });
//     const geometry = data.features[0].geometry;
//     if (routeLayer) map.removeLayer(routeLayer);
//     routeLayer = L.geoJSON(geometry, { color: 'blue', weight: 5 }).addTo(map);
// });
//     }

     (function() { //sidebar
            const sidebar = document.getElementById('sidebar');
            const openBtn = document.getElementById('openSidebar');
            const closeBtn = document.getElementById('closeSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');

            function openSidebar() {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
                document.body.classList.add('menu-open');
                // Prevent background scrolling
                document.body.style.overflow = 'hidden';
            }
            
            function closeSidebarFunc() {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
                document.body.classList.remove('menu-open');
                // Restore scrolling
                document.body.style.overflow = '';
            }

            if (openBtn) {
                openBtn.addEventListener('click', openSidebar);
            }
            
            if (closeBtn) {
                closeBtn.addEventListener('click', closeSidebarFunc);
            }
            
            if (backdrop) {
                backdrop.addEventListener('click', closeSidebarFunc);
            }

            // Close sidebar on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !sidebar.classList.contains('-translate-x-full')) {
                    closeSidebarFunc();
                }
            });

            // Handle resize events
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 1024) { // lg breakpoint
                    // On desktop, ensure sidebar is visible
                    sidebar.classList.remove('-translate-x-full');
                    backdrop.classList.add('hidden');
                    document.body.classList.remove('menu-open');
                    document.body.style.overflow = '';
                } else {
                    // On mobile, ensure sidebar is hidden by default
                    sidebar.classList.add('-translate-x-full');
                    backdrop.classList.add('hidden');
                    document.body.classList.remove('menu-open');
                    document.body.style.overflow = '';
                }
            });

            // Initialize - ensure correct state on page load
            if (window.innerWidth < 1024) {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
            } else {
                sidebar.classList.remove('-translate-x-full');
            }
        })();

        // initSidebar();
});
// async function saveMarker(lat, lng, title, description, categoryId) {
//     try {
//         // First save the marker
//         const markerRes = await fetch('/api/markers', {
//             method: 'POST',
//             headers: {
//                 'Content-Type': 'application/json',
//                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
//             },
//             body: JSON.stringify({
//                 lat: lat,
//                 lng: lng,
//                 title: title,
//                 description: description,
//                 markerCategory_id: categoryId
//             })
//         });
        
//         if (!markerRes.ok) {
//             const errorText = await markerRes.text();
//             console.error('Marker save error:', errorText);
//             throw new Error('Failed to save marker');
//         }
        
//         const markerData = await markerRes.json();
//         const markerId = markerData.data.marker_id;
//         console.log("Marker created:", markerData, "ID:", markerId);

//         // Then save the coordinate
//         const coordRes = await fetch('/api/coordinate', { // Changed from '/api/coordinate' to '/api/coordinates'
//             method: 'POST',
//             headers: {
//                 'Content-Type': 'application/json',
//                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
//             },
//             body: JSON.stringify({
//                 marker_id: markerId,
//                 image_id: 'MMD-0001', // Send null for no image
//                 status: 'temporary'
//             })
//         });

//         if (!coordRes.ok) {
//             const errorText = await coordRes.text();
//             console.error('Coordinate save error:', errorText);
//             throw new Error('Failed to save coordinate');
//         }
        
//         const coordData = await coordRes.json();
//         console.log("Coordinate saved:", coordData);
//         if(categoryId == 1 || categoryId === 1){
//         // console.log("coord id: ", coordData.data.coordinate_id);

//             //pasok natin sa station 
//             const stationRes = await fetch( '/api/station', {
//                 method: 'POST',
//                 headers: {
//                     'Content-Type': 'application/json',
//                     'X-CSRF-TOKEN': csrfToken
//                 },
//                 body: JSON.stringify({
//                     station_name: title,
//                     station_chief: 'Test Chief', // Send null for no image
//                     coordinate_id: coordData.data.coordinate_id
//                 })
//             });
//             if (!stationRes.ok) {
//             const errorText = await stationRes.text();
//             console.error('Coordinate save error:', errorText);
//             throw new Error('Failed to save coordinate');
//              }
//             //  console.log("station saveD:", coordData);
//         }
//         //hydrant
//           if(categoryId == 4 || categoryId === 4){
//         // console.log("coord id: ", coordData.data.coordinate_id);

//             //pasok natin sa station 
//             const hydrantRes = await fetch( '/api/hydrant', {
//                 method: 'POST',
//                 headers: {
//                     'Content-Type': 'application/json',
//                     'X-CSRF-TOKEN': csrfToken
//                 },
//                 body: JSON.stringify({
//                     location_id:  coordData.data.coordinate_id,
//                     station_id: 'STN-0001', // galing nalang sa modal
//                     // coordinate_id: coordData.data.coordinate_id
//                 })
//             });
//             if (!hydrantRes.ok) {
//             const errorText = await hydrantRes.text();
//             console.error('Coordinate save error:', errorText);
//             throw new Error('Failed to save coordinate');
//              }
//             //  console.log("hydrant saveD:", hydrantRes);
           
//         }

//         // Refresh markers to show the new one
        
//         // Close modal and clear form
//         closeModal();
//         document.getElementById('markerTitle').value = '';
//         document.getElementById('markerDescription').value = '';
        
//         // alert('Marker saved successfully!');
        
//     } catch (err) {
//         console.error("Error saving marker or coordinate:", err);
//         alert('Error saving marker: ' + err.message);
//     }
//         // fetchMarkers();
//         fetchCoordinate();


// }
// async function respondIncident(location){

//     console.log("location data:", location);

//     try{

//         const response = await fetch('/api/incident/latest');
//         const incidents = await response.json();

//         let incidentId = null;
//         let station_id = null;

//         incidents.forEach(incident => {

//             if(location.marker_id == incident.marker.marker_id){

//                 incidentId = incident.incident_id;
//                 station_id = incident.station_id;

//             }

//         });

//         if(!incidentId){
//             console.log("No matching incident found");
//             return;
//         }

//         // INSERT INCIDENT REPORT
//         const incidentRes = await fetch('/api/incidentReport', {

//             method: 'POST',

//             headers: {
//                 'Content-Type': 'application/json',
//                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
//             },

//             body: JSON.stringify({
//                 incident_id: incidentId,
//                 victims: 0,
//                 severity: 'low',
//                 dateEnded: null
//             })

//         });

//         if (!incidentRes.ok) {

//             const errorText = await incidentRes.text();
//             console.error("Insert error:", errorText);
//             throw new Error("Failed to insert incident report");

//         }

//         // UPDATE INCIDENT STATUS
//         const updateRes = await fetch(`/api/incident/${incidentId}`, {

//             method: 'PUT',

//             headers: {
//                 'Content-Type': 'application/json',
//                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
//             },

//             body: JSON.stringify({
//                 station_id: station_id,
//                 status: "responding"
//             })

//         });

//         if (!updateRes.ok) {

//             const errorText = await updateRes.text();
// console.error("Laravel response:", errorText);
//             throw new Error("Failed to update incident");

//         }

//         console.log("Incident updated successfully");

//     }

//     catch (err) {

//         console.error("Error:", err);
//         alert("Error: " + err.message);

//     }

// }
// async function respondIncident(location){
//     console.log("location data: ", location);

//     console.log("location id: ", location.marker_id);


//     try{
//         const response = await fetch('/api/incident/latest');
//         const incidents = await response.json();
//         var incidentId, station_id;
//             incidents.forEach(incident => {
//                 if(location.marker_id === incident.marker.marker_id || location.marker_id == incident.marker.marker_id ){
//                     console.log("incident data:", incident.incident_id);
//                     incidentId = incident.incident_id;
//                     station_id = incident.station_id;
//                 }
//             });
//             if(incidentId){
//                 // console.log("here: ", incidentId);
//                  const incidentRes = await fetch('/api/incidentReport', {
//                     method: 'POST',
//                     headers: {
//                         'Content-Type': 'application/json',
//                         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
//                     },
//                     body: JSON.stringify({
//                         incident_id: incidentId,
//                         victims: 0,
//                         severity: 'low',
//                         dateEnded: null
//                     })
//                 });

//                  if (!incidentRes.ok) {
//                     const errorText = await incidentRes.text();
//                     console.error('Marker save error:', errorText);
//                     throw new Error('Failed to save marker');
//                 }
//                 //update natin ung sa incient status
//                 const updateRes = await fetch(`/api/incident/${incidentId}`,{
//                     method: 'PUT',
//                     headers: {
//                         'Content-Type': 'application/json',
//                         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
//                     },
//                     body: JSON.stringify({
//                          incident_id: incidentId,
//                          station_id: station_id,
//                          status: "responding",
//                     })
//                 });
//                     if (!updateRes.ok) {
//                     const errorText = await updateRes.text();
//                     console.error('Marker save error:', errorText);
//                     throw new Error('Failed to save marker');
//                 }
//             }
  
//     }catch (err) {
//         console.error("Error saving marker or coordinate:", err);
//         alert('Error saving marker: ' + err.message);
//     }
// }
//  async function saveMarker(lat, lng, title, description, categoryId) {
// // let markerId = '';
//   try{   const markerRes = await fetch('/api/markers', {
//         method: 'POST',
//         headers: {
//             'Content-Type': 'application/json',
//             'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
//         },
//         body: JSON.stringify({
//             lat: lat,
//             lng: lng,
//             title: title,
//             description: description,
//             markerCategory_id: categoryId // default icon for now, can be dynamic later
//         })
//     });
//     const markerData = await markerRes.json();
//     const markerId = markerData.data.marker_id;
//     console.log("Marker created:", markerData, "ID:", markerId);
//     // .then(res => res.json())
//     // .then(data => {
//     //       markerId =  data.data.marker_id;
//     //     console.log(data);
//     //     console.log("id:", markerId);


//     //     // add marker visually
//     //     // L.marker([lat, lng]).addTo(map);
        
//     // })
//     // .catch(err => console.error(err));
//     //send to save
//        const coordRes = await fetch('/api/coordinate', {
//             method: 'POST',
//             headers: {
//                 'Content-Type': 'application/json'
//                 // 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
//             },
//             body: JSON.stringify({
//                 marker_id: markerId,
//                 image_id: 'MMD-0001', // <--- send null
//                 status: 'temporary'
//             })
//         });
//          const coordData = await coordRes.json();
//         console.log("Coordinate saved:", coordData);
// fetchMarkers();
// } catch (err) {
//         console.error("Error saving marker or coordinate:", err);
//     }
//         // .then(res => res.json())
//         // .then(data => {
         
//         //     console.log(data);
     


//         //     // add marker visually
//         //     // L.marker([lat, lng]).addTo(map);
            
//         // });
//     // .catch(err => console.error(err));
// // fetchMarkers();

// }
// function saveMarker(lat, lon, name) {
//     fetch('/admin/markers', {
//         method: 'POST',
//         headers: {
//             'Content-Type': 'application/json',
//             'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
//         },
//         body: JSON.stringify({
//             lat: lat,
//             lng: lon,
//             title: name
//         })
//     })    .then(res => res.json())
//     .then(data => {
//         console.log('Marker saved:', data);
//         alert(data.message);
//     })
//     .catch(err => {
//         console.error('Error saving marker:', err);
//         alert('Failed to save marker. Check console.');
//     });

// }
// function setName(lat, lon){

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
//         console.log('Address:', data);

//     })
//     .catch(err => console.error(err));
    
// }
async function saveMarker(lat, lng, title, description, categoryId) {
    try {
        // First save the marker
        const markerRes = await fetch('/api/markers', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                lat: lat,
                lng: lng,
                title: title,
                description: description,
                markerCategory_id: categoryId
            })
        });
        
        if (!markerRes.ok) {
            const errorText = await markerRes.text();
            console.error('Marker save error:', errorText);
            throw new Error('Failed to save marker');
        }
        
        const markerData = await markerRes.json();
        const markerId = markerData.data.marker_id;
        console.log("Marker created:", markerData, "ID:", markerId);

        // Then save the coordinate
        const coordRes = await fetch('/api/coordinate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                marker_id: markerId,
                image_id: 'MMD-0001',
                status: 'temporary'
            })
        });

        if (!coordRes.ok) {
            const errorText = await coordRes.text();
            console.error('Coordinate save error:', errorText);
            throw new Error('Failed to save coordinate');
        }
        
        const coordData = await coordRes.json();
        console.log("Coordinate saved:", coordData);
        
        // Handle station creation if needed
        if (categoryId == 1) {
            const stationRes = await fetch('/api/station', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    station_name: title,
                    station_chief: 'Test Chief',
                    coordinate_id: coordData.data.coordinate_id
                })
            });
            
            if (!stationRes.ok) {
                const errorText = await stationRes.text();
                console.error('Station save error:', errorText);
                throw new Error('Failed to save station');
            }
        }
        
        // Handle hydrant creation if needed
        if (categoryId == 4) {
            const hydrantRes = await fetch('/api/hydrant', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    location_id: coordData.data.coordinate_id,
                    station_id: 'STN-0001',
                })
            });
            
            if (!hydrantRes.ok) {
                const errorText = await hydrantRes.text();
                console.error('Hydrant save error:', errorText);
                throw new Error('Failed to save hydrant');
            }
        }

        // Close modal and clear form
        closeModal();
        document.getElementById('markerTitle').value = '';
        document.getElementById('markerDescription').value = '';
        
        // Refresh markers to show the new one
        await fetchCoordinate();
        
    } catch (err) {
        console.error("Error saving marker or coordinate:", err);
        alert('Error saving marker: ' + err.message);
    }
}
async function respondIncident(location) {
    console.log("location data:", location);

    try {
        const response = await fetch('/api/incident/latest');
        const incidents = await response.json();

        let incidentId = null;
        let station_id = null;

        incidents.forEach(incident => {
            if (location.marker_id == incident.marker.marker_id) {
                incidentId = incident.incident_id;
                station_id = incident.station_id;
            }
        });

        if (!incidentId) {
            console.log("No matching incident found");
            return;
        }

        // INSERT INCIDENT REPORT
        const incidentRes = await fetch('/api/incidentReport', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                incident_id: incidentId,
                victims: 0,
                severity: 'low',
                dateEnded: null
            })
        });

        if (!incidentRes.ok) {
            const errorText = await incidentRes.text();
            console.error("Insert error:", errorText);
            throw new Error("Failed to insert incident report");
        }

        // UPDATE INCIDENT STATUS
        const updateRes = await fetch(`/api/incident/${incidentId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                station_id: station_id,
                status: "responding"
            })
        });

        if (!updateRes.ok) {
            const errorText = await updateRes.text();
            console.error("Laravel response:", errorText);
            throw new Error("Failed to update incident");
        }

        console.log("Incident updated successfully");
        
        // Refresh markers to reflect status change
        await fetchCoordinate();

    } catch (err) {
        console.error("Error:", err);
        alert("Error: " + err.message);
    }
}
export function getName(lat, lon){
        const url =
            "https://api.geoapify.com/v1/geocode/reverse?" +
            `lat=${lat}&lon=${lon}&` +
            `apiKey=${apiKey}`;
    // fetch('/api/reverse', {
    let popupmarker =  L.popup() //add a loading address before displaying data
    .setLatLng([lat, lon])
        .setContent("Loading address...")
        .openOn(map);

    fetch('/api/reverse', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            lat: lat,
            lon: lon
        })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.features || data.features.length === 0) return;

        const address = data.features[0].properties.formatted;

        // Show popup at the marker position
    // const popupmarker =  L.popup()
    popupmarker.setLatLng([lat, lon])
    .setContent(`
        <div>
            <p>${address}</p>
            <button id="popupActionBtn" style="background-color: #007bff; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">Add as Marker</button>
        </div>
    `)


    if(popupmarker) {
        popupmarker.openOn(map);
    }
    
    const btn = document.getElementById('popupActionBtn');
    if(btn){
        btn.addEventListener('click', function() {

            // console.log('lat:', lat, 'lon:', lon);
            //save logic

            selectedLat = lat;
            selectedLng = lon;

            openModal();
            // saveMarker(lat, lon);
           
            // alert('Button inside popup clicked!');
        });
    }
    
    //  map.removeLayer(popupmarker);
        console.log('Reverse API:', data);

          popupmarker.on('popupclose', function() {
        map.removeLayer(popupmarker);
        popupmarker = null; // Important: clear the reference
    });
    })
    .catch(err => console.error(err));
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('saveCategory')
        .addEventListener('click', function() {
      
            const markercategory = document.getElementById('marker_category').value;
            // const iconDescription = document.getElementById('iconType').value;
            // openIconModal(iconName, iconDescription);
            console.log(markercategory);
            saveCategory(markercategory);
        }); 
});

function saveCategory(name) {
    fetch('/api/marker-categories', { // Change this to match your route
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            marker_category: name,
        })
    })
    .then(res => res.json())
    .then(data => {
        console.log('Category saved:', data);
        closeAddCategory(); // Close modal on success
    })
    .catch(err => console.error(err));
}
document.addEventListener('DOMContentLoaded', function () {

    document.getElementById('saveMarkerBtn')
        .addEventListener('click', function() {
            const title = document.getElementById('markerTitle').value;
            const description = document.getElementById('markerDescription').value;
            let categoryId = document.getElementById("mCategory").value;
            saveMarker(selectedLat, selectedLng, title, description,categoryId);
        });
});

