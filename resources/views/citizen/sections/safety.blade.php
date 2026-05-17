<div id="section-safety" class="content-section">
    <h1 class="text-2xl lg:text-3xl font-bold text-gray-800 mb-6">Safety Guide</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Sidebar Guide Menu -->
        <div class="bg-white rounded-xl shadow p-5">
            <h2 class="font-bold text-gray-800 mb-4">Emergency Guides</h2>
            <ul class="space-y-2" id="guide-menu">
                <li>
                    <a href="#" class="guide-link block p-2 bg-orange-50 text-orange-700 rounded-lg font-medium" data-guide="fire">
                        <i class="fa-solid fa-fire mr-2"></i>🔥 Fire Safety
                    </a>
                </li>
                <li>
                    <a href="#" class="guide-link block p-2 hover:bg-gray-50 rounded-lg" data-guide="flood">
                        <i class="fa-solid fa-water mr-2"></i>🌊 Flood Preparedness
                    </a>
                </li>
                <li>
                    <a href="#" class="guide-link block p-2 hover:bg-gray-50 rounded-lg" data-guide="earthquake">
                        <i class="fa-solid fa-house-crack mr-2"></i>🌪️ Earthquake
                    </a>
                </li>
                <li>
                    <a href="#" class="guide-link block p-2 hover:bg-gray-50 rounded-lg" data-guide="firstaid">
                        <i class="fa-solid fa-kit-medical mr-2"></i>🏥 First Aid
                    </a>
                </li>
                <li>
                    <a href="#" class="guide-link block p-2 hover:bg-gray-50 rounded-lg" data-guide="gobag">
                        <i class="fa-solid fa-bag-shopping mr-2"></i>📦 Go-Bag Checklist
                    </a>
                </li>
            </ul>
            
            <!-- Resource Links -->
            <div class="mt-6 pt-4 border-t border-gray-200">
                <h3 class="font-semibold text-gray-700 mb-2 text-sm">Quick Resources</h3>
                <ul class="space-y-2 text-sm">
                    <li>
                        <a href="#" class="resource-link text-gray-600 hover:text-orange-600 flex items-center gap-2" data-resource="emergency-contacts">
                            <i class="fa-solid fa-phone"></i> Emergency Contacts
                        </a>
                    </li>
                    <li>
                        <a href="#" class="resource-link text-gray-600 hover:text-orange-600 flex items-center gap-2" data-resource="evacuation-centers">
                            <i class="fa-solid fa-location-dot"></i> Evacuation Centers
                        </a>
                    </li>
                    <li>
                        <a href="#" class="resource-link text-gray-600 hover:text-orange-600 flex items-center gap-2" data-resource="safety-videos">
                            <i class="fa-solid fa-video"></i> Safety Videos
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content Area -->
        <div class="md:col-span-2 space-y-6" id="main-content-area">
            <!-- Fire Safety Card (Default) -->
            <div id="fire-guide" class="guide-content bg-white rounded-xl shadow overflow-hidden">
                <img src="https://images.unsplash.com/photo-1582131504859-5d3e9232b3a9?w=400" alt="Fire Safety" class="w-full h-48 object-cover">
                <div class="p-5">
                    <h2 class="text-xl font-bold text-gray-800 mb-3">🔥 Fire Safety at Home</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-check text-green-500 mt-1"></i>
                            <p class="text-sm text-gray-600">Install smoke alarms on every level</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-check text-green-500 mt-1"></i>
                            <p class="text-sm text-gray-600">Test alarms monthly</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-check text-green-500 mt-1"></i>
                            <p class="text-sm text-gray-600">Create escape plan with 2 ways out</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-check text-green-500 mt-1"></i>
                            <p class="text-sm text-gray-600">Keep fire extinguisher in kitchen</p>
                        </div>
                    </div>
                    
                    <!-- Fire Safety Statistics -->
                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <div class="bg-red-50 p-2 rounded-lg text-center">
                            <span class="text-red-600 font-bold text-lg">356K</span>
                            <p class="text-xs text-gray-600">Home fires/year</p>
                        </div>
                        <div class="bg-red-50 p-2 rounded-lg text-center">
                            <span class="text-red-600 font-bold text-lg">2,500+</span>
                            <p class="text-xs text-gray-600">Deaths/year</p>
                        </div>
                        <div class="bg-red-50 p-2 rounded-lg text-center">
                            <span class="text-red-600 font-bold text-lg">$7B</span>
                            <p class="text-xs text-gray-600">Property damage</p>
                        </div>
                    </div>
                    
                    <button class="download-guide-btn mt-2 text-orange-600 font-medium text-sm hover:text-orange-800 transition-colors" data-guide="fire">
                        <i class="fa-solid fa-download mr-1"></i> Download Full Guide →
                    </button>
                </div>
            </div>

            <!-- Flood Guide (Hidden by default) -->
            <div id="flood-guide" class="guide-content hidden bg-white rounded-xl shadow overflow-hidden">
                <img src="https://images.unsplash.com/photo-1547683905-f686c993aae5?w=400" alt="Flood Preparedness" class="w-full h-48 object-cover">
                <div class="p-5">
                    <h2 class="text-xl font-bold text-gray-800 mb-3">🌊 Flood Preparedness</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-check text-green-500 mt-1"></i>
                            <p class="text-sm text-gray-600">Know your flood risk zone</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-check text-green-500 mt-1"></i>
                            <p class="text-sm text-gray-600">Prepare emergency kit with documents</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-check text-green-500 mt-1"></i>
                            <p class="text-sm text-gray-600">Move to higher ground immediately</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-check text-green-500 mt-1"></i>
                            <p class="text-sm text-gray-600">Never walk/drive through floodwater</p>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 p-3 rounded-lg mb-4">
                        <h3 class="font-semibold text-blue-800 mb-2">📋 Flood Safety Checklist:</h3>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li>• Sandbags for doorways</li>
                            <li>• Battery-powered pump</li>
                            <li>• Waterproof document container</li>
                            <li>• Emergency radio</li>
                        </ul>
                    </div>
                    
                    <button class="download-guide-btn mt-2 text-orange-600 font-medium text-sm hover:text-orange-800 transition-colors" data-guide="flood">
                        <i class="fa-solid fa-download mr-1"></i> Download Full Guide →
                    </button>
                </div>
            </div>

            <!-- Earthquake Guide (Hidden by default) -->
            <div id="earthquake-guide" class="guide-content hidden bg-white rounded-xl shadow overflow-hidden">
                <img src="https://images.unsplash.com/photo-1583416586865-3f8b3d4b8a3d?w=400" alt="Earthquake Safety" class="w-full h-48 object-cover">
                <div class="p-5">
                    <h2 class="text-xl font-bold text-gray-800 mb-3">🌪️ Earthquake Safety</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-check text-green-500 mt-1"></i>
                            <p class="text-sm text-gray-600">Drop, Cover, and Hold On</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-check text-green-500 mt-1"></i>
                            <p class="text-sm text-gray-600">Secure heavy furniture to walls</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-check text-green-500 mt-1"></i>
                            <p class="text-sm text-gray-600">Prepare 3-day emergency kit</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-check text-green-500 mt-1"></i>
                            <p class="text-sm text-gray-600">Know gas/water shut-off valves</p>
                        </div>
                    </div>
                    
                    <div class="bg-yellow-50 p-3 rounded-lg mb-4">
                        <h3 class="font-semibold text-yellow-800 mb-2">⚠️ During an Earthquake:</h3>
                        <p class="text-sm text-yellow-700">Stay indoors until shaking stops. Stay away from windows and exterior walls. If outdoors, move to an open area away from buildings.</p>
                    </div>
                    
                    <button class="download-guide-btn mt-2 text-orange-600 font-medium text-sm hover:text-orange-800 transition-colors" data-guide="earthquake">
                        <i class="fa-solid fa-download mr-1"></i> Download Full Guide →
                    </button>
                </div>
            </div>

            <!-- First Aid Guide (Hidden by default) -->
            <div id="firstaid-guide" class="guide-content hidden bg-white rounded-xl shadow overflow-hidden">
                <img src="https://images.unsplash.com/photo-1586773860418-d37222d8fce3?w=400" alt="First Aid" class="w-full h-48 object-cover">
                <div class="p-5">
                    <h2 class="text-xl font-bold text-gray-800 mb-3">🏥 First Aid Essentials</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-check text-green-500 mt-1"></i>
                            <p class="text-sm text-gray-600">CPR: 30 compressions, 2 breaths</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-check text-green-500 mt-1"></i>
                            <p class="text-sm text-gray-600">Control bleeding with direct pressure</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-check text-green-500 mt-1"></i>
                            <p class="text-sm text-gray-600">Treat burns with cool water</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-check text-green-500 mt-1"></i>
                            <p class="text-sm text-gray-600">Don't move injured person</p>
                        </div>
                    </div>
                    
                    <div class="bg-green-50 p-3 rounded-lg mb-4">
                        <h3 class="font-semibold text-green-800 mb-2">🩹 First Aid Kit Must-Haves:</h3>
                        <ul class="text-sm text-green-700 space-y-1">
                            <li>• Bandages, gauze, medical tape</li>
                            <li>• Antiseptic wipes and antibiotic cream</li>
                            <li>• Pain relievers and antihistamines</li>
                            <li>• Emergency blanket and gloves</li>
                        </ul>
                    </div>
                    
                    <button class="download-guide-btn mt-2 text-orange-600 font-medium text-sm hover:text-orange-800 transition-colors" data-guide="firstaid">
                        <i class="fa-solid fa-download mr-1"></i> Download Full Guide →
                    </button>
                </div>
            </div>

            <!-- Go-Bag Guide (Hidden by default) -->
            <div id="gobag-guide" class="guide-content hidden bg-white rounded-xl shadow overflow-hidden">
                <img src="https://images.unsplash.com/photo-1583416586865-3f8b3d4b8a3d?w=400" alt="Go-Bag Checklist" class="w-full h-48 object-cover">
                <div class="p-5">
                    <h2 class="text-xl font-bold text-gray-800 mb-3">📦 Go-Bag Emergency Checklist</h2>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-2">Essentials:</h3>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>✓ Water (3-day supply)</li>
                                <li>✓ Non-perishable food</li>
                                <li>✓ First aid kit</li>
                                <li>✓ Flashlight & batteries</li>
                                <li>✓ Important documents</li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-2">Extras:</h3>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>✓ Portable charger</li>
                                <li>✓ Extra clothes/blankets</li>
                                <li>✓ Whistle & multi-tool</li>
                                <li>✓ Cash & IDs</li>
                                <li>✓ Prescription meds</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="bg-purple-50 p-3 rounded-lg mb-4">
                        <p class="text-sm text-purple-700"><i class="fa-solid fa-lightbulb mr-2"></i> Pro tip: Pack your go-bag in an easily accessible place and check every 6 months.</p>
                    </div>
                    
                    <button class="download-guide-btn mt-2 text-orange-600 font-medium text-sm hover:text-orange-800 transition-colors" data-guide="gobag">
                        <i class="fa-solid fa-download mr-1"></i> Download Full Guide →
                    </button>
                </div>
            </div>
            
            <!-- Quick Tips -->
            <div class="bg-gradient-to-r from-yellow-500 to-orange-500 text-white rounded-xl shadow p-5">
                <h3 class="font-bold mb-3">⚡ Quick Safety Tips</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="text-center">
                        <i class="fa-solid fa-phone text-2xl mb-2"></i>
                        <p class="text-xs">Know emergency numbers</p>
                    </div>
                    <div class="text-center">
                        <i class="fa-solid fa-kit-medical text-2xl mb-2"></i>
                        <p class="text-xs">Keep first aid kit handy</p>
                    </div>
                    <div class="text-center">
                        <i class="fa-solid fa-water text-2xl mb-2"></i>
                        <p class="text-xs">Store drinking water</p>
                    </div>
                    <div class="text-center">
                        <i class="fa-solid fa-flashlight text-2xl mb-2"></i>
                        <p class="text-xs">Flashlight & batteries</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== COMPLEMENTARY SECTION ===== -->
    <div class="mt-8 space-y-6">
        <!-- Section Title -->
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-shield text-orange-600"></i>
                Safety Resources & Tools
            </h2>
            <div class="flex gap-2">
                <button class="view-toggle active px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium" data-view="grid">
                    <i class="fa-solid fa-grid-2 mr-1"></i> Grid View
                </button>
                <button class="view-toggle px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium" data-view="list">
                    <i class="fa-solid fa-list mr-1"></i> List View
                </button>
            </div>
        </div>

        <!-- Interactive Safety Checklist -->
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-clipboard-list text-orange-600"></i>
                    Home Safety Checklist
                </h3>
                <span class="text-sm text-gray-500" id="checklist-progress">0/12 completed</span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="safety-checklist">
                <div class="checklist-item flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                    <input type="checkbox" class="safety-checkbox mt-1 w-4 h-4 text-orange-600 rounded">
                    <div>
                        <p class="font-medium text-gray-700">Smoke Alarms</p>
                        <p class="text-xs text-gray-500">Installed on every level and tested monthly</p>
                    </div>
                </div>
                <div class="checklist-item flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                    <input type="checkbox" class="safety-checkbox mt-1 w-4 h-4 text-orange-600 rounded">
                    <div>
                        <p class="font-medium text-gray-700">Fire Extinguisher</p>
                        <p class="text-xs text-gray-500">In kitchen, inspected and not expired</p>
                    </div>
                </div>
                <div class="checklist-item flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                    <input type="checkbox" class="safety-checkbox mt-1 w-4 h-4 text-orange-600 rounded">
                    <div>
                        <p class="font-medium text-gray-700">Escape Plan</p>
                        <p class="text-xs text-gray-500">Two ways out of every room, family knows plan</p>
                    </div>
                </div>
                <div class="checklist-item flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                    <input type="checkbox" class="safety-checkbox mt-1 w-4 h-4 text-orange-600 rounded">
                    <div>
                        <p class="font-medium text-gray-700">Meeting Spot</p>
                        <p class="text-xs text-gray-500">Designated safe meeting place outside</p>
                    </div>
                </div>
                <div class="checklist-item flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                    <input type="checkbox" class="safety-checkbox mt-1 w-4 h-4 text-orange-600 rounded">
                    <div>
                        <p class="font-medium text-gray-700">First Aid Kit</p>
                        <p class="text-xs text-gray-500">Well-stocked and easily accessible</p>
                    </div>
                </div>
                <div class="checklist-item flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                    <input type="checkbox" class="safety-checkbox mt-1 w-4 h-4 text-orange-600 rounded">
                    <div>
                        <p class="font-medium text-gray-700">Emergency Contacts</p>
                        <p class="text-xs text-gray-500">Posted near phone or saved in phones</p>
                    </div>
                </div>
                <div class="checklist-item flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                    <input type="checkbox" class="safety-checkbox mt-1 w-4 h-4 text-orange-600 rounded">
                    <div>
                        <p class="font-medium text-gray-700">Carbon Monoxide Detector</p>
                        <p class="text-xs text-gray-500">Installed near sleeping areas</p>
                    </div>
                </div>
                <div class="checklist-item flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                    <input type="checkbox" class="safety-checkbox mt-1 w-4 h-4 text-orange-600 rounded">
                    <div>
                        <p class="font-medium text-gray-700">Utilities Knowledge</p>
                        <p class="text-xs text-gray-500">Know how to shut off gas, water, electricity</p>
                    </div>
                </div>
                <div class="checklist-item flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                    <input type="checkbox" class="safety-checkbox mt-1 w-4 h-4 text-orange-600 rounded">
                    <div>
                        <p class="font-medium text-gray-700">Emergency Food/Water</p>
                        <p class="text-xs text-gray-500">3-day supply for all family members</p>
                    </div>
                </div>
                <div class="checklist-item flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                    <input type="checkbox" class="safety-checkbox mt-1 w-4 h-4 text-orange-600 rounded">
                    <div>
                        <p class="font-medium text-gray-700">Flashlights</p>
                        <p class="text-xs text-gray-500">Working flashlights with extra batteries</p>
                    </div>
                </div>
                <div class="checklist-item flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                    <input type="checkbox" class="safety-checkbox mt-1 w-4 h-4 text-orange-600 rounded">
                    <div>
                        <p class="font-medium text-gray-700">Important Documents</p>
                        <p class="text-xs text-gray-500">Copies in waterproof/fireproof safe</p>
                    </div>
                </div>
                <div class="checklist-item flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                    <input type="checkbox" class="safety-checkbox mt-1 w-4 h-4 text-orange-600 rounded">
                    <div>
                        <p class="font-medium text-gray-700">Pet/Child Needs</p>
                        <p class="text-xs text-gray-500">Plan for pets, children, or elderly</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 flex justify-between items-center">
                <button id="reset-checklist" class="text-sm text-gray-500 hover:text-gray-700">
                    <i class="fa-solid fa-rotate-left mr-1"></i> Reset Checklist
                </button>
                <button id="save-checklist" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm hover:bg-orange-700">
                    <i class="fa-solid fa-save mr-1"></i> Save Progress
                </button>
            </div>
        </div>

        <!-- Safety Training Videos & Resources Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="resources-grid">
            <!-- CPR Training Card -->
            <div class="bg-white rounded-xl shadow overflow-hidden hover:shadow-lg transition-shadow">
                <div class="h-32 bg-red-100 flex items-center justify-center">
                    <i class="fa-solid fa-heart-pulse text-5xl text-red-600"></i>
                </div>
                <div class="p-4">
                    <h4 class="font-semibold text-gray-800 mb-2">CPR Training</h4>
                    <p class="text-xs text-gray-600 mb-3">Learn hands-only CPR in 5 minutes</p>
                    <div class="flex justify-between items-center">
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Free</span>
                        <button class="start-training text-orange-600 text-sm hover:text-orange-800" data-training="cpr">
                            Start <i class="fa-solid fa-arrow-right ml-1"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Fire Extinguisher Card -->
            <div class="bg-white rounded-xl shadow overflow-hidden hover:shadow-lg transition-shadow">
                <div class="h-32 bg-orange-100 flex items-center justify-center">
                    <i class="fa-solid fa-fire-extinguisher text-5xl text-orange-600"></i>
                </div>
                <div class="p-4">
                    <h4 class="font-semibold text-gray-800 mb-2">Fire Extinguisher</h4>
                    <p class="text-xs text-gray-600 mb-3">PASS method: Pull, Aim, Squeeze, Sweep</p>
                    <div class="flex justify-between items-center">
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">Interactive</span>
                        <button class="start-training text-orange-600 text-sm hover:text-orange-800" data-training="fire">
                            Practice <i class="fa-solid fa-arrow-right ml-1"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Emergency Kit Builder Card -->
            <div class="bg-white rounded-xl shadow overflow-hidden hover:shadow-lg transition-shadow">
                <div class="h-32 bg-blue-100 flex items-center justify-center">
                    <i class="fa-solid fa-kit-medical text-5xl text-blue-600"></i>
                </div>
                <div class="p-4">
                    <h4 class="font-semibold text-gray-800 mb-2">Emergency Kit Builder</h4>
                    <p class="text-xs text-gray-600 mb-3">Customize your go-bag checklist</p>
                    <div class="flex justify-between items-center">
                        <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-full">Tool</span>
                        <button id="open-kit-builder" class="text-orange-600 text-sm hover:text-orange-800">
                            Build <i class="fa-solid fa-arrow-right ml-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Safety Alerts -->
        <div class="bg-white rounded-xl shadow p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-bell text-orange-600"></i>
                    Recent Safety Alerts
                </h3>
                <a href="#" class="text-sm text-orange-600 hover:underline">View All</a>
            </div>
            
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-triangle-exclamation text-yellow-600"></i>
                        <div>
                            <p class="font-medium text-gray-800">High Fire Risk Warning</p>
                            <p class="text-xs text-gray-600">Due to dry conditions - 2 hours ago</p>
                        </div>
                    </div>
                    <span class="text-xs bg-yellow-200 text-yellow-800 px-2 py-1 rounded-full">Active</span>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-cloud-rain text-blue-600"></i>
                        <div>
                            <p class="font-medium text-gray-800">Flood Advisory</p>
                            <p class="text-xs text-gray-600">Heavy rainfall expected - 5 hours ago</p>
                        </div>
                    </div>
                    <span class="text-xs bg-blue-200 text-blue-800 px-2 py-1 rounded-full">New</span>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-circle-info text-gray-600"></i>
                        <div>
                            <p class="font-medium text-gray-800">Monthly Siren Test</p>
                            <p class="text-xs text-gray-600">Scheduled for tomorrow at 10 AM</p>
                        </div>
                    </div>
                    <span class="text-xs bg-gray-200 text-gray-800 px-2 py-1 rounded-full">Upcoming</span>
                </div>
            </div>
        </div>

        <!-- Community Safety Map -->
        <div class="bg-white rounded-xl shadow p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-map-location-dot text-orange-600"></i>
                    Community Safety Map
                </h3>
                <button id="expand-map" class="text-sm text-orange-600 hover:underline">
                    Expand <i class="fa-solid fa-expand ml-1"></i>
                </button>
            </div>
            
            <div class="relative bg-gray-100 h-48 rounded-lg overflow-hidden">
                <!-- Map Placeholder -->
                <div class="absolute inset-0 bg-gradient-to-br from-gray-200 to-gray-300"></div>
                
                <!-- Map Markers -->
                <div class="absolute top-1/4 left-1/4 w-3 h-3 bg-red-600 rounded-full animate-ping"></div>
                <div class="absolute top-1/4 left-1/4 w-3 h-3 bg-red-600 rounded-full border-2 border-white"></div>
                
                <div class="absolute top-1/2 left-1/2 w-3 h-3 bg-green-600 rounded-full animate-ping"></div>
                <div class="absolute top-1/2 left-1/2 w-3 h-3 bg-green-600 rounded-full border-2 border-white"></div>
                
                <div class="absolute bottom-1/3 right-1/4 w-3 h-3 bg-blue-600 rounded-full animate-ping"></div>
                <div class="absolute bottom-1/3 right-1/4 w-3 h-3 bg-blue-600 rounded-full border-2 border-white"></div>
                
                <!-- Map Legend -->
                <div class="absolute bottom-2 left-2 bg-white bg-opacity-90 p-2 rounded-lg text-xs">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-2 h-2 bg-red-600 rounded-full"></span>
                        <span>Fire Stations</span>
                    </div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-2 h-2 bg-green-600 rounded-full"></span>
                        <span>Hospitals</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 bg-blue-600 rounded-full"></span>
                        <span>Evacuation Centers</span>
                    </div>
                </div>
                
                <!-- Map Controls -->
                <div class="absolute top-2 right-2 flex flex-col gap-1">
                    <button class="w-8 h-8 bg-white rounded-lg shadow flex items-center justify-center hover:bg-gray-50">
                        <i class="fa-solid fa-plus text-sm"></i>
                    </button>
                    <button class="w-8 h-8 bg-white rounded-lg shadow flex items-center justify-center hover:bg-gray-50">
                        <i class="fa-solid fa-minus text-sm"></i>
                    </button>
                </div>
            </div>
            
            <div class="mt-3 flex gap-2 text-xs text-gray-600">
                <span><i class="fa-solid fa-circle text-green-500 mr-1"></i> 3 Hospitals</span>
                <span><i class="fa-solid fa-circle text-red-500 mr-1"></i> 5 Fire Stations</span>
                <span><i class="fa-solid fa-circle text-blue-500 mr-1"></i> 4 Evacuation Centers</span>
            </div>
        </div>

        <!-- Safety Quiz Section -->
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-xl shadow p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-lg mb-1">🧠 Test Your Safety Knowledge</h3>
                    <p class="text-sm text-purple-100">Take our 5-minute safety quiz</p>
                </div>
                <i class="fa-solid fa-circle-question text-4xl opacity-50"></i>
            </div>
            
            <div class="bg-white bg-opacity-20 rounded-lg p-4 mb-4">
                <div class="flex justify-between text-sm mb-2">
                    <span>Question 1 of 5</span>
                    <span id="quiz-progress">0%</span>
                </div>
                <div class="w-full bg-white bg-opacity-30 rounded-full h-2">
                    <div id="quiz-progress-bar" class="bg-white h-2 rounded-full" style="width: 0%"></div>
                </div>
            </div>
            
            <p class="mb-4 font-medium">What is the correct way to use a fire extinguisher?</p>
            
            <div class="space-y-2 mb-4" id="quiz-options">
                <label class="quiz-option block bg-white bg-opacity-10 rounded-lg p-3 hover:bg-opacity-20 cursor-pointer">
                    <input type="radio" name="quiz" class="mr-2" value="A"> PASS: Pull, Aim, Squeeze, Sweep
                </label>
                <label class="quiz-option block bg-white bg-opacity-10 rounded-lg p-3 hover:bg-opacity-20 cursor-pointer">
                    <input type="radio" name="quiz" class="mr-2" value="B"> Spray and Run
                </label>
                <label class="quiz-option block bg-white bg-opacity-10 rounded-lg p-3 hover:bg-opacity-20 cursor-pointer">
                    <input type="radio" name="quiz" class="mr-2" value="C"> Point and Shoot
                </label>
                <label class="quiz-option block bg-white bg-opacity-10 rounded-lg p-3 hover:bg-opacity-20 cursor-pointer">
                    <input type="radio" name="quiz" class="mr-2" value="D"> Pour water on fire
                </label>
            </div>
            
            <div class="flex gap-3">
                <button id="check-answer" class="flex-1 bg-white text-purple-600 py-2 rounded-lg font-medium hover:bg-purple-50">
                    Check Answer
                </button>
                <button id="next-question" class="flex-1 bg-purple-700 text-white py-2 rounded-lg font-medium hover:bg-purple-800 disabled:opacity-50" disabled>
                    Next Question
                </button>
            </div>
            
            <div id="quiz-feedback" class="mt-3 text-sm hidden"></div>
        </div>

        <!-- Emergency Contacts Quick Dial -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <button class="emergency-dial bg-red-600 text-white p-4 rounded-xl hover:bg-red-700 transition-colors text-center" data-number="911">
                <i class="fa-solid fa-phone text-2xl mb-2"></i>
                <p class="font-bold">911</p>
                <p class="text-xs opacity-90">Emergency</p>
            </button>
            <button class="emergency-dial bg-orange-600 text-white p-4 rounded-xl hover:bg-orange-700 transition-colors text-center" data-number="555-0123">
                <i class="fa-solid fa-fire-extinguisher text-2xl mb-2"></i>
                <p class="font-bold">Fire Dept</p>
                <p class="text-xs opacity-90">Non-Emergency</p>
            </button>
            <button class="emergency-dial bg-green-600 text-white p-4 rounded-xl hover:bg-green-700 transition-colors text-center" data-number="555-0124">
                <i class="fa-solid fa-truck-medical text-2xl mb-2"></i>
                <p class="font-bold">Ambulance</p>
                <p class="text-xs opacity-90">Medical</p>
            </button>
            <button class="emergency-dial bg-blue-600 text-white p-4 rounded-xl hover:bg-blue-700 transition-colors text-center" data-number="555-0125">
                <i class="fa-solid fa-shield text-2xl mb-2"></i>
                <p class="font-bold">Police</p>
                <p class="text-xs opacity-90">Non-Emergency</p>
            </button>
        </div>
    </div>
</div>

<!-- Download Modal -->
<div id="download-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl max-w-md w-full p-6 m-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Download Guide</h3>
            <button id="close-modal" class="text-gray-500 hover:text-gray-700">
                <i class="fa-solid fa-times text-2xl"></i>
            </button>
        </div>
        <div class="text-center py-4">
            <i class="fa-solid fa-file-pdf text-red-500 text-6xl mb-4"></i>
            <h4 class="font-semibold text-lg mb-2" id="modal-guide-title">Fire Safety Guide</h4>
            <p class="text-gray-600 mb-4">Your download will start automatically.</p>
            <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                <div id="download-progress" class="bg-orange-600 h-2 rounded-full" style="width: 0%"></div>
            </div>
        </div>
        <div class="flex gap-3">
            <button id="cancel-download" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Cancel
            </button>
            <button id="save-manual" class="flex-1 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                Save File
            </button>
        </div>
    </div>
</div>

<!-- Resource Info Panel -->
<div id="resource-panel" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl max-w-2xl w-full p-6 m-4 max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800" id="resource-title">Emergency Contacts</h3>
            <button id="close-resource" class="text-gray-500 hover:text-gray-700">
                <i class="fa-solid fa-times text-2xl"></i>
            </button>
        </div>
        <div id="resource-content" class="space-y-4">
            <!-- Dynamic content will be loaded here -->
        </div>
    </div>
</div>

<!-- Emergency Kit Builder Modal -->
<div id="kit-builder-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl max-w-2xl w-full p-6 m-4 max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Build Your Emergency Kit</h3>
            <button id="close-kit-builder" class="text-gray-500 hover:text-gray-700">
                <i class="fa-solid fa-times text-2xl"></i>
            </button>
        </div>
        
        <div class="space-y-4">
            <p class="text-sm text-gray-600">Select items you already have and get a personalized checklist:</p>
            
            <div class="space-y-3">
                <h4 class="font-semibold text-gray-700">Water & Food (3-day supply)</h4>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                        <input type="checkbox" class="kit-item text-orange-600" data-category="water"> Water (1 gal/person/day)
                    </label>
                    <label class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                        <input type="checkbox" class="kit-item text-orange-600" data-category="food"> Non-perishable food
                    </label>
                    <label class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                        <input type="checkbox" class="kit-item text-orange-600" data-category="food"> Manual can opener
                    </label>
                    <label class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                        <input type="checkbox" class="kit-item text-orange-600" data-category="food"> Eating utensils
                    </label>
                </div>
            </div>
            
            <div class="space-y-3">
                <h4 class="font-semibold text-gray-700">First Aid & Medical</h4>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                        <input type="checkbox" class="kit-item text-orange-600"> First aid kit
                    </label>
                    <label class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                        <input type="checkbox" class="kit-item text-orange-600"> Prescription meds (7-day)
                    </label>
                    <label class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                        <input type="checkbox" class="kit-item text-orange-600"> Pain relievers
                    </label>
                    <label class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                        <input type="checkbox" class="kit-item text-orange-600"> Medical gloves
                    </label>
                </div>
            </div>
            
            <div class="space-y-3">
                <h4 class="font-semibold text-gray-700">Tools & Supplies</h4>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                        <input type="checkbox" class="kit-item text-orange-600"> Flashlight
                    </label>
                    <label class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                        <input type="checkbox" class="kit-item text-orange-600"> Extra batteries
                    </label>
                    <label class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                        <input type="checkbox" class="kit-item text-orange-600"> Multi-tool
                    </label>
                    <label class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                        <input type="checkbox" class="kit-item text-orange-600"> Whistle
                    </label>
                    <label class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                        <input type="checkbox" class="kit-item text-orange-600"> Portable charger
                    </label>
                    <label class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                        <input type="checkbox" class="kit-item text-orange-600"> Radio (hand-crank)
                    </label>
                </div>
            </div>
            
            <div class="space-y-3">
                <h4 class="font-semibold text-gray-700">Documents & Personal</h4>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                        <input type="checkbox" class="kit-item text-orange-600"> IDs/Passports (copies)
                    </label>
                    <label class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                        <input type="checkbox" class="kit-item text-orange-600"> Insurance documents
                    </label>
                    <label class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                        <input type="checkbox" class="kit-item text-orange-600"> Cash ($100-200)
                    </label>
                    <label class="flex items-center gap-2 p-2 bg-gray-50 rounded">
                        <input type="checkbox" class="kit-item text-orange-600"> Emergency contacts list
                    </label>
                </div>
            </div>
            
            <div class="mt-6 p-4 bg-orange-50 rounded-lg">
                <h4 class="font-semibold text-orange-800 mb-2">Your Kit Progress</h4>
                <div class="w-full bg-orange-200 rounded-full h-3">
                    <div id="kit-progress-bar" class="bg-orange-600 h-3 rounded-full" style="width: 0%"></div>
                </div>
                <p class="text-sm text-orange-700 mt-2" id="kit-progress-text">0 of 18 items selected</p>
            </div>
            
            <div class="flex gap-3 mt-4">
                <button id="generate-kit-list" class="flex-1 bg-orange-600 text-white py-2 rounded-lg hover:bg-orange-700">
                    Generate Checklist
                </button>
                <button id="print-kit-list" class="flex-1 border border-orange-600 text-orange-600 py-2 rounded-lg hover:bg-orange-50">
                    <i class="fa-solid fa-print mr-1"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

@vite(['resources/css/citizen/safety.css', 'resources/js/citizen/safety.js'])
