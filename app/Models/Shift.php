<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\ScopesData;

class Shift extends Model
{
    use HasFactory, SoftDeletes, ScopesData;

    // protected $fillable = ['shift_name', 'start_time', 'end_time'];
    protected $fillable = [
        'shift_name',
        'start_time',
        'end_time',
        'grace_period',
        'late_mark',
        'halfday_mark',
        'friday_break',
        'otherday_break',
        'halfday_break',
        'crosses_midnight',
        'is_active',
        'branch_id',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_shifts');
    }
}
