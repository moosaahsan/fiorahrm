<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ScopesData;

class PayrollItem extends Model
{
    use HasFactory, ScopesData;

    protected $fillable = [
        'payroll_id',
        'employee_id',
        'gross_salary',
        'total_deductions',
        'net_salary',
        'earnings_detail',
        'deductions_detail',
    ];

    protected $casts = [
        'earnings_detail' => 'array',
        'deductions_detail' => 'array',
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
