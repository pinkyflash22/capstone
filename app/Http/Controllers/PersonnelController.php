<?php
// app/Http/Controllers/PersonnelController.php

namespace App\Http\Controllers;

use App\Models\Personnel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PersonnelController extends Controller
{
    /**
     * Get available personnel by station
     */
    public function getAvailableByStation($stationId)
    {
        try {
            Log::info('Fetching available personnel for station: ' . $stationId);
            
            // Direct DB query to avoid relationship issues
            $personnel = DB::table('personnels')
                ->where('stationBelong', $stationId)
                ->where('availability', 'available')
                ->get();
            
            Log::info('Found ' . $personnel->count() . ' personnel records');
            
            if ($personnel->isEmpty()) {
                return response()->json([]);
            }
            
            $formatted = [];
            
            foreach ($personnel as $p) {
                // Get account info
                $account = DB::table('accounts')
                    ->where('account_id', $p->account_id)
                    ->first();
                
                $userInfo = null;
                if ($account) {
                    // Get user info from user_info table using info_id
                    $userInfo = DB::table('user_info')
                        ->where('user_id', $account->info_id)
                        ->first();
                }
                
                $formatted[] = [
                    'personnel_id' => $p->id,
                    'account_id' => $p->account_id,
                    'first_name' => $userInfo->first_name ?? 'Unknown',
                    'middle_name' => $userInfo->middle_name ?? '',
                    'last_name' => $userInfo->last_name ?? 'Unknown',
                    'full_name' => trim(
                        ($userInfo->first_name ?? '') . ' ' . 
                        ($userInfo->middle_name ?? '') . ' ' . 
                        ($userInfo->last_name ?? '')
                    ) ?: 'Unknown',
                    'rank' => $p->rank,
                    'availability' => $p->availability
                ];
            }
            
            return response()->json($formatted);

        } catch (\Exception $e) {
            Log::error('Error in getAvailableByStation: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available personnel',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
// app/Http/Controllers/PersonnelController.php

// namespace App\Http\Controllers;

// use App\Models\Personnel;
// use App\Models\UserInfo;
// use Illuminate\Http\Request;

// class PersonnelController extends Controller
// {
//     /**
//      * Get available personnel by station
//      */
//     public function getAvailableByStation($stationId)
//     {
//         try {
//             $personnel = Personnel::where('stationBelong', $stationId)
//                 ->where('availability', 'available')
//                 ->with(['account.userInfo' => function($query) {
//                     $query->select('user_id', 'first_name', 'middle_name', 'last_name');
//                 }])
//                 ->get(['id as personnel_id', 'account_id', 'rank', 'availability']);
            
//             // Format the response
//             $formatted = $personnel->map(function($p) {
//                 $userInfo = $p->account->userInfo ?? null;
//                 return [
//                     'personnel_id' => $p->personnel_id,
//                     'first_name' => $userInfo->first_name ?? 'Unknown',
//                     'middle_name' => $userInfo->middle_name ?? '',
//                     'last_name' => $userInfo->last_name ?? 'Unknown',
//                     'full_name' => trim($userInfo->first_name . ' ' . $userInfo->middle_name . ' ' . $userInfo->last_name) ?: 'Unknown',
//                     'rank' => $p->rank,
//                     'availability' => $p->availability
//                 ];
//             });
            
//             return response()->json($formatted);

//         } catch (\Exception $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Failed to fetch available personnel',
//                 'error' => $e->getMessage()
//             ], 500);
//         }
//     }

//     /**
//      * Update personnel availability
//      */
//     public function updateAvailability(Request $request, $id)
//     {
//         try {
//             $validated = $request->validate([
//                 'availability' => 'required|string|in:available,responding,inactive,resigned'
//             ]);

//             $personnel = Personnel::findOrFail($id);
//             $personnel->availability = $validated['availability'];
//             $personnel->save();

//             return response()->json([
//                 'success' => true,
//                 'message' => 'Availability updated successfully',
//                 'data' => $personnel
//             ]);

//         } catch (\Exception $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Failed to update availability',
//                 'error' => $e->getMessage()
//             ], 500);
//         }
//     }
// }
// // namespace App\Http\Controllers;

// // use Illuminate\Http\Request;
// // use App\Models\Personnel;

// // class PersonnelController extends Controller
// // {
// //     /**
// //      * List all personnels
// //      */
// //     public function index()
// //     {
// //         $personnels = Personnel::with(['account', 'station'])->get();
// //         return response()->json($personnels);
// //     }

// //     /**
// //      * Store a new personnel
// //      */
// //     public function store(Request $request)
// //     {
// //         $request->validate([
// //             'account_id' => 'required|exists:accounts,account_id',
// //             'stationBelong' => 'required|exists:stations,station_id',
// //             'rank' => 'required|string|max:255',
// //             'availability' => 'required|in:available,responding,inactive,resigned',
// //         ]);

// //         $personnel = Personnel::create($request->all());

// //         return response()->json([
// //             'success' => true,
// //             'personnel' => $personnel,
// //         ]);
// //     }

// //     /**
// //      * Show single personnel
// //      */
// //     public function show($id)
// //     {
// //         $personnel = Personnel::with(['account', 'station'])->findOrFail($id);
// //         return response()->json($personnel);
// //     }

// //     /**
// //      * Update personnel
// //      */
// //     public function update(Request $request, $id)
// //     {
// //         $personnel = Personnel::findOrFail($id);

// //         $request->validate([
// //             'rank' => 'sometimes|string|max:255',
// //             'availability' => 'sometimes|in:available,responding,inactive,resigned',
// //         ]);

// //         $personnel->update($request->only(['rank', 'availability']));

// //         return response()->json([
// //             'success' => true,
// //             'personnel' => $personnel,
// //         ]);
// //     }

// //     /**
// //      * Delete personnel
// //      */
// //     public function destroy($id)
// //     {
// //         $personnel = Personnel::findOrFail($id);
// //         $personnel->delete();

// //         return response()->json([
// //             'success' => true,
// //             'message' => 'Personnel deleted',
// //         ]);
// //     }
// // }