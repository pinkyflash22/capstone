<?php
use App\Http\Controllers\Api\RoutingController;
use App\Http\Controllers\MarkerController;
use App\Http\Controllers\MarkerCategoryController;
// use App\Http\Controllers\CoordinateController;
// use App\Http\Controllers\Admin\StationController;
use App\Http\Controllers\StationController;

use App\Http\Controllers\HydrantController;
use App\Http\Controllers\HydrantTypeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\IncidentTypeController;

// use App\Http\Controllers\MapShapeController;
use App\Http\Controllers\MarkController;
use App\Http\Controllers\BoundaryLocationController;
use App\Http\Controllers\FireRiskAreaOverlayController;
use App\Http\Controllers\StationOfficerController;
use App\Http\Controllers\TeamController;

use App\Http\Controllers\SpotInvestigationReportController;
use App\Http\Controllers\ProgressInvestigationReportController;
use App\Http\Controllers\FinalInvestigationReportController;
use App\Http\Controllers\OTPController;
use App\Http\Controllers\Chief\AnalyticController as ChiefAnalyticController;



use App\Http\Controllers\Admin\ManagementController;
use App\Http\Controllers\Admin\AnalyticsController;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


use App\Http\Controllers\IconController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\TelnyxWebhookController;


// Route::get('/test-responded-team', function() {
//     try {
//         $tableExists = Schema::hasTable('responded_team');
//         $columns = [];
        
//         if ($tableExists) {
//             $columns = DB::select('DESCRIBE responded_team');
//         }
        
//         return response()->json([
//             'table_exists' => $tableExists,
//             'columns' => $columns,
//             'incident_reports_exists' => Schema::hasTable('incident_report'),
//             'personnels_exists' => Schema::hasTable('personnels')
//         ]);
//     } catch (\Exception $e) {
//         return response()->json(['error' => $e->getMessage()], 500);
//     }
// });

// Route::get('/debug-station/{stationId}', function($stationId) {
//     try {
//         $data = [
//             'station_id' => $stationId,
//             'station_exists' => DB::table('stations')->where('station_id', $stationId)->exists(),
//             'personnel_count' => DB::table('personnels')->where('stationBelong', $stationId)->count(),
//             'available_count' => DB::table('personnels')
//                 ->where('stationBelong', $stationId)
//                 ->where('availability', 'available')
//                 ->count(),
//             'sample_personnel' => DB::table('personnels')
//                 ->where('stationBelong', $stationId)
//                 ->limit(3)
//                 ->get(),
//             'all_stations' => DB::table('stations')->select('station_id', 'station_name')->get()
//         ];
        
//         return response()->json($data);
//     } catch (\Exception $e) {
//         return response()->json(['error' => $e->getMessage()], 500);
//     }
// });
// Route::get('/test-personnel/{stationId}', function($stationId) {
//     try {
//         $result = [];
        
//         // Get personnel
//         $personnel = DB::table('personnels')
//             ->where('stationBelong', $stationId)
//             ->where('availability', 'available')
//             ->get();
        
//         $result['personnel'] = $personnel;
        
//         // For each personnel, get account and user info
//         foreach ($personnel as $p) {
//             $account = DB::table('accounts')
//                 ->where('account_id', $p->account_id)
//                 ->first();
            
//             $userInfo = null;
//             if ($account) {
//                 $userInfo = DB::table('user_info')
//                     ->where('user_id', $account->info_id)
//                     ->first();
//             }
            
//             $result['details'][] = [
//                 'personnel' => $p,
//                 'account' => $account,
//                 'user_info' => $userInfo
//             ];
//         }
        
//         return response()->json($result);
        
//     } catch (\Exception $e) {
//         return response()->json(['error' => $e->getMessage()], 500);
//     }
// });
// Route::get('/debug-incident-report/{id}', function($id) {
//     $report = DB::table('incident_report')->where('id', $id)->first();
//     return response()->json([
//         'id' => $id,
//         'exists_in_db' => $report ? true : false,
//         'report' => $report,
//         'all_reports' => DB::table('incident_report')->select('id', 'incident_id')->get()
//     ]);
// });
Route::middleware(['web', 'auth', 'role:provincial admin'])->prefix('/management')->group(function () {
    Route::get('/incidents', [ManagementController::class, 'getIncidents']);
    Route::get('/personnel', [ManagementController::class, 'getPersonnel']);

     Route::post('/incidents', [ManagementController::class, 'storeIncident']);
    Route::post('/personnel', [ManagementController::class, 'storePersonnel']);
    Route::post('/equipment', [ManagementController::class, 'storeEquipment']);

    //  Route::delete('/incidents', [ManagementController::class, 'destroy']);


    Route::get('/equipment', [ManagementController::class, 'getEquipment']);
    Route::put('/incidents/{id}', [ManagementController::class, 'updateIncident']);
    Route::put('/personnel/{id}', [ManagementController::class, 'updatePersonnel']);
    Route::put('/equipment/{id}', [ManagementController::class, 'updateEquipment']);
    Route::delete('/{type}/{id}', [ManagementController::class, 'destroy']);
});
Route::middleware(['web', 'auth', 'role:station admin,chief,provincial admin'])->prefix('/managements')->group(function () {
    Route::get('/incidents', [App\Http\Controllers\Chief\ManagementController::class, 'getIncidents']);
    Route::get('/reports', [App\Http\Controllers\Chief\ManagementController::class, 'getReports']);
    Route::get('/personnel', [App\Http\Controllers\Chief\ManagementController::class, 'getPersonnel']);

     Route::post('/incidents', [App\Http\Controllers\Chief\ManagementController::class, 'storeIncident']);
    Route::post('/personnel', [App\Http\Controllers\Chief\ManagementController::class, 'storePersonnel']);
    Route::post('/equipment', [App\Http\Controllers\Chief\ManagementController::class, 'storeEquipment']);

    //  Route::delete('/incidents', [ManagementController::class, 'destroy']);


    Route::get('/equipment', [App\Http\Controllers\Chief\ManagementController::class, 'getEquipment']);
    Route::put('/incidents/{id}', [App\Http\Controllers\Chief\ManagementController::class, 'updateIncident']);
    Route::put('/personnel/{id}', [App\Http\Controllers\Chief\ManagementController::class, 'updatePersonnel']);
    Route::put('/equipment/{id}', [App\Http\Controllers\Chief\ManagementController::class, 'updateEquipment']);
    Route::delete('/{type}/{id}', [App\Http\Controllers\Chief\ManagementController::class, 'destroy']);
});
// Get incidents for spot investigation (completed, not yet in spot_investigation)
Route::get('/incidents-for-spot', function() {
    $incidents = DB::table('incidents')
        ->whereIn('status', ['completed', 'Completed', 'controlled', 'Controlled'])
        ->whereNotIn('incident_id', function($query) {
            $query->select('incident_id')->from('spot_investigation_report');
        })
        ->select('incident_id', 'updated_at')
        ->get();
    
    return response()->json($incidents);
})->name('api.incidents.for-spot');

//sms otp
Route::post('/send-otp', [OTPController::class, 'sendOTP']);
Route::post('/verify-otp', [OTPController::class, 'verifyOTP']);
Route::post('/telnyx/webhook', TelnyxWebhookController::class);

Route::middleware(['web', 'auth', 'prevent.back.history', 'role:station admin,chief,provincial admin'])
    ->prefix('station-admin')
    ->group(function () {
        Route::post('/predictions', [ChiefAnalyticController::class, 'runPredictionApi']);
        Route::get('/predictions/{runId}', [ChiefAnalyticController::class, 'predictionRunStatus']);
    });
// Add to your routes/api.php or web.php
Route::get('/firefighter/assigned-incidents/{firefighterId}', [IncidentController::class, 'getAssignedIncidents']);
Route::get('/incidents/{incidentId}', [IncidentController::class, 'show']);
//for displaying markers
// Simple working endpoint for recent incidents
Route::get('/incidents/recent', function (Request $request) {
    try {
        $since = $request->get('since');
        
        // Simple query without relationships first to test
        $query = DB::table('incidents');
        
        if ($since) {
            $query->where('created_at', '>', $since);
        }
        
        $incidents = $query->orderBy('created_at', 'desc')->limit(50)->get();
        
        return response()->json([
            'success' => true,
            'data' => $incidents,
            'count' => $incidents->count()
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Incidents recent error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
});

// Simple marker count endpoint
Route::get('/markers/count', function () {
    try {
        $count = DB::table('markers')->count();
        
        return response()->json([
            'success' => true,
            'count' => $count,
            'total' => $count
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Markers count error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
});
//reporting modules
// Route::get('/spot-investigations', [SpotInvestigationReportController::class, 'getAllSpotInvestigations']);
Route::get('/spot-investigations-available', [SpotInvestigationReportController::class, 'getAvailableSpotInvestigations']);
Route::get('/progress-investigations-available', [FinalInvestigationReportController::class, 'getAvailableProgressInvestigations']);
Route::post('/spot-investigation/draft-narrative', [SpotInvestigationReportController::class, 'draftNarrative']);
Route::post('/progress-investigation/draft-narrative', [ProgressInvestigationReportController::class, 'draftNarrative']);
Route::post('/final-investigation/draft-narrative', [FinalInvestigationReportController::class, 'draftNarrative']);
Route::post('/spot-investigation', [SpotInvestigationReportController::class, 'store']);
Route::put('/spot-investigation/{id}', [SpotInvestigationReportController::class, 'update']);
Route::delete('/spot-investigation/{id}', [SpotInvestigationReportController::class, 'destroy']);



Route::post('/progress-investigation', [ProgressInvestigationReportController::class, 'store']);
Route::put('/progress-investigation/{id}', [ProgressInvestigationReportController::class, 'update']);
Route::delete('/progress-investigation/{progressInvestigationReport}', [ProgressInvestigationReportController::class, 'destroy']);

Route::post('/final-investigation', [FinalInvestigationReportController::class, 'store']);
Route::put('/final-investigation/{id}', [FinalInvestigationReportController::class, 'update']);
Route::delete('/final-investigation/{finalInvestigationReport}', [FinalInvestigationReportController::class, 'destroy']);

// Route::put('/incidents/{incidentId}/complete', [IncidentController::class, 'complete']);

Route::put('/incidents/{incidentId}/false-alarm', [IncidentController::class, 'markFalseAlarm']);
Route::get('/incidents/nearest-available-responder', [IncidentController::class, 'nearestAvailableResponder']);
Route::post('/incidents', [IncidentController::class, 'store']);
Route::put('/incidents/{incidentId}/citizen-update', [IncidentController::class, 'updateCitizenReport']);
Route::put('/incidents/{incidentId}/{statusId}', [IncidentController::class, 'updateIncidentStatus']);



       Route::get('/incidents/assigned', [IncidentController::class, 'getAssignedIncidents']);
    Route::get('/incidents/with-markers', [IncidentController::class, 'getIncidentsWithMarkers']);
    Route::get('/incidents/assigned/{userId}', [IncidentController::class, 'getAssignedIncidentForUser']);
    Route::post('/incidents/{id}/accept', [IncidentController::class, 'acceptIncident']);
    Route::post('/incidents/{id}/arrive', [IncidentController::class, 'arriveAtIncident']);
    Route::post('/incidents/{id}/complete', [IncidentController::class, 'completeIncident']);
    Route::get('/stations', [IncidentController::class, 'getStations']);
    Route::get('/incident-type', [IncidentTypeController::class, 'index']);


    // Route::post('/firefighter/status', [FirefighterController::class, 'updateStatus']);

Route::put('/api/incidents/{incidentId}', [IncidentController::class, 'cancelIncident']);
Route::delete('/api/markers/{markerId}', [MarkerController::class, 'destroy']);
Route::get('/api/markers', [MarkerController::class, 'index']);
Route::post('/route', [RoutingController::class, 'route']);
Route::post('/reverse', [RoutingController::class, 'reverse']);
Route::post('/marker-categories', [MarkerCategoryController::class, 'store'])->name('admin.marker-category.store');
Route::get('/marker-categories', [MarkerCategoryController::class, 'categories'])->name('admin.marker.categories');

//  Route::get('/api/stations', [App\Http\Controllers\Admin\AnalyticsController::class, 'getStations']);
//     Route::get('/api/station-analytics/{stationId}', [App\Http\Controllers\Admin\AnalyticsController::class, 'analyzeStation']);



Route::get('/markers', [MarkerController::class, 'index']);
// Route::resource('markers',MarkerController::class);
Route::post('/markers', [MarkerController::class, 'store']);  
Route::put('/markers', [MarkerController::class, 'update']) ->name('markers.update');  

Route::get('/editTeam/{id}/{teamId}', [TeamController::class, 'index']);


Route::get('/teams', [TeamController::class, 'fetchTeam']);

Route::put('/team/{id}', [TeamController::class, 'update']);

Route::put('/teams/{id}', [TeamController::class, 'updateStatus']);

// Every authenticated map user can observe shared routes.
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/route/active', [RouteController::class, 'getActive']);
    Route::get('/route/active/{incidentId}', [RouteController::class, 'getActiveByIncident']);
    Route::get('/route', [RouteController::class, 'index']);
    Route::get('/route/{route}', [RouteController::class, 'show']);
    Route::get('/route/{id}/truck-details', [RouteController::class, 'truckDetails']);
});

// Only responder/management roles can create, move, stop, or delete a shared route.
Route::middleware(['web', 'auth', 'role:admin,station admin,chief,firefighter'])->group(function () {
    Route::post('/route/clear-active', [RouteController::class, 'clearActive']);
    Route::put('/route/{id}/status', [RouteController::class, 'updateStatus']);
    Route::post('/route', [RouteController::class, 'store']);
    Route::put('/route/{route}', [RouteController::class, 'update']);
    Route::patch('/route/{route}', [RouteController::class, 'update']);
    Route::delete('/route/{route}', [RouteController::class, 'destroy']);
});


// 


// Address API routes for station form
Route::get('/api/cities/{provinceId}', [AdminController::class, 'getCities'])->name('api.admin.cities');
Route::get('/api/barangays/{cityId}', [AdminController::class, 'getBarangays'])->name('api.admin.barangays');
Route::post('/full-addresses', [AdminController::class, 'storeFullAddress'])->name('api.full-addresses');
Route::delete('/markers/{id}', [MarkerController::class, 'destroy']);
Route::get('/markers/latest', [MarkerController::class, 'latest']); //latest markers 
// Route::get('/coordinate/latest', [CoordinateController::class, 'latest']); //latest markers 

// Route::resource('coordinate', CoordinateController::class);
Route::resource('station', StationController::class);

// Update marker
Route::put('/markers/{markerId}', [MarkerController::class, 'update']);

Route::get('/station/marker/{markerId}', [StationController::class, 'getByMarker']);
Route::put('/station/marker/{markerId}', [StationController::class, 'updateByMarker']);

// Route::resource('personnels', PersonnelController::class);

Route::resource('hydrant', HydrantController::class);
Route::get('/hydrant/marker/{markerId}', [HydrantController::class, 'getByMarker']);
Route::put('/hydrant/marker/{markerId}', [HydrantController::class, 'updateByMarker']);
Route::get('/hydrant-types', [HydrantTypeController::class, 'index']);

// Your existing routes...

// // Responded Team routes
// Route::post('/responded-team', [RespondedTeamController::class, 'store']);
// Route::get('/responded-team/incident-report/{incidentReportId}', [RespondedTeamController::class, 'getByIncidentReport']);
// Route::put('/responded-team/{id}/status', [RespondedTeamController::class, 'updateStatus']);
// Route::delete('/responded-team/{id}', [RespondedTeamController::class, 'destroy']);

// // Personnel routes
// Route::get('/stations/{stationId}/available-personnel', [PersonnelController::class, 'getAvailableByStation']);
// Route::put('/personnel/{id}/availability', [PersonnelController::class, 'updateAvailability']);

Route::get('/shapes', [BoundaryLocationController::class, 'index']); // fetch all shapes
Route::get('/shapes/latest', [BoundaryLocationController::class, 'latest']);
Route::get('/fire-risk-areas', [FireRiskAreaOverlayController::class, 'index']);
Route::post('/boundaries/locate-point', [BoundaryLocationController::class, 'locatePoint']);

Route::middleware(['web', 'auth', 'role:station admin,chief'])->group(function () {
    Route::put('/markers/{markerId}/location', [MarkerController::class, 'updateLocation']);
    Route::get('/boundaries/cities', [BoundaryLocationController::class, 'cityOptions']);
    Route::get('/boundaries/editor-context', [BoundaryLocationController::class, 'editorContext']);
    Route::get('/boundaries/barangays/{cityId}', [BoundaryLocationController::class, 'availableBarangays']);
    Route::post('/shapes', [BoundaryLocationController::class, 'store']);
    Route::put('/shapes/{id}', [BoundaryLocationController::class, 'update']);
    Route::delete('/shapes/{id}', [BoundaryLocationController::class, 'destroy']);
});

Route::post('/connection',[StationOfficerController::class, 'store'])->name('station-officer.store');
// Route::get('/station-officers/{id}',[StationOfficerController::class, 'store'])->name('station-officer.store');

// Route::post('/icons', [IconController::class, 'store'])->name('admin.icon.store');
// Route::get('/icons', [IconController::class, 'index']);

Route::prefix('personnel')->group(function () {
    // Route::get('/', [PersonnelController::class, 'index']);
    // Route::post('/', [PersonnelController::class, 'store']);
    // Route::get('/{id}', [PersonnelController::class, 'show']);
    // Route::put('/{id}', [PersonnelController::class, 'update']);
    // Route::delete('/{id}', [PersonnelController::class, 'destroy']);
});

Route::get('/incident/latest', [IncidentController::class, 'latest']);
Route::post('/incidents/{incidentId}/auto-assign', [IncidentController::class, 'autoAssignIncident']);
Route::post('/incidents/auto-assign-recent-unassigned', [IncidentController::class, 'autoAssignRecentUnassignedIncidents']);
// Route::post('/incidentReport', [IncidentReportController::class, 'store']);

Route::middleware(['web', 'auth', 'role:station admin,chief,provincial admin'])
    ->put('/incident/{id}', [IncidentController::class, 'update']);
// async function getPersonnels() { //javasctipt sample fetch
//     const response = await fetch('/api/personnel');
//     const data = await response.json();
//     console.log(data); // Array of personnels with account and station info
// }

// Route::get('/icons', [DashboardController::class, 'index']);
// Route::post('/icons', [DashboardController::class, 'store']);

Route::get('/test', function() {
        return ['message' => 'API is working'];
    });
