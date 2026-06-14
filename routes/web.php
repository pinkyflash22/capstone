<?php
use App\Http\Controllers\AdminController;
// use App\Http\Controllers\MarkerController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PublicController;
// use App\Http\Controllers\MapController;
// use App\Http\Controllers\IconController;
use App\Http\Controllers\IncidentController;
// use App\Http\Controllers\ShapeController;
// use App\Http\Controllers\MarkController;
use App\Http\Controllers\MultimediaController;
// use App\Http\Controllers\AnalyticsController; // ADD THIS LINE
use App\Http\Controllers\Admin\AnalyticsController;

use App\Http\Controllers\Admin\PostController;
// use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Api\MarkerController;
use App\Http\Controllers\staff\FirefighterController;
use App\Http\Controllers\Admin\ManagementController;

use App\Http\Controllers\StationOfficerController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserLogController;

//station admin
use App\Http\Controllers\Chief\StationAdminController;
use App\Http\Controllers\Barangay\BarangayDashboardController;


// use App\Http\Controllers\Auth\StationLoginController;
use App\Http\Controllers\FsicRequestController;

use App\Http\Controllers\MarkerCategoryController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ResourceManagementController;



use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\EquipmentCategoryController;
use App\Http\Controllers\EquipmentController;
// use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\OTPController;

// use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
//note kailangan ko pang import ung controller para magamit ko yung mga functions nila sa routes
    Route::middleware('redirect.if.authenticated')->group(function () {
        Route::get('/', [PublicController::class, 'homepage'])->name('home');
        Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
        Route::post('/register', [RegisterController::class, 'register'])->name('register.store');
        Route::get('/login',[LoginController::class, 'index'])->name('auth.login');
        Route::post('/auth', [LoginController::class, 'authenticate'])->name('auth.authenticate');
        Route::get('/auth/google/redirect', [LoginController::class, 'redirectToGoogle'])->name('auth.google.redirect');
        Route::get('/auth/google/callback', [LoginController::class, 'handleGoogleCallback'])->name('auth.google.callback');
        Route::get('/auth/google/verify', [LoginController::class, 'showGoogleVerificationForm'])->name('auth.google.verify');
        Route::post('/auth/google/verify', [LoginController::class, 'verifyGoogleOtp'])->name('auth.google.verify.submit');
        Route::post('/auth/google/resend-otp', [LoginController::class, 'resendGoogleOtp'])->name('auth.google.resend-otp');
    });

    Route::get('/pwa/quick-report', [PublicController::class, 'pwaQuickReport'])->name('pwa.quick-report');

    // Registration routes with OTP
// Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register/send-otp', [RegisterController::class, 'sendOTP'])->name('register.send-otp');
Route::post('/register/verify-otp', [RegisterController::class, 'verifyOTPAndRegister'])->name('register.verify-otp');
Route::post('/register/resend-otp', [RegisterController::class, 'resendOTP'])->name('register.resend-otp');
Route::get('/api/recent-incidents',[IncidentController::class,'getRecentIncident']);

Route::middleware(['auth', 'prevent.back.history', 'role:admin'])->get('/test-api-key', function() {
    $apiKey = env('OPENROUTER_API_KEY');
    
    // Test with a simple models list request (this endpoint exists)
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json',
    ])->get('https://openrouter.ai/api/v1/models');
    
    return response()->json([
        'api_key_exists' => !empty($apiKey),
        'api_key_preview' => substr($apiKey, 0, 15) . '...',
        'response_status' => $response->status(),
        'is_valid' => $response->status() === 200,
        'models_count' => $response->successful() ? count($response->json()['data'] ?? []) : 0,
        'error' => $response->status() !== 200 ? $response->body() : null
    ]);
});
    // Route::get('/register', [RegisterController::class,'show'])->name('register');
    // Route::post('/register', [RegisterController::class,'register'])->name('register.store');
    // AJAX routes for address selection
    Route::get('/api/cities/{provinceId}', [RegisterController::class, 'getCities'])->name('api.cities');
    Route::get('/api/barangays/{cityId}', [RegisterController::class, 'getBarangays'])->name('api.barangays');
    Route::get('/api/postal-codes/{cityId}', [RegisterController::class, 'getPostalCodes'])->name('api.postal-codes');
    Route::get('/api/default-postal/{cityId}', [RegisterController::class, 'getDefaultPostalCode'])->name('api.default-postal');

    //locations based
    Route::get('/api/firefighter/locations', [FirefighterController::class, 'getAllLocations']);
    Route::post('/api/firefighter/location', [FirefighterController::class, 'updateLocation']);

    // Route::get('/', [LandingPageController::class, 'index'])->name('landing');
    Route::get('/api/stations', [LandingPageController::class, 'getStations'])->name('api.stations');
    Route::get('/api/hydrants', [LandingPageController::class, 'getHydrants'])->name('api.hydrants');
    Route::middleware(['auth', 'prevent.back.history', 'role:firefighter'])->get('/ff', function (){
        return view('firefighters.dashboard');
    });


// This route should be outside the auth middleware
Route::get('/check-auth', function() {
    return response()->json([
        'authenticated' => Auth::check()
    ]);
});
Route::middleware('auth')->post('/citizen/marker', [MarkerController::class, 'store']);
// Route::get('/register',[LoginController::class, 'register'])->name('auth.register');
//register infos
Route::post('/multimedia/store', [MultimediaController::class, 'store'])
    ->name('multimedia.store');
Route::get('/multimedia', [MultimediaController::class, 'index'])->name('multimedia.index');
Route::middleware(['auth', 'prevent.back.history', 'role:admin'])->get('/weather', function(){
    return view('admin.weather');
});

Route::middleware(['auth', 'prevent.back.history', 'role:admin'])->get('/debug-incidents', function() {
    // Check stations and their marker_ids
    $stations = DB::table('stations')->select('station_id', 'station_name', 'marker_id')->get();
    
    // Check incidents and their marker_ids
    $incidents = DB::table('incidents')
        ->select('incident_id', 'marker_id', 'created_at')
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    
    // Check if marker_ids match
    $stationMarkerIds = DB::table('stations')->whereNotNull('marker_id')->pluck('marker_id')->toArray();
    $incidentMarkerIds = DB::table('incidents')->whereNotNull('marker_id')->pluck('marker_id')->unique()->toArray();
    
    return response()->json([
        'stations' => $stations,
        'recent_incidents' => $incidents,
        'station_marker_ids' => $stationMarkerIds,
        'incident_marker_ids' => $incidentMarkerIds,
        'matching_markers' => array_intersect($stationMarkerIds, $incidentMarkerIds),
        'total_incidents' => DB::table('incidents')->count(),
        'incidents_with_marker' => DB::table('incidents')->whereNotNull('marker_id')->count(),
        'stations_with_marker' => DB::table('stations')->whereNotNull('marker_id')->count()
    ]);
});


Route::middleware(['auth', 'prevent.back.history', 'role:admin'])->get('/test-db', function() {
    try {
        $incidents = DB::table('incidents')->count();
        $markers = DB::table('markers')->count();
        
        return response()->json([
            'success' => true,
            'incident_count' => $incidents,
            'marker_count' => $markers,
            'tables' => DB::select('SHOW TABLES')
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});
// Route::get('/debug-analytics', function() {
//     try {
//         $controller = new App\Http\Controllers\AnalyticsController();
//         return response()->json([
//             'message' => 'Analytics controller loaded',
//             'auth_check' => auth()->check(),
//             'user_id' => auth()->id()
//         ]);
//     } catch (\Exception $e) {
//         return response()->json([
//             'error' => $e->getMessage(),
//             'file' => $e->getFile(),
//             'line' => $e->getLine()
//         ], 500);
//     }
// });
Route::middleware(['auth', 'prevent.back.history'])->group(function () {
    Route::get('/user-logs', [UserLogController::class, 'index'])->name('user-logs.index');
    Route::get('/user-logs/all', [UserLogController::class, 'all'])->name('user-logs.all');
    Route::post('/user-logs/action', [UserLogController::class, 'store'])->name('user-logs.store');
    Route::middleware(['role:citizen'])->group(function () {
        Route::get('/citizen/dashboard-data', [PublicController::class, 'dashboardData'])->name('citizen.dashboard-data');
        Route::get('/citizen/api/announcements', [App\Http\Controllers\staff\PostController::class, 'getPosts'])->name('citizen.announcements.feed');
        Route::post('/citizen/api/announcements/{post}/like', [App\Http\Controllers\staff\PostController::class, 'like'])->name('citizen.announcements.like');
        Route::post('/citizen/api/announcements/{post}/comment', [App\Http\Controllers\staff\PostController::class, 'comment'])->name('citizen.announcements.comment');
    });
        // Analytics page
    // Route::get('/admin/analytics', [AnalyticsController::class, 'index'])->name('admin.analytics');
    
    // API endpoints for analytics
    // Route::post('/api/predictive-analysis', [AnalyticsController::class, 'runAnalysis'])->name('api.analytics');
    // Route::get('/api/historical-analyses', [AnalyticsController::class, 'getHistoricalAnalyses'])->name('api.historical');

     Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {

        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/maps', [AdminController::class, 'maps'])->name('maps');
        Route::get('/alerts',[AdminController::class, 'alerts'])->name('alerts');
        Route::get('/reports',[AdminController::class, 'reports'])->name('reports');
         

            
        Route::get('/api/stations', [App\Http\Controllers\Admin\AnalyticsController::class, 'getStations']);
        Route::get('/api/station-analytics/{stationId}', [App\Http\Controllers\Admin\AnalyticsController::class, 'analyzeStation']);
        // Route::get('/aanalytics', function () {
        //         return view('admin.analytics');
        //     })->name('analytics');

        Route::get('/analytics', [App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics');
        Route::get('/api/stations-for-analytics', [App\Http\Controllers\AnalyticsController::class, 'getStationsForAnalytics']);
        Route::post('/api/ai-predictive-analysis', [App\Http\Controllers\AnalyticsController::class, 'runAIPredictiveAnalysis']);
        Route::get('/api/analyses/history', [App\Http\Controllers\AnalyticsController::class, 'getHistoricalAnalyses']);
        Route::get('/api/analyses/{id}', [App\Http\Controllers\AnalyticsController::class, 'getAnalysis']);
    
        // });
        // Route::post('/markers', [AdminController::class, 'store'])->name('admin.markers.store');
        Route::get('/marker-categories', [MarkerCategoryController::class, 'index']);
        // Route::get('/geticons', [IconController::class, 'getIcons'])->name('geticon');
        Route::get('/announcements', [PostController::class, 'index'])->name('announcements');
        Route::get('/posts/get', [PostController::class, 'getPosts'])->name('posts.get');
        Route::post('/posts/store', [PostController::class, 'store'])->name('posts.store');
        Route::post('/posts/{post}/like', [PostController::class, 'like'])->name('posts.like');
        Route::post('/posts/{post}/comment', [PostController::class, 'comment'])->name('posts.comment');
        Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
        Route::resource('management', ManagementController::class);
        Route::get('/management', [ManagementController::class,'index']) ->name('management');

        //delete officer on a station
        Route::post('/station-officers', [StationOfficerController::class, 'store'])->name('station-officers.store');
        Route::delete('/station-officer/{id}', [StationOfficerController::class, 'destroy'])->name('station-officer.destroy');
        Route::patch('/station-officers/{id}/status', [StationOfficerController::class, 'updateStatus'])->name('station-officer.updateStatus');
        Route::delete('/users/{id}', [StationOfficerController::class, 'destroyUser'])->name('users.destroy');
        Route::put('/users/{id}', [StationOfficerController::class, 'updateUser'])->name('users.update');
        Route::put('/officers/{id}', [StationOfficerController::class, 'updateStationOfficer'])->name('officers.update');
        Route::put('/stations/{station}', [App\Http\Controllers\Chief\ManagementController::class, 'updateStation'])->name('stations.update');
        
        Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
        // Route::get('/resource', function(){
        //     return view('admin.resource-management');
        // });
        Route::resource('equipment-category', EquipmentCategoryController::class);
        // Route::post('equipment-category', [EquipmentCategoryController::class, 'store']);

        Route::resource('equipment', EquipmentController::class);
        Route::resource('incidents', IncidentController::class);
        Route::resource('resource-management', ResourceManagementController::class);
    
     });

    Route::middleware(['role:station admin,chief'])->prefix('station-admin')->name('station-admin.')->group(function () {

        Route::get('/dashboard', [StationAdminController::class, 'index'])->name('dashboard');
        // Route::get('/request', [StationAdminController::class, 'fsic'])->name('fsic');

        Route::get('/maps', [StationAdminController::class, 'maps'])->name('maps');
        Route::get('/alerts',[StationAdminController::class, 'alerts'])->name('alerts');
        Route::get('/reports',[StationAdminController::class, 'reports'])->name('reports');
        Route::get('/schedule', [StationAdminController::class, 'schedule'])->name('schedule');
        Route::post('/schedule/events', [StationAdminController::class, 'storeScheduleEvent'])->name('schedule.events.store');
        Route::get('/profile', [StationAdminController::class, 'profile'])->name('profile');
        Route::put('/profile', [StationAdminController::class, 'updateProfile'])->name('profile.update');
        Route::get('/api/dashboard-stats', [StationAdminController::class, 'dashboardStats'])->name('dashboard-stats');
        Route::get('/api/management/equipment', [App\Http\Controllers\Chief\ManagementController::class, 'getEquipment'])->name('management.equipment');
                Route::resource('management', App\Http\Controllers\Chief\ManagementController::class);
        Route::get('/management', [App\Http\Controllers\Chief\ManagementController::class,'index']) ->name('management');

        //fsic
         Route::get('/fsic', [App\Http\Controllers\Chief\FsicManagementController::class, 'index'])->name('fsic');
         Route::post('/api/create-applicant', [App\Http\Controllers\Chief\FsicManagementController::class, 'createApplicant']);
         Route::post('/api/walk-in-citizen', [StationAdminController::class, 'createWalkInCitizen']);
         Route::post('/api/fsic', [App\Http\Controllers\Chief\FsicManagementController::class, 'store']);

            Route::get('/api/fsic/{id}', [App\Http\Controllers\Chief\FsicManagementController::class, 'show']);
            Route::post('/api/fsic/{id}/review', [App\Http\Controllers\Chief\FsicManagementController::class, 'review']);
            Route::post('/api/fsic/{id}/inspect', [App\Http\Controllers\Chief\FsicManagementController::class, 'inspect']);
            Route::post('/api/fsic/{id}/approve', [App\Http\Controllers\Chief\FsicManagementController::class, 'approve']);



        Route::get('/api/stations', [App\Http\Controllers\Admin\AnalyticsController::class, 'getStations']);
        Route::get('/api/station-analytics/{stationId}', [App\Http\Controllers\Admin\AnalyticsController::class, 'analyzeStation']);
        // Route::get('/aanalytics', function () {
        //         return view('admin.analytics');
        //     })->name('analytics');
        Route::post('/api/explore-recommendation', [App\Http\Controllers\Chief\AnalyticController::class, 'exploreRecommendation']);
        Route::get('/analytics', [App\Http\Controllers\Chief\AnalyticController::class, 'index'])->name('analytics');
        Route::get('/course-checklists', [App\Http\Controllers\Chief\AnalyticController::class, 'checklistIndex'])->name('course-checklists');
        Route::get('/api/stations-for-analytics', [App\Http\Controllers\Chief\AnalyticController::class, 'getStationsForAnalytics']);
        Route::post('/api/ai-predictive-analysis', [App\Http\Controllers\Chief\AnalyticController::class, 'runAIPredictiveAnalysis']);
        Route::get('/api/analyses/history', [App\Http\Controllers\Chief\AnalyticController::class, 'getHistoricalAnalyses']);
        Route::get('/api/analyses/{id}', [App\Http\Controllers\Chief\AnalyticController::class, 'getAnalysis']);
        Route::get('/api/action-plan-checklists', [App\Http\Controllers\Chief\AnalyticController::class, 'getActionPlanChecklists']);
        Route::patch('/api/action-plan-checklists/{id}/confirm', [App\Http\Controllers\Chief\AnalyticController::class, 'confirmActionPlanChecklist']);
        Route::patch('/api/action-plan-checklists/{id}/items', [App\Http\Controllers\Chief\AnalyticController::class, 'updateChecklistItemReview']);
        Route::get('/api/recommendation-suggestions', [App\Http\Controllers\Chief\AnalyticController::class, 'getRecommendationSuggestions']);
    
        // });
        // Route::post('/markers', [AdminController::class, 'store'])->name('admin.markers.store');
        Route::get('/marker-categories', [MarkerCategoryController::class, 'index']);
        // Route::get('/geticons', [IconController::class, 'getIcons'])->name('geticon');
        Route::get('/announcements', [StationAdminController::class, 'announcement'])->name('announcements');
        Route::get('/posts/get', [PostController::class, 'getPosts'])->name('posts.get');
        Route::post('/posts/store', [PostController::class, 'store'])->name('posts.store');
        Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
        Route::post('/posts/{post}/like', [PostController::class, 'like'])->name('posts.like');
        Route::post('/posts/{post}/comment', [PostController::class, 'comment'])->name('posts.comment');
        Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');


        //delete officer on a station
        Route::post('/station-officers', [StationOfficerController::class, 'store'])->name('station-officers.store');
        Route::delete('/station-officer/{id}', [StationOfficerController::class, 'destroy'])->name('station-officer.destroy');
        Route::patch('/station-officers/{id}/status', [StationOfficerController::class, 'updateStatus'])->name('station-officer.updateStatus');
        Route::delete('/users/{id}', [StationOfficerController::class, 'destroyUser'])->name('users.destroy');
        Route::put('/users/{id}', [StationOfficerController::class, 'updateUser'])->name('users.update');
        Route::put('/officers/{id}', [StationOfficerController::class, 'updateStationOfficer'])->name('officers.update');
        
        Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
        // Route::get('/resource', function(){
        //     return view('admin.resource-management');
        // });
        Route::resource('equipment-category', EquipmentCategoryController::class);
        // Route::post('equipment-category', [EquipmentCategoryController::class, 'store']);

        Route::resource('equipment', EquipmentController::class);
        Route::resource('incidents', IncidentController::class);
        Route::resource('resource-management', App\Http\Controllers\Chief\ResourceManagementController::class);
        Route::post('/resource-management/assign-equipment', [App\Http\Controllers\Chief\ResourceManagementController::class, 'assignEquipmentToStation'])
            ->name('resource-management.assign-equipment');
    
    });


    // Route::middleware(['role:ADMIN'])
    //             ->get('/admin/dashboard', function () {
    //                 return view('admin.dashboard');
    //             });
    // Route::middleware(['role:FIREFIGHTER'])
    //             ->get('/firefighter/dashboard', function () {
    //                 return view('firefighters.dashboard');
    // });

    // Route::middleware(['role:FIREFIGHTER'])->prefix('firefighter')->name('firefighter.')->group(function () {
    //     Route::get('/dashboard', [FirefighterController::class, 'index'])->name('dashboard'); // CORRECT
    //     Route::get('/incidents', [FirefighterController::class, 'viewAll'])->name('incidents.all');

    // });
    Route::middleware(['role:firefighter'])->prefix('firefighter')->name('firefighter.')->group(function () {
    // Route::get('/incidents', [App\Http\Controllers\Firefighter\IncidentController::class, 'index'])->name('incidents');
    // Route::post('/incidents/{incidentId}/accept', [App\Http\Controllers\Firefighter\IncidentController::class, 'accept'])->name('incidents.accept');
    // Route::put('/incidents/{incidentId}/status', [App\Http\Controllers\Firefighter\IncidentController::class, 'updateStatus'])->name('incidents.status');
    // Route::get('/incidents/{incidentId}/details', [App\Http\Controllers\Firefighter\IncidentController::class, 'details'])->name('incidents.details');
        Route::get('/dashboard', [FirefighterController::class, 'index'])->name('dashboard');
        Route::get('/incident-view', [FirefighterController::class, 'incidentView'])->name('view-this-page');
        Route::get('/my-station', [FirefighterController::class, 'myStation'])->name('my-station');
        Route::get('/equipments', [FirefighterController::class, 'equipments'])->name('equipments');
        Route::get('/fsic-requests', [FirefighterController::class, 'equipments'])->name('fsic-requests');
        Route::get('/api/fsic-requests/{id}', [FirefighterController::class, 'getAssignedFsicRequest'])->name('fsic-requests.show');
        Route::post('/api/fsic-requests/{id}/inspect', [FirefighterController::class, 'submitFsicInspection'])->name('fsic-requests.inspect');
        Route::get('/schedule', [FirefighterController::class, 'schedule'])->name('schedule');
        Route::get('/certifications', [FirefighterController::class, 'certifications'])->name('certifications');
        Route::get('/training', [FirefighterController::class, 'training'])->name('training');
        Route::get('/my-profile', [FirefighterController::class, 'myProfile'])->name('my-profile');
        Route::put('/my-profile', [FirefighterController::class, 'updateMyProfile'])->name('my-profile.update');


        Route::get('/assigned/{id}',[FirefighterController::class, 'getAssignedIncidents'])->name('assigned');
        Route::get('/incidents', [FirefighterController::class, 'incidentView'])->name('incidents');
        Route::get('/api/incidents/registry', [FirefighterController::class, 'incidentRegistryData'])->name('incidents.registry');

        


        Route::get('/incident', [FirefighterController::class, 'viewAll'])->name('incidents.all');
        Route::get('/announcements', [ App\Http\Controllers\staff\PostController::class, 'index'])->name('announcements');
        Route::get('/posts/get', [App\Http\Controllers\staff\PostController::class, 'getPosts'])->name('posts.get');
        Route::post('/posts/store', [App\Http\Controllers\staff\PostController::class, 'store'])->name('posts.store');
        Route::put('/posts/{post}', [App\Http\Controllers\staff\PostController::class, 'update'])->name('posts.update');
        Route::post('/posts/{post}/like', [App\Http\Controllers\staff\PostController::class, 'like'])->name('posts.like');
        Route::post('/posts/{post}/comment', [App\Http\Controllers\staff\PostController::class, 'comment'])->name('posts.comment');
        Route::delete('/posts/{post}', [App\Http\Controllers\staff\PostController::class, 'destroy'])->name('posts.destroy');


    });  

 Route::middleware(['role:citizen'])->prefix('citizen')->name('citizen.')->group(function () {
        Route::get('/mobile-hub', [PublicController::class, 'mobileHub'])->name('mobile-hub');
        Route::get('/dashboard/{section?}', [PublicController::class, 'index'])
            ->where('section', implode('|', PublicController::citizenDashboardSections()))
            ->name('dashboard');
        Route::put('/incidents/{incidentId}/update', [IncidentController::class, 'updateCitizenReport'])->name('incidents.update-report');
        Route::put('/profile', [PublicController::class, 'updateProfile'])->name('profile.update');
        Route::resource('incident', IncidentController::class);
        Route::resource('marker', MarkerController::class);



        Route::get('/fsic', [FsicRequestController::class, 'index'])->name('fsic.index');
        Route::post('/api/fsic/request', [FsicRequestController::class, 'store']);
        Route::get('/api/fsic/{id}', [FsicRequestController::class, 'show']);
        Route::put('/api/fsic/{id}', [FsicRequestController::class, 'update']);
        Route::delete('/api/fsic/{id}', [FsicRequestController::class, 'destroy']);
        Route::post('/api/fsic/{id}/claim', [FsicRequestController::class, 'claim']);
        Route::get('/api/fsic/my-requests', [FsicRequestController::class, 'myRequests']);
    });

    Route::middleware(['role:barangay,barangay captain'])->prefix('barangay')->name('barangay.')->group(function () {
        Route::get('/dashboard', [BarangayDashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('/hazards', [BarangayDashboardController::class, 'hazards'])->name('hazards');
        Route::get('/risk-map', [BarangayDashboardController::class, 'riskMap'])->name('risk-map');
        Route::post('/risk-map', [BarangayDashboardController::class, 'storeRiskArea'])->name('risk-map.store');
        Route::get('/patrols', [BarangayDashboardController::class, 'patrols'])->name('patrols');
        Route::get('/assessment', [BarangayDashboardController::class, 'assessment'])->name('assessment');
        Route::get('/reports', [BarangayDashboardController::class, 'reports'])->name('reports');
        Route::post('/reports/fire', [BarangayDashboardController::class, 'storeFireReport'])->name('reports.fire.store');
        Route::post('/reports/quick', [BarangayDashboardController::class, 'storeQuickFireReport'])->name('reports.quick');
        Route::get('/awareness', [BarangayDashboardController::class, 'awareness'])->name('awareness');
        Route::get('/profile', [BarangayDashboardController::class, 'profile'])->name('profile');
        Route::put('/profile', [BarangayDashboardController::class, 'updateProfile'])->name('profile.update');
    });
        // Route::middleware(['role:ADMIN,STATION ADMIN'])
        //     ->get('/management', function () {
        //         return view('management.dashboard');
        //     });
    // Logout route (accessible to all authenticated users)
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
// Add this at the BOTTOM of your web.php
    // Route::get('/logins', [LoginController::class, 'index'])->name('login');

// Route::get('/maps', [MapController::class, 'index'])->name('maps.show');
// Add this route to your web.php


// Route::middleware(['auth'])->group(function () {
//     Route::get('/admin/analytics', function () {
//         return view('admin.analytics');
//     })->name('admin.analytics');
    
//     // API endpoint for AI predictions
//     Route::post('/api/predictive-analysis', [App\Http\Controllers\AnalyticsController::class, 'runAnalysis'])->name('api.analytics');
// });
// Route::get('/', [PublicController::class, 'index'])->name('home');



// Route::prefix('admin')->group(function () {
//     Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
//     Route::get('/maps', [AdminController::class, 'maps'])->name('admin.maps');
//     // Route::post('/markers', [AdminController::class, 'store'])->name('admin.markers.store');
//     Route::get('/marker-categories', [MarkerCategoryController::class, 'index']);
//     Route::get('/geticons', [IconController::class, 'getIcons'])->name('geticon');
// });

// Route::prefix('user')->group(function(){
//    Route::get('/dashboard', [PublicController::class, 'index'])->name('citizen.dashboard');
// });

// Route::get('/map', [MarkerController::class, 'index']) ->name('map');
// Route::post('/markers', [MarkerController::class, 'store'])->name('markers.store');
// Route::put('/markers/{marker}', [MarkerController::class, 'update'])->name('markers.update');
// Route::delete('/markers/{marker}', [MarkerController::class, 'destroy'])->name('markers.destroy');
