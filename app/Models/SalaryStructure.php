<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ScopesData;

class SalaryStructure extends Model
{
    use HasFactory, ScopesData;

    protected $fillable = [
        'branch_id',
        'name',
        'basic_salary',
        'allowances',
        'deductions',
        'config',
        'effective_date',
        'employee_type',
        'is_active',
    ];

    protected $casts = [
        'allowances' => 'array',
        'deductions' => 'array',
        'config' => 'array',
        'effective_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
