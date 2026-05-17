<div id="section-events" class="content-section">
    <h1 class="text-2xl lg:text-3xl font-bold text-gray-800 mb-6">Drills & Events</h1>
    
    <!-- Tabs -->
    <div class="flex gap-2 mb-6 border-b border-gray-200">
        <button class="px-4 py-2 text-orange-600 border-b-2 border-orange-600 font-medium">Upcoming</button>
        <button class="px-4 py-2 text-gray-500 hover:text-gray-700">Past Events</button>
        <button class="px-4 py-2 text-gray-500 hover:text-gray-700">My RSVPs</button>
    </div>

    <!-- Featured Event -->
    <div class="bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-xl shadow-lg p-6 mb-6">
        <div class="flex flex-col lg:flex-row justify-between items-center gap-4">
            <div>
                <span class="bg-white/20 text-white px-3 py-1 rounded-full text-sm mb-3 inline-block">🔥 FEATURED</span>
                <h2 class="text-2xl font-bold mb-2">Annual Fire Drill & Safety Fair</h2>
                <p class="text-white/90 mb-2">March 20, 2026 · 9:00 AM - 3:00 PM @ Central Station</p>
                <div class="flex items-center gap-4 text-sm">
                    <span><i class="fa-regular fa-users mr-1"></i> 200+ attending</span>
                    <span><i class="fa-regular fa-clock mr-1"></i> 6 hours</span>
                </div>
            </div>
            <button class="bg-white text-orange-600 px-8 py-3 rounded-lg font-bold hover:bg-gray-100 shadow-lg">
                Register Now
            </button>
        </div>
    </div>

    <!-- Events Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Event Card 1 -->
        <div class="bg-white rounded-xl shadow overflow-hidden hover:shadow-lg transition">
            <div class="h-32 bg-gradient-to-r from-blue-400 to-blue-600 relative">
                <span class="absolute top-2 right-2 bg-white/90 text-blue-600 px-2 py-1 rounded-full text-xs font-bold">MAR 25</span>
            </div>
            <div class="p-4">
                <h3 class="font-bold text-gray-800 mb-1">CPR Certification Course</h3>
                <p class="text-sm text-gray-600 mb-2">Learn life-saving CPR techniques</p>
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                    <i class="fa-regular fa-clock"></i> 2:00 PM - 5:00 PM
                    <i class="fa-regular fa-building ml-2"></i> Station 7
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-green-600">12 spots left</span>
                    <button class="text-orange-600 text-sm hover:underline">RSVP →</button>
                </div>
            </div>
        </div>

        <!-- Event Card 2 -->
        <div class="bg-white rounded-xl shadow overflow-hidden hover:shadow-lg transition">
            <div class="h-32 bg-gradient-to-r from-green-400 to-green-600 relative">
                <span class="absolute top-2 right-2 bg-white/90 text-green-600 px-2 py-1 rounded-full text-xs font-bold">MAR 28</span>
            </div>
            <div class="p-4">
                <h3 class="font-bold text-gray-800 mb-1">Neighborhood Disaster Prep</h3>
                <p class="text-sm text-gray-600 mb-2">Emergency planning workshop</p>
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                    <i class="fa-regular fa-clock"></i> 6:30 PM - 8:30 PM
                    <i class="fa-regular fa-building ml-2"></i> Community Center
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-green-600">35 spots left</span>
                    <button class="text-orange-600 text-sm hover:underline">RSVP →</button>
                </div>
            </div>
        </div>

        <!-- Event Card 3 -->
        <div class="bg-white rounded-xl shadow overflow-hidden hover:shadow-lg transition">
            <div class="h-32 bg-gradient-to-r from-purple-400 to-purple-600 relative">
                <span class="absolute top-2 right-2 bg-white/90 text-purple-600 px-2 py-1 rounded-full text-xs font-bold">APR 02</span>
            </div>
            <div class="p-4">
                <h3 class="font-bold text-gray-800 mb-1">Youth Fire Safety Day</h3>
                <p class="text-sm text-gray-600 mb-2">For kids ages 5-12 with parents</p>
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-3">
                    <i class="fa-regular fa-clock"></i> 10:00 AM - 1:00 PM
                    <i class="fa-regular fa-building ml-2"></i> Firehouse Museum
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-green-600">25 spots left</span>
                    <button class="text-orange-600 text-sm hover:underline">RSVP →</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar View -->
    <div class="mt-8 bg-white rounded-xl shadow p-5">
        <h2 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fa-regular fa-calendar"></i> Monthly Calendar
        </h2>
        <div class="grid grid-cols-7 gap-1 text-center text-sm">
            <div class="font-semibold text-gray-600">S</div>
            <div class="font-semibold text-gray-600">M</div>
            <div class="font-semibold text-gray-600">T</div>
            <div class="font-semibold text-gray-600">W</div>
            <div class="font-semibold text-gray-600">T</div>
            <div class="font-semibold text-gray-600">F</div>
            <div class="font-semibold text-gray-600">S</div>
            <!-- Calendar days would go here -->
        </div>
    </div>
</div>