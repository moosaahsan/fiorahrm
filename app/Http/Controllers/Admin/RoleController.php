<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    // Modules to hide from role assignment UI (CRM modules not part of HRM)
    private $hiddenModules = ['Leads', 'Deals'];

    public function index()
    {
        $permissions = Permission::whereNotIn('module', $this->hiddenModules)
            ->get()->groupBy('module');
        
        // Custom sort order for modules
        $moduleOrder = [
            'Attendance' => 1,
            'Employees' => 2,
            'Settings' => 3,
            'Organization' => 4,
            'General' => 5
        ];

        $permissions = $permissions->sortBy(function($items, $key) use ($moduleOrder) {
            return $moduleOrder[$key] ?? 99;
        });

        return view('admin.roles.index', compact('permissions'));
    }

    public function data()
    {
        $roles = Role::withCount('permissions')->latest();
        return DataTables::of($roles)
            ->addIndexColumn()
            ->addColumn('action', function ($role) {
                return '
                    <div class="d-flex gap-2">
                        <a href="'.route('admin.roles.permissions', $role->id).'" class="btn-matrix-hub" title="Open Capability Matrix Hub">
                            <i class="mdi mdi-shield-link-variant"></i> <span>Manage Permissions</span>
                        </a>
                        '.($role->name !== 'admin' ? '
                        <button class="btn-saas-action edit-role-modal" data-id="'.$role->id.'" title="Rename Access Layer">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-saas-action btn-delete delete-role" data-id="'.$role->id.'" title="Terminate Access Layer">
                            <i class="fas fa-trash-alt"></i>
                        </button>' : '').'
                    </div>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function permissions(Role $role)
    {
        $this->authorize('manage-settings');
        
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        $permissions = Permission::whereNotIn('module', $this->hiddenModules)
            ->get()->groupBy('module');
        
        $moduleOrder = [
            'Attendance' => 1, 'Breaks' => 2, 'Leaves' => 3, 'Holidays' => 4,
            'Employees' => 5,
            'Settings' => 6, 'Organization' => 7, 'General' => 8
        ];

        $permissions = $permissions->sortBy(function($items, $key) use ($moduleOrder) {
            return $moduleOrder[$key] ?? 99;
        });

        return view('admin.roles.permissions', compact('role', 'permissions', 'rolePermissions'));
    }

    public function updatePermissions(Request $request, Role $role)
    {
        $this->authorize('manage-settings');

        if ($role->name === 'admin' && auth()->user()->hasRole('admin')) {
             // System admin protection - though usually they have all perms
        }

        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($request->has('permissions')) {
            $permissionNames = Permission::whereIn('id', $request->permissions)->pluck('name');
            $role->syncPermissions($permissionNames);
        } else {
            $role->syncPermissions([]);
        }

        return response()->json([
            'success' => true, 
            'message' => 'Access layer protocols updated for ' . $role->name
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);

        if ($request->has('permissions')) {
            $permissionNames = Permission::whereIn('id', $request->permissions)->pluck('name');
            $role->syncPermissions($permissionNames);
        }

        return response()->json([
            'success' => true, 
            'message' => 'Role identity created. Redirecting to capabilities matrix...',
            'role_id' => $role->id
        ]);
    }

    public function edit(string $id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        return response()->json($role);
    }

    public function update(Request $request, string $id)
    {
        $role = Role::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,'.$role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($role->name !== 'admin') {
            $role->update(['name' => $request->name]);
        }

        if ($request->has('permissions')) {
            $permissionNames = Permission::whereIn('id', $request->permissions)->pluck('name');
            $role->syncPermissions($permissionNames);
        } else {
            $role->syncPermissions([]);
        }

        return response()->json(['success' => true, 'message' => 'Role and permissions updated successfully']);
    }

    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);
        
        if ($role->name === 'admin') {
            return response()->json(['success' => false, 'message' => 'System administrator role cannot be deleted'], 403);
        }

        $role->delete();
        return response()->json(['success' => true, 'message' => 'Role deleted successfully']);
    }
}
