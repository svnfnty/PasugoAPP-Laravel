<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RiderRequestCancelled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $riderId;
    public $clientId;

    public function __construct($riderId, $clientId)
    {
        $this->riderId = $riderId;
        $this->clientId = $clientId;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('rider.' . $this->riderId),
        ];
    }

    public function broadcastAs()
    {
        return 'rider.cancelled';
    }
}
