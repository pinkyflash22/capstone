<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OpenRouteController extends Controller
{
    //   public function analyze(Request $request)
    // {
    //     $response = Http::get('https://openrouter.ai/api/v1/chat/completions', [
    //         'waypoints' => $request->start . '|' . $request->end,
    //         'mode' => 'drive',
    //         'type' => 'balanced',
    //         'apiKey' =>env('GEOAPIFY_KEY'),
    //         // env('GEOAPIFY_KEY')
    //     ]);
        
    //     return response()->json($response->json());
    // }

 
    // public function show(){
    //     return view('markers.map');
    // }
}
