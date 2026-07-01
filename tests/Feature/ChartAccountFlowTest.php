<?php

namespace Tests\Feature;

use App\Models\ChartAccount;
use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChartAccountFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_finance_can_view_and_create_chart_account(): void
    {
        $finance = $this->userWithRole('finance');

        $this->actingAs($finance)
            ->get(route('chart-accounts.index'))
            ->assertOk()
            ->assertSee('Daftar Akun');

        $this->actingAs($finance)
            ->post(route('chart-accounts.store'), [
                'code' => '1101',
                'name' => 'Kas Operasional',
                'account_type' => ChartAccount::TYPE_ASSET,
                'is_cash_account' => '1',
            ])
            ->assertRedirect(route('chart-accounts.index'));

        $account = ChartAccount::firstOrFail();

        $this->assertSame('1101', $account->code);
        $this->assertSame('Kas Operasional', $account->name);
        $this->assertSame(ChartAccount::BALANCE_DEBIT, $account->normal_balance);
        $this->assertTrue($account->is_cash_account);
    }

    public function test_accounting_menu_is_separate_from_finance_menu(): void
    {
        $finance = $this->userWithRole('finance');

        $this->actingAs($finance)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Akuntansi')
            ->assertSee('Daftar Akun')
            ->assertSee(route('chart-accounts.index'), false);
    }

    public function test_warehouse_cannot_access_chart_accounts(): void
    {
        $warehouse = $this->userWithRole('warehouse');

        $this->actingAs($warehouse)
            ->get(route('chart-accounts.index'))
            ->assertForbidden();
    }

    public function test_branch_finance_sees_global_and_own_branch_accounts_only(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $finance = $this->userWithRole('finance', ['company_branch_id' => $branchA->id]);

        $global = ChartAccount::create([
            'code' => '1100',
            'name' => 'Kas Global',
            'account_type' => ChartAccount::TYPE_ASSET,
            'normal_balance' => ChartAccount::BALANCE_DEBIT,
        ]);
        $own = ChartAccount::create([
            'code' => '1101',
            'name' => 'Kas Cabang A',
            'account_type' => ChartAccount::TYPE_ASSET,
            'normal_balance' => ChartAccount::BALANCE_DEBIT,
            'company_branch_id' => $branchA->id,
        ]);
        $other = ChartAccount::create([
            'code' => '1102',
            'name' => 'Kas Cabang B',
            'account_type' => ChartAccount::TYPE_ASSET,
            'normal_balance' => ChartAccount::BALANCE_DEBIT,
            'company_branch_id' => $branchB->id,
        ]);

        $this->actingAs($finance)
            ->get(route('chart-accounts.index'))
            ->assertOk()
            ->assertSee($global->name)
            ->assertSee($own->name)
            ->assertDontSee($other->name);
    }

    public function test_branch_finance_cannot_use_parent_account_from_other_branch(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $finance = $this->userWithRole('finance', ['company_branch_id' => $branchA->id]);
        $otherBranchParent = ChartAccount::create([
            'code' => '1200',
            'name' => 'Piutang Cabang B',
            'account_type' => ChartAccount::TYPE_ASSET,
            'normal_balance' => ChartAccount::BALANCE_DEBIT,
            'company_branch_id' => $branchB->id,
        ]);

        $this->actingAs($finance)
            ->post(route('chart-accounts.store'), [
                'code' => '1201',
                'name' => 'Piutang Cabang A',
                'account_type' => ChartAccount::TYPE_ASSET,
                'parent_id' => $otherBranchParent->id,
                'company_branch_id' => $branchA->id,
            ])
            ->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('chart_accounts', [
            'code' => '1201',
            'name' => 'Piutang Cabang A',
        ]);
    }

    public function test_global_account_cannot_use_branch_parent_account(): void
    {
        [, $branchB] = $this->twoCompanyBranches();
        $finance = $this->userWithRole('finance');
        $branchParent = ChartAccount::create([
            'code' => '1300',
            'name' => 'Persediaan Cabang B',
            'account_type' => ChartAccount::TYPE_ASSET,
            'normal_balance' => ChartAccount::BALANCE_DEBIT,
            'company_branch_id' => $branchB->id,
        ]);

        $this->actingAs($finance)
            ->post(route('chart-accounts.store'), [
                'code' => '1301',
                'name' => 'Persediaan Global Salah Parent',
                'account_type' => ChartAccount::TYPE_ASSET,
                'parent_id' => $branchParent->id,
                'company_branch_id' => null,
            ])
            ->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('chart_accounts', [
            'code' => '1301',
            'name' => 'Persediaan Global Salah Parent',
        ]);
    }
    public function test_used_chart_account_cannot_change_accounting_structure(): void
    {
        $finance = $this->userWithRole('finance');
        $account = ChartAccount::create([
            'code' => '1400',
            'name' => 'Persediaan Barang',
            'account_type' => ChartAccount::TYPE_ASSET,
            'normal_balance' => ChartAccount::BALANCE_DEBIT,
        ]);
        $journal = JournalEntry::create([
            'journal_number' => 'JRN-TEST-001',
            'journal_date' => Carbon::today(),
            'description' => 'Test used account',
            'status' => JournalEntry::STATUS_POSTED,
            'debit_total' => 10000,
            'credit_total' => 10000,
            'posted_by' => $finance->id,
            'posted_at' => now(),
        ]);
        $journal->lines()->create([
            'chart_account_id' => $account->id,
            'description' => 'Used line',
            'debit_amount' => 10000,
            'credit_amount' => 0,
        ]);

        $this->actingAs($finance)
            ->put(route('chart-accounts.update', $account), [
                'code' => '1400',
                'name' => 'Persediaan Barang',
                'account_type' => ChartAccount::TYPE_LIABILITY,
                'normal_balance' => ChartAccount::BALANCE_CREDIT,
                'parent_id' => null,
                'company_branch_id' => null,
            ])
            ->assertSessionHasErrors('account_type');

        $account->refresh();
        $this->assertSame(ChartAccount::TYPE_ASSET, $account->account_type);
        $this->assertSame(ChartAccount::BALANCE_DEBIT, $account->normal_balance);
    }
    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }

    private function twoCompanyBranches(): array
    {
        $company = CompanyProfile::create([
            'code' => 'KMG',
            'display_name' => 'Kurmigo Test',
            'legal_name' => 'PT Kurmigo Test',
            'is_active' => true,
        ]);

        return [
            CompanyBranch::create([
                'company_profile_id' => $company->id,
                'code' => 'A',
                'name' => 'Cabang A',
                'is_active' => true,
            ]),
            CompanyBranch::create([
                'company_profile_id' => $company->id,
                'code' => 'B',
                'name' => 'Cabang B',
                'is_active' => true,
            ]),
        ];
    }
}
