<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboundFoc extends Model
{
    use HasFactory;

    protected $fillable = [
        'foc_number',
        'customer_name',
        'customer_phone',
        'address',
        'foc_date',
        'reason',
        'reason_detail',
        'reference_order',
        'subtotal',
        'total',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'foc_date' => 'date',
        'subtotal' => 'integer',
        'total' => 'integer',
    ];

    // ===================== REASON CONSTANTS =====================
    
    const REASON_PROMOTION = 'promotion';
    const REASON_SAMPLE = 'sample';
    const REASON_SUPPORT = 'support';
    const REASON_COMPENSATION = 'compensation';
    const REASON_OTHER = 'other';

    const REASONS = [
        self::REASON_PROMOTION => 'Promosi',
        self::REASON_SAMPLE => 'Sample Produk',
        self::REASON_SUPPORT => 'Support Customer',
        self::REASON_COMPENSATION => 'Kompensasi',
        self::REASON_OTHER => 'Lainnya',
    ];

    // ===================== RELATIONSHIPS =====================
    
    public function items(): HasMany
    {
        return $this->hasMany(OutboundFocItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ===================== HELPER METHODS =====================
    
    public static function generateFocNumber(): string
    {
        $date = now()->format('Ymd');
        $last = self::whereDate('created_at', today())->orderBy('id', 'desc')->first();
        $sequence = $last ? intval(substr($last->foc_number, -4)) + 1 : 1;
        return 'FOC' . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason] ?? ucfirst($this->reason);
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->total, 0, ',', '.');
    }

    // ===================== SCOPES =====================
    
    public function scopeByReason($query, $reason)
    {
        return $query->where('reason', $reason);
    }
}