<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'max_days',
        'auto_allocate',
        'is_paid',
        'carry_forward',
        'max_carry_forward_days',
        'requires_approval',
        'is_active',
    ];

    protected $casts = [
        'max_days' => 'integer',
        'auto_allocate' => 'boolean',
        'is_paid' => 'boolean',
        'carry_forward' => 'boolean',
        'max_carry_forward_days' => 'integer',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Types outside the annual entitlement — compensatory leave is earned by
     * working a public holiday, maternity is granted case by case. Neither is
     * handed out up front.
     */
    public function isEarnedOnly(): bool
    {
        return ! $this->auto_allocate;
    }

    public const CPL_SLUG = 'cpl';
}
