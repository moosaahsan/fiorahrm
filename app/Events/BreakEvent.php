<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BreakEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $employeeId;
    public $breakData;

    public function __construct($employeeId, $breakData)
    {
        $this->employeeId = $employeeId;
        $this->breakData = $breakData;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('employee.' . $this->employeeId . '.breaks');
    }

    public function broadcastAs()
    {
        return 'break.updated';
    }

    public function broadcastWith()
    {
        return $this->breakData;
    }
}
