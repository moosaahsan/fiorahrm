<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\ScopesData;

class Attendance extends Model
{
    use HasFactory, ScopesData;

    protected $fillable = [
        'emp_id',
        'shift_id',
        'check_in',
        'check_out',
        'late_duration',
        'shift_over_at',
        'status',
        'shift_date',
        'is_manual',
        'modified_by',
        'branch_id',
        'checked_out_by'
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'created_at' => 'datetime',
        'shift_date' => 'date',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    // General relationship for all breaks of the employee
    // public function breaks()
    // {
    //     return $this->hasMany(\App\Models\EmployeeBreak::class, 'emp_id', 'emp_id');
    // }

    // In Attendance.php model
    public function breaks()
    {
        return $this->hasMany(\App\Models\EmployeeBreak::class, 'attendance_id', 'id');
    }


    // Accessor for only relevant (General type) breaks on this shift date
    public function getFilteredBreaksAttribute()
    {
        return $this->breaks()
            ->whereDate('created_at', $this->shift_date)
            ->where('type', 'General')
            ->get();
    }

    public function lateArrival()
    {
        return $this->hasOne(\App\Models\LateArrival::class, 'attendance_id');
    }

    public function halfDay()
    {
        return $this->hasOne(\App\Models\HalfDay::class, 'attendance_id');
    }

    public function modifier()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    public function checkedOutBy()
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

}
