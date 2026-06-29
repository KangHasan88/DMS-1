<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Services\Saas\RemoteModuleProvisioningSigner;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class RemoteModuleHealthController extends Controller
{
    public function __invoke(RemoteModuleProvisioningSigner $signer): JsonResponse
    {
        $checkedAt = now()->toIso8601String();
        $status = 'healthy';
        $message = 'DMS module is reachable.';

        $signedHealth = $signer->signHealth([
            'module_key' => config('modules.key', 'dms'),
            'status' => $status,
            'message' => $message,
            'checked_at' => $checkedAt,
            'expires' => now()->addSeconds((int) config('modules.remote_provision_ttl_seconds', 300))->getTimestamp(),
        ]);
        $callbackResult = $this->sendHealthCallback($signedHealth);

        return response()->json([
            'module_key' => config('modules.key', 'dms'),
            'status' => $status,
            'message' => $message,
            'checked_at' => $checkedAt,
            'signed_health' => $signedHealth,
            'callback_dispatched' => $callbackResult['sent'],
        ]);
    }

    private function sendHealthCallback(array $signedHealth): array
    {
        $url = (string) config('modules.central_health_callback_url', '');

        if ($url === '') {
            return ['sent' => false, 'status' => 'not_configured'];
        }

        try {
            $response = Http::timeout(10)->acceptJson()->post($url, $signedHealth);

            return [
                'sent' => $response->successful(),
                'status' => $response->status(),
            ];
        } catch (\Throwable $exception) {
            return [
                'sent' => false,
                'status' => 'error',
                'error' => $exception->getMessage(),
            ];
        }
    }
}
