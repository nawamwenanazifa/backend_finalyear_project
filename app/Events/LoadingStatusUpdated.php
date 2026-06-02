<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoadingStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public $status;
    
    public function __construct($status)
    {
        $this->status = $status;
    }
    
    public function broadcastOn()
    {
        return new Channel('loading-status.' . $this->status['operation_id']);
    }
    
    public function broadcastAs()
    {
        return 'loading-status.updated';
    }
}