<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        return view('profile.edit', compact('user'));
    }

    public function update(ProfileUpdateRequest $request)
    {
        $user = auth()->user();
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            $path = $request->file('photo')->store('users', 'public');
            $data['photo'] = $path;
        }

        $user->update($data);

        return back()->with('success', 'Profile berhasil diupdate.');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = auth()->user();
        $user->update([
            'password' => $request->new_password
        ]);

        return back()->with('success', 'Password berhasil diubah.');
    }

    public function activityLogs()
    {
        $logs = auth()->user()
            ->activityLogs()
            ->latest()
            ->paginate(20);

        return view('profile.activity', compact('logs'));
    }

    public function loginHistory()
    {
        $histories = auth()->user()
            ->loginHistories()
            ->latest()
            ->paginate(20);

        return view('profile.login-history', compact('histories'));
    }
}
