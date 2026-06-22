<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Services\Saas\RemoteModuleProvisioningSigner;
use Illuminate\Http\JsonResponse;

class RemoteModuleHealthController extends Controller
{
    public function __invoke(RemoteModuleProvisioningSigner $signer): JsonResponse
    {
        $checkedAt = now()->toIso8601String();
        $status = 'healthy';
        $message = 'DMS module is reachable.';

        return response()->json([
            'module_key' => config('modules.key', 'dms'),
            'status' => $status,
            'message' => $message,
            'checked_at' => $checkedAt,
            'signed_health' => $signer->signHealth([
                'module_key' => config('modules.key', 'dms'),
                'status' => $status,
                'message' => $message,
                'checked_at' => $checkedAt,
                'expires' => now()->addSeconds((int) config('modules.remote_provision_ttl_seconds', 300))->getTimestamp(),
            ]),
        ]);
    }
}
