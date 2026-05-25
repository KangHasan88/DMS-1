<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiUserSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_user_requires_authenticated_user(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_api_user_rejects_inactive_user(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        Sanctum::actingAs($user);

        $this->getJson('/api/user')->assertForbidden();
    }

    public function test_api_user_allows_active_user(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Sanctum::actingAs($user);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    }
}
