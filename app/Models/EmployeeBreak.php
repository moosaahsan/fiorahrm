<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

use App\Traits\ScopesData;

class EmployeeBreak extends Model
{
    use HasFactory, ScopesData;
    
    /**
     * Boot the model for automatic branching.
     */
    protected static function booted()
    {
        static::saving(function ($break) {
            if (!$break->branch_id && $break->emp_id) {
                $employee = \App\Models\Employee::find($break->emp_id);
                if ($employee) {
                    $break->branch_id = $employee->branch_id;
                }
            }
        });
    }

    protected $fillable = [
        'emp_id',             // Employee ID (foreign key)
        'attendance_id',      // Link to attendance
        'shift_date',         // Shift date for easier querying
        'start_time',         // Break start time
        'end_time',           // Break end time
        'type',               // Type of break (Official / General)
        'status',             // Status of break (e.g., On break, Ended)
        'reason',             // Reason for taking the break
        'spent_minutes',      // Total break time spent (in minutes)
        'remaining_minutes',  // Remaining allowed break time
        'exceeded_minutes',   // Time exceeded beyond allowance
        'approved_by',        // User who approved the break
    ];

    protected $dates = ['start_time', 'end_time'];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getDurationAttribute()
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }

        $timezone = (function_exists('get_employee_settings') ? get_employee_settings($this->emp_id, 'time_zone') : null) 
                    ?? (app_settings('app_timezone') ?? 'Asia/Karachi');
        
        // Use shift_date or created_at as anchor
        $baseDate = $this->shift_date ? Carbon::parse($this->shift_date, $timezone) : Carbon::parse($this->created_at, $timezone);
        $start = $baseDate->copy();
        
        $st = Carbon::parse($this->start_time, $timezone);
        $start->setTime($st->hour, $st->minute, $st->second);

        $end = Carbon::parse($this->end_time, $timezone);
        
        // Midnight rollover check: If end date wasn't saved, but it's clearly after start
        if ($end->lt($start) && $end->format('Y-m-d') === $start->format('Y-m-d')) {
            $end->addDay();
        }

        return (int) $start->diffInMinutes($end);
    }

    // Scope for active break
    public function scopeActive($query)
    {
        return $query->whereNull('end_time')->where('status', 'On break');
    }

    // Scope for today's breaks
    public function scopeToday($query)
    {
        return $query->whereDate('start_time', now()->toDateString());
    }

    // Automatically mark the break as 'On Break' if no activity is detected.
    public static function autoMarkBreak($employeeId)
    {
        // Get the last break for the employee
        $lastBreak = self::where('emp_id', $employeeId)
            ->where('status', 'On break')
            ->orderBy('start_time', 'desc')
            ->first();

        // If no break is ongoing or the employee is not on break, auto start a new break
        if (!$lastBreak || $lastBreak->status === 'Ended') {
            $startTime = now();
            $endTime = $startTime->copy()->addMinutes(5); // Default to a 5-minute break for inactivity

            return self::create([
                'emp_id' => $employeeId,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'type' => 'General',  // Could be 'Official' depending on logic
                'status' => 'On break',
                'spent_minutes' => 5,
                'remaining_minutes' => 0,
                'exceeded_minutes' => 0,
            ]);
        }

        return null;
    }

    public function calculateBreakTimeInSeconds()
    {
        $timezone = (function_exists('get_employee_settings') ? get_employee_settings($this->emp_id, 'time_zone') : null) 
                    ?? (app_settings('app_timezone') ?? 'Asia/Karachi');
        
        $createdAt = Carbon::parse($this->created_at)->timezone($timezone);
        $start = $createdAt->copy();
        
        if ($this->start_time) {
            $st = Carbon::parse($this->start_time)->timezone($timezone);
            $start->setTime($st->hour, $st->minute, $st->second);
            
            if ($start->gt($createdAt->copy()->addMinutes(1))) { 
                $start->subDay();
            }
        }

        $end = $this->end_time ? Carbon::parse($this->end_time)->timezone($timezone) : now($timezone);
        
        if ($end->lt($start)) {
            $end->addDay();
        }
        
        return (int) $start->diffInSeconds($end);
    }

    public function calculateBreakTime()
    {
        $seconds = $this->calculateBreakTimeInSeconds();
        
        // Handle database storage for end_time if not set
        if (!$this->end_time) {
             $timezone = (function_exists('get_employee_settings') ? get_employee_settings($this->emp_id, 'time_zone') : null) 
                    ?? (app_settings('app_timezone') ?? 'Asia/Karachi');
             $this->end_time = now($timezone)->format('Y-m-d H:i:s');
        }
        
        return (int) round($seconds / 60);
    }

    // Update the break's status and calculate the spent minutes once the break ends.
    public function endBreak($data = [])
    {
        $timezone = (function_exists('get_employee_settings') ? get_employee_settings($this->emp_id, 'time_zone') : null) 
                    ?? (app_settings('app_timezone') ?? 'Asia/Karachi');
        $allowed = get_effective_break_minutes((int) $this->emp_id, $this->shift_date);
        
        if (!empty($data)) {
            $this->fill($data);
        }

        $this->status = 'Completed'; 
        $spent = $this->calculateBreakTime();
        $this->spent_minutes = $spent;
        
        // Calculate total spent for today including this break
        $todaySpent = EmployeeBreak::where('emp_id', $this->emp_id)
            ->where('shift_date', $this->shift_date)
            ->where('status', 'Completed')
            ->where('id', '!=', $this->id)
            ->sum('spent_minutes') + $spent;

        $this->remaining_minutes = max($allowed - $todaySpent, 0);
        $this->exceeded_minutes = max($todaySpent - $allowed, 0);
        
        $this->save();
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }

    /** Auto-generated by cron or desktop inactivity (not a manual break). */
    public function isAutoGenerated(): bool
    {
        if ($this->type !== 'General') {
            return false;
        }

        $reason = (string) ($this->reason ?? '');

        return str_starts_with($reason, 'Auto break:')
            || str_starts_with($reason, 'Auto-break');
    }

    public function resolveStartCarbon(?string $timezone = null): ?Carbon
    {
        if (!$this->start_time) {
            return null;
        }

        $timezone = $timezone
            ?? (function_exists('get_employee_settings') ? get_employee_settings($this->emp_id, 'time_zone') : null)
            ?? (app_settings('app_timezone') ?? 'Asia/Karachi');

        $base = Carbon::parse($this->shift_date ?? $this->created_at, $timezone);
        $st = Carbon::parse($this->start_time, $timezone);

        return $base->copy()->setTime($st->hour, $st->minute, $st->second);
    }

    public function resolveEndCarbon(?string $timezone = null): ?Carbon
    {
        if (!$this->end_time) {
            return null;
        }

        $timezone = $timezone
            ?? (function_exists('get_employee_settings') ? get_employee_settings($this->emp_id, 'time_zone') : null)
            ?? (app_settings('app_timezone') ?? 'Asia/Karachi');

        if (strlen((string) $this->end_time) <= 8) {
            $base = Carbon::parse($this->shift_date ?? $this->created_at, $timezone);
            $end = $base->copy()->setTimeFromTimeString((string) $this->end_time);
            $start = $this->resolveStartCarbon($timezone);
            if ($start && $end->lt($start)) {
                $end->addDay();
            }

            return $end;
        }

        return Carbon::parse($this->end_time, $timezone);
    }

    public static function breaksOverlap(self $a, self $b, ?string $timezone = null): bool
    {
        $aStart = $a->resolveStartCarbon($timezone);
        $bStart = $b->resolveStartCarbon($timezone);

        if (!$aStart || !$bStart) {
            return false;
        }

        $tz = $timezone
            ?? (function_exists('get_employee_settings') ? get_employee_settings($a->emp_id, 'time_zone') : null)
            ?? (app_settings('app_timezone') ?? 'Asia/Karachi');

        $aEnd = $a->resolveEndCarbon($tz) ?? Carbon::now($tz);
        $bEnd = $b->resolveEndCarbon($tz) ?? Carbon::now($tz);

        return $aStart->lt($bEnd) && $bStart->lt($aEnd);
    }

    /** Remove auto breaks that overlap a manual/official break on the same shift. */
    public static function purgeSupersededAutoBreaks(int $empId, string $shiftDate, ?int $keepBreakId = null): void
    {
        $tz = get_employee_settings($empId, 'time_zone') ?? 'Asia/Karachi';

        $manualBreaks = self::where('emp_id', $empId)
            ->where('shift_date', $shiftDate)
            ->where('status', 'Completed')
            ->when($keepBreakId, fn ($q) => $q->where('id', '!=', $keepBreakId))
            ->get()
            ->reject(fn (self $break) => $break->isAutoGenerated());

        if ($keepBreakId) {
            $kept = self::find($keepBreakId);
            if ($kept && !$kept->isAutoGenerated()) {
                $manualBreaks = $manualBreaks->push($kept);
            }
        }

        if ($manualBreaks->isEmpty()) {
            return;
        }

        self::where('emp_id', $empId)
            ->where('shift_date', $shiftDate)
            ->when($keepBreakId, fn ($q) => $q->where('id', '!=', $keepBreakId))
            ->get()
            ->filter(fn (self $break) => $break->isAutoGenerated())
            ->each(function (self $auto) use ($manualBreaks, $tz) {
                foreach ($manualBreaks as $manual) {
                    if (self::breaksOverlap($auto, $manual, $tz)) {
                        $auto->delete();
                        break;
                    }
                }
            });
    }

    /** Hide auto breaks superseded by an overlapping official/manual break in UI lists. */
    public static function filterForDisplay(iterable $breaks): \Illuminate\Support\Collection
    {
        $collection = collect($breaks)->values();
        $removeIndexes = [];

        foreach ($collection as $i => $auto) {
            if (!($auto instanceof self) || !$auto->isAutoGenerated()) {
                continue;
            }

            foreach ($collection as $j => $other) {
                if ($i === $j || !($other instanceof self) || $other->isAutoGenerated()) {
                    continue;
                }

                if ($other->type === 'Official' && self::breaksOverlap($auto, $other)) {
                    $removeIndexes[] = $i;
                    break;
                }
            }
        }

        return $collection->except(array_unique($removeIndexes))->values();
    }
}
