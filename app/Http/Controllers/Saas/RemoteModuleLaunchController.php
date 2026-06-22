<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Services\Saas\RemoteModuleLaunchVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RemoteModuleLaunchController extends Controller
{
    public function __invoke(Request $request, RemoteModuleLaunchVerifier $verifier): RedirectResponse
    {
        $payload = $request->validate([
            'tenant_id' => ['required', 'uuid'],
            'tenant_module_id' => ['required', 'integer'],
            'module_key' => ['required', 'string', 'max:80'],
            'tenant_user_id' => ['nullable', 'uuid'],
            'tenant_user_role' => ['nullable', 'string', 'max:80'],
            'expires' => ['required', 'integer'],
            'signature' => ['required', 'string', 'size:64'],
        ]);

        abort_unless($verifier->verify($payload), 403);

        $request->session()->put('saas.remote_launch', $verifier->context($payload));

        return redirect()->route(auth()->check() ? 'dashboard' : 'login');
    }
}
