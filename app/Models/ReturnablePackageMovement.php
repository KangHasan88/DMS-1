<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ReturnablePackageMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'movement_number',
        'returnable_package_id',
        'customer_id',
        'company_branch_id',
        'movement_type',
        'movement_date',
        'quantity',
        'balance_before',
        'balance_after',
        'reference_type',
        'reference_id',
        'reference_number',
        'unit_value',
        'total_value',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'quantity' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
        'unit_value' => 'integer',
        'total_value' => 'integer',
    ];

    public const TYPE_ISSUED = 'issued';
    public const TYPE_RETURNED = 'returned';
    public const TYPE_SOLD = 'sold';
    public const TYPE_LOST = 'lost';
    public const TYPE_DAMAGED = 'damaged';
    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_LIST = [
        self::TYPE_ISSUED => 'Keluar ke Customer',
        self::TYPE_RETURNED => 'Kembali dari Customer',
        self::TYPE_SOLD => 'Dijual Putus',
        self::TYPE_LOST => 'Hilang',
        self::TYPE_DAMAGED => 'Rusak',
        self::TYPE_ADJUSTMENT => 'Penyesuaian',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(ReturnablePackage::class, 'returnable_package_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LIST[$this->movement_type] ?? str($this->movement_type)->headline()->toString();
    }

    public static function nextMovementNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'RPK-' . $date;
        $last = self::where('movement_number', 'like', $prefix . '%')->orderByDesc('id')->first();
        $sequence = $last ? ((int) substr($last->movement_number, -4)) + 1 : 1;

        return $prefix . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public static function recordMovement(array $data): self
    {
        return DB::transaction(function () use ($data) {
            $balance = ReturnablePackageBalance::lockForUpdate()->firstOrCreate(
                [
                    'returnable_package_id' => $data['returnable_package_id'],
                    'customer_id' => $data['customer_id'],
                    'company_branch_id' => $data['company_branch_id'] ?? null,
                ],
                [
                    'outstanding_quantity' => 0,
                ]
            );

            $quantity = (int) $data['quantity'];
            $before = (int) $balance->outstanding_quantity;
            $after = self::balanceAfter($before, $data['movement_type'], $quantity);

            if ($after < 0) {
                throw new \InvalidArgumentException('Saldo kemasan customer tidak boleh minus.');
            }

            $balance->forceFill([
                'outstanding_quantity' => $after,
                'last_movement_at' => now(),
            ])->save();

            return self::create([
                'movement_number' => self::nextMovementNumber(),
                'returnable_package_id' => $data['returnable_package_id'],
                'customer_id' => $data['customer_id'],
                'company_branch_id' => $data['company_branch_id'] ?? null,
                'movement_type' => $data['movement_type'],
                'movement_date' => $data['movement_date'],
                'quantity' => $quantity,
                'balance_before' => $before,
                'balance_after' => $after,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'unit_value' => (int) ($data['unit_value'] ?? 0),
                'total_value' => (int) ($data['unit_value'] ?? 0) * $quantity,
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);
        });
    }

    private static function balanceAfter(int $before, string $type, int $quantity): int
    {
        return match ($type) {
            self::TYPE_ISSUED => $before + $quantity,
            self::TYPE_RETURNED, self::TYPE_SOLD, self::TYPE_LOST, self::TYPE_DAMAGED => $before - $quantity,
            self::TYPE_ADJUSTMENT => $quantity,
            default => throw new \InvalidArgumentException('Tipe mutasi kemasan tidak valid.'),
        };
    }
}
