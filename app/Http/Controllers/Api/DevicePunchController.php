<?php

namespace App\Http\Controllers\Api;

use App\Models\Employee;
use App\Services\LateArrivalService;
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
        ]);

        $employee = Employee::where('device_user_id', $validated['device_user_id'])->first();
        if (!$employee) {
            return $this->sendError("No employee is mapped to device user id '{$validated['device_user_id']}'.", [], 404);
        }

        try {
            if ($validated['device'] === 'IN') {
                $data = $this->lateArrivalService->performCheckIn($employee->id);
            } else {
                $data = $this->lateArrivalService->performCheckOut($employee->id);
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
