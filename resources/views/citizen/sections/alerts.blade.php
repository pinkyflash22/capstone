{{-- working alerts page in citizen --}}
<div id="section-alerts" class="content-section">
    @php
        $nearbyAlertCollection = collect($nearbyIncidents ?? []);
        $activeIncidents = $nearbyAlertCollection;
        $activeNearbyAlertsCount = $activeIncidents->count();
        $fireNearbyAlertsCount = $activeIncidents->filter(fn($incident) => strtolower((string) ($incident['incident_type'] ?? $incident['incident_type_name'] ?? '')) === 'fire')->count();
        $medicalNearbyAlertsCount = $activeIncidents->filter(fn($incident) => str_contains(strtolower((string) ($incident['incident_type'] ?? $incident['incident_type_name'] ?? '')), 'medical'))->count();
        $rescueNearbyAlertsCount = $activeIncidents->filter(fn($incident) => str_contains(strtolower((string) ($incident['incident_type'] ?? $incident['incident_type_name'] ?? '')), 'rescue'))->count();
    @endphp
    <!-- Header with Stats -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">My Alerts</h1>
            <p class="text-sm text-gray-500 mt-1">Stay informed about incidents and emergencies in your area</p>
        </div>
        <div class="flex items-center gap-3 mt-3 sm:mt-0">
            <span class="bg-orange-100 text-orange-700 text-sm px-4 py-2 rounded-full flex items-center gap-2">
                <i class="fa-solid fa-bell"></i> 
                <span id="citizenAlertsActiveCount">{{ $activeNearbyAlertsCount }}</span> Active Incidents Nearby
            </span>
        </div>
    </div>

    <!-- Alert Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow p-4 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Fire Alerts</p>
                    <p id="citizenAlertsFireCount" class="text-2xl font-bold text-gray-800">{{ $fireNearbyAlertsCount }}</p>
                </div>
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center text-red-600">
                    <i class="fa-solid fa-fire"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Require immediate attention</p>
        </div>

        <div class="bg-white rounded-xl shadow p-4 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Medical Alerts</p>
                    <p id="citizenAlertsMedicalCount" class="text-2xl font-bold text-gray-800">{{ $medicalNearbyAlertsCount }}</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                    <i class="fa-solid fa-ambulance"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Medical emergencies</p>
        </div>

        <div class="bg-white rounded-xl shadow p-4 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Rescue Alerts</p>
                    <p id="citizenAlertsRescueCount" class="text-2xl font-bold text-gray-800">{{ $rescueNearbyAlertsCount }}</p>
                </div>
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center text-green-600">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Rescue operations</p>
        </div>

        <div class="bg-white rounded-xl shadow p-4 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Resolved Today</p>
                    <p class="text-2xl font-bold text-gray-800">
                        @php
                            $today = \Carbon\Carbon::today();
                            $resolvedToday = $incidents->filter(function($incident) use ($today) {
                                return in_array(strtolower((string) ($incident->status ?? $incident->incident_status ?? '')), ['completed', 'closed'], true) &&
                                       \Carbon\Carbon::parse($incident->created_at)->isToday();
                            })->count();
                        @endphp
                        {{ $resolvedToday }}
                    </p>
                </div>
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center text-purple-600">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Incidents cleared</p>
        </div>
    </div>

    <!-- Alert Filters and Search -->
    <div class="bg-white rounded-xl shadow mb-8">
        <div class="p-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                <div class="flex items-center gap-4">
                    <h2 class="font-bold text-gray-800">Active Alerts (<span id="citizenAlertsTableCount">{{ $activeNearbyAlertsCount }}</span>)</h2>
                    <div class="flex gap-2">
                        <button class="filter-btn active px-4 py-2 text-sm rounded-lg bg-orange-600 text-white" data-filter="all">All</button>
                        <button class="filter-btn px-4 py-2 text-sm rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200" data-filter="1">Fire</button>
                        <button class="filter-btn px-4 py-2 text-sm rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200" data-filter="2">Medical</button>
                        <button class="filter-btn px-4 py-2 text-sm rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200" data-filter="3">Rescue</button>
                    </div>
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <div class="relative flex-1 sm:flex-initial">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="text" placeholder="Search alerts..." class="pl-10 pr-4 py-2 border rounded-lg text-sm w-full sm:w-64 focus:outline-none focus:ring-2 focus:ring-orange-500" id="alertSearch">
                    </div>
                    <select class="text-sm border rounded-lg px-3 py-2 bg-white" id="alertSort">
                        <option value="newest">Sort by: Newest</option>
                        <option value="oldest">Sort by: Oldest</option>
                        <option value="type">Sort by: Type</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Alerts List -->
        <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto" id="alertsList">
            @forelse($activeIncidents as $incident)
            @php
                $incidentTypeName = '';
                $incidentIcon = '⚠️';
                $incidentColor = 'bg-yellow-500';
                
                if(($incident['incident_type_id'] ?? null) == 1) {
                    $incidentTypeName = 'Fire';
                    $incidentIcon = '🔥';
                    $incidentColor = 'bg-red-500';
                } elseif(($incident['incident_type_id'] ?? null) == 2) {
                    $incidentTypeName = 'Medical Emergency';
                    $incidentIcon = '🚑';
                    $incidentColor = 'bg-blue-500';
                } elseif(($incident['incident_type_id'] ?? null) == 3) {
                    $incidentTypeName = 'Rescue';
                    $incidentIcon = '🆘';
                    $incidentColor = 'bg-green-500';
                } else {
                    $incidentTypeName = 'Other';
                    $incidentIcon = '⚠️';
                    $incidentColor = 'bg-yellow-500';
                }
                
                $statusName = '';
                $statusColor = '';
                if(strtolower($incident['incident_status'] ?? '') == 'reported') {
                    $statusName = 'Reported';
                    $statusColor = 'bg-red-100 text-red-700';
                } elseif(strtolower($incident['incident_status'] ?? '') == 'responding') {
                    $statusName = 'Responding';
                    $statusColor = 'bg-yellow-100 text-yellow-700';
                } elseif(strtolower($incident['incident_status'] ?? '') == 'on scene') {
                    $statusName = 'On Scene';
                    $statusColor = 'bg-purple-100 text-purple-700';
                } else {
                    $statusName = 'Unknown';
                    $statusColor = 'bg-gray-100 text-gray-700';
                }
            @endphp
            <div class="p-5 hover:bg-gray-50 transition alert-item" data-type="{{ $incident['incident_type_id'] ?? '' }}" data-date="{{ $incident['created_at'] ?? '' }}" data-id="{{ $incident['incident_id'] ?? '' }}" data-lat="{{ $incident['lat'] ?? '' }}" data-lng="{{ $incident['lng'] ?? '' }}">
                <div class="flex items-start gap-4">
                    <span class="w-2 h-2 {{ $incidentColor }} rounded-full mt-2"></span>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="font-semibold text-gray-800">
                                {{ $incidentIcon }} {{ $incidentTypeName }} Incident
                            </h3>
                            <span class="text-xs {{ $statusColor }} px-2 py-1 rounded-full">
                                {{ $statusName }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 mb-2">
                            @if(!empty($incident['address']))
                                {{ $incident['address'] }}
                            @else
                                No additional details provided.
                            @endif
                        </p>
                        <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500">
                            <span><i class="fa-regular fa-clock mr-1"></i> {{ $incident['created_at_human'] ?? 'Just now' }}</span>
                            <span id="location-{{ $incident['incident_id'] ?? '' }}" class="location-span">
                                @if(!empty($incident['lat']) && !empty($incident['lng']))
                                    <i class="fa-solid fa-spinner fa-spin mr-1"></i> Loading address...
                                @else
                                    <i class="fa-solid fa-map-pin mr-1"></i> Location pending
                                @endif
                            </span>
                            <span><i class="fa-regular fa-flag mr-1"></i> 
                                @if(strtolower($incident['incident_status'] ?? '') == 'reported') Awaiting response
                                @elseif(strtolower($incident['incident_status'] ?? '') == 'responding') Crews dispatched
                                @elseif(strtolower($incident['incident_status'] ?? '') == 'on scene') Crews on scene
                                @else {{ $statusName }}
                                @endif
                            </span>
                            @if(isset($incident['distance_meters']))
                            <span><i class="fa-solid fa-location-crosshairs mr-1"></i> {{ number_format(($incident['distance_meters'] ?? 0) / 1000, 1) }} km away</span>
                            @endif
                        </div>
                        
                        <div class="flex gap-2 mt-3">
                            <button type="button" class="text-xs bg-orange-100 text-orange-700 px-3 py-1 rounded-full hover:bg-orange-200 transition" onclick="showCitizenAlertDetailsModal({{ $incident['incident_id'] ?? 0 }})">
                                <i class="fa-regular fa-eye mr-1"></i> View Details
                            </button>
                            <button type="button" class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded-full hover:bg-gray-200 transition" onclick="shareAlert({{ $incident['incident_id'] ?? 0 }})">
                                <i class="fa-regular fa-share-from-square mr-1"></i> Share
                            </button>
                            @if(!empty($incident['lat']) && !empty($incident['lng']))
                            <button type="button" class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded-full hover:bg-blue-200 transition" onclick="viewOnMap({{ $incident['lat'] }}, {{ $incident['lng'] }})">
                                <i class="fa-regular fa-map mr-1"></i> View on Map
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-regular fa-bell-slash text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-700 mb-2">No Active Alerts</h3>
                <p class="text-sm text-gray-500 mb-4">There are no nearby active incidents in your area at the moment.</p>
                <button type="button" class="bg-orange-600 text-white px-6 py-2 rounded-lg text-sm hover:bg-orange-700 transition" onclick="openIncidentModal()">
                    <i class="fa-regular fa-flag mr-2"></i>Report an Incident
                </button>
            </div>
            @endforelse
        </div>

        @if($activeIncidents->count() > 0)
        <div class="p-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
            <p class="text-sm text-gray-500">
                Showing <span class="font-medium" id="showingStart">1</span> to <span class="font-medium" id="showingEnd">{{ min(10, $activeIncidents->count()) }}</span> of <span class="font-medium">{{ $activeIncidents->count() }}</span> alerts
            </p>
            <div class="flex gap-2">
                <button class="px-3 py-1 border rounded-lg text-sm bg-white hover:bg-gray-50 pagination-prev" disabled>
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <button class="px-3 py-1 border rounded-lg text-sm bg-white hover:bg-gray-50 pagination-next">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>
        @endif
    </div>

    <!-- MY REPORTS HISTORY SECTION -->
    <div class="bg-white rounded-xl shadow">
        <div class="p-5 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-orange-600"></i>
                    My Reports History
                </h2>
                <span class="text-sm text-gray-500">Total Reports: <span id="citizenReportsTotalCount">{{ $countReport ?? 0 }}</span></span>
            </div>
        </div>

        @php
            $currentUserAccountId = Auth::user()->user_id ?? null;
            $userReportedIncidents = $incidents->where('reported_by', $currentUserAccountId)->sortByDesc('created_at');
        @endphp

        @if($userReportedIncidents->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Incident Type</th>
                            <th class="px-6 py-3">Location</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="citizenReportsTableBody">
                        @foreach($userReportedIncidents as $report)
                        @php
                            $reportTypeName = '';
                            $reportIcon = '⚠️';
                            if($report->incident_type_id == 1) {
                                $reportTypeName = 'Fire';
                                $reportIcon = '🔥';
                            } elseif($report->incident_type_id == 2) {
                                $reportTypeName = 'Medical Emergency';
                                $reportIcon = '🚑';
                            } elseif($report->incident_type_id == 3) {
                                $reportTypeName = 'Rescue';
                                $reportIcon = '🆘';
                            } else {
                                $reportTypeName = 'Other';
                                $reportIcon = '⚠️';
                            }
                            
                            $reportStatusName = '';
                            $reportStatusClass = '';
                            if($report->incident_status_id == 1) {
                                $reportStatusName = 'Reported';
                                $reportStatusClass = 'bg-red-100 text-red-700';
                            } elseif($report->incident_status_id == 2) {
                                $reportStatusName = 'Responding';
                                $reportStatusClass = 'bg-yellow-100 text-yellow-700';
                            } elseif($report->incident_status_id == 3) {
                                $reportStatusName = 'On Scene';
                                $reportStatusClass = 'bg-purple-100 text-purple-700';
                            } elseif($report->incident_status_id == 4) {
                                $reportStatusName = 'Controlled';
                                $reportStatusClass = 'bg-blue-100 text-blue-700';
                            } elseif($report->incident_status_id == 5) {
                                $reportStatusName = 'Closed';
                                $reportStatusClass = 'bg-gray-100 text-gray-700';
                            } else {
                                $reportStatusName = 'Unknown';
                                $reportStatusClass = 'bg-gray-100 text-gray-700';
                            }
                        @endphp
                        <tr id="report-row-{{ $report->incident_id }}" class="bg-white border-b hover:bg-gray-50 transition" data-incident-id="{{ $report->incident_id }}" data-status-id="{{ $report->incident_status_id }}">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                {{ $report->created_at->format('M d, Y') }}
                                <span class="text-xs text-gray-400 block">{{ $report->created_at->format('h:i A') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="flex items-center gap-1 report-type-label">
                                    {{ $reportIcon }} {{ $reportTypeName }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($report->marker && $report->marker->lat && $report->marker->lng)
                                    <span id="location-report-{{ $report->incident_id }}" class="text-xs location-span">
                                        <i class="fa-solid fa-spinner fa-spin mr-1"></i> Loading...
                                    </span>
                                @else
                                    <span class="text-gray-400">Location pending</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs report-status-badge {{ $reportStatusClass }}">
                                    {{ $reportStatusName }}
                                </span>
                            </td>
                            <td class="px-6 py-4 report-actions-cell">
                                @if($report->incident_status_id == 1)
                                <button type="button" class="text-orange-600 hover:text-orange-700 font-medium text-xs mr-2" onclick="showReportEditModal({{ $report->incident_id }})">
                                    <i class="fa-regular fa-pen-to-square mr-1"></i> Edit
                                </button>
                                <button type="button" class="text-red-600 hover:text-red-700 font-medium text-xs" onclick="cancelReport({{ $report->incident_id }}, {{ $report->marker_id }})">
                                    <i class="fa-regular fa-trash-can mr-1"></i> Cancel
                                </button>
                                @else
                                <button type="button" class="text-orange-600 hover:text-orange-700 font-medium text-xs" onclick="showCitizenAlertDetailsModal({{ $report->incident_id }})">
                                    <i class="fa-regular fa-eye mr-1"></i> View Details
                                </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-gray-50 border-t border-gray-200">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="text-center">
                        <p id="citizenReportsPendingCount" class="text-2xl font-bold text-orange-600">{{ $userReportedIncidents->where('status', 'reported')->count() }}</p>
                        <p class="text-xs text-gray-500">Pending Reports</p>
                    </div>
                    <div class="text-center">
                        <p id="citizenReportsInProgressCount" class="text-2xl font-bold text-yellow-600">{{ $userReportedIncidents->whereIn('status', ['en-route', 'on-scene', 'completed'])->count() }}</p>
                        <p class="text-xs text-gray-500">In Progress</p>
                    </div>
                    <div class="text-center">
                        <p id="citizenReportsFinishedCount" class="text-2xl font-bold text-green-600">{{ $userReportedIncidents->where('status', 'closed')->count() }}</p>
                        <p class="text-xs text-gray-500">Closed Cases</p>
                    </div>
                </div>
            </div>
        @else
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-regular fa-file-lines text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-700 mb-2">No Reports Yet</h3>
                <p class="text-sm text-gray-500 mb-4">You haven't reported any incidents yet.</p>
                <button type="button" class="bg-orange-600 text-white px-6 py-2 rounded-lg text-sm hover:bg-orange-700 transition" onclick="openIncidentModal()">
                    <i class="fa-regular fa-flag mr-2"></i>Report Your First Incident
                </button>
            </div>
        @endif
    </div>
</div>

<!-- Incident Details Modal (VIEW ONLY - for Active Alerts) -->
<div id="citizenAlertDetailsModal" class="modal">
    <div class="modal-content alert-details-modal-content">
        <div class="modal-header">
            <h2 class="text-xl font-bold">Alert Details</h2>
            <button class="modal-close" onclick="closeCitizenAlertDetailsModal()">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="citizenAlertDetailsContent">
            <div class="text-center py-8">
                <i class="fa-solid fa-spinner fa-spin text-2xl text-orange-600"></i>
                <p class="mt-2 text-gray-500">Loading alert details...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-outline" onclick="closeCitizenAlertDetailsModal()">Close</button>
            <button type="button" class="btn-primary" onclick="shareCurrentCitizenAlert()">Share</button>
        </div>
    </div>
</div>

<!-- Report Edit Modal (EDITABLE - for My Reports History) -->
<div id="reportEditModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="text-xl font-bold">Edit My Report</h2>
            <button class="modal-close" onclick="closeReportEditModal()">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="reportEditContent">
            <div class="text-center py-8">
                <i class="fa-solid fa-spinner fa-spin text-2xl text-orange-600"></i>
                <p class="mt-2 text-gray-500">Loading report data...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-outline" onclick="closeReportEditModal()">Cancel</button>
            <button type="button" class="btn-primary" onclick="saveReportChanges()">
                <i class="fa-regular fa-floppy-disk mr-1"></i> Save Changes
            </button>
        </div>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 16px;
    max-width: 600px;
    width: 90%;
    max-height: 85vh;
    overflow-y: auto;
    position: relative;
    margin: 20px;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
}

.alert-details-modal-content {
    max-width: 760px;
    background:
        radial-gradient(circle at top right, rgba(251, 146, 60, 0.14), transparent 28%),
        linear-gradient(180deg, #fffaf5 0%, #ffffff 38%);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
    background: white;
    border-radius: 16px 16px 0 0;
    position: sticky;
    top: 0;
    z-index: 10;
}

.modal-header h2 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    color: #9ca3af;
    font-size: 1.25rem;
    cursor: pointer;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.modal-close:hover {
    background: #f3f4f6;
    color: #4b5563;
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
    border-radius: 0 0 16px 16px;
}

.filter-btn {
    transition: all 0.2s ease;
    cursor: pointer;
}

.filter-btn:hover:not(.active) {
    background-color: #e5e7eb !important;
}

.btn-outline {
    padding: 8px 16px;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-outline:hover {
    background: #f9fafb;
}

.btn-primary {
    padding: 8px 16px;
    background: #f97316;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary:hover {
    background: #ea580c;
}

.btn-danger {
    padding: 8px 16px;
    background: #dc2626;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-danger:hover {
    background: #b91c1c;
}

.location-span {
    max-width: 250px;
    display: inline-block;
    white-space: normal;
    word-break: break-word;
}

.form-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: #f97316;
    ring: 2px solid rgba(249, 115, 22, 0.2);
}

.form-textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    resize: vertical;
    transition: all 0.2s;
}

.form-textarea:focus {
    outline: none;
    border-color: #f97316;
    ring: 2px solid rgba(249, 115, 22, 0.2);
}

.form-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 4px;
}

.alert-details-shell {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.alert-details-hero {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    padding: 20px;
    border-radius: 20px;
    background: linear-gradient(135deg, #fff7ed 0%, #ffffff 100%);
    border: 1px solid #fed7aa;
}

.alert-details-icon {
    width: 64px;
    height: 64px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 18px;
    background: #ffffff;
    box-shadow: 0 14px 32px rgba(249, 115, 22, 0.14);
    font-size: 32px;
}

.alert-details-kicker {
    margin: 0 0 4px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #ea580c;
}

.alert-details-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 800;
    color: #111827;
}

.alert-details-subtitle {
    margin: 6px 0 0;
    font-size: 14px;
    color: #6b7280;
}

.alert-details-chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 14px;
}

.alert-details-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    background: #ffffff;
    color: #374151;
    border: 1px solid #e5e7eb;
}

.alert-details-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.alert-details-card {
    padding: 16px;
    border-radius: 16px;
    background: #ffffff;
    border: 1px solid #f1f5f9;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}

.alert-details-card--full {
    grid-column: 1 / -1;
}

.alert-details-label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #9ca3af;
}

.alert-details-value {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
    color: #111827;
    line-height: 1.6;
}

.alert-details-value--muted {
    font-weight: 500;
    color: #6b7280;
}

.alert-details-stat {
    display: grid;
    grid-template-columns: 42px 1fr;
    gap: 12px;
    align-items: start;
}

.alert-details-stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ea580c;
    background: #fff7ed;
}

.alert-details-stat-copy p:first-child {
    margin: 0 0 4px;
    font-size: 12px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 700;
}

.alert-details-stat-copy p:last-child {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
    color: #111827;
    line-height: 1.5;
}

@media (max-width: 640px) {
    .alert-details-hero {
        flex-direction: column;
    }

    .alert-details-grid {
        grid-template-columns: 1fr;
    }
}

.loading-spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #f97316;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
const incidentsData = @json($incidents);
const incidentTypesData = @json($types ?? []);
let nearbyAlertsData = @json($nearbyIncidents ?? []);
let currentIncidentId = null;
let currentEditIncident = null;

// Incident type mapping
const incidentTypeMap = {
    1: { name: 'Fire', icon: '🔥', color: 'red' },
    2: { name: 'Medical Emergency', icon: '🚑', color: 'blue' },
    3: { name: 'Rescue', icon: '🆘', color: 'green' }
};

// Status mapping
const statusMap = {
    1: { name: 'Reported', class: 'bg-red-100 text-red-700', canCancel: true },
    2: { name: 'Responding', class: 'bg-yellow-100 text-yellow-700', canCancel: false },
    3: { name: 'On Scene', class: 'bg-purple-100 text-purple-700', canCancel: false },
    4: { name: 'Controlled', class: 'bg-blue-100 text-blue-700', canCancel: false },
    5: { name: 'Closed', class: 'bg-gray-100 text-gray-700', canCancel: false }
};

function canEditCitizenReport(incident) {
    return getIncidentStatusId(incident) === 1;
}

function getIncidentTypeName(typeId) {
    return incidentTypesData.find((type) => Number(type.incident_type_id) === Number(typeId))?.type || 'Other';
}

function getIncidentStatusId(incident = {}) {
    if (incident?.incident_status_id !== undefined && incident?.incident_status_id !== null) {
        return Number(incident.incident_status_id);
    }

    const status = String(incident?.incident_status || incident?.status || '').trim().toLowerCase();
    if (status === 'en-route' || status === 'responding') return 2;
    if (status === 'on-scene' || status === 'on scene') return 3;
    if (status === 'completed' || status === 'controlled') return 4;
    if (status === 'closed') return 5;
    return 1;
}

function getIncidentTypeDisplayName(incident = {}) {
    return incident?.incident_type_name
        || incident?.incident_type
        || incident?.incidentType?.type
        || getIncidentTypeName(incident?.incident_type_id)
        || 'Incident';
}

function getReportStatusMeta(statusId) {
    if (Number(statusId) === 1) return { label: 'Reported', badge: 'bg-red-100 text-red-700' };
    if (Number(statusId) === 2) return { label: 'Responding', badge: 'bg-yellow-100 text-yellow-700' };
    if (Number(statusId) === 3) return { label: 'On Scene', badge: 'bg-purple-100 text-purple-700' };
    if (Number(statusId) === 4) return { label: 'Controlled', badge: 'bg-blue-100 text-blue-700' };
    if (Number(statusId) === 5) return { label: 'Closed', badge: 'bg-gray-100 text-gray-700' };
    return { label: 'Unknown', badge: 'bg-gray-100 text-gray-700' };
}

function formatCoord(coord) {
    if (coord === null || coord === undefined) return 'N/A';
    const num = parseFloat(coord);
    return isNaN(num) ? coord : num.toFixed(6);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Load address for a single incident
async function loadAddressForIncident(incidentId, lat, lng, isReport = false) {
    const spanId = isReport ? `location-report-${incidentId}` : `location-${incidentId}`;
    const locationSpan = document.getElementById(spanId);
    
    if (!locationSpan) return;
    
    const numLat = parseFloat(lat);
    const numLng = parseFloat(lng);
    
    if (isNaN(numLat) || isNaN(numLng)) {
        locationSpan.innerHTML = `<i class="fa-solid fa-map-pin mr-1"></i> Invalid coordinates`;
        return;
    }
    
    try {
        if (typeof window.setThisName === 'function') {
            const address = await window.setThisName(numLat, numLng);
            if (address && address !== '' && address !== 'undefined') {
                locationSpan.innerHTML = `<i class="fa-solid fa-location-dot mr-1"></i> ${address}`;
                locationSpan.title = address;
                return;
            }
        }
        locationSpan.innerHTML = `<i class="fa-solid fa-map-pin mr-1"></i> ${numLat.toFixed(6)}, ${numLng.toFixed(6)}`;
    } catch (error) {
        console.error(`Error loading address:`, error);
        locationSpan.innerHTML = `<i class="fa-solid fa-map-pin mr-1"></i> ${numLat.toFixed(6)}, ${numLng.toFixed(6)}`;
    }
}

// Load all addresses
async function loadAllAddresses() {
    for (const incident of nearbyAlertsData) {
        if (incident && incident.lat && incident.lng) {
            await loadAddressForIncident(incident.incident_id, incident.lat, incident.lng, false);
            await new Promise(resolve => setTimeout(resolve, 150));
        }
    }
    
    const currentUserId = {{ Auth::user()->user_id ?? 'null' }};
    const userReports = incidentsData.filter(i => i.reported_by == currentUserId);
    
    for (const report of userReports) {
        if (report.marker && report.marker.lat && report.marker.lng) {
            await loadAddressForIncident(report.incident_id, report.marker.lat, report.marker.lng, true);
            await new Promise(resolve => setTimeout(resolve, 150));
        }
    }
}

function updateCitizenReportSummaryCounts() {
    const currentUserId = {{ Auth::user()->user_id ?? 'null' }};
    const userReports = incidentsData.filter((incident) => String(incident.reported_by) === String(currentUserId));

    const totalNode = document.getElementById('citizenReportsTotalCount');
    const pendingNode = document.getElementById('citizenReportsPendingCount');
    const inProgressNode = document.getElementById('citizenReportsInProgressCount');
    const finishedNode = document.getElementById('citizenReportsFinishedCount');

    if (totalNode) totalNode.textContent = userReports.length;
    if (pendingNode) pendingNode.textContent = userReports.filter((incident) => getIncidentStatusId(incident) === 1).length;
    if (inProgressNode) inProgressNode.textContent = userReports.filter((incident) => [2, 3, 4].includes(getIncidentStatusId(incident))).length;
    if (finishedNode) finishedNode.textContent = userReports.filter((incident) => getIncidentStatusId(incident) === 5).length;
}

function syncCitizenReportsEmptyState() {
    const reportsBody = document.getElementById('citizenReportsTableBody');
    if (!reportsBody) return;

    if (reportsBody.querySelector('tr')) {
        return;
    }

    reportsBody.innerHTML = `
        <tr>
            <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-400">
                No reports available.
            </td>
        </tr>
    `;
}

function getReadonlyReportActionMarkup(incidentId) {
    return `
        <button type="button" class="text-orange-600 hover:text-orange-700 font-medium text-xs" onclick="showCitizenAlertDetailsModal(${incidentId})">
            <i class="fa-regular fa-eye mr-1"></i> View Details
        </button>
    `;
}

function syncReportRowWithIncident(incident) {
    const row = document.getElementById(`report-row-${incident.incident_id}`);
    if (!row) return;

    const typeLabel = row.querySelector('.report-type-label');
    const statusBadge = row.querySelector('.report-status-badge');
    const actionsCell = row.querySelector('.report-actions-cell');
    const typeMeta = getNearbyAlertTypeMetaResolved(incident);
    const statusMeta = getReportStatusMeta(getIncidentStatusId(incident));

    row.dataset.statusId = getIncidentStatusId(incident);

    if (typeLabel) {
        typeLabel.innerHTML = `${typeMeta.icon} ${escapeHtml(getIncidentTypeDisplayName(incident))}`;
    }

    if (statusBadge) {
        statusBadge.className = `px-2 py-1 rounded-full text-xs report-status-badge ${statusMeta.badge}`;
        statusBadge.textContent = statusMeta.label;
    }

    if (actionsCell) {
        if (canEditCitizenReport(incident)) {
            actionsCell.innerHTML = `
                <button type="button" class="text-orange-600 hover:text-orange-700 font-medium text-xs mr-2" onclick="showReportEditModal(${incident.incident_id})">
                    <i class="fa-regular fa-pen-to-square mr-1"></i> Edit
                </button>
                <button type="button" class="text-red-600 hover:text-red-700 font-medium text-xs" onclick="cancelReport(${incident.incident_id}, ${incident.marker_id})">
                    <i class="fa-regular fa-trash-can mr-1"></i> Cancel
                </button>
            `;
        } else {
            actionsCell.innerHTML = getReadonlyReportActionMarkup(incident.incident_id);
        }
    }

    updateCitizenReportSummaryCounts();
}

// View on map
function viewOnMap(lat, lng) {
    const numLat = parseFloat(lat);
    const numLng = parseFloat(lng);
    if (isNaN(numLat) || isNaN(numLng)) {
        alert('Invalid coordinates');
        return;
    }
    window.open(`https://www.google.com/maps?q=${numLat},${numLng}`, '_blank');
}

function ensureCitizenDeleteDialogStyles() {
    if (document.getElementById('citizenDeleteDialogStyles')) {
        return;
    }

    const style = document.createElement('style');
    style.id = 'citizenDeleteDialogStyles';
    style.textContent = `
        .citizen-confirm-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 4000;
            padding: 1.25rem;
        }
        .citizen-confirm-card {
            width: min(100%, 28rem);
            background: #fff;
            border-radius: 1.25rem;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.2);
            overflow: hidden;
        }
        .citizen-confirm-header {
            background: linear-gradient(135deg, #ea580c, #f59e0b);
            color: #fff;
            padding: 1rem 1.25rem;
        }
        .citizen-confirm-body {
            padding: 1.25rem;
            color: #475569;
        }
        .citizen-confirm-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            padding: 0 1.25rem 1.25rem;
        }
        .citizen-confirm-btn {
            border: 0;
            border-radius: 9999px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .citizen-confirm-btn:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }
        .citizen-confirm-btn--cancel {
            background: #fff7ed;
            color: #c2410c;
        }
        .citizen-confirm-btn--confirm {
            background: linear-gradient(135deg, #dc2626, #ea580c);
            color: #fff;
        }
    `;

    document.head.appendChild(style);
}

function showCitizenDeleteConfirmDialog(message) {
    ensureCitizenDeleteDialogStyles();

    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'citizen-confirm-overlay';
        overlay.innerHTML = `
            <div class="citizen-confirm-card" role="dialog" aria-modal="true" aria-labelledby="citizenDeleteDialogTitle">
                <div class="citizen-confirm-header">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] opacity-90">Confirm Deletion</p>
                    <h3 id="citizenDeleteDialogTitle" class="text-lg font-bold mt-1">Delete this report?</h3>
                </div>
                <div class="citizen-confirm-body">
                    <p class="text-sm leading-6">${escapeHtml(message)}</p>
                </div>
                <div class="citizen-confirm-actions">
                    <button type="button" class="citizen-confirm-btn citizen-confirm-btn--cancel">No, keep it</button>
                    <button type="button" class="citizen-confirm-btn citizen-confirm-btn--confirm">Yes, delete it</button>
                </div>
            </div>
        `;

        const cancelButton = overlay.querySelector('.citizen-confirm-btn--cancel');
        const confirmButton = overlay.querySelector('.citizen-confirm-btn--confirm');

        const cleanup = (result) => {
            overlay.remove();
            document.body.style.overflow = '';
            resolve(result);
        };

        cancelButton.addEventListener('click', () => cleanup(false));
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                cleanup(false);
            }
        });

        confirmButton.addEventListener('click', () => {
            cancelButton.disabled = true;
            confirmButton.disabled = true;
            confirmButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Deleting...';
            cleanup(true);
        });

        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';
        confirmButton.focus();
    });
}

// Cancel Report Function
async function cancelReport(incidentId, markerId) {
    const shouldDelete = await showCitizenDeleteConfirmDialog('Delete this reported incident? This action cannot be undone.');
    if (!shouldDelete) {
        return;
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    try {
        const buttons = document.querySelectorAll(`[onclick*="cancelReport(${incidentId}"]`);
        buttons.forEach(btn => {
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Deleting...';
            btn.disabled = true;
        });
        window.showCitizenToast?.('Deleting report...', false, 2200);
        
        if (markerId) {
            const response = await fetch(`/api/markers/${markerId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                const payload = await response.json().catch(() => ({}));
                throw new Error(payload.message || 'Failed to delete report.');
            }
        }

        const incidentIndex = incidentsData.findIndex((incident) => Number(incident.incident_id) === Number(incidentId));
        if (incidentIndex !== -1) {
            incidentsData.splice(incidentIndex, 1);
        }

        nearbyAlertsData = nearbyAlertsData.filter((incident) => Number(incident.incident_id) !== Number(incidentId));
        document.getElementById(`report-row-${incidentId}`)?.remove();
        renderCitizenAlertsList();
        updateCitizenReportSummaryCounts();
        syncCitizenReportsEmptyState();
        if (typeof window.refreshCitizenOverviewData === 'function') {
            window.refreshCitizenOverviewData();
        }
        window.showCitizenToast?.('Report deleted successfully.');
    } catch (error) {
        console.error('Error cancelling report:', error);
        window.showCitizenToast?.(error.message || 'Failed to delete report. Please try again.', true, 3600);
        const buttons = document.querySelectorAll(`[onclick*="cancelReport(${incidentId}"]`);
        buttons.forEach(btn => {
            btn.innerHTML = '<i class="fa-regular fa-trash-can mr-1"></i> Cancel';
            btn.disabled = false;
        });
    }
}

function findCitizenAlertById(incidentId) {
    const nearbyMatch = nearbyAlertsData.find((incident) => String(incident.incident_id) === String(incidentId)) || null;
    const fullMatch = incidentsData.find((incident) => String(incident.incident_id) === String(incidentId)) || null;

    if (!nearbyMatch && !fullMatch) {
        return null;
    }

    return {
        ...(fullMatch || {}),
        ...(nearbyMatch || {}),
        marker: {
            ...(fullMatch?.marker || {}),
            lat: nearbyMatch?.lat ?? fullMatch?.marker?.lat ?? null,
            lng: nearbyMatch?.lng ?? fullMatch?.marker?.lng ?? null,
            description: nearbyMatch?.address ?? fullMatch?.marker?.description ?? '',
            address: fullMatch?.marker?.address ?? '',
        },
    };
}

function formatIncidentDateTime(value) {
    if (!value) return 'Unknown';

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString([], {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

function buildCitizenAlertDetailsMarkup(incident) {
    const typeMeta = getNearbyAlertTypeMetaResolved(incident);
    const statusMeta = getNearbyAlertStatusMeta(incident.incident_status || incident.incidentStatus?.status);
    const marker = incident.marker || {};
    const lat = marker.lat ?? incident.lat;
    const lng = marker.lng ?? incident.lng;
    const hasCoordinates = lat !== null && lat !== undefined && lng !== null && lng !== undefined;
    const reporterName = incident.reporter?.first_name
        ? `${incident.reporter.first_name} ${incident.reporter.last_name || ''}`.trim()
        : 'Community report';
    const stationName = incident.station?.station_name || 'Dispatching in progress';
    const description = marker.description || incident.description || 'No additional incident narrative was provided for this alert.';
    const address = marker.address || marker.description || incident.address || 'Address is still being confirmed.';
    const distanceText = typeof incident.distance_meters === 'number'
        ? `${(incident.distance_meters / 1000).toFixed(1)} km from your area`
        : 'Distance unavailable';

    return `
        <div class="alert-details-shell">
            <section class="alert-details-hero">
                <div class="alert-details-icon">${typeMeta.icon}</div>
                <div class="min-w-0 flex-1">
                    <p class="alert-details-kicker">Nearby emergency alert</p>
                    <h3 class="alert-details-title">${escapeHtml(typeMeta.name)} Incident</h3>
                    <p class="alert-details-subtitle">Alert ID #${escapeHtml(String(incident.incident_id || 'N/A'))}</p>
                    <div class="alert-details-chip-row">
                        <span class="alert-details-chip ${statusMeta.badge}">
                            <i class="fa-solid fa-bell-concierge"></i>
                            ${escapeHtml(statusMeta.label)}
                        </span>
                        <span class="alert-details-chip">
                            <i class="fa-regular fa-clock text-orange-500"></i>
                            ${escapeHtml(incident.created_at_human || formatIncidentDateTime(incident.created_at))}
                        </span>
                        <span class="alert-details-chip">
                            <i class="fa-solid fa-location-crosshairs text-orange-500"></i>
                            ${escapeHtml(distanceText)}
                        </span>
                    </div>
                </div>
            </section>

            <section class="alert-details-grid">
                <div class="alert-details-card">
                    <div class="alert-details-stat">
                        <div class="alert-details-stat-icon">
                            <i class="fa-solid fa-signal"></i>
                        </div>
                        <div class="alert-details-stat-copy">
                            <p>Current status</p>
                            <p>${escapeHtml(statusMeta.summary)}</p>
                        </div>
                    </div>
                </div>

                <div class="alert-details-card">
                    <div class="alert-details-stat">
                        <div class="alert-details-stat-icon">
                            <i class="fa-solid fa-house-fire"></i>
                        </div>
                        <div class="alert-details-stat-copy">
                            <p>Assigned response</p>
                            <p>${escapeHtml(stationName)}</p>
                        </div>
                    </div>
                </div>

                <div class="alert-details-card">
                    <div class="alert-details-label">
                        <i class="fa-regular fa-calendar"></i>
                        Reported at
                    </div>
                    <p class="alert-details-value">${escapeHtml(formatIncidentDateTime(incident.created_at))}</p>
                </div>

                <div class="alert-details-card">
                    <div class="alert-details-label">
                        <i class="fa-regular fa-user"></i>
                        Source
                    </div>
                    <p class="alert-details-value">${escapeHtml(reporterName)}</p>
                </div>

                <div class="alert-details-card alert-details-card--full">
                    <div class="alert-details-label">
                        <i class="fa-solid fa-location-dot"></i>
                        Location details
                    </div>
                    <p class="alert-details-value" id="citizen-alert-modal-location-${incident.incident_id}">${escapeHtml(address)}</p>
                    ${hasCoordinates ? `
                        <p class="alert-details-value alert-details-value--muted mt-2">
                            Coordinates: ${escapeHtml(formatCoord(lat))}, ${escapeHtml(formatCoord(lng))}
                        </p>
                    ` : `
                        <p class="alert-details-value alert-details-value--muted mt-2">Coordinates are not available yet.</p>
                    `}
                </div>

                <div class="alert-details-card alert-details-card--full">
                    <div class="alert-details-label">
                        <i class="fa-solid fa-align-left"></i>
                        Situation summary
                    </div>
                    <p class="alert-details-value alert-details-value--muted">${escapeHtml(description)}</p>
                </div>

                ${hasCoordinates ? `
                    <div class="alert-details-card alert-details-card--full">
                            <button type="button" class="w-full bg-blue-600 text-white px-4 py-3 rounded-xl text-sm font-semibold hover:bg-blue-700 transition" onclick="viewOnMap(${parseFloat(lat)}, ${parseFloat(lng)})">
                                <i class="fa-regular fa-map mr-2"></i> Open location in Google Maps
                            </button>
                    </div>
                ` : ''}
            </section>
        </div>
    `;
}

async function loadAddressForCitizenAlertModal(incidentId, lat, lng) {
    const locationSpan = document.getElementById(`citizen-alert-modal-location-${incidentId}`);
    if (!locationSpan) return;

    const numLat = parseFloat(lat);
    const numLng = parseFloat(lng);

    if (isNaN(numLat) || isNaN(numLng)) {
        locationSpan.innerHTML = 'Invalid coordinates';
        return;
    }

    try {
        if (typeof window.setThisName === 'function') {
            const address = await window.setThisName(numLat, numLng);
            if (address && address !== '') {
                locationSpan.innerHTML = escapeHtml(address);
                locationSpan.title = address;
                return;
            }
        }

        locationSpan.innerHTML = `${numLat.toFixed(6)}, ${numLng.toFixed(6)}`;
    } catch (error) {
        console.error('Error loading alert modal address:', error);
        locationSpan.innerHTML = `${numLat.toFixed(6)}, ${numLng.toFixed(6)}`;
    }
}

function showCitizenAlertDetailsModal(incidentId) {
    console.log('showCitizenAlertDetailsModal', incidentId);
    currentIncidentId = incidentId;
    const modalBody = document.getElementById('citizenAlertDetailsContent');
    if (!modalBody) return;

    window.logCitizenUserAction?.('viewed alert details');

    showCitizenDashboardModalById('citizenAlertDetailsModal');

    const incident = findCitizenAlertById(incidentId);
    if (!incident) {
        modalBody.innerHTML = `
            <div class="text-center py-10">
                <i class="fa-solid fa-circle-exclamation text-3xl text-red-500"></i>
                <p class="mt-3 text-sm text-gray-600">Alert details are not available right now.</p>
            </div>
        `;
        return;
    }

    modalBody.innerHTML = buildCitizenAlertDetailsMarkup(incident);

    const lat = incident.marker?.lat ?? incident.lat;
    const lng = incident.marker?.lng ?? incident.lng;
    if (lat && lng) {
        loadAddressForCitizenAlertModal(incident.incident_id, parseFloat(lat), parseFloat(lng));
    }
}

// Show Incident Details Modal (VIEW ONLY)
async function showIncidentDetailsModal(incidentId) {
    currentIncidentId = incidentId;
    const modalBody = document.getElementById('incidentDetailsContent');
    if (!modalBody) return;

    window.logCitizenUserAction?.('viewed incident details');

    openModal('incidentDetailsModal');
    modalBody.innerHTML = `
        <div class="text-center py-8">
            <i class="fa-solid fa-spinner fa-spin text-2xl text-orange-600"></i>
            <p class="mt-2 text-gray-500">Loading incident details...</p>
        </div>
    `;

    try {
        const response = await fetch(`/api/incidents/${incidentId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`Failed to load incident details: ${response.status}`);
        }

        const payload = await response.json();
        const incident = payload?.data;
        if (!incident) {
            throw new Error('Incident data missing');
        }

        const typeInfo = incidentTypeMap[incident.incident_type_id] || { name: incident.incidentType?.type || 'Other', icon: '⚠️', color: 'gray' };
        const statusInfo = statusMap[incident.incident_status_id] || { name: incident.incidentStatus?.status || 'Unknown', class: 'bg-gray-100 text-gray-700', canCancel: false };
        const lat = incident.marker?.lat ? formatCoord(incident.marker.lat) : 'N/A';
        const lng = incident.marker?.lng ? formatCoord(incident.marker.lng) : 'N/A';
        const locationText = (lat !== 'N/A' && lng !== 'N/A') ? `${lat}, ${lng}` : 'Pending';
        const reporterName = incident.reporter?.first_name
            ? `${incident.reporter.first_name} ${incident.reporter.last_name || ''}`.trim()
            : (incident.reported_by || 'Anonymous');

        modalBody.innerHTML = `
            <div class="space-y-4">
                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                    <div class="w-12 h-12 bg-${typeInfo.color}-100 rounded-full flex items-center justify-center">
                        <span class="text-2xl">${typeInfo.icon}</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg">${escapeHtml(incident.incidentType?.type || typeInfo.name)} Incident</h3>
                        <p class="text-sm text-gray-500">ID: #${incident.incident_id}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Status</p>
                        <p class="font-medium">
                            <span class="px-3 py-1 rounded-full text-xs ${statusInfo.class}">${escapeHtml(incident.incidentStatus?.status || statusInfo.name)}</span>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Reported</p>
                        <p class="font-medium text-sm">${incident.created_at ? new Date(incident.created_at).toLocaleString() : 'Unknown'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Location</p>
                        <p class="font-medium text-sm" id="modal-location-${incident.incident_id}">${locationText}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Reported By</p>
                        <p class="font-medium text-sm">${escapeHtml(String(reporterName))}</p>
                    </div>
                </div>
                ${incident.marker?.description ? `
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-xs text-gray-500 mb-1">Description</p>
                    <p class="text-sm text-gray-700">${escapeHtml(incident.marker.description)}</p>
                </div>
                ` : ''}
                ${incident.marker?.lat && incident.marker?.lng ? `
                <div class="flex gap-2">
                    <button class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition" onclick="viewOnMap(${parseFloat(incident.marker.lat)}, ${parseFloat(incident.marker.lng)})">
                        <i class="fa-regular fa-map mr-1"></i> Open in Google Maps
                    </button>
                </div>
                ` : ''}
            </div>
        `;

        if (incident.marker?.lat && incident.marker?.lng) {
            loadAddressForModal(incident.incident_id, parseFloat(incident.marker.lat), parseFloat(incident.marker.lng));
        }
    } catch (error) {
        console.error('Error loading incident details modal:', error);
        modalBody.innerHTML = `
            <div class="text-center py-10">
                <i class="fa-solid fa-circle-exclamation text-3xl text-red-500"></i>
                <p class="mt-3 text-sm text-gray-600">Failed to load incident details.</p>
            </div>
        `;
    }
}
// Show Report Edit Modal (EDITABLE)
function showReportEditModal(incidentId) {
    console.log('showReportEditModal', incidentId);
    currentEditIncident = incidentsData.find(i => i.incident_id == incidentId);
    console.log('current: ', currentEditIncident);
    if (!currentEditIncident) return;
    if (!canEditCitizenReport(currentEditIncident)) {
        const statusMeta = getReportStatusMeta(currentEditIncident.incident_status_id);
        window.showCitizenToast?.(`Only reported incidents can be edited. Current status: ${statusMeta.label}.`, true);
        return;
    }

    window.logCitizenUserAction?.('opened incident editor');
    
    const typeInfo = incidentTypeMap[currentEditIncident.incident_type_id] || { name: 'Other', icon: '⚠️', color: 'gray' };
    const typeOptions = incidentTypesData.map((type) => `
        <option value="${type.incident_type_id}" ${Number(currentEditIncident.incident_type_id) === Number(type.incident_type_id) ? 'selected' : ''}>
            ${escapeHtml(type.type)}
        </option>
    `).join('');
    
    const content = `
        <div class="space-y-4">
            <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                <div class="w-12 h-12 bg-${typeInfo.color}-100 rounded-full flex items-center justify-center">
                    <span class="text-2xl">${typeInfo.icon}</span>
                </div>
                <div>
                    <h3 class="font-semibold text-lg">Edit ${typeInfo.name} Report</h3>
                    <p class="text-sm text-gray-500">ID: #${currentEditIncident.incident_id}</p>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Category</label>
                    <select id="edit_incident_type_id" class="form-input">
                        ${typeOptions}
                    </select>
                </div>
                <div>
                    <label class="form-label">Reported On</label>
                    <input type="text" class="form-input bg-gray-50" value="${new Date(currentEditIncident.created_at).toLocaleString()}" readonly disabled>
                </div>
                <div class="col-span-2">
                    <label class="form-label">Title</label>
                    <input type="text" id="edit_title" class="form-input" value="${escapeHtml(currentEditIncident.marker?.title || '')}" placeholder="Brief incident title">
                </div>
                <div class="col-span-2">
                    <label class="form-label">Description</label>
                    <textarea id="edit_description" class="form-textarea" rows="4">${escapeHtml(currentEditIncident.marker?.description || '')}</textarea>
                </div>
            </div>
            
            <div class="bg-blue-50 p-3 rounded-lg">
                <p class="text-xs text-blue-600"><i class="fa-solid fa-info-circle mr-1"></i> You can update the incident category, title, and description while the report is still in Reported status.</p>
            </div>
        </div>
    `;
    const reportEditContent = document.getElementById('reportEditContent');
    if (!reportEditContent) {
        console.error('Report edit modal body not found');
        return;
    }

    console.log('applying: ');
    reportEditContent.innerHTML = content;
    showCitizenDashboardModalById('reportEditModal');
}

// Save report changes
async function saveReportChanges() {
    if (!currentEditIncident) return;
    if (!canEditCitizenReport(currentEditIncident)) {
        window.showCitizenToast?.('Only reported incidents can be edited.', true);
        return;
    }
    
    const updatedData = {
        incident_id: currentEditIncident.incident_id,
        incident_type_id: document.getElementById('edit_incident_type_id')?.value,
        title: document.getElementById('edit_title')?.value,
        description: document.getElementById('edit_description')?.value,
    };
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    try {
        const saveBtn = document.querySelector('#reportEditModal .btn-primary');
        saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Saving...';
        saveBtn.disabled = true;
        window.showCitizenToast?.('Saving report changes...', false, 1600);
        
        // Update marker
        if (currentEditIncident.marker_id) {
            const markerResponse = await fetch(`/api/markers/${currentEditIncident.marker_id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    title: updatedData.title,
                    description: updatedData.description,
                    markerCategory_id: currentEditIncident.marker?.markerCategory_id ?? 3
                })
            });

            if (!markerResponse.ok) {
                const markerPayload = await markerResponse.json().catch(() => ({}));
                throw new Error(markerPayload.message || 'Failed to update marker details.');
            }
        }
        
        const incidentResponse = await fetch(`/citizen/incidents/${currentEditIncident.incident_id}/update`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                incident_type_id: updatedData.incident_type_id
            })
        });

        if (!incidentResponse.ok) {
            const incidentPayload = await incidentResponse.json().catch(() => ({}));
            throw new Error(incidentPayload.message || 'Failed to update incident category.');
        }
        
        currentEditIncident.incident_type_id = Number(updatedData.incident_type_id);
        currentEditIncident.marker = currentEditIncident.marker || {};
        currentEditIncident.marker.title = updatedData.title;
        currentEditIncident.marker.description = updatedData.description;

        const nearbyMatch = nearbyAlertsData.find((incident) => Number(incident.incident_id) === Number(currentEditIncident.incident_id));
        if (nearbyMatch) {
            nearbyMatch.incident_type_id = Number(updatedData.incident_type_id);
            nearbyMatch.incident_type = getIncidentTypeName(updatedData.incident_type_id);
            nearbyMatch.address = updatedData.description;
        }

        syncReportRowWithIncident(currentEditIncident);
        renderCitizenAlertsList();
        closeReportEditModal();
        if (typeof window.refreshCitizenOverviewData === 'function') {
            window.refreshCitizenOverviewData();
        }
        window.showCitizenToast?.('Report updated successfully.');
        
    } catch (error) {
        console.error('Error saving report:', error);
        window.showCitizenToast?.(error.message || 'Failed to save changes. Please try again.', true, 3600);
        
        const saveBtn = document.querySelector('#reportEditModal .btn-primary');
        saveBtn.innerHTML = '<i class="fa-regular fa-floppy-disk mr-1"></i> Save Changes';
        saveBtn.disabled = false;
        return;
    }

    const saveBtn = document.querySelector('#reportEditModal .btn-primary');
    if (saveBtn) {
        saveBtn.innerHTML = '<i class="fa-regular fa-floppy-disk mr-1"></i> Save Changes';
        saveBtn.disabled = false;
    }
}

async function loadAddressForModal(incidentId, lat, lng) {
    const locationSpan = document.getElementById(`modal-location-${incidentId}`);
    if (!locationSpan) return;
    
    const numLat = parseFloat(lat);
    const numLng = parseFloat(lng);
    
    if (isNaN(numLat) || isNaN(numLng)) {
        locationSpan.innerHTML = 'Invalid coordinates';
        return;
    }
    
    try {
        if (typeof window.setThisName === 'function') {
            const address = await window.setThisName(numLat, numLng);
            if (address && address !== '') {
                locationSpan.innerHTML = address;
                locationSpan.title = address;
            } else {
                locationSpan.innerHTML = `${numLat.toFixed(6)}, ${numLng.toFixed(6)}`;
            }
        } else {
            locationSpan.innerHTML = `${numLat.toFixed(6)}, ${numLng.toFixed(6)}`;
        }
    } catch (error) {
        console.error('Error loading address for modal:', error);
        locationSpan.innerHTML = `${numLat.toFixed(6)}, ${numLng.toFixed(6)}`;
    }
}

function showCitizenDashboardModalById(modalId) {
    console.log('showCitizenDashboardModalById', modalId);
    const modal = document.getElementById(modalId);
    if (!modal) {
        console.error('Modal not found:', modalId);
        return;
    }

    if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }

    modal.classList.add('show');
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeDetailsModal() {
    const modal = document.getElementById('incidentDetailsModal');
    if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
    }
    document.body.style.overflow = 'auto';
    currentIncidentId = null;
}

function closeCitizenAlertDetailsModal() {
    const modal = document.getElementById('citizenAlertDetailsModal');
    if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
    }
    document.body.style.overflow = 'auto';
    currentIncidentId = null;
}

function closeReportEditModal() {
    const modal = document.getElementById('reportEditModal');
    if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
    }
    document.body.style.overflow = 'auto';
    currentEditIncident = null;
}

function shareCurrentIncident() {
    const incident = incidentsData.find(i => i.incident_id === currentIncidentId);
    if (incident) {
        shareAlert(currentIncidentId);
    }
}

function shareCurrentCitizenAlert() {
    const incident = findCitizenAlertById(currentIncidentId);
    if (incident) {
        shareAlert(currentIncidentId);
    }
}

function shareAlert(incidentId) {
    const incident = incidentsData.find(i => i.incident_id === incidentId);
    const typeInfo = incidentTypeMap[incident.incident_type_id] || { name: 'Incident', icon: '⚠️' };
    const statusInfo = statusMap[incident.incident_status_id] || { name: 'Active' };
    
    if (incident) {
        const shareData = {
            title: `Emergency Alert: ${typeInfo.name} Incident`,
            text: `There is an active ${typeInfo.name} incident. Status: ${statusInfo.name}.`,
            url: window.location.href
        };
        
        if (navigator.share) {
            navigator.share(shareData).catch(() => {});
        } else {
            navigator.clipboard.writeText(`${shareData.title}\n${shareData.text}`);
            alert('Alert details copied to clipboard!');
        }
    }
}

function shareCitizenAlertById(incidentId) {
    const incident = findCitizenAlertById(incidentId) || incidentsData.find(i => i.incident_id === incidentId);
    if (!incident) {
        return;
    }

    const typeInfo = getNearbyAlertTypeMetaResolved(incident);
    const statusInfo = getNearbyAlertStatusMeta(incident.incident_status || incident.incidentStatus?.status);
    const shareData = {
        title: `Emergency Alert: ${typeInfo.name} Incident`,
        text: `${typeInfo.name} alert nearby. Status: ${statusInfo.label}. ${incident.address || incident.marker?.description || ''}`.trim(),
        url: window.location.href
    };

    if (navigator.share) {
        navigator.share(shareData).catch(() => {});
    } else if (navigator.clipboard) {
        navigator.clipboard.writeText(`${shareData.title}\n${shareData.text}`);
        alert('Alert details copied to clipboard!');
    }
}

function getNearbyAlertTypeMeta(incidentTypeId, incidentTypeName = '') {
    if (Number(incidentTypeId) === 1) return { name: 'Fire', icon: '🔥', color: 'bg-red-500' };
    if (Number(incidentTypeId) === 2) return { name: 'Medical Emergency', icon: '🚑', color: 'bg-blue-500' };
    if (Number(incidentTypeId) === 3) return { name: 'Rescue', icon: '🆘', color: 'bg-green-500' };
    return { name: incidentTypeName || 'Other', icon: '⚠️', color: 'bg-yellow-500' };
}

function getNearbyAlertStatusMeta(statusValue = '') {
    const normalized = String(statusValue).trim().toLowerCase();
    if (normalized === 'reported') return { label: 'Reported', badge: 'bg-red-100 text-red-700', summary: 'Awaiting response' };
    if (normalized === 'responding' || normalized === 'en-route') return { label: 'Responding', badge: 'bg-yellow-100 text-yellow-700', summary: 'Crews dispatched' };
    if (normalized === 'on scene' || normalized === 'on-scene') return { label: 'On Scene', badge: 'bg-purple-100 text-purple-700', summary: 'Crews on scene' };
    if (normalized === 'completed' || normalized === 'controlled') return { label: 'Controlled', badge: 'bg-blue-100 text-blue-700', summary: 'Incident under control' };
    if (normalized === 'closed') return { label: 'Closed', badge: 'bg-gray-100 text-gray-700', summary: 'Incident closed' };
    return { label: 'Unknown', badge: 'bg-gray-100 text-gray-700', summary: 'Status unavailable' };
}

function getNearbyAlertTypeMetaResolved(incident = {}) {
    const normalizedName = String(getIncidentTypeDisplayName(incident) || '').trim().toLowerCase();
    if (normalizedName === 'fire') return { name: 'Fire', icon: '🔥', color: 'bg-red-500' };
    if (normalizedName.includes('medical')) return { name: 'Medical Emergency', icon: '🚑', color: 'bg-blue-500' };
    if (normalizedName.includes('rescue')) return { name: 'Rescue', icon: '🆘', color: 'bg-green-500' };
    return { name: getIncidentTypeDisplayName(incident) || 'Other', icon: '⚠️', color: 'bg-yellow-500' };
}

function renderCitizenAlertsList() {
    const alertsList = document.getElementById('alertsList');
    if (!alertsList) return;

    const activeCountNode = document.getElementById('citizenAlertsActiveCount');
    const tableCountNode = document.getElementById('citizenAlertsTableCount');
    const fireCountNode = document.getElementById('citizenAlertsFireCount');
    const medicalCountNode = document.getElementById('citizenAlertsMedicalCount');
    const rescueCountNode = document.getElementById('citizenAlertsRescueCount');

    const items = Array.isArray(nearbyAlertsData) ? [...nearbyAlertsData] : [];
    items.sort((left, right) => new Date(right.created_at || 0) - new Date(left.created_at || 0));

    if (activeCountNode) activeCountNode.textContent = items.length;
    if (tableCountNode) tableCountNode.textContent = items.length;
    if (fireCountNode) fireCountNode.textContent = items.filter((item) => getNearbyAlertTypeMetaResolved(item).name === 'Fire').length;
    if (medicalCountNode) medicalCountNode.textContent = items.filter((item) => getNearbyAlertTypeMetaResolved(item).name === 'Medical Emergency').length;
    if (rescueCountNode) rescueCountNode.textContent = items.filter((item) => getNearbyAlertTypeMetaResolved(item).name === 'Rescue').length;

    if (!items.length) {
        alertsList.innerHTML = `
            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-regular fa-bell-slash text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-700 mb-2">No Active Alerts</h3>
                <p class="text-sm text-gray-500 mb-4">There are no nearby active incidents in your area at the moment.</p>
                <button class="bg-orange-600 text-white px-6 py-2 rounded-lg text-sm hover:bg-orange-700 transition" onclick="openIncidentModal()">
                    <i class="fa-regular fa-flag mr-2"></i>Report an Incident
                </button>
            </div>
        `;
        return;
    }

    alertsList.innerHTML = items.map((incident) => {
        const typeMeta = getNearbyAlertTypeMetaResolved(incident);
        const statusMeta = getNearbyAlertStatusMeta(incident.incident_status);
        const address = escapeHtml(incident.address || 'No additional details provided.');
        const humanTime = escapeHtml(incident.created_at_human || 'Just now');
        const distance = typeof incident.distance_meters === 'number'
            ? `<span><i class="fa-solid fa-location-crosshairs mr-1"></i> ${(incident.distance_meters / 1000).toFixed(1)} km away</span>`
            : '';
        const canViewMap = incident.lat && incident.lng;

        return `
            <div class="p-5 hover:bg-gray-50 transition alert-item" data-type="${(getIncidentTypeDisplayName(incident) || 'incident').toLowerCase()}" data-date="${incident.created_at || ''}" data-id="${incident.incident_id || ''}" data-lat="${incident.lat || ''}" data-lng="${incident.lng || ''}">
                <div class="flex items-start gap-4">
                    <span class="w-2 h-2 ${typeMeta.color} rounded-full mt-2"></span>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="font-semibold text-gray-800">${typeMeta.icon} ${escapeHtml(typeMeta.name)} Incident</h3>
                            <span class="text-xs ${statusMeta.badge} px-2 py-1 rounded-full">${statusMeta.label}</span>
                        </div>
                        <p class="text-sm text-gray-600 mb-2">${address}</p>
                        <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500">
                            <span><i class="fa-regular fa-clock mr-1"></i> ${humanTime}</span>
                            <span id="location-${incident.incident_id}" class="location-span">
                                ${canViewMap ? '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Loading address...' : '<i class="fa-solid fa-map-pin mr-1"></i> Location pending'}
                            </span>
                            <span><i class="fa-regular fa-flag mr-1"></i> ${statusMeta.summary}</span>
                            ${distance}
                        </div>
                        <div class="flex gap-2 mt-3">
                            <button type="button" class="text-xs bg-orange-100 text-orange-700 px-3 py-1 rounded-full hover:bg-orange-200 transition" onclick="showCitizenAlertDetailsModal(${incident.incident_id})">
                                <i class="fa-regular fa-eye mr-1"></i> View Details
                            </button>
                            <button type="button" class="text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded-full hover:bg-gray-200 transition" onclick="shareAlert(${incident.incident_id})">
                                <i class="fa-regular fa-share-from-square mr-1"></i> Share
                            </button>
                            ${canViewMap ? `<button type="button" class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded-full hover:bg-blue-200 transition" onclick="viewOnMap(${incident.lat}, ${incident.lng})"><i class="fa-regular fa-map mr-1"></i> View on Map</button>` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    setTimeout(() => { loadAllAddresses(); }, 60);
}

window.refreshCitizenAlertsSection = function(payload) {
    nearbyAlertsData = Array.isArray(payload) ? payload : [];
    renderCitizenAlertsList();
};

// Filter and search functionality
document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const searchInput = document.getElementById('alertSearch');
    const sortSelect = document.getElementById('alertSort');
    let currentPage = 1;
    const itemsPerPage = 10;
    
    setTimeout(() => {
        renderCitizenAlertsList();
        loadAllAddresses();
    }, 1000);
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => {
                b.classList.remove('active', 'bg-orange-600', 'text-white');
                b.classList.add('bg-gray-100', 'text-gray-600');
            });
            this.classList.add('active', 'bg-orange-600', 'text-white');
            this.classList.remove('bg-gray-100', 'text-gray-600');
            
            const filter = this.dataset.filter;
            filterAlerts(filter, searchInput?.value || '');
        });
    });
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const activeFilter = document.querySelector('.filter-btn.active')?.dataset.filter || 'all';
            filterAlerts(activeFilter, this.value);
            currentPage = 1;
            updatePagination();
        });
    }
    
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const alertList = document.getElementById('alertsList');
            const items = Array.from(document.querySelectorAll('.alert-item'));
            
            items.sort((a, b) => {
                if (this.value === 'newest') {
                    return new Date(b.dataset.date) - new Date(a.dataset.date);
                } else if (this.value === 'oldest') {
                    return new Date(a.dataset.date) - new Date(b.dataset.date);
                } else if (this.value === 'type') {
                    return parseInt(a.dataset.type) - parseInt(b.dataset.type);
                }
                return 0;
            });
            
            items.forEach(item => alertList.appendChild(item));
            currentPage = 1;
            updatePagination();
        });
    }
    
    function updatePagination() {
        const visibleItems = Array.from(document.querySelectorAll('.alert-item')).filter(item => item.style.display !== 'none');
        const totalPages = Math.ceil(visibleItems.length / itemsPerPage);
        const start = (currentPage - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        
        document.querySelectorAll('.alert-item').forEach(item => {
            if (item.style.display !== 'none') {
                item.style.display = 'none';
            }
        });
        
        visibleItems.slice(start, end).forEach(item => {
            item.style.display = 'block';
        });
        
        const showingStart = visibleItems.length > 0 ? start + 1 : 0;
        const showingEnd = Math.min(end, visibleItems.length);
        const showingStartEl = document.getElementById('showingStart');
        const showingEndEl = document.getElementById('showingEnd');
        if (showingStartEl) showingStartEl.textContent = showingStart;
        if (showingEndEl) showingEndEl.textContent = showingEnd;
        
        const prevBtn = document.querySelector('.pagination-prev');
        const nextBtn = document.querySelector('.pagination-next');
        
        if (prevBtn) {
            prevBtn.disabled = currentPage === 1;
            prevBtn.onclick = () => {
                if (currentPage > 1) {
                    currentPage--;
                    updatePagination();
                }
            };
        }
        
        if (nextBtn) {
            nextBtn.disabled = currentPage === totalPages || totalPages === 0;
            nextBtn.onclick = () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    updatePagination();
                }
            };
        }
    }
    
    function filterAlerts(type, searchTerm = '') {
        const alertItems = document.querySelectorAll('.alert-item');
        let visibleCount = 0;
        
        alertItems.forEach(item => {
            const itemType = item.dataset.type;
            const itemText = item.textContent.toLowerCase();
            const matchesType = type === 'all' || itemType === type;
            const matchesSearch = searchTerm === '' || itemText.includes(searchTerm.toLowerCase());
            
            if (matchesType && matchesSearch) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });
        
        currentPage = 1;
        updatePagination();
        
        const alertsList = document.getElementById('alertsList');
        const existingNoResult = alertsList.querySelector('.no-result-message');
        
        if (visibleCount === 0 && !existingNoResult) {
            const noResultDiv = document.createElement('div');
            noResultDiv.className = 'p-8 text-center no-result-message';
            noResultDiv.innerHTML = `
                <i class="fa-solid fa-search text-gray-400 text-4xl mb-3"></i>
                <p class="text-gray-500">No alerts match your search criteria</p>
            `;
            alertsList.appendChild(noResultDiv);
        } else if (visibleCount > 0 && existingNoResult) {
            existingNoResult.remove();
        }
    }

    updatePagination();
});

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    if (event.target.classList && event.target.classList.contains('modal')) {
        event.target.classList.remove('show');
        event.target.style.display = 'none';
        event.target.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = 'auto';
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.modal.show').forEach(modal => {
            modal.classList.remove('show');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = 'auto';
        });
    }
});

// Expose functions globally
window.showReportEditModal = showReportEditModal;
window.showCitizenAlertDetailsModal = showCitizenAlertDetailsModal;
window.shareAlert = shareCitizenAlertById;
window.viewOnMap = viewOnMap;
window.cancelReport = cancelReport;
window.closeReportEditModal = closeReportEditModal;
window.closeCitizenAlertDetailsModal = closeCitizenAlertDetailsModal;
window.shareCurrentCitizenAlert = shareCurrentCitizenAlert;
window.saveReportChanges = saveReportChanges;
</script>


