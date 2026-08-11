<?php

namespace App\Models;

use App\Traits\ScopesData;
use Illuminate\Database\Eloquent\Model;

/**
 * A milestone on an employee's record: a salary increment, a promotion, or
 * confirmation off probation.
 */
class EmployeeCareerEvent extends Model
{
    use ScopesData;

    public const TYPE_INCREMENT = 'increment';
    public const TYPE_PROMOTION = 'promotion';
    public const TYPE_CONFIRMATION = 'confirmation';

    protected $fillable = [
        'employee_id',
        'type',
        'effective_date',
        'previous_salary',
        'new_salary',
        'previous_position',
        'new_position',
        'notes',
        'recorded_by',
        'branch_id',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'previous_salary' => 'decimal:2',
        'new_salary' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * How much the salary went up by, and by what share of the old salary.
     *
     * @return array{amount: float, percent: float|null}
     */
    public function increase(): array
    {
        $previous = (float) $this->previous_salary;
        $new = (float) $this->new_salary;
        $amount = $new - $previous;

        return [
            'amount' => $amount,
            'percent' => $previous > 0 ? round(($amount / $previous) * 100, 1) : null,
        ];
    }

    public function label(): string
    {
        return match ($this->type) {
            self::TYPE_INCREMENT => 'Salary Increment',
            self::TYPE_PROMOTION => 'Promotion',
            self::TYPE_CONFIRMATION => 'Confirmation',
            default => ucfirst($this->type),
        };
    }
}
