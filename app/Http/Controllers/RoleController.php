<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     */
    public function index(Request $request)
    {
        $roles = Role::with('permissions')
            ->when($request->search, function($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        $permissions = Permission::orderBy('name')->get()->groupBy(function($permission) {
            // Group permission by prefix (before space)
            $parts = explode(' ', $permission->name);
            return $parts[0] ?? 'Other';
        });

        return view('roles.create', compact('permissions'));
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        try {
            DB::beginTransaction();

            // Create role
            $role = Role::create([
                'name' => strtolower(str_replace(' ', '-', $request->name)),
                'guard_name' => 'web'
            ]);

            // Assign permissions
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            DB::commit();

            return redirect()
                ->route('roles.index')
                ->with('success', "Role {$role->name} berhasil dibuat!");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Gagal membuat role: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role)
    {
        $role->load('permissions');
        
        // Group permissions for display
        $groupedPermissions = $role->permissions->groupBy(function($permission) {
            $parts = explode(' ', $permission->name);
            return $parts[0] ?? 'Other';
        });

        return view('roles.show', compact('role', 'groupedPermissions'));
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role)
    {
        if ($this->isProtectedRole($role)) {
            return redirect()
                ->route('roles.index')
                ->with('error', 'Role sistem tidak dapat diedit.');
        }

        $permissions = Permission::orderBy('name')->get()->groupBy(function($permission) {
            $parts = explode(' ', $permission->name);
            return $parts[0] ?? 'Other';
        });

        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, Role $role)
    {
        if ($this->isProtectedRole($role)) {
            return redirect()
                ->route('roles.index')
                ->with('error', 'Role sistem tidak dapat diedit.');
        }

        $request->validate([
            'name' => 'required|string|max:50|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        try {
            DB::beginTransaction();

            // Update role name
            $role->name = strtolower(str_replace(' ', '-', $request->name));
            $role->save();

            // Sync permissions
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            } else {
                $role->syncPermissions([]);
            }

            DB::commit();

            return redirect()
                ->route('roles.index')
                ->with('success', "Role {$role->name} berhasil diupdate!");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Gagal mengupdate role: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role)
    {
        // Prevent deleting super-admin
        if ($role->name === 'super-admin') {
            return redirect()
                ->route('roles.index')
                ->with('error', 'Tidak dapat menghapus role Super Admin.');
        }

        // Check if role is assigned to any user
        if ($role->users()->count() > 0) {
            return redirect()
                ->route('roles.index')
                ->with('error', 'Role masih digunakan oleh ' . $role->users()->count() . ' user. Tidak dapat dihapus.');
        }

        try {
            $roleName = $role->name;
            $role->delete();

            return redirect()
                ->route('roles.index')
                ->with('success', "Role {$roleName} berhasil dihapus!");

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus role: ' . $e->getMessage());
        }
    }

    /**
     * Show permissions management page for a role.
     */
    public function permissions(Role $role)
    {
        if ($this->isProtectedRole($role)) {
            return redirect()
                ->route('roles.index')
                ->with('error', 'Permission role sistem tidak dapat diubah.');
        }

        $allPermissions = Permission::orderBy('name')->get()->groupBy(function($permission) {
            $parts = explode(' ', $permission->name);
            return $parts[0] ?? 'Other';
        });

        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('roles.permissions', compact('role', 'allPermissions', 'rolePermissions'));
    }

    /**
     * Update permissions for a role.
     */
    public function updatePermissions(Request $request, Role $role)
    {
        if ($this->isProtectedRole($role)) {
            return redirect()
                ->route('roles.index')
                ->with('error', 'Permission role sistem tidak dapat diubah.');
        }

        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        try {
            $role->syncPermissions($request->permissions ?? []);

            return redirect()
                ->route('roles.index')
                ->with('success', "Permission untuk role {$role->name} berhasil diupdate!");

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengupdate permission: ' . $e->getMessage());
        }
    }

    private function isProtectedRole(Role $role): bool
    {
        return $role->name === 'super-admin';
    }
}
