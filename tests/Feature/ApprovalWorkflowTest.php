<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ApprovalRequest;
use App\Models\CompanyBranch;
use App\Models\User;
use App\Services\ApprovalWorkflowService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_manager_can_view_and_approve_pending_request(): void
    {
        $requester = $this->userWithRole('warehouse', 'requester@example.test');
        $manager = $this->userWithRole('manager', 'manager@example.test');
        $branch = CompanyBranch::where('is_active', true)->first();

        $this->actingAs($requester);

        $approvalRequest = app(ApprovalWorkflowService::class)->request([
            'approval_type' => ApprovalRequest::TYPE_STOCK_ADJUSTMENT,
            'company_branch_id' => $branch?->id,
            'title' => 'Approval koreksi stok',
            'description' => 'Selisih opname membutuhkan approval.',
            'request_note' => 'Selisih fisik 2 pcs.',
            'payload' => ['product' => 'Demo Produk', 'qty' => 2],
        ]);

        $this->actingAs($manager)
            ->get(route('approval-requests.index'))
            ->assertOk()
            ->assertSee('Approval koreksi stok')
            ->assertSee('Penyesuaian Stok');

        $this->actingAs($manager)
            ->post(route('approval-requests.approve', $approvalRequest), [
                'decision_note' => 'Data opname valid.',
            ])
            ->assertRedirect(route('approval-requests.show', $approvalRequest));

        $this->assertSame(ApprovalRequest::STATUS_APPROVED, $approvalRequest->refresh()->status);
        $this->assertSame($manager->id, $approvalRequest->decided_by);
        $this->assertNotNull($approvalRequest->decided_at);
        $this->assertTrue(ActivityLog::where('log_name', 'approval')->where('event', ApprovalRequest::STATUS_APPROVED)->exists());
    }

    public function test_reject_requires_decision_note_and_request_cannot_be_decided_twice(): void
    {
        $admin = $this->userWithRole('admin', 'approval-admin@example.test');
        $approvalRequest = ApprovalRequest::create([
            'approval_type' => ApprovalRequest::TYPE_OUTBOUND_FOC,
            'title' => 'Approval FOC',
            'description' => 'FOC membutuhkan approval.',
            'requested_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('approval-requests.reject', $approvalRequest), [])
            ->assertSessionHasErrors('decision_note');

        $this->actingAs($admin)
            ->post(route('approval-requests.reject', $approvalRequest), [
                'decision_note' => 'Budget promo tidak cukup.',
            ])
            ->assertRedirect(route('approval-requests.show', $approvalRequest));

        $this->assertSame(ApprovalRequest::STATUS_REJECTED, $approvalRequest->refresh()->status);

        $this->actingAs($admin)
            ->post(route('approval-requests.approve', $approvalRequest))
            ->assertSessionHasErrors('approval');
    }

    public function test_sales_cannot_access_approval_desk(): void
    {
        $sales = $this->userWithRole('sales', 'approval-sales@example.test');

        $this->actingAs($sales)
            ->get(route('approval-requests.index'))
            ->assertForbidden();
    }

    private function userWithRole(string $role, string $email): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole($role);

        return $user;
    }
}
