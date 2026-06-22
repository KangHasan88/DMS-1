<?php

namespace App\Services;

use App\Models\ArInvoice;
use App\Models\ChartAccount;
use App\Models\CustomerPayment;
use App\Models\JournalEntry;
use App\Models\User;

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

        return $this->journal(
            sourceType: ArInvoice::class,
            sourceId: $invoice->id,
            date: $invoice->invoice_date?->toDateString() ?? now()->toDateString(),
            description: 'AR Invoice ' . $invoice->invoice_number,
            branchId: $invoice->company_branch_id,
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

        return $this->journal(
            sourceType: CustomerPayment::class,
            sourceId: $payment->id,
            date: $payment->payment_date?->toDateString() ?? now()->toDateString(),
            description: 'Pembayaran customer ' . $payment->payment_number,
            branchId: $payment->company_branch_id,
            postedBy: $postedBy,
            lines: [
                [$cash, $amount, 0, 'Kas diterima dari ' . $payment->payment_number],
                [$receivable, 0, $amount, 'Pelunasan piutang ' . $payment->payment_number],
            ],
        );
    }

    private function journal(
        string $sourceType,
        int $sourceId,
        string $date,
        string $description,
        ?int $branchId,
        ?User $postedBy,
        array $lines,
    ): JournalEntry {
        $debitTotal = collect($lines)->sum(fn ($line) => (int) $line[1]);
        $creditTotal = collect($lines)->sum(fn ($line) => (int) $line[2]);

        if ($debitTotal !== $creditTotal || $debitTotal <= 0) {
            throw new \InvalidArgumentException('Jurnal otomatis tidak balance.');
        }

        $journal = JournalEntry::create([
            'journal_number' => JournalEntry::nextJournalNumber(),
            'journal_date' => $date,
            'description' => $description,
            'company_branch_id' => $branchId,
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
