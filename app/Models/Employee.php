<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\ScopesData;

class Employee extends Model
{
    use HasFactory, SoftDeletes, ScopesData;
    protected $appends = ['profile_pic_url', 'cover_pic_url'];
    protected $fillable = [
        'name',
        'cnic',
        'position',
        'joining_date',
        'probation',
        'email',
        'contact_no',
        'emergency_no',
        'tracking',
        'resign_date',
        'status',
        'gender',
        'dob',
        'profile_pic',
        'cover_pic',
        'cover_pic_position',
        'user_id',
        'break_duration',
        'break_allowed_in_half_day',
        'number_full_days_allowed_in_month',
        'number_half_days_allowed_in_month',
        'late_minutes_margin',
        'leaves_allowed_in_year',
        'idle_time_allowed',
        'team_id',
        'branch_id',
        'salary_structure_id',
        'salary',
        'resign_reason',
        'exit_type',
        'served_notice',
        'suspended_start_date',
        'suspended_end_date',
        'resigned_by',
        'bank_name',
        'bank_account_name',
        'account_number',
        'iban',
        'branch_code',
        'deleted_by',
        'cnic_front_path',
        'cnic_back_path'
    ];

    public function resignedBy()
    {
        return $this->belongsTo(User::class, 'resigned_by');
    }

    public function salaryStructure()
    {
        return $this->belongsTo(SalaryStructure::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function evaluation()
    {
        return $this->hasOne(\App\Models\Performance\PerformanceEvaluation::class, 'emp_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'emp_id');
    }


    // public function leaves()
    // {
    //     return $this->hasMany(Leave::class);
    // }

    public function leaves()
    {
        return $this->hasMany(Leave::class, 'employee_id');
    }


    public function breaks()
    {
        return $this->hasMany(EmployeeBreak::class);
    }

    public function checkins()
    {
        return $this->hasMany(Checkin::class);
    }

    public function settings_old()
    {
        return $this->hasMany(EmployeeSetting::class);
    }

    public function settings()
    {
        return $this->hasMany(EmployeeSetting::class, 'emp_id'); // specify foreign key
    }


    public function shifts()
    {
        return $this->belongsToMany(Shift::class, 'employee_shifts', 'emp_id', 'shift_id')
            ->withPivot('assigned_at')
            ->withTimestamps();
    }


    public function shiftHistory()
    {
        return $this->hasMany(EmployeeShift::class, 'emp_id');
    }

    public function employeeShifts()
    {
        return $this->hasMany(EmployeeShift::class, 'emp_id');
    }

    // Helper to get today's shift (most recent assignment for today)
    public function todaysShift()
    {
        return $this->hasOne(EmployeeShift::class, 'emp_id')
            ->whereDate('assigned_at', now()->toDateString())
            ->latest('assigned_at');
    }

    public function currentShiftAssignment()
    {
        return $this->hasOne(EmployeeShift::class, 'emp_id')->latestOfMany();
    }


    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function currentShift()
    {
        return $this->hasOne(EmployeeShift::class, 'emp_id', 'id')
            ->with('shift')
            ->latest('id');
    }

    public function exitRecords()
    {
        return $this->hasMany(EmployeeExitRecord::class, 'emp_id');
    }

    public function getProfilePicUrlAttribute()
    {
        if ($this->profile_pic && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->profile_pic)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->profile_pic);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=4f46e5&background=f0f0ff';
    }

    public function getCoverPicUrlAttribute()
    {
        return get_cover_picture_url($this->cover_pic);
    }

}
