<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
     {{-- <script src="https://cdn.tailwindcss.com"></script> --}}

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
       <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet/dist/leaflet.css"
    />
     <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
     crossorigin=""></script>
          <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    <title>FireWatch 24/7 · Dashboard</title>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; }
         
.modal {
    display:none;
    position:fixed;
    top:0; left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,.5);
}

.modal-content{
    background:white;
    padding:20px;
    width:300px;
    margin:100px auto;
}
#map {
  position: relative;
  z-index: 0; /* ensures modal can appear above */
}

      .emergency-bg {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            position: relative;
            overflow: hidden;
        }
        .emergency-bg::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M30 5L5 30L30 55L55 30L30 5Z' fill='rgba(255,255,255,0.03)' stroke='none'/%3E%3C/svg%3E");
            background-size: 60px 60px;
            opacity: 0.1;
            pointer-events: none;
        }
        .fire-icon {
            animation: flicker 3s infinite;
        }
        @keyframes flicker {
            0%, 100% { opacity: 1; }
            33% { opacity: 0.8; transform: scale(1.02); }
            66% { opacity: 0.9; }
        }
        /* Sidebar transition */
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        /* Mobile menu open state */
        body.menu-open {
            overflow: hidden;
        }
  
    </style>
    @vite(['resources/css/app.css', 'resources/js/map.js'])

     <script>
        document.addEventListener('DOMContentLoaded', function(){
          
          var marker = @json($mapmarker)|| []; //mapmarker galing sa controller. php - php - js
          
               window.mapData = {
        marker: @json($mapmarker) || []
    };
        //   var marker = @json($mapmarker)|| [];
          marker.forEach(m => {
            // console.log("Marker Map:", marker);
            // console.log("id:", m.marker_id);
            // if(m.markerCategory_id == 1){
            //     //logic to change map icon
            // }
            // console.log("name:", m.title);
            // console.log("latitude:", m.lat);   
            // console.log("longitude:", m.lng);
          });

        });
          let multimediaData = [];
    let icons = [];
     document.addEventListener('DOMContentLoaded', function() {
        fetchIcons();
    });
       async function fetchIcons() {
        try {
            // Using your getIcons() method
            const response = await fetch('/admin/geticons'); // Adjust URL as needed
            const result = await response.json();
            
            if (result.success) {
                multimediaData = result.data;
                icons = result.data;
                
                // console.log('Data loaded:', multimediaData);
                // console.log('Total items:', result.count);
                
                // Now you can use the data!
                displayIcons(multimediaData);
            }
        } catch (error) {
            console.error('Error fetching icons:', error);
        }
    }
      function displayIcons(data) {
        // Example: Log each item
        data.forEach(item => {
            // console.log('Path:', item.path);
            // console.log('Type:', item.type);
            // console.log('ID:', item.multimedia_id);
            getPath(item.path, item.multimedia_id);
            
        });
        
        // Example: Add to window object for global access
        window.multimediaData = data;
    }
       
    </script>
     {{-- @vite('resources/css/styles.css')
     @vite('resources/js/map.js') --}}


</head>
<body class="bg-gray-50 min-h-screen flex flex-col lg:flex-row">

    <!-- SIDE NAVIGATION (mobile drawer & desktop fixed) -->
    <aside id="sidebar" class="fixed lg:sticky top-0 left-0 h-full w-72 bg-white shadow-xl lg:shadow-md z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
        <!-- inner container with emergency gradient header -->
        <div class="flex flex-col h-full">
            <!-- brand section with same emergency-bg style -->
            <div class="emergency-bg text-white p-5 relative">
                <div class="flex items-center gap-3 relative z-10">
                    <div class="w-10 h-10 bg-white/20 backdrop-blur rounded-lg flex items-center justify-center fire-icon">
                        <i class="fa-solid fa-fire-extinguisher text-white text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold">FireWatch 24/7</h2>
                        <p class="text-white/80 text-xs">command center</p>
                    </div>
                </div>
                <!-- close button (mobile only) -->
                <button id="closeSidebar" class="absolute top-5 right-4 lg:hidden text-white/80 hover:text-white">
                    <i class="fa-solid fa-xmark text-2xl"></i>
                </button>
                <!-- small status badge -->
                <div class="relative z-10 mt-4 flex items-center gap-2 text-xs">
                    <span class="flex items-center gap-1 bg-white/20 px-2 py-1 rounded-full"><i class="fa-solid fa-circle text-green-300 text-[8px]"></i> online</span>
                    <span class="bg-yellow-500/30 px-2 py-1 rounded-full">active 24/7</span>
                </div>
            </div>

            <!-- navigation links -->
            <nav class="flex-1 overflow-y-auto py-6 px-4 no-scrollbar">
                <ul class="space-y-2">
                    <li>
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 text-red-600 bg-red-50 rounded-xl font-medium">
                            <i class="fa-solid fa-gauge-high w-5"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.maps')}}" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition">
                            <i class="fa-solid fa-map-location-dot w-5"></i>
                            <span>Incident map</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition">
                            <i class="fa-solid fa-truck-fire w-5"></i>
                            <span>Active units</span>
                            <span class="ml-auto bg-red-100 text-red-800 text-xs font-semibold px-2 py-1 rounded-full">6</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition">
                            <i class="fa-solid fa-bell w-5"></i>
                            <span>Alerts</span>
                            <span class="ml-auto bg-yellow-100 text-yellow-800 text-xs font-semibold px-2 py-1 rounded-full">3</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition">
                            <i class="fa-solid fa-clock-rotate-left w-5"></i>
                            <span>History</span>
                        </a>
                    </li>
                    <li class="pt-4 mt-2 border-t border-gray-200">
                        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">resources</p>
                    </li>
                    <li>
                        <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition">
                            <i class="fa-solid fa-helmet-safety w-5"></i>
                            <span>Personnel</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition">
                            <i class="fa-solid fa-truck w-5"></i>
                            <span>Fleet</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition">
                            <i class="fa-solid fa-water w-5"></i>
                            <span>Hydrants</span>
                        </a>
                    </li>
                    <li class="pt-4 mt-2 border-t border-gray-200">
                        <a href="#" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-xl transition">
                            <i class="fa-solid fa-gear w-5"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- bottom user profile (emergency-bg compact) -->
             <div class="p-4 border-t border-gray-200">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-red-50 rounded-xl transition group">
                        <i class="fa-solid fa-arrow-right-from-bracket w-5 text-gray-400 group-hover:text-red-600"></i>
                        <span class="flex-1 text-left">Logout</span>
                    </button>
                </form>
            </div>
            {{-- <div class="emergency-bg m-4 p-3 rounded-xl text-white relative">
                <div class="flex items-center gap-3 relative z-10">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold truncate">Chief J. Hawkins</p>
                        <p class="text-xs text-white/70 truncate">admin · station 1</p>
                    </div>
                    <button class="text-white/70 hover:text-white">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </button>
                </div>
            </div> --}}
        </div>
    </aside>

    <!-- BACKDROP for mobile -->
    <div id="sidebarBackdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden transition-opacity duration-300"></div>

    <!-- MAIN CONTENT (right side) -->
    <main class="flex-1 min-w-0 bg-gray-50">

        <!-- TOP NAVIGATION (consistent with login page) -->
        <nav class="bg-white shadow-sm sticky top-0 z-30">
            <div class="px-4 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <!-- left: mobile menu button + logo (mobile) -->
                    <div class="flex items-center gap-3">
                        <button id="openSidebar" class="lg:hidden text-gray-700 focus:outline-none">
                            <i class="fa-solid fa-bars text-2xl"></i>
                        </button>
                        <!-- logo visible on mobile only (desktop already has sidebar brand) -->
                        <div class="flex items-center space-x-2 lg:hidden">
                            <div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center fire-icon">
                                <i class="fa-solid fa-fire-extinguisher text-white text-sm"></i>
                            </div>
                            <span class="text-lg font-bold text-gray-800">Fire<span class="text-red-600">Watch</span></span>
                        </div>
                    </div>

                    <!-- desktop menu (center) hidden on mobile -->
                    <div class="hidden lg:flex items-center space-x-8">
                        <a href="#" class="text-red-600 font-medium">Dashboard</a>
                        <a href="#" class="text-gray-700 hover:text-red-600 font-medium">Emergency</a>
                        <a href="#" class="text-gray-700 hover:text-red-600 font-medium">Services</a>
                        <a href="#" class="text-gray-700 hover:text-red-600 font-medium">Resources</a>
                        <a href="#" class="text-gray-700 hover:text-red-600 font-medium">Contact</a>
                    </div>

                    <!-- right side -->
                    <div class="flex items-center space-x-4">
                        <span class="hidden sm:inline text-sm text-gray-600">Station 7</span>
                        <button class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition flex items-center gap-2">
                            <i class="fa-solid fa-phone-alt"></i>
                            <span class="hidden sm:inline">Emergency</span>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- DASHBOARD CONTENT (cards, stats, map preview) -->
        <div class="p-4 lg:p-8 max-w-7xl mx-auto">
            <!-- page title + date -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">Operations dashboard</h1>
                <p class="text-sm text-gray-500 mt-1 sm:mt-0"><i class="fa-regular fa-calendar mr-1"></i> 21 Feb 2026 · 14:32 hrs</p>
            </div>

            <!-- emergency banner (like login page) -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 rounded-r-lg flex items-start gap-3">
                <i class="fa-solid fa-triangle-exclamation text-yellow-400 mt-1"></i>
                <div class="text-sm text-yellow-700">
                    <span class="font-bold">Active alert:</span> Red flag warning in sector 4. Crews on standby. For new emergencies call <span class="font-bold">911</span> immediately.
                </div>
            </div>

            
            <!-- KPI cards (emergency style) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-xl shadow p-5 border-l-4 border-red-600">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm">active incidents</p>
                            <p class="text-3xl font-bold text-gray-800">5</p>
                        </div>
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center text-red-600">
                            <i class="fa-solid fa-fire"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2"><i class="fa-solid fa-arrow-up text-green-600 mr-1"></i> +2 since last hour</p>
                </div>
                <div class="bg-white rounded-xl shadow p-5 border-l-4 border-orange-400">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm">units deployed</p>
                            <p class="text-3xl font-bold text-gray-800">12</p>
                        </div>
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center text-orange-600">
                            <i class="fa-solid fa-truck-fire"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">8 engines · 4 trucks</p>
                </div>
                <div class="bg-white rounded-xl shadow p-5 border-l-4 border-blue-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm">personnel on site</p>
                            <p class="text-3xl font-bold text-gray-800">47</p>
                        </div>
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                            <i class="fa-solid fa-helmet-safety"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">including 6 chiefs</p>
                </div>
                <div class="bg-white rounded-xl shadow p-5 border-l-4 border-emerald-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 text-sm">avg response</p>
                            <p class="text-3xl font-bold text-gray-800">3.2min</p>
                        </div>
                        <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-600">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">below national average</p>
                </div>
            </div>

            <!-- main grid: incident map + recent alerts (2col) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- map preview card (spans 2 cols) -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-bold text-gray-800"><i class="fa-solid fa-map-location-dot text-red-600 mr-2"></i> Live incident map · sector 4</h2>
                        <span class="text-xs bg-red-50 text-red-700 px-2 py-1 rounded-full">updated 2 min ago</span>
                    </div>
                    <div class="bg-gray-200 rounded-lg h-64 flex items-center justify-center text-gray-500 relative emergency-bg/10 " id="map"  style="background: #f1f5f9;">
                        <!-- dummy map with pins -->
{{--                           
                        <div class="relative w-full h-full"  >
                            <div class="absolute top-4 left-6 w-4 h-4 bg-red-600 rounded-full animate-ping opacity-75"></div>
                            <div class="absolute top-4 left-6 w-4 h-4 bg-red-600 rounded-full border-2 border-white"></div>
                            <span class="absolute top-7 left-12 text-xs font-bold bg-white px-2 py-0.5 rounded shadow">wildfire · 2 acres</span>
                            
                            <div class="absolute bottom-12 right-20 w-3 h-3 bg-orange-500 rounded-full animate-pulse"></div>
                            <span class="absolute bottom-16 right-28 text-xs bg-white px-2 py-0.5 rounded shadow">engine 41</span>
                            
                            <div class="absolute top-20 right-32 w-3 h-3 bg-yellow-500 rounded-full"></div>
                            <span class="absolute top-16 right-40 text-xs bg-white px-2 py-0.5 rounded shadow">hydrant low</span>
                            <div class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm">🔥 interactive map (simulated)</div>
                        </div> --}}
                    </div>
                    <div class="flex gap-3 mt-4 text-sm">
                        <button class="bg-red-600 text-white px-3 py-1.5 rounded-lg text-xs hover:bg-red-700" id="iconBtn" onclick="openIconModal()"><i class="fa-regular fa-add mr-1"></i> Add Icons</button>
                        <button class="border border-gray-300 px-3 py-1.5 rounded-lg text-xs hover:bg-gray-50" id="addCategory" onclick="openAddCategory()"><i class="fa-regular fa-layer-group mr-1"></i> Add Category</button>
                    </div>
                </div>

                <!-- recent alerts / quick actions -->
                <div class="bg-white rounded-xl shadow p-5">
                    <h2 class="font-bold text-gray-800 mb-4"><i class="fa-solid fa-bell text-red-600 mr-2"></i> recent alerts</h2>
                    <ul class="space-y-3">
                        <li class="flex gap-2 items-start">
                            <span class="w-2 h-2 bg-red-500 rounded-full mt-1.5"></span>
                            <div>
                                <p class="text-sm font-medium">structure fire · 223 oak st</p>
                                <p class="text-xs text-gray-500">3 min ago · 2 engines</p>
                            </div>
                        </li>
                        <li class="flex gap-2 items-start">
                            <span class="w-2 h-2 bg-yellow-500 rounded-full mt-1.5"></span>
                            <div>
                                <p class="text-sm font-medium">brush fire · hillside</p>
                                <p class="text-xs text-gray-500">22 min ago · 1 helicopter</p>
                            </div>
                        </li>
                        <li class="flex gap-2 items-start">
                            <span class="w-2 h-2 bg-yellow-500 rounded-full mt-1.5"></span>
                            <div>
                                <p class="text-sm font-medium">hazmat alert · industrial zone</p>
                                <p class="text-xs text-gray-500">1 hr ago · containment</p>
                            </div>
                        </li>
                        <li class="flex gap-2 items-start">
                            <span class="w-2 h-2 bg-green-500 rounded-full mt-1.5"></span>
                            <div>
                                <p class="text-sm font-medium">medical assist · station 2</p>
                                <p class="text-xs text-gray-500">2 hrs ago · cleared</p>
                            </div>
                        </li>
                    </ul>
                    <hr class="my-3">
                    <div class="grid grid-cols-2 gap-2 mt-2">
                        <button class="bg-gray-100 hover:bg-gray-200 p-2 rounded-lg text-xs"><i class="fa-regular fa-bell-slash mr-1"></i> silence</button>
                        <button class="bg-gray-100 hover:bg-gray-200 p-2 rounded-lg text-xs"><i class="fa-regular fa-flag mr-1"></i> all clear</button>
                    </div>
                </div>
            </div>

            <!-- bottom cards: station status & resources (like login stats style) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- station readiness -->
                <div class="bg-white rounded-xl shadow p-5">
                    <h3 class="font-semibold text-gray-700 flex items-center gap-2"><i class="fa-solid fa-helmet-safety text-red-600"></i> stations readiness</h3>
                    <div class="grid grid-cols-2 gap-3 mt-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Station 1</p>
                            <p class="text-xl font-bold">4/4 <span class="text-sm font-normal text-gray-400">units</span></p>
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">ready</span>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Station 3</p>
                            <p class="text-xl font-bold">3/4 <span class="text-sm font-normal text-gray-400">units</span></p>
                            <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">1 deployed</span>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Station 7</p>
                            <p class="text-xl font-bold">5/5</p>
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">ready</span>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500">Air base</p>
                            <p class="text-xl font-bold">2/2 <span class="text-sm font-normal">helos</span></p>
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">available</span>
                        </div>
                    </div>
                </div>
 
                <!-- quick stats (like login emergency stats) -->
                <div class="emergency-bg text-white rounded-xl shadow p-5 relative">
                    <div class="relative z-10">
                        <h3 class="font-semibold mb-3">today's operational summary</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-white/10 backdrop-blur rounded-lg p-3">
                                <div class="text-2xl font-bold">11</div>
                                <div class="text-xs text-white/80">calls received</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur rounded-lg p-3">
                                <div class="text-2xl font-bold">9</div>
                                <div class="text-xs text-white/80">incidents closed</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur rounded-lg p-3">
                                <div class="text-2xl font-bold">98%</div>
                                <div class="text-xs text-white/80">comms uptime</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur rounded-lg p-3">
                                <div class="text-2xl font-bold">24</div>
                                <div class="text-xs text-white/80">reserve personnel</div>
                            </div>
                        </div>
                        <p class="text-xs text-white/70 mt-3 flex items-center gap-1"><i class="fa-solid fa-circle-check"></i> all systems nominal</p>
                    </div>
                </div>
            </div>
         
            <!-- footer inside main (similar to login) -->
            <div class="text-center text-xs text-gray-400 mt-8 border-t pt-4">
                <i class="fa-solid fa-shield-halved mr-1"></i> PyroSense · secured emergency dashboard · live data
            </div>
        </div>
                          <!-- Marker Modal -->
<div id="markerModal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center z-50  justify-center hidden">
    <div class="modal-content bg-white rounded-lg shadow-lg p-6 w-96">
        <h3  class="text-xl font-bold mb-4">Add Marker</h3>

        <input type="text" id="markerTitle" class="border p-2 w-full mb-2" placeholder="Title">
        <textarea id="markerDescription"  class="border p-2 w-full mb-4" placeholder="Description"></textarea>
        <select name="" id="markerIcon">
            {{-- @foreach ($icons as $icon)
                <option value="{{ $icon->id }}">{{ $icon->icon_type }}</option>
            @endforeach --}}
           
        </select>
        <button  class="bg-blue-500 text-white px-4 py-2 rounded" id="saveMarkerBtn">Save</button>
        <button  class="bg-gray-300 px-4 py-2 rounded"  onclick="closeModal()">Cancel</button>
    </div>
</div>
{{-- icon modal --}}
<div id="iconModal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center z-50  justify-center hidden">
    <div class="modal-content bg-white rounded-lg shadow-lg p-6 w-96">
        <h3  class="text-xl font-bold mb-4">Add Icon</h3>
        <form action="" enctype="multipart/form-data">
        <input type="file" id="iconImage" class="border p-2 w-full mb-2" placeholder="Upload Icon" accept="image/*">
        <textarea id="iconType"  class="border p-2 w-full mb-4" placeholder="Icon Type"></textarea>
      
        <button  class="bg-blue-500 text-white px-4 py-2  rounded" id="saveIconBtn">Save</button>
            </form>
        <button  class="bg-gray-300 px-4 py-2 rounded"  onclick="closeIconModal()">Cancel</button>
    
    </div>
</div>
{{-- category modal --}}
<div id="categoryModal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center z-50  justify-center hidden">
    <div class="modal-content bg-white rounded-lg shadow-lg p-6 w-96">
        <h3  class="text-xl font-bold mb-4">Add Category (e.g hydrant, station)</h3>
      
        <input type="text"  class="border p-2 w-full mb-4" name="category" id="marker_category">
      
        <button  class="bg-blue-500 text-white px-4 py-2 rounded" type="submit" id="saveCategory">Save</button>
        <button  class="bg-gray-300 px-4 py-2 rounded"  onclick="closeAddCategory()">Cancel</button>
      
    </div>
</div>
    </main>

    <!-- mobile sidebar toggle script -->
{{-- <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script> --}}
{{-- At the bottom of your dashboard.blade.php, before closing body tag --}}

   <script>
    function openModal() {
    document.getElementById('markerModal').style.display = 'block';
}

function openIconModal() {
    document.getElementById('iconModal').style.display = 'block';
}
function closeIconModal() {
    document.getElementById('iconModal').style.display = 'none';
}
function openAddCategory() {
    document.getElementById('categoryModal').style.display = 'block';
}
function closeAddCategory() {
    document.getElementById('categoryModal').style.display = 'none';
}

    function closeModal() {
    document.getElementById('markerModal').style.display = 'none';
}
   </script>
</body>
</html>