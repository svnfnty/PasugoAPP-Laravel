<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $senderId;
    public $receiverId;
    public $message;
    public $senderType; // 'client' or 'rider'
    public $orderId;
    public $type;
    public $locationData;

    public function __construct($senderId, $receiverId, $message, $senderType, $orderId = null, $type = 'text', $locationData = null)
    {
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
        $this->message = $message;
        $this->senderType = $senderType;
        $this->orderId = $orderId;
        $this->type = $type;
        $this->locationData = $locationData;
    }

    public function broadcastOn(): array
    {
        $receiverType = ($this->senderType === 'client') ? 'rider' : 'client';

        return [
            // The receiver listens here
            new Channel('chat.' . $receiverType . '.' . $this->receiverId),
            // The sender listens here (echo back if needed, but we handle locally mostly)
            new Channel('chat.' . $this->senderType . '.' . $this->senderId),
        ];
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }
}
