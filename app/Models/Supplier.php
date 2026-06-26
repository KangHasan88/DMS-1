<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'alternate_phone',
        'email',
        'market_name',
        'stall_number',
        'address',
        'tax_npwp',
        'tax_nitku',
        'tax_name',
        'tax_address',
        'is_pkp',
        'latitude',
        'longitude',
        'photo',
        'category',
        'specialty',
        'min_order',
        'is_active',
        'total_transactions',
        'total_purchase',
        'notes',
        'payment_notes',
        'last_purchase_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_pkp' => 'boolean',
        'min_order' => 'integer',
        'total_transactions' => 'integer',
        'total_purchase' => 'integer',
        'last_purchase_at' => 'datetime',
    ];

    const CATEGORY_ALL = 'all';

    // ===================== RELATIONSHIPS =====================
    
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function directPurchases(): HasMany
    {
        return $this->hasMany(DirectPurchase::class);
    }

    public function consignments(): HasMany
    {
        return $this->hasMany(Consignment::class);
    }

    public function principals(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductPrincipal::class,
            'product_principal_supplier',
            'supplier_id',
            'product_principal_id'
        )->withTimestamps();
    }

    // ===================== ACCESSORS =====================
    
    public function getPhotoUrlAttribute(): string
    {
        if ($this->photo) {
            return asset('storage/' . $this->photo);
        }
        return asset('images/default-supplier.png');
    }

    public function getCategoryLabelAttribute(): string
    {
        return SupplierCategory::where('code', $this->category)->value('name')
            ?? ucfirst((string) $this->category);
    }

    public function getFormattedTotalPurchaseAttribute(): string
    {
        return 'Rp ' . number_format($this->total_purchase, 0, ',', '.');
    }
    
    public function getTotalPurchaseOrdersAttribute(): int
    {
        return $this->purchaseOrders()->count();
    }
    
    public function getTotalPurchaseAmountAttribute(): int
    {
        return $this->purchaseOrders()->where('status', PurchaseOrder::STATUS_RECEIVED)->sum('total');
    }

    // ===================== HELPER METHODS =====================
    
    public function updateStats(float $amount): void
    {
        $this->total_transactions++;
        $this->total_purchase += $amount;
        $this->last_purchase_at = now();
        $this->save();
    }

    // ===================== SCOPES =====================
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category)->orWhere('category', self::CATEGORY_ALL);
    }

    public function scopeByMarket($query, $market)
    {
        return $query->where('market_name', 'like', "%{$market}%");
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
            ->orWhere('phone', 'like', "%{$search}%")
            ->orWhere('market_name', 'like', "%{$search}%")
            ->orWhere('stall_number', 'like', "%{$search}%");
    }
}
