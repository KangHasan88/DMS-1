<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'email',
        'address',
        'latitude',
        'longitude',
        'photo',
        'referral_code',
        'referred_by',
        'customer_type',
        'credit_limit',
        'max_outstanding_orders',
        'credit_status',
        'credit_notes',
        'total_orders',
        'total_spent',
        'is_active',
        'notes',
        'last_order_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_limit' => 'integer',
        'max_outstanding_orders' => 'integer',
        'total_orders' => 'integer',
        'total_spent' => 'integer',
        'last_order_at' => 'datetime',
    ];

    public const CREDIT_NORMAL = 'normal';
    public const CREDIT_WATCHLIST = 'watchlist';
    public const CREDIT_BLOCKED = 'blocked';

    // ===================== RELATIONSHIPS =====================
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'referred_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Customer::class, 'referred_by');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id', 'user_id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'user_id', 'user_id');
    }

    // ===================== ACCESSORS =====================
    
    public function getPhotoUrlAttribute(): string
    {
        if ($this->photo) {
            return asset('storage/' . $this->photo);
        }
        return asset('images/default-avatar.png');
    }

    public function getFormattedTotalSpentAttribute(): string
    {
        return 'Rp ' . number_format($this->total_spent, 0, ',', '.');
    }

    public function getFormattedCreditLimitAttribute(): string
    {
        return 'Rp ' . number_format($this->credit_limit ?? 0, 0, ',', '.');
    }

    public function getCreditStatusLabelAttribute(): string
    {
        return match ($this->credit_status) {
            self::CREDIT_WATCHLIST => 'Watchlist',
            self::CREDIT_BLOCKED => 'Blocked',
            default => 'Normal',
        };
    }

    public function getCreditStatusBadgeAttribute(): string
    {
        return match ($this->credit_status) {
            self::CREDIT_WATCHLIST => 'dms-badge-warning',
            self::CREDIT_BLOCKED => 'dms-badge-danger',
            default => 'dms-badge-success',
        };
    }

    // ===================== HELPER METHODS =====================

    public function creditOpenStatuses(): array
    {
        return [
            Order::STATUS_PENDING_PAYMENT,
            Order::STATUS_PAID,
            Order::STATUS_CHECKING_STOCK,
            Order::STATUS_PROCURING,
            Order::STATUS_REPACKING,
            Order::STATUS_READY,
            Order::STATUS_SHIPPED,
        ];
    }

    public function outstandingOrderQuery()
    {
        return $this->orders()->whereIn('status', $this->creditOpenStatuses());
    }

    public function outstandingAmount(): int
    {
        return (int) $this->outstandingOrderQuery()->sum('grand_total');
    }

    public function outstandingOrdersCount(): int
    {
        return $this->outstandingOrderQuery()->count();
    }

    public function availableCredit(): int
    {
        if (($this->credit_limit ?? 0) <= 0) {
            return 0;
        }

        return max(0, (int) $this->credit_limit - $this->outstandingAmount());
    }

    public function isCreditBlocked(): bool
    {
        return $this->credit_status === self::CREDIT_BLOCKED;
    }

    public function isCreditWatchlisted(): bool
    {
        return $this->credit_status === self::CREDIT_WATCHLIST;
    }
    
    public function updateStats(): void
    {
        $orders = $this->orders()->where('status', 'delivered');
        
        $this->total_orders = $orders->count();
        $this->total_spent = $orders->sum('total');
        $this->last_order_at = $orders->latest()->first()?->delivered_at;
        
        $this->save();
    }

    // ===================== SCOPES =====================
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePremium($query)
    {
        return $query->where('customer_type', 'premium');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
            ->orWhere('phone', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%");
    }
}
