<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_view_activity_logs_but_cannot_clear_them(): void
    {
        $admin = $this->userWithRole('admin', 'activity-admin@example.test');
        $log = $this->createLog(now()->subDays(10));

        $this->actingAs($admin)
            ->get(route('activity-logs.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('activity-logs.clear'), ['days' => 1])
            ->assertForbidden();

        $this->assertDatabaseHas('activity_log', ['id' => $log->id]);
    }

    public function test_super_admin_can_clear_old_activity_logs(): void
    {
        $superAdmin = $this->userWithRole('super-admin', 'activity-super@example.test');
        $oldLog = $this->createLog(now()->subDays(10));
        $newLog = $this->createLog(now());

        $this->actingAs($superAdmin)
            ->post(route('activity-logs.clear'), ['days' => 5])
            ->assertRedirect(route('activity-logs.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('activity_log', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('activity_log', ['id' => $newLog->id]);
    }

    public function test_activity_log_rejects_invalid_date_range(): void
    {
        $admin = $this->userWithRole('admin', 'activity-date@example.test');

        $this->actingAs($admin)
            ->from(route('activity-logs.index'))
            ->get(route('activity-logs.index', [
                'date_from' => '2026-05-25',
                'date_to' => '2026-05-01',
            ]))
            ->assertRedirect(route('activity-logs.index'))
            ->assertSessionHasErrors('date_to');
    }

    private function userWithRole(string $role, string $email): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole($role);

        return $user;
    }

    private function createLog($createdAt): ActivityLog
    {
        return ActivityLog::create([
            'log_name' => 'test',
            'event' => 'created',
            'description' => 'Test activity log',
            'created_at' => $createdAt,
        ]);
    }
}
