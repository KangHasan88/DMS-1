<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Wallet extends Model
{
    use HasFactory;
    
    protected $fillable = ['user_id', 'balance'];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }
    
    // Helper buat nambah saldo
    public function addBalance($amount, $orderId = null, $description = 'Topup')
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Jumlah saldo harus lebih dari 0.');
        }

        $this->balance += $amount;
        $this->save();
        
        return $this->transactions()->create([
            'type' => 'topup',
            'amount' => $amount,
            'order_id' => $orderId,
            'description' => $description,
        ]);
    }
    
    // Helper buat kurangi saldo (refund)
    public function deductBalance($amount, $orderId = null, $description = 'Refund')
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Jumlah saldo harus lebih dari 0.');
        }

        if ($this->balance < $amount) {
            throw new \InvalidArgumentException('Saldo wallet tidak mencukupi.');
        }

        $this->balance -= $amount;
        $this->save();
        
        return $this->transactions()->create([
            'type' => 'refund',
            'amount' => $amount,
            'order_id' => $orderId,
            'description' => $description,
        ]);
    }
}
