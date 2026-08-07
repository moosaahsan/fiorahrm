<?php

namespace App\Models\Performance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Employee;

class EmployeeOfTheMonth extends Model
{
    use HasFactory;

    protected $table = 'employee_of_the_month';

    protected $fillable = [
        'month',
        'year',
        'employee_id',
        'performance_evaluation_id',
        'bio_comments',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function performanceEvaluation()
    {
        return $this->belongsTo(PerformanceEvaluation::class, 'performance_evaluation_id');
    }
}
