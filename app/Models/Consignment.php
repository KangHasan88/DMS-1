<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'cn_number',
        'supplier_id',
        'consignment_date',
        'return_date',
        'status',
        'total_items',
        'total_sold',
        'total_returned',
        'total_value',
        'total_paid',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'consignment_date' => 'date',
        'return_date' => 'date',
        'total_items' => 'integer',
        'total_sold' => 'integer',
        'total_returned' => 'integer',
        'total_value' => 'integer',
        'total_paid' => 'integer',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_PARTIAL = 'partial';
    const STATUS_RETURNED = 'returned';
    const STATUS_COMPLETED = 'completed';

    const STATUS_LIST = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_PARTIAL => 'Partial Return',
        self::STATUS_RETURNED => 'Fully Returned',
        self::STATUS_COMPLETED => 'Completed',
    ];

    const STATUS_COLORS = [
        self::STATUS_ACTIVE => 'success',
        self::STATUS_PARTIAL => 'warning',
        self::STATUS_RETURNED => 'info',
        self::STATUS_COMPLETED => 'secondary',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ConsignmentItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ConsignmentPayment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateCNNumber(): string
    {
        $date = now()->format('Ymd');
        $lastCN = self::whereDate('created_at', today())->orderBy('id', 'desc')->first();
        $sequence = $lastCN ? intval(substr($lastCN->cn_number, -4)) + 1 : 1;
        
        return 'CN' . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function getRemainingBalanceAttribute(): int
    {
        return $this->total_value - $this->total_paid;
    }

    public function updateStats(): void
    {
        $this->total_items = $this->items->sum('quantity');
        $this->total_sold = $this->items->sum('sold_quantity');
        $this->total_returned = $this->items->sum('returned_quantity');
        $this->total_value = $this->items->sum('subtotal');
        $this->save();
        
        // Update status
        if ($this->total_returned >= $this->total_items && $this->total_items > 0) {
            $this->status = self::STATUS_RETURNED;
        } elseif ($this->total_sold >= $this->total_items && $this->total_items > 0) {
            $this->status = self::STATUS_COMPLETED;
        } elseif ($this->total_returned > 0 || $this->total_sold > 0) {
            $this->status = self::STATUS_PARTIAL;
        } else {
            $this->status = self::STATUS_ACTIVE;
        }
        $this->save();
    }
}