<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'emp_id',
        'year',
        'total_leaves',
        'used_leaves',
        'rejected_leaves',
        'half_leaves',
        'carried_forward',
        'manual_adjustments',
        'policy_adjustments',
        'notes',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }

    // Accessor for remaining leaves (you can use this in views directly)
    public function getRemainingLeavesAttribute()
    {
        $halfLeaveValue = $this->half_leaves * 0.5;
        return $this->total_leaves + $this->carried_forward + $this->manual_adjustments + $this->policy_adjustments - $this->used_leaves - $halfLeaveValue;
    }
}
