<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait HasLastLogin
{
    /**
     * Update last login information
     */
    public function updateLastLogin(Request $request)
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);
    }

    /**
     * Scope for users who have never logged in
     */
    public function scopeNeverLoggedIn($query)
    {
        return $query->whereNull('last_login_at');
    }

    /**
     * Scope for users who logged in today
     */
    public function scopeLoggedInToday($query)
    {
        return $query->whereDate('last_login_at', today());
    }

    /**
     * Scope for users logged in within last X days
     */
    public function scopeLoggedInLastDays($query, $days)
    {
        return $query->where('last_login_at', '>=', now()->subDays($days));
    }

    /**
     * Check if user is online (active in last 5 minutes)
     */
    public function isOnline()
    {
        if (!$this->last_login_at) {
            return false;
        }
        
        return $this->last_login_at->gt(now()->subMinutes(5));
    }

    /**
     * Get formatted last login
     */
    public function getLastLoginFormattedAttribute()
    {
        if (!$this->last_login_at) {
            return 'Belum pernah login';
        }
        
        return $this->last_login_at->diffForHumans();
    }

    /**
     * Get last login with IP
     */
    public function getLastLoginWithIpAttribute()
    {
        if (!$this->last_login_at) {
            return 'Belum pernah login';
        }
        
        $ip = $this->last_login_ip ? " ({$this->last_login_ip})" : '';
        return $this->last_login_at->format('d M Y H:i') . $ip;
    }
}