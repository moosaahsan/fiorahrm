<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class JobPostingController extends Controller
{
    public function index()
    {
        $this->authorize('manage-job-postings');
        return view('admin.job_postings.index');
    }

    public function getPublicJobs(Request $request)
    {
        $query = JobPosting::where('status', 'Open');

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $jobs = $query->get(['id', 'title', 'category', 'type', 'shift', 'timings', 'description', 'requirements', 'benefits']);

        return response()->json([
            'success' => true,
            'jobs' => $jobs
        ]);
    }

    public function data()
    {
        $this->authorize('manage-job-postings');
        $query = JobPosting::latest();

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('category_badge', function ($row) {
                $class = $row->category === 'Billing' ? 'badge-info' : 'badge-primary';
                return '<span class="badge ' . $class . '">' . $row->category . '</span>';
            })
            ->addColumn('status_badge', function ($row) {
                $class = $row->status === 'Open' ? 'badge-success' : 'badge-danger';
                return '<span class="badge ' . $class . '">' . $row->status . '</span>';
            })
            ->addColumn('action', function ($row) {
                $btn = '<div class="d-flex gap-2">';
                $btn .= '<a href="' . route('admin.job-postings.edit', $row->id) . '" class="btn-saas-action" title="Edit"><i class="fas fa-pen-nib"></i></a>';
                $btn .= '<button class="btn-saas-action delete-job btn-delete" data-id="' . $row->id . '" title="Delete"><i class="fas fa-trash-alt text-danger"></i></button>';
                $btn .= '</div>';
                return $btn;
            })
            ->rawColumns(['category_badge', 'status_badge', 'action'])
            ->make(true);
    }

    public function create()
    {
        $this->authorize('manage-job-postings');
        return view('admin.job_postings.create');
    }

    public function store(Request $request)
    {
        $this->authorize('manage-job-postings');

        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|in:BPO,Billing',
            'type' => 'required|string',
            'shift' => 'required|string',
            'timings' => 'nullable|string',
            'status' => 'required|in:Open,Closed',
            'description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'benefits' => 'nullable|string',
        ]);

        JobPosting::create($request->all());

        return response()->json(['success' => true, 'message' => 'Job vacancy created successfully.']);
    }

    public function edit($id)
    {
        $this->authorize('manage-job-postings');
        $job = JobPosting::findOrFail($id);
        return view('admin.job_postings.edit', compact('job'));
    }

    public function update(Request $request, $id)
    {
        $this->authorize('manage-job-postings');
        $job = JobPosting::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|in:BPO,Billing',
            'type' => 'required|string',
            'shift' => 'required|string',
            'timings' => 'nullable|string',
            'status' => 'required|in:Open,Closed',
            'description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'benefits' => 'nullable|string',
        ]);

        $job->update($request->all());

        return response()->json(['success' => true, 'message' => 'Job vacancy updated successfully.']);
    }

    public function destroy($id)
    {
        $this->authorize('manage-job-postings');
        $job = JobPosting::findOrFail($id);
        $job->delete();
        return response()->json(['success' => true, 'message' => 'Job vacancy deleted successfully.']);
    }
}
