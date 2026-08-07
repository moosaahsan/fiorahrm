<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSetting extends Model
{
    use HasFactory;

    protected $fillable = ['emp_id', 'setting_name', 'setting_value', 'updated_by'];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }
}
