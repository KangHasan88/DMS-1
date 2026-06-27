<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class InventoryDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_number',
        'type',
        'status',
        'document_date',
        'warehouse_id',
        'transfer_to_warehouse_id',
        'company_branch_id',
        'reference_number',
        'notes',
        'created_by',
        'posted_by',
        'posted_at',
        'voided_by',
        'voided_at',
        'void_reason',
    ];

    protected $casts = [
        'document_date' => 'date',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public const TYPE_BTB = 'btb';
    public const TYPE_BKB = 'bkb';
    public const TYPE_TRANSFER = 'transfer';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_POSTED = 'posted';
    public const STATUS_VOID = 'void';

    public const TYPES = [
        self::TYPE_BTB => 'BTB - Bukti Terima Barang',
        self::TYPE_BKB => 'BKB - Bukti Keluar Barang',
        self::TYPE_TRANSFER => 'Transfer Gudang',
    ];

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_POSTED => 'Posted',
        self::STATUS_VOID => 'Void',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InventoryDocumentItem::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function transferToWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'transfer_to_warehouse_id');
    }

    public function companyBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public static function nextDocumentNumber(string $type): string
    {
        $prefix = strtoupper($type) . now()->format('Ym');
        $last = static::where('document_number', 'like', $prefix.'%')
            ->orderByDesc('document_number')
            ->value('document_number');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function post(?User $user = null): void
    {
        if ($this->status !== self::STATUS_DRAFT) {
            throw new \RuntimeException('Dokumen hanya bisa diposting dari status Draft.');
        }

        $this->loadMissing('items.product', 'warehouse', 'transferToWarehouse');

        if ($this->items->isEmpty()) {
            throw new \RuntimeException('Dokumen belum memiliki item.');
        }

        DB::transaction(function () use ($user) {
            foreach ($this->items as $item) {
                if ($this->type === self::TYPE_TRANSFER) {
                    $this->applyTransferMovement($item);

                    continue;
                }

                $this->applyStockMovement($item, $this->type === self::TYPE_BTB ? StockMovement::TYPE_IN : StockMovement::TYPE_OUT);
            }

            $this->forceFill([
                'status' => self::STATUS_POSTED,
                'posted_by' => $user?->id,
                'posted_at' => now(),
            ])->save();
        });
    }

    public function void(string $reason, ?User $user = null): void
    {
        if ($this->status !== self::STATUS_POSTED) {
            throw new \RuntimeException('Hanya dokumen Posted yang bisa divoid.');
        }

        $this->loadMissing('items.product', 'transferToWarehouse');

        DB::transaction(function () use ($reason, $user) {
            $reversalType = $this->type === self::TYPE_BTB ? StockMovement::TYPE_OUT : StockMovement::TYPE_IN;

            foreach ($this->items as $item) {
                if ($this->type === self::TYPE_TRANSFER) {
                    $this->reverseTransferMovement($item, $reason);

                    continue;
                }

                $this->applyStockMovement($item, $reversalType, 'VOID '.$this->document_number.' - '.$reason);
            }

            $this->forceFill([
                'status' => self::STATUS_VOID,
                'voided_by' => $user?->id,
                'voided_at' => now(),
                'void_reason' => $reason,
            ])->save();
        });
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? strtoupper((string) $this->type);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_POSTED => 'dms-badge-success',
            self::STATUS_VOID => 'dms-badge-danger',
            default => 'dms-badge-secondary',
        };
    }

    private function applyStockMovement(InventoryDocumentItem $item, string $movementType, ?string $reason = null): void
    {
        $this->applyWarehouseStockMovement(
            $item,
            $this->warehouse_id,
            $movementType,
            $this->type === self::TYPE_BTB ? StockMovement::SOURCE_BTB : StockMovement::SOURCE_BKB,
            $reason,
            true
        );
    }

    private function applyTransferMovement(InventoryDocumentItem $item): void
    {
        if (! $this->transfer_to_warehouse_id) {
            throw new \RuntimeException('Gudang tujuan wajib diisi untuk transfer gudang.');
        }

        if ((int) $this->warehouse_id === (int) $this->transfer_to_warehouse_id) {
            throw new \RuntimeException('Gudang asal dan gudang tujuan tidak boleh sama.');
        }

        $this->applyWarehouseStockMovement(
            $item,
            $this->warehouse_id,
            StockMovement::TYPE_OUT,
            StockMovement::SOURCE_TRANSFER_OUT,
            $this->document_number.' - transfer keluar',
            false
        );

        $this->applyWarehouseStockMovement(
            $item,
            $this->transfer_to_warehouse_id,
            StockMovement::TYPE_IN,
            StockMovement::SOURCE_TRANSFER_IN,
            $this->document_number.' - transfer masuk',
            false
        );
    }

    private function reverseTransferMovement(InventoryDocumentItem $item, string $reason): void
    {
        $this->applyWarehouseStockMovement(
            $item,
            $this->transfer_to_warehouse_id,
            StockMovement::TYPE_OUT,
            StockMovement::SOURCE_TRANSFER_OUT,
            'VOID '.$this->document_number.' - '.$reason,
            false
        );

        $this->applyWarehouseStockMovement(
            $item,
            $this->warehouse_id,
            StockMovement::TYPE_IN,
            StockMovement::SOURCE_TRANSFER_IN,
            'VOID '.$this->document_number.' - '.$reason,
            false
        );
    }

    private function applyWarehouseStockMovement(
        InventoryDocumentItem $item,
        int $warehouseId,
        string $movementType,
        string $sourceType,
        ?string $reason = null,
        bool $affectGlobalStock = false
    ): void {
        $initialQuantity = 0;
        if ((int) $warehouseId === (int) Warehouse::defaultId()) {
            $initialQuantity = (int) ProductStock::where('product_id', $item->product_id)->value('quantity');
        }

        $warehouseStock = ProductWarehouseStock::firstOrCreate(
            ['product_id' => $item->product_id, 'warehouse_id' => $warehouseId],
            ['quantity' => $initialQuantity, 'min_stock' => 0]
        );

        $warehouseStock = ProductWarehouseStock::whereKey($warehouseStock->id)->lockForUpdate()->first();
        $before = (int) $warehouseStock->quantity;

        if ($movementType === StockMovement::TYPE_OUT && $before < $item->quantity) {
            throw new \RuntimeException("Stok gudang {$item->product?->name} tidak mencukupi. Tersedia {$before}, butuh {$item->quantity}.");
        }

        $after = $movementType === StockMovement::TYPE_IN
            ? $before + $item->quantity
            : $before - $item->quantity;

        $warehouseStock->forceFill([
            'quantity' => $after,
            'last_updated_at' => now(),
            'updated_by' => auth()->id(),
        ])->save();

        if ($affectGlobalStock) {
            $this->updateGlobalStock($item, $movementType);
        }

        StockMovement::create([
            'product_id' => $item->product_id,
            'warehouse_id' => $warehouseId,
            'source_type' => $sourceType,
            'source_id' => $this->id,
            'type' => $movementType,
            'quantity' => $item->quantity,
            'before_quantity' => $before,
            'after_quantity' => $after,
            'reason' => $reason ?? $this->document_number,
            'created_by' => auth()->id(),
        ]);
    }

    private function updateGlobalStock(InventoryDocumentItem $item, string $movementType): void
    {
        $stock = ProductStock::firstOrCreate(
            ['product_id' => $item->product_id],
            ['quantity' => 0, 'consignment_quantity' => 0, 'min_stock' => 0]
        );

        $stock = ProductStock::whereKey($stock->id)->lockForUpdate()->first();
        $before = (int) $stock->quantity;

        if ($movementType === StockMovement::TYPE_OUT && $before < $item->quantity) {
            throw new \RuntimeException("Stok {$item->product?->name} tidak mencukupi. Tersedia {$before}, butuh {$item->quantity}.");
        }

        $after = $movementType === StockMovement::TYPE_IN
            ? $before + $item->quantity
            : $before - $item->quantity;

        $stock->forceFill([
            'quantity' => $after,
            'last_updated_at' => now(),
            'updated_by' => auth()->id(),
        ])->save();
    }
}
