<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One physical fingerprint scan forwarded by the device bridge, with what HRM did with it.
 */
class DevicePunchLog extends Model
{
    protected $fillable = [
        'device',
        'device_user_id',
        'device_user_name',
        'scan_time',
        'employee_id',
        'employee_name',
        'ok',
        'result',
        'source',
    ];

    protected $casts = [
        'scan_time' => 'datetime',
        'ok' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
