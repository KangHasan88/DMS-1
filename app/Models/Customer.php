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
        'total_orders',
        'total_spent',
        'is_active',
        'notes',
        'last_order_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'total_orders' => 'integer',
        'total_spent' => 'integer',
        'last_order_at' => 'datetime',
    ];

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

    // ===================== HELPER METHODS =====================
    
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