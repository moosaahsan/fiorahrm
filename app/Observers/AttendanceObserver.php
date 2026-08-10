<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Services\CompensatoryLeaveService;
use Illuminate\Support\Facades\Log;

/**
 * Watches attendance so working a public holiday earns compensatory leave,
 * no matter how the record got there — HR manual entry, an import, or a check-in.
 */
class AttendanceObserver
{
    public function created(Attendance $attendance): void
    {
        $this->recordCompensatoryLeave($attendance);
    }

    public function updated(Attendance $attendance): void
    {
        // A record only becomes eligible once it has a check-in and a worked
        // status, which HR often fills in after the fact.
        if ($attendance->wasChanged(['check_in', 'status', 'shift_date'])) {
            $this->recordCompensatoryLeave($attendance);
        }
    }

    /**
     * CPL detection must never block saving attendance — the attendance record
     * is the source of truth, the credit can be picked up later by `cpl:scan`.
     */
    protected function recordCompensatoryLeave(Attendance $attendance): void
    {
        try {
            CompensatoryLeaveService::recordForAttendance($attendance);
        } catch (\Throwable $e) {
            Log::error('Failed to record compensatory leave for attendance', [
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
