<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\ScopesData;

class LateArrival extends Model
{
    use SoftDeletes, ScopesData;

    protected $fillable = [
        'emp_id',
        'attendance_id',
        'shift_id',
        'branch_id',
        'date',
        'scheduled_start',
        'actual_check_in',
        'late_minutes',
        'late_reason',
    ];

    /**
     * Boot the model for automatic branching.
     */
    protected static function booted()
    {
        static::saving(function ($late) {
            if (!$late->branch_id && $late->emp_id) {
                $employee = \App\Models\Employee::find($late->emp_id);
                if ($employee) {
                    $late->branch_id = $employee->branch_id;
                }
            }
        });
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }
}
