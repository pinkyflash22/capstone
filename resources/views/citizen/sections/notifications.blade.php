<div id="section-notifications" class="content-section">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl lg:text-3xl font-bold text-gray-800">Notifications</h1>
        <button id="citizenNotificationsMarkRead" class="text-sm text-gray-500 hover:text-gray-700">
            <i class="fa-regular fa-circle-check mr-1"></i> Mark all as read
        </button>
    </div>

    <div class="flex gap-2 mb-4 border-b border-gray-200">
        <button class="px-4 py-2 text-orange-600 border-b-2 border-orange-600 font-medium">All</button>
        <button class="px-4 py-2 text-gray-500 hover:text-gray-700">Unread</button>
        <button class="px-4 py-2 text-gray-500 hover:text-gray-700">Alerts</button>
    </div>

    <div id="citizenNotificationsFeed" class="bg-white rounded-xl shadow divide-y divide-gray-200">
        <div class="p-6 text-center text-sm text-gray-500">
            No emergency notifications yet.
        </div>
    </div>

    <div class="mt-8 bg-white rounded-xl shadow p-5">
        <h2 class="font-bold text-gray-800 mb-3">Notification Settings</h2>
        <div class="space-y-3">
            <label class="flex items-center justify-between">
                <span class="text-sm">Emergency Alerts</span>
                <input type="checkbox" checked class="toggle">
            </label>
            <label class="flex items-center justify-between">
                <span class="text-sm">Nearby Alerts Only</span>
                <input type="checkbox" checked class="toggle">
            </label>
            <label class="flex items-center justify-between">
                <span class="text-sm">Sound and Vibration</span>
                <input type="checkbox" checked class="toggle">
            </label>
        </div>
    </div>
</div>
