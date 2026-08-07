<?php

namespace App\Models\Performance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Employee;
use App\Models\User;

class PerformanceEvaluation extends Model
{
    use HasFactory;

    protected $table = 'performance_evaluations';

    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'attendance_score',
        'leave_score',
        'break_score',
        'late_score',
        'dress_code_score',
        'work_performance_score',
        'behavior_score',
        'comments',
        'total_score',
        'status',
        'evaluator_id',
    ];

    protected $casts = [
        'attendance_score' => 'decimal:2',
        'leave_score' => 'decimal:2',
        'break_score' => 'decimal:2',
        'late_score' => 'decimal:2',
        'dress_code_score' => 'decimal:2',
        'work_performance_score' => 'decimal:2',
        'behavior_score' => 'decimal:2',
        'total_score' => 'decimal:2',
        'month' => 'integer',
        'year' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function employeeOfTheMonth()
    {
        return $this->hasOne(EmployeeOfTheMonth::class, 'performance_evaluation_id');
    }
}
