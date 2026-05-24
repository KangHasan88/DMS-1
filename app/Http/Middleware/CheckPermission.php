<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Super admin bisa akses semua
        if ($user->hasRole('super-admin')) {
            return $next($request);
        }
        
        // Check permission
        if ($user->can($permission)) {
            return $next($request);
        }
        
        // Jika tidak punya permission
        abort(403, 'Unauthorized action. You do not have permission to perform this action.');
    }
}