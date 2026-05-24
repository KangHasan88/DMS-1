// app/Http/Middleware/CheckUserActive.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserActive
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && !Auth::user()->is_active) {
            Auth::logout();
            
            return redirect()->route('login')
                ->with('error', 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.');
        }

        return $next($request);
    }
}

// app/Http/Middleware/LastUserActivity.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LastUserActivity
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            Auth::user()->update([
                'last_login_at' => now()
            ]);
        }

        return $next($request);
    }
}

// Register middleware di Kernel.php
// protected $middlewareAliases = [
//     'user.active' => \App\Http\Middleware\CheckUserActive::class,
//     'last.activity' => \App\Http\Middleware\LastUserActivity::class,
// ];