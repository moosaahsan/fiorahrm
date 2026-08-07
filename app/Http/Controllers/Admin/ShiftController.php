<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\EmployeeShift;
use App\Models\Employee;
use Yajra\DataTables\DataTables;
use App\Helpers\ActivityLogger;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view-shift');
        if ($request->ajax()) {
            $query = Shift::accessible();

            if ($request->filled('status')) {
                $query->where('is_active', $request->status);
            }

            $canEdit = auth()->user()->can('edit-shift');
            $canDelete = auth()->user()->can('delete-shift');

            return DataTables::of($query)
                ->addColumn('action', function ($shift) use ($canEdit, $canDelete) {
                    return view('admin.shifts.partials.actions', compact('shift', 'canEdit', 'canDelete'))->render();
                })
                ->addColumn('status', function ($shift) use ($canEdit) {
                    return view('admin.shifts.partials.status_toggle', compact('shift', 'canEdit'))->render();
                })
                ->addColumn('branch_name', function ($shift) {
                    return $shift->branch ? $shift->branch->name : '<span class="text-muted">Unassigned</span>';
                })
                ->editColumn('start_time', fn($shift) => date('g:i A', strtotime($shift->start_time)))
                ->editColumn('end_time', fn($shift) => date('g:i A', strtotime($shift->end_time)))
                ->editColumn('grace_period', fn($shift) => $shift->grace_period . ' min')
                ->editColumn('late_mark', fn($shift) => $shift->late_mark ? date('g:i A', strtotime($shift->late_mark)) : '-')
                ->editColumn('halfday_mark', fn($shift) => $shift->halfday_mark ? date('g:i A', strtotime($shift->halfday_mark)) : '-')
                ->addColumn('midnight', function ($shift) {
                    return $shift->crosses_midnight
                        ? '<span class="saas-badge-custom badge-midnight-saas"><i class="fas fa-moon me-1"></i> Midnight</span>'
                        : '<span class="saas-badge-custom badge-standard-saas"><i class="fas fa-sun me-1"></i> Standard</span>';
                })
                ->rawColumns(['action', 'status', 'midnight', 'branch_name'])
                ->make(true);
        }

        $branches = \App\Models\Branch::accessible()->where('is_active', true)->get();
        return view('admin.shifts.index', compact('branches'));
    }

    public function store(Request $request)
    {
        $this->authorize('create-shift');
        $validated = $request->validate([
            'shift_name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'grace_period' => 'nullable|integer|min:0',
            'late_mark' => 'nullable|date_format:H:i',
            'halfday_mark' => 'nullable|date_format:H:i',
            'friday_break' => 'nullable|integer|min:0',
            'otherday_break' => 'nullable|integer|min:0',
            'halfday_break' => 'nullable|integer|min:0',
            'crosses_midnight' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $validated['grace_period'] = $request->grace_period ?? 0;
        $validated['crosses_midnight'] = $request->has('crosses_midnight');
        $validated['is_active'] = $request->has('is_active');
        $validated['branch_id'] = $request->branch_id ?? (auth()->user()->employee->branch_id ?? null);

        $shift = Shift::create($validated);

        ActivityLogger::log('created', 'Shift', ActivityLogger::format('created', 'Shift', $shift->shift_name, $shift->id));

        return response()->json([
            'success' => true,
            'message' => 'Shift created successfully.'
        ]);
    }

    public function show($id)
    {
        $this->authorize('view-shift');
        $shift = Shift::accessible()->findOrFail($id);
        return response()->json($shift);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('edit-shift');
        $request->validate([
            'shift_name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'grace_period' => 'nullable|integer|min:0',
            'late_mark' => 'nullable|date_format:H:i',
            'halfday_mark' => 'nullable|date_format:H:i',
            'crosses_midnight' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $shift = Shift::accessible()->findOrFail($id);

        $shift->update([
            'shift_name' => $request->shift_name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'grace_period' => $request->grace_period ?? 0,
            'late_mark' => $request->late_mark ?? $shift->late_mark,
            'friday_break' => $request->friday_break ?? $shift->friday_break,
            'otherday_break' => $request->otherday_break ?? $shift->otherday_break,
            'halfday_break' => $request->halfday_break ?? $shift->halfday_break,
            'halfday_mark' => $request->halfday_mark ?? $shift->halfday_mark,
            'crosses_midnight' => $request->has('crosses_midnight'),
            'is_active' => $request->has('is_active'),
            'branch_id' => $request->branch_id ?? $shift->branch_id,
        ]);

        ActivityLogger::log('updated', 'Shift', ActivityLogger::format('updated', 'Shift', $shift->shift_name, $shift->id));

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Shift updated successfully.');
    }
    public function destroy($id)
    {
        $this->authorize('delete-shift');
        $shift = Shift::accessible()->findOrFail($id);
        $shiftName = $shift->shift_name;

        // Soft delete the shift
        $shift->delete();

        // Log activity
        ActivityLogger::log(
            'deleted',
            'Shift',
            ActivityLogger::format('deleted', 'Shift', $shiftName, $id)
        );

        return response()->json(['success' => true, 'message' => 'Shift deleted successfully.']);
    }


    public function toggleStatus(Request $request, $id)
    {
        $this->authorize('edit-shift');
        $shift = Shift::accessible()->findOrFail($id);
        $shift->is_active = $request->is_active ? 1 : 0;
        $shift->save();

        $status = $shift->is_active ? 'activated' : 'deactivated';

        ActivityLogger::log('status_toggle', 'Shift', ActivityLogger::format($status, 'Shift', $shift->shift_name, $shift->id));

        return response()->json(['success' => true]);
    }
}
