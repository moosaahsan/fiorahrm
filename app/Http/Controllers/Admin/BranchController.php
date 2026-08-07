<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Branch;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class BranchController extends Controller
{
    public function index()
    {
        $this->authorize('manage-settings');
        return view('admin.branches.index');
    }

    public function data()
    {
        $this->authorize('manage-settings');
        $branches = Branch::accessible()->withCount(['employees', 'departments'])->latest();

        return DataTables::of($branches)
            ->addIndexColumn()
            ->addColumn('status', function ($branch) {
                $class = $branch->is_active ? 'bg-success text-white' : 'bg-danger text-white';
                $text = $branch->is_active ? 'Active' : 'Inactive';
                return '<span class="badge '.$class.'">'.$text.'</span>';
            })
            ->addColumn('metrics', function ($branch) {
                return '<span class="badge bg-info text-white me-2">'.$branch->employees_count.' Employees</span>' .
                       '<span class="badge bg-secondary text-white">'.$branch->departments_count.' Depts</span>';
            })
            ->addColumn('action', function ($branch) {
                return '
                    <div class="d-flex gap-2">
                        <button class="btn-saas-action edit-branch" data-id="'.$branch->id.'" title="Edit Office">
                            <i class="fas fa-pen-nib"></i>
                        </button>
                        <button class="btn-saas-action btn-delete delete-branch" data-id="'.$branch->id.'" title="Delete Office">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>';
            })
            ->rawColumns(['action', 'status', 'metrics'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-settings');
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:branches',
            'address' => 'nullable|string',
            'timezone' => 'required|string',
            'is_active' => 'boolean'
        ]);

        Branch::create($request->all());

        return response()->json(['success' => true, 'message' => 'Office branch created successfully']);
    }

    public function edit(string $id)
    {
        $this->authorize('manage-settings');
        $branch = Branch::accessible()->findOrFail($id);
        return response()->json($branch);
    }

    public function update(Request $request, string $id)
    {
        $this->authorize('manage-settings');
        $branch = Branch::accessible()->findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:branches,code,'.$branch->id,
            'address' => 'nullable|string',
            'timezone' => 'required|string',
            'is_active' => 'boolean'
        ]);

        $branch->update($request->all());

        return response()->json(['success' => true, 'message' => 'Office branch updated successfully']);
    }

    public function destroy(string $id)
    {
        $this->authorize('manage-settings');
        $branch = Branch::accessible()->findOrFail($id);
        
        if ($branch->employees()->exists()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete branch with active employees'], 422);
        }

        $branch->delete();
        
        return response()->json(['success' => true, 'message' => 'Office branch deleted successfully']);
    }

    /**
     * Set active branch context for Admin
     */
    public function setContext(Request $request)
    {
        $request->validate(['branch_id' => 'nullable|exists:branches,id']);
        
        if ($request->branch_id) {
            session(['active_branch_id' => $request->branch_id]);
            $branch = Branch::find($request->branch_id);
            return response()->json(['success' => true, 'message' => 'Switched context to ' . $branch->name]);
        }
        
        session()->forget('active_branch_id');
        return response()->json(['success' => true, 'message' => 'Switched to Global View']);
    }
}
