<?php

namespace App\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\InteractsWithSockets;
// use Illuminate\Broadcasting\PresenceChannel;
// use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\Channel;

class ChatMessageEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $message;
    public $userName;
    public $id;
    public $customerId;
    public $typingMessage;
    public $onlineUserUpdated;
    public $engageUser;
    public $agentInfo;
    public $comments;
    public $userMessageStatusUpdate;
    public $messageType;
    public $onlineStatusUpdate;

    public function __construct(
        $userName=null,
        $message=null,
        $id=null,
        $customerId=null,
        $typingMessage=null,
        $onlineUserUpdated=null,
        $engageUser=null,
        $agentInfo=null,
        $comments=null,
        $userMessageStatusUpdate=null,
        $messageType=null,
        $onlineStatusUpdate=null
        )
    {
        $this->userName = $userName;
        $this->message = $message;
        $this->id = $id;
        $this->customerId = $customerId;
        $this->typingMessage = $typingMessage;
        $this->onlineUserUpdated = $onlineUserUpdated;
        $this->engageUser = $engageUser;
        $this->agentInfo = $agentInfo;
        $this->comments = $comments;
        $this->userMessageStatusUpdate = $userMessageStatusUpdate;
        $this->messageType = $messageType;
        $this->onlineStatusUpdate = $onlineStatusUpdate;
    }

    public function broadcastOn()
    {
        // return new PrivateChannel("livechat.{$this->customerId}");
        return new Channel('liveChat');
    }
    
}
