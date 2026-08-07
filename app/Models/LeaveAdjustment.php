<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveAdjustment extends Model
{
    protected $fillable = [
        'emp_id',
        'policy_name',
        'adjustment_amount',
        'month',
        'year',
        'applied_at',
        'notes',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'adjustment_amount' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }
}
