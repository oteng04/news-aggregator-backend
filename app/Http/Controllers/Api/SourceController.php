<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Source;
use Illuminate\Http\JsonResponse;

class SourceController extends Controller
{
    public function index(): JsonResponse
    {
        $sources = Source::where('enabled', true)->get();

        return response()->json([
            'success' => true,
            'data' => $sources
        ]);
    }
}