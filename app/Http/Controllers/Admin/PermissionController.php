<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class PermissionController extends Controller
{
    public function index()
    {
        return view('admin.permissions.index');
    }

    public function data()
    {
        $permissions = Permission::query()->latest();
        return DataTables::of($permissions)
            ->addIndexColumn()
            ->addColumn('action', function ($permission) {
                return '
                    <div class="d-flex gap-2">
                        <button class="btn-saas-action edit-permission" data-id="'.$permission->id.'" title="Edit Permission">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-saas-action btn-delete delete-permission" data-id="'.$permission->id.'" title="Delete Permission">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'module' => 'required|string|max:255',
        ]);

        Permission::create([
            'name' => strtolower(str_replace(' ', '-', $request->name)),
            'module' => $request->module,
            'guard_name' => 'web'
        ]);

        return response()->json(['success' => true, 'message' => 'Permission created successfully']);
    }

    public function edit($id)
    {
        $permission = Permission::findOrFail($id);
        return response()->json($permission);
    }

    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,'.$permission->id,
            'module' => 'required|string|max:255',
        ]);

        $permission->update([
            'name' => strtolower(str_replace(' ', '-', $request->name)),
            'module' => $request->module
        ]);

        return response()->json(['success' => true, 'message' => 'Permission updated successfully']);
    }

    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();
        
        return response()->json(['success' => true, 'message' => 'Permission deleted successfully']);
    }
}
