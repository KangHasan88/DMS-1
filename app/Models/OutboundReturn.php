<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboundReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_number',
        'customer_name',
        'customer_phone',
        'reference_order',
        'return_type',
        'reason_detail',
        'action',
        'replacement_order',
        'return_date',
        'subtotal',
        'total',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'subtotal' => 'integer',
        'total' => 'integer',
    ];

    // ===================== TYPE CONSTANTS =====================
    
    const TYPE_DEFECT = 'defect';
    const TYPE_WRONG_ITEM = 'wrong_item';
    const TYPE_EXPIRED = 'expired';
    const TYPE_CUSTOMER_RETURN = 'customer_return';
    const TYPE_OTHER = 'other';

    const TYPES = [
        self::TYPE_DEFECT => 'Barang Rusak',
        self::TYPE_WRONG_ITEM => 'Barang Salah',
        self::TYPE_EXPIRED => 'Kadaluarsa',
        self::TYPE_CUSTOMER_RETURN => 'Return Customer',
        self::TYPE_OTHER => 'Lainnya',
    ];

    // ===================== ACTION CONSTANTS =====================
    
    const ACTION_REPLACE = 'replace';
    const ACTION_REFUND = 'refund';
    const ACTION_STORE_CREDIT = 'store_credit';

    const ACTIONS = [
        self::ACTION_REPLACE => 'Ganti Barang',
        self::ACTION_REFUND => 'Refund Uang',
        self::ACTION_STORE_CREDIT => 'Store Credit',
    ];

    // ===================== RELATIONSHIPS =====================
    
    public function items(): HasMany
    {
        return $this->hasMany(OutboundReturnItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ===================== HELPER METHODS =====================
    
    public static function generateReturnNumber(): string
    {
        $date = now()->format('Ymd');
        $last = self::whereDate('created_at', today())->orderBy('id', 'desc')->first();
        $sequence = $last ? intval(substr($last->return_number, -4)) + 1 : 1;
        return 'RET' . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->return_type] ?? ucfirst($this->return_type);
    }

    public function getActionLabelAttribute(): string
    {
        return self::ACTIONS[$this->action] ?? ucfirst($this->action);
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->total, 0, ',', '.');
    }

    // ===================== SCOPES =====================
    
    public function scopeByType($query, $type)
    {
        return $query->where('return_type', $type);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }
}