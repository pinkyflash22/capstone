<?php

return [
    // One server-side key; no separate browser map key is required.
    'openrouteservice_key' => env('OPENROUTESERVICE_API_KEY', env('ORS_API_KEY')),
    'weatherapi_key' => env('WEATHERAPI_KEY', env('WEATHER_API_KEY')),
    'visual_crossing_key' => env('VISUAL_CROSSING_API_KEY', env('VISUALCROSSING_API_KEY')),
];
