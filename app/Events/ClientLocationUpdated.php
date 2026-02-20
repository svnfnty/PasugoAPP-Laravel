<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $clientId;
    public $lat;
    public $lng;

    public function __construct($clientId, $lat, $lng)
    {
        $this->clientId = $clientId;
        $this->lat = $lat;
        $this->lng = $lng;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('client.location.' . $this->clientId),
        ];
    }

    public function broadcastAs()
    {
        return 'client.location.updated';
    }
}
