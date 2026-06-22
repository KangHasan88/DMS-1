<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChartAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'account_type',
        'normal_balance',
        'parent_id',
        'company_branch_id',
        'description',
        'is_cash_account',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_cash_account' => 'boolean',
        'is_active' => 'boolean',
    ];

    public const TYPE_ASSET = 'asset';
    public const TYPE_LIABILITY = 'liability';
    public const TYPE_EQUITY = 'equity';
    public const TYPE_REVENUE = 'revenue';
    public const TYPE_EXPENSE = 'expense';
    public const TYPE_COGS = 'cogs';

    public const TYPE_LIST = [
        self::TYPE_ASSET => 'Aset',
        self::TYPE_LIABILITY => 'Kewajiban',
        self::TYPE_EQUITY => 'Ekuitas',
        self::TYPE_REVENUE => 'Pendapatan',
        self::TYPE_EXPENSE => 'Beban',
        self::TYPE_COGS => 'Harga Pokok Penjualan',
    ];

    public const BALANCE_DEBIT = 'debit';
    public const BALANCE_CREDIT = 'credit';

    public const BALANCE_LIST = [
        self::BALANCE_DEBIT => 'Debit',
        self::BALANCE_CREDIT => 'Kredit',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LIST[$this->account_type] ?? str($this->account_type)->headline()->toString();
    }

    public function getNormalBalanceLabelAttribute(): string
    {
        return self::BALANCE_LIST[$this->normal_balance] ?? str($this->normal_balance)->headline()->toString();
    }

    public static function defaultNormalBalance(string $accountType): string
    {
        return in_array($accountType, [self::TYPE_ASSET, self::TYPE_EXPENSE, self::TYPE_COGS], true)
            ? self::BALANCE_DEBIT
            : self::BALANCE_CREDIT;
    }
}
