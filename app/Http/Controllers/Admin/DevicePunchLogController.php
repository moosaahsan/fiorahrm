<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DevicePunchLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Admin page listing every fingerprint scan the device bridge forwarded, with the name stored
 * on the device and what HRM did with it. Mirrors the status page of the bridge itself.
 */
class DevicePunchLogController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->ajax()) {
            return view('admin.device_punch_logs.index');
        }

        $query = DevicePunchLog::query()->latest('scan_time')->latest('id');

        if ($request->filled('from')) {
            $query->where('scan_time', '>=', Carbon::parse($request->get('from'))->startOfDay());
        }
        if ($request->filled('to')) {
            $query->where('scan_time', '<=', Carbon::parse($request->get('to'))->endOfDay());
        }
        if ($request->filled('device')) {
            $query->where('device', $request->get('device'));
        }

        match ($request->get('status')) {
            'recorded' => $query->where('ok', true),
            'rejected' => $query->where('ok', false)->whereNotNull('employee_id'),
            'unmapped' => $query->whereNull('employee_id'),
            default => null,
        };

        return datatables()->eloquent($query)
            ->editColumn('scan_time', fn ($log) => $log->scan_time->format('d M Y, h:i:s A'))
            ->editColumn('device', function ($log) {
                $class = $log->device === 'IN' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800';

                return '<span class="rounded px-2 py-0.5 text-xs font-bold ' . $class . '">' . e($log->device) . '</span>';
            })
            ->editColumn('device_user_name', fn ($log) => e($log->device_user_name ?: '-'))
            ->editColumn('employee_name', function ($log) {
                return $log->employee_id
                    ? e($log->employee_name)
                    : '<span class="text-slate-400">Not mapped</span>';
            })
            ->editColumn('result', function ($log) {
                $class = $log->ok ? 'text-green-700 font-semibold' : 'text-amber-700';

                return '<span class="' . $class . '">' . e($log->result) . '</span>';
            })
            ->editColumn('source', fn ($log) => '<span class="text-xs text-slate-500">' . e($log->source) . '</span>')
            ->rawColumns(['device', 'employee_name', 'result', 'source'])
            ->make(true);
    }
}
