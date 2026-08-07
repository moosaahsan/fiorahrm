<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeBreakUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $employeeId;
    public $breakData;

    public function __construct($employeeId, $breakData)
    {
        $this->employeeId = $employeeId;
        $this->breakData = $breakData;
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('employee.' . $this->employeeId . '.breaks')];
    }

    public function broadcastAs(): string
    {
        return 'break.updated';
    }

    public function broadcastWith(): array
    {
        return $this->breakData;
    }
}