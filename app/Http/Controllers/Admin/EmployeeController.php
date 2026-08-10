<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use App\Models\EmployeeShift;
use App\Helpers\ActivityLogger;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use App\Models\Role;
use App\Models\EmployeeSetting;

class EmployeeController extends Controller
{

    public function indexx(Request $request)
    {
        $this->authorize('view-employees');
        if ($request->ajax()) {
            $shifts = Shift::all();

            $employees = Employee::accessible()->with('currentShiftAssignment.shift')
                ->when($request->filled('shift_id'), fn($q) => $q->whereHas('currentShiftAssignment', fn($qq) => $qq->where('shift_id', $request->shift_id)))
                ->when($request->filled('joining_from'), fn($q) => $q->whereDate('joining_date', '>=', $request->joining_from))
                ->when($request->filled('joining_to'), fn($q) => $q->whereDate('joining_date', '<=', $request->joining_to));

            return datatables()->eloquent($employees)
                // ->addColumn('emp_code', fn($employee) => 'AST-' . $employee->id)
                ->addColumn('position_status', fn($employee) => $employee->position . ' ' . (isEmployeeResigned($employee) ? '<span class="text-danger">(Resigned)</span>' : ''))
                ->addColumn('shift_dropdown', function ($employee) use ($shifts) {
                    $currentShiftId = $employee->currentShiftAssignment->shift_id ?? null;
                    $currentShiftName = $employee->currentShiftAssignment->shift->shift_name ?? 'No Shift';
                    $options = '<select class="form-control form-control-sm assign-shift workforce-inline-select" data-emp="' . $employee->id . '" title="' . e($currentShiftName) . '">';
                    $options .= '<option value="">-- Select --</option>';
                    foreach ($shifts as $shift) {
                        $selected = $shift->id == $currentShiftId ? 'selected' : '';
                        $options .= "<option value='{$shift->id}' $selected>{$shift->shift_name}</option>";
                    }
                    $options .= '</select>';
                    return $options;
                })
                ->addColumn('joining_date', fn($employee) => $employee->joining_date ? date('d M, Y', strtotime($employee->joining_date)) : '-')
                ->addColumn('action', fn($employee) => view('admin.employees.partials.actions', compact('employee'))->render())
                ->rawColumns(['shift_dropdown', 'action', 'position_status'])
                ->make(true);
        }

        $schedules = Shift::all();
        ActivityLogger::log('view', 'Employee', ActivityLogger::format('view', 'Employee', 'All Records', 'Listing'));

        return view('admin.employees.index', compact('schedules'));
    }

    public function index(Request $request)
    {
        $this->authorize('view-employee');
        if ($request->ajax()) {
            $canEdit = auth()->user()->can('edit-employee');
            $shifts = Shift::accessible()->get();
            $roles = Role::all(); // Roles usually global
            $teams = \App\Models\Team::accessible()->get(); // Fetch teams

            $employees = Employee::accessible()->with(['currentShiftAssignment.shift', 'user.roles', 'team', 'branch', 'resignedBy']) // Load roles relationship, team, and branch
                ->when($request->filled('status') && $request->status === 'resigned', fn($q) => $q->whereNotNull('resign_date'), fn($q) => $q->whereNull('resign_date'))
                ->when($request->filled('shift_id'), fn($q) => $q->whereHas('currentShiftAssignment', fn($qq) => $qq->where('shift_id', $request->shift_id)))
                ->when($request->filled('branch_id'), fn($q) => $q->where('branch_id', $request->branch_id))
                ->when($request->filled('joining_from'), fn($q) => $q->whereDate('joining_date', '>=', $request->joining_from))
                ->when($request->filled('joining_to'), fn($q) => $q->whereDate('joining_date', '<=', $request->joining_to));

            return datatables()->eloquent($employees)
                // ->addColumn('emp_code', fn($employee) => 'AST-' . $employee->id)
                ->addColumn('position_status', function ($employee) {
                    $status = $employee->position;
                    if (isEmployeeResigned($employee)) {
                        if ($employee->exit_type === 'suspended') {
                            $label = 'Suspended';
                            $badgeClass = 'badge-warning';
                        } elseif ($employee->exit_type === 'terminated') {
                            $label = 'Terminated';
                            $badgeClass = 'badge-dark';
                        } else {
                            $label = 'Resigned';
                            $badgeClass = 'badge-danger';
                        }
                        $status .= ' <span class="badge ' . $badgeClass . ' ml-2">' . $label . '</span>';
                        if ($employee->exit_type === 'suspended' && $employee->suspended_start_date && $employee->suspended_end_date) {
                            $status .= '<div class="text-muted small mt-1" style="max-width: 250px; white-space: normal; line-height: 1.2;">Period: '
                                . date('d M, Y', strtotime($employee->suspended_start_date))
                                . ' — '
                                . date('d M, Y', strtotime($employee->suspended_end_date))
                                . '</div>';
                        }
                        if ($employee->resign_reason) {
                            $status .= '<div class="text-muted small mt-1 font-italic" style="max-width: 250px; white-space: normal; line-height: 1.2;">Reason: ' . e($employee->resign_reason) . '</div>';
                        }
                        if ($employee->exit_type !== 'suspended' && $employee->served_notice !== null) {
                            $servedLabel = $employee->served_notice ? 'Yes' : 'No';
                            $servedClass = $employee->served_notice ? 'text-success' : 'text-danger';
                            $status .= '<div class="small mt-1 font-weight-bold">Served Notice: <span class="' . $servedClass . '">' . $servedLabel . '</span></div>';
                        }
                        if ($employee->resignedBy) {
                            $status .= '<div class="small mt-1 font-weight-bold text-muted">Offboarded By: <span class="text-dark">' . e($employee->resignedBy->name) . '</span></div>';
                        }
                    }
                    return $status;
                })
                ->addColumn('shift_dropdown', function ($employee) use ($shifts, $canEdit) {
                    $currentShiftId = $employee->currentShiftAssignment->shift_id ?? null;
                    $currentShiftName = $employee->currentShiftAssignment->shift->shift_name ?? 'No Shift';

                    if (!$canEdit) {
                        return '<span class="badge badge-soft-info">' . $currentShiftName . '</span>';
                    }

                    $options = '<select class="form-control form-control-sm assign-shift workforce-inline-select" data-emp="' . $employee->id . '" title="' . e($currentShiftName) . '">';
                    $options .= '<option value="">-- Select --</option>';
                    foreach ($shifts as $shift) {
                        $selected = $shift->id == $currentShiftId ? 'selected' : '';
                        $options .= "<option value='{$shift->id}' $selected>{$shift->shift_name}</option>";
                    }
                    $options .= '</select>';
                    return $options;
                })
                ->addColumn('role_dropdown', function ($employee) use ($roles, $canEdit) {
                    $currentRoleName = optional($employee->user->roles->first())->name ?? null;
                    $currentRoleDisplay = $currentRoleName ?? 'No Role';

                    if (!$canEdit) {
                        return '<span class="badge badge-soft-dark">' . $currentRoleDisplay . '</span>';
                    }

                    $options = '<select class="form-control form-control-sm assign-role" data-emp="' . $employee->id . '">';
                    $options .= '<option value="">-- Select Role --</option>';
                    foreach ($roles as $role) {
                        $selected = $role->name == $currentRoleName ? 'selected' : '';
                        $options .= "<option value='{$role->name}' $selected>{$role->name}</option>";
                    }
                    $options .= '</select>';
                    return $options;
                })
                ->addColumn('team_dropdown', function ($employee) use ($teams, $canEdit) {
                    $currentTeamName = $employee->team->name ?? 'No Team';
                    if (!$canEdit) {
                        return '<span class="badge badge-soft-primary">' . $currentTeamName . '</span>';
                    }

                    $options = '<select class="form-control form-control-sm assign-team workforce-inline-select" data-emp="' . $employee->id . '" title="' . e($currentTeamName) . '">';
                    $options .= '<option value="">-- No Team --</option>';
                    foreach ($teams as $team) {
                        $selected = $team->id == $employee->team_id ? 'selected' : '';
                        $options .= "<option value='{$team->id}' $selected>{$team->name}</option>";
                    }
                    $options .= '</select>';
                    return $options;
                })
                ->addColumn('joining_date', fn($employee) => $employee->joining_date ? date('d M, Y', strtotime($employee->joining_date)) : '-')
                ->editColumn('resign_date', fn($employee) => $employee->resign_date ? date('d M, Y', strtotime($employee->resign_date)) : '-')
                ->orderColumn('resign_date', function ($query, $order) {
                    $query->orderBy('employees.resign_date', $order)->orderBy('employees.id', $order);
                })
                ->addColumn('action', fn($employee) => view('admin.employees.partials.actions', compact('employee'))->render())
                ->rawColumns(['shift_dropdown', 'role_dropdown', 'team_dropdown', 'action', 'position_status'])
                ->make(true);
        }

        $schedules = Shift::accessible()->get();
        $roles = Role::all();

        // Restriction: Only admins can assign Admin/Administrator/COO roles
        if (!auth()->user()->hasRole(['admin', 'administrator'])) {
            $roles = $roles->reject(fn($role) => in_array(strtolower($role->name), ['admin', 'administrator', 'coo']));
        }

        $teams = \App\Models\Team::accessible()->get();
        $branches = \App\Models\Branch::accessible()->where('is_active', true)->get();
        $salary_structures = \App\Models\SalaryStructure::accessible()->get();
        ActivityLogger::log('view', 'Employee', ActivityLogger::format('view', 'Employee', 'All Records', 'Listing'));

        return view('admin.employees.index', compact('schedules', 'roles', 'teams', 'branches', 'salary_structures'));
    }

    public function create()
    {
        $this->authorize('create-employee');
        $schedules = Shift::accessible()->get();
        $roles = Role::all();

        // Restriction: Only admins can assign Admin/Administrator/COO roles
        if (!auth()->user()->hasRole(['admin', 'administrator'])) {
            $roles = $roles->reject(fn($role) => in_array(strtolower($role->name), ['admin', 'administrator', 'coo']));
        }

        $teams = \App\Models\Team::accessible()->get();
        $branches = \App\Models\Branch::accessible()->where('is_active', true)->get();
        $salary_structures = \App\Models\SalaryStructure::accessible()->get();

        return view('admin.employees.create', compact('schedules', 'roles', 'teams', 'branches', 'salary_structures'));
    }

    public function edit($id)
    {
        $this->authorize('edit-employee');
        $employee = Employee::accessible()->with(['user.roles', 'team', 'branch'])->findOrFail($id);
        $schedules = Shift::accessible()->get();
        $roles = Role::all();

        // Restriction: Only admins can assign Admin/Administrator/COO roles
        if (!auth()->user()->hasRole(['admin', 'administrator'])) {
            $roles = $roles->reject(fn($role) => in_array(strtolower($role->name), ['admin', 'administrator', 'coo']));
        }

        $teams = \App\Models\Team::accessible()->get();
        $branches = \App\Models\Branch::accessible()->where('is_active', true)->get();
        $salary_structures = \App\Models\SalaryStructure::accessible()->get();

        return view('admin.employees.edit', compact('employee', 'schedules', 'roles', 'teams', 'branches', 'salary_structures'));
    }

    public function show($id)
    {
        $this->authorize('view-employee');
        $employee = Employee::accessible()->with(['user.roles', 'team', 'branch', 'currentShiftAssignment.shift', 'exitRecords.processedBy'])->findOrFail($id);

        return view('admin.employees.show', compact('employee'));
    }

    public function assignShift(Request $request): JsonResponse
    {
        $this->authorize('edit-employee');
        $request->validate([
            'emp_id' => 'required|exists:employees,id',
            'shift_id' => 'required|exists:shifts,id',
        ]);

        // ⛔ prevent duplicate assignment on the SAME day & same shift
        $already = EmployeeShift::where('emp_id', $request->emp_id)
            ->where('shift_id', $request->shift_id)
            ->whereDate('assigned_at', today())
            ->exists();

        if ($already) {
            return response()->json([
                'success' => true,
                'message' => 'Shift already assigned today.'
            ], 200);
        }

        EmployeeShift::create([
            'emp_id' => $request->emp_id,
            'shift_id' => $request->shift_id,
            'assigned_at' => now(),
        ]);

        clear_employee_settings_cache((int) $request->emp_id);

        return response()->json(['success' => true]);
    }

    public function assignTeam(Request $request): JsonResponse
    {
        $this->authorize('edit-employee');
        $request->validate([
            'emp_id' => 'required|exists:employees,id',
            'team_id' => 'nullable|exists:teams,id',
        ]);

        $employee = Employee::findOrFail($request->emp_id);
        $employee->team_id = $request->team_id;
        $employee->save();

        return response()->json(['success' => true, 'message' => 'Team assigned successfully.']);
    }


    public function store(Request $request)
    {
        $this->authorize('create-employee');
        // Dynamic Validation based on Role
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|exists:roles,name',
            'dob' => 'nullable|date',
            'break_duration' => 'nullable|integer|min:0',
            'break_allowed_in_half_day' => 'nullable|integer|min:0',
            'number_full_days_allowed_in_month' => 'nullable|integer|min:0',
            'number_half_days_allowed_in_month' => 'nullable|integer|min:0',
            'late_minutes_margin' => 'nullable|integer|min:0',
            'leaves_allowed_in_year' => 'nullable|integer|min:0',
            'idle_time_allowed' => 'nullable|integer|min:0',
            'mark_half_day_after' => 'nullable|integer|min:0',
            'app_resp_grace_minutes' => 'nullable|integer|min:0',
            'time_zone' => 'nullable|string',
            'team_id' => 'nullable|exists:teams,id',
            'branch_id' => 'nullable|exists:branches,id',
            'salary_structure_id' => 'nullable',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'branch_code' => 'nullable|string|max:255',
        ];

        if (auth()->user()->hasRole(['admin', 'administrator'])) {
            if ($request->role !== 'admin') {
                $rules['salary'] = 'required|numeric|min:0';
            } else {
                $rules['salary'] = 'nullable|numeric|min:0';
            }
        }

        // Only require employee fields if NOT admin
        if ($request->role !== 'admin') {
            $rules = array_merge($rules, [
                'position' => 'required|string|max:255',
                'joining_date' => 'required|date',
                'probation' => 'required|integer|min:0',
                'contact_no' => 'required|string|max:20',
                'emergency_no' => 'required|string|max:20',
                'gender' => 'required|in:male,female',
                'schedule' => 'required|integer|exists:shifts,id',
            ]);
        } else {
            $rules = array_merge($rules, [
                'position' => 'nullable|string|max:255',
                'joining_date' => 'nullable|date',
                'probation' => 'nullable|integer|min:0',
                'contact_no' => 'nullable|string|max:20',
                'emergency_no' => 'nullable|string|max:20',
                'gender' => 'nullable|in:male,female',
                'schedule' => 'nullable|integer|exists:shifts,id',
            ]);
        }

        $validated = $request->validate($rules);

        // Security check for restricted roles
        if (in_array(strtolower($validated['role']), ['admin', 'administrator', 'coo']) && !auth()->user()->hasRole(['admin', 'administrator'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: Only administrators can assign Admin or COO roles.'], 403);
        }

        // Fill Admin Defaults
        if ($validated['role'] === 'admin') {
            $validated['position'] = $validated['position'] ?? 'Administrator';
            $validated['joining_date'] = $validated['joining_date'] ?? date('Y-m-d');
            $validated['probation'] = $validated['probation'] ?? 0;
            $validated['contact_no'] = $validated['contact_no'] ?? '-';
            $validated['emergency_no'] = $validated['emergency_no'] ?? '-';
            $validated['gender'] = $validated['gender'] ?? 'male';

            // Auto-assign first shift if none provided for Admin (System requirement)
            if (!isset($validated['schedule']) || empty($validated['schedule'])) {
                $firstShift = Shift::first();
                $validated['schedule'] = $firstShift ? $firstShift->id : 1;
            }
        }

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
        ]);

        // Assign role to user
        $role = Role::where('name', $validated['role'])->firstOrFail();
        $user->roles()->attach($role->id);

        // Create employee record only if role is NOT admin
        if ($validated['role'] !== 'admin') {
            $employeeData = [
                'user_id' => $user->id,
                'name' => $validated['name'],
                'email' => strtolower($validated['email']),
                'position' => $validated['position'],
                'joining_date' => $validated['joining_date'],
                'probation' => $validated['probation'],
                'contact_no' => $validated['contact_no'],
                'emergency_no' => $validated['emergency_no'],
                'gender' => $validated['gender'],
                'dob' => $validated['dob'] ?? null,
                'break_duration' => $validated['break_duration'] ?? 0,
                'break_allowed_in_half_day' => $validated['break_allowed_in_half_day'] ?? 30,
                'number_full_days_allowed_in_month' => $validated['number_full_days_allowed_in_month'] ?? 0,
                'number_half_days_allowed_in_month' => $validated['number_half_days_allowed_in_month'] ?? 0,
                'late_minutes_margin' => $validated['late_minutes_margin'] ?? 0,
                'leaves_allowed_in_year' => $validated['leaves_allowed_in_year'] ?? 16,
                'idle_time_allowed' => $validated['idle_time_allowed'] ?? 0,
                'team_id' => $validated['team_id'] ?? null,
                'branch_id' => $request->branch_id ?? (\App\Models\Team::find($request->team_id)->branch_id ?? (auth()->user()->employee->branch_id ?? null)),
                'salary_structure_id' => null,
                'bank_name' => $validated['bank_name'] ?? null,
                'bank_account_name' => $validated['bank_account_name'] ?? null,
                'account_number' => $validated['account_number'] ?? null,
                'iban' => $validated['iban'] ?? null,
                'branch_code' => $validated['branch_code'] ?? null,
            ];

            if (auth()->user()->hasRole(['admin', 'administrator'])) {
                $employeeData['salary'] = $request->salary ?? null;
            }

            $employee = Employee::create($employeeData);

            // Attach schedule
            $employee->shifts()->attach($validated['schedule'], ['assigned_at' => now()]);

            // Add default employee settings
            $defaultSettings = [
                'break_duration' => $request->input('break_duration', '45'),
                'half_day_allowed_in_month' => $request->input('number_half_days_allowed_in_month', '4'),
                'full_day_allowed_in_month' => $request->input('number_full_days_allowed_in_month', '2'),
                'leaves_allowed_in_year' => $request->input('leaves_allowed_in_year', '16'),
                'late_grace_minutes' => $request->input('late_minutes_margin', '5'),
                'idle_time_allowed' => $request->input('idle_time_allowed', '5'),
                'mark_half_day_after' => $request->input('mark_half_day_after', '120'),
                'app_resp_grace_minutes' => $request->input('app_resp_grace_minutes', '1'),
                'time_zone' => $request->input('time_zone', 'Asia/Karachi')
            ];

            foreach ($defaultSettings as $key => $value) {
                EmployeeSetting::create([
                    'emp_id' => $employee->id,
                    'setting_name' => $key,
                    'setting_value' => $value,
                    'updated_by' => auth()->id() ?? 1
                ]);
            }

            // Leave balances are allocated by LeaveService via the EmployeeObserver,
            // driven by leave_types.max_days and the eligibility waiting period.

            // Log activity
            ActivityLogger::log(
                'create',
                'Employee',
                ActivityLogger::format('create', 'Employee', $employee->name, $employee->id)
            );
        } else {
            // Log Admin creation
            ActivityLogger::log(
                'create',
                'Admin',
                ActivityLogger::format('create', 'Admin', $user->name, $user->id)
            );
        }

        session()->flash('success', 'New talent onboarded successfully!');
        return response()->json(['success' => true]);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('edit-employee');
        $employee = Employee::accessible()->with('user')->findOrFail($id);

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $employee->user_id,
            'password' => 'nullable|string|min:6',
            'position' => 'required',
            'joining_date' => 'required|date',
            'probation' => 'required|integer',
            'contact_no' => 'required|string',
            'emergency_no' => 'required|string',
            'gender' => 'required|in:male,female',
            'dob' => 'nullable|date',
            'schedule' => 'required|integer',
            'role' => 'required|exists:roles,name',
            'break_duration' => 'nullable|integer|min:0',
            'break_allowed_in_half_day' => 'nullable|integer|min:0',
            'number_full_days_allowed_in_month' => 'nullable|integer|min:0',
            'number_half_days_allowed_in_month' => 'nullable|integer|min:0',
            'late_minutes_margin' => 'nullable|integer|min:0',
            'leaves_allowed_in_year' => 'nullable|integer|min:0',
            'idle_time_allowed' => 'nullable|integer|min:0',
            'mark_half_day_after' => 'nullable|integer|min:0',
            'app_resp_grace_minutes' => 'nullable|integer|min:0',
            'time_zone' => 'nullable|string',
            'team_id' => 'nullable|exists:teams,id',
            'branch_id' => 'nullable|exists:branches,id',
            'salary_structure_id' => 'nullable',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'branch_code' => 'nullable|string|max:255',
            'cnic_front' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'cnic_back' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];

        if (auth()->user()->hasRole(['admin', 'administrator'])) {
            $rules['salary'] = 'required|numeric|min:0';
        }

        $validated = $request->validate($rules);

        // Security check for restricted roles
        if (in_array(strtolower($validated['role']), ['admin', 'administrator', 'coo']) && !auth()->user()->hasRole(['admin', 'administrator'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: Only administrators can assign Admin or COO roles.'], 403);
        }

        // Handle cropped base64 image
        $base64Image = $request->input('cropped_profile');
        if ($base64Image && preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {

            // Extract extension and data
            $type = strtolower($type[1]); // png, jpg, etc.
            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
            $decodedImage = base64_decode($base64Image);

            if ($decodedImage === false) {
                return response()->json(['success' => false, 'message' => 'Invalid image data.'], 400);
            }

            $filename = 'profile_pics/' . uniqid() . '.' . $type;

            // 1. Purani image delete karein (Storage facade ke zariye)
            if ($employee->profile_pic && Storage::disk('public')->exists($employee->profile_pic)) {
                Storage::disk('public')->delete($employee->profile_pic);
            }

            // 2. Nayi image save karein (Storage disk 'public' automatically app/public use karta hai)
            Storage::disk('public')->put($filename, $decodedImage);

            // Update path in variables
            $employee->profile_pic = $filename;
            $employee->user->profile_pic = $filename;
        }

        // Update USER record
        $employee->user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password']
                ? Hash::make($validated['password'])
                : $employee->user->password,
            'profile_pic' => $employee->user->profile_pic,
        ]);

        // Assign or update role
        $role = Role::where('name', $validated['role'])->firstOrFail();
        $employee->user->roles()->sync([$role->id]);

        // If role changed to Admin, delete the employee record
        if ($validated['role'] === 'admin') {
            $employee->delete();

            ActivityLogger::log('update', 'Employee', "Employee {$employee->name} converted to Admin and record removed from tracking.");

            return response()->json([
                'success' => true,
                'message' => 'User converted to Admin successfully! They will no longer appear in employee lists.',
            ]);
        }

        // Update EMPLOYEE record
        $employeeData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'position' => $validated['position'],
            'joining_date' => $validated['joining_date'],
            'probation' => $validated['probation'],
            'contact_no' => $validated['contact_no'],
            'emergency_no' => $validated['emergency_no'],
            'gender' => $validated['gender'],
            'dob' => $validated['dob'] ?? null,
            'profile_pic' => $employee->profile_pic,
            'break_duration' => $validated['break_duration'] ?? 45,
            'break_allowed_in_half_day' => $validated['break_allowed_in_half_day'] ?? 30,
            'number_full_days_allowed_in_month' => $validated['number_full_days_allowed_in_month'] ?? 0,
            'number_half_days_allowed_in_month' => $validated['number_half_days_allowed_in_month'] ?? 0,
            'late_minutes_margin' => $validated['late_minutes_margin'] ?? 5,
            'leaves_allowed_in_year' => $validated['leaves_allowed_in_year'] ?? 16,
            'idle_time_allowed' => $validated['idle_time_allowed'] ?? 5,
            'mark_half_day_after' => $validated['mark_half_day_after'] ?? null,
            'app_resp_grace_minutes' => $validated['app_resp_grace_minutes'] ?? null,
            'time_zone' => $validated['time_zone'] ?? null,
            'team_id' => $validated['team_id'] ?? null,
            'branch_id' => $request->branch_id ?? (\App\Models\Team::find($request->team_id)->branch_id ?? $employee->branch_id),
            'salary_structure_id' => null,
            'bank_name' => $validated['bank_name'] ?? null,
            'bank_account_name' => $validated['bank_account_name'] ?? null,
            'account_number' => $validated['account_number'] ?? null,
            'iban' => $validated['iban'] ?? null,
            'branch_code' => $validated['branch_code'] ?? null,
            'cnic_front_path' => $employee->cnic_front_path,
            'cnic_back_path' => $employee->cnic_back_path,
        ];

        // Handle CNIC uploads
        if ($request->hasFile('cnic_front')) {
            if ($employee->cnic_front_path && Storage::disk('public')->exists($employee->cnic_front_path)) {
                Storage::disk('public')->delete($employee->cnic_front_path);
            }
            $employeeData['cnic_front_path'] = $request->file('cnic_front')->store('cnic_docs', 'public');
        }

        if ($request->hasFile('cnic_back')) {
            if ($employee->cnic_back_path && Storage::disk('public')->exists($employee->cnic_back_path)) {
                Storage::disk('public')->delete($employee->cnic_back_path);
            }
            $employeeData['cnic_back_path'] = $request->file('cnic_back')->store('cnic_docs', 'public');
        }

        if (auth()->user()->hasRole(['admin', 'administrator'])) {
            $employeeData['salary'] = $request->salary ?? null;
        }

        $employee->update($employeeData);

        // Sync shift
        $employee->shifts()->sync([
            $validated['schedule'] => ['assigned_at' => now()]
        ]);

        // Update employee settings table
        $customSettings = [
            'break_duration' => $validated['break_duration'] ?? 45,
            'half_day_allowed_in_month' => $validated['number_half_days_allowed_in_month'] ?? 0,
            'full_day_allowed_in_month' => $validated['number_full_days_allowed_in_month'] ?? 0,
            'leaves_allowed_in_year' => $validated['leaves_allowed_in_year'] ?? 16,
            'late_grace_minutes' => $validated['late_minutes_margin'] ?? 5,
            'idle_time_allowed' => $validated['idle_time_allowed'] ?? 5,
            'mark_half_day_after' => $validated['mark_half_day_after'] ?? '120',
            'app_resp_grace_minutes' => $validated['app_resp_grace_minutes'] ?? '1',
            'time_zone' => $validated['time_zone'] ?? 'Asia/Karachi'
        ];

        foreach ($customSettings as $key => $value) {
            EmployeeSetting::updateOrCreate(
                ['emp_id' => $employee->id, 'setting_name' => $key],
                ['setting_value' => $value, 'updated_by' => auth()->id() ?? 1]
            );
        }

        clear_employee_settings_cache($employee->id);

        // Log activity
        ActivityLogger::log('update', 'Employee', ActivityLogger::format('update', 'Employee', $employee->name, $employee->id));

        return response()->json([
            'success' => true,
            'message' => 'Employee updated successfully!',
            'profile_pic_url' => $employee->profile_pic ? Storage::disk('public')->url($employee->profile_pic) : null
        ]);
    }



    public function resign(Request $request, $id)
    {
        $this->authorize('offboard-employee');
        $employee = Employee::accessible()->findOrFail($id);
        $exitType = $request->input('exit_type', 'resigned');

        if ($exitType === 'suspended') {
            $request->validate([
                'suspended_start_date' => 'required|date',
                'suspended_end_date' => 'required|date|after_or_equal:suspended_start_date',
                'resign_reason' => 'required|string|max:2000',
            ]);

            $employee->resign_date = $request->input('suspended_start_date');
            $employee->suspended_start_date = $request->input('suspended_start_date');
            $employee->suspended_end_date = $request->input('suspended_end_date');
            $employee->resign_reason = $request->input('resign_reason');
            $employee->exit_type = 'suspended';
            $employee->served_notice = null;
        } else {
            $request->validate([
                'exit_type' => 'required|in:resigned,terminated',
                'resign_reason' => 'nullable|string|max:2000',
            ]);

            $employee->resign_date = now()->toDateString();
            $employee->resign_reason = $request->input('resign_reason');
            $employee->exit_type = $exitType;
            $employee->served_notice = $request->input('served_notice');
            $employee->suspended_start_date = null;
            $employee->suspended_end_date = null;
        }

        $employee->resigned_by = auth()->id();
        $employee->save();

        if ($employee->user) {
            $employee->user->tokens()->delete();
        }

        $actionText = match ($employee->exit_type) {
            'terminated' => 'Terminated',
            'suspended' => 'Suspended',
            default => 'Resigned',
        };
        ActivityLogger::log('update', 'Employee', ActivityLogger::format('update', 'Employee', $employee->name . ' (' . $actionText . ')', $id));

        return response()->json(['success' => true, 'message' => 'Employee marked as ' . strtolower($actionText) . '.']);
    }

    public function rejoin(Request $request, $id)
    {
        $this->authorize('create-employee');
        $employee = Employee::accessible()->findOrFail($id);

        if (!$employee->resign_date) {
            return response()->json(['success' => false, 'message' => 'Employee is already active.'], 400);
        }

        // 1. Archive current exit data
        \App\Models\EmployeeExitRecord::create([
            'emp_id' => $employee->id,
            'exit_date' => $employee->resign_date,
            'exit_type' => $employee->exit_type ?? 'resigned',
            'exit_reason' => $employee->resign_reason,
            'served_notice' => $employee->served_notice ?? false,
            'suspended_start_date' => $employee->suspended_start_date,
            'suspended_end_date' => $employee->suspended_end_date,
            'processed_by' => $employee->resigned_by
        ]);

        // 2. Reset employee exit fields and update joining date
        $employee->resign_date = null;
        $employee->exit_type = null;
        $employee->resign_reason = null;
        $employee->served_notice = null;
        $employee->suspended_start_date = null;
        $employee->suspended_end_date = null;
        $employee->resigned_by = null;

        if ($request->filled('joining_date')) {
            $employee->joining_date = $request->joining_date;
        }

        $employee->save();

        ActivityLogger::log('update', 'Employee', ActivityLogger::format('update', 'Employee', $employee->name . ' (Rejoined)', $id));

        return response()->json(['success' => true, 'message' => 'Employee successfully re-onboarded!']);
    }

    public function destroy($id)
    {
        $this->authorize('delete-employee');
        $employee = Employee::accessible()->with('user')->findOrFail($id);

        // Detach shifts (many-to-many cleanup)
        $employee->shifts()->detach();

        // Record who deleted the employee
        $employee->deleted_by = auth()->user()->name ?? 'System';
        $employee->save();

        // Soft delete the employee
        $employee->delete();

        // Soft delete the associated user (if exists)
        if ($employee->user) {
            $employee->user->delete();
        }

        // Log the soft delete action
        ActivityLogger::log(
            'delete',
            'Employee',
            ActivityLogger::format('delete', 'Employee', $employee->name, $id)
        );

        return response()->json(['success' => true]);
    }



    public function employee_data($id)
    {
        $this->authorize('view-employee');
        $employee = Employee::accessible()->with('shifts')->findOrFail($id);
        return response()->json($employee);
    }

    public function saveLeaveBalance(Request $request)
    {
        $this->authorize('manage-leave-balances');
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type' => 'required|string',
            'year' => 'required|integer',
            'allocated' => 'required|numeric|min:0',
        ]);

        $balance = \App\Models\LeaveBalance::firstOrNew(
            [
                'employee_id' => $validated['employee_id'],
                'leave_type' => $validated['leave_type'],
                'year' => $validated['year']
            ]
        );

        $balance->allocated = $validated['allocated'];
        $balance->used = $balance->used ?? 0;
        $balance->remaining = $balance->allocated - $balance->used;
        $balance->save();

        return response()->json(['success' => true, 'message' => 'Leave balance saved successfully!']);
    }

    public function updateLeaveBalance(Request $request, $id)
    {
        $this->authorize('manage-leave-balances');
        $validated = $request->validate([
            'allocated' => 'required|numeric|min:0',
            'used' => 'required|numeric|min:0',
            'remaining' => 'required|numeric',
        ]);

        $balance = \App\Models\LeaveBalance::findOrFail($id);
        $balance->update($validated);

        return response()->json(['success' => true, 'message' => 'Leave balance updated successfully!']);
    }

    public function deleteLeaveBalance($id)
    {
        $this->authorize('manage-leave-balances');
        $balance = \App\Models\LeaveBalance::findOrFail($id);
        $balance->delete();
        return response()->json(['success' => true, 'message' => 'Leave balance deleted successfully!']);
    }

    public function viewCnic($id, $side)
    {
        $this->authorize('view-employee');
        $employee = Employee::findOrFail($id);

        $path = ($side === 'front') ? $employee->cnic_front_path : $employee->cnic_back_path;

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $file = Storage::disk('public')->get($path);
        $type = Storage::disk('public')->mimeType($path);

        return response($file, 200)->header('Content-Type', $type);
    }
}
