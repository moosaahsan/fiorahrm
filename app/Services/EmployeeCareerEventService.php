<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeCareerEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Records career milestones and applies them to the employee record.
 *
 * Recording is not just bookkeeping — an increment moves the salary, a
 * promotion moves the designation, a confirmation ends probation. The event row
 * keeps what was replaced, so the history reads correctly even after the
 * employee record has moved on.
 */
class EmployeeCareerEventService
{
    /**
     * Give a salary increment.
     *
     * @throws \InvalidArgumentException when the new salary is not an increase
     */
    public static function recordIncrement(
        Employee $employee,
        float $newSalary,
        $effectiveDate,
        ?int $recordedBy = null,
        ?string $notes = null,
    ): EmployeeCareerEvent {
        $previous = (float) $employee->salary;

        if ($newSalary <= $previous) {
            throw new \InvalidArgumentException(
                'The new salary must be higher than the current salary of ' . number_format($previous, 2) . '.'
            );
        }

        return DB::transaction(function () use ($employee, $newSalary, $previous, $effectiveDate, $recordedBy, $notes) {
            $event = EmployeeCareerEvent::create([
                'employee_id' => $employee->id,
                'type' => EmployeeCareerEvent::TYPE_INCREMENT,
                'effective_date' => Carbon::parse($effectiveDate)->toDateString(),
                'previous_salary' => $previous,
                'new_salary' => $newSalary,
                'notes' => $notes,
                'recorded_by' => $recordedBy,
                'branch_id' => $employee->branch_id,
            ]);

            $employee->salary = $newSalary;
            $employee->save();

            return $event;
        });
    }

    /**
     * Promote an employee to a new designation, optionally with a raise.
     *
     * @throws \InvalidArgumentException when the designation has not changed
     */
    public static function recordPromotion(
        Employee $employee,
        string $newPosition,
        $effectiveDate,
        ?float $newSalary = null,
        ?int $recordedBy = null,
        ?string $notes = null,
    ): EmployeeCareerEvent {
        $previousPosition = (string) $employee->position;

        if (trim($newPosition) === '' || trim($newPosition) === trim($previousPosition)) {
            throw new \InvalidArgumentException('The new designation must be different from the current one.');
        }

        $previousSalary = (float) $employee->salary;
        $raises = $newSalary !== null && $newSalary > $previousSalary;

        return DB::transaction(function () use (
            $employee, $newPosition, $previousPosition, $effectiveDate,
            $newSalary, $previousSalary, $raises, $recordedBy, $notes
        ) {
            $event = EmployeeCareerEvent::create([
                'employee_id' => $employee->id,
                'type' => EmployeeCareerEvent::TYPE_PROMOTION,
                'effective_date' => Carbon::parse($effectiveDate)->toDateString(),
                'previous_position' => $previousPosition,
                'new_position' => $newPosition,
                // Only recorded when the promotion actually carried a raise.
                'previous_salary' => $raises ? $previousSalary : null,
                'new_salary' => $raises ? $newSalary : null,
                'notes' => $notes,
                'recorded_by' => $recordedBy,
                'branch_id' => $employee->branch_id,
            ]);

            $employee->position = $newPosition;

            if ($raises) {
                $employee->salary = $newSalary;
            }

            $employee->save();

            return $event;
        });
    }

    /**
     * Confirm an employee off probation.
     *
     * @throws \InvalidArgumentException when they are already confirmed
     */
    public static function recordConfirmation(
        Employee $employee,
        $effectiveDate,
        ?int $recordedBy = null,
        ?string $notes = null,
    ): EmployeeCareerEvent {
        if ($employee->confirmed_at) {
            throw new \InvalidArgumentException(
                'This employee was already confirmed on ' . $employee->confirmed_at->format('d M Y') . '.'
            );
        }

        return DB::transaction(function () use ($employee, $effectiveDate, $recordedBy, $notes) {
            $date = Carbon::parse($effectiveDate)->toDateString();

            $event = EmployeeCareerEvent::create([
                'employee_id' => $employee->id,
                'type' => EmployeeCareerEvent::TYPE_CONFIRMATION,
                'effective_date' => $date,
                'notes' => $notes,
                'recorded_by' => $recordedBy,
                'branch_id' => $employee->branch_id,
            ]);

            $employee->confirmed_at = $date;
            $employee->save();

            return $event;
        });
    }

    /**
     * Undo an event, putting back what it replaced.
     *
     * Restoring the old salary or designation is only correct while nothing
     * newer has moved the same field — and a promotion can carry a raise, so the
     * check follows the fields, not the record type. Undo the newer record first.
     *
     * @throws \InvalidArgumentException when a newer event moved the same field
     */
    public static function undo(EmployeeCareerEvent $event): void
    {
        $employee = $event->employee;

        if (! $employee) {
            $event->delete();

            return;
        }

        $blocker = EmployeeCareerEvent::where('employee_id', $event->employee_id)
            ->where('id', '!=', $event->id)
            ->where(function ($query) use ($event) {
                // Newer than this one, breaking ties on id.
                $query->where('effective_date', '>', $event->effective_date)
                    ->orWhere(function ($q) use ($event) {
                        $q->where('effective_date', $event->effective_date)
                            ->where('id', '>', $event->id);
                    });
            })
            ->where(function ($query) use ($event) {
                $touchesNothing = true;

                if ($event->new_salary !== null) {
                    $query->orWhereNotNull('new_salary');
                    $touchesNothing = false;
                }

                if ($event->new_position !== null) {
                    $query->orWhereNotNull('new_position');
                    $touchesNothing = false;
                }

                if ($event->type === EmployeeCareerEvent::TYPE_CONFIRMATION || $touchesNothing) {
                    $query->orWhere('type', $event->type);
                }
            })
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->first();

        if ($blocker) {
            throw new \InvalidArgumentException(
                'A newer record (' . $blocker->label() . ' on ' . $blocker->effective_date->format('d M Y')
                . ') changed the same details. Remove that one first.'
            );
        }

        DB::transaction(function () use ($event, $employee) {
            if ($event->previous_salary !== null) {
                $employee->salary = $event->previous_salary;
            }

            if ($event->previous_position !== null) {
                $employee->position = $event->previous_position;
            }

            if ($event->type === EmployeeCareerEvent::TYPE_CONFIRMATION) {
                $employee->confirmed_at = null;
            }

            $employee->save();
            $event->delete();
        });
    }
}
