<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_type',
        'model_id',
        'late_policy',
        'break_policy',
        'leave_policy',
        'is_active',
    ];

    protected $casts = [
        'late_policy' => 'array',
        'break_policy' => 'array',
        'leave_policy' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the associated model (Team or Employee).
     */
    public function target()
    {
        if ($this->policy_type === 'Team') {
            return $this->belongsTo(Team::class, 'model_id');
        }
        if ($this->policy_type === 'Individual') {
            return $this->belongsTo(Employee::class, 'model_id');
        }
        return null;
    }
}
