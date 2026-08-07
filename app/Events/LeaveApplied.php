<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaveApplied implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $leave;
    public $employeeName;

    public function __construct($leave, $employeeName)
    {
        $this->leave = $leave;
        $this->employeeName = $employeeName;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('admin-notifications');
    }

    public function broadcastAs()
    {
        return 'leave.applied';
    }
}
