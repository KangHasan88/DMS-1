<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Super admin bisa akses semua
        if ($user->hasRole('super-admin')) {
            return $next($request);
        }
        
        // Check apakah user punya role yang diizinkan
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }
        
        // Jika tidak punya akses
        abort(403, 'Unauthorized access. You do not have permission to view this page.');
    }
}