<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class RemoteModuleHealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'module_key' => config('modules.key', 'dms'),
            'status' => 'healthy',
            'message' => 'DMS module is reachable.',
            'checked_at' => now()->toIso8601String(),
        ]);
    }
}
