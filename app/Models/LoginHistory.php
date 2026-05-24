<?php
// app/Models/LoginHistory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'device_type',
        'platform',
        'browser',
        'login_at',
        'logout_at',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getDurationAttribute()
    {
        if ($this->logout_at) {
            return $this->logout_at->diffForHumans($this->login_at, ['parts' => 2]);
        }
        return 'Still active';
    }

    public function getLoginTimeAttribute()
    {
        return $this->login_at->format('d M Y H:i:s');
    }
}