<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use App\Traits\HandlesManualAttendance;

class ImportAttendanceController extends Controller
{
    use HandlesManualAttendance;

    public function template()
    {
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=attendance_import_template.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Employee Name', 'Date', 'Check-in Time', 'Check-out Time', 'Work Status'];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            // Add a sample row
            fputcsv($file, ['John Smith', '2026-07-01', '09:00', '18:00', 'Present']);
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function modal()
    {
        abort_if(!auth()->user()->can('add-manual-attendance'), 403);
        return view('admin.attendance.modals.import_entry');
    }

    public function store(Request $request)
    {
        abort_if(!auth()->user()->can('add-manual-attendance'), 403);

        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt|max:5120' // 5MB max
        ]);

        $file = $request->file('import_file');
        $filePath = $file->getRealPath();

        $data = array_map('str_getcsv', file($filePath));
        if (count($data) < 2) {
            return response()->json(['success' => false, 'message' => 'The uploaded file is empty or missing data rows.']);
        }

        // Extract headers and strip BOM
        $headers = array_map(function($h) {
            // Remove BOM and any non-printable characters from headers
            $cleaned = preg_replace('/[\xef\xbb\xbf]/', '', $h);
            return trim($cleaned);
        }, $data[0]);
        unset($data[0]);

        $expectedHeaders = ['Employee Name', 'Date', 'Check-in Time', 'Check-out Time', 'Work Status'];
        foreach ($expectedHeaders as $expected) {
            if (!in_array($expected, $headers)) {
                return response()->json(['success' => false, 'message' => "Invalid template format. Missing column: {$expected}"]);
            }
        }

        $successCount = 0;
        $skippedCount = 0;
        $failedCount = 0;
        $errors = [];

        // Pre-fetch active employees for mapping
        $employees = Employee::accessible()->whereNull('resign_date')->get();

        // --- PRE-FLIGHT CHECK: Ensure all employees exist before importing anything ---
        $missingEmployees = [];
        foreach ($data as $index => $row) {
            if (count($headers) !== count($row)) continue;
            
            $rowData = array_combine($headers, array_map('trim', $row));
            $empName = $rowData['Employee Name'];
            
            if (empty($empName)) continue;

            $employee = $employees->first(function($emp) use ($empName) {
                return strtolower(trim($emp->name)) === strtolower($empName) || 
                       str_starts_with(strtolower(trim($emp->name)), strtolower($empName)) ||
                       strtolower(trim($emp->employee_id)) === strtolower($empName);
            });

            if (!$employee && !in_array($empName, $missingEmployees)) {
                $missingEmployees[] = $empName;
            }
        }

        if (count($missingEmployees) > 0) {
            $missingList = implode(', ', $missingEmployees);
            $msg = "Import Aborted! The following employees do not exist in the system: {$missingList}. Please create them first before importing.";
            return response()->json([
                'success' => false, 
                'message' => 'Import Aborted',
                'errors' => [$msg]
            ]);
        }
        // --- END PRE-FLIGHT CHECK ---

        foreach ($data as $index => $row) {
            if (count($headers) !== count($row)) {
                $failedCount++;
                $errors[] = "Row " . ($index + 1) . ": Column count mismatch.";
                continue;
            }

            $rowData = array_combine($headers, array_map('trim', $row));
            
            $empName = $rowData['Employee Name'] ?? '';
            $dateStr = $rowData['Date'] ?? '';
            $checkInStr = $rowData['Check-in Time'] ?? '';
            $checkOutStr = $rowData['Check-out Time'] ?? '';
            $statusStr = ucfirst(strtolower($rowData['Work Status'] ?? ''));

            // Ignore completely empty rows (common in Excel exports)
            if (empty($empName) && empty($dateStr) && empty($statusStr)) {
                continue;
            }

            if (empty($empName) || empty($dateStr) || empty($statusStr)) {
                $failedCount++;
                $errors[] = "Row " . ($index + 1) . ": Missing required fields.";
                continue;
            }

            // Find employee (Support exact match or StartsWith to handle Excel truncation/partial copy)
            $employee = $employees->first(function($emp) use ($empName) {
                return strtolower(trim($emp->name)) === strtolower($empName) || 
                       str_starts_with(strtolower(trim($emp->name)), strtolower($empName)) ||
                       strtolower(trim($emp->employee_id)) === strtolower($empName);
            });

            if (!$employee) {
                $failedCount++;
                $errors[] = "Row " . ($index + 1) . ": Employee '{$empName}' not found or resigned.";
                continue;
            }

            // Parse Date gracefully (handles DD/MM/YYYY, D/M/YYYY, and YYYY-MM-DD)
            try {
                // Replace dashes with slashes to normalize
                $normalizedDate = str_replace('-', '/', $dateStr);
                
                // Match D/M/YYYY or DD/MM/YYYY
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $normalizedDate, $matches)) {
                    $shiftDate = Carbon::createFromFormat('d/m/Y', $matches[1].'/'.$matches[2].'/'.$matches[3])->toDateString();
                } else {
                    $shiftDate = Carbon::parse($dateStr)->toDateString();
                }
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = "Row " . ($index + 1) . ": Invalid date format '{$dateStr}'.";
                continue;
            }

            // Skip Weekends automatically so the system naturally displays "Weekend"
            if (Carbon::parse($shiftDate)->isWeekend()) {
                $skippedCount++;
                continue;
            }

            // Allowed statuses
            $allowedStatuses = ['Present', 'Absent', 'Half Day', 'Late', 'Absent (unpaid)'];
            $statusMatched = false;
            foreach ($allowedStatuses as $allowed) {
                if (strtolower($allowed) === strtolower($statusStr)) {
                    $statusStr = $allowed;
                    $statusMatched = true;
                    break;
                }
            }

            if (!$statusMatched) {
                $failedCount++;
                $errors[] = "Row " . ($index + 1) . ": Invalid Work Status '{$statusStr}'.";
                continue;
            }

            $assignment = \App\Models\EmployeeShift::where('emp_id', $employee->id)
                ->whereDate('assigned_at', '<=', $shiftDate)
                ->orderBy('assigned_at', 'desc')
                ->first();
                
            $currentAssignment = \App\Models\EmployeeShift::where('emp_id', $employee->id)
                ->orderBy('assigned_at', 'desc')
                ->first();
                
            $shift = $assignment ? $assignment->shift : ($currentAssignment ? $currentAssignment->shift : Shift::where('is_active', 1)->first());
            
            if (!$shift) {
                $failedCount++;
                $errors[] = "Row " . ($index + 1) . ": No active shift found for employee.";
                continue;
            }

            // Check if record exists so we can update it instead of skipping
            $attendance = Attendance::where('emp_id', $employee->id)
                ->whereDate('shift_date', $shiftDate)
                ->first();

            if (!$attendance) {
                $attendance = new Attendance();
            }

            // Process saving (Transaction per row so failures don't ruin the whole batch if they are unrelated)
            DB::beginTransaction();
            try {
                $tz = get_employee_settings($employee->id, 'time_zone') ?? 'Asia/Karachi';
                
                $attendance->emp_id = $employee->id;
                $attendance->shift_id = $shift->id;
                $attendance->shift_date = $shiftDate;
                $attendance->status = ($statusStr === 'Absent' || $statusStr === 'Absent (Unpaid)') ? 'Absent' : 'Present';
                $attendance->is_manual = true;
                $attendance->modified_by = auth()->id();

                if ($statusStr !== 'Absent' && $statusStr !== 'Absent (Unpaid)') {
                    if (!empty($checkInStr)) {
                        $attendance->check_in = Carbon::parse($shiftDate . ' ' . $checkInStr, $tz);
                    }
                    if (!empty($checkOutStr)) {
                        $attendance->check_out = Carbon::parse($shiftDate . ' ' . $checkOutStr, $tz);
                        // Overnight logic
                        if ($attendance->check_in && $attendance->check_out && $attendance->check_out->lt($attendance->check_in)) {
                            $attendance->check_out->addDay();
                        }
                    }
                } else {
                    $attendance->check_in = null;
                    $attendance->check_out = null;
                }

                // Late Calculation
                $gracePeriod = (int) (get_employee_settings($employee->id, 'late_grace_minutes') ?? 5);
                if (($statusStr === 'Late' || $statusStr === 'Present') && $attendance->check_in) {
                    $shiftStart = Carbon::parse($shiftDate . ' ' . $shift->start_time, $tz);
                    if ($attendance->check_in->gt($shiftStart->copy()->addMinutes($gracePeriod))) {
                        $attendance->late_duration = $shiftStart->diffInMinutes($attendance->check_in);
                    } else {
                        $attendance->late_duration = 0;
                    }
                } else {
                    $attendance->late_duration = 0;
                }

                $attendance->save();

                $reason = "Bulk Import";
                if ($statusStr === 'Late' && $attendance->late_duration > 0) {
                    $this->createLateRecord($attendance, $shift, $reason);
                } elseif ($statusStr === 'Half Day') {
                    $this->createHalfDayRecord($attendance, $shift, $reason);
                } elseif ($statusStr === 'Absent') {
                    $this->handleAbsentDeduction($employee->id, $shiftDate, $shift, $reason);
                } elseif ($statusStr === 'Absent (Unpaid)') {
                    $this->handleUnpaidAbsentRecord($employee->id, $shiftDate, $shift, $reason);
                }

                if ($statusStr === 'Present' || $statusStr === 'Late') {
                    $this->handlePresentRecord($employee->id, $shiftDate);
                }

                DB::commit();
                $successCount++;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Import row failed: ' . $e->getMessage());
                $failedCount++;
                $errors[] = "Row " . ($index + 1) . ": Database error -> " . $e->getMessage();
            }
        }

        $message = "Import completed. Successfully imported: $successCount.";
        if ($skippedCount > 0) {
            $message .= " Skipped (Duplicates): $skippedCount.";
        }
        if ($failedCount > 0) {
            $message .= " Failed: $failedCount.";
        }

        return response()->json([
            'success' => $failedCount === 0,
            'message' => $message,
            'errors' => $errors
        ]);
    }
}
