<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\Employee;
use Yajra\DataTables\Facades\DataTables;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('view-team');
        $teams = Team::accessible()->withCount('employees')->with('department', 'leader', 'branch')->latest()->get();
        $departments = \App\Models\Department::accessible()->where('is_active', true)->get();
        
        $branchId = session('active_branch_id') ?? (auth()->user()->employee->branch_id ?? null);
        
        $leads = \App\Models\User::role(['manager', 'team-lead', 'supervisor'])
            ->whereHas('employee', function($q) use ($branchId) {
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })->get();

        $branches = \App\Models\Branch::accessible()->where('is_active', true)->get();
        return view('admin.teams.index', compact('teams', 'departments', 'leads', 'branches'));
    }

    public function teamData()
    {
        $this->authorize('view-team');
        $teams = Team::accessible()->withCount('employees')->with(['department', 'leader', 'branch'])->latest();
        $canEdit = auth()->user()->can('edit-team');
        $canDelete = auth()->user()->can('delete-team');

        return DataTables::of($teams)
            ->addIndexColumn()
            ->addColumn('department', function ($team) {
                return $team->department ? $team->department->name : '<span class="text-muted">Unassigned</span>';
            })
            ->addColumn('leader', function ($team) {
                return $team->leader ? $team->leader->name : '<span class="text-muted">No Leader</span>';
            })
            ->editColumn('employees_count', function($team) use ($canEdit) {
                $count = $team->employees_count;
                $html = '<div class="d-flex align-items-center justify-content-between">';
                $html .= '<span class="badge border bg-light text-dark px-3 mt-1" style="font-size: 0.85rem; border-radius: 8px;">' . $count . ' Personnel</span>';
                if ($canEdit) {
                    $html .= '
                        <button class="btn btn-saas-manage manage-members" data-id="' . $team->id . '" data-name="' . $team->name . '">
                            <i class="fas fa-vector-square"></i> Composition
                        </button>';
                }
                $html .= '</div>';
                return $html;
            })
            ->addColumn('action', function ($team) use ($canEdit, $canDelete) {
                $btns = '<div class="d-flex gap-2">';
                if ($canEdit) {
                    $btns .= '
                        <button class="btn-saas-action edit-team" data-id="' . $team->id . '" title="Edit Architecture">
                            <i class="fas fa-pen-nib"></i>
                        </button>';
                }
                if ($canDelete) {
                    $btns .= '
                        <button class="btn-saas-action btn-delete delete-team" data-id="' . $team->id . '" title="Decommission Group">
                            <i class="fas fa-trash-alt"></i>
                        </button>';
                }
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['action', 'department', 'leader', 'employees_count'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:teams',
            'department_id' => 'required|exists:departments,id',
            'leader_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'branch_id' => 'nullable|exists:branches,id'
        ]);

        $branchId = $request->branch_id;
        if (!$branchId) {
            $dept = \App\Models\Department::find($request->department_id);
            $branchId = $dept->branch_id ?? null;
        }

        Team::create(array_merge($request->all(), ['branch_id' => $branchId]));
        return response()->json(['success' => true, 'message' => 'Team created successfully']);
    }

    public function edit(string $id)
    {
        $this->authorize('edit-team');
        $team = Team::accessible()->findOrFail($id);
        return response()->json($team);
    }

    public function update(Request $request, string $id)
    {
        $this->authorize('edit-team');
        $team = Team::accessible()->findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255|unique:teams,name,'.$team->id,
            'department_id' => 'required|exists:departments,id',
            'leader_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'branch_id' => 'nullable|exists:branches,id'
        ]);

        $branchId = $request->branch_id;
        if (!$branchId) {
            $dept = \App\Models\Department::find($request->department_id);
            $branchId = $dept->branch_id ?? $team->branch_id;
        }

        $team->update(array_merge($request->all(), ['branch_id' => $branchId]));
        return response()->json(['success' => true, 'message' => 'Team updated successfully']);
    }

    public function destroy(string $id)
    {
        $this->authorize('delete-team');
        $team = Team::accessible()->findOrFail($id);
        // Ensure to remove team_id from employees before deleting or let foreign key SET NULL handle it in the DB
        Employee::where('team_id', $team->id)->update(['team_id' => null]);
        $team->delete();
        
        return response()->json(['success' => true, 'message' => 'Team deleted successfully']);
    }

    /**
     * Get members for assignment modal
     */
    public function getMembers(string $id)
    {
        $this->authorize('edit-team');
        $team = Team::accessible()->findOrFail($id);
        $branchId = $team->branch_id;
        
        // Fetch only employees belonging to the team's branch
        // and who are either unassigned OR already in this team
        $employees = Employee::select('id', 'name', 'position', 'team_id')
            ->where('branch_id', $branchId)
            ->where(function($q) use ($id) {
                $q->whereNull('team_id')
                  ->orWhere('team_id', $id);
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'team' => $team,
            'employees' => $employees
        ]);
    }

    /**
     * Update team members synchronization
     */
    public function updateMembers(Request $request, string $id)
    {
        $this->authorize('edit-team');
        $team = Team::accessible()->findOrFail($id);
        $employeeIds = $request->input('employee_ids', []); // Array of IDs to be in this team

        // 1. Remove current members who are NOT in the new list
        Employee::where('team_id', $id)
            ->whereNotIn('id', $employeeIds)
            ->update(['team_id' => null]);

        // 2. Assign new members
        if (!empty($employeeIds)) {
            Employee::whereIn('id', $employeeIds)
                ->update(['team_id' => $id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Team members updated successfully'
        ]);
    }
}
