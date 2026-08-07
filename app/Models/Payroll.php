<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ScopesData;

class Payroll extends Model
{
    use HasFactory, ScopesData;

    protected $fillable = [
        'branch_id',
        'month',
        'year',
        'total_gross',
        'total_deductions',
        'total_net',
        'status',
        'generated_by',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function items()
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
