<?php

namespace App\Http\Controllers\Api;

use App\Models\DevicePunchLog;
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
 *
 * Every scan (accepted or rejected) is also written to device_punch_logs so
 * it can be reviewed on the admin "Device Scans" page.
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
            // The name stored on the device for this user, and whether the bridge is
            // forwarding the scan live or catching up on an old one. Only used for the log.
            'device_user_name' => 'nullable|string|max:255',
            'source' => 'nullable|in:live,catch-up',
        ]);

        $timezone = get_app_settings('app_timezone') ?? 'Asia/Karachi';
        $at = !empty($validated['punched_at'])
            ? Carbon::createFromFormat('Y-m-d H:i:s', $validated['punched_at'], $timezone)
            : null;

        [$status, $message, $employee] = $this->process($validated, $at, $timezone);

        $this->writeLog($validated, $at ?? Carbon::now($timezone), $employee, $status === 200, $message);

        if ($status === 200) {
            return $this->sendResponse(['employee' => $employee->name], $message);
        }

        return $this->sendError($message, $employee ? ['employee' => $employee->name] : [], $status);
    }

    /**
     * Applies the scan. Returns [http status, message, employee|null].
     */
    private function process(array $validated, ?Carbon $at, string $timezone): array
    {
        $employee = Employee::where('device_user_id', $validated['device_user_id'])->first();
        if (!$employee) {
            return [404, "No employee is mapped to device user id '{$validated['device_user_id']}'.", null];
        }

        if ($at) {
            if ($at->gt(Carbon::now($timezone)->addMinutes(5))) {
                return [422, 'Punch time is in the future (check the device clock).', $employee];
            }
            if ($at->lt(Carbon::now($timezone)->subDays(self::MAX_PUNCH_AGE_DAYS))) {
                return [422, 'Punch is too old to be recorded automatically.', $employee];
            }
        }

        // Until the real shifts are assigned, the shift on file is just the default from the
        // employee import, so late/half-day/window rules would be judged against a wrong
        // schedule. Scans are then recorded as plain check-in/check-out times.
        // Flip DEVICE_BRIDGE_ENFORCE_SHIFT_RULES=true in .env once the shifts are in place.
        $skipShiftRules = ! config('services.device_bridge.enforce_shift_rules');

        try {
            if ($validated['device'] === 'IN') {
                // A fingerprint device has no way to type a reason, and a late check-in is
                // rejected without one, so late scans get this default reason.
                $data = $this->lateArrivalService->performCheckIn($employee->id, 'Marked via fingerprint device', $at, $skipShiftRules);
            } else {
                $data = $this->lateArrivalService->performCheckOut($employee->id, $at, $skipShiftRules);
            }

            return [200, $data['message'] ?? 'Punch recorded.', $employee];
        } catch (\Exception $e) {
            // Expected on repeat scans (device fires live events for every
            // touch, not just new ones) — e.g. "Already checked in for this
            // shift." The bridge shows this to whoever is watching the page,
            // it isn't a real failure.
            Log::info('Device punch not applied: ' . $e->getMessage(), [
                'employee_id' => $employee->id,
                'device' => $validated['device'],
            ]);

            return [422, $e->getMessage(), $employee];
        }
    }

    /**
     * Keeps one row per physical scan. A scan that was already recorded successfully is never
     * downgraded by a later "Already checked in" reply when the bridge re-sends it.
     * A logging problem must never stop the punch itself, so failures are only logged.
     */
    private function writeLog(array $validated, Carbon $scanTime, ?Employee $employee, bool $ok, string $message): void
    {
        try {
            $key = [
                'device' => $validated['device'],
                'device_user_id' => $validated['device_user_id'],
                'scan_time' => $scanTime->format('Y-m-d H:i:s'),
            ];

            $existing = DevicePunchLog::where($key)->first();
            if ($existing && $existing->ok) {
                return;
            }

            DevicePunchLog::updateOrCreate($key, [
                'device_user_name' => $validated['device_user_name'] ?? ($existing->device_user_name ?? null),
                'employee_id' => $employee?->id,
                'employee_name' => $employee?->name,
                'ok' => $ok,
                'result' => mb_substr($message, 0, 500),
                'source' => $validated['source'] ?? 'live',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Could not write device punch log: ' . $e->getMessage());
        }
    }
}
