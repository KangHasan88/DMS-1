<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SaasModuleTenant;
use App\Services\Saas\RemoteModuleProvisioningSigner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        return response()->json([
            'accepted' => true,
            'tenant_id' => $tenant->tenant_id,
            'tenant_module_id' => $tenant->tenant_module_id,
            'module_key' => $tenant->module_key,
            'status' => $tenant->status,
            'callback' => $signer->callbackPayload($payload),
        ]);
    }
}
