<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeExitRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'emp_id',
        'exit_date',
        'exit_type',
        'exit_reason',
        'served_notice',
        'suspended_start_date',
        'suspended_end_date',
        'processed_by'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
