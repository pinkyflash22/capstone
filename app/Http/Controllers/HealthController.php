<?php

namespace App\Http\Controllers;

use App\Support\OperationalHealth;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function ready(OperationalHealth $health): JsonResponse
    {
        $result = $health->readiness();

        return response()->json([
            'status' => $result['status'],
            'checked_at' => $result['checked_at'],
        ], $result['status'] === 'ready' ? 200 : 503);
    }

    public function status(OperationalHealth $health): JsonResponse
    {
        return response()->json($health->status());
    }
}
