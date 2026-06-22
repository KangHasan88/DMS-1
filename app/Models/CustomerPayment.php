<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class CustomerPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'user_id',
        'customer_id',
        'company_branch_id',
        'payment_date',
        'payment_method',
        'reference_number',
        'amount',
        'unallocated_amount',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'integer',
        'unallocated_amount' => 'integer',
    ];

    public const METHOD_CASH = 'cash';
    public const METHOD_TRANSFER = 'transfer';
    public const METHOD_GIRO = 'giro';
    public const METHOD_WALLET = 'wallet';
    public const METHOD_OTHER = 'other';

    public const METHOD_LIST = [
        self::METHOD_CASH => 'Tunai',
        self::METHOD_TRANSFER => 'Transfer',
        self::METHOD_GIRO => 'Giro',
        self::METHOD_WALLET => 'Wallet',
        self::METHOD_OTHER => 'Lainnya',
    ];

    public function customerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CustomerPaymentAllocation::class);
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHOD_LIST[$this->payment_method] ?? str($this->payment_method)->headline()->toString();
    }

    public function getIsFullyAllocatedAttribute(): bool
    {
        return (int) $this->unallocated_amount <= 0;
    }

    public static function nextPaymentNumber(?CompanyBranch $branch = null): string
    {
        $company = CompanyProfile::defaultProfile();
        $companyCode = $company?->document_code ?: 'DMS';
        $branchCode = $branch?->document_code ?: $branch?->code ?: 'MAIN';
        $date = now()->format('Ymd');
        $prefix = 'PAY-' . strtoupper(substr($companyCode, 0, 3)) . '-' . strtoupper(substr($branchCode, 0, 3)) . '-' . $date;
        $last = self::where('payment_number', 'like', $prefix . '%')->orderByDesc('id')->first();
        $sequence = $last ? ((int) substr($last->payment_number, -4)) + 1 : 1;

        return $prefix . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public static function receiveForInvoice(ArInvoice $invoice, array $data, ?User $receiver = null): self
    {
        $invoice->loadMissing('companyBranch', 'customer', 'customerUser');
        $amount = (int) $data['amount'];

        return DB::transaction(function () use ($invoice, $data, $receiver, $amount) {
            $payment = self::create([
                'payment_number' => self::nextPaymentNumber($invoice->companyBranch),
                'user_id' => $invoice->user_id,
                'customer_id' => $invoice->customer_id,
                'company_branch_id' => $invoice->company_branch_id,
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'amount' => $amount,
                'unallocated_amount' => $amount,
                'notes' => $data['notes'] ?? null,
                'received_by' => $receiver?->id,
            ]);

            $payment->allocateToInvoice($invoice, $amount, $data['notes'] ?? null);

            ActivityLog::record('customer_payments', 'received', 'Pembayaran customer diterima', $payment, [
                'payment_number' => $payment->payment_number,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $amount,
            ]);

            return $payment;
        });
    }

    public function allocateToInvoice(ArInvoice $invoice, int $amount, ?string $notes = null): CustomerPaymentAllocation
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Nominal pembayaran harus lebih dari 0.');
        }

        if ($invoice->status === ArInvoice::STATUS_VOID) {
            throw new \InvalidArgumentException('Invoice void tidak bisa dibayar.');
        }

        if ((int) $this->customer_id !== (int) $invoice->customer_id || (int) $this->user_id !== (int) $invoice->user_id) {
            throw new \InvalidArgumentException('Pembayaran dan invoice harus milik pelanggan yang sama.');
        }

        if ((int) $this->company_branch_id !== (int) $invoice->company_branch_id) {
            throw new \InvalidArgumentException('Pembayaran dan invoice harus berada di cabang yang sama.');
        }

        if ($amount > (int) $this->unallocated_amount) {
            throw new \InvalidArgumentException('Nominal alokasi melebihi saldo pembayaran.');
        }

        if ($amount > (int) $invoice->outstanding_amount) {
            throw new \InvalidArgumentException('Nominal pembayaran melebihi outstanding invoice.');
        }

        return DB::transaction(function () use ($invoice, $amount, $notes) {
            $allocation = $this->allocations()->create([
                'ar_invoice_id' => $invoice->id,
                'amount' => $amount,
                'notes' => $notes,
            ]);

            $invoice->forceFill([
                'paid_amount' => (int) $invoice->paid_amount + $amount,
            ]);
            $invoice->refreshPaymentStatus();

            $this->forceFill([
                'unallocated_amount' => max(0, (int) $this->unallocated_amount - $amount),
            ])->save();

            ActivityLog::record('customer_payments', 'allocated', 'Pembayaran dialokasikan ke AR Invoice', $allocation, [
                'payment_number' => $this->payment_number,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $amount,
            ]);

            return $allocation;
        });
    }
}
