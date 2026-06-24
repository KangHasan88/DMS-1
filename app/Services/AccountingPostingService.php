<?php

namespace App\Services;

use App\Models\ApInvoice;
use App\Models\ArInvoice;
use App\Models\ActivityLog;
use App\Models\ChartAccount;
use App\Models\CompanyBranch;
use App\Models\CustomerPayment;
use App\Models\JournalEntry;
use App\Models\SupplierPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AccountingPostingService
{
    public function postArInvoice(ArInvoice $invoice, ?User $postedBy = null): JournalEntry
    {
        $existing = $this->existingJournal(ArInvoice::class, $invoice->id);

        if ($existing) {
            return $existing;
        }

        $invoice->loadMissing('companyBranch');
        $receivable = $this->account('1102', 'Piutang Usaha', ChartAccount::TYPE_ASSET);
        $revenue = $this->account('4101', 'Pendapatan Penjualan', ChartAccount::TYPE_REVENUE);
        $amount = (int) $invoice->total_amount;
        app(AccountingPeriodLockService::class)->assertOpen($invoice->invoice_date?->toDateString() ?? now()->toDateString(), $invoice->company_branch_id);

        return $this->journal(
            sourceType: ArInvoice::class,
            sourceId: $invoice->id,
            date: $invoice->invoice_date?->toDateString() ?? now()->toDateString(),
            description: 'AR Invoice ' . $invoice->invoice_number,
            branch: $invoice->companyBranch,
            postedBy: $postedBy,
            lines: [
                [$receivable, $amount, 0, 'Piutang dari ' . $invoice->invoice_number],
                [$revenue, 0, $amount, 'Pendapatan dari ' . $invoice->invoice_number],
            ],
        );
    }

    public function postCustomerPayment(CustomerPayment $payment, ?User $postedBy = null): JournalEntry
    {
        $existing = $this->existingJournal(CustomerPayment::class, $payment->id);

        if ($existing) {
            return $existing;
        }

        $payment->loadMissing('companyBranch');
        $cash = $this->account('1110', 'Kas dan Bank', ChartAccount::TYPE_ASSET, isCashAccount: true);
        $receivable = $this->account('1102', 'Piutang Usaha', ChartAccount::TYPE_ASSET);
        $amount = (int) $payment->amount;
        app(AccountingPeriodLockService::class)->assertOpen($payment->payment_date?->toDateString() ?? now()->toDateString(), $payment->company_branch_id);

        return $this->journal(
            sourceType: CustomerPayment::class,
            sourceId: $payment->id,
            date: $payment->payment_date?->toDateString() ?? now()->toDateString(),
            description: 'Pembayaran customer ' . $payment->payment_number,
            branch: $payment->companyBranch,
            postedBy: $postedBy,
            lines: [
                [$cash, $amount, 0, 'Kas diterima dari ' . $payment->payment_number],
                [$receivable, 0, $amount, 'Pelunasan piutang ' . $payment->payment_number],
            ],
        );
    }

    public function postApInvoice(ApInvoice $invoice, ?User $postedBy = null): JournalEntry
    {
        $existing = $this->existingJournal(ApInvoice::class, $invoice->id);

        if ($existing) {
            return $existing;
        }

        $invoice->loadMissing('companyBranch');
        $inventory = $this->account('1301', 'Persediaan Barang Dagang', ChartAccount::TYPE_ASSET);
        $payable = $this->account('2101', 'Hutang Usaha', ChartAccount::TYPE_LIABILITY);
        $amount = (int) $invoice->total_amount;
        app(AccountingPeriodLockService::class)->assertOpen($invoice->invoice_date?->toDateString() ?? now()->toDateString(), $invoice->company_branch_id);

        return $this->journal(
            sourceType: ApInvoice::class,
            sourceId: $invoice->id,
            date: $invoice->invoice_date?->toDateString() ?? now()->toDateString(),
            description: 'AP Invoice ' . $invoice->invoice_number,
            branch: $invoice->companyBranch,
            postedBy: $postedBy,
            lines: [
                [$inventory, $amount, 0, 'Persediaan dari ' . $invoice->invoice_number],
                [$payable, 0, $amount, 'Hutang dari ' . $invoice->invoice_number],
            ],
        );
    }

    public function postSupplierPayment(SupplierPayment $payment, ?User $postedBy = null): JournalEntry
    {
        $existing = $this->existingJournal(SupplierPayment::class, $payment->id);

        if ($existing) {
            return $existing;
        }

        $payment->loadMissing('companyBranch');
        $payable = $this->account('2101', 'Hutang Usaha', ChartAccount::TYPE_LIABILITY);
        $cash = $this->account('1110', 'Kas dan Bank', ChartAccount::TYPE_ASSET, isCashAccount: true);
        $amount = (int) $payment->amount;
        app(AccountingPeriodLockService::class)->assertOpen($payment->payment_date?->toDateString() ?? now()->toDateString(), $payment->company_branch_id);

        return $this->journal(
            sourceType: SupplierPayment::class,
            sourceId: $payment->id,
            date: $payment->payment_date?->toDateString() ?? now()->toDateString(),
            description: 'Pembayaran supplier ' . $payment->payment_number,
            branch: $payment->companyBranch,
            postedBy: $postedBy,
            lines: [
                [$payable, $amount, 0, 'Pelunasan hutang ' . $payment->payment_number],
                [$cash, 0, $amount, 'Kas keluar untuk ' . $payment->payment_number],
            ],
        );
    }

    public function reverseSourcePosting(string $sourceType, int $sourceId, string $reason, ?User $postedBy = null): ?JournalEntry
    {
        $original = $this->existingJournal($sourceType, $sourceId);

        if (!$original) {
            return null;
        }

        $existingReversal = $this->existingJournal(JournalEntry::class, $original->id);

        if ($existingReversal) {
            return $existingReversal;
        }

        $original->loadMissing(['lines', 'companyBranch']);
        app(AccountingPeriodLockService::class)->assertOpen($original->journal_date?->toDateString() ?? now()->toDateString(), $original->company_branch_id);
        app(AccountingPeriodLockService::class)->assertOpen(now()->toDateString(), $original->company_branch_id);

        return DB::transaction(function () use ($original, $reason, $postedBy) {
            $original->forceFill([
                'status' => JournalEntry::STATUS_VOID,
                'voided_by' => $postedBy?->id,
                'voided_at' => now(),
                'void_reason' => $reason,
            ])->save();

            $reversal = JournalEntry::create([
                'journal_number' => JournalEntry::nextJournalNumber($original->companyBranch),
                'journal_date' => now()->toDateString(),
                'description' => 'Reversal ' . $original->journal_number . ' - ' . $reason,
                'company_branch_id' => $original->company_branch_id,
                'status' => JournalEntry::STATUS_POSTED,
                'source_type' => JournalEntry::class,
                'source_id' => $original->id,
                'debit_total' => $original->credit_total,
                'credit_total' => $original->debit_total,
                'posted_by' => $postedBy?->id,
                'posted_at' => now(),
            ]);

            foreach ($original->lines as $line) {
                $reversal->lines()->create([
                    'chart_account_id' => $line->chart_account_id,
                    'description' => 'Reversal: ' . ($line->description ?: $original->journal_number),
                    'debit_amount' => $line->credit_amount,
                    'credit_amount' => $line->debit_amount,
                ]);
            }

            ActivityLog::record('journal_entries', 'reversed', 'Jurnal otomatis dibalik', $original, [
                'journal_number' => $original->journal_number,
                'reversal_journal_number' => $reversal->journal_number,
                'reason' => $reason,
            ]);

            return $reversal;
        });
    }

    private function journal(
        string $sourceType,
        int $sourceId,
        string $date,
        string $description,
        ?CompanyBranch $branch,
        ?User $postedBy,
        array $lines,
    ): JournalEntry {
        $debitTotal = collect($lines)->sum(fn ($line) => (int) $line[1]);
        $creditTotal = collect($lines)->sum(fn ($line) => (int) $line[2]);

        if ($debitTotal !== $creditTotal || $debitTotal <= 0) {
            throw new \InvalidArgumentException('Jurnal otomatis tidak balance.');
        }

        $journal = JournalEntry::create([
            'journal_number' => JournalEntry::nextJournalNumber($branch),
            'journal_date' => $date,
            'description' => $description,
            'company_branch_id' => $branch?->id,
            'status' => JournalEntry::STATUS_POSTED,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'debit_total' => $debitTotal,
            'credit_total' => $creditTotal,
            'posted_by' => $postedBy?->id,
            'posted_at' => now(),
        ]);

        foreach ($lines as [$account, $debit, $credit, $lineDescription]) {
            $journal->lines()->create([
                'chart_account_id' => $account->id,
                'description' => $lineDescription,
                'debit_amount' => (int) $debit,
                'credit_amount' => (int) $credit,
            ]);
        }

        return $journal;
    }

    private function existingJournal(string $sourceType, int $sourceId): ?JournalEntry
    {
        return JournalEntry::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();
    }

    private function account(string $code, string $name, string $type, bool $isCashAccount = false): ChartAccount
    {
        return ChartAccount::firstOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'account_type' => $type,
                'normal_balance' => ChartAccount::defaultNormalBalance($type),
                'is_cash_account' => $isCashAccount,
                'is_active' => true,
            ]
        );
    }
}
