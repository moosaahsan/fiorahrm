<?php

namespace App\Models;

use App\Traits\ScopesData;
use Illuminate\Database\Eloquent\Model;

/**
 * A compensatory leave credit earned by working on a public holiday.
 *
 * Spending the credit is an ordinary Leave record with leave_type = 'cpl';
 * this model only records how the balance was earned and who approved it.
 */
class CompensatoryLeave extends Model
{
    use ScopesData;

    public const STATUS_PENDING = 'Pending';
    public const STATUS_APPROVED = 'Approved';
    public const STATUS_REJECTED = 'Rejected';
    public const STATUS_CANCELLED = 'Cancelled';

    protected $fillable = [
        'employee_id',
        'attendance_id',
        'company_off_day_id',
        'worked_date',
        'holiday_title',
        'days_earned',
        'status',
        'approved_by',
        'approved_at',
        'is_credited',
        'expires_at',
        'notes',
        'branch_id',
    ];

    protected $casts = [
        'worked_date' => 'date',
        'expires_at' => 'date',
        'approved_at' => 'datetime',
        'days_earned' => 'decimal:2',
        'is_credited' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function holiday()
    {
        return $this->belongsTo(CompanyOffDay::class, 'company_off_day_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
