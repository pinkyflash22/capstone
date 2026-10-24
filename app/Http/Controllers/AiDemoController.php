<?php

namespace App\Http\Controllers;

use App\Services\AiComparisonService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AiDemoController extends Controller
{
    public function index(AiComparisonService $ai)
    {
        return response()->view('demos.ai-comparison', ['models' => AiComparisonService::MODELS, 'context' => $ai->context()])
            ->header('Cache-Control', 'no-store');
    }

    public function catalog(AiComparisonService $ai)
    {
        return response()->json($ai->catalog())->header('Cache-Control', 'no-store');
    }

    public function generate(Request $request, AiComparisonService $ai)
    {
        $data = $request->validate(['model' => ['required', Rule::in(array_keys(AiComparisonService::MODELS))],
            'task' => ['required', 'in:mitigation,forecast_explanation']]);
        return response()->json($ai->generate($data['model'], $data['task']))->header('Cache-Control', 'no-store');
    }
}
