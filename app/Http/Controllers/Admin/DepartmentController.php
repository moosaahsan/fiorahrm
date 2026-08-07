<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class DepartmentController extends Controller
{
    public function index()
    {
        $this->authorize('view-department');
        
        $managers = \App\Models\User::role(['manager', 'supervisor', 'team-lead'])
            ->whereHas('employee', function($q) {
                $q->accessible();
            })->get();

        $branches = \App\Models\Branch::accessible()->where('is_active', true)->get();
        return view('admin.departments.index', compact('managers', 'branches'));
    }

    public function data()
    {
        $this->authorize('view-department');
        $departments = Department::accessible()->with(['branch', 'manager'])->withCount('teams')->latest();
        $canEdit = auth()->user()->can('edit-department');
        $canDelete = auth()->user()->can('delete-department');

        return DataTables::of($departments)
            ->addIndexColumn()
            ->addColumn('manager', function ($dept) {
                return $dept->manager ? $dept->manager->name : '<span class="text-muted">Unassigned</span>';
            })
            ->addColumn('action', function ($dept) use ($canEdit, $canDelete) {
                $btns = '<div class="d-flex gap-2">';
                if ($canEdit) {
                    $btns .= '
                        <button class="btn-saas-action edit-dept" data-id="'.$dept->id.'" title="Edit Department">
                            <i class="fas fa-pen-nib"></i>
                        </button>';
                }
                if ($canDelete) {
                    $btns .= '
                        <button class="btn-saas-action btn-delete delete-dept" data-id="'.$dept->id.'" title="Delete Department">
                            <i class="fas fa-trash-alt"></i>
                        </button>';
                }
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['action', 'manager'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $this->authorize('create-department');
        $request->validate([
            'name' => 'required|string|max:255|unique:departments',
            'description' => 'nullable|string',
            'manager_id' => 'nullable|exists:users,id',
            'branch_id' => 'nullable|exists:branches,id'
        ]);

        $branchId = $request->branch_id ?? (auth()->user()->employee->branch_id ?? null);

        Department::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'manager_id' => $request->manager_id,
            'branch_id' => $branchId,
        ]);

        return response()->json(['success' => true, 'message' => 'Department created successfully']);
    }

    public function edit(string $id)
    {
        $this->authorize('edit-department');
        $department = Department::accessible()->findOrFail($id);
        return response()->json($department);
    }

    public function update(Request $request, string $id)
    {
        $this->authorize('edit-department');
        $department = Department::accessible()->findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,'.$department->id,
            'description' => 'nullable|string',
            'manager_id' => 'nullable|exists:users,id'
        ]);

        $department->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'manager_id' => $request->manager_id,
            'branch_id' => $request->branch_id ?? $department->branch_id,
        ]);

        return response()->json(['success' => true, 'message' => 'Department updated successfully']);
    }

    public function destroy(string $id)
    {
        $this->authorize('delete-department');
        $department = Department::accessible()->findOrFail($id);
        $department->delete();
        
        return response()->json(['success' => true, 'message' => 'Department deleted successfully']);
    }

    public function getEmployees($id)
    {
        $employees = \App\Models\Employee::whereHas('team', function($q) use ($id) {
                $q->where('department_id', $id);
            })
            ->where('status', 1) // status is 1 for active based on data check
            ->orderBy('name')
            ->get(['id', 'name']);
            
        return response()->json($employees);
    }
}
