<?php
use App\Http\Controllers\AdminController;
// use App\Http\Controllers\MarkerController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PublicController;
// use App\Http\Controllers\MapController;
use App\Http\Controllers\IconController;
use App\Http\Controllers\IncidentController;
// use App\Http\Controllers\ShapeController;
// use App\Http\Controllers\MarkController;
use App\Http\Controllers\MultimediaController;
// use App\Http\Controllers\AnalyticsController; // ADD THIS LINE
use App\Http\Controllers\Admin\AnalyticsController;

use App\Http\Controllers\Admin\PostController;
// use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Api\MarkerController;
use App\Http\Controllers\MarkerController as ManagedMarkerController;
use App\Http\Controllers\staff\FirefighterController;
use App\Http\Controllers\staff\FirefighterV3Controller;
use App\Http\Controllers\staff\FinalInvestigationFirExportController;
use App\Http\Controllers\staff\ProgressInvestigationPirExportController;
use App\Http\Controllers\staff\AfterActivityReportController;
use App\Http\Controllers\staff\AfterActivityReportExportController;
use App\Http\Controllers\Chief\AfterActivityReportReviewController;
use App\Http\Controllers\staff\SpotInvestigationSirExportController;
use App\Http\Controllers\Admin\ManagementController;

use App\Http\Controllers\StationOfficerController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserLogController;
use App\Http\Controllers\VoiceCallController;
use App\Http\Controllers\FirefighterIncidentResponseController;
use App\Http\Controllers\IncidentCommandDispatchController;

//stationadmin
use App\Http\Controllers\Chief\StationAdminController;
use App\Http\Controllers\Barangay\BarangayDashboardController;
use App\Http\Controllers\BarangayController;
use App\Http\Controllers\BoundaryLocationController;
use App\Http\Controllers\StationMediaController;


// use App\Http\Controllers\Auth\StationLoginController;
use App\Http\Controllers\FsicRequestController;

use App\Http\Controllers\MarkerCategoryController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\LandingMediaController;
use App\Http\Controllers\RiskAreaReviewController;



use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\EquipmentCategoryController;
use App\Http\Controllers\EquipmentController;
// use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\OTPController;

// use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;

Route::get('/verify/after-activity-reports/{afterActivityReport}', [AfterActivityReportExportController::class, 'verify'])
    ->middleware('signed')
    ->name('after-activity-reports.verify');

Route::middleware(['auth', 'role:stationadmin,chief,provincial admin'])
    ->prefix('command')
    ->name('command.')
    ->group(function () {
        Route::get('/incidents/{incident}/dispatch-options', [IncidentCommandDispatchController::class, 'options'])->name('incidents.dispatch-options');
        Route::post('/incidents/{incident}/reassign', [IncidentCommandDispatchController::class, 'reassign'])->name('incidents.reassign');
        Route::post('/incidents/{incident}/external-response', [IncidentCommandDispatchController::class, 'externalResponse'])->name('incidents.external-response');
    });
//note kailangan ko pang import ung controller para magamit ko yung mga functions nila sa routes
Route::get('/build/assets/map-{hash}.js', function () {
    return redirect(\Illuminate\Support\Facades\Vite::asset('resources/js/map.js'));
})->where('hash', '[A-Za-z0-9_-]+');

    Route::middleware('redirect.if.authenticated')->group(function () {
        Route::get('/', [PublicController::class, 'homepage'])->name('home');
        Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
        Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
        Route::get('/login',[LoginController::class, 'index'])->name('auth.login');
        Route::get('/auth/session-refresh', [LoginController::class, 'refreshLoginSession'])->name('auth.session-refresh');
        Route::post('/auth', [LoginController::class, 'authenticate'])->middleware('throttle:10,1')->name('auth.authenticate');
        Route::get('/forgot-password', [LoginController::class, 'showForgotPasswordForm'])->name('password.request');
        Route::post('/forgot-password/search', [LoginController::class, 'searchPasswordRecoveryAccount'])->middleware('throttle:5,1')->name('password.lookup');
        Route::post('/forgot-password/send-code', [LoginController::class, 'sendPasswordResetOtp'])->middleware('throttle:3,5')->name('password.email');
        Route::get('/reset-password', [LoginController::class, 'showResetPasswordForm'])->name('password.reset.form');
        Route::post('/reset-password', [LoginController::class, 'resetPasswordWithOtp'])->middleware('throttle:5,1')->name('password.update');
        Route::get('/auth', fn () => redirect()->route('auth.login'));
        Route::get('/auth/google/redirect', [LoginController::class, 'redirectToGoogle'])->name('auth.google.redirect');
        Route::get('/auth/google/callback', [LoginController::class, 'handleGoogleCallback'])->name('auth.google.callback');
        Route::get('/auth/google/verify', [LoginController::class, 'showGoogleVerificationForm'])->name('auth.google.verify');
        Route::post('/auth/google/verify', [LoginController::class, 'verifyGoogleOtp'])->middleware('throttle:10,1')->name('auth.google.verify.submit');
        Route::post('/auth/google/resend-otp', [LoginController::class, 'resendGoogleOtp'])->middleware('throttle:3,5')->name('auth.google.resend-otp');
    });

    Route::get('/pwa/start', [PublicController::class, 'pwaQuickReport'])->name('pwa.start');
    Route::get('/pwa/quick-report', [PublicController::class, 'pwaQuickReport'])->name('pwa.quick-report');

    // Registration routes with OTP
// Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register/send-otp', [RegisterController::class, 'sendOTP'])->middleware('throttle:5,1')->name('register.send-otp');
Route::post('/register/verify-otp', [RegisterController::class, 'verifyOTPAndRegister'])->middleware('throttle:10,1')->name('register.verify-otp');
Route::post('/register/resend-otp', [RegisterController::class, 'resendOTP'])->middleware('throttle:3,5')->name('register.resend-otp');
Route::get('/api/recent-incidents', [IncidentController::class, 'getRecentIncident'])
    ->middleware('auth');

Route::middleware(['auth', 'prevent.back.history', 'role:provincial admin'])->get('/test-api-key', function() {
    abort_unless(app()->environment('local'), 404);
    $apiKey = env('OPENROUTER_API_KEY');
    
    // Test with a simple models list request (this endpoint exists)
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json',
    ])->get('https://openrouter.ai/api/v1/models');
    
    return response()->json([
        'api_key_exists' => !empty($apiKey),
        'response_status' => $response->status(),
        'is_valid' => $response->status() === 200,
        'models_count' => $response->successful() ? count($response->json()['data'] ?? []) : 0,
        'error' => $response->status() !== 200 ? 'The provider rejected the diagnostic request.' : null
    ]);
});
    // Route::get('/register', [RegisterController::class,'show'])->name('register');
    // Route::post('/register', [RegisterController::class,'register'])->name('register.store');
    // AJAX routes for address selection
    Route::get('/api/cities/{provinceId}', [RegisterController::class, 'getCities'])->name('api.cities');
    Route::get('/api/barangays/{cityId}', [RegisterController::class, 'getBarangays'])->name('api.barangays');
Route::get('/api/postal-codes/{cityId}', [RegisterController::class, 'getPostalCodes'])->name('api.postal-codes');
Route::get('/api/default-postal/{cityId}', [RegisterController::class, 'getDefaultPostalCode'])->name('api.default-postal');
Route::post('/voice-calls/anonymous/session', [VoiceCallController::class, 'bootstrapAnonymousSession'])
    ->middleware('throttle:20,1')
    ->name('voice-calls.anonymous-session');
Route::post('/anonymous/incidents', [IncidentController::class, 'storeAnonymous'])
    ->middleware('throttle:5,1')
    ->name('anonymous.incidents.store');

Route::middleware(['auth', 'prevent.back.history', 'role:stationadmin,chief,provincial admin'])
    ->prefix('landing-content')
    ->name('landing-media.')
    ->group(function () {
        Route::get('/', [LandingMediaController::class, 'index'])->name('index');
        Route::post('/', [LandingMediaController::class, 'store'])->name('store');
        Route::put('/{landingMedia}', [LandingMediaController::class, 'update'])->name('update');
        Route::patch('/{landingMedia}/visibility', [LandingMediaController::class, 'toggle'])->name('toggle');
        Route::delete('/{landingMedia}', [LandingMediaController::class, 'destroy'])->name('destroy');
    });

    //locations based
Route::get('/api/firefighter/locations', [FirefighterController::class, 'getAllLocations'])
    ->middleware(['auth', 'role:firefighter,stationadmin,chief,provincial admin'])
    ->name('firefighter-locations.index');

    // Route::get('/', [LandingPageController::class, 'index'])->name('landing');
    Route::get('/api/stations', [LandingPageController::class, 'getStations'])->name('api.stations');
    Route::get('/api/hydrants', [LandingPageController::class, 'getHydrants'])->name('api.hydrants');
    Route::get('/api/public-map-resources', [LandingPageController::class, 'getPublicMapResources'])->name('api.public-map-resources');
    Route::middleware(['auth', 'prevent.back.history', 'role:firefighter'])->get('/ff', function (){
        return view('firefighters.dashboard');
    });


// This route should be outside the auth middleware
Route::get('/check-auth', function() {
    $user = Auth::user();
    $role = strtolower($user?->role->role ?? '');

    $redirectTo = match ($role) {
        'admin' => route('admin.dashboard'),
        'stationadmin', 'chief' => route('station-admin.dashboard'),
        'firefighter' => route('firefighter.dashboard'),
        'barangay', 'barangay captain', 'barangay officer', 'barangay_officer' => route('barangay.dashboard'),
        'citizen' => route('citizen.dashboard'),
        default => null,
    };

    return response()->json([
        'authenticated' => Auth::check(),
        'redirect_to' => $redirectTo,
    ]);
});
Route::middleware('auth')->post('/citizen/marker', [MarkerController::class, 'store']);
// Route::get('/register',[LoginController::class, 'register'])->name('auth.register');
//register infos
Route::middleware(['auth', 'role:admin,provincial admin,stationadmin,chief,firefighter'])->group(function () {
    Route::post('/multimedia/store', [MultimediaController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('multimedia.store');
    Route::get('/multimedia', [MultimediaController::class, 'index'])->name('multimedia.index');
});
Route::middleware(['auth', 'prevent.back.history', 'role:provincial admin'])->get('/weather', function(){
    return view('admin.weather');
});

Route::middleware(['auth', 'prevent.back.history', 'role:provincial admin'])->get('/debug-incidents', function() {
    abort_unless(app()->environment('local'), 404);
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


Route::middleware(['auth', 'prevent.back.history', 'role:provincial admin'])->get('/test-db', function() {
    abort_unless(app()->environment('local'), 404);
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
    Route::get('/map/markers/feed', [MarkerController::class, 'index'])->name('map.markers.feed');
    Route::get('/map/markers/latest', [MarkerController::class, 'latestForViewer'])->name('map.markers.latest');
    Route::middleware(['role:admin,provincial admin,stationadmin,chief'])->group(function () {
        Route::post('/map/markers', [ManagedMarkerController::class, 'store'])->name('map.markers.store');
        Route::delete('/map/markers/{markerId}', [ManagedMarkerController::class, 'destroy'])->name('map.markers.destroy');
        Route::post('/map/marker-details/incidents', [IncidentController::class, 'store'])->name('map.marker-details.incidents.store');
        Route::post('/map/marker-details/full-addresses', [AdminController::class, 'storeFullAddress'])->name('map.marker-details.addresses.store');
        Route::post('/map/marker-details/stations', [\App\Http\Controllers\StationController::class, 'store'])->name('map.marker-details.stations.store');
        Route::post('/map/marker-details/hydrants', [\App\Http\Controllers\HydrantController::class, 'store'])->name('map.marker-details.hydrants.store');
        Route::post('/map/marker-details/barangay-halls', [\App\Http\Controllers\BarangayHallController::class, 'store'])->name('map.marker-details.barangay-halls.store');
    });

    Route::prefix('voice-calls')->name('voice-calls.')->group(function () {
        Route::post('/', [VoiceCallController::class, 'store'])->name('store');
        Route::get('/incoming', [VoiceCallController::class, 'incoming'])->name('incoming');
        Route::get('/stream', [VoiceCallController::class, 'stream'])->name('stream');
        Route::post('/callbacks', [VoiceCallController::class, 'callback'])->name('callback');
        Route::get('/callbacks/incoming', [VoiceCallController::class, 'incomingCallback'])->name('callbacks.incoming');
        Route::get('/{voiceCall}', [VoiceCallController::class, 'show'])->name('show');
        Route::post('/{voiceCall}/seen', [VoiceCallController::class, 'markSeen'])->name('seen');
        Route::post('/{voiceCall}/accept', [VoiceCallController::class, 'accept'])->name('accept');
        Route::post('/{voiceCall}/accept-callback', [VoiceCallController::class, 'acceptCallback'])->name('accept-callback');
        Route::post('/{voiceCall}/reject-callback', [VoiceCallController::class, 'rejectCallback'])->name('reject-callback');
        Route::post('/{voiceCall}/reject', [VoiceCallController::class, 'reject'])->name('reject');
        Route::post('/{voiceCall}/end', [VoiceCallController::class, 'end'])->name('end');
        Route::post('/{voiceCall}/hold', [VoiceCallController::class, 'hold'])->name('hold');
        Route::post('/{voiceCall}/resume', [VoiceCallController::class, 'resume'])->name('resume');
        Route::post('/{voiceCall}/create-incident', [VoiceCallController::class, 'createIncident'])->name('create-incident');
        Route::post('/{voiceCall}/flag-prank', [VoiceCallController::class, 'flagPrank'])->name('flag-prank');
        Route::post('/{voiceCall}/token', [VoiceCallController::class, 'token'])->name('token');
    });

    Route::get('/user-logs', [UserLogController::class, 'index'])->name('user-logs.index');
    Route::get('/user-logs/all', [UserLogController::class, 'all'])->name('user-logs.all');
    Route::post('/user-logs/action', [UserLogController::class, 'store'])->name('user-logs.store');
    Route::middleware(['role:citizen'])->group(function () {
        Route::get('/citizen/dashboard-data', [PublicController::class, 'dashboardData'])->name('citizen.dashboard-data');
        Route::get('/citizen/api/announcements', [App\Http\Controllers\staff\PostController::class, 'getPosts'])->name('citizen.announcements.feed');
        Route::post('/citizen/api/announcements/{post}/like', [App\Http\Controllers\staff\PostController::class, 'like'])->name('citizen.announcements.like');
        Route::post('/citizen/api/announcements/{post}/comment', [App\Http\Controllers\staff\PostController::class, 'comment'])->name('citizen.announcements.comment');
        Route::put('/citizen/api/announcements/{post}/comment/{comment}', [App\Http\Controllers\staff\PostController::class, 'updateComment'])->name('citizen.announcements.comment.update');
        Route::delete('/citizen/api/announcements/{post}/comment/{comment}', [App\Http\Controllers\staff\PostController::class, 'destroyComment'])->name('citizen.announcements.comment.destroy');
    });
        // Analytics page
    // Route::get('/admin/analytics', [AnalyticsController::class, 'index'])->name('admin.analytics');
    
    // API endpoints for analytics
    // Route::post('/api/predictive-analysis', [AnalyticsController::class, 'runAnalysis'])->name('api.analytics');
    // Route::get('/api/historical-analyses', [AnalyticsController::class, 'getHistoricalAnalyses'])->name('api.historical');

     Route::middleware(['role:provincial admin'])->prefix('admin')->name('admin.')->group(function () {

        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/api/dashboard-stats', [AdminController::class, 'dashboardStats'])->name('dashboard-stats');
        Route::get('/maps', [AdminController::class, 'maps'])->name('maps');
        Route::get('/alerts',[AdminController::class, 'alerts'])->name('alerts');
        Route::get('/reports',[AdminController::class, 'reports'])->name('reports');
        Route::get('/spot-reports/{spotReportId}/sir.docx', [SpotInvestigationSirExportController::class, 'downloadWord'])->name('spot-reports.sir.word');
        Route::get('/progress-reports/{progressReportId}/pir.docx', [ProgressInvestigationPirExportController::class, 'downloadWord'])->name('progress-reports.pir.word');
        Route::get('/final-reports/{finalReportId}/fir.docx', [FinalInvestigationFirExportController::class, 'downloadWord'])->name('final-reports.fir.word');
        Route::get('/after-activity-reports', [AfterActivityReportReviewController::class, 'index'])->name('after-activity-reports.index');
        Route::patch('/after-activity-reports/{afterActivityReport}/review', [AfterActivityReportReviewController::class, 'review'])->name('after-activity-reports.review');
        Route::get('/after-activity-reports/{afterActivityReport}/aar.pdf', [AfterActivityReportExportController::class, 'downloadPdf'])->name('after-activity-reports.pdf');
        Route::get('/mitigations', [App\Http\Controllers\Chief\StationAdminController::class, 'mitigations'])->name('mitigations');
        Route::post('/mitigations/suggestions/refresh', [App\Http\Controllers\Chief\StationAdminController::class, 'refreshGeneratedMitigationDraftRecommendations'])->name('mitigations.suggestions.refresh');
        Route::post('/mitigations/checklist-draft', [App\Http\Controllers\Chief\StationAdminController::class, 'generateMitigationDraftFromChecklist'])->name('mitigations.checklist-draft');
        Route::post('/mitigations', [App\Http\Controllers\Chief\StationAdminController::class, 'storeMitigation'])->name('mitigations.store');
        Route::post('/mitigations/generated-drafts/{checklistId}/approve', [App\Http\Controllers\Chief\StationAdminController::class, 'approveGeneratedMitigationDraft'])->name('mitigations.generated-drafts.approve');
        Route::post('/mitigations/generated-drafts/{checklistId}/reject', [App\Http\Controllers\Chief\StationAdminController::class, 'rejectGeneratedMitigationDraft'])->name('mitigations.generated-drafts.reject');
        Route::put('/mitigations/{mitigationActionId}', [App\Http\Controllers\Chief\StationAdminController::class, 'updateMitigation'])->name('mitigations.update');
        Route::delete('/mitigations/{mitigationActionId}', [App\Http\Controllers\Chief\StationAdminController::class, 'destroyMitigation'])->name('mitigations.destroy');
        Route::get('/schedule', [AdminController::class, 'schedule'])->name('schedule');
        Route::post('/schedule/events', [App\Http\Controllers\Chief\StationAdminController::class, 'storeScheduleEvent'])->name('schedule.events.store');
        Route::put('/schedule/events/{eventActivity}', [App\Http\Controllers\Chief\StationAdminController::class, 'updateScheduleEvent'])->name('schedule.events.update');
        Route::delete('/schedule/events/{eventActivity}', [App\Http\Controllers\Chief\StationAdminController::class, 'destroyScheduleEvent'])->name('schedule.events.destroy');
        Route::get('/risk-reviews', [RiskAreaReviewController::class, 'adminIndex'])->name('risk-reviews.index');
        Route::post('/risk-reviews', [RiskAreaReviewController::class, 'adminStore'])->name('risk-reviews.store');
        Route::patch('/risk-reviews/{riskAreaId}', [RiskAreaReviewController::class, 'adminReview'])->name('risk-reviews.review');
        Route::patch('/risk-reviews/{riskAreaId}/active', [RiskAreaReviewController::class, 'adminUpdateActive'])->name('risk-reviews.active.update');
        Route::delete('/risk-reviews/{riskAreaId}/active', [RiskAreaReviewController::class, 'adminDeleteActive'])->name('risk-reviews.active.delete');
        Route::post('/community-activity-requests/{requestId}/approve', [App\Http\Controllers\CommunityActivityRequestController::class, 'approve'])->name('community-activity-requests.approve');
        Route::post('/community-activity-requests/{requestId}/reject', [App\Http\Controllers\CommunityActivityRequestController::class, 'reject'])->name('community-activity-requests.reject');
         

            
        Route::get('/api/stations', [App\Http\Controllers\Admin\AnalyticsController::class, 'getStations']);
        Route::get('/api/station-analytics/{stationId}', [App\Http\Controllers\Admin\AnalyticsController::class, 'analyzeStation']);
        // Route::get('/aanalytics', function () {
        //         return view('admin.analytics');
        //     })->name('analytics');

        Route::post('/api/explore-recommendation', [App\Http\Controllers\Chief\AnalyticController::class, 'exploreRecommendation']);
        Route::get('/analytics', [App\Http\Controllers\Chief\AnalyticController::class, 'index'])->name('analytics');
        Route::get('/analytics/accuracy.csv', [App\Http\Controllers\Chief\AnalyticController::class, 'exportAccuracyCsv'])->name('analytics.accuracy.csv');
        Route::get('/analytics/accuracy.pdf', [App\Http\Controllers\Chief\AnalyticController::class, 'exportAccuracyPdf'])->name('analytics.accuracy.pdf');
        Route::get('/analytics/analysis.pdf', [App\Http\Controllers\Chief\AnalyticController::class, 'exportAnalysisPdf'])->name('analytics.analysis.pdf');
        Route::get('/api/command-notifications', [App\Http\Controllers\CommandNotificationController::class, 'index'])->name('command-notifications.index');
        Route::post('/api/command-notifications/read-all', [App\Http\Controllers\CommandNotificationController::class, 'markAllAsRead'])->name('command-notifications.read-all');
        Route::post('/api/command-notifications/{notificationId}/read', [App\Http\Controllers\CommandNotificationController::class, 'markAsRead'])->name('command-notifications.read');
        Route::get('/course-checklists', [App\Http\Controllers\Chief\AnalyticController::class, 'checklistIndex'])->name('course-checklists');
        Route::post('/api/action-plan-checklists', [App\Http\Controllers\Chief\AnalyticController::class, 'storeActionPlanChecklist'])->name('action-plan-checklists.store');
        Route::put('/api/action-plan-checklists/{id}', [App\Http\Controllers\Chief\AnalyticController::class, 'updateActionPlanChecklist'])->name('action-plan-checklists.update');
        Route::delete('/api/action-plan-checklists/{id}', [App\Http\Controllers\Chief\AnalyticController::class, 'destroyActionPlanChecklist'])->name('action-plan-checklists.destroy');
        Route::get('/api/stations-for-analytics', [App\Http\Controllers\Chief\AnalyticController::class, 'getStationsForAnalytics']);
        Route::get('/api/analyses/latest-snapshot', [App\Http\Controllers\Chief\AnalyticController::class, 'getLatestAnalysisSnapshot']);
        Route::get('/api/historical-weather-preview', [App\Http\Controllers\Chief\AnalyticController::class, 'previewHistoricalWeatherArchive']);
        Route::post('/api/historical-weather-save', [App\Http\Controllers\Chief\AnalyticController::class, 'saveHistoricalWeatherArchive']);
        Route::post('/api/ai-predictive-analysis', [App\Http\Controllers\Chief\AnalyticController::class, 'runAIPredictiveAnalysis']);
        Route::post('/api/retrain-forecast-model', [App\Http\Controllers\Chief\AnalyticController::class, 'retrainForecastModel']);
        Route::get('/api/retrain-forecast-model-history', [App\Http\Controllers\Chief\AnalyticController::class, 'forecastModelTrainingHistory']);
        Route::get('/api/retrain-forecast-model/{trainingId}', [App\Http\Controllers\Chief\AnalyticController::class, 'forecastModelTrainingStatus']);
        Route::get('/api/analyses/history', [App\Http\Controllers\Chief\AnalyticController::class, 'getHistoricalAnalyses']);
        Route::get('/api/analyses/{id}/revisions', [App\Http\Controllers\Chief\AnalyticController::class, 'getAnalysisRevisions']);
        Route::post('/api/analyses/{id}/rollback', [App\Http\Controllers\Chief\AnalyticController::class, 'rollbackAnalysisRevision']);
        Route::get('/api/analyses/{id}', [App\Http\Controllers\Chief\AnalyticController::class, 'getAnalysis']);
        Route::get('/api/action-plan-checklists', [App\Http\Controllers\Chief\AnalyticController::class, 'getActionPlanChecklists'])->name('action-plan-checklists.index');
        Route::patch('/api/action-plan-checklists/{id}/confirm', [App\Http\Controllers\Chief\AnalyticController::class, 'confirmActionPlanChecklist'])->name('action-plan-checklists.confirm');
        Route::patch('/api/action-plan-checklists/{id}/items', [App\Http\Controllers\Chief\AnalyticController::class, 'updateChecklistItemReview'])->name('action-plan-checklists.items');
        Route::get('/api/action-plan-checklists/{id}/history', [App\Http\Controllers\Chief\AnalyticController::class, 'getChecklistHistory'])->name('action-plan-checklists.history');
        Route::patch('/api/action-plan-checklists/bulk', [App\Http\Controllers\Chief\AnalyticController::class, 'bulkUpdateActionPlanChecklists'])->name('action-plan-checklists.bulk');
        Route::get('/api/recommendation-suggestions', [App\Http\Controllers\Chief\AnalyticController::class, 'getRecommendationSuggestions']);
    
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
        Route::resource('management', App\Http\Controllers\Chief\ManagementController::class);
        Route::get('/management', [App\Http\Controllers\Chief\ManagementController::class,'index']) ->name('management');

        //delete officer on a station
        Route::post('/station-officers', [StationOfficerController::class, 'store'])->name('station-officers.store');
        Route::delete('/station-officer/{id}', [StationOfficerController::class, 'destroy'])->name('station-officer.destroy');
        Route::patch('/station-officers/{id}/status', [StationOfficerController::class, 'updateStatus'])->name('station-officer.updateStatus');
        Route::delete('/users/{id}', [StationOfficerController::class, 'destroyUser'])->name('users.destroy');
        Route::put('/users/{id}', [StationOfficerController::class, 'updateUser'])->name('users.update');
        Route::put('/officers/{id}', [StationOfficerController::class, 'updateStationOfficer'])->name('officers.update');
        Route::put('/stations/{station}', [App\Http\Controllers\Chief\ManagementController::class, 'updateStation'])->name('stations.update');
        Route::post('/stations/{station}/media/profile', [StationMediaController::class, 'storeProfile'])->name('stations.media.profile');
        Route::post('/stations/{station}/media/gallery', [StationMediaController::class, 'storeGallery'])->name('stations.media.gallery');
        Route::put('/station-media/{stationMedia}', [StationMediaController::class, 'update'])->name('station-media.update');
        Route::delete('/station-media/{stationMedia}', [StationMediaController::class, 'destroy'])->name('station-media.destroy');
        
        Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
        Route::put('/teams/{id}', [TeamController::class, 'update'])->name('teams.update');
        Route::patch('/teams/{id}/status', [TeamController::class, 'updateStatus'])->name('teams.status.update');
        Route::resource('equipment-category', EquipmentCategoryController::class);
        // Route::post('equipment-category', [EquipmentCategoryController::class, 'store']);

        Route::resource('equipment', EquipmentController::class);
        Route::resource('incidents', IncidentController::class);
        Route::resource('resource-management', App\Http\Controllers\Chief\ResourceManagementController::class);
        Route::post('/apparatus', [App\Http\Controllers\ApparatusController::class, 'store'])->name('apparatus.store');
        Route::put('/apparatus/{apparatus}', [App\Http\Controllers\ApparatusController::class, 'update'])->name('apparatus.update');
        Route::delete('/apparatus/{apparatus}', [App\Http\Controllers\ApparatusController::class, 'destroy'])->name('apparatus.destroy');
        Route::post('/users/register', [RegisterController::class, 'register'])->name('users.register');
        Route::post('/resource-management/assign-equipment', [App\Http\Controllers\Chief\ResourceManagementController::class, 'assignEquipmentToStation'])->name('resource-management.assign-equipment');
        Route::get('/fsic', [App\Http\Controllers\Chief\FsicManagementController::class, 'index'])->name('fsic');
        Route::post('/api/create-applicant', [App\Http\Controllers\Chief\FsicManagementController::class, 'createApplicant']);
        Route::post('/api/fsic', [App\Http\Controllers\Chief\FsicManagementController::class, 'store']);
        Route::get('/api/fsic/{id}', [App\Http\Controllers\Chief\FsicManagementController::class, 'show']);
        Route::post('/api/fsic/{id}/review', [App\Http\Controllers\Chief\FsicManagementController::class, 'review']);
        Route::post('/api/fsic/{id}/inspect', [App\Http\Controllers\Chief\FsicManagementController::class, 'inspect']);
        Route::post('/api/fsic/{id}/approve', [App\Http\Controllers\Chief\FsicManagementController::class, 'approve']);
        Route::get('/barangays', [BarangayController::class, 'index'])->name('barangays.index');
        Route::get('/barangays/create', [BarangayController::class, 'create'])->name('barangays.create');
        Route::post('/barangays', [BarangayController::class, 'store'])->name('barangays.store');
        Route::get('/barangays/{barangay}', [BarangayController::class, 'show'])->name('barangays.show');
        Route::put('/barangays/{barangay}', [BarangayController::class, 'update'])->name('barangays.update');
        Route::delete('/barangays/{barangay}', [BarangayController::class, 'destroy'])->name('barangays.destroy');
        Route::post('/barangay-officers', [App\Http\Controllers\Chief\BarangayOfficerController::class, 'store'])->name('barangay-officers.store');
        Route::put('/barangay-officers/{barangayOfficer}', [App\Http\Controllers\Chief\BarangayOfficerController::class, 'update'])->name('barangay-officers.update');
        Route::delete('/barangay-officers/{barangayOfficer}', [App\Http\Controllers\Chief\BarangayOfficerController::class, 'destroy'])->name('barangay-officers.destroy');
        Route::post('/barangay-boundaries/{barangayId}/sync', [BoundaryLocationController::class, 'importBarangayBoundary'])->name('barangay-boundaries.sync');
     });

    Route::middleware(['role:stationadmin,chief'])->prefix('station-admin')->name('station-admin.')->group(function () {

        Route::get('/dashboard', [StationAdminController::class, 'index'])->name('dashboard');
        // Route::get('/request', [StationAdminController::class, 'fsic'])->name('fsic');

        Route::get('/maps', [StationAdminController::class, 'maps'])->name('maps');
        Route::get('/alerts',[StationAdminController::class, 'alerts'])->name('alerts');
        Route::get('/reports',[StationAdminController::class, 'reports'])->name('reports');
        Route::get('/spot-reports/{spotReportId}/sir.docx', [SpotInvestigationSirExportController::class, 'downloadWord'])->name('spot-reports.sir.word');
        Route::get('/progress-reports/{progressReportId}/pir.docx', [ProgressInvestigationPirExportController::class, 'downloadWord'])->name('progress-reports.pir.word');
        Route::get('/final-reports/{finalReportId}/fir.docx', [FinalInvestigationFirExportController::class, 'downloadWord'])->name('final-reports.fir.word');
        Route::get('/after-activity-reports', [AfterActivityReportReviewController::class, 'index'])->name('after-activity-reports.index');
        Route::patch('/after-activity-reports/{afterActivityReport}/review', [AfterActivityReportReviewController::class, 'review'])->name('after-activity-reports.review');
        Route::get('/after-activity-reports/{afterActivityReport}/aar.pdf', [AfterActivityReportExportController::class, 'downloadPdf'])->name('after-activity-reports.pdf');
        Route::get('/mitigations', [StationAdminController::class, 'mitigations'])->name('mitigations');
        Route::get('/mitigations/table', [StationAdminController::class, 'mitigationTable'])->name('mitigations.table');
        Route::post('/mitigations/suggestions/refresh', [StationAdminController::class, 'refreshGeneratedMitigationDraftRecommendations'])->name('mitigations.suggestions.refresh');
        Route::post('/mitigations/checklist-draft', [StationAdminController::class, 'generateMitigationDraftFromChecklist'])->name('mitigations.checklist-draft');
        Route::post('/mitigations', [StationAdminController::class, 'storeMitigation'])->name('mitigations.store');
        Route::post('/mitigations/generated-drafts/{checklistId}/approve', [StationAdminController::class, 'approveGeneratedMitigationDraft'])->name('mitigations.generated-drafts.approve');
        Route::post('/mitigations/generated-drafts/{checklistId}/reject', [StationAdminController::class, 'rejectGeneratedMitigationDraft'])->name('mitigations.generated-drafts.reject');
        Route::put('/mitigations/{mitigationActionId}', [StationAdminController::class, 'updateMitigation'])->name('mitigations.update');
        Route::post('/mitigations/{mitigationActionId}/completion-review', [StationAdminController::class, 'reviewMitigationCompletion'])->name('mitigations.completion-review');
        Route::delete('/mitigations/{mitigationActionId}', [StationAdminController::class, 'destroyMitigation'])->name('mitigations.destroy');
        Route::get('/risk-reviews', [RiskAreaReviewController::class, 'chiefIndex'])->name('risk-reviews.index');
        Route::post('/risk-reviews', [RiskAreaReviewController::class, 'chiefStore'])->name('risk-reviews.store');
        Route::patch('/risk-reviews/{riskAreaId}', [RiskAreaReviewController::class, 'chiefReview'])->name('risk-reviews.review');
        Route::patch('/risk-reviews/{riskAreaId}/active', [RiskAreaReviewController::class, 'chiefUpdateActive'])->name('risk-reviews.active.update');
        Route::delete('/risk-reviews/{riskAreaId}/active', [RiskAreaReviewController::class, 'chiefDeleteActive'])->name('risk-reviews.active.delete');
        Route::get('/schedule', [StationAdminController::class, 'schedule'])->name('schedule');
        Route::post('/schedule/events', [StationAdminController::class, 'storeScheduleEvent'])->name('schedule.events.store');
        Route::put('/schedule/events/{eventActivity}', [StationAdminController::class, 'updateScheduleEvent'])->name('schedule.events.update');
        Route::delete('/schedule/events/{eventActivity}', [StationAdminController::class, 'destroyScheduleEvent'])->name('schedule.events.destroy');
        Route::post('/community-activity-requests/{requestId}/approve', [App\Http\Controllers\CommunityActivityRequestController::class, 'approve'])->name('community-activity-requests.approve');
        Route::post('/community-activity-requests/{requestId}/reject', [App\Http\Controllers\CommunityActivityRequestController::class, 'reject'])->name('community-activity-requests.reject');
        Route::get('/profile', [StationAdminController::class, 'profile'])->name('profile');
        Route::put('/profile', [StationAdminController::class, 'updateProfile'])->name('profile.update');
        Route::get('/api/dashboard-stats', [StationAdminController::class, 'dashboardStats'])->name('dashboard-stats');
        Route::get('/api/incidents/map', [StationAdminController::class, 'incidentMapData'])->name('incidents.map');
        Route::get('/api/incidents/map/changes', [StationAdminController::class, 'incidentMapChanges'])->name('incidents.map.changes');
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
        Route::get('/analytics/accuracy.csv', [App\Http\Controllers\Chief\AnalyticController::class, 'exportAccuracyCsv'])->name('analytics.accuracy.csv');
        Route::get('/analytics/accuracy.pdf', [App\Http\Controllers\Chief\AnalyticController::class, 'exportAccuracyPdf'])->name('analytics.accuracy.pdf');
        Route::get('/analytics/analysis.pdf', [App\Http\Controllers\Chief\AnalyticController::class, 'exportAnalysisPdf'])->name('analytics.analysis.pdf');
        Route::get('/api/command-notifications', [App\Http\Controllers\CommandNotificationController::class, 'index'])->name('command-notifications.index');
        Route::post('/api/command-notifications/read-all', [App\Http\Controllers\CommandNotificationController::class, 'markAllAsRead'])->name('command-notifications.read-all');
        Route::post('/api/command-notifications/{notificationId}/read', [App\Http\Controllers\CommandNotificationController::class, 'markAsRead'])->name('command-notifications.read');
        Route::get('/weather-context', [App\Http\Controllers\Chief\WeatherContextController::class, 'index'])->name('weather-context.index');
        Route::post('/weather-context/backfill', [App\Http\Controllers\Chief\WeatherContextController::class, 'backfill'])->name('weather-context.backfill');
        Route::get('/course-checklists', [App\Http\Controllers\Chief\AnalyticController::class, 'checklistIndex'])->name('course-checklists');
        Route::post('/api/action-plan-checklists', [App\Http\Controllers\Chief\AnalyticController::class, 'storeActionPlanChecklist'])->name('action-plan-checklists.store');
        Route::put('/api/action-plan-checklists/{id}', [App\Http\Controllers\Chief\AnalyticController::class, 'updateActionPlanChecklist'])->name('action-plan-checklists.update');
        Route::delete('/api/action-plan-checklists/{id}', [App\Http\Controllers\Chief\AnalyticController::class, 'destroyActionPlanChecklist'])->name('action-plan-checklists.destroy');
        Route::get('/api/stations-for-analytics', [App\Http\Controllers\Chief\AnalyticController::class, 'getStationsForAnalytics']);
        Route::get('/api/analyses/latest-snapshot', [App\Http\Controllers\Chief\AnalyticController::class, 'getLatestAnalysisSnapshot']);
        Route::get('/api/historical-weather-preview', [App\Http\Controllers\Chief\AnalyticController::class, 'previewHistoricalWeatherArchive']);
        Route::post('/api/historical-weather-save', [App\Http\Controllers\Chief\AnalyticController::class, 'saveHistoricalWeatherArchive']);
        Route::post('/api/ai-predictive-analysis', [App\Http\Controllers\Chief\AnalyticController::class, 'runAIPredictiveAnalysis']);
        Route::post('/api/retrain-forecast-model', [App\Http\Controllers\Chief\AnalyticController::class, 'retrainForecastModel']);
        Route::get('/api/retrain-forecast-model-history', [App\Http\Controllers\Chief\AnalyticController::class, 'forecastModelTrainingHistory']);
        Route::get('/api/retrain-forecast-model/{trainingId}', [App\Http\Controllers\Chief\AnalyticController::class, 'forecastModelTrainingStatus']);
        Route::get('/api/analyses/history', [App\Http\Controllers\Chief\AnalyticController::class, 'getHistoricalAnalyses']);
        Route::get('/api/analyses/{id}/revisions', [App\Http\Controllers\Chief\AnalyticController::class, 'getAnalysisRevisions']);
        Route::post('/api/analyses/{id}/rollback', [App\Http\Controllers\Chief\AnalyticController::class, 'rollbackAnalysisRevision']);
        Route::get('/api/analyses/{id}', [App\Http\Controllers\Chief\AnalyticController::class, 'getAnalysis']);
        Route::get('/api/action-plan-checklists', [App\Http\Controllers\Chief\AnalyticController::class, 'getActionPlanChecklists'])->name('action-plan-checklists.index');
        Route::patch('/api/action-plan-checklists/{id}/confirm', [App\Http\Controllers\Chief\AnalyticController::class, 'confirmActionPlanChecklist'])->name('action-plan-checklists.confirm');
        Route::patch('/api/action-plan-checklists/{id}/items', [App\Http\Controllers\Chief\AnalyticController::class, 'updateChecklistItemReview'])->name('action-plan-checklists.items');
        Route::get('/api/action-plan-checklists/{id}/history', [App\Http\Controllers\Chief\AnalyticController::class, 'getChecklistHistory'])->name('action-plan-checklists.history');
        Route::patch('/api/action-plan-checklists/bulk', [App\Http\Controllers\Chief\AnalyticController::class, 'bulkUpdateActionPlanChecklists'])->name('action-plan-checklists.bulk');
        Route::get('/api/recommendation-suggestions', [App\Http\Controllers\Chief\AnalyticController::class, 'getRecommendationSuggestions']);
    
        // });
        // Route::post('/markers', [AdminController::class, 'store'])->name('admin.markers.store');
        Route::get('/marker-categories', [MarkerCategoryController::class, 'index']);
        Route::get('/geticons', [IconController::class, 'getIcons'])->name('geticon');
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
        Route::put('/stations/{station}', [App\Http\Controllers\Chief\ManagementController::class, 'updateStation'])->name('stations.update');
        Route::post('/stations/{station}/media/profile', [StationMediaController::class, 'storeProfile'])->name('stations.media.profile');
        Route::post('/stations/{station}/media/gallery', [StationMediaController::class, 'storeGallery'])->name('stations.media.gallery');
        Route::put('/station-media/{stationMedia}', [StationMediaController::class, 'update'])->name('station-media.update');
        Route::delete('/station-media/{stationMedia}', [StationMediaController::class, 'destroy'])->name('station-media.destroy');
        
        Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
        Route::put('/teams/{id}', [TeamController::class, 'update'])->name('teams.update');
        Route::patch('/teams/{id}/status', [TeamController::class, 'updateStatus'])->name('teams.status.update');
        Route::resource('equipment-category', EquipmentCategoryController::class);
        // Route::post('equipment-category', [EquipmentCategoryController::class, 'store']);

        Route::resource('equipment', EquipmentController::class);
        Route::resource('incidents', IncidentController::class);
        Route::resource('resource-management', App\Http\Controllers\Chief\ResourceManagementController::class);
        Route::post('/apparatus', [App\Http\Controllers\ApparatusController::class, 'store'])->name('apparatus.store');
        Route::put('/apparatus/{apparatus}', [App\Http\Controllers\ApparatusController::class, 'update'])->name('apparatus.update');
        Route::delete('/apparatus/{apparatus}', [App\Http\Controllers\ApparatusController::class, 'destroy'])->name('apparatus.destroy');
        Route::post('/users/register', [RegisterController::class, 'register'])->name('users.register');
        Route::post('/barangay-officers', [App\Http\Controllers\Chief\BarangayOfficerController::class, 'store'])->name('barangay-officers.store');
        Route::put('/barangay-officers/{barangayOfficer}', [App\Http\Controllers\Chief\BarangayOfficerController::class, 'update'])->name('barangay-officers.update');
        Route::delete('/barangay-officers/{barangayOfficer}', [App\Http\Controllers\Chief\BarangayOfficerController::class, 'destroy'])->name('barangay-officers.destroy');
        Route::get('/barangays', [BarangayController::class, 'index'])->name('barangays.index');
        Route::get('/barangays/create', [BarangayController::class, 'create'])->name('barangays.create');
        Route::post('/barangays', [BarangayController::class, 'store'])->name('barangays.store');
        Route::get('/barangays/{barangay}', [BarangayController::class, 'show'])->name('barangays.show');
        Route::put('/barangays/{barangay}', [BarangayController::class, 'update'])->name('barangays.update');
        Route::delete('/barangays/{barangay}', [BarangayController::class, 'destroy'])->name('barangays.destroy');
        Route::post('/resource-management/assign-equipment', [App\Http\Controllers\Chief\ResourceManagementController::class, 'assignEquipmentToStation'])
            ->name('resource-management.assign-equipment');
        Route::post('/barangay-boundaries/{barangayId}/sync', [BoundaryLocationController::class, 'importBarangayBoundary'])
            ->name('barangay-boundaries.sync');
    
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
        Route::get('/api/my-station/overview', [FirefighterController::class, 'myStationOverviewData'])->name('my-station.overview');
        Route::get('/api/my-station/personnel', [FirefighterController::class, 'myStationPersonnelData'])->name('my-station.personnel');
        Route::get('/api/my-station/equipment', [FirefighterController::class, 'myStationEquipmentData'])->name('my-station.equipment');
        Route::get('/api/my-station/reports', [FirefighterController::class, 'myStationReportsData'])->name('my-station.reports');
        Route::get('/api/my-station/modal-data', [FirefighterController::class, 'myStationModalData'])->name('my-station.modal-data');
        Route::get('/api/notifications', [App\Http\Controllers\CommandNotificationController::class, 'index'])->name('notifications.index');
        Route::post('/api/notifications/read-all', [App\Http\Controllers\CommandNotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
        Route::post('/api/notifications/{notificationId}/read', [App\Http\Controllers\CommandNotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::get('/spot-reports/{spotReportId}/sir.docx', [SpotInvestigationSirExportController::class, 'downloadWord'])->name('spot-reports.sir.word');
        Route::get('/progress-reports/{progressReportId}/pir.docx', [ProgressInvestigationPirExportController::class, 'downloadWord'])->name('progress-reports.pir.word');
        Route::get('/final-reports/{finalReportId}/fir.docx', [FinalInvestigationFirExportController::class, 'downloadWord'])->name('final-reports.fir.word');
        Route::post('/after-activity-reports', [AfterActivityReportController::class, 'store'])->name('after-activity-reports.store');
        Route::put('/after-activity-reports/{afterActivityReport}', [AfterActivityReportController::class, 'update'])->name('after-activity-reports.update');
        Route::delete('/after-activity-reports/{afterActivityReport}', [AfterActivityReportController::class, 'destroy'])->name('after-activity-reports.destroy');
        Route::get('/after-activity-reports/{afterActivityReport}/aar.docx', [AfterActivityReportExportController::class, 'downloadWord'])->name('after-activity-reports.word');
        Route::get('/after-activity-reports/{afterActivityReport}/aar.pdf', [AfterActivityReportExportController::class, 'downloadPdf'])->name('after-activity-reports.pdf');
        Route::patch('/investigation-reports/{reportType}/{reportId}/proof-images/order', [App\Http\Controllers\staff\InvestigationReportImageController::class, 'reorder'])
            ->whereIn('reportType', ['spot', 'progress', 'final', 'aar'])
            ->name('investigation-reports.proof-images.order');
        Route::delete('/investigation-reports/{reportType}/{reportId}/proof-images/{imageId}', [App\Http\Controllers\staff\InvestigationReportImageController::class, 'destroy'])
            ->whereIn('reportType', ['spot', 'progress', 'final', 'aar'])
            ->name('investigation-reports.proof-images.destroy');
        Route::redirect('/equipments', '/firefighter/fsic');
        Route::redirect('/fsic-requests', '/firefighter/fsic');
        Route::get('/fsic', [FirefighterController::class, 'equipments'])->name('equipments');
        Route::get('/api/fsic-requests/{id}', [FirefighterController::class, 'getAssignedFsicRequest'])->name('fsic-requests.show');
        Route::post('/api/fsic-requests/{id}/inspect', [FirefighterController::class, 'submitFsicInspection'])->name('fsic-requests.inspect');
        Route::get('/schedule', [FirefighterController::class, 'schedule'])->name('schedule');
        Route::get('/api/schedule', [FirefighterController::class, 'scheduleData'])->name('schedule.data');
        Route::get('/api/dashboard', [FirefighterController::class, 'dashboardData'])->name('dashboard.data');
        Route::get('/api/dashboard/posts', [FirefighterController::class, 'dashboardPostsData'])->name('dashboard.posts');
        Route::post('/api/location', [FirefighterController::class, 'updateLocation'])->name('location.update');
        Route::post('/response-units/{unit}/apparatus-location', [App\Http\Controllers\ApparatusLocationController::class, 'update'])->name('response-units.apparatus-location');
        Route::post('/incidents/{incident}/respond', [FirefighterIncidentResponseController::class, 'respond'])->name('incidents.respond');
        Route::post('/incidents/{incident}/arrive', [FirefighterIncidentResponseController::class, 'arrive'])->name('incidents.arrive');
        Route::post('/incidents/{incident}/backup', [FirefighterIncidentResponseController::class, 'requestBackup'])->name('incidents.backup');
        Route::post('/incidents/{incident}/complete', [FirefighterIncidentResponseController::class, 'complete'])->name('incidents.complete');
        Route::post('/incidents/{incident}/false-alarm', [FirefighterIncidentResponseController::class, 'falseAlarm'])->name('incidents.false-alarm');
        Route::post('/incidents/{incident}/close', [FirefighterIncidentResponseController::class, 'close'])->name('incidents.close');
        Route::get('/incidents/{incident}/response-resources', [FirefighterIncidentResponseController::class, 'resources'])->name('incidents.response-resources');
        Route::post('/schedule/events', [FirefighterController::class, 'storeScheduleEvent'])->name('schedule.events.store');
        Route::put('/schedule/events/{eventActivity}', [FirefighterController::class, 'updateScheduleEvent'])->name('schedule.events.update');
        Route::delete('/schedule/events/{eventActivity}', [FirefighterController::class, 'destroyScheduleEvent'])->name('schedule.events.destroy');
        Route::get('/certifications', [FirefighterController::class, 'certifications'])->name('certifications');
        Route::get('/training', [FirefighterController::class, 'training'])->name('training');
        Route::get('/my-profile', [FirefighterController::class, 'myProfile'])->name('my-profile');
        Route::put('/my-profile', [FirefighterController::class, 'updateMyProfile'])->name('my-profile.update');
        Route::post('/my-profile/photo', [FirefighterController::class, 'updateMyProfilePhoto'])->name('my-profile.photo.update');
        Route::put('/my-profile/email', [FirefighterController::class, 'updateMyProfileEmail'])->name('my-profile.email.update');
        Route::put('/my-profile/password', [FirefighterController::class, 'updateMyProfilePassword'])->name('my-profile.password.update');


        Route::get('/assigned/{id}',[FirefighterController::class, 'getAssignedIncidents'])->name('assigned');
        Route::get('/incidents', [FirefighterController::class, 'incidentView'])->name('incidents');
        Route::get('/api/incidents/registry', [FirefighterController::class, 'incidentRegistryData'])->name('incidents.registry');
        Route::get('/api/incidents/registry/{incident}', [FirefighterController::class, 'incidentRegistryDetail'])->name('incidents.registry.detail');
        Route::get('/risk-reviews', [RiskAreaReviewController::class, 'firefighterIndex'])->name('risk-reviews.index');
        Route::patch('/risk-reviews/{riskAreaId}', [RiskAreaReviewController::class, 'firefighterReview'])->name('risk-reviews.review');
        Route::get('/barangay-cooperation', [App\Http\Controllers\Firefighter\BarangayCooperationController::class, 'index'])->name('barangay-cooperation.index');
        Route::patch('/barangay-cooperation/checklists/{checklistId}/items/{itemId}', [App\Http\Controllers\Firefighter\BarangayCooperationController::class, 'updateChecklistItem'])->name('barangay-cooperation.checklists.items.update');
        Route::patch('/barangay-cooperation/mitigations/{mitigationActionId}', [App\Http\Controllers\Firefighter\BarangayCooperationController::class, 'updateMitigation'])->name('barangay-cooperation.mitigations.update');

        


        Route::get('/incident', [FirefighterController::class, 'viewAll'])->name('incidents.all');
        Route::get('/announcements', [ App\Http\Controllers\staff\PostController::class, 'index'])->name('announcements');
        Route::get('/posts/get', [App\Http\Controllers\staff\PostController::class, 'getPosts'])->name('posts.get');
        Route::post('/posts/store', [App\Http\Controllers\staff\PostController::class, 'store'])->name('posts.store');
        Route::put('/posts/{post}', [App\Http\Controllers\staff\PostController::class, 'update'])->name('posts.update');
        Route::post('/posts/{post}/like', [App\Http\Controllers\staff\PostController::class, 'like'])->name('posts.like');
        Route::post('/posts/{post}/comment', [App\Http\Controllers\staff\PostController::class, 'comment'])->name('posts.comment');
        Route::delete('/posts/{post}', [App\Http\Controllers\staff\PostController::class, 'destroy'])->name('posts.destroy');


    });  

    Route::middleware(['role:firefighter'])->prefix('firefighter-v2')->name('firefighter.v2.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\staff\FirefighterV2Controller::class, 'dashboard'])->name('dashboard');
        Route::get('/incidents', [App\Http\Controllers\staff\FirefighterV2Controller::class, 'incidents'])->name('incidents');
        Route::get('/api/dashboard', [App\Http\Controllers\staff\FirefighterV2Controller::class, 'dashboardData'])->name('api.dashboard');
        Route::get('/api/incidents', [App\Http\Controllers\staff\FirefighterV2Controller::class, 'incidentsData'])->name('api.incidents');
        Route::get('/my-station', [App\Http\Controllers\staff\FirefighterV2Controller::class, 'station'])->name('station');
        Route::get('/requests', [App\Http\Controllers\staff\FirefighterV2Controller::class, 'requests'])->name('requests');
        Route::get('/schedule', [App\Http\Controllers\staff\FirefighterV2Controller::class, 'scheduleV2'])->name('schedule');
        Route::get('/announcements', [App\Http\Controllers\staff\FirefighterV2Controller::class, 'announcements'])->name('announcements');
        Route::get('/profile', [App\Http\Controllers\staff\FirefighterV2Controller::class, 'profile'])->name('profile');
    });

    Route::middleware(['role:firefighter'])->prefix('firefighter-v3')->name('firefighter.v3.')->group(function () {
        Route::get('/dashboard/{section?}', [FirefighterV3Controller::class, 'shell'])
            ->where('section', implode('|', FirefighterV3Controller::HYBRID_SECTIONS))
            ->name('dashboard');
    });

 Route::middleware(['role:citizen'])->prefix('citizen')->name('citizen.')->group(function () {
        Route::get('/mobile-hub', [PublicController::class, 'mobileHub'])->name('mobile-hub');
        Route::get('/dashboard/{section?}', [PublicController::class, 'index'])
            ->where('section', implode('|', PublicController::citizenDashboardSections()))
            ->name('dashboard');
        Route::post('/community-activity-requests', [App\Http\Controllers\CommunityActivityRequestController::class, 'storeCitizen'])->name('community-activity-requests.store');
        Route::patch('/community-activity-requests/{requestId}', [App\Http\Controllers\CommunityActivityRequestController::class, 'updateCitizen'])->whereNumber('requestId')->name('community-activity-requests.update');
        Route::post('/risk-reports', [PublicController::class, 'storeCitizenRiskReport'])->name('risk-reports.store');
        Route::put('/incidents/{incidentId}/update', [IncidentController::class, 'updateCitizenReport'])->name('incidents.update-report');
        Route::put('/profile', [PublicController::class, 'updateProfile'])->name('profile.update');
        Route::post('/profile/photo', [PublicController::class, 'updateProfilePhoto'])->name('profile.photo.update');
        Route::put('/profile/email', [PublicController::class, 'updateProfileEmail'])->name('profile.email.update');
        Route::put('/profile/password', [PublicController::class, 'updateProfilePassword'])->name('profile.password.update');
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

    Route::middleware(['role:barangay,barangay captain,barangay officer,barangay_officer'])->prefix('barangay')->name('barangay.')->group(function () {
        Route::get('/dashboard', [BarangayDashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('/dashboard/records', [BarangayDashboardController::class, 'dashboardRecords'])->name('dashboard.records');
        Route::get('/hazards', [BarangayDashboardController::class, 'hazards'])->name('hazards');
        Route::get('/risk-map', [BarangayDashboardController::class, 'riskMap'])->name('risk-map');
        Route::post('/risk-map', [BarangayDashboardController::class, 'storeRiskArea'])->name('risk-map.store');
        Route::put('/risk-map/{riskAreaId}', [BarangayDashboardController::class, 'updateRiskArea'])->name('risk-map.update');
        Route::delete('/risk-map/{riskAreaId}', [BarangayDashboardController::class, 'destroyRiskArea'])->name('risk-map.destroy');
        Route::get('/patrols', [BarangayDashboardController::class, 'patrols'])->name('patrols');
        Route::get('/assessment', [BarangayDashboardController::class, 'assessment'])->name('assessment');
        Route::get('/checklists', [BarangayDashboardController::class, 'checklists'])->name('checklists');
        Route::get('/checklists/content', [BarangayDashboardController::class, 'checklistsContent'])->name('checklists.content');
        Route::patch('/checklists/prediction/{checklistId}', [BarangayDashboardController::class, 'updatePredictionChecklist'])->name('checklists.prediction.update');
        Route::patch('/checklists/{checklistId}/items/{itemId}', [BarangayDashboardController::class, 'updateChecklistItem'])->name('checklists.items.update');
        Route::patch('/checklists/visits/{visitId}/sign-off', [App\Http\Controllers\Barangay\ChecklistVisitController::class, 'signOff'])->name('checklists.visits.sign-off');
        Route::patch('/mitigations/{mitigationActionId}', [BarangayDashboardController::class, 'updateMitigation'])->name('mitigations.update');
        Route::delete('/mitigations/{mitigationActionId}', [BarangayDashboardController::class, 'destroyMitigation'])->name('mitigations.destroy');
        Route::get('/notifications', [App\Http\Controllers\Barangay\BarangayNotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [App\Http\Controllers\Barangay\BarangayNotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
        Route::post('/notifications/{notificationId}/read', [App\Http\Controllers\Barangay\BarangayNotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::get('/reports', [BarangayDashboardController::class, 'reports'])->name('reports');
        Route::get('/reports/data', [BarangayDashboardController::class, 'reportRecords'])->name('reports.data');
        Route::post('/reports/fire', [BarangayDashboardController::class, 'storeFireReport'])->name('reports.fire.store');
        Route::put('/reports/fire/{recordId}', [BarangayDashboardController::class, 'updateFireReport'])->name('reports.fire.update');
        Route::delete('/reports/fire/{recordId}', [BarangayDashboardController::class, 'destroyFireReport'])->name('reports.fire.destroy');
        Route::post('/reports/quick', [BarangayDashboardController::class, 'storeQuickFireReport'])->name('reports.quick');
        Route::get('/awareness', [BarangayDashboardController::class, 'awareness'])->name('awareness');
        Route::post('/community-activity-requests', [App\Http\Controllers\CommunityActivityRequestController::class, 'storeBarangay'])->name('community-activity-requests.store');
        Route::get('/profile', [BarangayDashboardController::class, 'profile'])->name('profile');
        Route::put('/profile', [BarangayDashboardController::class, 'updateProfile'])->name('profile.update');
    });
        // Route::middleware(['role:ADMIN,STATIONADMIN'])
        //     ->get('/management', function () {
        //         return view('management.dashboard');
        //     });
    // Fallback for stale cached clients that still navigate to GET /logout.
    // This does not perform logout on GET; it renders a CSRF-protected POST form and submits it immediately.
    Route::get('/logout', function () {
        return response()
            ->view('auth.logout-redirect')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    })->name('logout.fallback');

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
Route::get('/health/ready', [HealthController::class, 'ready'])->name('health.ready');
Route::get('/health/status', [HealthController::class, 'status'])
    ->middleware(['auth', 'role:provincial admin'])
    ->name('health.status');
