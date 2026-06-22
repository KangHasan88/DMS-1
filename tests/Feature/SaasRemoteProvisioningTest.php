<?php

namespace Tests\Feature;

use App\Models\SaasModuleTenant;
use App\Services\Saas\RemoteModuleProvisioningSigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaasRemoteProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_provisioning_payload_registers_remote_tenant_module(): void
    {
        config()->set('modules.remote_provision_secret', 'testing-provision-secret');

        $payload = app(RemoteModuleProvisioningSigner::class)->sign([
            'tenant_id' => '11111111-1111-4111-8111-111111111111',
            'tenant_module_id' => 77,
            'module_key' => 'dms',
            'operation_id' => '33333333-3333-4333-8333-333333333333',
            'expires' => now()->addMinute()->getTimestamp(),
        ]);

        $response = $this->postJson('/module-provisioning', $payload);

        $response->assertOk()
            ->assertJson([
                'accepted' => true,
                'tenant_module_id' => 77,
                'module_key' => 'dms',
                'status' => 'provisioned',
            ])
            ->assertJsonPath('callback.status', 'succeeded')
            ->assertJsonPath('callback.module_key', 'dms');

        $this->assertDatabaseHas('saas_module_tenants', [
            'tenant_id' => '11111111-1111-4111-8111-111111111111',
            'tenant_module_id' => 77,
            'module_key' => 'dms',
            'operation_id' => '33333333-3333-4333-8333-333333333333',
            'status' => 'provisioned',
        ]);
    }

    public function test_invalid_provisioning_signature_is_rejected(): void
    {
        config()->set('modules.remote_provision_secret', 'testing-provision-secret');

        $payload = app(RemoteModuleProvisioningSigner::class)->sign([
            'tenant_id' => '11111111-1111-4111-8111-111111111111',
            'tenant_module_id' => 77,
            'module_key' => 'dms',
            'operation_id' => '33333333-3333-4333-8333-333333333333',
            'expires' => now()->addMinute()->getTimestamp(),
        ]);
        $payload['signature'] = str_repeat('0', 64);

        $this->postJson('/module-provisioning', $payload)->assertForbidden();
        $this->assertDatabaseCount('saas_module_tenants', 0);
    }
}
