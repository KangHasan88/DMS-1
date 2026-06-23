<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
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
        $currentUser = $request->user();
        $branchScope = $currentUser?->scopedCompanyBranchId();
        $companyBranches = $this->availableBranches();
        $users = User::with('roles', 'companyBranch')
            ->when($branchScope, fn ($query) => $query->where('company_branch_id', $branchScope))
            ->when($request->search, function($query, $search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->role, function($query, $role) {
                $query->whereHas('roles', function($q) use ($role) {
                    $q->where('name', $role);
                });
            })
            ->when($request->status, function($query, $status) {
                $query->where('is_active', $status === 'active');
            })
            ->when($request->company_branch_id && !$branchScope, function($query) use ($request) {
                $branchId = $request->company_branch_id;
                $query->where('company_branch_id', $branchId);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $roles = Role::all()->pluck('name');

        return view('admin.users.index', compact('users', 'roles', 'companyBranches'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = $this->availableRoles();
        $branchScope = auth()->user()?->scopedCompanyBranchId();
        $supervisors = User::role(['manager', 'admin'])
            ->when($branchScope, fn ($query) => $query->where('company_branch_id', $branchScope))
            ->get();
        $companyBranches = $this->availableBranches();
        
        return view('admin.users.create', compact('roles', 'supervisors', 'companyBranches'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(UserRequest $request)
    {
        $data = $request->validated();
        $branchScope = $request->user()?->scopedCompanyBranchId();
        if ($branchScope) {
            $data['company_branch_id'] = $branchScope;
        }
        
        // FIX: HAPUS HASH DI SINI - BIAHKAN MUTATOR YANG BEKERJA
        // Jangan hash password di controller karena sudah ada mutator di model
        
        // Handle photo upload
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('users', 'public');
            $data['photo'] = $path;
        }

        // Create user - password akan otomatis di-hash oleh mutator setPasswordAttribute
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

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil dibuat.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $this->authorizeBranchAccess($user);

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
        $this->authorizeBranchAccess($user);

        $roles = $this->availableRoles();
        $branchScope = auth()->user()?->scopedCompanyBranchId();
        $supervisors = User::role(['manager', 'admin'])
            ->where('id', '!=', $user->id)
            ->when($branchScope, fn ($query) => $query->where('company_branch_id', $branchScope))
            ->get();
        $companyBranches = $this->availableBranches($user->company_branch_id);
        
        return view('admin.users.edit', compact('user', 'roles', 'supervisors', 'companyBranches'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UserRequest $request, User $user)
    {
        $this->authorizeBranchAccess($user);

        $data = $request->validated();
        $branchScope = $request->user()?->scopedCompanyBranchId();
        if ($branchScope) {
            $data['company_branch_id'] = $branchScope;
        }
        
        // FIX: JANGAN HASH PASSWORD DI SINI - BIAHKAN MUTATOR YANG BEKERJA
        // Cukup set password apa adanya, nanti otomatis di-hash oleh mutator
        // Tapi kalau password tidak diisi, hapus dari array
        
        if (empty($data['password'])) {
            unset($data['password']);
        }
        // Jangan hash! Biarkan mutator yang handle
        
        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            $path = $request->file('photo')->store('users', 'public');
            $data['photo'] = $path;
        }

        // Update user - password akan otomatis di-hash oleh mutator jika ada
        $user->update($data);
        
        // Sync roles
        if ($request->filled('roles')) {
            $user->syncRoles($request->roles);
        }

        // Update supervisor
        $user->supervisor_id = $request->supervisor_id;
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil diupdate.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        $this->authorizeBranchAccess($user);

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

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User {$name} berhasil dihapus.");
    }

    /**
     * Toggle user active status.
     */
    public function toggleActive(User $user)
    {
        $this->authorizeBranchAccess($user);

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat mengubah status akun sendiri.'
            ]);
        }

        if ($user->hasRole('super-admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat mengubah status Super Admin.'
            ]);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Status user berhasil diubah.',
            'is_active' => $user->is_active
        ]);
    }

    private function availableRoles()
    {
        return Role::query()
            ->when(!auth()->user()?->hasRole('super-admin'), fn ($query) => $query->where('name', '!=', 'super-admin'))
            ->get();
    }

    private function availableBranches(?int $currentBranchId = null)
    {
        $branches = CompanyProfile::defaultProfile()
            ->branches()
            ->where(function ($query) use ($currentBranchId) {
                $query->where('is_active', true);

                if ($currentBranchId) {
                    $query->orWhere('id', $currentBranchId);
                }
            });
        $branchScope = auth()->user()?->scopedCompanyBranchId();

        return $branches
            ->when($branchScope, fn ($query) => $query->where('id', $branchScope))
            ->get();
    }

    private function authorizeBranchAccess(User $user): void
    {
        $branchScope = auth()->user()?->scopedCompanyBranchId();

        abort_if($branchScope && (int) $user->company_branch_id !== (int) $branchScope, 403);
    }
}
