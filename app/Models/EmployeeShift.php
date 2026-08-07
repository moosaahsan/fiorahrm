<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\ScopesData;

class EmployeeShift extends Model
{
    use HasFactory, ScopesData;

    protected $fillable = ['emp_id', 'shift_id', 'assigned_at'];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }
}

