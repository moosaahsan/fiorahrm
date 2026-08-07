<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LateArrival;
use App\Models\Employee;
use App\Helpers\ActivityLogger;

class LateArrivalController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view-late-arrivals');
        if ($request->ajax()) {
            $query = LateArrival::accessible()->with('employee')
                ->leftJoin('employees', 'late_arrivals.emp_id', '=', 'employees.id')
                ->select(
                    'late_arrivals.*', 
                    'employees.id as emp_primary_id', 
                    'employees.name as employee_name',
                    'employees.profile_pic'
                );

            if ($request->filled('name')) {
                $query->where('employees.name', 'like', '%' . $request->name . '%');
            }

            if ($request->filled('date')) {
                $query->whereDate('late_arrivals.date', $request->date);
            }

            // Calculate aggregate stats for the filtered dataset
            $statsQuery = clone $query;
            $totalIncidents = $statsQuery->count();
            $totalMinutes = $statsQuery->sum('late_minutes');

            $canEdit = auth()->user()->can('edit-late-arrival');
            $canDelete = auth()->user()->can('delete-late-arrival');

            return datatables()->eloquent($query)
                ->addColumn('formatted_date', function ($row) {
                    return date('d M, Y', strtotime($row->date));
                })
                ->addColumn('profile_pic_url', function($row) {
                    return $row->profile_pic && \Storage::disk('public')->exists($row->profile_pic) ? \Storage::disk('public')->url($row->profile_pic) : null;
                })
                ->addColumn('employee_id_badge', function ($row) {
                    return 'AST-' . $row->emp_primary_id;
                })
                ->addColumn('late_duration', function ($row) {
                    $hours = floor($row->late_minutes / 60);
                    $minutes = $row->late_minutes % 60;
                    $text = ($hours ? $hours . 'h ' : '') . ($minutes ? $minutes . 'm' : '');
                    return '<span class="saas-status-badge late"><i class="fas fa-clock mr-1"></i>' . $text . '</span>';
                })
                ->addColumn('action', function ($row) use ($canEdit, $canDelete) {
                    $btns = '<div class="d-flex gap-2 justify-content-end">';
                    if ($canEdit) {
                        $btns .= '<button class="btn-saas-action edit_late" data-id="' . $row->id . '" title="Edit Incident"><i class="fas fa-edit"></i></button>';
                    }
                    if ($canDelete) {
                        $btns .= '<button class="btn-saas-action delete_late" data-id="' . $row->id . '" title="Delete Record"><i class="fas fa-trash"></i></button>';
                    }
                    $btns .= '</div>';
                    return $btns;
                })
                ->editColumn('scheduled_start', function ($row) {
                    return date('g:i A', strtotime($row->scheduled_start));
                })
                ->editColumn('actual_check_in', function ($row) {
                    return date('g:i A', strtotime($row->actual_check_in));
                })
                ->rawColumns(['late_duration', 'action'])
                ->with([
                    'total_incidents' => $totalIncidents,
                    'total_minutes' => $totalMinutes,
                    'lost_hours' => round($totalMinutes / 60, 1)
                ])
                ->make(true);
        }

        return view('admin.late_arrivals.index');
    }


    public function edit($id)
    {
        $this->authorize('edit-late-arrival');
        $lateArrival = LateArrival::accessible()->with('employee')->findOrFail($id);
        $employeeName = $lateArrival->employee->name ?? 'Unknown';

        ActivityLogger::log('edit', 'LateArrival', ActivityLogger::format('edit', 'LateArrival', $employeeName, $id));

        return view('admin.late_arrivals.edit', compact('lateArrival'));
    }

    public function update(Request $request, $id)
    {
        $this->authorize('edit-late-arrival');
        $late = LateArrival::accessible()->with('employee')->findOrFail($id);

        $request->validate([
            'late_reason' => 'nullable|string|max:255',
            'actual_check_in' => 'required|date_format:H:i',
            'scheduled_start' => 'required|date_format:H:i',
        ]);

        $late->update([
            'late_reason' => $request->late_reason,
            'actual_check_in' => $request->actual_check_in,
            'scheduled_start' => $request->scheduled_start,
        ]);

        $employeeName = $late->employee->name ?? 'Unknown';

        ActivityLogger::log('updated', 'LateArrival', ActivityLogger::format('updated', 'LateArrival', $employeeName, $id));

        return response()->json(['success' => true]);
    }

    public function show($id)
    {
        $this->authorize('view-late-arrivals');
        $late = LateArrival::accessible()->with(['employee', 'shift'])->findOrFail($id);
        $employeeName = $late->employee->name ?? 'Unknown';

        ActivityLogger::log('view', 'LateArrival', ActivityLogger::format('view', 'LateArrival', $employeeName, $id));

        return view('admin.late_arrivals.show', compact('late'));
    }

    public function destroy($id)
    {
        $this->authorize('delete-late-arrival');
        $late = LateArrival::accessible()->with('employee')->findOrFail($id);
        $employeeName = $late->employee->name ?? 'Unknown';
        $late->delete();

        ActivityLogger::log('deleted', 'LateArrival', ActivityLogger::format('deleted', 'LateArrival', $employeeName, $id));

        return response()->json(['success' => true]);
    }

    public function trash()
    {
        $this->authorize('view-late-arrivals');
        $lateArrivals = LateArrival::accessible()->onlyTrashed()->with('employee')->paginate(20);

        ActivityLogger::log('view', 'LateArrival', ActivityLogger::format('view', 'LateArrival', 'Recycle Bin', 'Trash'));

        return view('admin.late_arrivals.trash', compact('lateArrivals'));
    }

    public function restore($id)
    {
        $this->authorize('delete-late-arrival'); // Restoring is similar to deleting privileges
        $late = LateArrival::accessible()->withTrashed()->with('employee')->findOrFail($id);
        $employeeName = $late->employee->name ?? 'Unknown';
        $late->restore();

        ActivityLogger::log('restore', 'LateArrival', ActivityLogger::format('restore', 'LateArrival', $employeeName, $id));

        return redirect()->back()->with('success', 'Record restored.');
    }
    public function clearTrash()
    {
        $this->authorize('delete-late-arrival');
        LateArrival::onlyTrashed()->forceDelete();

        ActivityLogger::log('cleared', 'LateArrival', ActivityLogger::format('cleared', 'LateArrival', 'Recycle Bin', 'Trash'));

        return redirect()->back()->with('success', 'All records cleared from trash.');
    }
}
