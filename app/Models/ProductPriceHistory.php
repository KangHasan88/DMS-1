<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'old_price',
        'new_price',
        'old_base_price',
        'new_base_price',
        'reason',
    ];

    protected $casts = [
        'old_price' => 'integer',
        'new_price' => 'integer',
        'old_base_price' => 'integer',
        'new_base_price' => 'integer',
    ];

    // Relasi ke product
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Relasi ke user (yang melakukan perubahan)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Accessor untuk menampilkan selisih harga
    public function getPriceDifferenceAttribute(): ?int
    {
        if ($this->old_price && $this->new_price) {
            return $this->new_price - $this->old_price;
        }
        return null;
    }

    // Accessor untuk persentase perubahan
    public function getPriceChangePercentageAttribute(): ?float
    {
        if ($this->old_price && $this->old_price > 0) {
            return round(($this->new_price - $this->old_price) / $this->old_price * 100, 2);
        }
        return null;
    }
}