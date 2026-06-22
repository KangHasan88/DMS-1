<?php

namespace Tests\Feature;

use App\Models\SaasModuleTenant;
use App\Services\Saas\RemoteModuleLaunchVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaasRemoteModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_remote_health_endpoint_reports_dms_module(): void
    {
        $response = $this->getJson('/health');

        $response->assertOk()
            ->assertJson([
                'module_key' => 'dms',
                'status' => 'healthy',
            ]);
    }

    public function test_signed_remote_launch_stores_saas_context_and_redirects_to_login(): void
    {
        config()->set('modules.remote_launch_secret', 'testing-secret');
        SaasModuleTenant::create([
            'tenant_id' => '11111111-1111-4111-8111-111111111111',
            'tenant_module_id' => 42,
            'module_key' => 'dms',
            'operation_id' => '33333333-3333-4333-8333-333333333333',
            'status' => 'provisioned',
            'provisioned_at' => now(),
        ]);

        $payload = app(RemoteModuleLaunchVerifier::class)->sign([
            'tenant_id' => '11111111-1111-4111-8111-111111111111',
            'tenant_module_id' => 42,
            'module_key' => 'dms',
            'tenant_user_id' => '22222222-2222-4222-8222-222222222222',
            'tenant_user_role' => 'owner',
            'expires' => now()->addMinute()->getTimestamp(),
        ]);

        $response = $this->get('/sso/launch?'.http_build_query($payload));

        $response->assertRedirect(route('login'));
        $this->assertSame('11111111-1111-4111-8111-111111111111', session('saas.remote_launch.tenant_id'));
        $this->assertSame('42', session('saas.remote_launch.tenant_module_id'));
        $this->assertNotNull(SaasModuleTenant::first()->last_launch_at);
    }

    public function test_signed_remote_launch_requires_provisioned_tenant_module(): void
    {
        config()->set('modules.remote_launch_secret', 'testing-secret');

        $payload = app(RemoteModuleLaunchVerifier::class)->sign([
            'tenant_id' => '11111111-1111-4111-8111-111111111111',
            'tenant_module_id' => 42,
            'module_key' => 'dms',
            'tenant_user_id' => '22222222-2222-4222-8222-222222222222',
            'tenant_user_role' => 'owner',
            'expires' => now()->addMinute()->getTimestamp(),
        ]);

        $this->get('/sso/launch?'.http_build_query($payload))->assertForbidden();
    }

    public function test_expired_remote_launch_is_rejected(): void
    {
        config()->set('modules.remote_launch_secret', 'testing-secret');

        $payload = app(RemoteModuleLaunchVerifier::class)->sign([
            'tenant_id' => '11111111-1111-4111-8111-111111111111',
            'tenant_module_id' => 42,
            'module_key' => 'dms',
            'tenant_user_id' => '',
            'tenant_user_role' => '',
            'expires' => now()->subMinute()->getTimestamp(),
        ]);

        $this->get('/sso/launch?'.http_build_query($payload))->assertForbidden();
    }
}
