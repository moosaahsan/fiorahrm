<?php

namespace App\Models;

use App\Traits\ScopesData;
use Illuminate\Database\Eloquent\Model;

/**
 * A year-end payout of unused leave.
 *
 * The days are taken out of the employee's leave balance when the cashout is
 * created; the money reaches them through the payroll run named by
 * payroll_month / payroll_year.
 */
class LeaveCashout extends Model
{
    use ScopesData;

    public const STATUS_PENDING = 'Pending';
    public const STATUS_PAID = 'Paid';
    public const STATUS_CANCELLED = 'Cancelled';

    protected $fillable = [
        'employee_id',
        'year',
        'leave_type',
        'days',
        'amount',
        'status',
        'payroll_month',
        'payroll_year',
        'payroll_item_id',
        'paid_at',
        'processed_by',
        'notes',
        'branch_id',
    ];

    protected $casts = [
        'year' => 'integer',
        'days' => 'decimal:2',
        'amount' => 'decimal:2',
        'payroll_month' => 'integer',
        'payroll_year' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type', 'slug');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function payrollItem()
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePayable($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PAID]);
    }

    /**
     * Label used on the payslip.
     */
    public function payslipLabel(): string
    {
        $type = $this->leaveType?->name ?? ucfirst($this->leave_type);

        return "Leave Encashment — {$type} {$this->year}";
    }
}
