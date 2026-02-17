<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RiderOrdered implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $riderId;
    public $clientId;
    public $clientName;
    public $serviceType;

    public function __construct($riderId, $clientId, $clientName, $serviceType)
    {
        $this->riderId = $riderId;
        $this->clientId = $clientId;
        $this->clientName = $clientName;
        $this->serviceType = $serviceType;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('rider.' . $this->riderId),
        ];
    }

    public function broadcastAs()
    {
        return 'rider.ordered';
    }
}
