<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RiderLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $riderId;
    public $name;
    public $bio;
    public $lat;
    public $lng;
    public $status;

    public function __construct($riderId, $lat, $lng, $status = 'available', $name = null, $bio = null)
    {
        $this->riderId = $riderId;
        $this->lat = $lat;
        $this->lng = $lng;
        $this->status = $status;
        $this->name = $name;
        $this->bio = $bio;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('riders'),
        ];
    }

    public function broadcastAs()
    {
        return 'rider.location.updated';
    }
}
