<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ScopesData;

class HalfDay extends Model
{
    use ScopesData;
    protected $fillable = [
        'emp_id',
        'attendance_id',
        'shift_id',
        'date',
        'reason',
        'source',
        'created_by',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
