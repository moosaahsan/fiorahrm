<?php

namespace App\Http\Controllers\Api;

use App\Models\Employee;
use App\Services\LateArrivalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives fingerprint punches forwarded by the local device-bridge script
 * (tools/device-bridge/app.py) from the ZKTeco IN/OUT devices and records
 * them as real check-ins/check-outs using the same rules as the employee
 * app (LateArrivalService).
 *
 * Not user-authenticated — the bridge is a machine on the local network,
 * not a logged-in person — so it's protected by a shared secret header
 * instead of a Sanctum token.
 */
class DevicePunchController extends BaseController
{
    /** Scans older than this are refused instead of being back-filled. */
    private const MAX_PUNCH_AGE_DAYS = 7;

    public function __construct(private LateArrivalService $lateArrivalService)
    {
    }

    public function store(Request $request)
    {
        $expectedSecret = config('services.device_bridge.secret');
        if (empty($expectedSecret) || $request->header('X-Device-Bridge-Secret') !== $expectedSecret) {
            return $this->sendError('Unauthorized.', [], 401);
        }

        $validated = $request->validate([
            'device' => 'required|in:IN,OUT',
            'device_user_id' => 'required|string',
            // When the finger was really placed on the device, in the app's timezone.
            // Optional: without it the punch is recorded at the moment it arrives.
            'punched_at' => 'nullable|date_format:Y-m-d H:i:s',
        ]);

        $employee = Employee::where('device_user_id', $validated['device_user_id'])->first();
        if (!$employee) {
            return $this->sendError("No employee is mapped to device user id '{$validated['device_user_id']}'.", [], 404);
        }

        $at = null;
        if (!empty($validated['punched_at'])) {
            $timezone = get_app_settings('app_timezone') ?? 'Asia/Karachi';
            $at = Carbon::createFromFormat('Y-m-d H:i:s', $validated['punched_at'], $timezone);

            if ($at->gt(Carbon::now($timezone)->addMinutes(5))) {
                return $this->sendError('Punch time is in the future (check the device clock).', ['employee' => $employee->name], 422);
            }
            if ($at->lt(Carbon::now($timezone)->subDays(self::MAX_PUNCH_AGE_DAYS))) {
                return $this->sendError('Punch is too old to be recorded automatically.', ['employee' => $employee->name], 422);
            }
        }

        try {
            if ($validated['device'] === 'IN') {
                // A fingerprint device has no way to type a reason, and a late check-in is
                // rejected without one, so late scans get this default reason.
                $data = $this->lateArrivalService->performCheckIn($employee->id, 'Marked via fingerprint device', $at);
            } else {
                $data = $this->lateArrivalService->performCheckOut($employee->id, $at);
            }

            return $this->sendResponse([
                'employee' => $employee->name,
            ], $data['message'] ?? 'Punch recorded.');
        } catch (\Exception $e) {
            // Expected on repeat scans (device fires live events for every
            // touch, not just new ones) — e.g. "Already checked in for this
            // shift." The bridge shows this to whoever is watching the page,
            // it isn't a real failure.
            Log::info('Device punch not applied: ' . $e->getMessage(), [
                'employee_id' => $employee->id,
                'device' => $validated['device'],
            ]);
            return $this->sendError($e->getMessage(), ['employee' => $employee->name], 422);
        }
    }
}
