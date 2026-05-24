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

        $before = $this->balance;
        $this->balance += $amount;
        $this->save();

        $transaction = $this->transactions()->create([
            'type' => 'topup',
            'amount' => $amount,
            'order_id' => $orderId,
            'description' => $description,
        ]);

        ActivityLog::record('wallet', 'balance_added', 'Saldo wallet bertambah', $this, [
            'wallet_id' => $this->id,
            'user_id' => $this->user_id,
            'order_id' => $orderId,
            'amount' => $amount,
            'before_balance' => $before,
            'after_balance' => $this->balance,
            'description' => $description,
            'transaction_id' => $transaction->id,
        ]);

        return $transaction;
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

        $before = $this->balance;
        $this->balance -= $amount;
        $this->save();

        $transaction = $this->transactions()->create([
            'type' => 'refund',
            'amount' => $amount,
            'order_id' => $orderId,
            'description' => $description,
        ]);

        ActivityLog::record('wallet', 'balance_deducted', 'Saldo wallet berkurang', $this, [
            'wallet_id' => $this->id,
            'user_id' => $this->user_id,
            'order_id' => $orderId,
            'amount' => $amount,
            'before_balance' => $before,
            'after_balance' => $this->balance,
            'description' => $description,
            'transaction_id' => $transaction->id,
        ]);

        return $transaction;
    }
}
