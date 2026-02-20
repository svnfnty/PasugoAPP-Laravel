<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MissionCancelled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $clientId;
    public $riderId;
    public $riderName;
    public $orderId;
    public $reason;

    public function __construct($clientId, $riderId, $riderName, $orderId, $reason = 'Rider cancelled the mission')
    {
        $this->clientId = $clientId;
        $this->riderId = $riderId;
        $this->riderName = $riderName;
        $this->orderId = $orderId;
        $this->reason = $reason;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('client.' . $this->clientId),
            new Channel('chat.client.' . $this->clientId),
        ];
    }

    public function broadcastAs()
    {
        return 'mission.cancelled';
    }
}
