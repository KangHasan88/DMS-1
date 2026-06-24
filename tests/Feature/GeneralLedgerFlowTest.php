<?php

namespace Tests\Feature;

use App\Models\ChartAccount;
use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use App\Models\JournalEntry;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneralLedgerFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_finance_can_view_general_ledger_running_balance(): void
    {
        $finance = $this->userWithRole('finance');
        $cash = $this->account('1101', 'Kas Operasional', ChartAccount::TYPE_ASSET);
        $capital = $this->account('3101', 'Modal Pemilik', ChartAccount::TYPE_EQUITY);

        $this->postJournal('2026-06-01', $cash, $capital, 100000);
        $this->postJournal('2026-06-15', $cash, $capital, 50000);

        $this->actingAs($finance)
            ->get(route('general-ledger.index', [
                'chart_account_id' => $cash->id,
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-30',
            ]))
            ->assertOk()
            ->assertSee('Buku Besar')
            ->assertSee('Kas Operasional')
            ->assertSee('Rp 150.000');
    }

    public function test_general_ledger_calculates_opening_balance(): void
    {
        $finance = $this->userWithRole('finance');
        $cash = $this->account('1101', 'Kas Operasional', ChartAccount::TYPE_ASSET);
        $capital = $this->account('3101', 'Modal Pemilik', ChartAccount::TYPE_EQUITY);

        $this->postJournal('2026-05-20', $cash, $capital, 80000);
        $this->postJournal('2026-06-10', $cash, $capital, 20000);

        $this->actingAs($finance)
            ->get(route('general-ledger.index', [
                'chart_account_id' => $cash->id,
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-30',
            ]))
            ->assertOk()
            ->assertSee('Saldo Awal')
            ->assertSee('Rp 80.000')
            ->assertSee('Rp 100.000');
    }

    public function test_warehouse_cannot_access_general_ledger(): void
    {
        $warehouse = $this->userWithRole('warehouse');

        $this->actingAs($warehouse)
            ->get(route('general-ledger.index'))
            ->assertForbidden();
    }

    public function test_finance_can_view_cash_bank_balances_and_mutations(): void
    {
        $finance = $this->userWithRole('finance');
        $cash = ChartAccount::create([
            'code' => '1101',
            'name' => 'Kas Operasional',
            'account_type' => ChartAccount::TYPE_ASSET,
            'normal_balance' => ChartAccount::BALANCE_DEBIT,
            'is_cash_account' => true,
            'is_active' => true,
        ]);
        $bank = ChartAccount::create([
            'code' => '1102',
            'name' => 'Bank BCA',
            'account_type' => ChartAccount::TYPE_ASSET,
            'normal_balance' => ChartAccount::BALANCE_DEBIT,
            'is_cash_account' => true,
            'is_active' => true,
        ]);
        $receivable = $this->account('1201', 'Piutang Usaha', ChartAccount::TYPE_ASSET);
        $capital = $this->account('3101', 'Modal Pemilik', ChartAccount::TYPE_EQUITY);

        $this->postJournal('2026-06-01', $cash, $capital, 100000);
        $this->postJournal('2026-06-10', $bank, $capital, 50000);
        $this->postJournal('2026-06-15', $receivable, $capital, 25000);

        $this->actingAs($finance)
            ->get(route('cash-bank.index', [
                'chart_account_id' => $cash->id,
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-30',
            ]))
            ->assertOk()
            ->assertSee('Kas & Bank')
            ->assertSee('Kas Operasional')
            ->assertSee('Bank BCA')
            ->assertSee('Rp 150.000')
            ->assertDontSee('Piutang Usaha');
    }

    public function test_finance_can_post_cash_bank_operational_expense(): void
    {
        $finance = $this->userWithRole('finance');
        $cash = ChartAccount::create([
            'code' => '1101',
            'name' => 'Kas Kecil Operasional',
            'account_type' => ChartAccount::TYPE_ASSET,
            'normal_balance' => ChartAccount::BALANCE_DEBIT,
            'is_cash_account' => true,
            'is_active' => true,
        ]);
        $fuelExpense = ChartAccount::create([
            'code' => '6101',
            'name' => 'Biaya BBM',
            'account_type' => ChartAccount::TYPE_EXPENSE,
            'normal_balance' => ChartAccount::BALANCE_DEBIT,
            'is_cash_account' => false,
            'is_active' => true,
        ]);

        $this->actingAs($finance)
            ->post(route('cash-bank.expenses.store'), [
                'transaction_date' => '2026-06-20',
                'cash_account_id' => $cash->id,
                'expense_account_id' => $fuelExpense->id,
                'amount' => 75000,
                'reference_number' => 'BBM-001',
                'description' => 'BBM kendaraan delivery',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $journal = JournalEntry::with('lines.account')
            ->where('description', 'like', 'Biaya Operasional - BBM kendaraan delivery%')
            ->firstOrFail();

        $this->assertSame(75000, $journal->debit_total);
        $this->assertSame(75000, $journal->credit_total);
        $this->assertTrue($journal->lines->contains(fn ($line) => $line->account->code === '6101' && $line->debit_amount === 75000));
        $this->assertTrue($journal->lines->contains(fn ($line) => $line->account->code === '1101' && $line->credit_amount === 75000));
    }

    public function test_finance_can_post_cash_bank_transfer(): void
    {
        $finance = $this->userWithRole('finance');
        $bank = ChartAccount::create([
            'code' => '1101',
            'name' => 'Bank BCA Operasional',
            'account_type' => ChartAccount::TYPE_ASSET,
            'normal_balance' => ChartAccount::BALANCE_DEBIT,
            'is_cash_account' => true,
            'is_active' => true,
        ]);
        $pettyCash = ChartAccount::create([
            'code' => '1102',
            'name' => 'Kas Kecil Operasional',
            'account_type' => ChartAccount::TYPE_ASSET,
            'normal_balance' => ChartAccount::BALANCE_DEBIT,
            'is_cash_account' => true,
            'is_active' => true,
        ]);

        $this->actingAs($finance)
            ->post(route('cash-bank.transfers.store'), [
                'transfer_date' => '2026-06-21',
                'from_cash_account_id' => $bank->id,
                'to_cash_account_id' => $pettyCash->id,
                'amount' => 1000000,
                'reference_number' => 'TRF-KK-001',
                'description' => 'Isi kas kecil operasional',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $journal = JournalEntry::with('lines.account')
            ->where('description', 'like', 'Transfer Kas/Bank - Isi kas kecil operasional%')
            ->firstOrFail();

        $this->assertSame(1000000, $journal->debit_total);
        $this->assertSame(1000000, $journal->credit_total);
        $this->assertTrue($journal->lines->contains(fn ($line) => $line->account->code === '1102' && $line->debit_amount === 1000000));
        $this->assertTrue($journal->lines->contains(fn ($line) => $line->account->code === '1101' && $line->credit_amount === 1000000));
    }

    public function test_branch_finance_only_sees_scoped_ledger_accounts(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $finance = $this->userWithRole('finance', ['company_branch_id' => $branchA->id]);
        $ownCash = $this->account('1101', 'Kas Cabang A', ChartAccount::TYPE_ASSET, $branchA);
        $ownCapital = $this->account('3101', 'Modal Cabang A', ChartAccount::TYPE_EQUITY, $branchA);
        $otherCash = $this->account('1102', 'Kas Cabang B', ChartAccount::TYPE_ASSET, $branchB);
        $otherCapital = $this->account('3102', 'Modal Cabang B', ChartAccount::TYPE_EQUITY, $branchB);

        $this->postJournal('2026-06-01', $ownCash, $ownCapital, 100000, $branchA);
        $this->postJournal('2026-06-01', $otherCash, $otherCapital, 90000, $branchB);

        $this->actingAs($finance)
            ->get(route('general-ledger.index', [
                'chart_account_id' => $ownCash->id,
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-30',
            ]))
            ->assertOk()
            ->assertSee('Kas Cabang A')
            ->assertSee('Rp 100.000')
            ->assertDontSee('Kas Cabang B')
            ->assertDontSee('Rp 90.000');
    }

    private function postJournal(
        string $date,
        ChartAccount $debitAccount,
        ChartAccount $creditAccount,
        int $amount,
        ?CompanyBranch $branch = null,
    ): JournalEntry {
        $journal = JournalEntry::create([
            'journal_number' => 'JRN-' . uniqid(),
            'journal_date' => $date,
            'description' => 'Test journal',
            'company_branch_id' => $branch?->id,
            'status' => JournalEntry::STATUS_POSTED,
            'debit_total' => $amount,
            'credit_total' => $amount,
        ]);

        $journal->lines()->create([
            'chart_account_id' => $debitAccount->id,
            'debit_amount' => $amount,
            'credit_amount' => 0,
        ]);
        $journal->lines()->create([
            'chart_account_id' => $creditAccount->id,
            'debit_amount' => 0,
            'credit_amount' => $amount,
        ]);

        return $journal;
    }

    private function account(string $code, string $name, string $type, ?CompanyBranch $branch = null): ChartAccount
    {
        return ChartAccount::create([
            'code' => $code,
            'name' => $name,
            'account_type' => $type,
            'normal_balance' => ChartAccount::defaultNormalBalance($type),
            'company_branch_id' => $branch?->id,
            'is_active' => true,
        ]);
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
