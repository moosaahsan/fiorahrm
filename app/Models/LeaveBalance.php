<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    //

    protected $fillable = ['employee_id', 'leave_type', 'year', 'allocated', 'used', 'remaining'];
    protected $dates = [
        'created_at',
        'updated_at',
    ];
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type', 'slug');
    }
}
