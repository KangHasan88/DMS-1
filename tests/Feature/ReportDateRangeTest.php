<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportDateRangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_report_rejects_invalid_dates(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)
            ->from('/reports/sales')
            ->get('/reports/sales?start_date=not-a-date')
            ->assertRedirect('/reports/sales')
            ->assertSessionHasErrors('start_date');
    }

    public function test_report_rejects_reversed_date_range(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)
            ->from('/reports/sales')
            ->get('/reports/sales?start_date=2026-05-25&end_date=2026-05-01')
            ->assertRedirect('/reports/sales')
            ->assertSessionHasErrors('end_date');
    }

    public function test_report_export_uses_validated_date_range(): void
    {
        $user = $this->superAdmin();

        $response = $this->actingAs($user)
            ->get('/reports/export/sales?start_date=2026-05-01&end_date=2026-05-25');

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $content = $response->streamedContent();
        $this->assertStringContainsString('2026-05-01', $content);
        $this->assertStringContainsString('2026-05-25', $content);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }
}
