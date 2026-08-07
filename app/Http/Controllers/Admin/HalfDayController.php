<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HalfDay;
use App\Models\Attendance;
use App\Helpers\ActivityLogger;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class HalfDayController extends Controller
{
    public function index()
    {
        ActivityLogger::log('view', 'HalfDay', ActivityLogger::format('view', 'HalfDay', 'All Records', 'Listing'));
        return view('admin.half_days.index');
    }

    public function data(Request $request)
    {
        $query = HalfDay::accessible()->with(['employee', 'attendance.shift'])
            ->leftJoin('employees', 'half_days.emp_id', '=', 'employees.id')
            ->select('half_days.*', 'employees.profile_pic', 'employees.name as emp_name');

        if ($request->filled('name')) {
            $query->where('employees.name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('date')) {
            $query->whereDate('half_days.date', $request->date);
        }

        // Calculate aggregate stats for the filtered dataset
        $statsQuery = clone $query;
        $totalIncidents = $statsQuery->count();
        
        return DataTables::of($query)
            ->addColumn('profile_pic_url', function ($row) {
                return $row->profile_pic && \Storage::disk('public')->exists($row->profile_pic) ? \Storage::disk('public')->url($row->profile_pic) : null;
            })
            ->editColumn('date', fn($row) => \Carbon\Carbon::parse($row->date)->format('d M, Y'))
            ->editColumn('reason', function($row) {
                $text = ucfirst(str_replace('_', ' ', $row->reason ?? 'Policy Violation'));
                $icon = str_contains(strtolower($text), 'late') ? 'clock' : 'signpost-split';
                return '<span class="saas-status-badge late"><i class="fas fa-' . $icon . ' mr-1"></i>' . $text . '</span>';
            })
            ->addColumn('shift', fn($row) => $row->attendance->shift->shift_name ?? 'N/A')
            ->addColumn('check_in', fn($row) => $row->attendance->check_in ? date('g:i A', strtotime($row->attendance->check_in)) : '-')
            ->addColumn('check_out', fn($row) => $row->attendance->check_out ? date('g:i A', strtotime($row->attendance->check_out)) : '-')
            ->addColumn('working_duration', fn($row) => $row->attendance->working_duration ?? '-')
            ->rawColumns(['reason']) 
            ->with([
                'total_incidents' => $totalIncidents,
                'short_shift_count' => (clone $statsQuery)->where('reason', 'like', '%worked less%')->count(),
                'late_start_count' => (clone $statsQuery)->where('reason', 'like', '%late more%')->count(),
            ])
            ->make(true);
    }

    public function evaluateHalfDay($attendanceId)
    {
        $attendance = Attendance::with(['employee', 'shift'])->find($attendanceId);
        if (!$attendance || !$attendance->check_in || !$attendance->check_out) {
            return;
        }

        $checkIn = Carbon::parse($attendance->check_in);
        $checkOut = Carbon::parse($attendance->check_out);
        $shiftIn = Carbon::parse($attendance->shift->start_time);
        $lateMinutes = $checkIn->diffInMinutes($shiftIn, false);
        $workDuration = $checkOut->diffInMinutes($checkIn);

        $reason = null;

        if ($lateMinutes > 120) {
            $reason = "Late more than 2 hours";
        } elseif ($workDuration < 240) {
            $reason = "Worked less than 4 hours";
        }

        if ($reason) {
            $alreadyExists = HalfDay::where('attendance_id', $attendanceId)->exists();
            if (!$alreadyExists) {
                $halfDay = HalfDay::create([
                    'emp_id' => $attendance->emp_id,
                    'attendance_id' => $attendanceId,
                    'reason' => $reason,
                ]);

                $empName = $attendance->employee->name ?? 'Unknown';
                ActivityLogger::log(
                    'create',
                    'HalfDay',
                    ActivityLogger::format('create', 'HalfDay', $empName, $attendance->emp_id) .
                    "<br><small>Reason: <em>{$reason}</em></small>"
                );
            }
        }
    }

    public function destroy($id)
    {
        $halfDay = HalfDay::accessible()->with('employee')->findOrFail($id);
        $empName = $halfDay->employee->name ?? 'Unknown';

        $halfDay->delete();

        ActivityLogger::log(
            'deleted',
            'HalfDay',
            ActivityLogger::format('deleted', 'HalfDay', $empName, $halfDay->emp_id)
        );

        return redirect()->back()->with('success', 'Half-day record deleted.');
    }
}
