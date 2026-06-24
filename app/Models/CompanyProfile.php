<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CompanyProfile extends Model
{
    protected $fillable = [
        'code',
        'display_name',
        'legal_name',
        'npwp',
        'nitku',
        'is_pkp',
        'tax_address',
        'phone',
        'email',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_pkp' => 'boolean',
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(CompanyBranch::class)->orderByDesc('is_invoice_default')->orderBy('sort_order')->orderBy('name');
    }

    public function activeBranches(): HasMany
    {
        return $this->hasMany(CompanyBranch::class)->where('is_active', true)->orderByDesc('is_invoice_default')->orderBy('sort_order')->orderBy('name');
    }

    public function invoiceBranch(): HasOne
    {
        return $this->hasOne(CompanyBranch::class)->where('is_invoice_default', true);
    }

    public static function defaultProfile(): self
    {
        $companyCode = static::normalizeCodePart(data_get(config('invoice.company', []), 'code', 'KMG'), 'KMG');

        return static::query()->firstOrCreate(
            ['is_active' => true],
            [
                'code' => $companyCode,
                'display_name' => data_get(config('invoice.company', []), 'display_name', config('app.name', 'Kurmigo DMS')),
                'legal_name' => data_get(config('invoice.company', []), 'legal_name', 'PT Kurmigo Distribusi Indonesia'),
                'npwp' => data_get(config('invoice.company', []), 'npwp'),
                'is_pkp' => false,
                'phone' => data_get(config('invoice.company', []), 'phone'),
                'email' => data_get(config('invoice.company', []), 'email'),
                'address' => data_get(config('invoice.branch', []), 'address'),
            ]
        );
    }

    public function defaultInvoiceBranch(): ?CompanyBranch
    {
        return $this->invoiceBranch()
            ->where('is_active', true)
            ->first()
            ?: $this->activeBranches()->first();
    }

    public function toInvoiceCompany(): array
    {
        return [
            'code' => $this->code,
            'display_name' => $this->display_name,
            'legal_name' => $this->legal_name,
            'npwp' => $this->npwp,
            'nitku' => $this->nitku,
            'is_pkp' => $this->is_pkp,
            'tax_address' => $this->tax_address,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
        ];
    }

    public static function normalizeCodePart(string $value, string $fallback = 'KMG'): string
    {
        $code = preg_replace('/[^A-Za-z0-9]/', '', $value) ?: $fallback;

        return substr(strtoupper($code), 0, 3);
    }
}
