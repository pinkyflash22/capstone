<div id="section-neighborhood" class="content-section">
    <h1 class="text-2xl lg:text-3xl font-bold text-gray-800 mb-6">Neighborhood Watch</h1>
    
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center text-green-600">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Active Members</p>
                    <p class="text-2xl font-bold text-gray-800">156</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                    <i class="fa-solid fa-eye"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Watching Now</p>
                    <p class="text-2xl font-bold text-gray-800">8</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center text-yellow-600">
                    <i class="fa-solid fa-flag"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Reports This Week</p>
                    <p class="text-2xl font-bold text-gray-800">23</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center text-purple-600">
                    <i class="fa-solid fa-shield"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Safety Score</p>
                    <p class="text-2xl font-bold text-gray-800">A-</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Activity Feed -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow p-5">
            <h2 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-orange-600"></i>
                Recent Neighborhood Activity
            </h2>
            <div class="space-y-4">
                <div class="flex gap-3 p-3 bg-gray-50 rounded-lg">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center text-green-600 flex-shrink-0">
                        <i class="fa-solid fa-check text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium">Sarah from Maple St reported: "Suspicious vehicle on our block"</p>
                        <p class="text-xs text-gray-500 mt-1">15 minutes ago · 3 neighbors confirmed</p>
                    </div>
                </div>
                <div class="flex gap-3 p-3 bg-gray-50 rounded-lg">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 flex-shrink-0">
                        <i class="fa-solid fa-camera text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium">Security camera captured package theft on Elm Street</p>
                        <p class="text-xs text-gray-500 mt-1">2 hours ago · footage shared with police</p>
                    </div>
                </div>
                <div class="flex gap-3 p-3 bg-gray-50 rounded-lg">
                    <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-600 flex-shrink-0">
                        <i class="fa-solid fa-bell text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium">Alert: Open fire hydrant on Pine & 5th</p>
                        <p class="text-xs text-gray-500 mt-1">3 hours ago · Public works notified</p>
                    </div>
                </div>
                <div class="flex gap-3 p-3 bg-gray-50 rounded-lg">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center text-green-600 flex-shrink-0">
                        <i class="fa-solid fa-face-smile text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium">Lost dog found! Max reunited with family on Oak Ave</p>
                        <p class="text-xs text-gray-500 mt-1">5 hours ago · thanks to neighbor alerts</p>
                    </div>
                </div>
            </div>
            <button class="mt-4 text-orange-600 text-sm font-medium hover:underline">View All Activity →</button>
        </div>

        <!-- Online Neighbors & Map -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow p-5">
                <h2 class="font-bold text-gray-800 mb-3">🟢 Online Now</h2>
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            <span class="text-sm">Michael R.</span>
                        </div>
                        <span class="text-xs text-gray-500">2 blocks away</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            <span class="text-sm">Jennifer L.</span>
                        </div>
                        <span class="text-xs text-gray-500">next street</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            <span class="text-sm">David W.</span>
                        </div>
                        <span class="text-xs text-gray-500">around corner</span>
                    </div>
                </div>
                <hr class="my-3">
                <button class="w-full text-sm bg-orange-50 text-orange-700 p-2 rounded-lg hover:bg-orange-100">
                    <i class="fa-regular fa-message mr-1"></i> Join Group Chat
                </button>
            </div>

            <div class="bg-white rounded-xl shadow p-5">
                <h2 class="font-bold text-gray-800 mb-3">📋 Safety Tips</h2>
                <ul class="space-y-2 text-sm">
                    <li class="flex gap-2"><i class="fa-solid fa-lightbulb text-yellow-500"></i> Keep porch lights on at night</li>
                    <li class="flex gap-2"><i class="fa-solid fa-lightbulb text-yellow-500"></i> Report suspicious activity immediately</li>
                    <li class="flex gap-2"><i class="fa-solid fa-lightbulb text-yellow-500"></i> Update emergency contact list</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Next Meeting Card -->
    <div class="mt-6 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl shadow p-5">
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
            <div>
                <h3 class="font-bold text-lg">🗓️ Next Neighborhood Watch Meeting</h3>
                <p class="text-white/90">Thursday, March 15 · 7:00 PM @ Community Center</p>
            </div>
            <button class="bg-white text-blue-600 px-6 py-2 rounded-lg font-medium hover:bg-gray-100">
                RSVP Now
            </button>
        </div>
    </div>
</div>