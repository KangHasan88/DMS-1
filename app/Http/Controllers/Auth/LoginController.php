<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'), $request->filled('remember'))) {
            $user = Auth::user();
            
            // Check if user is active
            if (!$user->is_active) {
                Auth::logout();
                
                throw ValidationException::withMessages([
                    'email' => ['Akun Anda telah dinonaktifkan.'],
                ]);
            }

            // Record login history
            $user->recordLogin($request);

            // Regenerate session
            $request->session()->regenerate();

            // Redirect based on role
            return redirect()->intended($this->redirectTo());
        }

        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')],
        ]);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        
        if ($user) {
            $user->recordLogout();
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    protected function redirectTo()
    {
        $user = Auth::user();
        
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            return '/admin/dashboard';
        } elseif ($user->hasRole('manager')) {
            return '/manager/dashboard';
        } elseif ($user->hasRole('sales')) {
            return '/sales/dashboard';
        } elseif ($user->hasRole('warehouse')) {
            return '/warehouse/dashboard';
        } elseif ($user->hasRole('finance')) {
            return '/finance/dashboard';
        }
        
        return '/dashboard';
    }
}