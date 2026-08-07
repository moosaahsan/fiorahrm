<?php
namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log($action, $module, $description)
    {
        $user = Auth::user();
        $userName = $user ? $user->name : 'System';

        ActivityLog::create([
            'user_id' => $user->id ?? null,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Format activity log message with HTML highlights.
     *
     * @param string $action        // e.g. created, updated, deleted, activated, deactivated
     * @param string $moduleName    // e.g. Shift, LateArrival
     * @param string $itemName      // e.g. Morning Shift, Employee Name
     * @param int|string $itemId    // The ID of the item
     * @return string
     */
    public static function format(string $action, string $moduleName, string $itemName, $itemId): string
    {
        $user = Auth::user();
        $userName = $user ? $user->name : 'System';

        $actionColors = [
            'created' => '#28a745',
            'updated' => '#ffc107',
            'deleted' => '#dc3545',
            'activated' => '#28a745',
            'deactivated' => '#dc3545',
            'status_toggle' => '#17a2b8',
            'edit' => '#007bff',
        ];

        $color = $actionColors[$action] ?? '#6c757d'; // default grey

        return "{$moduleName} <span style='font-weight:bold; color:#007bff;'>{$itemName}</span> 
        (ID: <span style='color:#28a745;'>{$itemId}</span>) was 
        <span style='font-weight:bold; color:{$color};'>" . ucfirst($action) . "</span> 
        by <span style='font-weight:bold; color:#6f42c1;'>{$userName}</span>";
    }


}
