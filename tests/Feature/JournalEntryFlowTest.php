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

class JournalEntryFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_finance_can_post_balanced_manual_journal(): void
    {
        $finance = $this->userWithRole('finance');
        [$cash, $capital] = $this->basicAccounts();

        $this->actingAs($finance)
            ->post(route('journal-entries.store'), [
                'journal_date' => now()->toDateString(),
                'description' => 'Setoran modal awal',
                'lines' => [
                    ['chart_account_id' => $cash->id, 'debit_amount' => 100000, 'credit_amount' => 0],
                    ['chart_account_id' => $capital->id, 'debit_amount' => 0, 'credit_amount' => 100000],
                ],
            ])
            ->assertRedirect();

        $journal = JournalEntry::with('lines')->firstOrFail();

        $this->assertSame(JournalEntry::STATUS_POSTED, $journal->status);
        $this->assertSame(100000, $journal->debit_total);
        $this->assertSame(100000, $journal->credit_total);
        $this->assertCount(2, $journal->lines);

        $this->actingAs($finance)
            ->get(route('journal-entries.show', $journal))
            ->assertOk()
            ->assertSee($journal->journal_number)
            ->assertSee('Setoran modal awal');
    }

    public function test_unbalanced_journal_is_rejected(): void
    {
        $finance = $this->userWithRole('finance');
        [$cash, $capital] = $this->basicAccounts();

        $this->actingAs($finance)
            ->from(route('journal-entries.index'))
            ->post(route('journal-entries.store'), [
                'journal_date' => now()->toDateString(),
                'description' => 'Jurnal tidak balance',
                'lines' => [
                    ['chart_account_id' => $cash->id, 'debit_amount' => 100000, 'credit_amount' => 0],
                    ['chart_account_id' => $capital->id, 'debit_amount' => 0, 'credit_amount' => 90000],
                ],
            ])
            ->assertRedirect(route('journal-entries.index'))
            ->assertSessionHasErrors('lines');

        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_accounting_menu_shows_journal_entries_for_finance(): void
    {
        $finance = $this->userWithRole('finance');

        $this->actingAs($finance)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Akuntansi')
            ->assertSee('Jurnal Umum')
            ->assertSee(route('journal-entries.index'), false);
    }

    public function test_warehouse_cannot_access_journal_entries(): void
    {
        $warehouse = $this->userWithRole('warehouse');

        $this->actingAs($warehouse)
            ->get(route('journal-entries.index'))
            ->assertForbidden();
    }

    public function test_branch_finance_cannot_use_other_branch_account(): void
    {
        [$branchA, $branchB] = $this->twoCompanyBranches();
        $finance = $this->userWithRole('finance', ['company_branch_id' => $branchA->id]);
        $cash = $this->account('1101', 'Kas Cabang A', ChartAccount::TYPE_ASSET, $branchA);
        $otherCapital = $this->account('3102', 'Modal Cabang B', ChartAccount::TYPE_EQUITY, $branchB);

        $this->actingAs($finance)
            ->from(route('journal-entries.index'))
            ->post(route('journal-entries.store'), [
                'journal_date' => now()->toDateString(),
                'description' => 'Cross branch attempt',
                'lines' => [
                    ['chart_account_id' => $cash->id, 'debit_amount' => 100000, 'credit_amount' => 0],
                    ['chart_account_id' => $otherCapital->id, 'debit_amount' => 0, 'credit_amount' => 100000],
                ],
            ])
            ->assertRedirect(route('journal-entries.index'))
            ->assertSessionHasErrors('lines.1.chart_account_id');

        $this->assertDatabaseCount('journal_entries', 0);
    }

    private function basicAccounts(): array
    {
        return [
            $this->account('1101', 'Kas Operasional', ChartAccount::TYPE_ASSET),
            $this->account('3101', 'Modal Pemilik', ChartAccount::TYPE_EQUITY),
        ];
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
