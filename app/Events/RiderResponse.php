<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RiderResponse implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $clientId;
    public $riderId;
    public $riderName;
    public $decision; // accept, decline
    public $serviceType;
    public $orderId;

    public function __construct($clientId, $riderId, $riderName, $decision, $serviceType = 'order', $orderId = null)
    {
        $this->clientId = $clientId;
        $this->riderId = $riderId;
        $this->riderName = $riderName;
        $this->decision = $decision;
        $this->serviceType = $serviceType;
        $this->orderId = $orderId;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('client.' . $this->clientId),
        ];
    }

    public function broadcastAs()
    {
        return 'rider.responded';
    }
}
