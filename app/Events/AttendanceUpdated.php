<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $employeeId;
    public $attendanceData;

    public function __construct($employeeId, $attendanceData)
    {
        $this->employeeId = $employeeId;
        $this->attendanceData = $attendanceData;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('employee.' . $this->employeeId . '.attendance');
    }

    public function broadcastAs()
    {
        return 'attendance.updated';
    }
}
