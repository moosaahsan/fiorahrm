<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;

use App\Traits\ScopesData;

class Leave extends Model
{
    use HasFactory, ScopesData;
    
    /**
     * Boot the model to handle automatic branching.
     */
    protected static function booted()
    {
        static::saving(function ($leave) {
            if (!$leave->branch_id && $leave->employee_id) {
                $employee = \App\Models\Employee::find($leave->employee_id);
                if ($employee) {
                    $leave->branch_id = $employee->branch_id;
                }
            }
        });
    }

    protected $fillable = ['employee_id', 'shift_id', 'leave_type', 'start_date', 'end_date', 'status', 'day_type', 'reason', 'approved_by', 'is_balance_deducted', 'branch_id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type', 'slug');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvals()
    {
        return $this->hasMany(LeaveApproval::class)->orderBy('created_at', 'asc');
    }
}