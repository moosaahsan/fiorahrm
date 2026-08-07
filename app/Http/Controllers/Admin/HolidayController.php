<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyOffDay;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HolidayController extends Controller
{
    /**
     * Get scoped teams based on current user's role.
     */
    private function getScopedTeams()
    {
        return \App\Models\Team::accessible()->get();
    }

    public function index()
    {
        $this->authorize('view-holidays');
        
        $branchId = session('active_branch_id') ?? (auth()->user()->employee->branch_id ?? null);
        $teams = \App\Models\Team::accessible()->where('is_active', true)->get();
        
        $summary = [
            'total' => CompanyOffDay::accessible()->count(),
            'upcoming' => CompanyOffDay::accessible()->where('start_date', '>', now())->count(),
            'global' => CompanyOffDay::accessible()->doesntHave('teams')->count(),
            'regional' => CompanyOffDay::accessible()->has('teams')->count(),
        ];
        
        $branches = \App\Models\Branch::accessible()->where('is_active', true)->get();
        return view('admin.holidays.index', compact('teams', 'summary', 'branches'));
    }

    public function data(Request $request)
    {
        $this->authorize('view-holidays');
        $user = auth()->user();
        $query = CompanyOffDay::accessible()->with(['teams', 'branch']);

        // No need for manual scoping if using accessible() trait
        // However, we maintain existing filtered logic if needed for specific use cases
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('team_id')) {
            $query->whereHas('teams', function($q) use ($request) {
                $q->where('teams.id', $request->team_id);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return \DataTables::of($query)
            ->addColumn('team_info', function($row) {
                if ($row->teams->count() > 0) {
                    return $row->teams->pluck('name')->toArray();
                }
                return ['Global (All Teams)'];
            })
            ->addColumn('date_range', function($row) {
                return $row->start_date->format('d M Y') . ' — ' . $row->end_date->format('d M Y');
            })
            ->addColumn('status', function($row) {
                if ($row->start_date > now()) return 'Upcoming';
                if ($row->end_date < now()) return 'Past';
                return 'Active';
            })
            ->make(true);
    }

    public function store(Request $request)
    {
        $this->authorize('create-holiday');
        $request->validate([
            'note' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|string',
            'note' => 'nullable|string',
            'team_ids' => 'nullable|array',
            'team_ids.*' => 'exists:teams,id',
            'branch_id' => 'nullable|exists:branches,id'
        ]);

        $branchId = $request->branch_id ?? (session('active_branch_id') ?? (auth()->user()->employee->branch_id ?? null));

        $holiday = CompanyOffDay::create(array_merge($request->except(['team_ids', 'branch_id']), ['branch_id' => $branchId]));
        
        if ($request->has('team_ids')) {
            $holiday->teams()->sync($request->team_ids);
        }

        return redirect()->route('admin.holidays.index')->with('success', 'Holiday added successfully.');
    }

    public function update(Request $request, $id)
    {
        $this->authorize('edit-holiday');
        $request->validate([
            'note' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|string',
            'note' => 'nullable|string',
            'team_ids' => 'nullable|array',
            'team_ids.*' => 'exists:teams,id',
            'branch_id' => 'nullable|exists:branches,id'
        ]);

        $holiday = CompanyOffDay::accessible()->findOrFail($id);
        
        $branchId = $request->branch_id ?? $holiday->branch_id;
        
        $holiday->update(array_merge($request->except(['team_ids', 'branch_id']), ['branch_id' => $branchId]));
        
        // Sync teams
        $holiday->teams()->sync($request->input('team_ids', []));

        return redirect()->route('admin.holidays.index')->with('success', 'Holiday updated successfully.');
    }

    public function destroy($id)
    {
        $this->authorize('delete-holiday');
        $holiday = CompanyOffDay::accessible()->findOrFail($id);
        $holiday->delete();

        return redirect()->route('admin.holidays.index')->with('success', 'Holiday deleted successfully.');
    }
}
