<div id="section-contacts" class="content-section">
    <h1 class="text-2xl lg:text-3xl font-bold text-gray-800 mb-6">Emergency Contacts</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Primary Emergency Numbers -->
        <div class="bg-white rounded-xl shadow p-5">
            <h2 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-phone-volume text-red-600"></i> Primary Emergency
            </h2>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                    <div>
                        <p class="font-semibold text-gray-800">Emergency</p>
                        <p class="text-sm text-gray-600">Police, Fire, Medical</p>
                    </div>
                    <a href="tel:911" class="bg-red-600 text-white px-4 py-2 rounded-lg text-xl font-bold hover:bg-red-700">911</a>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                    <div>
                        <p class="font-semibold text-gray-800">Non-Emergency</p>
                        <p class="text-sm text-gray-600">Fire Department</p>
                    </div>
                    <a href="tel:555-0123" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-blue-700">555-0123</a>
                </div>
            </div>
        </div>
        
        <!-- Local Stations -->
        <div class="bg-white rounded-xl shadow p-5">
            <h2 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-fire-extinguisher text-orange-600"></i> Fire Stations
            </h2>
            <div class="space-y-3">
                @foreach ($stations as $st )
                    <div class="flex justify-between items-center">
                    <div>
                        <p class="font-semibold">{{ $st->station_name }}</p>
                        <p class="text-xs text-gray-500">{{ $st->address_id }} · 0.8 mi</p>
                    </div>
                    <a href="tel:{{ $st->contact_number }}" class="text-orange-600 hover:underline">{{ $st->contact_number }} </a>
                </div>
                @endforeach
                
                {{-- <div class="flex justify-between items-center">
                    <div>
                        <p class="font-semibold">Station 12</p>
                        <p class="text-xs text-gray-500">450 Oak Ave · 1.5 mi</p>
                    </div>
                    <a href="tel:555-0112" class="text-orange-600 hover:underline">555-0112</a>
                </div>
                <div class="flex justify-between items-center">
                    <div>
                        <p class="font-semibold">Station 3</p>
                        <p class="text-xs text-gray-500">789 Maple Dr · 2.1 mi</p>
                    </div>
                    <a href="tel:555-0103" class="text-orange-600 hover:underline">555-0103</a>
                </div> --}}
            </div>
        </div>
        
        <!-- Community Contacts -->
        <div class="bg-white rounded-xl shadow p-5 md:col-span-2">
            <h2 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-people-group text-green-600"></i> Community Support
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <i class="fa-solid fa-hospital text-2xl text-blue-600 mb-2"></i>
                    <p class="font-semibold">Medical Center</p>
                    <p class="text-xs text-gray-500">24/7 Emergency</p>
                    <a href="tel:555-0200" class="text-sm text-blue-600">555-0200</a>
                </div>
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <i class="fa-solid fa-truck-droplet text-2xl text-red-600 mb-2"></i>
                    <p class="font-semibold">Hazmat Team</p>
                    <p class="text-xs text-gray-500">Chemical Emergencies</p>
                    <a href="tel:555-0300" class="text-sm text-red-600">555-0300</a>
                </div>
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <i class="fa-solid fa-hand-holding-heart text-2xl text-purple-600 mb-2"></i>
                    <p class="font-semibold">Disaster Relief</p>
                    <p class="text-xs text-gray-500">Red Cross</p>
                    <a href="tel:555-0400" class="text-sm text-purple-600">555-0400</a>
                </div>
            </div>
        </div>
    </div>
</div>