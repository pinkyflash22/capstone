{{-- <div id="section-incidents" class="content-section">
    <!-- Header with Stats -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">Nearby Incidents</h1>
            <p class="text-sm text-gray-500 mt-1">Real-time incidents and emergencies in your area</p>
        </div>
        <div class="flex items-center gap-3 mt-3 sm:mt-0">
            <span class="bg-orange-100 text-orange-700 text-sm px-4 py-2 rounded-full flex items-center gap-2">
                <i class="fa-solid fa-location-dot"></i>
                Within 5 mile radius
            </span>
        </div>
    </div>

    <!-- Incident Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow p-4 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Active Fires</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $incidents->where('incident_type_id', '1')->where('status', '!=', 'resolved')->count() }}</p>
                </div>
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center text-red-600">
                    <i class="fa-solid fa-fire"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-4 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Medical Emergencies</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $incidents->where('incident_type_id', '2')->where('status', '!=', 'resolved')->count() }}</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                    <i class="fa-solid fa-truck-medical"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-4 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Other Incidents</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $incidents->whereNotIn('incident_type_id', ['1', '2'])->where('status', '!=', 'resolved')->count() }}</p>
                </div>
                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center text-yellow-600">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-4 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Responders On Scene</p>
                    <p class="text-2xl font-bold text-gray-800">12</p>
                </div>
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center text-green-600">
                    <i class="fa-solid fa-user"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Grid: Map + Incident List -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Map Column (spans 2 columns) -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow overflow-hidden">
          
        </div>
        
        <!-- Incident List Column -->
        <div class="bg-white rounded-xl shadow flex flex-col h-[600px]">
            <div class="p-4 border-b border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-list text-orange-600"></i>
                        Active Incidents
                    </h2>
                    <span class="bg-orange-100 text-orange-700 text-xs px-3 py-1 rounded-full font-medium">
                        {{ $incidents->where('status', '!=', 'resolved')->count() }} active
                    </span>
                </div>
                
                <!-- Filter Tabs -->
                <div class="flex gap-2 text-xs">
                    <button class="incident-filter-btn active px-3 py-1.5 rounded-full bg-orange-600 text-white" data-filter="all">All</button>
                    <button class="incident-filter-btn px-3 py-1.5 rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200" data-filter="fire">🔥 Fire</button>
                    <button class="incident-filter-btn px-3 py-1.5 rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200" data-filter="medical">🚑 Medical</button>
                    <button class="incident-filter-btn px-3 py-1.5 rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200" data-filter="other">⚠️ Other</button>
                </div>
            </div>
            
            <!-- Scrollable Incident List -->
            <div class="flex-1 overflow-y-auto p-4 space-y-3" id="incidentList">
                @php
                    $activeIncidents = $incidents->where('status', '!=', 'resolved')->sortByDesc('created_at');
                @endphp
                
                @forelse($activeIncidents as $incident)
                <div class="incident-item border rounded-lg p-4 hover:shadow-md transition cursor-pointer" 
                     data-id="{{ $incident->id }}"
                     data-type="{{ $incident->incident_type }}"
                     data-lat="{{ $incident->marker->lat ?? 0 }}"
                     data-lng="{{ $incident->marker->lng ?? 0 }}"
                     onclick="focusOnIncident({{ $incident->id }})">
                    
                    <div class="flex items-start gap-3">
                        <!-- Incident Icon -->
                        <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0
                            @if($incident->incident_type == 'fire') bg-red-100
                            @elseif($incident->incident_type == 'medical') bg-blue-100
                            @else bg-yellow-100
                            @endif">
                            <i class="fa-solid 
                                @if($incident->incident_type == 'fire') fa-fire text-red-600
                                @elseif($incident->incident_type == 'medical') fa-truck-medical text-blue-600
                                @else fa-triangle-exclamation text-yellow-600
                                @endif text-lg">
                            </i>
                        </div>
                        
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="font-semibold text-gray-800">
                                    {{ ucfirst($incident->incident_type) }} Incident
                                </h3>
                                <span class="text-xs px-2 py-1 rounded-full
                                    @if($incident->status == 'reported') bg-red-100 text-red-700
                                    @elseif($incident->status == 'responding') bg-yellow-100 text-yellow-700
                                    @else bg-green-100 text-green-700
                                    @endif">
                                    {{ ucfirst($incident->status) }}
                                </span>
                            </div>
                            
                            <p class="text-sm text-gray-600 mb-2">
                                @if($incident->marker)
                                    <span class="incident-distance" 
                                          data-lat="{{ $incident->marker->lat }}" 
                                          data-lng="{{ $incident->marker->lng }}">
                                        Calculating distance...
                                    </span> · 
                                    {{ $incident->marker->address ?? 'Location pinned' }}
                                @else
                                    Location pending
                                @endif
                            </p>
                            
                            <div class="flex items-center gap-3 text-xs text-gray-500">
                                <span><i class="fa-regular fa-clock mr-1"></i> {{ $incident->created_at->diffForHumans() }}</span>
                                @if($incident->responders_count ?? false)
                                <span><i class="fa-solid fa-people-group mr-1"></i> {{ $incident->responders_count }} responding</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fa-regular fa-circle-check text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-600 font-medium">No active incidents</p>
                    <p class="text-xs text-gray-400 mt-1">Your area is currently clear</p>
                </div>
                @endforelse
            </div>
            
            <!-- Quick Stats Footer -->
            <div class="p-4 bg-gray-50 border-t border-gray-200">
                <div class="grid grid-cols-2 gap-2 text-center text-xs">
                    <div>
                        <p class="text-gray-500">Avg Response Time</p>
                        <p class="font-bold text-gray-800">4.2 min</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Units Deployed</p>
                        <p class="font-bold text-gray-800">8</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Timeline -->
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-orange-600"></i>
            Recent Activity Timeline
        </h3>
        
        <div class="relative">
            <!-- Timeline Line -->
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
            
            <div class="space-y-4 ml-10">
                @php
                    $recentIncidents = $incidents->sortByDesc('created_at')->take(5);
                @endphp
                
                @foreach($recentIncidents as $incident)
                <div class="relative">
                    <!-- Timeline Dot -->
                    <div class="absolute -left-10 top-1 w-4 h-4 rounded-full border-2 border-white
                        @if($incident->status == 'resolved') bg-green-500
                        @elseif($incident->status == 'responding') bg-yellow-500
                        @else bg-red-500
                        @endif">
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-medium text-gray-800">
                                @if($incident->incident_type == 'fire') 🔥
                                @elseif($incident->incident_type == 'medical') 🚑
                                @else ⚠️
                                @endif
                                {{ ucfirst($incident->incident_type) }} Incident
                            </span>
                            <span class="text-xs text-gray-500">{{ $incident->created_at->format('h:i A') }}</span>
                        </div>
                        <p class="text-sm text-gray-600">
                            @if($incident->status == 'resolved')
                                Incident resolved · Area cleared
                            @elseif($incident->status == 'responding')
                                Crews responding · ETA {{ rand(3, 8) }} minutes
                            @else
                                New report · Awaiting dispatch
                            @endif
                        </p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Safety Tips & Resources -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-gradient-to-br from-orange-50 to-red-50 rounded-xl shadow p-5">
            <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                <i class="fa-solid fa-shield text-orange-600"></i>
                Safety Tips for Today
            </h3>
            <ul class="space-y-2 text-sm">
                <li class="flex items-start gap-2">
                    <i class="fa-solid fa-check-circle text-green-500 mt-0.5"></i>
                    <span>Keep emergency kit accessible</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fa-solid fa-check-circle text-green-500 mt-0.5"></i>
                    <span>Charge your mobile devices</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fa-solid fa-check-circle text-green-500 mt-0.5"></i>
                    <span>Review evacuation routes with family</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fa-solid fa-circle-exclamation text-orange-500 mt-0.5"></i>
                    <span>Red flag warning: avoid outdoor burning</span>
                </li>
            </ul>
        </div>

        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                <i class="fa-solid fa-phone-volume text-orange-600"></i>
                Quick Emergency Contacts
            </h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="text-center p-3 bg-gray-50 rounded-lg hover:bg-orange-50 transition cursor-pointer" onclick="window.location.href='tel:911'">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-2">
                        <i class="fa-solid fa-phone text-red-600"></i>
                    </div>
                    <p class="font-medium text-sm">911</p>
                    <p class="text-xs text-gray-500">Emergency</p>
                </div>
                <div class="text-center p-3 bg-gray-50 rounded-lg hover:bg-orange-50 transition cursor-pointer" onclick="window.location.href='tel:311'">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-2">
                        <i class="fa-solid fa-building text-blue-600"></i>
                    </div>
                    <p class="font-medium text-sm">311</p>
                    <p class="text-xs text-gray-500">Non-Emergency</p>
                </div>
            </div>
        </div>
    </div>
</div> --}}
<div id="section-incidents" class="content-section">
    <!-- Header with Stats -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">Nearby Incidents</h1>
            <p class="text-sm text-gray-500 mt-1">Real-time incidents and emergencies in your area</p>
        </div>
        <div class="flex items-center gap-3 mt-3 sm:mt-0">
            <span class="bg-orange-100 text-orange-700 text-sm px-4 py-2 rounded-full flex items-center gap-2">
                <i class="fa-solid fa-location-dot"></i>
                Within 5 mile radius
            </span>
        </div>
    </div>

    <!-- Incident Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow p-4 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Active Fires</p>
                    <p class="text-2xl font-bold text-gray-800" id="activeFiresCount">0</p>
                </div>
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center text-red-600">
                    <i class="fa-solid fa-fire"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-4 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Medical Emergencies</p>
                    <p class="text-2xl font-bold text-gray-800" id="activeMedicalCount">0</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                    <i class="fa-solid fa-truck-medical"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-4 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Other Incidents</p>
                    <p class="text-2xl font-bold text-gray-800" id="activeOtherCount">0</p>
                </div>
                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center text-yellow-600">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-4 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Responders On Scene</p>
                    <p class="text-2xl font-bold text-gray-800">12</p>
                </div>
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center text-green-600">
                    <i class="fa-solid fa-user"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Grid: Incident List + Details Panel -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Incident List Column (LEFT SIDE) -->
        <div class="bg-white rounded-xl shadow flex flex-col h-[600px] lg:col-span-1">
            <div class="p-4 border-b border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-list text-orange-600"></i>
                        Active Incidents
                    </h2>
                    <span class="bg-orange-100 text-orange-700 text-xs px-3 py-1 rounded-full font-medium" id="activeIncidentsCount">
                        0 active
                    </span>
                </div>
                
                <!-- Filter Tabs -->
                <div class="flex gap-2 text-xs">
                    <button class="incident-filter-btn active px-3 py-1.5 rounded-full bg-orange-600 text-white" data-filter="all">All</button>
                    <button class="incident-filter-btn px-3 py-1.5 rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200" data-filter="1">🔥 Fire</button>
                    <button class="incident-filter-btn px-3 py-1.5 rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200" data-filter="2">🚑 Medical</button>
                    <button class="incident-filter-btn px-3 py-1.5 rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200" data-filter="other">⚠️ Other</button>
                </div>
            </div>
            
            <!-- Scrollable Incident List -->
            <div class="flex-1 overflow-y-auto p-4 space-y-3" id="incidentList">
                @php
                    $activeIncidents = $incidents->filter(function($incident) {
                        return $incident->incident_status_id;
                    })->sortByDesc('created_at');
                @endphp
                
                @forelse($activeIncidents as $incident)
                <div class="incident-item border rounded-lg p-4 hover:shadow-md transition cursor-pointer" 
                     data-id="{{ $incident->incident_id }}"
                     data-type="{{ $incident->incident_type_id ?? '0' }}"
                     onclick="selectIncident({{ $incident->incident_id }})">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0
                            @if($incident->incident_type_id  == '1') bg-red-100
                            @elseif($incident->incident_type_id == '2') bg-blue-100
                            @else bg-yellow-100
                            @endif">
                            <i class="fa-solid 
                                @if($incident->incident_type_id == '1') fa-fire text-red-600
                                @elseif($incident->incident_type_id == '2') fa-truck-medical text-blue-600
                                @else fa-triangle-exclamation text-yellow-600
                                @endif text-lg">
                            </i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="font-semibold text-gray-800">
                                    {{ ucfirst($incident->incident_type_id ?? 'Unknown') }} Incident
                                </h3>
                                <span class="text-xs px-2 py-1 rounded-full
                                    @if($incident->incident_status_id == '1') bg-red-100 text-red-700
                                    @elseif($incident->incident_status_id == '2') bg-yellow-100 text-yellow-700
                                    @else bg-green-100 text-green-700
                                    @endif">
                                    {{ ucfirst($incident->incident_status_id ?? 'Unknown') }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mb-2">
                                {{ $incident->marker->lat ?? 'Location pinned' }}
                            </p>
                            <div class="flex items-center gap-3 text-xs text-gray-500">
                                <span><i class="fa-regular fa-clock mr-1"></i> {{ $incident->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-right text-gray-300 text-xs mt-2"></i>
                    </div>
                </div>
                @empty
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fa-regular fa-circle-check text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-600 font-medium">No active incidents</p>
                    <p class="text-xs text-gray-400 mt-1">Your area is currently clear</p>
                </div>
                @endforelse
            </div>
            
            <!-- Quick Stats Footer -->
            <div class="p-4 bg-gray-50 border-t border-gray-200">
                <div class="grid grid-cols-2 gap-2 text-center text-xs">
                    <div>
                        <p class="text-gray-500">Avg Response Time</p>
                        <p class="font-bold text-gray-800">4.2 min</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Units Deployed</p>
                        <p class="font-bold text-gray-800">8</p>
                    </div>
                </div>
            </div>
        </div>
        
       <!-- Incident Details Panel (RIGHT SIDE) - FIXED -->
<div class="lg:col-span-2 bg-white rounded-xl shadow flex flex-col h-[600px]">
    <div class="p-4 border-b border-gray-200 bg-gradient-to-r from-orange-50 to-red-50">
        <h2 class="font-bold text-gray-800 flex items-center gap-2">
            <i class="fa-solid fa-circle-info text-orange-600"></i>
            Incident Details
        </h2>
        <p class="text-xs text-gray-500 mt-1">Click on any incident from the list to view details</p>
    </div>
    
    <!-- Incident Details Content -->
    <div id="incidentDetailsPanel" class="flex-1 overflow-y-auto p-6">
        <div class="text-center py-12">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-map-marker-alt text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-600 mb-2">No Incident Selected</h3>
            <p class="text-sm text-gray-400">Click on any incident from the list to view detailed information here</p>
        </div>
    </div>
    
    <!-- Action Buttons Footer -->
    <div class="p-4 bg-gray-50 border-t border-gray-200 flex gap-3">
        <button id="navigateToIncidentBtn" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
            <i class="fa-solid fa-route mr-2"></i> Go to Google Maps
        </button>
        <button id="shareIncidentBtn" class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
            <i class="fa-solid fa-share-alt mr-2"></i> Share
        </button>
    </div>
</div>
    </div>

    <!-- Recent Activity Timeline -->
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-orange-600"></i>
            Recent Activity Timeline
        </h3>
        
        <div class="relative">
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
            <div class="space-y-4 ml-10" id="recentTimeline">
                @php
                    $recentIncidents = $incidents->sortByDesc('created_at')->take(5);
                @endphp
                
                @forelse($recentIncidents as $incident)
                <div class="relative">
                    <div class="absolute -left-10 top-1 w-4 h-4 rounded-full border-2 border-white
                        @if($incident->incident_status_id == '4') bg-green-500
                        @elseif($incident->incident_status_id == '2') bg-yellow-500
                        @else bg-red-500
                        @endif">
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-medium text-gray-800">
                                @if($incident->incident_type_id == '1') 🔥
                                @elseif($incident->incident_type_id == '2') 🚑
                                @else ⚠️
                                @endif
                                {{ ucfirst($incident->incident_type_id ?? 'Unknown') }} Incident
                            </span>
                            <span class="text-xs text-gray-500">{{ $incident->created_at->format('h:i A') }}</span>
                        </div>
                        <p class="text-sm text-gray-600">
                            @if($incident->incident_status_id == '4')
                                Incident resolved · Area cleared
                            @elseif($incident->incident_status_id == '2')
                                Crews responding · ETA {{ rand(3, 8) }} minutes
                            @else
                                New report · Awaiting dispatch
                            @endif
                        </p>
                    </div>
                </div>
                @empty
                <div class="text-center py-4 text-gray-400">No recent activity</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
<!-- Incident Details Modal (reuse from alerts section) -->
<div id="incidentDetailsModal" class="modal">
    <div class="modal-content max-w-2xl">
        <div class="modal-header">
            <h2 class="text-xl font-bold">Incident Details</h2>
            <button class="modal-close" onclick="closeModal('incidentDetailsModal')">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="incidentDetailsContent">
            <!-- Content will be populated dynamically -->
        </div>
        <div class="modal-footer">
            <button class="btn-outline" onclick="closeModal('incidentDetailsModal')">Close</button>
            <button class="btn-primary" onclick="shareIncident()">Share</button>
        </div>
    </div>
</div>
<script>
// ============================================
// GLOBAL FUNCTIONS - DEFINED IMMEDIATELY
// ============================================

// Store incidents data from Laravel (make it global)
window.incidentsData = @json($incidents);
window.currentSelectedIncident = null;

// Get status name from status ID
window.getStatusName = function(statusId) {
    const statusMap = {
        1: 'Reported',
        2: 'Responding', 
        3: 'Resolved',
        4: 'Closed'
    };
    return statusMap[statusId] || 'Unknown';
};

// Get status class from status ID
window.getStatusClass = function(statusId) {
    const statusClassMap = {
        1: 'bg-red-100 text-red-700',
        2: 'bg-yellow-100 text-yellow-700',
        3: 'bg-green-100 text-green-700',
        4: 'bg-gray-100 text-gray-700'
    };
    return statusClassMap[statusId] || 'bg-gray-100 text-gray-700';
};

// Get status icon from status ID
window.getStatusIcon = function(statusId) {
    const statusIconMap = {
        1: 'fa-flag',
        2: 'fa-truck',
        3: 'fa-check-circle',
        4: 'fa-folder-closed'
    };
    return statusIconMap[statusId] || 'fa-info-circle';
};

// Get incident type display
window.getIncidentTypeDisplay = function(typeId) {
    const typeMap = {
        1: { name: 'Fire', icon: 'fa-fire', bgClass: 'bg-red-100', iconClass: 'text-red-600' },
        2: { name: 'Medical', icon: 'fa-truck-medical', bgClass: 'bg-blue-100', iconClass: 'text-blue-600' },
        3: { name: 'Rescue', icon: 'fa-hand-holding-heart', bgClass: 'bg-purple-100', iconClass: 'text-purple-600' },
        4: { name: 'Hazmat', icon: 'fa-skull-crossbones', bgClass: 'bg-orange-100', iconClass: 'text-orange-600' }
    };
    return typeMap[typeId] || { name: 'Other', icon: 'fa-exclamation-triangle', bgClass: 'bg-yellow-100', iconClass: 'text-yellow-600' };
};

// Update statistics counters
window.updateStatistics = function() {
    const activeIncidents = window.incidentsData.filter(incident => {
        return incident.incident_status_id && incident.incident_status_id < 3;
    });
    
    const fireCount = activeIncidents.filter(i => i.incident_type_id == 1).length;
    const medicalCount = activeIncidents.filter(i => i.incident_type_id == 2).length;
    const otherCount = activeIncidents.filter(i => i.incident_type_id > 2).length;
    
    const fireEl = document.getElementById('activeFiresCount');
    const medicalEl = document.getElementById('activeMedicalCount');
    const otherEl = document.getElementById('activeOtherCount');
    const activeEl = document.getElementById('activeIncidentsCount');
    
    if (fireEl) fireEl.textContent = fireCount;
    if (medicalEl) medicalEl.textContent = medicalCount;
    if (otherEl) otherEl.textContent = otherCount;
    if (activeEl) activeEl.innerHTML = `${activeIncidents.length} active`;
};

// Render incident details in the right panel
window.renderIncidentDetails = function(incident) {
    const panel = document.getElementById('incidentDetailsPanel');
    if (!panel) {
        console.error('incidentDetailsPanel not found');
        return;
    }
    
    const typeInfo = window.getIncidentTypeDisplay(incident.incident_type_id);
    const statusName = window.getStatusName(incident.incident_status_id);
    const statusClass = window.getStatusClass(incident.incident_status_id);
    const statusIcon = window.getStatusIcon(incident.incident_status_id);
    
    const lat = incident.marker?.lat || incident.lat;
    const lng = incident.marker?.lng || incident.lng;
    
    panel.innerHTML = `
        <div class="space-y-5">
            <!-- Header with Icon -->
            <div class="flex items-center gap-4 p-4 ${typeInfo.bgClass} rounded-xl">
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-md">
                    <i class="fa-solid ${typeInfo.icon} ${typeInfo.iconClass} text-3xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-800">
                        ${typeInfo.name} Incident
                    </h3>
                    <p class="text-sm text-gray-600">ID: #${incident.incident_id}</p>
                </div>
            </div>
            
            <!-- Status Badge -->
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <span class="text-sm text-gray-600">Current Status</span>
                <span class="px-3 py-1 rounded-full text-sm font-medium ${statusClass}">
                    <i class="fa-solid ${statusIcon} mr-1"></i>
                    ${statusName}
                </span>
            </div>
            
            <!-- Details Grid -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-xs text-gray-500 mb-1">Reported At</p>
                    <p class="font-medium text-sm">${incident.created_at ? new Date(incident.created_at).toLocaleString() : 'Unknown'}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-xs text-gray-500 mb-1">Last Updated</p>
                    <p class="font-medium text-sm">${incident.updated_at ? new Date(incident.updated_at).toLocaleString() : 'Unknown'}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-xs text-gray-500 mb-1">Reported By</p>
                    <p class="font-medium text-sm">${incident.reported_by || 'Anonymous'}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-xs text-gray-500 mb-1">Assigned Station</p>
                    <p class="font-medium text-sm">${incident.station?.station_name || 'Not Assigned'}</p>
                </div>
            </div>
            
            <!-- Location Section -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-xs text-gray-500 mb-2 flex items-center gap-1">
                    <i class="fa-solid fa-location-dot text-red-500"></i> Location Details
                </p>
                <p class="font-medium text-sm mb-2">${incident.marker?.address || incident.location || 'No address available'}</p>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-white p-2 rounded">
                        <span class="text-gray-500">Latitude:</span>
                        <span class="font-mono ml-1">${lat ? parseFloat(lat).toFixed(6) : 'N/A'}</span>
                    </div>
                    <div class="bg-white p-2 rounded">
                        <span class="text-gray-500">Longitude:</span>
                        <span class="font-mono ml-1">${lng ? parseFloat(lng).toFixed(6) : 'N/A'}</span>
                    </div>
                </div>
            </div>
            
            <!-- Description -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-xs text-gray-500 mb-2 flex items-center gap-1">
                    <i class="fa-solid fa-align-left"></i> Description
                </p>
                <p class="text-sm text-gray-700 leading-relaxed">
                    ${incident.marker?.description || incident.description || 'No description provided'}
                </p>
            </div>
            
            ${incident.team ? `
            <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                <p class="text-xs text-blue-600 font-semibold mb-2 flex items-center gap-1">
                    <i class="fa-solid fa-users"></i> Assigned Team
                </p>
                <p class="font-medium text-sm">${incident.team.team_name || `Team ${incident.team.team_id}`}</p>
                <p class="text-xs text-gray-500 mt-1">Team ID: #${incident.team.team_id}</p>
            </div>
            ` : ''}
        </div>
    `;
};

// ============================================
// MAIN SELECT INCIDENT FUNCTION - GLOBALLY AVAILABLE
// ============================================
window.selectIncident = function(incidentId) {
    console.log('selectIncident called with ID:', incidentId);
    
    // Make sure incidentsData is available
    if (!window.incidentsData) {
        console.error('incidentsData not loaded yet');
        return;
    }
    
    const incident = window.incidentsData.find(i => i.incident_id == incidentId);
    if (!incident) {
        console.error('Incident not found:', incidentId);
        return;
    }
    
    console.log('Found incident:', incident);
    window.currentSelectedIncident = incident;
    
    // Highlight selected incident in list
    document.querySelectorAll('.incident-item').forEach(item => {
        item.classList.remove('border-orange-500', 'bg-orange-50');
        if (item.dataset.id == incidentId) {
            item.classList.add('border-orange-500', 'bg-orange-50');
        }
    });
    
    // Update details panel
    window.renderIncidentDetails(incident);
    
    // Enable action buttons
    const navigateBtn = document.getElementById('navigateToIncidentBtn');
    const shareBtn = document.getElementById('shareIncidentBtn');
    if (navigateBtn) navigateBtn.disabled = false;
    if (shareBtn) shareBtn.disabled = false;
};

// ============================================
// DOM CONTENT LOADED - INITIALIZE EVENT LISTENERS
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing incident panel');
    console.log('selectIncident function available:', typeof window.selectIncident);
    console.log('Total incidents loaded:', window.incidentsData.length);
    
    // Initialize statistics
    window.updateStatistics();
    
    // Navigation button handler
    const navigateBtn = document.getElementById('navigateToIncidentBtn');
    if (navigateBtn) {
        // Remove any existing listeners
        const newNavigateBtn = navigateBtn.cloneNode(true);
        navigateBtn.parentNode.replaceChild(newNavigateBtn, navigateBtn);
        
        newNavigateBtn.addEventListener('click', function() {
            if (window.currentSelectedIncident) {
                const lat = window.currentSelectedIncident.marker?.lat || window.currentSelectedIncident.lat;
                const lng = window.currentSelectedIncident.marker?.lng || window.currentSelectedIncident.lng;
                if (lat && lng && typeof window.calculateRouteToIncident === 'function') {
                    window.calculateRouteToIncident(window.currentSelectedIncident.incident_id);
                } else if (lat && lng) {
                    window.open(`https://maps.google.com/?q=${lat},${lng}`, '_blank');
                }
            }
        });
    }
    
    // Share button handler
    const shareBtn = document.getElementById('shareIncidentBtn');
    if (shareBtn) {
        // Remove any existing listeners
        const newShareBtn = shareBtn.cloneNode(true);
        shareBtn.parentNode.replaceChild(newShareBtn, shareBtn);
        
        newShareBtn.addEventListener('click', function() {
            if (window.currentSelectedIncident) {
                const typeName = window.getIncidentTypeDisplay(window.currentSelectedIncident.incident_type_id).name;
                const statusName = window.getStatusName(window.currentSelectedIncident.incident_status_id);
                const shareText = `${typeName} Incident - Status: ${statusName} - ID: #${window.currentSelectedIncident.incident_id}`;
                if (navigator.share) {
                    navigator.share({
                        title: `${typeName} Incident Alert`,
                        text: shareText,
                        url: window.location.href
                    });
                } else {
                    navigator.clipboard.writeText(shareText);
                    alert('Incident details copied to clipboard!');
                }
            }
        });
    }
    
    // Filter incidents
    document.querySelectorAll('.incident-filter-btn').forEach(btn => {
        // Remove any existing listeners
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        
        newBtn.addEventListener('click', function() {
            document.querySelectorAll('.incident-filter-btn').forEach(b => {
                b.classList.remove('active', 'bg-orange-600', 'text-white');
                b.classList.add('bg-gray-100', 'text-gray-600');
            });
            this.classList.add('active', 'bg-orange-600', 'text-white');
            this.classList.remove('bg-gray-100', 'text-gray-600');
            
            const filter = this.dataset.filter;
            const items = document.querySelectorAll('.incident-item');
            
            items.forEach(item => {
                const type = item.dataset.type;
                if (filter === 'all' || 
                    (filter === '1' && type === '1') ||
                    (filter === '2' && type === '2') ||
                    (filter === 'other' && !['1', '2'].includes(type))) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
    
    // Test click on first incident (optional - for debugging)
    const firstIncident = document.querySelector('.incident-item');
    if (firstIncident && firstIncident.dataset.id) {
        console.log('First incident ID:', firstIncident.dataset.id);
    }
});
</script>
<style>
/* Map marker styles */
.user-location-marker {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
    }
}

.incident-item {
    transition: all 0.2s ease;
}

.incident-item:hover {
    transform: translateX(4px);
    border-color: #f97316;
}

.incident-filter-btn {
    transition: all 0.2s ease;
    cursor: pointer;
}

.incident-filter-btn:hover:not(.active) {
    background-color: #e5e7eb !important;
}

/* Timeline styles */
.timeline-dot {
    animation: glow 2s infinite;
}

@keyframes glow {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.5);
    }
    50% {
        box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.2);
    }
}
</style>
