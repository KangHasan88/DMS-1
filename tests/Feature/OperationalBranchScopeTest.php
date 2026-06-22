<?php

namespace Tests\Feature;

use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use App\Models\Delivery;
use App\Models\DeliveryTimeSlot;
use App\Models\Order;
use App\Models\OutboundFoc;
use App\Models\OutboundReturn;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalBranchScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_branch_admin_only_sees_own_branch_deliveries(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $admin = $this->userWithRole('admin', ['company_branch_id' => $branchA->id]);
        $ownDelivery = $this->deliveryForBranch($branchA, 'KMGOPSA0001');
        $otherDelivery = $this->deliveryForBranch($branchB, 'KMGOPSB0001');

        $this->actingAs($admin)
            ->get(route('deliveries.index'))
            ->assertOk()
            ->assertSee($ownDelivery->order->order_number)
            ->assertDontSee($otherDelivery->order->order_number);
    }

    public function test_branch_admin_cannot_view_other_branch_delivery(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $admin = $this->userWithRole('admin', ['company_branch_id' => $branchA->id]);
        $otherDelivery = $this->deliveryForBranch($branchB, 'KMGOPSB0002');

        $this->actingAs($admin)
            ->get(route('deliveries.show', $otherDelivery))
            ->assertForbidden();
    }

    public function test_super_admin_can_see_all_delivery_time_slots(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $admin = $this->userWithRole('super-admin');

        $this->deliveryTimeSlot(null, 'Slot Global');
        $this->deliveryTimeSlot($branchA, 'Slot Cabang A');
        $this->deliveryTimeSlot($branchB, 'Slot Cabang B');

        $this->actingAs($admin)
            ->get(route('delivery-time-slots.index'))
            ->assertOk()
            ->assertSee('Slot Global')
            ->assertSee('Slot Cabang A')
            ->assertSee('Slot Cabang B');
    }

    public function test_branch_admin_only_sees_global_and_own_branch_delivery_time_slots(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $admin = $this->userWithRole('admin', ['company_branch_id' => $branchA->id]);

        $this->deliveryTimeSlot(null, 'Slot Global');
        $this->deliveryTimeSlot($branchA, 'Slot Cabang A');
        $this->deliveryTimeSlot($branchB, 'Slot Cabang B');

        $this->actingAs($admin)
            ->get(route('delivery-time-slots.index'))
            ->assertOk()
            ->assertSee('Slot Global')
            ->assertSee('Slot Cabang A')
            ->assertDontSee('Slot Cabang B');
    }

    public function test_branch_admin_only_sees_own_branch_foc_records(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $admin = $this->userWithRole('admin', ['company_branch_id' => $branchA->id]);
        $ownFoc = $this->outboundFocForBranch($branchA, 'FOCA0001');
        $otherFoc = $this->outboundFocForBranch($branchB, 'FOCB0001');

        $this->actingAs($admin)
            ->get(route('outbound-focs.index'))
            ->assertOk()
            ->assertSee($ownFoc->foc_number)
            ->assertDontSee($otherFoc->foc_number);
    }

    public function test_branch_admin_cannot_view_other_branch_foc_record(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $admin = $this->userWithRole('admin', ['company_branch_id' => $branchA->id]);
        $otherFoc = $this->outboundFocForBranch($branchB, 'FOCB0002');

        $this->actingAs($admin)
            ->get(route('outbound-focs.show', $otherFoc))
            ->assertForbidden();
    }

    public function test_branch_admin_only_sees_own_branch_return_records(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $admin = $this->userWithRole('admin', ['company_branch_id' => $branchA->id]);
        $ownReturn = $this->outboundReturnForBranch($branchA, 'RETA0001');
        $otherReturn = $this->outboundReturnForBranch($branchB, 'RETB0001');

        $this->actingAs($admin)
            ->get(route('outbound-returns.index'))
            ->assertOk()
            ->assertSee($ownReturn->return_number)
            ->assertDontSee($otherReturn->return_number);
    }

    public function test_branch_admin_cannot_view_other_branch_return_record(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $admin = $this->userWithRole('admin', ['company_branch_id' => $branchA->id]);
        $otherReturn = $this->outboundReturnForBranch($branchB, 'RETB0002');

        $this->actingAs($admin)
            ->get(route('outbound-returns.show', $otherReturn))
            ->assertForbidden();
    }

    private function deliveryForBranch(CompanyBranch $branch, string $orderNumber): Delivery
    {
        $customer = $this->userWithRole('customer', ['company_branch_id' => $branch->id]);
        $kurir = $this->userWithRole('kurir', ['company_branch_id' => $branch->id]);

        $order = Order::create([
            'user_id' => $customer->id,
            'company_branch_id' => $branch->id,
            'order_number' => $orderNumber,
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_time_slot' => '06:00-09:00',
            'address' => 'Jl. Operasional Test',
            'delivery_fee' => 0,
            'packing_fee' => 0,
            'subtotal' => 10000,
            'total' => 10000,
            'grand_total' => 10000,
            'status' => Order::STATUS_READY,
            'order_source' => Order::ORDER_SOURCE_ADMIN,
            'payment_method' => Order::PAYMENT_MANUAL,
            'fulfillment_type' => Order::FULFILLMENT_STOCK,
            'payment_timing' => Order::PAYMENT_TIMING_POST_PAID,
            'discount_type' => Order::DISCOUNT_NONE,
            'shipping_type' => Order::SHIPPING_NONE,
            'include_ppn' => false,
            'ppn_rate' => 11,
        ]);

        return Delivery::create([
            'order_id' => $order->id,
            'kurir_id' => $kurir->id,
            'status' => Delivery::STATUS_ASSIGNED,
            'assigned_at' => now(),
        ]);
    }

    private function deliveryTimeSlot(?CompanyBranch $branch, string $name): DeliveryTimeSlot
    {
        return DeliveryTimeSlot::create([
            'company_branch_id' => $branch?->id,
            'name' => $name,
            'start_time' => '08:00',
            'end_time' => '10:00',
            'period_label' => 'Pagi',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function outboundFocForBranch(CompanyBranch $branch, string $number): OutboundFoc
    {
        return OutboundFoc::create([
            'foc_number' => $number,
            'company_branch_id' => $branch->id,
            'customer_name' => 'Customer FOC ' . $number,
            'foc_date' => now()->toDateString(),
            'reason' => OutboundFoc::REASON_SAMPLE,
            'subtotal' => 0,
            'total' => 0,
            'created_by' => $this->userWithRole('admin', ['company_branch_id' => $branch->id])->id,
        ]);
    }

    private function outboundReturnForBranch(CompanyBranch $branch, string $number): OutboundReturn
    {
        return OutboundReturn::create([
            'return_number' => $number,
            'company_branch_id' => $branch->id,
            'customer_name' => 'Customer Return ' . $number,
            'return_type' => OutboundReturn::TYPE_DEFECT,
            'action' => OutboundReturn::ACTION_REPLACE,
            'return_date' => now()->toDateString(),
            'subtotal' => 0,
            'total' => 0,
            'created_by' => $this->userWithRole('admin', ['company_branch_id' => $branch->id])->id,
        ]);
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['is_active' => true], $attributes));
        $user->assignRole($role);

        return $user;
    }

    private function twoCompanyBranches(): array
    {
        $company = CompanyProfile::defaultProfile();
        $branchA = $company->defaultInvoiceBranch();
        $branchB = CompanyBranch::create([
            'company_profile_id' => $company->id,
            'name' => 'Cabang Operasional B',
            'code' => 'OPB',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        return [$branchA, $branchB];
    }
}
