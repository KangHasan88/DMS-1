<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\UserRequest;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request)
    {
        $users = User::with('roles')
            ->when($request->search, function($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->when($request->role, function($query, $role) {
                $query->whereHas('roles', function($q) use ($role) {
                    $q->where('name', $role);
                });
            })
            ->when($request->status, function($query, $status) {
                $query->where('is_active', $status === 'active');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $roles = Role::all()->pluck('name');

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = Role::all();
        $supervisors = User::role(['manager', 'admin'])->get();
        
        return view('admin.users.create', compact('roles', 'supervisors'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(UserRequest $request)
    {
        $data = $request->validated();
        
        // ?? HASH PASSWORD DENGAN ARGON (WAJIB!)
        if (isset($data['password'])) {
            $data['password'] = Hash::driver('argon')->make($data['password']);
        }
        
        // Handle photo upload
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('users', 'public');
            $data['photo'] = $path;
        }

        // Create user
        $user = User::create($data);
        
        // Assign roles
        if ($request->filled('roles')) {
            $user->assignRole($request->roles);
        }

        // Assign supervisor
        if ($request->filled('supervisor_id')) {
            $user->supervisor_id = $request->supervisor_id;
            $user->save();
        }

        // Log activity
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties(['roles' => $request->roles])
            ->log('User created');

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil dibuat.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load(['roles', 'supervisor', 'loginHistories' => function($q) {
            $q->latest()->limit(10);
        }]);

        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $roles = Role::all();
        $supervisors = User::role(['manager', 'admin'])->where('id', '!=', $user->id)->get();
        
        return view('admin.users.edit', compact('user', 'roles', 'supervisors'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UserRequest $request, User $user)
    {
        $data = $request->validated();
        
        // ?? HASH PASSWORD JIKA ADA PERUBAHAN
        if (!empty($data['password'])) {
            $data['password'] = Hash::driver('argon')->make($data['password']);
        } else {
            // Hapus password dari array jika kosong (tidak diubah)
            unset($data['password']);
        }
        
        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            
            $path = $request->file('photo')->store('users', 'public');
            $data['photo'] = $path;
        }

        // Update user
        $user->update($data);
        
        // Sync roles
        if ($request->filled('roles')) {
            $user->syncRoles($request->roles);
        }

        // Update supervisor
        $user->supervisor_id = $request->supervisor_id;
        $user->save();

        // Log activity
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log('User updated');

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil diupdate.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        // Prevent deleting self
        if ($user->id === auth()->id()) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        // Prevent deleting super admin
        if ($user->hasRole('super-admin')) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Tidak dapat menghapus Super Admin.');
        }

        // Delete photo
        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }

        $user->delete();

        // Log activity
        activity()
            ->causedBy(auth()->user())
            ->log("User deleted: {$user->name}");

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil dihapus.');
    }

    /**
     * Toggle user active status.
     */
    public function toggleActive(User $user)
    {
        if ($user->hasRole('super-admin') && $user->id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat mengubah status Super Admin.'
            ]);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log($user->is_active ? 'User activated' : 'User deactivated');

        return response()->json([
            'success' => true,
            'message' => 'Status user berhasil diubah.',
            'is_active' => $user->is_active
        ]);
    }
}