<?php

namespace Tests\Feature;

use App\Models\SaasModuleTenant;
use App\Services\Saas\RemoteModuleProvisioningSigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'saas',
            'event' => 'provisioning.accepted',
            'description' => 'SaaS module provisioning diterima',
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

    public function test_provisioning_auto_sends_callback_when_central_callback_url_is_configured(): void
    {
        config()->set('modules.remote_provision_secret', 'testing-provision-secret');
        config()->set('modules.central_provisioning_callback_url', 'https://saas.kurmigo.id/module-provisioning/callback');
        Http::fake([
            'https://saas.kurmigo.id/module-provisioning/callback' => Http::response(['accepted' => true]),
        ]);

        $payload = app(RemoteModuleProvisioningSigner::class)->sign([
            'tenant_id' => '11111111-1111-4111-8111-111111111111',
            'tenant_module_id' => 78,
            'module_key' => 'dms',
            'operation_id' => '33333333-3333-4333-8333-333333333333',
            'expires' => now()->addMinute()->getTimestamp(),
        ]);

        $this->postJson('/module-provisioning', $payload)
            ->assertOk()
            ->assertJsonPath('callback_dispatched', true);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://saas.kurmigo.id/module-provisioning/callback'
                && $request['tenant_module_id'] === '78'
                && $request['module_key'] === 'dms'
                && $request['status'] === 'succeeded'
                && is_string($request['signature'] ?? null);
        });

        $tenant = SaasModuleTenant::where('tenant_module_id', 78)->firstOrFail();
        $this->assertTrue($tenant->metadata['provisioning_callback']['sent']);
    }

    public function test_remote_module_secrets_do_not_fallback_to_app_key(): void
    {
        config()->set('app.key', 'base64:' . base64_encode('app-key-value'));
        config()->set('modules.remote_launch_secret', null);
        config()->set('modules.remote_provision_secret', null);

        $this->assertNull(config('modules.remote_launch_secret'));
        $this->assertNull(config('modules.remote_provision_secret'));
    }
}
