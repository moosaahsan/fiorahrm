<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $action = $request->get('action') ?? '';
        $module = $request->get('module') ?? '';

        $query = ActivityLog::query()
            ->leftJoin('users', 'activity_logs.user_id', '=', 'users.id')
            ->select('activity_logs.*', 'users.name as performed_by')
            ->latest('activity_logs.created_at');

        if ($action !== '') {
            $query->where('activity_logs.action', $action);
        }

        if ($module !== '') {
            $query->where('activity_logs.module', 'like', '%' . $module . '%');
        }

        if ($request->ajax()) {
            return datatables()->eloquent($query)
                ->editColumn('performed_by', function ($log) {
                    return $log->performed_by ?? 'System';
                })
                ->editColumn('description', function ($log) {
                    return $log->description; // Already contains HTML
                })
                ->addColumn('action_badge', function ($log) {
                    return '<span class="badge badge-info">' . ucfirst($log->action) . '</span>';
                })
                ->editColumn('created_at', function ($log) {
                    return \Carbon\Carbon::parse($log->created_at)->diffForHumans();
                })
                ->rawColumns(['description', 'action_badge']) // 👈 Enable HTML rendering
                ->make(true);
        }

        $logs = $query->paginate(25);
        return view('admin.activity_logs.index', compact('logs'));
    }

}
