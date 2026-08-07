<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaveStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $employeeId;
    public $status;
    public $reason;

    public function __construct($employeeId, $status, $reason = null)
    {
        $this->employeeId = $employeeId;
        $this->status = $status;
        $this->reason = $reason;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('employee.' . $this->employeeId . '.leaves');
    }

    public function broadcastAs()
    {
        return 'leave.status.updated';
    }
}
