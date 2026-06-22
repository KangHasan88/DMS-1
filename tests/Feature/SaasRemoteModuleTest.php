<?php

namespace Tests\Feature;

use App\Services\Saas\RemoteModuleLaunchVerifier;
use Tests\TestCase;

class SaasRemoteModuleTest extends TestCase
{
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
