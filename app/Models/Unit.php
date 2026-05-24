<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'symbol',
        'category',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ===================== RELATIONSHIPS =====================
    
    /**
     * Relasi ke Product (satu unit bisa dipakai banyak produk)
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'unit_id');
    }

    // ===================== ACCESSORS =====================
    
    /**
     * Get formatted display name
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->symbol ? "{$this->name} ({$this->symbol})" : $this->name;
    }

    // ===================== SCOPES =====================
    
    /**
     * Scope untuk unit aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk unit berdasarkan kategori
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope untuk pencarian unit
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
            ->orWhere('code', 'like', "%{$search}%")
            ->orWhere('symbol', 'like', "%{$search}%");
    }
}