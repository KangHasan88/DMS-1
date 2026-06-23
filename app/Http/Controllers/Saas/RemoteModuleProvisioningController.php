<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SaasModuleTenant;
use App\Services\Saas\RemoteModuleProvisioningSigner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RemoteModuleProvisioningController extends Controller
{
    public function __invoke(Request $request, RemoteModuleProvisioningSigner $signer): JsonResponse
    {
        $payload = $request->validate([
            'tenant_id' => ['required', 'uuid'],
            'tenant_module_id' => ['required', 'integer'],
            'module_key' => ['required', 'string', 'max:80'],
            'operation_id' => ['required', 'uuid'],
            'expires' => ['required', 'integer'],
            'signature' => ['required', 'string', 'size:64'],
        ]);

        if (!$signer->verify($payload)) {
            return response()->json(['accepted' => false], 403);
        }

        $tenant = SaasModuleTenant::query()->updateOrCreate(
            [
                'tenant_module_id' => $payload['tenant_module_id'],
                'module_key' => $payload['module_key'],
            ],
            [
                'tenant_id' => $payload['tenant_id'],
                'operation_id' => $payload['operation_id'],
                'status' => 'provisioned',
                'metadata' => [
                    'provisioning_payload_received_at' => now()->toISOString(),
                ],
                'provisioned_at' => now(),
            ],
        );

        ActivityLog::record('saas', 'provisioning.accepted', 'SaaS module provisioning diterima', $tenant, [
            'tenant_id' => $tenant->tenant_id,
            'tenant_module_id' => $tenant->tenant_module_id,
            'module_key' => $tenant->module_key,
            'operation_id' => $tenant->operation_id,
        ]);

        $callback = $signer->callbackPayload($payload);
        $callbackResult = $this->sendProvisioningCallback($tenant, $callback);

        return response()->json([
            'accepted' => true,
            'tenant_id' => $tenant->tenant_id,
            'tenant_module_id' => $tenant->tenant_module_id,
            'module_key' => $tenant->module_key,
            'status' => $tenant->status,
            'callback' => $callback,
            'callback_dispatched' => $callbackResult['sent'],
        ]);
    }

    private function sendProvisioningCallback(SaasModuleTenant $tenant, array $callback): array
    {
        $url = (string) config('modules.central_provisioning_callback_url', '');

        if ($url === '') {
            return ['sent' => false, 'status' => 'not_configured'];
        }

        try {
            $response = Http::timeout(10)->acceptJson()->post($url, $callback);
            $result = [
                'sent' => $response->successful(),
                'status' => $response->status(),
                'sent_at' => now()->toISOString(),
            ];
        } catch (\Throwable $exception) {
            $result = [
                'sent' => false,
                'status' => 'error',
                'sent_at' => now()->toISOString(),
                'error' => $exception->getMessage(),
            ];
        }

        $metadata = $tenant->metadata ?? [];
        $metadata['provisioning_callback'] = $result;
        $tenant->forceFill(['metadata' => $metadata])->save();

        return $result;
    }
}
