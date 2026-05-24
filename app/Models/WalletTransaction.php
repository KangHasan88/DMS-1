<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WalletTransaction extends Model
{
    use HasFactory;
    
    protected $fillable = ['wallet_id', 'order_id', 'type', 'amount', 'description'];
    
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
    
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}