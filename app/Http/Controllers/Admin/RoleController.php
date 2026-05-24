<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view roles')->only(['index', 'show']);
        $this->middleware('permission:create roles')->only(['create', 'store']);
        $this->middleware('permission:edit roles')->only(['edit', 'update']);
        $this->middleware('permission:delete roles')->only(['destroy']);
    }

    public function index()
    {
        $roles = Role::with('permissions')->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::all()->groupBy(function($permission) {
            return explode(' ', $permission->name)[1] ?? 'other';
        });
        
        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'array'
        ]);

        $role = Role::create(['name' => $request->name]);
        
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role berhasil dibuat.');
    }

    public function edit(Role $role)
    {
        if ($role->name === 'super-admin') {
            return redirect()
                ->route('admin.roles.index')
                ->with('error', 'Super Admin role tidak dapat diedit.');
        }

        $permissions = Permission::all()->groupBy(function($permission) {
            return explode(' ', $permission->name)[1] ?? 'other';
        });
        
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        if ($role->name === 'super-admin') {
            return redirect()
                ->route('admin.roles.index')
                ->with('error', 'Super Admin role tidak dapat diedit.');
        }

        $request->validate([
            'name' => 'required|unique:roles,name,' . $role->id,
            'permissions' => 'array'
        ]);

        $role->update(['name' => $request->name]);
        
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role berhasil diupdate.');
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'super-admin') {
            return response()->json([
                'success' => false,
                'message' => 'Super Admin role tidak dapat dihapus.'
            ]);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil dihapus.'
        ]);
    }
}