<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DirectPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'supplier_id',
        'supplier_name',
        'supplier_phone',
        'purchase_date',
        'subtotal',
        'total',
        'purchase_type',
        'reference_po',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'subtotal' => 'integer',
        'total' => 'integer',
    ];

    // ===================== TYPE CONSTANTS =====================
    
    const TYPE_CASH = 'cash';
    const TYPE_FOC = 'foc';

    const TYPES = [
        self::TYPE_CASH => 'Cash (Pembelian Tunai)',
        self::TYPE_FOC => 'Free of Charge (Bonus/GR)',
    ];

    // ===================== RELATIONSHIPS =====================
    
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DirectPurchaseItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ===================== HELPER METHODS =====================
    
    public static function generateInvoiceNumber(string $type = 'cash'): string
    {
        $prefix = $type === self::TYPE_FOC ? 'FOC' : 'DP';
        $date = now()->format('Ymd');
        $lastPurchase = self::whereDate('created_at', today())
            ->where('purchase_type', $type)
            ->orderBy('id', 'desc')
            ->first();
        $sequence = $lastPurchase ? intval(substr($lastPurchase->invoice_number, -4)) + 1 : 1;
        
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->purchase_type] ?? ucfirst($this->purchase_type);
    }

    public function getFormattedTotalAttribute(): string
    {
        if ($this->purchase_type === self::TYPE_FOC) {
            return 'GRATIS';
        }
        return 'Rp ' . number_format($this->total, 0, ',', '.');
    }
}