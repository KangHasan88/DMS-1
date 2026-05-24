<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        
        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            
            // Store new photo
            $path = $request->file('photo')->store('photos', 'public');
            $data['photo'] = $path;
        }
        
        // Update user
        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // Log activity
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties(['updated_fields' => array_keys($data)])
            ->log('Profil diperbarui');

        return redirect()->route('profile.edit')->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        // Log activity
        activity()
            ->causedBy($request->user())
            ->performedOn($request->user())
            ->log('Password diubah');

        return redirect()->route('profile.edit')->with('success', 'Password berhasil diubah.');
    }

    /**
     * Update user's photo.
     */
    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $user = $request->user();
        
        // Delete old photo
        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }
        
        // Store new photo
        $path = $request->file('photo')->store('photos', 'public');
        $user->photo = $path;
        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Foto profil berhasil diperbarui.');
    }

    /**
     * Remove user's photo.
     */
    public function removePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
            $user->photo = null;
            $user->save();
        }

        return redirect()->route('profile.edit')->with('success', 'Foto profil berhasil dihapus.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        // Delete user photo
        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }
        
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Show login history.
     */
    public function loginHistory(Request $request): View
    {
        $histories = $request->user()
            ->loginHistories()
            ->orderBy('login_at', 'desc')
            ->paginate(10);

        return view('profile.login-history', compact('histories'));
    }
}