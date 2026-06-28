let distance= [];
import { loadMarkers } from "./user";

window.thisOfficer = window.stationOfficer || {};

console.log('officers in functions: ',    window.thisOfficer );
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
export function station(id, stationname){

    console.log("id: " , id);
    console.log("station: " , stationname);
    fetchTeam();
}


/**
 * Validate if an incident should be considered a repeat/duplicate of a previous incident
 * @param {number} newLat - Latitude of new incident
 * @param {number} newLng - Longitude of new incident  
 * @param {string} newTime - ISO timestamp of new incident
 * @param {Array} previousIncidents - Array of previous incidents from database
 * @returns {Object} - { isDuplicate: boolean, matchedIncident: object|null, reason: string }
 */
export async function validateIncidentDuplicate(newLat, newLng, newTime, previousIncidents, newIncidentTypeId = null) {
    const TIME_THRESHOLD_MINUTES = 10; // 10 minutes
    const DISTANCE_THRESHOLD_METERS = 200; // 200 meters radius
    const normalizedTypeId = newIncidentTypeId !== null && newIncidentTypeId !== undefined && `${newIncidentTypeId}`.trim() !== ''
        ? parseInt(newIncidentTypeId, 10)
        : null;

    const newIncidentTime = new Date(newTime);
    
    // Filter incidents that are within the time threshold
    const recentIncidents = previousIncidents.filter(incident => {
        const incidentTime = new Date(incident.alarmdatetime);
        const timeDiffMinutes = Math.abs(newIncidentTime - incidentTime) / (1000 * 60);
        const incidentTypeId = incident.incident_type_id !== null && incident.incident_type_id !== undefined
            ? parseInt(incident.incident_type_id, 10)
            : null;

        if (normalizedTypeId !== null && incidentTypeId !== normalizedTypeId) {
            return false;
        }

        return timeDiffMinutes <= TIME_THRESHOLD_MINUTES;
    });
    
    if (recentIncidents.length === 0) {
        return {
            isDuplicate: false,
            matchedIncident: null,
            reason: 'No recent incidents within time threshold'
        };
    }
    
    // Check distance for each recent incident
    for (const incident of recentIncidents) {
        // Get incident coordinates (adjust based on your data structure)
        let incidentLat, incidentLng;
        
        if (incident.marker?.coordinate?.marker) {
            incidentLat = incident.marker.coordinate.marker.lat;
            incidentLng = incident.marker.coordinate.marker.lng;
        } else if (incident.marker?.lat && incident.marker?.lng) {
            incidentLat = incident.marker.lat;
            incidentLng = incident.marker.lng;
        } else if (incident.lat && incident.lng) {
            incidentLat = incident.lat;
            incidentLng = incident.lng;
        } else {
            console.warn('Incident missing coordinates:', incident);
            continue;
        }
        
        const distance = getDistanceMeters(newLat, newLng, incidentLat, incidentLng);
        
        if (distance <= DISTANCE_THRESHOLD_METERS) {
            return {
                isDuplicate: true,
                matchedIncident: incident,
                reason: `Within ${distance.toFixed(0)}m and ${Math.abs(newIncidentTime - new Date(incident.alarmdatetime)) / (1000 * 60)} minutes of previous incident`
            };
        }
    }
    
    return {
        isDuplicate: false,
        matchedIncident: null,
        reason: 'No incidents within distance threshold'
    };
}

/**
 * Get distance between two coordinates in meters
 */
function getDistanceMeters(lat1, lon1, lat2, lon2) {
    const R = 6371000; // Earth radius in meters
    const dLat = toRad(lat2 - lat1);
    const dLon = toRad(lon2 - lon1);
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

window.getDistanceMetersForCitizen = getDistanceMeters;

function toRad(deg) {
    return deg * Math.PI / 180;
}

export async function setName(lat, lon) {
    
    // const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    try {
        const res = await fetch('/api/reverse', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ lat, lon })
        });

        const contentType = res.headers.get('content-type') || '';
        if (!res.ok || !contentType.includes('application/json')) {
            const raw = await res.text();
            console.error('Reverse geocode failed:', res.status, raw?.slice(0, 200));
            return '';
        }

        const data = await res.json();

        if (!data.features || data.features.length === 0) return '';

        return data.features[0].properties.formatted;
    } catch (err) {
        console.error(err);
        return '';
    }
}

export async function usersLocation(lat, lon){
    console.log('firefighter: ', lat, ' ', lon);
}
export function getRoute(centerLat, centerLng, endLat, endLng, typeOfMap) {
const apiKey = "83677a1086d44c4e82f903fd2fe2ea9b";
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const url =
            "https://api.geoapify.com/v1/routing?" +
            `waypoints=${centerLat},${centerLng}|${endLat},${endLng}&` +
            "mode=drive&" +
            "type=balanced&" +
            `apiKey=${apiKey}`;

            // `apiKey=${apiKey}`;

            fetch('/api/route', { // now it’s /route, not /api/route
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
                //  'X-CSRF-TOKEN': '{{ csrf_token() }}'
                // 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                start: centerLat + "," + centerLng,
                end: endLat+ "," + endLng
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

             const routeLayer = L.geoJSON(geometry, { color: 'blue', weight: 5 });

        // Add it to the persistent layer group
            window.routesGroup.addLayer(routeLayer);
        //     if (window.routeLayer) map.removeLayer(routeLayer);
        //    if(typeOfMap == "map"){
        //     window.routeLayer = L.geoJSON(geometry, { color: 'blue', weight: 5 }); 
        //     routesGroup.addLayer(routeLayer); 
        //    }  else{
        //     window.routeLayer = L.geoJSON(geometry, { color: 'blue', weight: 5 });
        //    }
            
            // typeOfMap == 'userMap'?  window.routeLayer = L.geoJSON(geometry, { color: 'blue', weight: 5 }).addTo(window.usermap) :   window.routeLayer = L.geoJSON(geometry, { color: 'blue', weight: 5 }).addTo(window.map);
        });
}

export async function userLocation(lat, lng, userid, values, image) {
    const title = 'fire';
    const description = 'burning, need help!';
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Save marker first
    const markerRes = await fetch('/api/markers', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token
        },
        body: JSON.stringify({
            lat: lat,
            lng: lng,
            //   lat: 15.805368596703,
            // lng: 120.30871352592,
            title: title,
            description: description,
            markerCategory_id: 2
        })
    });
    const markerData = await markerRes.json();
    const markerId = markerData.marker_id ?? markerData?.data?.marker_id;
    if (!markerId) {
        throw new Error('Marker ID missing from /api/markers response');
    }
    document.getElementById('marker_id_input').value = markerId;

    // Find nearest station
    const nearestStation = await getStations(lat, lng, 1);
    // const nearestStation = await getStations(15.800644644332,120.28005909571);

    const nearestStationId = nearestStation.station_id; // <-- this is what you need

    console.log("Nearest station ID:", nearestStationId);

    const now = new Date();

    // Send incident to the database using the nearest station ID
    const incidentRes = await fetch('/citizen/incident', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token
        },
        body: JSON.stringify({
            marker_id: markerId,
            station_id: nearestStationId, // use nearest station pero daan dapat sa team_id kaaagad ito?
            status: 'reported',
            alarmdatetime: now,
            reported_by: userid,
            incident_type: 'fire'
        })
    });

    const incidentData = await incidentRes.json();
    console.log("Incident saved:", incidentData);

    // Handle image upload if exists
    if (image && image !== 0) {
        let formData = new FormData();
        formData.append('path', image);
        formData.append('type', 'proof');

        const imgRes = await fetch('/multimedia/store', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: formData
        });
        const imgData = await imgRes.json();
        const multimediaId = imgData.multimedia.multimedia_id;

        // Save to coordinate table
        await fetch('/api/coordinate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({
                marker_id: markerId,
                image_id: multimediaId,
                status: 'temporary'
            })
        });
    }
}

export async function saveIncident(description, lat, lng, type, imageInput, reporter) {
    console.log('saveIncident called with:', { description, lat, lng, type, reporter });
    const reportProgress = (stage, detail = '') => {
        if (typeof window.updateIncidentReportLoading === 'function') {
            window.updateIncidentReportLoading(stage, detail);
        }
    };
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        console.error('CSRF token not found');
        throw new Error('Security token missing. Please refresh the page.');
    }
    
    try {
        const selectedFile = getIncidentUploadFile(imageInput);
        const title = 'Citizen Fire Emergency';
        let markerId = null;

        const currentTime = new Date().toISOString();
        const recentIncidentsResponse = await fetch('/api/recent-incidents?minutes=10', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });

        let recentIncidents = [];
        if (recentIncidentsResponse.ok) {
            const incidentsData = await recentIncidentsResponse.json();
            recentIncidents = incidentsData.data || incidentsData.incidents || [];
        }

        const validation = await validateIncidentDuplicate(
            parseFloat(lat),
            parseFloat(lng),
            currentTime,
            recentIncidents,
            type
        );

        if (validation.isDuplicate) {
            const matchedIncident = validation.matchedIncident || {};
            const matchedTime = matchedIncident.alarmdatetime ? new Date(matchedIncident.alarmdatetime) : null;
            const minutesAgo = matchedTime
                ? Math.round(Math.abs(new Date(currentTime) - matchedTime) / (1000 * 60))
                : null;
            const incidentLat = matchedIncident.marker?.coordinate?.marker?.lat || matchedIncident.marker?.lat || matchedIncident.lat;
            const incidentLng = matchedIncident.marker?.coordinate?.marker?.lng || matchedIncident.marker?.lng || matchedIncident.lng;
            const distanceMeters = (incidentLat !== undefined && incidentLng !== undefined)
                ? Math.round(getDistanceMeters(parseFloat(lat), parseFloat(lng), parseFloat(incidentLat), parseFloat(incidentLng)))
                : null;

            const duplicateMessageParts = ['A fire report was already submitted for this area recently.'];

            if (minutesAgo !== null) {
                duplicateMessageParts.push(`Reported ${minutesAgo} minute${minutesAgo === 1 ? '' : 's'} ago.`);
            }

            if (distanceMeters !== null && !Number.isNaN(distanceMeters)) {
                duplicateMessageParts.push(`Distance: ${distanceMeters} meters.`);
            }

            if (typeof window.showCitizenToast === 'function') {
                window.showCitizenToast(duplicateMessageParts.join(' '), true, 5200);
            } else {
                showNotification(duplicateMessageParts.join(' '), 'warning', 5200);
            }

            return false;
        }

        reportProgress(
            'Submitting',
            selectedFile ? 'Uploading your attachment and saving your fire report...' : 'Saving your fire report...'
        );
        const multimediaId = await uploadIncidentMultimedia(selectedFile, csrfToken);

        // 2. Save marker
        console.log('Saving marker...');
        const markerData = await saveIncidentMarker({
            title,
            description,
            lat,
            lng,
            multimediaId,
            csrfToken
        });

        console.log('Marker saved:', markerData);
        
        // Extract marker_id
        markerId = markerData.data?.marker_id || markerData.marker_id;
        
        if (!markerId) {
            throw new Error('No marker_id returned from server');
        }
        
        console.log('Marker ID:', markerId);
        
        // 5. Save incident
        reportProgress('Submitting', 'Finishing your fire report...');
        console.log('Saving incident...');
        const incidentPayload = {
            marker_id: markerId,
            status: 'reported',
            reported_by: reporter,
            alarmdatetime: new Date().toISOString(),
        };

        const incidentResponse = await fetch('/citizen/incident', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(incidentPayload)
        });
        
        if (!incidentResponse.ok) {
            const errorText = await incidentResponse.text();
            console.error('Incident save error:', errorText);
            throw new Error('Failed to save incident');
        }
        
        const incidentData = await incidentResponse.json();
        console.log('Incident saved successfully:', incidentData);

        if (incidentData?.data) {
            window.latestReportedIncidentId = incidentData.data.incident_id;
            window.myReportedIncidents = Array.isArray(window.myReportedIncidents) ? window.myReportedIncidents : [];
            window.myReportedIncidents.unshift(incidentData.data);
            window.incidentsData = Array.isArray(window.incidentsData) ? window.incidentsData : [];
            window.incidentsData.unshift(incidentData.data);
        }

        window.setTimeout(() => {
            if (typeof window.refreshCitizenMarkersIfChanged === 'function') {
                window.refreshCitizenMarkersIfChanged(true);
            } else if (typeof loadMarkers === 'function') {
                loadMarkers();
            }
            if (typeof window.startCitizenRouteDiscovery === 'function') {
                window.startCitizenRouteDiscovery();
            }
        }, 0);
        
        window.latestIncidentSubmitMessage = incidentData?.message || null;

        return true;
        
    } catch (error) {
        console.error('Error in saveIncident:', error);
        throw error;
    }
}

function getIncidentUploadFile(imageInput) {
    if (!imageInput) {
        return null;
    }

    if (imageInput.files && imageInput.files.length > 0) {
        return imageInput.files[0];
    }

    if (imageInput._selectedFile instanceof File) {
        return imageInput._selectedFile;
    }

    return null;
}

async function uploadIncidentMultimedia(selectedFile, csrfToken) {
    if (!selectedFile) {
        console.log('No multimedia file selected for this incident');
        return null;
    }

    console.log('Uploading image...', selectedFile.name);
    const formData = new FormData();
    formData.append('path', selectedFile);
    formData.append('type', 'proof');

    const imgResponse = await fetch('/multimedia/store', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: formData
    });

    if (!imgResponse.ok) {
        throw new Error('Failed to upload image');
    }

    const imgData = await imgResponse.json();
    console.log('Image saved:', imgData);
    const multimediaId = imgData.multimedia?.multimedia_id;

    if (!multimediaId) {
        throw new Error('Image uploaded but no multimedia ID was returned');
    }

    return multimediaId;
}

async function saveIncidentMarker({ title, description, lat, lng, multimediaId, csrfToken }) {
    const markerResponse = await fetch('/api/markers', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            title,
            description,
            lat: parseFloat(lat),
            lng: parseFloat(lng),
            status: 'active',
            category: 'fire'
        })
    });

    if (!markerResponse.ok) {
        const errorText = await markerResponse.text();
        console.error('Marker save error:', errorText);
        throw new Error('Failed to save marker');
    }

    return markerResponse.json();
}
// export async function saveIncident(description, lat, lng, type, imageInput, reporter) {
//     console.log('saveIncident called with:', { description, lat, lng, type, reporter });
    
//     // Get CSRF token
//     const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
//     if (!csrfToken) {
//         console.error('CSRF token not found');
//         showNotification('Security token missing. Please refresh the page.', 'error');
//         return false;
//     }
    
//     const title = 'Help Emergency';
//     let multimediaId = null;
//     let markerId = null;
//     let selectedTeamId = null;

    
//     try {
//         // 1. Upload image if exists
//         if (imageInput && imageInput.files && imageInput.files.length > 0) {
//             console.log('Uploading image...');
//             const formData = new FormData();
//             formData.append('path', imageInput.files[0]);
//             formData.append('type', 'proof');
            
//             const imgResponse = await fetch('/multimedia/store', {
//                 method: 'POST',
//                 headers: {
//                     'Accept': 'application/json',
//                     'X-CSRF-TOKEN': csrfToken
//                 },
//                 body: formData
//             });
            
//             if (!imgResponse.ok) {
//                 throw new Error('Failed to upload image');
//             }
            
//             const imgData = await imgResponse.json();
//             console.log('Image saved:', imgData);
//             multimediaId = imgData.multimedia?.multimedia_id;
//         }else{
//             console.log('error uploadingimage: ', imageInput);
//         }
        
//         // 2. Save marker
//         console.log('Saving marker...');
//         const markerResponse = await fetch('/api/markers', {
//             method: 'POST',
//             headers: {
//                 'Accept': 'application/json',
//                 'Content-Type': 'application/json',
//                 'X-CSRF-TOKEN': csrfToken
//             },
//             body: JSON.stringify({
//                 title: title,
//                 description: description,
//                 lat: parseFloat(lat),
//                 lng: parseFloat(lng),
//                 status_id: 1,
//                 markerCategory_id: 2, // default to 2
//                 multimedia_id: multimediaId || null
//             })
//         });
        
//         if (!markerResponse.ok) {
//             const errorText = await markerResponse.text();
//             console.error('Marker save error:', errorText);
//             throw new Error('Failed to save marker');
//         }
        
//         const markerData = await markerResponse.json();
//         console.log('Marker saved:', markerData);
        
//         // Extract marker_id
//         markerId = markerData.data?.marker_id || markerData.marker_id;
        
//         if (!markerId) {
//             throw new Error('No marker_id returned from server');
//         }
        
//         console.log('Marker ID:', markerId);
        
//         // 3. Find nearest station
//         console.log('Finding nearest station...');
//         const nearestStation = await getStations(lat, lng);

//         if (!nearestStation) {
//             throw new Error('Could not find nearest station');
//         }

//         // Make sure we're using the numeric station_id
//         const nearestStationId = parseInt(nearestStation.station_id);
//         console.log('Nearest station ID (numeric):', nearestStationId, 'Type:', typeof nearestStationId);

//         // 4. Fetch teams with the numeric station_id
//         const teamData = await fetchTeam(nearestStationId, csrfToken);
//         console.log('Team data received:', teamData);

//         // Check if we have available teams
//         if (teamData.available_teams && teamData.available_teams.length > 0) {
//             selectedTeamId = teamData.available_teams[0].team_id;
//             console.log('Selected team ID:', selectedTeamId);
//         } else {
//             console.warn('No available teams found. Team data summary:', {
//                 station_id: teamData.station_id,
//                 total_teams: teamData.total_teams_at_station,
//                 available_count: teamData.available_teams_count,
//                 message: teamData.message || 'No available teams'
//             });
//             selectedTeamId = null;
//         }
        
//         // 5. Save incident
//         console.log('Saving incident...');
//         const incidentPayload = {
//             marker_id: markerId,
//             incident_status_id: 1,
//             incident_type_id: parseInt(type),
//             station_id: nearestStationId,
//             reported_by: reporter,
//             alarmdatetime: new Date().toISOString()
//         };
        
//         // Only add team_id if we have one
//         if (selectedTeamId) {
//             incidentPayload.team_id = selectedTeamId;
//         }
        
//         const incidentResponse = await fetch('/citizen/incident', {
//             method: 'POST',
//             headers: {
//                 'Accept': 'application/json',
//                 'Content-Type': 'application/json',
//                 'X-CSRF-TOKEN': csrfToken
//             },
//             body: JSON.stringify(incidentPayload)
//         });
        
//         if (!incidentResponse.ok) {
//             const errorText = await incidentResponse.text();
//             console.error('Incident save error:', errorText);
//             throw new Error('Failed to save incident');
//         }
        
//         const incidentData = await incidentResponse.json();
//         console.log('Incident saved successfully:', incidentData);
        
//         console.log('trying updating status teams:', selectedTeamId);

//             fetch(`/api/teams/${selectedTeamId}`,{
//                 method: 'PUT',
//                 headers: {
//                     'Accept': 'application/json',
//                     'Content-Type': 'application/json',
//                     'X-CSRF-TOKEN': csrfToken
//                 },
//                 body: JSON.stringify({
//                     status: 'responding'
//                 })  
//             })
//             .then(res => {
//                 if (!res.ok) {
//                     throw new Error('Failed to update team status');
//                 }
//                 return res.json();
//             })
//             .then(data => {
//                 console.log('Team status updated to responding:', data);
//             })
//             .catch(err => {
//                 console.error('Error updating team status:', err);
//                 showNotification('Error updating team status: ' + err.message, 'error');
//             });
//         // showNotification('Incident reported successfully!', 'success');
//         // fetch('api/route', {
//         //     method: 'POST',
//         //     headers: {
//         //         'Accept': 'application/json',
//         //         'Content-Type': 'application/json',
//         //         'X-CSRF-TOKEN': csrfToken
//         //     }
//         // }); 
//         // 6. Reload markers if function exists
//         if (typeof loadMarkers === 'function') {
//             loadMarkers();
//         }
        
//         return true;
        
//     } catch (error) {
//         console.error('Error in saveIncident:', error);
//         showNotification('Error: ' + error.message, 'error');
//         return false;
//     }
// }

async function fetchTeam(nearestStationId, csrfToken) {
    try {
        console.log('Fetching teams for station:', nearestStationId);

        if (!csrfToken) {
            console.error('CSRF token not provided to fetchTeam');
            return {
                station_id: nearestStationId,
                total_teams_at_station: 0,
                available_teams_count: 0,
                available_teams: [],
                error: 'CSRF token missing'
            };
        }

        const response = await fetch('/api/teams', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        if (!response.ok) {
            throw new Error(`Failed to fetch teams: ${response.status}`);
        }

        const data = await response.json();
        console.log('Raw teams data:', data);

        // Handle different response structures
        let allTeams = [];
        if (data.data && Array.isArray(data.data)) {
            allTeams = data.data;
        } else if (Array.isArray(data)) {
            allTeams = data;
        } else {
            console.error('Unexpected teams response format:', data);
            return {
                station_id: nearestStationId,
                total_teams_at_station: 0,
                available_teams_count: 0,
                available_teams: [],
                error: 'Invalid response format'
            };
        }

        console.log(`Total teams fetched: ${allTeams.length}`);
        //nearestStationId //magagamit to para comparison
        // Process teams - get station_id from team_leader
        const teamsWithStation = allTeams.map(team => {
            let stationId = null;
            
            // Get station_id from team_leader relationship
            if (team.team_leader) {
                const id = team.team_leader.user_id; //user_id nakuha natin ngayon try natin compare sa station officers.
              
                window.thisOfficer.map(of=>{
                    if(id == of.user_id){
                        stationId = of.station_id;
                    }
                });
                 //get natin mga station officers.
                //gawin mamaya umaga paggising
                // stationId = team.team_leader.station_id;
                console.log(`Team ${team.team_id}: station_id from team_leader = ${stationId}`);
            } else {
                console.warn(`Team ${team.team_id}: No team_leader or station_id found`);
            }

            return {
                ...team,
                station_id: stationId
            };
        });

        // Filter only teams at the requested station
        const targetStationId = parseInt(nearestStationId);
        let thisStationId = null;
        const stationTeams = teamsWithStation.filter(team => {
            // window.thisOfficer.filter(t => {
            //     if(t.team_leader){
            //     const id = t.team_leader.user_id; //user_id nakuha natin ngayon try natin compare sa station officers.

            //     }
                    
            // });
            if (!team.station_id) return false;
            return parseInt(team.station_id) === targetStationId;
        });

        console.log(`Teams at station ${nearestStationId}: ${stationTeams.length}`);

        // Filter available teams
        const availableTeams = stationTeams.filter(team => {
            const status = (team.status || '').toLowerCase();
            return ['available', 'active', 'ready'].includes(status);
        });

        // if(availableTeams.length == 0 || availableTeams.length === 0){

        // }
        console.log(`Available teams at station: ${availableTeams.length}`);

        return {
            station_id: nearestStationId,
            total_teams_at_station: stationTeams.length,
            available_teams_count: availableTeams.length,
            available_teams: availableTeams,
            available_team_ids: availableTeams.map(team => team.team_id)
        };

    } catch (error) {
        console.error('Error in fetchTeam:', error);
        return {
            station_id: nearestStationId,
            total_teams_at_station: 0,
            available_teams_count: 0,
            available_teams: [],
            error: error.message
        };
    }
}

async function fetchAllTeamsWithStations(csrfToken) {
    if (!csrfToken) {
        return [];
    }

    const response = await fetch('/api/teams', {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    });

    if (!response.ok) {
        throw new Error(`Failed to fetch teams: ${response.status}`);
    }

    const data = await response.json();

    let allTeams = [];
    if (data.data && Array.isArray(data.data)) {
        allTeams = data.data;
    } else if (Array.isArray(data)) {
        allTeams = data;
    }

    return allTeams.map(team => {
        let stationId = null;

        if (team.team_leader) {
            const teamLeaderId = team.team_leader.user_id;
            const officer = Array.isArray(window.thisOfficer)
                ? window.thisOfficer.find(of => String(of.user_id) === String(teamLeaderId))
                : null;
            stationId = officer?.station_id ?? null;
        }

        return {
            ...team,
            station_id: stationId
        };
    });
}
//working getstations before modification april 11
// export async function getStations(latitude, longitude) {
//     try {
//         console.log('Fetching stations from /api/station...');
//         const response = await fetch('/api/station', {
//             headers: {
//                 'Accept': 'application/json',
//                 'X-CSRF-TOKEN': csrfToken
//             }
//         });
        
//         if (!response.ok) {
//             console.error('Station API response not OK:', response.status, response.statusText);
//             throw new Error(`Station API error: ${response.status}`);
//         }
        
//         const data = await response.json();
//         console.log('Raw station API response:', data);
        
//         // Handle different response structures
//         let stations = [];
//         if (data.data && Array.isArray(data.data)) {
//             stations = data.data;
//         } else if (Array.isArray(data)) {
//             stations = data;
//         } else if (data.stationData && Array.isArray(data.stationData)) {
//             stations = data.stationData;
//         } else {
//             console.error('Unexpected station response format:', data);
//             // Try to use a default station or return a fallback
//             return getDefaultStation();
//         }
        
//         if (!stations || stations.length === 0) {
//             console.warn('No stations available in database');
//             // Return a default station or throw with clear message
//             return getDefaultStation();
//         }
        
//         console.log(`Found ${stations.length} stations:`, stations);
        
//         // Calculate distances to each station
//         const distancePromises = stations.map(async (station) => {
//             // Try to get coordinates from different possible structures
//             let stationLat, stationLng;
            
//             if (station.marker && station.marker.lat && station.marker.lng) {
//                 stationLat = station.marker.lat;
//                 stationLng = station.marker.lng;
//             } else if (station.lat && station.lng) {
//                 stationLat = station.lat;
//                 stationLng = station.lng;
//             } else if (station.coordinate && station.coordinate.marker) {
//                 stationLat = station.coordinate.marker.lat;
//                 stationLng = station.coordinate.marker.lng;
//             } else {
//                 console.warn('Station missing coordinates:', station);
//                 return { station, distance: Infinity };
//             }
            
//             try {
//                 const distance = await getRouteDistance(
//                     parseFloat(latitude),
//                     parseFloat(longitude),
//                     parseFloat(stationLat),
//                     parseFloat(stationLng)
//                 );
//                 return { station, distance };
//             } catch (err) {
//                 console.error(`Error calculating distance for station ${station.station_name}:`, err);
//                 return { station, distance: Infinity };
//             }
//         });
        
//         // Wait for all distance calculations
//         const stationsWithDistances = await Promise.all(distancePromises);
        
//         // Filter out stations with invalid distances and find the nearest
//         const validStations = stationsWithDistances.filter(s => s.distance !== Infinity);
        
//         if (validStations.length === 0) {
//             console.error('No valid stations with calculable distances');
//             return getDefaultStation();
//         }
        
//         // Find the station with minimum distance
//         const nearest = validStations.reduce((min, current) => 
//             current.distance < min.distance ? current : min
//         );
        
//         console.log('Nearest station:', nearest.station.station_name, 'Distance:', nearest.distance, 'meters');
        
//         return nearest.station; 
        
//     } catch (error) {
//         console.error('Fetch error in getStations:', error);
//         // Return a fallback station instead of throwing
//         return getDefaultStation();
//     }
// }

function isFireIncidentType(incidentTypeId) {
    return parseInt(incidentTypeId, 10) === 1;
}

export async function getStations(latitude, longitude, incidentTypeId = 1) {
    try {
        console.log('Fetching stations from /api/station...');
        const [response, allTeams] = await Promise.all([
            fetch('/api/station', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            }),
            fetchAllTeamsWithStations(csrfToken)
        ]);
        
        
        if (!response.ok) {
            console.error('Station API response not OK:', response.status, response.statusText);
            throw new Error(`Station API error: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Raw station API response:', data);
        
        // Handle different response structures
        let stations = [];
        if (data.data && Array.isArray(data.data)) {
            stations = data.data;
        } else if (Array.isArray(data)) {
            stations = data;
        } else if (data.stationData && Array.isArray(data.stationData)) {
            stations = data.stationData;
        } else {
            console.error('Unexpected station response format:', data);
            return getDefaultStation();
        }
        
        if (!stations || stations.length === 0) {
            console.warn('No stations available in database');
            return getDefaultStation();
        }
        
        console.log(`Found ${stations.length} stations:`, stations);
        
        // Calculate direct distances locally to avoid expensive routing calls on submit.
        const stationsWithDistances = stations.map((station) => {
            // Try to get coordinates from different possible structures
            let stationLat, stationLng;
            
            if (station.marker && station.marker.lat && station.marker.lng) {
                stationLat = station.marker.lat;
                stationLng = station.marker.lng;
            } else if (station.lat && station.lng) {
                stationLat = station.lat;
                stationLng = station.lng;
            } else if (station.coordinate && station.coordinate.marker) {
                stationLat = station.coordinate.marker.lat;
                stationLng = station.coordinate.marker.lng;
            } else {
                console.warn('Station missing coordinates:', station);
                return { station, distance: Infinity };
            }

            const distance = getDistanceMeters(
                parseFloat(latitude),
                parseFloat(longitude),
                parseFloat(stationLat),
                parseFloat(stationLng)
            );

            return { station, distance };
        });
        
        // Fire incidents can inspect the 5 nearest stations. Other categories stay with the nearest station only.
        const validStations = stationsWithDistances
            .filter(s => s.distance !== Infinity)
            .sort((a, b) => a.distance - b.distance);
        
        if (validStations.length === 0) {
            console.error('No valid stations with calculable distances');
            return getDefaultStation();
        }

        const stationLimit = isFireIncidentType(incidentTypeId) ? 5 : 1;
        const candidateStations = validStations.slice(0, stationLimit);
        console.log(`Checking ${stationLimit} nearest station(s):`, candidateStations.map(item => ({
            station_id: item.station.station_id,
            station_name: item.station.station_name,
            distance: item.distance
        })));
        
        const availableTeamsByStation = allTeams.reduce((map, team) => {
            const teamStationId = parseInt(team.station_id);
            const status = (team.status || '').toLowerCase();

            if (!teamStationId || status !== 'available') {
                return map;
            }

            if (!map.has(teamStationId)) {
                map.set(teamStationId, []);
            }

            map.get(teamStationId).push(team);
            return map;
        }, new Map());

        const nearestWithAvailableTeams = candidateStations.find(({ station }) => {
            const stationId = parseInt(station.station_id);
            return availableTeamsByStation.has(stationId) && availableTeamsByStation.get(stationId).length > 0;
        });

        let selectedStation = null;

        if (nearestWithAvailableTeams) {
            const stationId = parseInt(nearestWithAvailableTeams.station.station_id);
            const availableTeams = availableTeamsByStation.get(stationId) || [];
            selectedStation = {
                ...nearestWithAvailableTeams.station,
                _availableTeams: availableTeams,
                _teamSummary: {
                    station_id: stationId,
                    total_teams_at_station: allTeams.filter(team => parseInt(team.station_id) === stationId).length,
                    available_teams_count: availableTeams.length,
                    available_teams: availableTeams,
                    available_team_ids: availableTeams.map(team => team.team_id)
                }
            };
        }
        
        // If none of the 5 nearest stations has an available team,
        // keep the nearest station assignment but leave team_id null on save.
        if (!selectedStation) {
            const nearestCheckedStation = candidateStations[0]?.station;
            if (nearestCheckedStation) {
                const stationId = parseInt(nearestCheckedStation.station_id);
                selectedStation = {
                    ...nearestCheckedStation,
                    _availableTeams: [],
                    _teamSummary: {
                        station_id: stationId,
                        total_teams_at_station: allTeams.filter(team => parseInt(team.station_id) === stationId).length,
                        available_teams_count: 0,
                        available_teams: [],
                        available_team_ids: [],
                        fallback_mode: 'nearest_station_only'
                    }
                };
            } else {
                selectedStation = getDefaultStation();
            }
        }
        
        console.log('Selected station:', selectedStation.station_name, 'ID:', selectedStation.station_id);
        return selectedStation;
        
    } catch (error) {
        console.error('Fetch error in getStations:', error);
        return getDefaultStation();
    }
}

// Add a fallback function to return a default station
// Add a fallback function to return a default station
function getDefaultStation() {
    console.warn('Using default station as fallback');
    
    // Try to fetch the first available station from the database
    // This is better than using a hardcoded string
    return {
        station_id: 1,  // Use integer instead of string
        station_name: 'BFP URBIZTONDO',
        station_code: 'DF-001',
        contact_number: '911',
        email: 'emergency@fire.gov.ph',
        _availableTeams: [],
        marker: {
            lat: 15.8222,
            lng: 120.3297
        }
    };
}
export async function getRouteDistance(startLat, startLng, endLat, endLng) {
    try {
        const url = `https://api.geoapify.com/v1/routing?waypoints=${startLat},${startLng}|${endLat},${endLng}&mode=drive&apiKey=83677a1086d44c4e82f903fd2fe2ea9b`;

        const response = await fetch(url);
        const data = await response.json();

        if (!data.features || data.features.length === 0) {
            console.warn('No route found for coordinates:', startLat, startLng, endLat, endLng);
            return Infinity; // skip this route
        }

        const distance = data.features[0].properties.distance; // meters
        // const steps = data.features[0].properties.segments[0].steps;
        // const instructionsDiv = document.getElementById('instructions');

        // instructionsDiv.innerHTML = '';
        // steps.forEach((step, index) => {
        //     instructionsDiv.innerHTML += `<p>${index + 1}. ${step.instruction} (${step.distance}m)</p>`;
        // });
        return distance;

    } catch (error) {
        console.error('Geoapify routing error:', error);
        return Infinity; // skip this route
    }
}

function clusterIncidents(incidents, radiusMeters = 200, timeMinutes = 30) {
    const clusters = [];

    incidents.forEach(incident => {
        const incidentLat = incident.marker.coordinate.marker.lat;
        const incidentLng = incident.marker.coordinate.marker.lng;
        const incidentTime = new Date(incident.alarmdatetime);

        // Check existing clusters
        let foundCluster = false;
        for (let cluster of clusters) {
            const clusterLat = cluster.lat;
            const clusterLng = cluster.lng;
            const clusterTime = new Date(cluster.time);

            const distance = getDistanceMeters(incidentLat, incidentLng, clusterLat, clusterLng);
            const timeDiff = Math.abs(incidentTime - clusterTime) / (1000 * 60); // in minutes

            if (distance <= radiusMeters && timeDiff <= timeMinutes) {
                // Add to existing cluster
                cluster.incidents.push(incident);
                foundCluster = true;
                break;
            }
        }

        if (!foundCluster) {
            clusters.push({
                lat: incidentLat,
                lng: incidentLng,
                time: incidentTime,
                incidents: [incident]
            });
        }
    });

    return clusters;
}
/**
 * Show a notification toast message
 * @param {string} message - The message to display
 * @param {string} type - The type of notification: 'success', 'error', 'warning', 'info'
 * @param {number} duration - Duration in milliseconds (default: 5000)
 */
export function showNotification(message, type = 'info', duration = 5000) {
    // Check if notification container exists, if not create it
    let container = document.getElementById('notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notification-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 350px;
        `;
        document.body.appendChild(container);
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    // Set icon based on type
    let icon = '';
    switch (type) {
        case 'success':
            icon = 'fa-check-circle';
            break;
        case 'error':
            icon = 'fa-exclamation-circle';
            break;
        case 'warning':
            icon = 'fa-exclamation-triangle';
            break;
        default:
            icon = 'fa-info-circle';
    }
    
    notification.style.cssText = `
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : type === 'warning' ? '#f59e0b' : '#3b82f6'};
        color: white;
        padding: 12px 16px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 14px;
        font-weight: 500;
        animation: slideIn 0.3s ease;
        cursor: pointer;
        min-width: 250px;
        max-width: 350px;
    `;
    
    notification.innerHTML = `
        <i class="fa-solid ${icon}" style="font-size: 18px;"></i>
        <span style="flex: 1;">${message}</span>
        <i class="fa-solid fa-times" style="cursor: pointer; opacity: 0.7; font-size: 12px;"></i>
    `;
    
    // Add to container
    container.appendChild(notification);
    
    // Add click handler to close
    const closeBtn = notification.querySelector('.fa-times');
    closeBtn.addEventListener('click', () => {
        notification.remove();
    });
    
    // Auto-remove after duration
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }
    }, duration);
}

// Add CSS animations for notifications
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .notification:hover {
        transform: scale(1.02);
        transition: transform 0.2s ease;
    }
`;
document.head.appendChild(notificationStyles);
// Make it available globally
window.showNotification = showNotification;
// // Helper to calculate distance between 2 points (Haversine formula)
// function getDistanceMeters(lat1, lon1, lat2, lon2) {
//     const R = 6371000; // Earth radius in meters
//     const dLat = toRad(lat2 - lat1);
//     const dLon = toRad(lon2 - lon1);
//     const a = Math.sin(dLat/2)**2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon/2)**2;
//     const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
//     return R * c;
// }

// function toRad(deg) {
//     return deg * Math.PI / 180;
// }
